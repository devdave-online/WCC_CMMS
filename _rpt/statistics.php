<?php
// ============================================================
// statistics.php — WCC CMMS KPI & Performance Dashboard
// ============================================================
// Industry-standard analytics engine with:
//   - Shift-calendar-aware downtime (2×8hr, Mon–Fri, 06:00–22:00)
//   - Per-asset MTBF with overlapping interval merging
//   - MTTA / MTTR / MDT / Ghost Time calculations (via the single engine, inc/kpi.php)
//   - Technician workload utilization
//   - Raw data ledgers with CSV export
// ============================================================
include __DIR__ . '/../auth.php';
require_perm('view_statistics');

// Enterprise centralized DB connection (highest quality)
require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();

// ── DATE RANGE FILTER ──────────────────────────────────────────
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');

// ── HARDCODED SHIFT CALENDAR ──────────────────────────────────
// 2 shifts × 8 hours = 16 production hours/day
// Shift window: 06:00 – 22:00, Monday – Friday
$SHIFT_START   = '06:00:00';
$SHIFT_END     = '22:00:00';
$SHIFT_HOURS   = 16; // total per day (2 × 8)
$WORKING_DAYS  = [1, 2, 3, 4, 5]; // ISO: 1=Mon, 5=Fri

$interval_days = max(1, round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1);

// Count only working days in the interval for capacity calculations
$working_day_count = 0;
$d = strtotime($start_date);
while ($d <= strtotime($end_date)) {
    if (in_array((int)date('N', $d), $WORKING_DAYS)) $working_day_count++;
    $d = strtotime('+1 day', $d);
}
$capacity_minutes_per_tech = $working_day_count * 8 * 60; // Single 8hr shift per tech


// ============================================================
// SHIFT-CALENDAR ENGINE + KPI ENGINE (single source of truth)
// ============================================================
require_once __DIR__ . '/../inc/shift_calendar.php';
require_once __DIR__ . '/../inc/kpi.php';

