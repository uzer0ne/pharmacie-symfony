<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }
     /**
     * Trouve tous les produits dont le stock est inférieur ou égal au seuil minimum
     * @return Produit[]
     */
    public function findProduitsACommander(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stock_actuel <= p.stock_minimum')
            ->andWhere('p.actif = :actif') // On ne commande pas les produits désactivés
            ->setParameter('actif', true)
            ->orderBy('p.nom_produit', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne les produits actifs dont le stock_actuel est <= stock_alerte.
     * Le tri place en premier les produits en situation critique (stock négatif ou nul).
     *
     * @return Produit[]
     */
    public function findProduitsEnAlerte(): array
    {
        return $this->createQueryBuilder('p')
            // Seulement les produits actifs
            ->andWhere('p.actif = :actif')
            ->setParameter('actif', true)
            // Sous ou à la limite du seuil d'alerte (inclut les stocks négatifs)
            ->andWhere('p.stock_actuel <= p.stock_alerte')
            // Les plus critiques en premier
            ->orderBy('p.stock_actuel', 'ASC')
            ->addOrderBy('p.nom_produit', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
