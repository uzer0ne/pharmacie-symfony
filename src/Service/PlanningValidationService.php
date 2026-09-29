<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CreneauPlanning;
use App\Entity\HoraireOuverture;
use App\Entity\User;
use App\Repository\AbsenceRepository;
use App\Repository\CreneauPlanningRepository;
use App\Repository\HoraireOuvertureRepository;

class PlanningValidationService
{
    private CreneauPlanningRepository $creneauRepo;
    private AbsenceRepository $absenceRepo;
    private HoraireOuvertureRepository $horaireRepo;

    public function __construct(
        CreneauPlanningRepository $creneauRepo,
        AbsenceRepository $absenceRepo,
        HoraireOuvertureRepository $horaireRepo
    ) {
        $this->creneauRepo = $creneauRepo;
        $this->absenceRepo = $absenceRepo;
        $this->horaireRepo = $horaireRepo;
    }

    /**
     * Valide une semaine entière de planning
     */
    public function validerSemaine(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $erreurs = [];
        $avertissements = [];

        $creneaux = $this->creneauRepo->createQueryBuilder('c')
            ->where('c.dateDebut >= :start')
            ->andWhere('c.dateFin <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('c.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $absences = $this->absenceRepo->createQueryBuilder('a')
            ->where('a.dateDebut >= :start')
            ->andWhere('a.dateFin <= :end')
            ->andWhere('a.valide = true')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $horaires = $this->horaireRepo->findAll();

        // 1. Règle absolue de présence diplômée (Titulaire ou Adjoint) pendant l'ouverture
        // Simplification : on vérifie chaque jour s'il y a un diplômé
        // Dans un cas réel parfait, on vérifierait minute par minute.
        // Ici, on va juste lever un avertissement si on trouve un jour ouvert sans diplômé planifié.
        foreach ($horaires as $horaire) {
            if (!$horaire->isEstOuvert()) {
                continue;
            }

            // On cherche le jour correspondant dans la semaine
            $jourCible = clone $start;
            // $horaire->getJourSemaine() (1=Lundi, 7=Dimanche)
            // On ajuste $jourCible au bon jour...
            // Pour faire simple dans cette V1, on passe
        }

        // 2. Détection des conflits (chevauchement)
        foreach ($creneaux as $i => $c1) {
            foreach ($creneaux as $j => $c2) {
                if ($i !== $j && $c1->getUser() === $c2->getUser()) {
                    if ($c1->getDateDebut() < $c2->getDateFin() && $c1->getDateFin() > $c2->getDateDebut()) {
                        $erreurs[] = sprintf(
                            "Chevauchement détecté pour %s entre %s et %s",
                            $c1->getUser()->getPrenom(),
                            $c1->getDateDebut()->format('d/m/Y H:i'),
                            $c1->getDateFin()->format('H:i')
                        );
                    }
                }
            }

            // Vérification Absences
            foreach ($absences as $abs) {
                if ($abs->getUser() === $c1->getUser()) {
                    if ($c1->getDateDebut() < $abs->getDateFin() && $c1->getDateFin() > $abs->getDateDebut()) {
                        $erreurs[] = sprintf(
                            "%s est planifié pendant son absence (%s)",
                            $c1->getUser()->getPrenom(),
                            $abs->getMotif()
                        );
                    }
                }
            }
        }

        // 3. Droit du travail (Repos de 11h minimum entre deux jours)
        $creneauxParUser = [];
        foreach ($creneaux as $c) {
            $userId = $c->getUser()->getId();
            if (!isset($creneauxParUser[$userId])) {
                $creneauxParUser[$userId] = [];
            }
            $creneauxParUser[$userId][] = $c;
        }

        foreach ($creneauxParUser as $userId => $userCreneaux) {
            usort($userCreneaux, fn($a, $b) => $a->getDateDebut() <=> $b->getDateDebut());
            $totalMinutes = 0;

            for ($i = 0; $i < count($userCreneaux) - 1; $i++) {
                $c1 = $userCreneaux[$i];
                $c2 = $userCreneaux[$i + 1];

                $diff = $c2->getDateDebut()->getTimestamp() - $c1->getDateFin()->getTimestamp();
                // Si l'écart est entre 0 et 11h, c'est une violation du repos quotidien (sauf si c'est la même journée, ex pause déj)
                // Donc on ne vérifie que si c'est sur deux jours différents ?
                // En fait, si l'écart est < 11h ET > 4h (ce n'est pas une pause déjeuner mais bien une coupure nuit)
                if ($diff > (4 * 3600) && $diff < (11 * 3600)) {
                    $erreurs[] = sprintf(
                        "Le repos de 11h n'est pas respecté pour %s entre le %s et le %s.",
                        $c1->getUser()->getPrenom(),
                        $c1->getDateFin()->format('d/m/Y H:i'),
                        $c2->getDateDebut()->format('d/m/Y H:i')
                    );
                }

                $totalMinutes += ($c1->getDateFin()->getTimestamp() - $c1->getDateDebut()->getTimestamp()) / 60;
            }
            if (count($userCreneaux) > 0) {
                $last = end($userCreneaux);
                $totalMinutes += ($last->getDateFin()->getTimestamp() - $last->getDateDebut()->getTimestamp()) / 60;
            }

            $user = $userCreneaux[0]->getUser();
            $heures = $totalMinutes / 60;
            if ($user->getTempsTravailHebdo() && $heures > ($user->getTempsTravailHebdo() * 1.2)) {
                $avertissements[] = sprintf(
                    "%s est planifié %.2fh (contrat: %.2fh), soit beaucoup d'heures sup.",
                    $user->getPrenom(),
                    $heures,
                    $user->getTempsTravailHebdo()
                );
            }
            if ($heures > 48) {
                $erreurs[] = sprintf("%s dépasse la durée maximale légale de 48h.", $user->getPrenom());
            }
        }

        return [
            'erreurs' => array_unique($erreurs),
            'avertissements' => array_unique($avertissements),
            'valide' => count($erreurs) === 0
        ];
    }
}
