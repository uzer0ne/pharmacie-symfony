<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PropositionReassortDTO;
use App\Entity\CommandeFournisseur;
use App\Entity\LigneCommandeFournisseur;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion du réassort automatique.
 *
 * Ce service calcule les besoins en réapprovisionnement basé sur les
 * seuils d'alerte et génère des brouillons de commandes fournisseur.
 */
final class ReassortService
{
    public function __construct(
        private readonly ProduitRepository      $produitRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
    ) {}

    /**
     * Analyse le stock et retourne les propositions de réassort.
     *
     * Règle métier :
     *   - Produit concerné : actif = true ET stock_actuel <= stock_alerte
     *   - Quantité à commander = stock_minimum - stock_actuel
     *   - Le stock_actuel peut être négatif (ventes sur commande) → on gère
     *
     * @return PropositionReassortDTO[]
     */
    public function calculerBesoinsReassort(): array
    {
        $produits = $this->produitRepository->findProduitsEnAlerte();
        $propositions = [];

        foreach ($produits as $produit) {
            $stockActuel  = $produit->getStockActuel() ?? 0;
            $stockMinimum = $produit->getStockMinimum() ?? 0;

            // Quantité à commander : toujours positive, même si stock négatif
            $quantiteACommander = $stockMinimum - $stockActuel;

            if ($quantiteACommander <= 0) {
                // Cas de garde : stock_minimum mal configuré, on ignore
                $this->logger->warning(
                    'Produit {nom} est en alerte mais la quantité calculée est <= 0. Ignoré.',
                    ['nom' => $produit->getNomProduit()]
                );
                continue;
            }

            $prixAchat = (float)($produit->getPrixAchat() ?? 0);

            $propositions[] = new PropositionReassortDTO(
                produit:            $produit,
                stockActuel:        $stockActuel,
                stockMinimum:       $stockMinimum,
                stockAlerte:        $produit->getStockAlerte() ?? 0,
                quantiteACommander: $quantiteACommander,
                prixAchatUnitaire:  $prixAchat,
            );

            $this->logger->info(
                'Réassort proposé : {nom} — stock: {stock}, commande: {qte}',
                [
                    'nom'   => $produit->getNomProduit(),
                    'stock' => $stockActuel,
                    'qte'   => $quantiteACommander,
                ]
            );
        }

        return $propositions;
    }

    /**
     * Génère et persiste une CommandeFournisseur au statut BROUILLON.
     *
     * @param PropositionReassortDTO[] $propositions
     * @throws \InvalidArgumentException si la liste est vide
     */
    public function genererBrouillonCommande(array $propositions): CommandeFournisseur
    {
        if (empty($propositions)) {
            throw new \InvalidArgumentException(
                'Impossible de générer une commande : la liste de propositions est vide.'
            );
        }

        $commande = new CommandeFournisseur();
        $commande->setStatut(CommandeFournisseur::STATUT_BROUILLON);

        foreach ($propositions as $dto) {
            $ligne = new LigneCommandeFournisseur();
            $ligne->setProduit($dto->produit);
            $ligne->setQuantiteCommandee($dto->quantiteACommander);
            $ligne->setPrixAchatUnitaire((string)$dto->prixAchatUnitaire);

            $commande->addLigne($ligne);
        }

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        $this->logger->info(
            'Brouillon de commande #{id} créé avec {nb} ligne(s) pour un total estimé de {total} €.',
            [
                'id'    => $commande->getId(),
                'nb'    => $commande->getLignes()->count(),
                'total' => number_format($commande->getMontantTotal(), 2, ',', ' '),
            ]
        );

        return $commande;
    }
}
