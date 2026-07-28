<?php
$pdo = new PDO('mysql:host=localhost;dbname=workshop_db;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('DESCRIBE work_orders');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
