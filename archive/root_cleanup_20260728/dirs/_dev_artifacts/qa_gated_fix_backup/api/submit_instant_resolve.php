<?php
require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Session expired.']);
    exit;
}

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['equip_id']) || empty($data['action_taken'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); exit;
    }
    
    // EVIL MAID PROTECTION: Hard-lock to logged in user
    $tech_name = $_SESSION['username'] ?? 'Unknown User';

    $now = date('Y-m-d H:i:s');
    $dateOnly = date('Y-m-d');
    $timeOnly = date('H:i:s');
    // Using a special prefix so you know this bypassed the normal workflow
    $ticket_id = 'TK-QR-' . date('ymd-His'); 

    // 1. Create the ticket directly as CLOSED
    $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'low', 'CLOSED')");
    $stmt->execute([
        $ticket_id, $data['equip_id'], $dateOnly, $timeOnly,
        $tech_name, $tech_name, $data['action_taken']
    ]);

    // 2. Log the instant action
    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, 'Quick Fix', 'Minor Adjustment', ?, 'None', 'None')");
    $stmtAction->execute([
        $ticket_id, $tech_name, $now, $now, $data['action_taken']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Instant Fix logged successfully!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not record the quick resolution.']);
}
?>