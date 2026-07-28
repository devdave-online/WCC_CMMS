-- 0008_add_badge_number_and_registration_config.sql
-- Add badge_number as the public safe identifier (I-badge number)
-- Hide user_id from UI
-- New table for Registration Configurator: which fields to collect at user registration
-- Fields can be toggled; missing data uses anonym defaults: "Not used", "TBD", "N/A"

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `badge_number` VARCHAR(50) NULL UNIQUE COMMENT 'I-Badge / Employee badge number - public safe ID for UI and login safety (TISAX compliant)';

-- New config table for what data to collect during registration
CREATE TABLE IF NOT EXISTS `user_registration_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `field_name` VARCHAR(50) NOT NULL COMMENT 'e.g. full_name, email, phone, department, workshop_id, certifications, notes',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `label` VARCHAR(100) NOT NULL,
    `display_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default config (common fields)
INSERT IGNORE INTO `user_registration_config` (field_name, is_enabled, is_required, label, display_order) VALUES
('full_name', 1, 1, 'Full Name', 1),
('email', 1, 1, 'Email', 2),
('phone', 1, 0, 'Phone', 3),
('department', 1, 0, 'Department', 4),
('workshop_id', 1, 0, 'Location / Workshop', 5),
('certifications', 1, 0, 'Certifications / Skills', 6),
('notes', 1, 0, 'Notes', 7),
('status', 1, 1, 'Status', 8);

-- Note: badge_number and role_level are always collected (not configurable here)
-- user_id stays internal, never shown in main UI
