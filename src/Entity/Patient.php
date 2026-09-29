<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[UniqueEntity(fields: ['nir'], message: 'Ce numéro de sécurité sociale est déjà enregistré.', ignoreNull: true)]
class Patient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Patient', type: 'integer')]
    private ?int $idPatient = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du patient est obligatoire.')]
    private ?string $nom_patient = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom du patient est obligatoire.')]
    private ?string $prenom_patient = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_patient = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_naissance = null;

    #[ORM\Column(length: 15, unique: true, nullable: true)]
    #[Assert\Length(
        exactly: 15,
        exactMessage: 'Le numéro de sécurité sociale doit comporter exactement {{ limit }} chiffres.'
    )]
    #[Assert\Regex(
        pattern: '/^\d{15}$/',
        message: 'Le numéro de sécurité sociale ne doit contenir que des chiffres (15 au total).'
    )]
    private ?string $nir = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: PatientMutuelle::class)]
    private Collection $patientMutuelles;

    // ▼▼▼ AJOUTEZ CETTE PROPRIÉTÉ ▼▼▼
    #[ORM\OneToMany(mappedBy: "patient", targetEntity: Vente::class)]
    private Collection $ventes;

    public function __construct()
    {
        $this->patientMutuelles = new ArrayCollection();
        $this->ordonnances = new ArrayCollection();
        $this->ventes = new ArrayCollection();
    }
   
    /**
     * @return Collection<int, PatientMutuelle>
     */
    public function getPatientMutuelles(): Collection
    {
        return $this->patientMutuelles;
    }

    public function addPatientMutuelle(PatientMutuelle $patientMutuelle): static
    {
        if (!$this->patientMutuelles->contains($patientMutuelle)) {
            $this->patientMutuelles->add($patientMutuelle);
            $patientMutuelle->setPatient($this);
        }

        return $this;
    }

    public function removePatientMutuelle(PatientMutuelle $patientMutuelle): static
    {
        if ($this->patientMutuelles->removeElement($patientMutuelle)) {
            if ($patientMutuelle->getPatient() === $this) {
                $patientMutuelle->setPatient(null);
            }
        }

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

    public function getId(): ?int
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

    public function getNir(): ?string
    {
        return $this->nir;
    }

    public function setNir(?string $nir): static
    {
        $this->nir = $nir;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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
            $vente->setPatient($this);
        }

        return $this;
    }

    public function removeVente(Vente $vente): static
    {
        if ($this->ventes->removeElement($vente)) {
            // set the owning side to null (unless already changed)
            if ($vente->getPatient() === $this) {
                $vente->setPatient(null);
            }
        }

        return $this;
    }

}
