<?php
header('Content-Type: application/json');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Modified to count tickets in the last 48 hours for each machine
    $stmt = $pdo->query("
        SELECT e.*, w.name as plant_name, l.name as line_name,
        (SELECT COUNT(*) FROM active_tickets t WHERE t.equip_id = e.equip_id AND t.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)) as recent_count
        FROM equipment e 
        LEFT JOIN workshops w ON e.workshop_id = w.workshop_id 
        LEFT JOIN production_lines l ON e.line_id = l.line_id
        ORDER BY e.equip_id ASC
    ");
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $equipment]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>