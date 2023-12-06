<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $this->addFlash(
            'danger',
            'Connecté!'
        );

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        $this->addFlash(
            'success',
            'Déconnecté.'
        );
    }

    #[Route(path: '/profile', name: 'app_profile')]
    public function profile()
    {
        $profile = $this->getUser();

        return $this->render('security/profile.html.twig', ['user' => $profile]);
    }

    #[Route(path: '/profile/edit', name: 'app_profile_edit')]
    public function edit(EntityManagerInterface $em, Request $request)
    {
        $profile = $this->getUser();

        $form = $this->createForm(UserType::class, $profile);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($profile);
            $em->flush();

            $this->addFlash(
                'success',
                'Profil modifié.'
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/edit.html.twig', ['form' => $form->createView()]);
    }
}
