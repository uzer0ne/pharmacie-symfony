<?php

namespace App\Form;

use App\Entity\Vente;
use App\Entity\Patient;
use App\Entity\Ordonnance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('date_vente', DateType::class, [
              //  'widget' => 'single_text',
                //'label' => 'Date de la Vente',
                //'attr' => ['class' => 'form-control'],
            //])
            // ->add('montant_total') // Le montant sera calculé dans le contrôleur

            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => function(Patient $patient) {
                    return $patient->getPrenomPatient() . ' ' . $patient->getNomPatient();
                },
                'placeholder' => 'Vente libre (sans patient)',
                'label' => 'Patient (Optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('ordonnance', EntityType::class, [
                'class' => Ordonnance::class,
                'choice_label' => function(Ordonnance $ordonnance) {
                    return 'Ordonnance n°' . $ordonnance->getId() . ' du ' . $ordonnance->getDateOrdonnance()->format('d/m/Y');
                },
                'placeholder' => 'Aucune ordonnance liée',
                'label' => 'Ordonnance (Optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                // TODO: Ajouter une logique (JS ou query_builder) pour filtrer
                // les ordonnances en fonction du patient sélectionné ci-dessus.
            ])

            // ===== C'EST LA PARTIE LA PLUS IMPORTANTE =====
            ->add('ligneVentes', CollectionType::class, [
                'entry_type' => LigneVenteType::class, // Le formulaire qu'on vient de créer
                'entry_options' => ['label' => false],
                'label' => false, // On n'affiche pas "Ligne Ventes"
                
                'allow_add' => true,    // Autorise l'ajout de nouveaux éléments
                'allow_delete' => true, // Autorise la suppression d'éléments
                'by_reference' => false, // Force l'appel de addLigneVente() et removeLigneVente() sur l'entité Vente
                
                'prototype' => true, // Nécessaire pour le JS
                'attr' => [
                    'class' => 'collection-ligne-ventes',
                    'data-prototype-name' => '__name__', //
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
        ]);
    }
}