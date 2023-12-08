<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\PanierType;
use App\Entity\Produit;
use App\Entity\ContenuPanier;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PanierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


#[Route('/{_locale}/panier')]
class PanierController extends AbstractController
{

    // Route pour consulter le panier
    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(TranslatorInterface $translator, EntityManagerInterface $em): Response
    {
        // Redirection si aucun utilisateur est connecté 
        if($this->getUser() == null) {
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.connected')
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
    public function validate(TranslatorInterface $translator, PanierRepository $panierRepository, EntityManagerInterface $em): Response
    {
        // Redirection si aucun utilisateur est connecté
        if($this->getUser() == null) {
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.connected')
            );
            $this->redirectToRoute('app_login');
        }

        // Récupérer le dernier panier de l'utilisateur
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'status'=>0]);

        try {
            // Si le panier est vide
            if($panier->getContenuPaniers()->isEmpty())
            {
                $this->addFlash(
                    'danger',
                    $translator->trans('panier.alert.empty')
                );
                $this->redirectToRoute('app_panier_index');
            }

            // foreach produit
            foreach ($panier->getContenuPaniers() as $contenu) {
                $produit = $contenu->getProduit();
                // Si le produit est en stock
                if($produit->getStock() >= $contenu->getQuantite())
                {
                    $produit->setStock($produit->getStock() - $contenu->getQuantite());
                    $em->persist($produit);
                }
                else {
                    // Message flash
                    $this->addFlash(
                        'danger',
                        $translator->trans('panier.alert.nostock'). ' ' . $produit->getNom() . ' Quantité disponible: ' . $produit->getStock()
                    );
                    return $this->redirectToRoute('app_panier_index');
                }
            }

            // Changement status du panier pour valider la commande
            $panier->setStatus(1);
            $panier->setDateAchat(new \DateTime());

            // Enregistrement en base de données
            $em->persist($panier);
            
            // Message flash
            $this->addFlash(
                'success',
                $translator->trans('panier.alert.success')
            );

        } catch (Exception $e)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.error')
            );
        }

        // Push des modifications
        $em->flush();
        // Redirection
        return $this->redirectToRoute('app_accueil');
    }

    #[Route('/quantite/{id}/{bool}', name:"app_panier_update_quantite")]
    public function addQuantite(Produit $produit, EntityManagerInterface $em, bool $bool, TranslatorInterface $translator): Response
    {

        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Récupération du panier de l'utilisateur
        $panier = $em->getRepository(Panier::class)->findOneBy(['utilisateur' => $profile, 'status'=>0]);
        
        // Récupérer les objets du panier
        $contenu = $panier->getContenuPaniers();

        // Si le produit est déjà dans le panier
        foreach ($contenu as $contenuPanier) {
            if($contenuPanier->getProduit() == $produit)
            {
                // Si on veut ajouter une quantité
                if($bool == 1)
                {
                    // Si le produit est en stock
                    if($produit->getStock() > $contenuPanier->getQuantite())
                    {
                        $contenuPanier->setQuantite($contenuPanier->getQuantite() + 1);
                        $em->persist($contenuPanier);
                    }
                    else {
                        // Message flash
                        $this->addFlash(
                            'danger',
                            $translator->trans('panier.alert.nostock')
                        );
                    }
                }
                // Si on veut enlever une quantité
                else {
                    // Si la quantité est supérieur à 1
                    if($contenuPanier->getQuantite() > 1)
                    {
                        $contenuPanier->setQuantite($contenuPanier->getQuantite() - 1);
                        $em->persist($contenuPanier);
                    }
                    else {
                        // supprimer 
                        $em->remove($contenuPanier);

                        // Message flash
                        $this->addFlash(
                            'success',
                            $translator->trans('panier.alert.deleted')
                        );
                    }
                }
            }
        }
        
        // Push des modifications
        $em->flush();

        return $this->redirectToRoute('app_panier_index');
    }

    // Route pour consulter les paniers non achetés
    #[Route('/admin', name: 'app_panier_admin_show', methods: ['GET'])]
    public function adminList(EntityManagerInterface $em, TranslatorInterface $translator): Response
    {

        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            // Message flash
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'est pas un super admin
        if($profile->getRoles()[0] != "ROLE_SUPER_ADMIN")
        {
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.forbidden')
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
    public function show(Panier $panier, TranslatorInterface $translator): Response
    {
        // Redirection si aucun utilisateur est connecté
        $profile = $this->getUser();
        if($profile == null)
        {
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.connected')
            );

            return $this->redirectToRoute('app_login');
        }

        // Redirection si l'utilisateur n'est pas le propriétaire du panier
        if($panier->getUtilisateur() != $profile)
        {
            // Message Flash
            $this->addFlash(
                'danger',
                $translator->trans('panier.alert.forbidden')
            );

            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('panier/show.html.twig', [
            'panier' => $panier,
        ]);
    }

    

 
    #[Route('/ajouter/{id}', name: 'app_panier_new', methods: ['GET', 'POST'])]
    public function new(Produit $produit, int $id, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator): Response
    {
        // Récupération de l'utilisateur
        $user = $this->getUser();
        if ($user == null) {

            // Message flash
            $this->addFlash('error', $translator->trans('panier.alert.connected'));
            
            // Redirection de l'utilisateur
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        // Récupérer le panier de l'utilisateur
        $panier = $entityManager->getRepository(Panier::class)->findOneBy(['utilisateur' => $user, 'status' => 0]);
        
        // Si le panier n'existe pas, on le crée
        if(!$panier){
            $panier = new Panier();
            $panier->setUtilisateur($user);
            $panier->setStatus(0);
            
            $entityManager->persist($panier);
        }
    
        // Récupérer le contenu du panier
        $contenuPanier = $panier->getContenuPaniers();
        
        $trouve = 0;
        $i = 0;

        // Recherche du produit dans le panier
        while($i < count($contenuPanier) && !$trouve)
        {
            $item = $contenuPanier[$i];

            // Si le produit exsite, on l'incrémente de 1
            if($item->getProduit() == $produit)
            {
                $item->setQuantite($item->getQuantite() + 1);
                $entityManager->persist($item);
                $trouve = 1;
            } else 
            {
                $i++;
            }
        }


        // Si le produit n'existe pas
        if (!$trouve) {
            // Ajouter un nouveau produit au panier
            $contenuPanier = new ContenuPanier();
            $contenuPanier->setDateAjout(new \DateTime())
                        ->setProduit($produit)
                        ->setQuantite(1)
                        ->setPanier($panier);
            $entityManager->persist($contenuPanier);
        } 

        // Enregistrement en base de données
        $entityManager->flush();

        // Message flash
        $this->addFlash('success',  $translator->trans('panier.alert.added'));
        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);        
    }
/*
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
