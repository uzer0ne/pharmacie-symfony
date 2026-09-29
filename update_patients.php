<?php
require 'vendor/autoload.php';
use App\Kernel;
use App\Entity\Patient;
use App\Entity\PatientMutuelle;
use App\Entity\Mutuelle;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "Mise à jour des patients existants...\n";
$patients = $em->getRepository(Patient::class)->findAll();
$updatedCount = 0;
foreach ($patients as $p) {
    if (empty($p->getNir())) {
        // Génération d'un faux NIR à 15 chiffres : S(1) YY(2) MM(2) DPT(2) COM(3) ORD(3) CLE(2)
        $gender = rand(1, 2);
        $year = str_pad((string)rand(40, 99), 2, '0', STR_PAD_LEFT);
        $month = str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT);
        $rest = (string)rand(100000000, 999999999);
        $p->setNir($gender . $year . $month . $rest);
        $updatedCount++;
    }
}
echo "$updatedCount patient(s) existant(s) mis à jour avec un NIR.\n";

$mutuelles = $em->getRepository(Mutuelle::class)->findAll();
if (count($mutuelles) > 0) {
    echo "Ajout de nouveaux patients avec mutuelle...\n";
    $newPatientsData = [
        ['Durand', 'Alice', '7 Chemin des Roses', '1978-03-12', '2780375111222', '0655443322'],
        ['Lefevre', 'Lucas', '12 Boulevard Haussmann', '1995-07-24', '1950775222333', '0699881122'],
        ['Moreau', 'Claire', '88 Rue de la Paix', '1982-12-05', '2821275333444', '0677665544'],
        ['Simon', 'Hugo', '5 Impasse du Moulin', '1990-09-18', '1900975444555', '0633221100'],
        ['Roux', 'Emma', '2 Place de la Mairie', '2001-02-14', '2010275555666', '0611111111']
    ];

    foreach ($newPatientsData as $data) {
        $p = new Patient();
        $p->setNomPatient($data[0]);
        $p->setPrenomPatient($data[1]);
        $p->setAdressePatient($data[2]);
        $p->setDateNaissance(new \DateTime($data[3]));
        $p->setNir($data[4]);
        $p->setTelephone($data[5]);
        $em->persist($p);

        // Attribution d'une mutuelle aléatoire
        $randomMutuelle = $mutuelles[array_rand($mutuelles)];
        
        $pm = new PatientMutuelle();
        $pm->setPatient($p);
        $pm->setMutuelle($randomMutuelle);
        $pm->setNumeroAdherent('ADH-' . rand(10000, 99999));
        $pm->setDateDebutValidite(new \DateTime(date('Y') . '-01-01'));
        $pm->setDateFinValidite(new \DateTime(date('Y') . '-12-31'));
        $pm->setTypeConvention(['RO', 'RC'][rand(0, 1)]);
        $pm->setTauxCouverture((string) [70, 80, 100][rand(0, 2)]);
        $pm->setActif(true);
        $em->persist($pm);
    }
    echo count($newPatientsData) . " nouveaux patients ajoutés.\n";
} else {
    echo "Aucune mutuelle trouvée pour associer aux nouveaux patients.\n";
}

$em->flush();
echo "Base de données synchronisée avec succès !\n";
