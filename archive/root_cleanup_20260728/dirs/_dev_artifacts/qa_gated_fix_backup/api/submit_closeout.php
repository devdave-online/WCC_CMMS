<?php
header('Content-Type: application/json');

// Enterprise centralized DB (Phase 1 complete)
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/audit.php';
$pdo = get_wcc_db_connection();

require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap

try {
    // Evil Maid Protection: Reject unauthenticated requests
    if (!isset($_SESSION['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid Session']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['ticket_id'])) { 
        echo json_encode(['status' => 'error', 'message' => 'Invalid data or missing ticket ID']); 
        exit; 
    }

    $locked_supervisor = $_SESSION['username'];

    // Upgraded Query: Updates status AND saves the supervisor who signed off
    $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'CLOSED', closed_by = ? WHERE ticket_id = ?");
    $stmtUpdate->execute([$locked_supervisor, $data['ticket_id']]);

    // Phase 5 audit logging
    wcc_audit_log(
        'ticket.close',
        'active_tickets',
        $data['ticket_id'],
        ['status' => 'OPEN/ESCALATED'],
        ['status' => 'CLOSED', 'closed_by' => $locked_supervisor],
        'Supervisor sign-off'
    );

    echo json_encode(['status' => 'success', 'message' => 'Closed successfully!']);
} catch (PDOException $e) {
    // 🔥 ZERO-DOWNTIME LEGACY FALLBACK (Phase 5 migration era) 🔥
    // Primary path: Run `php migrations/migrate.php --apply` (applies 0002).
    // This fallback is kept only for backward compatibility on old/unmigrated installs.
    // After a successful migration run, this code path should never be needed.
    // Consider removing this block in a future cleanup once all deployments are migrated.
    // See: migrations/README.md and CMMS_QA_AND_FUTURE_PLAN.md (Phase 5)
    if (strpos($e->getMessage(), "Unknown column 'closed_by'") !== false) {
        try {
            $pdo->exec("ALTER TABLE active_tickets ADD COLUMN closed_by VARCHAR(255) DEFAULT 'Unknown'");
            $stmtUpdate = $pdo->prepare("UPDATE active_tickets SET status = 'CLOSED', closed_by = ? WHERE ticket_id = ?");
            $stmtUpdate->execute([$locked_supervisor, $data['ticket_id']]);

            wcc_audit_log(
                'ticket.close',
                'active_tickets',
                $data['ticket_id'],
                null,
                ['status' => 'CLOSED', 'closed_by' => $locked_supervisor],
                'Legacy fallback - column auto-created'
            );

            echo json_encode(['status' => 'success', 'message' => 'Closed successfully! (Database auto-updated)']);
        } catch (PDOException $e2) {
            echo json_encode(['status' => 'error', 'message' => 'Auto-DB Upgrade Failed: ' . $e2->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Could not close out the ticket.']);
    }
}
?>