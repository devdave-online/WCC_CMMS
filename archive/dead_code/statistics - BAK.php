<?php
include 'auth.php';
$host = 'localhost'; $db = 'workshop_db'; $user = 'root'; $pass = '';

// INTERVAL & SHIFT SELECTOR LOGIC
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$shift_hours = isset($_GET['shift_hours']) ? (float)$_GET['shift_hours'] : 7.5;

// Calculate total days in interval (Inclusive)
$interval_days = max(1, round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);
// Total max working minutes per technician in this interval
$capacity_minutes = $interval_days * $shift_hours * 60;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $stmt = $pdo->prepare("SELECT * FROM active_tickets WHERE status = 'CLOSED' AND DATE(report_date) >= ? AND DATE(report_date) <= ?");
    $stmt->execute([$start_date, $end_date]);
    $closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtActions = $pdo->query("SELECT * FROM ticket_actions ORDER BY action_start ASC");
    $all_actions = $stmtActions->fetchAll(PDO::FETCH_ASSOC);
    
    $actions_by_ticket = [];
    foreach ($all_actions as $action) { $actions_by_ticket[$action['ticket_id']][] = $action; }

    $total_closed = count($closed_tickets);
    $total_downtime_minutes = 0;
    $total_labor_minutes = 0;
    $total_reaction_minutes = 0;
    $tickets_with_reactions = 0;
    
    $parts_consumed = []; 
    $tech_stats = []; // Holds Workload data per technician

    foreach ($closed_tickets as $ticket) {
        $tid = $ticket['ticket_id'];
        $start_stamp = strtotime(str_replace(' ', 'T', $ticket['report_date'] . ' ' . $ticket['report_time']));
        $end_stamp = $start_stamp; 
        
        if (isset($actions_by_ticket[$tid]) && count($actions_by_ticket[$tid]) > 0) {
            // MTTD
            $first_action_start = strtotime(str_replace(' ', 'T', $actions_by_ticket[$tid][0]['action_start']));
            $reaction = round(($first_action_start - $start_stamp) / 60);
            if ($reaction > 0) { $total_reaction_minutes += $reaction; $tickets_with_reactions++; }

            // LABOR & PARTS
            foreach ($actions_by_ticket[$tid] as $act) {
                $act_start = strtotime(str_replace(' ', 'T', $act['action_start']));
                $act_end = strtotime(str_replace(' ', 'T', $act['action_end']));
                $labor = round(($act_end - $act_start) / 60);
                $total_labor_minutes += $labor;
                
                if ($act_end > $end_stamp) { $end_stamp = $act_end; }
                
                // --- TECHNICIAN WORKLOAD TRACKER ---
                $tech_name = trim($act['tech_name']);
                if (!isset($tech_stats[$tech_name])) {
                    $tech_stats[$tech_name] = ['labor_minutes' => 0, 'interventions' => 0];
                }
                $tech_stats[$tech_name]['labor_minutes'] += $labor;
                $tech_stats[$tech_name]['interventions'] += 1;

                // --- PARTS TRACKER ---
                $pu = trim($act['parts_used']);
                if (!empty($pu) && strtolower($pu) !== 'none' && strtolower($pu) !== 'n/a' && $pu !== '-') {
                    $parts_consumed[] = [
                        'ticket_id' => $tid, 'equip_id' => $ticket['equip_id'],
                        'tech_name' => $tech_name, 'parts_used' => $pu, 'date' => date('Y-m-d', $act_end)
                    ];
                }
            }
        }
        
        $downtime = round(($end_stamp - $start_stamp) / 60);
        if ($downtime > 0) { $total_downtime_minutes += $downtime; }
    }

    // KPIs
    $total_ghost_minutes = max(0, $total_downtime_minutes - $total_labor_minutes);
    $mttr_minutes = $total_closed > 0 ? round($total_labor_minutes / $total_closed) : 0;
    $mttd_minutes = $tickets_with_reactions > 0 ? round($total_reaction_minutes / $tickets_with_reactions) : 0;
    $period_minutes = $interval_days * (2 * $shift_hours) * 60;
    $uptime_minutes = max(0, $period_minutes - $total_downtime_minutes);
    $mtbf_minutes = $total_closed > 0 ? round($uptime_minutes / $total_closed) : 0;

    function formatTime($mins) {
        $d = floor($mins / 1440); $h = floor(($mins % 1440) / 60); $m = $mins % 60;
        $res = ""; if($d>0) $res .= "{$d}d "; return $res . "{$h}h {$m}m";
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Workshop Statistics</title>
    <style>
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .metric-card {
            background: var(--panel-bg); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--panel-border);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            text-align: center;
            color: var(--text-primary);
        }
        .metric-card h3 { margin: 0 0 10px 0; color: var(--text-secondary); font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
        .metric-card .value { font-size: 1.8em; font-weight: bold; color: var(--text-accent); }
        .workload-card {
            background: var(--panel-bg); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--panel-border);
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            color: var(--text-primary);
        }
        .workload-card h3 { margin: 0 0 10px 0; display: flex; justify-content: space-between; color: var(--text-primary); }
        .progress-bg { background: rgba(0,0,0,0.2); height: 10px; border-radius: 5px; margin: 15px 0; overflow: hidden; border: 1px solid var(--panel-border); }
        .progress-bar { height: 100%; transition: width 0.5s ease-in-out; }
        .filter-bar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 15px; margin-bottom: 25px; padding: 15px; background: var(--panel-bg); border-radius: 12px; border: 1px solid var(--panel-border); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; color: var(--text-secondary); }
        .filter-form input { padding: 6px 10px; border-radius: 6px; background: rgba(0,0,0,0.1); border: 1px solid var(--panel-border); color: var(--text-primary); }
        .btn-filter { background: var(--text-accent); color: #0f172a; border: none; padding: 6px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-filter:hover { background: #0ea5e9; }
        .section-title { font-size: 1.4em; color: var(--text-accent); margin: 30px 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        @media print { .no-print { display: none !important; } .dashboard-container { box-shadow: none; border: none; background: white; } * { color: black !important; } }
    </style>
</head>
<body><?php include 'nav.php'; ?>

<div class="dashboard-container dash-box">
    <div class="header-flex">
        <h2>KPI & Performance Dashboard</h2>
        <div class="no-print">
            <a href="index.php" class="nav-btn">🏠 Hub</a>
        </div>
    </div>

    <div class="filter-bar no-print">
        <form method="GET" action="statistics.php" class="filter-form">
            <label>From:</label> <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
            <label>Till:</label> <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
            <label>Shift Length (hrs):</label> <input type="number" step="0.1" name="shift_hours" value="<?= htmlspecialchars($shift_hours) ?>" style="width:60px;" required>
            <button type="submit" class="btn-filter">Apply Filter</button>
        </form>
        <div class="export-group">
            <button onclick="exportToCSV('exportTable', 'Tickets_Report.csv')" class="nav-btn" style="background:#047857; color:white; border:none;">📄 Tickets</button>
            <button onclick="exportToCSV('partsTable', 'Parts_Report.csv')" class="nav-btn" style="background:#0f766e; color:white; border:none;">⚙️ Parts</button>
            <button onclick="window.print()" class="nav-btn" style="background:#1e3a8a; color:white; border:none;">🖨️ PDF</button>
        </div>
    </div>

    <div style="margin-bottom: 20px; font-weight: bold; color: #475569;">
        Report Period: <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?> 
        <span style="font-weight:normal; font-size:0.9em;">(<?= $interval_days ?> days active tracking)</span>
    </div>

    <div class="metrics-grid">
        <div class="metric-card"><h3>Total Events</h3><div class="value"><?= $total_closed ?></div></div>
        <div class="metric-card"><h3>MTTD (Reaction)</h3><div class="value"><?= formatTime($mttd_minutes) ?></div></div>
        <div class="metric-card"><h3>MTTR (Repair)</h3><div class="value"><?= formatTime($mttr_minutes) ?></div></div>
        <div class="metric-card"><h3>MTBF (Reliability)</h3><div class="value"><?= formatTime($mtbf_minutes) ?></div></div>
        <div class="metric-card" style="background:rgba(254, 226, 226, 0.4);"><h3>👻 Ghost Time</h3><div class="value" style="color:#b91c1c;"><?= formatTime($total_ghost_minutes) ?></div></div>
    </div>

    <div class="section-title">Technician Workload (Utilization)</div>
    <div class="metrics-grid">
        <?php if(empty($tech_stats)): ?>
            <div style="color:var(--text-secondary);">No labor logged in this interval.</div>
        <?php else: ?>
            <?php foreach($tech_stats as $tech_name => $stats): 
                $utilization = round(($stats['labor_minutes'] / $capacity_minutes) * 100, 1);
                
                $bar_color = "#1e40af"; 
                $text_color = "#1e3a8a";
                if ($utilization > 85) { $bar_color = "#b91c1c"; $text_color = "#b91c1c"; } 
                elseif ($utilization < 40) { $bar_color = "#047857"; $text_color = "#047857"; } 
                
                $visual_width = min(100, $utilization);
            ?>
                <div class="workload-card">
                    <h3><?= htmlspecialchars($tech_name) ?> <span style="color: <?= $text_color ?>;"><?= $utilization ?>%</span></h3>
                    <div class="stats">
                        Labor: <strong><?= formatTime($stats['labor_minutes']) ?></strong> | Interventions: <strong><?= $stats['interventions'] ?></strong>
                    </div>
                    <div class="progress-bg">
                        <div class="progress-bar" style="width: <?= $visual_width ?>%; background: <?= $bar_color ?>;"></div>
                    </div>
                    <div class="util-text" style="color: <?= $text_color ?>;"><?= $utilization ?>% of Capacity</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div style="font-size:0.85em; color:var(--text-secondary); margin-top:-10px; margin-bottom:30px;">
        *Capacity based on <?= $shift_hours ?> hour shift lengths. Note: Calculation assumes tech was present all <?= $interval_days ?> days of the interval.
    </div>

    <details style="margin-top: 20px; background: rgba(255,255,255,0.4); padding: 15px; border-radius: 12px; cursor: pointer;">
        <summary style="font-weight: bold; color: #1e3a8a; font-size: 1.1em;">📂 View Raw Data Ledgers (Tickets & Parts)</summary>
        
        <h4 style="margin-top: 20px;">Tickets Ledger</h4>
        <table class="data-table" id="exportTable">
            <thead><tr><th>Ticket ID</th><th>Equipment</th><th>Reported Date</th><th>Issue</th></tr></thead>
            <tbody>
                <?php foreach ($closed_tickets as $tick): ?>
                    <tr><td><?= htmlspecialchars($tick['ticket_id']) ?></td><td><?= htmlspecialchars($tick['equip_id']) ?></td><td><?= htmlspecialchars($tick['report_date']) ?></td><td><?= htmlspecialchars($tick['fault_desc']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 style="margin-top: 30px;">Parts Consumption</h4>
        <table class="data-table" id="partsTable">
            <thead><tr><th>Ticket ID</th><th>Equipment</th><th>Technician</th><th>Parts Replaced</th><th>Date Used</th></tr></thead>
            <tbody>
                <?php foreach ($parts_consumed as $part): ?>
                    <tr><td style="color:var(--text-secondary);"><?= htmlspecialchars($part['ticket_id']) ?></td><td><?= htmlspecialchars($part['equip_id']) ?></td><td><?= htmlspecialchars($part['tech_name']) ?></td><td style="font-weight:bold; color:#0f766e;"><?= htmlspecialchars($part['parts_used']) ?></td><td><?= htmlspecialchars($part['date']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>

</div>

<script>
    function exportToCSV(tableId, filename) {
        let table = document.getElementById(tableId); if (!table) return;
        let rows = table.querySelectorAll("tr"); let csv = [];
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) { let data = cols[j].innerText.replace(/"/g, '""'); row.push('"' + data + '"'); }
            csv.push(row.join(","));
        }
        let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        let downloadLink = document.createElement("a"); downloadLink.download = filename; downloadLink.href = window.URL.createObjectURL(csvFile); downloadLink.style.display = "none";
        document.body.appendChild(downloadLink); downloadLink.click(); document.body.removeChild(downloadLink);
    }
</script>

</body>
</html>


