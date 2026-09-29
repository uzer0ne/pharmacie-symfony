<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PropositionReassortDTO;
use App\Entity\CommandeFournisseur;
use App\Entity\MouvementStock;
use App\Repository\CommandeFournisseurRepository;
use App\Service\ReassortService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reassort')]
#[IsGranted('ROLE_GESTIONNAIRE_STOCK')]
class ReassortController extends AbstractController
{
    /**
     * Affiche les produits en alerte avec les propositions de réassort.
     */
    #[Route('', name: 'app_reassort_index', methods: ['GET'])]
    public function index(ReassortService $reassortService, \App\Repository\FournisseurRepository $fournisseurRepo): Response
    {
        return $this->render('reassort/index.html.twig', [
            'propositions' => $reassortService->calculerBesoinsReassort(),
            'fournisseurs' => $fournisseurRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    /**
     * Génère un brouillon de commande depuis le formulaire.
     * Lit les quantités personnalisées saisies par l'utilisateur.
     */
    #[Route('/generer', name: 'app_reassort_generer', methods: ['POST'])]
    public function generer(Request $request, ReassortService $reassortService, \App\Repository\FournisseurRepository $fournisseurRepo, EntityManagerInterface $em): Response
    {
        $toutesPropositions = $reassortService->calculerBesoinsReassort();
        $idsSelectionnes    = $request->request->all('produits_selectionnes'); // tableau d'IDs
        $quantitesCustom    = $request->request->all('quantites');             // [produit_id => qte]
        $fournisseurId      = $request->request->get('fournisseur_id');

        $propositionsFiltrees = [];
        foreach ($toutesPropositions as $p) {
            $idStr = (string) $p->produit->getId();
            if (!in_array($idStr, $idsSelectionnes, true)) {
                continue;
            }

            // Écrase la qté calculée par celle saisie si elle est positive
            $qteCustom = isset($quantitesCustom[$idStr]) ? (int) $quantitesCustom[$idStr] : 0;
            if ($qteCustom > 0) {
                $p = new PropositionReassortDTO(
                    produit:            $p->produit,
                    stockActuel:        $p->stockActuel,
                    stockMinimum:       $p->stockMinimum,
                    stockAlerte:        $p->stockAlerte,
                    quantiteACommander: $qteCustom,
                    prixAchatUnitaire:  $p->prixAchatUnitaire,
                );
            }
            $propositionsFiltrees[] = $p;
        }

        if (empty($propositionsFiltrees)) {
            $this->addFlash('warning', 'Aucun produit sélectionné ou toutes les quantités sont à 0.');
            return $this->redirectToRoute('app_reassort_index');
        }

        try {
            $commande = $reassortService->genererBrouillonCommande($propositionsFiltrees);
            
            if ($fournisseurId) {
                $fournisseur = $fournisseurRepo->find($fournisseurId);
                if ($fournisseur) {
                    $commande->setFournisseur($fournisseur);
                    $em->flush();
                }
            }

            $this->addFlash('success', sprintf(
                'Brouillon #%d créé avec %d ligne(s). Soumettez-le au pharmacien pour validation.',
                $commande->getId(),
                $commande->getLignes()->count()
            ));
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_reassort_index');
        }
    }

    /**
     * Historique de toutes les commandes fournisseur.
     */
    #[Route('/commandes', name: 'app_reassort_commandes', methods: ['GET'])]
    public function commandes(CommandeFournisseurRepository $commandeRepo): Response
    {
        return $this->render('reassort/commandes.html.twig', [
            'commandes' => $commandeRepo->findBy([], ['date_creation' => 'DESC']),
        ]);
    }

    /**
     * Détail d'une commande fournisseur.
     */
    #[Route('/commandes/{id}', name: 'app_reassort_show', methods: ['GET'])]
    public function show(CommandeFournisseur $commande): Response
    {
        return $this->render('reassort/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    /**
     * Soumet le brouillon au pharmacien titulaire pour validation.
     * Accessible au gestionnaire de stock.
     */
    #[Route('/commandes/{id}/soumettre', name: 'app_reassort_soumettre', methods: ['POST'])]
    public function soumettre(Request $request, CommandeFournisseur $commande, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('soumettre_' . $commande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        if ($commande->getStatut() !== CommandeFournisseur::STATUT_BROUILLON) {
            $this->addFlash('warning', 'Cette commande n\'est pas en brouillon.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        $commande->setStatut(CommandeFournisseur::STATUT_A_VALIDER);
        $em->flush();
        $this->addFlash('success', 'Commande soumise au pharmacien pour validation.');
        return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
    }

    /**
     * Validation pharmacienne — ou par délégation au gestionnaire.
     */
    #[Route('/commandes/{id}/valider', name: 'app_reassort_valider', methods: ['POST'])]
    public function valider(Request $request, CommandeFournisseur $commande, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('valider_' . $commande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        if ($commande->getStatut() !== CommandeFournisseur::STATUT_A_VALIDER) {
            $this->addFlash('warning', 'Cette commande n\'est pas en attente de validation.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        $commande->setStatut(CommandeFournisseur::STATUT_VALIDEE);
        $em->flush();
        $this->addFlash('success', 'Commande validée. Elle peut maintenant être envoyée au grossiste-répartiteur.');
        return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
    }

    /**
     * Envoi au grossiste — accessible au gestionnaire ou pharmacien.
     */
    #[Route('/commandes/{id}/envoyer', name: 'app_reassort_envoyer', methods: ['POST'])]
    public function envoyer(Request $request, CommandeFournisseur $commande, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('envoyer_commande_' . $commande->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        if ($commande->getStatut() !== CommandeFournisseur::STATUT_VALIDEE) {
            $this->addFlash('warning', 'Seule une commande validée peut être envoyée.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }
        $commande->setStatut(CommandeFournisseur::STATUT_ENVOYEE);
        $em->flush();
        $this->addFlash('success', sprintf('Commande #%d envoyée au grossiste-répartiteur.', $commande->getId()));
        return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
    }

    /**
     * Réception physique — Page de pointage des quantités et dates de péremption.
     * Accessible au gestionnaire.
     */
    #[Route('/commandes/{id}/receptionner', name: 'app_reassort_receptionner', methods: ['GET', 'POST'])]
    public function receptionner(Request $request, CommandeFournisseur $commande, EntityManagerInterface $em): Response
    {
        if ($commande->getStatut() !== CommandeFournisseur::STATUT_ENVOYEE) {
            $this->addFlash('warning', 'Seule une commande envoyée peut être réceptionnée.');
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('receptionner_commande_' . $commande->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_reassort_receptionner', ['id' => $commande->getId()]);
            }

            $quantitesRecues = $request->request->all('quantites');
            $datesPeremption = $request->request->all('dates_peremption');
            $prixAchat       = $request->request->all('prix_achat');

            foreach ($commande->getLignes() as $ligne) {
                $produit = $ligne->getProduit();
                if ($produit !== null) {
                    $idStr = (string) $ligne->getId();
                    $qteRecue = isset($quantitesRecues[$idStr]) ? (int) $quantitesRecues[$idStr] : 0;
                    
                    // Mise à jour du prix d'achat si saisi (nouveau prix facturé)
                    if (isset($prixAchat[$idStr]) && $prixAchat[$idStr] !== '') {
                        $nouveauPrix = str_replace(',', '.', $prixAchat[$idStr]);
                        if (is_numeric($nouveauPrix)) {
                            // On met à jour le prix catalogue
                            $produit->setPrixAchat((string)$nouveauPrix);
                        }
                    }
                    
                    if ($qteRecue > 0) {
                        // 1. Mise à jour du stock
                        $produit->setStockActuel(($produit->getStockActuel() ?? 0) + $qteRecue);
                        
                        // 2. Mise à jour de la date de péremption si saisie
                        if (!empty($datesPeremption[$idStr])) {
                            try {
                                $produit->setDateExpiration(new \DateTime($datesPeremption[$idStr]));
                            } catch (\Exception $e) {}
                        }

                        // 3. Traçabilité : Création du mouvement de stock
                        $mouvement = new MouvementStock();
                        $mouvement->setProduit($produit);
                        $mouvement->setQuantite($qteRecue);
                        $mouvement->setType('ENTREE');
                        
                        $motif = 'Réception commande #' . $commande->getId();
                        if ($qteRecue < $ligne->getQuantiteCommandee()) {
                            $motif .= ' (Reliquat: reçu ' . $qteRecue . '/' . $ligne->getQuantiteCommandee() . ')';
                        }
                        $mouvement->setMotif($motif);
                        
                        $user = $this->getUser();
                        if ($user instanceof \App\Entity\User) {
                            $mouvement->setUtilisateur($user);
                        }
                        
                        $em->persist($mouvement);
                    }
                }
            }

            $commande->setStatut(CommandeFournisseur::STATUT_RECEPTIONNEE);
            $em->flush();

            $this->addFlash('success', sprintf('Commande #%d réceptionnée avec succès et stocks mis à jour !', $commande->getId()));
            return $this->redirectToRoute('app_reassort_show', ['id' => $commande->getId()]);
        }

        return $this->render('reassort/reception.html.twig', [
            'commande' => $commande,
        ]);
    }
}
