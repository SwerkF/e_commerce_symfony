<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Panier;
use App\Form\UserType;
use App\Form\UserPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    // Connexion
    #[Route(path: '/{_locale}/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
        // Redirection si un utilisateur est connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    // Déconnexion
    #[Route(path: '/{_locale}/logout', name: 'app_logout')]
    public function logout()
    {
        // Message flash
        $this->addFlash(
            'success',
            $translator->trans('register.alert.disconnected')
        );

        return $this->redirectToRoute('app_accueil');
    }

    // Consultation de notre profile
    #[Route(path: '/{_locale}/profile', name: 'app_profile')]
    public function profile(EntityManagerInterface $em,  TranslatorInterface $translator)
    {

        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Récupérer les commandes de l'utilisateur
        $commandes = $em->getRepository(Panier::class)->findBy(['utilisateur' => $this->getUser(), 'status'=>1]);

        return $this->render('security/profile.html.twig', ['user' => $profile, 'commandes' => $commandes]);
    }

    // Modification du profile
    #[Route(path: '/{_locale}/profile/edit', name: 'app_profile_edit')]
    public function edit(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $userPasswordHasher,  TranslatorInterface $translator)
    {
        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.forbidden')
            );

            return $this->redirectToRoute('app_login');
        }

        // Création du formulaire
        $form = $this->createForm(UserType::class, $profile);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Upload de l'image
            $imageFile = $form->get('profile_picture')->getData();

            if($imageFile)
            {
                // Generer un nom unique pour le fichier en fonction de son extension
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    // Suppression de l'image existante
                    $oldFilename = $profile->getProfilePicture();
                    if($oldFilename != null)
                    {
                        unlink(__DIR__.'/../../public/uploads/images/profile_pictures/'.$oldFilename);
                    }
                } catch (FileException $e) {
                    
                }

                $profile->setProfilePicture($newFilename);
            }

            // Enregistrement en base de données
            $em->persist($profile);
            $em->flush();

            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('register.alert.edited')
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/edit.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/users/admin', name: 'app_clientList')]
    public function listUsers(UserRepository $userRepository,  TranslatorInterface $translator)
    {

        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si l'utilisateur n'est pas connecté
        if($user == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Vérification des permissions
        if($user->getRoles()[0] != 'ROLE_SUPER_ADMIN')
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.forbidden')
            );

            return $this->redirectToRoute('app_produit_index');  
        }

        $users = $userRepository->findAll();

        return $this->render('panier/admin/showUser.html.twig', [
            'users' => $users,
        ]);
    }

    // Modification du mot de passe
    #[Route(path: '/{_locale}/profile/password', name: 'app_password_edit')]
    public function editPassword(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $userPasswordHasher,  TranslatorInterface $translator)
    {
        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Création du formulaire
        $form = $this->createForm(UserPasswordType::class, $profile);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Vérifier si le mot de passe entré correspond au mot de passe de l'utilisateur
            if(!$userPasswordHasher->isPasswordValid($profile, $form->get('oldPassword')->getData()))
            {
                // Message flash
                $this->addFlash(
                    'danger',
                    $translator->trans('errmdp')
                );

                return $this->redirectToRoute('app_password_edit');
            }

            // hashage du nouveau mot de passe
            $profile->setPassword(
                $userPasswordHasher->hashPassword(
                    $profile,
                    $form->get('plainPassword')->getData()
                )
            );

            // Enregistrement en base de données
            $em->persist($profile);
            $em->flush();

            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('mdpedited')
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/edit_password.html.twig', ['form' => $form->createView()]);
    }

    
}
