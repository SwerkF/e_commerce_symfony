<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/{_locale}/produit')]
class ProduitController extends AbstractController
{
    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if($user == null)
        {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour ajouter un produit'
            );
            return $this->redirectToRoute('app_login');
        }

        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                'Vous n\'avez pas les droits pour ajouter un produit'
            );
            return $this->redirectToRoute('app_produit_index');
        }

        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('photo')->getData();

            if($imageFile)
            {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('product_photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                $produit->setPhoto($newFilename);
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if($user == null)
        {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour modifier un produit'
            );
            return $this->redirectToRoute('app_login');
        }

        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                'Vous n\'avez pas les droits pour modifier un produit'
            );
            return $this->redirectToRoute('app_produit_index');
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if($user == null)
        {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour modifier un produit'
            );
            return $this->redirectToRoute('app_login');
        }

        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                'Vous n\'avez pas les droits pour modifier un produit'
            );
            return $this->redirectToRoute('app_produit_index');
        }

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}
