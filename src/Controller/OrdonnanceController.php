<?php

namespace App\Controller;

use App\Entity\Ordonnance;
use App\Form\OrdonnanceType;
use App\Repository\OrdonnanceRepository;
use App\Repository\PatientRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ordonnance')]
class OrdonnanceController extends AbstractController
{
    #[Route('/', name: 'app_ordonnance_index', methods: ['GET'])]
    public function index(OrdonnanceRepository $repo): Response
    {
        return $this->render('ordonnance/index.html.twig', [
            'ordonnances' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ordonnance_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ordonnance = new Ordonnance();
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // La relation ManyToMany est gérée automatiquement par Doctrine
            $em->persist($ordonnance);
            $em->flush(); // ⭐ CORRECTION : utiliser $em, pas $entityManager

            $this->addFlash('success', 'Ordonnance créée avec succès !');

            return $this->redirectToRoute('app_ordonnance_show', ['id' => $ordonnance->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ordonnance/new.html.twig', [
            'ordonnance' => $ordonnance,
            'form' => $form->createView(), // ⭐ CORRECTION : ajouter createView()
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
        $form = $this->createForm(OrdonnanceType::class, $ordonnance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // synchronise les produits : côté Produit setOrdonnance() s'exécute si nécessaire
            $em->flush();
            return $this->redirectToRoute('app_ordonnance_index');
        }

        return $this->render('ordonnance/edit.html.twig', [
            'ordonnance' => $ordonnance,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_ordonnance_delete', methods: ['POST'])]
    public function delete(Request $request, Ordonnance $ordonnance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ordonnance->getId(), $request->request->get('_token'))) {
            $em->remove($ordonnance);
            $em->flush();
        }
        return $this->redirectToRoute('app_ordonnance_index');
    }
}
