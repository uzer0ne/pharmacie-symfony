<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


#[ORM\Entity(repositoryClass: PatientRepository::class)]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Patient', type: 'integer')]
    private ?int $idPatient = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_patient = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom_patient = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_patient = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_naissance = null;

    #[ORM\ManyToMany(targetEntity: Mutuelle::class, inversedBy: 'patients')]
    #[ORM\JoinTable(name: 'Posseder')]
    #[ORM\JoinColumn(name: 'Id_Patient', referencedColumnName: 'Id_Patient')]
    #[ORM\InverseJoinColumn(name: 'Id_Mutuelle', referencedColumnName: 'Id_Mutuelle')]
    private Collection $mutuelles;

    public function __construct()
    {
        $this->mutuelles = new ArrayCollection();
        $this->ordonnances = new ArrayCollection();
    }
   

    public function getMutuelles(): Collection
    {
        return $this->mutuelles;
    }

    public function addMutuelle(Mutuelle $mutuelle): self
    {
        if (!$this->mutuelles->contains($mutuelle)) {
            $this->mutuelles->add($mutuelle);
        }

        return $this;
    }

    public function removeMutuelle(Mutuelle $mutuelle): self
    {
        $this->mutuelles->removeElement($mutuelle);
        return $this;
    }

    #[ORM\OneToMany(mappedBy: "patient", targetEntity: Ordonnance::class)]
    private Collection $ordonnances;

    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }


    public function getIdPatient(): ?int
    {
        return $this->idPatient;
    }

    public function getNomPatient(): ?string
    {
        return $this->nom_patient;
    }

    public function setNomPatient(string $nom_patient): static
    {
        $this->nom_patient = $nom_patient;

        return $this;
    }

    public function getPrenomPatient(): ?string
    {
        return $this->prenom_patient;
    }

    public function setPrenomPatient(string $prenom_patient): static
    {
        $this->prenom_patient = $prenom_patient;

        return $this;
    }

    public function getAdressePatient(): ?string
    {
        return $this->adresse_patient;
    }

    public function setAdressePatient(string $adresse_patient): static
    {
        $this->adresse_patient = $adresse_patient;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->date_naissance;
    }

    public function setDateNaissance(\DateTime $date_naissance): static
    {
        $this->date_naissance = $date_naissance;

        return $this;
    }


}
