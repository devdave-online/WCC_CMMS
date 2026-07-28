-- ==============================================================
--  WCC CMMS — workshop_db  |  Full Schema
--  Generated: 2026-07-05 from live DB introspection
--  Updated: 2026-07-12 after Phase 5 migrations applied
--  Engine: InnoDB | Charset: utf8mb4_general_ci
--
-- IMPORTANT: For new installs or to bring an existing DB up to date,
-- run: php migrations/migrate.php --apply
-- See: migrations/README.md and CMMS_QA_AND_FUTURE_PLAN.md
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `workshop_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `workshop_db`;

-- ==============================================================
-- 1. APP_SETTINGS
--    Stores runtime configuration (e.g. session lockout time).
--    Managed via app_settings.php (Admin only).
-- ==============================================================
CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_id`    INT(11)       NOT NULL AUTO_INCREMENT,
  `category`      VARCHAR(50)   NOT NULL,
  `setting_key`   VARCHAR(100)  NOT NULL,
  `setting_value` TEXT          DEFAULT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default: 15-minute session lockout
INSERT IGNORE INTO `app_settings` (`category`, `setting_key`, `setting_value`)
VALUES ('Security', 'session_lockout_time', '15');


-- ==============================================================
-- SYSTEM: schema_migrations (Phase 5)
-- Tracks which numbered migrations from the migrations/ folder have been applied.
-- Used by migrations/migrate.php
-- ==============================================================
CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `filename`    VARCHAR(255) NOT NULL,
    `applied_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- SYSTEM: audit_log (Phase 5)
-- Centralized audit trail for critical changes.
-- Populated by application code via audit helper.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `log_id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actor_user_id`   INT(11)      NULL,
    `action`          VARCHAR(100) NOT NULL,
    `entity_type`     VARCHAR(50)  NOT NULL,
    `entity_id`       VARCHAR(100) NOT NULL,
    `before_json`     LONGTEXT     NULL CHECK (JSON_VALID(`before_json`) OR `before_json` IS NULL),
    `after_json`      LONGTEXT     NULL CHECK (JSON_VALID(`after_json`) OR `after_json` IS NULL),
    `notes`           TEXT         NULL,
    PRIMARY KEY (`log_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_action` (`action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_actor` (`actor_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Per-user notification center (see migration 0015). NULL link = no navigation.
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL,
    `type`       VARCHAR(50)  NOT NULL DEFAULT 'system',
    `message`    TEXT         NOT NULL,
    `link`       VARCHAR(255) DEFAULT NULL,
    `severity`   VARCHAR(10)  NOT NULL DEFAULT 'info',
    `is_read`    TINYINT(1)   DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 2. USERS
--    Authentication and RBAC.
--    role_level: 1=Operator | 2=Technician | 3=Supervisor | 4=Admin
-- ==============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(50)   NOT NULL,
  `password_hash`    VARCHAR(255)  NOT NULL,
  `role_level`       INT(11)       NOT NULL DEFAULT 1,
  `permissions_json` LONGTEXT      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                     CHECK (JSON_VALID(`permissions_json`)),
  `api_key`          VARCHAR(64)   NULL UNIQUE COMMENT 'REST API v1 key (X-API-Key). NULL = none issued.',
  `theme_prefs_json` LONGTEXT      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                     COMMENT 'JSON for per-user dark/light custom theme prefs (Phase 4+ server persist)'
                     CHECK (JSON_VALID(`theme_prefs_json`) OR `theme_prefs_json` IS NULL),
  `admin_layout_json` LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                     COMMENT 'Ordered JSON array of admin_panel tile ids; NULL = default order'
                     CHECK (JSON_VALID(`admin_layout_json`) OR `admin_layout_json` IS NULL),
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email`            VARCHAR(255)  NULL UNIQUE COMMENT 'Primary contact for invites/notifications',
  `full_name`        VARCHAR(100)  NULL,
  `phone`            VARCHAR(50)   NULL,
  `department`       VARCHAR(100)  NULL,
  `status`           ENUM('active','inactive','pending') NOT NULL DEFAULT 'active' COMMENT 'Account status',
  `last_login`       DATETIME      NULL COMMENT 'Last successful login for activity tracking',
  `notes`            TEXT          NULL,
  `workshop_id`      INT(11)       NULL COMMENT 'Optional FK to workshops for location scoping',
  `certifications`   LONGTEXT      NULL COMMENT 'JSON array of skills/certs for labor assignment'
                     CHECK (JSON_VALID(`certifications`) OR `certifications` IS NULL),
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Force change on next login',
  `badge_number` VARCHAR(50) NULL UNIQUE COMMENT 'I-Badge / Employee badge number - public safe ID for UI and login safety (TISAX compliant)',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `badge_number` (`badge_number`),
  KEY `idx_status` (`status`),
  KEY `idx_workshop` (`workshop_id`),
  KEY `idx_last_login` (`last_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User registration configurator (checkboxes for what data to collect)
