<?php
/**
 * Purchase Orders Resource Handler
 * Covers purchase_orders and basic po_items
 */

function handle_purchase_orders($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_purchase_requests'); // or specific perm
            if ($id) {
                $stmt = $pdo->prepare("SELECT po.*, v.vendor_name FROM purchase_orders po LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id WHERE po.po_id = ?");
                $stmt->execute([$id]);
                $po = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$po) api_error('Purchase order not found', 404);

                // Include items
                $itemsStmt = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ?");
                $itemsStmt->execute([$id]);
                $po['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                api_response(true, $po);
            } else {
                $status = $_GET['status'] ?? null;
                $vendor_id = $_GET['vendor_id'] ?? null;

                $sql = "SELECT po.*, v.vendor_name FROM purchase_orders po LEFT JOIN vendors_suppliers v ON po.vendor_id = v.vendor_id WHERE 1=1";
                $params = [];

                if ($status) {
                    $sql .= " AND po.status = ?";
                    $params[] = $status;
                }
                if ($vendor_id) {
                    $sql .= " AND po.vendor_id = ?";
                    $params[] = $vendor_id;
                }

                $sql .= " ORDER BY po.created_at DESC LIMIT 50";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                api_response(true, $items);
            }
            break;

        case 'POST':
            require_api_perm('create_purchase_requests');
            if (empty($input['vendor_id'])) api_error('vendor_id is required');

            $po_number = 'PO-' . date('YmdHis');
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, created_by, dept_id, status, total_amount, notes) VALUES (?, ?, ?, ?, 'Draft', ?, ?)");
            $stmt->execute([
                $po_number,
                $input['vendor_id'],
                $_SESSION['user_id'] ?? null,
                $input['dept_id'] ?? null,
                $input['total_amount'] ?? 0,
                $input['notes'] ?? null
            ]);
            $newId = $pdo->lastInsertId();
            api_response(true, ['po_id' => $newId, 'po_number' => $po_number], 'Purchase order created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('approve_purchase_orders');
            if (!$id) api_error('PO ID required');
            $fields = [];
            $params = [];
            $allowed = ['status', 'total_amount', 'notes', 'dept_id'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE purchase_orders SET " . implode(', ', $fields) . " WHERE po_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Purchase order updated');
            break;

        case 'DELETE':
            require_api_perm('manage_users'); // high privilege
            if (!$id) api_error('PO ID required');
            $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE po_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Purchase order deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
