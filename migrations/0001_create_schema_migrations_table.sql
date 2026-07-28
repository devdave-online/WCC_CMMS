-- 0001_create_schema_migrations_table.sql
-- Phase 5: Foundational migration tracking table.
-- This table records which numbered .sql migrations have been applied.
-- Enables the migrate.php runner to compute "pending" vs "applied".
--
-- Date: 2026-07-12
-- Idempotent: CREATE TABLE IF NOT EXISTS + UNIQUE on filename.

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `filename`    VARCHAR(255) NOT NULL,
    `applied_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: ensure any pre-existing manual migrations are noted later via runner or manual insert.
-- Example (commented):
-- INSERT IGNORE INTO schema_migrations (filename) VALUES ('0002_add_closed_by_to_active_tickets.sql');
