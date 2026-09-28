<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\LigneVente;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

#[Route('/vente')]
final class VenteController extends AbstractController
{
    #[Route(name: 'app_vente_index', methods: ['GET'])]
    public function index(VenteRepository $venteRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $venteRepository->createQueryBuilder('v')
            ->orderBy('v.date_vente', 'DESC')
            ->getQuery();

        $ventes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
        ]);
    }

    #[Route('/new', name: 'app_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();
        $vente->setVendeur($this->getUser());
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                // --- Logique métier (Stock et Prix) ---
                if ($vente->getLigneVentes()->isEmpty()) {
                    // On n'autorise pas une vente sans produits
                    $form->addError(new FormError('Une vente doit contenir au moins un produit.'));
                    throw new \Exception('Vente vide');
                }

                foreach ($vente->getLigneVentes() as $ligneVente) {
                    $produit = $ligneVente->getProduit();
                    $quantiteDemandee = $ligneVente->getQuantite();

                    // 1. Vérification du stock (on vérifie sans décrémenter)
                    if ($produit->getStockActuel() < $quantiteDemandee) {
                        // Pas assez de stock ! On bloque la vente.
                        $form->get('ligneVentes')->addError(new FormError(
                            "Stock insuffisant pour le produit '{$produit->getNomProduit()}'. " .
                            "Demandé: {$quantiteDemandee}, Disponible: {$produit->getStockActuel()}"
                        ));
                        throw new \Exception('Stock insuffisant');
                    }

                    // 2. "Bloquer" le prix de vente au moment de l'achat
                    $ligneVente->setPrixUnitaireVente($produit->getPrixProduit());
                }

                // Le montant total sera calculé après les honoraires

                // 5. Calculer le Tiers Payant (Part Sécu, Part Mutuelle, Reste à payer)
                $montantSecu = 0.0;
                $montantMutuelle = 0.0;
                $resteAPayer = 0.0;

                $patient = $vente->getPatient();
                
                // Si la case "Créer une ordonnance" est cochée
                $creerOrdonnance = $form->get('creer_ordonnance')->getData();
                $medecin = $form->get('medecin')->getData();
                
                if ($creerOrdonnance) {
                    if (!$patient) {
                        $form->addError(new FormError('Vous devez sélectionner un patient pour créer une ordonnance.'));
                        throw new \Exception('Patient manquant');
                    }
                    if (!$medecin) {
                        $form->addError(new FormError('Vous devez sélectionner un médecin prescripteur.'));
                        throw new \Exception('Medecin manquant');
                    }
                    
                    $nouvelleOrdonnance = new \App\Entity\Ordonnance();
                    $nouvelleOrdonnance->setPatient($patient);
                    $nouvelleOrdonnance->setMedecin($medecin);
                    
                    $datePrescription = $form->get('date_ordonnance')->getData();
                    if ($datePrescription) {
                        $nouvelleOrdonnance->setDateOrdonnance(\DateTimeImmutable::createFromMutable($datePrescription));
                    } else {
                        $nouvelleOrdonnance->setDateOrdonnance(new \DateTimeImmutable());
                    }
                    
                    $dateFin = $form->get('date_fin_ordonnance')->getData();
                    if ($dateFin) {
                        $nouvelleOrdonnance->setDateFin(\DateTimeImmutable::createFromMutable($dateFin));
                    }
                    
                    $nbLignesRemboursables = 0;
                    
                    foreach ($vente->getLigneVentes() as $ligneVente) {
                        $ligneOrd = new \App\Entity\LigneOrdonnance();
                        $ligneOrd->setOrdonnance($nouvelleOrdonnance);
                        $ligneOrd->setProduit($ligneVente->getProduit());
                        $ligneOrd->setQuantite($ligneVente->getQuantite());
                        $ligneOrd->setPosologie('Selon prescription');
                        $ligneOrd->setDureeTraitement(30);
                        $ligneOrd->setRenouvellementsAutorises(0);
                        $entityManager->persist($ligneOrd);
                        
                        // Check si c'est remboursable
                        if ($ligneVente->getProduit()->getCodeCip()) {
                            $nbLignesRemboursables++;
                        }
                    }
                    
                    $entityManager->persist($nouvelleOrdonnance);
                    $vente->setOrdonnance($nouvelleOrdonnance);
                    
                    // --- MOTEUR DE RÈGLES DES HONORAIRES DE DISPENSATION ---
                    $honoraires = [];
                    
                    // 1. Honoraire de dispensation à la boîte (HDB) : 1,02€ par boîte remboursable
                    $nbBoitesRemboursables = 0;
                    foreach ($vente->getLigneVentes() as $ligneVente) {
                        if ($ligneVente->getProduit()->getCodeCip()) {
                            $nbBoitesRemboursables += $ligneVente->getQuantite();
                        }
                    }
                    
                    if ($nbBoitesRemboursables > 0) {
                        $montantHDB = $nbBoitesRemboursables * 1.02;
                        $honoraires[] = [
                            'libelle' => 'HONORAIRE DE DISPENSATION',
                            'montant' => round($montantHDB, 2),
                            'txR' => '65%',
                            'qte' => $nbBoitesRemboursables
                        ];
                    }
                    
                    // 2. Honoraire ordonnance complexe (HDR) : 0,31€ si >= 5 lignes remboursables
                    if ($nbLignesRemboursables >= 5) {
                        $honoraires[] = [
                            'libelle' => 'HONORAIRE ORDONNANCE COMPLEXE',
                            'montant' => 0.31,
                            'txR' => '100%',
                            'qte' => 1
                        ];
                    }
                    
                    // 3. Honoraire lié à l'âge (HDA) : 1,58€ si < 3 ans ou > 70 ans
                    if ($patient && $patient->getDateNaissance()) {
                        $age = $patient->getDateNaissance()->diff(new \DateTime())->y;
                        if ($age < 3 || $age > 70) {
                            $honoraires[] = [
                                'libelle' => 'HONORAIRE LIE A L AGE',
                                'montant' => 1.58,
                                'txR' => '65%',
                                'qte' => 1
                            ];
                        }
                    }
                    
                    $vente->setDetailsHonoraires($honoraires);
                }

                // 4. Calculer le montant total de la vente (Prend en compte les honoraires)
                $vente->calculerMontantTotal();

                $ordonnance = $vente->getOrdonnance();

                $hasMutuelle = ($patient && $patient->getMutuelles()->count() > 0);

                foreach ($vente->getLigneVentes() as $ligne) {
                    $produit = $ligne->getProduit();
                    $prixLigne = (float) $ligne->getPrixTotal();
                    
                    $partSecu = 0.0;
                    $partMut = 0.0;
                    $resteLigne = $prixLigne;

                    // Le remboursement ne s'applique que s'il y a une ordonnance
                    if ($ordonnance) {
                        $tauxSecu = 0;
                        if ($produit->getCodeCip()) {
                            $medBdpm = $entityManager->getRepository(\App\Entity\MedicamentBdpm::class)
                                                     ->findOneBy(['codeCip13' => $produit->getCodeCip()]);
                            if ($medBdpm && $medBdpm->getTauxRemboursement()) {
                                $tauxSecu = (float) str_replace('%', '', $medBdpm->getTauxRemboursement());
                                $ligne->setTauxRemboursement($medBdpm->getTauxRemboursement());
                            }
                        }

                        $partSecu = round($prixLigne * ($tauxSecu / 100), 2);
                        $resteLigne = $prixLigne - $partSecu;

                        // Si le patient a une mutuelle et que le médicament est remboursable, 
                        // on simule que la mutuelle prend en charge le ticket modérateur
                        if ($hasMutuelle && $tauxSecu > 0) {
                            $partMut = $resteLigne;
                            $resteLigne = 0.0;
                        }
                    }

                    $montantSecu += $partSecu;
                    $montantMutuelle += $partMut;
                    $resteAPayer += $resteLigne;
                }
                
                // Remboursement des honoraires de dispensation
                if ($ordonnance && is_array($vente->getDetailsHonoraires())) {
                    foreach ($vente->getDetailsHonoraires() as $honoraire) {
                        $prixHonoraire = (float) $honoraire['montant'];
                        // On extrait le taux (ex: '65%' -> 65)
                        $tauxSecuHonoraire = (float) str_replace('%', '', $honoraire['txR']);
                        
                        $partSecu = round($prixHonoraire * ($tauxSecuHonoraire / 100), 2);
                        $resteLigne = $prixHonoraire - $partSecu;
                        $partMut = 0.0;
                        
                        if ($hasMutuelle && $tauxSecuHonoraire > 0) {
                            $partMut = $resteLigne;
                            $resteLigne = 0.0;
                        }
                        
                        $montantSecu += $partSecu;
                        $montantMutuelle += $partMut;
                        $resteAPayer += $resteLigne;
                    }
                }

                $vente->setMontantSecu(number_format($montantSecu, 2, '.', ''));
                $vente->setMontantMutuelle(number_format($montantMutuelle, 2, '.', ''));
                $vente->setResteAPayer(number_format($resteAPayer, 2, '.', ''));
                // --- Fin de la logique métier ---

                $entityManager->persist($vente); // Persiste la Vente
                $entityManager->flush(); // Sauvegarde tout (Vente, Lignes)

                $this->addFlash('info', 'Veuillez procéder à l\'encaissement.');

                return $this->redirectToRoute('app_vente_checkout', ['id' => $vente->getId()], Response::HTTP_SEE_OTHER);

            } catch (\Exception $e) {
                // Si une erreur (stock...) se produit, on ne sauvegarde rien
                // et on affiche le message d'erreur sur le formulaire.
                if (!in_array($e->getMessage(), ['Stock insuffisant', 'Vente vide', 'Patient manquant', 'Medecin manquant'])) {
                    $this->addFlash('danger', 'Erreur inattendue: ' . $e->getMessage());
                }
            }
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/checkout', name: 'app_vente_checkout', methods: ['GET'])]
    public function checkout(Vente $vente): Response
    {
        // Si la vente est déjà payée, on ne la réencaisse pas
        if ($vente->getStatut() === 'PAYEE') {
            $this->addFlash('warning', 'Cette vente est déjà payée.');
            return $this->redirectToRoute('app_vente_show', ['id' => $vente->getId()]);
        }

        return $this->render('vente/checkout.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/confirm-payment', name: 'app_vente_confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('confirm_payment'.$vente->getId(), $request->request->getString('_token'))) {
            
            // Si la vente était déjà payée (ex: double clic ou retour arrière)
            if ($vente->getStatut() === 'PAYEE') {
                return $this->redirectToRoute('app_vente_new');
            }

            $montantEncaisse = (float) $request->request->get('montant_encaisse');
            $monnaieRendue = (float) $request->request->get('monnaie_rendue');
            
            $resteAPayer = (float) $vente->getResteAPayer();
            
            if ($montantEncaisse < $resteAPayer) {
                $this->addFlash('danger', 'Montant insuffisant.');
                return $this->redirectToRoute('app_vente_checkout', ['id' => $vente->getId()]);
            }

            $vente->setMontantEncaisse((string) $montantEncaisse);
            $vente->setMonnaieRendue((string) $monnaieRendue);
            $vente->setStatut('PAYEE');

            // 1. Décrémentation effective des stocks
            foreach ($vente->getLigneVentes() as $ligneVente) {
                $produit = $ligneVente->getProduit();
                $quantiteDemandee = $ligneVente->getQuantite();

                $produit->setStockActuel($produit->getStockActuel() - $quantiteDemandee);

                $mouvement = new \App\Entity\MouvementStock();
                $mouvement->setProduit($produit);
                $mouvement->setQuantite(-$quantiteDemandee);
                $mouvement->setType('VENTE');
                $mouvement->setMotif('Vente en caisse');
                $mouvement->setUtilisateur($this->getUser());
                $entityManager->persist($mouvement);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Encaissement validé avec succès !');

            // Retour au comptoir pour le prochain client
            return $this->redirectToRoute('app_vente_new');
        }

        return $this->redirectToRoute('app_vente_checkout', ['id' => $vente->getId()]);
    }

    #[Route('/{id}', name: 'app_vente_show', methods: ['GET'])]
    public function show(Vente $vente): Response
    {
        return $this->render('vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        // --- Logique d'édition (gestion des stocks complexe) ---
        // 1. On sauvegarde l'état des stocks/produits AVANT la modification du formulaire
        // On utilise un tableau associatif [id_ligne => ['produit_id' => int, 'quantite' => int]]
        $originalData = [];
        foreach ($vente->getLigneVentes() as $ligne) {
            if ($ligne->getId()) {
                $originalData[$ligne->getId()] = [
                    'produit' => $ligne->getProduit(),
                    'quantite' => $ligne->getQuantite()
                ];
            }
        }

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            try {
                // STRATÉGIE FIABLE : 
                // 1. Pour les lignes existantes : On remet TOUT l'ancien stock (comme si on annulait la ligne).
                // 2. Ensuite, on recalcule le retrait de stock pour la nouvelle version de la ligne.
                // Cela gère automatiquement les changements de quantité ET les changements de produit.

                // A. Traitement des suppressions (Lignes qui ne sont plus dans le formulaire)
                foreach ($originalData as $id => $data) {
                    // On cherche si cette ligne existe encore dans la collection soumise
                    $exists = $vente->getLigneVentes()->exists(fn($key, $l) => $l->getId() === $id);
                    
                    if (!$exists) {
                        // La ligne a été supprimée : on remet le stock uniquement si payée
                        if ($vente->getStatut() === 'PAYEE') {
                            $oldProduit = $data['produit'];
                            $oldProduit->setStockActuel($oldProduit->getStockActuel() + $data['quantite']);
                        }
                        // Note: Doctrine gère le remove() via orphanRemoval=true dans l'entité Vente
                    }
                }

                // B. Traitement des ajouts et modifications
                foreach ($vente->getLigneVentes() as $ligneVente) {
                    $nouveauProduit = $ligneVente->getProduit();
                    $nouvelleQuantite = $ligneVente->getQuantite();
                    
                    // Si c'est une ligne existante, on commence par "rembourser" l'ancien stock
                    if ($ligneVente->getId() && isset($originalData[$ligneVente->getId()])) {
                        if ($vente->getStatut() === 'PAYEE') {
                            $oldData = $originalData[$ligneVente->getId()];
                            $oldProduit = $oldData['produit'];
                            $oldProduit->setStockActuel($oldProduit->getStockActuel() + $oldData['quantite']);
                        }
                    }

                    // Maintenant, on vérifie si on a assez de stock pour la NOUVELLE demande
                    if ($nouveauProduit->getStockActuel() < $nouvelleQuantite) {
                         throw new \Exception("Stock insuffisant pour '{$nouveauProduit->getNomProduit()}'. Demandé: {$nouvelleQuantite}, En stock: {$nouveauProduit->getStockActuel()}");
                    }

                    // On déduit le stock
                    if ($vente->getStatut() === 'PAYEE') {
                        $nouveauProduit->setStockActuel($nouveauProduit->getStockActuel() - $nouvelleQuantite);
                    }
                    
                    // On met à jour le prix unitaire (au cas où le produit a changé ou le prix a évolué)
                    $ligneVente->setPrixUnitaireVente($nouveauProduit->getPrixProduit());
                }

                // 4. Recalculer le total
                $vente->calculerMontantTotal();

                // 5. Recalculer les parts Sécu / Mutuelle / Reste à payer
                $montantSecu = 0.0;
                $montantMutuelle = 0.0;
                $resteAPayer = 0.0;
                $hasMutuelle = $vente->getPatient() && $vente->getPatient()->getMutuelle();
                $ordonnance = $vente->getOrdonnance();

                foreach ($vente->getLigneVentes() as $ligne) {
                    $produit = $ligne->getProduit();
                    $prixLigne = (float) $ligne->getPrixTotal();
                    
                    $partSecu = 0.0;
                    $partMut = 0.0;
                    $resteLigne = $prixLigne;

                    if ($ordonnance) {
                        $tauxSecu = 0;
                        if ($produit->getCodeCip()) {
                            $medBdpm = $entityManager->getRepository(\App\Entity\MedicamentBdpm::class)
                                                     ->findOneBy(['codeCip13' => $produit->getCodeCip()]);
                            if ($medBdpm && $medBdpm->getTauxRemboursement()) {
                                $tauxSecu = (float) str_replace('%', '', $medBdpm->getTauxRemboursement());
                                $ligne->setTauxRemboursement($medBdpm->getTauxRemboursement());
                            }
                        }

                        $partSecu = round($prixLigne * ($tauxSecu / 100), 2);
                        $resteLigne = $prixLigne - $partSecu;

                        if ($hasMutuelle && $tauxSecu > 0) {
                            $partMut = $resteLigne;
                            $resteLigne = 0.0;
                        }
                    }

                    $montantSecu += $partSecu;
                    $montantMutuelle += $partMut;
                    $resteAPayer += $resteLigne;
                }

                if ($ordonnance && is_array($vente->getDetailsHonoraires())) {
                    foreach ($vente->getDetailsHonoraires() as $honoraire) {
                        $prixHonoraire = (float) $honoraire['montant'];
                        $tauxSecuHonoraire = (float) str_replace('%', '', $honoraire['txR']);
                        
                        $partSecu = round($prixHonoraire * ($tauxSecuHonoraire / 100), 2);
                        $resteLigne = $prixHonoraire - $partSecu;
                        $partMut = 0.0;
                        
                        if ($hasMutuelle && $tauxSecuHonoraire > 0) {
                            $partMut = $resteLigne;
                            $resteLigne = 0.0;
                        }
                        
                        $montantSecu += $partSecu;
                        $montantMutuelle += $partMut;
                        $resteAPayer += $resteLigne;
                    }
                }

                $vente->setMontantSecu(number_format($montantSecu, 2, '.', ''));
                $vente->setMontantMutuelle(number_format($montantMutuelle, 2, '.', ''));
                $vente->setResteAPayer(number_format($resteAPayer, 2, '.', ''));
                
                $entityManager->flush(); // Sauvegarde toutes les modifications
                $this->addFlash('success', 'Vente mise à jour avec succès !');

                if ($vente->getStatut() === 'EN_ATTENTE') {
                    return $this->redirectToRoute('app_vente_checkout', ['id' => $vente->getId()]);
                }
                
                return $this->redirectToRoute('app_vente_show', ['id' => $vente->getId()], Response::HTTP_SEE_OTHER);

            } catch (\Exception $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vente_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vente->getId(), $request->getPayload()->getString('_token'))) {
            
            // --- Logique métier demandée par l'utilisateur ---
            // Lors de la suppression d'une vente entière, le stock NE DOIT PAS être restitué.
            // La vente est effacée de l'historique mais les produits restent décrémentés du stock.
            
            $entityManager->remove($vente);
            $entityManager->flush();
            
            $this->addFlash('success', 'Vente supprimée de l\'historique (les stocks restent inchangés).');
        }

        return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}