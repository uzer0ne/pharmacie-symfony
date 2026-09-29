<?php
$file = 'templates/reassort/show.html.twig';
$content = file_get_contents($file);

// Remplacer le bloc A_VALIDER
$content = preg_replace(
    '/{% if is_granted\(\'ROLE_PHARMACIEN\'\) %}\s*(<form action="\{\{ path\(\'app_reassort_valider\'.*?<\/form>)\s*{% else %}\s*<div class="alert alert-info.*?<\/div>\s*{% endif %}/s',
    '$1',
    $content
);

// Remplacer le bloc VALIDEE
$content = preg_replace(
    '/{% if is_granted\(\'ROLE_PHARMACIEN\'\) %}\s*(<form action="\{\{ path\(\'app_reassort_envoyer\'.*?<\/form>)\s*{% else %}\s*<div class="alert alert-success.*?<\/div>\s*{% endif %}/s',
    '$1',
    $content
);

file_put_contents($file, $content);
echo "Modification UI reassort terminée.";
