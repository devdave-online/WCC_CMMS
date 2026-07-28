<?php
/**
 * tests/kpi_accuracy.php — accuracy harness for the KPI engine (inc/kpi.php).
 *
 *   php tests/kpi_accuracy.php
 *
 * Strategy: an INDEPENDENT reference implementation of shift-adjusted minutes
 * (brute-force, minute-by-minute — a different algorithm from ShiftCalendar's
 * segment clamping) computes the expected value for every metric. The engine is
 * then run over 250+ constructed scenarios and every field is asserted equal to
 * the reference, plus:
 *   - a set of hand-computed canonical scenarios (absolute anchors),
 *   - the identities MDT = MTTA + MTTR and MTTR = Active + Ghost, on every case,
 *   - Hold ⊆ Ghost, on every case,
 *   - asset reliability + plant rollup + classification + garbage-row rejection.
 *
 * No database: pure functions only. Exit code is non-zero if anything fails.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
require_once __DIR__ . '/../inc/kpi.php';   // pulls in shift_calendar.php

const SS = '06:00:00', SE = '22:00:00';
const WD = [1, 2, 3, 4, 5];
$HOLIDAYS = ['2026-07-16'];   // a Thursday, to exercise the holiday path
$CAL = new ShiftCalendar(SS, SE, WD, $HOLIDAYS);

$PASS = 0; $FAIL = 0; $FAILS = [];
function check($cond, $label, &$P, &$F, &$FA) { if ($cond) { $P++; } else { $F++; $FA[] = $label; } }

/* ---- Independent reference: shift-adjusted minutes, minute by minute ---- */
function refMin(int $s, int $e): int {
    global $HOLIDAYS;
    if ($e <= $s) return 0;
    $m = 0;
    for ($t = $s; $t < $e; $t += 60) {
        if (!in_array((int)date('N', $t), WD, true)) continue;
        $day = date('Y-m-d', $t);
        if (in_array($day, $HOLIDAYS, true)) continue;
        $base = strtotime($day . ' 00:00:00');
        $sod  = $t - $base;
        if ($sod >= (strtotime($day . ' ' . SS) - $base) && $sod < (strtotime($day . ' ' . SE) - $base)) $m++;
    }
    return $m;
}

/* Expected per-ticket metrics from raw (already-clean) actions, via the reference. */
function refTicket(string $report, array $actions): array {
    $rep = strtotime($report);
    $valid = [];
    foreach ($actions as $a) {
        $s = strtotime($a['action_start']); $e = strtotime($a['action_end']);
        if ($s === false || $e === false || $e < $s) continue;
        $valid[] = ['s' => $s, 'e' => $e, 'hold' => wcc_kpi_is_hold_marker($a)];
    }
    if (!$valid) return ['measurable' => false];
    usort($valid, fn($x, $y) => $x['s'] <=> $y['s']);
    $first = $valid[0]['s'];
    $last  = max(array_column($valid, 'e'));

    $mtta = refMin($rep, $first);
    $mdt  = refMin($rep, $last);
    $mttr = refMin($first, $last);

    // Active = minutes in [first,last) that are in-shift AND covered by some action.
    $active = 0;
    for ($t = $first; $t < $last; $t += 60) {
        if (refMin($t, $t + 60) === 0) continue;   // off-shift minute
        foreach ($valid as $v) { if ($t >= $v['s'] && $t < $v['e']) { $active++; break; } }
    }
    $ghost = max(0, $mttr - $active);

    $labour = 0;
    foreach ($valid as $v) $labour += refMin($v['s'], $v['e']);

    $hold = 0;
    foreach ($valid as $i => $v) {
        if (!$v['hold']) continue;
        $next = null;
        foreach ($valid as $w) { if ($w['s'] > $v['s']) { $next = $w['s']; break; } }
        if ($next !== null) $hold += refMin($v['s'], $next);
    }
    $hold = min($hold, $ghost);

    return compact('mtta', 'mttr', 'mdt', 'active', 'ghost', 'labour', 'hold') + ['measurable' => true];
}

