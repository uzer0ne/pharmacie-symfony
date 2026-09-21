<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class LigneOrdonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ordonnance::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'ordonnance_id', referencedColumnName: 'Id_Ordonnance', nullable: false)]
    private ?Ordonnance $ordonnance = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'Id_Produit', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $quantite = 1;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $posologie = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dureeTraitement = null; // Exprimé en jours

    #[ORM\Column(type: Types::INTEGER)]
    private int $renouvellementsAutorises = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): self
    {
        $this->ordonnance = $ordonnance;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getPosologie(): ?string
    {
        return $this->posologie;
    }

    public function setPosologie(string $posologie): self
    {
        $this->posologie = $posologie;
        return $this;
    }

    public function getDureeTraitement(): ?int
    {
        return $this->dureeTraitement;
    }

    public function setDureeTraitement(int $dureeTraitement): self
    {
        $this->dureeTraitement = $dureeTraitement;
        return $this;
    }

    public function getRenouvellementsAutorises(): int
    {
        return $this->renouvellementsAutorises;
    }

    public function setRenouvellementsAutorises(int $renouvellementsAutorises): self
    {
        $this->renouvellementsAutorises = $renouvellementsAutorises;
        return $this;
    }
}
