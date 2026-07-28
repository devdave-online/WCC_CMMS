<?php
/**
 * Companion App — offline photo evidence upload (multipart).
 *
 * POST multipart:
 *   parent_type = TICKET | WO
 *   parent_id   = ticket_id or wo_id
 *   caption     = optional
 *   file        = image binary
 *
 * 1) Stores file under uploads/companion_evidence/{type}/{id}/
 * 2) Inserts a DB row:
 *      TICKET → ticket_attachments (existing web table)
 *      WO     → wo_attachments (companion table)
 *
 * Does not modify web pages.
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/api_guard.php';
$pdo = get_wcc_db_connection();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$parent_type = strtoupper(trim((string)($_POST['parent_type'] ?? '')));
$parent_id = trim((string)($_POST['parent_id'] ?? ''));
$caption = trim((string)($_POST['caption'] ?? ''));
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($parent_type === '' || $parent_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'parent_type and parent_id required']);
    exit;
}
if (!in_array($parent_type, ['TICKET', 'WO'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'parent_type must be TICKET or WO']);
    exit;
}
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'file required']);
    exit;
}

$f = $_FILES['file'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error code ' . ($f['error'] ?? -1)]);
    exit;
}

$maxBytes = 12 * 1024 * 1024; // 12 MB
if (($f['size'] ?? 0) > $maxBytes) {
    echo json_encode(['status' => 'error', 'message' => 'File too large (max 12MB)']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($f['tmp_name']) ?: ($f['type'] ?? 'application/octet-stream');
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
if (!isset($allowed[$mime])) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPEG/PNG/WebP/GIF allowed']);
    exit;
}
$ext = $allowed[$mime];

// Ensure WO attachments table exists (tickets already have ticket_attachments).
try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS wo_attachments (
            id INT(11) NOT NULL AUTO_INCREMENT,
            wo_id INT(11) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            caption VARCHAR(500) DEFAULT NULL,
            uploaded_by INT(11) DEFAULT NULL,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wo_id (wo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
} catch (Throwable $e) {
    // Non-fatal — insert may still work if table already exists
}

// Optional caption column on ticket_attachments (ignore if already there / no privilege)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM ticket_attachments LIKE 'caption'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE ticket_attachments ADD COLUMN caption VARCHAR(500) DEFAULT NULL AFTER file_path");
    }
} catch (Throwable $e) { /* ignore */ }

$safeParent = preg_replace('/[^A-Za-z0-9._-]/', '_', $parent_id);
$relDir = 'uploads/companion_evidence/' . strtolower($parent_type) . '/' . $safeParent;
$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    echo json_encode(['status' => 'error', 'message' => 'Server path error']);
    exit;
}
$absDir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
if (!is_dir($absDir)) {
    if (!mkdir($absDir, 0775, true) && !is_dir($absDir)) {
        echo json_encode(['status' => 'error', 'message' => 'Could not create upload directory']);
        exit;
    }
}

$basename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$absPath = $absDir . DIRECTORY_SEPARATOR . $basename;
if (!move_uploaded_file($f['tmp_name'], $absPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to store file']);
    exit;
}

if ($caption !== '') {
    @file_put_contents($absPath . '.txt', $caption);
}

$urlPath = '/' . $relDir . '/' . $basename;
$dbId = null;

try {
    if ($parent_type === 'TICKET') {
        // Validate ticket exists (FK on ticket_attachments)
        $chk = $pdo->prepare('SELECT ticket_id FROM active_tickets WHERE ticket_id = ? LIMIT 1');
        $chk->execute([$parent_id]);
        if (!$chk->fetch()) {
            @unlink($absPath);
            echo json_encode(['status' => 'error', 'message' => 'Ticket not found for attachment']);
            exit;
        }
        try {
            $ins = $pdo->prepare(
                'INSERT INTO ticket_attachments (ticket_id, file_name, file_path, caption, uploaded_by)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([$parent_id, $basename, $urlPath, $caption !== '' ? $caption : null, $user_id ?: null]);
        } catch (Throwable $e) {
            // Fallback if caption column missing
            $ins = $pdo->prepare(
                'INSERT INTO ticket_attachments (ticket_id, file_name, file_path, uploaded_by)
                 VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$parent_id, $basename, $urlPath, $user_id ?: null]);
        }
        $dbId = (int)$pdo->lastInsertId();
    } else {
        $woId = (int)$parent_id;
        if ($woId <= 0) {
            @unlink($absPath);
            echo json_encode(['status' => 'error', 'message' => 'Invalid wo_id']);
            exit;
        }
        $chk = $pdo->prepare('SELECT wo_id FROM work_orders WHERE wo_id = ? LIMIT 1');
        $chk->execute([$woId]);
        if (!$chk->fetch()) {
            @unlink($absPath);
            echo json_encode(['status' => 'error', 'message' => 'Work order not found for attachment']);
            exit;
        }
        $ins = $pdo->prepare(
            'INSERT INTO wo_attachments (wo_id, file_name, file_path, caption, uploaded_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $ins->execute([$woId, $basename, $urlPath, $caption !== '' ? $caption : null, $user_id ?: null]);
        $dbId = (int)$pdo->lastInsertId();
    }
} catch (Throwable $e) {
    @unlink($absPath);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB insert failed: ' . $e->getMessage(),
    ]);
    exit;
}

if (function_exists('audit_log')) {
    try {
        audit_log('companion_evidence_upload', [
            'parent_type' => $parent_type,
            'parent_id' => $parent_id,
            'path' => $urlPath,
            'db_id' => $dbId,
            'user_id' => $user_id,
        ]);
    } catch (Throwable $e) { /* ignore */ }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Evidence stored',
    'url' => $urlPath,
    'path' => $urlPath,
    'db_id' => $dbId,
]);
