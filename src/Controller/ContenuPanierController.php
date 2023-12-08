<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Form\ContenuPanierType;
use App\Repository\ContenuPanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contenu')]
class ContenuPanierController extends AbstractController
{
    // Voir les contenus de tous les paniers
    #[Route('/', name: 'app_contenu_panier_index', methods: ['GET'])]
    public function index(ContenuPanierRepository $contenuPanierRepository): Response
    {
        return $this->render('contenu_panier/index.html.twig', [
            'contenu_paniers' => $contenuPanierRepository->findAll(),
        ]);
    }

    // Nouveau contenu dans le panier
    #[Route('/new', name: 'app_contenu_panier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contenuPanier = new ContenuPanier();
        $form = $this->createForm(ContenuPanierType::class, $contenuPanier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contenuPanier);
            $entityManager->flush();

            return $this->redirectToRoute('app_contenu_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contenu_panier/new.html.twig', [
            'contenu_panier' => $contenuPanier,
            'form' => $form,
        ]);
    }

    // Affichage un contenu panier
    #[Route('/{id}', name: 'app_contenu_panier_show', methods: ['GET'])]
    public function show(ContenuPanier $contenuPanier): Response
    {
        return $this->render('contenu_panier/show.html.twig', [
            'contenu_panier' => $contenuPanier,
        ]);
    }

    // Edit contenu panier
    #[Route('/{id}/edit', name: 'app_contenu_panier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContenuPanier $contenuPanier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContenuPanierType::class, $contenuPanier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contenu_panier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contenu_panier/edit.html.twig', [
            'contenu_panier' => $contenuPanier,
            'form' => $form,
        ]);
    }

    // Supprimer un contenu du panier
    #[Route('/{id}', name: 'app_contenu_panier_delete', methods: ['POST'])]
    public function delete(Request $request, ContenuPanier $contenuPanier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contenuPanier->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contenuPanier);
            $entityManager->flush();
        }

        // flash message
        $this->addFlash(
            'success',
            'Produit supprimé du panier.'
        );

        return $this->redirectToRoute('app_panier_index', [], Response::HTTP_SEE_OTHER);
    }
}
