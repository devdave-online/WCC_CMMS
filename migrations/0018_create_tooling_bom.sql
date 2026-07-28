-- 0018_create_tooling_bom.sql
-- Parts linked to tooling (mirrors equipment_bom, but tooling_id → inventory_parts).
-- A tool can be allocated to a machine (toolings.linked_equip_id) AND carry its own
-- spare parts BOM (inserts, springs, seals, etc.).

CREATE TABLE IF NOT EXISTS `tooling_bom` (
  `bom_id` int(11) NOT NULL AUTO_INCREMENT,
  `tooling_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bom_id`),
  UNIQUE KEY `uq_tooling_bom_part` (`tooling_id`,`part_id`),
  KEY `idx_tooling_bom_tooling` (`tooling_id`),
  KEY `idx_tooling_bom_part` (`part_id`),
  CONSTRAINT `tooling_bom_tooling_fk` FOREIGN KEY (`tooling_id`) REFERENCES `toolings` (`tooling_id`) ON DELETE CASCADE,
  CONSTRAINT `tooling_bom_part_fk` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
