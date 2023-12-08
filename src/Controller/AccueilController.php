<?php

namespace App\Controller;


use App\Entity\Produit;
use App\Form\ProduitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/{_locale}')]
class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(Request $request,EntityManagerInterface $em): Response
    {
        $produits = $em->getRepository(Produit::class)->findAll(); 
        return $this->render('accueil/index.html.twig', [
            #'controller_name' => 'AccueilController',
            'produits' => $produits,

        ]);
    }
}
