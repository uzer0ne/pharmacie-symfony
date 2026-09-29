<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PatientMutuelleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientMutuelleRepository::class)]
class PatientMutuelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'patientMutuelles')]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'Id_Patient', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(inversedBy: 'patientMutuelles')]
    #[ORM\JoinColumn(name: 'mutuelle_id', referencedColumnName: 'Id_Mutuelle', nullable: false)]
    private ?Mutuelle $mutuelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numero_adherent = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_debut_validite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_fin_validite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type_convention = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $taux_couverture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getMutuelle(): ?Mutuelle
    {
        return $this->mutuelle;
    }

    public function setMutuelle(?Mutuelle $mutuelle): static
    {
        $this->mutuelle = $mutuelle;

        return $this;
    }

    public function getNumeroAdherent(): ?string
    {
        return $this->numero_adherent;
    }

    public function setNumeroAdherent(string $numero_adherent): static
    {
        $this->numero_adherent = $numero_adherent;

        return $this;
    }

    public function getDateDebutValidite(): ?\DateTimeInterface
    {
        return $this->date_debut_validite;
    }

    public function setDateDebutValidite(\DateTimeInterface $date_debut_validite): static
    {
        $this->date_debut_validite = $date_debut_validite;

        return $this;
    }

    public function getDateFinValidite(): ?\DateTimeInterface
    {
        return $this->date_fin_validite;
    }

    public function setDateFinValidite(\DateTimeInterface $date_fin_validite): static
    {
        $this->date_fin_validite = $date_fin_validite;

        return $this;
    }

    public function getTypeConvention(): ?string
    {
        return $this->type_convention;
    }

    public function setTypeConvention(?string $type_convention): static
    {
        $this->type_convention = $type_convention;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getTauxCouverture(): ?string
    {
        return $this->taux_couverture;
    }

    public function setTauxCouverture(string $taux_couverture): static
    {
        $this->taux_couverture = $taux_couverture;

        return $this;
    }

    /**
     * Détermine si ce contrat est valide AUJOURD'HUI en fonction des dates.
     * C'est cette méthode qui doit être utilisée pour l'affichage et les calculs.
     * Le flag "actif" ne sert qu'à la suspension manuelle (perte/résiliation).
     */
    public function isCurrentlyValid(): bool
    {
        $today = new \DateTime('today');
        return $this->actif === true
            && $this->date_debut_validite !== null
            && $this->date_fin_validite !== null
            && $this->date_debut_validite <= $today
            && $this->date_fin_validite >= $today;
    }
}
