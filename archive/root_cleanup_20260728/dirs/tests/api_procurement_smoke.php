<?php
/**
 * WCC CMMS — procurement data model + API resource smoke (CLI only).
 *
 *   C:\xampp\php\php.exe tests\api_procurement_smoke.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__);
require_once $root . '/inc/db.php';
require_once $root . '/inc/procurement.php';

$fail = 0;
$pass = 0;

function ok(string $label, bool $cond, string $detail = ''): void
{
    global $fail, $pass;
    if ($cond) {
        $pass++;
        echo "  OK  $label\n";
    } else {
        $fail++;
        echo " FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

echo "=== WCC api_procurement_smoke ===\n\n";

$pdo = get_wcc_db_connection();

// No phantom table
$chk = $pdo->query("SHOW TABLES LIKE 'purchase_requests'")->fetch();
ok('No purchase_requests table (model is purchase_orders)', $chk === false);

// purchase_orders exists
$poTable = $pdo->query("SHOW TABLES LIKE 'purchase_orders'")->fetch();
ok('purchase_orders table exists', $poTable !== false);

$count = (int)$pdo->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn();
ok('Can count purchase_orders', $count >= 0, "count=$count");

// Procurement route helper returns expected shape
$route = wcc_procurement_route($pdo, 10.0);
ok('wcc_procurement_route has status', isset($route['status']));
ok('wcc_procurement_route has approval_level', isset($route['approval_level']));
ok('wcc_procurement_route has auto_approved', array_key_exists('auto_approved', $route));

// Resource files aligned
$prSrc = file_get_contents($root . '/api/v1/resources/purchase_requests.php');
ok('PR resource documents purchase_orders model', strpos($prSrc, 'purchase_orders') !== false);
ok('PR resource has no phantom table query FROM purchase_requests', !preg_match('/FROM\s+purchase_requests\b/i', $prSrc));
ok('PR resource uses approve_purchase_orders', strpos($prSrc, 'approve_purchase_orders') !== false);

$poSrc = file_get_contents($root . '/api/v1/resources/purchase_orders.php');
ok('PO resource checks fulfill_purchase_orders', strpos($poSrc, 'fulfill_purchase_orders') !== false);

echo "\n=== Result: $pass passed, $fail failed ===\n";
exit($fail > 0 ? 1 : 0);
