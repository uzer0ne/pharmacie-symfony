<?php

use App\Kernel;
use App\Entity\Produit;
use App\Entity\MedicamentBdpm;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// 1. Vider les tables liées (ventes, ordonnances) pour éviter les erreurs de clés étrangères
$connection = $em->getConnection();
$connection->executeStatement('SET FOREIGN_KEY_CHECKS=0'); // Pour MySQL, ou ignorer si SQLite
try {
    $connection->executeStatement('DELETE FROM ligne_vente');
    $connection->executeStatement('DELETE FROM vente');
    $connection->executeStatement('DELETE FROM ligne_ordonnance');
    $connection->executeStatement('DELETE FROM ordonnance');
    $connection->executeStatement('DELETE FROM produit');
} catch (\Exception $e) {
    // SQLite syntax
    $connection->executeStatement('PRAGMA foreign_keys = OFF');
    $connection->executeStatement('DELETE FROM ligne_vente');
    $connection->executeStatement('DELETE FROM vente');
    $connection->executeStatement('DELETE FROM ligne_ordonnance');
    $connection->executeStatement('DELETE FROM ordonnance');
    $connection->executeStatement('DELETE FROM produit');
    $connection->executeStatement('PRAGMA foreign_keys = ON');
}

echo "Tables nettoyées.\n";

// 2. Récupérer 50 médicaments au hasard (ou les 50 premiers "connus")
$bdpmRepo = $em->getRepository(MedicamentBdpm::class);
// On filtre sur ceux qui ont un prix pour avoir des données réalistes
$query = $bdpmRepo->createQueryBuilder('m')
    ->where('m.prixRemboursement IS NOT NULL')
    ->andWhere('m.statutAmm = :statut')
    ->setParameter('statut', 'Autorisation active')
    ->setMaxResults(50)
    ->getQuery();

$medicaments = $query->getResult();

$count = 0;
foreach ($medicaments as $med) {
    $produit = new Produit();
    $produit->setNomProduit($med->getDenomination());
    
    // Code CIP
    if ($med->getCodeCip13()) {
        $produit->setCodeCip($med->getCodeCip13());
    }

    // Famille (on utilise la substance active)
    if ($med->getSubstanceActive()) {
        $produit->setFamille($med->getSubstanceActive());
    }

    // Dosage
    $produit->setDosageProduit($med->getDosage() ?? 'N/A');

    // Prix
    $prixBDPM = (float) $med->getPrixRemboursement();
    // On utilise la nouvelle logique (Prix réglementé si remboursable, +30% sinon)
    $prixVenteSuggere = $med->getPrixVenteSuggere(0.30);
    $produit->setPrixProduit($prixVenteSuggere ?: number_format($prixBDPM, 2, '.', ''));
    $produit->setPrixAchat(number_format($prixBDPM, 2, '.', '')); // On simule le prix d'achat = prix sécu

    // Dates
    $produit->setDateFabrication(new \DateTime('-' . rand(1, 12) . ' months'));
    $produit->setDateExpiration(new \DateTime('+' . rand(12, 36) . ' months'));

    // Stocks simulés
    $produit->setStockActuel(rand(15, 100));
    $produit->setStockMinimum(5);
    $produit->setStockAlerte(10);
    $produit->setActif(true);

    // Description
    $desc = "Substance: " . ($med->getSubstanceActive() ?? 'N/A') . "\n";
    $desc .= "Forme: " . ($med->getFormePharmaceutique() ?? 'N/A') . "\n";
    $desc .= "Titulaire: " . ($med->getTitulaire() ?? 'N/A');
    $produit->setDescription($desc);

    $em->persist($produit);
    $count++;
}

$em->flush();
echo "Génération terminée : $count produits créés depuis la BDPM.\n";
