<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CreneauPlanning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreneauPlanning>
 */
class CreneauPlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreneauPlanning::class);
    }
}