/* ---- 1. Canonical hand-computed anchors ---- */
$canon = [
    ['label' => 'single clean action',
     'report' => '2026-07-20 08:00:00',
     'actions' => [['action_start' => '2026-07-20 08:30:00', 'action_end' => '2026-07-20 10:00:00']],
     'exp' => ['mtta' => 30, 'mttr' => 90, 'mdt' => 120, 'active' => 90, 'ghost' => 0, 'labour' => 90, 'hold' => 0]],
    ['label' => 'hold + resume same day',
     'report' => '2026-07-20 08:00:00',
     'actions' => [
        ['action_start' => '2026-07-20 08:30:00', 'action_end' => '2026-07-20 09:00:00'],
        ['action_start' => '2026-07-20 09:00:00', 'action_end' => '2026-07-20 09:00:00', 'root_cause' => 'On Hold'],
        ['action_start' => '2026-07-20 11:00:00', 'action_end' => '2026-07-20 11:30:00'],
     ],
     'exp' => ['mtta' => 30, 'mttr' => 180, 'mdt' => 210, 'active' => 60, 'ghost' => 120, 'labour' => 60, 'hold' => 120]],
    ['label' => 'parallel techs',
     'report' => '2026-07-20 08:00:00',
     'actions' => [
        ['action_start' => '2026-07-20 08:30:00', 'action_end' => '2026-07-20 09:30:00'],
        ['action_start' => '2026-07-20 09:00:00', 'action_end' => '2026-07-20 10:00:00'],
     ],
     'exp' => ['mtta' => 30, 'mttr' => 90, 'mdt' => 120, 'active' => 90, 'ghost' => 0, 'labour' => 120, 'hold' => 0]],
    ['label' => 'overnight (off-shift excluded)',
     'report' => '2026-07-20 21:30:00',
     'actions' => [['action_start' => '2026-07-21 06:30:00', 'action_end' => '2026-07-21 07:00:00']],
     'exp' => ['mtta' => 60, 'mttr' => 30, 'mdt' => 90, 'active' => 30, 'ghost' => 0, 'labour' => 30, 'hold' => 0]],
];
foreach ($canon as $c) {
    $m = wcc_kpi_ticket_metrics(['report_date' => explode(' ', $c['report'])[0], 'report_time' => explode(' ', $c['report'])[1]], $c['actions'], $CAL);
    foreach ($c['exp'] as $k => $v) check($m[$k] === $v, "canon[{$c['label']}].$k expected $v got {$m[$k]}", $PASS, $FAIL, $FAILS);
    check($m['mdt'] === $m['mtta'] + $m['mttr'], "canon[{$c['label']}] identity MDT", $PASS, $FAIL, $FAILS);
    check($m['mttr'] === $m['active'] + $m['ghost'], "canon[{$c['label']}] identity MTTR", $PASS, $FAIL, $FAILS);
}

/* ---- 2. Generated scenarios cross-checked vs the independent reference ---- */
mt_srand(20260724);
$days = ['2026-07-13','2026-07-14','2026-07-15','2026-07-16','2026-07-17','2026-07-20','2026-07-21','2026-07-22','2026-07-23','2026-07-24'];
$reportTimes = ['05:30:00','06:30:00','08:00:00','13:15:00','17:45:00','21:30:00','23:00:00'];
$genCount = 0;
for ($n = 0; $n < 260; $n++) {
    $day = $days[mt_rand(0, count($days) - 1)];
    $rt  = $reportTimes[mt_rand(0, count($reportTimes) - 1)];
    $report = "$day $rt";
    $cursor = strtotime($report) + mt_rand(5, 120) * 60;   // first action starts after report
    $nActions = mt_rand(1, 3);
    $actions = [];
    for ($k = 0; $k < $nActions; $k++) {
        $dur = mt_rand(10, 150) * 60;
        $start = $cursor;
        $end   = $start + $dur;
        $isHold = ($k < $nActions - 1) && mt_rand(0, 2) === 0;   // sometimes a hold (never last)
        if ($isHold) { $end = $start; }                          // zero-length marker
        $actions[] = [
            'action_start' => date('Y-m-d H:i:00', $start),
            'action_end'   => date('Y-m-d H:i:00', $end),
            'root_cause'   => $isHold ? 'On Hold' : 'Repair',
        ];
        // advance cursor: gap (maybe across a night) then next action
        $gap = mt_rand(0, 1) ? mt_rand(15, 240) * 60 : mt_rand(10, 20) * 3600;   // sometimes multi-hour/overnight
        $cursor = $end + $gap;
    }
    $exp = refTicket($report, $actions);
    $m = wcc_kpi_ticket_metrics(['report_date' => $day, 'report_time' => $rt], $actions, $CAL);
    $genCount++;
    if (!$exp['measurable']) { check($m['measurable'] === false, "gen[$n] should be non-measurable", $PASS, $FAIL, $FAILS); continue; }
    check($m['measurable'] === true, "gen[$n] measurable", $PASS, $FAIL, $FAILS);
    foreach (['mtta','mttr','mdt','active','ghost','labour','hold'] as $k) {
        check($m[$k] === $exp[$k], "gen[$n].$k ref={$exp[$k]} engine={$m[$k]} ($report, ".count($actions)." acts)", $PASS, $FAIL, $FAILS);
    }
    check($m['mdt'] === $m['mtta'] + $m['mttr'], "gen[$n] identity MDT", $PASS, $FAIL, $FAILS);
    check($m['mttr'] === $m['active'] + $m['ghost'], "gen[$n] identity MTTR", $PASS, $FAIL, $FAILS);
    check($m['hold'] <= $m['ghost'], "gen[$n] hold<=ghost", $PASS, $FAIL, $FAILS);
}

