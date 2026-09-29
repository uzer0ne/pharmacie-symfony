<?php

namespace App\Service;

use App\Entity\ClotureCaisse;
use App\Entity\PosteCaisse;
use App\Entity\SessionCaisse;
use App\Entity\User;
use App\Repository\PaiementVenteRepository;
use App\Repository\SessionCaisseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier gérant le cycle de vie complet d'une session de caisse.
 *
 * Responsabilités :
 *   - Ouverture sécurisée d'une session (une seule session ouverte par poste)
 *   - Calcul du montant théorique en espèces (fond + recette espèces)
 *   - Clôture atomique avec génération du Ticket Z et calcul de l'écart
 *
 * Injection via autowiring Symfony (déclaré automatiquement comme service).
 */
final class CaisseService
{
    public function __construct(
        private readonly EntityManagerInterface   $em,
        private readonly SessionCaisseRepository  $sessionRepo,
        private readonly PaiementVenteRepository  $paiementRepo,
    ) {}

    // =========================================================================
    // 1. OUVERTURE DE SESSION
    // =========================================================================

    /**
     * Ouvre une nouvelle session de caisse sur un poste physique.
     *
     * @param PosteCaisse $poste        Le comptoir physique sur lequel on travaille
     * @param User        $user         L'employé qui ouvre la caisse
     * @param float       $fondDeCaisse Le montant en espèces déposé dans le tiroir (fonds de départ)
     *
     * @return SessionCaisse La session créée et persistée
     *
     * @throws SessionDejaOuverteException Si le poste a déjà une session OUVERTE
     * @throws \InvalidArgumentException   Si le fond de caisse est négatif
     */
    public function ouvrirSessionCaisse(
        PosteCaisse $poste,
        User        $user,
        float       $fondDeCaisse
    ): SessionCaisse {
        // ── Garde-fous ──────────────────────────────────────────────────────

        if ($fondDeCaisse < 0) {
            throw new \InvalidArgumentException(
                'Le fond de caisse ne peut pas être négatif.'
            );
        }

        // Vérification : une seule session ouverte par poste à la fois
        $sessionExistante = $this->sessionRepo->findSessionOuverte($poste);
        if ($sessionExistante !== null) {
            throw new SessionDejaOuverteException(
                sprintf(
                    'Le poste "%s" a déjà une session ouverte (ID: %d, ouverte le %s par %s). '
                    . 'Clôturez-la avant d\'en ouvrir une nouvelle.',
                    $poste->getNomPoste(),
                    $sessionExistante->getId(),
                    $sessionExistante->getDateOuverture()->format('d/m/Y à H:i'),
                    $sessionExistante->getUserOuverture()->getUserIdentifier()
                )
            );
        }

        // ── Création de la session ──────────────────────────────────────────

        $session = new SessionCaisse();
        $session->setPoste($poste);
        $session->setUserOuverture($user);
        $session->setFondDeCaisse((string) round($fondDeCaisse, 2));
        $session->setStatut(SessionCaisse::STATUT_OUVERTE);
        // date_ouverture est initialisée à new \DateTime() dans le constructeur

        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }

    // =========================================================================
    // 2. CALCUL DU MONTANT THÉORIQUE EN ESPÈCES
    // =========================================================================

    /**
     * Calcule le montant théorique total en espèces présent dans le tiroir.
     *
     * Formule :
     *   fond_de_caisse + Σ PaiementVente(mode=ESPECES) sur toutes les ventes de la session
     *
     * @param SessionCaisse $session La session à analyser
     *
     * @return float Le montant théorique (en euros, arrondi à 2 décimales)
     */
    public function calculerMontantTheoriqueEspeces(SessionCaisse $session): float
    {
        $fondInitial      = (float) $session->getFondDeCaisse();
        $recetteEspeces   = $this->paiementRepo->sumEspecesParSession($session);

        return round($fondInitial + $recetteEspeces, 2);
    }

    // =========================================================================
    // 3. CLÔTURE DE SESSION (TICKET Z)
    // =========================================================================

