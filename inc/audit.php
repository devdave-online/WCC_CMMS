<?php
/**
 * WCC CMMS - Audit Logging Helper (Phase 5)
 *
 * Usage:
 *   require_once __DIR__ . '/audit.php';
 *   wcc_audit_log($action, $entity_type, $entity_id, $before = null, $after = null, $notes = null);
 *
 * Example:
 *   wcc_audit_log('ticket.close', 'active_tickets', $ticket_id, $old_ticket, $new_ticket, 'Supervisor: ' . $user);
 */

require_once __DIR__ . '/db.php';

function wcc_audit_log(string $action, string $entity_type, string $entity_id, $before = null, $after = null, ?string $notes = null): void
{
    try {
        $pdo = get_wcc_db_connection();

        $actor = $_SESSION['user_id'] ?? null;

        $beforeJson = $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null;
        $afterJson  = $after  !== null ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare("
            INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, before_json, after_json, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $actor,
            $action,
            $entity_type,
            $entity_id,
            $beforeJson,
            $afterJson,
            $notes
        ]);
    } catch (Throwable $e) {
        // Never let audit logging break the main flow.
        // In production this would go to error_log or a monitoring system.
        error_log('[WCC AUDIT] Failed to log action "' . $action . '": ' . $e->getMessage());
    }
}
