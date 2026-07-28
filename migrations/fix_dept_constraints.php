<?php
// fix_dept_constraints.php
// Drops the restrictive purchase_orders -> departments constraint and adds ON DELETE SET NULL
// CLI only — one-shot ops script (not part of the numbered migration runner).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden: fix_dept_constraints.php is CLI only.\n");
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    echo "Fixing department constraints...\n";

    // Drop the existing constraint
    $pdo->exec("ALTER TABLE purchase_orders DROP FOREIGN KEY purchase_orders_ibfk_3");

    // Add the new constraint with ON DELETE SET NULL
    $pdo->exec("ALTER TABLE purchase_orders ADD CONSTRAINT purchase_orders_ibfk_3 FOREIGN KEY (dept_id) REFERENCES departments (dept_id) ON DELETE SET NULL");

    echo "Successfully updated purchase_orders_ibfk_3 to ON DELETE SET NULL.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
