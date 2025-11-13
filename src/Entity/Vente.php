<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Vente', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_vente = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant_total = '0.00';

    // Une vente peut être liée à un patient (mais c'est facultatif, pour les ventes libres)
    #[ORM\ManyToOne(targetEntity: Patient::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(name: 'Id_Patient', referencedColumnName: 'Id_Patient', nullable: true)]
    private ?Patient $patient = null;

    // Une vente peut être liée à une ordonnance (facultatif)
    #[ORM\ManyToOne(targetEntity: Ordonnance::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(name: 'Id_Ordonnance', referencedColumnName: 'Id_Ordonnance', nullable: true)]
    private ?Ordonnance $ordonnance = null;

    // Une vente contient plusieurs lignes de vente
    // cascade: persist/remove -> si on crée/supprime une Vente, ses LigneVente sont aussi créées/supprimées.
    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: LigneVente::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ligneVentes;

    // TODO: Ajouter la liaison vers l'Utilisateur (le vendeur) quand l'entité User existera
    // #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ventes')]
    // #[ORM\JoinColumn(name: 'Id_User', referencedColumnName: 'Id_User', nullable: false)]
    // private ?User $vendeur = null;

    public function __construct()
    {
        $this->ligneVentes = new ArrayCollection();
        $this->date_vente = new \DateTime(); // La date de vente est 'maintenant' par défaut
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateVente(): ?\DateTimeInterface
    {
        return $this->date_vente;
    }

    public function setDateVente(\DateTimeInterface $date_vente): static
    {
        $this->date_vente = $date_vente;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montant_total;
    }

    public function setMontantTotal(string $montant_total): static
    {
        $this->montant_total = $montant_total;

        return $this;
    }

    /**
     * @return Collection<int, LigneVente>
     */
    public function getLigneVentes(): Collection
    {
        return $this->ligneVentes;
    }

    public function addLigneVente(LigneVente $ligneVente): static
    {
        if (!$this->ligneVentes->contains($ligneVente)) {
            $this->ligneVentes->add($ligneVente);
            $ligneVente->setVente($this);
        }

        return $this;
    }

    public function removeLigneVente(LigneVente $ligneVente): static
    {
        if ($this->ligneVentes->removeElement($ligneVente)) {
            // set the owning side to null (unless already changed)
            if ($ligneVente->getVente() === $this) {
                $ligneVente->setVente(null);
            }
        }

        return $this;
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

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): static
    {
        $this->ordonnance = $ordonnance;

        return $this;
    }

    // Méthode utilitaire pour calculer le montant total
    public function calculerMontantTotal(): static
    {
        $total = 0.0;
        foreach ($this->ligneVentes as $ligne) {
            $total += (float) $ligne->getPrixTotal();
        }
        $this->montant_total = (string) $total;
        return $this;
    }
}