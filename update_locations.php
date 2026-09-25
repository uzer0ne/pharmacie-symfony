<?php
require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use App\Entity\Produit;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$produitRepo = $em->getRepository(Produit::class);

$produits = $produitRepo->findAll();

$updated = 0;

foreach ($produits as $produit) {
    $nom = strtolower($produit->getNomProduit() ?? '');
    $famille = strtolower($produit->getFamille() ?? '');
    $desc = strtolower($produit->getDescription() ?? '');
    $searchString = $nom . ' ' . $famille . ' ' . $desc;

    // Rules
    if (str_contains($searchString, 'vaccin') || str_contains($searchString, 'insulin') || str_contains($searchString, 'froid')) {
        $produit->setEmpZone('FRIDGE');
        $produit->setEmpColonne('01');
        $produit->setEmpNiveau('A');
        $produit->setEmpPosition('01');
        $produit->setFamille('Vaccins / Chaîne du froid');
    } elseif (str_contains($searchString, 'morphine') || str_contains($searchString, 'fentanyl') || str_contains($searchString, 'oxycodone') || str_contains($searchString, 'stup')) {
        $produit->setEmpZone('SAFE');
        $produit->setEmpColonne('01');
        $produit->setEmpNiveau('A');
        $produit->setEmpPosition('01');
        $produit->setFamille('Stupéfiants');
    } elseif (str_contains($searchString, 'crème') || str_contains($searchString, 'shampooing') || str_contains($searchString, 'beauté') || str_contains($searchString, 'para')) {
        $produit->setEmpZone('PARA');
        $produit->setEmpColonne('01');
        $produit->setEmpNiveau('A');
        $produit->setEmpPosition('01');
        $produit->setFamille('Parapharmacie');
    } elseif (str_contains($searchString, 'cardio') || str_contains($searchString, 'tens') || str_contains($searchString, 'diab') || str_contains($searchString, 'metformin') || str_contains($searchString, 'bisoprolol')) {
        $produit->setEmpZone('RX');
        $produit->setFamille('Cardiologie / Diabète');
        // Distribute in columns 05-08
        $cols = ['05', '06', '07', '08'];
        $produit->setEmpColonne($cols[array_rand($cols)]);
        $nivs = ['A', 'B', 'C', 'D', 'E', 'F'];
        $produit->setEmpNiveau($nivs[array_rand($nivs)]);
        $produit->setEmpPosition(str_pad(rand(1, 10), 2, '0', STR_PAD_LEFT));
    } else {
        // Default: Aigues (Antibiotiques, Antalgiques, etc)
        $produit->setEmpZone('RX');
        if (empty($produit->getFamille())) {
            if (str_contains($searchString, 'amoxicilline') || str_contains($searchString, 'antibio')) {
                $produit->setFamille('Antibiotiques');
            } elseif (str_contains($searchString, 'paracetamol') || str_contains($searchString, 'ibuprof') || str_contains($searchString, 'doliprane') || str_contains($searchString, 'analg')) {
                $produit->setFamille('Analgésiques');
            } else {
                $produit->setFamille('Médecine Générale');
            }
        }
        
        // Distribute in columns 01-04
        $cols = ['01', '02', '03', '04'];
        
        // Let's try to be somewhat alphabetical by name
        $firstChar = substr($nom, 0, 1);
        if ($firstChar >= 'a' && $firstChar <= 'f') $col = '01';
        elseif ($firstChar >= 'g' && $firstChar <= 'l') $col = '02';
        elseif ($firstChar >= 'm' && $firstChar <= 's') $col = '03';
        else $col = '04';

        $produit->setEmpColonne($col);
        $nivs = ['A', 'B', 'C', 'D', 'E', 'F'];
        $produit->setEmpNiveau($nivs[array_rand($nivs)]);
        $produit->setEmpPosition(str_pad(rand(1, 10), 2, '0', STR_PAD_LEFT));
    }

    $em->persist($produit);
    $updated++;
}

$em->flush();

echo "Mise a jour reussie de $updated produits avec des emplacements logiques.\n";
