<?php
/**
 * WCC CMMS — static + light runtime security gate checks (CLI only).
 *
 *   C:\xampp\php\php.exe tests\security_gates.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
$fail = 0;
$pass = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo "  OK  $label\n";
    } else {
        $fail++;
        echo " FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

function file_has(string $path, string $needle): bool
{
    if (!is_file($path)) {
        return false;
    }
    return strpos((string)file_get_contents($path), $needle) !== false;
}

echo "=== WCC security_gates ===\n\n";

// Authz
check(
    'inventory_audit requires view_inventory',
    file_has($root . '/_logi/inventory_audit.php', "require_perm('view_inventory')")
);
check(
    'ajax_get_audit_details checks view_inventory',
    file_has($root . '/_logi/ajax_get_audit_details.php', "can('view_inventory')")
);
check(
    'get_comments uses api_guard_perm view_tickets',
    file_has($root . '/api/get_comments.php', "api_guard_perm('view_tickets')")
);
check(
    'get_historical_kpis uses view_statistics',
    file_has($root . '/api/get_historical_kpis.php', "api_guard_perm('view_statistics')")
);

// Phantom permission gone from REST PR resource
$pr = (string)@file_get_contents($root . '/api/v1/resources/purchase_requests.php');
check(
    'purchase_requests has no approve_purchase_requests phantom',
    strpos($pr, 'approve_purchase_requests') === false
);
check(
    'purchase_requests uses purchase_orders table',
    strpos($pr, 'purchase_orders') !== false
);

// CSRF infrastructure
check('csrf.php has wcc_csrf_require_json', file_has($root . '/inc/csrf.php', 'function wcc_csrf_require_json'));
check('csrf.php accepts X-CSRF-Token', file_has($root . '/inc/csrf.php', 'HTTP_X_CSRF_TOKEN'));
check('head.php emits WCC_CSRF', file_has($root . '/inc/head.php', 'WCC_CSRF'));
check('submit_ticket requires CSRF', file_has($root . '/api/submit_ticket.php', 'wcc_csrf_require_json'));
check('purchase_orders POST CSRF', file_has($root . '/_logi/purchase_orders.php', 'wcc_csrf_require'));

// Identity
// Status gate may be English literal or i18n key after localization
check(
    'login checks account status',
    file_has($root . '/login.php', "acct_status")
    && (file_has($root . '/login.php', 'inactive or pending')
        || file_has($root . '/login.php', "login.inactive")
        || file_has($root . '/login.php', "'active'"))
);
check('api bootstrap has api_user_is_active', file_has($root . '/api/v1/bootstrap.php', 'function api_user_is_active'));

// Migrations CLI-only
check('migrate.php CLI guard', file_has($root . '/migrations/migrate.php', "PHP_SAPI !== 'cli'"));
check('fix_dept_constraints CLI guard', file_has($root . '/migrations/fix_dept_constraints.php', "PHP_SAPI !== 'cli'"));

// Fixed local DB credentials (no runtime credential UI)
check('db.php uses fixed root empty password', file_has($root . '/inc/db.php', "\$password = ''"));
check('app_settings has no DB credential UI', !file_has($root . '/_mgmt/app_settings.php', 'save_db_config'));

// XSS helper
check('wcc-ui.js has escapeHtml', file_has($root . '/js/wcc-ui.js', 'function escapeHtml'));

// Password reset not hard-coded "password"
$users = (string)@file_get_contents($root . '/_mgmt/users.php');
check(
    'users.php uses wcc_generate_temp_password',
    strpos($users, 'wcc_generate_temp_password') !== false
);
check(
    'users.php does not password_hash literal password',
    strpos($users, "password_hash('password'") === false
);

echo "\n=== Result: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);
