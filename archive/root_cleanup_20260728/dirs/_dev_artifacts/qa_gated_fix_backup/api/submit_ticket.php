<?php
header('Content-Type: application/json');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap

try {
    
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid Session']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Invalid data']); exit; }
    
    // Generate Ticket ID if not provided
    if (empty($data['ticket_id'])) {
        $data['ticket_id'] = "TK-WEB-" . date('ymd-His');
    }
    
    // Ensure priority defaults to normal if somehow missing
    $priority = isset($data['priority']) ? $data['priority'] : 'normal';
    
    // Server-side identity hard-lock
    $locked_announced_by = $_SESSION['username'];

    $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'OPEN')");
    $stmt->execute([
        $data['ticket_id'], $data['equip_id'], $data['report_date'], $data['report_time'], 
        $locked_announced_by, $data['pic'], $data['fault_desc'], $priority
    ]);

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

    echo json_encode(['status' => 'success', 'message' => 'Event Registered! Ticket ID: ' . $data['ticket_id']]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not create ticket. Please try again.']);
}
?>