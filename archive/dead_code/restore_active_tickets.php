<?php
$file = 'c:\xampp\htdocs\active_tickets.php';
$content = file_get_contents($file);

$target = <<<EOD
<body><?php include 'nav.php'; ?>


                <th>Equipment Details</th>
EOD;

$replacement = <<<EOD
<body><?php include 'nav.php'; ?>

<div class="dashboard-container">
    <div class="header-flex">
        <h2>Active Tickets</h2>
        <div style="display:flex; gap:10px;"><a href="index.php" class="nav-btn">🏠 Menu</a><a href="register.php" class="nav-btn primary">+ New Ticket</a></div>
    </div>

    <div style="background: var(--panel-bg); border-radius: 16px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--panel-border); border-top: 1px solid var(--panel-border-top); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-end;">
            <span style="font-weight: 800; color: var(--text-accent); font-size: 1.1em; letter-spacing: 1px;">🏭 FACTORY HEALTH</span>
            <span style="font-weight: 800; color: <?= \$health_color ?>; font-size: 1.4em;"><?= \$health_pct ?>% UPTIME</span>
        </div>
        <div style="width: 100%; background: rgba(0,0,0,0.2); border-radius: 10px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);">
            <div style="width: <?= \$health_pct ?>%; background: <?= \$health_color ?>; height: 100%; transition: width 1.5s ease-in-out;"></div>
        </div>
        <div style="font-size: 0.85em; color: var(--text-secondary); margin-top: 8px; font-weight: 600;">
            <?= \$total_machines - \$down_machines ?> of <?= \$total_machines ?> machines are currently operational
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Equipment Details</th>
EOD;

$new_content = str_replace($target, $replacement, $content);
file_put_contents($file, $new_content);
echo "Restored active_tickets.php";
?>
