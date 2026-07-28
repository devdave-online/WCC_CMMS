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
            'users', 'roles', 'equipment', 'toolings', 'production-lines', 'tickets', 'ticket-actions', 'work-orders',
            'inventory', 'vendors', 'purchase-orders', 'purchase-requests',
            'stats', 'audit', 'api-keys', 'ai-context', 'me'
        ],
        'note' => 'Use X-API-Key header or Basic Auth. See /rest_api_core.md for details. Toolings: /toolings, /toolings/{id}/bom, /toolings/{id}/documents.',
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

    case 'toolings':
        require __DIR__ . '/resources/toolings.php';
        $subId = $segments[3] ?? null;
        handle_toolings($method, $id, $input, $subResource, $subId);
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
        // Current user info — enriched for companion app profile
        $user = current_api_user();
        $uid = $user['user_id'];
        
        // Fetch full user record
        $stmt = $pdo->prepare("SELECT user_id, username, badge_number, role_level, email, full_name, status, created_at, last_login FROM users WHERE user_id = ?");
        $stmt->execute([$uid]);
        $fullUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;
        
        // Fetch live stats.
        //
        // The people-columns (pic / announced_by / tech_name) hold either the username or
        // the display name depending on which path wrote the row, so every read matches on
        // BOTH spellings. Uses the shared helpers in inc/techident.php (auto-loaded by
        // bootstrap.php) rather than a matcher local to this endpoint — a third convention
        // is exactly the failure mode being avoided here.
        $aliases      = wcc_tech_aliases($fullUser);
        $aliasSlots   = wcc_tech_alias_placeholders($aliases);

        // Closed-out count matches web my_profile.php: who closed the ticket (closed_by),
        // NOT who was PIC. PIC is assignment; closed_by is the technician who finished it.
        $ticketsClosed = $pdo->prepare(
            "SELECT COUNT(*) FROM active_tickets
             WHERE closed_by IN ($aliasSlots) AND status = 'CLOSED'"
        );
        $ticketsClosed->execute($aliases);
        $closedCount = $ticketsClosed->fetchColumn();

        // Companion cannot announce/raise tickets — keep announced_by count for web parity
        // only. Companion UI uses tickets_as_pic (live floor load) instead.
        $ticketsReported = $pdo->prepare(
            "SELECT COUNT(*) FROM active_tickets WHERE announced_by IN ($aliasSlots)"
        );
        $ticketsReported->execute($aliases);
        $reportedCount = $ticketsReported->fetchColumn();

        // Live tickets where this tech is Person In Charge (shop-floor workload).
        $asPic = $pdo->prepare(
            "SELECT COUNT(*) FROM active_tickets
             WHERE pic IN ($aliasSlots)
               AND UPPER(COALESCE(status, 'OPEN')) IN ('OPEN','PENDING','ESCALATED','HOLD')"
        );
        $asPic->execute($aliases);
        $asPicCount = $asPic->fetchColumn();

        // Interventions = real wrench actions. Exclude COMPANION_DEMO_SEED rows used only
        // to exercise gamified proficiency hours so demo data does not fake KPIs.
        $totalInterventions = $pdo->prepare(
            "SELECT COUNT(*) FROM ticket_actions
             WHERE tech_name IN ($aliasSlots)
               AND (fault_type IS NULL OR fault_type <> 'COMPANION_DEMO_SEED')"
        );
        $totalInterventions->execute($aliases);
        $interventionCount = $totalInterventions->fetchColumn();

        // Average wrench time on real actions only (same seed exclusion).
        $avgStmt = $pdo->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, action_start, action_end))
             FROM ticket_actions
             WHERE tech_name IN ($aliasSlots)
               AND action_end > action_start
               AND (fault_type IS NULL OR fault_type <> 'COMPANION_DEMO_SEED')"
        );
        $avgStmt->execute($aliases);
        $avgMinutes = $avgStmt->fetchColumn();
        if ($avgMinutes === null || $avgMinutes === false) {
            $avgWrench = '--';
        } else {
            $m = (int) round($avgMinutes);
            $avgWrench = $m >= 60 ? (intdiv($m, 60) . 'h ' . ($m % 60) . 'm') : ($m . 'm');
        }
        
        // Role label comes from role_definitions via get_role_name(), never a literal map.
        //
        // The map that used to be here was wrong from level 3 upward and, worse,
        // reported level 5 — the read-only "Custom Viewer" — as "L5 — Admin". Roles are
        // editable in the RBAC screen, so any hardcoded copy is guaranteed to drift;
        // level 6 (Storekeeper) did not exist in it at all.
        $__lvl      = (int)($fullUser['role_level'] ?? 1);
        $__roleName = get_role_name($__lvl);

        api_response(true, [
            'user_id'       => $fullUser['user_id'],
            'username'      => $fullUser['username'],
            'name'          => $fullUser['full_name'] ?? $fullUser['username'],
            'badge'         => $fullUser['badge_number'] ?? 'N/A',
            'role'          => 'L' . $__lvl . ' — ' . $__roleName,   // e.g. "L4 — Admin"
            'role_name'     => $__roleName,                          // unformatted, for the app to style itself
            'role_level'    => $fullUser['role_level'],
            'email'         => $fullUser['email'],
            'status'        => $fullUser['status'],
            'last_login'    => $fullUser['last_login'],
            'interventions' => number_format($interventionCount),
            'tickets_closed'   => number_format($closedCount),
            'tickets_reported' => number_format($reportedCount), // web / legacy; companion uses tickets_as_pic
            'tickets_as_pic'   => number_format($asPicCount),   // live OPEN/PENDING/ESCALATED/HOLD as PIC
            'avg_wrench_time'  => $avgWrench
        ]);
        break;

    case 'ai-context':
        require __DIR__ . '/resources/ai_context.php';
        handle_ai_context($method, $id, $input);
        break;

    default:
        api_error('Resource not found', 404);
}
