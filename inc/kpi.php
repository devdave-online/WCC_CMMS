<?php
/**
 * inc/kpi.php — WCC CMMS reliability KPI engine (THE single source of truth).
 *
 * Every reliability figure in the product comes from here. `_rpt/statistics.php`
 * is the reference caller; the ops-page Factory Health chips, the trend API and
 * the diagnostics page all call these same functions, so a machine reads the
 * same number on every screen. There is no second engine — if a KPI looks wrong,
 * it is wrong here and nowhere else.
 *
 * These are PURE functions: give them ticket/action rows plus a ShiftCalendar and
 * they return numbers. No database access, no output — which is exactly what lets
 * the accuracy test harness drive 200+ constructed datasets through them.
 *
 * ── The model (all times are shift-adjusted working minutes) ──────────────────
 *
 *   Per repaired ticket, from its report stamp and its action rows:
 *     MTTA  = report            -> first action start   (response / acknowledge)
 *     MTTR  = first action start -> last action end      (elapsed repair window)
 *     MDT   = report            -> last action end        (total down time)   = MTTA + MTTR
 *     Active= union of action intervals                   (hands-on, parallel-safe)
 *     Ghost = MTTR - Active                               (idle within the repair)  MTTR = Active + Ghost
 *     Hold  = gaps that follow an explicit "Put on Hold"  (a named slice of Ghost)
 *     Labour= sum of every action's own duration          (effort; parallel work counts fully)
 *
 *   Reliability (two distinct, deliberately different statistics):
 *     Per-asset MTBF = asset uptime / asset failures
 *     Plant  MTBF    = total fleet uptime / total failures   (includes never-failed assets)
 *   where uptime = scheduled operating minutes - shift-adjusted downtime.
 *
 *   Availability = (scheduled - downtime) / scheduled, fleet-wide by default.
 *
 * MTTR was previously "sum of active labour" (which double-counts parallel techs and
 * can exceed the actual repair window). That number is kept, correctly, as Labour —
 * a workload metric — while MTTR is now the standard elapsed repair time.
 */

require_once __DIR__ . '/shift_calendar.php';

/**
 * Reliability event classes. Every ticket has one (schema default 'failure').
 * Only the classes an admin marks as "counts as a failure" feed the MTBF failure
 * count — downtime, availability and the time metrics still include every closed
 * corrective ticket, because the machine really was down regardless of cause.
 */
const WCC_EVENT_CLASSES = [
    'failure'    => 'Failure / Breakdown',
    'induced'    => 'Induced / Secondary damage',
    'inspection' => 'Inspection / PM check',
    'no_fault'   => 'No Fault Found',
    'setup'      => 'Setup / Changeover',
    'request'    => 'Request / Facilities',
];
const WCC_EVENT_CLASS_DEFAULT_FAILURES = ['failure', 'induced'];

/** Which event-class keys currently count as a failure (from app_settings, cached). */
function wcc_kpi_failure_classes(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = WCC_EVENT_CLASS_DEFAULT_FAILURES;
    try {
        $v = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'kpi_failure_classes'")->fetchColumn();
        if ($v !== false && $v !== null) {
            $arr = json_decode($v, true);
            if (is_array($arr)) {
                // Keep only known keys, drop anything stale.
                $cache = array_values(array_intersect($arr, array_keys(WCC_EVENT_CLASSES)));
            }
        }
    } catch (Throwable $e) { /* keep default */ }
    return $cache;
}

/** Does a ticket's class count as a failure? Unknown/blank class is treated as 'failure'. */
function wcc_kpi_counts_as_failure(?string $class, array $failureClasses): bool
{
    $class = ($class === null || $class === '') ? 'failure' : $class;
    return in_array($class, $failureClasses, true);
}

/**
 * Is this action the synthetic zero-minute marker written by api/submit_hold.php
 * when a ticket is placed on hold? Such markers carry no hands-on time; the gap
 * that follows one is genuine waiting (typically for parts).
 */
