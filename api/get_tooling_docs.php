<?php
/**
 * List documents linked to a tooling record.
 * GET ?tooling_id=N
 * Perm: view_toolings
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
        SELECT doc_id, doc_title, doc_type, file_path, uploaded_by, uploaded_at
        FROM tooling_documents
        WHERE tooling_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$tooling_id]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $docs]);
} catch (Exception $e) {
    error_log('[WCC get_tooling_docs] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