// ============================================================
// DATA FETCH & KPI CALCULATION
// ============================================================
try {
    // Use centralized connection (fixed undefined vars)
    $pdo = get_wcc_db_connection();
    
    // Fetch plant holidays
    $holidays_stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'plant_holidays'");
    $holidays_json = $holidays_stmt->fetchColumn() ?: '[]';
    $plant_holidays = json_decode($holidays_json, true) ?? [];

    $calendar = new ShiftCalendar($SHIFT_START, $SHIFT_END, $WORKING_DAYS, $plant_holidays);

    // ── 1. CLOSED TICKETS with aggregated action data ──────────
    $stmt = $pdo->prepare("
        SELECT t.ticket_id, t.equip_id, t.report_date, t.report_time,
               t.fault_desc, t.priority, t.announced_by, t.pic, t.closed_by, t.event_class,
               e.equip_name, w.name AS workshop_name,
               MIN(a.action_start) AS first_action,
               MAX(a.action_end)   AS last_action,
               SUM(TIMESTAMPDIFF(MINUTE, a.action_start, a.action_end)) AS active_labor_minutes
        FROM active_tickets t
        LEFT JOIN equipment e ON t.equip_id = e.equip_id
        LEFT JOIN workshops w ON e.workshop_id = w.workshop_id
        LEFT JOIN ticket_actions a ON t.ticket_id = a.ticket_id
        WHERE t.status = 'CLOSED'
          AND t.report_date >= ? AND t.report_date <= ?
        GROUP BY t.ticket_id
        ORDER BY t.report_date DESC, t.report_time DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 2. ALL ACTIONS for workload + parts ────────────────────
    $stmtActions = $pdo->prepare("
        SELECT a.*, t.equip_id, t.report_date
        FROM ticket_actions a
        JOIN active_tickets t ON a.ticket_id = t.ticket_id
        WHERE t.status = 'CLOSED'
          AND t.report_date >= ? AND t.report_date <= ?
        ORDER BY a.action_start ASC
    ");
    $stmtActions->execute([$start_date, $end_date]);
    $all_actions = $stmtActions->fetchAll(PDO::FETCH_ASSOC);

    // Build lookup: actions by ticket
    $actions_by_ticket = [];
    foreach ($all_actions as $action) {
        $actions_by_ticket[$action['ticket_id']][] = $action;
    }

    // ── ACCUMULATORS ───────────────────────────────────────────
    $total_closed = count($tickets);

    // Which event classes count as a failure (admin-configurable, default failure+induced).
    $failureClasses = wcc_kpi_failure_classes($pdo);

    // Plant-level per-ticket time metrics (only measurable tickets contribute).
    $ticketRows = [];   // one wcc_kpi_ticket_metrics() row per measurable ticket

    // Per-asset reliability inputs.
    $assetData = [];    // equip_id => ['windows'=>[], 'failures'=>int, 'name'=>str, 'rows'=>[]]

    // Workshop & equipment event counts
    $workshop_counts = [];
    $equip_counts    = [];
    $equip_names     = [];

    // Technician workload
    $tech_stats = [];

    // Parts consumption
    $parts_consumed = [];

    // Fault type distribution
    $fault_type_counts = [];

    // ── 3. PROCESS EACH TICKET ─────────────────────────────────
    // All KPI maths live in inc/kpi.php so every page agrees; this loop only
    // feeds tickets through it and buckets the results per asset.
    foreach ($tickets as $t) {
        $equip_id  = $t['equip_id'];
        $equipName = $t['equip_name'] ?? "EQ-{$equip_id}";
        $workshop  = $t['workshop_name'] ?? 'Unassigned';

        // Event counts
        $workshop_counts[$workshop] = ($workshop_counts[$workshop] ?? 0) + 1;
        $equip_counts[$equip_id]    = ($equip_counts[$equip_id] ?? 0) + 1;
        $equip_names[$equip_id]     = $equipName;

        if (!isset($assetData[$equip_id])) {
            $assetData[$equip_id] = ['windows' => [], 'failures' => 0, 'name' => $equipName, 'rows' => []];
        }

        $metrics = wcc_kpi_ticket_metrics($t, $actions_by_ticket[$t['ticket_id']] ?? [], $calendar);
        if (!$metrics['measurable']) continue;   // no usable action → not a data point

        // Does this event count as a failure? Every closed ticket defaults to 'failure',
        // so until anything is reclassified the numbers are exactly as before.
        $metrics['is_failure'] = wcc_kpi_counts_as_failure($t['event_class'] ?? 'failure', $failureClasses);
        $ticketRows[] = $metrics;

        // Failure COUNT is class-filtered; the downtime window below is recorded for
        // every closed corrective ticket (the machine was down whatever the cause).
        if ($metrics['is_failure']) $assetData[$equip_id]['failures']++;
        $assetData[$equip_id]['rows'][] = $metrics;

        // Downtime window for this event = report → last action end (raw stamps;
        // the shift calendar adjusts and merges them per asset below).
        $reportStamp = strtotime($t['report_date'] . ' ' . $t['report_time']);
        $ends = [];
        foreach (($actions_by_ticket[$t['ticket_id']] ?? []) as $a) {
            if (!empty($a['action_end'])) $ends[] = strtotime($a['action_end']);
        }
        if ($ends) {
            $assetData[$equip_id]['windows'][] = ['start' => $reportStamp, 'end' => max($ends)];
        }
    }

    // ── 4. TECHNICIAN WORKLOAD & PARTS ─────────────────────────
    foreach ($all_actions as $act) {
        $tech_name = trim($act['tech_name'] ?? '');
        if (empty($tech_name)) continue;

        // Same guard as the KPI engine: drop garbage rows (MySQL zero-dates, unparseable,
        // inverted, pre-epoch) so a single bad action can't report 740,000 days of labour.
        $startRaw = trim((string)($act['action_start'] ?? ''));
        $endRaw   = trim((string)($act['action_end'] ?? ''));
        if ($startRaw === '' || $endRaw === ''
            || strncmp($startRaw, '0000-00-00', 10) === 0 || strncmp($endRaw, '0000-00-00', 10) === 0) continue;
        $act_start = strtotime($startRaw);
        $act_end   = strtotime($endRaw);
        if ($act_start === false || $act_end === false || $act_start <= 0 || $act_end <= 0 || $act_end < $act_start) continue;

        $labor_min = (int)round(($act_end - $act_start) / 60);

        if (!isset($tech_stats[$tech_name])) {
            $tech_stats[$tech_name] = ['labor_minutes' => 0, 'interventions' => 0];
        }
        $tech_stats[$tech_name]['labor_minutes']  += $labor_min;
        $tech_stats[$tech_name]['interventions']  += 1;

        // Fault type distribution
        $ft = trim($act['fault_type'] ?? 'Unknown');
        $fault_type_counts[$ft] = ($fault_type_counts[$ft] ?? 0) + 1;

        // Parts consumption
        $pu = trim($act['parts_used'] ?? '');
        if (!empty($pu) && !in_array(strtolower($pu), ['none', 'n/a', '-', ''])) {
            $parts_consumed[] = [
                'ticket_id' => $act['ticket_id'],
                'equip_id'  => $act['equip_id'],
                'tech_name' => $tech_name,
                'parts_used'=> $pu,
                'date'      => date('Y-m-d', $act_end)
            ];
        }
    }

    // ── 5. PER-ASSET RELIABILITY (via the KPI engine) ──────────
    // Each asset is expected to run the full plant shift calendar in the window.
    $totalScheduledMinutes = $working_day_count * $SHIFT_HOURS * 60;

    $assetMTBF    = []; // equip_id => display row, only assets that had events
    $allAssetRows = []; // equip_id => reliability row for EVERY asset (for the fleet rollup)

    foreach ($assetData as $equip_id => $data) {
        $rel = wcc_kpi_asset_reliability($data['windows'], $totalScheduledMinutes, $data['failures'], $calendar);
        $assetAgg = wcc_kpi_aggregate($data['rows']); // per-asset time metrics
        $allAssetRows[$equip_id] = $rel;

        if ($data['failures'] > 0) {
            $assetMTBF[$equip_id] = [
                'name'         => $data['name'],
                'failures'     => $rel['failures'],
                'downtime'     => $rel['downtime'],
                'uptime'       => $rel['uptime'],
                'mtbf'         => $rel['mtbf'],
                'mttr'         => $assetAgg['mttr'],
                'availability' => $rel['availability'],
            ];
        }
    }

    // Fold in every machine that had NO event this period so that Plant MTBF and
    // fleet Availability are measured against the whole plant, not only the
    // troubled machines. A never-failed asset contributes full uptime, zero downtime.
    $allEquip = $pdo->query("SELECT equip_id, equip_name FROM equipment ORDER BY equip_name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allEquip as $eq) {
        if (!isset($allAssetRows[$eq['equip_id']])) {
            $allAssetRows[$eq['equip_id']] = wcc_kpi_asset_reliability([], $totalScheduledMinutes, 0, $calendar);
        }
    }

    // ── 6. FINAL KPI AGGREGATION ───────────────────────────────
    // Two populations for the time metrics (Q3 toggle): every repaired ticket, and
    // only the failure-classified ones. In the demo everything defaults to 'failure',
    // so the two are identical until tickets are reclassified.
    $agg     = wcc_kpi_aggregate($ticketRows);
    $aggFail = wcc_kpi_aggregate(array_values(array_filter($ticketRows, fn($r) => !empty($r['is_failure']))));

    $kpi_mtta   = $agg['mtta'];   // response time (was mislabelled "MTTD")
    $kpi_mttr   = $agg['mttr'];   // elapsed repair window
    $kpi_mdt    = $agg['mdt'];
    $kpi_active = $agg['active']; // hands-on (union of actions)
    $kpi_ghost  = $agg['ghost'];  // idle within the repair
    $kpi_hold   = $agg['hold'];   // the on-hold slice of ghost
    $kpi_labour = $agg['labour']; // repair effort (workload)

    $plant = wcc_kpi_plant_rollup($allAssetRows);
    $kpi_mtbf            = $plant['mtbf'];                // minutes, or null when there were no failures
    $availability_fleet  = $plant['availability_fleet'];
    $availability_failed = $plant['availability_failed'];
    $availability_pct    = $availability_fleet;          // headline = whole-fleet (standard)

    // Machine Explorer: every machine, searchable — failed ones show their reliability,
    // the rest show a clean 100% period.
    $explorerMachines = [];
    foreach ($allEquip as $eq) {
        $eid = $eq['equip_id'];
        if (isset($assetMTBF[$eid])) {
            $explorerMachines[] = $assetMTBF[$eid];
        } else {
            $r = $allAssetRows[$eid];
            $explorerMachines[] = [
                'name'         => $eq['equip_name'],
                'failures'     => 0,
                'downtime'     => 0,
                'uptime'       => $r['uptime'],
                'mtbf'         => $r['uptime'], // no failure in period → at least the full uptime
                'mttr'         => 0,
                'availability' => 100.0,
            ];
        }
    }
    $assetMetricsJson = json_encode($explorerMachines);

    // Top offenders (by failure count)
    arsort($equip_counts);
    $top_offenders = array_slice($equip_counts, 0, 5, true);

    // Sort fault types desc
    arsort($fault_type_counts);

    // ── HELPER ─────────────────────────────────────────────────
    function formatTime(int $mins): string {
        if ($mins <= 0) return '0h 0m';
        $d = floor($mins / 1440);
        $h = floor(($mins % 1440) / 60);
        $m = $mins % 60;
        $res = '';
        if ($d > 0) $res .= "{$d}d ";
        return $res . "{$h}h {$m}m";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<?php
$page_title = __('stats.dashboard_title');
require_once __DIR__ . '/../inc/head.php';
?>
    <!-- Chart.js (Local for offline support) -->
    <script src="/js/chart.js"></script>
    <style>
        /* ── STATISTICS PAGE STYLES ────────────────────────── */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--input-bg);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--text-accent);
            border-radius: 16px 16px 0 0;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }
        .stat-card .stat-icon {
            font-size: 1.8em;
            margin-bottom: 8px;
            display: block;
        }
        .stat-card .stat-label {
            font-size: 0.78em;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 600;
        }
        .stat-card .stat-value {
            font-size: 2em;
            font-weight: 800;
            color: var(--text-accent);
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }
        .stat-card .stat-formula {
            font-size: 0.72em;
            color: var(--text-secondary);
            font-style: italic;
            margin-top: 8px;
            opacity: 0.7;
        }

        /* Accent stripe overrides */
        .stat-card.accent-blue::before    { background: #3b82f6; }
        .stat-card.accent-cyan::before    { background: #06b6d4; }
        .stat-card.accent-green::before   { background: #10b981; }
        .stat-card.accent-red::before     { background: #ef4444; }
        .stat-card.accent-amber::before   { background: #f59e0b; }
        .stat-card.accent-purple::before  { background: #8b5cf6; }

        /* MDT Frame (spans 2 cols) */
        .mdt-frame {
            grid-column: span 2;
            background: var(--input-bg);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            position: relative;
        }
        .mdt-frame::before { display: none; }
        .mdt-frame .mdt-title {
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #3b82f6;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .mdt-frame .mdt-value {
            font-size: 2.4em;
            font-weight: 800;
            color: #60a5fa;
            font-variant-numeric: tabular-nums;
        }
        .mdt-frame .mdt-formula {
            font-size: 0.72em;
            color: var(--text-secondary);
            font-style: italic;
            margin-top: 4px;
            opacity: 0.7;
        }
        .mdt-inner-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 18px;
        }
        .mdt-sub-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }
        .mdt-sub-card .stat-label { font-size: 0.75em; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 6px; font-weight: 600; }
        .mdt-sub-card .stat-value { font-size: 1.6em; font-weight: 800; color: var(--text-accent); }
        .mdt-sub-card .stat-formula { font-size: 0.7em; color: var(--text-secondary); font-style: italic; margin-top: 6px; opacity: 0.7; }

        /* Events card (tall, spans rows) */
        .events-card {
            grid-row: span 2;
            display: flex;
            flex-direction: column;
        }

        .stat-list {
            text-align: left;
            background: rgba(0, 0, 0, 0.15);
            padding: 12px;
            border-radius: 10px;
            margin-top: 12px;
            flex: 1;
        }
        .stat-list h4 {
            margin: 0 0 10px 0;
            font-size: 0.8em;
            color: var(--text-accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 5px;
        }
        .stat-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85em;
            color: var(--text-primary);
            padding: 4px 0;
        }
        .stat-list-item + .stat-list-item {
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* Ghost card */
        .ghost-card {
            background: rgba(239, 68, 68, 0.08) !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }
        .ghost-card::before { background: #ef4444 !important; }
        .ghost-card .stat-value { color: #f87171 !important; }
        .ghost-desc {
            font-size: 0.78em;
            color: var(--text-secondary);
            margin-top: 10px;
            line-height: 1.5;
            text-align: left;
        }

        /* ── FILTER BAR ────────────────────────────────────── */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px 20px;
            background: var(--input-bg);
            border-radius: 14px;
            border: 1px solid var(--panel-border);
            align-items: center;
        }
        .filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        .filter-form label { font-weight: 600; }
        .filter-form input[type="date"] {
            padding: 7px 12px;
            border-radius: 8px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--panel-border);
            color: var(--text-primary);
            font-size: 0.9em;
        }
        .btn-filter {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9em;
        }
        .btn-filter:hover {
            background: linear-gradient(135deg, #0369a1, #075985);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .export-group {
            display: flex;
            gap: 8px;
        }
        .export-btn {
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            background: var(--input-bg);
            color: var(--text-primary);
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.2s;
        }
        .export-btn:hover {
            background: var(--btn-hover-bg);
            border-color: var(--text-accent);
        }

        /* ── SECTION TITLES ────────────────────────────────── */
        .section-title {
            font-size: 1.3em;
            color: var(--text-accent);
            margin: 35px 0 20px 0;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* ── WORKLOAD CARDS ────────────────────────────────── */
        .workload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }
        .workload-card {
            background: var(--input-bg);
            border: 1px solid var(--panel-border);
            padding: 20px;
            border-radius: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .workload-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        .workload-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .workload-header .tech-name {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.05em;
        }
        .workload-header .util-pct {
            font-weight: 800;
            font-size: 1.1em;
        }
        .workload-stats {
            font-size: 0.85em;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }
        .workload-stats strong { color: var(--text-primary); }
        .progress-track {
            background: rgba(0, 0, 0, 0.25);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* ── ASSET MTBF TABLE ──────────────────────────────── */
        .asset-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88em;
        }
        .asset-table th {
            background: rgba(0,0,0,0.3);
            color: var(--text-secondary);
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--panel-border);
        }
        .asset-table td {
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        .asset-table tbody tr:hover {
            background: rgba(255,255,255,0.03);
        }

        /* ── LEDGER DETAILS ────────────────────────────────── */
        .ledger-details {
            margin-top: 25px;
            background: var(--input-bg);
            border: 1px solid var(--panel-border);
            padding: 20px;
            border-radius: 14px;
        }
        .ledger-details summary {
            font-weight: 700;
            color: var(--text-accent);
            font-size: 1.05em;
            cursor: pointer;
            padding: 5px 0;
        }
        .ledger-details[open] summary {
            margin-bottom: 15px;
            border-bottom: 1px solid var(--panel-border);
            padding-bottom: 10px;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
            margin-top: 10px;
        }
        .ledger-table th {
            background: rgba(0,0,0,0.2);
            color: var(--text-secondary);
            font-size: 0.78em;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--panel-border);
        }
        .ledger-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: var(--text-primary);
        }
        .ledger-table tbody tr:hover { background: rgba(255,255,255,0.03); }

        /* ── REPORT PERIOD BADGE ───────────────────────────── */
        .period-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--input-bg);
            border: 1px solid var(--panel-border);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.88em;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        .period-badge strong { color: var(--text-primary); }

        /* ── FAULT TYPE DISTRIBUTION ───────────────────────── */
        .fault-bar-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
            font-size: 0.85em;
        }
        .fault-bar-label {
            min-width: 140px;
            color: var(--text-secondary);
            text-align: right;
            font-weight: 600;
        }
        .fault-bar-track {
            flex: 1;
            height: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 5px;
            overflow: hidden;
        }
        .fault-bar-fill {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            transition: width 0.5s ease;
        }
        .fault-bar-count {
            min-width: 30px;
            color: var(--text-primary);
            font-weight: 700;
        }

        /* ── SLIDE-OUT OVERLAY PANEL ────────────────────────── */
        .overlay-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .overlay-backdrop.active {
            opacity: 1;
            visibility: visible;
        }
        .ticket-overlay {
            position: fixed;
            top: 0; right: -520px;
            width: 500px;
            max-width: 90vw;
            height: 100vh;
            background: var(--modal-bg, #1e293b);
            border-left: 2px solid var(--text-accent);
            box-shadow: -10px 0 40px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            transition: right 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .ticket-overlay.active {
            right: 0;
        }
        .overlay-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--panel-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .overlay-header h3 {
            margin: 0;
            color: var(--text-accent);
            font-size: 1.15em;
            font-weight: 700;
        }
        .overlay-header .overlay-count {
            background: var(--text-accent);
            color: #0f172a;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 800;
        }
        .overlay-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5em;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
            line-height: 1;
        }
        .overlay-close:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .overlay-body {
            flex: 1;
            overflow-y: auto;
            padding: 16px 24px;
        }
        .overlay-ticket {
            background: var(--input-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: border-color 0.2s;
            border-left: 4px solid transparent;
        }
        .overlay-ticket:hover {
            border-left-color: var(--text-accent);
        }
        .overlay-ticket .ot-id {
            font-family: monospace;
            font-size: 0.82em;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .overlay-ticket .ot-equip {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1em;
            margin-bottom: 4px;
        }
        .overlay-ticket .ot-desc {
            font-size: 0.88em;
            color: var(--text-secondary);
            line-height: 1.4;
            margin-bottom: 8px;
        }
        .overlay-ticket .ot-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 0.78em;
            color: var(--text-secondary);
        }
        .overlay-ticket .ot-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .overlay-empty {
            text-align: center;
            color: var(--text-secondary);
            padding: 40px 20px;
            font-size: 0.95em;
        }

        /* Make list items clickable */
        .stat-list-item.clickable {
            cursor: pointer;
            padding: 6px 8px;
            margin: 0 -8px;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .stat-list-item.clickable:hover {
            background: rgba(56, 189, 248, 0.1);
        }
        .stat-list-item.clickable span {
            border-bottom: 1px dashed rgba(255,255,255,0.2);
        }

        /* ── RESPONSIVE ────────────────────────────────────── */
        @media (max-width: 800px) {
            .mdt-frame { grid-column: span 1; }
            .mdt-inner-grid { grid-template-columns: 1fr; }
            .events-card { grid-row: span 1; }
            .ticket-overlay { width: 100%; max-width: 100vw; }
        }
        @media print {
            .no-print { display: none !important; }
            .dashboard-container { box-shadow: none; border: none; }
            .overlay-backdrop, .ticket-overlay { display: none !important; }
        }
    </style>
<?php include __DIR__ . '/../nav.php'; ?>

<div class="dashboard-container">
    <!-- Header -->
    <div class="page-header">
        <h1>📊 <?= __e('stats.dashboard_title') ?></h1>
        <div class="no-print" style="display:flex; gap:8px;">
            <a href="../index.php" class="nav-btn">🏠 <?= __e('common.hub') ?></a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar no-print">
        <form method="GET" action="/_rpt/statistics.php" class="filter-form">
            <label><?= __e('stats.from') ?></label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
            <label><?= __e('stats.till') ?></label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
            <button type="submit" class="btn-filter"><?= __e('stats.apply_filter') ?></button>
        </form>
        <div class="export-group">
            <button onclick="exportToCSV('exportTable', 'Tickets_Report.csv')" class="export-btn">📄 <?= __e('stats.tickets_csv') ?></button>
            <button onclick="exportToCSV('partsTable', 'Parts_Report.csv')" class="export-btn">⚙️ <?= __e('stats.parts_csv') ?></button>
            <button onclick="window.print()" class="export-btn">🖨️ <?= __e('stats.print_pdf') ?></button>
        </div>
    </div>

    <!-- Period Badge -->
    <div class="period-badge">
        📅 <strong><?= date('M d, Y', strtotime($start_date)) ?></strong> → <strong><?= date('M d, Y', strtotime($end_date)) ?></strong>
        &nbsp;•&nbsp; <?= $interval_days ?> calendar days &nbsp;•&nbsp; <?= $working_day_count ?> working days &nbsp;•&nbsp; Shift: 2×8hr (06:00–22:00)
    </div>

    <!-- Population toggle (Q3): which tickets the time metrics average over -->
    <div class="no-print" style="display:flex; align-items:center; gap:10px; margin:-6px 0 20px; font-size:0.85em; color:var(--text-secondary); flex-wrap:wrap;">
        <span style="font-weight:600;">Response &amp; repair times over:</span>
        <div style="display:inline-flex; border:1px solid var(--panel-border); border-radius:20px; overflow:hidden;">
            <button type="button" id="popAll"  onclick="setPopulation('all')"  style="border:none; cursor:pointer; padding:5px 14px; font-size:0.9em; font-weight:700; background:var(--text-accent); color:#0f172a;">All repaired</button>
            <button type="button" id="popFail" onclick="setPopulation('fail')" style="border:none; cursor:pointer; padding:5px 14px; font-size:0.9em; font-weight:700; background:transparent; color:var(--text-secondary);">Failures only</button>
        </div>
        <span style="opacity:0.7;" title="Failure classes are set in Admin Panel → KPI Targets">Failures = classes marked as failures; everything repaired still counts toward downtime &amp; availability.</span>
    </div>

    <!-- ════════════════════════════════════════════════════
         SECTION 1: HERO KPI GRID
         ════════════════════════════════════════════════════ -->
    <div class="stats-grid">

        <!-- TOTAL EVENTS (tall card) -->
        <div class="stat-card accent-blue events-card">
            <span class="stat-icon">🔧</span>
            <div class="stat-label">Total Closed Events</div>
            <div class="stat-value"><?= $total_closed ?></div>

            <div class="stat-list">
                <h4>Events by Workshop</h4>
                <?php if (empty($workshop_counts)): ?>
                    <div class="stat-list-item" style="color: var(--text-secondary);">No data</div>
                <?php else: ?>
                    <?php foreach ($workshop_counts as $w => $c): ?>
                        <div class="stat-list-item clickable" onclick="openOverlay('workshop', '<?= htmlspecialchars(addslashes($w), ENT_QUOTES) ?>')">
                            <span><?= htmlspecialchars($w) ?></span>
                            <strong style="color:var(--text-accent);"><?= $c ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="stat-list">
                <h4>Top Repeat Offenders</h4>
                <?php if (empty($top_offenders)): ?>
                    <div class="stat-list-item" style="color: var(--text-secondary);">No data</div>
                <?php else: ?>
                    <?php $rank = 1; foreach ($top_offenders as $eid => $cnt): ?>
                        <div class="stat-list-item clickable" onclick="openOverlay('equip', '<?= $eid ?>')">
                            <span><?= $rank++ ?>. <?= htmlspecialchars($equip_names[$eid] ?? "EQ-{$eid}") ?></span>
                            <strong style="color:#f87171;"><?= $cnt ?> faults</strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MDT FRAME (spans 2 cols) -->
        <div class="mdt-frame">
            <div class="mdt-title">Mean Down Time (MDT)</div>
            <div class="mdt-value" id="mdtVal" data-all="<?= formatTime($agg['mdt']) ?>" data-fail="<?= formatTime($aggFail['mdt']) ?>"><?= formatTime($kpi_mdt) ?></div>
            <div class="mdt-formula">MDT = MTTA + MTTR &nbsp;·&nbsp; shift-adjusted, report → resolution</div>

            <div class="mdt-inner-grid">
                <div class="mdt-sub-card">
                    <div class="stat-label">⏱ MTTA (Response Time)</div>
                    <div class="stat-value" id="mttaVal" data-all="<?= formatTime($agg['mtta']) ?>" data-fail="<?= formatTime($aggFail['mtta']) ?>"><?= formatTime($kpi_mtta) ?></div>
                    <div class="stat-formula">Report → First Technician Response</div>
                </div>
                <div class="mdt-sub-card">
                    <div class="stat-label">🔧 MTTR (Repair Time)</div>
                    <div class="stat-value" id="mttrVal" data-all="<?= formatTime($agg['mttr']) ?>" data-fail="<?= formatTime($aggFail['mttr']) ?>"><?= formatTime($kpi_mttr) ?></div>
                    <div class="stat-formula">First Response → Resolution (elapsed)<br>
                        Repair Labour (effort): <strong id="labourVal" data-all="<?= formatTime($agg['labour']) ?>" data-fail="<?= formatTime($aggFail['labour']) ?>"><?= formatTime($kpi_labour) ?></strong></div>
                </div>
            </div>
        </div>

        <!-- PLANT MTBF -->
        <div class="stat-card accent-green">
            <span class="stat-icon">🛡️</span>
            <div class="stat-label">Plant MTBF (Reliability)</div>
            <div class="stat-value" style="color: #10b981;"><?= $kpi_mtbf === null ? '—' : formatTime($kpi_mtbf) ?></div>
            <div class="stat-formula">Fleet uptime / failures<br>Per-machine MTBF in the table below</div>
        </div>

        <!-- AVAILABILITY (fleet-wide, with a failed-assets-only toggle) -->
        <div class="stat-card accent-cyan" id="availCard"
             data-fleet="<?= $availability_fleet ?>" data-failed="<?= $availability_failed ?>">
            <span class="stat-icon">📈</span>
            <div class="stat-label">Availability <span id="availScopeTag" style="font-weight:600; opacity:0.65;">(fleet)</span></div>
            <div class="stat-value" id="availValue" style="color: <?= $availability_fleet >= 90 ? '#10b981' : ($availability_fleet >= 75 ? '#f59e0b' : '#ef4444') ?>;"><?= $availability_fleet ?>%</div>
            <div class="stat-formula">A = (Scheduled − Downtime) / Scheduled<br>
                <a href="#" id="availToggle" onclick="toggleAvailScope(event)" style="color: var(--text-accent); text-decoration: none; border-bottom: 1px dashed;">show failed assets only</a>
            </div>
        </div>

        <!-- GHOST TIME -->
        <div class="stat-card ghost-card">
            <span class="stat-icon">👻</span>
            <div class="stat-label">Ghost Time (Waste)</div>
            <div class="stat-value" id="ghostVal" data-all="<?= formatTime($agg['ghost']) ?>" data-fail="<?= formatTime($aggFail['ghost']) ?>"><?= formatTime($kpi_ghost) ?></div>
            <div class="stat-formula">Ghost = MTTR − Active Repair<span id="holdLine"<?= $kpi_hold > 0 ? '' : ' style="display:none;"' ?>><br>of which <strong>On Hold</strong>: <span id="holdVal" data-all="<?= formatTime($agg['hold']) ?>" data-fail="<?= formatTime($aggFail['hold']) ?>"><?= formatTime($kpi_hold) ?></span></span></div>
            <p class="ghost-desc">
                Idle time inside the repair window — waiting for spare parts, technician travel, delayed production handovers, or gaps between logged actions. The <strong>On Hold</strong> slice is time a ticket sat explicitly paused (usually awaiting parts). Minimising Ghost Time proves the workflow — not just the technicians — is efficient.
            </p>
        </div>

        <!-- MACHINE EXPLORER (inline, no popup, in the exact area) -->
        <div id="machineExplorer" style="background: var(--input-bg); border: 1px solid var(--panel-border); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; min-height: 260px; grid-column: 3 / -1; align-self: start; font-size: 0.95em; overflow: hidden;">
            <div style="font-weight: 700; color: var(--text-accent); font-size: 1.05em; margin-bottom: 8px; display:flex; align-items:center; gap:6px;">
                🔍 Machine Explorer
            </div>

            <!-- Wide searchbar - strictly contained inside parent for beautiful layout -->
            <div style="width:100%; box-sizing:border-box; margin-bottom:8px;">
                <input type="text" id="machineSearch" placeholder="Type to search machines..." 
                       onkeyup="filterExplorerMachines()" 
                       style="width:100%; box-sizing:border-box; padding:10px 12px; border-radius:8px; border:1px solid var(--panel-border); background:var(--panel-bg); color:var(--text-primary); font-size:1em; margin:0;">
            </div>

            <div id="explorerContent" style="flex:1; overflow-y:auto; font-size:0.95em; line-height:1.3; max-height:224px;">
                <!-- List view (idling or searching): exactly 7 entries visible, rest scrollable.
                     After select: full big KPIs view. -->
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         SECTION: HISTORICAL TREND ANALYSIS
         ════════════════════════════════════════════════════ -->
    <div class="section-title">📊 Historical Trend Analysis</div>
    <div style="background: var(--input-bg); border: 1px solid var(--panel-border); border-radius: 14px; padding: 20px; margin-bottom: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- MDT Chart -->
        <div style="position: relative; height: 350px;">
            <canvas id="mdtChart"></canvas>
        </div>
        <!-- MTBF Chart -->
        <div style="position: relative; height: 350px;">
            <canvas id="mtbfChart"></canvas>
        </div>
    </div>


    <!-- ════════════════════════════════════════════════════
         SECTION 2: PER-ASSET MTBF BREAKDOWN
         ════════════════════════════════════════════════════ -->
    <?php if (!empty($assetMTBF)): ?>
    <div class="section-title">🏭 Per-Asset Reliability Breakdown</div>
    <div style="background: var(--input-bg); border: 1px solid var(--panel-border); border-radius: 14px; padding: 20px; margin-bottom: 30px; overflow-x: auto;">
        <table class="asset-table">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th>Failures</th>
                    <th>Downtime (Shift-Adj.)</th>
                    <th>Uptime</th>
                    <th>MTBF</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assetMTBF as $eid => $a):
                    $a_avail = $totalScheduledMinutes > 0 ? round((($totalScheduledMinutes - $a['downtime']) / $totalScheduledMinutes) * 100, 1) : 100;
                    $avail_color = $a_avail >= 90 ? '#10b981' : ($a_avail >= 75 ? '#f59e0b' : '#ef4444');
                ?>
                <tr>
                    <td style="font-weight: 700;"><?= htmlspecialchars($a['name']) ?></td>
                    <td style="text-align:center; color:#f87171; font-weight:700;"><?= $a['failures'] ?></td>
                    <td><?= formatTime($a['downtime']) ?></td>
                    <td><?= formatTime($a['uptime']) ?></td>
                    <td style="font-weight:700; color:#10b981;"><?= formatTime($a['mtbf']) ?></td>
                    <td style="font-weight:700; color:<?= $avail_color ?>;"><?= $a_avail ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════
         SECTION 3: FAULT TYPE DISTRIBUTION
         ════════════════════════════════════════════════════ -->
    <?php if (!empty($fault_type_counts)): ?>
    <div class="section-title">⚡ Fault Type Distribution</div>
    <div style="background: var(--input-bg); border: 1px solid var(--panel-border); border-radius: 14px; padding: 20px; margin-bottom: 30px;">
        <?php
            $max_ft = max($fault_type_counts);
            foreach ($fault_type_counts as $ft => $cnt):
                $pct = $max_ft > 0 ? round(($cnt / $max_ft) * 100) : 0;
        ?>
        <div class="fault-bar-row">
            <div class="fault-bar-label"><?= htmlspecialchars($ft) ?></div>
            <div class="fault-bar-track">
                <div class="fault-bar-fill" style="width: <?= $pct ?>%;"></div>
            </div>
            <div class="fault-bar-count"><?= $cnt ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════
         SECTION 4: TECHNICIAN WORKLOAD
         ════════════════════════════════════════════════════ -->
    <div class="section-title">👷 Technician Workload (Utilization)</div>
    <div class="workload-grid">
        <?php if (empty($tech_stats)): ?>
            <div style="color: var(--text-secondary); padding: 10px;">No labor logged in this interval.</div>
        <?php else: ?>
            <?php foreach ($tech_stats as $tname => $stats):
                $utilization  = $capacity_minutes_per_tech > 0 ? round(($stats['labor_minutes'] / $capacity_minutes_per_tech) * 100, 1) : 0;
                $visual_width = min(100, $utilization);
                if ($utilization > 85)      { $bar_color = '#ef4444'; $text_color = '#ef4444'; }
                elseif ($utilization < 40)   { $bar_color = '#10b981'; $text_color = '#10b981'; }
                else                         { $bar_color = '#3b82f6'; $text_color = '#3b82f6'; }
            ?>
            <div class="workload-card">
                <div class="workload-header">
                    <span class="tech-name"><?= htmlspecialchars($tname) ?></span>
                    <span class="util-pct" style="color: <?= $text_color ?>;"><?= $utilization ?>%</span>
                </div>
                <div class="workload-stats">
                    Labor: <strong><?= formatTime($stats['labor_minutes']) ?></strong> &nbsp;|&nbsp;
                    Interventions: <strong><?= $stats['interventions'] ?></strong>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?= $visual_width ?>%; background: <?= $bar_color ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div style="font-size: 0.82em; color: var(--text-secondary); margin-top: -10px; margin-bottom: 30px;">
        *Capacity based on single 8-hour shift per technician. Utilization calculated over <?= $working_day_count ?> working days in the interval.
    </div>

    <!-- ════════════════════════════════════════════════════
         SECTION 5: RAW DATA LEDGERS
         ════════════════════════════════════════════════════ -->
    <details class="ledger-details">
        <summary>📂 View Raw Data Ledgers (Tickets & Parts)</summary>

        <h4 style="margin-top: 15px; color: var(--text-accent);">Closed Tickets Ledger</h4>
        <table class="ledger-table" id="exportTable">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Equipment</th>
                    <th>Workshop</th>
                    <th>Reported</th>
                    <th>Priority</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $tick): ?>
                <tr>
                    <td style="color: var(--text-secondary); font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars($tick['ticket_id']) ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($tick['equip_name'] ?? $tick['equip_id']) ?></td>
                    <td><?= htmlspecialchars($tick['workshop_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($tick['report_date']) ?></td>
                    <td>
                        <?php
                            $p = strtolower($tick['priority'] ?? 'normal');
                            $badge_class = "badge-{$p}";
                        ?>
                        <span class="prio-badge <?= $badge_class ?>"><?= htmlspecialchars(ucfirst($p)) ?></span>
                    </td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($tick['fault_desc'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 style="margin-top: 30px; color: var(--text-accent);">Parts Consumption</h4>
        <table class="ledger-table" id="partsTable">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Equipment</th>
                    <th>Technician</th>
                    <th>Parts Replaced</th>
                    <th>Date Used</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($parts_consumed)): ?>
                    <tr><td colspan="5" style="color: var(--text-secondary); text-align: center;">No parts consumed in this interval.</td></tr>
                <?php else: ?>
                    <?php foreach ($parts_consumed as $part): ?>
                    <tr>
                        <td style="color: var(--text-secondary); font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars($part['ticket_id']) ?></td>
                        <td><?= htmlspecialchars($equip_names[$part['equip_id']] ?? $part['equip_id']) ?></td>
                        <td><?= htmlspecialchars($part['tech_name']) ?></td>
                        <td style="font-weight: 700; color: #10b981;"><?= htmlspecialchars($part['parts_used']) ?></td>
                        <td><?= htmlspecialchars($part['date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </details>

</div>

<!-- ════════════════════════════════════════════════════
     SLIDE-OUT OVERLAY PANEL
     ════════════════════════════════════════════════════ -->
<div class="overlay-backdrop" id="overlayBackdrop" onclick="closeOverlay()"></div>
<div class="ticket-overlay" id="ticketOverlay">
    <div class="overlay-header">
        <div style="display:flex; align-items:center; gap:10px;">
            <h3 id="overlayTitle">Tickets</h3>
            <span class="overlay-count" id="overlayCount">0</span>
        </div>
        <button class="overlay-close" onclick="closeOverlay()">&times;</button>
    </div>
    <div class="overlay-body" id="overlayBody"></div>
</div>

<!-- ════════════════════════════════════════════════════
     JAVASCRIPT: CSV Export + Overlay Logic
     ════════════════════════════════════════════════════ -->
<script>
    // Ticket data injected from PHP
    const ticketData = <?php
        // Build a JSON-safe array of ticket info for JS
        $jsTickets = [];
        foreach ($tickets as $t) {
            $jsTickets[] = [
                'id'       => $t['ticket_id'],
                'equip_id' => (int)$t['equip_id'],
                'equip'    => $t['equip_name'] ?? 'EQ-'.$t['equip_id'],
                'workshop' => $t['workshop_name'] ?? 'Unassigned',
                'date'     => $t['report_date'],
                'time'     => $t['report_time'] ?? '',
                'priority' => $t['priority'] ?? 'normal',
                'desc'     => $t['fault_desc'] ?? '',
                'pic'      => $t['pic'] ?? '',
                'closed_by'=> $t['closed_by'] ?? '',
            ];
        }
        echo json_encode($jsTickets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>;

    function openOverlay(type, key) {
        let filtered = [];
        let title = '';

        if (type === 'workshop') {
            filtered = ticketData.filter(t => t.workshop === key);
            title = '🏭 ' + key;
        } else if (type === 'equip') {
            const eid = parseInt(key);
            filtered = ticketData.filter(t => t.equip_id === eid);
            const name = filtered.length > 0 ? filtered[0].equip : 'Equipment #' + key;
            title = '⚙️ ' + name;
        }

        document.getElementById('overlayTitle').textContent = title;
        document.getElementById('overlayCount').textContent = filtered.length;

        const body = document.getElementById('overlayBody');
        if (filtered.length === 0) {
            body.innerHTML = '<div class="overlay-empty">No tickets found.</div>';
        } else {
            body.innerHTML = filtered.map(t => {
                const prioClass = 'badge-' + (t.priority || 'normal').toLowerCase();
                return `
                    <div class="overlay-ticket">
                        <div class="ot-id">${escHtml(t.id)}</div>
                        <div class="ot-equip">${escHtml(t.equip)}</div>
                        <div class="ot-desc">${escHtml(t.desc) || '<em style="opacity:0.5;">No description</em>'}</div>
                        <div class="ot-meta">
                            <span>📅 ${escHtml(t.date)}</span>
                            <span>🕐 ${escHtml(t.time)}</span>
                            <span class="prio-badge ${prioClass}">${escHtml(ucfirst(t.priority))}</span>
                            ${t.pic ? '<span>👤 ' + escHtml(t.pic) + '</span>' : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        document.getElementById('overlayBackdrop').classList.add('active');
        document.getElementById('ticketOverlay').classList.add('active');
        body.scrollTop = 0;
    }

    function closeOverlay() {
        document.getElementById('overlayBackdrop').classList.remove('active');
        document.getElementById('ticketOverlay').classList.remove('active');
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeOverlay();
    });

    // Helpers
    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Population toggle: swap the response/repair time metrics between every repaired
    // ticket and only the failure-classified ones. Reliability (MTBF/Availability) is
    // unaffected — it is already about failures and downtime.
    function setPopulation(which) {
        const on  = which === 'fail' ? 'popFail' : 'popAll';
        const off = which === 'fail' ? 'popAll'  : 'popFail';
        const bOn = document.getElementById(on), bOff = document.getElementById(off);
        bOn.style.background = 'var(--text-accent)';  bOn.style.color = '#0f172a';
        bOff.style.background = 'transparent';         bOff.style.color = 'var(--text-secondary)';
        ['mdtVal','mttaVal','mttrVal','labourVal','ghostVal','holdVal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = el.dataset[which === 'fail' ? 'fail' : 'all'];
        });
        const holdVal = document.getElementById('holdVal');
        const holdLine = document.getElementById('holdLine');
        if (holdVal && holdLine) holdLine.style.display = /^0h 0m$/.test(holdVal.textContent.trim()) ? 'none' : '';
    }

    // Availability headline: swap between whole-fleet (standard) and failed-assets-only.
    let availShowFailed = false;
    function toggleAvailScope(ev) {
        ev.preventDefault();
        const card = document.getElementById('availCard');
        availShowFailed = !availShowFailed;
        const pct = parseFloat(availShowFailed ? card.dataset.failed : card.dataset.fleet);
        const val = document.getElementById('availValue');
        val.textContent = pct + '%';
        val.style.color = pct >= 90 ? '#10b981' : (pct >= 75 ? '#f59e0b' : '#ef4444');
        document.getElementById('availScopeTag').textContent = availShowFailed ? '(failed only)' : '(fleet)';
        document.getElementById('availToggle').textContent = availShowFailed ? 'show whole fleet' : 'show failed assets only';
    }

    // CSV Export
    function exportToCSV(tableId, filename) {
        let table = document.getElementById(tableId);
        if (!table) return;
        let rows = table.querySelectorAll("tr");
        let csv = [];
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
        let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        let link = document.createElement("a");
        link.download = filename;
        link.href = window.URL.createObjectURL(csvFile);
        link.style.display = "none";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<script>
// Inline Machine Explorer (no popup) - matches user drawings
const assetData = <?= $assetMetricsJson ? $assetMetricsJson : '[]' ?>;
let explorerSelected = null;

function formatTimeJS(minutes) {
    if (!minutes || minutes < 1) return '0m';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function filterExplorerMachines() {
    const searchInput = document.getElementById('machineSearch');
    if (!searchInput) return;
    const q = (searchInput.value || '').toLowerCase().trim();
    const container = document.getElementById('explorerContent');
    if (!container) return;
    const filtered = q ? assetData.filter(m => (m.name || '').toLowerCase().includes(q)) : assetData;
    renderExplorerList(filtered, container);
}

function renderExplorerList(machines, container) {
    container.innerHTML = '';
    container.style.maxHeight = '224px';  // show only 7 entries, rest scrollable
    container.style.overflowY = 'auto';
    if (!machines.length) {
        container.innerHTML = '<div style="color:var(--text-secondary); padding:4px 6px; font-size:0.95em;">No matches.</div>';
        return;
    }
    machines.forEach(m => {
        const div = document.createElement('div');
        div.style.cssText = 'padding:6px 8px; margin:2px 0; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-size:0.95em; border-radius:4px; min-height:28px;';
        div.onmouseenter = () => div.style.background = 'rgba(255,255,255,0.06)';
        div.onmouseleave = () => div.style.background = 'transparent';
        const mtbfStr = formatTimeJS(m.mtbf || 0);
        const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
        div.innerHTML = `
            <span style="font-weight:600;">${esc(m.name || 'Unknown')}</span>
            <span style="display:flex; gap:8px; font-size:0.9em;">
                <span style="color:#f87171;">${esc(m.failures || 0)} fails</span>
                <span style="color:#10b981; font-weight:700;">${esc(mtbfStr)}</span>
            </span>
        `;
        div.onclick = () => selectExplorerMachine(m);
        container.appendChild(div);
    });
}

function selectExplorerMachine(machine) {
    explorerSelected = machine;
    const container = document.getElementById('explorerContent');
    const mtbfStr = formatTimeJS(machine.mtbf || 0);
    const mttrStr = formatTimeJS(machine.mttr || 0);
    const failStr = machine.failures || 0;
    const downStr = formatTimeJS(machine.downtime || 0);

    // Remove list height limit for the selected big view
    container.style.maxHeight = 'none';
    container.style.overflowY = 'visible';

    const escM = (typeof escapeHtml === 'function') ? escapeHtml : (s) => String(s ?? '');
    container.innerHTML = `
        <!-- Locked header -->
        <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: 8px; padding: 8px 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 0.7em; color: var(--text-secondary); font-weight: 600;">LOCKED MACHINE</div>
                <div style="font-size: 1.15em; font-weight: 800; color: var(--text-accent);">${escM(machine.name || 'Unknown')}</div>
            </div>
            <button onclick="unlockExplorerMachine(); event.stopImmediatePropagation();" 
                    style="background: none; border: 1px solid #ef4444; color: #ef4444; padding: 4px 8px; border-radius: 6px; font-size: 0.8em; cursor: pointer; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                🔓 Unlock
            </button>
        </div>

        <!-- Big Individual KPIs (first attempt style - large & prominent) -->
        <div style="font-size: 0.75em; color: var(--text-secondary); margin-bottom: 6px; font-weight: 600;">INDIVIDUAL METRICS FOR THIS MACHINE</div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <!-- Big MTBF -->
            <div style="background: var(--panel-bg); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; padding: 10px; text-align: center;">
                <div style="font-size: 0.65em; color: #10b981; font-weight: 700; letter-spacing: 0.5px;">MTBF</div>
                <div style="font-size: 1.9em; font-weight: 900; color: #10b981; line-height: 1.1; margin: 4px 0;">${mtbfStr}</div>
                <div style="font-size: 0.6em; color: var(--text-secondary);">Mean Time Between Failures</div>
            </div>

            <!-- Big MTTR -->
            <div style="background: var(--panel-bg); border: 1px solid rgba(234,179,8,0.3); border-radius: 8px; padding: 10px; text-align: center;">
                <div style="font-size: 0.65em; color: #eab308; font-weight: 700; letter-spacing: 0.5px;">MTTR</div>
                <div style="font-size: 1.9em; font-weight: 900; color: #eab308; line-height: 1.1; margin: 4px 0;">${mttrStr}</div>
                <div style="font-size: 0.6em; color: var(--text-secondary);">Mean Time To Repair</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
            <div style="background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 8px; padding: 8px; text-align: center;">
                <div style="font-size: 0.6em; color: var(--text-secondary); font-weight: 600;">FAILURES</div>
                <div style="font-size: 1.4em; font-weight: 800; color: var(--text-primary);">${failStr}</div>
            </div>
            <div style="background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 8px; padding: 8px; text-align: center;">
                <div style="font-size: 0.6em; color: var(--text-secondary); font-weight: 600;">DOWNTIME</div>
                <div style="font-size: 1.4em; font-weight: 800; color: var(--text-primary);">${downStr}</div>
            </div>
        </div>
    `;
}

function unlockExplorerMachine() {
    explorerSelected = null;
    const container = document.getElementById('explorerContent');
    const searchInput = document.getElementById('machineSearch');
    if (searchInput) searchInput.value = '';
    renderExplorerList(assetData, container);
}

// init the list (populates with all machines on load)
(function initExplorer() {
    const container = document.getElementById('explorerContent');
    if (container && typeof assetData !== 'undefined' && assetData.length) {
        renderExplorerList(assetData, container);
    }
})();

// ── FETCH & RENDER CHARTS ────────────────────────────────────
(async function initCharts() {
    try {
        const response = await fetch('/api/get_historical_kpis.php');
        const json = await response.json();
        
        if (json.status !== 'success') {
            console.error('Failed to load chart data:', json.message);
            return;
        }

        const data = json.data;
        const labels = data.monthly.map(m => m.month);

        // Styling Variables
        const neonBlue = '#38bdf8';
        const neonPurple = '#c084fc';
        const neonGreen = '#34d399';
        const neonOrange = '#fb923c';

        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.family = "'Inter', sans-serif";
        
        // Custom Click Handler for Weekly Breakdown
        const handleChartClick = (evt, elements, chart, type) => {
            if (!elements.length) return;
            const index = elements[0].index;
            const monthLabel = labels[index];
            const weekly = data.weekly[monthLabel];
            
            if (!weekly || !weekly.length) {
                showToast('No data for ' + monthLabel, 'info');
                return;
            }

            document.getElementById('weeklyModalTitle').innerText = `${monthLabel} — ${type} Breakdown`;
            
            let html = '';
            if (type === 'MTBF') {
                html = `<table class="ledger-table" style="width:100%; border-collapse:collapse; font-size:0.9em; text-align:left;">
                    <thead><tr><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">Week</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">Failures</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">MTBF (Hours)</th></tr></thead>
                    <tbody>`;
                
                weekly.forEach(w => {
                    html += `<tr>
                        <td style="padding:4px 0;">${w.week}</td>
                        <td style="color:var(--danger); font-weight:bold; padding:4px 0;">${w.failures}</td>
                        <td style="padding:4px 0; color:var(--success); font-weight:bold;">${w.mtbf === null || w.mtbf === undefined ? '<span style="color:var(--text-muted); font-weight:normal;">no failures</span>' : w.mtbf + 'h'}</td>
                    </tr>`;
                });
            } else {
                html = `<table class="ledger-table" style="width:100%; border-collapse:collapse; font-size:0.9em; text-align:left;">
                    <thead><tr><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">Week</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">Failures</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">MTTA</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">MTTR</th><th style="padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text-secondary);">MDT</th></tr></thead>
                    <tbody>`;
                
                weekly.forEach(w => {
                    html += `<tr>
                        <td style="padding:4px 0;">${w.week}</td>
                        <td style="color:var(--danger); font-weight:bold; padding:4px 0;">${w.failures}</td>
                        <td style="padding:4px 0;">${w.mttd}m</td>
                        <td style="padding:4px 0;">${w.mttr}m</td>
                        <td style="padding:4px 0;">${w.mdt}m</td>
                    </tr>`;
                });
            }
            html += `</tbody></table>`;
            
            document.getElementById('weeklyModalBody').innerHTML = html;
            
            const modal = document.getElementById('weeklyModal');
            modal.style.display = 'block';

            const nativeEvent = evt.native || evt;
            let posX = nativeEvent.clientX + 15;
            let posY = nativeEvent.clientY + 15;
            
            // Adjust if it goes off screen right
            if (posX + modal.offsetWidth > window.innerWidth) {
                posX = nativeEvent.clientX - modal.offsetWidth - 15;
            }
            // Adjust if it goes off screen bottom
            if (posY + modal.offsetHeight > window.innerHeight) {
                posY = window.innerHeight - modal.offsetHeight - 15;
            }
            
            modal.style.left = posX + 'px';
            modal.style.top = posY + 'px';
        };

        // MDT CHART (MTTD & MTTR)
        const ctxMdt = document.getElementById('mdtChart').getContext('2d');
        new Chart(ctxMdt, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Real MTTA',
                        data: data.monthly.map(m => m.real_mttd),
                        borderColor: neonBlue,
                        backgroundColor: neonBlue,
                        borderWidth: 2,
                        pointBackgroundColor: '#1e293b',
                        pointBorderColor: neonBlue,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        tension: 0.3
                    },
                    {
                        label: 'Target MTTA',
                        data: data.monthly.map(m => m.target_mttd),
                        borderColor: 'rgba(56, 189, 248, 0.3)',
                        borderWidth: 1,
                        borderDash: [5, 5],
                        pointRadius: 0
                    },
                    {
                        label: 'Real MTTR',
                        data: data.monthly.map(m => m.real_mttr),
                        borderColor: neonOrange,
                        backgroundColor: neonOrange,
                        borderWidth: 2,
                        pointBackgroundColor: '#1e293b',
                        pointBorderColor: neonOrange,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        tension: 0.3
                    },
                    {
                        label: 'Target MTTR',
                        data: data.monthly.map(m => m.target_mttr),
                        borderColor: 'rgba(251, 146, 60, 0.3)',
                        borderWidth: 1,
                        borderDash: [5, 5],
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Mean Down Time (MDT) Components (Minutes)', color: '#fff', font: { size: 14 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                },
                interaction: { mode: 'index', intersect: false },
                onClick: (evt, elements, chart) => handleChartClick(evt, elements, chart, 'MDT')
            }
        });

        // MTBF CHART
        const ctxMtbf = document.getElementById('mtbfChart').getContext('2d');
        new Chart(ctxMtbf, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Real MTBF',
                        data: data.monthly.map(m => m.real_mtbf),
                        borderColor: neonGreen,
                        backgroundColor: neonGreen,
                        borderWidth: 2,
                        pointBackgroundColor: '#1e293b',
                        pointBorderColor: neonGreen,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        tension: 0.3
                    },
                    {
                        label: 'Target MTBF',
                        data: data.monthly.map(m => m.target_mtbf),
                        borderColor: 'rgba(52, 211, 153, 0.3)',
                        borderWidth: 1,
                        borderDash: [5, 5],
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Operations MTBF (Hours)', color: '#fff', font: { size: 14 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                },
                interaction: { mode: 'index', intersect: false },
                onClick: (evt, elements, chart) => handleChartClick(evt, elements, chart, 'MTBF')
            }
        });

    } catch (err) {
        console.error('Chart init error:', err);
    }
})();

// Close localized modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('weeklyModal');
    if (modal && modal.style.display === 'block') {
        if (!modal.contains(e.target) && !e.target.closest('canvas')) {
            modal.style.display = 'none';
        }
    }
});
</script>
<!-- Weekly Breakdown Modal (Localized Mini Pop-up) -->
<!-- Themed via tokens: this panel hardcoded a dark navy background and pale text,
     so in light mode it rendered as a dark slab over a light page. -->
<div id="weeklyModal" style="display:none; z-index: 100000; position: fixed; background: var(--modal-bg); color: var(--text-primary); padding: 14px 16px; border-radius: var(--radius-md, 8px); font-size: 0.82em; width: 340px; height: auto; box-shadow: var(--shadow-2, 0 15px 40px rgba(0,0,0,0.35)); border: 1px solid var(--panel-border); text-align: left;">
    <span onclick="document.getElementById('weeklyModal').style.display='none'" style="position: absolute; right: 10px; top: 10px; font-size: 18px; cursor: pointer; color: var(--text-secondary);">&times;</span>
    <h3 id="weeklyModalTitle" style="color: var(--text-accent); margin-top: 0; margin-bottom: 12px; font-size: 1.1em;">Weekly Breakdown</h3>
    <div id="weeklyModalBody" style="margin-top: 5px; font-size: 1em; max-height: 400px; overflow-y: auto;">
        <!-- Populated via JS -->
    </div>
</div>

</body>
</html>