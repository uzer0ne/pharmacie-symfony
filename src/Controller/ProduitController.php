<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Service\AlerteStockService;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    // ... (index et new ne changent pas) ...

    #[Route('/', name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository, AlerteStockService $alerteStockService, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $produitRepository->createQueryBuilder('p')
            ->orderBy('p.nom_produit', 'ASC')
            ->getQuery();

        $produits = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
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
    public function show(#[MapEntity(mapping: ['idProduit' => 'idProduit'])] Produit $produit): Response
    {
        // Symfony a trouvé le produit grâce à {idProduit} ou a renvoyé une 404
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    // ⭐ CORRECTION EDIT : On passe par le Repository et l'ID (comme pour show)
    #[Route('/{idProduit}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(mapping: ['idProduit' => 'idProduit'])] Produit $produit, EntityManagerInterface $entityManager): Response
    {
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
    public function delete(Request $request, #[MapEntity(mapping: ['idProduit' => 'idProduit'])] Produit $produit, EntityManagerInterface $entityManager): Response
    {
        // Note: getId() est la méthode standard, assurez-vous qu'elle existe dans votre entité Produit
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    // ⭐ NOUVELLE ROUTE API POUR LE SCANNER
    #[Route('/api/search/{cip}', name: 'app_produit_search_cip', methods: ['GET'])]
    public function searchByCip(string $cip, ProduitRepository $produitRepository): JsonResponse
    {
        // On cherche le produit par son code CIP
        $produit = $produitRepository->findOneBy(['code_cip' => $cip]);

        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], 404);
        }

        // On renvoie les données utiles au format JSON
        return new JsonResponse([
            'id' => $produit->getId(), // Assurez-vous d'avoir un getter getId()
            'nom' => $produit->getNomProduit(),
            'prix' => $produit->getPrixProduit(),
            'stock' => $produit->getStockActuel()
        ]);
    }
}