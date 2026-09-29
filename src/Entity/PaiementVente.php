<?php

namespace App\Entity;

use App\Repository\PaiementVenteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente un paiement partiel ou total sur une vente.
 * Une vente peut avoir plusieurs PaiementVente (ex: 30€ CB + 5€ ESPECES).
 * Cette entité remplace le champ monolithique `montant_encaisse` de Vente
 * pour gérer les encaissements multi-modes.
 */
#[ORM\Entity(repositoryClass: PaiementVenteRepository::class)]
class PaiementVente
{
    // ── Modes de paiement acceptés ───────────────────────────────────────────
    public const MODE_CB       = 'CB';
    public const MODE_ESPECES  = 'ESPECES';
    public const MODE_CHEQUE   = 'CHEQUE';
    public const MODE_MUTUELLE = 'MUTUELLE';
    public const MODE_VIREMENT = 'VIREMENT';

    public const MODES_DISPONIBLES = [
        'Carte Bancaire'     => self::MODE_CB,
        'Espèces'            => self::MODE_ESPECES,
        'Chèque'             => self::MODE_CHEQUE,
        'Tiers Payant Mutuelle' => self::MODE_MUTUELLE,
        'Virement'           => self::MODE_VIREMENT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** La vente à laquelle ce paiement est rattaché */
    #[ORM\ManyToOne(targetEntity: Vente::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: 'vente_id', referencedColumnName: 'Id_Vente', nullable: false)]
    private ?Vente $vente = null;

    /**
     * Mode de paiement : CB, ESPECES, CHEQUE, MUTUELLE, VIREMENT.
     * Seuls les paiements ESPECES entrent dans le calcul du Ticket Z.
     */
    #[ORM\Column(length: 20)]
    private ?string $mode_paiement = null;

    /** Montant encaissé via ce mode (en euros) */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = '0.00';

    /** Horodatage du paiement (peut différer de la date de vente en cas de correction) */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_paiement = null;

    public function __construct()
    {
        $this->date_paiement = new \DateTime();
    }

    // ── Getters & Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(string $mode_paiement): static
    {
        if (!in_array($mode_paiement, array_values(self::MODES_DISPONIBLES))) {
            throw new \InvalidArgumentException(
                sprintf('Mode de paiement invalide "%s".', $mode_paiement)
            );
        }
        $this->mode_paiement = $mode_paiement;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(\DateTimeInterface $date_paiement): static
    {
        $this->date_paiement = $date_paiement;
        return $this;
    }

    /**
     * Retourne le libellé lisible du mode de paiement.
     */
    public function getLibelleMode(): string
    {
        return array_search($this->mode_paiement, self::MODES_DISPONIBLES) ?: $this->mode_paiement;
    }
}
