<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
$pdo->exec("ALTER TABLE vente ADD details_honoraires JSON DEFAULT NULL");
echo "DB updated for honoraires.\n";
