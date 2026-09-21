<?php

namespace App\Form;

use App\Entity\LigneVente;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository; // Nécessaire pour le query_builder
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
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.actif = :val')
                        ->andWhere('p.stock_actuel > 0')
                        ->setParameter('val', true)
                        ->orderBy('p.nom_produit', 'ASC');
                },
                'choice_label' => function(Produit $produit) {
                    return $produit->getNomProduit() . ' - ' . $produit->getDosageProduit();
                },
                'choice_attr' => function(Produit $produit) {
                    return [
                        'data-stock' => $produit->getStockActuel(),
                        'data-dosage' => $produit->getDosageProduit(),
                        'data-famille' => $produit->getFamille() ?? 'Non définie',
                        'data-description' => $produit->getDescription() ?? 'Aucune description',
                    ];
                },
                'label' => 'Produit',
                'placeholder' => 'Choisir un produit',
                'attr' => [
                    'class' => 'form-select produit-select',
                ],
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