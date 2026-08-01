-- WCC CMMS — database schema (OB1.0.0)
--
-- Complete structure for a fresh installation, plus the configuration
-- reference data an install needs to behave correctly:
--   role_definitions  the 6 built-in roles and their permission sets
--   app_settings      KPI targets, session timeout, procurement workflow
--   uuid_rules        equipment identifier rules
--
-- No tickets, equipment, users or other business data — the system starts empty.
-- On first visit the app creates an 'admin' account and forces a password change.
--
--   mysql -u root workshop_db < schema.sql

SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_tickets` (
  `ticket_id` varchar(50) NOT NULL,
  `equip_id` int(11) NOT NULL,
  `report_date` date DEFAULT NULL,
  `report_time` time DEFAULT NULL,
  `announced_by` varchar(100) DEFAULT NULL,
  `pic` varchar(100) DEFAULT NULL,
  `fault_desc` text DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'normal',
  `status` varchar(50) DEFAULT 'OPEN',
  `closed_by` varchar(100) DEFAULT 'Unknown',
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `event_class` varchar(32) NOT NULL DEFAULT 'failure' COMMENT 'Reliability event class (failure|induced|inspection|no_fault|setup|request); see inc/kpi.php',
  PRIMARY KEY (`ticket_id`),
  KEY `equip_id` (`equip_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `active_tickets_ibfk_1` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytics_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `metric_name` (`metric_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actor_user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(100) NOT NULL,
  `before_json` longtext DEFAULT NULL CHECK (json_valid(`before_json`) or `before_json` is null),
  `after_json` longtext DEFAULT NULL CHECK (json_valid(`after_json`) or `after_json` is null),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_actor` (`actor_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_budget_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `dept_id` (`dept_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `department_budget_logs_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE,
  CONSTRAINT `department_budget_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `budget_allocated` decimal(12,2) DEFAULT 0.00,
  `budget_consumed` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_prompt_prefs` (
  `user_id` int(11) NOT NULL,
  `status` enum('shown','snoozed','dismissed') NOT NULL DEFAULT 'shown',
  `snooze_until` datetime DEFAULT NULL,
  `last_action` varchar(40) DEFAULT NULL COMMENT 'coffee | coffee_snooze | no_coffee',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_donation_prompt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eam_directory` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `ull_name` varchar(100) NOT NULL,
  `ole_type` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `equip_id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_uuid` varchar(36) DEFAULT NULL,
  `oem_brand` varchar(100) DEFAULT NULL,
  `oem_model` varchar(100) DEFAULT NULL,
  `oem_serial` varchar(100) DEFAULT NULL,
  `equip_name` varchar(100) NOT NULL,
  `parent_asset_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `criticality` enum('A','B','C') DEFAULT 'B',
  `equipment_type` varchar(100) DEFAULT NULL,
  `plant_name` varchar(100) DEFAULT NULL,
  `line_name` varchar(100) DEFAULT NULL,
  `station_name` varchar(100) DEFAULT NULL,
  `geo_coords` varchar(100) DEFAULT NULL,
  `date_of_purchase` date DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `po_value` decimal(12,2) DEFAULT 0.00,
  `fat_date` date DEFAULT NULL,
  `sat_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `lifecycle_years` int(11) DEFAULT 10,
  `depreciation_rule` varchar(100) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `eol_date` date DEFAULT NULL,
  `base_speed` varchar(50) DEFAULT NULL,
  `base_pressure` varchar(50) DEFAULT NULL,
  `base_temp` varchar(50) DEFAULT NULL,
  `base_voltage` varchar(50) DEFAULT NULL,
  `pm_hours_interval` int(11) DEFAULT NULL,
  `pm_days_interval` int(11) DEFAULT NULL,
  `last_pm_date` date DEFAULT NULL,
  `loto_protocol` text DEFAULT NULL,
  `sop_link` varchar(255) DEFAULT NULL,
  `technical_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technical_details`)),
  `workshop_id` int(11) DEFAULT NULL,
  `line_id` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `asset_purchase_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`equip_id`),
  UNIQUE KEY `asset_uuid` (`asset_uuid`),
  KEY `parent_asset_id` (`parent_asset_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`parent_asset_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_bom` (
  `bom_id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  PRIMARY KEY (`bom_id`),
  UNIQUE KEY `unique_bom_part` (`equip_id`,`part_id`),
  CONSTRAINT `equipment_bom_ibfk_1` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment_documents` (
  `doc_id` int(11) NOT NULL AUTO_INCREMENT,
  `equip_id` int(11) NOT NULL,
  `doc_title` varchar(255) NOT NULL,
  `doc_type` varchar(50) DEFAULT 'SOP' COMMENT 'SOP, Manual, Diagram, etc.',
  `file_path` varchar(500) NOT NULL COMMENT 'Relative path inside _doc/',
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doc_id`),
  KEY `fk_equip_docs_equip_id` (`equip_id`),
  CONSTRAINT `fk_equip_docs_equip_id` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_ledger` (
  `ledger_id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `part_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL COMMENT 'positive for receipts, negative for consumption',
  `reason` varchar(100) NOT NULL COMMENT 'e.g. wo_consume, ticket_action, po_receipt, adjustment',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'work_orders, active_tickets, purchase_orders',
  `reference_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ledger_id`),
  KEY `idx_part` (`part_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_reason` (`reason`),
  CONSTRAINT `fk_ledger_part` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB AUTO_INCREMENT=343 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_parts` (
  `part_id` int(11) NOT NULL AUTO_INCREMENT,
  `part_name` varchar(100) NOT NULL,
  `internal_code` varchar(50) NOT NULL,
  `manufacturer_id` int(11) DEFAULT NULL,
  `stock_level` int(11) NOT NULL DEFAULT 0,
  `minimum_threshold` int(11) NOT NULL DEFAULT 5,
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vendor_sku` varchar(100) DEFAULT NULL,
  `standardized_desc` varchar(255) DEFAULT NULL,
  `oem_name` varchar(100) DEFAULT NULL,
  `oem_part_number` varchar(100) DEFAULT NULL,
  `supersession_sku` varchar(100) DEFAULT NULL,
  `maximum_stock` int(11) DEFAULT 0,
  `standard_lead_time` int(11) DEFAULT 0,
  `expedited_lead_time` int(11) DEFAULT 0,
  `moq` int(11) DEFAULT 1,
  `uom` varchar(50) DEFAULT 'Each',
  `currency` varchar(10) DEFAULT 'USD',
  `price_expiration` date DEFAULT NULL,
  `eol_date` date DEFAULT NULL,
  `shelf_life_months` int(11) DEFAULT 0,
  `material_spec` varchar(255) DEFAULT NULL,
  `compliance_docs` text DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `aisle` varchar(50) DEFAULT NULL,
  `rack` varchar(50) DEFAULT NULL,
  `shelf` varchar(50) DEFAULT NULL,
  `bin_code` varchar(50) DEFAULT NULL,
  `auto_reorder` tinyint(1) DEFAULT 0,
  `primary_vendor_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `batch_lot` varchar(100) DEFAULT NULL,
  `part_condition` enum('New','Refurbished','Defective','Awaiting QA') DEFAULT 'New',
  `lifecycle_status` enum('Active','Phasing Out','Obsolete') DEFAULT 'Active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`part_id`),
  UNIQUE KEY `internal_code` (`internal_code`),
  KEY `fk_inv_vendor` (`manufacturer_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_inv_vendor` FOREIGN KEY (`manufacturer_id`) REFERENCES `vendors_suppliers` (`vendor_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_broadcast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `severity` varchar(10) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pm_checklist_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `checklist_id` int(11) NOT NULL,
  `task_desc` varchar(255) NOT NULL,
  `expected_time_mins` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`item_id`),
  KEY `fk_checklist_id` (`checklist_id`),
  CONSTRAINT `fk_checklist_id` FOREIGN KEY (`checklist_id`) REFERENCES `pm_checklists` (`checklist_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pm_checklists` (
  `checklist_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`checklist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pm_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `parts_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parts_list`)),
  `checklist_id` int(11) DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `next_run_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_documents` (
  `doc_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `doc_type` varchar(30) NOT NULL COMMENT 'pr_generated | invoice',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'stored path for uploaded docs (invoice); NULL for on-the-fly generated (PR)',
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`doc_id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_type` (`doc_type`),
  CONSTRAINT `fk_podoc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_items` (
  `po_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `ordered_qty` int(11) NOT NULL DEFAULT 1,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('Pending','Received','Backordered','Quarantined') DEFAULT 'Pending',
  PRIMARY KEY (`po_item_id`),
  KEY `po_id` (`po_id`),
  KEY `part_id` (`part_id`),
  CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `po_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_status_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `po_id` (`po_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `po_status_logs_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `po_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_api_keys` (
  `id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL,
  `key_prefix` varchar(16) NOT NULL DEFAULT '',
  `key_hash` varchar(64) NOT NULL,
  `permissions_json` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `last_used_at` varchar(40) DEFAULT NULL,
  `created_by` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppcl_api_hash` (`key_hash`),
  KEY `idx_ppcl_api_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_attachments` (
  `id` varchar(64) NOT NULL,
  `entity_type` varchar(64) NOT NULL,
  `entity_id` varchar(64) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `mime` varchar(120) DEFAULT NULL,
  `size_bytes` int(10) unsigned NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(64) DEFAULT NULL,
  `uploaded_by_name` varchar(120) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_att_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_audit_log` (
  `id` varchar(64) NOT NULL,
  `created_at` varchar(40) NOT NULL,
  `user_id` varchar(64) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `display_name` varchar(120) DEFAULT NULL,
  `action` varchar(64) NOT NULL DEFAULT 'modify',
  `entity_type` varchar(64) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `entity_label` varchar(255) DEFAULT NULL,
  `path_label` varchar(512) DEFAULT NULL,
  `field_name` varchar(120) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `summary` text NOT NULL,
  `meta_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_audit_created` (`created_at`),
  KEY `idx_ppcl_audit_user` (`user_id`),
  KEY `idx_ppcl_audit_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_buildings` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_config_templates` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `parameters_json` longtext DEFAULT NULL,
  `parameter_sets_json` longtext DEFAULT NULL,
  `revision` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_drafts` (
  `process_id` varchar(64) NOT NULL,
  `payload_json` longtext NOT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_meta` (
  `id` tinyint(4) NOT NULL DEFAULT 1,
  `app_user` varchar(120) NOT NULL DEFAULT 'Operator',
  `theme` varchar(16) NOT NULL DEFAULT 'light',
  `record_seq` int(10) unsigned NOT NULL DEFAULT 0,
  `retention_years` int(10) unsigned NOT NULL DEFAULT 15,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_processes` (
  `id` varchar(64) NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters_json` longtext DEFAULT NULL,
  `parameter_sets_json` longtext DEFAULT NULL,
  `assigned_line_ids_json` text DEFAULT NULL,
  `line_param_overrides_json` longtext DEFAULT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `use_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_used_at` varchar(40) DEFAULT NULL,
  `revision` int(10) unsigned NOT NULL DEFAULT 1,
  `source_template_id` varchar(64) DEFAULT NULL,
  `source_template_revision` int(10) unsigned DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_proc_prod` (`product_id`),
  KEY `idx_ppcl_proc_tmpl` (`source_template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_product_families` (
  `id` varchar(64) NOT NULL,
  `project_id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_fam_proj` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_production_lines` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(64) DEFAULT NULL,
  `building_id` varchar(64) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_line_bldg` (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_products` (
  `id` varchar(64) NOT NULL,
  `product_family_id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_prod_fam` (`product_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_projects` (
  `id` varchar(64) NOT NULL,
  `building_id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_proj_bldg` (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_records` (
  `id` varchar(64) NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `routing_step_id` varchar(50) DEFAULT NULL,
  `record_number` varchar(64) NOT NULL,
  `payload_json` longtext NOT NULL,
  `overall_status` varchar(16) DEFAULT NULL,
  `production_line_id` varchar(64) DEFAULT NULL,
  `production_line_name` varchar(255) DEFAULT NULL,
  `batch_or_lot` varchar(255) DEFAULT NULL,
  `filled_by` varchar(255) DEFAULT NULL,
  `started_at` varchar(40) DEFAULT NULL,
  `completed_at` varchar(40) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `voided` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppcl_rec_number` (`record_number`),
  KEY `idx_ppcl_rec_completed` (`completed_at`),
  KEY `idx_ppcl_rec_line` (`production_line_id`),
  KEY `idx_ppcl_rec_status` (`overall_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_roles` (
  `id` varchar(64) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `permissions_json` longtext NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppcl_role_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_sessions` (
  `token` varchar(64) NOT NULL,
  `user_id` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token`),
  KEY `idx_ppcl_sess_user` (`user_id`),
  KEY `idx_ppcl_sess_exp` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_users` (
  `id` varchar(64) NOT NULL,
  `username` varchar(80) NOT NULL,
  `display_name` varchar(120) NOT NULL DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'operator',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  `last_login_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppcl_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_webhook_deliveries` (
  `id` varchar(64) NOT NULL,
  `webhook_id` varchar(64) NOT NULL,
  `event` varchar(80) NOT NULL,
  `payload_json` longtext DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `status_code` int(11) DEFAULT NULL,
  `response_snippet` varchar(500) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_whd_wh` (`webhook_id`),
  KEY `idx_ppcl_whd_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcl_s_webhooks` (
  `id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL,
  `url` text NOT NULL,
  `secret` varchar(128) DEFAULT NULL,
  `events_json` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `last_fired_at` varchar(40) DEFAULT NULL,
  `last_status_code` int(11) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_by` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppcl_wh_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_api_keys` (
  `id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL,
  `key_prefix` varchar(16) NOT NULL DEFAULT '',
  `key_hash` varchar(64) NOT NULL,
  `permissions_json` longtext DEFAULT NULL,
  `project_ids_json` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `last_used_at` varchar(40) DEFAULT NULL,
  `created_by` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_api_hash` (`key_hash`),
  KEY `idx_ppr_api_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_attachments` (
  `id` varchar(64) NOT NULL,
  `entity_type` varchar(64) NOT NULL,
  `entity_id` varchar(64) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `mime` varchar(120) DEFAULT NULL,
  `size_bytes` int(10) unsigned NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(64) DEFAULT NULL,
  `uploaded_by_name` varchar(120) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_att_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_audit_log` (
  `id` varchar(64) NOT NULL,
  `created_at` varchar(40) NOT NULL,
  `user_id` varchar(64) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `display_name` varchar(120) DEFAULT NULL,
  `action` varchar(64) NOT NULL DEFAULT 'modify',
  `entity_type` varchar(64) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `entity_label` varchar(255) DEFAULT NULL,
  `summary` text NOT NULL,
  `meta_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_breaks` (
  `id` varchar(64) NOT NULL,
  `shift_id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT 'Break',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_break_shift` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_downtime_codes` (
  `id` varchar(64) NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_dt_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_lines` (
  `id` varchar(64) NOT NULL,
  `code` varchar(64) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `project_id` varchar(64) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_meta` (
  `id` tinyint(4) NOT NULL DEFAULT 1,
  `webhook_url` text DEFAULT NULL,
  `base_public_url` varchar(512) DEFAULT NULL,
  `retention_days` int(10) unsigned NOT NULL DEFAULT 730,
  `target_band_pct` decimal(6,2) NOT NULL DEFAULT 5.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `max_log_hours` int(10) unsigned NOT NULL DEFAULT 8,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_production_logs` (
  `id` varchar(64) NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `line_id` varchar(64) NOT NULL,
  `project_id` varchar(64) NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `shift_id` varchar(64) NOT NULL,
  `business_date` date NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
  `remarks` text DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'open',
  `opened_by` varchar(64) DEFAULT NULL,
  `opened_by_name` varchar(120) DEFAULT NULL,
  `signed_by` varchar(64) DEFAULT NULL,
  `signed_by_name` varchar(120) DEFAULT NULL,
  `signed_at` varchar(40) DEFAULT NULL,
  `gross_seconds` int(10) unsigned DEFAULT NULL,
  `break_seconds` int(10) unsigned DEFAULT NULL,
  `net_seconds` int(10) unsigned DEFAULT NULL,
  `uph` decimal(14,4) DEFAULT NULL,
  `cycle_seconds` decimal(14,4) DEFAULT NULL,
  `anomaly` tinyint(1) NOT NULL DEFAULT 0,
  `anomaly_note` varchar(255) DEFAULT NULL,
  `voided` tinyint(1) NOT NULL DEFAULT 0,
  `void_reason` varchar(255) DEFAULT NULL,
  `items_json` longtext DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  `target_uph` decimal(12,4) DEFAULT NULL,
  `target_band_pct` decimal(6,2) DEFAULT NULL,
  `target_uph_min` decimal(12,4) DEFAULT NULL,
  `target_uph_max` decimal(12,4) DEFAULT NULL,
  `hours_json` longtext DEFAULT NULL,
  `changeover_seconds` int(10) unsigned DEFAULT NULL,
  `overtime_hours` int(10) unsigned NOT NULL DEFAULT 0,
  `plan_quantity` decimal(14,3) DEFAULT NULL,
  `good_quantity` decimal(14,3) DEFAULT NULL,
  `scrap_quantity` decimal(14,3) DEFAULT NULL,
  `rework_quantity` decimal(14,3) DEFAULT NULL,
  `downtime_seconds` int(10) unsigned DEFAULT NULL,
  `oee_availability` decimal(8,4) DEFAULT NULL,
  `oee_performance` decimal(8,4) DEFAULT NULL,
  `oee_quality` decimal(8,4) DEFAULT NULL,
  `oee` decimal(8,4) DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_log_line_start` (`line_id`,`start_at`),
  KEY `idx_ppr_log_date_shift` (`business_date`,`shift_id`),
  KEY `idx_ppr_log_status` (`status`),
  KEY `idx_ppr_log_line_status` (`line_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_products` (
  `id` varchar(64) NOT NULL,
  `project_id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(64) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `target_uph` decimal(12,4) DEFAULT NULL,
  `source` varchar(16) NOT NULL DEFAULT 'manual',
  `ppcl_product_id` varchar(64) DEFAULT NULL,
  `family_name` varchar(255) DEFAULT NULL,
  `search_blob` varchar(768) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  `target_uph_min` decimal(12,4) DEFAULT NULL,
  `target_uph_max` decimal(12,4) DEFAULT NULL,
  `ideal_cycle_seconds` decimal(12,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_prod_ppcl` (`ppcl_product_id`),
  KEY `idx_ppr_prod_proj` (`project_id`),
  KEY `idx_ppr_prod_search` (`search_blob`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_projects` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `source` varchar(16) NOT NULL DEFAULT 'manual',
  `ppcl_project_id` varchar(64) DEFAULT NULL,
  `search_blob` varchar(512) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_proj_ppcl` (`ppcl_project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_roles` (
  `id` varchar(64) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `permissions_json` longtext NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_role_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_sessions` (
  `token` varchar(64) NOT NULL,
  `user_id` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token`),
  KEY `idx_ppr_sess_user` (`user_id`),
  KEY `idx_ppr_sess_exp` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_shifts` (
  `id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(40) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `crosses_midnight` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_users` (
  `id` varchar(64) NOT NULL,
  `username` varchar(80) NOT NULL,
  `display_name` varchar(120) NOT NULL DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'operator',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `project_ids_json` longtext DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  `updated_at` varchar(40) DEFAULT NULL,
  `last_login_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppr_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_webhook_deliveries` (
  `id` varchar(64) NOT NULL,
  `webhook_id` varchar(64) NOT NULL,
  `event` varchar(80) NOT NULL,
  `payload_json` longtext DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `status_code` int(11) DEFAULT NULL,
  `response_snippet` varchar(500) DEFAULT NULL,
  `created_at` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_whd_wh` (`webhook_id`),
  KEY `idx_ppr_whd_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppr_webhooks` (
  `id` varchar(64) NOT NULL,
  `name` varchar(120) NOT NULL,
  `url` text NOT NULL,
  `secret` varchar(128) DEFAULT NULL,
  `events_json` longtext DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` varchar(40) DEFAULT NULL,
  `last_fired_at` varchar(40) DEFAULT NULL,
  `last_status_code` int(11) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_by` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ppr_wh_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `workshop_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `products_built` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`line_id`),
  KEY `workshop_id` (`workshop_id`),
  CONSTRAINT `production_lines_ibfk_1` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Pending Approval','Issued','Shipped','In Transit','Partially Received','Fully Received','Closed','Cancelled') DEFAULT 'Draft',
  `approval_level` varchar(50) DEFAULT 'Auto-Approved',
  `is_emergency_bypass` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `vendor_id` (`vendor_id`),
  KEY `created_by` (`created_by`),
  KEY `purchase_orders_ibfk_3` (`dept_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors_suppliers` (`vendor_id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_limit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `window_start` int(11) NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip_endpoint` (`ip_address`,`endpoint`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_definitions` (
  `role_level` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions_json` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`role_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheduled_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `total_tickets` int(11) NOT NULL DEFAULT 0,
  `mttr_minutes` int(11) NOT NULL DEFAULT 0,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_automation_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(255) NOT NULL,
  `equipment_category` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT '?',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category` (`equipment_category`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team_directory` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `role_type` varchar(50) NOT NULL COMMENT 'technical | production',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  KEY `idx_role_active` (`role_type`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) NOT NULL,
  `tech_name` varchar(100) DEFAULT NULL,
  `action_start` datetime DEFAULT NULL,
  `action_end` datetime DEFAULT NULL,
  `fault_type` varchar(100) DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `parts_used` text DEFAULT NULL,
  `escalated_to` varchar(100) DEFAULT NULL,
  `timestamp_logged` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`action_id`),
  KEY `ticket_actions_ibfk_1` (`ticket_id`),
  CONSTRAINT `ticket_actions_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=426 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `fk_ticket_comments` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_parts_consumed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) DEFAULT NULL,
  `part_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `consumed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `part_id` (`part_id`),
  CONSTRAINT `ticket_parts_consumed_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`),
  CONSTRAINT `ticket_parts_consumed_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tooling_bom` (
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
  CONSTRAINT `tooling_bom_part_fk` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`) ON DELETE CASCADE,
  CONSTRAINT `tooling_bom_tooling_fk` FOREIGN KEY (`tooling_id`) REFERENCES `toolings` (`tooling_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tooling_documents` (
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `toolings` (
  `tooling_id` int(11) NOT NULL AUTO_INCREMENT,
  `tooling_code` varchar(50) NOT NULL,
  `tooling_name` varchar(150) NOT NULL,
  `category` varchar(80) DEFAULT NULL COMMENT 'Die, Mold, Fixture, Jig, Gauge, Hand Tool, Cutting Tool, Other',
  `tooling_type` varchar(80) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `oem_brand` varchar(100) DEFAULT NULL,
  `oem_model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `status` enum('Available','In Use','Maintenance','Calibration Due','Retired') NOT NULL DEFAULT 'Available',
  `condition_rating` enum('New','Good','Fair','Poor') NOT NULL DEFAULT 'Good',
  `location` varchar(150) DEFAULT NULL,
  `workshop_id` int(11) DEFAULT NULL,
  `line_id` int(11) DEFAULT NULL,
  `linked_equip_id` int(11) DEFAULT NULL COMMENT 'Optional home / allocated equipment',
  `owner_dept` varchar(100) DEFAULT NULL,
  `calibration_due` date DEFAULT NULL,
  `last_calibration` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tooling_id`),
  UNIQUE KEY `uq_tooling_code` (`tooling_code`),
  KEY `idx_toolings_barcode` (`barcode`),
  KEY `idx_toolings_status` (`status`),
  KEY `idx_toolings_category` (`category`),
  KEY `idx_toolings_linked_equip` (`linked_equip_id`),
  KEY `idx_toolings_deleted` (`deleted_at`),
  KEY `idx_toolings_asset_tag` (`asset_tag`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_registration_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(50) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_level` int(11) NOT NULL DEFAULT 1,
  `permissions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions_json`)),
  `api_key` varchar(64) DEFAULT NULL COMMENT 'REST API v1 key (X-API-Key). NULL = no key issued.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `theme_pref` text DEFAULT NULL,
  `session_timeout_mins` int(11) DEFAULT NULL,
  `theme_prefs_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON object for dark/light custom theme color prefs (Phase 4+)' CHECK (json_valid(`theme_prefs_json`) or `theme_prefs_json` is null),
  `email` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `workshop_id` int(11) DEFAULT NULL,
  `certifications` longtext DEFAULT NULL CHECK (json_valid(`certifications`) or `certifications` is null),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `badge_number` varchar(50) DEFAULT NULL COMMENT 'I-Badge / Employee badge number - public safe ID for UI and login safety (TISAX compliant)',
  `admin_layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ordered JSON array of admin_panel tile ids; NULL = default order' CHECK (json_valid(`admin_layout_json`) or `admin_layout_json` is null),
  `locale` varchar(16) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `badge_number` (`badge_number`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_status` (`status`),
  KEY `idx_workshop` (`workshop_id`),
  KEY `idx_last_login` (`last_login`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uuid_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `target_entity` varchar(100) DEFAULT 'Equipment',
  `category` varchar(255) NOT NULL,
  `prefix` varchar(50) DEFAULT '',
  `serial_length` int(11) DEFAULT 4,
  `current_serial` int(11) DEFAULT 1,
  `random_chars` int(11) DEFAULT 0,
  `char_type` varchar(50) DEFAULT 'NUMERIC',
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors_suppliers` (
  `vendor_id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(100) NOT NULL,
  `primary_contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `vendor_address` text DEFAULT NULL,
  `shipping_time` varchar(100) DEFAULT NULL,
  `vendor_type` varchar(50) DEFAULT NULL,
  `vendor_remarks` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 5.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `vendor_name` (`vendor_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wo_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wo_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `wo_id` (`wo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_orders` (
  `wo_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `parts_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parts_list`)),
  `checklist_data` text DEFAULT NULL COMMENT 'JSON array snapshot of checklist items',
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled','Missed') DEFAULT 'Scheduled',
  `completed_date` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`wo_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workshops` (
  `workshop_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT '',
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`workshop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ---------------------------------------------------------------- config

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `role_definitions` WRITE;
/*!40000 ALTER TABLE `role_definitions` DISABLE KEYS */;
INSERT INTO `role_definitions` VALUES (1,'Operator','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":true,\"view_statistics\":false,\"view_equipment\":true,\"manage_equipment\":false,\"view_toolings\":true,\"manage_toolings\":false,\"view_inventory\":false,\"manage_inventory\":false,\"view_vendors\":false,\"manage_vendors\":false,\"view_purchase_requests\":false,\"create_purchase_requests\":false,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":false,\"view_work_orders\":false,\"manage_work_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"reset_passwords\":false,\"delete_users\":false}','2026-07-27 16:00:33','Basic access - create and view tickets, limited visibility.');
INSERT INTO `role_definitions` VALUES (2,'Technician','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":false,\"view_history\":true,\"view_statistics\":true,\"view_equipment\":true,\"manage_equipment\":false,\"view_toolings\":true,\"manage_toolings\":false,\"view_inventory\":true,\"manage_inventory\":false,\"view_vendors\":true,\"manage_vendors\":false,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":false,\"view_work_orders\":true,\"manage_work_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"reset_passwords\":false,\"delete_users\":false}','2026-07-27 16:00:33','Field technicians - can take over and view most operational data.');
INSERT INTO `role_definitions` VALUES (3,'Supervisor','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":true,\"view_history\":true,\"view_statistics\":true,\"view_equipment\":true,\"manage_equipment\":true,\"view_toolings\":true,\"manage_toolings\":true,\"view_inventory\":true,\"manage_inventory\":false,\"view_vendors\":true,\"manage_vendors\":false,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":false,\"view_work_orders\":true,\"manage_work_orders\":true,\"manage_users\":false,\"manage_settings\":false,\"reset_passwords\":false,\"delete_users\":false}','2026-07-27 16:00:33','Supervisors - full ticket lifecycle + manage equipment/tooling and work orders.');
INSERT INTO `role_definitions` VALUES (4,'Admin','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":true,\"view_history\":true,\"view_statistics\":true,\"view_equipment\":true,\"manage_equipment\":true,\"view_toolings\":true,\"manage_toolings\":true,\"view_inventory\":true,\"manage_inventory\":true,\"view_vendors\":true,\"manage_vendors\":true,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":true,\"fulfill_purchase_orders\":true,\"view_work_orders\":true,\"manage_work_orders\":true,\"manage_users\":true,\"manage_settings\":true,\"reset_passwords\":true,\"delete_users\":true}','2026-07-27 16:00:33','Full system access including user management and settings.');
INSERT INTO `role_definitions` VALUES (5,'Custom Viewer','{\"view_toolings\":false,\"manage_toolings\":false,\"view_tickets\":false,\"create_tickets\":false,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":false,\"view_statistics\":false,\"view_equipment\":false,\"manage_equipment\":false,\"view_inventory\":false,\"manage_inventory\":false,\"view_vendors\":false,\"manage_vendors\":false,\"view_purchase_requests\":false,\"create_purchase_requests\":false,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":false,\"view_work_orders\":false,\"manage_work_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"reset_passwords\":false,\"delete_users\":false}','2026-07-27 16:00:33',NULL);
INSERT INTO `role_definitions` VALUES (6,'Storekeeper','{\"view_tickets\":false,\"create_tickets\":false,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":false,\"view_statistics\":false,\"view_equipment\":true,\"view_inventory\":true,\"view_vendors\":true,\"view_work_orders\":false,\"manage_work_orders\":false,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":true,\"manage_users\":false,\"manage_settings\":false,\"manage_equipment\":false,\"manage_inventory\":true,\"manage_vendors\":false,\"reset_passwords\":false,\"delete_users\":false,\"view_toolings\":true,\"manage_toolings\":false}','2026-07-27 16:00:33',NULL);
/*!40000 ALTER TABLE `role_definitions` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,'EquipmentLabels','equip_label_symbology','qrcode');
INSERT INTO `app_settings` VALUES (2,'EquipmentLabels','tooling_label_symbology','code128');
INSERT INTO `app_settings` VALUES (3,'EquipmentLabels','equip_label_fields','{\"uuid\":true,\"serial\":true,\"brand_model\":false,\"location\":true,\"category_crit\":false}');
INSERT INTO `app_settings` VALUES (4,'EquipmentLabels','equip_label_method','browser_sheet');
INSERT INTO `app_settings` VALUES (5,'EquipmentLabels','equip_label_width_mm','50.8');
INSERT INTO `app_settings` VALUES (6,'EquipmentLabels','equip_label_height_mm','25.4');
INSERT INTO `app_settings` VALUES (7,'EquipmentLabels','equip_label_page_preset','a4');
INSERT INTO `app_settings` VALUES (8,'EquipmentLabels','equip_label_page_width_mm','210');
INSERT INTO `app_settings` VALUES (9,'EquipmentLabels','equip_label_page_height_mm','297');
INSERT INTO `app_settings` VALUES (10,'EquipmentLabels','equip_label_margin_mm','10');
INSERT INTO `app_settings` VALUES (11,'EquipmentLabels','equip_label_gap_x_mm','3');
INSERT INTO `app_settings` VALUES (12,'EquipmentLabels','equip_label_gap_y_mm','3');
INSERT INTO `app_settings` VALUES (13,'EquipmentLabels','equip_label_printer_ip','');
INSERT INTO `app_settings` VALUES (14,'EquipmentLabels','equip_label_printer_port','9100');
INSERT INTO `app_settings` VALUES (15,'EquipmentLabels','equip_label_dpi','203');
INSERT INTO `app_settings` VALUES (16,'EquipmentLabels','equip_label_darkness','10');
INSERT INTO `app_settings` VALUES (17,'EquipmentLabels','equip_label_speed','4');
INSERT INTO `app_settings` VALUES (18,'Procurement','procurement_workflow_enabled','1');
INSERT INTO `app_settings` VALUES (19,'Procurement','po_auto_approve_limit','0');
INSERT INTO `app_settings` VALUES (20,'Inventory','stock_warn_pct','25');
INSERT INTO `app_settings` VALUES (21,'KPI','target_calc_mode','static');
INSERT INTO `app_settings` VALUES (22,'KPI','target_mttd','60');
INSERT INTO `app_settings` VALUES (23,'KPI','target_mttr','120');
INSERT INTO `app_settings` VALUES (24,'KPI','target_mtbf','48');
INSERT INTO `app_settings` VALUES (25,'KPI','kpi_failure_classes','[\"failure\",\"induced\"]');
INSERT INTO `app_settings` VALUES (26,'Security','session_lockout_time','360');
INSERT INTO `app_settings` VALUES (27,'KPI','plant_holidays','[]');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `uuid_rules` WRITE;
/*!40000 ALTER TABLE `uuid_rules` DISABLE KEYS */;
INSERT INTO `uuid_rules` VALUES (1,'Tooling','Die','TL-DIE-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (2,'Tooling','Mold','TL-MLD-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (3,'Tooling','Fixture','TL-FIX-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (4,'Tooling','Jig','TL-JIG-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (5,'Tooling','Gauge','TL-GAU-',3,3,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (6,'Tooling','Hand Tool','TL-HND-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (7,'Tooling','Cutting Tool','TL-CUT-',3,1,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (8,'Tooling','Other','TL-GEN-',3,3,0,'NUMERIC');
INSERT INTO `uuid_rules` VALUES (9,'Tooling','GLOBAL_DEFAULT','TL-GEN-',3,1,0,'NUMERIC');
/*!40000 ALTER TABLE `uuid_rules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS=1;
