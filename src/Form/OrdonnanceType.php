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
            ->add('dateOrdonnance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de l\'ordonnance'
            ])
            ->add('patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => 'NomPatient',
                'placeholder' => 'Choisir un patient',
            ])
            ->add('medecin', EntityType::class, [
                'class' => Medecin::class,
                'choice_label' => 'NomMedecin',
                'placeholder' => 'Choisir un médecin',
            ])
            ->add('lignes', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => LigneOrdonnanceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false
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
