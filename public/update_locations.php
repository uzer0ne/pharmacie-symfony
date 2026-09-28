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

$produits = $entityManager->getRepository(Produit::class)->findAll();

$zones = ['RX', 'OTC', 'PARA', 'FRIDGE', 'SAFE', 'MAT', 'STOCK'];
$colonnes = ['01', '02', '03', '04', '05', '06', '07', '08'];
$niveaux = ['A', 'B', 'C', 'D', 'E', 'F'];

$count = 0;
foreach ($produits as $produit) {
    // Determine a logical zone based on active substance or name if possible, else random
    // But for simplicity, we can just pick a random standard zone.
    $zone = $zones[array_rand($zones)];
    // But wait, most medicines should be RX or OTC.
    if (rand(1, 100) <= 60) {
        $zone = 'RX'; // 60% of drugs are prescription
    } elseif (rand(1, 100) <= 80) {
        $zone = 'OTC'; // Over the counter
    }
    
    $produit->setEmpZone($zone);
    $produit->setEmpColonne($colonnes[array_rand($colonnes)]);
    $produit->setEmpNiveau($niveaux[array_rand($niveaux)]);
    $produit->setEmpPosition((string) rand(1, 10));
    
    $count++;
}

$entityManager->flush();
echo "Mis à jour les emplacements de $count produits.\n";
