<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = 'user' AND TABLE_SCHEMA = 'pharmacie'");
$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($fks);
