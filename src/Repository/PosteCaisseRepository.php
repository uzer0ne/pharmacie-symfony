<?php

namespace App\Repository;

use App\Entity\PosteCaisse;
use App\Entity\SessionCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PosteCaisse>
 */
class PosteCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PosteCaisse::class);
    }

    /**
     * Retourne tous les postes actifs, triés par nom.
     *
     * @return PosteCaisse[]
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('p.nom_poste', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
