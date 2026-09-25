<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pharmacie;charset=utf8mb4', 'root', '');
$pdo->exec("ALTER TABLE user ADD is_deleted TINYINT(1) NOT NULL DEFAULT 0");
echo "DB updated successfully.\n";
