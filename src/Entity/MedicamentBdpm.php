<?php

namespace App\Entity;

use App\Repository\MedicamentBdpmRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicamentBdpmRepository::class)]
#[ORM\Table(name: 'medicament_bdpm')]
#[ORM\Index(columns: ['denomination'], name: 'idx_denomination')]
#[ORM\Index(columns: ['code_cis'], name: 'idx_code_cis')]
#[ORM\Index(columns: ['code_cip13'], name: 'idx_code_cip13')]
class MedicamentBdpm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code Identifiant de Spécialité (CIS) — identifiant unique du médicament BDPM
     */
    #[ORM\Column(length: 20, unique: true)]
    private string $codeCis;

    /**
     * Dénomination officielle (ex: "DOLIPRANE 1000 mg, comprimé")
     */
    #[ORM\Column(length: 500)]
    private string $denomination;

    /**
     * Forme pharmaceutique (ex: "comprimé", "gélule", "solution buvable")
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formePharmaceutique = null;

    /**
     * Voies d'administration (ex: "orale", "cutanée")
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $voiesAdministration = null;

    /**
     * Statut AMM (ex: "Autorisation active", "Retrait")
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $statutAmm = null;

    /**
     * Titulaire de l'AMM (laboratoire)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titulaire = null;

    /**
     * Code CIP-13 (code barres pharmacie, 13 chiffres)
     */
    #[ORM\Column(length: 13, nullable: true)]
    private ?string $codeCip13 = null;

    /**
     * Libellé de la présentation (ex: "boîte de 8 comprimés")
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $libellePresentation = null;

    /**
     * Prix de remboursement BDPM (en euros, ex: 2.35)
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $prixRemboursement = null;

    /**
     * Taux de remboursement Sécu (ex: "65 %", "100 %")
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tauxRemboursement = null;

    /**
     * Substance(s) active(s) (ex: "PARACETAMOL")
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $substanceActive = null;

    /**
     * Dosage de la substance active (ex: "1 000 mg")
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dosage = null;

    /**
     * Date de dernière mise à jour de cet enregistrement depuis la BDPM
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereMAJ = null;

    // ===================== GETTERS / SETTERS =====================

    public function getId(): ?int { return $this->id; }

    public function getCodeCis(): string { return $this->codeCis; }
    public function setCodeCis(string $codeCis): static { $this->codeCis = $codeCis; return $this; }

    public function getDenomination(): string { return $this->denomination; }
    public function setDenomination(string $denomination): static { $this->denomination = $denomination; return $this; }

    public function getFormePharmaceutique(): ?string { return $this->formePharmaceutique; }
    public function setFormePharmaceutique(?string $formePharmaceutique): static { $this->formePharmaceutique = $formePharmaceutique; return $this; }

    public function getVoiesAdministration(): ?string { return $this->voiesAdministration; }
    public function setVoiesAdministration(?string $voiesAdministration): static { $this->voiesAdministration = $voiesAdministration; return $this; }

    public function getStatutAmm(): ?string { return $this->statutAmm; }
    public function setStatutAmm(?string $statutAmm): static { $this->statutAmm = $statutAmm; return $this; }

    public function getTitulaire(): ?string { return $this->titulaire; }
    public function setTitulaire(?string $titulaire): static { $this->titulaire = $titulaire; return $this; }

    public function getCodeCip13(): ?string { return $this->codeCip13; }
    public function setCodeCip13(?string $codeCip13): static { $this->codeCip13 = $codeCip13; return $this; }

    public function getLibellePresentation(): ?string { return $this->libellePresentation; }
    public function setLibellePresentation(?string $libellePresentation): static { $this->libellePresentation = $libellePresentation; return $this; }

    public function getPrixRemboursement(): ?string { return $this->prixRemboursement; }
    public function setPrixRemboursement(?string $prixRemboursement): static { $this->prixRemboursement = $prixRemboursement; return $this; }

    public function getTauxRemboursement(): ?string { return $this->tauxRemboursement; }
    public function setTauxRemboursement(?string $tauxRemboursement): static { $this->tauxRemboursement = $tauxRemboursement; return $this; }

    public function getSubstanceActive(): ?string { return $this->substanceActive; }
    public function setSubstanceActive(?string $substanceActive): static { $this->substanceActive = $substanceActive; return $this; }

    public function getDosage(): ?string { return $this->dosage; }
    public function setDosage(?string $dosage): static { $this->dosage = $dosage; return $this; }

    public function getDerniereMAJ(): ?\DateTimeImmutable { return $this->derniereMAJ; }
    public function setDerniereMAJ(?\DateTimeImmutable $derniereMAJ): static { $this->derniereMAJ = $derniereMAJ; return $this; }

    /**
     * Calcule le prix de vente suggéré.
     * En France, les médicaments remboursables ont un prix public imposé (Prix BDPM).
     * S'il n'est pas remboursable, le prix est libre et on peut appliquer une marge (ex: +30%).
     */
    public function getPrixVenteSuggere(float $tauxMarge = 0.30): ?string
    {
        if ($this->prixRemboursement === null) {
            return null;
        }
        $prix = (float) $this->prixRemboursement;
        if ($prix <= 0) {
            return null;
        }
        
        // S'il a un taux de remboursement (ex: "65%"), le prix de vente doit être exactement le prix public
        if ($this->tauxRemboursement !== null && trim($this->tauxRemboursement) !== '') {
            return number_format($prix, 2, '.', '');
        }

        // Sinon (non remboursable, prix libre), on applique la marge
        return number_format($prix * (1 + $tauxMarge), 2, '.', '');
    }

    /**
     * Retourne le nom court pour l'affichage (substance + dosage si disponible)
     */
    public function getNomCourt(): string
    {
        // La dénomination BDPM contient déjà la substance et le dosage
        // ex: "DOLIPRANE 1000 mg, comprimé"
        return $this->denomination;
    }

    public function __toString(): string
    {
        return $this->denomination;
    }
}
