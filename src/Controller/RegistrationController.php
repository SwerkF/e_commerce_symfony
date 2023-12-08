<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Repository\UserRepository;

class RegistrationController extends AbstractController
{
    #[Route('/{_locale}/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        // Initialisation de l'utilisateur
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        
        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Enregistrement en base de données
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', $translator->trans('register.alert.success'));
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/client', name: 'app_clientList')]
    public function listUsers(UserRepository $userRepository)
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

        // Redirection si l'utilisateur n'a pas les droits
        if($user->getRoles()[0] != 'ROLE_SUPER_ADMIN')
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('register.alert.forbidden')
            );

            return $this->redirectToRoute('app_produit_index');  
        }

        // Récupération des utilisateurs
        $users = $userRepository->findBy([], ['id' => 'DESC']);

        return $this->render('panier/admin/showUser.html.twig', [
            'users' => $users,
        ]);
    }
}
