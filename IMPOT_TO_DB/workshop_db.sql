-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 10:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `workshop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_tickets`
--

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
  `event_class` varchar(32) NOT NULL DEFAULT 'failure' COMMENT 'Reliability event class (failure|induced|inspection|no_fault|setup|request); see inc/kpi.php'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_logs`
--

CREATE TABLE `analytics_logs` (
  `log_id` int(11) NOT NULL,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actor_user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(100) NOT NULL,
  `before_json` longtext DEFAULT NULL CHECK (json_valid(`before_json`) or `before_json` is null),
  `after_json` longtext DEFAULT NULL CHECK (json_valid(`after_json`) or `after_json` is null),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `budget_allocated` decimal(12,2) DEFAULT 0.00,
  `budget_consumed` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_budget_logs`
--

CREATE TABLE `department_budget_logs` (
  `log_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eam_directory`
--

CREATE TABLE `eam_directory` (
  `member_id` int(11) NOT NULL,
  `ull_name` varchar(100) NOT NULL,
  `ole_type` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equip_id` int(11) NOT NULL,
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
  `asset_purchase_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_bom`
--

CREATE TABLE `equipment_bom` (
  `bom_id` int(11) NOT NULL,
  `equip_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_documents`
--

CREATE TABLE `equipment_documents` (
  `doc_id` int(11) NOT NULL,
  `equip_id` int(11) NOT NULL,
  `doc_title` varchar(255) NOT NULL,
  `doc_type` varchar(50) DEFAULT 'SOP' COMMENT 'SOP, Manual, Diagram, etc.',
  `file_path` varchar(500) NOT NULL COMMENT 'Relative path inside _doc/',
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_ledger`
--

CREATE TABLE `inventory_ledger` (
  `ledger_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `part_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL COMMENT 'positive for receipts, negative for consumption',
  `reason` varchar(100) NOT NULL COMMENT 'e.g. wo_consume, ticket_action, po_receipt, adjustment',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'work_orders, active_tickets, purchase_orders',
  `reference_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_parts`
--

CREATE TABLE `inventory_parts` (
  `part_id` int(11) NOT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `severity` varchar(10) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_broadcast`
--

CREATE TABLE `notification_broadcast` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_checklists`
--

CREATE TABLE `pm_checklists` (
  `checklist_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_checklist_items`
--

CREATE TABLE `pm_checklist_items` (
  `item_id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `task_desc` varchar(255) NOT NULL,
  `expected_time_mins` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pm_schedules`
--

CREATE TABLE `pm_schedules` (
  `schedule_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `parts_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parts_list`)),
  `checklist_id` int(11) DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `next_run_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `po_documents`
--

CREATE TABLE `po_documents` (
  `doc_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `doc_type` varchar(30) NOT NULL COMMENT 'pr_generated | invoice',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'stored path for uploaded docs (invoice); NULL for on-the-fly generated (PR)',
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `po_item_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `ordered_qty` int(11) NOT NULL DEFAULT 1,
  `received_qty` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('Pending','Received','Backordered','Quarantined') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `po_status_logs`
--

CREATE TABLE `po_status_logs` (
  `log_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_lines`
--

CREATE TABLE `production_lines` (
  `line_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `products_built` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Pending Approval','Issued','Shipped','In Transit','Partially Received','Fully Received','Closed','Cancelled') DEFAULT 'Draft',
  `approval_level` varchar(50) DEFAULT 'Auto-Approved',
  `is_emergency_bypass` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit`
--

CREATE TABLE `rate_limit` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `window_start` int(11) NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip_address`, `endpoint`, `window_start`, `request_count`) VALUES
(1, '192.168.0.122', 'login', 1785263705, 1);

-- --------------------------------------------------------

--
-- Table structure for table `role_definitions`
--

CREATE TABLE `role_definitions` (
  `role_level` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions_json` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `report_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `total_tickets` int(11) NOT NULL DEFAULT 0,
  `mttr_minutes` int(11) NOT NULL DEFAULT 0,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES
(1, '0001_create_schema_migrations_table.sql', '2026-07-12 19:12:25'),
(2, '0002_add_closed_by_to_active_tickets.sql', '2026-07-12 19:12:25'),
(3, '0003_add_theme_prefs_json_to_users.sql', '2026-07-12 19:12:25'),
(4, '0004_create_audit_log_table.sql', '2026-07-12 19:17:19'),
(5, '0005_add_soft_delete_columns.sql', '2026-07-12 19:18:02'),
(6, '0006_create_inventory_ledger.sql', '2026-07-12 19:18:41'),
(7, '0007_enhance_users_table.sql', '2026-07-14 18:16:20'),
(8, '0008_add_badge_number_and_registration_config.sql', '2026-07-14 18:16:20'),
(9, '0010_create_equipment_documents.sql', '2026-07-14 18:16:20'),
(10, '0011_add_api_key_to_users.sql', '2026-07-18 13:38:21'),
(11, '0012_po_comments_and_documents.sql', '2026-07-18 18:09:18'),
(12, '0013_procurement_workflow.sql', '2026-07-18 23:44:01'),
(13, '0007_create_skill_automation_config.sql', '2026-07-19 08:45:33'),
(14, '0014_add_admin_layout_json_to_users.sql', '2026-07-19 08:45:33'),
(15, '0015_create_notifications.sql', '2026-07-21 17:30:48'),
(16, '0016_add_event_class_to_active_tickets.sql', '2026-07-24 19:27:16'),
(17, '0017_create_toolings.sql', '2026-07-26 10:49:06'),
(18, '0018_create_tooling_bom.sql', '2026-07-26 11:03:43'),
(19, '0019_create_tooling_documents.sql', '2026-07-27 19:00:10'),
(20, '0020_add_closed_at_to_active_tickets.sql', '2026-07-27 19:00:10'),
(21, '0021_add_locale_to_users.sql', '2026-07-27 19:00:10'),
(22, '0009_add_role_definitions.sql', '2026-07-27 19:00:33'),
(23, '0022_create_donation_prompt_prefs.sql', '2026-07-28 18:09:56');

-- --------------------------------------------------------

--
-- Table structure for table `skill_automation_config`
--

CREATE TABLE `skill_automation_config` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `equipment_category` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_audit_logs`
--

CREATE TABLE `system_audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_directory`
--

CREATE TABLE `team_directory` (
  `member_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_type` varchar(50) NOT NULL COMMENT 'technical | production',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_actions`
--

CREATE TABLE `ticket_actions` (
  `action_id` int(11) NOT NULL,
  `ticket_id` varchar(50) NOT NULL,
  `tech_name` varchar(100) DEFAULT NULL,
  `action_start` datetime DEFAULT NULL,
  `action_end` datetime DEFAULT NULL,
  `fault_type` varchar(100) DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `parts_used` text DEFAULT NULL,
  `escalated_to` varchar(100) DEFAULT NULL,
  `timestamp_logged` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_comments`
--

CREATE TABLE `ticket_comments` (
  `comment_id` int(11) NOT NULL,
  `ticket_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_parts_consumed`
--

CREATE TABLE `ticket_parts_consumed` (
  `id` int(11) NOT NULL,
  `ticket_id` varchar(50) DEFAULT NULL,
  `part_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `consumed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `toolings`
--

CREATE TABLE `toolings` (
  `tooling_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tooling_bom`
--

CREATE TABLE `tooling_bom` (
  `bom_id` int(11) NOT NULL,
  `tooling_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tooling_documents`
--

CREATE TABLE `tooling_documents` (
  `doc_id` int(11) NOT NULL,
  `tooling_id` int(11) NOT NULL,
  `doc_title` varchar(255) NOT NULL,
  `doc_type` varchar(50) DEFAULT 'SOP' COMMENT 'SOP, Manual, Drawing, Calibration, Diagram, Other',
  `file_path` varchar(500) NOT NULL COMMENT 'Relative path inside _doc/',
  `uploaded_by` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
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
  `locale` varchar(16) NOT NULL DEFAULT 'en'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role_level`, `permissions_json`, `api_key`, `created_at`, `theme_pref`, `session_timeout_mins`, `theme_prefs_json`, `email`, `full_name`, `phone`, `department`, `status`, `last_login`, `notes`, `workshop_id`, `certifications`, `updated_at`, `must_change_password`, `badge_number`, `admin_layout_json`, `locale`) VALUES
(1, 'admin', '$2y$10$yg8ukBVQU.xK4iYrsGM1R.LfAYYtULDvZMpqMTzl3Tdma1KKFg8hW', 4, NULL, NULL, '2026-07-28 17:47:18', NULL, NULL, NULL, NULL, 'Administrator', NULL, NULL, 'active', '2026-07-28 22:09:55', NULL, NULL, NULL, '2026-07-28 19:10:57', 1, 'IB-00001', NULL, 'en');

-- --------------------------------------------------------

--
-- Table structure for table `user_registration_config`
--

CREATE TABLE `user_registration_config` (
  `id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_skills`
--

CREATE TABLE `user_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uuid_rules`
--

CREATE TABLE `uuid_rules` (
  `rule_id` int(11) NOT NULL,
  `target_entity` varchar(100) DEFAULT 'Equipment',
  `category` varchar(255) NOT NULL,
  `prefix` varchar(50) DEFAULT '',
  `serial_length` int(11) DEFAULT 4,
  `current_serial` int(11) DEFAULT 1,
  `random_chars` int(11) DEFAULT 0,
  `char_type` varchar(50) DEFAULT 'NUMERIC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors_suppliers`
--

CREATE TABLE `vendors_suppliers` (
  `vendor_id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `workshop_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT '',
  `status` varchar(50) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

CREATE TABLE `work_orders` (
  `wo_id` int(11) NOT NULL,
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
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wo_attachments`
--

CREATE TABLE `wo_attachments` (
  `id` int(11) NOT NULL,
  `wo_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_tickets`
--
ALTER TABLE `active_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `equip_id` (`equip_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `analytics_logs`
--
ALTER TABLE `analytics_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `metric_name` (`metric_name`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_actor` (`actor_user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `department_budget_logs`
--
ALTER TABLE `department_budget_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `eam_directory`
--
ALTER TABLE `eam_directory`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equip_id`),
  ADD UNIQUE KEY `asset_uuid` (`asset_uuid`),
  ADD KEY `parent_asset_id` (`parent_asset_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `equipment_bom`
--
ALTER TABLE `equipment_bom`
  ADD PRIMARY KEY (`bom_id`),
  ADD UNIQUE KEY `unique_bom_part` (`equip_id`,`part_id`);

--
-- Indexes for table `equipment_documents`
--
ALTER TABLE `equipment_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `fk_equip_docs_equip_id` (`equip_id`);

--
-- Indexes for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD PRIMARY KEY (`ledger_id`),
  ADD KEY `idx_part` (`part_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_reason` (`reason`);

--
-- Indexes for table `inventory_parts`
--
ALTER TABLE `inventory_parts`
  ADD PRIMARY KEY (`part_id`),
  ADD UNIQUE KEY `internal_code` (`internal_code`),
  ADD KEY `fk_inv_vendor` (`manufacturer_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_broadcast`
--
ALTER TABLE `notification_broadcast`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pm_checklists`
--
ALTER TABLE `pm_checklists`
  ADD PRIMARY KEY (`checklist_id`);

--
-- Indexes for table `pm_checklist_items`
--
ALTER TABLE `pm_checklist_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_checklist_id` (`checklist_id`);

--
-- Indexes for table `pm_schedules`
--
ALTER TABLE `pm_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `po_documents`
--
ALTER TABLE `po_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `idx_po` (`po_id`),
  ADD KEY `idx_type` (`doc_type`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `po_status_logs`
--
ALTER TABLE `po_status_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `production_lines`
--
ALTER TABLE `production_lines`
  ADD PRIMARY KEY (`line_id`),
  ADD KEY `workshop_id` (`workshop_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `purchase_orders_ibfk_3` (`dept_id`);

--
-- Indexes for table `rate_limit`
--
ALTER TABLE `rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip_endpoint` (`ip_address`,`endpoint`);

--
-- Indexes for table `role_definitions`
--
ALTER TABLE `role_definitions`
  ADD PRIMARY KEY (`role_level`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_filename` (`filename`);

--
-- Indexes for table `skill_automation_config`
--
ALTER TABLE `skill_automation_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_category` (`equipment_category`);

--
-- Indexes for table `system_audit_logs`
--
ALTER TABLE `system_audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `team_directory`
--
ALTER TABLE `team_directory`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `idx_role_active` (`role_type`,`is_active`);

--
-- Indexes for table `ticket_actions`
--
ALTER TABLE `ticket_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `ticket_actions_ibfk_1` (`ticket_id`);

--
-- Indexes for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `ticket_parts_consumed`
--
ALTER TABLE `ticket_parts_consumed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `toolings`
--
ALTER TABLE `toolings`
  ADD PRIMARY KEY (`tooling_id`),
  ADD UNIQUE KEY `uq_tooling_code` (`tooling_code`),
  ADD KEY `idx_toolings_barcode` (`barcode`),
  ADD KEY `idx_toolings_status` (`status`),
  ADD KEY `idx_toolings_category` (`category`),
  ADD KEY `idx_toolings_linked_equip` (`linked_equip_id`),
  ADD KEY `idx_toolings_deleted` (`deleted_at`),
  ADD KEY `idx_toolings_asset_tag` (`asset_tag`);

--
-- Indexes for table `tooling_bom`
--
ALTER TABLE `tooling_bom`
  ADD PRIMARY KEY (`bom_id`),
  ADD UNIQUE KEY `uq_tooling_bom_part` (`tooling_id`,`part_id`),
  ADD KEY `idx_tooling_bom_tooling` (`tooling_id`),
  ADD KEY `idx_tooling_bom_part` (`part_id`);

--
-- Indexes for table `tooling_documents`
--
ALTER TABLE `tooling_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `idx_tooling_docs_tooling` (`tooling_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `badge_number` (`badge_number`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_workshop` (`workshop_id`),
  ADD KEY `idx_last_login` (`last_login`);

--
-- Indexes for table `user_registration_config`
--
ALTER TABLE `user_registration_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `field_name` (`field_name`);

--
-- Indexes for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `uuid_rules`
--
ALTER TABLE `uuid_rules`
  ADD PRIMARY KEY (`rule_id`);

--
-- Indexes for table `vendors_suppliers`
--
ALTER TABLE `vendors_suppliers`
  ADD PRIMARY KEY (`vendor_id`),
  ADD UNIQUE KEY `vendor_name` (`vendor_name`);

--
-- Indexes for table `workshops`
--
ALTER TABLE `workshops`
  ADD PRIMARY KEY (`workshop_id`);

--
-- Indexes for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD PRIMARY KEY (`wo_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `wo_attachments`
--
ALTER TABLE `wo_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wo_id` (`wo_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analytics_logs`
--
ALTER TABLE `analytics_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_budget_logs`
--
ALTER TABLE `department_budget_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eam_directory`
--
ALTER TABLE `eam_directory`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equip_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_bom`
--
ALTER TABLE `equipment_bom`
  MODIFY `bom_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_documents`
--
ALTER TABLE `equipment_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  MODIFY `ledger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_parts`
--
ALTER TABLE `inventory_parts`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_broadcast`
--
ALTER TABLE `notification_broadcast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_checklists`
--
ALTER TABLE `pm_checklists`
  MODIFY `checklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_checklist_items`
--
ALTER TABLE `pm_checklist_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pm_schedules`
--
ALTER TABLE `pm_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `po_documents`
--
ALTER TABLE `po_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `po_status_logs`
--
ALTER TABLE `po_status_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_lines`
--
ALTER TABLE `production_lines`
  MODIFY `line_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rate_limit`
--
ALTER TABLE `rate_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `skill_automation_config`
--
ALTER TABLE `skill_automation_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_audit_logs`
--
ALTER TABLE `system_audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_directory`
--
ALTER TABLE `team_directory`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_actions`
--
ALTER TABLE `ticket_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_parts_consumed`
--
ALTER TABLE `ticket_parts_consumed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `toolings`
--
ALTER TABLE `toolings`
  MODIFY `tooling_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tooling_bom`
--
ALTER TABLE `tooling_bom`
  MODIFY `bom_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tooling_documents`
--
ALTER TABLE `tooling_documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_registration_config`
--
ALTER TABLE `user_registration_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uuid_rules`
--
ALTER TABLE `uuid_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors_suppliers`
--
ALTER TABLE `vendors_suppliers`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workshops`
--
ALTER TABLE `workshops`
  MODIFY `workshop_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_orders`
--
ALTER TABLE `work_orders`
  MODIFY `wo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wo_attachments`
--
ALTER TABLE `wo_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_tickets`
--
ALTER TABLE `active_tickets`
  ADD CONSTRAINT `active_tickets_ibfk_1` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE;

--
-- Constraints for table `department_budget_logs`
--
ALTER TABLE `department_budget_logs`
  ADD CONSTRAINT `department_budget_logs_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_budget_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`parent_asset_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_bom`
--
ALTER TABLE `equipment_bom`
  ADD CONSTRAINT `equipment_bom_ibfk_1` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_documents`
--
ALTER TABLE `equipment_documents`
  ADD CONSTRAINT `fk_equip_docs_equip_id` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_ledger`
--
ALTER TABLE `inventory_ledger`
  ADD CONSTRAINT `fk_ledger_part` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`);

--
-- Constraints for table `inventory_parts`
--
ALTER TABLE `inventory_parts`
  ADD CONSTRAINT `fk_inv_vendor` FOREIGN KEY (`manufacturer_id`) REFERENCES `vendors_suppliers` (`vendor_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pm_checklist_items`
--
ALTER TABLE `pm_checklist_items`
  ADD CONSTRAINT `fk_checklist_id` FOREIGN KEY (`checklist_id`) REFERENCES `pm_checklists` (`checklist_id`) ON DELETE CASCADE;

--
-- Constraints for table `po_documents`
--
ALTER TABLE `po_documents`
  ADD CONSTRAINT `fk_podoc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_items_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`);

--
-- Constraints for table `po_status_logs`
--
ALTER TABLE `po_status_logs`
  ADD CONSTRAINT `po_status_logs_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `production_lines`
--
ALTER TABLE `production_lines`
  ADD CONSTRAINT `production_lines_ibfk_1` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors_suppliers` (`vendor_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

--
-- Constraints for table `system_audit_logs`
--
ALTER TABLE `system_audit_logs`
  ADD CONSTRAINT `system_audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_actions`
--
ALTER TABLE `ticket_actions`
  ADD CONSTRAINT `ticket_actions_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_comments`
--
ALTER TABLE `ticket_comments`
  ADD CONSTRAINT `fk_ticket_comments` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_parts_consumed`
--
ALTER TABLE `ticket_parts_consumed`
  ADD CONSTRAINT `ticket_parts_consumed_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`),
  ADD CONSTRAINT `ticket_parts_consumed_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`);

--
-- Constraints for table `tooling_bom`
--
ALTER TABLE `tooling_bom`
  ADD CONSTRAINT `tooling_bom_part_fk` FOREIGN KEY (`part_id`) REFERENCES `inventory_parts` (`part_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tooling_bom_tooling_fk` FOREIGN KEY (`tooling_id`) REFERENCES `toolings` (`tooling_id`) ON DELETE CASCADE;

--
-- Constraints for table `tooling_documents`
--
ALTER TABLE `tooling_documents`
  ADD CONSTRAINT `fk_tooling_docs_tooling_id` FOREIGN KEY (`tooling_id`) REFERENCES `toolings` (`tooling_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD CONSTRAINT `work_orders_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
