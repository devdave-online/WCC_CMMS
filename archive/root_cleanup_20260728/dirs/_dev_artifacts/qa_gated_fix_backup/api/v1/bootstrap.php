<?php
/**
 * REST API Bootstrap for WCC CMMS
 * Provides auth, DB, helpers for v1 API
 */

require_once __DIR__ . '/../../inc/error.php';
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../rbac.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function api_response($success, $data = null, $message = '', $status = 200, $meta = null) {
    http_response_code($status);
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ];
    if ($meta) {
        $response['meta'] = $meta;
    }
    echo json_encode($response);
    exit;
}

function api_error($message, $status = 400) {
    api_response(false, null, $message, $status);
}

// Simple auth for API: support X-API-Key or Basic Auth
function api_authenticate() {
    global $pdo;

    // Try X-API-Key header
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE api_key = ? AND api_key IS NOT NULL");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_level'] = $user['role_level'];
            $_SESSION['permissions'] = wcc_get_permissions($user['role_level'], $user['permissions_json']);
            return true;
        }
    }

    // Try Basic Auth
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_level'] = $user['role_level'];
            $_SESSION['permissions'] = wcc_get_permissions($user['role_level'], $user['permissions_json']);
            return true;
        }
    }

    // Fallback to session if browser-based
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    api_error('Unauthorized. Provide X-API-Key or Basic Auth', 401);
}

// Get JSON input.
// An empty request body (e.g. every GET, and bodyless DELETEs) is treated as
// "no payload" -> [] rather than a malformed-JSON error. Only a non-empty body
// that fails to parse is rejected.
function get_json_input() {
    $input = file_get_contents('php://input');
    if ($input === false || trim($input) === '') {
        return [];
    }
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        api_error('Invalid JSON body', 400);
    }
    return $data ?? [];
}

// Get current user
function current_api_user() {
    if (!isset($_SESSION['user_id'])) {
        api_error('Authentication required', 401);
    }
    return $_SESSION;
}

// Require specific permission
function require_api_perm($permission) {
    if (!can($permission)) {
        api_error('Forbidden: insufficient permissions', 403);
    }
}

// Simple pagination helper
function get_pagination() {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = min(100, max(1, intval($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $per_page;
    return [$page, $per_page, $offset];
}

// Helper to build meta
function build_meta($page, $per_page, $returned, $total = null) {
    $meta = [
        'page' => $page,
        'per_page' => $per_page,
        'returned' => $returned
    ];
    if ($total !== null) $meta['total'] = $total;
    return $meta;
}
