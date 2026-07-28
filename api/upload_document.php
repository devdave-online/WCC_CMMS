<?php
/**
 * Document upload for Equipment (legacy) and Tooling.
 *
 * POST multipart:
 *   csrf, doc_title, doc_type, doc_file
 *   entity = equipment | tooling  (default: equipment when equip_id present)
 *   equip_id   — required for equipment
 *   tooling_id — required for tooling
 *
 * Equipment path is unchanged for admin_panel (equip_id only, manage_settings).
 * Tooling allows manage_toolings OR manage_settings.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/session.php';

try {
    // Align with api_guard: accept user_id or username (some session rebuilds set only user_id briefly)
    if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    require_once __DIR__ . '/../inc/api_guard.php';
    require_once __DIR__ . '/../rbac.php';
    require_once __DIR__ . '/../inc/csrf.php';
    wcc_csrf_require_json($_POST['csrf'] ?? null);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
        exit;
    }

    $entity = strtolower(trim((string)($_POST['entity'] ?? '')));
    $equip_id = isset($_POST['equip_id']) ? (int)$_POST['equip_id'] : 0;
    $tooling_id = isset($_POST['tooling_id']) ? (int)$_POST['tooling_id'] : 0;

    // Backward compatible: no entity + equip_id → equipment
    if ($entity === '' || $entity === 'equipment') {
        if ($equip_id > 0) {
            $entity = 'equipment';
        } elseif ($tooling_id > 0) {
            $entity = 'tooling';
        } else {
            $entity = 'equipment';
        }
    }
    if (!in_array($entity, ['equipment', 'tooling'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid entity. Use equipment or tooling.']);
        exit;
    }

    if ($entity === 'equipment') {
        api_guard_perm('manage_settings');
    } else {
        // Vault operators: manage_toolings (or legacy manage_equipment) or settings
        if (!can('manage_toolings') && !can('manage_equipment') && !can('manage_settings')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden — need manage_toolings.']);
            exit;
        }
    }

    $doc_title = trim((string)($_POST['doc_title'] ?? ''));
    $doc_type = trim((string)($_POST['doc_type'] ?? 'Other'));
    $allowed_types = ['SOP', 'Manual', 'Diagram', 'Drawing', 'Calibration', 'Other'];
    if (!in_array($doc_type, $allowed_types, true)) {
        $doc_type = 'Other';
    }

    if ($doc_title === '' || !isset($_FILES['doc_file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields or file']);
        exit;
    }
    if ($entity === 'equipment' && $equip_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing equip_id']);
        exit;
    }
    if ($entity === 'tooling' && $tooling_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing tooling_id']);
        exit;
    }

    $file = $_FILES['doc_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File upload error code: ' . $file['error']]);
        exit;
    }
    if ($file['size'] > 20 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File too large (max 20MB)']);
        exit;
    }

    $allowed_exts = ['pdf', 'docx', 'txt', 'png', 'jpg', 'jpeg'];
    $file_info = pathinfo($file['name']);
    $ext = strtolower($file_info['extension'] ?? '');
    if (!in_array($ext, $allowed_exts, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_exts)]);
        exit;
    }

    $pdo = get_wcc_db_connection();
    $base_doc_dir = __DIR__ . '/../_doc';

    if ($entity === 'equipment') {
        $stmt = $pdo->prepare("SELECT asset_uuid FROM equipment WHERE equip_id = ?");
        $stmt->execute([$equip_id]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$equipment) {
            echo json_encode(['status' => 'error', 'message' => 'Equipment not found']);
            exit;
        }
        $folder = !empty($equipment['asset_uuid'])
            ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$equipment['asset_uuid'])
            : ('eq_' . $equip_id);
        // Keep equipment layout: _doc/{uuid}/file  (unchanged)
        $rel_dir = $folder;
        $target_table = 'equipment_documents';
        $fk_col = 'equip_id';
        $fk_val = $equip_id;
    } else {
        $stmt = $pdo->prepare("SELECT tooling_id, tooling_code FROM toolings WHERE tooling_id = ? AND deleted_at IS NULL");
        $stmt->execute([$tooling_id]);
        $tooling = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tooling) {
            echo json_encode(['status' => 'error', 'message' => 'Tooling not found']);
            exit;
        }
        $code = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)($tooling['tooling_code'] ?? ''));
        if ($code === '') {
            $code = 'tl_' . $tooling_id;
        }
        // Namespace under tooling/ so we never collide with equipment UUID folders
        $rel_dir = 'tooling/' . $code;
        $target_table = 'tooling_documents';
        $fk_col = 'tooling_id';
        $fk_val = $tooling_id;
    }

    $abs_dir = $base_doc_dir . '/' . $rel_dir;
    if (!is_dir($abs_dir)) {
        if (!mkdir($abs_dir, 0777, true) && !is_dir($abs_dir)) {
            echo json_encode(['status' => 'error', 'message' => 'Could not create document folder']);
            exit;
        }
    }

    $safe_filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_info['filename'] ?? 'doc') . '.' . $ext;
    $final_filename = time() . '_' . $safe_filename;
    $destination = $abs_dir . '/' . $final_filename;
    $relative_path = $rel_dir . '/' . $final_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
        exit;
    }

    // Only the two known tables (no user input in SQL identifiers)
    $uploadedBy = (string)($_SESSION['username'] ?? ('user#' . (int)($_SESSION['user_id'] ?? 0)));
    if ($target_table === 'equipment_documents') {
        $pdo->prepare("INSERT INTO equipment_documents (equip_id, doc_title, doc_type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)")
            ->execute([$fk_val, $doc_title, $doc_type, $relative_path, $uploadedBy]);
    } else {
        $pdo->prepare("INSERT INTO tooling_documents (tooling_id, doc_title, doc_type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)")
            ->execute([$fk_val, $doc_title, $doc_type, $relative_path, $uploadedBy]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Document uploaded and linked successfully!']);
} catch (Exception $e) {
    error_log('[WCC upload_document] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
}