function wcc_kpi_is_hold_marker(array $action): bool
{
    if (isset($action['root_cause']) && strcasecmp(trim((string)$action['root_cause']), 'On Hold') === 0) {
        return true;
    }
    if (isset($action['action_taken']) && stripos((string)$action['action_taken'], 'PLACED ON HOLD') !== false) {
        return true;
    }
    return false;
}

/**
 * Time metrics for a single ticket, all shift-adjusted working minutes.
 *
 * @param array        $ticket  needs report_date and report_time
 * @param array        $actions that ticket's rows from ticket_actions (each with action_start/action_end)
 * @param ShiftCalendar $cal
 * @return array{mtta:int,mttr:int,mdt:int,active:int,ghost:int,hold:int,labour:int,measurable:bool}
 *         measurable=false when the ticket has no usable action (excluded from the time averages).
 */
function wcc_kpi_ticket_metrics(array $ticket, array $actions, ShiftCalendar $cal): array
{
    $blank = ['mtta' => 0, 'mttr' => 0, 'mdt' => 0, 'active' => 0, 'ghost' => 0, 'hold' => 0, 'labour' => 0, 'measurable' => false];

    $report = strtotime(($ticket['report_date'] ?? '') . ' ' . ($ticket['report_time'] ?? '00:00:00'));
    if ($report === false) return $blank;

    // Keep only actions with a sane, non-inverted start/end. Bad rows (MySQL
    // zero-dates, un-parseable stamps, pre-epoch garbage) are dropped, not trusted —
    // strtotime('0000-00-00 …') returns an ancient timestamp, not false, so it must
    // be screened explicitly or a single bad row detonates the whole average.
    $valid = [];
    foreach ($actions as $a) {
        $sr = trim((string)($a['action_start'] ?? ''));
        $er = trim((string)($a['action_end'] ?? ''));
        if ($sr === '' || $er === '') continue;
        if (strncmp($sr, '0000-00-00', 10) === 0 || strncmp($er, '0000-00-00', 10) === 0) continue;
        $s = strtotime($sr);
        $e = strtotime($er);
        if ($s === false || $e === false || $s <= 0 || $e <= 0 || $e < $s) continue;
        $valid[] = ['start' => $s, 'end' => $e, 'hold' => wcc_kpi_is_hold_marker($a)];
    }
    if (empty($valid)) return $blank;

    usort($valid, fn($x, $y) => $x['start'] <=> $y['start']);
    $firstStart = $valid[0]['start'];
    $lastEnd    = max(array_column($valid, 'end'));

    $mtta = $cal->getWorkingMinutes($report, $firstStart);       // response
    $mdt  = $cal->getWorkingMinutes($report, $lastEnd);          // total down time
    $mttr = $cal->getWorkingMinutes($firstStart, $lastEnd);      // elapsed repair window

    // Hands-on = shift-adjusted union of the action intervals, so two technicians
    // working the same hour count as one hour, not two.
    $merged = $cal->mergeIntervals(array_map(fn($v) => ['start' => $v['start'], 'end' => $v['end']], $valid));
    $active = 0;
    foreach ($merged as $m) $active += $cal->getWorkingMinutes($m['start'], $m['end']);

    $ghost = max(0, $mttr - $active);                            // idle within the repair

    // Repair labour (effort): every action's own duration, summed. Parallel work
    // is fully counted here on purpose — this is the workload metric, not a clock.
    $labour = 0;
    foreach ($valid as $v) $labour += $cal->getWorkingMinutes($v['start'], $v['end']);

    // Explicit on-hold: the gap from each hold marker to the next action that starts
    // after it. It is a subset of Ghost by construction (a marker adds no hands-on time).
    $hold = 0;
    $count = count($valid);
    for ($i = 0; $i < $count; $i++) {
        if (!$valid[$i]['hold']) continue;
        $next = null;
        for ($j = 0; $j < $count; $j++) {
            if ($valid[$j]['start'] > $valid[$i]['start']) { $next = $valid[$j]['start']; break; }
        }
        if ($next !== null) $hold += $cal->getWorkingMinutes($valid[$i]['start'], $next);
    }
    $hold = min($hold, $ghost);

    return ['mtta' => $mtta, 'mttr' => $mttr, 'mdt' => $mdt, 'active' => $active, 'ghost' => $ghost, 'hold' => $hold, 'labour' => $labour, 'measurable' => true];
}

