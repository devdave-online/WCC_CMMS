<?php
require 'inc/db.php';
$pdo = get_wcc_db_connection();
try {
    $pdo->prepare('DELETE FROM departments WHERE dept_id = 11')->execute();
    echo 'Success';
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
