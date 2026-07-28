<?php
header('Content-Type: application/json');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap

try {
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid Session']);
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

    if (!isset($data['ticket_id'])) { echo json_encode(['status' => 'error', 'message' => 'Invalid data']); exit; }

    // Server-side identity hard-lock
    require_once __DIR__ . '/../inc/techident.php';
    // Stamp the display name (falls back to username) so history and the
    // proficiency board read as people, not logins.
    $locked_tech_name = wcc_tech_name();

    $stmtAction = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, escalated_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtAction->execute([
        $data['ticket_id'], $locked_tech_name, $data['action_start'], $data['action_end'], 
        $data['fault_type'], $data['root_cause'], $data['action_taken'], $data['parts_used'], $data['escalated_to']
    ]);

    $new_status = ($data['action_type'] === 'escalate') ? 'ESCALATED' : 'PENDING';
    $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = ? WHERE ticket_id = ?");
    $stmtUpdate->execute([$new_status, $data['ticket_id']]);

    // Handle inventory consumption. Each consumed part: validate it exists, consume
    // only what's actually on hand (stock floored at 0), and record the real amount
    // in the inventory_ledger so it's trailable in the parts-consumption history —
    // mirroring how work-order consumption is logged.
    if (isset($data['parts_consumed_data']) && is_array($data['parts_consumed_data'])) {
        require_once __DIR__ . '/../inc/reorder.php';
        $actor  = (int)($_SESSION['user_id'] ?? 0);
        $ticket = $data['ticket_id'];
        $lookup = $pdo->prepare("SELECT stock_level FROM inventory_parts WHERE part_id = ?");
        $decr   = $pdo->prepare("UPDATE inventory_parts SET stock_level = GREATEST(stock_level - ?, 0) WHERE part_id = ?");
        $ledger = $pdo->prepare("INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id, actor_user_id) VALUES (?, ?, 'ticket_consume', 'active_tickets', ?, ?)");
        $reorder_check = [];
        foreach ($data['parts_consumed_data'] as $part) {
            $qty = (int)$part['qty'];
            $part_id = (int)$part['part_id'];
            if ($qty <= 0 || $part_id <= 0) continue;

            $lookup->execute([$part_id]);
            $onHand = $lookup->fetchColumn();
            if ($onHand === false) continue; // unknown part — skip (no phantom consumption)

            $actual = min($qty, (int)$onHand); // can't consume more than exists
            if ($actual <= 0) continue;

            $decr->execute([$actual, $part_id]);
            $ledger->execute([$part_id, -$actual, $ticket, $actor]); // trailable stock movement
            $reorder_check[$part_id] = true;
        }
        // Event-driven auto-reorder for anything now at/below minimum.
        foreach (array_keys($reorder_check) as $rpid) {
            wcc_check_and_reorder($pdo, (int)$rpid, $actor);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Action logged successfully!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not take over the ticket. Please try again.']);
}
?>