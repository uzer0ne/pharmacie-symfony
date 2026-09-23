<?php

namespace App\Controller;

use App\Entity\Ordonnance;
use App\Repository\MedicamentBdpmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * Recherche de médicaments dans la BDPM (ANSM) importée localement.
     * Utilisé par le widget de recherche dans le formulaire Produit.
     * 
     * GET /api/medicaments/search?q=doliprane
     */
    #[Route('/medicaments/search', name: 'api_medicaments_search', methods: ['GET'])]
    public function searchMedicaments(Request $request, MedicamentBdpmRepository $repo): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $medicaments = $repo->search($q, 15);

        $results = array_map(function ($med) {
            $prixSuggere = $med->getPrixVenteSuggere(0.30); // +30% de marge

            return [
                'id'                  => $med->getId(),
                'denomination'        => $med->getDenomination(),
                'formePharmaceutique' => $med->getFormePharmaceutique(),
                'substanceActive'     => $med->getSubstanceActive(),
                'dosage'              => $med->getDosage(),
                'codeCip13'           => $med->getCodeCip13(),
                'libellePresentation' => $med->getLibellePresentation(),
                'prixRemboursement'   => $med->getPrixRemboursement(),
                'tauxRemboursement'   => $med->getTauxRemboursement(),
                'titulaire'           => $med->getTitulaire(),
                'prixVenteSuggere'    => $prixSuggere,  // Prix réglementé si remboursable, sinon prix + 30%
            ];
        }, $medicaments);

        return $this->json($results);
    }
}
