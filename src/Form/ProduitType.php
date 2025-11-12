<?php

namespace App\Form;

use App\Entity\Ordonnance;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code_produit')
            ->add('date_fabrication')
            ->add('date_expiration')
            ->add('dosage_produit')
            ->add('code_cip', TextType::class, [
                'label' => 'Code CIP',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 3400936676324'
                ],
                'help' => 'Code Identifiant de Présentation'
            ])
            ->add('nom_produit')
            ->add('prix_produit')
            /***->add('ordonnances', EntityType::class, [
                'class' => Ordonnance::class,
                'choice_label' => 'id',
                'multiple' => true,
                'expanded' => false,
                'required' => false, //
            ])***/
            ->add('prix_achat', NumberType::class, [
                'label' => 'Prix d\'achat HT',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0.00'
                ]
            ])
            ->add('prix_produit', NumberType::class, [
                'label' => 'Prix de vente TTC',
                'scale' => 2,
                'attr' => [
                    'placeholder' => '0.00'
                ]
            ])
            // ⭐ NOUVEAUX CHAMPS STOCKS
            ->add('stock_actuel', IntegerType::class, [
                'label' => 'Stock actuel',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0'
                ],
                'help' => 'Quantité actuellement en stock'
            ])
            ->add('stock_minimum', IntegerType::class, [
                'label' => 'Stock minimum',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '5'
                ],
                'help' => 'Seuil d\'alerte critique'
            ])
            ->add('stock_alerte', IntegerType::class, [
                'label' => 'Stock d\'alerte',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '10'
                ],
                'help' => 'Seuil d\'alerte préventive'
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Produit actif',
                'required' => false,
                'help' => 'Désactiver pour masquer le produit'
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
