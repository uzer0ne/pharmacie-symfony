<?php

namespace App\Form;

use App\Entity\Patient;
use App\Entity\Mutuelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom_patient')
            ->add('prenom_patient')
            ->add('adresse_patient')
            ->add('date_naissance')
            ->add('nir', null, [
                'label' => 'Numéro de Sécurité Sociale (NIR)'
            ])
            ->add('telephone')
            ->add('email')
          
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}
