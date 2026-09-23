<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
// Delete remaining admins or users just in case
$pdo->exec("DELETE FROM user WHERE email LIKE 'admin%' OR email LIKE 'user%'");
// Insert caissier
$stmt = $pdo->prepare("INSERT INTO user (email, roles, password) VALUES (?, ?, ?)");
$stmt->execute([
    'caisse@Jeffarma.fr',
    '["ROLE_CAISSIER"]',
    '$2y$13$TjgQOnAxA3MKRje6jfG4gOONUSLUN1KIfEWaQAWJLEhu5LEiroYKK'
]);
echo "Caissier added successfully.\n";
