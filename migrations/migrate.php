<?php
/**
 * WCC CMMS - Migration Runner (Phase 5)
 *
 * Simple, safe, versioned SQL migrations using a tracking table.
 *
 * Usage:
 *   php migrations/migrate.php                 # list status (applied vs pending)
 *   php migrations/migrate.php --dry           # show what would be applied (no changes)
 *   php migrations/migrate.php --apply         # apply all pending migrations
 *
 * Features:
 * - Uses centralized get_wcc_db_connection() from inc/db.php
 * - Ensures schema_migrations tracking table exists (bootstrap)
 * - Applies .sql files in lexical order (0001_..., 0002_...)
 * - Records each successful filename + timestamp
 * - Idempotent: skips already-applied files
 * - --dry mode for safety preview
 *
 * Safety notes:
 * - Migrations should be idempotent where possible (IF NOT EXISTS, etc.)
 * - Always backup DB before running on important data.
 * - DDL statements (ALTER/CREATE) in MySQL often auto-commit.
 * - Run with --dry first.
 */

// Defense-in-depth: never run over HTTP even if .htaccess is misconfigured.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden: migrations/migrate.php is CLI only.\n");
}

require_once __DIR__ . '/../inc/db.php';

$pdo = get_wcc_db_connection();

$dryRun   = in_array('--dry', $argv ?? [], true);
$doApply  = in_array('--apply', $argv ?? [], true);
$showHelp = in_array('--help', $argv ?? [], true) || in_array('-h', $argv ?? [], true);

if ($showHelp) {
    echo "WCC CMMS Migration Runner\n\n";
    echo "Commands:\n";
    echo "  php migrations/migrate.php           List applied / pending\n";
    echo "  php migrations/migrate.php --dry     Preview pending (no execution)\n";
    echo "  php migrations/migrate.php --apply   Apply all pending migrations\n\n";
    echo "Migrations are discovered from *.sql files sorted lexicographically.\n";
    exit(0);
}

echo "WCC CMMS Migration Runner\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Mode: " . ($dryRun ? 'DRY RUN (preview only)' : ($doApply ? 'APPLY PENDING' : 'STATUS (list only)')) . "\n\n";

// ------------------------------------------------------------------
// 1. Bootstrap: Ensure tracking table exists (so we can apply 0001)
//    This is the only inline DDL; everything else comes from .sql files.
// ------------------------------------------------------------------
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id`          INT(11)      NOT NULL AUTO_INCREMENT,
            `filename`    VARCHAR(255) NOT NULL,
            `applied_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_filename` (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
} catch (PDOException $e) {
    echo "ERROR: Failed to ensure schema_migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// ------------------------------------------------------------------
// 2. Discover all .sql migrations (sorted)
// ------------------------------------------------------------------
$migrationsDir = __DIR__;
$allFiles = glob($migrationsDir . '/*.sql');
sort($allFiles);

if (empty($allFiles)) {
    echo "No .sql migration files found in migrations/.\n";
    exit(0);
}

// ------------------------------------------------------------------
// 3. Load already-applied filenames
// ------------------------------------------------------------------
$applied = [];
try {
    $stmt = $pdo->query("SELECT filename FROM schema_migrations ORDER BY filename");
    $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "ERROR: Could not read schema_migrations: " . $e->getMessage() . "\n";
    exit(1);
}

$appliedSet = array_flip($applied);

// ------------------------------------------------------------------
// 4. Compute pending
// ------------------------------------------------------------------
$pending = [];
foreach ($allFiles as $file) {
    $basename = basename($file);
    if (!isset($appliedSet[$basename])) {
        $pending[] = ['path' => $file, 'name' => $basename];
    }
}

// ------------------------------------------------------------------
// 5. Report status
// ------------------------------------------------------------------
echo "Total migrations found: " . count($allFiles) . "\n";
echo "Already applied:        " . count($applied) . "\n";
echo "Pending:                " . count($pending) . "\n\n";

echo "=== APPLIED ===\n";
if (empty($applied)) {
    echo "  (none)\n";
} else {
    foreach ($applied as $f) {
        echo "  ✓ $f\n";
    }
}
echo "\n";

echo "=== PENDING ===\n";
if (empty($pending)) {
    echo "  (none - schema is up to date)\n\n";
} else {
    foreach ($pending as $p) {
        echo "  → " . $p['name'] . "\n";
    }
    echo "\n";
}

if (!$doApply && !$dryRun) {
    echo "Tip: Use --dry to preview or --apply to execute pending migrations.\n";
    echo "     Always review the .sql files first.\n\n";
    exit(0);
}

// ------------------------------------------------------------------
// 6. Apply (or dry-run) pending migrations
// ------------------------------------------------------------------
if (empty($pending)) {
    echo "Nothing to apply.\n";
    exit(0);
}

if ($dryRun) {
    echo "DRY RUN: The following would be executed:\n\n";
    foreach ($pending as $p) {
        echo "  [DRY] Would apply: " . $p['name'] . "\n";
    }
    echo "\nNo changes were made.\n";
    exit(0);
}

// Real apply mode
echo "APPLYING PENDING MIGRATIONS...\n\n";

$successCount = 0;
$failCount = 0;

foreach ($pending as $p) {
    $name = $p['name'];
    $path = $p['path'];
    $sql = file_get_contents($path);

    if ($sql === false) {
        echo "  ✗ ERROR reading $name\n";
        $failCount++;
        continue;
    }

    echo "  → Applying $name ... ";

    try {
        // Execute the migration (simple exec is sufficient for our DDL-heavy files)
        $pdo->exec($sql);

        // Record as applied (idempotent via UNIQUE)
        $ins = $pdo->prepare("INSERT IGNORE INTO schema_migrations (filename) VALUES (?)");
        $ins->execute([$name]);

        echo "OK\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "FAILED\n";
        echo "    Error: " . $e->getMessage() . "\n";
        $failCount++;

        // Continue to next; do not abort the whole batch unless critical.
        // In future we could add --stop-on-error flag.
    }
}

echo "\n";
echo "Summary: $successCount succeeded, $failCount failed.\n";

if ($failCount > 0) {
    echo "Some migrations failed. Review errors above and the .sql files.\n";
    exit(1);
}

echo "All pending migrations applied successfully.\n";
echo "Run again to confirm status.\n";
