<?php

namespace App\Controller;
use App\Entity\Patient;
use App\Entity\Mutuelle;
use App\Form\Ordonnance;
use App\Form\PatientType;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/patient' )]
class PatientController extends AbstractController
{
    #[Route(name: 'app_patient_index', methods: ['GET'])]
    public function index(PatientRepository $patientRepository): Response
    {
        $patient = new Patient();
        $form = $this->createForm(PatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ⭐ Doctrine va automatiquement gérer la table Posseder
            // grâce à la relation ManyToMany définie dans les entités
            
            $entityManager->persist($patient);
            $entityManager->flush();

            $this->addFlash('success', 'Patient créé avec succès !');

            return $this->redirectToRoute('app_patient_show', ['idPatient' => $patient->getIdPatient()], Response::HTTP_SEE_OTHER);
        }
        return $this->render('patient/index.html.twig', [
            'patients' => $patientRepository->findAll(),
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

   #[Route('/{idPatient}', name: 'app_patient_show', methods: ['GET'])]
    public function show(int $idPatient, PatientRepository $patientRepository): Response
    {
        $patient = $patientRepository->find($idPatient);

        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé.');
        }

        return $this->render('patient/show.html.twig', [
            'patient' => $patient,
        ]);
    }

    #[Route('/{idPatient}/edit', name: 'app_patient_edit', methods: ['GET', 'POST'])]
    public function edit(int $idPatient, Request $request, PatientRepository $patientRepository, EntityManagerInterface $entityManager): Response
    {
        $patient = $patientRepository->find($idPatient);

        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé.');
        }

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
    public function delete(
        int $idPatient,
        Request $request,
        PatientRepository $patientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $patient = $patientRepository->find($idPatient);

        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete'.$patient->getIdPatient(), $request->request->get('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_patient_index', [], Response::HTTP_SEE_OTHER);
    }

    
}
