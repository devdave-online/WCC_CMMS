<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/session.php';      // hardened session bootstrap
require_once __DIR__ . '/../inc/api_guard.php';
api_guard_perm('view_statistics');
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/shift_calendar.php';
require_once __DIR__ . '/../inc/kpi.php';          // single KPI engine — same maths as the dashboard

// NOTE ON KEYS: the JSON keys real_mttd / weekly.mttd are kept for the chart JS,
// but they now carry the corrected MTTA (response) value. MTTR is the elapsed
// repair window and MTBF is uptime ÷ failures — identical to _rpt/statistics.php.

try {
    $pdo = get_wcc_db_connection();

    // 1. Targets + holidays
    $settings = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $target_mttd = (float)($settings['target_mttd'] ?? 60);
    $target_mttr = (float)($settings['target_mttr'] ?? 120);
    $target_mtbf = (float)($settings['target_mtbf'] ?? 48);
    $target_calc_mode = $settings['target_calc_mode'] ?? 'static';
    $holidays = json_decode($settings['plant_holidays'] ?? '[]', true) ?? [];

    $SHIFT_START = '06:00:00';
    $SHIFT_END   = '22:00:00';
    $WORKING_DAYS = [1, 2, 3, 4, 5];
    $SHIFT_HOURS = 16;
    $calendar = new ShiftCalendar($SHIFT_START, $SHIFT_END, $WORKING_DAYS, $holidays);

    // Fixed fleet for the reliability rollup (fleet-wide, like the dashboard).
    $allEquipIds = $pdo->query("SELECT equip_id FROM equipment")->fetchAll(PDO::FETCH_COLUMN);

    $countWorkingDays = function (string $s, string $e) use ($WORKING_DAYS): int {
        $c = 0; $d = strtotime($s); $t = strtotime($e);
        while ($d !== false && $d <= $t) {
            if (in_array((int)date('N', $d), $WORKING_DAYS, true)) $c++;
            $d = strtotime('+1 day', $d);
        }
        return $c;
    };

    // Plant reliability rollup for a set of per-asset {windows,failures} buckets.
    $rollup = function (array $buckets, int $schedPerAsset) use ($calendar, $allEquipIds): array {
        $rows = [];
        foreach ($buckets as $eid => $b) {
            $rows[$eid] = wcc_kpi_asset_reliability($b['windows'], $schedPerAsset, $b['failures'], $calendar);
        }
        foreach ($allEquipIds as $eid) {
            if (!isset($rows[$eid])) $rows[$eid] = wcc_kpi_asset_reliability([], $schedPerAsset, 0, $calendar);
        }
        return wcc_kpi_plant_rollup($rows);
    };

    $raw_months = [];
    $months_data = [];
    $weekly_data = [];

    // 2. Fifteen months of raw data (three extra for the rolling target window).
    for ($i = 14; $i >= 0; $i--) {
        $first_day  = date('Y-m-01', strtotime("-$i months"));
        $last_day   = date('Y-m-t',  strtotime("-$i months"));
        $month_label = date('M Y', strtotime($first_day));

        $tk = $pdo->prepare("SELECT ticket_id, equip_id, report_date, report_time
                               FROM active_tickets
                              WHERE status = 'CLOSED' AND report_date >= ? AND report_date <= ?");
        $tk->execute([$first_day, $last_day]);
        $tickets = $tk->fetchAll(PDO::FETCH_ASSOC);

        $ax = $pdo->prepare("SELECT a.ticket_id, a.action_start, a.action_end, a.root_cause, a.action_taken
                               FROM ticket_actions a
                               JOIN active_tickets t ON a.ticket_id = t.ticket_id
                              WHERE t.status = 'CLOSED' AND t.report_date >= ? AND t.report_date <= ?
                              ORDER BY a.action_start ASC");
        $ax->execute([$first_day, $last_day]);
        $byTicket = [];
        foreach ($ax->fetchAll(PDO::FETCH_ASSOC) as $a) $byTicket[$a['ticket_id']][] = $a;

        $monthSched = $countWorkingDays($first_day, $last_day) * $SHIFT_HOURS * 60;

        $monthRows = [];
        $monthAssets = [];
        $weeks = [];

        foreach ($tickets as $t) {
            $eid = $t['equip_id'];
            if (!isset($monthAssets[$eid])) $monthAssets[$eid] = ['windows' => [], 'failures' => 0];

            $m = wcc_kpi_ticket_metrics($t, $byTicket[$t['ticket_id']] ?? [], $calendar);
            if (!$m['measurable']) continue;

            $monthRows[] = $m;
            $monthAssets[$eid]['failures']++;

            $report = strtotime($t['report_date'] . ' ' . $t['report_time']);
            $ends = [];
            foreach (($byTicket[$t['ticket_id']] ?? []) as $a) {
                if (empty($a['action_end']) || strncmp((string)$a['action_end'], '0000-00-00', 10) === 0) continue;
                $e = strtotime((string)$a['action_end']);
                if ($e !== false && $e > 0) $ends[] = $e;
            }
            $window = $ends ? ['start' => $report, 'end' => max($ends)] : null;
            if ($window) $monthAssets[$eid]['windows'][] = $window;

            // Weekly bucket (ISO week/year so December→January is handled).
            $wk = date('W', $report); $yr = date('o', $report); $wlabel = 'Week ' . $wk;
            if (!isset($weeks[$wlabel])) $weeks[$wlabel] = ['rows' => [], 'assets' => [], 'failures' => 0, 'year' => $yr, 'week' => $wk];
            $weeks[$wlabel]['rows'][] = $m;
            $weeks[$wlabel]['failures']++;
            if (!isset($weeks[$wlabel]['assets'][$eid])) $weeks[$wlabel]['assets'][$eid] = ['windows' => [], 'failures' => 0];
            $weeks[$wlabel]['assets'][$eid]['failures']++;
            if ($window) $weeks[$wlabel]['assets'][$eid]['windows'][] = $window;
        }

        $magg   = wcc_kpi_aggregate($monthRows);
        $mplant = $rollup($monthAssets, $monthSched);
        // A month with no failures has no mean time BETWEEN failures — emit null so
        // Chart.js draws a gap instead of a spike that flattens every real month.
        $mtbf_hours = $mplant['mtbf'] === null ? null : round($mplant['mtbf'] / 60, 1);

        // Weekly rows
        ksort($weeks);
        $weekly_processed = [];
        foreach ($weeks as $wlabel => $w) {
            $dto = new DateTime(); $dto->setISODate($w['year'], $w['week']);
            $ws = $dto->format('Y-m-d'); $dto->modify('+6 days'); $we = $dto->format('Y-m-d');
            $wSched = $countWorkingDays($ws, $we) * $SHIFT_HOURS * 60;
            $wagg   = wcc_kpi_aggregate($w['rows']);
            $wplant = $rollup($w['assets'], $wSched);
            $weekly_processed[] = [
                'week'     => $wlabel,
                'failures' => $w['failures'],
                'mttd'     => $wagg['mtta'],   // response (MTTA)
                'mttr'     => $wagg['mttr'],
                'mdt'      => $wagg['mdt'],
                'mtbf'     => $wplant['mtbf'] === null ? null : round($wplant['mtbf'] / 60, 1),
            ];
        }

        $raw_months[] = [
            'month'      => $month_label,
            'real_mttd'  => $magg['mtta'],   // response (MTTA)
            'real_mttr'  => $magg['mttr'],   // elapsed repair
            'real_mdt'   => $magg['mdt'],
            'real_mtbf'  => $mtbf_hours,
            'failures'   => $mplant['failures'],
            'total_mttd' => $magg['mtta'] * $magg['count'],
            'count_mttd' => $magg['count'],
            'total_mttr' => $magg['mttr'] * $magg['count'],
            'count_mttr' => $magg['count'],
            'uptime_hours' => $mplant['uptime'] / 60,
            'weekly_processed' => $weekly_processed,
        ];
    }

    // 3. Twelve display months, with static or 3-month rolling targets.
    for ($m = 3; $m <= 14; $m++) {
        $r = $raw_months[$m];

        $calc_target_mttd = $target_mttd;
        $calc_target_mttr = $target_mttr;
        $calc_target_mtbf = $target_mtbf;

        if ($target_calc_mode === 'dynamic') {
            $sum_c_mttd = $raw_months[$m-1]['count_mttd'] + $raw_months[$m-2]['count_mttd'] + $raw_months[$m-3]['count_mttd'];
            $sum_t_mttd = $raw_months[$m-1]['total_mttd'] + $raw_months[$m-2]['total_mttd'] + $raw_months[$m-3]['total_mttd'];
            $sum_c_mttr = $raw_months[$m-1]['count_mttr'] + $raw_months[$m-2]['count_mttr'] + $raw_months[$m-3]['count_mttr'];
            $sum_t_mttr = $raw_months[$m-1]['total_mttr'] + $raw_months[$m-2]['total_mttr'] + $raw_months[$m-3]['total_mttr'];
            $sum_fail   = $raw_months[$m-1]['failures'] + $raw_months[$m-2]['failures'] + $raw_months[$m-3]['failures'];
            $sum_uptime = $raw_months[$m-1]['uptime_hours'] + $raw_months[$m-2]['uptime_hours'] + $raw_months[$m-3]['uptime_hours'];

            $calc_target_mttd = $sum_c_mttd > 0 ? round($sum_t_mttd / $sum_c_mttd) : $target_mttd;
            $calc_target_mttr = $sum_c_mttr > 0 ? round($sum_t_mttr / $sum_c_mttr) : $target_mttr;
            // Uptime ÷ failures, matching the real MTBF definition. Falls back to the
            // static target on a quiet window rather than spiking.
            $calc_target_mtbf = $sum_fail > 0 ? round($sum_uptime / $sum_fail, 1) : $target_mtbf;
        }

        $months_data[] = [
            'month'       => $r['month'],
            'real_mttd'   => $r['real_mttd'],
            'real_mttr'   => $r['real_mttr'],
            'real_mdt'    => $r['real_mdt'],
            'real_mtbf'   => $r['real_mtbf'],
            'failures'    => $r['failures'],
            'target_mttd' => $calc_target_mttd,
            'target_mttr' => $calc_target_mttr,
            'target_mtbf' => $calc_target_mtbf,
        ];
        $weekly_data[$r['month']] = $r['weekly_processed'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'monthly' => $months_data,
            'weekly'  => $weekly_data,
            'targets' => ['mttd' => $target_mttd, 'mttr' => $target_mttr, 'mtbf' => $target_mtbf],
        ],
    ]);

} catch (Exception $e) {
    error_log('[WCC get_historical_kpis] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Could not load KPI history.']);
}