/**
 * Average a set of per-ticket metric rows (only measurable ones should be passed in).
 * Means are taken over the same population, so MDT = MTTA + MTTR and MTTR = Active + Ghost
 * survive aggregation (to the minute, modulo rounding).
 *
 * @param array $rows results of wcc_kpi_ticket_metrics()
 * @return array{count:int,mtta:int,mttr:int,mdt:int,active:int,ghost:int,hold:int,labour:int}
 */
function wcc_kpi_aggregate(array $rows): array
{
    $n = count($rows);
    if ($n === 0) {
        return ['count' => 0, 'mtta' => 0, 'mttr' => 0, 'mdt' => 0, 'active' => 0, 'ghost' => 0, 'hold' => 0, 'labour' => 0];
    }
    $mean = fn(string $k) => (int)round(array_sum(array_column($rows, $k)) / $n);
    return [
        'count'  => $n,
        'mtta'   => $mean('mtta'),
        'mttr'   => $mean('mttr'),
        'mdt'    => $mean('mdt'),
        'active' => $mean('active'),
        'ghost'  => $mean('ghost'),
        'hold'   => $mean('hold'),
        'labour' => $mean('labour'),
    ];
}

/**
 * Reliability for one asset over the window.
 *
 * @param array         $downtimeWindows raw [{start,end}] down periods (report -> last action end) per event
 * @param int           $scheduledMinutes the asset's scheduled operating minutes in the window
 * @param int           $failures         number of failure-classified events on this asset
 * @param ShiftCalendar $cal
 * @return array{downtime:int,uptime:int,availability:float,mtbf:?int,failures:int}
 *         mtbf is null when there were no failures — there is no interval between failures to average.
 */
function wcc_kpi_asset_reliability(array $downtimeWindows, int $scheduledMinutes, int $failures, ShiftCalendar $cal): array
{
    $merged = $cal->mergeIntervals($downtimeWindows);           // overlapping faults are not double-counted
    $downtime = 0;
    foreach ($merged as $m) $downtime += $cal->getWorkingMinutes($m['start'], $m['end']);
    $downtime = min($downtime, max(0, $scheduledMinutes));
    $uptime   = max(0, $scheduledMinutes - $downtime);
    $avail    = $scheduledMinutes > 0 ? round(($uptime / $scheduledMinutes) * 100, 1) : 100.0;
    $mtbf     = $failures > 0 ? (int)round($uptime / $failures) : null;

    return ['downtime' => $downtime, 'uptime' => $uptime, 'availability' => $avail, 'mtbf' => $mtbf, 'failures' => $failures];
}

/**
 * Roll individual asset reliability rows up to the plant.
 *
 * @param array $assets rows from wcc_kpi_asset_reliability() for EVERY asset (incl. never-failed ones)
 * @return array{uptime:int,downtime:int,scheduled:int,failures:int,mtbf:?int,
 *               availability_fleet:float,availability_failed:float,failed_assets:int}
 */
