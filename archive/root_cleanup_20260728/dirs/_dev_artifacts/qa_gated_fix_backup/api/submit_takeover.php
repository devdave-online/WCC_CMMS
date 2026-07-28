<?php
header('Content-Type: application/json');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap

try {
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid Session']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['ticket_id'])) { echo json_encode(['status' => 'error', 'message' => 'Invalid data']); exit; }

    // Server-side identity hard-lock
    $locked_tech_name = $_SESSION['username'];

    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtAction->execute([
        $data['ticket_id'], $locked_tech_name, $data['action_start'], $data['action_end'], 
        $data['fault_type'], $data['root_cause'], $data['action_taken'], $data['parts_used'], $data['escalated_to']
    ]);

    $new_status = ($data['action_type'] === 'escalate') ? 'ESCALATED' : 'PENDING';
    $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = ? WHERE ticket_id = ?");
    $stmtUpdate->execute([$new_status, $data['ticket_id']]);

    // Handle inventory consumption
    if (isset($data['parts_consumed_data']) && is_array($data['parts_consumed_data'])) {
        $stmtInv = $pdo->prepare("UPDATE inventory_parts SET stock_level = stock_level - ? WHERE part_id = ? AND stock_level >= ?");
        foreach ($data['parts_consumed_data'] as $part) {
            $qty = (int)$part['qty'];
            $part_id = (int)$part['part_id'];
            if ($qty > 0 && $part_id > 0) {
                // To keep it simple, we decrement, but we cap it at >= qty so it doesn't go below 0
                // Wait, if we want to allow negative stock (for backorders), we remove `AND stock_level >= ?`.
                // For a workshop, negative stock might be realistic if they grab parts without POs, but let's just do a blind decrement for now since they said "deduct inventory".
                $pdo->prepare("UPDATE inventory_parts SET stock_level = stock_level - ? WHERE part_id = ?")->execute([$qty, $part_id]);
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Action logged successfully!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not take over the ticket. Please try again.']);
}
?>