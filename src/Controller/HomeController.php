<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Mutuelle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

     #[Route('/patient', name: 'app_patients')]
    public function patient(EntityManagerInterface $entityManager): Response
    {
        $patient = $entityManager->getRepository(Patient::class)->findAll();

        return $this->render('patient/index.html.twig', [
            'patients' => $patient,
        ]);
    }
    #[Route('/mutuelle', name: 'app_mutuelle')]
    public function mutuelle(EntityManagerInterface $entityManager): Response
    {
        $mutuelle = $entityManager->getRepository(Mutuelle::class)->findAll();

        return $this->render('mutuelle/index.html.twig', [
            'mutuelles' => $mutuelle,
        ]);
    }
}
