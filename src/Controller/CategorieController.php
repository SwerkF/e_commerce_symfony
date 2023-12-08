<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/categorie')]
class CategorieController extends AbstractController
{
    #[Route('/', name: 'app_categorie_index', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        return $this->render('categorie/index.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {

        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté
        if($user == null)
        {
            $this->addFlash(
                'danger',
                $translator->trans('categorie.alert.connected')
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                $translator->trans('categorie.alert.forbidden')
            );
            return $this->redirectToRoute('app_categorie_index');
        }

        // Nouvelle catégorie vide
        $categorie = new Categorie();

        // Formulaire d'ajout
        $form = $this->createForm(CategorieType::class, $categorie);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Enregistrement de la catégorie
            $entityManager->persist($categorie);
            $entityManager->flush();

            // Message flash
            $this->addFlash('success', $translator->trans('categorie.alert.added'));



            return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_show', methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        return $this->render('categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté
        if($user == null)
        {
            $this->addFlash(
                'danger',
                'Vous devez être connecté pour modifier une catégorie'
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                'Vous n\'avez pas les droits pour modifier une catégorie'
            );
            return $this->redirectToRoute('app_categorie_index');
        }

        // Formulaire d'edition
        $form = $this->createForm(CategorieType::class, $categorie);
        
        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Enregistrement de la catégorie
            $entityManager->persist($categorie);
            $entityManager->flush();

            // Message flash
            $this->addFlash('success',  $translator->trans('categorie.alert.edited'));

            return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_delete', methods: ['POST'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();

        // Redirection si aucun utilisateur est connecté
        if($user == null)
        {
            $this->addFlash(
                'danger',
                $translator->trans('categorie.alert.connected')
            );
            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'a pas les droits
        if(!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles()))
        {
            $this->addFlash(
                'danger',
                $translator->trans('categorie.alert.forbidden')
            );
            return $this->redirectToRoute('app_categorie_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($categorie);
            $entityManager->flush();

            // Message flash
            $this->addFlash('success',  $translator->trans('categorie.alert.deleted'));
        }

        return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
    }
}
