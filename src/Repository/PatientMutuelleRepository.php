<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PatientMutuelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PatientMutuelle>
 */
class PatientMutuelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientMutuelle::class);
    }

    /**
     * Retourne le contrat mutuelle actuellement valide (basé sur les DATES)
     * pour un patient X. Le flag actif sert de suspension manuelle.
     *
     * @param int $patientId
     * @return PatientMutuelle|null
     */
    public function findValidMutuelleForPatient(int $patientId): ?PatientMutuelle
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('pm')
            ->andWhere('pm.patient = :patientId')
            ->andWhere('pm.actif = :actif')
            ->andWhere('pm.date_debut_validite <= :today')
            ->andWhere('pm.date_fin_validite >= :today')
            ->setParameter('patientId', $patientId)
            ->setParameter('actif', true)
            ->setParameter('today', $today)
            ->orderBy('pm.date_fin_validite', 'DESC') // Le plus récent en premier
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
