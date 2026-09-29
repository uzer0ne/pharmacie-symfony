<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HoraireOuvertureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoraireOuvertureRepository::class)]
class HoraireOuverture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $jourSemaine = null; // 1 = Lundi, 7 = Dimanche

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebutMatin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFinMatin = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureDebutAprem = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFinAprem = null;

    #[ORM\Column]
    private bool $estOuvert = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJourSemaine(): ?int
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(int $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;
        return $this;
    }

    public function getHeureDebutMatin(): ?\DateTimeInterface
    {
        return $this->heureDebutMatin;
    }

    public function setHeureDebutMatin(?\DateTimeInterface $heureDebutMatin): static
    {
        $this->heureDebutMatin = $heureDebutMatin;
        return $this;
    }

    public function getHeureFinMatin(): ?\DateTimeInterface
    {
        return $this->heureFinMatin;
    }

    public function setHeureFinMatin(?\DateTimeInterface $heureFinMatin): static
    {
        $this->heureFinMatin = $heureFinMatin;
        return $this;
    }

    public function getHeureDebutAprem(): ?\DateTimeInterface
    {
        return $this->heureDebutAprem;
    }

    public function setHeureDebutAprem(?\DateTimeInterface $heureDebutAprem): static
    {
        $this->heureDebutAprem = $heureDebutAprem;
        return $this;
    }

    public function getHeureFinAprem(): ?\DateTimeInterface
    {
        return $this->heureFinAprem;
    }

    public function setHeureFinAprem(?\DateTimeInterface $heureFinAprem): static
    {
        $this->heureFinAprem = $heureFinAprem;
        return $this;
    }

    public function isEstOuvert(): bool
    {
        return $this->estOuvert;
    }

    public function setEstOuvert(bool $estOuvert): static
    {
        $this->estOuvert = $estOuvert;
        return $this;
    }
}
