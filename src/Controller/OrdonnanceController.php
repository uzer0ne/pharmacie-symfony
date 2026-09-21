<?php

namespace App\Controller;

use App\Entity\Ordonnance;
use App\Form\OrdonnanceType;
use App\Repository\OrdonnanceRepository;
use App\Repository\PatientRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/', name: 'app_ordonnance_index', methods: ['GET'])]
    public function index(OrdonnanceRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $repo->createQueryBuilder('o')
            ->orderBy('o.dateOrdonnance', 'DESC')
            ->getQuery();

        $ordonnances = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('ordonnance/index.html.twig', [
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/new', name: 'app_ordonnance_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ordonnance = new Ordonnance();
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($ordonnance->getLignes()->isEmpty()) {
                $this->addFlash('danger', "L'ordonnance doit contenir au moins un médicament prescrit.");
            } else {
                // ✅ Une ordonnance = une prescription médicale, pas un mouvement de stock.
                // Le stock ne sera décrémenté que lors de la VENTE des produits.
                $em->persist($ordonnance);
                $em->flush();
                $this->addFlash('success', 'Ordonnance créée avec succès !');
                return $this->redirectToRoute('app_ordonnance_show', ['id' => $ordonnance->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('ordonnance/new.html.twig', [
            'ordonnance' => $ordonnance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ordonnance_show', methods: ['GET'])]
    public function show(Ordonnance $ordonnance): Response
    {
        return $this->render('ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ordonnance_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Ordonnance $ordonnance, EntityManagerInterface $em): Response
    {
        $originalData = [];
        foreach ($ordonnance->getLignes() as $ligne) {
            if ($ligne->getId()) {
                $originalData[$ligne->getId()] = [
                    'produit' => $ligne->getProduit(),
                    'quantite' => $ligne->getQuantite()
                ];
            }
        }

        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Ordonnance = prescription seulement. Pas de mouvement de stock.
            $em->flush();
            $this->addFlash('success', 'Ordonnance mise à jour avec succès !');
            return $this->redirectToRoute('app_ordonnance_show', ['id' => $ordonnance->getId()]);
        }

        return $this->render('ordonnance/edit.html.twig', [
            'ordonnance' => $ordonnance,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ordonnance_delete', methods: ['POST'])]
    public function delete(Request $request, Ordonnance $ordonnance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ordonnance->getId(), $request->request->get('_token'))) {
            // ✅ Ordonnance = prescription seulement. Aucun stock n'a été touché à la création,
            // donc rien à rembourser à la suppression.
            $em->remove($ordonnance);
            $em->flush();
            $this->addFlash('success', 'Ordonnance supprimée avec succès.');
        }
        return $this->redirectToRoute('app_ordonnance_index');
    }
}
