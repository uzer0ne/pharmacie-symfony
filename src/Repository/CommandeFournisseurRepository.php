<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CommandeFournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeFournisseur>
 */
class CommandeFournisseurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeFournisseur::class);
    }

    /** @return CommandeFournisseur[] */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('c.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
