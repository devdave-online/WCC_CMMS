<?php
require_once __DIR__ . '/inc/db.php';
$pdo = get_wcc_db_connection();

echo "Seeding historical tickets...\n";

for ($i = 14; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    
    // Insert 3 to 6 tickets per month
    $num_tickets = rand(3, 6);
    
    for ($t = 0; $t < $num_tickets; $t++) {
        $day = rand(1, 28);
        $report_date = date('Y-m-d', strtotime(date('Y-m-', strtotime($month_start)) . sprintf('%02d', $day)));
        $report_time = sprintf('%02d:%02d:00', rand(7, 16), rand(0, 59));
        
        $ticket_id = "TK-MOCK-" . date('ymd', strtotime($report_date)) . "-" . rand(1000, 9999);
        
        $stmt = $pdo->prepare("INSERT INTO active_tickets (ticket_id, fault_desc, equip_id, priority, report_date, report_time, status) VALUES (?, ?, ?, ?, ?, ?, 'CLOSED')");
        $stmt->execute([
            $ticket_id,
            "Auto generated mock issue",
            1, // Assuming equip_id 1 exists
            'Medium',
            $report_date,
            $report_time
        ]);
        
        // Add action
        $mttd_mins = rand(10, 100);
        $mttr_mins = rand(20, 180);
        
        $action_start = date('Y-m-d H:i:s', strtotime("$report_date $report_time + $mttd_mins minutes"));
        $action_end = date('Y-m-d H:i:s', strtotime($action_start) + ($mttr_mins * 60));
        
        $stmt = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, action_taken, action_start, action_end) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $ticket_id,
            "Fixed mock issue",
            $action_start,
            $action_end
        ]);
    }
}

echo "Done seeding!\n";
?>
