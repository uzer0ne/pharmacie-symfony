<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CreneauPlanningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreneauPlanningRepository::class)]
class CreneauPlanning
{
    public const TYPE_COMPTOIR = 'COMPTOIR';
    public const TYPE_BACK_OFFICE = 'BACK_OFFICE';
    public const TYPE_GARDE_JOURNEE = 'GARDE_JOURNEE';
    public const TYPE_GARDE_NUIT = 'GARDE_NUIT';
    public const TYPE_ASTREINTE = 'ASTREINTE';

    public const STATUT_BROUILLON = 'BROUILLON';
    public const STATUT_PUBLIE = 'PUBLIE';
    public const STATUT_ANNULE = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 50)]
    private string $typeCreneau = self::TYPE_COMPTOIR;

    #[ORM\Column(length: 50)]
    private string $statut = self::STATUT_BROUILLON;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getTypeCreneau(): string
    {
        return $this->typeCreneau;
    }

    public function setTypeCreneau(string $typeCreneau): static
    {
        $this->typeCreneau = $typeCreneau;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }
}
