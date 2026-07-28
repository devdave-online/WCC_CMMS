<?php
require 'inc/db.php';
$pdo = get_wcc_db_connection();
try {
    $pdo->exec("ALTER TABLE equipment ADD COLUMN asset_purchase_id VARCHAR(100) NULL");
    echo "Added asset_purchase_id column.\n";
} catch (PDOException $e) {
    echo "Error (asset_purchase_id): " . $e->getMessage() . "\n";
}
?>
