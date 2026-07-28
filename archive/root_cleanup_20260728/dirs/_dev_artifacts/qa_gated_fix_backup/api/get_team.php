<?php
// Set the content type to JSON so the browser knows how to read this
header('Content-Type: application/json');

// Database connection details
$host = 'localhost';
$db   = 'workshop_db';
$user = 'root';
$pass = ''; // Leave blank if you have no password set in XAMPP

try {
    // Create the connection
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the requested role from the URL (e.g., get_team.php?role=technical)
    $role = isset($_GET['role']) ? $_GET['role'] : 'technical';

    // Fetch names where role matches and user is active
    $stmt = $pdo->prepare("SELECT full_name FROM team_directory WHERE role_type = ? AND is_active = 1 ORDER BY full_name ASC");
    $stmt->execute([$role]);
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send back the data
    echo json_encode(['status' => 'success', 'data' => $team]);

} catch (PDOException $e) {
    // If something goes wrong, tell the browser there was an error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>