<?php
require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['ticket_id']) || empty(trim($data['comment_text']))) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket ID and Comment Text are required.']);
        exit;
    }

    $tech_name = $_SESSION['username'] ?? 'Unknown User';
    
    // Optional: Only allow comments on active tickets (OPEN, PENDING, ESCALATED, HOLD)
    $stmtCheck = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
    $stmtCheck->execute([$data['ticket_id']]);
    $ticket = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found.']);
        exit;
    }
    
    if ($ticket['status'] === 'CLOSED') {
        echo json_encode(['status' => 'error', 'message' => 'Cannot add comments to a closed ticket.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_name, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['ticket_id'],
        $tech_name,
        trim($data['comment_text'])
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Comment added successfully!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
