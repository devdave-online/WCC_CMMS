<?php
/**
 * WCC CMMS — Procurement routing helper.
 *
 * Single source of truth for how a purchase request is routed on submit,
 * honouring the app_settings toggle + auto-approve limit. Used by both the
 * manual PR flow (_logi/purchase_requests.php) and the auto-reorder engine
 * (inc/reorder.php) so they behave identically.
 */

require_once __DIR__ . '/db.php';

/**
 * Decide the routing for a PR of a given total.
 * Returns ['status', 'approval_level', 'auto_approved' (bool), 'reason' (string|null)].
 *   - workflow off OR total within a positive limit → Issued / Auto-Approved
 *   - otherwise → Pending Approval / Requires Admin
 */
function wcc_procurement_route(PDO $pdo, float $total): array
{
    $wf_on = true; $auto_limit = 0.0;
    try {
        $s = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('procurement_workflow_enabled','po_auto_approve_limit')")
                 ->fetchAll(PDO::FETCH_KEY_PAIR);
        $wf_on = (($s['procurement_workflow_enabled'] ?? '1') === '1');
        $auto_limit = (float)($s['po_auto_approve_limit'] ?? 0);
    } catch (Throwable $e) { /* settings missing → default: workflow on, no limit */ }

    $under_limit   = ($auto_limit > 0 && $total <= $auto_limit);
    $auto_approved = (!$wf_on) || $under_limit;

    if ($auto_approved) {
        return [
            'status'         => 'Issued',
            'approval_level' => 'Auto-Approved',
            'auto_approved'  => true,
            'reason'         => !$wf_on
                ? 'Auto-approved — approval workflow is disabled.'
                : 'Auto-approved — total $' . number_format($total, 2) . ' is within the $' . number_format($auto_limit, 2) . ' limit.',
        ];
    }
    return [
        'status'         => 'Pending Approval',
        'approval_level' => 'Requires Admin',
        'auto_approved'  => false,
        'reason'         => null,
    ];
}
