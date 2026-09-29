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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montant_secu = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montant_mutuelle = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $reste_a_payer = '0.00';

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

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details_honoraires = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'EN_ATTENTE';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montant_encaisse = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monnaie_rendue = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $vendeur = null;

    /**
     * Session de caisse durant laquelle cette vente a été enregistrée.
     * Nullable pour rétrocompatibilité avec les anciennes ventes sans session.
     */
    #[ORM\ManyToOne(targetEntity: SessionCaisse::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?SessionCaisse $session_caisse = null;

    /**
     * Paiements associés à ce ticket (CB, Espèces, Chèque, Mutuelle...).
     * Remplace le champ monolithique montant_encaisse pour les nouvelles ventes.
     */
    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: PaiementVente::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $paiements;

    public function __construct()
    {
        $this->ligneVentes = new ArrayCollection();
        $this->paiements   = new ArrayCollection();
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

    public function getMontantSecu(): ?string
    {
        return $this->montant_secu;
    }

    public function setMontantSecu(?string $montant_secu): static
    {
        $this->montant_secu = $montant_secu;
        return $this;
    }

    public function getMontantMutuelle(): ?string
    {
        return $this->montant_mutuelle;
    }

    public function setMontantMutuelle(?string $montant_mutuelle): static
    {
        $this->montant_mutuelle = $montant_mutuelle;
        return $this;
    }

    public function getResteAPayer(): ?string
    {
        return $this->reste_a_payer;
    }

    public function setResteAPayer(?string $reste_a_payer): static
    {
        $this->reste_a_payer = $reste_a_payer;
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
        
        // Ajouter les honoraires au total si présents
        if (is_array($this->details_honoraires)) {
            foreach ($this->details_honoraires as $h) {
                $total += (float) $h['montant'];
            }
        }
        
        $this->montant_total = (string) $total;
        return $this;
    }

    public function getDetailsHonoraires(): ?array
    {
        return $this->details_honoraires;
    }

    public function setDetailsHonoraires(?array $details_honoraires): static
    {
        $this->details_honoraires = $details_honoraires;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMontantEncaisse(): ?string
    {
        return $this->montant_encaisse;
    }

    public function setMontantEncaisse(?string $montant_encaisse): static
    {
        $this->montant_encaisse = $montant_encaisse;
        return $this;
    }

    public function getMonnaieRendue(): ?string
    {
        return $this->monnaie_rendue;
    }

    public function setMonnaieRendue(?string $monnaie_rendue): static
    {
        $this->monnaie_rendue = $monnaie_rendue;
        return $this;
    }

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }

    public function setVendeur(?User $vendeur): static
    {
        $this->vendeur = $vendeur;
        return $this;
    }

    // ── SessionCaisse ────────────────────────────────────────────────────────

    public function getSessionCaisse(): ?SessionCaisse
    {
        return $this->session_caisse;
    }

    public function setSessionCaisse(?SessionCaisse $session_caisse): static
    {
        $this->session_caisse = $session_caisse;
        return $this;
    }

    // ── PaiementVente ────────────────────────────────────────────────────────

    /** @return Collection<int, PaiementVente> */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(PaiementVente $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setVente($this);
        }
        return $this;
    }

    public function removePaiement(PaiementVente $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getVente() === $this) {
                $paiement->setVente(null);
            }
        }
        return $this;
    }

    /**
     * Calcule le total réellement encaissé via les PaiementVente.
     * Utile pour vérifier que le ticket est soldé.
     */
    public function getTotalPaiements(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $paiement) {
            $total += (float) $paiement->getMontant();
        }
        return $total;
    }
}