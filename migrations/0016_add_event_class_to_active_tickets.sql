-- 0016_add_event_class_to_active_tickets.sql
-- Purpose: Classify what kind of event a ticket is, so reliability maths (MTBF)
--          can count only genuine FAILURES instead of treating every closed ticket
--          as a breakdown. Inspections, no-fault-found, changeovers and facility
--          requests are downtime but not failures.
--
--          Class keys are defined in inc/kpi.php (WCC_EVENT_CLASSES). Which classes
--          COUNT as a failure is admin-configurable in app_settings.kpi_failure_classes
--          (default: failure + induced).
--
-- Non-breaking: the column defaults to 'failure', so every existing row and every
--          new ticket stays exactly as it is today until deliberately reclassified.
--          The KPI numbers are unchanged the instant this migration runs.
--
-- Used by: inc/kpi.php, _rpt/statistics.php, api/get_historical_kpis.php,
--          api/cron_analytics.php, register.php + api/submit_ticket.php.
--
-- Safe / idempotent.

ALTER TABLE `active_tickets`
    ADD COLUMN IF NOT EXISTS `event_class` VARCHAR(32) NOT NULL DEFAULT 'failure'
        COMMENT 'Reliability event class (failure|induced|inspection|no_fault|setup|request); see inc/kpi.php';

-- Belt-and-suspenders: guarantee no NULL/empty slips through on odd engines.
UPDATE `active_tickets` SET `event_class` = 'failure'
 WHERE `event_class` IS NULL OR `event_class` = '';
