<?php

namespace App\Repository;

use App\Entity\ClotureCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClotureCaisse>
 */
class ClotureCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClotureCaisse::class);
    }

    /**
     * Retourne les dernières clôtures triées par date décroissante.
     *
     * @return ClotureCaisse[]
     */
    public function findDernieres(int $limit = 30): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.date_cloture', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
