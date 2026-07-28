-- Migration 0007: Create skill_automation_config table for Gamified Skills

CREATE TABLE IF NOT EXISTS `skill_automation_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `skill_name` VARCHAR(255) NOT NULL,
  `equipment_category` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT '⭐',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category` (`equipment_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
