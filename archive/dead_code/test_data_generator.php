<?php
// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

// 1. Ensure Equipment
$pdo->exec("INSERT IGNORE INTO equipment (equip_name, category) VALUES ('CNC Machine Alpha', 'Production')");
$equip_id = $pdo->lastInsertId() ?: 1;

// 2. Ensure Parts
$pdo->exec("INSERT IGNORE INTO inventory_parts (part_name, internal_code, stock_level) VALUES ('Alpha Sensor', 'SEN-001', 50)");
$part1_id = $pdo->lastInsertId() ?: 1;

$pdo->exec("INSERT IGNORE INTO inventory_parts (part_name, internal_code, stock_level) VALUES ('Beta Valve', 'VAL-002', 20)");
$part2_id = $pdo->lastInsertId() ?: 2;

// 3. Create active_tickets and ticket_actions
$start_dates = ['2026-07-01', '2026-07-02', '2026-07-03', '2026-07-04', '2026-07-05'];

for ($i = 0; $i < 5; $i++) {
    $tid = 'TK-SIM-' . uniqid();
    $r_date = $start_dates[$i];
    $r_time = '08:00:00';
    
    // Create Ticket
    $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by, status) VALUES (?, ?, ?, ?, 'Operator', 'CLOSED')");
    $stmt->execute([$tid, $equip_id, $r_date, $r_time]);
    
    // Action Start is 2 hours after report time
    $a_start = $r_date . ' 10:00:00';
    // Action End is 4 hours after report time (so 2 hours downtime, 2 hours detect)
    $a_end = $r_date . ' 12:00:00';
    
    $stmtAct = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type, action_taken, parts_used) VALUES (?, 'Test Tech', ?, ?, 'Mechanical', 'Fixed it', 'Part Used')");
    $stmtAct->execute([$tid, $a_start, $a_end]);
    
    // Decrement stock directly (Simulating submit_takeover.php logic)
    $pdo->prepare("UPDATE inventory_parts SET stock_level = stock_level - 1 WHERE part_id = ?")->execute([$part1_id]);
}

echo "Inserted 5 test tickets successfully.\n";
?>
