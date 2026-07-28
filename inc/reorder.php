<?php
/**
 * WCC CMMS — Event-driven auto-reorder.
 *
 * Called right after a part's stock is decremented. If the part is flagged
 * auto_reorder, is active, and has dropped to/below its minimum threshold —
 * and no open order already covers it — a reorder PR is created through the
 * normal procurement workflow (wcc_procurement_route) and the right people
 * are notified. Never throws into the caller (try/catch, logs only).
 *
 * Returns the new PO number if an order was placed, else null.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/procurement.php';
require_once __DIR__ . '/notifications.php';

function wcc_check_and_reorder(PDO $pdo, int $part_id, ?int $actor_uid = null): ?string
{
    try {
        $st = $pdo->prepare("SELECT part_id, part_name, internal_code, stock_level, minimum_threshold,
                                    maximum_stock, moq, primary_vendor_id, cost_per_unit,
                                    auto_reorder, lifecycle_status
                               FROM inventory_parts WHERE part_id = ?");
        $st->execute([$part_id]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p) return null;

        // Guards: flag on, active, and at/below threshold.
        if ((int)$p['auto_reorder'] !== 1) return null;
        if (($p['lifecycle_status'] ?? 'Active') !== 'Active') return null;
        if ((int)$p['stock_level'] > (int)$p['minimum_threshold']) return null;

        // Dedupe: an open order already covers this part (incl. in-transit).
        $dup = $pdo->prepare("SELECT COUNT(*) FROM po_items i JOIN purchase_orders p ON i.po_id = p.po_id
                               WHERE i.part_id = ? AND p.status IN
                               ('Draft','Pending Approval','Issued','Shipped','In Transit','Partially Received')");
        $dup->execute([$part_id]);
        if ((int)$dup->fetchColumn() > 0) return null;

        $part_label = $p['part_name'] . ' (' . ($p['internal_code'] ?: ('#' . $part_id)) . ')';

        // No vendor → can't auto-order; tell inventory so a human can act.
        if (empty($p['primary_vendor_id'])) {
            wcc_notify_perm('manage_inventory', 'low_stock',
                'Low stock: ' . $part_label . ' is at ' . (int)$p['stock_level'] . ' (min ' . (int)$p['minimum_threshold'] . ') but has no vendor set — manual reorder needed.',
                '/_logi/inventory.php', 'warning');
            return null;
        }

        // Reorder-to-max, MOQ floor.
        $qty = ((int)$p['maximum_stock'] > (int)$p['stock_level'])
             ? (int)$p['maximum_stock'] - (int)$p['stock_level']
             : max(1, (int)$p['moq']);
        $unit_price = (float)$p['cost_per_unit'];
        $total = $qty * $unit_price;

        // Department for budget tracking: explicit app_setting override if set,
        // otherwise fall back to the single/first department (the Budget) so the
        // spend consumes a budget on receipt. NULL only if no departments exist.
        $dept_id = null;
        try {
            $d = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_reorder_dept_id'")->fetchColumn();
            if ($d !== false && $d !== '' && (int)$d > 0) {
                $dept_id = (int)$d;
            } else {
                $first = $pdo->query("SELECT dept_id FROM departments ORDER BY dept_id ASC LIMIT 1")->fetchColumn();
                if ($first !== false) $dept_id = (int)$first;
            }
        } catch (Throwable $e) {}

        $route     = wcc_procurement_route($pdo, $total);
        $po_number = "PR-AUTO-" . date("Ymd") . "-" . rand(1000, 9999);

        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, dept_id, created_by, total_amount, status, approval_level) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$po_number, (int)$p['primary_vendor_id'], $dept_id, $actor_uid, $total, $route['status'], $route['approval_level']]);
        $po_id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO po_items (po_id, part_id, ordered_qty, unit_price) VALUES (?, ?, ?, ?)")
            ->execute([$po_id, $part_id, $qty, $unit_price]);

        $log = $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
        $log->execute([$po_id, 'Auto-Reorder Generated', 'Draft', ($route['auto_approved'] ? 'Pending Approval' : $route['status']),
                       'Stock ' . (int)$p['stock_level'] . ' ≤ minimum ' . (int)$p['minimum_threshold'] . ' — auto-reorder ' . $qty . ' × ' . $part_label, $actor_uid]);
        if ($route['auto_approved']) {
            $log->execute([$po_id, 'Auto-Approved', 'Pending Approval', 'Issued', $route['reason'], $actor_uid]);
        }
        $pdo->commit();

        // Notify inventory + the correct next-step role.
        wcc_notify_perm('manage_inventory', 'low_stock',
            'Auto-reorder ' . $po_number . ' placed: ' . $qty . ' × ' . $part_label . ' (stock ' . (int)$p['stock_level'] . ' ≤ min ' . (int)$p['minimum_threshold'] . ').',
            '/_logi/purchase_orders.php', 'warning');
        if ($route['auto_approved']) {
            wcc_notify_perm('fulfill_purchase_orders', 'po_awaiting', 'Auto-reorder ' . $po_number . ' awaiting fulfilment.', '/_logi/purchase_orders.php', 'info');
        } else {
            wcc_notify_perm('approve_purchase_orders', 'pr_pending', 'Auto-reorder ' . $po_number . ' needs cost approval ($' . number_format($total, 2) . ').', '/_logi/purchase_orders.php', 'warning');
        }

        return $po_number;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[WCC REORDER] part ' . $part_id . ': ' . $e->getMessage());
        return null;
    }
}
