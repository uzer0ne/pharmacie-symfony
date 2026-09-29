<?php

namespace App\Entity;

use App\Repository\MutuelleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


#[ORM\Entity(repositoryClass: MutuelleRepository::class)]
class Mutuelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Mutuelle', type: 'integer')]
    private ?int $idMutuelle = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_mutuelle = null;

    #[ORM\Column(length: 255)]
    private ?string $contact_mutuelle = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code_amc = null;

    #[ORM\OneToMany(mappedBy: 'mutuelle', targetEntity: PatientMutuelle::class)]
    private Collection $patientMutuelles;

    public function __construct()
    {
        $this->patientMutuelles = new ArrayCollection();
    }

    public function getIdMutuelle(): ?int
    {
        return $this->idMutuelle;
    }

    public function getId(): ?int
    {
        return $this->idMutuelle;
    }

    public function getNomMutuelle(): ?string
    {
        return $this->nom_mutuelle;
    }

    public function setNomMutuelle(string $nom_mutuelle): static
    {
        $this->nom_mutuelle = $nom_mutuelle;

        return $this;
    }

    public function getContactMutuelle(): ?string
    {
        return $this->contact_mutuelle;
    }

    public function setContactMutuelle(string $contact_mutuelle): static
    {
        $this->contact_mutuelle = $contact_mutuelle;

        return $this;
    }

    public function getCodeAmc(): ?string
    {
        return $this->code_amc;
    }

    public function setCodeAmc(string $code_amc): static
    {
        $this->code_amc = $code_amc;

        return $this;
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
            $patientMutuelle->setMutuelle($this);
        }

        return $this;
    }

    public function removePatientMutuelle(PatientMutuelle $patientMutuelle): static
    {
        if ($this->patientMutuelles->removeElement($patientMutuelle)) {
            if ($patientMutuelle->getMutuelle() === $this) {
                $patientMutuelle->setMutuelle(null);
            }
        }

        return $this;
    }
}
