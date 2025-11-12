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

    public function getStatistiquesAlertes(): array
    {
        return [
            'rupture' => count($this->getProduitsEnRupture()),
            'critique' => count($this->getProduitsStockCritique()),
            'alerte' => count($this->getProduitsStockAlerte()),
            'expiration' => count($this->getProduitsExpirationProche()),
            'total_alertes' => count($this->getProduitsEnRupture()) + 
                              count($this->getProduitsStockCritique()) + 
                              count($this->getProduitsStockAlerte()) + 
                              count($this->getProduitsExpirationProche())
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