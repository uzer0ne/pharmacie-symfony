<?php

namespace App\Repository;

use App\Entity\MedicamentBdpm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MedicamentBdpmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicamentBdpm::class);
    }

    /**
     * Recherche textuelle sur la dénomination et la substance active
     * Retourne les 20 premiers résultats
     */
    public function search(string $query, int $limit = 20): array
    {
        $q = '%' . mb_strtoupper(trim($query)) . '%';

        return $this->createQueryBuilder('m')
            ->where('UPPER(m.denomination) LIKE :q OR UPPER(m.substanceActive) LIKE :q')
            ->andWhere('m.statutAmm = :statut')
            ->setParameter('q', $q)
            ->setParameter('statut', 'Autorisation active')
            ->orderBy('m.denomination', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par code CIP-13 (pour le scanner code-barres)
     */
    public function findByCodeCip13(string $cip13): ?MedicamentBdpm
    {
        return $this->findOneBy(['codeCip13' => $cip13]);
    }

    /**
     * Recherche par code CIS
     */
    public function findByCodeCis(string $cis): ?MedicamentBdpm
    {
        return $this->findOneBy(['codeCis' => $cis]);
    }
}
