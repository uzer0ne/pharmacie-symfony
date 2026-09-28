<?php

use App\Kernel;
use App\Entity\Produit;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

/** @var Produit[] $produits */
$produits = $entityManager->getRepository(Produit::class)->findBy([], ['nom_produit' => 'ASC']);

$rxProducts = [];
$otcProducts = [];

// Keywords for smart categorization
$fridgeKeywords = ['VACCIN', 'INSULIN', 'TRESIBA', 'LANTUS', 'TOUJEO', 'NOVORAPID', 'HUMALOG', 'OZEMPIC', 'TRULICITY'];
$safeKeywords = ['MORPHINE', 'OXYCODONE', 'FENTANYL', 'METHADONE', 'RITALINE', 'SKENAN', 'OXYCONTIN', 'SOPROVAL', 'CONCERTA'];
$paraKeywords = ['CREME', 'SHAMPOOING', 'GEL', 'BAUME', 'LAIT', 'LOTION', 'SAVON', 'DENTIFRICE', 'BROSSE', 'EAU THERMALE', 'SERUM', 'HYDRATANT'];
$matKeywords = ['PANSEMENT', 'SERINGUE', 'AIGUILLE', 'COMPRESSE', 'BANDE', 'THERMOMETRE', 'TENSIOMETRE', 'ATTELLE', 'CANNE', 'LUNETTES'];
$otcKeywords = ['DOLIPRANE', 'DAFALGAN', 'EFFERALGAN', 'SPASFON', 'SMECTA', 'NUROFEN', 'STREPSILS', 'HUMEX', 'ACTIFED', 'GAVISCON', 'MAALOX', 'IMODIUM'];

$count = 0;

// 1. First Pass: Special Zones
foreach ($produits as $p) {
    $name = strtoupper($p->getNomProduit());
    $isSpecial = false;

    // Is it SAFE (Stupéfiants) ?
    foreach ($safeKeywords as $kw) {
        if (str_contains($name, $kw)) {
            $p->setEmpZone('SAFE');
            $p->setEmpColonne('01');
            $p->setEmpNiveau('A');
            $p->setEmpPosition((string) rand(1, 12));
            $isSpecial = true;
            break;
        }
    }
    if ($isSpecial) continue;

    // Is it FRIDGE (Thermosensibles) ?
    foreach ($fridgeKeywords as $kw) {
        if (str_contains($name, $kw)) {
            $p->setEmpZone('FRIDGE');
            $p->setEmpColonne('01');
            $p->setEmpNiveau(chr(rand(65, 68))); // A to D
            $p->setEmpPosition((string) rand(1, 10));
            $isSpecial = true;
            break;
        }
    }
    if ($isSpecial) continue;

    // Is it MAT (Matériel) ?
    foreach ($matKeywords as $kw) {
        if (str_contains($name, $kw)) {
            $p->setEmpZone('MAT');
            $p->setEmpColonne('0'.rand(1, 3));
            $p->setEmpNiveau(chr(rand(65, 70)));
            $p->setEmpPosition((string) rand(1, 5));
            $isSpecial = true;
            break;
        }
    }
    if ($isSpecial) continue;

    // Is it PARA (Parapharmacie) ?
    foreach ($paraKeywords as $kw) {
        if (str_contains($name, $kw)) {
            $p->setEmpZone('PARA');
            $p->setEmpColonne('0'.rand(1, 5));
            $p->setEmpNiveau(chr(rand(65, 70)));
            $p->setEmpPosition((string) rand(1, 10));
            $isSpecial = true;
            break;
        }
    }
    if ($isSpecial) continue;

    // Is it OTC (Libre service comptoir) ?
    $isOtc = false;
    foreach ($otcKeywords as $kw) {
        if (str_contains($name, $kw)) {
            $isOtc = true;
            break;
        }
    }

    if ($isOtc) {
        $otcProducts[] = $p;
    } else {
        $rxProducts[] = $p;
    }
}

// 2. Second Pass: Distribute RX Homogeneously (Alphabetical distribution over 8 bays)
// We have 8 bays (01 to 08), 6 levels (A to F), 12 positions
$totalRx = count($rxProducts);
$bays = ['01', '02', '03', '04', '05', '06', '07', '08'];
$levels = ['A', 'B', 'C', 'D', 'E', 'F'];

if ($totalRx > 0) {
    $productsPerBay = ceil($totalRx / count($bays));
    
    foreach ($rxProducts as $index => $p) {
        $bayIndex = (int) floor($index / $productsPerBay);
        if ($bayIndex >= count($bays)) $bayIndex = count($bays) - 1;
        
        $positionInBay = $index % $productsPerBay;
        $productsPerLevel = ceil($productsPerBay / count($levels));
        
        $levelIndex = (int) floor($positionInBay / $productsPerLevel);
        if ($levelIndex >= count($levels)) $levelIndex = count($levels) - 1;
        
        $positionInLevel = ($positionInBay % $productsPerLevel) + 1; // 1-based index
        
        $p->setEmpZone('RX');
        $p->setEmpColonne($bays[$bayIndex]);
        $p->setEmpNiveau($levels[$levelIndex]);
        $p->setEmpPosition(str_pad((string)$positionInLevel, 2, '0', STR_PAD_LEFT));
    }
}

// 3. Optional: Same logic for OTC if we have a lot, or just random
$totalOtc = count($otcProducts);
if ($totalOtc > 0) {
    $otcBays = ['01', '02']; // 2 bays for OTC
    $productsPerBayOtc = ceil($totalOtc / count($otcBays));
    foreach ($otcProducts as $index => $p) {
        $bayIndex = (int) floor($index / $productsPerBayOtc);
        if ($bayIndex >= count($otcBays)) $bayIndex = count($otcBays) - 1;
        
        $positionInBay = $index % $productsPerBayOtc;
        $productsPerLevel = ceil($productsPerBayOtc / count($levels));
        
        $levelIndex = (int) floor($positionInBay / $productsPerLevel);
        if ($levelIndex >= count($levels)) $levelIndex = count($levels) - 1;
        
        $positionInLevel = ($positionInBay % $productsPerLevel) + 1;
        
        $p->setEmpZone('OTC');
        $p->setEmpColonne($otcBays[$bayIndex]);
        $p->setEmpNiveau($levels[$levelIndex]);
        $p->setEmpPosition(str_pad((string)$positionInLevel, 2, '0', STR_PAD_LEFT));
    }
}

$entityManager->flush();

echo "Algorithme de tri spatial terminé !\n";
echo "Spéciaux (Frigo, Coffre, Para, Mat) assignés.\n";
echo "RX (Tiroirs): $totalRx produits répartis uniformément (A-Z) sur 8 Baies.\n";
echo "OTC (Comptoir): $totalOtc produits répartis uniformément.\n";
