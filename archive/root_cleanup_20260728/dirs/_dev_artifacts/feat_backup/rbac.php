<?php
// ============================================================
// rbac.php — WCC Role-Based Access Control Engine
// ============================================================
// Usage:
//   require_once 'rbac.php';     (after session_start / auth.php)
//   can('view_statistics')       → bool
//   require_perm('manage_users') → exits with denied block if false
// ============================================================

// ------------------------------------------------------------
// ROLE NAMES (centralized - used across nav, profiles, user lists)
// These are the base names; actual names can come from role_definitions table.
// ------------------------------------------------------------
const ROLE_NAMES = [
    1 => 'Operator',
    2 => 'Technician',
    3 => 'Supervisor',
    4 => 'Admin'
];

// ------------------------------------------------------------
// DEFAULT PERMISSION SETS BY ROLE LEVEL
// These are the baseline / fallback. 
// The DB table `role_definitions` (populated via Role Presets UI) is preferred when available.
// Per-user permissions_json can still override any individual key.
// ------------------------------------------------------------
const ROLE_PERMISSIONS = [
    // Level 1 — Operator
    1 => [
        'view_tickets'             => true,
        'create_tickets'           => true,
        'takeover_tickets'         => false,
        'closeout_tickets'         => false,
        'view_history'             => true,
        'view_statistics'          => false,
        'view_equipment'           => true,
        'view_inventory'           => false,
        'view_vendors'             => false,
        'view_work_orders'         => false,
        'manage_work_orders'       => false,
        'view_purchase_requests'   => false,
        'create_purchase_requests' => false,
        'approve_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => false,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
    ],
    // Level 2 — Technician
    2 => [
        'view_tickets'             => true,
        'create_tickets'           => true,
        'takeover_tickets'         => true,
        'closeout_tickets'         => false,
        'view_history'             => true,
        'view_statistics'          => true,
        'view_equipment'           => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => false,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => false,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
    ],
    // Level 3 — Supervisor
    3 => [
        'view_tickets'             => true,
        'create_tickets'           => true,
        'takeover_tickets'         => true,
        'closeout_tickets'         => true,
        'view_history'             => true,
        'view_statistics'          => true,
        'view_equipment'           => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => true,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => true,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
    ],
    // Level 4 — Admin (all permissions)
    4 => [
        'view_tickets'             => true,
        'create_tickets'           => true,
        'takeover_tickets'         => true,
        'closeout_tickets'         => true,
        'view_history'             => true,
        'view_statistics'          => true,
        'view_equipment'           => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => true,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => true,
        'manage_users'             => true,
        'manage_settings'          => true,
        'manage_equipment'         => true,
        'manage_inventory'         => true,
        'manage_vendors'           => true,
        'reset_passwords'          => true,
    ],
];

