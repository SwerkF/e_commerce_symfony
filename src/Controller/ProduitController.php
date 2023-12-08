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
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        // Récupération de l'utilisateur 
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté
        if($user == null)
        {

            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.connected')
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.forbidden')
            );
            return $this->redirectToRoute('app_produit_index');
        }

        // Formulaire
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Traitement de l'image
            $imageFile = $form->get('photo')->getData();

            if($imageFile)
            {
                // Nouveau nom de fichier
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('product_photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                // Enregistrement du nom de fichier
                $produit->setPhoto($newFilename);
            } else {
                $produit->setPhoto('default.png');
            }

            // Enregistrement du produit
            $entityManager->persist($produit);
            $entityManager->flush();

            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('produit.alert.success')
            );

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
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {

        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté.
        if($user == null)
        {
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.connected')
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.forbidden')
            );
            return $this->redirectToRoute('app_produit_index');
        }

        // Formulaire
        $form = $this->createForm(ProduitType::class, $produit);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
           // Traitement de l'image
           $imageFile = $form->get('photo')->getData();

           if($imageFile)
           {
                // Nouveau nom de fichier
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('product_photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }
                // Supprimer l'ancienne image
                $oldFilename = $produit->getPhoto();
                if($oldFilename != null)
                {
                    unlink(__DIR__.'/../../public/uploads/images/product_photo/'.$oldFilename);
                }

                // Enregistrement du nom de fichier
                $produit->setPhoto($newFilename);
           }

            // Enregistrement du produit
            $entityManager->persist($produit);
            $entityManager->flush();

            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('produit.alert.edited')
            );

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté.
        if($user == null)
        {
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.connected')
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                $translator->trans('produit.alert.forbidden')
            );
            return $this->redirectToRoute('app_produit_index');
        }

        // supprimer le produit de la table contenupanier
        $contenuPanier = $produit->getContenuPaniers();
        foreach($contenuPanier as $contenu)
        {
            $entityManager->remove($contenu);
            $entityManager->flush();
        }

        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();

            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('produit.alert.deleted')
            );
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}
