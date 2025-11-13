<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Produit', type: 'integer')]
    private ?int $idProduit = null;

    #[ORM\Column(length: 255)]
    private ?string $code_produit = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_fabrication = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_expiration = null;

    #[ORM\Column(length: 255)]
    private ?string $dosage_produit = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_produit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix_produit = null;

       // ⭐ NOUVELLES PROPRIÉTÉS STOCKS
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $stock_actuel = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $stock_minimum = 5;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $stock_alerte = 10;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prix_achat = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $code_cip = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    // ⭐ CORRECTION : Collection au lieu de ?Ordonnance
    #[ORM\ManyToMany(targetEntity: Ordonnance::class, mappedBy: 'produits')]
    private Collection $ordonnances;
       // ▼▼▼ AJOUTEZ CETTE PROPRIÉTÉ ▼▼▼
    #[ORM\OneToMany(mappedBy: "produit", targetEntity: LigneVente::class)]
    private Collection $ligneVentes;

    public function __construct()
    {
        $this->ordonnances = new ArrayCollection();
        $this->stock_actuel = 0;
        $this->stock_minimum = 5;
        $this->stock_alerte = 10;
        $this->actif = true;
        $this->ligneVentes = new ArrayCollection();

    }

    // ⭐ CORRECTION : Retourne Collection, pas ?Ordonnance
    /**
     * return Collection<int, Ordonnance>
     */
    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }

    // ⭐ SUPPRIMER setOrdonnance() car ManyToMany n'a pas de setter

    public function addOrdonnance(Ordonnance $ordonnance): static
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->addProduit($this);
        }

        return $this;
    }

    public function removeOrdonnance(Ordonnance $ordonnance): static
    {
        if ($this->ordonnances->removeElement($ordonnance)) {
            $ordonnance->removeProduit($this);
        }

        return $this;
    }

    // ... autres getters/setters existants ...
    public function getId(): ?int
    {
        return $this->idProduit;
    }

    public function getCodeProduit(): ?string
    {
        return $this->code_produit;
    }

    public function setCodeProduit(string $code_produit): static
    {
        $this->code_produit = $code_produit;

        return $this;
    }

    public function getDateFabrication(): ?\DateTime
    {
        return $this->date_fabrication;
    }

    public function setDateFabrication(\DateTime $date_fabrication): static
    {
        $this->date_fabrication = $date_fabrication;

        return $this;
    }

    public function getDateExpiration(): ?\DateTime
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(\DateTime $date_expiration): static
    {
        $this->date_expiration = $date_expiration;

        return $this;
    }

    public function getDosageProduit(): ?string
    {
        return $this->dosage_produit;
    }

    public function setDosageProduit(string $dosage_produit): static
    {
        $this->dosage_produit = $dosage_produit;

        return $this;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(string $nom_produit): static
    {
        $this->nom_produit = $nom_produit;

        return $this;
    }

    public function getPrixProduit(): ?string
    {
        return $this->prix_produit;
    }

    public function setPrixProduit(string $prix_produit): static
    {
        $this->prix_produit = $prix_produit;

        return $this;
    }
    // ⭐ NOUVEAUX GETTERS/SETTERS STOCKS

    public function getStockActuel(): ?int
    {
        return $this->stock_actuel;
    }

    public function setStockActuel(int $stock_actuel): static
    {
        $this->stock_actuel = $stock_actuel;

        return $this;
    }

    public function getStockMinimum(): ?int
    {
        return $this->stock_minimum;
    }

    public function setStockMinimum(int $stock_minimum): static
    {
        $this->stock_minimum = $stock_minimum;

        return $this;
    }
    public function getStockAlerte(): ?int
    {
        return $this->stock_alerte;
    }

    public function setStockAlerte(int $stock_alerte): static
    {
        $this->stock_alerte = $stock_alerte;

        return $this;
    }

    public function getPrixAchat(): ?string
    {
        return $this->prix_achat;
    }

    public function setPrixAchat(?string $prix_achat): static
    {
        $this->prix_achat = $prix_achat;

        return $this;
    }
     public function getCodeCip(): ?string
    {
        return $this->code_cip;
    }

    public function setCodeCip(?string $code_cip): static
    {
        $this->code_cip = $code_cip;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    
    // ⭐ MÉTHODES MÉTIER POUR LA GESTION DES STOCKS

    public function getStatutStock(): string
    {
        if ($this->stock_actuel <= 0) {
            return 'rupture';
        } elseif ($this->stock_actuel <= $this->stock_minimum) {
            return 'critique';
        } elseif ($this->stock_actuel <= $this->stock_alerte) {
            return 'alerte';
        } else {
            return 'normal';
        }
    }
    public function getCouleurStatutStock(): string
    {
        return match($this->getStatutStock()) {
            'rupture' => 'danger',
            'critique' => 'danger',
            'alerte' => 'warning',
            default => 'success'
        };
    }
    public function getIconeStatutStock(): string
    {
        return match($this->getStatutStock()) {
            'rupture' => 'bi-exclamation-triangle',
            'critique' => 'bi-exclamation-triangle',
            'alerte' => 'bi-exclamation-circle',
            default => 'bi-check-circle'
        };
    }
    public function getMessageStatutStock(): string
    {
        return match($this->getStatutStock()) {
            'rupture' => 'Rupture de stock',
            'critique' => 'Stock critique',
            'alerte' => 'Stock faible',
            default => 'Stock normal'
        };
    }
        public function estEnRupture(): bool
    {
        return $this->stock_actuel <= 0;
    }

    public function estStockCritique(): bool
    {
        return $this->stock_actuel > 0 && $this->stock_actuel <= $this->stock_minimum;
    }

    public function estStockAlerte(): bool
    {
        return $this->stock_actuel > $this->stock_minimum && $this->stock_actuel <= $this->stock_alerte;
    }
     public function getMarge(): ?string
    {
        if ($this->prix_achat && $this->prix_produit) {
            $marge = floatval($this->prix_produit) - floatval($this->prix_achat);
            return number_format($marge, 2);
        }
        return null;
    }

    public function getTauxMarge(): ?string
    {
        if ($this->prix_achat && $this->prix_produit && floatval($this->prix_achat) > 0) {
            $marge = floatval($this->prix_produit) - floatval($this->prix_achat);
            $taux = ($marge / floatval($this->prix_achat)) * 100;
            return number_format($taux, 1);
        }
        return null;
    }

    // Vérifie si le produit expire dans moins de X jours
    public function expireBientot(int $jours = 30): bool
    {
        $aujourdhui = new \DateTime();
        $difference = $this->date_expiration->diff($aujourdhui);
        return $difference->days <= $jours && $difference->invert == 1;
    }

    public function getJoursAvantExpiration(): int
    {
        $aujourdhui = new \DateTime();
        $difference = $this->date_expiration->diff($aujourdhui);
        return $difference->invert ? $difference->days : -$difference->days;
    }
    
    // ▼▼▼ AJOUTEZ CES MÉTHODES ▼▼▼

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
            $ligneVente->setProduit($this);
        }

        return $this;
    }

    public function removeLigneVente(LigneVente $ligneVente): static
    {
        if ($this->ligneVentes->removeElement($ligneVente)) {
            // set the owning side to null (unless already changed)
            if ($ligneVente->getProduit() === $this) {
                $ligneVente->setProduit(null);
            }
        }

        return $this;
    }


}