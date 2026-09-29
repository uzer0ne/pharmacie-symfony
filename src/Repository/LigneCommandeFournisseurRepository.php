<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LigneCommandeFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneCommandeFournisseur>
 */
class LigneCommandeFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneCommandeFournisseur::class);
    }
}
