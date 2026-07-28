-- 0017_create_toolings.sql
-- Purpose: Manufacturing tooling registry (dies, molds, fixtures, jigs, gauges,
--          hand tools, cutting tools). Allocable to equipment via linked_equip_id.
--
-- Companion API (api/companion/toolings.php + scan_lookup) already expects:
--   tooling_id, tooling_name, tooling_code, barcode, asset_tag, category,
--   status, location, notes
--
-- Safe / idempotent: CREATE TABLE IF NOT EXISTS + seed INSERT IGNORE.

CREATE TABLE IF NOT EXISTS `toolings` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed standard plant tooling (codes unique → re-run safe via INSERT IGNORE)
INSERT IGNORE INTO `toolings`
  (`tooling_code`,`tooling_name`,`category`,`tooling_type`,`barcode`,`asset_tag`,
   `oem_brand`,`oem_model`,`serial_number`,`status`,`condition_rating`,`location`,
   `workshop_id`,`linked_equip_id`,`owner_dept`,`calibration_due`,`last_calibration`,
   `purchase_date`,`cost`,`notes`,`is_active`)
VALUES
('TL-DIE-001','Progressive Die — Bracket Blanking','Die','Progressive die','BC-TL-DIE-001','AT-DIE-001',
 'Schuler','PD-240','SN-D240-01','In Use','Good','Tool Crib A / Rack D1',
 1,1,'Tooling & Fixtures',NULL,NULL,'2022-03-15',18500.00,'Primary die for bracket family; allocated to NHX 5000 cell.',1),

('TL-DIE-002','Form Die — Cover Deep Draw','Die','Form die','BC-TL-DIE-002','AT-DIE-002',
 'Aida','FD-120','SN-FD120-08','Available','Good','Tool Crib A / Rack D2',
 1,NULL,'Tooling & Fixtures',NULL,NULL,'2021-11-02',12400.00,'Spare form die; not currently allocated.',1),

('TL-MLD-001','Injection Mold Insert Set — Housing','Mold','Insert mold','BC-TL-MLD-001','AT-MLD-001',
 'Husky','IM-H88','SN-H88-14','Maintenance','Fair','Tool Room / Bench 3',
 1,NULL,'Tooling & Fixtures',NULL,NULL,'2020-06-20',9200.00,'Cavity polish scheduled; do not issue.',1),

('TL-FIX-001','Weld Fixture — Frame Subassy','Fixture','Welding fixture','BC-TL-FIX-001','AT-FIX-001',
 'In-house','WF-FRAME','SN-WF-03','In Use','Good','Weld Bay / Station 1',
 1,7,'Tooling & Fixtures',NULL,NULL,'2023-01-10',6400.00,'Allocated to Fronius TPS 400i weld station.',1),

('TL-FIX-002','Robot Gripper Finger Set','Fixture','EOAT','BC-TL-FIX-002','AT-FIX-002',
 'Schunk','PGN-plus','SN-PGN-22','In Use','Good','Robot Cell / KUKA',
 1,8,'Tooling & Fixtures',NULL,NULL,'2023-08-01',2100.00,'Allocated to KUKA KR 60 weld robot.',1),

('TL-JIG-001','Drill Jig — Manifold Ports','Jig','Drill jig','BC-TL-JIG-001','AT-JIG-001',
 'In-house','DJ-MAN','SN-DJ-05','Available','Good','Tool Crib B / Shelf J1',
 1,4,'Tooling & Fixtures',NULL,NULL,'2022-09-12',890.00,'Fits Haas VF-4SS manifold setup.',1),

('TL-GAU-001','Go/No-Go Plug Gauge Ø12 H7','Gauge','Plug gauge','BC-TL-GAU-001','AT-GAU-001',
 'Mitutoyo','PG-12H7','SN-PG12-09','Calibration Due','Good','Metrology Cabinet 2',
 1,NULL,'Quality / Metrology',DATE_ADD(CURDATE(), INTERVAL -5 DAY),DATE_ADD(CURDATE(), INTERVAL -370 DAY),'2021-04-01',180.00,'Overdue calibration — quarantine until re-cert.',1),

('TL-GAU-002','Digital Torque Wrench 5–100 Nm','Hand Tool','Torque wrench','BC-TL-GAU-002','AT-TW-002',
 'Snap-on','ATECH3FR100','SN-TW100-17','Available','Good','Tool Crib B / Hook T4',
 1,NULL,'Maintenance Operations',DATE_ADD(CURDATE(), INTERVAL 45 DAY),DATE_ADD(CURDATE(), INTERVAL -320 DAY),'2023-02-14',420.00,'Cal due within 45 days.',1),

('TL-CUT-001','End Mill Set Carbide 4–16 mm','Cutting Tool','End mill kit','BC-TL-CUT-001','AT-CUT-001',
 'Sandvik','CoroMill','SN-EM-44','In Use','Good','Tool Crib A / Cart C1',
 1,2,'Tooling & Fixtures',NULL,NULL,'2024-01-20',650.00,'Allocated to Mazak VTC-800.',1),

('TL-CUT-002','Turning Insert Kit CNMG 1204','Cutting Tool','Insert kit','BC-TL-CUT-002','AT-CUT-002',
 'Kennametal','CNMG120408','SN-CN-88','Available','New','Tool Crib A / Drawer I3',
 1,3,'Tooling & Fixtures',NULL,NULL,'2024-05-01',290.00,'Spare kit for Okuma LB3000.',1),

('TL-COL-001','CNC Collet Set ER32','Cutting Tool','Collet set','BC-TL-COL-001','AT-COL-001',
 'Rego-Fix','ER32','SN-ER32-11','Available','Good','Tool Crib A / Drawer C2',
 1,NULL,'Tooling & Fixtures',NULL,NULL,'2022-07-18',340.00,'Shared pool — issue against open work orders.',1),

('TL-HND-001','Dial Indicator Set 0.001 mm','Gauge','Indicator set','BC-TL-HND-001','AT-IND-001',
 'Mitutoyo','2046S','SN-DI-33','Available','Good','Metrology Cabinet 1',
 1,NULL,'Quality / Metrology',DATE_ADD(CURDATE(), INTERVAL 120 DAY),DATE_ADD(CURDATE(), INTERVAL -245 DAY),'2021-12-05',260.00,NULL,1);
