<?php

use App\Kernel;
use App\Entity\Produit;
use App\Entity\MedicamentBdpm;

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Find 1000 random medicaments that have a price and a CIP code
$query = $entityManager->createQuery(
    'SELECT m FROM App\Entity\MedicamentBdpm m 
     WHERE m.codeCip13 IS NOT NULL 
     AND m.prixRemboursement IS NOT NULL'
)->setMaxResults(1000);

$medicaments = $query->getResult();
echo "Trouvé " . count($medicaments) . " médicaments à importer.\n";

$zones = ['A', 'B', 'C', 'D'];
$colonnes = ['1', '2', '3', '4'];
$niveaux = ['A', 'B', 'C', 'D', 'E', 'F'];

$count = 0;
foreach ($medicaments as $med) {
    $produit = new Produit();
    
    // Some logic to extract a nice name without all the "comprimé" junk for the display
    $parts = explode(',', $med->getDenomination());
    $nomBase = trim($parts[0]);
    if (strlen($nomBase) > 255) {
        $nomBase = substr($nomBase, 0, 250);
    }
    
    $produit->setNomProduit($nomBase);
    $produit->setCodeCip($med->getCodeCip13());
    $produit->setCodeProduit($med->getCodeCis()); // Custom internal code, let's use CIS
    $produit->setDosageProduit($med->getDosage() ?: 'Standard');
    $produit->setFamille($med->getTitulaire() ?: 'Laboratoire inconnu');
    $produit->setDescription($med->getFormePharmaceutique() . ' - ' . $med->getLibellePresentation());
    
    // Dates
    $dateFab = new \DateTime('-' . rand(1, 24) . ' months');
    $produit->setDateFabrication($dateFab);
    
    $dateExp = clone $dateFab;
    $dateExp->modify('+' . rand(24, 60) . ' months');
    $produit->setDateExpiration($dateExp);
    
    // Pricing
    $prix = (float) $med->getPrixRemboursement();
    if ($prix <= 0) $prix = rand(2, 50) + (rand(0, 99) / 100); // Fallback
    
    $produit->setPrixProduit((string) $prix);
    $produit->setPrixAchat((string) round($prix * 0.7, 2)); // 30% margin
    
    // Stock
    $produit->setStockActuel(rand(20, 150));
    $produit->setStockMinimum(10);
    $produit->setStockAlerte(20);
    $produit->setActif(true);
    
    // Location
    $produit->setEmpZone($zones[array_rand($zones)]);
    $produit->setEmpColonne($colonnes[array_rand($colonnes)]);
    $produit->setEmpNiveau($niveaux[array_rand($niveaux)]);
    $produit->setEmpPosition((string) rand(1, 10));
    
    $entityManager->persist($produit);
    
    $count++;
    if ($count % 100 === 0) {
        $entityManager->flush();
        $entityManager->clear(Produit::class); // Clear to free memory
        echo "Importé $count produits...\n";
    }
}

$entityManager->flush();
echo "Importation terminée avec succès ! $count produits ajoutés.\n";
