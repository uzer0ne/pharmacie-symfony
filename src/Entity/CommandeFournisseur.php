<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommandeFournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeFournisseurRepository::class)]
#[ORM\Table(name: 'commande_fournisseur')]
class CommandeFournisseur
{
    public const STATUT_BROUILLON    = 'BROUILLON';
    public const STATUT_A_VALIDER    = 'A_VALIDER';
    public const STATUT_VALIDEE      = 'VALIDEE';
    public const STATUT_ENVOYEE      = 'ENVOYEE';
    public const STATUT_RECEPTIONNEE = 'RECEPTIONNEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_BROUILLON;

    /** @var Collection<int, LigneCommandeFournisseur> */
    #[ORM\OneToMany(
        mappedBy: 'commande',
        targetEntity: LigneCommandeFournisseur::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $lignes;

    #[ORM\ManyToOne(targetEntity: Fournisseur::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fournisseur $fournisseur = null;

    public function __construct()
    {
        $this->date_creation = new \DateTime();
        $this->lignes = new ArrayCollection();
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;
        return $this;
    }

    public function getId(): ?int { return $this->id; }

    public function getDateCreation(): \DateTimeInterface { return $this->date_creation; }
    public function setDateCreation(\DateTimeInterface $d): static { $this->date_creation = $d; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    /** @return Collection<int, LigneCommandeFournisseur> */
    public function getLignes(): Collection { return $this->lignes; }

    public function addLigne(LigneCommandeFournisseur $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
        return $this;
    }

    public function removeLigne(LigneCommandeFournisseur $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            if ($ligne->getCommande() === $this) {
                $ligne->setCommande(null);
            }
        }
        return $this;
    }

    /** Montant total de la commande */
    public function getMontantTotal(): float
    {
        return array_sum(
            $this->lignes->map(fn(LigneCommandeFournisseur $l) => $l->getMontantLigne())->toArray()
        );
    }
}
