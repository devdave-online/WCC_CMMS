<?php
/**
 * Purchase Requests Resource Handler
 */

function handle_purchase_requests($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_purchase_requests');
            if ($id) {
                $stmt = $pdo->prepare("SELECT pr.*, u.username as requested_by_name FROM purchase_requests pr LEFT JOIN users u ON pr.requested_by = u.user_id WHERE pr.pr_id = ?");
                $stmt->execute([$id]);
                $pr = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pr) api_error('Purchase request not found', 404);
                api_response(true, $pr);
            } else {
                list($page, $per_page, $offset) = get_pagination();
                $status = $_GET['status'] ?? null;
                $sql = "SELECT pr.*, u.username as requested_by_name FROM purchase_requests pr LEFT JOIN users u ON pr.requested_by = u.user_id WHERE 1=1";
                $params = [];
                if ($status) {
                    $sql .= " AND pr.status = ?";
                    $params[] = $status;
                }
                $sql .= " ORDER BY pr.created_at DESC LIMIT ? OFFSET ?";
                $params[] = $per_page;
                $params[] = $offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $meta = ['page' => $page, 'per_page' => $per_page, 'returned' => count($items)];
                api_response(true, $items, '', 200, $meta);
            }
            break;

        case 'POST':
            require_api_perm('create_purchase_requests');
            if (empty($input['item_description'])) api_error('item_description is required');
            $stmt = $pdo->prepare("INSERT INTO purchase_requests (requested_by, item_description, quantity, justification, status, priority) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $input['item_description'],
                $input['quantity'] ?? 1,
                $input['justification'] ?? '',
                $input['priority'] ?? 'normal'
            ]);
            api_response(true, ['pr_id' => $pdo->lastInsertId()], 'Purchase request created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('approve_purchase_requests');
            if (!$id) api_error('PR ID required');
            $fields = [];
            $params = [];
            $allowed = ['status', 'quantity', 'justification', 'priority', 'approved_by'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE purchase_requests SET " . implode(', ', $fields) . " WHERE pr_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Purchase request updated');
            break;

        case 'DELETE':
            require_api_perm('manage_users');
            if (!$id) api_error('PR ID required');
            $stmt = $pdo->prepare("DELETE FROM purchase_requests WHERE pr_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Purchase request deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
