<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap

try {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    require_once __DIR__ . '/../inc/api_guard.php';
    api_guard_perm('takeover_tickets');
    require_once __DIR__ . '/../inc/csrf.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    wcc_csrf_require_json($data['csrf'] ?? null);

    if (!isset($data['ticket_id']) || !isset($data['reason']) || !isset($data['explanation'])) { 
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']); 
        exit; 
    }

    $ticket_id = $data['ticket_id'];
    $reason = $data['reason'];
    $explanation = $data['explanation'];
    require_once __DIR__ . '/../inc/techident.php';
    $tech_name = wcc_tech_name();

    // Ensure the ticket exists
    $stmt = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    // Change status to HOLD
    $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'HOLD' WHERE ticket_id = ?");
    $stmtUpdate->execute([$ticket_id]);

    // Log the hold action as a zero-minute action so it appears in the timeline
    // This explicitly tracks the ghost time reason without adding to active wrench time (start == end)
    $now = date('Y-m-d H:i:s');
    $action_taken = "⏸️ PLACED ON HOLD\nReason: $reason\nExplanation: $explanation";
    
    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtAction->execute([
        $ticket_id, $tech_name, $now, $now, 
        'Other', 'On Hold', $action_taken, 'None', 'None'
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Ticket put on hold.']);
} catch (PDOException $e) {
    error_log('[WCC submit_hold] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>
