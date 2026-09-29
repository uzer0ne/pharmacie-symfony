<?php

namespace App\Controller;

use App\Entity\PosteCaisse;
use App\Entity\SessionCaisse;
use App\Repository\PosteCaisseRepository;
use App\Repository\SessionCaisseRepository;
use App\Repository\ClotureCaisseRepository;
use App\Service\CaisseService;
use App\Service\SessionDejaOuverteException;
use App\Service\SessionDejaFermeeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/caisse')]
#[IsGranted('ROLE_CAISSIER')]
final class CaisseController extends AbstractController
{
    // =========================================================================
    // TABLEAU DE BORD CAISSE
    // =========================================================================

    /**
     * Vue principale : liste des postes avec leur statut (ouvert/fermé).
     */
    #[Route('', name: 'app_caisse_index', methods: ['GET'])]
    public function index(
        PosteCaisseRepository   $postesRepo,
        SessionCaisseRepository $sessionRepo
    ): Response {
        $postes = $postesRepo->findActifs();

        // On récupère la session ouverte pour chaque poste (null si fermé)
        $sessionsOuvertes = [];
        foreach ($postes as $poste) {
            $sessionsOuvertes[$poste->getId()] = $sessionRepo->findSessionOuverte($poste);
        }

        return $this->render('caisse/index.html.twig', [
            'postes'           => $postes,
            'sessions_ouvertes' => $sessionsOuvertes,
        ]);
    }

    // =========================================================================
    // OUVERTURE DE SESSION
    // =========================================================================

    /**
     * Formulaire d'ouverture de caisse (sélection du fond de caisse).
     */
    #[Route('/ouvrir/{id}', name: 'app_caisse_ouvrir', methods: ['GET', 'POST'])]
    public function ouvrir(
        PosteCaisse      $poste,
        Request          $request,
        CaisseService    $caisseService
    ): Response {
        if ($request->isMethod('POST')) {
            $fond = (float) $request->request->get('fond_de_caisse', 0);

            try {
                $session = $caisseService->ouvrirSessionCaisse($poste, $this->getUser(), $fond);
                
                // Mémoriser sur quelle caisse l'utilisateur travaille actuellement
                $request->getSession()->set('active_session_caisse_id', $session->getId());

                $this->addFlash('success', sprintf(
                    '✅ Caisse "%s" ouverte avec un fond de %.2f €.',
                    $poste->getNomPoste(),
                    $fond
                ));
                return $this->redirectToRoute('app_caisse_session', ['id' => $session->getId()]);

            } catch (SessionDejaOuverteException $e) {
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('app_caisse_index');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('warning', $e->getMessage());
            }
        }

        return $this->render('caisse/ouvrir.html.twig', [
            'poste' => $poste,
        ]);
    }

    // =========================================================================
    // SESSION EN COURS
    // =========================================================================

    #[Route('/session/{id}/assigner', name: 'app_caisse_assigner', methods: ['GET'])]
    public function assigner(SessionCaisse $session, Request $request): Response
    {
        if ($session->getStatut() === SessionCaisse::STATUT_OUVERTE) {
            $request->getSession()->set('active_session_caisse_id', $session->getId());
            $this->addFlash('success', 'Vous êtes maintenant affecté(e) à la ' . $session->getPoste()->getNomPoste() . '.');
            return $this->redirectToRoute('app_vente_new');
        }

        $this->addFlash('danger', 'Impossible de s\'affecter à une caisse fermée.');
        return $this->redirectToRoute('app_caisse_index');
    }

    /**
     * Tableau de bord d'une session ouverte : ventes du jour, totaux par mode, bouton clôture.
     */
    #[Route('/session/{id}', name: 'app_caisse_session', methods: ['GET'])]
    public function session(
        SessionCaisse $session,
        CaisseService $caisseService
    ): Response {
        $resume = $caisseService->getResumePourTicketZ($session);


        return $this->render('caisse/session.html.twig', [
            'session' => $session,
            'resume'  => $resume,
        ]);
    }

    // =========================================================================
    // CLÔTURE (TICKET Z)
    // =========================================================================

    /**
     * Formulaire de clôture : l'employé saisit le montant d'espèces compté physiquement.
     */
    #[Route('/cloturer/{id}', name: 'app_caisse_cloturer', methods: ['GET', 'POST'])]
    public function cloturer(
        SessionCaisse $session,
        Request       $request,
        CaisseService $caisseService
    ): Response {
        if (!$session->isOuverte()) {
            $this->addFlash('warning', 'Cette session est déjà clôturée.');
            return $this->redirectToRoute('app_caisse_index');
        }

        $resume = $caisseService->getResumePourTicketZ($session);

        if ($request->isMethod('POST')) {
            $montantSaisi = (float) $request->request->get('montant_saisi', 0);

            try {
                $cloture = $caisseService->cloturerSession($session, $this->getUser(), $montantSaisi);

                $ecart = (float) $cloture->getEcartCaisse();
                if ($ecart === 0.0) {
                    $this->addFlash('success', '✅ Caisse clôturée. Pas d\'écart — parfait équilibre !');
                } elseif ($ecart > 0) {
                    $this->addFlash('success', sprintf('✅ Caisse clôturée. Boni : +%.2f €', $ecart));
                } else {
                    $this->addFlash('warning', sprintf('⚠️ Caisse clôturée. Mali : %.2f €', $ecart));
                }

                return $this->redirectToRoute('app_caisse_ticket_z', ['id' => $cloture->getId()]);

            } catch (SessionDejaFermeeException $e) {
                $this->addFlash('danger', $e->getMessage());
                return $this->redirectToRoute('app_caisse_index');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur lors de la clôture : ' . $e->getMessage());
            }
        }

        return $this->render('caisse/cloturer.html.twig', [
            'session' => $session,
            'resume'  => $resume,
        ]);
    }

    // =========================================================================
    // TICKET Z (APERÇU CLÔTURE)
    // =========================================================================

    /**
     * Affichage du Ticket Z après clôture (imprimable).
     */
    #[Route('/ticket-z/{id}', name: 'app_caisse_ticket_z', methods: ['GET'])]
    public function ticketZ(
        \App\Entity\ClotureCaisse $cloture
    ): Response {
        return $this->render('caisse/ticket_z.html.twig', [
            'cloture' => $cloture,
            'session' => $cloture->getSession(),
        ]);
    }

    // =========================================================================
    // HISTORIQUE DES CLÔTURES
    // =========================================================================

    /**
     * Liste paginée des Tickets Z passés.
     */
    #[Route('/historique', name: 'app_caisse_historique', methods: ['GET'])]
    #[IsGranted('ROLE_GESTIONNAIRE_STOCK')]
    public function historique(ClotureCaisseRepository $repo): Response
    {
        return $this->render('caisse/historique.html.twig', [
            'clotures' => $repo->findDernieres(50),
        ]);
    }

    // =========================================================================
    // GESTION DES POSTES (Admin)
    // =========================================================================

    /**
     * Création d'un nouveau poste de caisse (pharmacien seulement).
     */
    #[Route('/poste/new', name: 'app_caisse_poste_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function newPoste(
        Request                $request,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom_poste', ''));
            if (empty($nom)) {
                $this->addFlash('warning', 'Le nom du poste est obligatoire.');
            } else {
                $poste = (new PosteCaisse())->setNomPoste($nom)->setActif(true);
                $em->persist($poste);
                $em->flush();
                $this->addFlash('success', sprintf('Poste "%s" créé avec succès.', $nom));
                return $this->redirectToRoute('app_caisse_index');
            }
        }

        return $this->render('caisse/poste_new.html.twig');
    }
}
