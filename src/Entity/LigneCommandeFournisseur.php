<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LigneCommandeFournisseurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeFournisseurRepository::class)]
#[ORM\Table(name: 'ligne_commande_fournisseur')]
class LigneCommandeFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CommandeFournisseur::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommandeFournisseur $commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'Id_Produit', nullable: false)]
    private ?Produit $produit = null;

    /** Quantité à commander (toujours positive) */
    #[ORM\Column(type: Types::INTEGER)]
    private int $quantite_commandee = 0;

    /** Prix d'achat unitaire au moment de la commande (snapshot) */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix_achat_unitaire = null;

    public function getId(): ?int { return $this->id; }

    public function getCommande(): ?CommandeFournisseur { return $this->commande; }
    public function setCommande(?CommandeFournisseur $commande): static { $this->commande = $commande; return $this; }

    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): static { $this->produit = $produit; return $this; }

    public function getQuantiteCommandee(): int { return $this->quantite_commandee; }
    public function setQuantiteCommandee(int $q): static { $this->quantite_commandee = max(0, $q); return $this; }

    public function getPrixAchatUnitaire(): ?string { return $this->prix_achat_unitaire; }
    public function setPrixAchatUnitaire(?string $p): static { $this->prix_achat_unitaire = $p; return $this; }

    /** Sous-total de la ligne */
    public function getMontantLigne(): float
    {
        return (float)($this->prix_achat_unitaire ?? 0) * $this->quantite_commandee;
    }
}
