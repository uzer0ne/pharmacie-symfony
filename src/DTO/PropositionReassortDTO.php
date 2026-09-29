<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Produit;

/**
 * DTO représentant une proposition de réassort pour un produit.
 */
final class PropositionReassortDTO
{
    public function __construct(
        public readonly Produit $produit,
        public readonly int     $stockActuel,
        public readonly int     $stockMinimum,
        public readonly int     $stockAlerte,
        public readonly int     $quantiteACommander,
        public readonly float   $prixAchatUnitaire,
    ) {}

    /** Sous-total estimé pour cette ligne */
    public function getMontantEstime(): float
    {
        return $this->prixAchatUnitaire * $this->quantiteACommander;
    }
}
