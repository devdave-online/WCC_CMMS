<?php
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

// 1. Create a config mapping
$pdo->exec("INSERT IGNORE INTO skill_automation_config (equipment_category, skill_name, icon) VALUES ('Robotics', 'Robotics Tech', '🤖')");
$pdo->exec("INSERT IGNORE INTO skill_automation_config (equipment_category, skill_name, icon) VALUES ('Conveyors', 'Conveyor Master', '🎢')");

// 2. We need some equipment with these categories
$pdo->exec("INSERT IGNORE INTO equipment (equip_name, category, is_active) VALUES ('Robot Arm A', 'Robotics', 1)");
$equip1 = $pdo->lastInsertId();
if (!$equip1) $equip1 = $pdo->query("SELECT equip_id FROM equipment WHERE category='Robotics' LIMIT 1")->fetchColumn();

$pdo->exec("INSERT IGNORE INTO equipment (equip_name, category, is_active) VALUES ('Main Conveyor', 'Conveyors', 1)");
$equip2 = $pdo->lastInsertId();
if (!$equip2) $equip2 = $pdo->query("SELECT equip_id FROM equipment WHERE category='Conveyors' LIMIT 1")->fetchColumn();

// 3. We need some tickets
$pdo->exec("INSERT IGNORE INTO active_tickets (ticket_id, equip_id, status) VALUES ('TK-MOCK-1', $equip1, 'CLOSED')");
$pdo->exec("INSERT IGNORE INTO active_tickets (ticket_id, equip_id, status) VALUES ('TK-MOCK-2', $equip2, 'CLOSED')");

// 4. We need some users to assign time to. Let's fetch some users
$users = $pdo->query("SELECT * FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    $u1 = $users[0]['full_name'] ?: $users[0]['username'];
    $u2 = isset($users[1]) ? ($users[1]['full_name'] ?: $users[1]['username']) : $u1;
    $u3 = isset($users[2]) ? ($users[2]['full_name'] ?: $users[2]['username']) : $u1;
    
    // Novice Robotics (12h)
    $pdo->exec("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end) VALUES ('TK-MOCK-1', '{$u1}', '2026-07-15 06:00:00', '2026-07-15 18:00:00')");

    // Expert Conveyors (150h)
    $pdo->exec("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end) VALUES ('TK-MOCK-2', '{$u2}', '2026-07-10 08:00:00', '2026-07-16 14:00:00')");

    // Master Robotics (250h)
    $pdo->exec("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end) VALUES ('TK-MOCK-1', '{$u3}', '2026-06-01 08:00:00', '2026-06-11 18:00:00')");
}

echo "Mock data seeded!";
