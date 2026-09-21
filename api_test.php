<?php
require 'vendor/autoload.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$ord = $em->getRepository(\App\Entity\Ordonnance::class)->find(5);
$res = [];
foreach ($ord->getLignes() as $l) {
    $p = $l->getProduit();
    $res[] = ['id'=>$p->getId(), 'nom'=>$p->getNomProduit(), 'quantite'=>$l->getQuantite()];
}
echo json_encode($res);
