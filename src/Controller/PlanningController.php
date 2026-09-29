<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/planning')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class PlanningController extends AbstractController
{
    #[Route('/', name: 'app_planning_index')]
    public function index(): Response
    {
        return $this->render('planning/index.html.twig');
    }
}
