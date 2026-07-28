-- 0006_create_inventory_ledger.sql
-- Phase 5: Stronger inventory transaction ledger.
-- Replaces reliance on free-text parts_used in ticket_actions + ad-hoc stock updates.
-- Every stock change (deduct on use, receive on PO) should eventually log here.
--
-- This gives proper history, cost tracking, and auditability.

CREATE TABLE IF NOT EXISTS `inventory_ledger` (
    `ledger_id`     INT(11)      NOT NULL AUTO_INCREMENT,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `part_id`       INT(11)      NOT NULL,
    `change_qty`    INT(11)      NOT NULL COMMENT 'positive for receipts, negative for consumption',
    `reason`        VARCHAR(100) NOT NULL COMMENT 'e.g. wo_consume, ticket_action, po_receipt, adjustment',
    `reference_type` VARCHAR(50) NULL COMMENT 'work_orders, active_tickets, purchase_orders',
    `reference_id`  VARCHAR(100) NULL,
    `notes`         TEXT         NULL,
    `actor_user_id` INT(11)      NULL,
    PRIMARY KEY (`ledger_id`),
    KEY `idx_part` (`part_id`),
    KEY `idx_created` (`created_at`),
    KEY `idx_reason` (`reason`),
    CONSTRAINT `fk_ledger_part` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
