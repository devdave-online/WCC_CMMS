<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_settings');

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/audit.php';
require_once __DIR__ . '/../inc/dbadmin.php';
require_once __DIR__ . '/../inc/demo_mode.php';

// Data Administration can back up, restore and TRUNCATE the whole database, and
// it hands out dumps containing every password hash. There is no safe subset to
// expose publicly, so on a demo instance the page is closed outright.
wcc_demo_block_page('Data Administration (backup, restore and flush)');

$pdo = get_wcc_db_connection();

// ------------------------------------------------------------------
// POST handlers — every action is manage_settings-gated + CSRF-checked.
// Destructive actions (restore/flush) auto-take a full backup first and
// require a typed confirmation word. Uses Post/Redirect/Get so a refresh
// never re-fires a destructive action.
// ------------------------------------------------------------------
function wcc_da_flash($type, $msg) { $_SESSION['wcc_dataadmin_flash'] = ['type' => $type, 'msg' => $msg]; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    wcc_csrf_require($_POST['csrf'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'download_backup') {
        $r = wcc_db_backup();
        if ($r['ok']) {
            wcc_audit_log('data.backup', 'database', WCC_DB_NAME, null, ['file' => $r['filename'], 'bytes' => $r['bytes']], 'Full DB backup (download)');
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $r['filename'] . '"');
            header('Content-Length: ' . $r['bytes']);
            readfile($r['path']);
            exit;
        }
        wcc_da_flash('error', $r['error']);
        header('Location: /_mgmt/admin_backup.php'); exit;
    }

    if ($action === 'save_backup') {
        $r = wcc_db_backup();
        if ($r['ok']) {
            wcc_audit_log('data.backup', 'database', WCC_DB_NAME, null, ['file' => $r['filename']], 'Full DB snapshot to server');
            wcc_da_flash('success', 'Snapshot saved: ' . $r['filename'] . ' (' . round($r['bytes'] / 1024) . ' KB)');
        } else {
            wcc_da_flash('error', $r['error']);
        }
        header('Location: /_mgmt/admin_backup.php'); exit;
    }

    if ($action === 'restore') {
        if (($_POST['confirm_word'] ?? '') !== 'RESTORE') {
            wcc_da_flash('error', 'Type RESTORE to confirm.');
            header('Location: /_mgmt/admin_backup.php'); exit;
        }
        $src = null; $srcLabel = '';
        if (!empty($_FILES['sql_file']['name']) && ($_FILES['sql_file']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $tmp = $_FILES['sql_file']['tmp_name'];
            if (is_uploaded_file($tmp) && strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION)) === 'sql') {
                $src = $tmp; $srcLabel = 'upload: ' . basename($_FILES['sql_file']['name']);
            } else {
                wcc_da_flash('error', 'Please upload a valid .sql file.');
                header('Location: /_mgmt/admin_backup.php'); exit;
            }
        } elseif (!empty($_POST['backup_file'])) {
            $src = wcc_backup_path($_POST['backup_file']); $srcLabel = basename($_POST['backup_file']);
            if (!$src) { wcc_da_flash('error', 'Selected backup not found.'); header('Location: /_mgmt/admin_backup.php'); exit; }
        } else {
            wcc_da_flash('error', 'Upload a .sql file or pick a saved backup to restore.');
            header('Location: /_mgmt/admin_backup.php'); exit;
        }

        $pre = wcc_db_backup('pre_restore');           // mandatory undo point
        $res = wcc_db_restore($src);
        wcc_audit_log('data.restore', 'database', WCC_DB_NAME, null, ['source' => $srcLabel, 'pre_backup' => $pre['filename'] ?? null, 'ok' => $res['ok']], 'DB restore');
        if ($res['ok']) {
            wcc_da_flash('success', 'Restored from ' . $srcLabel . '. Safety snapshot: ' . ($pre['filename'] ?? '—'));
        } else {
            wcc_da_flash('error', 'Restore failed: ' . $res['error'] . ($pre['ok'] ? ' (safety snapshot ' . $pre['filename'] . ' was taken)' : ''));
        }
        header('Location: /_mgmt/admin_backup.php'); exit;
    }

    if ($action === 'flush') {
        if (($_POST['confirm_word'] ?? '') !== 'FLUSH') {
            wcc_da_flash('error', 'Type FLUSH to confirm.');
            header('Location: /_mgmt/admin_backup.php'); exit;
        }
        $tables = $_POST['tables'] ?? [];
        if (!is_array($tables) || !$tables) {
            wcc_da_flash('error', 'Select at least one table to flush.');
            header('Location: /_mgmt/admin_backup.php'); exit;
        }
        $pre = wcc_db_backup('pre_flush');             // mandatory undo point
        $res = wcc_db_flush($tables);
        $cleared = 0; $failed = [];
        foreach ($res as $name => $r) {
            if (!empty($r['ok'])) $cleared += (int)($r['cleared'] ?? 0);
            else $failed[] = $name;
        }
        wcc_audit_log('data.flush', 'database', WCC_DB_NAME, null, ['tables' => array_keys($res), 'rows_cleared' => $cleared, 'pre_backup' => $pre['filename'] ?? null], 'DB flush');
        $msg = 'Flushed ' . (count($res) - count($failed)) . ' table(s), ' . $cleared . ' rows cleared. Safety snapshot: ' . ($pre['filename'] ?? '—');
        if ($failed) $msg .= ' — failed: ' . implode(', ', $failed);
        wcc_da_flash($failed ? 'warning' : 'success', $msg);
        header('Location: /_mgmt/admin_backup.php'); exit;
    }
}

