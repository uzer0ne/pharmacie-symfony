<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\LigneOrdonnance;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class LigneOrdonnanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                // Juste le nom + dosage pour l'option HTML : TomSelect s'occupe du rendu visuel
                'choice_label' => function (Produit $produit) {
                    return $produit->getNomProduit() . ' - ' . $produit->getDosageProduit();
                },
                'choice_attr' => function(Produit $produit) {
                    return [
                        'data-stock'       => $produit->getStockActuel(),
                        'data-famille'     => $produit->getFamille() ?? 'Non définie',
                        'data-dosage'      => $produit->getDosageProduit() ?? '',
                        'data-description' => $produit->getDescription() ?? 'Aucune description.',
                    ];
                },
                'placeholder' => 'Choisir un produit',
                'label'       => 'Produit prescrit *',
                'attr'        => ['class' => 'form-select produit-select'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.nom_produit', 'ASC');
                },
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité *',
                'attr'  => ['min' => 1, 'class' => 'form-control quantite-input'],
                'data'  => 1, // valeur par défaut
            ])
            ->add('posologie', TextareaType::class, [
                'label'    => 'Posologie',
                'required' => false,
                'attr'     => ['rows' => 2, 'placeholder' => 'Ex: 1 comprimé matin et soir'],
            ])
            ->add('dureeTraitement', IntegerType::class, [
                'label'    => 'Durée du traitement (jours)',
                'required' => false,
                'attr'     => ['min' => 1],
            ])
            ->add('renouvellementsAutorises', IntegerType::class, [
                'label'    => 'Renouvellements autorisés',
                'required' => false,
                'data'     => 0, // par défaut 0 renouvellements
                'attr'     => ['min' => 0],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneOrdonnance::class,
        ]);
    }
}
