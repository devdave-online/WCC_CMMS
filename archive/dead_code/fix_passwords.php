<?php
$pdo = new PDO('mysql:host=localhost;dbname=workshop_db;charset=utf8mb4', 'root', '');
$hash = password_hash('admin', PASSWORD_DEFAULT);
$pdo->exec("UPDATE users SET password_hash = '$hash' WHERE username = 'admin'");
$hash2 = password_hash('password', PASSWORD_DEFAULT);
$pdo->exec("UPDATE users SET password_hash = '$hash2' WHERE username != 'admin'");
echo "Passwords reset";
?>
