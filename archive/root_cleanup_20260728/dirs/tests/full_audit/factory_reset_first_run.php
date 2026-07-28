<?php
/**
 * Factory reset to first-run state (CLI only).
 * - Optional backup
 * - TRUNCATE all tables except schema_migrations
 * - Next GET /login.php seeds admin / password
 *
 *   C:\xampp\php\php.exe tests\full_audit\factory_reset_first_run.php
 *   C:\xampp\php\php.exe tests\full_audit\factory_reset_first_run.php --no-backup
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$root = dirname(__DIR__, 2);
require_once $root . '/inc/db.php';
require_once $root . '/inc/dbadmin.php';

$doBackup = !in_array('--no-backup', $argv ?? [], true);

echo "=== WCC factory reset → first-run ===\n";

if ($doBackup) {
    echo "Backup…\n";
    $b = wcc_db_backup('pre_factory_reset');
    if (!$b['ok']) {
        echo "WARN backup failed: " . ($b['error'] ?? '?') . " — continuing flush\n";
    } else {
        echo "  saved {$b['filename']} ({$b['bytes']} bytes)\n";
    }
}

$pdo = get_wcc_db_connection();
$tables = $pdo->query(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
     ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$toFlush = [];
foreach ($tables as $t) {
    if (strtolower($t) === 'schema_migrations') {
        continue; // keep migration bookkeeping
    }
    $toFlush[] = $t;
}

echo 'Flushing ' . count($toFlush) . " tables (keeping schema_migrations)…\n";
$results = wcc_db_flush($toFlush);
$ok = 0;
$fail = 0;
foreach ($results as $name => $r) {
    if (!empty($r['ok'])) {
        $ok++;
        echo "  OK  $name cleared " . ($r['cleared'] ?? 0) . "\n";
    } else {
        $fail++;
        echo "  FAIL $name — " . ($r['error'] ?? '?') . "\n";
    }
}

$users = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$tickets = (int)$pdo->query('SELECT COUNT(*) FROM active_tickets')->fetchColumn();
$equip = (int)$pdo->query('SELECT COUNT(*) FROM equipment')->fetchColumn();

echo "\nPost-flush: users=$users tickets=$tickets equipment=$equip\n";
echo "flush_ok=$ok flush_fail=$fail\n";
echo "Next: open /login.php → seeds admin / password → must change password on login.\n";
exit($fail > 0 ? 1 : 0);
