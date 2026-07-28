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
        'view_toolings'            => true,
        'view_inventory'           => false,
        'view_vendors'             => false,
        'view_work_orders'         => false,
        'manage_work_orders'       => false,
        'view_purchase_requests'   => false,
        'create_purchase_requests' => false,
        'approve_purchase_orders'  => false,
        'fulfill_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => false,
        'manage_toolings'          => false,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
        'delete_users'             => false,
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
        'view_toolings'            => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => false,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => false,
        'fulfill_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => false,
        'manage_toolings'          => false,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
        'delete_users'             => false,
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
        'view_toolings'            => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => true,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => false,
        'fulfill_purchase_orders'  => false,
        'manage_users'             => false,
        'manage_settings'          => false,
        'manage_equipment'         => true,
        'manage_toolings'          => true,
        'manage_inventory'         => false,
        'manage_vendors'           => false,
        'reset_passwords'          => false,
        'delete_users'             => false,
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
        'view_toolings'            => true,
        'view_inventory'           => true,
        'view_vendors'             => true,
        'view_work_orders'         => true,
        'manage_work_orders'       => true,
        'view_purchase_requests'   => true,
        'create_purchase_requests' => true,
        'approve_purchase_orders'  => true,
        'fulfill_purchase_orders'  => true,
        'manage_users'             => true,
        'manage_settings'          => true,
        'manage_equipment'         => true,
        'manage_toolings'          => true,
        'manage_inventory'         => true,
        'manage_vendors'           => true,
        'reset_passwords'          => true,
        'delete_users'             => true,
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
                return wcc_backfill_tooling_perms($decoded);
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

/**
 * Older role_definitions / user overrides lack tooling keys. Until an admin
 * re-saves presets, inherit from equipment so nobody silently loses access.
 * Once view_toolings / manage_toolings appear in the JSON (even as false),
 * those explicit values win — so checkboxes can flush access independently.
 */
function wcc_backfill_tooling_perms(array $perms): array
{
    if (!array_key_exists('view_toolings', $perms)) {
        $perms['view_toolings'] = !empty($perms['view_equipment']);
    }
    if (!array_key_exists('manage_toolings', $perms)) {
        $perms['manage_toolings'] = !empty($perms['manage_equipment']);
    }
    return $perms;
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
    'manage_equipment'         => ['label' => 'Manage Equipment Vault',     'group' => 'Assets'],
    'view_toolings'            => ['label' => 'View Tooling Registry',      'group' => 'Assets'],
    'manage_toolings'          => ['label' => 'Manage Tooling Vault',       'group' => 'Assets'],
    'view_inventory'           => ['label' => 'View Inventory',             'group' => 'Assets'],
    'manage_inventory'         => ['label' => 'Manage Inventory (Add Parts)','group' => 'Assets'],
    'view_vendors'             => ['label' => 'View Vendors Directory',     'group' => 'Procurement'],
    'manage_vendors'           => ['label' => 'Manage Vendors',             'group' => 'Procurement'],
    'view_purchase_requests'   => ['label' => 'View Purchase Requests',     'group' => 'Procurement'],
    'create_purchase_requests' => ['label' => 'Create Purchase Requests',   'group' => 'Procurement'],
    'approve_purchase_orders'  => ['label' => 'Approve Purchase Orders (cost sign-off)', 'group' => 'Procurement'],
    'fulfill_purchase_orders'  => ['label' => 'Fulfill POs (ship / receive / close)',    'group' => 'Procurement'],
    'view_work_orders'         => ['label' => 'View Work Orders',           'group' => 'Maintenance'],
    'manage_work_orders'       => ['label' => 'Manage Work Orders',         'group' => 'Maintenance'],
    'manage_users'             => ['label' => 'Manage Users (RBAC)',        'group' => 'Admin'],
    'manage_settings'          => ['label' => 'Admin Panel & System Settings', 'group' => 'Admin'],
    'reset_passwords'          => ['label' => 'Reset User Passwords',       'group' => 'Admin'],
    'delete_users'             => ['label' => 'Delete Users',               'group' => 'Admin'],
];

// ------------------------------------------------------------
// wcc_get_permissions()
// Resolves the final permission set for a user.
//
// Build order (so incomplete role_definitions JSON never drops keys forever,
// and Custom Viewer with [] can still receive per-user grants):
//   1. Full registry skeleton (every PERMISSION_LABELS key → false)
//   2. Role defaults from DB (or ROLE_PERMISSIONS fallback for 1–4)
//   3. Per-user permissions_json overrides (only registry keys)
// ------------------------------------------------------------
function wcc_get_permissions(int $role_level, ?string $permissions_json): array {
    // 1. Full registry — guarantees every known key exists
    $resolved = [];
    foreach (array_keys(PERMISSION_LABELS) as $key) {
        $resolved[$key] = false;
    }

    // 2. Role defaults overlay (may be partial / empty for custom levels)
    $defaults = get_role_permissions($role_level);
    if (is_array($defaults)) {
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $resolved)) {
                $resolved[$key] = (bool)$value;
            }
        }
    }

    // 3. Per-user overrides — keys must be in the registry (not only in role defaults)
    if (!empty($permissions_json)) {
        $overrides = json_decode($permissions_json, true);
        if (is_array($overrides)) {
            $overrides = wcc_backfill_tooling_perms($overrides);
            foreach ($overrides as $key => $value) {
                if (array_key_exists($key, $resolved)) {
                    $resolved[$key] = (bool)$value;
                }
            }
        }
    }

    return $resolved;
}

// ------------------------------------------------------------
// can()
// Returns true if the current session user has the permission.
// Falls back gracefully if permissions aren't in session yet.
// ------------------------------------------------------------
function can(string $permission): bool {
    if (!isset($_SESSION['permissions'])) {
        // Fallback: full registry merge from role only (no per-user JSON available here)
        $level = (int)($_SESSION['role_level'] ?? 1);
        $defaults = wcc_get_permissions($level, null);
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
