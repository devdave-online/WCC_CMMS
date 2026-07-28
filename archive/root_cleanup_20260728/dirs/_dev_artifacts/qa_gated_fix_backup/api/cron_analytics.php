<?php
// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // 1. Total Breakdowns (Only count completed/CLOSED incidents)
    $stmt = $pdo->query("SELECT COUNT(*) FROM active_tickets WHERE status = 'CLOSED'");
    $total_breakdowns = (float)$stmt->fetchColumn();

    // 2. Total Downtime (in Hours)
    // ticket_actions has action_start and action_end
    $stmt = $pdo->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, action_start, action_end)) FROM ticket_actions WHERE action_start IS NOT NULL AND action_end IS NOT NULL");
    $total_downtime_minutes = (float)$stmt->fetchColumn();
    $total_downtime_hours = $total_downtime_minutes / 60;

    // 3. MTTR (Mean Time To Repair) in Hours
    $mttr = $total_breakdowns > 0 ? ($total_downtime_hours / $total_breakdowns) : 0;

    // 4. MTTD (Mean Time To Detect) in Hours
    // Diff between ticket report_time (assuming report_date + report_time) and action_start
    $stmt = $pdo->query("
        SELECT SUM(TIMESTAMPDIFF(MINUTE, CONCAT(a.report_date, ' ', a.report_time), t.action_start)) 
        FROM active_tickets a 
        JOIN ticket_actions t ON a.ticket_id = t.ticket_id 
        WHERE t.action_start IS NOT NULL
    ");
    $total_detect_minutes = (float)$stmt->fetchColumn();
    $mttd = $total_breakdowns > 0 ? (($total_detect_minutes / 60) / $total_breakdowns) : 0;

    // 5. Total Equipment Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM equipment");
    $total_equip = (float)$stmt->fetchColumn();

    // 6. MTBF (Mean Time Between Failures) in Hours
    // Assume 24/7 operation over the last 30 days for simplicity
    $total_available_hours = $total_equip * 24 * 30; 
    $total_uptime_hours = $total_available_hours - $total_downtime_hours;
    $mtbf = $total_breakdowns > 0 ? ($total_uptime_hours / $total_breakdowns) : $total_available_hours;

    // 7. OEE (Availability approximation)
    // OEE = Availability * Performance * Quality. We only have Availability.
    $oee = $total_available_hours > 0 ? (($total_uptime_hours / $total_available_hours) * 100) : 100;

    // 8. Total Parts Consumed
    // We can sum ordered_qty from po_items if needed, or parts consumption. Since we just added true parts consumption, let's just count from `inventory_parts` stock reduction, but we don't have a history table for parts. 
    // Let's use PO Orders as a metric.
    $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders");
    $total_orders = (float)$stmt->fetchColumn();

    $metrics = [
        'Total Breakdowns' => $total_breakdowns,
        'Total Downtime (Hrs)' => $total_downtime_hours,
        'MTTR (Hrs)' => $mttr,
        'MTTD (Hrs)' => $mttd,
        'MTBF (Hrs)' => $mtbf,
        'Total PO Orders' => $total_orders,
        'OEE (%)' => $oee
    ];

    $upsert = $pdo->prepare("INSERT INTO analytics_logs (metric_name, metric_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)");
    
    foreach($metrics as $name => $val) {
        $upsert->execute([$name, $val]);
    }

    echo "Analytics updated successfully.\n";

} catch (PDOException $e) {
    echo "Error during analytics run. Check logs.\n";
}
?>
