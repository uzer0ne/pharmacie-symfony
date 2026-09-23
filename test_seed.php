<?php
require 'vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->bootEnv('.env');
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$patient = $em->getRepository(\App\Entity\Patient::class)->find(1);
if (!$patient) {
    $patient = new \App\Entity\Patient();
    $patient->setNomPatient('Dupont');
    $patient->setPrenomPatient('Jean');
    $patient->setAdressePatient('1 rue de la Paix');
    $patient->setDateNaissance(new \DateTime('1980-01-01'));
    $em->persist($patient);
    $em->flush();
}
$mut = $em->getRepository(\App\Entity\Mutuelle::class)->find(1);
if (!$mut) {
    $mut = new \App\Entity\Mutuelle();
    $mut->setNomMutuelle('Harmonie Mutuelle');
    $mut->setContactMutuelle('0102030405');
    $mut->setTauxRemboursement('100');
    $em->persist($mut);
    $em->flush();
}
$patient->addMutuelle($mut);
$ord = $em->getRepository(\App\Entity\Ordonnance::class)->find(1);
if (!$ord) {
    $ord = new \App\Entity\Ordonnance();
    $ord->setPatient($patient);
    $ord->setDateOrdonnance(new \DateTime());
    $ord->setValidForFirstDispense(true);
    
    // add a line
    $prod = $em->getRepository(\App\Entity\Produit::class)->findOneBy([]);
    if ($prod) {
        $ligne = new \App\Entity\LigneOrdonnance();
        $ligne->setOrdonnance($ord);
        $ligne->setProduit($prod);
        $ligne->setQuantite(2);
        $ligne->setPosologie('1 par jour');
        $ligne->setDureeTraitement(10);
        $ligne->setRenouvellementsAutorises(0);
        $em->persist($ligne);
    }
    
    $em->persist($ord);
}
$em->flush();
echo "Data seeded!";