$groups  = wcc_db_tables();
$backups = wcc_list_backups();
$flash   = $_SESSION['wcc_dataadmin_flash'] ?? null;
unset($_SESSION['wcc_dataadmin_flash']);
$csrf    = wcc_csrf_token();

$groupMeta = [
    'transactional' => ['label' => '📊 Transactional data',  'tone' => 'ok',      'note' => 'Day-to-day records. Clearing these is the usual "start fresh" case.'],
    'reference'     => ['label' => '🏭 Reference / setup data', 'tone' => 'warn',  'note' => 'Master data (parts, equipment, vendors, lines). Clearing means re-setting up the plant.'],
    'config'        => ['label' => '⚙️ Accounts &amp; config',  'tone' => 'danger', 'note' => 'Users, roles, settings. Clearing breaks login &amp; RBAC — a default admin is re-seeded on next login.'],
    'system'        => ['label' => '🧬 System',                 'tone' => 'danger', 'note' => 'Migration bookkeeping. Do not clear unless you know exactly why.'],
];

$page_title = 'Data Administration';
require_once __DIR__ . '/../inc/head.php';
?>
<style>
    .da-grid { display: grid; grid-template-columns: 1fr; gap: var(--space-5); max-width: 920px; }
    .da-card { background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: var(--radius-lg); padding: var(--space-5); }
    .da-card h3 { margin: 0 0 6px; color: var(--text-accent); }
    .da-card p.hint { color: var(--text-secondary); font-size: var(--fs-sm); margin: 0 0 16px; }
    .da-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .da-danger { border-color: var(--danger-border); }
    .da-group { border: 1px solid var(--panel-border); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 12px; }
    .da-group.warn { border-color: var(--warning-border); background: var(--warning-bg); }
    .da-group.danger { border-color: var(--danger-border); background: var(--danger-bg); }
    .da-group-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
    .da-group-head strong { color: var(--text-primary); }
    .da-group-note { font-size: var(--fs-xs); color: var(--text-secondary); margin-bottom: 8px; }
    .da-tables { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 4px 16px; }
    .da-tbl { display: flex; align-items: center; gap: 8px; font-size: 0.9em; padding: 3px 0; }
    .da-tbl code { color: var(--text-primary); }
    .da-tbl .rows { margin-left: auto; color: var(--text-muted); font-size: var(--fs-xs); }
    .da-confirm { display: flex; align-items: center; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
    .da-confirm input[type=text] { width: 160px; font-family: monospace; letter-spacing: 1px; text-transform: uppercase; }
    .da-backuplist { max-height: 140px; overflow-y: auto; font-size: var(--fs-sm); border: 1px solid var(--panel-border); border-radius: var(--radius-sm); padding: 8px 10px; margin-top: 8px; }
    .da-backuplist div { color: var(--text-secondary); padding: 2px 0; }
</style>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <div class="page-header" style="margin-bottom:10px;">
        <h1>🗄️ Data Administration</h1>
        <?php if (can('manage_settings')): ?>
        <a href="/_mgmt/admin_panel.php" class="nav-btn" style="white-space:nowrap;">← Return to Admin Panel</a>
        <?php endif; ?>
    </div>
    <p style="color:var(--text-secondary); max-width:920px;">
        Full-database backup, restore, and selective flush. Restore and flush both take an automatic safety backup first and require a typed confirmation. Backups are written to <code>/backups</code> (blocked from direct web download).
    </p>

    <div class="da-grid">

        <!-- BACKUP -->
        <div class="da-card">
            <h3>💾 Backup</h3>
            <p class="hint">Complete <code>mysqldump</code> of every table (not a partial list). Download a copy and/or save a timestamped snapshot on the server.</p>
            <div class="da-actions">
                <form method="POST">
                    <input type="hidden" name="action" value="download_backup">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button type="submit" class="pill-btn pill-success">⬇️ Download full backup</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="save_backup">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button type="submit" class="pill-btn pill-info">🗄️ Save snapshot to server</button>
                </form>
            </div>
            <?php if ($backups): ?>
            <div class="da-backuplist">
                <?php foreach (array_slice($backups, 0, 12) as $b): ?>
                    <div>📄 <?= htmlspecialchars($b['name']) ?> — <?= round($b['bytes'] / 1024) ?> KB · <?= date('M j, H:i', $b['mtime']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- RESTORE -->
        <div class="da-card da-danger">
            <h3>♻️ Restore</h3>
            <p class="hint">Replace current data from a backup. A full safety snapshot is taken automatically before restoring.</p>
            <form method="POST" enctype="multipart/form-data" id="restoreForm">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <div class="field">
                    <label for="da_sql">Upload a <code>.sql</code> backup</label>
                    <input type="file" id="da_sql" name="sql_file" accept=".sql">
                </div>
                <div class="field">
                    <label for="da_pick">— or restore a saved snapshot —</label>
                    <select id="da_pick" name="backup_file" style="width:100%; max-width:520px;">
                        <option value="">(choose a saved backup)</option>
                        <?php foreach ($backups as $b): ?>
                            <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?> (<?= round($b['bytes'] / 1024) ?> KB)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="da-confirm">
                    <label for="restoreConfirm" style="margin:0;">Type <strong>RESTORE</strong>:</label>
                    <input type="text" id="restoreConfirm" name="confirm_word" autocomplete="off" oninput="armRestore()">
                    <button type="button" id="restoreBtn" class="pill-btn pill-danger" disabled onclick="doRestore()">♻️ Restore now</button>
                </div>
            </form>
        </div>

        <!-- FLUSH -->
        <div class="da-card da-danger">
            <h3>🧹 Flush tables</h3>
            <p class="hint">Permanently empty selected tables (FK-safe <code>TRUNCATE</code>). A full safety snapshot is taken automatically first.</p>
            <form method="POST" id="flushForm">
                <input type="hidden" name="action" value="flush">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">

                <div class="da-actions" style="margin-bottom:12px;">
                    <button type="button" class="pill-btn pill-warning" onclick="factoryReset()">↺ Factory Reset (all transactional)</button>
                    <button type="button" class="pill-btn" onclick="clearFlushSelection()">Clear selection</button>
                </div>

                <?php foreach (['transactional','reference','config','system'] as $gk): $rows = $groups[$gk]; if (!$rows) continue; $m = $groupMeta[$gk]; ?>
                <div class="da-group <?= $m['tone'] === 'ok' ? '' : $m['tone'] ?>">
                    <div class="da-group-head">
                        <strong><?= $m['label'] ?></strong>
                        <label style="font-size:var(--fs-xs); color:var(--text-secondary); display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" onchange="toggleGroup(this, '<?= $gk ?>')"> select all
                        </label>
                    </div>
                    <div class="da-group-note"><?= $m['tone'] === 'danger' ? '⚠ ' : '' ?><?= $m['note'] ?></div>
                    <div class="da-tables">
                        <?php foreach ($rows as $t): ?>
                        <label class="da-tbl">
                            <input type="checkbox" class="flush-cb grp-<?= $gk ?>" name="tables[]" value="<?= htmlspecialchars($t['name']) ?>">
                            <code><?= htmlspecialchars($t['name']) ?></code>
                            <span class="rows"><?= number_format($t['rows']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="da-confirm">
                    <span style="color:var(--text-secondary); font-size:var(--fs-sm);"><span id="flushCount">0</span> table(s) selected.</span>
                    <label for="flushConfirm" style="margin:0;">Type <strong>FLUSH</strong>:</label>
                    <input type="text" id="flushConfirm" name="confirm_word" autocomplete="off" oninput="armFlush()">
                    <button type="button" id="flushBtn" class="pill-btn pill-danger" disabled onclick="doFlush()">🧹 Flush selected</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    <?php if ($flash): ?>
    document.addEventListener('DOMContentLoaded', () => { if (typeof showToast === 'function') showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type']) ?>, 7000); });
    <?php endif; ?>

    // ---- Restore arming ----
    function armRestore() {
        document.getElementById('restoreBtn').disabled = (document.getElementById('restoreConfirm').value.toUpperCase() !== 'RESTORE');
    }
    function doRestore() {
        const f = document.getElementById('restoreForm');
        const hasUpload = document.getElementById('da_sql').files.length > 0;
        const hasPick = document.getElementById('da_pick').value !== '';
        if (!hasUpload && !hasPick) { showToast('Choose a .sql upload or pick a saved backup.', 'warning'); return; }
        openWccConfirm('Restore will REPLACE current data with this backup. A full safety snapshot is taken first. Proceed?', () => f.submit(), 'Restore now');
    }

    // ---- Flush selection + arming ----
    function updateFlushCount() {
        document.getElementById('flushCount').textContent = document.querySelectorAll('.flush-cb:checked').length;
    }
    function toggleGroup(master, gk) {
        document.querySelectorAll('.grp-' + gk).forEach(cb => cb.checked = master.checked);
        updateFlushCount();
    }
    function factoryReset() {
        document.querySelectorAll('.flush-cb').forEach(cb => cb.checked = false);
        document.querySelectorAll('.grp-transactional').forEach(cb => cb.checked = true);
        updateFlushCount();
        showToast('Selected all transactional tables. Review, then type FLUSH.', 'info');
    }
    function clearFlushSelection() {
        document.querySelectorAll('.flush-cb').forEach(cb => cb.checked = false);
        document.querySelectorAll('#flushForm input[type=checkbox]').forEach(cb => cb.checked = false);
        updateFlushCount();
    }
    function armFlush() {
        document.getElementById('flushBtn').disabled = (document.getElementById('flushConfirm').value.toUpperCase() !== 'FLUSH');
    }
    function doFlush() {
        const n = document.querySelectorAll('.flush-cb:checked').length;
        if (!n) { showToast('Select at least one table to flush.', 'warning'); return; }
        openWccConfirm('Permanently clear ' + n + ' selected table(s)? This cannot be undone except from the safety backup taken first.', () => document.getElementById('flushForm').submit(), 'Flush ' + n + ' table(s)');
    }
    document.addEventListener('change', (e) => { if (e.target.classList && e.target.classList.contains('flush-cb')) updateFlushCount(); });
</script>
</body>
</html>
