<?php
/**
 * Companion App — ticket comments (JSON).
 *
 * Complementary to legacy get_comments.php / add_comment.php which serve the web UI
 * (HTML fragments). This endpoint does not replace or modify those files.
 *
 * GET  ?ticket_id=TK-…  → { status, data: [ { comment_id, ticket_id, user_name, comment_text, created_at } ] }
 * POST JSON { ticket_id, comment_text } → { status, message }
 */
require_once __DIR__ . '/../../inc/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/api_guard.php';
$pdo = get_wcc_db_connection();

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        api_guard_perm('view_tickets');
        $ticket_id = $_GET['ticket_id'] ?? '';
        if ($ticket_id === '') {
            echo json_encode(['status' => 'error', 'message' => 'Ticket ID required']);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT comment_id, ticket_id, user_name, comment_text, created_at
             FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC"
        );
        $stmt->execute([$ticket_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        api_guard_perm('view_tickets');
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($data['ticket_id']) || empty(trim((string)($data['comment_text'] ?? '')))) {
            echo json_encode(['status' => 'error', 'message' => 'Ticket ID and Comment Text are required.']);
            exit;
        }
        $ticket_id = $data['ticket_id'];
        $check = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
        $check->execute([$ticket_id]);
        $ticket = $check->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) {
            echo json_encode(['status' => 'error', 'message' => 'Ticket not found.']);
            exit;
        }
        if (($ticket['status'] ?? '') === 'CLOSED') {
            echo json_encode(['status' => 'error', 'message' => 'Cannot add comments to a closed ticket.']);
            exit;
        }
        require_once __DIR__ . '/../../inc/techident.php';
        $tech_name = wcc_tech_name();
        $ins = $pdo->prepare(
            "INSERT INTO ticket_comments (ticket_id, user_name, comment_text) VALUES (?, ?, ?)"
        );
        $ins->execute([$ticket_id, $tech_name, trim($data['comment_text'])]);
        echo json_encode(['status' => 'success', 'message' => 'Comment added successfully!']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
