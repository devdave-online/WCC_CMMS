<?php
/**
 * WCC CMMS — RBAC merge regression (CLI only).
 *
 *   C:\xampp\php\php.exe tests\rbac_merge.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/rbac.php';

$fail = 0;
$pass = 0;

function assert_true(string $label, bool $cond): void
{
    global $fail, $pass;
    if ($cond) {
        $pass++;
        echo "  OK  $label\n";
    } else {
        $fail++;
        echo " FAIL $label\n";
    }
}

echo "=== WCC rbac_merge ===\n\n";

// Full key set
$admin = wcc_get_permissions(4, null);
assert_true('Admin resolves 22 registry keys', count($admin) === count(PERMISSION_LABELS));
assert_true('Admin has fulfill_purchase_orders key', array_key_exists('fulfill_purchase_orders', $admin));
assert_true('Admin has delete_users key', array_key_exists('delete_users', $admin));

// Custom Viewer empty role JSON still allows explicit grants
$viewer = wcc_get_permissions(5, json_encode([
    'view_tickets' => true,
    'view_history' => true,
    'view_inventory' => true,
]));
assert_true('Viewer override view_tickets=true', !empty($viewer['view_tickets']));
assert_true('Viewer override view_inventory=true', !empty($viewer['view_inventory']));
assert_true('Viewer still false for manage_users', empty($viewer['manage_users']));
assert_true('Viewer has full key set', count($viewer) === count(PERMISSION_LABELS));

// Unknown keys in override JSON ignored
$junk = wcc_get_permissions(1, json_encode(['not_a_real_perm' => true, 'view_tickets' => false]));
assert_true('Unknown override keys ignored', !array_key_exists('not_a_real_perm', $junk));
assert_true('Operator can still resolve view_tickets key', array_key_exists('view_tickets', $junk));

// Override can turn off a default-true
$op = wcc_get_permissions(1, json_encode(['view_tickets' => false]));
assert_true('Override can deny view_tickets for Operator', empty($op['view_tickets']));

echo "\n=== Result: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);
