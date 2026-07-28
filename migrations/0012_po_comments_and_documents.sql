-- ==============================================================
-- 0012 — Procurement: step comments + PO documents
-- Adds a free-text note to the PO audit trail (so buyers can
-- comment on each step while a shipment is processed), and a
-- table to hold generated / uploaded documents per PO
-- (the requisition document and the supplier invoice).
-- ==============================================================

-- 1. Comment/note on each audit-trail entry
ALTER TABLE `po_status_logs`
  ADD COLUMN `note` TEXT NULL AFTER `status_to`;

-- 2. Documents attached to a purchase order
CREATE TABLE IF NOT EXISTS `po_documents` (
  `doc_id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `po_id`         INT(11)      NOT NULL,
  `doc_type`      VARCHAR(30)  NOT NULL COMMENT 'pr_generated | invoice',
  `file_path`     VARCHAR(500) DEFAULT NULL COMMENT 'stored path for uploaded docs (invoice); NULL for on-the-fly generated (PR)',
  `original_name` VARCHAR(255) DEFAULT NULL,
  `uploaded_by`   INT(11)      DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_type` (`doc_type`),
  CONSTRAINT `fk_podoc_po` FOREIGN KEY (`po_id`)
    REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