function wcc_kpi_plant_rollup(array $assets): array
{
    $uptime = 0; $downtime = 0; $scheduled = 0; $failures = 0;
    $failedUptime = 0; $failedScheduled = 0; $failedCount = 0;

    foreach ($assets as $a) {
        $sched = $a['uptime'] + $a['downtime'];
        $uptime    += $a['uptime'];
        $downtime  += $a['downtime'];
        $scheduled += $sched;
        $failures  += $a['failures'];
        if ($a['failures'] > 0) {
            $failedUptime    += $a['uptime'];
            $failedScheduled += $sched;
            $failedCount++;
        }
    }

    return [
        'uptime'    => $uptime,
        'downtime'  => $downtime,
        'scheduled' => $scheduled,
        'failures'  => $failures,
        'mtbf'      => $failures > 0 ? (int)round($uptime / $failures) : null,
        // Fleet-wide (standard) vs the focused "assets that actually failed" view.
        'availability_fleet'  => $scheduled > 0 ? round(($uptime / $scheduled) * 100, 1) : 100.0,
        'availability_failed' => $failedScheduled > 0 ? round(($failedUptime / $failedScheduled) * 100, 1) : 100.0,
        'failed_assets' => $failedCount,
    ];
}

/**
 * The "glance" chips shown on the operations pages (Factory Health on
 * _maint/active_tickets.php, the header strip on _maint/pm_calendar.php).
 *
 * Deliberately a ROLLING window, not month-to-date. Month-to-date collapses at the
 * turn of every month: on the 1st the range is a single day, so there are no closed
 * failures yet, MTBF comes back null and the chip renders "—" even though the plant
 * has plenty of history. A trailing 30 days is always populated and is the usual
 * reading of a headline reliability number anyway.
 *
 * Shift parameters mirror _rpt/statistics.php so the chips and the dashboard agree.
 *
 * @return array{mtbf:?float,labour:int,from:string,to:string,failures:int}
 *         mtbf in HOURS (null only when the window genuinely had no failures),
 *         labour in minutes (mean hands-on repair effort per ticket).
 */
function wcc_kpi_glance(PDO $pdo, int $days = 30): array
{
    $from = date('Y-m-d', strtotime('-' . $days . ' days'));
    $to   = date('Y-m-d');

    $holJson  = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='plant_holidays'")->fetchColumn() ?: '[]';
    $cal      = new ShiftCalendar('06:00:00', '22:00:00', [1, 2, 3, 4, 5], json_decode($holJson, true) ?? []);
    $op       = wcc_kpi_window_summary($pdo, $from, $to, $cal, 16, [1, 2, 3, 4, 5]);

    return [
        'mtbf'     => $op['mtbf'] === null ? null : round($op['mtbf'] / 60, 1), // hours
        'labour'   => $op['labour'],                                            // minutes, mean per ticket
        'failures' => $op['failures'],
        'from'     => $from,
        'to'       => $to,
    ];
}

/**
 * DB-backed convenience: the whole KPI picture for a date range, computed with the
 * same pure functions the dashboard uses. This is what the lighter consumers call
 * — the ops-page Factory Health chips, the trend API and the diagnostics page — so
 * every screen agrees. `_rpt/statistics.php` runs its own richer fetch (it also needs
 * per-asset tables, technician workload, parts) but through these same functions,
 * so a like-for-like window matches this to the minute.
 *
 * Scheduling mirrors the dashboard: working weekdays in range × shift hours. Holidays
 * are honoured by the ShiftCalendar for downtime.
 *
 * @return array time metrics (mtta,mttr,mdt,active,ghost,hold,labour), failures,
 *               plant mtbf (minutes|null), availability_fleet/failed, scheduled_per_asset.
 */
