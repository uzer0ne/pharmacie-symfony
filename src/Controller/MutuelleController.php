<?php

namespace App\Controller;

use App\Entity\Mutuelle;
use App\Form\MutuelleType;
use App\Repository\MutuelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/mutuelle')]
class MutuelleController extends AbstractController
{
    #[Route(name: 'app_mutuelle_index', methods: ['GET'])]
    public function index(MutuelleRepository $mutuelleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $mutuelleRepository->createQueryBuilder('m')
            ->orderBy('m.nom_mutuelle', 'ASC')
            ->getQuery();

        $mutuelles = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('mutuelle/index.html.twig', [
            'mutuelles' => $mutuelles,
        ]);
    }

    #[Route('/new', name: 'app_mutuelle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $mutuelle = new Mutuelle();
        $form = $this->createForm(MutuelleType::class, $mutuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mutuelle);
            $entityManager->flush();

            return $this->redirectToRoute('app_mutuelle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mutuelle/new.html.twig', [
            'mutuelle' => $mutuelle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mutuelle_show', methods: ['GET'])]
    public function show(Mutuelle $mutuelle): Response
    {
        return $this->render('mutuelle/show.html.twig', [
            'mutuelle' => $mutuelle,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mutuelle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mutuelle $mutuelle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MutuelleType::class, $mutuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_mutuelle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mutuelle/edit.html.twig', [
            'mutuelle' => $mutuelle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_mutuelle_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function delete(Request $request, Mutuelle $mutuelle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mutuelle->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($mutuelle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_mutuelle_index', [], Response::HTTP_SEE_OTHER);
    }

}
