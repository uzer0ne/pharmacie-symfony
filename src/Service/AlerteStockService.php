<?php
// src/Service/AlerteStockService.php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteStockService
{
    public function __construct(
        private ProduitRepository $produitRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getProduitsEnRupture(): array
    {
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.stock_actuel <= 0')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getResult();
    }

    public function getProduitsStockCritique(): array
    {
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.stock_actuel > 0')
            ->andWhere('p.stock_actuel <= p.stock_minimum')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getResult();
    }

    public function getProduitsStockAlerte(): array
    {
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.stock_actuel > p.stock_minimum')
            ->andWhere('p.stock_actuel <= p.stock_alerte')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getResult();
    }

    public function getProduitsExpirationProche(int $jours = 30): array
    {
        $dateLimite = new \DateTime();
        $dateLimite->modify("+$jours days");

        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.date_expiration <= :dateLimite')
            ->andWhere('p.date_expiration >= :aujourdhui')
            ->andWhere('p.actif = true')
            ->setParameter('dateLimite', $dateLimite)
            ->setParameter('aujourdhui', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getValeurStock(): float
    {
        $produits = $this->produitRepository->createQueryBuilder('p')
            ->where('p.stock_actuel > 0')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getResult();

        $valeur = 0.0;
        foreach ($produits as $p) {
            $prix = (float) $p->getPrixAchat(); // Valorisation souvent sur le prix d'achat
            $valeur += $prix * $p->getStockActuel();
        }
        return $valeur;
    }

    public function getProduitsACommander(): array
    {
        // Regroupe les ruptures et les produits sous le seuil d'alerte
        return $this->produitRepository->createQueryBuilder('p')
            ->where('p.stock_actuel <= p.stock_alerte')
            ->andWhere('p.actif = true')
            ->getQuery()
            ->getResult();
    }

    public function getStatistiquesAlertes(): array
    {
        return [
            'rupture' => count($this->getProduitsEnRupture()), // Pour rétrocompatibilité si besoin
            'a_commander' => count($this->getProduitsACommander()),
            'expiration' => count($this->getProduitsExpirationProche(90)), // 90 jours (3 mois) au lieu de 30 pour une pharma
            'valeur_stock' => $this->getValeurStock()
        ];
    }

    public function decrementerStock(Produit $produit, int $quantite = 1): void
    {
        $nouveauStock = $produit->getStockActuel() - $quantite;
        $produit->setStockActuel(max(0, $nouveauStock));
        
        $this->entityManager->persist($produit);
        $this->entityManager->flush();
    }

    public function incrementerStock(Produit $produit, int $quantite = 1): void
    {
        $nouveauStock = $produit->getStockActuel() + $quantite;
        $produit->setStockActuel($nouveauStock);
        
        $this->entityManager->persist($produit);
        $this->entityManager->flush();
    }
}