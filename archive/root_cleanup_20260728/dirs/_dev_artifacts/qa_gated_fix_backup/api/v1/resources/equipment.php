<?php
/**
 * Equipment Resource Handler
 */

function handle_equipment($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equip_id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$item) api_error('Equipment not found', 404);
                api_response(true, $item);
            } else {
                require_api_perm('view_equipment');
                list($page, $per_page, $offset) = get_pagination();
                $category = $_GET['category'] ?? null;
                $active = $_GET['is_active'] ?? null;
                $sql = "SELECT * FROM equipment WHERE 1=1";
                $params = [];
                if ($category) {
                    $sql .= " AND category = ?";
                    $params[] = $category;
                }
                if ($active !== null) {
                    $sql .= " AND is_active = ?";
                    $params[] = (int)$active;
                }
                $sql .= " ORDER BY equip_id DESC LIMIT ? OFFSET ?";
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
            require_api_perm('manage_equipment');
            if (empty($input['equip_name'])) api_error('equip_name is required');
            $stmt = $pdo->prepare("INSERT INTO equipment (equip_name, category, criticality, is_active, plant_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['equip_name'],
                $input['category'] ?? null,
                $input['criticality'] ?? 'B',
                $input['is_active'] ?? 1,
                $input['plant_name'] ?? null
            ]);
            api_response(true, ['equip_id' => $pdo->lastInsertId()], 'Equipment created', 201);
            break;

        case 'PUT':
            require_api_perm('manage_equipment');
            if (!$id) api_error('Equipment ID required');
            // Simplified update
            $fields = [];
            $params = [];
            foreach (['equip_name', 'category', 'criticality', 'is_active', 'plant_name'] as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE equipment SET " . implode(', ', $fields) . " WHERE equip_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Equipment updated');
            break;

        case 'DELETE':
            require_api_perm('manage_equipment');
            if (!$id) api_error('Equipment ID required');
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equip_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Equipment deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
