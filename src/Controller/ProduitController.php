<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Service\AlerteStockService;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    // ... (index et new ne changent pas) ...

    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository, AlerteStockService $alerteStockService): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
            'stats' => $alerteStockService->getStatistiquesAlertes(),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    // Cette méthode était déjà correcte, on la garde telle quelle
    #[Route('/{idProduit}', name: 'app_produit_show', methods: ['GET'])]
    public function show(ProduitRepository $produitRepository, int $idProduit): Response
    {
        $produit = $produitRepository->find($idProduit);

        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    // ⭐ CORRECTION EDIT : On passe par le Repository et l'ID (comme pour show)
    #[Route('/{idProduit}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idProduit, ProduitRepository $produitRepository, EntityManagerInterface $entityManager): Response
    {
        // 1. On récupère le produit manuellement
        $produit = $produitRepository->find($idProduit);
        
        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        // 2. Le reste du code est identique
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    // ⭐ CORRECTION DELETE : On passe aussi par le Repository et l'ID
    #[Route('/{idProduit}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, int $idProduit, ProduitRepository $produitRepository, EntityManagerInterface $entityManager): Response
    {
        // 1. On récupère le produit manuellement
        $produit = $produitRepository->find($idProduit);

        if ($produit) {
            // Note: getId() est la méthode standard, assurez-vous qu'elle existe dans votre entité Produit
            // ou utilisez getIdProduit() si c'est le seul getter disponible.
            if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
                $entityManager->remove($produit);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}