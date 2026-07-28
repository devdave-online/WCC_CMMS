<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap

try {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    // Additional RBAC check could go here if needed

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
        exit;
    }

    $equip_id = isset($_POST['equip_id']) ? (int)$_POST['equip_id'] : 0;
    $doc_title = $_POST['doc_title'] ?? '';
    $doc_type = $_POST['doc_type'] ?? 'Other';

    if (!$equip_id || empty($doc_title) || !isset($_FILES['doc_file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields or file']);
        exit;
    }

    $file = $_FILES['doc_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File upload error code: ' . $file['error']]);
        exit;
    }

    // Limit size to 20MB
    if ($file['size'] > 20 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File too large (max 20MB)']);
        exit;
    }

    // Allowed extensions
    $allowed_exts = ['pdf', 'docx', 'txt', 'png', 'jpg', 'jpeg'];
    $file_info = pathinfo($file['name']);
    $ext = strtolower($file_info['extension'] ?? '');
    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_exts)]);
        exit;
    }

    $pdo = get_wcc_db_connection();

    // Get asset_uuid for folder creation
    $stmt = $pdo->prepare("SELECT asset_uuid FROM equipment WHERE equip_id = ?");
    $stmt->execute([$equip_id]);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipment) {
        echo json_encode(['status' => 'error', 'message' => 'Equipment not found']);
        exit;
    }

    $uuid = $equipment['asset_uuid'];
    if (empty($uuid)) {
        // Fallback if equipment has no UUID (though it should in this system)
        $uuid = 'eq_' . $equip_id; 
    }

    $base_doc_dir = __DIR__ . '/../_doc';
    $equip_doc_dir = $base_doc_dir . '/' . $uuid;

    if (!is_dir($equip_doc_dir)) {
        mkdir($equip_doc_dir, 0777, true);
    }

    // Clean filename
    $safe_filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_info['filename']) . '.' . $ext;
    // Prepend timestamp to avoid overrides
    $final_filename = time() . '_' . $safe_filename;
    
    $destination = $equip_doc_dir . '/' . $final_filename;
    $relative_path = $uuid . '/' . $final_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Insert DB record
        $stmt = $pdo->prepare("INSERT INTO equipment_documents (equip_id, doc_title, doc_type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$equip_id, $doc_title, $doc_type, $relative_path, $_SESSION['username']]);
        
        echo json_encode(['status' => 'success', 'message' => 'Document uploaded and linked successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
