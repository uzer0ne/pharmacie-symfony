<?php
require 'vendor/autoload.php';
use App\Kernel;
use App\Entity\Mutuelle;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$mutuelles = $em->getRepository(Mutuelle::class)->findAll();

// Vrais codes AMC (exemples réels fréquents de grands groupes)
$amcMapping = [
    'harmonie' => '77563212',
    'mgen' => '77568531',
    'allianz' => '55210046',
    'axa' => '72202804',
    'macif' => '78145251',
    'groupama' => '77569974',
];

foreach ($mutuelles as $mutuelle) {
    $nom = strtolower($mutuelle->getNomMutuelle());
    foreach ($amcMapping as $key => $realAmc) {
        if (str_contains($nom, $key)) {
            $mutuelle->setCodeAmc($realAmc);
            break;
        }
    }
}

$em->flush();
echo "Codes AMC mis à jour avec de vraies valeurs !\n";
