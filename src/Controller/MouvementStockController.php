<?php

namespace App\Controller;

use App\Repository\MouvementStockRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mouvements-stock')]
final class MouvementStockController extends AbstractController
{
    #[Route('/', name: 'app_mouvement_stock_index', methods: ['GET'])]
    public function index(Request $request, MouvementStockRepository $repository, PaginatorInterface $paginator): Response
    {
        $query = $repository->createQueryBuilder('m')
            ->orderBy('m.dateMouvement', 'DESC')
            ->getQuery();

        $mouvements = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20 // 20 items per page
        );

        return $this->render('mouvement_stock/index.html.twig', [
            'mouvements' => $mouvements,
        ]);
    }
}
