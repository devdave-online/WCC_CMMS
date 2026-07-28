<?php
// cron_requisition.php - Automated Requisition Engine (batch)
// Access control: runnable from CLI (scheduled task) or by an authenticated user
// with procurement authority. Not open to the anonymous web.
//
// This is the BATCH sweep. The primary path is now event-driven: parts are
// re-ordered the moment consumption drops them below threshold (inc/reorder.php,
// hooked into the work-order/ticket consumption points). This sweep exists as a
// safety net / manual "Run reorder check" and shares the exact same logic so
// both paths produce identical, well-formed reorder PRs.
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    require_once __DIR__ . '/rbac.php';
    require_once __DIR__ . '/inc/csrf.php';
    if (!can('approve_purchase_orders') && !can('manage_inventory')) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Forbidden: this engine runs from the scheduler or an authorized user only.';
        exit;
    }
    // State-changing over the web → require the session CSRF token.
    wcc_csrf_require($_GET['csrf'] ?? $_POST['csrf'] ?? null);
}

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/reorder.php';
$pdo = get_wcc_db_connection();

$actor = $_SESSION['user_id'] ?? null;
$json  = (PHP_SAPI !== 'cli' && isset($_GET['json']));

try {
    // Every candidate part; wcc_check_and_reorder re-applies all guards + dedupe.
    $parts = $pdo->query("SELECT part_id FROM inventory_parts WHERE stock_level <= minimum_threshold AND auto_reorder = 1 AND lifecycle_status = 'Active'")
                 ->fetchAll(PDO::FETCH_COLUMN);

    $created = [];
    foreach ($parts as $pid) {
        $po = wcc_check_and_reorder($pdo, (int)$pid, $actor !== null ? (int)$actor : null);
        if ($po) $created[] = $po;
    }

    $msg = 'Reorder check complete. Placed ' . count($created) . ' reorder(s).';
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => $msg, 'created' => $created]);
    } else {
        header('Content-Type: text/plain');
        echo $msg . "\n";
        foreach ($created as $po) echo "  $po\n";
    }
} catch (Throwable $e) {
    if ($json) { header('Content-Type: application/json'); echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    else { die('Cron Error: ' . $e->getMessage()); }
}
