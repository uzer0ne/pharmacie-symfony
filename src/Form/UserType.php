<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom de l\'employé']
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom de l\'employé']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email'
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle système (Droits)',
                'choices' => [
                    'Pharmacien (Accès Total)' => 'ROLE_PHARMACIEN',
                    'Logisticien (Gestion Stock)' => 'ROLE_GESTIONNAIRE_STOCK',
                    'Vendeur (Caissier)' => 'ROLE_CAISSIER',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('qualification', ChoiceType::class, [
                'label' => 'Qualification (Planning)',
                'choices' => [
                    'Pharmacien Titulaire' => 'TITULAIRE',
                    'Pharmacien Adjoint' => 'ADJOINT',
                    'Préparateur en pharmacie' => 'PREPARATEUR',
                    'Étudiant en pharmacie' => 'ETUDIANT',
                    'Logisticien' => 'LOGISTICIEN',
                    'Vendeur' => 'VENDEUR',
                ],
                'placeholder' => 'Choisir une qualification',
                'required' => false,
            ])
            ->add('tempsTravailHebdo', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, [
                'label' => 'Temps de travail hebdo (Heures)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 35']
            ])
            ->add('numeroRpps', TextType::class, [
                'label' => 'Numéro RPPS (Pharmaciens)',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laisser vide pour ne pas modifier'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
