-- 0002_add_closed_by_to_active_tickets.sql
-- Phase 5 migration
-- Purpose: Formalize the 'closed_by' column that was previously added on-demand
--          by api/submit_closeout.php (the zero-downtime ALTER fallback).
-- Date added to plan: 2026-07-12
-- Author: David
--
-- This column records which supervisor/user performed the final closeout on a ticket.
-- Used in history views, statistics, and closeout flows.

-- Idempotent guard (works on MySQL 8.0.19+; for older: run manually or wrap in procedure)
ALTER TABLE active_tickets 
    ADD COLUMN IF NOT EXISTS closed_by VARCHAR(255) DEFAULT 'Unknown';

-- Optional: backfill any existing CLOSED rows that might be missing it
-- (safe - only touches rows that have NULL/empty)
UPDATE active_tickets 
SET closed_by = COALESCE(closed_by, 'Unknown') 
WHERE status = 'CLOSED' AND (closed_by IS NULL OR closed_by = '');

-- After applying this migration, the runtime ALTER fallback in submit_closeout.php
-- can eventually be simplified (kept for now as extra safety during rollout).
