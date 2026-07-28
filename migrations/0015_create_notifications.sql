-- 0015_create_notifications.sql
-- Purpose: Formalize the per-user notification center.
--   The `notifications` table already exists in the live DB (from an earlier
--   abandoned attempt) but was untracked by schema/migrations and unused by any
--   code. This makes it a tracked, reproducible object for fresh installs and
--   adds a `severity` column for the overlay's icon/colour.
--
-- Used by: inc/notifications.php, nav.php (bell + overlay), api/notifications.php
-- Safe / idempotent.

CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `type`       VARCHAR(50)  NOT NULL DEFAULT 'system',
    `message`    TEXT         NOT NULL,
    `link`       VARCHAR(255) DEFAULT NULL,
    `severity`   VARCHAR(10)  NOT NULL DEFAULT 'info',
    `is_read`    TINYINT(1)   DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_notif_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If the table pre-existed without `severity` (live DB), add it.
ALTER TABLE `notifications`
    ADD COLUMN IF NOT EXISTS `severity` VARCHAR(10) NOT NULL DEFAULT 'info' AFTER `link`;

-- Ensure the composite index exists on pre-existing tables too.
-- (CREATE INDEX has no IF NOT EXISTS in MariaDB < 10.5; guarded via a harmless
--  ADD that is ignored if present is not portable, so rely on the CREATE TABLE
--  above for fresh installs; live DB indexing handled manually if needed.)
