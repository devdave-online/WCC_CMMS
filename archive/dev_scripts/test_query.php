<?php
require 'inc/db.php';
$pdo = get_wcc_db_connection();
try {
    $stmt = $pdo->query("DESCRIBE equipment");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
