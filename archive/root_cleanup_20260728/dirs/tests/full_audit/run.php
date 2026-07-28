<?php
/**
 * WCC CMMS — Full functional audit orchestrator (CLI only).
 *
 *   C:\xampp\php\php.exe tests\full_audit\run.php
 *   C:\xampp\php\php.exe tests\full_audit\run.php --mutate
 *   C:\xampp\php\php.exe tests\full_audit\run.php --suite=02_http_smoke
 *
 * Does not modify application source. Mutations only with --mutate.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$root = dirname(__DIR__, 2);
$auditRoot = __DIR__;

require_once $auditRoot . '/lib/HttpClient.php';
require_once $auditRoot . '/lib/Report.php';
require_once $auditRoot . '/lib/DbProbe.php';

$config = require $auditRoot . '/config.php';
if (is_file($auditRoot . '/config.local.php')) {
    $config = array_merge($config, require $auditRoot . '/config.local.php');
}

$mutate = in_array('--mutate', $argv, true);
$suiteFilter = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--suite=')) {
        $suiteFilter = substr($a, 8);
    }
}

$phpBin = 'php';
foreach ([
    'C:\\xampp\\php\\php.exe',
    'C:/xampp/php/php.exe',
    PHP_BINARY,
] as $cand) {
    if ($cand && is_file($cand)) {
        $phpBin = $cand;
        break;
    }
}

echo "=== WCC Full Audit ===\n";
echo "Base:   {$config['base_url']}\n";
echo "User:   {$config['admin_user']}\n";
echo "Mutate: " . ($mutate ? 'YES' : 'no') . "\n";
echo "PHP:    $phpBin\n\n";

$report = new WccAuditReport();
$http = new WccAuditHttpClient($config['base_url'], (int)$config['timeout']);
$db = new WccAuditDbProbe();
$registry = require $auditRoot . '/registry.php';

$ensureLogin = static function (bool $force = false) use ($http, $config, $report): bool {
    // Always re-login between suites: visiting /login.php mid-run can drop context,
    // and isAuthenticated alone proved flaky across long request chains.
    $user = $config['admin_user'];
    $pass = $config['admin_pass'];
    if (!$force && $http->isAuthenticated()) {
        // Double-check CSRF on tooling ledger (authoritative app page)
        $p = $http->get('/_eam/toolings.php', true);
        if ($http->extractCsrf($p['body']) && str_contains($p['body'], 'ledgerTable')) {
            return true;
        }
    }
    if ($http->login($user, $pass)) {
        $p = $http->get('/_eam/toolings.php', true);
        if ($http->extractCsrf($p['body']) || str_contains($p['body'], 'ledgerTable') || str_contains($p['body'], 'Tooling')) {
            return true;
        }
    }
    if ($http->login('admin', 'password') || $http->login('admin', 'Demo2026!')) {
        return true;
    }
    $report->fail('Runner', 'relogin', "failed for $user");
    return false;
};

$ctx = [
    'root' => $root,
    'config' => $config,
    'http' => $http,
    'db' => $db,
    'registry' => $registry,
    'mutate' => $mutate,
    'php_bin' => $phpBin,
    'ensure_login' => $ensureLogin,
];

$suites = [
    '01_static_gates',
    '02_http_smoke',
    '03_search_loop',
    '04_tickets_loop',
    '05_assets_loop',
    '06_inventory_loop',
    '07_procurement_loop',
    '08_api_rest_loop',
];
// Deep write-path probe always when --mutate (upload, BOM, symbology, cleanup)
if ($mutate) {
    $suites[] = '09_deep_probe';
}

// Auth once before HTTP suites
echo "--- Bootstrap login ---\n";
if ($ensureLogin()) {
    $report->ok('Runner', 'bootstrap_login');
} else {
    $report->fail('Runner', 'bootstrap_login', 'cannot authenticate — HTTP suites will degrade');
}
echo "\n";

foreach ($suites as $name) {
    if ($suiteFilter && $suiteFilter !== $name && $suiteFilter !== preg_replace('/^\d+_/', '', $name)) {
        continue;
    }
    $file = $auditRoot . '/suites/' . $name . '.php';
    if (!is_file($file)) {
        $report->fail('Runner', $name, 'suite file missing');
        continue;
    }
    echo "--- Suite: $name ---\n";
    // Fresh session before each HTTP suite (except static)
    if ($name !== '01_static_gates') {
        $ensureLogin(true);
    }
    $fn = require $file;
    if (!is_callable($fn)) {
        $report->fail('Runner', $name, 'suite did not return callable');
        continue;
    }
    try {
        $fn($report, $ctx);
    } catch (Throwable $e) {
        $report->fail('Runner', $name . '_exception', $e->getMessage());
    }
    echo "\n";
}

// Also run existing CLI suites as section "Legacy"
echo "--- Suite: legacy_security_gates ---\n";
$legacy = [];
$code = 0;
exec(escapeshellarg($phpBin) . ' ' . escapeshellarg($root . '/tests/security_gates.php') . ' 2>&1', $legacy, $code);
if ($code === 0) {
    $report->ok('Legacy', 'security_gates', 'exit 0');
} else {
    $report->fail('Legacy', 'security_gates', implode("\n", array_slice($legacy, 0, 5)));
}
echo implode("\n", array_slice($legacy, 0, 30)) . "\n\n";

$stamp = date('Ymd_His');
$reportDir = $auditRoot . '/reports';
$md = $reportDir . '/audit_' . $stamp . '.md';
$js = $reportDir . '/audit_' . $stamp . '.json';
$report->writeMarkdown($md);
$report->writeJson($js);

echo "=== Done ===\n";
echo "Failures: " . $report->failCount() . "\n";
echo "Report:   $md\n";

exit($report->failCount() > 0 ? 1 : 0);
