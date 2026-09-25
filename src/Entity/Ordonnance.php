<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrdonnanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdonnanceRepository::class)]
class Ordonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Ordonnance', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $dateOrdonnance = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(name: 'Id_Patient', referencedColumnName: 'Id_Patient', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(name: 'Id_Medecin', referencedColumnName: 'Id_Medecin')]
    private ?Medecin $medecin = null;

    /**
     * @var Collection<int, LigneOrdonnance>
     */
    #[ORM\OneToMany(mappedBy: 'ordonnance', targetEntity: LigneOrdonnance::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    /**
     * @var Collection<int, Vente>
     */
    #[ORM\OneToMany(mappedBy: 'ordonnance', targetEntity: Vente::class)]
    private Collection $ventes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->ventes = new ArrayCollection();
        $this->dateOrdonnance = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateOrdonnance(): ?\DateTimeImmutable
    {
        return $this->dateOrdonnance;
    }

    public function setDateOrdonnance(\DateTimeImmutable $dateOrdonnance): self
    {
        $this->dateOrdonnance = $dateOrdonnance;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    public function getMedecin(): ?Medecin
    {
        return $this->medecin;
    }

    public function setMedecin(?Medecin $medecin): self
    {
        $this->medecin = $medecin;
        return $this;
    }

    /**
     * @return Collection<int, LigneOrdonnance>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneOrdonnance $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setOrdonnance($this);
        }
        return $this;
    }

    public function removeLigne(LigneOrdonnance $ligne): self
    {
        if ($this->lignes->removeElement($ligne)) {
            // S'assure que le côté propriétaire est mis à jour
            if ($ligne->getOrdonnance() === $this) {
                $ligne->setOrdonnance(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Vente>
     */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $vente): self
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes->add($vente);
            $vente->setOrdonnance($this);
        }
        return $this;
    }

    public function removeVente(Vente $vente): self
    {
        if ($this->ventes->removeElement($vente)) {
            if ($vente->getOrdonnance() === $this) {
                $vente->setOrdonnance(null);
            }
        }
        return $this;
    }

    /**
     * Règle de législation pharmaceutique : 
     * Une ordonnance ne peut faire l'objet d'une première délivrance que si elle
     * est présentée dans les 3 mois (90 jours) suivant sa date de prescription.
     */
    public function isValidForFirstDispense(): bool
    {
        if ($this->dateOrdonnance === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $expirationDate = $this->dateOrdonnance->modify('+90 days');

        // Valide si la date d'aujourd'hui est entre la date de l'ordonnance et sa date d'expiration
        return $now >= $this->dateOrdonnance && $now <= $expirationDate;
    }
}