function wcc_kpi_window_summary(PDO $pdo, string $startDate, string $endDate, ShiftCalendar $cal, int $shiftHours, array $workingDays, ?array $failureClasses = null): array
{
    $failureClasses = $failureClasses ?? wcc_kpi_failure_classes($pdo);

    $workingDayCount = 0;
    $d = strtotime($startDate); $endT = strtotime($endDate);
    while ($d !== false && $d <= $endT) {
        if (in_array((int)date('N', $d), $workingDays, true)) $workingDayCount++;
        $d = strtotime('+1 day', $d);
    }
    $scheduledPerAsset = $workingDayCount * $shiftHours * 60;

    $tk = $pdo->prepare("SELECT ticket_id, equip_id, report_date, report_time, event_class
                           FROM active_tickets
                          WHERE status = 'CLOSED' AND report_date >= ? AND report_date <= ?");
    $tk->execute([$startDate, $endDate]);
    $tickets = $tk->fetchAll(PDO::FETCH_ASSOC);

    $ax = $pdo->prepare("SELECT a.ticket_id, a.action_start, a.action_end, a.root_cause, a.action_taken
                           FROM ticket_actions a
                           JOIN active_tickets t ON a.ticket_id = t.ticket_id
                          WHERE t.status = 'CLOSED' AND t.report_date >= ? AND t.report_date <= ?
                          ORDER BY a.action_start ASC");
    $ax->execute([$startDate, $endDate]);
    $byTicket = [];
    foreach ($ax->fetchAll(PDO::FETCH_ASSOC) as $a) $byTicket[$a['ticket_id']][] = $a;

    $rows = []; $assetData = [];
    foreach ($tickets as $t) {
        $eid = $t['equip_id'];
        if (!isset($assetData[$eid])) $assetData[$eid] = ['windows' => [], 'failures' => 0];

        $m = wcc_kpi_ticket_metrics($t, $byTicket[$t['ticket_id']] ?? [], $cal);
        if (!$m['measurable']) continue;

        $m['is_failure'] = wcc_kpi_counts_as_failure($t['event_class'] ?? 'failure', $failureClasses);
        $rows[] = $m;
        // Failure COUNT is class-filtered; downtime windows below include every
        // closed corrective ticket (the machine was down regardless of cause).
        if ($m['is_failure']) $assetData[$eid]['failures']++;

        $report = strtotime($t['report_date'] . ' ' . $t['report_time']);
        $ends = [];
        foreach (($byTicket[$t['ticket_id']] ?? []) as $a) {
            if (empty($a['action_end']) || strncmp((string)$a['action_end'], '0000-00-00', 10) === 0) continue;
            $e = strtotime((string)$a['action_end']);
            if ($e !== false && $e > 0) $ends[] = $e;
        }
        if ($ends) $assetData[$eid]['windows'][] = ['start' => $report, 'end' => max($ends)];
    }

    // Roll up over every machine, so plant MTBF and availability are fleet-wide.
    $allRows = [];
    foreach ($assetData as $eid => $dta) {
        $allRows[$eid] = wcc_kpi_asset_reliability($dta['windows'], $scheduledPerAsset, $dta['failures'], $cal);
    }
    foreach ($pdo->query("SELECT equip_id FROM equipment")->fetchAll(PDO::FETCH_COLUMN) as $eid) {
        if (!isset($allRows[$eid])) $allRows[$eid] = wcc_kpi_asset_reliability([], $scheduledPerAsset, 0, $cal);
    }

    $plant = wcc_kpi_plant_rollup($allRows);
    $agg   = wcc_kpi_aggregate($rows);

    return [
        'count'               => $agg['count'],                 // measurable tickets (time-metric population)
        'failures'            => $plant['failures'],
        'mtta'                => $agg['mtta'],
        'mttr'                => $agg['mttr'],
        'mdt'                 => $agg['mdt'],
        'active'              => $agg['active'],
        'ghost'               => $agg['ghost'],
        'hold'                => $agg['hold'],
        'labour'              => $agg['labour'],
        'mtbf'                => $plant['mtbf'],                 // minutes, or null
        'uptime'              => $plant['uptime'],              // fleet uptime minutes (for rolling targets)
        'downtime'            => $plant['downtime'],
        'availability_fleet'  => $plant['availability_fleet'],
        'availability_failed' => $plant['availability_failed'],
        'scheduled_per_asset' => $scheduledPerAsset,
        'working_days'        => $workingDayCount,
    ];
}
