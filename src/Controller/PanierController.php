<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\PanierType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{

    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(PanierRepository $panierRepository): Response
    {
        // Redirection si aucun utilisateur

        if($this->getUser() == null) {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour consulter votre panier.'
            );
            $this->redirectToRoute('app_login');
        }

        // Récupérer le dernier panier de l'utilisateur
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'status'=>0]);

        $total = 0;

       if($panier)
       {
        foreach($panier->getContenuPaniers() as $contenuPanier) {
            $total += $contenuPanier->getQuantite() * $contenuPanier->getProduit()->getPrix();
        }
       }

        // Redirection
        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total
        ]);
    }

    #[Route('/validate', name:"app_panier_validate")]
    public function validate(PanierRepository $panierRepository, EntityManagerInterface $em): Response
    {
        // Redirection si aucun utilisateur

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

            $panier->setStatus(1);
            $panier->setDateAchat(new \DateTime());
            $em->persist($panier);
            $em->flush();
            
            $this->addFlash(
                'success',
                'Commande validée! Merci pour votre commande.'
            );

        } catch (Exception $e)
        {
            $this->addFlash(
                'danger',
                'Une erreur est survenue lors de la validation de votre panier. Veuillez réessayer.'
            );
        }

        // Redirection
        return $this->redirectToRoute('app_accueil');
    }

    #[Route('/{id}', name: 'app_panier_show', methods: ['GET'])]
    public function show(Panier $panier): Response
    {
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
