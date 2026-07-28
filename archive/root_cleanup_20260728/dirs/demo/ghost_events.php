<?php
/**
 * demo/ghost_events.php — demo data that exercises the two Phase-4 KPI features:
 *
 *   1. Ghost / On-Hold time — a few CLOSED tickets whose repair paused ("awaiting
 *      part") mid-way, so the dashboard's Ghost Time and its On-Hold slice are non-zero.
 *   2. Event classification — reclassifies a handful of recent closed tickets to
 *      non-failure classes (inspection / no-fault / request), so MTBF (failures only)
 *      differs from the total, and the population toggle shows a real difference.
 *
 * Idempotent: re-running wipes its own TK-GHOST-* rows first and re-applies. It only
 * touches demo data. Hooked into demo/demo_seed.php and runnable standalone via
 * demo/apply_ghost_events.php.
 */

function wcc_demo_ghost_tickets(): array
{
    // report + [ [start,end,hold?,taken], ... ]. Dates are recent weekdays so they land
    // in the 30-day dashboard window and inside the shift calendar (Mon-Fri 06:00-22:00).
    return [
        [
            'id' => 'TK-GHOST-001', 'report' => '2026-07-15 08:00:00',
            'fault' => 'Hydraulic pressure loss on clamp unit — awaiting seal kit',
            'pic' => 'Jide Okafor', 'tech' => 'Jide Okafor',
            'actions' => [
                ['2026-07-15 08:30:00', '2026-07-15 09:30:00', false, 'Diagnosis: main clamp seal blown, pressure dropping under load.'],
                ['2026-07-15 10:00:00', '2026-07-15 10:00:00', true,  "PLACED ON HOLD\nReason: Awaiting Parts\nExplanation: Seal kit not in stores, drawn from vendor."],
                ['2026-07-15 14:00:00', '2026-07-15 15:30:00', false, 'Seal kit received, cylinder reseated and pressure tested to spec.'],
            ],
        ],
        [
            'id' => 'TK-GHOST-002', 'report' => '2026-07-20 09:00:00',
            'fault' => 'Servo drive fault, axis 3 — replacement drive on next-day delivery',
            'pic' => 'Priya Nair', 'tech' => 'Priya Nair',
            'actions' => [
                ['2026-07-20 09:30:00', '2026-07-20 10:15:00', false, 'Drive throwing overcurrent; confirmed failed output stage.'],
                ['2026-07-20 10:15:00', '2026-07-20 10:15:00', true,  "PLACED ON HOLD\nReason: Awaiting Parts\nExplanation: Replacement servo drive on next-day courier."],
                ['2026-07-21 07:00:00', '2026-07-21 09:00:00', false, 'New drive fitted, parameters restored from backup, axis homed and verified.'],
            ],
        ],
        [
            'id' => 'TK-GHOST-003', 'report' => '2026-07-22 13:00:00',
            'fault' => 'Conveyor gearbox noise — bearing on order overnight',
            'pic' => 'Rui Silva', 'tech' => 'Rui Silva',
            'actions' => [
                ['2026-07-22 13:30:00', '2026-07-22 14:00:00', false, 'Gearbox input bearing rumble; stripped and confirmed spalling.'],
                ['2026-07-22 14:00:00', '2026-07-22 14:00:00', true,  "PLACED ON HOLD\nReason: Awaiting Parts\nExplanation: Bearing ordered, overnight delivery."],
                ['2026-07-23 08:00:00', '2026-07-23 10:00:00', false, 'Bearing pressed in, gearbox refilled and run-in; noise gone.'],
            ],
        ],
    ];
}

/** Apply the ghost/on-hold tickets + a few non-failure reclassifications to $pdo. */
function wcc_demo_apply_ghost_events(PDO $pdo): array
{
    $out = ['ghost_tickets' => 0, 'ghost_actions' => 0, 'reclassified' => 0];

    // Pick a few real equipment ids to hang the tickets on.
    $equipIds = $pdo->query("SELECT equip_id FROM equipment ORDER BY equip_id LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    if (count($equipIds) < 1) return $out;

    // 1. Clean prior ghost rows (idempotent).
    $pdo->exec("DELETE FROM ticket_actions WHERE ticket_id LIKE 'TK-GHOST-%'");
    $pdo->exec("DELETE FROM active_tickets WHERE ticket_id LIKE 'TK-GHOST-%'");

    $tk = $pdo->prepare("INSERT INTO active_tickets
        (ticket_id, equip_id, report_date, report_time, announced_by, pic, fault_desc, priority, status, closed_by, event_class, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'high', 'CLOSED', ?, 'failure', ?)");
    $ta = $pdo->prepare("INSERT INTO ticket_actions
        (ticket_id, tech_name, action_start, action_end, fault_type, root_cause, action_taken, parts_used, timestamp_logged)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach (wcc_demo_ghost_tickets() as $i => $g) {
        $equip = $equipIds[$i % count($equipIds)];
        [$rDate, $rTime] = explode(' ', $g['report']);
        $tk->execute([$g['id'], $equip, $rDate, $rTime, 'Production', $g['pic'], $g['fault'], $g['tech'], $g['report']]);
        $out['ghost_tickets']++;
        foreach ($g['actions'] as $a) {
            [$start, $end, $isHold, $taken] = $a;
            $ta->execute([
                $g['id'], $g['tech'], $start, $end,
                $isHold ? 'Other' : 'Mechanical',
                $isHold ? 'On Hold' : 'Wear',
                $taken,
                'None',
                $end,
            ]);
            $out['ghost_actions']++;
        }
    }

    // 2. Reclassify a handful of recent closed tickets to non-failure classes so the
    //    "failures only" view and MTBF classification visibly differ. Deterministic:
    //    the most recent real (non-ghost) closed tickets, rotated across classes.
    $recent = $pdo->query("SELECT ticket_id FROM active_tickets
                            WHERE status='CLOSED' AND ticket_id NOT LIKE 'TK-GHOST-%'
                              AND report_date >= (CURDATE() - INTERVAL 25 DAY)
                            ORDER BY report_date DESC, ticket_id DESC LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
    $classes = ['inspection', 'inspection', 'no_fault', 'no_fault', 'request', 'request'];
    $upd = $pdo->prepare("UPDATE active_tickets SET event_class = ? WHERE ticket_id = ?");
    foreach ($recent as $idx => $tid) {
        $upd->execute([$classes[$idx] ?? 'inspection', $tid]);
        $out['reclassified']++;
    }

    return $out;
}
