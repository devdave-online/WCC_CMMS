<?php
/**
 * Work Orders / PMs Resource Handler
 * Supports preventive and corrective maintenance
 */

function handle_work_orders($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_work_orders');
            if ($id) {
                $stmt = $pdo->prepare("SELECT wo.*, e.equip_name FROM work_orders wo LEFT JOIN equipment e ON wo.equipment_id = e.equip_id WHERE wo.wo_id = ?");
                $stmt->execute([$id]);
                $wo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$wo) api_error('Work order not found', 404);
                api_response(true, $wo);
            } else {
                list($page, $per_page, $offset) = get_pagination();

                $status = $_GET['status'] ?? null;
                $equip_id = $_GET['equip_id'] ?? null;

                $sql = "SELECT wo.*, e.equip_name FROM work_orders wo LEFT JOIN equipment e ON wo.equipment_id = e.equip_id WHERE 1=1";
                $params = [];

                if ($status) {
                    $sql .= " AND wo.status = ?";
                    $params[] = $status;
                }
                if ($equip_id) {
                    $sql .= " AND wo.equipment_id = ?";
                    $params[] = $equip_id;
                }

                // History (Completed): most recently completed first. Otherwise schedule order.
                if ($status !== null && strcasecmp((string)$status, 'Completed') === 0) {
                    $sql .= " ORDER BY COALESCE(wo.completed_date, wo.started_at, wo.scheduled_date) DESC LIMIT ? OFFSET ?";
                } else {
                    $sql .= " ORDER BY wo.scheduled_date DESC LIMIT ? OFFSET ?";
                }
                $params[] = $per_page;
                $params[] = $offset;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Basic meta
                $meta = [
                    'page' => $page,
                    'per_page' => $per_page,
                    'returned' => count($items)
                ];
                api_response(true, $items, '', 200, $meta);
            }
            break;

        case 'POST':
            require_api_perm('manage_work_orders');
            if (empty($input['equip_id'])) api_error('equip_id is required');
            $status = $input['status'] ?? 'Scheduled';
            // Two columns in the old statement do not exist on work_orders: the live
            // column is `equipment_id` (not equip_id), and there is no `priority`
            // column at all — work order urgency is carried by the linked ticket.
            // The request field stays `equip_id` so existing API clients keep working.
            $stmt = $pdo->prepare("INSERT INTO work_orders (equipment_id, title, description, status, scheduled_date, assigned_to) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['equip_id'],
                $input['title'] ?? 'New Work Order',
                $input['description'] ?? '',
                $status,
                $input['scheduled_date'] ?? date('Y-m-d'),
                $input['assigned_to'] ?? null
            ]);
            api_response(true, ['wo_id' => $pdo->lastInsertId()], 'Work order created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_work_orders');
            if (!$id) api_error('Work Order ID required');
            $fields = [];
            $params = [];
            $allowed = ['title', 'description', 'status', 'scheduled_date', 'assigned_to', 'priority', 'completed_date'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE work_orders SET " . implode(', ', $fields) . " WHERE wo_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Work order updated');
            break;

        case 'DELETE':
            require_api_perm('manage_work_orders');
            if (!$id) api_error('Work Order ID required');
            $stmt = $pdo->prepare("DELETE FROM work_orders WHERE wo_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Work order deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
