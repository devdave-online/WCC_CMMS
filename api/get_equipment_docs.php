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

    $stmt = $pdo->prepare("
        SELECT doc_id, doc_title, doc_type, file_path, uploaded_by, uploaded_at 
        FROM equipment_documents 
        WHERE equip_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$equip_id]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $docs]);
} catch (Exception $e) {
    error_log('[WCC get_equipment_docs] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>
