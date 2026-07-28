<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/shift_calendar.php';
require_once __DIR__ . '/../../../inc/session.php'; // hardened session bootstrap

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = get_wcc_db_connection();

    // 1. Fetch KPI Targets and Holidays
    $settings = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $target_mttd = (float)($settings['target_mttd'] ?? 60);
    $target_mttr = (float)($settings['target_mttr'] ?? 120);
    $target_mtbf = (float)($settings['target_mtbf'] ?? 48);
    $target_calc_mode = $settings['target_calc_mode'] ?? 'static';
    $holidays = json_decode($settings['plant_holidays'] ?? '[]', true) ?? [];

    $SHIFT_START = '06:00:00';
    $SHIFT_END = '22:00:00';
    $WORKING_DAYS = [1, 2, 3, 4, 5];
    $calendar = new ShiftCalendar($SHIFT_START, $SHIFT_END, $WORKING_DAYS, $holidays);

    $raw_months = [];
    $months_data = [];
    $weekly_data = [];

    // 2. Generate last 15 months (to allow 3-month rolling average for 12 months)
    for ($i = 14; $i >= 0; $i--) {
        $first_day = date('Y-m-01', strtotime("-$i months"));
        $last_day = date('Y-m-t', strtotime("-$i months"));
        $month_label = date('M Y', strtotime($first_day));
        
        // Fetch tickets for this month
        $stmt = $pdo->prepare("
            SELECT t.ticket_id, t.report_date, t.report_time,
                   MIN(a.action_start) AS first_action,
                   MAX(a.action_end)   AS last_action,
                   SUM(TIMESTAMPDIFF(MINUTE, a.action_start, a.action_end)) AS active_labor_minutes
            FROM active_tickets t
            LEFT JOIN ticket_actions a ON t.ticket_id = a.ticket_id
            WHERE t.status = 'CLOSED'
              AND t.report_date >= ? AND t.report_date <= ?
            GROUP BY t.ticket_id
        ");
        $stmt->execute([$first_day, $last_day]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_mttd = 0; $count_mttd = 0;
        $total_mttr = 0; $count_mttr = 0;
        $total_mdt = 0; $count_mdt = 0;
        $total_failures = count($tickets);

        // Group tickets by week for the weekly breakdown
        $weeks = [];

        foreach ($tickets as $t) {
            $reportStamp = strtotime($t['report_date'] . ' ' . $t['report_time']);
            $weekNum = date('W', $reportStamp);
            $yearNum = date('Y', $reportStamp);
            $weekLabel = 'Week ' . $weekNum;
            if (!isset($weeks[$weekLabel])) $weeks[$weekLabel] = ['failures' => 0, 'mttd' => 0, 'mttr' => 0, 'mdt' => 0, 'mttd_count' => 0, 'mttr_count' => 0, 'mdt_count' => 0, 'year' => $yearNum, 'week' => $weekNum];
            $weeks[$weekLabel]['failures']++;

            if ($t['first_action'] && $t['last_action']) {
                $firstActionStamp = strtotime($t['first_action']);
                $lastActionStamp  = strtotime($t['last_action']);

                $mttd = $calendar->getWorkingMinutes($reportStamp, $firstActionStamp);
                $total_mttd += $mttd; $count_mttd++;
                $weeks[$weekLabel]['mttd'] += $mttd; $weeks[$weekLabel]['mttd_count']++;

                $mttr = max(0, (int)$t['active_labor_minutes']);
                $total_mttr += $mttr; $count_mttr++;
                $weeks[$weekLabel]['mttr'] += $mttr; $weeks[$weekLabel]['mttr_count']++;

                $mdt = $calendar->getWorkingMinutes($reportStamp, $lastActionStamp);
                $total_mdt += $mdt; $count_mdt++;
                $weeks[$weekLabel]['mdt'] += $mdt; $weeks[$weekLabel]['mdt_count']++;
            }
        }

        // Process Weekly Aggregations
        $weekly_processed = [];
        ksort($weeks); // Sort by week number
        foreach ($weeks as $wLabel => $wData) {
            $dto = new DateTime();
            $dto->setISODate($wData['year'], $wData['week']);
            $week_start = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $week_end = $dto->format('Y-m-d');
            
            // To accurately calculate Weekly MTBF we need the scheduled time in that week.
            $w_scheduled_mins = $calendar->getScheduledMinutesInDateRange($week_start, $week_end);
            $w_scheduled_hours = $w_scheduled_mins / 60;
            $w_mtbf_hours = $wData['failures'] > 0 ? round($w_scheduled_hours / $wData['failures'], 1) : round($w_scheduled_hours, 1);

            $weekly_processed[] = [
                'week' => $wLabel,
                'failures' => $wData['failures'],
                'mttd' => $wData['mttd_count'] > 0 ? round($wData['mttd'] / $wData['mttd_count']) : 0,
                'mttr' => $wData['mttr_count'] > 0 ? round($wData['mttr'] / $wData['mttr_count']) : 0,
                'mdt' => $wData['mdt_count'] > 0 ? round($wData['mdt'] / $wData['mdt_count']) : 0,
                'mtbf' => $w_mtbf_hours
            ];
        }

        // Calculate Monthly Metrics
        $avg_mttd = $count_mttd > 0 ? round($total_mttd / $count_mttd) : 0;
        $avg_mttr = $count_mttr > 0 ? round($total_mttr / $count_mttr) : 0;
        $avg_mdt = $count_mdt > 0 ? round($total_mdt / $count_mdt) : 0;

        // Operations MTBF = Total Scheduled Working Minutes in Month / Failures
        $scheduled_mins = $calendar->getScheduledMinutesInDateRange($first_day, $last_day);
        $scheduled_hours = $scheduled_mins / 60;
        
        $mtbf_hours = $total_failures > 0 ? round($scheduled_hours / $total_failures, 1) : round($scheduled_hours, 1);

        $raw_months[] = [
            'month' => $month_label,
            'real_mttd' => $avg_mttd,
            'real_mttr' => $avg_mttr,
            'real_mdt' => $avg_mdt,
            'real_mtbf' => $mtbf_hours,
            'failures' => $total_failures,
            'total_mttd' => $total_mttd,
            'count_mttd' => $count_mttd,
            'total_mttr' => $total_mttr,
            'count_mttr' => $count_mttr,
            'scheduled_hours' => $scheduled_hours,
            'weekly_processed' => $weekly_processed
        ];
    }

    // Now compute the 12 display months (from index 3 to 14 in raw_months)
    for ($m = 3; $m <= 14; $m++) {
        $r = $raw_months[$m];
        
        $calc_target_mttd = $target_mttd;
        $calc_target_mttr = $target_mttr;
        $calc_target_mtbf = $target_mtbf;
        
        if ($target_calc_mode === 'dynamic') {
            $sum_count_mttd = $raw_months[$m-1]['count_mttd'] + $raw_months[$m-2]['count_mttd'] + $raw_months[$m-3]['count_mttd'];
            $sum_total_mttd = $raw_months[$m-1]['total_mttd'] + $raw_months[$m-2]['total_mttd'] + $raw_months[$m-3]['total_mttd'];
            
            $sum_count_mttr = $raw_months[$m-1]['count_mttr'] + $raw_months[$m-2]['count_mttr'] + $raw_months[$m-3]['count_mttr'];
            $sum_total_mttr = $raw_months[$m-1]['total_mttr'] + $raw_months[$m-2]['total_mttr'] + $raw_months[$m-3]['total_mttr'];
            
            $sum_failures = $raw_months[$m-1]['failures'] + $raw_months[$m-2]['failures'] + $raw_months[$m-3]['failures'];
            $sum_sched = $raw_months[$m-1]['scheduled_hours'] + $raw_months[$m-2]['scheduled_hours'] + $raw_months[$m-3]['scheduled_hours'];
            
            $calc_target_mttd = $sum_count_mttd > 0 ? round($sum_total_mttd / $sum_count_mttd) : $target_mttd;
            $calc_target_mttr = $sum_count_mttr > 0 ? round($sum_total_mttr / $sum_count_mttr) : $target_mttr;
            $calc_target_mtbf = $sum_failures > 0 ? round($sum_sched / $sum_failures, 1) : round($sum_sched, 1);
        }
        
        $months_data[] = [
            'month' => $r['month'],
            'real_mttd' => $r['real_mttd'],
            'real_mttr' => $r['real_mttr'],
            'real_mdt' => $r['real_mdt'],
            'real_mtbf' => $r['real_mtbf'],
            'failures' => $r['failures'],
            'target_mttd' => $calc_target_mttd,
            'target_mttr' => $calc_target_mttr,
            'target_mtbf' => $calc_target_mtbf
        ];
        $weekly_data[$r['month']] = $r['weekly_processed'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'monthly' => $months_data,
            'weekly' => $weekly_data,
            'targets' => [
                'mttd' => $target_mttd,
                'mttr' => $target_mttr,
                'mtbf' => $target_mtbf
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
