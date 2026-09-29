<?php

namespace App\Repository;

use App\Entity\PaiementVente;
use App\Entity\SessionCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementVente>
 */
class PaiementVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementVente::class);
    }

    /**
     * Calcule le total encaissé en espèces pour une session donnée.
     * Utilisé par CaisseService::calculerMontantTheoriqueEspeces().
     */
    public function sumEspecesParSession(SessionCaisse $session): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant) as total')
            ->join('p.vente', 'v')
            ->andWhere('v.session_caisse = :session')
            ->andWhere('p.mode_paiement = :mode')
            ->setParameter('session', $session)
            ->setParameter('mode', PaiementVente::MODE_ESPECES)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /**
     * Retourne le détail des paiements groupés par mode pour une session.
     * Utile pour générer le Ticket Z complet.
     *
     * @return array<string, float> ['CB' => 1250.00, 'ESPECES' => 300.00, ...]
     */
    public function getTotauxParModeEtSession(SessionCaisse $session): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.mode_paiement, SUM(p.montant) as total')
            ->join('p.vente', 'v')
            ->andWhere('v.session_caisse = :session')
            ->setParameter('session', $session)
            ->groupBy('p.mode_paiement')
            ->getQuery()
            ->getResult();

        $totaux = [];
        foreach ($rows as $row) {
            $totaux[$row['mode_paiement']] = (float) $row['total'];
        }

        return $totaux;
    }
}