CREATE TABLE IF NOT EXISTS `user_registration_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `field_name` VARCHAR(50) NOT NULL COMMENT 'e.g. full_name, email, phone...',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `label` VARCHAR(100) NOT NULL,
    `display_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- Role Definitions (editable presets)
-- Role levels are fixed (1-4). Permissions are stored as JSON so the
-- Role Presets UI can fully control the defaults.
-- These feed into wcc_get_permissions() and the "Reset to Role" feature.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `role_definitions` (
    `role_level` TINYINT(1) NOT NULL PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `permissions_json` TEXT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default seeds (match the previous hardcoded values in rbac.php)
INSERT IGNORE INTO `role_definitions` (role_level, name, permissions_json, description) VALUES
(1, 'Operator', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":false,"closeout_tickets":false,"view_history":true,"view_statistics":false,"view_equipment":true,"view_inventory":false,"view_vendors":false,"view_work_orders":false,"manage_work_orders":false,"view_purchase_requests":false,"create_purchase_requests":false,"approve_purchase_orders":false,"fulfill_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false,"delete_users":false}',
 'Basic access - create and view own tickets, limited visibility.'),

(2, 'Technician', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":false,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":false,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"fulfill_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false,"delete_users":false}',
 'Field technicians - can take over and view most operational data.'),

(3, 'Supervisor', 
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":true,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":true,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"fulfill_purchase_orders":false,"manage_users":false,"manage_settings":false,"manage_equipment":true,"manage_inventory":false,"manage_vendors":false,"reset_passwords":false,"delete_users":false}',
 'Supervisors - full ticket lifecycle + manage equipment and work orders.'),

(4, 'Admin',
 '{"view_tickets":true,"create_tickets":true,"takeover_tickets":true,"closeout_tickets":true,"view_history":true,"view_statistics":true,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":true,"manage_work_orders":true,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":true,"fulfill_purchase_orders":true,"manage_users":true,"manage_settings":true,"manage_equipment":true,"manage_inventory":true,"manage_vendors":true,"reset_passwords":true,"delete_users":true}',
 'Full system access including user management and settings.'),

-- Storekeeper (migration 0013): procurement/inventory personnel. Fulfils approved
-- POs (ship/transit/receive/close) + manages stock, but cannot approve spend.
(6, 'Storekeeper',
 '{"view_tickets":false,"create_tickets":false,"takeover_tickets":false,"closeout_tickets":false,"view_history":false,"view_statistics":false,"view_equipment":true,"view_inventory":true,"view_vendors":true,"view_work_orders":false,"manage_work_orders":false,"view_purchase_requests":true,"create_purchase_requests":true,"approve_purchase_orders":false,"fulfill_purchase_orders":true,"manage_users":false,"manage_settings":false,"manage_equipment":false,"manage_inventory":true,"manage_vendors":false,"reset_passwords":false,"delete_users":false}',
 'Inventory/procurement personnel: fulfil approved POs, manage stock. Cannot approve spend.');