/* ---- 3. Garbage-row rejection ---- */
$garbage = [
    ['action_start' => '0000-00-00 00:00:00', 'action_end' => '2026-07-20 12:00:00'],   // MySQL zero-date start
    ['action_start' => '2026-07-20 12:00:00', 'action_end' => '0000-00-00 00:00:00'],   // zero-date end
    ['action_start' => '',                    'action_end' => '2026-07-20 12:00:00'],   // empty
    ['action_start' => '2026-07-20 12:00:00', 'action_end' => '2026-07-20 11:00:00'],   // inverted
];
$mg = wcc_kpi_ticket_metrics(['report_date' => '2026-07-20', 'report_time' => '08:00:00'], $garbage, $CAL);
check($mg['measurable'] === false, "all-garbage ticket is non-measurable (no explosion)", $PASS, $FAIL, $FAILS);
// one good action among garbage → measurable, only the good one counts
$mixed = array_merge($garbage, [['action_start' => '2026-07-20 08:30:00', 'action_end' => '2026-07-20 09:00:00']]);
$mm = wcc_kpi_ticket_metrics(['report_date' => '2026-07-20', 'report_time' => '08:00:00'], $mixed, $CAL);
check($mm['measurable'] === true && $mm['mttr'] === 30 && $mm['mtta'] === 30, "garbage ignored, good action counts", $PASS, $FAIL, $FAILS);

/* ---- 4. Asset reliability + plant rollup ---- */
$sched = 1000;
$ax = wcc_kpi_asset_reliability([['start' => strtotime('2026-07-20 08:00'), 'end' => strtotime('2026-07-20 09:40')]], $sched, 2, $CAL);
check($ax['downtime'] === 100 && $ax['uptime'] === 900 && $ax['mtbf'] === 450 && $ax['availability'] === 90.0, "asset X reliability", $PASS, $FAIL, $FAILS);
$overlap = wcc_kpi_asset_reliability([
    ['start' => strtotime('2026-07-20 08:00'), 'end' => strtotime('2026-07-20 09:00')],
    ['start' => strtotime('2026-07-20 08:30'), 'end' => strtotime('2026-07-20 09:30')],   // overlaps → merged 90m
], $sched, 2, $CAL);
check($overlap['downtime'] === 90, "overlapping downtime merged (not double-counted)", $PASS, $FAIL, $FAILS);
$ay = wcc_kpi_asset_reliability([], $sched, 0, $CAL);
check($ay['mtbf'] === null && $ay['availability'] === 100.0, "never-failed asset: null MTBF, 100% avail", $PASS, $FAIL, $FAILS);
$plant = wcc_kpi_plant_rollup([$ax, $ay]);
check($plant['mtbf'] === 950 && $plant['availability_fleet'] === 95.0 && $plant['availability_failed'] === 90.0 && $plant['failures'] === 2,
    "plant rollup (fleet 95%, failed-only 90%)", $PASS, $FAIL, $FAILS);

/* ---- 5. Classification helper ---- */
check(wcc_kpi_counts_as_failure('failure', ['failure','induced']) === true,  "class: failure counts", $PASS, $FAIL, $FAILS);
check(wcc_kpi_counts_as_failure('inspection', ['failure','induced']) === false, "class: inspection excluded", $PASS, $FAIL, $FAILS);
check(wcc_kpi_counts_as_failure(null, ['failure']) === true,  "class: null → treated as failure", $PASS, $FAIL, $FAILS);
check(wcc_kpi_counts_as_failure('', ['failure']) === true,    "class: empty → treated as failure", $PASS, $FAIL, $FAILS);

/* ---- 6. Aggregate identity over a population ---- */
$rows = [];
foreach ($canon as $c) {
    $rows[] = wcc_kpi_ticket_metrics(['report_date' => explode(' ', $c['report'])[0], 'report_time' => explode(' ', $c['report'])[1]], $c['actions'], $CAL);
}
$agg = wcc_kpi_aggregate($rows);
check($agg['count'] === count($rows), "aggregate count", $PASS, $FAIL, $FAILS);

/* ---- Summary ---- */
echo "\n=== KPI ACCURACY HARNESS ===\n";
echo "Generated scenarios : $genCount\n";
echo "Assertions passed   : $PASS\n";
echo "Assertions failed   : $FAIL\n";
if ($FAIL > 0) {
    echo "\nFAILURES (first 20):\n";
    foreach (array_slice($FAILS, 0, 20) as $f) echo "  ✗ $f\n";
    exit(1);
}
echo "\n✅ ALL PASS — engine matches the independent reference across every scenario.\n";
exit(0);
