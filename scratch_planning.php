<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$userRepo = $em->getRepository(App\Entity\User::class);
$users = $userRepo->findAll();

// 1. Définir les qualifications basées sur les rôles
$sonia = null;
$jean = null;
$yohann = null;

foreach ($users as $user) {
    if (in_array('ROLE_PHARMACIEN', $user->getRoles())) {
        $user->setQualification('TITULAIRE');
        $user->setTempsTravailHebdo(35);
        $sonia = $user;
    } elseif (in_array('ROLE_GESTIONNAIRE_STOCK', $user->getRoles())) {
        $user->setQualification('PREPARATEUR');
        $user->setTempsTravailHebdo(35);
        $jean = $user;
    } elseif (in_array('ROLE_CAISSIER', $user->getRoles())) {
        $user->setQualification('ETUDIANT');
        $user->setTempsTravailHebdo(14);
        $yohann = $user;
    }
}
$em->flush();

// 2. Générer des créneaux pour cette semaine (28 Sept 2026 - 4 Oct 2026)
$em->getConnection()->executeStatement('DELETE FROM creneau_planning');

function addCreneau($em, $user, $start, $end, $type) {
    if (!$user) return;
    $c = new App\Entity\CreneauPlanning();
    $c->setUser($user);
    $c->setDateDebut(new \DateTime($start));
    $c->setDateFin(new \DateTime($end));
    $c->setTypeCreneau($type);
    $c->setStatut('PUBLIE');
    $em->persist($c);
}

// Lundi 28 Sept 2026
addCreneau($em, $sonia, '2026-09-28 08:30:00', '2026-09-28 12:30:00', 'COMPTOIR');
addCreneau($em, $sonia, '2026-09-28 14:00:00', '2026-09-28 18:00:00', 'BACK_OFFICE');
addCreneau($em, $jean, '2026-09-28 09:00:00', '2026-09-28 13:00:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-09-28 14:00:00', '2026-09-28 19:00:00', 'COMPTOIR');

// Mardi 29 Sept 2026
addCreneau($em, $sonia, '2026-09-29 08:30:00', '2026-09-29 18:30:00', 'COMPTOIR'); // Journée continue
addCreneau($em, $jean, '2026-09-29 09:00:00', '2026-09-29 13:00:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-09-29 14:00:00', '2026-09-29 19:00:00', 'BACK_OFFICE');

// Mercredi 30 Sept 2026
addCreneau($em, $sonia, '2026-09-30 08:30:00', '2026-09-30 12:30:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-09-30 14:00:00', '2026-09-30 19:00:00', 'COMPTOIR');
addCreneau($em, $yohann, '2026-09-30 14:00:00', '2026-09-30 19:00:00', 'COMPTOIR'); // Etudiant l'aprem

// Jeudi 1 Oct 2026
addCreneau($em, $sonia, '2026-10-01 09:00:00', '2026-10-01 13:00:00', 'BACK_OFFICE');
addCreneau($em, $jean, '2026-10-01 08:30:00', '2026-10-01 12:30:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-10-01 14:00:00', '2026-10-01 19:00:00', 'COMPTOIR');

// Vendredi 2 Oct 2026
addCreneau($em, $sonia, '2026-10-02 08:30:00', '2026-10-02 12:30:00', 'COMPTOIR');
addCreneau($em, $sonia, '2026-10-02 14:00:00', '2026-10-02 19:00:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-10-02 09:00:00', '2026-10-02 12:30:00', 'COMPTOIR');
addCreneau($em, $jean, '2026-10-02 13:30:00', '2026-10-02 18:00:00', 'COMPTOIR');

// Samedi 3 Oct 2026
addCreneau($em, $yohann, '2026-10-03 09:00:00', '2026-10-03 13:00:00', 'COMPTOIR');
addCreneau($em, $yohann, '2026-10-03 14:00:00', '2026-10-03 19:00:00', 'COMPTOIR');
addCreneau($em, $sonia, '2026-10-03 09:00:00', '2026-10-03 13:00:00', 'COMPTOIR');

$em->flush();
echo "Génération terminée avec succès.\n";
