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
use Symfony\Component\Form\Extension\Core\Type\TextType; // Nécessaire pour le champ scan

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

            // ⭐ CHAMP SCANNER (Non lié à la base de données)
            ->add('scan_cip', TextType::class, [
                'mapped' => false, // Ce champ n'est pas dans l'entité Vente
                'required' => false,
                'label' => 'Scanner un produit (Code CIP)',
                'attr' => [
                    'placeholder' => 'Cliquez ici et scannez...',
                    'class' => 'form-control mb-3',
                    'id' => 'scanner-input', // ID pour le JavaScript
                    'autofocus' => true, // Le curseur se mettra ici automatiquement
                    'inputmode' => 'numeric', // ⭐ Affiche le pavé numérique sur mobile
                ],
            ])

            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => function(Patient $patient) {
                    return $patient->getPrenomPatient() . ' ' . $patient->getNomPatient();
                },
                'placeholder' => 'Vente libre (sans ordonna)',
                'label' => 'Patient (Optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('creer_ordonnance', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'mapped' => false,
                'label' => 'Délivrance sur ordonnance (Applique le Tiers Payant et crée un historique)',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
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