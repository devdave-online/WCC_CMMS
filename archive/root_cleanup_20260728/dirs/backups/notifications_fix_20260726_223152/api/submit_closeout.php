<?php
header('Content-Type: application/json');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
require_once __DIR__ . '/../inc/notifications.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../inc/session.php'; // hardened session bootstrap

try {
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid Session']);
        exit;
    }
    require_once __DIR__ . '/../inc/api_guard.php';
    api_guard_perm('closeout_tickets');
    require_once __DIR__ . '/../inc/csrf.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }
    wcc_csrf_require_json($data['csrf'] ?? null);

    if (!isset($data['ticket_id'])) { 
        echo json_encode(['status' => 'error', 'message' => 'Invalid data or missing ticket ID']); 
        exit; 
    }

    $locked_supervisor = $_SESSION['username'];
    $ticketId = (string)$data['ticket_id'];

    // Status + who closed + when closed (history sorts by closed_at)
    try {
        $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'CLOSED', closed_by = ?, closed_at = NOW() WHERE ticket_id = ?");
        $stmtUpdate->execute([$locked_supervisor, $ticketId]);
    } catch (PDOException $eCol) {
        // Self-heal missing columns on half-migrated installs
        if (str_contains($eCol->getMessage(), 'closed_at')) {
            try { $pdo->exec("ALTER TABLE active_tickets ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL"); } catch (Throwable $eIgn) {}
        }
        if (str_contains($eCol->getMessage(), 'closed_by')) {
            try { $pdo->exec("ALTER TABLE active_tickets ADD COLUMN closed_by VARCHAR(255) DEFAULT 'Unknown'"); } catch (Throwable $eIgn) {}
        }
        $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'CLOSED', closed_by = ?, closed_at = NOW() WHERE ticket_id = ?");
        $stmtUpdate->execute([$locked_supervisor, $ticketId]);
    }

    if ($stmtUpdate->rowCount() < 1) {
        // Still succeed if already closed; verify exists
        $chk = $pdo->prepare("SELECT status FROM active_tickets WHERE ticket_id = ?");
        $chk->execute([$ticketId]);
        $st = $chk->fetchColumn();
        if ($st === false) {
            echo json_encode(['status' => 'error', 'message' => 'Ticket not found.']);
            exit;
        }
    }

    wcc_audit_log(
        'ticket.close',
        'active_tickets',
        $ticketId,
        ['status' => 'OPEN/ESCALATED/PENDING'],
        ['status' => 'CLOSED', 'closed_by' => $locked_supervisor],
        'Supervisor sign-off'
    );

    // History viewers + analytics roles (not only view_statistics)
    $msg = 'Event closed: ' . $ticketId . ' (signed off by ' . $locked_supervisor . ')';
    $link = '/_rpt/history.php';
    $actor = (int)($_SESSION['user_id'] ?? 0);
    wcc_notify_perm('view_history', 'ticket_closed', $msg, $link, 'success', $actor);
    wcc_notify_perm('view_statistics', 'ticket_closed', $msg, $link, 'success', $actor);

    echo json_encode(['status' => 'success', 'message' => 'Closed successfully!']);
} catch (PDOException $e) {
    error_log('[WCC submit_closeout] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Could not close out the ticket.']);
}
?>