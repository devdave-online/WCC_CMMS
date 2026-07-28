-- 0004_create_audit_log_table.sql
-- Phase 5: Full audit logging foundation.
-- Purpose: Centralized table for recording critical changes across the system
--          (ticket lifecycle, work orders, inventory movements, PO changes, user actions, etc.).
-- This will allow compliance, debugging, and history beyond free-text fields.
--
-- Columns:
--   actor_user_id: who did it (nullable for system/cron)
--   action: short code like 'ticket.create', 'inventory.deduct', 'po.status_change'
--   entity_type, entity_id: what was affected
--   before_json, after_json: snapshot of relevant data (for diffing)
--   notes: human readable or extra context
--
-- Idempotent safe.

CREATE TABLE IF NOT EXISTS `audit_log` (
    `log_id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actor_user_id`   INT(11)      NULL,
    `action`          VARCHAR(100) NOT NULL,
    `entity_type`     VARCHAR(50)  NOT NULL,
    `entity_id`       VARCHAR(100) NOT NULL,
    `before_json`     LONGTEXT     NULL CHECK (JSON_VALID(`before_json`) OR `before_json` IS NULL),
    `after_json`      LONGTEXT     NULL CHECK (JSON_VALID(`after_json`) OR `after_json` IS NULL),
    `notes`           TEXT         NULL,
    PRIMARY KEY (`log_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_actor` (`actor_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional seed comment for existing data if needed later.
