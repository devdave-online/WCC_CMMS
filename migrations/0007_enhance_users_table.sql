-- 0007_enhance_users_table.sql
-- Purpose: Expand users table with industry-standard profile fields for a professional CMMS.
-- Adds: email, full_name, phone, department, status, last_login, notes, workshop_id (for location scoping),
--       certifications (JSON), updated_at, must_change_password (if missing).
-- This brings user management closer to leaders like Limble/UpKeep: rich profiles, status, last activity, multi-site.
-- Idempotent with IF NOT EXISTS / ADD COLUMN IF NOT EXISTS.
-- Safe to run multiple times.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) NULL UNIQUE COMMENT 'Primary contact for invites/notifications',
    ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active','inactive','pending') NOT NULL DEFAULT 'active' COMMENT 'Account status',
    ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL COMMENT 'Last successful login for activity tracking',
    ADD COLUMN IF NOT EXISTS `notes` TEXT NULL,
    ADD COLUMN IF NOT EXISTS `workshop_id` INT(11) NULL COMMENT 'Optional FK to workshops for location scoping',
    ADD COLUMN IF NOT EXISTS `certifications` LONGTEXT NULL COMMENT 'JSON array of skills/certs for labor assignment'
        CHECK (JSON_VALID(`certifications`) OR `certifications` IS NULL),
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Force change on next login';

-- Add FK if workshops table exists (safe)
-- ALTER TABLE `users` ADD CONSTRAINT IF NOT EXISTS `fk_users_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops`(`workshop_id`) ON DELETE SET NULL;

-- Backfill defaults for existing users (run once manually if needed after)
-- UPDATE `users` SET `status` = 'active' WHERE `status` IS NULL;
-- UPDATE `users` SET `must_change_password` = 1 WHERE `password_hash` = MD5('password') OR username = 'admin'; -- example

-- Optional index for performance
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_status` (`status`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_workshop` (`workshop_id`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_last_login` (`last_login`);
