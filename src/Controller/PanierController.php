<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\PanierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/panier')]
class PanierController extends AbstractController
{

    // Route pour consulter le panier
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // Redirection si aucun utilisateur est connecté 
        if($this->getUser() == null) {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour consulter votre panier.'
            );
            $this->redirectToRoute('app_login');
        }

        // Récupérer le dernier panier de l'utilisateur
        $panier = $em->getRepository(Panier::class)->findOneBy(['utilisateur' => $this->getUser(), 'status'=>0]);

        // Redirection
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
        ]);
    }

    // Validation du panier
    #[Route('/validate', name:"app_panier_validate")]
    public function validate(PanierRepository $panierRepository, EntityManagerInterface $em): Response
    {
        // Redirection si aucun utilisateur est connecté
        if($this->getUser() == null) {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour consulter votre panier.'
            );
            $this->redirectToRoute('app_login');
        }

        // Récupérer le dernier panier de l'utilisateur
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'status'=>0]);

        try {
            // Changement status du panier pour valider la commande
            $panier->setStatus(1);
            $panier->setDateAchat(new \DateTime());

            // Enregistrement en base de données
            $em->persist($panier);
            $em->flush();
            
            // Message flash
            $this->addFlash(
                'success',
                'Commande validée! Merci pour votre commande.'
            );

        } catch (Exception $e)
        {
            // Message flash
            $this->addFlash(
                'danger',
                'Une erreur est survenue lors de la validation de votre panier. Veuillez réessayer.'
            );
        }

        // Redirection
        return $this->redirectToRoute('app_accueil');
    }

    // Route pour consulter les paniers non achetés
    #[Route('/admin', name: 'app_panier_admin_show', methods: ['GET'])]
    public function adminList(EntityManagerInterface $em): Response
    {

        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour accéder à cette page.'
            );

            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'est pas un super admin
        if($profile->getRoles()[0] != "ROLE_SUPER_ADMIN")
        {
            $this->addFlash(
                'danger',
                'Vous n\'avez pas accès à cette page.'
            );

            return $this->redirectToRoute('app_accueil');
        }

        // Récupération des commandes non finalisées
        $paniers = $em->getRepository(Panier::class)->findBy(['status'=>0]);

        return $this->render('panier/admin/show.html.twig', [
            'paniers' => $paniers,
        ]);
    }

    // Route pour consulter nos commandes
    #[Route('/{id}', name: 'app_panier_show', methods: ['GET'])]
    public function show(Panier $panier): Response
    {
        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour accéder à cette page.'
            );

            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'est pas le propriétaire du panier
        if($panier->getUtilisateur() != $profile)
        {
            // Message Flash
            $this->addFlash(
                'danger',
                'Vous n\'avez pas accès à cette page.'
            );

            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('panier/show.html.twig', [
            'panier' => $panier,
        ]);
    }

    

 /*
    #[Route('/new', name: 'app_panier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $panier = new Panier();
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($panier);
            $entityManager->flush();

            return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panier/new.html.twig', [
            'panier' => $panier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_panier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Panier $panier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panier/edit.html.twig', [
            'panier' => $panier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panier_delete', methods: ['POST'])]
    public function delete(Request $request, Panier $panier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$panier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($panier);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
    }
    */
}
