<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CreneauPlanning;
use App\Repository\AbsenceRepository;
use App\Repository\CreneauPlanningRepository;
use App\Repository\HoraireOuvertureRepository;
use App\Repository\UserRepository;
use App\Service\PlanningValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/planning')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiPlanningController extends AbstractController
{
    #[Route('', name: 'api_planning_get', methods: ['GET'])]
    public function getPlanning(
        Request $request,
        CreneauPlanningRepository $creneauRepo,
        AbsenceRepository $absenceRepo,
        UserRepository $userRepo
    ): JsonResponse {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');

        if (!$startStr || !$endStr) {
            return new JsonResponse(['error' => 'Missing start or end parameters'], 400);
        }

        $start = new \DateTime($startStr);
        $end = new \DateTime($endStr);

        $qb = $creneauRepo->createQueryBuilder('c')
            ->where('c.dateDebut >= :start')
            ->andWhere('c.dateFin <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Si l'utilisateur n'est pas pharmacien, il ne voit QUE ses propres créneaux publiés
        if (!$this->isGranted('ROLE_PHARMACIEN')) {
            $qb->andWhere('c.user = :user')
               ->andWhere('c.statut = :statut')
               ->setParameter('user', $this->getUser())
               ->setParameter('statut', CreneauPlanning::STATUT_PUBLIE);
        }

        $creneaux = $qb->getQuery()->getResult();

        $events = [];
        foreach ($creneaux as $c) {
            $qualif = $c->getUser()->getQualification();
            $color = '#3b82f6'; // default blue
            if ($qualif === 'TITULAIRE' || $qualif === 'ADJOINT') {
                $color = '#ef4444';
            } elseif ($qualif === 'PREPARATEUR') {
                $color = '#10b981';
            } elseif ($qualif === 'ETUDIANT' || $qualif === 'RAYONNISTE') {
                $color = '#f59e0b';
            }
            if ($c->getStatut() === 'BROUILLON') {
                $color = '#94a3b8'; // Gris si brouillon
            }

            $events[] = [
                'id' => $c->getId(),
                'resourceId' => $c->getUser()->getId(), // FullCalendar resource (User)
                'start' => $c->getDateDebut()->format('Y-m-d\TH:i:s'),
                'end' => $c->getDateFin()->format('Y-m-d\TH:i:s'),
                'title' => $c->getUser()->getPrenom() . ' - ' . $c->getTypeCreneau(),
                'status' => $c->getStatut(),
                'color' => $color,
            ];
        }

        // Ajouter les absences
        $qbAbs = $absenceRepo->createQueryBuilder('a');
        if (!$this->isGranted('ROLE_PHARMACIEN')) {
            $qbAbs->where('a.user = :user')
                  ->setParameter('user', $this->getUser());
        }
        $absences = $qbAbs->getQuery()->getResult();
        
        foreach ($absences as $a) {
            $events[] = [
                'id' => 'abs_'.$a->getId(),
                'resourceId' => $a->getUser()->getId(),
                'start' => $a->getDateDebut()->format('Y-m-d\TH:i:s'),
                'end' => $a->getDateFin()->format('Y-m-d\TH:i:s'),
                'title' => 'Absence: ' . $a->getMotif(),
                'color' => '#ef4444',
                'display' => 'background'
            ];
        }

        return new JsonResponse($events);
    }

    #[Route('/resources', name: 'api_planning_resources', methods: ['GET'])]
    public function getResources(UserRepository $userRepo): JsonResponse
    {
        if ($this->isGranted('ROLE_PHARMACIEN')) {
            $users = $userRepo->findAll();
        } else {
            $users = [$this->getUser()];
        }
        
        $resources = [];
        foreach ($users as $u) {
            if ($u->isDeleted()) continue;
            $resources[] = [
                'id' => $u->getId(),
                'title' => $u->getPrenom() . ' ' . $u->getNom(),
                'qualification' => $u->getQualification() ?? 'Non défini'
            ];
        }
        return new JsonResponse($resources);
    }

    #[Route('/creneau', name: 'api_planning_create_creneau', methods: ['POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function createCreneau(Request $request, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $userRepo->find($data['resourceId']);
        if (!$user) return new JsonResponse(['error' => 'User not found'], 404);

        $creneau = new CreneauPlanning();
        $creneau->setUser($user);
        $creneau->setDateDebut(new \DateTime($data['start']));
        $creneau->setDateFin(new \DateTime($data['end']));
        $creneau->setTypeCreneau($data['title'] ?? CreneauPlanning::TYPE_COMPTOIR);
        $creneau->setStatut(CreneauPlanning::STATUT_BROUILLON);

        $em->persist($creneau);
        $em->flush();

        return new JsonResponse(['status' => 'success', 'id' => $creneau->getId()]);
    }

    #[Route('/creneau/{id}', name: 'api_planning_update_creneau', methods: ['PUT'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function updateCreneau(int $id, Request $request, EntityManagerInterface $em, CreneauPlanningRepository $repo, UserRepository $userRepo): JsonResponse
    {
        $creneau = $repo->find($id);
        if (!$creneau) return new JsonResponse(['error' => 'Not found'], 404);

        $data = json_decode($request->getContent(), true);

        if (isset($data['start'])) $creneau->setDateDebut(new \DateTime($data['start']));
        if (isset($data['end'])) $creneau->setDateFin(new \DateTime($data['end']));
        if (isset($data['resourceId'])) {
            $user = $userRepo->find($data['resourceId']);
            if ($user) $creneau->setUser($user);
        }

        $em->flush();
        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/creneau/{id}', name: 'api_planning_delete_creneau', methods: ['DELETE'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function deleteCreneau(int $id, EntityManagerInterface $em, CreneauPlanningRepository $repo): JsonResponse
    {
        $creneau = $repo->find($id);
        if (!$creneau) return new JsonResponse(['error' => 'Not found'], 404);

        $em->remove($creneau);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/valider-semaine', name: 'api_planning_valider', methods: ['POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function validerSemaine(Request $request, PlanningValidationService $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $start = new \DateTime($data['start']);
        $end = new \DateTime($data['end']);

        $result = $validator->validerSemaine($start, $end);
        return new JsonResponse($result);
    }

    #[Route('/publier-semaine', name: 'api_planning_publier', methods: ['POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function publierSemaine(Request $request, CreneauPlanningRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $start = new \DateTime($data['start']);
        $end = new \DateTime($data['end']);

        $creneaux = $repo->createQueryBuilder('c')
            ->where('c.dateDebut >= :start')
            ->andWhere('c.dateFin <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        foreach ($creneaux as $c) {
            if ($c->getStatut() === CreneauPlanning::STATUT_BROUILLON) {
                $c->setStatut(CreneauPlanning::STATUT_PUBLIE);
            }
        }
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }
}
