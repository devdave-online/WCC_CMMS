<?php
// Enterprise centralized DB connection (highest quality) - debug script
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

$stmt = $pdo->query("SHOW TABLES LIKE 'team_directory'");
$exists = $stmt->fetch();
if ($exists) {
    echo "team_directory exists.\n";
    $stmt2 = $pdo->query('SELECT * FROM team_directory');
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "team_directory DOES NOT EXIST.\n";
}
?>
