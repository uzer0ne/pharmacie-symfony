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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $taux_remboursement = null;

     #[ORM\ManyToMany(targetEntity: Patient::class, mappedBy: 'mutuelles')]
    private Collection $patients;
    public function getId(): ?int
    {
        return $this->idMutuelle;
    }

    public function __construct()
    {
        $this->patients = new ArrayCollection();
    }

    public function getIdMutuelle(): ?int
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

    public function getTauxRemboursement(): ?string
    {
        return $this->taux_remboursement;
    }

    public function setTauxRemboursement(string $taux_remboursement): static
    {
        $this->taux_remboursement = $taux_remboursement;

        return $this;
    }

   
    public function getPatients(): Collection
    {
        return $this->patients;
    }

}
