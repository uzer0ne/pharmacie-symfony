<?php

namespace App\Entity;

use App\Repository\PosteCaisseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente un comptoir physique de la pharmacie (ex: "Caisse 1 - Accueil").
 * Un poste peut avoir plusieurs sessions dans le temps, mais une seule OUVERTE à la fois.
 */
#[ORM\Entity(repositoryClass: PosteCaisseRepository::class)]
class PosteCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom lisible du poste, affiché à l'employé (ex: "Caisse 1 - Accueil") */
    #[ORM\Column(length: 100)]
    private ?string $nom_poste = null;

    /** Si false, le poste est désactivé et ne peut pas être ouvert */
    #[ORM\Column]
    private bool $actif = true;

    /** Toutes les sessions qui ont eu lieu sur ce poste (historique complet) */
    #[ORM\OneToMany(mappedBy: 'poste', targetEntity: SessionCaisse::class, cascade: ['persist'])]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPoste(): ?string
    {
        return $this->nom_poste;
    }

    public function setNomPoste(string $nom_poste): static
    {
        $this->nom_poste = $nom_poste;
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

    /** @return Collection<int, SessionCaisse> */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(SessionCaisse $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setPoste($this);
        }
        return $this;
    }

    public function removeSession(SessionCaisse $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getPoste() === $this) {
                $session->setPoste(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom_poste ?? 'Poste #' . $this->id;
    }
}
