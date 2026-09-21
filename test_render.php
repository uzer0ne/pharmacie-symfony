<?php
require 'vendor/autoload.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$formFactory = $container->get('form.factory');
$form = $formFactory->create(\App\Form\OrdonnanceType::class);
$view = $form->createView();
$twig = $container->get('twig');
$html = $twig->render('ordonnance/new.html.twig', [
    'ordonnance' => new \App\Entity\Ordonnance(),
    'form' => $view,
]);
file_put_contents('test_out.html', $html);
