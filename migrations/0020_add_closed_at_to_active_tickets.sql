-- 0020_add_closed_at_to_active_tickets.sql
-- When a ticket is closed, history must sort by close time (not open/created time).
-- Without closed_at, a ticket opened last week and closed today sinks far down the archive.

ALTER TABLE active_tickets
    ADD COLUMN IF NOT EXISTS closed_at TIMESTAMP NULL DEFAULT NULL AFTER closed_by;

-- Best-effort backfill: use created_at when already CLOSED (better than NULL)
UPDATE active_tickets
   SET closed_at = created_at
 WHERE status = 'CLOSED'
   AND closed_at IS NULL;
