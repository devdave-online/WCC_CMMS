<?php
/**
 * WCC CMMS — what "overdue" means for a work order.
 *
 * The dashboard and the Work Orders list each carried their own copy of this rule
 * (`status IN ('Scheduled','In Progress') AND scheduled_date < CURDATE()`), which
 * was wrong in both directions:
 *
 *   - it counted IN PROGRESS work as overdue. A job someone started yesterday and
 *     is still working on today is in flight, not late.
 *   - it ignored MISSED work orders entirely — the ones that genuinely were not
 *     done — so the alert reported phantom problems while hiding the real ones.
 *
 * A work order is overdue when its scheduled date has passed and nobody has picked
 * it up, or when it has already been marked Missed. Once work starts it stops being
 * overdue; once it is Completed or Cancelled it is closed.
 */

/** SQL predicate over an aliased work_orders table. */
function wcc_wo_overdue_sql(string $alias = 'w'): string
{
    return "(({$alias}.status = 'Scheduled' AND {$alias}.scheduled_date < CURDATE()) OR {$alias}.status = 'Missed')";
}

/** Same rule, applied to an already-fetched row. $today is the DB's CURDATE(). */
function wcc_wo_is_overdue(array $wo, ?string $today = null): bool
{
    $status = $wo['status'] ?? '';
    if ($status === 'Missed') return true;
    if ($status !== 'Scheduled') return false;          // In Progress / Completed / Cancelled

    $sched = $wo['scheduled_date'] ?? '';
    if ($sched === '' || $sched === null) return false;

    $today = $today ?: date('Y-m-d');
    return strtotime($sched) < strtotime($today);
}

/** Count of overdue work orders, for dashboard badges. */
function wcc_wo_overdue_count(PDO $pdo): int
{
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM work_orders w WHERE " . wcc_wo_overdue_sql('w'))->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
