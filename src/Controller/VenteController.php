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

                    // 1. Vérification du stock
                    if ($produit->getStockActuel() < $quantiteDemandee) {
                        // Pas assez de stock ! On bloque la vente.
                        $form->get('ligneVentes')->addError(new FormError(
                            "Stock insuffisant pour le produit '{$produit->getNomProduit()}'. " .
                            "Demandé: {$quantiteDemandee}, Disponible: {$produit->getStockActuel()}"
                        ));
                        throw new \Exception('Stock insuffisant');
                    }

                    // 2. Mettre à jour le stock du produit et créer le mouvement de stock
                    $produit->setStockActuel($produit->getStockActuel() - $quantiteDemandee);

                    $mouvement = new \App\Entity\MouvementStock();
                    $mouvement->setProduit($produit);
                    $mouvement->setQuantite(-$quantiteDemandee);
                    $mouvement->setType('VENTE');
                    $mouvement->setMotif('Vente en caisse');
                    $mouvement->setUtilisateur($this->getUser());
                    $entityManager->persist($mouvement);

                    // 3. "Bloquer" le prix de vente au moment de l'achat
                    $ligneVente->setPrixUnitaireVente($produit->getPrixProduit());
                }

                // 4. Calculer le montant total de la vente
                $vente->calculerMontantTotal();

                // 5. Calculer le Tiers Payant (Part Sécu, Part Mutuelle, Reste à payer)
                $montantSecu = 0.0;
                $montantMutuelle = 0.0;
                $resteAPayer = 0.0;

                $patient = $vente->getPatient();
                
                // Si la case "Créer une ordonnance" est cochée
                $creerOrdonnance = $form->get('creer_ordonnance')->getData();
                if ($creerOrdonnance) {
                    if (!$patient) {
                        $form->addError(new FormError('Vous devez sélectionner un patient pour créer une ordonnance.'));
                        throw new \Exception('Patient manquant');
                    }
                    
                    $nouvelleOrdonnance = new \App\Entity\Ordonnance();
                    $nouvelleOrdonnance->setPatient($patient);
                    $nouvelleOrdonnance->setDateOrdonnance(new \DateTimeImmutable());
                    
                    foreach ($vente->getLigneVentes() as $ligneVente) {
                        $ligneOrd = new \App\Entity\LigneOrdonnance();
                        $ligneOrd->setOrdonnance($nouvelleOrdonnance);
                        $ligneOrd->setProduit($ligneVente->getProduit());
                        $ligneOrd->setQuantite($ligneVente->getQuantite());
                        $ligneOrd->setPosologie('Selon prescription');
                        $ligneOrd->setDureeTraitement(30);
                        $ligneOrd->setRenouvellementsAutorises(0);
                        $entityManager->persist($ligneOrd);
                    }
                    
                    $entityManager->persist($nouvelleOrdonnance);
                    $vente->setOrdonnance($nouvelleOrdonnance);
                }

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

                $vente->setMontantSecu(number_format($montantSecu, 2, '.', ''));
                $vente->setMontantMutuelle(number_format($montantMutuelle, 2, '.', ''));
                $vente->setResteAPayer(number_format($resteAPayer, 2, '.', ''));
                // --- Fin de la logique métier ---

                $entityManager->persist($vente); // Persiste la Vente
                $entityManager->flush(); // Sauvegarde tout (Vente, Lignes, et Stocks Produits)

                $this->addFlash('success', 'Vente enregistrée avec succès !');

                // Redirige vers la page de la vente créée, c'est mieux que l'index
                return $this->redirectToRoute('app_vente_show', ['id' => $vente->getId()], Response::HTTP_SEE_OTHER);

            } catch (\Exception $e) {
                // Si une erreur (stock...) se produit, on ne sauvegarde rien
                // et on affiche le message d'erreur sur le formulaire.
                if (!in_array($e->getMessage(), ['Stock insuffisant', 'Vente vide', 'Patient manquant'])) {
                    $this->addFlash('danger', 'Erreur inattendue: ' . $e->getMessage());
                }
            }
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
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
                        // La ligne a été supprimée : on remet le stock
                        $oldProduit = $data['produit'];
                        $oldProduit->setStockActuel($oldProduit->getStockActuel() + $data['quantite']);
                        // Note: Doctrine gère le remove() via orphanRemoval=true dans l'entité Vente
                    }
                }

                // B. Traitement des ajouts et modifications
                foreach ($vente->getLigneVentes() as $ligneVente) {
                    $nouveauProduit = $ligneVente->getProduit();
                    $nouvelleQuantite = $ligneVente->getQuantite();
                    
                    // Si c'est une ligne existante, on commence par "rembourser" l'ancien stock
                    if ($ligneVente->getId() && isset($originalData[$ligneVente->getId()])) {
                        $oldData = $originalData[$ligneVente->getId()];
                        $oldProduit = $oldData['produit'];
                        $oldProduit->setStockActuel($oldProduit->getStockActuel() + $oldData['quantite']);
                    }

                    // Maintenant, on vérifie si on a assez de stock pour la NOUVELLE demande
                    // (Note: si le produit n'a pas changé, le stock a été incrémenté juste au-dessus, donc on revérifie le total)
                    if ($nouveauProduit->getStockActuel() < $nouvelleQuantite) {
                         throw new \Exception("Stock insuffisant pour '{$nouveauProduit->getNomProduit()}'. Demandé: {$nouvelleQuantite}, En stock: {$nouveauProduit->getStockActuel()}");
                    }

                    // On déduit le stock
                    $nouveauProduit->setStockActuel($nouveauProduit->getStockActuel() - $nouvelleQuantite);
                    
                    // On met à jour le prix unitaire (au cas où le produit a changé ou le prix a évolué)
                    // Dans une vraie pharmacie, on voudrait peut-être garder l'ancien prix si c'est juste une correction de qté,
                    // mais ici on réactualise au prix catalogue actuel.
                    $ligneVente->setPrixUnitaireVente($nouveauProduit->getPrixProduit());
                }

                // 4. Recalculer le total
                $vente->calculerMontantTotal();
                
                $entityManager->flush(); // Sauvegarde toutes les modifications
                $this->addFlash('success', 'Vente mise à jour avec succès !');

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