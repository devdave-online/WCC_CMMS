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
    $ticket_id = $_GET['ticket_id'] ?? '';
    if (empty($ticket_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket ID required']);
        exit;
    }

    $stmtCmt = $pdo->prepare("SELECT * FROM ticket_comments WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmtCmt->execute([$ticket_id]);
    $comments = $stmtCmt->fetchAll(PDO::FETCH_ASSOC);

    $html = "";
    if (empty($comments)) {
        $html = '<div style="font-size: 0.9em; color: var(--text-secondary); font-style: italic;">No comments yet.</div>';
    } else {
        foreach($comments as $cmt) {
            $name = htmlspecialchars($cmt['user_name']);
            $date = htmlspecialchars(date('M d, H:i', strtotime($cmt['created_at'])));
            $text = nl2br(htmlspecialchars($cmt['comment_text']));
            $html .= '
            <div style="background: rgba(255,255,255,0.05); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #38bdf8;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.85em;">
                    <strong style="color: var(--text-primary);">' . $name . '</strong>
                    <span style="color: var(--text-secondary);">' . $date . '</span>
                </div>
                <div style="font-size: 0.95em; color: #e2e8f0;">
                    ' . $text . '
                </div>
            </div>';
        }
    }

    echo json_encode(['status' => 'success', 'html' => $html]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
