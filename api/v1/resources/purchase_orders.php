<?php
/**
 * Purchase Orders Resource Handler
 * Covers purchase_orders + po_items. Status transitions respect
 * approve_purchase_orders vs fulfill_purchase_orders (same as web UI).
 */

function handle_purchase_orders($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if (!can('view_purchase_requests') && !can('approve_purchase_orders') && !can('fulfill_purchase_orders')) {
                require_api_perm('view_purchase_requests');
            }
            if ($id) {
                $stmt = $pdo->prepare(
                    "SELECT po.*, v.vendor_name FROM purchase_orders po
                     LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id
                     WHERE po.po_id = ?"
                );
                $stmt->execute([$id]);
                $po = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$po) {
                    api_error('Purchase order not found', 404);
                }

                $itemsStmt = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ?");
                $itemsStmt->execute([$id]);
                $po['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                api_response(true, $po);
            }

            $status = $_GET['status'] ?? null;
            $vendor_id = $_GET['vendor_id'] ?? null;

            $sql = "SELECT po.*, v.vendor_name FROM purchase_orders po
                    LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id WHERE 1=1";
            $params = [];

            if ($status) {
                $sql .= " AND po.status = ?";
                $params[] = $status;
            }
            if ($vendor_id) {
                $sql .= " AND po.vendor_id = ?";
                $params[] = $vendor_id;
            }

            $sql .= " ORDER BY po.po_id DESC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            api_response(true, $items);
            break;

        case 'POST':
            // Prefer /purchase-requests for PR-shaped create; this path creates a Draft shell.
            require_api_perm('create_purchase_requests');
            if (empty($input['vendor_id'])) {
                api_error('vendor_id is required');
            }

            $po_number = 'PO-' . date('YmdHis');
            $stmt = $pdo->prepare(
                "INSERT INTO purchase_orders (po_number, vendor_id, created_by, dept_id, status, total_amount, notes)
                 VALUES (?, ?, ?, ?, 'Draft', ?, ?)"
            );
            $stmt->execute([
                $po_number,
                $input['vendor_id'],
                $_SESSION['user_id'] ?? null,
                $input['dept_id'] ?? null,
                $input['total_amount'] ?? 0,
                $input['notes'] ?? null,
            ]);
            $newId = $pdo->lastInsertId();
            api_response(true, ['po_id' => $newId, 'po_number' => $po_number], 'Purchase order created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            if (!$id) {
                api_error('PO ID required');
            }
            $st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
            $st->execute([$id]);
            $current = $st->fetchColumn();
            if ($current === false) {
                api_error('Purchase order not found', 404);
            }

            $fields = [];
            $params = [];
            $allowed = ['total_amount', 'notes', 'dept_id'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }

            if (isset($input['status'])) {
                $new_status = $input['status'];
                $transition_ok = false;
                if ($new_status === 'Issued') {
                    $transition_ok = can('approve_purchase_orders');
                } elseif (in_array($new_status, ['Shipped', 'In Transit', 'Partially Received', 'Fully Received', 'Closed'], true)) {
                    $transition_ok = can('fulfill_purchase_orders');
                } elseif ($new_status === 'Cancelled') {
                    $transition_ok = can('approve_purchase_orders') || can('fulfill_purchase_orders');
                } elseif ($new_status === 'Pending Approval') {
                    $transition_ok = can('create_purchase_requests') || can('approve_purchase_orders');
                }
                if (!$transition_ok) {
                    api_error('Forbidden: insufficient permissions for this status transition', 403);
                }
                $fields[] = 'status = ?';
                $params[] = $new_status;
            }

            if (empty($fields)) {
                api_error('No fields to update');
            }
            if (isset($input['status']) && !can('approve_purchase_orders') && !can('fulfill_purchase_orders')) {
                require_api_perm('approve_purchase_orders');
            } elseif (!isset($input['status'])) {
                require_api_perm('approve_purchase_orders');
            }

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE purchase_orders SET " . implode(', ', $fields) . " WHERE po_id = ?");
            $stmt->execute($params);

            if (isset($input['status'])) {
                $pdo->prepare(
                    "INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by)
                     VALUES (?, 'Status Update', ?, ?, ?, ?)"
                )->execute([
                    $id, $current, $input['status'], $input['note'] ?? null, $_SESSION['user_id'] ?? null,
                ]);
            }

            api_response(true, null, 'Purchase order updated');
            break;

        case 'DELETE':
            require_api_perm('approve_purchase_orders');
            if (!$id) {
                api_error('PO ID required');
            }
            $st = $pdo->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
            $st->execute([$id]);
            $current = $st->fetchColumn();
            if ($current === false) {
                api_error('Purchase order not found', 404);
            }
            if (!in_array($current, ['Draft', 'Cancelled', 'Pending Approval'], true)) {
                api_error('Only Draft, Pending Approval, or Cancelled POs may be deleted');
            }
            $pdo->prepare("DELETE FROM po_items WHERE po_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM po_status_logs WHERE po_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM purchase_orders WHERE po_id = ?")->execute([$id]);
            api_response(true, null, 'Purchase order deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
