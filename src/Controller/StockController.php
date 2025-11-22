<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock')]
class StockController extends AbstractController
{
    #[Route('/reapprovisionnement', name: 'app_stock_reapprovisionnement', methods: ['GET'])]
    public function reapprovisionnement(ProduitRepository $produitRepository): Response
    {
        // On utilise notre nouvelle requête
        $produitsACommander = $produitRepository->findProduitsACommander();

        return $this->render('stock/reapprovisionnement.html.twig', [
            'produits' => $produitsACommander,
            'date' => new \DateTime(), // Pour afficher la date sur le bon de commande
        ]);
    }
}