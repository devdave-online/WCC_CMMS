<?php
/**
 * Roles / Presets Resource Handler
 * Based on role_definitions table
 */

function handle_roles($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            require_api_perm('manage_users');
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM role_definitions WHERE role_level = ?");
                $stmt->execute([$id]);
                $role = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$role) api_error('Role not found', 404);
                api_response(true, $role);
            } else {
                $stmt = $pdo->query("SELECT * FROM role_definitions ORDER BY role_level ASC");
                $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                api_response(true, $roles);
            }
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_users');
            if (!$id) api_error('Role level required');
            $fields = [];
            $params = [];
            $allowed = ['name', 'permissions_json', 'description'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE role_definitions SET " . implode(', ', $fields) . " WHERE role_level = ?");
            $stmt->execute($params);
            api_response(true, null, 'Role updated');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}
