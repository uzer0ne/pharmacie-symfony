<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\LigneVente;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use App\Repository\ProduitRepository; // Important: nous avons besoin de ce Repository
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection; // Nécessaire pour la logique d'édition
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError; // Pour afficher les erreurs de stock

#[Route('/vente')]
final class VenteController extends AbstractController
{
    #[Route(name: 'app_vente_index', methods: ['GET'])]
    public function index(VenteRepository $venteRepository): Response
    {
        return $this->render('vente/index.html.twig', [
            'ventes' => $venteRepository->findBy([], ['date_vente' => 'DESC']), // Tri par date
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

                    // 2. Mettre à jour le stock du produit
                    $produit->setStockActuel($produit->getStockActuel() - $quantiteDemandee);

                    // 3. "Bloquer" le prix de vente au moment de l'achat
                    $ligneVente->setPrixUnitaireVente($produit->getPrixProduit());
                }

                // 4. Calculer le montant total de la vente
                $vente->calculerMontantTotal();
                // --- Fin de la logique métier ---

                $entityManager->persist($vente); // Persiste la Vente
                $entityManager->flush(); // Sauvegarde tout (Vente, Lignes, et Stocks Produits)

                $this->addFlash('success', 'Vente enregistrée avec succès !');

                // Redirige vers la page de la vente créée, c'est mieux que l'index
                return $this->redirectToRoute('app_vente_show', ['id' => $vente->getId()], Response::HTTP_SEE_OTHER);

            } catch (\Exception $e) {
                // Si une erreur (stock...) se produit, on ne sauvegarde rien
                // et on affiche le message d'erreur sur le formulaire.
                if ($e->getMessage() !== 'Stock insuffisant' && $e->getMessage() !== 'Vente vide') {
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
        // 1. On "photographie" l'état des lignes *avant* de soumettre le formulaire
        $originalLigneVentes = new ArrayCollection();
        foreach ($vente->getLigneVentes() as $ligne) {
            $originalLigneVentes->add($ligne);
        }

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            try {
                // 2. On regarde les lignes qui ont été *supprimées*
                foreach ($originalLigneVentes as $ligne) {
                    if (false === $vente->getLigneVentes()->contains($ligne)) {
                        // Cette ligne a été supprimée du formulaire
                        // On doit *remettre* le stock
                        $produit = $ligne->getProduit();
                        $produit->setStockActuel($produit->getStockActuel() + $ligne->getQuantite());
                        
                        $entityManager->remove($ligne); // On la supprime de la BDD
                    }
                }

                // 3. On regarde les lignes *mises à jour* ou *ajoutées*
                foreach ($vente->getLigneVentes() as $ligneVente) {
                    $produit = $ligneVente->getProduit();
                    $quantiteDemandee = $ligneVente->getQuantite();
                    
                    $originalLigne = $originalLigneVentes->filter(
                        fn(LigneVente $l) => $l->getId() === $ligneVente->getId() && $l->getId() !== null
                    )->first();

                    if ($originalLigne) {
                        // C'est une ligne *existante* qu'on a modifiée
                        $quantiteOriginale = $originalLigne->getQuantite();
                        $diff = $quantiteDemandee - $quantiteOriginale; // Ex: 5 - 3 = 2 (on retire 2 du stock)
                                                                        // Ex: 2 - 5 = -3 (on remet 3 au stock)

                        if ($diff > 0 && $produit->getStockActuel() < $diff) {
                            // On demande plus que ce qu'on avait, et le stock n'est pas suffisant
                            throw new \Exception("Stock insuffisant pour '{$produit->getNomProduit()}'. Stock restant: {$produit->getStockActuel()}, Besoin de: {$diff} en plus.");
                        }
                        // Met à jour le stock (le signe +/- est géré par la variable $diff)
                        $produit->setStockActuel($produit->getStockActuel() - $diff);

                    } else {
                        // C'est une *nouvelle* ligne (ajoutée pendant l'édition)
                        if ($produit->getStockActuel() < $quantiteDemandee) {
                            throw new \Exception("Stock insuffisant pour le nouveau produit '{$produit->getNomProduit()}'. Demandé: {$quantiteDemandee}, Disponible: {$produit->getStockActuel()}");
                        }
                        // On bloque le prix et on décrémente le stock
                        $ligneVente->setPrixUnitaireVente($produit->getPrixProduit());
                        $produit->setStockActuel($produit->getStockActuel() - $quantiteDemandee);
                    }
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
            
            // --- Logique métier (Restitution des stocks) ---
            // Avant de supprimer la vente, on remet tous les produits en stock
            foreach ($vente->getLigneVentes() as $ligne) {
                $produit = $ligne->getProduit();
                if ($produit) {
                    $produit->setStockActuel($produit->getStockActuel() + $ligne->getQuantite());
                }
            }
            // --- Fin de la logique ---

            $entityManager->remove($vente);
            $entityManager->flush();
            
            $this->addFlash('success', 'Vente supprimée. Les stocks des produits ont été réajustés.');
        }

        return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}