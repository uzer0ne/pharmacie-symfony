<?php

namespace App\Controller;

use App\Entity\Ordonnance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    /**
     * Retourne les produits d'une ordonnance en JSON pour pré-remplir le formulaire de vente.
     */
    #[Route('/ordonnance/{id}/produits', name: 'api_ordonnance_produits', methods: ['GET'])]
    public function getProduitsOrdonnance(Ordonnance $ordonnance): JsonResponse
    {
        $produits = [];

        foreach ($ordonnance->getLignes() as $ligne) {
            $produit = $ligne->getProduit();
            if (!$produit) continue;

            $produits[] = [
                'id'          => $produit->getId(),
                'nom'         => $produit->getNomProduit(),
                'dosage'      => $produit->getDosageProduit(),
                'famille'     => $produit->getFamille(),
                'stock'       => $produit->getStockActuel(),
                'quantite'    => $ligne->getQuantite(),
                'posologie'   => $ligne->getPosologie(),
                'duree'       => $ligne->getDureeTraitement(),
            ];
        }

        return $this->json($produits);
    }
}
