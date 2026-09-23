<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
// Add nom and prenom columns to user table
$pdo->exec("ALTER TABLE user ADD nom VARCHAR(100) DEFAULT NULL, ADD prenom VARCHAR(100) DEFAULT NULL;");

// Update users
$pdo->exec("UPDATE user SET nom = 'YARA', prenom = 'Sonia' WHERE email = 'pharmacien@Jeffarma.fr'");
$pdo->exec("UPDATE user SET nom = 'YARA', prenom = 'Jean-François' WHERE email = 'stock@Jeffarma.fr'");
$pdo->exec("UPDATE user SET nom = 'YARA', prenom = 'Yohann' WHERE email = 'caisse@Jeffarma.fr'");

echo "DB updated successfully.\n";
