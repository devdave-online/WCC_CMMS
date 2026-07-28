<?php
/**
 * Linked parts (tool BOM) for a tooling record.
 * Mirrors get_equipment_bom.php — same JSON shape for ledger modal reuse.
 * GET ?tooling_id=N
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_perm('view_toolings');
require_once __DIR__ . '/../inc/db.php';

try {
    if (!isset($_GET['tooling_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing tooling_id']);
        exit;
    }

    $pdo = get_wcc_db_connection();
    $tooling_id = (int)$_GET['tooling_id'];
    if ($tooling_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid tooling_id']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            b.bom_id,
            b.quantity,
            p.part_name,
            p.internal_code AS sku
        FROM tooling_bom b
        JOIN inventory_parts p ON b.part_id = p.part_id
        WHERE b.tooling_id = ?
        ORDER BY p.part_name ASC
    ");
    $stmt->execute([$tooling_id]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $parts]);
} catch (Exception $e) {
    error_log('[WCC get_tooling_bom] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
