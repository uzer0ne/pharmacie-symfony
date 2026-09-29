<?php
// load_fixtures.php
require 'vendor/autoload.php';

use App\Kernel;
use App\Entity\Mutuelle;
use App\Entity\Patient;
use App\Entity\PatientMutuelle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
/** @var EntityManagerInterface $em */
$em = $container->get('doctrine')->getManager();

echo "Création Mutuelles...\n";
$mutuellesData = [
    ['Allianz Santé', '0800 123 456', 'AMC' . uniqid()],
    ['Axa Santé', '3676', 'AMC' . uniqid()],
    ['Macif Santé', '09 72 72 72 72', 'AMC' . uniqid()]
];
$mutuelles = [];
foreach ($mutuellesData as $data) {
    $m = new Mutuelle();
    $m->setNomMutuelle($data[0]);
    $m->setContactMutuelle($data[1]);
    $m->setCodeAmc($data[2]);
    $em->persist($m);
    $mutuelles[] = $m;
}

echo "Création Patients...\n";
$patientsData = [
    ['Boucher', 'Paul', '15 Rue des Lilas', '1985-05-14', '1850575123456', '0601020304'],
    ['Picard', 'Marie', '23 Avenue de la Mer', '1990-11-22', '2901175123456', '0611223344']
];
$patients = [];
foreach ($patientsData as $data) {
    $p = new Patient();
    $p->setNomPatient($data[0]);
    $p->setPrenomPatient($data[1]);
    $p->setAdressePatient($data[2]);
    $p->setDateNaissance(new \DateTime($data[3]));
    $p->setNir($data[4]);
    $p->setTelephone($data[5]);
    $em->persist($p);
    $patients[] = $p;
}

echo "Création Contrats Mutuelle...\n";
$pm1 = new PatientMutuelle();
$pm1->setPatient($patients[0]);
$pm1->setMutuelle($mutuelles[0]);
$pm1->setNumeroAdherent('ALL-12345-X');
$pm1->setDateDebutValidite(new \DateTime('2024-01-01'));
$pm1->setDateFinValidite(new \DateTime('2024-12-31'));
$pm1->setTypeConvention('RC');
$pm1->setTauxCouverture('100.00');
$pm1->setActif(true);
$em->persist($pm1);

$pm2 = new PatientMutuelle();
$pm2->setPatient($patients[1]);
$pm2->setMutuelle($mutuelles[1]);
$pm2->setNumeroAdherent('AXA-98765-Y');
$pm2->setDateDebutValidite(new \DateTime('2024-01-01'));
$pm2->setDateFinValidite(new \DateTime('2024-12-31'));
$pm2->setTypeConvention('RO');
$pm2->setTauxCouverture('70.00');
$pm2->setActif(true);
$em->persist($pm2);

$em->flush();
echo "Données ajoutées avec succès !\n";
