<?php
/**
 * WCC CMMS — Data Administration helpers (backup / restore / flush).
 *
 * Shells out to the XAMPP MariaDB client tools (exec/proc_open are enabled here;
 * the app already runs C:\xampp binaries by absolute path, see lint_all.php).
 * These are powerful, destructive operations — callers MUST gate on
 * require_perm('manage_settings') + CSRF, and every destructive path auto-takes
 * a full backup first.
 */

require_once __DIR__ . '/db.php';

const WCC_MYSQL_BIN  = 'C:\\xampp\\mysql\\bin\\';
const WCC_DB_NAME    = 'workshop_db';
const WCC_DB_USER    = 'root';
const WCC_BACKUP_DIR = __DIR__ . '/../backups';

/** Full mysqldump of the whole database into backups/. Returns [ok,path,filename,bytes,error]. */
function wcc_db_backup(?string $label = null): array
{
    $dir = WCC_BACKUP_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0777, true);

    $safe = $label ? '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $label) : '';
    $file = $dir . '/wcc_backup_' . date('Ymd_His') . $safe . '.sql';

    // --result-file writes stdout to disk (avoids shell-redirection quoting on Windows).
    // No -p: the local MariaDB root has an empty password.
    $cmd = '"' . WCC_MYSQL_BIN . 'mysqldump.exe"'
         . ' --user=' . WCC_DB_USER . ' --host=localhost'
         . ' --single-transaction --routines --events --add-drop-table'
         . ' --result-file="' . $file . '" ' . WCC_DB_NAME . ' 2>&1';

    $out = []; $rc = 0;
    @exec($cmd, $out, $rc);

    $ok = ($rc === 0 && is_file($file) && filesize($file) > 0);
    return [
        'ok'       => $ok,
        'path'     => $ok ? $file : null,
        'filename' => $ok ? basename($file) : null,
        'bytes'    => $ok ? (int)filesize($file) : 0,
        'error'    => $ok ? null : ('mysqldump failed (rc=' . $rc . '): ' . trim(implode(' ', $out))),
    ];
}

/** Restore a .sql dump by streaming it to the mysql client's stdin. Returns [ok,error]. */
function wcc_db_restore(string $sqlPath): array
{
    if (!is_file($sqlPath)) return ['ok' => false, 'error' => 'Backup file not found.'];

    $cmd = '"' . WCC_MYSQL_BIN . 'mysql.exe"'
         . ' --user=' . WCC_DB_USER . ' --host=localhost ' . WCC_DB_NAME;

    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open($cmd, $desc, $pipes);
    if (!is_resource($proc)) return ['ok' => false, 'error' => 'Could not start the mysql client.'];

    // Stream the dump to stdin in 1 MB chunks — no full-file memory load, and no
    // dependence on PHP upload limits when restoring an on-disk backup.
    $fh = fopen($sqlPath, 'rb');
    if ($fh) {
        while (!feof($fh)) { fwrite($pipes[0], fread($fh, 1 << 20)); }
        fclose($fh);
    }
    fclose($pipes[0]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $rc = proc_close($proc);

    $ok = ($rc === 0);
    return ['ok' => $ok, 'error' => $ok ? null : ('Restore failed (rc=' . $rc . '): ' . trim($stderr))];
}

/**
 * Live base tables grouped for the flush UI, each with an exact row count.
 * Groups: transactional (safe-ish), reference, config, system (danger).
 */
function wcc_db_tables(): array
{
    $pdo = get_wcc_db_connection();
    $names = $pdo->query("SELECT table_name FROM information_schema.tables
                           WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
                           ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

    // Transactional = day-to-day records (safe-ish to clear).
    $transactional = ['active_tickets','ticket_actions','ticket_comments','ticket_attachments','ticket_parts_consumed',
                      'work_orders','purchase_orders','po_items','po_status_logs','po_documents','departments',
                      'inventory_ledger','department_budget_logs','notifications','notification_broadcast',
                      'audit_log','system_audit_logs','analytics_logs','pm_schedules','scheduled_reports','rate_limit'];
    // Reference = master/setup data (clearing means re-setting up the plant).
    $reference = ['inventory_parts','equipment','production_lines','workshops','vendors_suppliers','uuid_rules',
                  'pm_checklists','pm_checklist_items','equipment_bom','equipment_documents','team_directory','eam_directory'];
    // Config = accounts / RBAC / settings (clearing breaks login & app config).
    $config = ['users','role_definitions','app_settings','user_registration_config','skill_automation_config','user_skills'];
    // System = migration bookkeeping.
    $system = ['schema_migrations'];

    $groups = ['transactional' => [], 'reference' => [], 'config' => [], 'system' => []];
    foreach ($names as $t) {
        try { $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
        catch (Throwable $e) { $count = 0; }
        if (in_array($t, $transactional, true))      $g = 'transactional';
        elseif (in_array($t, $reference, true))      $g = 'reference';
        elseif (in_array($t, $config, true))         $g = 'config';
        elseif (in_array($t, $system, true))         $g = 'system';
        else                                         $g = 'reference'; // unknown/new → reference (danger side)
        $groups[$g][] = ['name' => $t, 'rows' => $count];
    }
    return $groups;
}

/** FK-safe TRUNCATE of validated tables. Returns [table => [ok, cleared|error]]. */
function wcc_db_flush(array $tables): array
{
    $pdo = get_wcc_db_connection();
    $valid = $pdo->query("SELECT table_name FROM information_schema.tables
                           WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    $map = [];
    foreach ($valid as $v) $map[strtolower($v)] = $v; // exact-case lookup, anti-injection whitelist

    $results = [];
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach ($tables as $t) {
        $key = strtolower((string)$t);
        if (!isset($map[$key])) { $results[(string)$t] = ['ok' => false, 'error' => 'unknown table']; continue; }
        $name = $map[$key];
        try {
            $before = (int)$pdo->query("SELECT COUNT(*) FROM `$name`")->fetchColumn();
            $pdo->exec("TRUNCATE TABLE `$name`");
            $results[$name] = ['ok' => true, 'cleared' => $before];
        } catch (Throwable $e) {
            $results[$name] = ['ok' => false, 'error' => $e->getMessage()];
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    return $results;
}

/** Saved backups in backups/, newest first. */
function wcc_list_backups(): array
{
    if (!is_dir(WCC_BACKUP_DIR)) return [];
    $list = [];
    foreach (glob(WCC_BACKUP_DIR . '/*.sql') as $f) {
        $list[] = ['name' => basename($f), 'bytes' => (int)filesize($f), 'mtime' => (int)filemtime($f)];
    }
    usort($list, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $list;
}

/** Resolve a chosen backup filename to a real path inside backups/ (traversal-safe). */
function wcc_backup_path(string $filename): ?string
{
    $base = basename($filename); // strip any directory component
    if (substr($base, -4) !== '.sql') return null;
    $p = WCC_BACKUP_DIR . '/' . $base;
    return is_file($p) ? $p : null;
}
