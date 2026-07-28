<?php
// Set the content type to JSON so the browser knows how to read this
header('Content-Type: application/json');

// Team directory for ticket "announced by" pickers — any logged-in user who can
// open the registration flows needs this; keep login gate (not a sensitive ledger).
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_login();

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    // Get the requested role from the URL (e.g., get_team.php?role=technical)
    $role = isset($_GET['role']) ? $_GET['role'] : 'technical';

    // Fetch names where role matches and user is active
    $stmt = $pdo->prepare("SELECT full_name FROM team_directory WHERE role_type = ? AND is_active = 1 ORDER BY full_name ASC");
    $stmt->execute([$role]);
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send back the data
    echo json_encode(['status' => 'success', 'data' => $team]);

} catch (PDOException $e) {
    // Log the detail, return a generic message (never leak SQL/internal detail)
    error_log('[WCC get_team] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not load team directory.']);
}
