<?php

namespace App\Entity;

use App\Repository\SessionCaisseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente le cycle de vie d'une ouverture de tiroir-caisse.
 * Une session commence à l'ouverture (fond de caisse initial) et se termine
 * à la clôture (Ticket Z). Toutes les ventes réalisées pendant la session
 * y sont rattachées.
 */
#[ORM\Entity(repositoryClass: SessionCaisseRepository::class)]
class SessionCaisse
{
    // ── Statuts possibles ────────────────────────────────────────────────────
    public const STATUT_OUVERTE = 'OUVERTE';
    public const STATUT_FERMEE  = 'FERMEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Le comptoir physique sur lequel cette session est ouverte */
    #[ORM\ManyToOne(targetEntity: PosteCaisse::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PosteCaisse $poste = null;

    /** L'employé qui a ouvert la session (fond de caisse initial) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_ouverture = null;

    /** Horodatage d'ouverture de la caisse */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_ouverture = null;

    /** Horodatage de clôture (null si la session est encore ouverte) */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_fermeture = null;

    /**
     * Montant en espèces mis dans le tiroir au début de la session
     * (pour pouvoir rendre la monnaie dès le premier client).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $fond_de_caisse = '0.00';

    /** Statut de la session : OUVERTE ou FERMEE */
    #[ORM\Column(length: 10)]
    private ?string $statut = self::STATUT_OUVERTE;

    /** Toutes les ventes réalisées pendant cette session */
    #[ORM\OneToMany(mappedBy: 'session_caisse', targetEntity: Vente::class)]
    private Collection $ventes;

    /** La clôture associée (présente uniquement quand statut = FERMEE) */
    #[ORM\OneToOne(mappedBy: 'session', targetEntity: ClotureCaisse::class, cascade: ['persist', 'remove'])]
    private ?ClotureCaisse $cloture = null;

    public function __construct()
    {
        $this->ventes = new ArrayCollection();
        $this->date_ouverture = new \DateTime();
    }

    // ── Getters & Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoste(): ?PosteCaisse
    {
        return $this->poste;
    }

    public function setPoste(?PosteCaisse $poste): static
    {
        $this->poste = $poste;
        return $this;
    }

    public function getUserOuverture(): ?User
    {
        return $this->user_ouverture;
    }

    public function setUserOuverture(?User $user_ouverture): static
    {
        $this->user_ouverture = $user_ouverture;
        return $this;
    }

    public function getDateOuverture(): ?\DateTimeInterface
    {
        return $this->date_ouverture;
    }

    public function setDateOuverture(\DateTimeInterface $date_ouverture): static
    {
        $this->date_ouverture = $date_ouverture;
        return $this;
    }

    public function getDateFermeture(): ?\DateTimeInterface
    {
        return $this->date_fermeture;
    }

    public function setDateFermeture(?\DateTimeInterface $date_fermeture): static
    {
        $this->date_fermeture = $date_fermeture;
        return $this;
    }

    public function getFondDeCaisse(): ?string
    {
        return $this->fond_de_caisse;
    }

    public function setFondDeCaisse(string $fond_de_caisse): static
    {
        $this->fond_de_caisse = $fond_de_caisse;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!in_array($statut, [self::STATUT_OUVERTE, self::STATUT_FERMEE])) {
            throw new \InvalidArgumentException(
                sprintf('Statut invalide "%s". Valeurs autorisées : OUVERTE, FERMEE.', $statut)
            );
        }
        $this->statut = $statut;
        return $this;
    }

    public function isOuverte(): bool
    {
        return $this->statut === self::STATUT_OUVERTE;
    }

    /** @return Collection<int, Vente> */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $vente): static
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes->add($vente);
            $vente->setSessionCaisse($this);
        }
        return $this;
    }

    public function removeVente(Vente $vente): static
    {
        if ($this->ventes->removeElement($vente)) {
            if ($vente->getSessionCaisse() === $this) {
                $vente->setSessionCaisse(null);
            }
        }
        return $this;
    }

    public function getCloture(): ?ClotureCaisse
    {
        return $this->cloture;
    }

    public function setCloture(?ClotureCaisse $cloture): static
    {
        // Côté inverse de la relation OneToOne
        if ($cloture === null && $this->cloture !== null) {
            $this->cloture->setSession(null);
        }
        if ($cloture !== null && $cloture->getSession() !== $this) {
            $cloture->setSession($this);
        }
        $this->cloture = $cloture;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            'Session #%d — %s (%s)',
            $this->id,
            $this->poste?->getNomPoste() ?? '?',
            $this->date_ouverture?->format('d/m/Y H:i') ?? '?'
        );
    }
}