-- Procurement workflow settings (migration 0013)
INSERT IGNORE INTO `app_settings` (`category`, `setting_key`, `setting_value`) VALUES
('Procurement', 'procurement_workflow_enabled', '1'),
('Procurement', 'po_auto_approve_limit', '0');


-- ==============================================================
-- 3. TEAM_DIRECTORY
--    Stores technician and production staff names for ticket
--    workflow dropdowns (Announced By, PIC, Tech, Supervisor).
--    Queried by api/get_team.php?role=technical|production
-- ==============================================================
CREATE TABLE IF NOT EXISTS `team_directory` (
  `member_id`  INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(100) NOT NULL,
  `role_type`  VARCHAR(50)  NOT NULL COMMENT 'technical | production',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  KEY `idx_role_active` (`role_type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 4. DEPARTMENTS
--    Budget tracking by department.
--    Referenced by purchase_orders.dept_id.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `departments` (
  `dept_id`          INT(11)        NOT NULL AUTO_INCREMENT,
  `dept_name`        VARCHAR(100)   NOT NULL,
  `budget_allocated` DECIMAL(12,2)  DEFAULT 0.00,
  `budget_consumed`  DECIMAL(12,2)  DEFAULT 0.00,
  PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 5. VENDORS_SUPPLIERS
--    Master supplier directory.
--    Referenced by: equipment, inventory_parts, purchase_orders
-- ==============================================================
CREATE TABLE IF NOT EXISTS `vendors_suppliers` (
  `vendor_id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `vendor_name`          VARCHAR(100)  NOT NULL,
  `primary_contact_name` VARCHAR(100)  DEFAULT NULL,
  `contact_email`        VARCHAR(100)  DEFAULT NULL,
  `contact_phone`        VARCHAR(50)   DEFAULT NULL,
  `emergency_contact`    VARCHAR(100)  DEFAULT NULL,
  `payment_terms`        VARCHAR(50)   DEFAULT NULL,
  `vendor_address`       TEXT          DEFAULT NULL,
  `shipping_time`        VARCHAR(100)  DEFAULT NULL,
  `vendor_type`          VARCHAR(50)   DEFAULT NULL,
  `vendor_remarks`       TEXT          DEFAULT NULL,
  `rating`               DECIMAL(3,2)  DEFAULT 5.00,
  `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `vendor_name` (`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 6. EQUIPMENT
--    Asset register — the core entity of the CMMS.
--    Referenced by: active_tickets, equipment_bom
--    Self-references: parent_asset_id (asset hierarchy)
-- ==============================================================
CREATE TABLE IF NOT EXISTS `equipment` (
  `equip_id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `asset_uuid`        VARCHAR(36)   DEFAULT NULL,
  `oem_brand`         VARCHAR(100)  DEFAULT NULL,
  `oem_model`         VARCHAR(100)  DEFAULT NULL,
  `oem_serial`        VARCHAR(100)  DEFAULT NULL,
  `equip_name`        VARCHAR(100)  NOT NULL,
  `parent_asset_id`   INT(11)       DEFAULT NULL COMMENT 'Self-ref FK for asset hierarchy',
  `category`          VARCHAR(100)  DEFAULT NULL,
  `criticality`       ENUM('A','B','C') DEFAULT 'B',
  `equipment_type`    VARCHAR(100)  DEFAULT NULL,
  `plant_name`        VARCHAR(100)  DEFAULT NULL COMMENT 'Maps to "workshop" in API',
  `line_name`         VARCHAR(100)  DEFAULT NULL COMMENT 'Maps to "line" in API',
  `station_name`      VARCHAR(100)  DEFAULT NULL,
  `geo_coords`        VARCHAR(100)  DEFAULT NULL,
  `date_of_purchase`  DATE          DEFAULT NULL,
  `vendor_id`         INT(11)       DEFAULT NULL,
  `po_value`          DECIMAL(12,2) DEFAULT 0.00,
  `fat_date`          DATE          DEFAULT NULL COMMENT 'Factory Acceptance Test',
  `sat_date`          DATE          DEFAULT NULL COMMENT 'Site Acceptance Test',
  `is_active`         TINYINT(1)    DEFAULT 0 COMMENT '1=ONLINE, 0=OFFLINE',
  `lifecycle_years`   INT(11)       DEFAULT 10,
  `depreciation_rule` VARCHAR(100)  DEFAULT NULL,
  `warranty_expiry`   DATE          DEFAULT NULL,
  `eol_date`          DATE          DEFAULT NULL,
  `base_speed`        VARCHAR(50)   DEFAULT NULL,
  `base_pressure`     VARCHAR(50)   DEFAULT NULL,
  `base_temp`         VARCHAR(50)   DEFAULT NULL,
  `base_voltage`      VARCHAR(50)   DEFAULT NULL,
  `pm_hours_interval` INT(11)       DEFAULT NULL,
  `pm_days_interval`  INT(11)       DEFAULT NULL,
  `last_pm_date`      DATE          DEFAULT NULL,
  `loto_protocol`     TEXT          DEFAULT NULL,
  `sop_link`          VARCHAR(255)  DEFAULT NULL,
  `technical_details` LONGTEXT      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                      CHECK (JSON_VALID(`technical_details`))
                      COMMENT 'JSON: { "custom_fields": [{"key":"...","value":"..."}] }',
  `deleted_at`        TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`equip_id`),
  UNIQUE KEY `asset_uuid` (`asset_uuid`),
  KEY `parent_asset_id` (`parent_asset_id`),
  CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`parent_asset_id`)
    REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 7. INVENTORY_PARTS
--    Spare parts register with full logistics and compliance data.
--    Referenced by: po_items, equipment_bom
-- ==============================================================
CREATE TABLE IF NOT EXISTS `inventory_parts` (
  `part_id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `part_name`          VARCHAR(100)  NOT NULL,
  `internal_code`      VARCHAR(50)   NOT NULL,
  `manufacturer_id`    INT(11)       DEFAULT NULL COMMENT 'FK → vendors_suppliers',
  -- Stock & Cost
  `stock_level`        INT(11)       NOT NULL DEFAULT 0,
  `minimum_threshold`  INT(11)       NOT NULL DEFAULT 5,
  `cost_per_unit`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- Part DNA
  `vendor_sku`         VARCHAR(100)  DEFAULT NULL,
  `standardized_desc`  VARCHAR(255)  DEFAULT NULL,
  `oem_name`           VARCHAR(100)  DEFAULT NULL,
  `oem_part_number`    VARCHAR(100)  DEFAULT NULL,
  `supersession_sku`   VARCHAR(100)  DEFAULT NULL,
  -- Logistics
  `maximum_stock`      INT(11)       DEFAULT 0,
  `standard_lead_time` INT(11)       DEFAULT 0 COMMENT 'Days',
  `expedited_lead_time`INT(11)       DEFAULT 0 COMMENT 'Days',
  `moq`                INT(11)       DEFAULT 1 COMMENT 'Minimum Order Quantity',
  `uom`                VARCHAR(50)   DEFAULT 'Each' COMMENT 'Unit of Measure',
  `currency`           VARCHAR(10)   DEFAULT 'USD',
  `price_expiration`   DATE          DEFAULT NULL,
  -- Compliance
  `eol_date`           DATE          DEFAULT NULL,
  `shelf_life_months`  INT(11)       DEFAULT 0,
  `material_spec`      VARCHAR(255)  DEFAULT NULL,
  `compliance_docs`    TEXT          DEFAULT NULL,
  -- Storage Coordinates
  `warehouse_id`       INT(11)       DEFAULT NULL,
  `aisle`              VARCHAR(50)   DEFAULT NULL,
  `rack`               VARCHAR(50)   DEFAULT NULL,
  `shelf`              VARCHAR(50)   DEFAULT NULL,
  `bin_code`           VARCHAR(50)   DEFAULT NULL,
  -- Tracking
  `auto_reorder`       TINYINT(1)    DEFAULT 0,
  `primary_vendor_id`  INT(11)       DEFAULT NULL,
  `serial_number`      VARCHAR(100)  DEFAULT NULL,
  `batch_lot`          VARCHAR(100)  DEFAULT NULL,
  `part_condition`     ENUM('New','Refurbished','Defective','Awaiting QA') DEFAULT 'New',
  `lifecycle_status`   ENUM('Active','Phasing Out','Obsolete') DEFAULT 'Active',
  `deleted_at`         TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`part_id`),
  UNIQUE KEY `internal_code` (`internal_code`),
  KEY `fk_inv_vendor` (`manufacturer_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_inv_vendor` FOREIGN KEY (`manufacturer_id`)
    REFERENCES `vendors_suppliers` (`vendor_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 8. EQUIPMENT_BOM
--    Bill of Materials — links spare parts to equipment.
--    Unique pair (equip_id, part_id) enforced.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `equipment_bom` (
  `bom_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `equip_id` INT(11) NOT NULL,
  `part_id`  INT(11) NOT NULL,
  `quantity` INT(11) DEFAULT 1,
  PRIMARY KEY (`bom_id`),
  UNIQUE KEY `unique_bom_part` (`equip_id`, `part_id`),
  CONSTRAINT `equipment_bom_ibfk_1` FOREIGN KEY (`equip_id`)
    REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 9. ACTIVE_TICKETS
--    Live fault tickets. status='CLOSED' tickets remain here
--    and are also shown in history.php.
--    ticket_id format: TK-WEB-YYMMDD-HHMMSS (generated client-side)
-- ==============================================================
CREATE TABLE IF NOT EXISTS `active_tickets` (
  `ticket_id`    VARCHAR(50)  NOT NULL,
  `equip_id`     INT(11)      NOT NULL,
  `report_date`  DATE         DEFAULT NULL,
  `report_time`  TIME         DEFAULT NULL,
  `announced_by` VARCHAR(100) DEFAULT NULL COMMENT 'From team_directory (production)',
  `pic`          VARCHAR(100) DEFAULT NULL COMMENT 'Person In Charge — from team_directory (technical)',
  `fault_desc`   TEXT         DEFAULT NULL,
  `priority`     VARCHAR(50)  DEFAULT 'normal' COMMENT 'critical | high | normal | low',
  `status`       VARCHAR(50)  DEFAULT 'OPEN'   COMMENT 'OPEN | ESCALATED | PENDING | CLOSED',
  `closed_by`    VARCHAR(100) DEFAULT 'Unknown',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`   TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `equip_id` (`equip_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `active_tickets_ibfk_1` FOREIGN KEY (`equip_id`)
    REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 10. TICKET_ACTIONS
--     One row per technician intervention on a ticket.
--     Drives MTTR, tech workload, and parts consumption in stats.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `ticket_actions` (
  `action_id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `ticket_id`        VARCHAR(50)  NOT NULL,
  `tech_name`        VARCHAR(100) DEFAULT NULL COMMENT 'From team_directory (technical)',
  `action_start`     DATETIME     DEFAULT NULL,
  `action_end`       DATETIME     DEFAULT NULL,
  `fault_type`       VARCHAR(100) DEFAULT NULL COMMENT 'Mechanical | Electrical | Pneumatic/Hydraulic | Software/Controls | Tooling/Fixture | Operator Error | Other',
  `root_cause`       TEXT         DEFAULT NULL,
  `action_taken`     TEXT         DEFAULT NULL,
  `parts_used`       TEXT         DEFAULT NULL COMMENT 'Free text; checked for parts consumption in statistics.php',
  `escalated_to`     VARCHAR(100) DEFAULT NULL COMMENT '"None" if not escalated',
  `timestamp_logged` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`action_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_actions_ibfk_1` FOREIGN KEY (`ticket_id`)
    REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 11. PURCHASE_ORDERS
--     Core procurement entity. Includes full lifecycle status.
--     po_number format: PR-YYYYMMDD-XXXX
-- ==============================================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `po_id`                INT(11)        NOT NULL AUTO_INCREMENT,
  `po_number`            VARCHAR(50)    NOT NULL,
  `vendor_id`            INT(11)        NOT NULL,
  `created_by`           INT(11)        NOT NULL,
  `dept_id`              INT(11)        DEFAULT NULL,
  `total_amount`         DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `status`               ENUM(
                           'Draft',
                           'Pending Approval',
                           'Issued',
                           'Shipped',
                           'In Transit',
                           'Partially Received',
                           'Fully Received',
                           'Closed',
                           'Cancelled'
                         ) DEFAULT 'Draft',
  `approval_level`       VARCHAR(50)    DEFAULT 'Auto-Approved'
                         COMMENT 'Auto-Approved | Requires Admin | Maintenance Manager',
  `is_emergency_bypass`  TINYINT(1)     DEFAULT 0,
  `created_at`           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `vendor_id` (`vendor_id`),
  KEY `created_by` (`created_by`),
  KEY `dept_id` (`dept_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`vendor_id`)
    REFERENCES `vendors_suppliers` (`vendor_id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`user_id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`dept_id`)
    REFERENCES `departments` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 12. PO_ITEMS
--     Line items for a purchase order.
--     Receipt updates inventory_parts.stock_level.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `po_items` (
  `po_item_id`   INT(11)       NOT NULL AUTO_INCREMENT,
  `po_id`        INT(11)       NOT NULL,
  `part_id`      INT(11)       NOT NULL,
  `ordered_qty`  INT(11)       NOT NULL DEFAULT 1,
  `received_qty` INT(11)       NOT NULL DEFAULT 0,
  `unit_price`   DECIMAL(10,2) NOT NULL COMMENT 'Locked at submission time',
  `currency`     VARCHAR(10)   DEFAULT 'USD',
  `status`       ENUM('Pending','Received','Backordered','Quarantined') DEFAULT 'Pending',
  PRIMARY KEY (`po_item_id`),
  KEY `po_id` (`po_id`),
  KEY `part_id` (`part_id`),
  CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`)
    REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `po_items_ibfk_2` FOREIGN KEY (`part_id`)
    REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 13. PO_STATUS_LOGS
--     IATF-compliant audit trail for all PO status transitions
--     and goods receipt events.
--     Displayed in the "Lifecycle Audit Trail" panel in
--     purchase_orders.php (expanded row view).
-- ==============================================================
CREATE TABLE IF NOT EXISTS `po_status_logs` (
  `log_id`      INT(11)      NOT NULL AUTO_INCREMENT,
  `po_id`       INT(11)      NOT NULL,
  `action_type` VARCHAR(50)  NOT NULL COMMENT 'e.g. "Status Update", "Receipt Processed", "Received N × PartName"',
  `status_from` VARCHAR(50)  DEFAULT NULL,
  `status_to`   VARCHAR(50)  DEFAULT NULL,
  `note`        TEXT         DEFAULT NULL COMMENT 'free-text comment on this step (migration 0012)',
  `changed_by`  INT(11)      DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `po_id` (`po_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `po_status_logs_ibfk_1` FOREIGN KEY (`po_id`)
    REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `po_status_logs_ibfk_2` FOREIGN KEY (`changed_by`)
    REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================================
-- PO_DOCUMENTS (migration 0012)
-- Documents attached to a purchase order: the generated
-- requisition (pr_generated) and the uploaded supplier invoice.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `po_documents` (
  `doc_id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `po_id`         INT(11)      NOT NULL,
  `doc_type`      VARCHAR(30)  NOT NULL COMMENT 'pr_generated | invoice',
  `file_path`     VARCHAR(500) DEFAULT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `uploaded_by`   INT(11)      DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_type` (`doc_type`),
  CONSTRAINT `fk_podoc_po` FOREIGN KEY (`po_id`)
    REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 14. WORK_ORDERS
--     Scheduled preventive/corrective maintenance tasks.
--     Assigned to users (role_level >= 2).
-- ==============================================================
CREATE TABLE IF NOT EXISTS `work_orders` (
  `wo_id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(100) NOT NULL,
  `description`    TEXT         DEFAULT NULL,
  `assigned_to`    INT(11)      DEFAULT NULL,
  `status`         ENUM('Scheduled','In Progress','Completed') DEFAULT 'Scheduled',
  `scheduled_date` DATE         DEFAULT NULL,
  -- Extended columns used by the application (PM scheduling, parts, completion tracking)
  `equipment_id`   INT(11)      DEFAULT NULL,
  `parts_list`     TEXT         DEFAULT NULL COMMENT 'JSON array of part_ids',
  `completed_date` DATETIME     DEFAULT NULL,
  `completed_by`   VARCHAR(100) DEFAULT NULL,
  `deleted_at`     TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`wo_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `equipment_id` (`equipment_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`assigned_to`)
    REFERENCES `users` (`user_id`),
  CONSTRAINT `work_orders_ibfk_2` FOREIGN KEY (`equipment_id`)
    REFERENCES `equipment` (`equip_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- SYSTEM: inventory_ledger (Phase 5)
-- Transactional history of all stock movements. Preferred over free-text parts_used.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `inventory_ledger` (
    `ledger_id`      INT(11)      NOT NULL AUTO_INCREMENT,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `part_id`        INT(11)      NOT NULL,
    `change_qty`     INT(11)      NOT NULL COMMENT 'positive for receipts, negative for consumption',
    `reason`         VARCHAR(100) NOT NULL COMMENT 'e.g. wo_consume, ticket_action, po_receipt, adjustment',
    `reference_type` VARCHAR(50)  NULL COMMENT 'work_orders, active_tickets, purchase_orders',
    `reference_id`   VARCHAR(100) NULL,
    `notes`          TEXT         NULL,
    `actor_user_id`  INT(11)      NULL,
    PRIMARY KEY (`ledger_id`),
    KEY `idx_part` (`part_id`),
    KEY `idx_created` (`created_at`),
    KEY `idx_reason` (`reason`),
    CONSTRAINT `fk_ledger_part` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================================================
-- 15. ANALYTICS_LOGS
--     Key-value store for cached/aggregated KPI metrics.
--     Updated externally (e.g. cron_requisition.php).
-- ==============================================================
CREATE TABLE IF NOT EXISTS `analytics_logs` (
  `log_id`       INT(11)       NOT NULL AUTO_INCREMENT,
  `metric_name`  VARCHAR(50)   NOT NULL,
  `metric_value` DECIMAL(10,2) NOT NULL,
  `last_updated` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `metric_name` (`metric_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


SET FOREIGN_KEY_CHECKS = 1;
-- ==============================================================
-- ==============================================================
-- ==============================================================
-- 19. SKILL AUTOMATION CONFIG
--     Maps equipment_category to specific gamified skills.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `skill_automation_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `skill_name` VARCHAR(255) NOT NULL,
  `equipment_category` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT '⭐',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category` (`equipment_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==============================================================
-- 18. USER_SKILLS
--     Tracks certifications and skills of technicians.
-- ==============================================================
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `skill_name` VARCHAR(255) NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
