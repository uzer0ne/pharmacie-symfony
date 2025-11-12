<?php

namespace App\Entity;

use App\Repository\MedecinRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
class Medecin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "Id_Medecin", type: "integer")]
    private ?int $idMedecin = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_medecin = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom_medecin = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_medecin = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_medecin = null;

    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Ordonnance::class)]
    private Collection $ordonnances;

    public function __construct()
    {
        $this->ordonnances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->idMedecin;
    }

    public function getNomMedecin(): ?string
    {
        return $this->nom_medecin;
    }

    public function setNomMedecin(string $nom_medecin): static
    {
        $this->nom_medecin = $nom_medecin;

        return $this;
    }

    public function getPrenomMedecin(): ?string
    {
        return $this->prenom_medecin;
    }

    public function setPrenomMedecin(string $prenom_medecin): static
    {
        $this->prenom_medecin = $prenom_medecin;

        return $this;
    }

    public function getContactMedecin(): ?string
    {
        return $this->contact_medecin;
    }

    public function setContactMedecin(string $contact_medecin): static
    {
        $this->contact_medecin = $contact_medecin;

        return $this;
    }

    public function getAdresseMedecin(): ?string
    {
        return $this->adresse_medecin;
    }

    public function setAdresseMedecin(string $adresse_medecin): static
    {
        $this->adresse_medecin = $adresse_medecin;

        return $this;
    }
}