// ------------------------------------------------------------
// get_role_permissions()
// Loads role defaults from DB (role_definitions table) if present.
// Falls back to the hardcoded ROLE_PERMISSIONS const for levels 1-4 (for safety).
// New custom levels (5+) will return whatever is stored or empty array.
// This powers the "presets" and "Reset Perms to Role".
// ------------------------------------------------------------
function get_role_permissions(int $role_level): array {
    $level = max(1, $role_level);

    // Try database first (the editable source of truth)
    try {
        if (function_exists('get_wcc_db_connection')) {
            $pdo = get_wcc_db_connection();
        } else {
            require_once __DIR__ . '/inc/db.php';
            $pdo = get_wcc_db_connection();
        }

        $stmt = $pdo->prepare("SELECT permissions_json FROM role_definitions WHERE role_level = ?");
        $stmt->execute([$level]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $decoded = json_decode($row['permissions_json'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    } catch (Exception $e) {
        // table may not exist or DB down — fall through
    }

    // Hardcoded fallback only for classic levels 1-4
    if ($level <= 4 && isset(ROLE_PERMISSIONS[$level])) {
        return ROLE_PERMISSIONS[$level];
    }

    return []; // new custom role with no definition yet
}

// Get all defined roles from DB (or fallback for 1-4)
function get_all_roles(): array {
    $roles = [];
    try {
        if (function_exists('get_wcc_db_connection')) {
            $pdo = get_wcc_db_connection();
        } else {
            require_once __DIR__ . '/inc/db.php';
            $pdo = get_wcc_db_connection();
        }

        $rows = $pdo->query("SELECT role_level, name FROM role_definitions ORDER BY role_level ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $lvl = (int)$row['role_level'];
            $roles[$lvl] = $row['name'] ?: (ROLE_NAMES[$lvl] ?? "Custom Level $lvl");
        }
    } catch (Exception $e) {}

    // Ensure at least the classic 1-4 exist as fallback
    foreach (ROLE_NAMES as $lvl => $name) {
        if (!isset($roles[$lvl])) {
            $roles[$lvl] = $name;
        }
    }
    ksort($roles);
    return $roles;
}

// Get role name (prefers DB)
function get_role_name(int $role_level): string {
    $all = get_all_roles();
    return $all[$role_level] ?? (ROLE_NAMES[$role_level] ?? "Level $role_level");
}

// ------------------------------------------------------------
// Human-readable labels for the permissions editor UI
// ------------------------------------------------------------
const PERMISSION_LABELS = [
    'view_tickets'             => ['label' => 'View Tickets',              'group' => 'Tickets'],
    'create_tickets'           => ['label' => 'Create Tickets',            'group' => 'Tickets'],
    'takeover_tickets'         => ['label' => 'Takeover Tickets',          'group' => 'Tickets'],
    'closeout_tickets'         => ['label' => 'Closeout Tickets',          'group' => 'Tickets'],
    'view_history'             => ['label' => 'View Event History',   'group' => 'Tickets'],
    'view_statistics'          => ['label' => 'View Analytics / KPIs',     'group' => 'Reports'],
    'view_equipment'           => ['label' => 'View Equipment Ledger',      'group' => 'Assets'],
    'manage_equipment'         => ['label' => 'Manage Equipment',           'group' => 'Assets'],
    'view_inventory'           => ['label' => 'View Inventory',             'group' => 'Assets'],
    'manage_inventory'         => ['label' => 'Manage Inventory (Add Parts)','group' => 'Assets'],
    'view_vendors'             => ['label' => 'View Vendors Directory',     'group' => 'Procurement'],
    'manage_vendors'           => ['label' => 'Manage Vendors',             'group' => 'Procurement'],
    'view_purchase_requests'   => ['label' => 'View Purchase Requests',     'group' => 'Procurement'],
    'create_purchase_requests' => ['label' => 'Create Purchase Requests',   'group' => 'Procurement'],
    'approve_purchase_orders'  => ['label' => 'Approve / Manage POs',       'group' => 'Procurement'],
    'view_work_orders'         => ['label' => 'View Work Orders',           'group' => 'Maintenance'],
    'manage_work_orders'       => ['label' => 'Manage Work Orders',         'group' => 'Maintenance'],
    'manage_users'             => ['label' => 'Manage Users (RBAC)',        'group' => 'Admin'],
    'manage_settings'          => ['label' => 'Admin Panel & System Settings', 'group' => 'Admin'],
    'reset_passwords'          => ['label' => 'Reset User Passwords',       'group' => 'Admin'],
];

// ------------------------------------------------------------
// wcc_get_permissions()
// Resolves the final permission set for a user.
// Merges role-level defaults with any per-user JSON overrides.
// ------------------------------------------------------------
function wcc_get_permissions(int $role_level, ?string $permissions_json): array {
    // Load from DB (editable presets) or hardcoded fallback
    $defaults = get_role_permissions($role_level);

    if (!empty($permissions_json)) {
        $overrides = json_decode($permissions_json, true);
        if (is_array($overrides)) {
            // Only override keys that exist in the permission registry
            foreach ($overrides as $key => $value) {
                if (array_key_exists($key, $defaults)) {
                    $defaults[$key] = (bool)$value;
                }
            }
        }
    }

    return $defaults;
}

// ------------------------------------------------------------
// can()
// Returns true if the current session user has the permission.
// Falls back gracefully if permissions aren't in session yet.
// ------------------------------------------------------------
function can(string $permission): bool {
    if (!isset($_SESSION['permissions'])) {
        // Fallback: derive from role_level alone (no overrides) — uses DB presets when available
        $level = (int)($_SESSION['role_level'] ?? 1);
        $defaults = get_role_permissions($level);
        return (bool)($defaults[$permission] ?? false);
    }
    return (bool)($_SESSION['permissions'][$permission] ?? false);
}

// ------------------------------------------------------------
// require_perm()
// Hard gate — exits with a styled "Access Denied" block if
// the current user lacks the specified permission.
// Call AFTER nav.php is included if you want the sidebar shown,
// or BEFORE if you want a bare error page.
// ------------------------------------------------------------
function require_perm(string $permission, bool $show_nav = true): void {
    if (!can($permission)) {
        if ($show_nav) {
            require_once __DIR__ . '/nav.php';
        }
        $label = PERMISSION_LABELS[$permission]['label'] ?? $permission;
        echo "
        <div class='dashboard-container' style='margin: 50px auto; max-width: 600px; text-align: center;'>
            <div style='font-size: 3em; margin-bottom: 15px;'>🔒</div>
            <h2 style='color:#ef4444; margin-bottom: 10px;'>Access Denied</h2>
            <p style='color:#94a3b8; margin-bottom: 20px;'>
                You do not have the <strong style='color:#f87171;'>{$label}</strong> permission.<br>
                Contact your administrator to request access.
            </p>
            <a href='index.php' class='nav-btn'>← Return to Hub</a>
        </div>
        </body></html>";
        exit;
    }
}
