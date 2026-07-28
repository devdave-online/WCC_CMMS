<?php
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../inc/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => __('common.unauthorized')]);
    exit;
}
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_perm('view_tickets');
require_once __DIR__ . '/../inc/csrf.php';

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    wcc_csrf_require_json($data['csrf'] ?? null);

    if (empty($data['ticket_id']) || empty(trim($data['comment_text'] ?? ''))) {
        echo json_encode(['status' => 'error', 'message' => __('ticket.comment_required')]);
        exit;
    }

    require_once __DIR__ . '/../inc/techident.php';
    $tech_name = wcc_tech_name();
    
    // Optional: Only allow comments on active tickets (OPEN, PENDING, ESCALATED, HOLD)
    $stmtCheck = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
    $stmtCheck->execute([$data['ticket_id']]);
    $ticket = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => __('ticket.not_found')]);
        exit;
    }
    
    if ($ticket['status'] === 'CLOSED') {
        echo json_encode(['status' => 'error', 'message' => __('ticket.cannot_comment_closed')]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_name, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['ticket_id'],
        $tech_name,
        trim($data['comment_text'])
    ]);

    echo json_encode(['status' => 'success', 'message' => __('ticket.comment_added')]);
} catch (PDOException $e) {
    error_log('[WCC add_comment] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => __('api.database_error')]);
}
?>
