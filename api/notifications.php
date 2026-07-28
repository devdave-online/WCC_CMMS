<?php
/**
 * Notification actions (per-user). JSON POST, session + CSRF gated.
 * Actions: mark_read (one), mark_all_read, delete_one, delete_all.
 * Every query is scoped to the session user — no cross-user access.
 */
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../inc/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => __('common.unauthorized')]);
    exit;
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';

$pdo  = get_wcc_db_connection();
$uid  = (int)$_SESSION['user_id'];
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$action = $body['action'] ?? '';
$csrf   = $body['csrf'] ?? null;

if (!wcc_csrf_valid($csrf)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => __('common.security_check')]);
    exit;
}

try {
    switch ($action) {
        case 'mark_read':
            $id = (int)($body['id'] ?? 0);
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
            echo json_encode(['status' => 'success']);
            break;
        case 'mark_all_read':
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
            echo json_encode(['status' => 'success', 'message' => __('notif.all_marked_read')]);
            break;
        case 'delete_one':
            $id = (int)($body['id'] ?? 0);
            $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
            echo json_encode(['status' => 'success']);
            break;
        case 'delete_all':
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$uid]);
            echo json_encode(['status' => 'success', 'message' => __('notif.all_cleared')]);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => __('notif.unknown_action')]);
    }
} catch (Throwable $e) {
    error_log('[WCC notifications] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => __('notif.could_not_update')]);
}
?>
