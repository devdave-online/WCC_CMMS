<?php
include __DIR__ . '/../auth.php';
require_once __DIR__ . '/../rbac.php';
require_perm('view_statistics');

// Enterprise centralized DB connection (Phase 1 fix)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

// If manually triggering cron
if (isset($_GET['run_cron'])) {
    include __DIR__ . '/../api/cron_analytics.php';
    header("Location: /_rpt/setup_vault_analytics.php?success=1");
    exit;
}

// Fetch logs
$stmt = $pdo->query("SELECT * FROM analytics_logs");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$metrics = [];
$last_updated = 'Never';
foreach($logs as $l) {
    $metrics[$l['metric_name']] = $l['metric_value'];
    $last_updated = $l['last_updated']; // assuming they all update together
}

$oee = $metrics['OEE (%)'] ?? 0;
$mttr = $metrics['MTTR (Hrs)'] ?? 0;
$mttd = $metrics['MTTD (Hrs)'] ?? 0;
$mtbf = $metrics['MTBF (Hrs)'] ?? 0;
$downtime = $metrics['Total Downtime (Hrs)'] ?? 0;
$breakdowns = $metrics['Total Breakdowns'] ?? 0;
$orders = $metrics['Total PO Orders'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>System Diagnostics & KPIs</title>
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
        .kpi-card { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid var(--panel-border); text-align: center; }
        .kpi-title { font-size: 0.9em; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .kpi-value { font-size: 2em; color: var(--text-accent); font-weight: bold; font-family: monospace; }
        
        .hero-kpi { grid-column: span 4; background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(30, 58, 138, 0.2)); border-color: var(--text-accent); display:flex; justify-content:space-around; align-items:center; }
        .hero-title { font-size: 1.2em; color: var(--text-secondary); }
        .hero-value { font-size: 4em; color: var(--text-primary); font-weight: 900; text-shadow: 0 0 20px rgba(56, 189, 248, 0.5); }
    </style>
</head>
<body><?php include __DIR__ . '/../nav.php'; ?>
    <?php require_once __DIR__ . '/../rbac.php'; ?>
    <div class="dashboard-container">
        <div class="header-flex">
            <h2>📈 Advanced Analytics & KPI Diagnostics</h2>
            <div style="display:flex; gap:15px; align-items:center;">
                <span style="color:var(--text-secondary); font-size:0.9em;">Last Calculated: <?= $last_updated ?></span>
                <a href="?run_cron=1" class="btn">🔄 Run Analytics Engine Now</a>
                <a href="/_mgmt/app_settings.php" class="nav-btn">⬅️ BACK</a>
            </div>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #22c55e; padding: 10px; border-radius: 8px; margin-top: 15px; text-align:center;">
                Analytics Engine executed successfully. KPIs updated.
            </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card hero-kpi">
                <div>
                    <div class="hero-title">Overall Equipment Effectiveness (OEE)</div>
                    <div class="hero-value"><?= number_format($oee, 2) ?>%</div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">MTTR (Mean Time To Repair)</div>
                <div class="kpi-value"><?= number_format($mttr, 2) ?> <span style="font-size:0.4em;">hrs</span></div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-title">MTTD (Mean Time To Detect)</div>
                <div class="kpi-value"><?= number_format($mttd, 2) ?> <span style="font-size:0.4em;">hrs</span></div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-title">MTBF (Mean Time Between Failures)</div>
                <div class="kpi-value"><?= number_format($mtbf, 0) ?> <span style="font-size:0.4em;">hrs</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">Total Downtime</div>
                <div class="kpi-value" style="color:#ef4444;"><?= number_format($downtime, 2) ?> <span style="font-size:0.4em;">hrs</span></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">Total Breakdowns</div>
                <div class="kpi-value"><?= number_format($breakdowns) ?></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-title">Total PO Orders</div>
                <div class="kpi-value"><?= number_format($orders) ?></div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-title">Live Engine Status</div>
                <div class="kpi-value" style="color:#22c55e; font-size:1.5em; margin-top:10px;">🟢 Online</div>
            </div>
        </div>
    </div>
</body>
</html>


