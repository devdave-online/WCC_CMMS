<?php
require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../inc/i18n.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => __('ticket.unauthorized_expired')]);
    exit;
}
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_perm('create_tickets');
require_once __DIR__ . '/../inc/csrf.php';

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    wcc_csrf_require_json($data['csrf'] ?? null);

    if (empty($data['equip_id']) || empty($data['action_taken'])) {
        echo json_encode(['status' => 'error', 'message' => __('ticket.missing_fields')]); exit;
    }
    
    // EVIL MAID PROTECTION: Hard-lock to logged in user
    require_once __DIR__ . '/../inc/techident.php';
    $tech_name = wcc_tech_name();

    $now = date('Y-m-d H:i:s');
    $dateOnly = date('Y-m-d');
    $timeOnly = date('H:i:s');
    // Using a special prefix so you know this bypassed the normal workflow
    $ticket_id = 'TK-QR-' . date('ymd-His'); 

    // 1. Create the ticket directly as CLOSED (with closed_by / closed_at for history)
    try {
        $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status, closed_by, closed_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'low', 'CLOSED', ?, NOW())");
        $stmt->execute([
            $ticket_id, $data['equip_id'], $dateOnly, $timeOnly,
            $tech_name, $tech_name, $data['action_taken'], $tech_name
        ]);
    } catch (PDOException $eCol) {
        // Older schema without closed_at
        $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status, closed_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'low', 'CLOSED', ?)");
        $stmt->execute([
            $ticket_id, $data['equip_id'], $dateOnly, $timeOnly,
            $tech_name, $tech_name, $data['action_taken'], $tech_name
        ]);
    }

    // 2. Log the instant action
    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, 'Quick Fix', 'Minor Adjustment', ?, 'None', 'None')");
    $stmtAction->execute([
        $ticket_id, $tech_name, $now, $now, $data['action_taken']
    ]);

    // 3. Notify history + analytics (union-dedupe: one bell row per user)
    require_once __DIR__ . '/../inc/notifications.php';
    $msg = 'Quick resolve closed: ' . $ticket_id . ' (by ' . $tech_name . ')';
    $actor = (int)($_SESSION['user_id'] ?? 0);
    wcc_notify_perms(['view_history', 'view_statistics'], 'ticket_closed', $msg, '/_rpt/history.php', 'success', $actor);

    echo json_encode(['status' => 'success', 'message' => __('ticket.instant_logged'), 'ticket_id' => $ticket_id]);
} catch (PDOException $e) {
    error_log('[WCC submit_instant_resolve] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => __('ticket.could_not_resolve')]);
}
?>
