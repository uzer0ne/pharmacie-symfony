<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Mutuelle;
use App\Entity\Vente;
use App\Entity\Ordonnance;
use App\Repository\PatientRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PatientRepository $patientRepository, ProduitRepository $produitRepository, EntityManagerInterface $entityManager): Response
    {
        // 1. Nombre total de patients
        $nbPatients = $patientRepository->count([]);

        // 2. Nombre de produits en stock critique ou rupture
        $produitsCritiques = $produitRepository->findProduitsACommander();
        $nbStockCritique = count($produitsCritiques);

        // 3. Calcul du chiffre d'affaires du jour
        $today = new \DateTime('today');
        $ventesDuJour = $entityManager->getRepository(Vente::class)->createQueryBuilder('v')
            ->where('v.date_vente >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $chiffreAffairesJour = 0.0;
        foreach ($ventesDuJour as $vente) {
            $chiffreAffairesJour += (float) $vente->getMontantTotal();
        }

        // 4. Nombre d'ordonnances récentes (7 derniers jours)
        $sevenDaysAgo = (new \DateTime())->modify('-7 days');
        $ordonnancesRecentes = $entityManager->getRepository(Ordonnance::class)->createQueryBuilder('o')
            ->where('o.date_ordonnance >= :sevenDaysAgo')
            ->setParameter('sevenDaysAgo', $sevenDaysAgo)
            ->getQuery()
            ->getResult();
        $nbOrdonnancesRecentes = count($ordonnancesRecentes);

        // 5. Données pour le graphique des ventes (7 derniers jours)
        $ventesChartData = [];
        $ventesChartLabels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $ventesChartLabels[] = $date->format('d/m');
            
            $start = clone $date;
            $start->setTime(0, 0, 0);
            $end = clone $date;
            $end->setTime(23, 59, 59);

            $ventesQuery = $entityManager->getRepository(Vente::class)->createQueryBuilder('v')
                ->where('v.date_vente >= :start')
                ->andWhere('v.date_vente <= :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getResult();

            $totalJour = 0;
            foreach ($ventesQuery as $v) {
                $totalJour += (float) $v->getMontantTotal();
            }
            $ventesChartData[] = $totalJour;
        }

        return $this->render('home/index.html.twig', [
            'nbPatients' => $nbPatients,
            'nbStockCritique' => $nbStockCritique,
            'chiffreAffairesJour' => $chiffreAffairesJour,
            'nbOrdonnancesRecentes' => $nbOrdonnancesRecentes,
            'ventesChartLabels' => json_encode($ventesChartLabels),
            'ventesChartData' => json_encode($ventesChartData),
        ]);
    }
}
