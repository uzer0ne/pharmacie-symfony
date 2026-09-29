<?php

namespace App\Form;

use App\Entity\Mutuelle;
use App\Entity\PatientMutuelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientMutuelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mutuelle', EntityType::class, [
                'class' => Mutuelle::class,
                'choice_label' => 'nomMutuelle',
                'label' => 'Organisme de Mutuelle',
                'attr' => ['class' => 'form-select']
            ])
            ->add('numero_adherent', TextType::class, [
                'label' => 'Numéro d\'adhérent (sur la carte)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 123456789 (optionnel)']
            ])
            ->add('taux_couverture', NumberType::class, [
                'label' => 'Taux de couverture (%)',
                'scale' => 2,
                'attr' => ['placeholder' => 'Ex: 100.00', 'step' => '0.01']
            ])
            ->add('type_convention', TextType::class, [
                'label' => 'Type de convention / Garantie',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: RO, RC, etc.']
            ])
            ->add('date_debut_validite', DateType::class, [
                'label' => 'Début de validité',
                'widget' => 'single_text',
            ])
            ->add('date_fin_validite', DateType::class, [
                'label' => 'Fin de validité',
                'widget' => 'single_text',
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Contrat Actif',
                'required' => false,
                'data' => true // Coché par défaut
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PatientMutuelle::class,
        ]);
    }
}
