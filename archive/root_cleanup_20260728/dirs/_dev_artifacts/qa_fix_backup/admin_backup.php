<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('manage_settings');

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        $tables = ['users', 'equipment', 'active_tickets', 'ticket_actions', 'work_orders', 'inventory_parts', 'purchase_orders', 'purchase_requests', 'vendors_suppliers', 'production_lines', 'workshops'];
        
        $sql = "-- WCC CMMS Backup - " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Get create table
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $create = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($create) {
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $create['Create Table'] . ";\n\n";
            }
            
            // Get data
            $dataStmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                $cols = array_keys($row);
                $vals = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($row));
                
                $sql .= "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        $filename = 'wcc_backup_' . date('Ymd_His') . '.sql';
        
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $sql;
        exit;
        
    } catch (Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}
?>
<?php
$page_title = 'Database Backup';
require_once __DIR__ . '/../inc/head.php';
?>
    <link rel="stylesheet" href="/css/global.css">
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <h1>🗄️ Database Backup</h1>
    
    <div style="max-width: 700px; margin: 20px 0;">
        <p>This tool creates a SQL dump of the main WCC tables. You can use it to restore the system later.</p>
        
        <div style="background: var(--panel-bg); border: 1px solid var(--panel-border); padding: 20px; border-radius: 8px; margin: 20px 0;">
            <strong>Important:</strong>
            <ul style="margin-top: 10px;">
                <li>This is a basic backup of core tables only.</li>
                <li>For production, set up automated mysqldump via cron.</li>
                <li>Store backups securely and test restores regularly.</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div style="color: #ef4444; margin: 15px 0;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <button type="submit" name="create_backup" class="nav-btn primary" style="padding: 12px 24px; font-size: 1.1em;">
                ⬇️ Download Full SQL Backup Now
            </button>
        </form>
        
        <p style="margin-top: 30px; font-size: 0.9em; color: var(--text-secondary);">
            Tip: You can also run this from command line or schedule it externally for better reliability.
        </p>
    </div>
</div>
</body>
</html>
