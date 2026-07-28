<?php
/**
 * Users Resource Handler
 */

function handle_users($method, $id, $input) {
    global $pdo;

    switch ($method) {
        case 'GET':
            if ($id) {
                require_api_perm('manage_users');
                $stmt = $pdo->prepare("SELECT user_id, username, badge_number, role_level, email, full_name, status, created_at, last_login FROM users WHERE user_id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) api_error('User not found', 404);
                api_response(true, $user);
            } else {
                require_api_perm('manage_users');
                list($page, $per_page, $offset) = get_pagination();
                $status = $_GET['status'] ?? null;
                $sql = "SELECT user_id, username, badge_number, role_level, email, full_name, status, created_at, last_login FROM users WHERE 1=1";
                $params = [];
                if ($status) {
                    $sql .= " AND status = ?";
                    $params[] = $status;
                }
                $sql .= " ORDER BY user_id ASC LIMIT ? OFFSET ?";
                $params[] = $per_page;
                $params[] = $offset;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $meta = ['page' => $page, 'per_page' => $per_page, 'returned' => count($users)];
                api_response(true, $users, '', 200, $meta);
            }
            break;

        case 'POST':
            require_api_perm('manage_users');
            if (empty($input['username']) || empty($input['password'])) {
                api_error('username and password are required');
            }
            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_level, badge_number, email, full_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['username'],
                $hash,
                $input['role_level'] ?? 1,
                $input['badge_number'] ?? null,
                $input['email'] ?? null,
                $input['full_name'] ?? null,
                $input['status'] ?? 'active'
            ]);
            $newId = $pdo->lastInsertId();
            api_response(true, ['user_id' => $newId], 'User created', 201);
            break;

        case 'PUT':
        case 'PATCH':
            require_api_perm('manage_users');
            if (!$id) api_error('User ID required');
            $fields = [];
            $params = [];
            $allowed = ['role_level', 'email', 'full_name', 'phone', 'status', 'notes', 'badge_number'];
            foreach ($allowed as $f) {
                if (isset($input[$f])) {
                    $fields[] = "$f = ?";
                    $params[] = $input[$f];
                }
            }
            if (isset($input['password'])) {
                $fields[] = "password_hash = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            if (empty($fields)) api_error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?");
            $stmt->execute($params);
            api_response(true, null, 'User updated');
            break;

        case 'DELETE':
            require_api_perm('manage_users');
            if (!$id) api_error('User ID required');
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$id]);
            api_response(true, null, 'User deleted');
            break;

        default:
            api_error('Method not allowed', 405);
    }
}

// Helper for API key generation (can be called separately)
function generate_api_key_for_user($userId) {
    global $pdo;
    $newKey = bin2hex(random_bytes(32)); // 64 char key
    $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE user_id = ?");
    $stmt->execute([$newKey, $userId]);
    return $newKey;
}

