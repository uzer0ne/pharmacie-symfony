<?php

namespace App\Controller;
use App\Entity\Patient;
use App\Entity\Mutuelle;
use App\Form\Ordonnance;
use App\Form\PatientType;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient' )]
class PatientController extends AbstractController
{
    #[Route(name: 'app_patient_index', methods: ['GET'])]
    public function index(PatientRepository $patientRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $patientRepository->createQueryBuilder('p')
            ->orderBy('p.nom_patient', 'ASC')
            ->getQuery();

        $patients = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('patient/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/new', name: 'app_patient_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($patient);
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/new.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);  
         
    }
    #[Route('/{idPatient}/mutuelle/add', name: 'app_patient_add_mutuelle', methods: ['GET', 'POST'])]
    public function addMutuelle(
        #[MapEntity(mapping: ['idPatient' => 'idPatient'])] Patient $patient, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        $patientMutuelle = new \App\Entity\PatientMutuelle();
        $patientMutuelle->setPatient($patient);

        $form = $this->createForm(\App\Form\PatientMutuelleType::class, $patientMutuelle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($patientMutuelle);
            $entityManager->flush();

            $this->addFlash('success', 'La carte mutuelle a été ajoutée au dossier du patient.');
            return $this->redirectToRoute('app_patient_show', ['idPatient' => $patient->getIdPatient()]);
        }

        return $this->render('patient/add_mutuelle.html.twig', [
            'patient' => $patient,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idPatient}/mutuelle/{id}/toggle', name: 'app_patient_toggle_mutuelle', methods: ['POST'])]
    public function toggleMutuelle(
        #[MapEntity(mapping: ['idPatient' => 'idPatient'])] Patient $patient,
        \App\Entity\PatientMutuelle $patientMutuelle,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle_mutuelle_' . $patientMutuelle->getId(), $request->request->get('_token'))) {
            $patientMutuelle->setActif(!$patientMutuelle->isActif());
            $entityManager->flush();
            $status = $patientMutuelle->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('info', 'Contrat mutuelle ' . $status . ' avec succès.');
        }
        return $this->redirectToRoute('app_patient_show', ['idPatient' => $patient->getIdPatient()]);
    }

   #[Route('/{idPatient}', name: 'app_patient_show', methods: ['GET'])]
    public function show(#[MapEntity(mapping: ['idPatient' => 'idPatient'])] Patient $patient): Response
    {
        // Symfony a déjà trouvé le patient grâce à {idPatient} ou renvoyé une 404
        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[Route('/{idPatient}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, #[MapEntity(mapping: ['idPatient' => 'idPatient'])] Patient $patient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('patient/edit.html.twig', [
            'patient' => $patient,
            'form' => $form,
        ]);
    }

    #[Route('/{idPatient}', name: 'app_patient_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PHARMACIEN')]
    public function delete(Request $request, #[MapEntity(mapping: ['idPatient' => 'idPatient'])] Patient $patient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$patient->getIdPatient(), $request->request->get('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
    }

    
}
