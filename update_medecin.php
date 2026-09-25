<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
$pdo->exec("ALTER TABLE medecin ADD specialite VARCHAR(255) DEFAULT NULL, ADD numero_rpps VARCHAR(11) DEFAULT NULL");
echo "Updated Medecin schema\n";
