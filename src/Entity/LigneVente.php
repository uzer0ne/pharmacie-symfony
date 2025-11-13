<?php

namespace App\Entity;

use App\Repository\LigneVenteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneVenteRepository::class)]
class LigneVente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_LigneVente', type: 'integer')]
    private ?int $id = null;

    // La LigneVente appartient à une Vente
    #[ORM\ManyToOne(inversedBy: 'ligneVentes')]
    #[ORM\JoinColumn(name: 'Id_Vente', referencedColumnName: 'Id_Vente', nullable: false)]
    private ?Vente $vente = null;

    // La LigneVente concerne un Produit
    #[ORM\ManyToOne(inversedBy: 'ligneVentes')]
    #[ORM\JoinColumn(name: 'Id_Produit', referencedColumnName: 'Id_Produit', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $quantite = 1;

    // On stocke le prix au moment de la vente,
    // au cas où le prix du produit changerait plus tard.
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix_unitaire_vente = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaireVente(): ?string
    {
        return $this->prix_unitaire_vente;
    }

    public function setPrixUnitaireVente(string $prix_unitaire_vente): static
    {
        $this->prix_unitaire_vente = $prix_unitaire_vente;

        return $this;
    }

    // Méthode utilitaire pour obtenir le total de la ligne
    public function getPrixTotal(): float
    {
        return $this->quantite * (float) $this->prix_unitaire_vente;
    }
}