    /**
     * Clôture une session de caisse et génère le Ticket Z (ClotureCaisse).
     *
     * Étapes réalisées dans une transaction atomique :
     *   1. Vérification que la session est bien OUVERTE
     *   2. Calcul du montant théorique en espèces
     *   3. Calcul de l'écart (boni si positif, mali si négatif)
     *   4. Création de l'entité ClotureCaisse
     *   5. Passage de la session en statut FERMEE avec horodatage
     *   6. Persistance transactionnelle (tout ou rien)
     *
     * @param SessionCaisse $session              La session à clôturer
     * @param User          $user                 L'employé qui réalise le comptage
     * @param float         $montantSaisiPhysique Le montant d'espèces compté physiquement dans le tiroir
     *
     * @return ClotureCaisse L'entité de clôture générée (contient l'écart)
     *
     * @throws SessionDejaFermeeException Si la session est déjà clôturée
     * @throws \Throwable                 En cas d'erreur de persistance (la transaction est rollback)
     */
    public function cloturerSession(
        SessionCaisse $session,
        User          $user,
        float         $montantSaisiPhysique
    ): ClotureCaisse {
        // ── Garde-fous ──────────────────────────────────────────────────────

        if (!$session->isOuverte()) {
            throw new SessionDejaFermeeException(
                sprintf(
                    'La session #%d est déjà clôturée. Impossible de la clôturer à nouveau.',
                    $session->getId()
                )
            );
        }

        if ($montantSaisiPhysique < 0) {
            throw new \InvalidArgumentException(
                'Le montant saisi physiquement ne peut pas être négatif.'
            );
        }

        // ── Calculs métier ──────────────────────────────────────────────────

        $montantTheorique = $this->calculerMontantTheoriqueEspeces($session);
        $ecart            = round($montantSaisiPhysique - $montantTheorique, 2);

        // ── Transaction atomique ────────────────────────────────────────────

        $this->em->beginTransaction();

        try {
            // 1. Création du Ticket Z
            $cloture = new ClotureCaisse();
            $cloture->setSession($session);
            $cloture->setUserCloture($user);
            $cloture->setMontantTheoriqueEspeces((string) $montantTheorique);
            $cloture->setMontantSaisiEspeces((string) round($montantSaisiPhysique, 2));
            $cloture->setEcartCaisse((string) $ecart);
            // date_cloture est initialisée à new \DateTime() dans le constructeur

            // 2. Fermeture de la session
            $session->setStatut(SessionCaisse::STATUT_FERMEE);
            $session->setDateFermeture(new \DateTime());
            $session->setCloture($cloture);

            // 3. Persistance
            $this->em->persist($cloture);
            $this->em->flush();
            $this->em->commit();

        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e; // On re-propage pour que le contrôleur puisse afficher une erreur
        }

        return $cloture;
    }

    // =========================================================================
    // 4. HELPERS
    // =========================================================================

    /**
     * Retourne un résumé des encaissements par mode de paiement pour une session.
     * Utilisé pour afficher le détail du Ticket Z.
     *
     * @return array{
     *   totaux_par_mode: array<string, float>,
     *   total_general: float,
     *   fond_initial: float,
     *   theorique_especes: float,
     *   nb_ventes: int
     * }
     */
    public function getResumePourTicketZ(SessionCaisse $session): array
    {
        $totauxParMode   = $this->paiementRepo->getTotauxParModeEtSession($session);
        $totalGeneral    = array_sum($totauxParMode);
        $fondInitial     = (float) $session->getFondDeCaisse();
        $theoriqueEspeces = $this->calculerMontantTheoriqueEspeces($session);
        $nbVentes        = $session->getVentes()->count();

        return [
            'totaux_par_mode'   => $totauxParMode,
            'total_general'     => round($totalGeneral, 2),
            'fond_initial'      => round($fondInitial, 2),
            'theorique_especes' => $theoriqueEspeces,
            'nb_ventes'         => $nbVentes,
        ];
    }
}
