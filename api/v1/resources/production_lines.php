<?php
/**
 * Production Lines Resource Handler
 */

function handle_production_lines($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('view_equipment');
            if ($id) {
                $stmt = $pdo->prepare("SELECT l.*, w.name as workshop_name FROM production_lines l LEFT JOIN workshops w ON l.workshop_id = w.workshop_id WHERE l.line_id = ?");
                $stmt->execute([$id]);
                $line = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$line) api_error('Production line not found', 404);
                api_response(true, $line);
            } else {
                $workshop_id = $_GET['workshop_id'] ?? null;
                $sql = "SELECT l.*, w.name as workshop_name FROM production_lines l LEFT JOIN workshops w ON l.workshop_id = w.workshop_id";
                $params = [];
                if ($workshop_id) {
                    $sql .= " WHERE l.workshop_id = ?";
                    $params[] = $workshop_id;
                }
                $sql .= " ORDER BY w.name, l.name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                api_response(true, $items);
            }
            break;

        case 'POST':
            require_api_perm('manage_equipment');
            if (empty($input['name']) || empty($input['workshop_id'])) {
                api_error('name and workshop_id are required');
            }
            $stmt = $pdo->prepare("INSERT INTO production_lines (workshop_id, name, products_built, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $input['workshop_id'],
                $input['name'],
                $input['products_built'] ?? 0,
                $input['status'] ?? 'Active'
            ]);
            api_response(true, ['line_id' => $pdo->lastInsertId()], 'Production line created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_equipment');
            if (!$id) api_error('Line ID required');
            $fields = [];
            $params = [];
            $allowed = ['name', 'workshop_id', 'products_built', 'status'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE production_lines SET " . implode(', ', $fields) . " WHERE line_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'Production line updated');
            break;

        case 'DELETE':
            require_api_perm('manage_equipment');
            if (!$id) api_error('Line ID required');
            $stmt = $pdo->prepare("DELETE FROM production_lines WHERE line_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'Production line deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
