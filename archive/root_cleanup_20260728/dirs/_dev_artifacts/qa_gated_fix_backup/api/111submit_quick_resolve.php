<?php
header('Content-Type: application/json');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['ticket_id']) || !isset($data['tech_name']) || !isset($data['action_taken'])) { 
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']); 
        exit; 
    }

    // Capture the exact moment of the quick fix
    $now = date('Y-m-d H:i:s');

    // 1. Insert the action (using identical start/end times and placeholder fault data)
    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, 'Quick Fix', 'Minor Adjustment', ?, 'None', 'None')");
    $stmtAction->execute([
        $data['ticket_id'], 
        $data['tech_name'], 
        $now, 
        $now, 
        $data['action_taken']
    ]);

    // 2. Immediately close the ticket
    $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'CLOSED' WHERE ticket_id = ?");
    $stmtUpdate->execute([$data['ticket_id']]);

    echo json_encode(['status' => 'success', 'message' => 'Quick Fix Applied & Ticket Closed!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>