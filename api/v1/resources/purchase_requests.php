<?php
/**
 * Purchase Requests Resource Handler
 *
 * IMPORTANT: There is no separate purchase_requests table.
 * A PR is a row in purchase_orders (typically po_number like PR-YYYYMMDD-####),
 * matching the web UI in _logi/purchase_requests.php.
 */

require_once __DIR__ . '/../../../inc/procurement.php';
require_once __DIR__ . '/../../../inc/notifications.php';

function handle_purchase_requests($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_purchase_requests');
            if ($id) {
                $stmt = $pdo->prepare(
                    "SELECT po.*, v.vendor_name, u.username AS created_by_name
                     FROM purchase_orders po
                     LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id
                     LEFT JOIN users u ON po.created_by = u.user_id
                     WHERE po.po_id = ?"
                );
                $stmt->execute([$id]);
                $pr = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pr) {
                    api_error('Purchase request not found', 404);
                }
                $itemsStmt = $pdo->prepare(
                    "SELECT pi.*, p.part_name, p.internal_code
                     FROM po_items pi
                     LEFT JOIN inventory_parts p ON pi.part_id = p.part_id
                     WHERE pi.po_id = ?"
                );
                $itemsStmt->execute([$id]);
                $pr['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                api_response(true, $pr);
            }

            list($page, $per_page, $offset) = get_pagination();
            $status = $_GET['status'] ?? null;
            $sql = "SELECT po.*, v.vendor_name, u.username AS created_by_name
                    FROM purchase_orders po
                    LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id
                    LEFT JOIN users u ON po.created_by = u.user_id
                    WHERE 1=1";
            $params = [];
            if ($status) {
                $sql .= " AND po.status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY po.po_id DESC LIMIT ? OFFSET ?";
            $params[] = $per_page;
            $params[] = $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $meta = build_meta($page, $per_page, count($items));
            api_response(true, $items, '', 200, $meta);
            break;

        case 'POST':
            // Create PR the same way as the web UI: vendor + line items + procurement route.
            require_api_perm('create_purchase_requests');
            $vendor_id = (int)($input['vendor_id'] ?? 0);
            $dept_id = !empty($input['dept_id']) ? (int)$input['dept_id'] : null;
            $lines = $input['items'] ?? $input['lines'] ?? [];
            if ($vendor_id <= 0) {
                api_error('vendor_id is required');
            }
            if (!is_array($lines) || count($lines) === 0) {
                api_error('items[] with part_id and qty is required');
            }

            $total_amount = 0.0;
            $final_items = [];
            foreach ($lines as $line) {
                $pid = (int)($line['part_id'] ?? 0);
                $qty = (int)($line['qty'] ?? $line['quantity'] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    continue;
                }
                $stmt = $pdo->prepare(
                    "SELECT part_id, cost_per_unit, part_name FROM inventory_parts WHERE part_id = ?"
                );
                $stmt->execute([$pid]);
                $part = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$part) {
                    continue;
                }
                $unit_price = (float)$part['cost_per_unit'];
                $total_amount += $unit_price * $qty;
                $final_items[] = [
                    'part_id' => $part['part_id'],
                    'qty' => $qty,
                    'unit_price' => $unit_price,
                ];
            }
            if (count($final_items) === 0) {
                api_error('No valid line items');
            }

            $po_number = 'PR-' . date('Ymd') . '-' . random_int(1000, 9999);
            $route = wcc_procurement_route($pdo, (float)$total_amount);
            $status = $route['status'];
            $approval_level = $route['approval_level'];
            $auto_approved = $route['auto_approved'];
            $auto_reason = $route['reason'];
            $created_by = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare(
                "INSERT INTO purchase_orders (po_number, vendor_id, dept_id, created_by, total_amount, status, approval_level)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $po_number, $vendor_id, $dept_id, $created_by, $total_amount, $status, $approval_level,
            ]);
            $po_id = (int)$pdo->lastInsertId();

            $stmt_item = $pdo->prepare(
                "INSERT INTO po_items (po_id, part_id, ordered_qty, unit_price) VALUES (?, ?, ?, ?)"
            );
            foreach ($final_items as $item) {
                $stmt_item->execute([$po_id, $item['part_id'], $item['qty'], $item['unit_price']]);
            }

            $stmt_log = $pdo->prepare(
                "INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt_log->execute([
                $po_id, 'PR Submitted', 'Draft', ($auto_approved ? 'Pending Approval' : $status), null, $created_by,
            ]);
            if ($auto_approved) {
                $stmt_log->execute([
                    $po_id, 'Auto-Approved', 'Pending Approval', 'Issued', $auto_reason, $created_by,
                ]);
                wcc_notify_perm(
                    'fulfill_purchase_orders',
                    'po_awaiting',
                    'PO ' . $po_number . ' is approved and awaiting fulfilment ($' . number_format($total_amount, 2) . ').',
                    '/_logi/purchase_orders.php',
                    'info',
                    $created_by !== null ? (int)$created_by : null
                );
            } else {
                wcc_notify_perm(
                    'approve_purchase_orders',
                    'pr_pending',
                    'PR ' . $po_number . ' needs cost approval ($' . number_format($total_amount, 2) . ').',
                    '/_logi/purchase_orders.php',
                    'warning',
                    $created_by !== null ? (int)$created_by : null
                );
            }

            api_response(true, [
                'po_id' => $po_id,
                'po_number' => $po_number,
                'status' => $status,
                'approval_level' => $approval_level,
                'total_amount' => $total_amount,
                'auto_approved' => $auto_approved,
            ], 'Purchase request created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            // Cost approval / cancel only — not logistics fulfilment.
            require_api_perm('approve_purchase_orders');
            if (!$id) {
                api_error('PR / PO ID required');
            }
            $st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
            $st->execute([$id]);
            $current = $st->fetchColumn();
            if ($current === false) {
                api_error('Purchase request not found', 404);
            }

            $new_status = $input['status'] ?? null;
            if ($new_status === null) {
                api_error('status is required for update (Issued or Cancelled)');
            }
            $allowed = ['Issued', 'Cancelled'];
            if (!in_array($new_status, $allowed, true)) {
                api_error('Invalid status for PR approval path. Use Issued or Cancelled (fulfilment uses /purchase-orders).');
            }
            if ($new_status === 'Issued' && $current !== 'Pending Approval' && $current !== 'Draft') {
                api_error('Only Pending Approval / Draft PRs can be cost-approved to Issued');
            }

            $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?")->execute([$new_status, $id]);
            $pdo->prepare(
                "INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by)
                 VALUES (?, 'Status Update', ?, ?, ?, ?)"
            )->execute([
                $id, $current, $new_status, $input['note'] ?? null, $_SESSION['user_id'] ?? null,
            ]);
            if ($new_status === 'Issued') {
                wcc_notify_perm(
                    'fulfill_purchase_orders',
                    'po_awaiting',
                    'PO #' . $id . ' approved and awaiting fulfilment.',
                    '/_logi/purchase_orders.php',
                    'info',
                    isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null
                );
            }
            api_response(true, null, 'Purchase request updated');
            break;

        case 'DELETE':
            require_api_perm('approve_purchase_orders');
            if (!$id) {
                api_error('PR / PO ID required');
            }
            $st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
            $st->execute([$id]);
            $current = $st->fetchColumn();
            if ($current === false) {
                api_error('Purchase request not found', 404);
            }
            if (!in_array($current, ['Draft', 'Cancelled', 'Pending Approval'], true)) {
                api_error('Only Draft, Pending Approval, or Cancelled PRs may be deleted');
            }
            $pdo->prepare("DELETE FROM po_items WHERE po_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM po_status_logs WHERE po_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM purchase_orders WHERE po_id = ?")->execute([$id]);
            api_response(true, null, 'Purchase request deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
