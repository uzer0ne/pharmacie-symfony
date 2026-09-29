<?php

namespace App\Repository;

use App\Entity\PosteCaisse;
use App\Entity\SessionCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionCaisse>
 */
class SessionCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionCaisse::class);
    }

    /**
     * Trouve la session OUVERTE pour un poste donné.
     * Retourne null si aucune session n'est en cours.
     * Utilisé par CaisseService pour vérifier qu'on ne double-ouvre pas.
     */
    public function findSessionOuverte(PosteCaisse $poste): ?SessionCaisse
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.poste = :poste')
            ->andWhere('s.statut = :statut')
            ->setParameter('poste', $poste)
            ->setParameter('statut', SessionCaisse::STATUT_OUVERTE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les N dernières sessions (toutes confondues), utile pour le tableau de bord.
     *
     * @return SessionCaisse[]
     */
    public function findDernieres(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.date_ouverture', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les sessions ouvertes sur tous les postes (vue gestionnaire).
     *
     * @return SessionCaisse[]
     */
    public function findSessionsOuvertes(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', SessionCaisse::STATUT_OUVERTE)
            ->orderBy('s.date_ouverture', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
