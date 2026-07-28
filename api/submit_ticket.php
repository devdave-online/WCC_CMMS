<?php
header('Content-Type: application/json');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/techident.php';
require_once __DIR__ . '/../inc/ticketid.php';
require_once __DIR__ . '/../inc/audit.php';
require_once __DIR__ . '/../inc/notifications.php';
require_once __DIR__ . '/../inc/kpi.php';   // WCC_EVENT_CLASSES for event_class validation
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap
require_once __DIR__ . '/../inc/i18n.php';

try {
    
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => __('ticket.unauthorized_session')]);
        exit;
    }
    require_once __DIR__ . '/../inc/api_guard.php';
    api_guard_perm('create_tickets');
    require_once __DIR__ . '/../inc/csrf.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        echo json_encode(['status' => 'error', 'message' => __('ticket.invalid_data')]);
        exit;
    }
    wcc_csrf_require_json($data['csrf'] ?? null);
    
    // Ensure priority defaults to normal if somehow missing
    $priority = isset($data['priority']) ? $data['priority'] : 'normal';

    // Reliability event class — validated against the known taxonomy; anything else
    // (or missing) falls back to 'failure', matching the column default.
    $event_class = (isset($data['event_class']) && isset(WCC_EVENT_CLASSES[$data['event_class']]))
        ? $data['event_class'] : 'failure';

    // Server-side identity hard-lock
    $locked_announced_by = wcc_tech_name();

    // The ticket ID is always allocated server-side and is never taken from the
    // request: a client-supplied key could collide with an existing ticket. The
    // helper retries if a concurrent registration claims the same sequence number.
    $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, event_class, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'OPEN')");
    $data['ticket_id'] = wcc_insert_ticket($pdo, function (string $ticketId) use ($stmt, $data, $locked_announced_by, $priority, $event_class) {
        $stmt->execute([
            $ticketId, $data['equip_id'], $data['report_date'], $data['report_time'],
            $locked_announced_by, $data['pic'], $data['fault_desc'], $priority, $event_class
        ]);
    });

    // Phase 5 audit logging
    wcc_audit_log(
        'ticket.create',
        'active_tickets',
        $data['ticket_id'],
        null,
        [
            'equip_id' => $data['equip_id'],
            'priority' => $priority,
            'announced_by' => $locked_announced_by,
            'pic' => $data['pic']
        ],
        'New intervention logged'
    );

    // Notify technicians/supervisors who can take over tickets (not the reporter).
    wcc_notify_perm('takeover_tickets', 'ticket_new',
        'New event logged: ' . mb_substr($data['fault_desc'] ?? 'intervention', 0, 60) . ' (' . $data['ticket_id'] . ')',
        '/_maint/active_tickets.php',
        ($priority === 'high' ? 'warning' : 'info'),
        (int)($_SESSION['user_id'] ?? 0)
    );

    echo json_encode(['status' => 'success', 'message' => __('ticket.registered', ['id' => $data['ticket_id']])]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => __('ticket.could_not_create')]);
}
?>
