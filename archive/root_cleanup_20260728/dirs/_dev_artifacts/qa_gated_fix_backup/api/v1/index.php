<?php
/**
 * REST API v1 Router for WCC CMMS
 * Entry point: /api/v1/index.php or clean URLs via .htaccess
 */

require_once __DIR__ . '/bootstrap.php';

api_authenticate();

// Parse request - supports both /api/v1/users and /api/v1/index.php/users
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/api/v1';
$path = str_replace([$basePath, '/index.php'], '', $requestUri);
$path = trim($path, '/');
$segments = $path ? explode('/', $path) : [];

$method = $_SERVER['REQUEST_METHOD'];
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$subResource = $segments[2] ?? null;

$input = get_json_input();

if (empty($resource)) {
    api_response(true, [
        'version' => '1.1',
        'resources' => [
            'users', 'roles', 'equipment', 'production-lines', 'tickets', 'ticket-actions', 'work-orders',
            'inventory', 'vendors', 'purchase-orders', 'purchase-requests',
            'stats', 'audit', 'api-keys', 'ai-context', 'me'
        ],
        'note' => 'Use X-API-Key header or Basic Auth. See /rest_api_core.md for details.',
        'docs' => '/rest_api_core.md'
    ]);
}

// Route to resources
switch ($resource) {
    case 'users':
        require __DIR__ . '/resources/users.php';
        handle_users($method, $id, $input);
        break;

    case 'equipment':
        require __DIR__ . '/resources/equipment.php';
        handle_equipment($method, $id, $input);
        break;

    case 'tickets':
        require __DIR__ . '/resources/tickets.php';
        handle_tickets($method, $id, $input);
        break;

    case 'ticket-actions':
        require __DIR__ . '/resources/ticket_actions.php';
        handle_ticket_actions($method, $id, $input);
        break;

    case 'work-orders':
        require __DIR__ . '/resources/work_orders.php';
        handle_work_orders($method, $id, $input);
        break;

    case 'inventory':
        require __DIR__ . '/resources/inventory.php';
        handle_inventory($method, $id, $input);
        break;

    case 'vendors':
        require __DIR__ . '/resources/vendors.php';
        handle_vendors($method, $id, $input);
        break;

    case 'purchase-orders':
        require __DIR__ . '/resources/purchase_orders.php';
        handle_purchase_orders($method, $id, $input);
        break;

    case 'purchase-requests':
        require __DIR__ . '/resources/purchase_requests.php';
        handle_purchase_requests($method, $id, $input);
        break;

    case 'production-lines':
        require __DIR__ . '/resources/production_lines.php';
        handle_production_lines($method, $id, $input);
        break;

    case 'stats':
        require __DIR__ . '/resources/stats.php';
        handle_stats($method, $id, $input);
        break;

    case 'api-keys':
        // Simple API key management: POST /api-keys?user_id=XX to generate
        if ($method === 'POST') {
            require_api_perm('manage_users');
            $userId = $_GET['user_id'] ?? $id;
            if (!$userId) api_error('user_id required');
            require __DIR__ . '/resources/users.php';
            $key = generate_api_key_for_user($userId);
            api_response(true, ['api_key' => $key, 'user_id' => $userId], 'API key generated');
        } else {
            api_error('Only POST supported for api-keys', 405);
        }
        break;

    case 'audit':
        require __DIR__ . '/resources/audit.php';
        handle_audit($method, $id, $input);
        break;

    case 'roles':
        require __DIR__ . '/resources/roles.php';
        handle_roles($method, $id, $input);
        break;

    case 'me':
        // Current user info
        $user = current_api_user();
        api_response(true, [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role_level' => $user['role_level'],
            'badge_number' => $user['badge_number'] ?? null
        ]);
        break;

    case 'ai-context':
        require __DIR__ . '/resources/ai_context.php';
        handle_ai_context($method, $id, $input);
        break;

    default:
        api_error('Resource not found', 404);
}
