<?php
require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();
$user_id = (int)$_SESSION['user_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'add') {
        $skill_name = trim($data['skill_name'] ?? '');
        $expiry_date = !empty($data['expiry_date']) ? $data['expiry_date'] : null;

        if (empty($skill_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Skill name is required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_name, expiry_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $skill_name, $expiry_date]);
        
        echo json_encode(['status' => 'success', 'message' => 'Skill added successfully', 'id' => $pdo->lastInsertId()]);
        
    } elseif ($action === 'delete') {
        $skill_id = (int)($data['id'] ?? 0);
        
        // Ensure they only delete their own skill
        $stmt = $pdo->prepare("DELETE FROM user_skills WHERE id = ? AND user_id = ?");
        $stmt->execute([$skill_id, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Skill removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
