<?php
// Access control: CLI (scheduler), OR included by an already-authenticated page,
// OR direct web access by a user who can view analytics. Blocks anonymous web hits.
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    require_once __DIR__ . '/../rbac.php';
    if (!can('view_statistics')) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Forbidden.';
        exit;
    }
}

// Diagnostics snapshot — a rolling 30-day view computed by the ONE KPI engine, so
// this page can never disagree with the dashboard again. (The previous version mixed
// all-time counts with a fixed 30-day 24/7 window, which produced impossible numbers.)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/shift_calendar.php';
require_once __DIR__ . '/../inc/kpi.php';
$pdo = get_wcc_db_connection();

try {
    $settings = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $holidays = json_decode($settings['plant_holidays'] ?? '[]', true) ?? [];
    $cal = new ShiftCalendar('06:00:00', '22:00:00', [1, 2, 3, 4, 5], $holidays);

    $start = date('Y-m-d', strtotime('-30 days'));
    $end   = date('Y-m-d');
    $s = wcc_kpi_window_summary($pdo, $start, $end, $cal, 16, [1, 2, 3, 4, 5]);

    $total_orders = (float)$pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();

    $metrics = [
        'Total Breakdowns'     => $s['failures'],
        'Total Downtime (Hrs)' => round($s['downtime'] / 60, 2),
        'MTTR (Hrs)'           => round($s['mttr'] / 60, 2),
        'MTTA (Hrs)'           => round($s['mtta'] / 60, 2),
        'MTBF (Hrs)'           => $s['mtbf'] === null ? 0 : round($s['mtbf'] / 60, 2),
        'Availability (%)'     => $s['availability_fleet'],
        'Total PO Orders'      => $total_orders,
    ];

    $upsert = $pdo->prepare("INSERT INTO analytics_logs (metric_name, metric_value) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value)");
    foreach ($metrics as $name => $val) {
        $upsert->execute([$name, $val]);
    }

    // Retire the metrics the old engine wrote under different names, so stale values
    // don't linger in the ledger.
    $pdo->prepare("DELETE FROM analytics_logs WHERE metric_name IN ('OEE (%)', 'MTTD (Hrs)')")->execute();

    echo "Analytics updated successfully.\n";

} catch (PDOException $e) {
    echo "Error during analytics run. Check logs.\n";
}
