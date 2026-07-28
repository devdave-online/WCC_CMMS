-- 0019_create_tooling_documents.sql
-- Documents linked to tooling (mirrors equipment_documents).
-- Files stored under _doc/tooling/{folder}/...

CREATE TABLE IF NOT EXISTS `tooling_documents` (
  `doc_id` int(11) NOT NULL AUTO_INCREMENT,
  `tooling_id` int(11) NOT NULL,
  `doc_title` varchar(255) NOT NULL,
  `doc_type` varchar(50) DEFAULT 'SOP' COMMENT 'SOP, Manual, Drawing, Calibration, Diagram, Other',
  `file_path` varchar(500) NOT NULL COMMENT 'Relative path inside _doc/',
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doc_id`),
  KEY `idx_tooling_docs_tooling` (`tooling_id`),
  CONSTRAINT `fk_tooling_docs_tooling_id` FOREIGN KEY (`tooling_id`) REFERENCES `toolings` (`tooling_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
