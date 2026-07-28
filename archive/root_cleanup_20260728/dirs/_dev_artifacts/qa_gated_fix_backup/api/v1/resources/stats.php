<?php
/**
 * Stats / Analytics Resource for AI and external consumers
 */

function handle_stats($method, $id, $input) {
    global $pdo;

    if ($method !== 'GET') {
        api_error('Only GET is supported for stats', 405);
    }

    require_api_perm('view_statistics');

    $type = $_GET['type'] ?? 'overview';

    switch ($type) {
        case 'overview':
            $stats = [];

            // Tickets
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM active_tickets GROUP BY status");
            $stats['tickets_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Work Orders
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM work_orders GROUP BY status");
            $stats['work_orders_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Equipment
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM equipment");
            $stats['equipment'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Open POs
            $stmt = $pdo->query("SELECT COUNT(*) as open_pos FROM purchase_orders WHERE status NOT IN ('Completed', 'Cancelled')");
            $stats['purchase_orders'] = $stmt->fetch(PDO::FETCH_ASSOC);

            api_response(true, $stats);
            break;

        case 'kpi':
            // Simple KPIs - can be expanded
            $kpis = [];
            $stmt = $pdo->query("SELECT COUNT(*) as open_tickets FROM active_tickets WHERE status IN ('OPEN', 'PENDING')");
            $kpis['open_tickets'] = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) as overdue_wos FROM work_orders WHERE status IN ('Scheduled', 'In Progress') AND scheduled_date < CURDATE()");
            $kpis['overdue_work_orders'] = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_open_hours FROM active_tickets WHERE status IN ('OPEN', 'PENDING')");
            $kpis['avg_ticket_open_hours'] = round($stmt->fetchColumn() ?? 0, 1);

            api_response(true, $kpis);
            break;

        default:
            api_error('Unknown stats type. Use ?type=overview or ?type=kpi', 400);
    }
}
