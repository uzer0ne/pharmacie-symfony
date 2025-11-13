<?php

namespace App\Form;

use App\Entity\LigneVente;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneVenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom_produit', // Affiche le nom du produit dans la liste
                'label' => 'Produit',
                'placeholder' => 'Choisir un produit',
                'attr' => [
                    'class' => 'form-select produit-select', // Classe pour JS
                ],
                'group_by' => function($choice, $key, $value) {
                    // Optionnel: groupe les produits par leur statut de stock
                    return $choice->getStatutStock();
                },
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control quantite-input',
                    'min' => 1,
                    'value' => 1, // Quantité par défaut
                ],
            ])
            // Le prix_unitaire_vente sera défini dans le contrôleur
            // pour s'assurer qu'il est correct et non modifiable par l'utilisateur.
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneVente::class,
        ]);
    }
}