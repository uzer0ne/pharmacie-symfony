<?php
$file = 'templates/partials/_sidebar.html.twig';
$content = file_get_contents($file);

$content = preg_replace(
    '/{% if is_granted\(\'ROLE_PHARMACIEN\'\) %}(\s*<li class="nav-item border-top border-secondary pt-2 mt-2">\s*<a class="nav-link.*?Planning.*?<\/a>\s*<\/li>\s*){% endif %}/s',
    '$1',
    $content
);

file_put_contents($file, $content);
