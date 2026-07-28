-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: workshop_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `active_tickets`
--

DROP TABLE IF EXISTS `active_tickets`;
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `equip_id` (`equip_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  CONSTRAINT `active_tickets_ibfk_1` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_tickets`
--

LOCK TABLES `active_tickets` WRITE;
/*!40000 ALTER TABLE `active_tickets` DISABLE KEYS */;
INSERT INTO `active_tickets` VALUES ('TK-MOCK-1',16,NULL,NULL,NULL,NULL,NULL,'normal','CLOSED','Unknown','2026-07-15 14:47:51',NULL),('TK-MOCK-2',17,NULL,NULL,NULL,NULL,NULL,'normal','CLOSED','Unknown','2026-07-15 14:47:51',NULL),('TK-MOCK-250509-1609',1,'2025-05-09','15:25:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250510-7156',1,'2025-05-10','14:03:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250511-6767',1,'2025-05-11','16:12:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250513-2938',1,'2025-05-13','07:34:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250519-5018',1,'2025-05-19','08:20:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250604-2272',1,'2025-06-04','13:33:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250619-6953',1,'2025-06-19','12:37:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250628-1049',1,'2025-06-28','13:21:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250628-5403',1,'2025-06-28','11:22:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250704-5201',1,'2025-07-04','14:08:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250714-7882',1,'2025-07-14','13:32:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250725-3481',1,'2025-07-25','11:49:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250806-3343',1,'2025-08-06','09:18:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250812-8507',1,'2025-08-12','13:29:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250820-3950',1,'2025-08-20','09:18:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250902-1043',1,'2025-09-02','07:37:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250908-7852',1,'2025-09-08','10:55:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250910-9713',1,'2025-09-10','15:50:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250912-9267',1,'2025-09-12','08:46:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-250923-8899',1,'2025-09-23','09:16:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251001-7861',1,'2025-10-01','15:07:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251009-1300',1,'2025-10-09','10:40:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251012-6721',1,'2025-10-12','07:22:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251025-6665',1,'2025-10-25','09:30:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251028-1311',1,'2025-10-28','16:18:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251103-7334',1,'2025-11-03','10:27:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251106-4889',1,'2025-11-06','10:09:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251116-9358',1,'2025-11-16','14:48:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251126-6912',1,'2025-11-26','08:05:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251203-5185',1,'2025-12-03','13:32:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251204-9724',1,'2025-12-04','16:12:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251211-1794',1,'2025-12-11','15:19:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251220-4057',1,'2025-12-20','11:25:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-251224-1157',1,'2025-12-24','15:10:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260102-4921',1,'2026-01-02','07:15:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260109-7456',1,'2026-01-09','12:58:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260126-1258',1,'2026-01-26','13:02:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260206-1062',1,'2026-02-06','08:23:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260220-8452',1,'2026-02-20','16:00:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260222-9345',1,'2026-02-22','13:50:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260302-5663',1,'2026-03-02','09:34:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260317-9812',1,'2026-03-17','09:25:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260327-9621',1,'2026-03-27','14:22:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260408-6776',1,'2026-04-08','13:43:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260410-9674',1,'2026-04-10','08:06:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260413-3573',1,'2026-04-13','10:09:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260415-4536',1,'2026-04-15','13:43:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260421-7685',1,'2026-04-21','14:33:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260422-7049',1,'2026-04-22','07:03:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260506-1344',1,'2026-05-06','12:54:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260511-9559',1,'2026-05-11','14:41:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260512-7230',1,'2026-05-12','14:57:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260515-6703',1,'2026-05-15','09:34:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260522-6680',1,'2026-05-22','15:58:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260527-8565',1,'2026-05-27','12:38:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260601-2614',1,'2026-06-01','08:39:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260611-3815',1,'2026-06-11','16:29:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260611-6785',1,'2026-06-11','11:56:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260611-8971',1,'2026-06-11','12:26:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260619-6716',1,'2026-06-19','09:32:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260707-3469',1,'2026-07-07','08:08:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260718-6747',1,'2026-07-18','09:30:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260721-8639',1,'2026-07-21','12:15:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260723-2868',1,'2026-07-23','07:41:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260725-5584',1,'2026-07-25','11:52:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-MOCK-260727-4838',1,'2026-07-27','14:57:00',NULL,NULL,'Auto generated mock issue','Medium','CLOSED','Unknown','2026-07-15 20:17:47',NULL),('TK-QR-260711-184627',6,'2026-07-11','18:46:27','admin','admin','55','low','CLOSED','Unknown','2026-07-11 16:46:27',NULL),('TK-QR-260714-195719',6,'2026-07-14','19:57:19','admin','admin','999','low','CLOSED','Unknown','2026-07-14 17:57:19',NULL),('TK-QR-260714-200400',1,'2026-07-14','20:04:00','admin','admin','wer','low','CLOSED','Unknown','2026-07-14 18:04:00',NULL),('TK-QR-260714-200420',2,'2026-07-14','20:04:20','admin','admin','rww','low','CLOSED','Unknown','2026-07-14 18:04:20',NULL),('TK-QR-260714-200547',11,'2026-07-14','20:05:47','admin','admin','verynice','low','CLOSED','Unknown','2026-07-14 18:05:47',NULL),('TK-QR-260714-231343',4,'2026-07-14','23:13:43','admin','admin','555','low','CLOSED','Unknown','2026-07-14 21:13:43',NULL),('TK-QR-260714-231352',6,'2026-07-14','23:13:52','admin','admin','999','low','CLOSED','Unknown','2026-07-14 21:13:52',NULL),('TK-QR-260716-174817',3,'2026-07-16','17:48:17','admin','admin','55','low','CLOSED','Unknown','2026-07-16 15:48:17',NULL),('TK-WCC-260714-005',3,'2026-07-14','00:15:00','admin','John Doe','55','normal','CLOSED','admin','2026-07-14 21:15:23',NULL),('TK-WEB-260710-100001',1,'2026-07-03','10:00:00','operator1','admin','Main conveyor belt is completely detached from the drive roller.','normal','CLOSED','admin','2026-07-03 18:40:43',NULL),('TK-WEB-260710-100002',4,'2026-07-03','11:00:00','operator1','admin','CNC spindle bearing failure causing severe lateral vibration.','high','CLOSED','admin','2026-07-03 18:40:43',NULL),('TK-WEB-260710-100003',1,'2026-07-07','18:00:00','admin','admin','Hydraulic fluid leak detected near the primary press actuator.','low','CLOSED','admin','2026-07-07 18:15:28',NULL),('TK-WEB-260710-100004',6,'2026-07-09','00:54:29','admin','admin','Proximity sensor on sorting line 2 is misaligned and failing to trigger.','low','CLOSED','Unknown','2026-07-08 22:54:29',NULL),('TK-WEB-260710-100005',1,'2026-07-09','00:17:24','QA','admin','Safety light curtain fault preventing machine startup sequence.','normal','CLOSED','Unknown','2026-07-08 21:17:24',NULL),('TK-WEB-260710-100006',5,'2026-07-01','08:00:00','Operator','admin','Pneumatic pressure drop in the packaging arm circuit.','normal','CLOSED','Unknown','2026-07-08 21:11:32',NULL),('TK-WEB-260710-100007',5,'2026-07-02','08:00:00','Operator','admin','Overheating warning triggered on the main extruder motor.','normal','CLOSED','Unknown','2026-07-08 21:11:32',NULL),('TK-WEB-260710-100008',5,'2026-07-03','08:00:00','Operator','admin','Control panel touchscreen is unresponsive to operator inputs.','normal','CLOSED','Unknown','2026-07-08 21:11:32',NULL),('TK-WEB-260710-100009',5,'2026-07-04','08:00:00','Operator','admin','Conveyor jam detected at the secondary visual inspection station.','normal','CLOSED','Unknown','2026-07-08 21:11:32',NULL),('TK-WEB-260710-100010',5,'2026-07-05','08:00:00','Operator','admin','VFD fault code F004 displayed on the main chiller unit.','normal','CLOSED','Unknown','2026-07-08 21:11:32',NULL),('TK-WEB-260710-100011',1,'2026-07-08','20:39:00','Mike Ross','admin','Coolant pump flow rate is below the required operational threshold.','high','CLOSED','Mike Ross','2026-07-08 17:40:52',NULL),('TK-WEB-260710-100012',1,'2026-07-08','00:30:00','admin','admin','Abnormal grinding noise originating from the gearbox assembly.','normal','CLOSED','admin','2026-07-08 21:30:54',NULL),('TK-WEB-260710-100013',4,'2026-07-08','00:50:00','admin','admin','Emergency stop button latch failure on operator station 3.','critical','CLOSED','admin','2026-07-08 21:51:01',NULL),('TK-WEB-260710-100014',6,'2026-07-08','01:30:00','admin','admin','Servo motor encoder synchronization error during homing cycle.','high','CLOSED','admin','2026-07-08 22:30:59',NULL),('TK-WEB-260710-100015',1,'2026-07-09','00:20:00','admin','admin','Conveyor belt is making a loud squeaking noise under heavy load.','low','CLOSED','admin','2026-07-09 21:21:59',NULL),('TK-WEB-260712-204325',6,'2026-07-12','20:42:00','admin','Jane Smith','Grok didnt broke this.','normal','CLOSED','admin','2026-07-12 17:43:25',NULL),('TK-WEB-260712-204601',6,'2026-07-12','20:45:00','admin','John Doe','Escalation','normal','CLOSED','admin','2026-07-12 17:46:01',NULL),('TK-WEB-260714-231751',13,'2026-07-14','00:17:00','admin','Jane Smith','77','normal','CLOSED','admin','2026-07-14 21:17:51',NULL),('TK-WEB-260716-165316',4,'2026-07-16','17:53:00','admin','Jane Smith','55','normal','CLOSED','admin','2026-07-16 14:53:16',NULL),('TK-WEB-260716-171245',6,'2026-07-16','18:12:00','admin','Jane Smith','77','normal','CLOSED','admin','2026-07-16 15:12:45',NULL),('TK-WEB-260716-172158',3,'2026-07-16','18:21:00','admin','Jane Smith','3','normal','CLOSED','admin','2026-07-16 15:21:58',NULL),('TK-WEB-260716-173607',11,'2026-07-16','18:35:00','admin','John Doe','5','normal','CLOSED','admin','2026-07-16 15:36:07',NULL),('TK-WEB-260716-174147',4,'2026-07-16','18:41:00','admin','John Doe','test','normal','CLOSED','admin','2026-07-16 15:41:47',NULL),('TK-WEB-260716-174216',4,'2026-07-16','18:42:00','admin','Jane Smith','test2','normal','CLOSED','admin','2026-07-16 15:42:16',NULL),('TK-WEB-260716-174320',3,'2026-07-16','18:43:00','admin','John Doe','test3','normal','CLOSED','admin','2026-07-16 15:43:20',NULL),('TK-WEB-260716-174400',10,'2026-07-16','18:43:00','admin','Jane Smith','test4','normal','CLOSED','admin','2026-07-16 15:44:00',NULL),('TK-WEB-260716-174516',11,'2026-07-16','18:45:00','admin','Jane Smith','Comment test 1','normal','CLOSED','admin','2026-07-16 15:45:16',NULL),('TK-WEB-260716-174525',8,'2026-07-16','18:45:00','admin','John Doe','Comment test 2','normal','CLOSED','admin','2026-07-16 15:45:25',NULL),('TK-WEB-260716-174836',7,'2026-07-16','18:48:00','admin','John Doe','MAJOR MAJOR','high','CLOSED','admin','2026-07-16 15:48:36',NULL),('TK-WEB-260716-175011',10,'2026-07-16','18:49:00','admin','John Doe','Closeout comments ','normal','CLOSED','admin','2026-07-16 15:50:11',NULL),('TK-WEB-260717-215431',1,'2026-07-17','22:54:00','admin','UI Verification','UI unification verification ticket (Phase 5 end-to-end test)','normal','CLOSED','admin','2026-07-17 19:54:31',NULL);
/*!40000 ALTER TABLE `active_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `analytics_logs`
--

DROP TABLE IF EXISTS `analytics_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytics_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `metric_name` (`metric_name`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_logs`
--

LOCK TABLES `analytics_logs` WRITE;
/*!40000 ALTER TABLE `analytics_logs` DISABLE KEYS */;
INSERT INTO `analytics_logs` VALUES (1,'Total Breakdowns',92.00,'2026-07-16 03:32:33'),(2,'Total Downtime (Hrs)',532.13,'2026-07-16 03:32:33'),(3,'MTTR (Hrs)',5.78,'2026-07-16 03:32:33'),(4,'MTTD (Hrs)',10.28,'2026-07-16 03:32:33'),(5,'MTBF (Hrs)',127.26,'2026-07-16 03:32:33'),(6,'Total PO Orders',43.00,'2026-07-16 03:32:33'),(7,'OEE (%)',95.65,'2026-07-16 03:32:33');
/*!40000 ALTER TABLE `analytics_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,'Security','session_lockout_time','360'),(4,'SLA','sla_hours_critical','2'),(5,'SLA','sla_hours_high','8'),(6,'SLA','sla_hours_normal','48'),(8,'KPI','target_mttd','60'),(9,'KPI','target_mttr','120'),(10,'KPI','target_mtbf','48'),(11,'KPI','plant_holidays','[]'),(12,'KPI','target_calc_mode','dynamic'),(13,'Features','allow_checklist_photos','0');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'2026-07-14 16:32:11',1,'inventory.receipt','inventory_parts','2',NULL,'{\"qty\":15}','PO 122 receipt'),(2,'2026-07-14 17:37:15',1,'work_order.completed','work_orders','26','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(3,'2026-07-14 17:43:07',1,'work_order.completed','work_orders','1','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(4,'2026-07-14 17:43:07',1,'inventory.deduct','inventory_parts','901',NULL,'{\"qty\":40}','WO 1 consumption'),(5,'2026-07-14 17:45:33',1,'work_order.completed','work_orders','35','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(6,'2026-07-14 18:00:38',1,'ticket.close','active_tickets','TK-WEB-260710-100002','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(7,'2026-07-14 18:01:59',1,'ticket.close','active_tickets','TK-WEB-260710-100001','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(8,'2026-07-14 18:04:06',1,'ticket.close','active_tickets','TK-WEB-260710-100012','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(9,'2026-07-14 20:56:24',1,'work_order.completed','work_orders','27','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(10,'2026-07-14 20:56:24',1,'inventory.deduct','inventory_parts','2',NULL,'{\"qty\":1}','WO 27 consumption'),(11,'2026-07-14 21:15:08',1,'work_order.completed','work_orders','32','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(12,'2026-07-14 21:15:08',1,'inventory.deduct','inventory_parts','904',NULL,'{\"qty\":1}','WO 32 consumption'),(13,'2026-07-14 21:15:23',1,'ticket.create','active_tickets','TK-WCC-260714-005',NULL,'{\"equip_id\":\"3\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(14,'2026-07-14 21:17:51',1,'ticket.create','active_tickets','TK-WEB-260714-231751',NULL,'{\"equip_id\":\"13\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(15,'2026-07-14 21:18:23',1,'ticket.close','active_tickets','TK-WEB-260714-231751','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(16,'2026-07-14 22:10:58',1,'user.update','user','3',NULL,NULL,'Profile/permissions updated by admin'),(17,'2026-07-14 22:11:10',1,'user.update','user','3',NULL,NULL,'Profile/permissions updated by admin'),(18,'2026-07-14 22:11:22',1,'user.update','user','7',NULL,NULL,'Profile/permissions updated by admin'),(19,'2026-07-14 22:13:13',1,'user.update','user','7',NULL,NULL,'Profile/permissions updated by admin'),(20,'2026-07-15 15:28:32',1,'work_order.completed','work_orders','33','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(21,'2026-07-15 15:28:32',1,'inventory.deduct','inventory_parts','2',NULL,'{\"qty\":2}','WO 33 consumption'),(22,'2026-07-15 15:33:22',1,'inventory.receipt','inventory_parts','2',NULL,'{\"qty\":10}','PO 143 receipt'),(23,'2026-07-16 14:53:16',1,'ticket.create','active_tickets','TK-WEB-260716-165316',NULL,'{\"equip_id\":\"4\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(24,'2026-07-16 15:05:13',1,'ticket.close','active_tickets','TK-WEB-260716-165316','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(25,'2026-07-16 15:06:21',1,'ticket.close','active_tickets','TK-WEB-260710-100014','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(26,'2026-07-16 15:12:45',1,'ticket.create','active_tickets','TK-WEB-260716-171245',NULL,'{\"equip_id\":\"6\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(27,'2026-07-16 15:13:40',1,'ticket.close','active_tickets','TK-WCC-260714-005','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(28,'2026-07-16 15:14:48',1,'ticket.close','active_tickets','TK-WEB-260716-171245','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(29,'2026-07-16 15:21:58',1,'ticket.create','active_tickets','TK-WEB-260716-172158',NULL,'{\"equip_id\":\"3\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(30,'2026-07-16 15:22:23',1,'ticket.close','active_tickets','TK-WEB-260716-172158','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(31,'2026-07-16 15:22:44',1,'work_order.completed','work_orders','50','{\"status\":\"Scheduled\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(32,'2026-07-16 15:36:07',1,'ticket.create','active_tickets','TK-WEB-260716-173607',NULL,'{\"equip_id\":\"11\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(33,'2026-07-16 15:41:26',1,'ticket.close','active_tickets','TK-WEB-260716-173607','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(34,'2026-07-16 15:41:47',1,'ticket.create','active_tickets','TK-WEB-260716-174147',NULL,'{\"equip_id\":\"4\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(35,'2026-07-16 15:42:03',1,'ticket.close','active_tickets','TK-WEB-260716-174147','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(36,'2026-07-16 15:42:16',1,'ticket.create','active_tickets','TK-WEB-260716-174216',NULL,'{\"equip_id\":\"4\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(37,'2026-07-16 15:43:06',1,'ticket.close','active_tickets','TK-WEB-260716-174216','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(38,'2026-07-16 15:43:20',1,'ticket.create','active_tickets','TK-WEB-260716-174320',NULL,'{\"equip_id\":\"3\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(39,'2026-07-16 15:44:00',1,'ticket.create','active_tickets','TK-WEB-260716-174400',NULL,'{\"equip_id\":\"10\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(40,'2026-07-16 15:44:15',1,'ticket.close','active_tickets','TK-WEB-260716-174400','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(41,'2026-07-16 15:44:54',1,'ticket.close','active_tickets','TK-WEB-260716-174320','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(42,'2026-07-16 15:45:16',1,'ticket.create','active_tickets','TK-WEB-260716-174516',NULL,'{\"equip_id\":\"11\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"Jane Smith\"}','New intervention logged'),(43,'2026-07-16 15:45:25',1,'ticket.create','active_tickets','TK-WEB-260716-174525',NULL,'{\"equip_id\":\"8\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(44,'2026-07-16 15:45:50',1,'ticket.close','active_tickets','TK-WEB-260716-174525','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(45,'2026-07-16 15:46:26',1,'ticket.close','active_tickets','TK-WEB-260716-174516','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(46,'2026-07-16 15:48:36',1,'ticket.create','active_tickets','TK-WEB-260716-174836',NULL,'{\"equip_id\":\"7\",\"priority\":\"high\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(47,'2026-07-16 15:49:34',1,'ticket.close','active_tickets','TK-WEB-260716-174836','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(48,'2026-07-16 15:50:11',1,'ticket.create','active_tickets','TK-WEB-260716-175011',NULL,'{\"equip_id\":\"10\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"John Doe\"}','New intervention logged'),(49,'2026-07-16 15:55:15',1,'ticket.close','active_tickets','TK-WEB-260716-175011','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off'),(50,'2026-07-16 16:50:36',1,'work_order.in progress','work_orders','51','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(51,'2026-07-16 16:52:03',1,'work_order.in progress','work_orders','51','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(52,'2026-07-16 16:52:26',1,'work_order.in progress','work_orders','52','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(53,'2026-07-16 17:10:19',1,'work_order.in progress','work_orders','51','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(54,'2026-07-16 17:10:42',1,'work_order.completed','work_orders','51','{\"status\":\"In Progress\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(55,'2026-07-16 17:10:56',1,'work_order.completed','work_orders','52','{\"status\":\"In Progress\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(56,'2026-07-16 17:30:16',1,'work_order.completed','work_orders','53','{\"status\":\"In Progress\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(57,'2026-07-16 17:54:13',1,'work_order.in progress','work_orders','54','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(58,'2026-07-16 17:54:29',1,'work_order.in progress','work_orders','54','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(59,'2026-07-16 19:46:21',1,'work_order.in progress','work_orders','54','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(60,'2026-07-16 19:47:27',1,'work_order.completed','work_orders','41','{\"status\":\"In Progress\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(61,'2026-07-16 21:25:23',1,'work_order.in progress','work_orders','54','{\"status\":\"In Progress\"}','{\"status\":\"In Progress\",\"completed_by\":1}','WO takeover/close with notes'),(62,'2026-07-16 21:25:41',1,'work_order.completed','work_orders','54','{\"status\":\"In Progress\"}','{\"status\":\"Completed\",\"completed_by\":1}','WO takeover/close with notes'),(63,'2026-07-17 19:54:31',1,'ticket.create','active_tickets','TK-WEB-260717-215431',NULL,'{\"equip_id\":\"1\",\"priority\":\"normal\",\"announced_by\":\"admin\",\"pic\":\"UI Verification\"}','New intervention logged'),(64,'2026-07-17 19:55:12',1,'ticket.close','active_tickets','TK-WEB-260717-215431','{\"status\":\"OPEN\\/ESCALATED\"}','{\"status\":\"CLOSED\",\"closed_by\":\"admin\"}','Supervisor sign-off');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_budget_logs`
--

DROP TABLE IF EXISTS `department_budget_logs`;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_budget_logs`
--

LOCK TABLES `department_budget_logs` WRITE;
/*!40000 ALTER TABLE `department_budget_logs` DISABLE KEYS */;
INSERT INTO `department_budget_logs` VALUES (11,23,'Allocate',10000.00,'Initial Allocation',1,'2026-07-14 17:44:56'),(12,23,'Consume',500.00,'PO Received: PR-20260715-3195',1,'2026-07-15 15:33:22');
/*!40000 ALTER TABLE `department_budget_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `budget_allocated` decimal(12,2) DEFAULT 0.00,
  `budget_consumed` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (23,'Main Budget',10000.00,500.00);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eam_directory`
--

DROP TABLE IF EXISTS `eam_directory`;
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

--
-- Dumping data for table `eam_directory`
--

LOCK TABLES `eam_directory` WRITE;
/*!40000 ALTER TABLE `eam_directory` DISABLE KEYS */;
/*!40000 ALTER TABLE `eam_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,'78cd32d7-341b-48ba-b528-af9b955f763b','','','','Main Conveyor Belt Alpha',NULL,'Mechanical','B',NULL,'','',NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"custom_fields\":[{\"key\":\"12\",\"value\":\"13\"},{\"key\":\"13\",\"value\":\"14\"},{\"key\":\"14\",\"value\":\"15\"}]}',1,NULL,NULL,''),(2,'12ff71d0-4a2b-4214-90ee-055e3e8fd3e7','','','','Robotic Welding Arm B',NULL,'Roboticsa','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',1,NULL,NULL,NULL),(3,NULL,NULL,NULL,NULL,'Packaging Unit 4',NULL,'Packaging','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(4,NULL,NULL,NULL,NULL,'CNC Milling Center',NULL,'Machining','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(5,NULL,NULL,NULL,NULL,'CNC Machine Alpha',NULL,'Production','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(6,'MCH-0001','','','','Test Engine 1',NULL,'Mechanical','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,NULL),(7,'6e0bd51f-847d-4d1e-9f97-df8460c59fd7','','','','Robotic Welding Arm BARD',NULL,'Mechanical','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(8,'MCH-0002','','','','Test Machine Alpha-Gamma',NULL,'Mechanical','A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(9,'4b3a8e9a-5e42-4f8a-84da-240361b28a21','HydroTech','HT-2000','SN-1002394','Hydro Generator Beta V2',NULL,'Power Generation','A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(10,'afdc3b0d-6784-4e3d-9c4e-41cbccb8817c','','','','Subagent Test Machine 1 Edited',NULL,'Testing','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(11,'7496f6bd-900b-40e0-8256-f786e591c4dd','SubagentOEM','','','Subagent Test Machine 2 Edited',NULL,'Testing-Edited','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'1200 RPM','90 PSI',NULL,NULL,NULL,90,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(12,'e9137aa1-cac5-43f8-be21-6474a919e287','','','','Subagent Test Machine 3 Edited',NULL,'Testing-Edited','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(13,'TST-0001','Subagent Brand','Model-S4','SN-S4-123','Subagent Test Machine 4 Edited',NULL,'Testing','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,90,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL),(14,NULL,NULL,NULL,NULL,'Test Equipment',NULL,'TestCategory','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'test-uuid-1234','','','','Test Name',NULL,'SomeCategory','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,0,10,NULL,NULL,NULL,'','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]',NULL,NULL,NULL,NULL),(16,NULL,NULL,NULL,NULL,'Robot Arm A',NULL,'Robotics','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(17,NULL,NULL,NULL,NULL,'Main Conveyor',NULL,'Conveyors','B',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_bom`
--

DROP TABLE IF EXISTS `equipment_bom`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_bom`
--

LOCK TABLES `equipment_bom` WRITE;
/*!40000 ALTER TABLE `equipment_bom` DISABLE KEYS */;
INSERT INTO `equipment_bom` VALUES (1,4,901,1),(2,4,903,1);
/*!40000 ALTER TABLE `equipment_bom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_documents`
--

DROP TABLE IF EXISTS `equipment_documents`;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_documents`
--

LOCK TABLES `equipment_documents` WRITE;
/*!40000 ALTER TABLE `equipment_documents` DISABLE KEYS */;
INSERT INTO `equipment_documents` VALUES (1,12,'55','Other','e9137aa1-cac5-43f8-be21-6474a919e287/1784053341_Figure_1.png','admin','2026-07-14 18:22:21');
/*!40000 ALTER TABLE `equipment_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_ledger`
--

DROP TABLE IF EXISTS `inventory_ledger`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_ledger`
--

LOCK TABLES `inventory_ledger` WRITE;
/*!40000 ALTER TABLE `inventory_ledger` DISABLE KEYS */;
INSERT INTO `inventory_ledger` VALUES (1,'2026-07-14 16:32:11',2,15,'po_receipt','purchase_orders','122',NULL,1),(2,'2026-07-14 17:43:07',901,-40,'wo_consume','work_orders','1',NULL,1),(3,'2026-07-14 20:56:24',2,-1,'wo_consume','work_orders','27',NULL,1),(4,'2026-07-14 21:15:08',904,-1,'wo_consume','work_orders','32',NULL,1),(5,'2026-07-15 15:28:32',2,-2,'wo_consume','work_orders','33',NULL,1),(6,'2026-07-15 15:33:22',2,10,'po_receipt','purchase_orders','143',NULL,1);
/*!40000 ALTER TABLE `inventory_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_parts`
--

DROP TABLE IF EXISTS `inventory_parts`;
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
) ENGINE=InnoDB AUTO_INCREMENT=926 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_parts`
--

LOCK TABLES `inventory_parts` WRITE;
/*!40000 ALTER TABLE `inventory_parts` DISABLE KEYS */;
INSERT INTO `inventory_parts` VALUES (1,'Enterprise Bearing','ENT-BR-01',NULL,20,5,12.50,'VND-BR-999','Bearing, Roller, 50mm','','','',30,0,0,1,'Each','USD',NULL,NULL,0,'','',-1,'A-3','R-12','S-5','B-08',0,NULL,'SN-12345','LOT-99','New','Active',NULL),(2,'Test Part A','SKU-TEST-A',NULL,25,5,50.00,'','','','','',0,0,0,1,'Each','USD',NULL,NULL,0,'','',NULL,'','','','',0,NULL,'','','New','Active',NULL),(900,'Motor Drive','MTR-100',NULL,10,5,120.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(901,'Ball Bearing','BRG-50',NULL,32,5,15.50,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(902,'Conveyor Belt','CVB-200',NULL,1,5,850.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(903,'Alpha Sensor','SEN-001',NULL,45,5,0.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(904,'Beta Valve','VAL-002',NULL,19,5,0.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(905,'TEST','TEST1',NULL,8,5,0.00,'','','','','',0,0,0,1,'Each','USD',NULL,NULL,0,'','',NULL,'','','','',0,NULL,'','','New','Active',NULL),(916,'Allen-Bradley ControlLogix 5580','PLC-AB-5580',21,12,5,1250.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(917,'SKF Explorer Deep Groove Ball Bearing','BRG-SKF-6205',22,50,5,24.50,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(918,'Omron E2E Proximity Sensor','SNR-OMR-E2E',23,30,5,85.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(919,'SMC Pneumatic Cylinder','CYL-SMC-C85',24,15,5,145.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(920,'Siemens SINAMICS V20 Drive','DRV-SIE-V20',25,5,5,450.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(921,'Fluke 87V Multimeter','TOL-FLK-87V',21,3,5,499.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(922,'Festo Solenoid Valve','VAL-FST-VUVS',22,25,5,112.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Each','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(923,'3M Safety Glasses (Box of 20)','PPE-3M-GLS',23,10,5,45.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Box','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(924,'Loctite 242 Threadlocker','CHM-LOC-242',24,15,5,18.50,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Bottle','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL),(925,'Banner Engineering Safety Light Curtain','SAF-BAN-LC',25,2,5,850.00,NULL,NULL,NULL,NULL,NULL,0,0,0,1,'Set','USD',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'New','Active',NULL);
/*!40000 ALTER TABLE `inventory_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_broadcast`
--

DROP TABLE IF EXISTS `notification_broadcast`;
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

--
-- Dumping data for table `notification_broadcast`
--

LOCK TABLES `notification_broadcast` WRITE;
/*!40000 ALTER TABLE `notification_broadcast` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_broadcast` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'system','Mock notification for testing!','index.php',0,'2026-07-07 18:02:01'),(2,1,'po_pending','PR PR-20260707-8893 submitted and is Pending Approval.','purchase_requests.php',0,'2026-07-07 18:05:02');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pm_checklist_items`
--

DROP TABLE IF EXISTS `pm_checklist_items`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_checklist_items`
--

LOCK TABLES `pm_checklist_items` WRITE;
/*!40000 ALTER TABLE `pm_checklist_items` DISABLE KEYS */;
INSERT INTO `pm_checklist_items` VALUES (5,1,'test',1),(6,1,'12',1);
/*!40000 ALTER TABLE `pm_checklist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pm_checklists`
--

DROP TABLE IF EXISTS `pm_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pm_checklists` (
  `checklist_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`checklist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_checklists`
--

LOCK TABLES `pm_checklists` WRITE;
/*!40000 ALTER TABLE `pm_checklists` DISABLE KEYS */;
INSERT INTO `pm_checklists` VALUES (1,'test','test','2026-07-16 16:48:11');
/*!40000 ALTER TABLE `pm_checklists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pm_schedules`
--

DROP TABLE IF EXISTS `pm_schedules`;
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
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_schedules`
--

LOCK TABLES `pm_schedules` WRITE;
/*!40000 ALTER TABLE `pm_schedules` DISABLE KEYS */;
INSERT INTO `pm_schedules` VALUES (1,'Weekly Conveyor PM','',1,NULL,'[\"902\",\"900\"]',NULL,7,'2026-07-16','2026-07-09 06:08:08'),(2,'Electrical Panel Check - Packaging Unit 4','Check for any abnormal wear and tear. Report findings to the supervisor.',3,2,'[]',NULL,14,'2026-08-23','2026-07-09 06:18:21'),(3,'End-Effector Sensor Check - Robotic Welding Arm B','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',2,NULL,'[]',NULL,365,'2026-08-04','2026-07-09 06:18:21'),(4,'End-Effector Sensor Check - Robotic Welding Arm BARD','Verify all sensors are responding within normal parameters.',7,NULL,'[901,903,902]',NULL,14,'2026-08-12','2026-07-09 06:18:21'),(5,'Axis Calibration - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,NULL,'[]',NULL,365,'2026-07-18','2026-07-09 06:18:21'),(6,'Axis Calibration - Robotic Welding Arm B','Verify all sensors are responding within normal parameters.',2,NULL,'[]',NULL,180,'2026-07-16','2026-07-09 06:18:21'),(7,'Motor Amperage Analysis - Main Conveyor Belt Alpha','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',1,NULL,'[]',NULL,30,'2026-07-21','2026-07-09 06:18:21'),(8,'Axis Calibration - Robotic Welding Arm B','Check for any abnormal wear and tear. Report findings to the supervisor.',2,1,'[904,903]',NULL,14,'2026-07-24','2026-07-09 06:18:21'),(9,'Way Lube Top-Off - CNC Machine Alpha','Check for any abnormal wear and tear. Report findings to the supervisor.',5,NULL,'[903,2]',NULL,30,'2026-07-10','2026-07-09 06:18:21'),(10,'Axis Calibration - Robotic Welding Arm B','Check for any abnormal wear and tear. Report findings to the supervisor.',2,3,'[]',NULL,180,'2026-07-23','2026-07-09 06:18:21'),(11,'Electrical Panel Check - Test Engine 1','Check for any abnormal wear and tear. Report findings to the supervisor.',6,NULL,'[904]',NULL,180,'2026-08-05','2026-07-09 06:18:21'),(12,'Electrical Panel Check - Packaging Unit 4','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',3,NULL,'[904]',NULL,7,'2026-08-04','2026-07-09 06:18:21'),(13,'General Inspection - Test Machine Alpha-Gamma','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',8,2,'[900,902]',NULL,14,'2026-08-14','2026-07-09 06:18:21'),(14,'Electrical Panel Check - Packaging Unit 4','Verify all sensors are responding within normal parameters.',3,NULL,'[900,2]',NULL,180,'2026-08-07','2026-07-09 06:18:21'),(15,'Joint Greasing - Robotic Welding Arm B','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',2,1,'[901,900,903]',NULL,180,'2026-07-26','2026-07-09 06:18:21'),(16,'Joint Greasing - Robotic Welding Arm B','Verify all sensors are responding within normal parameters.',2,2,'[900,904]',NULL,14,'2026-08-17','2026-07-09 06:18:21'),(17,'Axis Calibration - Robotic Welding Arm B','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',2,1,'[]',NULL,180,'2026-07-12','2026-07-09 06:18:21'),(18,'Joint Greasing - Robotic Welding Arm BARD','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',7,NULL,'[904,2]',NULL,7,'2026-07-10','2026-07-09 06:18:21'),(19,'Deep Cleaning - Test Engine 1','Verify all sensors are responding within normal parameters.',6,2,'[904,2,1]',NULL,180,'2026-08-04','2026-07-09 06:18:21'),(20,'General Inspection - Packaging Unit 4','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',3,3,'[1]',NULL,30,'2026-08-20','2026-07-09 06:18:21'),(21,'Belt Tension Check - Main Conveyor Belt Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',1,3,'[1]',NULL,14,'2026-08-10','2026-07-09 06:18:21'),(22,'Coolant Flush - CNC Milling Center','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',4,NULL,'[1,904,902]',NULL,7,'2026-08-19','2026-07-09 06:18:21'),(23,'Deep Cleaning - Test Engine 1','Verify all sensors are responding within normal parameters.',6,1,'[900]',NULL,180,'2026-07-19','2026-07-09 06:18:21'),(24,'Way Lube Top-Off - CNC Milling Center','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',4,NULL,'[902,901]',NULL,30,'2026-08-19','2026-07-09 06:18:21'),(25,'Spindle Runout Test - CNC Machine Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',5,NULL,'[2]',NULL,90,'2026-07-20','2026-07-09 06:18:21'),(26,'Deep Cleaning - Test Engine 1','Check for any abnormal wear and tear. Report findings to the supervisor.',6,3,'[1]',NULL,365,'2026-08-06','2026-07-09 06:18:21'),(27,'General Inspection - Test Engine 1','Verify all sensors are responding within normal parameters.',6,3,'[903,902]',NULL,14,'2026-08-10','2026-07-09 06:18:21'),(28,'Roller Lubrication - Main Conveyor Belt Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',1,NULL,'[1,902]',NULL,14,'2026-08-17','2026-07-09 06:18:21'),(29,'Electrical Panel Check - Test Machine Alpha-Gamma','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',8,3,'[903,1,901]',NULL,7,'2026-08-17','2026-07-09 06:18:21'),(30,'Belt Tension Check - Main Conveyor Belt Alpha','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',1,2,'[900,1,904]',NULL,180,'2026-07-26','2026-07-09 06:18:21'),(31,'Way Lube Top-Off - CNC Milling Center','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',4,NULL,'[900]',NULL,180,'2026-08-22','2026-07-09 06:18:21'),(32,'Axis Calibration - Robotic Welding Arm BARD','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',7,NULL,'[]',NULL,7,'2026-08-16','2026-07-09 06:18:21'),(33,'Belt Tension Check - Main Conveyor Belt Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',1,3,'[2]',NULL,90,'2026-07-11','2026-07-09 06:18:21'),(34,'Spindle Runout Test - CNC Machine Alpha','Verify all sensors are responding within normal parameters.',5,NULL,'[900,2,904]',NULL,14,'2026-07-20','2026-07-09 06:18:21'),(35,'Spindle Runout Test - CNC Machine Alpha','Verify all sensors are responding within normal parameters.',5,NULL,'[1,2,904]',NULL,14,'2026-07-19','2026-07-09 06:18:21'),(36,'Belt Tension Check - Main Conveyor Belt Alpha','Check for any abnormal wear and tear. Report findings to the supervisor.',1,NULL,'[]',NULL,90,'2026-07-21','2026-07-09 06:18:21'),(37,'Belt Tension Check - Main Conveyor Belt Alpha','Check for any abnormal wear and tear. Report findings to the supervisor.',1,NULL,'[903]',NULL,90,'2026-08-19','2026-07-09 06:18:21'),(38,'Deep Cleaning - Packaging Unit 4','Check for any abnormal wear and tear. Report findings to the supervisor.',3,1,'[2,904]',NULL,365,'2026-08-13','2026-07-09 06:18:21'),(39,'Deep Cleaning - Packaging Unit 4','Verify all sensors are responding within normal parameters.',3,3,'[2]',NULL,90,'2026-07-23','2026-07-09 06:18:21'),(40,'Joint Greasing - Robotic Welding Arm B','Verify all sensors are responding within normal parameters.',2,NULL,'[]',NULL,180,'2026-08-06','2026-07-09 06:18:21'),(41,'Deep Cleaning - Test Engine 1','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',6,2,'[2]',NULL,90,'2026-07-27','2026-07-09 06:18:21'),(42,'General Inspection - Test Engine 1','Verify all sensors are responding within normal parameters.',6,1,'[903]',NULL,14,'2026-07-30','2026-07-09 06:18:21'),(43,'Axis Calibration - Robotic Welding Arm BARD','Check for any abnormal wear and tear. Report findings to the supervisor.',7,NULL,'[901]',NULL,7,'2026-08-11','2026-07-09 06:18:21'),(44,'Spindle Runout Test - CNC Milling Center','Check for any abnormal wear and tear. Report findings to the supervisor.',4,1,'[900,901,2]',NULL,14,'2026-07-14','2026-07-09 06:18:21'),(45,'Way Lube Top-Off - CNC Milling Center','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',4,NULL,'[901,2]',NULL,365,'2026-07-24','2026-07-09 06:18:21'),(46,'End-Effector Sensor Check - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,3,'[900,901]',NULL,30,'2026-08-22','2026-07-09 06:18:21'),(47,'Belt Tension Check - Main Conveyor Belt Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',1,NULL,'[2,900,902]',NULL,90,'2026-08-17','2026-07-09 06:18:21'),(48,'Joint Greasing - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,NULL,'[903,1]',NULL,180,'2026-07-11','2026-07-09 06:18:21'),(49,'Spindle Runout Test - CNC Machine Alpha','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',5,NULL,'[902,900]',NULL,7,'2026-07-11','2026-07-09 06:18:21'),(50,'Coolant Flush - CNC Machine Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',5,1,'[900,904,902]',NULL,365,'2026-08-09','2026-07-09 06:18:21'),(51,'Way Lube Top-Off - CNC Milling Center','Check for any abnormal wear and tear. Report findings to the supervisor.',4,3,'[901,900]',NULL,14,'2026-08-23','2026-07-09 06:18:21'),(52,'Deep Cleaning - Test Machine Alpha-Gamma','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',8,3,'[903,1,901]',NULL,180,'2026-08-02','2026-07-09 06:18:21'),(53,'Electrical Panel Check - Test Engine 1','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',6,3,'[900,902]',NULL,30,'2026-08-17','2026-07-09 06:18:21'),(54,'Electrical Panel Check - Packaging Unit 4','Check for any abnormal wear and tear. Report findings to the supervisor.',3,NULL,'[]',NULL,180,'2026-07-11','2026-07-09 06:18:21'),(55,'Axis Calibration - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,NULL,'[904,900,902]',NULL,365,'2026-08-23','2026-07-09 06:18:21'),(56,'Electrical Panel Check - Packaging Unit 4','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',3,2,'[]',NULL,365,'2026-08-14','2026-07-09 06:18:21'),(57,'Motor Amperage Analysis - Main Conveyor Belt Alpha','Verify all sensors are responding within normal parameters.',1,2,'[2,901]',NULL,90,'2026-08-13','2026-07-09 06:18:21'),(58,'Coolant Flush - CNC Machine Alpha','Verify all sensors are responding within normal parameters.',5,1,'[1]',NULL,90,'2026-07-20','2026-07-09 06:18:21'),(59,'Deep Cleaning - Packaging Unit 4','Verify all sensors are responding within normal parameters.',3,NULL,'[2,904]',NULL,7,'2026-07-16','2026-07-09 06:18:21'),(60,'Axis Calibration - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,3,'[900]',NULL,14,'2026-07-30','2026-07-09 06:18:21'),(61,'Deep Cleaning - Test Machine Alpha-Gamma','Check for any abnormal wear and tear. Report findings to the supervisor.',8,NULL,'[901,903]',NULL,180,'2026-08-06','2026-07-09 06:18:21'),(62,'Electrical Panel Check - Packaging Unit 4','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',3,1,'[1,903]',NULL,90,'2026-07-16','2026-07-09 06:18:21'),(63,'Joint Greasing - Robotic Welding Arm BARD','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',7,1,'[]',NULL,30,'2026-08-19','2026-07-09 06:18:21'),(64,'Spindle Runout Test - CNC Machine Alpha','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',5,NULL,'[2,900]',NULL,7,'2026-07-17','2026-07-09 06:18:21'),(65,'Deep Cleaning - Test Engine 1','Please follow the standard operating procedure for this maintenance task. Ensure LOTO is applied.',6,1,'[1]',NULL,180,'2026-07-26','2026-07-09 06:18:21'),(66,'Deep Cleaning - Test Engine 1','Check for any abnormal wear and tear. Report findings to the supervisor.',6,NULL,'[2]',NULL,180,'2026-07-31','2026-07-09 06:18:21'),(67,'Deep Cleaning - Packaging Unit 4','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',3,2,'[]',NULL,14,'2026-07-26','2026-07-09 06:18:21'),(68,'General Inspection - Packaging Unit 4','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',3,NULL,'[902,2]',NULL,365,'2026-08-08','2026-07-09 06:18:21'),(69,'Way Lube Top-Off - CNC Milling Center','Check for any abnormal wear and tear. Report findings to the supervisor.',4,NULL,'[904]',NULL,14,'2026-07-23','2026-07-09 06:18:21'),(70,'Belt Tension Check - Main Conveyor Belt Alpha','Verify all sensors are responding within normal parameters.',1,1,'[902,1]',NULL,90,'2026-08-12','2026-07-09 06:18:21'),(71,'Joint Greasing - Robotic Welding Arm BARD','Verify all sensors are responding within normal parameters.',7,NULL,'[902,2,903]',NULL,7,'2026-08-22','2026-07-09 06:18:21'),(72,'General Inspection - Test Machine Alpha-Gamma','Check for any abnormal wear and tear. Report findings to the supervisor.',8,2,'[901,2]',NULL,7,'2026-07-29','2026-07-09 06:18:21'),(73,'Deep Cleaning - Test Engine 1','Verify all sensors are responding within normal parameters.',6,2,'{\"0\":900,\"2\":904}',NULL,7,'2026-08-08','2026-07-09 06:18:21'),(74,'Electrical Panel Check - Test Engine 1','Verify all sensors are responding within normal parameters.',6,1,'[904,2,903]',NULL,30,'2026-07-20','2026-07-09 06:18:21'),(75,'End-Effector Sensor Check - Robotic Welding Arm B','Perform a thorough cleaning and lubrication. Do not overtighten fittings.',2,NULL,'[1,901,902]',NULL,365,'2026-08-20','2026-07-09 06:18:21'),(76,'Coolant Flush - CNC Milling Center','Check for any abnormal wear and tear. Report findings to the supervisor.',4,NULL,'[2,904,902]',NULL,14,'2026-07-30','2026-07-09 06:18:21');
/*!40000 ALTER TABLE `pm_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_items`
--

DROP TABLE IF EXISTS `po_items`;
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
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_items`
--

LOCK TABLES `po_items` WRITE;
/*!40000 ALTER TABLE `po_items` DISABLE KEYS */;
INSERT INTO `po_items` VALUES (1,1,2,20,0,50.00,'USD','Pending'),(2,100,900,2,0,120.00,'USD','Pending'),(3,101,901,10,0,15.50,'USD','Pending'),(4,102,902,2,0,850.00,'USD','Pending'),(5,103,902,5,0,850.00,'USD','Pending'),(6,104,900,5,0,120.00,'USD','Pending'),(7,105,900,1,0,120.00,'USD','Pending'),(8,106,901,20,10,15.50,'USD','Received'),(9,107,901,1,1,15.50,'USD','Received'),(10,108,902,1,1,850.00,'USD','Received'),(11,109,900,2,0,120.00,'USD','Pending'),(12,110,1,1,1,12.50,'USD','Received'),(13,111,1,5,0,12.50,'USD','Pending'),(14,112,1,50,50,12.50,'USD','Received'),(15,113,1,2,2,12.50,'USD','Received'),(16,114,902,2,0,850.00,'USD','Pending'),(17,115,1,3,3,12.50,'USD','Received'),(18,116,2,4,4,50.00,'USD','Received'),(20,118,900,1,1,120.00,'USD','Received'),(21,119,901,2,2,15.50,'USD','Received'),(22,120,900,1,1,120.00,'USD','Received'),(23,121,2,1,0,50.00,'USD','Pending'),(24,122,2,15,15,50.00,'USD','Received'),(25,124,902,2,2,850.00,'USD','Received'),(26,125,905,5,5,0.00,'USD','Received'),(28,127,922,3,0,382.99,'USD','Pending'),(29,127,918,8,0,869.93,'USD','Pending'),(30,128,923,2,0,724.55,'USD','Pending'),(31,128,917,4,0,284.34,'USD','Pending'),(32,128,920,3,0,673.87,'USD','Pending'),(33,129,919,7,0,244.47,'USD','Pending'),(34,129,920,7,0,788.01,'USD','Pending'),(35,129,919,8,0,630.60,'USD','Pending'),(36,130,922,9,0,108.86,'USD','Pending'),(37,130,925,1,0,121.86,'USD','Pending'),(38,130,923,3,0,469.22,'USD','Pending'),(39,131,917,3,0,684.43,'USD','Pending'),(40,132,920,2,2,221.61,'USD','Received'),(41,133,924,9,0,132.84,'USD','Pending'),(42,134,916,9,7,536.81,'USD','Pending'),(43,134,923,3,1,406.62,'USD','Pending'),(44,134,924,2,1,105.69,'USD','Pending'),(45,135,918,7,0,426.67,'USD','Pending'),(46,135,924,2,0,633.77,'USD','Pending'),(47,135,916,5,0,803.61,'USD','Pending'),(48,136,922,5,0,277.52,'USD','Pending'),(49,136,916,7,0,616.87,'USD','Pending'),(50,137,925,8,3,509.26,'USD','Pending'),(51,137,920,2,1,952.54,'USD','Pending'),(52,138,918,3,1,806.79,'USD','Pending'),(53,138,922,2,1,832.88,'USD','Pending'),(54,138,918,4,1,56.44,'USD','Pending'),(55,139,917,3,0,167.86,'USD','Pending'),(56,139,917,2,0,526.36,'USD','Pending'),(57,140,917,2,2,381.49,'USD','Received'),(58,140,917,2,2,601.06,'USD','Received'),(59,141,920,5,5,840.41,'USD','Received'),(60,143,2,10,10,50.00,'USD','Received');
/*!40000 ALTER TABLE `po_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_status_logs`
--

DROP TABLE IF EXISTS `po_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_status_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `po_id` (`po_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `po_status_logs_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `po_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_status_logs`
--

LOCK TABLES `po_status_logs` WRITE;
/*!40000 ALTER TABLE `po_status_logs` DISABLE KEYS */;
INSERT INTO `po_status_logs` VALUES (1,113,'PR Submitted','Draft','Issued',1,'2026-07-03 19:45:18'),(2,113,'PO Status Re-evaluated',NULL,'Partially Received',1,'2026-07-03 19:46:49'),(3,114,'PR Submitted','Draft','Pending Approval',1,'2026-07-03 20:36:37'),(4,114,'Status Update','Pending Approval','Cancelled',1,'2026-07-03 20:37:09'),(5,115,'PR Submitted','Draft','Issued',1,'2026-07-03 20:37:41'),(6,115,'PO Status Re-evaluated',NULL,'Partially Received',1,'2026-07-03 20:37:58'),(7,116,'PR Submitted','Draft','Issued',1,'2026-07-03 23:30:05'),(8,116,'PO Status Re-evaluated',NULL,'Partially Received',1,'2026-07-03 23:31:02'),(11,116,'Received 2 × Test Part A (2/4)','Pending','Pending',1,'2026-07-04 12:03:30'),(12,116,'Receipt Processed','Partially Received','Partially Received',1,'2026-07-04 12:03:30'),(13,116,'Received 2 × Test Part A (4/4)','Pending','Received',1,'2026-07-04 12:05:49'),(14,116,'Receipt Processed','Partially Received','Fully Received',1,'2026-07-04 12:05:49'),(15,115,'Received 1 × Enterprise Bearing (1/3)','Pending','Pending',1,'2026-07-04 12:05:53'),(16,115,'Receipt Processed','Partially Received','Partially Received',1,'2026-07-04 12:05:53'),(17,115,'Received 2 × Enterprise Bearing (3/3)','Pending','Received',1,'2026-07-04 12:05:59'),(18,115,'Receipt Processed','Partially Received','Fully Received',1,'2026-07-04 12:05:59'),(19,116,'Status Update','Fully Received','Closed',1,'2026-07-04 12:07:37'),(20,115,'Status Update','Fully Received','Closed',1,'2026-07-04 17:00:06'),(21,118,'PR Submitted','Draft','Pending Approval',1,'2026-07-04 17:00:33'),(22,118,'Status Update','Pending Approval','Issued',1,'2026-07-04 17:00:44'),(23,118,'Status Update','Issued','Shipped',1,'2026-07-04 17:00:48'),(24,118,'Status Update','Shipped','In Transit',1,'2026-07-04 17:00:50'),(25,118,'Received 1 × Motor Drive (1/1)','Pending','Received',1,'2026-07-04 17:00:56'),(26,118,'Receipt Processed','In Transit','Fully Received',1,'2026-07-04 17:00:56'),(27,118,'Status Update','Fully Received','Closed',1,'2026-07-04 17:00:59'),(28,113,'Received 1 × Enterprise Bearing (1/2)','Pending','Pending',1,'2026-07-04 21:59:58'),(29,113,'Receipt Processed','Partially Received','Partially Received',1,'2026-07-04 21:59:58'),(30,112,'Status Update','Fully Received','Closed',1,'2026-07-04 22:00:01'),(31,113,'Receipt Processed','Partially Received','Partially Received',1,'2026-07-04 22:00:07'),(32,119,'PR Submitted','Draft','Pending Approval',1,'2026-07-04 22:23:20'),(33,119,'Status Update','Pending Approval','Issued',1,'2026-07-04 22:23:31'),(34,119,'Status Update','Issued','Shipped',1,'2026-07-04 22:23:34'),(35,119,'Status Update','Shipped','In Transit',1,'2026-07-04 22:23:36'),(36,119,'Received 1 × Ball Bearing (1/2)','Pending','Pending',1,'2026-07-04 22:23:45'),(37,119,'Receipt Processed','In Transit','Partially Received',1,'2026-07-04 22:23:45'),(38,119,'Received 1 × Ball Bearing (2/2)','Pending','Received',1,'2026-07-04 22:23:51'),(39,119,'Receipt Processed','Partially Received','Fully Received',1,'2026-07-04 22:23:51'),(40,119,'Status Update','Fully Received','Closed',1,'2026-07-04 22:23:56'),(41,113,'Received 1 × Enterprise Bearing (2/2)','Pending','Received',1,'2026-07-05 01:06:54'),(42,113,'Receipt Processed','Partially Received','Fully Received',1,'2026-07-05 01:06:54'),(43,113,'Status Update','Fully Received','Closed',1,'2026-07-05 01:06:57'),(44,120,'PR Submitted','Draft','Pending Approval',1,'2026-07-05 21:14:12'),(45,120,'Status Update','Pending Approval','Issued',1,'2026-07-05 21:14:25'),(46,120,'Status Update','Issued','Shipped',1,'2026-07-05 21:14:34'),(47,120,'Status Update','Shipped','In Transit',1,'2026-07-05 21:14:38'),(48,120,'Received 1 × Motor Drive (1/1)','Pending','Received',1,'2026-07-05 21:14:47'),(49,120,'Receipt Processed','In Transit','Fully Received',1,'2026-07-05 21:14:47'),(50,120,'Status Update','Fully Received','Closed',1,'2026-07-05 21:14:54'),(51,121,'PR Submitted','Draft','Pending Approval',1,'2026-07-07 17:46:01'),(52,122,'PR Submitted','Draft','Pending Approval',1,'2026-07-07 18:05:02'),(53,123,'Status Update','Fully Received','Closed',1,'2026-07-10 20:56:09'),(54,124,'PR Submitted','Draft','Pending Approval',1,'2026-07-12 17:50:05'),(55,124,'Status Update','Pending Approval','Issued',1,'2026-07-12 17:50:19'),(56,124,'Status Update','Issued','Shipped',1,'2026-07-12 17:50:23'),(57,124,'Status Update','Shipped','In Transit',1,'2026-07-12 17:50:24'),(58,124,'Received 2 × Conveyor Belt (2/2)','Pending','Received',1,'2026-07-12 17:50:29'),(59,124,'Receipt Processed','In Transit','Fully Received',1,'2026-07-12 17:50:29'),(60,124,'Status Update','Fully Received','Closed',1,'2026-07-12 17:50:31'),(61,125,'PR Submitted','Draft','Pending Approval',1,'2026-07-12 17:51:19'),(62,125,'Status Update','Pending Approval','Issued',1,'2026-07-12 17:51:29'),(63,125,'Status Update','Issued','Shipped',1,'2026-07-12 17:51:31'),(64,125,'Status Update','Shipped','In Transit',1,'2026-07-12 17:51:32'),(65,125,'Received 5 × TEST (5/5)','Pending','Received',1,'2026-07-12 17:51:35'),(66,125,'Receipt Processed','In Transit','Fully Received',1,'2026-07-12 17:51:35'),(67,125,'Status Update','Fully Received','Closed',1,'2026-07-12 17:51:37'),(68,122,'Status Update','Pending Approval','Issued',1,'2026-07-14 16:31:55'),(69,122,'Status Update','Issued','Shipped',1,'2026-07-14 16:31:58'),(70,122,'Status Update','Shipped','In Transit',1,'2026-07-14 16:32:02'),(71,122,'Received 15 × Test Part A (15/15)','Pending','Received',1,'2026-07-14 16:32:11'),(72,122,'Receipt Processed','In Transit','Fully Received',1,'2026-07-14 16:32:11'),(73,122,'Status Update','Fully Received','Closed',1,'2026-07-14 16:32:14'),(74,127,'Mock Data Generated','Draft','In Transit',1,'2026-07-14 16:50:53'),(75,128,'Mock Data Generated','Draft','Pending Approval',1,'2026-07-14 16:50:53'),(76,129,'Mock Data Generated','Draft','Pending Approval',1,'2026-07-14 16:50:53'),(77,130,'Mock Data Generated','Draft','Draft',1,'2026-07-14 16:50:53'),(78,131,'Mock Data Generated','Draft','In Transit',1,'2026-07-14 16:50:53'),(79,132,'Mock Data Generated','Draft','Fully Received',1,'2026-07-14 16:50:53'),(80,133,'Mock Data Generated','Draft','In Transit',1,'2026-07-14 16:50:53'),(81,134,'Mock Data Generated','Draft','Partially Received',1,'2026-07-14 16:50:53'),(82,135,'Mock Data Generated','Draft','Pending Approval',1,'2026-07-14 16:50:53'),(83,136,'Mock Data Generated','Draft','In Transit',1,'2026-07-14 16:50:53'),(84,137,'Mock Data Generated','Draft','Partially Received',1,'2026-07-14 16:50:53'),(85,138,'Mock Data Generated','Draft','Partially Received',1,'2026-07-14 16:50:53'),(86,139,'Mock Data Generated','Draft','Draft',1,'2026-07-14 16:50:53'),(87,140,'Mock Data Generated','Draft','Fully Received',1,'2026-07-14 16:50:53'),(88,141,'Mock Data Generated','Draft','Fully Received',1,'2026-07-14 16:50:53'),(89,139,'Status Update','Draft','Cancelled',1,'2026-07-14 16:53:42'),(90,143,'PR Submitted','Draft','Pending Approval',1,'2026-07-15 15:30:27'),(91,143,'Status Update','Pending Approval','Issued',1,'2026-07-15 15:31:57'),(92,143,'Status Update','Issued','Shipped',1,'2026-07-15 15:32:26'),(93,143,'Status Update','Shipped','In Transit',1,'2026-07-15 15:32:52'),(94,143,'Received 10 × Test Part A (10/10)','Pending','Received',1,'2026-07-15 15:33:22'),(95,143,'Receipt Processed','In Transit','Fully Received',1,'2026-07-15 15:33:22'),(96,142,'Status Update','Issued','Shipped',1,'2026-07-18 13:46:34'),(97,142,'Status Update','Shipped','In Transit',1,'2026-07-18 13:46:39');
/*!40000 ALTER TABLE `po_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_lines`
--

DROP TABLE IF EXISTS `production_lines`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_lines`
--

LOCK TABLES `production_lines` WRITE;
/*!40000 ALTER TABLE `production_lines` DISABLE KEYS */;
INSERT INTO `production_lines` VALUES (1,1,'Conveyor Alpha','','Active'),(2,2,'f','','Active');
/*!40000 ALTER TABLE `production_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
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
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-20260703-2456',1,1,NULL,1000.00,'Pending Approval','Maintenance Manager',0,'2026-07-03 19:08:55'),(100,'PO-20260703-1111',1,1,NULL,240.00,'Draft','Auto-Approved',0,'2026-06-28 19:18:42'),(101,'PO-20260703-1112',2,1,NULL,155.00,'Pending Approval','Auto-Approved',0,'2026-06-29 19:18:42'),(102,'PO-20260703-1113',1,1,NULL,1700.00,'Issued','Maintenance Manager',0,'2026-06-30 19:18:42'),(103,'PO-20260703-1114',3,1,NULL,5500.00,'Pending Approval','Plant Director',0,'2026-07-01 19:18:42'),(104,'PO-20260703-1115',2,1,NULL,600.00,'Issued','Maintenance Manager',0,'2026-07-02 19:18:42'),(105,'PO-20260703-1116',1,1,NULL,120.00,'Issued','Auto-Approved',0,'2026-07-03 09:18:42'),(106,'PO-20260703-1117',2,1,NULL,310.00,'Fully Received','Auto-Approved',0,'2026-07-03 11:18:42'),(107,'PO-20260703-1118',3,1,NULL,15.50,'Fully Received','Auto-Approved',0,'2026-07-03 14:18:42'),(108,'PO-20260703-1119',1,1,NULL,850.00,'Closed','Maintenance Manager',0,'2026-07-03 16:18:42'),(109,'PO-20260703-1120',2,1,NULL,240.00,'Cancelled','Auto-Approved',0,'2026-07-03 18:18:42'),(110,'PR-20260703-1848',4,1,NULL,12.50,'Fully Received','Auto-Approved',0,'2026-07-03 19:29:52'),(111,'PR-20260703-1890',8,1,NULL,62.50,'Issued','Auto-Approved',0,'2026-07-03 19:32:04'),(112,'PR-20260703-5273',1,1,NULL,625.00,'Closed','Maintenance Manager',0,'2026-07-03 19:32:29'),(113,'PR-20260703-8059',1,1,NULL,25.00,'Closed','Auto-Approved',0,'2026-07-03 19:45:18'),(114,'PR-20260703-1044',10,1,NULL,1700.00,'Cancelled','Maintenance Manager',0,'2026-07-03 20:36:37'),(115,'PR-20260703-4732',6,1,NULL,37.50,'Closed','Auto-Approved',0,'2026-07-03 20:37:41'),(116,'PR-20260704-4866',8,1,NULL,200.00,'Closed','Auto-Approved',0,'2026-07-03 23:30:05'),(118,'PR-20260704-5267',9,1,NULL,120.00,'Closed','Requires Admin',0,'2026-07-04 17:00:33'),(119,'PR-20260705-3106',10,1,NULL,31.00,'Closed','Requires Admin',0,'2026-07-04 22:23:20'),(120,'PR-20260705-3331',5,1,NULL,120.00,'Closed','Requires Admin',0,'2026-07-05 21:14:12'),(121,'PR-20260707-3427',8,1,NULL,50.00,'Pending Approval','Requires Admin',0,'2026-07-07 17:46:01'),(122,'PR-20260707-8893',8,1,NULL,750.00,'Closed','Requires Admin',0,'2026-07-07 18:05:02'),(123,'PO-QA-1783545444',1,1,NULL,1500.00,'Closed','Auto-Approved',0,'2026-07-08 21:17:24'),(124,'PR-20260712-2703',4,1,NULL,1700.00,'Closed','Requires Admin',0,'2026-07-12 17:50:05'),(125,'PR-20260712-3468',1,1,NULL,0.00,'Closed','Requires Admin',0,'2026-07-12 17:51:19'),(127,'PR-20260001',21,1,NULL,8108.41,'In Transit','Auto-Approved',0,'2026-07-14 16:50:53'),(128,'PR-20260002',25,1,NULL,4608.07,'Pending Approval','Auto-Approved',0,'2026-07-14 16:50:53'),(129,'PR-20260003',22,1,NULL,12272.16,'Pending Approval','Auto-Approved',0,'2026-07-14 16:50:53'),(130,'PR-20260004',24,1,NULL,2509.26,'Draft','Auto-Approved',0,'2026-07-14 16:50:53'),(131,'PR-20260005',24,1,NULL,2053.29,'In Transit','Auto-Approved',0,'2026-07-14 16:50:53'),(132,'PR-20260006',23,1,NULL,443.22,'Fully Received','Auto-Approved',0,'2026-07-14 16:50:53'),(133,'PR-20260007',25,1,NULL,1195.56,'In Transit','Auto-Approved',0,'2026-07-14 16:50:53'),(134,'PR-20260008',22,1,NULL,6262.53,'Partially Received','Auto-Approved',0,'2026-07-14 16:50:53'),(135,'PR-20260009',22,1,NULL,8272.28,'Pending Approval','Auto-Approved',0,'2026-07-14 16:50:53'),(136,'PR-20260010',22,1,NULL,5705.69,'In Transit','Auto-Approved',0,'2026-07-14 16:50:53'),(137,'PR-20260011',22,1,NULL,5979.16,'Partially Received','Auto-Approved',0,'2026-07-14 16:50:53'),(138,'PR-20260012',22,1,NULL,4311.89,'Partially Received','Auto-Approved',0,'2026-07-14 16:50:53'),(139,'PR-20260013',23,1,NULL,1556.30,'Cancelled','Auto-Approved',0,'2026-07-14 16:50:53'),(140,'PR-20260014',21,1,NULL,1965.10,'Fully Received','Auto-Approved',0,'2026-07-14 16:50:53'),(141,'PR-20260015',25,1,NULL,4202.05,'Fully Received','Auto-Approved',0,'2026-07-14 16:50:53'),(142,'PO-CONV-102',23,1,NULL,1200.00,'In Transit','Auto-Approved',0,'2026-07-14 17:42:54'),(143,'PR-20260715-3195',23,1,23,500.00,'Fully Received','Requires Admin',0,'2026-07-15 15:30:27');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_limit`
--

DROP TABLE IF EXISTS `rate_limit`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_limit`
--

LOCK TABLES `rate_limit` WRITE;
/*!40000 ALTER TABLE `rate_limit` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_definitions`
--

DROP TABLE IF EXISTS `role_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_definitions` (
  `role_level` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions_json` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_definitions`
--

LOCK TABLES `role_definitions` WRITE;
/*!40000 ALTER TABLE `role_definitions` DISABLE KEYS */;
INSERT INTO `role_definitions` VALUES (1,'Operator','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":true,\"view_statistics\":false,\"view_equipment\":true,\"view_inventory\":false,\"view_vendors\":false,\"view_work_orders\":false,\"manage_work_orders\":false,\"view_purchase_requests\":false,\"create_purchase_requests\":false,\"approve_purchase_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"manage_equipment\":false,\"manage_inventory\":false,\"manage_vendors\":false,\"reset_passwords\":false}','2026-07-13 04:11:32'),(2,'Technician','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":false,\"view_history\":true,\"view_statistics\":false,\"view_equipment\":true,\"view_inventory\":true,\"view_vendors\":true,\"view_work_orders\":true,\"manage_work_orders\":false,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"manage_equipment\":false,\"manage_inventory\":false,\"manage_vendors\":false,\"reset_passwords\":false}','2026-07-13 04:11:46'),(3,'Supervisor','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":true,\"view_history\":true,\"view_statistics\":true,\"view_equipment\":true,\"view_inventory\":true,\"view_vendors\":true,\"view_work_orders\":true,\"manage_work_orders\":true,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"manage_users\":false,\"manage_settings\":false,\"manage_equipment\":true,\"manage_inventory\":false,\"manage_vendors\":false,\"reset_passwords\":false}','2026-07-13 04:11:32'),(4,'Admin','{\"view_tickets\":true,\"create_tickets\":true,\"takeover_tickets\":true,\"closeout_tickets\":true,\"view_history\":true,\"view_statistics\":true,\"view_equipment\":true,\"view_inventory\":true,\"view_vendors\":true,\"view_work_orders\":true,\"manage_work_orders\":true,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":true,\"manage_users\":true,\"manage_settings\":true,\"manage_equipment\":true,\"manage_inventory\":true,\"manage_vendors\":true,\"reset_passwords\":true}','2026-07-13 04:11:32'),(5,'Custom Viewer','[]','2026-07-13 04:12:18');
/*!40000 ALTER TABLE `role_definitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheduled_reports`
--

DROP TABLE IF EXISTS `scheduled_reports`;
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

--
-- Dumping data for table `scheduled_reports`
--

LOCK TABLES `scheduled_reports` WRITE;
/*!40000 ALTER TABLE `scheduled_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheduled_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'0001_create_schema_migrations_table.sql','2026-07-12 19:12:25'),(2,'0002_add_closed_by_to_active_tickets.sql','2026-07-12 19:12:25'),(3,'0003_add_theme_prefs_json_to_users.sql','2026-07-12 19:12:25'),(4,'0004_create_audit_log_table.sql','2026-07-12 19:17:19'),(5,'0005_add_soft_delete_columns.sql','2026-07-12 19:18:02'),(6,'0006_create_inventory_ledger.sql','2026-07-12 19:18:41'),(7,'0007_enhance_users_table.sql','2026-07-14 18:16:20'),(8,'0008_add_badge_number_and_registration_config.sql','2026-07-14 18:16:20'),(9,'0010_create_equipment_documents.sql','2026-07-14 18:16:20'),(10,'0011_add_api_key_to_users.sql','2026-07-18 13:38:21');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_automation_config`
--

DROP TABLE IF EXISTS `skill_automation_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_automation_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(255) NOT NULL,
  `equipment_category` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT '?',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category` (`equipment_category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_automation_config`
--

LOCK TABLES `skill_automation_config` WRITE;
/*!40000 ALTER TABLE `skill_automation_config` DISABLE KEYS */;
INSERT INTO `skill_automation_config` VALUES (1,'Robotics Tech','Robotics','🤖'),(2,'Conveyor Master','Conveyors','🎢');
/*!40000 ALTER TABLE `skill_automation_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_audit_logs`
--

DROP TABLE IF EXISTS `system_audit_logs`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_audit_logs`
--

LOCK TABLES `system_audit_logs` WRITE;
/*!40000 ALTER TABLE `system_audit_logs` DISABLE KEYS */;
INSERT INTO `system_audit_logs` VALUES (1,1,'LOGIN','User admin logged in','127.0.0.1','2026-07-07 17:36:43'),(2,1,'LOGIN','User admin logged in','127.0.0.1','2026-07-07 17:41:03'),(3,1,'LOGIN','User admin logged in','127.0.0.1','2026-07-07 18:02:38'),(4,1,'LOGIN','User admin logged in','::1','2026-07-07 18:03:08'),(5,1,'LOGIN','User admin logged in','::1','2026-07-07 18:16:43'),(6,1,'LOGIN','User admin logged in','::1','2026-07-07 18:17:44');
/*!40000 ALTER TABLE `system_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_directory`
--

DROP TABLE IF EXISTS `team_directory`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_directory`
--

LOCK TABLES `team_directory` WRITE;
/*!40000 ALTER TABLE `team_directory` DISABLE KEYS */;
INSERT INTO `team_directory` VALUES (1,'John Doe','technical',1,'2026-07-08 16:32:40'),(2,'Jane Smith','technical',1,'2026-07-08 16:32:40'),(3,'Mike Ross','production',1,'2026-07-08 16:32:40'),(4,'Sarah Connor','production',1,'2026-07-08 16:32:40');
/*!40000 ALTER TABLE `team_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_actions`
--

DROP TABLE IF EXISTS `ticket_actions`;
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
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_actions`
--

LOCK TABLES `ticket_actions` WRITE;
/*!40000 ALTER TABLE `ticket_actions` DISABLE KEYS */;
INSERT INTO `ticket_actions` VALUES (1,'TK-WEB-260710-100002','tech1','2026-07-03 21:40:43','2026-07-03 21:40:43','Mechanical','Worn bearing','Replaced bearing','Bearing X1','None','2026-07-03 18:40:43'),(2,'TK-WEB-260710-100003','admin','2026-07-07 18:00:00','2026-07-07 18:00:00','Quick Fix','Minor Adjustment','Cleared jam manually','None','None','2026-07-07 18:15:28'),(3,'TK-WEB-260710-100006','Test Tech','2026-07-01 10:00:00','2026-07-01 12:00:00','Mechanical',NULL,'Fixed it','Part Used',NULL,'2026-07-08 21:11:32'),(4,'TK-WEB-260710-100007','Test Tech','2026-07-02 10:00:00','2026-07-02 12:00:00','Mechanical',NULL,'Fixed it','Part Used',NULL,'2026-07-08 21:11:32'),(5,'TK-WEB-260710-100008','Test Tech','2026-07-03 10:00:00','2026-07-03 12:00:00','Mechanical',NULL,'Fixed it','Part Used',NULL,'2026-07-08 21:11:32'),(6,'TK-WEB-260710-100009','Test Tech','2026-07-04 10:00:00','2026-07-04 12:00:00','Mechanical',NULL,'Fixed it','Part Used',NULL,'2026-07-08 21:11:32'),(7,'TK-WEB-260710-100010','Test Tech','2026-07-05 10:00:00','2026-07-05 12:00:00','Mechanical',NULL,'Fixed it','Part Used',NULL,'2026-07-08 21:11:32'),(8,'TK-WEB-260710-100005','QA Tech','2026-07-08 23:00:00','2026-07-09 00:00:00','Quick Fix',NULL,'Tightened bolts','None',NULL,'2026-07-08 21:17:24'),(9,'TK-WEB-260710-100012','admin','0000-00-00 00:00:00','0000-00-00 00:00:00','Electrical','Test root cause: loose wire connection.','Re-secured the loose wire on terminal block 2.','','None','2026-07-08 21:35:39'),(10,'TK-WEB-260710-100011','admin','2026-07-09 00:42:00','2026-07-09 00:42:00','Electrical','Test Root Cause','ACtion taken','None','Jane Smith','2026-07-08 21:43:14'),(11,'TK-WEB-260710-100011','admin','2026-07-09 00:47:00','2026-07-09 00:47:00','Electrical','Test Root Cause','action','None','None','2026-07-08 21:47:41'),(12,'TK-WEB-260710-100013','admin','2026-07-09 00:51:00','2026-07-09 00:51:00','Electrical','Test Root Cause','11','None','John Doe','2026-07-08 21:51:14'),(13,'TK-WEB-260710-100013','admin','2026-07-09 00:51:00','2026-07-09 00:51:00','Electrical','Test root cause: loose wire connection.','1212','ID: 2 | Test Part A (SKU-TEST-A)','None','2026-07-08 21:51:28'),(14,'TK-WEB-260710-100004','admin','2026-07-09 00:54:29','2026-07-09 00:54:29','Quick Fix','Minor Adjustment','Test instant resolve','None','None','2026-07-08 22:54:29'),(15,'TK-WEB-260710-100015','admin','2026-07-10 00:22:00','2026-07-10 00:22:00','Electrical','Belt wear and tear over time, causing friction reduction.','Replaced the conveyor belt with a new one and calibrated the tension.','None','None','2026-07-09 21:22:35'),(16,'TK-WEB-260710-100014','admin','2026-07-10 00:31:00','2026-07-10 00:31:00','Mechanical','Electrical overload due to power surge.','Replaced the blown fuse and restarted the machine.','None','Jane Smith','2026-07-09 21:32:42'),(17,'TK-QR-260711-184627','admin','2026-07-11 18:46:27','2026-07-11 18:46:27','Quick Fix','Minor Adjustment','55','None','None','2026-07-11 16:46:27'),(18,'TK-WEB-260712-204325','admin','2026-07-12 20:40:00','2026-07-12 20:45:00','Electrical','It didnt','Nothing','None','None','2026-07-12 17:43:56'),(19,'TK-WEB-260712-204601','admin','2026-07-12 20:46:00','2026-07-12 20:46:00','Pneumatic/Hydraulic','1','1','None','Jane Smith','2026-07-12 17:46:16'),(20,'TK-WEB-260712-204601','admin','2026-07-12 20:46:00','2026-07-12 20:46:00','Software/Controls','2','2','None','None','2026-07-12 17:46:31'),(21,'TK-QR-260714-195719','admin','2026-07-14 19:57:19','2026-07-14 19:57:19','Quick Fix','Minor Adjustment','999','None','None','2026-07-14 17:57:19'),(22,'TK-WEB-260710-100001','admin','2026-07-14 21:01:00','2026-07-14 21:01:00','Electrical','123','123','None','John Doe','2026-07-14 18:01:42'),(23,'TK-WEB-260710-100001','admin','2026-07-14 21:01:00','2026-07-14 21:01:00','Electrical','234','234','None','None','2026-07-14 18:01:52'),(24,'TK-QR-260714-200400','admin','2026-07-14 20:04:00','2026-07-14 20:04:00','Quick Fix','Minor Adjustment','wer','None','None','2026-07-14 18:04:00'),(25,'TK-QR-260714-200420','admin','2026-07-14 20:04:20','2026-07-14 20:04:20','Quick Fix','Minor Adjustment','rww','None','None','2026-07-14 18:04:20'),(26,'TK-QR-260714-200547','admin','2026-07-14 20:05:47','2026-07-14 20:05:47','Quick Fix','Minor Adjustment','verynice','None','None','2026-07-14 18:05:47'),(27,'TK-WEB-260710-100014','admin','2026-07-14 23:53:00','2026-07-14 23:53:00','Electrical','Test root cause: loose wire connection.','3','None','Jane Smith','2026-07-14 20:54:01'),(28,'TK-QR-260714-231343','admin','2026-07-14 23:13:43','2026-07-14 23:13:43','Quick Fix','Minor Adjustment','555','None','None','2026-07-14 21:13:43'),(29,'TK-QR-260714-231352','admin','2026-07-14 23:13:52','2026-07-14 23:13:52','Quick Fix','Minor Adjustment','999','None','None','2026-07-14 21:13:52'),(30,'TK-WEB-260714-231751','admin','2026-07-15 00:17:00','2026-07-15 00:17:00','Tooling/Fixture','7','7','None','None','2026-07-14 21:18:03'),(31,'TK-MOCK-1','Admin','2026-07-15 06:00:00','2026-07-15 18:00:00',NULL,NULL,NULL,NULL,NULL,'2026-07-15 14:47:51'),(32,'TK-MOCK-2','Supervisor1','2026-07-10 08:00:00','2026-07-16 14:00:00',NULL,NULL,NULL,NULL,NULL,'2026-07-15 14:47:51'),(33,'TK-MOCK-1','Tech1','2026-06-01 08:00:00','2026-06-11 18:00:00',NULL,NULL,NULL,NULL,NULL,'2026-07-15 14:47:51'),(34,'TK-MOCK-250509-1609',NULL,'2025-05-09 16:46:00','2025-05-09 19:43:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(35,'TK-MOCK-250513-2938',NULL,'2025-05-13 07:58:00','2025-05-13 09:56:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(36,'TK-MOCK-250510-7156',NULL,'2025-05-10 14:49:00','2025-05-10 15:20:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(37,'TK-MOCK-250511-6767',NULL,'2025-05-11 16:45:00','2025-05-11 18:53:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(38,'TK-MOCK-250519-5018',NULL,'2025-05-19 09:20:00','2025-05-19 11:00:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(39,'TK-MOCK-250604-2272',NULL,'2025-06-04 14:33:00','2025-06-04 16:00:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(40,'TK-MOCK-250619-6953',NULL,'2025-06-19 13:36:00','2025-06-19 15:04:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(41,'TK-MOCK-250628-1049',NULL,'2025-06-28 13:52:00','2025-06-28 15:55:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(42,'TK-MOCK-250628-5403',NULL,'2025-06-28 12:46:00','2025-06-28 13:27:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(43,'TK-MOCK-250714-7882',NULL,'2025-07-14 15:06:00','2025-07-14 16:08:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(44,'TK-MOCK-250725-3481',NULL,'2025-07-25 13:10:00','2025-07-25 14:21:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(45,'TK-MOCK-250704-5201',NULL,'2025-07-04 14:51:00','2025-07-04 16:13:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(46,'TK-MOCK-250812-8507',NULL,'2025-08-12 14:05:00','2025-08-12 16:43:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(47,'TK-MOCK-250820-3950',NULL,'2025-08-20 10:44:00','2025-08-20 13:20:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(48,'TK-MOCK-250806-3343',NULL,'2025-08-06 09:39:00','2025-08-06 12:33:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(49,'TK-MOCK-250902-1043',NULL,'2025-09-02 08:53:00','2025-09-02 09:34:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(50,'TK-MOCK-250923-8899',NULL,'2025-09-23 10:04:00','2025-09-23 12:20:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(51,'TK-MOCK-250910-9713',NULL,'2025-09-10 16:18:00','2025-09-10 18:15:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(52,'TK-MOCK-250908-7852',NULL,'2025-09-08 12:10:00','2025-09-08 12:47:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(53,'TK-MOCK-250912-9267',NULL,'2025-09-12 09:42:00','2025-09-12 10:14:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(54,'TK-MOCK-251028-1311',NULL,'2025-10-28 16:39:00','2025-10-28 19:37:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(55,'TK-MOCK-251001-7861',NULL,'2025-10-01 15:50:00','2025-10-01 16:27:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(56,'TK-MOCK-251009-1300',NULL,'2025-10-09 11:45:00','2025-10-09 12:59:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(57,'TK-MOCK-251025-6665',NULL,'2025-10-25 10:26:00','2025-10-25 11:00:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(58,'TK-MOCK-251012-6721',NULL,'2025-10-12 08:39:00','2025-10-12 10:14:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(59,'TK-MOCK-251103-7334',NULL,'2025-11-03 12:05:00','2025-11-03 14:04:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(60,'TK-MOCK-251116-9358',NULL,'2025-11-16 16:25:00','2025-11-16 17:39:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(61,'TK-MOCK-251106-4889',NULL,'2025-11-06 11:03:00','2025-11-06 13:12:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(62,'TK-MOCK-251126-6912',NULL,'2025-11-26 08:36:00','2025-11-26 11:08:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(63,'TK-MOCK-251220-4057',NULL,'2025-12-20 12:20:00','2025-12-20 15:18:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(64,'TK-MOCK-251203-5185',NULL,'2025-12-03 14:54:00','2025-12-03 17:01:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(65,'TK-MOCK-251211-1794',NULL,'2025-12-11 15:40:00','2025-12-11 16:38:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(66,'TK-MOCK-251204-9724',NULL,'2025-12-04 16:43:00','2025-12-04 19:35:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(67,'TK-MOCK-251224-1157',NULL,'2025-12-24 15:30:00','2025-12-24 18:13:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(68,'TK-MOCK-260126-1258',NULL,'2026-01-26 14:26:00','2026-01-26 14:53:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(69,'TK-MOCK-260109-7456',NULL,'2026-01-09 13:36:00','2026-01-09 16:08:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(70,'TK-MOCK-260102-4921',NULL,'2026-01-02 08:43:00','2026-01-02 09:16:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(71,'TK-MOCK-260206-1062',NULL,'2026-02-06 09:58:00','2026-02-06 11:11:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(72,'TK-MOCK-260222-9345',NULL,'2026-02-22 15:13:00','2026-02-22 17:00:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(73,'TK-MOCK-260220-8452',NULL,'2026-02-20 16:20:00','2026-02-20 16:47:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(74,'TK-MOCK-260327-9621',NULL,'2026-03-27 14:58:00','2026-03-27 17:42:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(75,'TK-MOCK-260317-9812',NULL,'2026-03-17 10:58:00','2026-03-17 12:40:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(76,'TK-MOCK-260302-5663',NULL,'2026-03-02 10:54:00','2026-03-02 12:34:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(77,'TK-MOCK-260422-7049',NULL,'2026-04-22 07:59:00','2026-04-22 08:52:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(78,'TK-MOCK-260415-4536',NULL,'2026-04-15 14:33:00','2026-04-15 17:15:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(79,'TK-MOCK-260410-9674',NULL,'2026-04-10 08:45:00','2026-04-10 10:49:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(80,'TK-MOCK-260413-3573',NULL,'2026-04-13 10:36:00','2026-04-13 13:03:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(81,'TK-MOCK-260408-6776',NULL,'2026-04-08 14:54:00','2026-04-08 15:40:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(82,'TK-MOCK-260421-7685',NULL,'2026-04-21 14:48:00','2026-04-21 16:05:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(83,'TK-MOCK-260511-9559',NULL,'2026-05-11 15:43:00','2026-05-11 18:15:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(84,'TK-MOCK-260506-1344',NULL,'2026-05-06 13:23:00','2026-05-06 14:44:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(85,'TK-MOCK-260527-8565',NULL,'2026-05-27 13:34:00','2026-05-27 14:26:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(86,'TK-MOCK-260515-6703',NULL,'2026-05-15 09:58:00','2026-05-15 10:19:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(87,'TK-MOCK-260522-6680',NULL,'2026-05-22 17:11:00','2026-05-22 18:08:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(88,'TK-MOCK-260512-7230',NULL,'2026-05-12 15:24:00','2026-05-12 16:04:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(89,'TK-MOCK-260611-3815',NULL,'2026-06-11 17:24:00','2026-06-11 18:35:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(90,'TK-MOCK-260611-8971',NULL,'2026-06-11 12:38:00','2026-06-11 13:09:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(91,'TK-MOCK-260611-6785',NULL,'2026-06-11 12:30:00','2026-06-11 13:44:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(92,'TK-MOCK-260601-2614',NULL,'2026-06-01 09:39:00','2026-06-01 12:36:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(93,'TK-MOCK-260619-6716',NULL,'2026-06-19 10:14:00','2026-06-19 12:39:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(94,'TK-MOCK-260723-2868',NULL,'2026-07-23 08:43:00','2026-07-23 10:19:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(95,'TK-MOCK-260725-5584',NULL,'2026-07-25 12:49:00','2026-07-25 13:53:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(96,'TK-MOCK-260718-6747',NULL,'2026-07-18 10:39:00','2026-07-18 13:34:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(97,'TK-MOCK-260721-8639',NULL,'2026-07-21 12:41:00','2026-07-21 15:27:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(98,'TK-MOCK-260727-4838',NULL,'2026-07-27 15:16:00','2026-07-27 17:15:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(99,'TK-MOCK-260707-3469',NULL,'2026-07-07 09:04:00','2026-07-07 10:23:00',NULL,NULL,'Fixed mock issue',NULL,NULL,'2026-07-15 20:17:47'),(100,'TK-WEB-260716-165316','admin','2026-07-16 17:00:00','2026-07-16 17:05:00','Mechanical','Testing','Tested takeover','None','None','2026-07-16 15:03:56'),(101,'TK-WCC-260714-005','admin','2026-07-16 18:09:00','2026-07-16 18:09:00','Electrical','Belt wear and tear over time, causing friction reduction.','55','None','None','2026-07-16 15:09:33'),(102,'TK-WCC-260714-005','admin','2026-07-16 17:09:55','2026-07-16 17:09:55','Other','On Hold','⏸️ PLACED ON HOLD\nReason: Other\nExplanation: 11','None','None','2026-07-16 15:09:55'),(103,'TK-WCC-260714-005','admin','2026-07-16 18:12:00','2026-07-16 18:12:00','Electrical','555','5','None','Jane Smith','2026-07-16 15:12:24'),(104,'TK-WCC-260714-005','admin','2026-07-16 17:13:01','2026-07-16 17:13:01','Other','On Hold','⏸️ PLACED ON HOLD\nReason: Waiting for Parts\nExplanation: 55','None','None','2026-07-16 15:13:01'),(105,'TK-WCC-260714-005','admin','2026-07-16 18:13:00','2026-07-16 18:13:00','Pneumatic/Hydraulic','123','55','None','John Doe','2026-07-16 15:13:15'),(106,'TK-WEB-260716-171245','admin','2026-07-16 18:14:00','2026-07-16 18:14:00','Electrical','Test root cause: loose wire connection.','77','None','None','2026-07-16 15:14:16'),(107,'TK-WEB-260716-171245','admin','2026-07-16 17:14:23','2026-07-16 17:14:23','Other','On Hold','⏸️ PLACED ON HOLD\nReason: Waiting for Parts\nExplanation: 77','None','None','2026-07-16 15:14:23'),(108,'TK-WEB-260716-171245','admin','2026-07-16 18:14:00','2026-07-16 18:14:00','Pneumatic/Hydraulic','77','77','None','John Doe','2026-07-16 15:14:35'),(109,'TK-WEB-260716-172158','admin','2026-07-16 18:22:00','2026-07-16 18:22:00','Mechanical','3','3','None','None','2026-07-16 15:22:06'),(110,'TK-WEB-260716-172158','admin','2026-07-16 17:22:10','2026-07-16 17:22:10','Other','On Hold','⏸️ PLACED ON HOLD\nReason: Waiting for Parts\nExplanation: 3','None','None','2026-07-16 15:22:10'),(111,'TK-WEB-260716-172158','admin','2026-07-16 18:22:00','2026-07-16 18:22:00','Electrical','3','3','None','None','2026-07-16 15:22:17'),(112,'TK-WEB-260716-173607','admin','2026-07-16 18:37:00','2026-07-16 18:37:00','Pneumatic/Hydraulic','Belt wear and tear over time, causing friction reduction.','2','None','Jane Smith','2026-07-16 15:37:15'),(113,'TK-WEB-260716-174147','admin','2026-07-16 18:41:00','2026-07-16 18:41:00','Software/Controls','test1','test1','None','None','2026-07-16 15:41:57'),(114,'TK-WEB-260716-174216','admin','2026-07-16 18:42:00','2026-07-16 18:42:00','Software/Controls','test2','test2','None','John Doe','2026-07-16 15:42:31'),(115,'TK-WEB-260716-174216','admin','2026-07-16 18:42:00','2026-07-16 18:42:00','Electrical','test2','test2','None','None','2026-07-16 15:42:51'),(116,'TK-WEB-260716-174320','admin','2026-07-16 18:43:00','2026-07-16 18:43:00','Software/Controls','test3','test3','None','John Doe','2026-07-16 15:43:31'),(117,'TK-WEB-260716-174400','admin','2026-07-16 18:44:00','2026-07-16 18:44:00','Software/Controls','4','4','None','None','2026-07-16 15:44:07'),(118,'TK-WEB-260716-174320','admin','2026-07-16 17:44:23','2026-07-16 17:44:23','Other','On Hold','⏸️ PLACED ON HOLD\nReason: Waiting for Parts\nExplanation: 4','None','None','2026-07-16 15:44:23'),(119,'TK-WEB-260716-174320','admin','2026-07-16 18:44:00','2026-07-16 18:44:00','Pneumatic/Hydraulic','4','4','None','Jane Smith','2026-07-16 15:44:31'),(120,'TK-WEB-260716-174320','admin','2026-07-16 18:44:00','2026-07-16 18:44:00','Electrical','4','4','None','None','2026-07-16 15:44:44'),(121,'TK-WEB-260716-174525','admin','2026-07-16 18:45:00','2026-07-16 18:45:00','Tooling/Fixture','2','2','None','None','2026-07-16 15:45:43'),(122,'TK-WEB-260716-174516','admin','2026-07-16 18:46:00','2026-07-16 18:46:00','Software/Controls','12','12','None','Jane Smith','2026-07-16 15:46:12'),(123,'TK-WEB-260716-174516','admin','2026-07-16 18:46:00','2026-07-16 18:46:00','Pneumatic/Hydraulic','12','12','None','None','2026-07-16 15:46:20'),(124,'TK-QR-260716-174817','admin','2026-07-16 17:48:17','2026-07-16 17:48:17','Quick Fix','Minor Adjustment','55','None','None','2026-07-16 15:48:17'),(125,'TK-WEB-260716-174836','admin','2026-07-16 18:49:00','2026-07-16 18:49:00','Other','4','Major error, for testing purposes.','None','None','2026-07-16 15:49:19'),(126,'TK-WEB-260716-175011','admin','2026-07-16 18:50:00','2026-07-16 18:50:00','Pneumatic/Hydraulic','2','2','None','None','2026-07-16 15:50:26'),(127,'TK-WEB-260717-215431','admin','2026-07-17 22:54:00','2026-07-17 22:54:00','Mechanical','Verification test root cause','Verified new UI end-to-end; no physical intervention.','None','None','2026-07-17 19:54:59');
/*!40000 ALTER TABLE `ticket_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `active_tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_attachments`
--

LOCK TABLES `ticket_attachments` WRITE;
/*!40000 ALTER TABLE `ticket_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_comments`
--

DROP TABLE IF EXISTS `ticket_comments`;
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_comments`
--

LOCK TABLES `ticket_comments` WRITE;
/*!40000 ALTER TABLE `ticket_comments` DISABLE KEYS */;
INSERT INTO `ticket_comments` VALUES (1,'TK-WEB-260716-173607','admin','22','2026-07-16 15:36:31'),(2,'TK-WEB-260716-173607','admin','2222','2026-07-16 15:36:37'),(3,'TK-WEB-260716-173607','admin','55','2026-07-16 15:41:10'),(4,'TK-WEB-260716-173607','admin','78','2026-07-16 15:41:11'),(5,'TK-WEB-260716-174525','admin','comment test 2','2026-07-16 15:45:39'),(6,'TK-WEB-260716-174516','admin','12','2026-07-16 15:45:59'),(7,'TK-WEB-260716-174516','admin','12','2026-07-16 15:46:00'),(8,'TK-WEB-260716-174516','admin','12','2026-07-16 15:46:01'),(9,'TK-WEB-260716-174516','admin','12','2026-07-16 15:46:04'),(10,'TK-WEB-260716-174836','admin','MAJOR MAJOR','2026-07-16 15:48:43'),(11,'TK-WEB-260716-174836','admin','MAJOR MAJOR MAJOR','2026-07-16 15:48:46'),(12,'TK-WEB-260716-174836','admin','Comments archived with technical audit trail.','2026-07-16 15:48:58'),(13,'TK-WEB-260716-174836','admin','nice job Gemini!','2026-07-16 15:49:02'),(14,'TK-WEB-260716-175011','admin','COMMENT 1','2026-07-16 15:50:32'),(15,'TK-WEB-260716-175011','admin','COMMENT2','2026-07-16 15:50:34'),(16,'TK-WEB-260716-175011','admin','COMMENT3','2026-07-16 15:50:38'),(17,'TK-WEB-260716-175011','admin','1','2026-07-16 15:52:26'),(18,'TK-WEB-260716-175011','admin','1','2026-07-16 15:52:26'),(19,'TK-WEB-260716-175011','admin','1','2026-07-16 15:52:27'),(20,'TK-WEB-260716-175011','admin','2','2026-07-16 15:52:28');
/*!40000 ALTER TABLE `ticket_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_parts_consumed`
--

DROP TABLE IF EXISTS `ticket_parts_consumed`;
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

--
-- Dumping data for table `ticket_parts_consumed`
--

LOCK TABLES `ticket_parts_consumed` WRITE;
/*!40000 ALTER TABLE `ticket_parts_consumed` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_parts_consumed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_registration_config`
--

DROP TABLE IF EXISTS `user_registration_config`;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_registration_config`
--

LOCK TABLES `user_registration_config` WRITE;
/*!40000 ALTER TABLE `user_registration_config` DISABLE KEYS */;
INSERT INTO `user_registration_config` VALUES (1,'full_name',1,1,'Full Name',1,'2026-07-12 22:57:39','2026-07-12 22:57:39'),(2,'email',1,1,'Email',2,'2026-07-12 22:57:39','2026-07-12 22:57:39'),(3,'phone',0,0,'Phone',3,'2026-07-12 22:57:39','2026-07-12 23:00:48'),(4,'department',0,0,'Department',4,'2026-07-12 22:57:39','2026-07-12 23:00:48'),(5,'workshop_id',1,0,'Location / Workshop',5,'2026-07-12 22:57:39','2026-07-12 22:57:39'),(6,'certifications',0,0,'Certifications / Skills',6,'2026-07-12 22:57:39','2026-07-12 23:00:48'),(7,'notes',0,0,'Notes',7,'2026-07-12 22:57:39','2026-07-12 23:00:48'),(8,'status',1,1,'Status',8,'2026-07-12 22:57:39','2026-07-12 22:57:39');
/*!40000 ALTER TABLE `user_registration_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_skills`
--

DROP TABLE IF EXISTS `user_skills`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_skills`
--

LOCK TABLES `user_skills` WRITE;
/*!40000 ALTER TABLE `user_skills` DISABLE KEYS */;
INSERT INTO `user_skills` VALUES (2,7,'Master electrician',NULL,'2026-07-14 22:11:22'),(3,7,'Master Mechanic',NULL,'2026-07-14 22:13:13');
/*!40000 ALTER TABLE `user_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
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
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `badge_number` (`badge_number`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_status` (`status`),
  KEY `idx_workshop` (`workshop_id`),
  KEY `idx_last_login` (`last_login`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$i5PWhqCS/PcAvPfu5YCVLebfiC.zNm4V5Z/aJUWfQNX5suK1krkkm',4,NULL,NULL,'2026-07-03 18:40:43',NULL,15,'{\"dark\":{\"--text-accent\":\"#38bdf8\",\"--sidebar-bg\":\"#1e293b\",\"--panel-bg\":\"linear-gradient(135deg, rgba(30, 41, 59, 0.75), rgba(15, 23, 42, 0.95))\",\"--bg-gradient\":\"linear-gradient(135deg, #0f172a, #1e293b, #0f172a, #020617)\",\"--sidebar-text\":\"#e2e8f0\",\"--text-primary\":\"#f8fafc\"},\"light\":{\"--text-accent\":\"#1e3a8a\",\"--sidebar-bg\":\"#f1f5f9\",\"--panel-bg\":\"linear-gradient(135deg, rgba(255,255,255,0.7), rgba(241,245,249,0.5))\",\"--bg-gradient\":\"linear-gradient(135deg, #e0e7ff, #BDC2FF, #f1f5f9, #e0e7ff)\",\"--sidebar-text\":\"#1e293b\",\"--text-primary\":\"#0f172a\"}}','admin@example.com','Admin',NULL,NULL,'active','2026-07-18 17:52:28',NULL,NULL,NULL,'2026-07-18 14:52:28',0,'IB-01001'),(2,'supervisor1','$2y$10$D.ZaCI0M6mHJTTq.MTagmezYsg669TM43OqKD04xH6zwzjQ45DdJC',3,NULL,NULL,'2026-07-03 18:40:43',NULL,NULL,NULL,'supervisor1@example.com','Supervisor1',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-12 22:57:57',0,'IB-01002'),(3,'tech1','$2y$10$D.ZaCI0M6mHJTTq.MTagmezYsg669TM43OqKD04xH6zwzjQ45DdJC',2,NULL,NULL,'2026-07-03 18:40:43',NULL,NULL,NULL,'tech1@example.com','Tech1',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-12 22:57:57',0,'IB-01003'),(4,'operator1','$2y$10$D.ZaCI0M6mHJTTq.MTagmezYsg669TM43OqKD04xH6zwzjQ45DdJC',1,NULL,NULL,'2026-07-03 18:40:43',NULL,NULL,NULL,'operator1@example.com','Operator1',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-12 22:57:57',0,'IB-01004'),(5,'test_operator','$2y$10$KPUHCxXhM3gCvKM2/zbyWeuzZmG5iZs3DtRGYuO1HubxJlP13lY6u',1,'{\"takeover_tickets\":true,\"closeout_tickets\":true,\"view_statistics\":true,\"manage_equipment\":true,\"view_inventory\":true,\"manage_inventory\":true,\"manage_vendors\":true,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"view_work_orders\":true,\"manage_work_orders\":true}',NULL,'2026-07-10 13:55:32',NULL,NULL,NULL,'test_operator@example.com','Test_operator',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-12 22:57:57',0,'IB-01005'),(7,'test_sup','$2y$10$nGR.yt/I4J1utFStWnok4ucAWK9m9pLIpEtU4ztqHoJhCY1z5OFxu',3,'{\"view_tickets\":false,\"create_tickets\":false,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":false,\"view_statistics\":false,\"view_equipment\":false,\"manage_equipment\":false,\"view_inventory\":false,\"view_vendors\":false,\"view_purchase_requests\":false,\"create_purchase_requests\":false,\"view_work_orders\":false,\"manage_work_orders\":false}',NULL,'2026-07-10 13:57:58',NULL,NULL,NULL,'test_sup@example.com','Test_sup',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-12 22:57:57',0,'IB-01007'),(8,'tech2','$2y$10$FlXGxHItQvWsm8PQDBqyUOKx3dDd4O6JIqDweV3zsUuqYlmBCMoj.',2,NULL,NULL,'2026-07-12 22:48:24',NULL,NULL,NULL,'tech2@example.com','Tech Two','555-0102','Maintenance','active',NULL,'Certified welder',1,NULL,'2026-07-12 22:57:57',1,'IB-01008'),(9,'sup2','$2y$10$HAudw1t1CZv2.T3KoEhGoOqziHQBRXpRkpgqNSZgTMA0kdmleahQ.',3,NULL,NULL,'2026-07-12 22:48:24',NULL,NULL,NULL,'sup2@example.com','Supervisor Two','555-0103','Operations','active',NULL,'',2,NULL,'2026-07-12 22:57:57',1,'IB-01009'),(10,'op2','$2y$10$YG5ojTD/KO7hyHcEtcv14.pm3Z7lrvRZzXOr6gUEY6jes5wQE1/9q',1,NULL,NULL,'2026-07-12 22:48:24',NULL,NULL,NULL,'op2@example.com','Operator Two','555-0104','Production','active',NULL,'',NULL,NULL,'2026-07-12 22:57:57',1,'IB-01010'),(13,'test_op','$2y$10$/g0bsgLu3cNfTjadH6hwP.lM7JkUmyg1SS7Qi/hLPHqJWoUhZDO5O',1,NULL,NULL,'2026-07-13 04:11:57',NULL,NULL,NULL,NULL,'Test Operator',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-13 04:11:57',0,'IB-TEST01'),(14,'test_admin','$2y$10$/g0bsgLu3cNfTjadH6hwP.lM7JkUmyg1SS7Qi/hLPHqJWoUhZDO5O',4,NULL,NULL,'2026-07-13 04:11:57',NULL,NULL,NULL,NULL,'Test Admin',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-13 04:11:57',0,'IB-TEST99'),(16,'test_custom','$2y$10$GhFTAnJ3lIK.TwbYTs94IOVBBsI5KU8MHpGbnDCTpYFRzEbF/iB2m',5,NULL,NULL,'2026-07-13 04:13:00',NULL,NULL,NULL,NULL,'Test Custom',NULL,NULL,'active',NULL,NULL,NULL,NULL,'2026-07-13 04:13:00',0,'IB-CUST01');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uuid_rules`
--

DROP TABLE IF EXISTS `uuid_rules`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uuid_rules`
--

LOCK TABLES `uuid_rules` WRITE;
/*!40000 ALTER TABLE `uuid_rules` DISABLE KEYS */;
INSERT INTO `uuid_rules` VALUES (1,'Equipment','Mechanical','MCH-',4,3,0,'ALPHANUMERIC'),(2,'Equipment','Testing','TST-',4,2,0,'ALPHANUMERIC');
/*!40000 ALTER TABLE `uuid_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors_suppliers`
--

DROP TABLE IF EXISTS `vendors_suppliers`;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors_suppliers`
--

LOCK TABLES `vendors_suppliers` WRITE;
/*!40000 ALTER TABLE `vendors_suppliers` DISABLE KEYS */;
INSERT INTO `vendors_suppliers` VALUES (1,'Sample Brand 1','John Doe','john.doe@example.com','555-0101',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(2,'Sample Brand 2','Kevin Doe','kevin.doe@example.com','555-0102',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(3,'Sample Brand 3','Jane Smith','jane.smith@example.com','555-0103',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(4,'Sample Brand 4','Michael Johnson','michael.j@example.com','555-0104',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(5,'Sample Brand 5','Emily Davis','emily.davis@example.com','555-0105',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(6,'Sample Brand 6','Robert Wilson','robert.w@example.com','555-0106',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(7,'Sample Brand 7','Sarah Brown','sarah.b@example.com','555-0107',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(8,'Sample Brand 8','David Miller','david.miller@example.com','555-0108',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(9,'Sample Brand 9','Laura Taylor','laura.t@example.com','555-0109',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(10,'Sample Brand 10','James Anderson','james.a@example.com','555-0110',NULL,NULL,NULL,NULL,NULL,NULL,5.00,'2026-07-03 18:49:48'),(21,'Rockwell Automation',NULL,'automation@rockwell.test',NULL,NULL,'Net 30',NULL,NULL,'Industrial Controls & PLCs',NULL,5.00,'2026-07-14 16:50:53'),(22,'Grainger',NULL,'sales@grainger.test',NULL,NULL,'Net 15',NULL,NULL,'MRO Supplies & Tools',NULL,5.00,'2026-07-14 16:50:53'),(23,'Fastenal',NULL,'b2b@fastenal.test',NULL,NULL,'Net 30',NULL,NULL,'Fasteners & Safety Gear',NULL,5.00,'2026-07-14 16:50:53'),(24,'SMC Corporation',NULL,'orders@smc.test',NULL,NULL,'Net 45',NULL,NULL,'Pneumatics & Actuators',NULL,5.00,'2026-07-14 16:50:53'),(25,'Siemens',NULL,'industrial@siemens.test',NULL,NULL,'Net 60',NULL,NULL,'Drives & Motors',NULL,5.00,'2026-07-14 16:50:53');
/*!40000 ALTER TABLE `vendors_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES (1,'Weekly Conveyor PM','Auto-generated from PM Schedule: \n\nTechnician Notes: reset\nParts actually consumed: Ball Bearing (BRG-50) x40',1,'[\"902\",\"900\"]',NULL,NULL,'Completed','2026-07-14 20:43:07',1,'2026-07-16',NULL,NULL),(2,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset -10.',2,'[]',NULL,3,'Completed','2026-06-29 00:00:00',3,'2026-06-29',NULL,NULL),(3,'Scheduled PM: CNC Machine Alpha','Auto-generated test WO for offset -10.',5,'[]',NULL,2,'Completed','2026-06-30 00:00:00',2,'2026-06-29',NULL,NULL),(4,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset -10.',4,'[]',NULL,3,'Completed','2026-06-29 00:00:00',3,'2026-06-29',NULL,NULL),(5,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset -5.\n\nTechnician Notes: 5',2,'[]',NULL,2,'Completed','2026-07-09 22:30:40',1,'2026-07-07',NULL,NULL),(6,'Scheduled PM: Packaging Unit 4','Auto-generated test WO for offset -5.\n\nTechnician Notes: 5\n\nTechnician Notes: 5\n\nTechnician Notes: 3',3,'[]',NULL,3,'Completed','2026-07-09 12:06:40',1,'2026-07-04',NULL,NULL),(7,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset -5.',2,'[]',NULL,2,'Completed','2026-07-04 00:00:00',2,'2026-07-04',NULL,NULL),(8,'Scheduled PM: Main Conveyor Belt Alpha','Auto-generated test WO for offset -4.',1,'[]',NULL,3,'Completed','2026-07-06 00:00:00',3,'2026-07-05',NULL,NULL),(9,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset -4.',4,'[]',NULL,2,'Completed','2026-07-06 00:00:00',2,'2026-07-05',NULL,NULL),(10,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset -4.',4,'[]',NULL,3,'Cancelled',NULL,NULL,'2026-07-05',NULL,NULL),(11,'Scheduled PM: Test Machine Alpha-Gamma','Auto-generated test WO for offset -3.',8,'[]',NULL,3,'Cancelled',NULL,NULL,'2026-07-06',NULL,NULL),(12,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset -3.',4,'[]',NULL,3,'Completed','2026-07-06 00:00:00',3,'2026-07-06',NULL,NULL),(13,'Scheduled PM: Test Machine Alpha-Gamma','Auto-generated test WO for offset -3.',8,'[]',NULL,1,'Completed','2026-07-07 00:00:00',1,'2026-07-06',NULL,NULL),(14,'Scheduled PM: Test Machine Alpha-Gamma','Auto-generated test WO for offset -2.',8,'[]',NULL,1,'Completed','2026-07-08 00:00:00',1,'2026-07-07',NULL,NULL),(15,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset -2.',2,'[]',NULL,2,'Completed','2026-07-07 00:00:00',2,'2026-07-07',NULL,NULL),(16,'Scheduled PM: Test Machine Alpha-Gamma','Auto-generated test WO for offset -1.',8,'[]',NULL,2,'Completed','2026-07-09 00:00:00',2,'2026-07-08',NULL,NULL),(17,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset -1.',2,'[]',NULL,3,'Completed','2026-07-09 00:00:00',3,'2026-07-08',NULL,NULL),(18,'Scheduled PM: Test Engine 1','Auto-generated test WO for offset -1.',6,'[]',NULL,1,'Completed','2026-07-08 00:00:00',1,'2026-07-08',NULL,NULL),(19,'Scheduled PM: Robotic Welding Arm BARD','Auto-generated test WO for offset 0.',7,'[]',NULL,3,'Completed','2026-07-09 00:00:00',3,'2026-07-09',NULL,NULL),(20,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset 0.',4,'[]',NULL,1,'Completed','2026-07-10 00:00:00',1,'2026-07-09',NULL,NULL),(21,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 0.',2,'[]',NULL,3,'Completed','2026-07-09 00:00:00',3,'2026-07-09',NULL,NULL),(22,'Scheduled PM: Main Conveyor Belt Alpha','Auto-generated test WO for offset 1.\n\nTechnician Notes: test\nParts actually consumed: 905',1,'[]',NULL,3,'Completed','2026-07-12 20:57:36',1,'2026-07-10',NULL,NULL),(23,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 1.\n\nTechnician Notes: 12\nParts actually consumed: 1',2,'[]',NULL,1,'Completed','2026-07-12 21:00:17',1,'2026-07-10',NULL,NULL),(24,'Scheduled PM: Test Engine 1','Auto-generated test WO for offset 1.\n\nTechnician Notes: 5\nParts actually consumed: Motor Drive (MTR-100) x2, Conveyor Belt (CVB-200) x3',6,'[]',NULL,3,'Completed','2026-07-12 21:24:56',1,'2026-07-10',NULL,NULL),(25,'Scheduled PM: CNC Milling Center','Auto-generated test WO for offset 1.',4,'[]',NULL,2,'Cancelled',NULL,NULL,'2026-07-10',NULL,NULL),(26,'Scheduled PM: Robotic Welding Arm BARD','Auto-generated test WO for offset 2.\n\nTechnician Notes: 55',7,'[]',NULL,1,'Completed','2026-07-14 20:37:15',1,'2026-07-11',NULL,NULL),(27,'Scheduled PM: Test Engine 1','Auto-generated test WO for offset 2.\n\nTechnician Notes: tset\nParts actually consumed: Test Part A (SKU-TEST-A) x1',6,'[]',NULL,2,'Completed','2026-07-14 23:56:24',1,'2026-07-11',NULL,NULL),(28,'Scheduled PM: CNC Machine Alpha','Auto-generated test WO for offset 3.',5,'[]',NULL,2,'Cancelled',NULL,NULL,'2026-07-12',NULL,NULL),(29,'Scheduled PM: Test Machine Alpha-Gamma','Auto-generated test WO for offset 3.',8,'[]',NULL,1,'Cancelled',NULL,NULL,'2026-07-12',NULL,NULL),(30,'Scheduled PM: CNC Machine Alpha','Auto-generated test WO for offset 3.\n\nTechnician Notes: test\nParts actually consumed: Enterprise Bearing (ENT-BR-01) x50, TEST (TEST1) x10',5,'[]',NULL,2,'Completed','2026-07-12 21:09:54',1,'2026-07-12',NULL,NULL),(31,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 5.\n\nTechnician Notes: 5',2,'[]',NULL,2,'Completed','2026-07-09 12:05:53',1,'2026-07-14',NULL,NULL),(32,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 5.\n\nTechnician Notes: 7474\nParts actually consumed: Beta Valve (VAL-002) x1',2,'[]',NULL,1,'Completed','2026-07-15 00:15:08',1,'2026-07-14',NULL,NULL),(33,'Scheduled PM: Packaging Unit 4','Auto-generated test WO for offset 5.\n\nTechnician Notes: Completed PM maintenance, replaced 2 SKU-TEST-A parts.\nParts actually consumed: Test Part A (SKU-TEST-A) x2',3,'[]',NULL,3,'Completed','2026-07-15 18:28:32',1,'2026-07-14',NULL,NULL),(34,'Scheduled PM: Robotic Welding Arm BARD','Auto-generated test WO for offset 7.',7,'[]',NULL,1,'Scheduled',NULL,NULL,'2026-07-16',NULL,NULL),(35,'Scheduled PM: Main Conveyor Belt Alpha','Auto-generated test WO for offset 7.\n\nTechnician Notes: 123123',1,'[]',NULL,1,'Completed','2026-07-14 20:45:33',1,'2026-07-16',NULL,NULL),(36,'Scheduled PM: Test Engine 1','Auto-generated test WO for offset 10.',6,'[]',NULL,2,'Scheduled',NULL,NULL,'2026-07-19',NULL,NULL),(37,'Scheduled PM: CNC Machine Alpha','Auto-generated test WO for offset 10.',5,'[]',NULL,2,'Scheduled',NULL,NULL,'2026-07-19',NULL,NULL),(38,'Scheduled PM: Packaging Unit 4','Auto-generated test WO for offset 14.',3,'[]',NULL,1,'Scheduled',NULL,NULL,'2026-07-23',NULL,NULL),(39,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 14.',2,'[]',NULL,3,'Scheduled',NULL,NULL,'2026-07-23',NULL,NULL),(40,'Scheduled PM: Robotic Welding Arm B','Auto-generated test WO for offset 14.',2,'[]',NULL,3,'Scheduled',NULL,NULL,'2026-07-23',NULL,NULL),(41,'Scheduled PM: Main Conveyor Belt Alpha','Auto-generated test WO for offset 14.\n\nTechnician Notes: 5',1,'[]','[]',2,'Scheduled','2026-07-16 22:47:27',1,'2026-07-23','2026-07-16 22:26:32',NULL),(44,'Test Ad-Hoc Work Order','\n\nTechnician Notes: 5',5,'[]',NULL,1,'Missed','2026-07-09 22:30:53',1,'2026-07-09',NULL,NULL),(47,'1','\n\nTechnician Notes: 25',4,'[]',NULL,NULL,'Cancelled','2026-07-09 22:28:47',1,'2026-08-11',NULL,NULL),(48,'TEST work order','TEST\n\nTechnician Notes: added test part to machine\nParts actually consumed: 905',2,'[]',NULL,2,'Completed','2026-07-12 20:56:32',1,'2026-07-13',NULL,NULL),(49,'123','test',5,'[]',NULL,NULL,'Scheduled',NULL,NULL,'2026-07-16',NULL,NULL),(50,'test2','\n\nTechnician Notes: 3',2,'[]',NULL,NULL,'Completed','2026-07-16 18:22:44',1,'2026-07-15',NULL,NULL),(51,'test','test\n\nTechnician Notes: Done \n\nTechnician Notes: done\n\nTechnician Notes: 123\n\nTechnician Notes: 1234',15,'[]','[{\"task_desc\":\"test\",\"expected_time_mins\":1,\"completed\":false},{\"task_desc\":\"12\",\"expected_time_mins\":12,\"completed\":false}]',1,'Completed','2026-07-16 20:10:42',1,'2026-07-15','2026-07-16 19:50:03',NULL),(52,'test2','test2\n\nTechnician Notes: done\n\nTechnician Notes: 123',15,'[]','[{\"task_desc\":\"test\",\"expected_time_mins\":1,\"completed\":false},{\"task_desc\":\"12\",\"expected_time_mins\":12,\"completed\":false}]',NULL,'Completed','2026-07-16 20:10:56',1,'2026-07-15','2026-07-16 19:52:15',NULL),(53,'33','33\n\nTechnician Notes: e',5,'[]','[{\"task_desc\":\"test\",\"expected_time_mins\":1,\"completed\":false,\"photo_paths\":[\"\\/uploads\\/checklists\\/wo_53_task_0_1784223016_0.png\"]},{\"task_desc\":\"12\",\"expected_time_mins\":12,\"completed\":false,\"photo_paths\":[\"\\/uploads\\/checklists\\/wo_53_task_1_1784223016_0.png\"]}]',1,'Completed','2026-07-16 20:30:16',1,'2026-07-15','2026-07-16 20:11:05',NULL),(54,'88','\n\nTechnician Notes: 5\n\nTechnician Notes: 8\n\nTechnician Notes: 2\n\nTechnician Notes: 3\n\nTechnician Notes: 2',9,'[]','[{\"task_desc\":\"test\",\"expected_time_mins\":1,\"completed\":false},{\"task_desc\":\"12\",\"expected_time_mins\":12,\"completed\":false}]',NULL,'Completed','2026-07-17 00:25:41',1,'2026-07-15','2026-07-16 20:53:48',NULL);
/*!40000 ALTER TABLE `work_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workshops`
--

DROP TABLE IF EXISTS `workshops`;
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

--
-- Dumping data for table `workshops`
--

LOCK TABLES `workshops` WRITE;
/*!40000 ALTER TABLE `workshops` DISABLE KEYS */;
INSERT INTO `workshops` VALUES (1,'Test Workshop A','Zone 1','Active'),(2,'Test Workshop A','Zone 2','Active');
/*!40000 ALTER TABLE `workshops` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-18 21:08:52
