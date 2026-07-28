<?php
/**
 * Inventory Resource Handler
 */

function handle_inventory($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_inventory');
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM inventory_parts WHERE part_id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) api_error('Part not found', 404);
                api_response(true, $item);
            } else {
                list($page, $per_page, $offset) = get_pagination();
                $search = $_GET['search'] ?? '';
                $sql = "SELECT * FROM inventory_parts WHERE 1=1";
                $params = [];
                if ($search) {
                    $sql .= " AND (part_name LIKE ? OR internal_code LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                $sql .= " ORDER BY part_name LIMIT ? OFFSET ?";
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
            require_api_perm('manage_inventory');
            if (empty($input['part_name']) || empty($input['internal_code'])) {
                api_error('part_name and internal_code required');
            }
            $stmt = $pdo->prepare("INSERT INTO inventory_parts (part_name, internal_code, stock_level, minimum_threshold, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['part_name'],
                $input['internal_code'],
                $input['stock_level'] ?? 0,
                $input['minimum_threshold'] ?? 5,
                $input['cost_per_unit'] ?? 0
            ]);
            api_response(true, ['part_id' => $pdo->lastInsertId()], 'Part created', 201);
            break;

        case 'PUT':
            require_api_perm('manage_inventory');
            if (!$id) api_error('Part ID required');
            $fields = [];
            $params = [];
            $allowed = ['part_name', 'stock_level', 'minimum_threshold', 'cost_per_unit'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE inventory_parts SET " . implode(', ', $fields) . " WHERE part_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Inventory item updated');
            break;

        case 'DELETE':
            require_api_perm('manage_inventory');
            if (!$id) api_error('Part ID required');
            $stmt = $pdo->prepare("DELETE FROM inventory_parts WHERE part_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Inventory item deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
