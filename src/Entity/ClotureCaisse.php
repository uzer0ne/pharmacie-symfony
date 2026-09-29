<?php

namespace App\Entity;

use App\Repository\ClotureCaisseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente la clôture de caisse (Ticket Z).
 * Générée par CaisseService::cloturerSession(), elle documente le résultat
 * du comptage physique des espèces et calcule l'écart (boni/mali).
 */
#[ORM\Entity(repositoryClass: ClotureCaisseRepository::class)]
class ClotureCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * La session fermée par cette clôture.
     * Relation OneToOne : une clôture = une session, une session = une clôture.
     */
    #[ORM\OneToOne(inversedBy: 'cloture', targetEntity: SessionCaisse::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionCaisse $session = null;

    /**
     * Montant théorique en espèces calculé par le système :
     * fond_de_caisse + somme de tous les PaiementVente en ESPECES de la session.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant_theorique_especes = '0.00';

    /**
     * Montant réellement compté dans le tiroir par l'employé.
     * C'est la valeur saisie manuellement lors de la clôture.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant_saisi_especes = '0.00';

    /**
     * Écart = montant_saisi - montant_theorique.
     * Positif → boni (il y a plus d'argent que prévu).
     * Négatif → mali (il manque de l'argent).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $ecart_caisse = '0.00';

    /** L'employé qui a réalisé la clôture physique */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_cloture = null;

    /** Horodatage exact de la clôture (pour l'audit) */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_cloture = null;

    public function __construct()
    {
        $this->date_cloture = new \DateTime();
    }

    // ── Getters & Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?SessionCaisse
    {
        return $this->session;
    }

    public function setSession(?SessionCaisse $session): static
    {
        $this->session = $session;
        return $this;
    }

    public function getMontantTheoriqueEspeces(): ?string
    {
        return $this->montant_theorique_especes;
    }

    public function setMontantTheoriqueEspeces(string $montant_theorique_especes): static
    {
        $this->montant_theorique_especes = $montant_theorique_especes;
        return $this;
    }

    public function getMontantSaisiEspeces(): ?string
    {
        return $this->montant_saisi_especes;
    }

    public function setMontantSaisiEspeces(string $montant_saisi_especes): static
    {
        $this->montant_saisi_especes = $montant_saisi_especes;
        return $this;
    }

    public function getEcartCaisse(): ?string
    {
        return $this->ecart_caisse;
    }

    public function setEcartCaisse(string $ecart_caisse): static
    {
        $this->ecart_caisse = $ecart_caisse;
        return $this;
    }

    public function getUserCloture(): ?User
    {
        return $this->user_cloture;
    }

    public function setUserCloture(?User $user_cloture): static
    {
        $this->user_cloture = $user_cloture;
        return $this;
    }

    public function getDateCloture(): ?\DateTimeInterface
    {
        return $this->date_cloture;
    }

    public function setDateCloture(\DateTimeInterface $date_cloture): static
    {
        $this->date_cloture = $date_cloture;
        return $this;
    }

    /**
     * Retourne true si l'écart est un boni (surplus), false si mali (manque).
     */
    public function isBoni(): bool
    {
        return (float) $this->ecart_caisse >= 0;
    }
}
