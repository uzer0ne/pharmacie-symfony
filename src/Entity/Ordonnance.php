<?php

namespace App\Entity;

use App\Repository\OrdonnanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: OrdonnanceRepository::class)]
class Ordonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Ordonnance', type: 'integer')]
    private ?int $idOrdonnance = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_ordonnance = null;

    #[ORM\Column(length: 255)]
    private ?string $durée_traitement = null;

    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(name: 'Id_Patient', referencedColumnName: 'Id_Patient', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToMany(targetEntity: Produit::class, inversedBy: 'ordonnances')]
    #[ORM\JoinTable(name: 'Regroupe')]
    #[ORM\JoinColumn(name: 'Id_Ordonnance', referencedColumnName: 'Id_Ordonnance')]
    #[ORM\InverseJoinColumn(name: 'Id_Produit', referencedColumnName: 'Id_Produit')]
    private Collection $produits;

    #[ORM\ManyToOne(targetEntity: Medecin::class, inversedBy: 'ordonnances')]
    #[ORM\JoinColumn(name: "Id_Medecin", referencedColumnName: "Id_Medecin")]
    private ?Medecin $medecin = null;
    #[ORM\OneToMany(mappedBy: "ordonnance", targetEntity: Vente::class)]
    private Collection $ventes;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
        $this->medecins = new ArrayCollection();
        $this->dateOrdonnance = new \DateTime();
        $this->ventes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->idOrdonnance;
    }

    public function getDateOrdonnance(): ?\DateTime
    {
        return $this->date_ordonnance;
    }

    public function setDateOrdonnance(\DateTime $date_ordonnance): self
    {
        $this->date_ordonnance = $date_ordonnance;

        return $this;
    }

    public function getDureeTraitement(): ?string
    {
        return $this->durée_traitement;
    }

    public function setDureeTraitement(string $durée_traitement): static
    {
        $this->durée_traitement = $durée_traitement;

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
     * return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    
    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            // ⭐ CORRECTION : Utiliser addOrdonnance() au lieu de setOrdonnance()
            $produit->addOrdonnance($this);
        }
        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            // ⭐ CORRECTION : Utiliser removeOrdonnance()
            $produit->removeOrdonnance($this);
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

    public function addVente(Vente $vente): static
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes->add($vente);
            $vente->setOrdonnance($this);
        }

        return $this;
    }

    public function removeVente(Vente $vente): static
    {
        if ($this->ventes->removeElement($vente)) {
            // set the owning side to null (unless already changed)
            if ($vente->getOrdonnance() === $this) {
                $vente->setOrdonnance(null);
            }
        }

        return $this;
    }
}
