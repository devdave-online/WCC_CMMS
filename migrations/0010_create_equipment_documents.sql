-- Migration: 0010_create_equipment_documents.sql
-- Description: Creates the equipment_documents table for managing Safety SOPs, Manuals, and Diagrams linked to equipment.

CREATE TABLE IF NOT EXISTS `equipment_documents` (
  `doc_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `equip_id` INT(11) NOT NULL,
  `doc_title` VARCHAR(255) NOT NULL,
  `doc_type` VARCHAR(50) DEFAULT 'SOP' COMMENT 'SOP, Manual, Diagram, etc.',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Relative path inside _doc/',
  `uploaded_by` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_equip_docs_equip_id` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
