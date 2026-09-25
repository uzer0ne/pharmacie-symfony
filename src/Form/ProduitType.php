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
            ->add('date_fabrication')
            ->add('date_expiration')
            ->add('dosage_produit')
            ->add('code_cip', TextType::class, [
                'label' => 'Code CIP',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Scannez le code-barre ici...',
                    'autofocus' => true, // Le curseur se mettra ici automatiquement à l'ouverture de la page
                    'class' => 'form-control fw-bold' // On le met en gras pour bien le voir
                ],
                'help' => 'Code Identifiant de Présentation'
            ])
            ->add('nom_produit')
            ->add('famille', TextType::class, [
                'label' => 'Famille Thérapeutique',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Antibiotique, Cardiologie...']
            ])
            ->add('empZone', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Zone (Ex: RX, OTC, FRIDGE)',
                'required' => false,
                'choices' => [
                    'RX - Ordonnancier' => 'RX',
                    'OTC - Comptoir' => 'OTC',
                    'FRIDGE - Frigo' => 'FRIDGE',
                    'MAT - Matériel' => 'MAT',
                    'STOCK - Réserve' => 'STOCK',
                    'PARA - Parapharmacie' => 'PARA',
                    'SAFE - Coffre' => 'SAFE'
                ],
                'placeholder' => 'Choisir une zone...'
            ])
            ->add('empColonne', TextType::class, [
                'label' => 'Baie / Colonne (Ex: 01, 02)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 03']
            ])
            ->add('empNiveau', TextType::class, [
                'label' => 'Étagère (Ex: A, B, C)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: C']
            ])
            ->add('empPosition', TextType::class, [
                'label' => 'Position sur le niveau (Ex: 01, 02)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 02']
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description & Indications',
                'required' => false,
                'attr' => ['placeholder' => 'Ce que fait le produit...', 'style' => 'height: 100px;']
            ])
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
                'label' => 'Stock',
                'disabled' => true,
                'help' => 'Calculé via l\'historique des mouvements.'
            ])
            ->add('ajustement_quantite', IntegerType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Ajuster le stock (+ ou -)',
                'attr' => [
                    'placeholder' => 'ex: 10 pour ajouter, -5 pour retirer'
                ],
                'help' => 'Laissez vide si vous ne modifiez pas le stock.'
            ])
            ->add('ajustement_motif', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Motif de l\'ajustement',
                'attr' => [
                    'placeholder' => 'ex: Inventaire, Casse, Réception commande...'
                ]
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
