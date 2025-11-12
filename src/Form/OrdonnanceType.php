<?php

namespace App\Form;

use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\Produit;
use App\Entity\Medecin;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrdonnanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_ordonnance', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('duree_traitement', TextareaType::class, [
                'required' => false,
                'label' => 'Durée du traitement / Consignes'
            ])
            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => 'NomPatient',
                'placeholder' => 'Choisir un patient',
            ])
            ->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                'choice_label' => 'NomMedecin', // adapte
                'placeholder' => 'Choisir un médecin',
            ])
           ->add('produits', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nomProduit',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'label' => 'Produits prescrits'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ordonnance::class,
        ]);
    }
}
