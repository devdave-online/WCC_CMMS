<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_perm('view_equipment');
require_once __DIR__ . '/../inc/db.php';

try {
    if (!isset($_GET['equip_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing equip_id']);
        exit;
    }

    $pdo = get_wcc_db_connection();
    $equip_id = (int)$_GET['equip_id'];

    // Join equipment_bom with inventory_parts to get part names and SKUs
    $stmt = $pdo->prepare("
        SELECT 
            b.quantity,
            p.part_name,
            p.internal_code AS sku
        FROM equipment_bom b
        JOIN inventory_parts p ON b.part_id = p.part_id
        WHERE b.equip_id = ?
        ORDER BY p.part_name ASC
    ");
    $stmt->execute([$equip_id]);
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $parts]);
} catch (Exception $e) {
    error_log('[WCC get_equipment_bom] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>
