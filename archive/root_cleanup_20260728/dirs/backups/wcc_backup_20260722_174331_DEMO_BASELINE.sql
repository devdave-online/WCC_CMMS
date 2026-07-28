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
INSERT INTO `active_tickets` VALUES ('TK-251030-014',23,'2025-10-30','15:32:50','Priya Nair','Sara Lindqvist','Thermoformer seal temperature unstable','normal','CLOSED','Sara Lindqvist','2025-10-30 13:32:50',NULL),('TK-251031-056',5,'2025-10-31','20:18:25','Elise Moreau','Taro Yamamoto','Servo press position error at bottom dead ctr','normal','CLOSED','Taro Yamamoto','2025-10-31 18:18:25',NULL),('TK-251102-060',9,'2025-11-02','09:16:13','Marc Dubois','Katerina Novak','Palletiser stops, pattern incomplete','normal','CLOSED','Katerina Novak','2025-11-02 07:16:13',NULL),('TK-251105-106',19,'2025-11-05','14:35:07','Elise Moreau','Sara Lindqvist','Chiller low delta-T, machines running warm','high','CLOSED','Sara Lindqvist','2025-11-05 12:35:07',NULL),('TK-251108-062',23,'2025-11-08','15:40:48','Rui Silva','Katerina Novak','Chiller low delta-T, machines running warm','normal','CLOSED','Katerina Novak','2025-11-08 13:40:48',NULL),('TK-251109-078',15,'2025-11-09','21:41:47','Marc Dubois','Sara Lindqvist','Servo press position error at bottom dead ctr','critical','CLOSED','Sara Lindqvist','2025-11-09 19:41:47',NULL),('TK-251112-024',21,'2025-11-12','08:58:11','Elise Moreau','Sara Lindqvist','Coolant pressure low, tool wear increasing','normal','CLOSED','Sara Lindqvist','2025-11-12 06:58:11',NULL),('TK-251112-053',8,'2025-11-12','16:27:02','Marc Dubois','Sara Lindqvist','Press brake not holding pressure','normal','CLOSED','Sara Lindqvist','2025-11-12 14:27:02',NULL),('TK-251113-016',13,'2025-11-13','11:41:09','Priya Nair','Sara Lindqvist','Palletiser stops, pattern incomplete','high','CLOSED','Sara Lindqvist','2025-11-13 09:41:09',NULL),('TK-251113-070',7,'2025-11-13','07:06:07','Elise Moreau','Katerina Novak','Tool changer stalls mid-swap','low','CLOSED','Katerina Novak','2025-11-13 05:06:07',NULL),('TK-251118-041',20,'2025-11-18','12:57:51','Elise Moreau','Katerina Novak','Crane hoist limit switch intermittent','normal','CLOSED','Katerina Novak','2025-11-18 10:57:51',NULL),('TK-251121-079',22,'2025-11-21','18:26:23','Priya Nair','Katerina Novak','Vision rig rejecting at 12 percent','low','CLOSED','Katerina Novak','2025-11-21 16:26:23',NULL),('TK-251122-031',22,'2025-11-22','16:30:56','Rui Silva','Jide Okafor','Press brake not holding pressure','low','CLOSED','Jide Okafor','2025-11-22 14:30:56',NULL),('TK-251124-095',14,'2025-11-24','10:10:39','Priya Nair','Taro Yamamoto','Weld quality NOK, porosity in seam','high','CLOSED','Taro Yamamoto','2025-11-24 08:10:39',NULL),('TK-251128-122',11,'2025-11-28','12:01:19','Rui Silva','Sara Lindqvist','Servo press position error at bottom dead ctr','low','CLOSED','Sara Lindqvist','2025-11-28 10:01:19',NULL),('TK-251130-063',6,'2025-11-30','16:52:52','Rui Silva','Taro Yamamoto','Crane hoist limit switch intermittent','low','CLOSED','Taro Yamamoto','2025-11-30 14:52:52',NULL),('TK-251201-008',5,'2025-12-01','16:58:57','Rui Silva','Jide Okafor','Laser cutter head crash on nest start','normal','PENDING',NULL,'2025-12-01 14:58:57',NULL),('TK-251203-126',15,'2025-12-03','07:01:11','Priya Nair','Taro Yamamoto','Palletiser stops, pattern incomplete','high','CLOSED','Taro Yamamoto','2025-12-03 05:01:11',NULL),('TK-251207-101',8,'2025-12-07','11:58:13','Rui Silva','Jide Okafor','Vision rig rejecting at 12 percent','critical','CLOSED','Jide Okafor','2025-12-07 09:58:13',NULL),('TK-251210-004',1,'2025-12-10','14:14:51','Marc Dubois',NULL,'Tool changer stalls mid-swap','high','OPEN',NULL,'2025-12-10 12:14:51',NULL),('TK-251214-109',16,'2025-12-14','06:03:21','Priya Nair','Jide Okafor','Hydraulic power pack noisy, foaming oil','normal','CLOSED','Jide Okafor','2025-12-14 04:03:21',NULL),('TK-251217-038',23,'2025-12-17','14:09:10','Elise Moreau','Sara Lindqvist','Palletiser stops, pattern incomplete','normal','CLOSED','Sara Lindqvist','2025-12-17 12:09:10',NULL),('TK-251220-033',12,'2025-12-20','17:52:47','Rui Silva','Taro Yamamoto','Nutrunner torque out of window','high','CLOSED','Taro Yamamoto','2025-12-20 15:52:47',NULL),('TK-251225-040',13,'2025-12-25','14:21:39','Rui Silva','Taro Yamamoto','Chiller low delta-T, machines running warm','critical','CLOSED','Taro Yamamoto','2025-12-25 12:21:39',NULL),('TK-251226-074',11,'2025-12-26','09:17:44','Marc Dubois','Katerina Novak','Laser cutter head crash on nest start','high','CLOSED','Katerina Novak','2025-12-26 07:17:44',NULL),('TK-251228-100',1,'2025-12-28','11:28:46','Priya Nair','Taro Yamamoto','Servo press position error at bottom dead ctr','high','CLOSED','Taro Yamamoto','2025-12-28 09:28:46',NULL),('TK-251228-117',24,'2025-12-28','11:15:41','Rui Silva','Jide Okafor','Weld quality NOK, porosity in seam','critical','CLOSED','Jide Okafor','2025-12-28 09:15:41',NULL),('TK-260103-013',16,'2026-01-03','09:06:58','Rui Silva','Taro Yamamoto','Vision rig rejecting at 12 percent','low','CLOSED','Taro Yamamoto','2026-01-03 07:06:58',NULL),('TK-260103-057',12,'2026-01-03','14:58:43','Rui Silva','Sara Lindqvist','Vision rig rejecting at 12 percent','critical','CLOSED','Sara Lindqvist','2026-01-03 12:58:43',NULL),('TK-260105-047',14,'2026-01-05','06:04:19','Marc Dubois','Taro Yamamoto','Axis servo fault F-0031 on rapid move','normal','CLOSED','Taro Yamamoto','2026-01-05 04:04:19',NULL),('TK-260105-087',6,'2026-01-05','07:47:23','Rui Silva','Taro Yamamoto','Hydraulic power pack noisy, foaming oil','low','CLOSED','Taro Yamamoto','2026-01-05 05:47:23',NULL),('TK-260108-036',9,'2026-01-08','19:51:53','Marc Dubois','Jide Okafor','Thermoformer seal temperature unstable','low','CLOSED','Jide Okafor','2026-01-08 17:51:53',NULL),('TK-260109-113',20,'2026-01-09','21:18:06','Rui Silva','Jide Okafor','Axis servo fault F-0031 on rapid move','low','CLOSED','Jide Okafor','2026-01-09 19:18:06',NULL),('TK-260112-005',8,'2026-01-12','19:58:34','Elise Moreau','Sara Lindqvist','Conveyor belt tracking off to one side','low','PENDING',NULL,'2026-01-12 17:58:34',NULL),('TK-260122-102',15,'2026-01-22','09:59:26','Elise Moreau','Taro Yamamoto','Thermoformer seal temperature unstable','critical','CLOSED','Taro Yamamoto','2026-01-22 07:59:26',NULL),('TK-260125-089',20,'2026-01-25','17:55:40','Priya Nair','Jide Okafor','Spindle overheat alarm on cycle start','critical','CLOSED','Jide Okafor','2026-01-25 15:55:40',NULL),('TK-260128-002',11,'2026-01-28','08:17:59','Elise Moreau',NULL,'Coolant pressure low, tool wear increasing','low','OPEN',NULL,'2026-01-28 06:17:59',NULL),('TK-260201-082',19,'2026-02-01','18:05:55','Rui Silva','Taro Yamamoto','Palletiser stops, pattern incomplete','normal','CLOSED','Taro Yamamoto','2026-02-01 16:05:55',NULL),('TK-260208-066',3,'2026-02-08','17:13:39','Elise Moreau','Katerina Novak','Guard door interlock will not reset','normal','CLOSED','Katerina Novak','2026-02-08 15:13:39',NULL),('TK-260208-099',18,'2026-02-08','14:10:11','Priya Nair','Taro Yamamoto','Nutrunner torque out of window','normal','CLOSED','Taro Yamamoto','2026-02-08 12:10:11',NULL),('TK-260217-125',8,'2026-02-17','06:58:40','Priya Nair','Taro Yamamoto','Coder printing faint, missing characters','low','CLOSED','Taro Yamamoto','2026-02-17 04:58:40',NULL),('TK-260218-067',10,'2026-02-18','12:55:32','Marc Dubois','Sara Lindqvist','Spindle overheat alarm on cycle start','normal','CLOSED','Sara Lindqvist','2026-02-18 10:55:32',NULL),('TK-260219-115',10,'2026-02-19','11:33:31','Rui Silva','Katerina Novak','Conveyor belt tracking off to one side','critical','CLOSED','Katerina Novak','2026-02-19 09:33:31',NULL),('TK-260223-083',2,'2026-02-23','16:28:38','Marc Dubois','Jide Okafor','Compressor tripping on high discharge temp','critical','CLOSED','Jide Okafor','2026-02-23 14:28:38',NULL),('TK-260227-049',4,'2026-02-27','12:02:46','Elise Moreau','Sara Lindqvist','Conveyor belt tracking off to one side','low','CLOSED','Sara Lindqvist','2026-02-27 10:02:46',NULL),('TK-260307-052',1,'2026-03-07','09:49:29','Priya Nair','Jide Okafor','Laser cutter head crash on nest start','normal','CLOSED','Jide Okafor','2026-03-07 07:49:29',NULL),('TK-260307-077',8,'2026-03-07','07:15:20','Elise Moreau','Katerina Novak','Nutrunner torque out of window','normal','CLOSED','Katerina Novak','2026-03-07 05:15:20',NULL),('TK-260308-006',15,'2026-03-08','09:12:43','Rui Silva','Sara Lindqvist','Robot in fault, brake release error','normal','PENDING',NULL,'2026-03-08 07:12:43',NULL),('TK-260308-021',24,'2026-03-08','18:45:33','Elise Moreau','Katerina Novak','Hydraulic power pack noisy, foaming oil','normal','CLOSED','Katerina Novak','2026-03-08 16:45:33',NULL),('TK-260310-091',10,'2026-03-10','09:30:29','Marc Dubois','Taro Yamamoto','Axis servo fault F-0031 on rapid move','critical','CLOSED','Taro Yamamoto','2026-03-10 07:30:29',NULL),('TK-260311-107',2,'2026-03-11','18:10:43','Priya Nair','Katerina Novak','Crane hoist limit switch intermittent','critical','CLOSED','Katerina Novak','2026-03-11 16:10:43',NULL),('TK-260312-058',19,'2026-03-12','10:43:44','Priya Nair','Katerina Novak','Thermoformer seal temperature unstable','high','CLOSED','Katerina Novak','2026-03-12 08:43:44',NULL),('TK-260313-081',12,'2026-03-13','16:07:26','Priya Nair','Taro Yamamoto','Coder printing faint, missing characters','normal','CLOSED','Taro Yamamoto','2026-03-13 14:07:26',NULL),('TK-260315-010',19,'2026-03-15','15:21:53','Rui Silva','Taro Yamamoto','Leak test bench failing good parts','normal','CLOSED','Taro Yamamoto','2026-03-15 13:21:53',NULL),('TK-260319-045',24,'2026-03-19','09:32:36','Rui Silva','Katerina Novak','Spindle overheat alarm on cycle start','normal','CLOSED','Katerina Novak','2026-03-19 07:32:36',NULL),('TK-260323-044',17,'2026-03-23','10:00:19','Priya Nair','Katerina Novak','Guard door interlock will not reset','low','CLOSED','Katerina Novak','2026-03-23 08:00:19',NULL),('TK-260324-076',1,'2026-03-24','21:16:05','Marc Dubois','Katerina Novak','Leak test bench failing good parts','low','CLOSED','Katerina Novak','2026-03-24 19:16:05',NULL),('TK-260326-123',18,'2026-03-26','11:41:06','Rui Silva','Katerina Novak','Vision rig rejecting at 12 percent','normal','CLOSED','Katerina Novak','2026-03-26 09:41:06',NULL),('TK-260330-059',2,'2026-03-30','20:12:03','Marc Dubois','Sara Lindqvist','Coder printing faint, missing characters','high','CLOSED','Sara Lindqvist','2026-03-30 17:12:03',NULL),('TK-260403-121',4,'2026-04-03','21:09:47','Rui Silva','Jide Okafor','Nutrunner torque out of window','normal','CLOSED','Jide Okafor','2026-04-03 18:09:47',NULL),('TK-260403-124',1,'2026-04-03','14:02:08','Elise Moreau','Jide Okafor','Thermoformer seal temperature unstable','normal','CLOSED','Jide Okafor','2026-04-03 11:02:08',NULL),('TK-260404-110',23,'2026-04-04','11:52:52','Priya Nair','Taro Yamamoto','Guard door interlock will not reset','high','CLOSED','Taro Yamamoto','2026-04-04 08:52:52',NULL),('TK-260405-023',14,'2026-04-05','09:31:19','Rui Silva','Katerina Novak','Spindle overheat alarm on cycle start','normal','CLOSED','Katerina Novak','2026-04-05 06:31:19',NULL),('TK-260405-116',17,'2026-04-05','11:12:20','Marc Dubois','Jide Okafor','Robot in fault, brake release error','critical','CLOSED','Jide Okafor','2026-04-05 08:12:20',NULL),('TK-260406-009',12,'2026-04-06','07:28:12','Marc Dubois','Taro Yamamoto','Press brake not holding pressure','normal','PENDING',NULL,'2026-04-06 04:28:12',NULL),('TK-260408-017',20,'2026-04-08','15:38:12','Elise Moreau','Sara Lindqvist','Compressor tripping on high discharge temp','low','CLOSED','Sara Lindqvist','2026-04-08 12:38:12',NULL),('TK-260408-055',22,'2026-04-08','11:23:29','Marc Dubois','Sara Lindqvist','Nutrunner torque out of window','critical','CLOSED','Sara Lindqvist','2026-04-08 08:23:29',NULL),('TK-260408-069',24,'2026-04-08','15:21:11','Priya Nair','Katerina Novak','Axis servo fault F-0031 on rapid move','high','CLOSED','Katerina Novak','2026-04-08 12:21:11',NULL),('TK-260409-048',21,'2026-04-09','07:36:16','Priya Nair','Sara Lindqvist','Tool changer stalls mid-swap','normal','CLOSED','Sara Lindqvist','2026-04-09 04:36:16',NULL),('TK-260413-080',5,'2026-04-13','07:26:06','Priya Nair','Jide Okafor','Thermoformer seal temperature unstable','critical','CLOSED','Jide Okafor','2026-04-13 04:26:06',NULL),('TK-260423-088',13,'2026-04-23','09:55:12','Priya Nair','Katerina Novak','Guard door interlock will not reset','high','CLOSED','Katerina Novak','2026-04-23 06:55:12',NULL),('TK-260424-071',14,'2026-04-24','09:21:02','Elise Moreau','Taro Yamamoto','Conveyor belt tracking off to one side','low','CLOSED','Taro Yamamoto','2026-04-24 06:21:02',NULL),('TK-260428-064',13,'2026-04-28','17:04:37','Priya Nair','Jide Okafor','Dust extractor low suction at station 3','low','CLOSED','Jide Okafor','2026-04-28 14:04:37',NULL),('TK-260501-029',8,'2026-05-01','20:31:54','Priya Nair','Katerina Novak','Weld quality NOK, porosity in seam','high','CLOSED','Katerina Novak','2026-05-01 17:31:54',NULL),('TK-260505-039',6,'2026-05-05','08:42:40','Elise Moreau','Sara Lindqvist','Compressor tripping on high discharge temp','low','CLOSED','Sara Lindqvist','2026-05-05 05:42:40',NULL),('TK-260506-061',16,'2026-05-06','20:44:34','Priya Nair','Sara Lindqvist','Compressor tripping on high discharge temp','normal','CLOSED','Sara Lindqvist','2026-05-06 17:44:34',NULL),('TK-260506-093',24,'2026-05-06','11:54:25','Marc Dubois','Taro Yamamoto','Conveyor belt tracking off to one side','normal','CLOSED','Taro Yamamoto','2026-05-06 08:54:25',NULL),('TK-260507-096',21,'2026-05-07','19:48:28','Elise Moreau','Taro Yamamoto','Laser cutter head crash on nest start','normal','CLOSED','Taro Yamamoto','2026-05-07 16:48:28',NULL),('TK-260508-090',3,'2026-05-08','20:34:39','Marc Dubois','Katerina Novak','Coolant pressure low, tool wear increasing','critical','CLOSED','Katerina Novak','2026-05-08 17:34:39',NULL),('TK-260509-034',19,'2026-05-09','16:27:48','Rui Silva','Taro Yamamoto','Servo press position error at bottom dead ctr','high','CLOSED','Taro Yamamoto','2026-05-09 13:27:48',NULL),('TK-260511-030',15,'2026-05-11','13:05:28','Marc Dubois','Taro Yamamoto','Laser cutter head crash on nest start','normal','CLOSED','Taro Yamamoto','2026-05-11 10:05:28',NULL),('TK-260512-104',5,'2026-05-12','17:10:24','Elise Moreau','Jide Okafor','Palletiser stops, pattern incomplete','normal','CLOSED','Jide Okafor','2026-05-12 14:10:24',NULL),('TK-260513-046',7,'2026-05-13','15:51:45','Marc Dubois','Taro Yamamoto','Coolant pressure low, tool wear increasing','normal','CLOSED','Taro Yamamoto','2026-05-13 12:51:45',NULL),('TK-260513-098',11,'2026-05-13','07:48:37','Marc Dubois','Katerina Novak','Leak test bench failing good parts','low','CLOSED','Katerina Novak','2026-05-13 04:48:37',NULL),('TK-260514-118',7,'2026-05-14','11:08:51','Rui Silva','Sara Lindqvist','Laser cutter head crash on nest start','normal','CLOSED','Sara Lindqvist','2026-05-14 08:08:51',NULL),('TK-260516-019',10,'2026-05-16','20:02:53','Rui Silva','Sara Lindqvist','Crane hoist limit switch intermittent','critical','CLOSED','Sara Lindqvist','2026-05-16 17:02:53',NULL),('TK-260517-043',10,'2026-05-17','17:16:45','Rui Silva','Sara Lindqvist','Hydraulic power pack noisy, foaming oil','normal','CLOSED','Sara Lindqvist','2026-05-17 14:16:45',NULL),('TK-260517-068',17,'2026-05-17','19:12:32','Elise Moreau','Sara Lindqvist','Coolant pressure low, tool wear increasing','critical','CLOSED','Sara Lindqvist','2026-05-17 16:12:32',NULL),('TK-260518-065',20,'2026-05-18','12:13:17','Rui Silva','Sara Lindqvist','Hydraulic power pack noisy, foaming oil','high','CLOSED','Sara Lindqvist','2026-05-18 09:13:17',NULL),('TK-260520-032',5,'2026-05-20','17:26:10','Rui Silva','Jide Okafor','Leak test bench failing good parts','low','CLOSED','Jide Okafor','2026-05-20 14:26:10',NULL),('TK-260526-050',11,'2026-05-26','13:55:04','Priya Nair','Sara Lindqvist','Robot in fault, brake release error','high','CLOSED','Sara Lindqvist','2026-05-26 10:55:04',NULL),('TK-260527-012',9,'2026-05-27','08:33:06','Priya Nair','Taro Yamamoto','Servo press position error at bottom dead ctr','high','CLOSED','Taro Yamamoto','2026-05-27 05:33:06',NULL),('TK-260527-105',12,'2026-05-27','13:16:41','Priya Nair','Jide Okafor','Compressor tripping on high discharge temp','normal','CLOSED','Jide Okafor','2026-05-27 10:16:41',NULL),('TK-260529-025',4,'2026-05-29','11:10:31','Elise Moreau','Katerina Novak','Axis servo fault F-0031 on rapid move','normal','CLOSED','Katerina Novak','2026-05-29 08:10:31',NULL),('TK-260531-108',9,'2026-05-31','18:25:11','Priya Nair','Taro Yamamoto','Dust extractor low suction at station 3','low','CLOSED','Taro Yamamoto','2026-05-31 15:25:11',NULL),('TK-260601-028',1,'2026-06-01','18:42:53','Elise Moreau','Taro Yamamoto','Robot in fault, brake release error','critical','CLOSED','Taro Yamamoto','2026-06-01 15:42:53',NULL),('TK-260605-085',16,'2026-06-05','19:25:40','Marc Dubois','Katerina Novak','Crane hoist limit switch intermittent','critical','CLOSED','Katerina Novak','2026-06-05 16:25:40',NULL),('TK-260606-015',6,'2026-06-06','06:22:19','Elise Moreau','Taro Yamamoto','Coder printing faint, missing characters','normal','CLOSED','Taro Yamamoto','2026-06-06 03:22:19',NULL),('TK-260608-114',3,'2026-06-08','08:28:33','Elise Moreau','Taro Yamamoto','Tool changer stalls mid-swap','high','CLOSED','Taro Yamamoto','2026-06-08 05:28:33',NULL),('TK-260609-112',13,'2026-06-09','18:54:08','Marc Dubois','Katerina Novak','Coolant pressure low, tool wear increasing','low','CLOSED','Katerina Novak','2026-06-09 15:54:08',NULL),('TK-260611-020',17,'2026-06-11','21:57:04','Marc Dubois','Sara Lindqvist','Dust extractor low suction at station 3','high','CLOSED','Sara Lindqvist','2026-06-11 18:57:04',NULL),('TK-260612-001',4,'2026-06-12','20:25:37','Priya Nair',NULL,'Spindle overheat alarm on cycle start','low','OPEN',NULL,'2026-06-12 17:25:37',NULL),('TK-260616-097',4,'2026-06-16','16:52:40','Marc Dubois','Katerina Novak','Press brake not holding pressure','normal','CLOSED','Katerina Novak','2026-06-16 13:52:40',NULL),('TK-260620-007',22,'2026-06-20','18:52:21','Priya Nair','Jide Okafor','Weld quality NOK, porosity in seam','normal','PENDING',NULL,'2026-06-20 15:52:21',NULL),('TK-260627-075',18,'2026-06-27','06:18:29','Elise Moreau','Katerina Novak','Press brake not holding pressure','normal','CLOSED','Katerina Novak','2026-06-27 03:18:29',NULL),('TK-260629-111',6,'2026-06-29','15:23:51','Elise Moreau','Sara Lindqvist','Spindle overheat alarm on cycle start','low','CLOSED','Sara Lindqvist','2026-06-29 12:23:51',NULL),('TK-260703-120',21,'2026-07-03','20:02:00','Rui Silva','Jide Okafor','Leak test bench failing good parts','normal','CLOSED','Jide Okafor','2026-07-03 17:02:00',NULL),('TK-260706-035',2,'2026-07-06','21:07:51','Priya Nair','Katerina Novak','Vision rig rejecting at 12 percent','normal','CLOSED','Katerina Novak','2026-07-06 18:07:51',NULL),('TK-260706-094',7,'2026-07-06','16:32:21','Marc Dubois','Sara Lindqvist','Robot in fault, brake release error','low','CLOSED','Sara Lindqvist','2026-07-06 13:32:21',NULL),('TK-260709-086',23,'2026-07-09','18:52:05','Rui Silva','Sara Lindqvist','Dust extractor low suction at station 3','normal','CLOSED','Sara Lindqvist','2026-07-09 15:52:05',NULL),('TK-260710-054',15,'2026-07-10','16:16:50','Priya Nair','Sara Lindqvist','Leak test bench failing good parts','normal','CLOSED','Sara Lindqvist','2026-07-10 13:16:50',NULL),('TK-260710-073',4,'2026-07-10','15:09:01','Rui Silva','Katerina Novak','Weld quality NOK, porosity in seam','high','CLOSED','Katerina Novak','2026-07-10 12:09:01',NULL),('TK-260711-051',18,'2026-07-11','15:48:02','Priya Nair','Jide Okafor','Weld quality NOK, porosity in seam','normal','CLOSED','Jide Okafor','2026-07-11 12:48:02',NULL),('TK-260714-003',18,'2026-07-14','18:47:37','Elise Moreau',NULL,'Axis servo fault F-0031 on rapid move','normal','OPEN',NULL,'2026-07-14 15:47:37',NULL),('TK-260714-022',7,'2026-07-14','09:57:24','Rui Silva','Jide Okafor','Guard door interlock will not reset','normal','CLOSED','Jide Okafor','2026-07-14 06:57:24',NULL),('TK-260714-119',14,'2026-07-14','11:52:57','Marc Dubois','Jide Okafor','Press brake not holding pressure','critical','CLOSED','Jide Okafor','2026-07-14 08:52:57',NULL),('TK-260715-027',18,'2026-07-15','10:11:38','Priya Nair','Katerina Novak','Conveyor belt tracking off to one side','critical','CLOSED','Katerina Novak','2026-07-15 07:11:38',NULL),('TK-260715-042',3,'2026-07-15','12:58:15','Elise Moreau','Taro Yamamoto','Dust extractor low suction at station 3','high','CLOSED','Taro Yamamoto','2026-07-15 09:58:15',NULL),('TK-260716-072',21,'2026-07-16','10:22:40','Marc Dubois','Katerina Novak','Robot in fault, brake release error','normal','CLOSED','Katerina Novak','2026-07-16 07:22:40',NULL),('TK-260716-103',22,'2026-07-16','16:24:32','Rui Silva','Katerina Novak','Coder printing faint, missing characters','normal','CLOSED','Katerina Novak','2026-07-16 13:24:32',NULL),('TK-260717-026',11,'2026-07-17','09:04:35','Priya Nair','Sara Lindqvist','Tool changer stalls mid-swap','normal','CLOSED','Sara Lindqvist','2026-07-17 06:04:35',NULL),('TK-260718-092',17,'2026-07-18','20:54:08','Elise Moreau','Sara Lindqvist','Tool changer stalls mid-swap','normal','CLOSED','Sara Lindqvist','2026-07-18 17:54:08',NULL),('TK-260719-018',3,'2026-07-19','18:01:19','Elise Moreau','Sara Lindqvist','Chiller low delta-T, machines running warm','critical','CLOSED','Sara Lindqvist','2026-07-19 15:01:19',NULL),('TK-260720-037',16,'2026-07-20','19:34:51','Rui Silva','Sara Lindqvist','Coder printing faint, missing characters','critical','CLOSED','Sara Lindqvist','2026-07-20 16:34:51',NULL),('TK-260720-084',9,'2026-07-20','11:05:42','Marc Dubois','Jide Okafor','Chiller low delta-T, machines running warm','high','CLOSED','Jide Okafor','2026-07-20 08:05:42',NULL),('TK-260722-011',2,'2026-07-22','19:39:43','Priya Nair','Jide Okafor','Nutrunner torque out of window','critical','CLOSED','Jide Okafor','2026-07-22 16:39:43',NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_logs`
--

LOCK TABLES `analytics_logs` WRITE;
/*!40000 ALTER TABLE `analytics_logs` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES (1,'Security','session_lockout_time','360'),(4,'SLA','sla_hours_critical','2'),(5,'SLA','sla_hours_high','8'),(6,'SLA','sla_hours_normal','48'),(8,'KPI','target_mttd','60'),(9,'KPI','target_mttr','120'),(10,'KPI','target_mtbf','48'),(11,'KPI','plant_holidays','[]'),(12,'KPI','target_calc_mode','dynamic'),(13,'Features','allow_checklist_photos','0'),(14,'Procurement','procurement_workflow_enabled','0'),(15,'Procurement','po_auto_approve_limit','0'),(32,'EquipmentLabels','equip_label_symbology','qrcode'),(33,'EquipmentLabels','equip_label_fields','{\"uuid\":true,\"serial\":true,\"brand_model\":false,\"location\":true,\"category_crit\":false}'),(34,'EquipmentLabels','equip_label_method','browser_sheet'),(35,'EquipmentLabels','equip_label_width_mm','50.8'),(36,'EquipmentLabels','equip_label_height_mm','25.4'),(37,'EquipmentLabels','equip_label_page_preset','a4'),(38,'EquipmentLabels','equip_label_page_width_mm','210'),(39,'EquipmentLabels','equip_label_page_height_mm','297'),(40,'EquipmentLabels','equip_label_margin_mm','10'),(41,'EquipmentLabels','equip_label_gap_x_mm','3'),(42,'EquipmentLabels','equip_label_gap_y_mm','3'),(43,'EquipmentLabels','equip_label_printer_ip',''),(44,'EquipmentLabels','equip_label_printer_port','9100'),(45,'EquipmentLabels','equip_label_dpi','203'),(46,'EquipmentLabels','equip_label_darkness','10'),(47,'EquipmentLabels','equip_label_speed','4');
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'2026-04-26 05:38:00',2,'equipment.create','equipment','18',NULL,NULL,'Added asset to the register'),(2,'2026-06-27 10:44:31',10,'equipment.update','equipment','29',NULL,NULL,'Updated PM interval'),(3,'2026-02-04 09:11:36',1,'user.create','users','12',NULL,NULL,'Created technician account'),(4,'2026-04-23 10:39:54',2,'po.approve','purchase_orders','9',NULL,NULL,'Approved purchase request'),(5,'2026-02-13 12:13:10',2,'po.receive','purchase_orders','15',NULL,NULL,'Goods receipt recorded'),(6,'2026-07-14 13:01:44',10,'settings.update','app_settings','1',NULL,NULL,'Changed SLA target'),(7,'2026-03-26 07:33:44',10,'inventory.adjust','inventory_parts','6',NULL,NULL,'Stock count correction'),(8,'2026-05-21 11:28:27',2,'equipment.create','equipment','4',NULL,NULL,'Added asset to the register'),(9,'2026-05-12 03:49:40',2,'equipment.update','equipment','29',NULL,NULL,'Updated PM interval'),(10,'2026-05-13 06:25:26',10,'user.create','users','9',NULL,NULL,'Created technician account'),(11,'2026-06-18 08:01:53',2,'po.approve','purchase_orders','1',NULL,NULL,'Approved purchase request'),(12,'2026-05-16 17:23:57',10,'po.receive','purchase_orders','5',NULL,NULL,'Goods receipt recorded'),(13,'2026-07-18 17:01:20',2,'settings.update','app_settings','15',NULL,NULL,'Changed SLA target'),(14,'2026-04-03 17:37:08',1,'inventory.adjust','inventory_parts','3',NULL,NULL,'Stock count correction'),(15,'2026-03-16 07:33:46',1,'equipment.create','equipment','18',NULL,NULL,'Added asset to the register'),(16,'2026-04-10 16:00:58',10,'equipment.update','equipment','10',NULL,NULL,'Updated PM interval'),(17,'2026-07-02 11:02:33',2,'user.create','users','25',NULL,NULL,'Created technician account'),(18,'2026-05-08 10:09:55',10,'po.approve','purchase_orders','24',NULL,NULL,'Approved purchase request'),(19,'2026-05-13 17:04:00',2,'po.receive','purchase_orders','13',NULL,NULL,'Goods receipt recorded'),(20,'2026-02-25 11:04:39',1,'settings.update','app_settings','29',NULL,NULL,'Changed SLA target'),(21,'2026-05-27 18:29:28',1,'inventory.adjust','inventory_parts','25',NULL,NULL,'Stock count correction'),(22,'2026-06-12 06:39:14',2,'equipment.create','equipment','18',NULL,NULL,'Added asset to the register'),(23,'2026-06-23 04:11:32',2,'equipment.update','equipment','24',NULL,NULL,'Updated PM interval'),(24,'2026-02-17 12:54:09',10,'user.create','users','22',NULL,NULL,'Created technician account'),(25,'2026-02-24 15:01:06',1,'po.approve','purchase_orders','7',NULL,NULL,'Approved purchase request'),(26,'2026-06-27 18:54:56',2,'po.receive','purchase_orders','6',NULL,NULL,'Goods receipt recorded'),(27,'2026-07-03 16:39:22',10,'settings.update','app_settings','7',NULL,NULL,'Changed SLA target'),(28,'2026-06-28 18:32:36',2,'inventory.adjust','inventory_parts','18',NULL,NULL,'Stock count correction'),(29,'2026-03-12 10:34:34',10,'equipment.create','equipment','3',NULL,NULL,'Added asset to the register'),(30,'2026-04-28 16:08:16',2,'equipment.update','equipment','1',NULL,NULL,'Updated PM interval'),(31,'2026-04-09 15:03:45',10,'user.create','users','11',NULL,NULL,'Created technician account'),(32,'2026-04-05 13:35:23',1,'po.approve','purchase_orders','7',NULL,NULL,'Approved purchase request'),(33,'2026-05-08 08:12:11',1,'po.receive','purchase_orders','3',NULL,NULL,'Goods receipt recorded'),(34,'2026-05-05 03:18:13',2,'settings.update','app_settings','18',NULL,NULL,'Changed SLA target'),(35,'2026-05-01 16:54:53',10,'inventory.adjust','inventory_parts','30',NULL,NULL,'Stock count correction'),(36,'2026-02-13 19:27:22',1,'equipment.create','equipment','16',NULL,NULL,'Added asset to the register'),(37,'2026-04-06 10:06:04',10,'equipment.update','equipment','23',NULL,NULL,'Updated PM interval'),(38,'2026-06-04 16:10:26',2,'user.create','users','17',NULL,NULL,'Created technician account'),(39,'2026-07-04 12:12:34',2,'po.approve','purchase_orders','16',NULL,NULL,'Approved purchase request'),(40,'2026-02-28 19:37:21',1,'po.receive','purchase_orders','25',NULL,NULL,'Goods receipt recorded');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_budget_logs`
--

LOCK TABLES `department_budget_logs` WRITE;
/*!40000 ALTER TABLE `department_budget_logs` DISABLE KEYS */;
INSERT INTO `department_budget_logs` VALUES (1,1,'allocation',250000.00,'Annual budget allocated for the current financial year.',1,'2025-10-11 17:05:29'),(2,2,'allocation',180000.00,'Annual budget allocated for the current financial year.',1,'2025-10-08 10:17:22'),(3,3,'allocation',95000.00,'Annual budget allocated for the current financial year.',1,'2025-10-02 09:29:36'),(4,4,'allocation',60000.00,'Annual budget allocated for the current financial year.',1,'2025-10-08 11:53:23'),(5,5,'allocation',40000.00,'Annual budget allocated for the current financial year.',1,'2025-10-13 07:14:08'),(6,1,'consumption',10337.15,'Cumulative spend against received purchase orders.',10,'2026-07-09 03:52:55'),(7,2,'consumption',5009.60,'Cumulative spend against received purchase orders.',10,'2026-07-11 11:31:12'),(8,3,'consumption',3297.60,'Cumulative spend against received purchase orders.',10,'2026-07-09 08:00:16'),(9,4,'consumption',4670.90,'Cumulative spend against received purchase orders.',10,'2026-06-30 16:26:47'),(10,5,'consumption',5651.80,'Cumulative spend against received purchase orders.',10,'2026-07-02 03:17:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Maintenance Operations',250000.00,10337.15),(2,'Production Support',180000.00,5009.60),(3,'Facilities & Utilities',95000.00,3297.60),(4,'Tooling & Fixtures',60000.00,4670.90),(5,'Health, Safety & Env.',40000.00,5651.80);
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,'WCC-8D450E-0001','DMG Mori','NHX 5000','DMG-477917','DMG Mori NHX 5000 Machining Ctr',NULL,'Machining','A','CNC Machining Center','Plant A','CNC Cell 1','ST-01',NULL,'2015-10-16',7,173000.00,NULL,NULL,1,15,NULL,'2020-01-16','2031-10-16',NULL,NULL,NULL,'400V 3ph',NULL,90,'2026-06-11','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,1,NULL,NULL),(2,'WCC-31F9EC-0002','Yamazaki Mazak','VTC-800/30SR','YAM-842737','Mazak VTC-800 Vertical Center',NULL,'Machining','A','CNC Machining Center','Plant A','CNC Cell 1','ST-02',NULL,'2020-05-03',3,446000.00,NULL,NULL,1,16,NULL,'2024-10-03','2040-05-03',NULL,NULL,NULL,'400V 3ph',NULL,60,'2026-06-16','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Siemens Comfort 12in\"}',1,1,NULL,NULL),(3,'WCC-534B6F-0003','Okuma','LB3000 EX II','OKU-483206','Okuma LB3000 EX II Lathe',NULL,'Machining','A','CNC Turning Center','Plant A','CNC Cell 2','ST-03',NULL,'2016-02-02',4,431000.00,NULL,NULL,1,20,NULL,'2018-02-02','2031-02-02',NULL,NULL,NULL,'690V 3ph',NULL,30,'2026-06-05','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',1,2,NULL,NULL),(4,'WCC-A8CC0E-0004','Haas','VF-4SS','HAA-381628','Haas VF-4SS Mill',NULL,'Machining','B','CNC Machining Center','Plant A','CNC Cell 2','ST-04',NULL,'2019-04-17',7,129000.00,NULL,NULL,1,16,NULL,'2021-11-17','2039-04-17',NULL,NULL,NULL,'230V 1ph',NULL,60,'2026-04-20','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',1,2,NULL,NULL),(5,'WCC-FBCE45-0005','Knoll','KTS-40','KNO-917195','Chip Conveyor & Coolant Unit 1',NULL,'Auxiliary','C','Coolant System','Plant A','CNC Cell 1','ST-05',NULL,'2018-01-21',7,229000.00,NULL,NULL,1,17,NULL,'2021-10-21','2036-01-21',NULL,NULL,NULL,'690V 3ph',NULL,180,'2026-07-03','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Siemens S7-1500\",\"network\":\"PROFINET\",\"hmi\":\"Siemens Comfort 12in\"}',1,1,NULL,NULL),(6,'WCC-F0F3A2-0006','Knoll','KTS-40','KNO-267335','Chip Conveyor & Coolant Unit 2',NULL,'Auxiliary','C','Coolant System','Plant A','CNC Cell 2','ST-06',NULL,'2016-04-04',8,132000.00,NULL,NULL,1,17,NULL,'2020-05-04','2033-04-04',NULL,NULL,NULL,'400V 3ph',NULL,30,'2026-06-13','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Fanuc 31i\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,2,NULL,NULL),(7,'WCC-8E8498-0007','Fronius','TPS 400i','FRO-974171','Fronius TPS 400i Weld Station',NULL,'Fabrication','B','Welding Station','Plant A','Fabrication Line','ST-07',NULL,'2024-04-26',2,167000.00,NULL,NULL,1,19,NULL,'2028-02-26','2038-04-26',NULL,NULL,NULL,'690V 3ph',NULL,90,'2026-04-01','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,3,NULL,NULL),(8,'WCC-502B65-0008','KUKA','KR 60-3','KUK-469595','KUKA KR 60 Weld Robot',NULL,'Robotics','A','Articulated Robot','Plant A','Fabrication Line','ST-08',NULL,'2015-05-02',8,318000.00,NULL,NULL,1,17,NULL,'2017-05-02','2030-05-02',NULL,NULL,NULL,'690V 3ph',NULL,30,'2026-07-11','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',1,3,NULL,NULL),(9,'WCC-9B4557-0009','Amada','HFE 1303','AMA-625920','Amada HFE 1303 Press Brake',NULL,'Fabrication','B','Press Brake','Plant A','Fabrication Line','ST-09',NULL,'2024-01-28',6,316000.00,NULL,NULL,1,15,NULL,'2026-09-28','2041-01-28',NULL,NULL,NULL,'400V 3ph',NULL,90,'2026-06-03','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Fanuc 31i\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,3,NULL,NULL),(10,'WCC-755332-0010','TRUMPF','TruLaser 3030','TRU-978027','Trumpf TruLaser 3030 Cutter',NULL,'Fabrication','A','Laser Cutter','Plant A','Fabrication Line','ST-10',NULL,'2016-07-19',2,255000.00,NULL,NULL,1,13,NULL,'2019-02-19','2034-07-19',NULL,NULL,NULL,'230V 1ph',NULL,30,'2026-06-21','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Siemens Comfort 12in\"}',1,3,NULL,NULL),(11,'WCC-3A3BF2-0011','Interroll','MCP-2000','INT-284600','Assembly Conveyor A1 (Main)',NULL,'Conveyance','B','Belt Conveyor','Plant B','Assembly Line 1','ST-11',NULL,'2017-11-08',5,454000.00,NULL,NULL,1,12,NULL,'2022-01-08','2029-11-08',NULL,NULL,NULL,'400V 3ph',NULL,180,'2026-04-10','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Siemens Comfort 12in\"}',2,4,NULL,NULL),(12,'WCC-601C77-0012','Atlas Copco','Tensor STR61','ATL-902415','Atlas Copco Nutrunner Cell A1',NULL,'Assembly','B','Torque System','Plant B','Assembly Line 1','ST-12',NULL,'2018-04-18',4,107000.00,NULL,NULL,1,14,NULL,'2021-05-18','2033-04-18',NULL,NULL,NULL,'690V 3ph',NULL,60,'2026-07-11','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',2,4,NULL,NULL),(13,'WCC-5A9A2D-0013','ATEQ','F620','ATE-494378','Leak Test Bench A1',NULL,'Quality','A','Leak Tester','Plant B','Assembly Line 1','ST-13',NULL,'2021-10-20',4,381000.00,NULL,NULL,1,18,NULL,'2024-12-20','2037-10-20',NULL,NULL,NULL,'400V 3ph',NULL,180,'2026-05-25','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',2,4,NULL,NULL),(14,'WCC-D719B0-0014','FANUC','M-20iD/25','FAN-834036','FANUC M-20iD Pick and Place',NULL,'Robotics','B','Articulated Robot','Plant B','Assembly Line 2','ST-14',NULL,'2014-12-17',8,322000.00,NULL,NULL,1,20,NULL,'2019-11-17','2028-12-17',NULL,NULL,NULL,'230V 1ph',NULL,60,'2026-05-21','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Siemens S7-1500\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',2,5,NULL,NULL),(15,'WCC-7E387A-0015','Interroll','MCP-1600','INT-270532','Assembly Conveyor A2',NULL,'Conveyance','C','Belt Conveyor','Plant B','Assembly Line 2','ST-15',NULL,'2021-12-18',2,278000.00,NULL,NULL,1,19,NULL,'2023-12-18','2034-12-18',NULL,NULL,NULL,'690V 3ph',NULL,90,'2026-04-03','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Fanuc 31i\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',2,5,NULL,NULL),(16,'WCC-F7DCAA-0016','Promess','EMAP-100','PRO-177970','Servo Press Station A2',NULL,'Assembly','A','Servo Press','Plant B','Assembly Line 2','ST-16',NULL,'2020-12-19',1,465000.00,NULL,NULL,1,17,NULL,'2025-07-19','2038-12-19',NULL,NULL,NULL,'400V 3ph',NULL,180,'2026-06-23','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Siemens S7-1500\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',2,5,NULL,NULL),(17,'WCC-A0CCC5-0017','Cognex','In-Sight 9912','COG-119627','Vision Inspection Rig A2',NULL,'Quality','B','Vision System','Plant B','Assembly Line 2','ST-17',NULL,'2023-10-10',8,42000.00,NULL,NULL,1,15,NULL,'2028-05-10','2039-10-10',NULL,NULL,NULL,'400V 3ph',NULL,30,'2026-05-11','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Siemens S7-1500\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',2,5,NULL,NULL),(18,'WCC-6A7621-0018','MULTIVAC','R 145','MUL-815687','Multivac R145 Thermoformer',NULL,'Packaging','B','Thermoformer','Plant B','Packaging Line','ST-18',NULL,'2016-02-04',4,468000.00,NULL,NULL,1,12,NULL,'2018-08-04','2030-02-04',NULL,NULL,NULL,'690V 3ph',NULL,30,'2026-04-17','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Fanuc 31i\",\"network\":\"PROFINET\",\"hmi\":\"Fanuc iHMI\"}',2,6,NULL,NULL),(19,'WCC-A7AB84-0019','Markem-Imaje','9450','MAR-227858','Markem-Imaje 9450 Coder',NULL,'Packaging','C','Inkjet Coder','Plant B','Packaging Line','ST-19',NULL,'2016-07-15',2,132000.00,NULL,NULL,1,16,NULL,'2019-12-15','2031-07-15',NULL,NULL,NULL,'690V 3ph',NULL,90,'2026-04-28','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',2,6,NULL,NULL),(20,'WCC-5481EA-0020','Robopac','Genesis HS','ROB-582981','Robopac Palletiser P1',NULL,'Packaging','B','Palletiser','Plant B','Packaging Line','ST-20',NULL,'2024-02-19',7,124000.00,NULL,NULL,1,14,NULL,'2028-10-19','2044-02-19',NULL,NULL,NULL,'690V 3ph',NULL,30,'2026-06-27','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Siemens S7-1500\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',2,6,NULL,NULL),(21,'WCC-F2F49F-0021','Atlas Copco','GA 55 VSD+','ATL-860562','Atlas Copco GA 55 Compressor',NULL,'Utilities','A','Air Compressor','Plant A',NULL,'Utilities',NULL,'2021-01-25',5,137000.00,NULL,NULL,1,19,NULL,'2023-10-25','2034-01-25',NULL,NULL,NULL,'690V 3ph',NULL,90,'2026-04-08','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Siemens Comfort 12in\"}',1,NULL,NULL,NULL),(22,'WCC-BF4148-0022','Trane','CGAM 070','TRA-192253','Chiller Unit CH-1',NULL,'Utilities','A','Process Chiller','Plant A',NULL,'Utilities',NULL,'2018-06-11',1,28000.00,NULL,NULL,1,12,NULL,'2022-02-11','2034-06-11',NULL,NULL,NULL,'400V 3ph',NULL,90,'2026-06-14','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Beckhoff CX2040\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,NULL,NULL,NULL),(23,'WCC-6F3C79-0023','Nederman','NFPD 4000','NED-624640','Dust Extraction Unit DE-2',NULL,'Utilities','C','Dust Extractor','Plant A',NULL,'Utilities',NULL,'2019-01-13',2,70000.00,NULL,NULL,1,18,NULL,'2021-11-13','2037-01-13',NULL,NULL,NULL,'400V 3ph',NULL,60,'2026-05-21','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,NULL,NULL,NULL),(24,'WCC-FFE91B-0024','Konecranes','CXT 5000','KON-996199','Overhead Crane 5T Bay 1',NULL,'Handling','B','Overhead Crane','Plant A',NULL,'Utilities',NULL,'2023-04-13',3,454000.00,NULL,NULL,1,20,NULL,'2025-08-13','2035-04-13',NULL,NULL,NULL,'400V 3ph',NULL,60,'2026-04-05','1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.',NULL,'{\"control\":\"Allen-Bradley CompactLogix\",\"network\":\"PROFINET\",\"hmi\":\"Beijer X2\"}',1,NULL,NULL,NULL);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_bom`
--

LOCK TABLES `equipment_bom` WRITE;
/*!40000 ALTER TABLE `equipment_bom` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_documents`
--

LOCK TABLES `equipment_documents` WRITE;
/*!40000 ALTER TABLE `equipment_documents` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_ledger`
--

LOCK TABLES `inventory_ledger` WRITE;
/*!40000 ALTER TABLE `inventory_ledger` DISABLE KEYS */;
INSERT INTO `inventory_ledger` VALUES (1,'2026-03-15 16:09:53',13,-1,'ticket_consume','active_tickets','TK-260315-010','Consumed during repair of TK-260315-010',6),(2,'2026-07-22 18:35:43',18,-1,'ticket_consume','active_tickets','TK-260722-011','Consumed during repair of TK-260722-011',4),(3,'2026-01-03 09:26:58',28,-2,'ticket_consume','active_tickets','TK-260103-013','Consumed during repair of TK-260103-013',6),(4,'2025-10-30 16:56:50',33,-2,'ticket_consume','active_tickets','TK-251030-014','Consumed during repair of TK-251030-014',5),(5,'2026-06-06 04:28:19',3,-1,'ticket_consume','active_tickets','TK-260606-015','Consumed during repair of TK-260606-015',6),(6,'2025-11-13 11:56:09',8,-1,'ticket_consume','active_tickets','TK-251113-016','Consumed during repair of TK-251113-016',5),(7,'2026-04-08 16:37:12',13,-1,'ticket_consume','active_tickets','TK-260408-017','Consumed during repair of TK-260408-017',5),(8,'2026-07-19 17:34:19',18,-3,'ticket_consume','active_tickets','TK-260719-018','Consumed during repair of TK-260719-018',5),(9,'2026-06-11 21:57:04',28,-3,'ticket_consume','active_tickets','TK-260611-020','Consumed during repair of TK-260611-020',5),(10,'2026-03-08 18:35:33',33,-2,'ticket_consume','active_tickets','TK-260308-021','Consumed during repair of TK-260308-021',7),(11,'2026-07-14 09:17:24',3,-2,'ticket_consume','active_tickets','TK-260714-022','Consumed during repair of TK-260714-022',4),(12,'2026-04-05 11:44:19',8,-1,'ticket_consume','active_tickets','TK-260405-023','Consumed during repair of TK-260405-023',7),(13,'2025-11-12 09:03:11',13,-2,'ticket_consume','active_tickets','TK-251112-024','Consumed during repair of TK-251112-024',5),(14,'2026-05-29 11:39:31',18,-2,'ticket_consume','active_tickets','TK-260529-025','Consumed during repair of TK-260529-025',7),(15,'2026-07-17 10:39:35',23,-2,'ticket_consume','active_tickets','TK-260717-026','Consumed during repair of TK-260717-026',5),(16,'2026-07-15 09:31:38',28,-3,'ticket_consume','active_tickets','TK-260715-027','Consumed during repair of TK-260715-027',7),(17,'2026-06-01 18:53:53',33,-2,'ticket_consume','active_tickets','TK-260601-028','Consumed during repair of TK-260601-028',6),(18,'2026-05-11 12:41:28',8,-2,'ticket_consume','active_tickets','TK-260511-030','Consumed during repair of TK-260511-030',6),(19,'2025-11-22 21:43:56',13,-3,'ticket_consume','active_tickets','TK-251122-031','Consumed during repair of TK-251122-031',4),(20,'2026-05-20 17:17:10',18,-3,'ticket_consume','active_tickets','TK-260520-032','Consumed during repair of TK-260520-032',4),(21,'2025-12-20 17:54:47',23,-2,'ticket_consume','active_tickets','TK-251220-033','Consumed during repair of TK-251220-033',6),(22,'2026-05-09 16:56:48',28,-3,'ticket_consume','active_tickets','TK-260509-034','Consumed during repair of TK-260509-034',6),(23,'2026-07-06 21:34:51',33,-1,'ticket_consume','active_tickets','TK-260706-035','Consumed during repair of TK-260706-035',7),(24,'2026-07-20 18:13:51',8,-2,'ticket_consume','active_tickets','TK-260720-037','Consumed during repair of TK-260720-037',5),(25,'2025-12-17 13:54:10',13,-2,'ticket_consume','active_tickets','TK-251217-038','Consumed during repair of TK-251217-038',5),(26,'2026-05-05 10:05:40',18,-3,'ticket_consume','active_tickets','TK-260505-039','Consumed during repair of TK-260505-039',5),(27,'2025-12-25 14:20:39',23,-1,'ticket_consume','active_tickets','TK-251225-040','Consumed during repair of TK-251225-040',6),(28,'2026-03-23 10:40:19',8,-1,'ticket_consume','active_tickets','TK-260323-044','Consumed during repair of TK-260323-044',7),(29,'2026-02-27 12:28:46',33,-2,'ticket_consume','active_tickets','TK-260227-049','Consumed during repair of TK-260227-049',5),(30,'2026-07-11 14:51:02',8,-3,'ticket_consume','active_tickets','TK-260711-051','Consumed during repair of TK-260711-051',4),(31,'2025-11-12 21:17:02',18,-3,'ticket_consume','active_tickets','TK-251112-053','Consumed during repair of TK-251112-053',5),(32,'2026-07-10 16:40:50',23,-3,'ticket_consume','active_tickets','TK-260710-054','Consumed during repair of TK-260710-054',5),(33,'2026-04-08 10:39:29',28,-3,'ticket_consume','active_tickets','TK-260408-055','Consumed during repair of TK-260408-055',5),(34,'2025-10-31 21:44:25',33,-1,'ticket_consume','active_tickets','TK-251031-056','Consumed during repair of TK-251031-056',6),(35,'2026-01-03 15:11:43',3,-1,'ticket_consume','active_tickets','TK-260103-057','Consumed during repair of TK-260103-057',5),(36,'2026-03-12 11:49:44',8,-1,'ticket_consume','active_tickets','TK-260312-058','Consumed during repair of TK-260312-058',7),(37,'2025-11-02 09:11:13',18,-3,'ticket_consume','active_tickets','TK-251102-060','Consumed during repair of TK-251102-060',7),(38,'2026-05-06 21:48:34',23,-1,'ticket_consume','active_tickets','TK-260506-061','Consumed during repair of TK-260506-061',5),(39,'2025-11-08 17:01:48',28,-2,'ticket_consume','active_tickets','TK-251108-062','Consumed during repair of TK-251108-062',7),(40,'2025-11-30 17:25:52',33,-3,'ticket_consume','active_tickets','TK-251130-063','Consumed during repair of TK-251130-063',6),(41,'2026-04-28 16:13:37',3,-1,'ticket_consume','active_tickets','TK-260428-064','Consumed during repair of TK-260428-064',4),(42,'2026-05-18 11:05:17',8,-2,'ticket_consume','active_tickets','TK-260518-065','Consumed during repair of TK-260518-065',5),(43,'2026-02-08 17:46:39',13,-2,'ticket_consume','active_tickets','TK-260208-066','Consumed during repair of TK-260208-066',7),(44,'2026-05-17 17:57:32',23,-1,'ticket_consume','active_tickets','TK-260517-068','Consumed during repair of TK-260517-068',5),(45,'2026-04-08 17:25:11',28,-3,'ticket_consume','active_tickets','TK-260408-069','Consumed during repair of TK-260408-069',7),(46,'2026-04-24 08:03:02',3,-3,'ticket_consume','active_tickets','TK-260424-071','Consumed during repair of TK-260424-071',6),(47,'2026-07-16 10:18:40',8,-2,'ticket_consume','active_tickets','TK-260716-072','Consumed during repair of TK-260716-072',7),(48,'2026-07-10 13:56:01',13,-3,'ticket_consume','active_tickets','TK-260710-073','Consumed during repair of TK-260710-073',7),(49,'2025-12-26 09:49:44',18,-2,'ticket_consume','active_tickets','TK-251226-074','Consumed during repair of TK-251226-074',7),(50,'2026-06-27 09:35:29',23,-1,'ticket_consume','active_tickets','TK-260627-075','Consumed during repair of TK-260627-075',7),(51,'2026-03-24 21:11:05',28,-3,'ticket_consume','active_tickets','TK-260324-076','Consumed during repair of TK-260324-076',7),(52,'2026-03-07 06:45:20',33,-1,'ticket_consume','active_tickets','TK-260307-077','Consumed during repair of TK-260307-077',7),(53,'2025-11-09 22:21:47',3,-2,'ticket_consume','active_tickets','TK-251109-078','Consumed during repair of TK-251109-078',5),(54,'2025-11-21 19:38:23',8,-3,'ticket_consume','active_tickets','TK-251121-079','Consumed during repair of TK-251121-079',7),(55,'2026-02-23 18:02:38',28,-3,'ticket_consume','active_tickets','TK-260223-083','Consumed during repair of TK-260223-083',4),(56,'2026-07-20 11:35:42',33,-2,'ticket_consume','active_tickets','TK-260720-084','Consumed during repair of TK-260720-084',4),(57,'2026-06-05 19:08:40',3,-3,'ticket_consume','active_tickets','TK-260605-085','Consumed during repair of TK-260605-085',7),(58,'2026-01-05 07:30:23',13,-1,'ticket_consume','active_tickets','TK-260105-087','Consumed during repair of TK-260105-087',6),(59,'2026-04-23 09:20:12',18,-2,'ticket_consume','active_tickets','TK-260423-088','Consumed during repair of TK-260423-088',7),(60,'2026-01-25 21:58:40',23,-2,'ticket_consume','active_tickets','TK-260125-089','Consumed during repair of TK-260125-089',4),(61,'2026-05-08 19:36:39',28,-2,'ticket_consume','active_tickets','TK-260508-090','Consumed during repair of TK-260508-090',7),(62,'2026-03-10 11:49:29',33,-1,'ticket_consume','active_tickets','TK-260310-091','Consumed during repair of TK-260310-091',6),(63,'2026-07-18 22:12:08',3,-2,'ticket_consume','active_tickets','TK-260718-092','Consumed during repair of TK-260718-092',5),(64,'2026-05-06 10:28:25',8,-3,'ticket_consume','active_tickets','TK-260506-093','Consumed during repair of TK-260506-093',6),(65,'2026-07-06 16:48:21',13,-1,'ticket_consume','active_tickets','TK-260706-094','Consumed during repair of TK-260706-094',5),(66,'2025-11-24 10:26:39',18,-3,'ticket_consume','active_tickets','TK-251124-095','Consumed during repair of TK-251124-095',6),(67,'2026-05-07 19:24:28',23,-2,'ticket_consume','active_tickets','TK-260507-096','Consumed during repair of TK-260507-096',6),(68,'2026-05-13 07:21:37',33,-3,'ticket_consume','active_tickets','TK-260513-098','Consumed during repair of TK-260513-098',7),(69,'2026-02-08 14:13:11',3,-3,'ticket_consume','active_tickets','TK-260208-099','Consumed during repair of TK-260208-099',6),(70,'2025-12-28 12:06:46',8,-2,'ticket_consume','active_tickets','TK-251228-100','Consumed during repair of TK-251228-100',6),(71,'2026-07-16 14:25:32',23,-2,'ticket_consume','active_tickets','TK-260716-103','Consumed during repair of TK-260716-103',7),(72,'2026-05-12 16:13:24',28,-2,'ticket_consume','active_tickets','TK-260512-104','Consumed during repair of TK-260512-104',4),(73,'2026-05-27 12:50:41',33,-3,'ticket_consume','active_tickets','TK-260527-105','Consumed during repair of TK-260527-105',4),(74,'2025-11-05 16:16:07',3,-1,'ticket_consume','active_tickets','TK-251105-106','Consumed during repair of TK-251105-106',5),(75,'2026-03-11 19:51:43',8,-2,'ticket_consume','active_tickets','TK-260311-107','Consumed during repair of TK-260311-107',7),(76,'2026-05-31 17:17:11',13,-3,'ticket_consume','active_tickets','TK-260531-108','Consumed during repair of TK-260531-108',6),(77,'2026-04-04 11:35:52',23,-1,'ticket_consume','active_tickets','TK-260404-110','Consumed during repair of TK-260404-110',6),(78,'2026-06-29 18:08:51',28,-2,'ticket_consume','active_tickets','TK-260629-111','Consumed during repair of TK-260629-111',5),(79,'2026-06-09 18:08:08',33,-3,'ticket_consume','active_tickets','TK-260609-112','Consumed during repair of TK-260609-112',7),(80,'2026-06-08 10:06:33',8,-3,'ticket_consume','active_tickets','TK-260608-114','Consumed during repair of TK-260608-114',6),(81,'2026-02-19 11:15:31',13,-1,'ticket_consume','active_tickets','TK-260219-115','Consumed during repair of TK-260219-115',7),(82,'2026-05-14 11:12:51',28,-1,'ticket_consume','active_tickets','TK-260514-118','Consumed during repair of TK-260514-118',5),(83,'2026-07-03 20:30:00',3,-1,'ticket_consume','active_tickets','TK-260703-120','Consumed during repair of TK-260703-120',4),(84,'2025-11-28 12:37:19',13,-1,'ticket_consume','active_tickets','TK-251128-122','Consumed during repair of TK-251128-122',5),(85,'2026-03-26 12:23:06',18,-1,'ticket_consume','active_tickets','TK-260326-123','Consumed during repair of TK-260326-123',7),(86,'2026-04-03 15:01:08',23,-2,'ticket_consume','active_tickets','TK-260403-124','Consumed during repair of TK-260403-124',4),(87,'2026-02-17 06:32:40',28,-3,'ticket_consume','active_tickets','TK-260217-125','Consumed during repair of TK-260217-125',6),(88,'2025-12-03 06:49:11',33,-2,'ticket_consume','active_tickets','TK-251203-126','Consumed during repair of TK-251203-126',6),(89,'2026-05-17 13:25:00',6,-1,'wo_consume','work_orders','1','Consumed on work order #1',6),(90,'2026-07-05 10:27:00',14,-1,'wo_consume','work_orders','3','Consumed on work order #3',7),(91,'2025-12-29 11:54:00',22,-3,'wo_consume','work_orders','5','Consumed on work order #5',4),(92,'2025-12-26 09:22:00',30,-2,'wo_consume','work_orders','7','Consumed on work order #7',4),(93,'2026-02-13 14:34:00',3,-2,'wo_consume','work_orders','9','Consumed on work order #9',5),(94,'2026-05-01 09:20:00',11,-3,'wo_consume','work_orders','11','Consumed on work order #11',4),(95,'2026-04-26 11:12:00',19,-2,'wo_consume','work_orders','13','Consumed on work order #13',5),(96,'2025-12-10 09:10:00',27,-1,'wo_consume','work_orders','15','Consumed on work order #15',6),(97,'2026-05-02 11:35:00',35,-1,'wo_consume','work_orders','17','Consumed on work order #17',4),(98,'2026-07-05 13:00:00',8,-1,'wo_consume','work_orders','19','Consumed on work order #19',5),(99,'2025-12-20 13:01:00',16,-3,'wo_consume','work_orders','21','Consumed on work order #21',7),(100,'2026-04-10 11:08:00',24,-2,'wo_consume','work_orders','23','Consumed on work order #23',7),(101,'2025-12-01 12:45:00',32,-3,'wo_consume','work_orders','25','Consumed on work order #25',4),(102,'2026-06-02 09:19:00',5,-1,'wo_consume','work_orders','27','Consumed on work order #27',7),(103,'2026-02-15 13:02:00',13,-3,'wo_consume','work_orders','29','Consumed on work order #29',6),(104,'2025-12-19 07:31:00',21,-1,'wo_consume','work_orders','31','Consumed on work order #31',6),(105,'2025-12-28 09:31:00',29,-1,'wo_consume','work_orders','33','Consumed on work order #33',7),(106,'2026-06-24 09:42:53',27,4,'po_receipt','purchase_orders','17','Goods receipt against PO #17',10),(107,'2026-06-22 09:42:53',30,4,'po_receipt','purchase_orders','17','Goods receipt against PO #17',10),(108,'2026-06-14 15:30:55',33,7,'po_receipt','purchase_orders','18','Goods receipt against PO #18',10),(109,'2026-06-11 15:30:55',1,8,'po_receipt','purchase_orders','18','Goods receipt against PO #18',10),(110,'2026-06-15 15:30:55',4,9,'po_receipt','purchase_orders','18','Goods receipt against PO #18',10),(111,'2026-06-14 15:30:55',7,6,'po_receipt','purchase_orders','18','Goods receipt against PO #18',10),(112,'2026-06-10 16:06:29',4,25,'po_receipt','purchase_orders','19','Goods receipt against PO #19',10),(113,'2026-06-08 16:06:29',7,22,'po_receipt','purchase_orders','19','Goods receipt against PO #19',10),(114,'2026-06-13 16:06:29',10,16,'po_receipt','purchase_orders','19','Goods receipt against PO #19',10),(115,'2026-06-02 14:06:03',10,4,'po_receipt','purchase_orders','20','Goods receipt against PO #20',10),(116,'2026-06-05 06:09:02',16,13,'po_receipt','purchase_orders','21','Goods receipt against PO #21',10),(117,'2026-05-27 06:09:02',19,13,'po_receipt','purchase_orders','21','Goods receipt against PO #21',10),(118,'2026-05-27 06:09:02',22,17,'po_receipt','purchase_orders','21','Goods receipt against PO #21',10),(119,'2026-05-31 06:09:02',25,23,'po_receipt','purchase_orders','21','Goods receipt against PO #21',10),(120,'2026-05-27 09:16:26',22,18,'po_receipt','purchase_orders','22','Goods receipt against PO #22',10),(121,'2026-06-03 09:16:26',25,24,'po_receipt','purchase_orders','22','Goods receipt against PO #22',10),(122,'2026-06-03 09:16:26',28,24,'po_receipt','purchase_orders','22','Goods receipt against PO #22',10),(123,'2026-05-21 06:51:23',28,20,'po_receipt','purchase_orders','23','Goods receipt against PO #23',10),(124,'2026-05-30 06:51:23',31,19,'po_receipt','purchase_orders','23','Goods receipt against PO #23',10),(125,'2026-04-06 05:50:37',34,24,'po_receipt','purchase_orders','24','Goods receipt against PO #24',10),(126,'2026-04-07 05:50:37',2,17,'po_receipt','purchase_orders','24','Goods receipt against PO #24',10),(127,'2026-04-05 05:50:37',5,19,'po_receipt','purchase_orders','24','Goods receipt against PO #24',10),(128,'2026-04-04 17:18:04',5,11,'po_receipt','purchase_orders','25','Goods receipt against PO #25',10),(129,'2026-04-21 12:07:29',11,25,'po_receipt','purchase_orders','26','Goods receipt against PO #26',10),(130,'2026-04-20 12:07:29',14,23,'po_receipt','purchase_orders','26','Goods receipt against PO #26',10),(131,'2026-04-06 05:26:10',17,18,'po_receipt','purchase_orders','27','Goods receipt against PO #27',10),(132,'2026-04-05 05:26:10',20,16,'po_receipt','purchase_orders','27','Goods receipt against PO #27',10),(133,'2026-04-03 05:26:10',23,8,'po_receipt','purchase_orders','27','Goods receipt against PO #27',10),(134,'2026-04-11 13:26:45',23,2,'po_receipt','purchase_orders','28','Goods receipt against PO #28',10),(135,'2026-04-14 14:28:56',29,8,'po_receipt','purchase_orders','29','Goods receipt against PO #29',10),(136,'2026-04-15 14:28:56',32,4,'po_receipt','purchase_orders','29','Goods receipt against PO #29',10),(137,'2026-04-13 14:28:56',35,12,'po_receipt','purchase_orders','29','Goods receipt against PO #29',10),(138,'2026-04-10 10:25:11',35,9,'po_receipt','purchase_orders','30','Goods receipt against PO #30',10),(139,'2026-04-10 10:25:11',3,22,'po_receipt','purchase_orders','30','Goods receipt against PO #30',10),(140,'2026-04-07 03:22:02',6,11,'po_receipt','purchase_orders','31','Goods receipt against PO #31',10),(141,'2026-04-10 03:22:02',9,18,'po_receipt','purchase_orders','31','Goods receipt against PO #31',10);
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_parts`
--

LOCK TABLES `inventory_parts` WRITE;
/*!40000 ALTER TABLE `inventory_parts` DISABLE KEYS */;
INSERT INTO `inventory_parts` VALUES (1,'Deep Groove Ball Bearing 6205-2RS','BRG-6205',NULL,42,12,14.50,NULL,'Deep Groove Ball Bearing 6205-2RS','SKF Authorised Partner BV','BRG9533',NULL,60,13,4,10,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R1','S1','A-01-01',1,2,NULL,NULL,'New','Phasing Out',NULL),(2,'Deep Groove Ball Bearing 6308-2RS','BRG-6308',NULL,6,10,38.90,NULL,'Deep Groove Ball Bearing 6308-2RS','SKF Authorised Partner BV','BRG9733',NULL,40,3,1,10,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R2','S2','A-02-02',1,2,NULL,NULL,'New','Active',NULL),(3,'Spherical Roller Bearing 22215','BRG-22215',NULL,3,4,187.00,NULL,'Spherical Roller Bearing 22215','SKF Authorised Partner BV','BRG9206',NULL,12,4,5,2,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R3','S3','A-03-03',1,2,NULL,NULL,'New','Active',NULL),(4,'Rotary Shaft Seal 45x62x8','SEA-4562',NULL,88,25,6.20,NULL,'Rotary Shaft Seal 45x62x8','Baltic Bearing & Seal','SEA6314',NULL,150,5,5,50,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R4','S4','A-04-04',1,5,NULL,NULL,'New','Active',NULL),(5,'O-Ring Kit NBR 70 (assorted)','SEA-ORK1',NULL,14,5,42.00,NULL,'O-Ring Kit NBR 70 (assorted)','Baltic Bearing & Seal','SEA9823',NULL,30,5,3,5,'kit','EUR',NULL,NULL,0,NULL,NULL,1,'A','R5','S1','A-05-01',0,5,NULL,NULL,'New','Active',NULL),(6,'Hydraulic Filter Element HF-320','FLT-HF320',NULL,9,12,64.75,NULL,'Hydraulic Filter Element HF-320','Hydratech Fluid Power','FLT3069',NULL,36,17,1,12,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R6','S2','A-06-02',1,6,NULL,NULL,'New','Active',NULL),(7,'Coolant Filter Bag 25 micron','FLT-CB25',NULL,60,20,9.10,NULL,'Coolant Filter Bag 25 micron','Nordwerk Industrial Supply','FLT1459',NULL,120,19,1,40,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R1','S3','A-01-03',1,1,NULL,NULL,'New','Active',NULL),(8,'Compressor Air Filter GA55','FLT-GA55',NULL,2,3,121.00,NULL,'Compressor Air Filter GA55','Atlas Pneumatic Group','FLT5644',NULL,12,20,3,3,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'A','R2','S4','A-02-04',1,4,NULL,NULL,'New','Active',NULL),(9,'Pneumatic Cylinder 32x100 ISO','PNU-32100',NULL,7,4,96.40,NULL,'Pneumatic Cylinder 32x100 ISO','Atlas Pneumatic Group','PNU5182',NULL,16,21,3,4,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R3','S1','B-03-01',0,4,NULL,NULL,'New','Active',NULL),(10,'Solenoid Valve 5/2 24VDC','PNU-SV52',NULL,18,8,73.20,NULL,'Solenoid Valve 5/2 24VDC','Atlas Pneumatic Group','PNU1068',NULL,40,12,2,10,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R4','S2','B-04-02',1,4,NULL,NULL,'New','Active',NULL),(11,'FRL Unit 1/2in with Regulator','PNU-FRL12',NULL,5,3,158.00,NULL,'FRL Unit 1/2in with Regulator','Atlas Pneumatic Group','PNU5103',NULL,10,10,5,2,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R5','S3','B-05-03',0,4,NULL,NULL,'New','Active',NULL),(12,'Servo Motor 1FK7 Replacement','ELC-1FK7',NULL,1,2,1480.00,NULL,'Servo Motor 1FK7 Replacement','Siemens Drive Services','ELC4197',NULL,4,7,3,1,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R6','S4','B-06-04',1,3,NULL,NULL,'New','Active',NULL),(13,'VFD 7.5kW 400V','ELC-VFD75',NULL,3,2,890.00,NULL,'VFD 7.5kW 400V','Siemens Drive Services','ELC3475',NULL,8,12,2,1,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R1','S1','B-01-01',1,3,NULL,NULL,'New','Active',NULL),(14,'Contactor 3RT 25A','ELC-3RT25',NULL,22,10,44.30,NULL,'Contactor 3RT 25A','Volt & Circuit Electricals','ELC3887',NULL,50,14,4,10,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R2','S2','B-02-02',1,7,NULL,NULL,'New','Active',NULL),(15,'Safety Relay PNOZ s5','ELC-PNOZ',NULL,6,4,212.00,NULL,'Safety Relay PNOZ s5','Volt & Circuit Electricals','ELC9993',NULL,12,3,4,2,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R3','S3','B-03-03',1,7,NULL,NULL,'New','Active',NULL),(16,'Proximity Sensor M12 PNP NO','ELC-PRX12',NULL,35,15,28.60,NULL,'Proximity Sensor M12 PNP NO','Volt & Circuit Electricals','ELC5931',NULL,80,14,2,20,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'B','R4','S4','B-04-04',1,7,NULL,NULL,'New','Active',NULL),(17,'Photoelectric Sensor Retro 10m','ELC-PHT10',NULL,0,6,61.40,NULL,'Photoelectric Sensor Retro 10m','Volt & Circuit Electricals','ELC5024',NULL,24,5,2,6,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R5','S1','C-05-01',1,7,NULL,NULL,'New','Active',NULL),(18,'E-Stop Mushroom Button 22mm','ELC-ESTOP',NULL,19,8,33.10,NULL,'E-Stop Mushroom Button 22mm','Volt & Circuit Electricals','ELC1037',NULL,40,18,5,10,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R6','S2','C-06-02',0,7,NULL,NULL,'New','Active',NULL),(19,'Timing Belt HTD 8M-1600-30','MEC-HTD16',NULL,11,6,78.90,NULL,'Timing Belt HTD 8M-1600-30','Nordwerk Industrial Supply','MEC9535',NULL,24,7,1,6,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R1','S3','C-01-03',1,1,NULL,NULL,'New','Active',NULL),(20,'V-Belt SPZ 1600','MEC-SPZ16',NULL,26,10,17.40,NULL,'V-Belt SPZ 1600','Nordwerk Industrial Supply','MEC7874',NULL,60,15,2,20,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R2','S4','C-02-04',1,1,NULL,NULL,'New','Active',NULL),(21,'Conveyor Belt PVC 600mm (per m)','MEC-CB600',NULL,35,15,52.00,NULL,'Conveyor Belt PVC 600mm (per m)','Nordwerk Industrial Supply','MEC9093',NULL,90,13,2,30,'m','EUR',NULL,NULL,0,NULL,NULL,1,'C','R3','S1','C-03-01',1,1,NULL,NULL,'New','Active',NULL),(22,'Drive Chain 12B-1 (per m)','MEC-DC12B',NULL,12,8,24.80,NULL,'Drive Chain 12B-1 (per m)','Nordwerk Industrial Supply','MEC4780',NULL,40,19,2,10,'m','EUR',NULL,NULL,0,NULL,NULL,1,'C','R4','S2','C-04-02',0,1,NULL,NULL,'New','Active',NULL),(23,'Linear Guide Block HGH25','MEC-HGH25',NULL,4,4,143.50,NULL,'Linear Guide Block HGH25','ToolCraft Precision Ltd','MEC1789',NULL,12,13,2,2,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R5','S3','C-05-03',1,8,NULL,NULL,'New','Active',NULL),(24,'Ball Screw Support Unit BK15','MEC-BK15',NULL,8,3,118.00,NULL,'Ball Screw Support Unit BK15','ToolCraft Precision Ltd','MEC9889',NULL,16,21,4,2,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'C','R6','S4','C-06-04',0,8,NULL,NULL,'New','Active',NULL),(25,'Carbide End Mill 12mm 4FL','TOL-EM12',NULL,30,12,47.90,NULL,'Carbide End Mill 12mm 4FL','ToolCraft Precision Ltd','TOL8880',NULL,80,17,1,20,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'D','R1','S1','D-01-01',1,8,NULL,NULL,'New','Active',NULL),(26,'Carbide Insert CNMG 120408','TOL-CN120',NULL,95,40,11.75,NULL,'Carbide Insert CNMG 120408','ToolCraft Precision Ltd','TOL1048',NULL,200,19,4,50,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'D','R2','S2','D-02-02',1,8,NULL,NULL,'New','Active',NULL),(27,'Collet ER32 12mm','TOL-ER32',NULL,16,6,38.00,NULL,'Collet ER32 12mm','ToolCraft Precision Ltd','TOL6387',NULL,30,21,5,6,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'D','R3','S3','D-03-03',0,8,NULL,NULL,'New','Active',NULL),(28,'Welding Torch Nozzle M8','TOL-WTN8',NULL,24,12,15.30,NULL,'Welding Torch Nozzle M8','Atlas Pneumatic Group','TOL4316',NULL,60,14,2,20,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'D','R4','S4','D-04-04',1,4,NULL,NULL,'New','Active',NULL),(29,'Weld Contact Tip 1.0mm','TOL-WCT10',NULL,150,60,2.85,NULL,'Weld Contact Tip 1.0mm','Atlas Pneumatic Group','TOL1299',NULL,400,14,3,100,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'D','R5','S1','D-05-01',1,4,NULL,NULL,'New','Active',NULL),(30,'Hydraulic Hose 3/8in R2 (per m)','HYD-H38',NULL,18,8,21.60,NULL,'Hydraulic Hose 3/8in R2 (per m)','Hydratech Fluid Power','HYD3848',NULL,50,6,3,10,'m','EUR',NULL,NULL,0,NULL,NULL,1,'D','R6','S2','D-06-02',1,6,NULL,NULL,'New','Active',NULL),(31,'Hydraulic Oil ISO VG46 (20L)','HYD-OIL46',NULL,7,4,96.00,NULL,'Hydraulic Oil ISO VG46 (20L)','Hydratech Fluid Power','HYD5230',NULL,20,15,1,4,'drum','EUR',NULL,NULL,0,NULL,NULL,1,'D','R1','S3','D-01-03',1,6,NULL,NULL,'New','Active',NULL),(32,'Way Lube ISO VG68 (5L)','HYD-WL68',NULL,13,6,41.20,NULL,'Way Lube ISO VG68 (5L)','Hydratech Fluid Power','HYD5187',NULL,30,12,4,6,'can','EUR',NULL,NULL,0,NULL,NULL,1,'D','R2','S4','D-02-04',0,6,NULL,NULL,'New','Active',NULL),(33,'Spindle Grease NLGI 2 (400g)','HYD-GRS2',NULL,20,8,27.50,NULL,'Spindle Grease NLGI 2 (400g)','SKF Authorised Partner BV','HYD1324',NULL,40,17,2,10,'tube','EUR',NULL,NULL,0,NULL,NULL,1,'E','R3','S1','E-03-01',1,2,NULL,NULL,'New','Active',NULL),(34,'Air Filter Panel G4 592x592','FAC-AFG4',NULL,24,12,18.90,NULL,'Air Filter Panel G4 592x592','Nordwerk Industrial Supply','FAC2481',NULL,60,21,2,24,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'E','R4','S2','E-04-02',1,1,NULL,NULL,'New','Active',NULL),(35,'LED High Bay 150W IP65','FAC-LED150',NULL,9,4,87.00,NULL,'LED High Bay 150W IP65','Volt & Circuit Electricals','FAC8412',NULL,20,3,5,4,'pcs','EUR',NULL,NULL,0,NULL,NULL,1,'E','R5','S3','E-05-03',0,7,NULL,NULL,'New','Active',NULL);
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
  `severity` varchar(10) NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'inventory','Low stock: Photoelectric Sensor Retro 10m at 0 (minimum 6)','/_logi/inventory.php','danger',0,'2026-07-17 14:44:12'),(2,1,'inventory','Low stock: Servo Motor 1FK7 Replacement at 1 (minimum 2)','/_logi/inventory.php','warning',0,'2026-07-22 14:53:51'),(3,1,'inventory','Low stock: Compressor Air Filter GA55 at 2 (minimum 3)','/_logi/inventory.php','warning',0,'2026-07-17 06:14:55'),(4,1,'inventory','Low stock: Spherical Roller Bearing 22215 at 3 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-21 17:23:55'),(5,1,'inventory','Low stock: Linear Guide Block HGH25 at 4 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-22 08:21:59'),(6,1,'procurement','4 purchase requests are waiting for your approval','/_logi/purchase_orders.php','warning',0,'2026-07-21 18:54:28'),(7,1,'work_order','3 work orders are past their scheduled date','/_maint/work_orders.php','danger',0,'2026-07-18 10:14:03'),(8,1,'ticket','New critical event registered on Okuma LB3000 EX II Lathe','/_maint/active_tickets.php','danger',0,'2026-07-16 06:02:23'),(9,1,'pm','2 PM schedules are overdue and need rescheduling','/_maint/pm_calendar.php','warning',0,'2026-07-22 09:04:55'),(10,1,'procurement','PO fully received and checked into stores','/_logi/purchase_orders.php','info',1,'2026-07-22 12:29:35'),(11,1,'ticket','Event closed: coolant pressure restored on CNC Cell 1','/_rpt/history.php','info',1,'2026-07-18 07:02:05'),(12,1,'system','Nightly backup completed successfully','/_mgmt/admin_backup.php','info',1,'2026-07-22 16:41:54'),(13,2,'inventory','Low stock: Photoelectric Sensor Retro 10m at 0 (minimum 6)','/_logi/inventory.php','danger',0,'2026-07-20 14:10:03'),(14,2,'inventory','Low stock: Servo Motor 1FK7 Replacement at 1 (minimum 2)','/_logi/inventory.php','warning',0,'2026-07-21 15:41:20'),(15,2,'inventory','Low stock: Compressor Air Filter GA55 at 2 (minimum 3)','/_logi/inventory.php','warning',0,'2026-07-16 12:10:53'),(16,2,'inventory','Low stock: Spherical Roller Bearing 22215 at 3 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-19 16:34:50'),(17,2,'inventory','Low stock: Linear Guide Block HGH25 at 4 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-22 16:04:01'),(18,2,'procurement','4 purchase requests are waiting for your approval','/_logi/purchase_orders.php','warning',0,'2026-07-18 10:48:40'),(19,2,'work_order','3 work orders are past their scheduled date','/_maint/work_orders.php','danger',0,'2026-07-20 17:24:16'),(20,2,'ticket','New critical event registered on Okuma LB3000 EX II Lathe','/_maint/active_tickets.php','danger',0,'2026-07-17 17:07:36'),(21,2,'pm','2 PM schedules are overdue and need rescheduling','/_maint/pm_calendar.php','warning',0,'2026-07-16 16:37:15'),(22,2,'procurement','PO fully received and checked into stores','/_logi/purchase_orders.php','info',1,'2026-07-20 11:16:50'),(23,2,'ticket','Event closed: coolant pressure restored on CNC Cell 1','/_rpt/history.php','info',1,'2026-07-18 18:45:05'),(24,2,'system','Nightly backup completed successfully','/_mgmt/admin_backup.php','info',1,'2026-07-17 08:56:41'),(25,10,'inventory','Low stock: Photoelectric Sensor Retro 10m at 0 (minimum 6)','/_logi/inventory.php','danger',0,'2026-07-16 07:24:17'),(26,10,'inventory','Low stock: Servo Motor 1FK7 Replacement at 1 (minimum 2)','/_logi/inventory.php','warning',0,'2026-07-20 11:29:33'),(27,10,'inventory','Low stock: Compressor Air Filter GA55 at 2 (minimum 3)','/_logi/inventory.php','warning',0,'2026-07-22 17:27:59'),(28,10,'inventory','Low stock: Spherical Roller Bearing 22215 at 3 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-19 04:34:12'),(29,10,'inventory','Low stock: Linear Guide Block HGH25 at 4 (minimum 4)','/_logi/inventory.php','warning',0,'2026-07-21 09:49:37'),(30,10,'procurement','4 purchase requests are waiting for your approval','/_logi/purchase_orders.php','warning',0,'2026-07-22 07:20:55'),(31,10,'procurement','PO fully received and checked into stores','/_logi/purchase_orders.php','info',1,'2026-07-18 07:21:33');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_checklist_items`
--

LOCK TABLES `pm_checklist_items` WRITE;
/*!40000 ALTER TABLE `pm_checklist_items` DISABLE KEYS */;
INSERT INTO `pm_checklist_items` VALUES (1,1,'Check and top spindle lubrication reservoir',10),(2,1,'Inspect way covers and wipers for damage',10),(3,1,'Clean chip conveyor and coolant tank strainer',25),(4,1,'Verify coolant concentration with refractometer',10),(5,1,'Check air pressure and drain FRL bowl',5),(6,1,'Inspect drag chains and cable routing',15),(7,1,'Run axis backlash check and record values',20),(8,1,'Verify E-stop and guard interlocks',10),(9,2,'Inspect belt for wear, cuts and tracking',15),(10,2,'Check and re-tension belt to spec',15),(11,2,'Grease head and tail pulley bearings',10),(12,2,'Inspect drive chain and sprocket wear',10),(13,2,'Verify emergency pull-cord operation',10),(14,3,'Inspect and clean robot arm and cabling',20),(15,3,'Check axis backlash against baseline',30),(16,3,'Verify brake test on all axes',20),(17,3,'Check gearbox oil level and condition',25),(18,3,'Confirm mastering and re-master if required',30),(19,3,'Test safety-rated monitored stop',15),(20,4,'Check and record discharge temperature',5),(21,4,'Inspect and replace air intake filter if loaded',20),(22,4,'Drain receiver and check autodrain operation',10),(23,4,'Check oil level and top if required',10),(24,4,'Leak survey on main header',30),(25,5,'Verify all E-stops halt motion within spec',30),(26,5,'Test guard interlocks, dual channel',30),(27,5,'Verify light curtain response time',25),(28,5,'Confirm LOTO points match documented protocol',20),(29,5,'Record results and sign off',15);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_checklists`
--

LOCK TABLES `pm_checklists` WRITE;
/*!40000 ALTER TABLE `pm_checklists` DISABLE KEYS */;
INSERT INTO `pm_checklists` VALUES (1,'CNC Machining Centre - Monthly PM','Monthly preventive routine for machining centres.','2025-10-31 18:09:53'),(2,'Conveyor - Quarterly PM','Quarterly routine for belt conveyors.','2025-09-29 17:10:21'),(3,'Industrial Robot - Semi-annual PM','Semi-annual routine for articulated robots.','2025-11-20 07:33:14'),(4,'Compressed Air System - Monthly PM','Monthly routine for compressor and air network.','2025-11-03 15:26:31'),(5,'Safety Systems - Annual Verification','Annual verification of safety functions.','2025-10-21 14:45:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_schedules`
--

LOCK TABLES `pm_schedules` WRITE;
/*!40000 ALTER TABLE `pm_schedules` DISABLE KEYS */;
INSERT INTO `pm_schedules` VALUES (1,'CNC Machining Centre - Monthly PM - DMG Mori NHX 5000 Machining Ctr','Scheduled preventive maintenance per OEM interval.',1,7,NULL,1,30,'2026-07-19','2025-10-19 15:04:23'),(2,'CNC Machining Centre - Monthly PM - Mazak VTC-800 Vertical Center','Scheduled preventive maintenance per OEM interval.',2,4,NULL,1,30,'2026-07-16','2025-12-12 06:17:28'),(3,'CNC Machining Centre - Monthly PM - Okuma LB3000 EX II Lathe','Scheduled preventive maintenance per OEM interval.',3,5,NULL,1,30,'2026-08-17','2025-11-09 11:38:18'),(4,'CNC Machining Centre - Monthly PM - Haas VF-4SS Mill','Scheduled preventive maintenance per OEM interval.',4,5,NULL,1,30,'2026-07-25','2025-10-25 13:26:49'),(5,'Conveyor - Quarterly PM - Assembly Conveyor A1 (Main)','Scheduled preventive maintenance per OEM interval.',11,7,NULL,2,90,'2026-09-17','2025-12-13 17:54:05'),(6,'Conveyor - Quarterly PM - Assembly Conveyor A2','Scheduled preventive maintenance per OEM interval.',15,4,NULL,2,90,'2026-07-24','2025-10-15 17:27:42'),(7,'Industrial Robot - Semi-annual PM - KUKA KR 60 Weld Robot','Scheduled preventive maintenance per OEM interval.',8,6,NULL,3,180,'2026-08-25','2025-11-21 06:10:31'),(8,'Industrial Robot - Semi-annual PM - FANUC M-20iD Pick and Place','Scheduled preventive maintenance per OEM interval.',14,7,NULL,3,180,'2026-08-20','2025-10-30 07:58:56'),(9,'Compressed Air System - Monthly PM - Atlas Copco GA 55 Compressor','Scheduled preventive maintenance per OEM interval.',21,5,NULL,4,30,'2026-09-13','2025-10-18 17:14:37'),(10,'Safety Systems - Annual Verification - Trumpf TruLaser 3030 Cutter','Scheduled preventive maintenance per OEM interval.',10,7,NULL,5,365,'2026-09-07','2025-12-31 15:05:59'),(11,'Safety Systems - Annual Verification - Servo Press Station A2','Scheduled preventive maintenance per OEM interval.',16,6,NULL,5,365,'2026-09-12','2025-12-12 06:25:38');
/*!40000 ALTER TABLE `pm_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_documents`
--

DROP TABLE IF EXISTS `po_documents`;
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

--
-- Dumping data for table `po_documents`
--

LOCK TABLES `po_documents` WRITE;
/*!40000 ALTER TABLE `po_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `po_documents` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_items`
--

LOCK TABLES `po_items` WRITE;
/*!40000 ALTER TABLE `po_items` DISABLE KEYS */;
INSERT INTO `po_items` VALUES (1,1,1,14,0,14.50,'EUR','Pending'),(2,1,4,25,0,6.20,'EUR','Pending'),(3,2,7,16,0,9.10,'EUR','Pending'),(4,2,10,15,0,73.20,'EUR','Pending'),(5,2,13,18,0,890.00,'EUR','Pending'),(6,2,16,8,0,28.60,'EUR','Pending'),(7,3,13,23,0,890.00,'EUR','Pending'),(8,3,16,8,0,28.60,'EUR','Pending'),(9,3,19,8,0,78.90,'EUR','Pending'),(10,3,22,7,0,24.80,'EUR','Pending'),(11,4,19,25,0,78.90,'EUR','Pending'),(12,5,25,23,0,47.90,'EUR','Pending'),(13,6,31,3,0,96.00,'EUR','Pending'),(14,6,34,21,0,18.90,'EUR','Pending'),(15,6,2,9,0,38.90,'EUR','Pending'),(16,7,2,9,0,38.90,'EUR','Pending'),(17,8,8,4,0,121.00,'EUR','Pending'),(18,8,11,5,0,158.00,'EUR','Pending'),(19,9,14,10,0,44.30,'EUR','Pending'),(20,9,17,11,0,61.40,'EUR','Pending'),(21,9,20,3,0,17.40,'EUR','Pending'),(22,10,20,14,0,17.40,'EUR','Pending'),(23,10,23,14,0,143.50,'EUR','Pending'),(24,10,26,6,0,11.75,'EUR','Pending'),(25,10,29,8,0,2.85,'EUR','Pending'),(26,11,26,4,0,11.75,'EUR','Pending'),(27,11,29,13,0,2.85,'EUR','Pending'),(28,12,32,6,0,41.20,'EUR','Pending'),(29,13,3,4,0,187.00,'EUR','Pending'),(30,13,6,9,0,64.75,'EUR','Pending'),(31,14,9,9,0,96.40,'EUR','Pending'),(32,14,12,20,0,1480.00,'EUR','Pending'),(33,14,15,12,0,212.00,'EUR','Pending'),(34,14,18,9,0,33.10,'EUR','Pending'),(35,15,15,7,0,212.00,'EUR','Pending'),(36,15,18,21,0,33.10,'EUR','Pending'),(37,16,21,21,0,52.00,'EUR','Pending'),(38,17,27,9,4,38.00,'EUR','Pending'),(39,17,30,8,4,21.60,'EUR','Pending'),(40,18,33,15,7,27.50,'EUR','Pending'),(41,18,1,16,8,14.50,'EUR','Pending'),(42,18,4,19,9,6.20,'EUR','Pending'),(43,18,7,13,6,9.10,'EUR','Pending'),(44,19,4,25,25,6.20,'EUR','Received'),(45,19,7,22,22,9.10,'EUR','Received'),(46,19,10,16,16,73.20,'EUR','Received'),(47,20,10,4,4,73.20,'EUR','Received'),(48,21,16,13,13,28.60,'EUR','Received'),(49,21,19,13,13,78.90,'EUR','Received'),(50,21,22,17,17,24.80,'EUR','Received'),(51,21,25,23,23,47.90,'EUR','Received'),(52,22,22,18,18,24.80,'EUR','Received'),(53,22,25,24,24,47.90,'EUR','Received'),(54,22,28,24,24,15.30,'EUR','Received'),(55,23,28,20,20,15.30,'EUR','Received'),(56,23,31,19,19,96.00,'EUR','Received'),(57,24,34,24,24,18.90,'EUR','Received'),(58,24,2,17,17,38.90,'EUR','Received'),(59,24,5,19,19,42.00,'EUR','Received'),(60,25,5,11,11,42.00,'EUR','Received'),(61,26,11,25,25,158.00,'EUR','Received'),(62,26,14,23,23,44.30,'EUR','Received'),(63,27,17,18,18,61.40,'EUR','Received'),(64,27,20,16,16,17.40,'EUR','Received'),(65,27,23,8,8,143.50,'EUR','Received'),(66,28,23,2,2,143.50,'EUR','Received'),(67,29,29,8,8,2.85,'EUR','Received'),(68,29,32,4,4,41.20,'EUR','Received'),(69,29,35,12,12,87.00,'EUR','Received'),(70,30,35,9,9,87.00,'EUR','Received'),(71,30,3,22,22,187.00,'EUR','Received'),(72,31,6,11,11,64.75,'EUR','Received'),(73,31,9,18,18,96.40,'EUR','Received'),(74,32,12,16,0,1480.00,'EUR','Pending'),(75,32,15,16,0,212.00,'EUR','Pending'),(76,32,18,15,0,33.10,'EUR','Pending'),(77,33,18,6,0,33.10,'EUR','Pending'),(78,33,21,21,0,52.00,'EUR','Pending'),(79,33,24,17,0,118.00,'EUR','Pending');
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

--
-- Dumping data for table `po_status_logs`
--

LOCK TABLES `po_status_logs` WRITE;
/*!40000 ALTER TABLE `po_status_logs` DISABLE KEYS */;
INSERT INTO `po_status_logs` VALUES (1,1,'created',NULL,'Draft','Purchase request raised.',1,'2026-07-15 09:07:31'),(2,2,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-17 17:23:31'),(3,3,'created',NULL,'Draft','Purchase request raised.',10,'2026-07-12 18:48:04'),(4,3,'status_change','Draft','Pending Approval',NULL,2,'2026-07-13 18:48:04'),(5,4,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-15 15:17:20'),(6,4,'status_change','Draft','Pending Approval',NULL,2,'2026-07-17 15:17:20'),(7,5,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-21 03:27:56'),(8,5,'status_change','Draft','Pending Approval',NULL,10,'2026-07-22 03:27:56'),(9,6,'created',NULL,'Draft','Purchase request raised.',10,'2026-07-08 06:32:20'),(10,6,'status_change','Draft','Pending Approval',NULL,2,'2026-07-11 06:32:20'),(11,7,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-07 07:15:22'),(12,7,'status_change','Draft','Pending Approval',NULL,1,'2026-07-09 07:15:22'),(13,7,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',1,'2026-07-13 07:15:22'),(14,8,'created',NULL,'Draft','Purchase request raised.',10,'2026-07-07 09:41:22'),(15,8,'status_change','Draft','Pending Approval',NULL,10,'2026-07-10 09:41:22'),(16,8,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',2,'2026-07-12 09:41:22'),(17,9,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-12 12:58:04'),(18,9,'status_change','Draft','Pending Approval',NULL,10,'2026-07-14 12:58:04'),(19,9,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',1,'2026-07-18 12:58:04'),(20,10,'created',NULL,'Draft','Purchase request raised.',10,'2026-07-02 10:54:53'),(21,10,'status_change','Draft','Pending Approval',NULL,1,'2026-07-05 10:54:53'),(22,10,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-07-06 10:54:53'),(23,11,'created',NULL,'Draft','Purchase request raised.',2,'2026-07-08 10:14:00'),(24,11,'status_change','Draft','Pending Approval',NULL,2,'2026-07-12 10:14:00'),(25,11,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',2,'2026-07-17 10:14:00'),(26,11,'status_change','Issued','Shipped',NULL,1,'2026-07-22 10:14:00'),(27,12,'created',NULL,'Draft','Purchase request raised.',1,'2026-06-20 15:29:19'),(28,12,'status_change','Draft','Pending Approval',NULL,1,'2026-06-21 15:29:19'),(29,12,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',2,'2026-06-24 15:29:19'),(30,12,'status_change','Issued','Shipped',NULL,2,'2026-06-28 15:29:19'),(31,13,'created',NULL,'Draft','Purchase request raised.',1,'2026-06-22 06:51:07'),(32,13,'status_change','Draft','Pending Approval',NULL,10,'2026-06-25 06:51:07'),(33,13,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',1,'2026-06-27 06:51:07'),(34,13,'status_change','Issued','Shipped',NULL,10,'2026-06-30 06:51:07'),(35,14,'created',NULL,'Draft','Purchase request raised.',2,'2026-06-17 16:44:02'),(36,14,'status_change','Draft','Pending Approval',NULL,10,'2026-06-20 16:44:02'),(37,14,'status_change','Pending Approval','Issued','Approved by Plant Director.',10,'2026-06-24 16:44:02'),(38,14,'status_change','Issued','Shipped',NULL,10,'2026-06-25 16:44:02'),(39,14,'status_change','Shipped','In Transit',NULL,2,'2026-06-27 16:44:02'),(40,15,'created',NULL,'Draft','Purchase request raised.',2,'2026-06-25 09:13:39'),(41,15,'status_change','Draft','Pending Approval',NULL,1,'2026-06-30 09:13:39'),(42,15,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',2,'2026-07-01 09:13:39'),(43,15,'status_change','Issued','Shipped',NULL,2,'2026-07-02 09:13:39'),(44,15,'status_change','Shipped','In Transit',NULL,2,'2026-07-05 09:13:39'),(45,16,'created',NULL,'Draft','Purchase request raised.',1,'2026-06-15 08:01:39'),(46,16,'status_change','Draft','Pending Approval',NULL,1,'2026-06-18 08:01:39'),(47,16,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',1,'2026-06-19 08:01:39'),(48,16,'status_change','Issued','Shipped',NULL,1,'2026-06-24 08:01:39'),(49,16,'status_change','Shipped','In Transit',NULL,1,'2026-06-29 08:01:39'),(50,17,'created',NULL,'Draft','Purchase request raised.',2,'2026-06-16 09:42:53'),(51,17,'status_change','Draft','Pending Approval',NULL,1,'2026-06-19 09:42:53'),(52,17,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',1,'2026-06-24 09:42:53'),(53,17,'status_change','Issued','Shipped',NULL,1,'2026-06-27 09:42:53'),(54,17,'status_change','Shipped','In Transit',NULL,10,'2026-06-28 09:42:53'),(55,17,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-06-29 09:42:53'),(56,18,'created',NULL,'Draft','Purchase request raised.',1,'2026-06-08 15:30:55'),(57,18,'status_change','Draft','Pending Approval',NULL,1,'2026-06-09 15:30:55'),(58,18,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',2,'2026-06-10 15:30:55'),(59,18,'status_change','Issued','Shipped',NULL,2,'2026-06-11 15:30:55'),(60,18,'status_change','Shipped','In Transit',NULL,1,'2026-06-16 15:30:55'),(61,18,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',1,'2026-06-17 15:30:55'),(62,19,'created',NULL,'Draft','Purchase request raised.',2,'2026-06-01 16:06:29'),(63,19,'status_change','Draft','Pending Approval',NULL,2,'2026-06-05 16:06:29'),(64,19,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-06-09 16:06:29'),(65,19,'status_change','Issued','Shipped',NULL,10,'2026-06-12 16:06:29'),(66,19,'status_change','Shipped','In Transit',NULL,2,'2026-06-16 16:06:29'),(67,19,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-06-19 16:06:29'),(68,19,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',2,'2026-06-21 16:06:29'),(69,20,'created',NULL,'Draft','Purchase request raised.',2,'2026-05-21 14:06:03'),(70,20,'status_change','Draft','Pending Approval',NULL,1,'2026-05-22 14:06:03'),(71,20,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',10,'2026-05-24 14:06:03'),(72,20,'status_change','Issued','Shipped',NULL,10,'2026-05-25 14:06:03'),(73,20,'status_change','Shipped','In Transit',NULL,2,'2026-05-29 14:06:03'),(74,20,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-05-31 14:06:03'),(75,20,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-06-05 14:06:03'),(76,21,'created',NULL,'Draft','Purchase request raised.',10,'2026-05-24 06:09:02'),(77,21,'status_change','Draft','Pending Approval',NULL,10,'2026-05-28 06:09:02'),(78,21,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-06-02 06:09:02'),(79,21,'status_change','Issued','Shipped',NULL,2,'2026-06-04 06:09:02'),(80,21,'status_change','Shipped','In Transit',NULL,2,'2026-06-09 06:09:02'),(81,21,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',2,'2026-06-13 06:09:02'),(82,21,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-06-16 06:09:02'),(83,22,'created',NULL,'Draft','Purchase request raised.',1,'2026-05-22 09:16:26'),(84,22,'status_change','Draft','Pending Approval',NULL,2,'2026-05-27 09:16:26'),(85,22,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',1,'2026-05-30 09:16:26'),(86,22,'status_change','Issued','Shipped',NULL,2,'2026-06-04 09:16:26'),(87,22,'status_change','Shipped','In Transit',NULL,2,'2026-06-09 09:16:26'),(88,22,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',2,'2026-06-12 09:16:26'),(89,22,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',10,'2026-06-17 09:16:26'),(90,23,'created',NULL,'Draft','Purchase request raised.',10,'2026-05-18 06:51:23'),(91,23,'status_change','Draft','Pending Approval',NULL,2,'2026-05-20 06:51:23'),(92,23,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-05-24 06:51:23'),(93,23,'status_change','Issued','Shipped',NULL,1,'2026-05-27 06:51:23'),(94,23,'status_change','Shipped','In Transit',NULL,2,'2026-06-01 06:51:23'),(95,23,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',2,'2026-06-04 06:51:23'),(96,23,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-06-06 06:51:23'),(97,24,'created',NULL,'Draft','Purchase request raised.',1,'2026-04-01 05:50:37'),(98,24,'status_change','Draft','Pending Approval',NULL,1,'2026-04-06 05:50:37'),(99,24,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-04-10 05:50:37'),(100,24,'status_change','Issued','Shipped',NULL,10,'2026-04-13 05:50:37'),(101,24,'status_change','Shipped','In Transit',NULL,1,'2026-04-18 05:50:37'),(102,24,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-04-22 05:50:37'),(103,24,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',2,'2026-04-24 05:50:37'),(104,24,'status_change','Fully Received','Closed','Invoice matched, order closed.',10,'2026-04-29 05:50:37'),(105,25,'created',NULL,'Draft','Purchase request raised.',2,'2026-03-27 17:18:04'),(106,25,'status_change','Draft','Pending Approval',NULL,2,'2026-03-30 17:18:04'),(107,25,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',10,'2026-04-03 17:18:04'),(108,25,'status_change','Issued','Shipped',NULL,10,'2026-04-06 17:18:04'),(109,25,'status_change','Shipped','In Transit',NULL,2,'2026-04-07 17:18:04'),(110,25,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-04-09 17:18:04'),(111,25,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',2,'2026-04-14 17:18:04'),(112,25,'status_change','Fully Received','Closed','Invoice matched, order closed.',2,'2026-04-17 17:18:04'),(113,26,'created',NULL,'Draft','Purchase request raised.',1,'2026-04-10 12:07:29'),(114,26,'status_change','Draft','Pending Approval',NULL,10,'2026-04-11 12:07:29'),(115,26,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',10,'2026-04-16 12:07:29'),(116,26,'status_change','Issued','Shipped',NULL,10,'2026-04-18 12:07:29'),(117,26,'status_change','Shipped','In Transit',NULL,1,'2026-04-19 12:07:29'),(118,26,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',2,'2026-04-21 12:07:29'),(119,26,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-04-26 12:07:29'),(120,26,'status_change','Fully Received','Closed','Invoice matched, order closed.',1,'2026-04-28 12:07:29'),(121,27,'created',NULL,'Draft','Purchase request raised.',1,'2026-03-27 05:26:10'),(122,27,'status_change','Draft','Pending Approval',NULL,10,'2026-03-29 05:26:10'),(123,27,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',2,'2026-03-31 05:26:10'),(124,27,'status_change','Issued','Shipped',NULL,1,'2026-04-02 05:26:10'),(125,27,'status_change','Shipped','In Transit',NULL,2,'2026-04-05 05:26:10'),(126,27,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-04-08 05:26:10'),(127,27,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',2,'2026-04-10 05:26:10'),(128,27,'status_change','Fully Received','Closed','Invoice matched, order closed.',2,'2026-04-11 05:26:10'),(129,28,'created',NULL,'Draft','Purchase request raised.',10,'2026-03-30 13:26:45'),(130,28,'status_change','Draft','Pending Approval',NULL,1,'2026-04-03 13:26:45'),(131,28,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',10,'2026-04-07 13:26:45'),(132,28,'status_change','Issued','Shipped',NULL,10,'2026-04-12 13:26:45'),(133,28,'status_change','Shipped','In Transit',NULL,2,'2026-04-13 13:26:45'),(134,28,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',1,'2026-04-18 13:26:45'),(135,28,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-04-19 13:26:45'),(136,28,'status_change','Fully Received','Closed','Invoice matched, order closed.',1,'2026-04-20 13:26:45'),(137,29,'created',NULL,'Draft','Purchase request raised.',10,'2026-04-05 14:28:56'),(138,29,'status_change','Draft','Pending Approval',NULL,10,'2026-04-08 14:28:56'),(139,29,'status_change','Pending Approval','Issued','Auto-approved: total within the configured approval limit.',10,'2026-04-12 14:28:56'),(140,29,'status_change','Issued','Shipped',NULL,2,'2026-04-14 14:28:56'),(141,29,'status_change','Shipped','In Transit',NULL,10,'2026-04-16 14:28:56'),(142,29,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',1,'2026-04-19 14:28:56'),(143,29,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',2,'2026-04-22 14:28:56'),(144,29,'status_change','Fully Received','Closed','Invoice matched, order closed.',1,'2026-04-25 14:28:56'),(145,30,'created',NULL,'Draft','Purchase request raised.',2,'2026-03-29 10:25:11'),(146,30,'status_change','Draft','Pending Approval',NULL,10,'2026-03-31 10:25:11'),(147,30,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',1,'2026-04-04 10:25:11'),(148,30,'status_change','Issued','Shipped',NULL,1,'2026-04-08 10:25:11'),(149,30,'status_change','Shipped','In Transit',NULL,2,'2026-04-11 10:25:11'),(150,30,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-04-15 10:25:11'),(151,30,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',10,'2026-04-20 10:25:11'),(152,30,'status_change','Fully Received','Closed','Invoice matched, order closed.',1,'2026-04-23 10:25:11'),(153,31,'created',NULL,'Draft','Purchase request raised.',2,'2026-04-04 03:22:02'),(154,31,'status_change','Draft','Pending Approval',NULL,1,'2026-04-09 03:22:02'),(155,31,'status_change','Pending Approval','Issued','Approved by Maintenance Manager.',2,'2026-04-13 03:22:02'),(156,31,'status_change','Issued','Shipped',NULL,10,'2026-04-15 03:22:02'),(157,31,'status_change','Shipped','In Transit',NULL,2,'2026-04-19 03:22:02'),(158,31,'status_change','In Transit','Partially Received','Part shipment received, balance outstanding.',10,'2026-04-22 03:22:02'),(159,31,'status_change','Partially Received','Fully Received','All lines received and checked into stores.',1,'2026-04-26 03:22:02'),(160,31,'status_change','Fully Received','Closed','Invoice matched, order closed.',1,'2026-04-27 03:22:02'),(161,32,'created',NULL,'Draft','Purchase request raised.',2,'2026-05-08 09:56:22'),(162,32,'status_change','Draft','Pending Approval',NULL,10,'2026-05-09 09:56:22'),(163,32,'status_change','Pending Approval','Cancelled','Cancelled - requirement covered from stock.',1,'2026-05-13 09:56:22'),(164,33,'created',NULL,'Draft','Purchase request raised.',2,'2026-05-14 13:15:47'),(165,33,'status_change','Draft','Pending Approval',NULL,2,'2026-05-18 13:15:47'),(166,33,'status_change','Pending Approval','Cancelled','Cancelled - requirement covered from stock.',1,'2026-05-20 13:15:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_lines`
--

LOCK TABLES `production_lines` WRITE;
/*!40000 ALTER TABLE `production_lines` DISABLE KEYS */;
INSERT INTO `production_lines` VALUES (1,1,'CNC Cell 1','Hydraulic manifolds, valve bodies','Active'),(2,1,'CNC Cell 2','Pump housings, flange sets','Active'),(3,1,'Fabrication Line','Weldments, frames, brackets','Active'),(4,2,'Assembly Line 1','Pump assemblies (series 400)','Active'),(5,2,'Assembly Line 2','Actuator modules','Active'),(6,2,'Packaging Line','Palletised finished goods','Active');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PR-20260715-1001',8,2,1,358.00,'Draft','Auto-Approved',0,'2026-07-15 09:07:31'),(2,'PR-20260717-1002',5,1,2,17492.40,'Draft','Plant Director',0,'2026-07-17 17:23:31'),(3,'PR-20260712-1003',3,1,3,21503.60,'Pending Approval','Plant Director',0,'2026-07-12 18:48:04'),(4,'PR-20260715-1004',7,2,4,1972.50,'Pending Approval','Maintenance Manager',0,'2026-07-15 15:17:20'),(5,'PR-20260721-1005',2,1,5,1101.70,'Pending Approval','Auto-Approved',0,'2026-07-21 03:27:56'),(6,'PR-20260708-1006',8,1,1,1035.00,'Pending Approval','Auto-Approved',0,'2026-07-08 06:32:20'),(7,'PR-20260707-1007',7,2,2,350.10,'Issued','Auto-Approved',1,'2026-07-07 07:15:22'),(8,'PR-20260707-1008',6,10,3,1274.00,'Issued','Auto-Approved',0,'2026-07-07 09:41:22'),(9,'PR-20260712-1009',2,1,4,1170.60,'Issued','Auto-Approved',0,'2026-07-12 12:58:04'),(10,'PR-20260702-1010',5,1,5,2345.90,'Issued','Maintenance Manager',0,'2026-07-02 10:54:53'),(11,'PR-20260708-1011',6,10,1,84.05,'Shipped','Auto-Approved',0,'2026-07-08 10:14:00'),(12,'PR-20260620-1012',1,2,2,247.20,'Shipped','Auto-Approved',0,'2026-06-20 15:29:19'),(13,'PR-20260622-1013',5,1,3,1330.75,'Shipped','Auto-Approved',0,'2026-06-22 06:51:07'),(14,'PR-20260617-1014',3,10,4,33309.50,'In Transit','Plant Director',0,'2026-06-17 16:44:02'),(15,'PR-20260625-1015',8,10,5,2179.10,'In Transit','Maintenance Manager',0,'2026-06-25 09:13:39'),(16,'PR-20260615-1016',1,2,1,1092.00,'In Transit','Auto-Approved',0,'2026-06-15 08:01:39'),(17,'PR-20260616-1017',2,1,2,514.80,'Partially Received','Auto-Approved',0,'2026-06-16 09:42:53'),(18,'PR-20260608-1018',7,10,3,880.60,'Partially Received','Auto-Approved',0,'2026-06-08 15:30:55'),(19,'PR-20260601-1019',3,1,4,1526.40,'Fully Received','Maintenance Manager',0,'2026-06-01 16:06:29'),(20,'PR-20260521-1020',1,1,5,292.80,'Fully Received','Auto-Approved',0,'2026-05-21 14:06:03'),(21,'PR-20260524-1021',6,10,1,2920.80,'Fully Received','Maintenance Manager',0,'2026-05-24 06:09:02'),(22,'PR-20260522-1022',1,10,2,1963.20,'Fully Received','Maintenance Manager',0,'2026-05-22 09:16:26'),(23,'PR-20260518-1023',5,10,3,2130.00,'Fully Received','Maintenance Manager',0,'2026-05-18 06:51:23'),(24,'PR-20260401-1024',8,1,4,1912.90,'Closed','Maintenance Manager',0,'2026-04-01 05:50:37'),(25,'PR-20260327-1025',5,1,5,462.00,'Closed','Auto-Approved',0,'2026-03-27 17:18:04'),(26,'PR-20260410-1026',7,2,1,4968.90,'Closed','Maintenance Manager',0,'2026-04-10 12:07:29'),(27,'PR-20260327-1027',8,1,2,2531.60,'Closed','Maintenance Manager',0,'2026-03-27 05:26:10'),(28,'PR-20260330-1028',2,1,3,287.00,'Closed','Auto-Approved',0,'2026-03-30 13:26:45'),(29,'PR-20260405-1029',5,1,4,1231.60,'Closed','Auto-Approved',0,'2026-04-05 14:28:56'),(30,'PR-20260329-1030',6,1,5,4897.00,'Closed','Maintenance Manager',0,'2026-03-29 10:25:11'),(31,'PR-20260404-1031',2,1,1,2447.45,'Closed','Maintenance Manager',0,'2026-04-04 03:22:02'),(32,'PR-20260508-1032',8,2,2,27568.50,'Cancelled','Plant Director',0,'2026-05-08 09:56:22'),(33,'PR-20260514-1033',6,10,3,3296.60,'Cancelled','Maintenance Manager',0,'2026-05-14 13:15:47');
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
INSERT INTO `role_definitions` VALUES (1,'Operator','{\"view_tickets\": true, \"create_tickets\": true, \"takeover_tickets\": false, \"closeout_tickets\": false, \"view_history\": true, \"view_statistics\": false, \"view_equipment\": true, \"view_inventory\": false, \"view_vendors\": false, \"view_work_orders\": false, \"manage_work_orders\": false, \"view_purchase_requests\": false, \"create_purchase_requests\": false, \"approve_purchase_orders\": false, \"manage_users\": false, \"manage_settings\": false, \"manage_equipment\": false, \"manage_inventory\": false, \"manage_vendors\": false, \"reset_passwords\": false, \"delete_users\": false, \"fulfill_purchase_orders\": false}','2026-07-18 23:42:14'),(2,'Technician','{\"view_tickets\": true, \"create_tickets\": true, \"takeover_tickets\": true, \"closeout_tickets\": false, \"view_history\": true, \"view_statistics\": false, \"view_equipment\": true, \"view_inventory\": true, \"view_vendors\": true, \"view_work_orders\": true, \"manage_work_orders\": false, \"view_purchase_requests\": true, \"create_purchase_requests\": true, \"approve_purchase_orders\": false, \"manage_users\": false, \"manage_settings\": false, \"manage_equipment\": false, \"manage_inventory\": false, \"manage_vendors\": false, \"reset_passwords\": false, \"delete_users\": false, \"fulfill_purchase_orders\": false}','2026-07-18 23:42:14'),(3,'Supervisor','{\"view_tickets\": true, \"create_tickets\": true, \"takeover_tickets\": true, \"closeout_tickets\": true, \"view_history\": true, \"view_statistics\": true, \"view_equipment\": true, \"view_inventory\": true, \"view_vendors\": true, \"view_work_orders\": true, \"manage_work_orders\": true, \"view_purchase_requests\": true, \"create_purchase_requests\": true, \"approve_purchase_orders\": false, \"manage_users\": false, \"manage_settings\": false, \"manage_equipment\": true, \"manage_inventory\": false, \"manage_vendors\": false, \"reset_passwords\": false, \"delete_users\": false, \"fulfill_purchase_orders\": false}','2026-07-18 23:42:14'),(4,'Admin','{\"view_tickets\": true, \"create_tickets\": true, \"takeover_tickets\": true, \"closeout_tickets\": true, \"view_history\": true, \"view_statistics\": true, \"view_equipment\": true, \"view_inventory\": true, \"view_vendors\": true, \"view_work_orders\": true, \"manage_work_orders\": true, \"view_purchase_requests\": true, \"create_purchase_requests\": true, \"approve_purchase_orders\": true, \"manage_users\": true, \"manage_settings\": true, \"manage_equipment\": true, \"manage_inventory\": true, \"manage_vendors\": true, \"reset_passwords\": true, \"delete_users\": true, \"fulfill_purchase_orders\": true}','2026-07-18 23:42:14'),(5,'Custom Viewer','[]','2026-07-13 04:12:18'),(6,'Storekeeper','{\"view_tickets\":false,\"create_tickets\":false,\"takeover_tickets\":false,\"closeout_tickets\":false,\"view_history\":false,\"view_statistics\":false,\"view_equipment\":true,\"view_inventory\":true,\"view_vendors\":true,\"view_work_orders\":false,\"manage_work_orders\":false,\"view_purchase_requests\":true,\"create_purchase_requests\":true,\"approve_purchase_orders\":false,\"fulfill_purchase_orders\":true,\"manage_users\":false,\"manage_settings\":false,\"manage_equipment\":false,\"manage_inventory\":true,\"manage_vendors\":false,\"reset_passwords\":false,\"delete_users\":false}','2026-07-18 23:42:30');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES (1,'0001_create_schema_migrations_table.sql','2026-07-12 19:12:25'),(2,'0002_add_closed_by_to_active_tickets.sql','2026-07-12 19:12:25'),(3,'0003_add_theme_prefs_json_to_users.sql','2026-07-12 19:12:25'),(4,'0004_create_audit_log_table.sql','2026-07-12 19:17:19'),(5,'0005_add_soft_delete_columns.sql','2026-07-12 19:18:02'),(6,'0006_create_inventory_ledger.sql','2026-07-12 19:18:41'),(7,'0007_enhance_users_table.sql','2026-07-14 18:16:20'),(8,'0008_add_badge_number_and_registration_config.sql','2026-07-14 18:16:20'),(9,'0010_create_equipment_documents.sql','2026-07-14 18:16:20'),(10,'0011_add_api_key_to_users.sql','2026-07-18 13:38:21'),(11,'0012_po_comments_and_documents.sql','2026-07-18 18:09:18'),(12,'0013_procurement_workflow.sql','2026-07-18 23:44:01'),(13,'0007_create_skill_automation_config.sql','2026-07-19 08:45:33'),(14,'0014_add_admin_layout_json_to_users.sql','2026-07-19 08:45:33'),(15,'0015_create_notifications.sql','2026-07-21 17:30:48');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_audit_logs`
--

LOCK TABLES `system_audit_logs` WRITE;
/*!40000 ALTER TABLE `system_audit_logs` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_directory`
--

LOCK TABLES `team_directory` WRITE;
/*!40000 ALTER TABLE `team_directory` DISABLE KEYS */;
INSERT INTO `team_directory` VALUES (1,'Alex Rivera','Manager',1,'2025-09-19 16:52:05'),(2,'Priya Nair','Supervisor',1,'2025-06-24 03:44:35'),(3,'Marc Dubois','Supervisor',1,'2025-09-25 07:19:18'),(4,'Jide Okafor','Technician',1,'2025-07-07 05:35:01'),(5,'Sara Lindqvist','Technician',1,'2025-09-02 16:08:52'),(6,'Taro Yamamoto','Technician',1,'2025-07-09 11:32:38'),(7,'Katerina Novak','Technician',1,'2025-07-25 18:51:26'),(8,'Rui Silva','Operator',1,'2025-07-16 11:21:54'),(9,'Elise Moreau','Operator',1,'2025-08-28 06:58:25'),(10,'Hendrik Bakker','Storekeeper',1,'2025-06-29 12:12:04'),(11,'Claire Whitfield','Viewer',1,'2025-06-19 15:10:56');
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
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_actions`
--

LOCK TABLES `ticket_actions` WRITE;
/*!40000 ALTER TABLE `ticket_actions` DISABLE KEYS */;
INSERT INTO `ticket_actions` VALUES (1,'TK-260112-005','Sara Lindqvist','2026-01-12 20:56:34',NULL,'Mechanical','Tail pulley bearing seized, belt pulling right','Diagnosis in progress - tail pulley bearing seized, belt pulling right. Awaiting spare part.','',NULL,'2026-01-12 18:56:34'),(2,'TK-260308-006','Sara Lindqvist','2026-03-08 10:34:43',NULL,'Electrical','Axis 3 brake contactor welded closed','Diagnosis in progress - axis 3 brake contactor welded closed. Awaiting spare part.','',NULL,'2026-03-08 08:34:43'),(3,'TK-260620-007','Jide Okafor','2026-06-20 19:22:21',NULL,'Process','Contact tip worn and shielding gas flow low','Diagnosis in progress - contact tip worn and shielding gas flow low. Awaiting spare part.','',NULL,'2026-06-20 16:22:21'),(4,'TK-251201-008','Jide Okafor','2025-12-01 17:16:57',NULL,'Mechanical','Sheet not seated, height sensor calibration drifted','Diagnosis in progress - sheet not seated, height sensor calibration drifted. Awaiting spare part.','',NULL,'2025-12-01 15:16:57'),(5,'TK-260406-009','Taro Yamamoto','2026-04-06 08:12:12',NULL,'Hydraulic','Main cylinder seal blown','Diagnosis in progress - main cylinder seal blown. Awaiting spare part.','',NULL,'2026-04-06 05:12:12'),(6,'TK-260315-010','Taro Yamamoto','2026-03-15 15:36:53','2026-03-15 18:09:53','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','VFD 7.5kW 400V x1',NULL,'2026-03-15 16:09:53'),(7,'TK-260722-011','Jide Okafor','2026-07-22 20:33:43','2026-07-22 21:35:43','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','E-Stop Mushroom Button 22mm x1',NULL,'2026-07-22 18:35:43'),(8,'TK-260527-012','Taro Yamamoto','2026-05-27 09:09:06','2026-05-27 10:54:06','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','',NULL,'2026-05-27 07:54:06'),(9,'TK-260103-013','Taro Yamamoto','2026-01-03 10:08:58','2026-01-03 11:26:58','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','Welding Torch Nozzle M8 x2',NULL,'2026-01-03 09:26:58'),(10,'TK-251030-014','Sara Lindqvist','2025-10-30 16:51:50','2025-10-30 18:56:50','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','Spindle Grease NLGI 2 (400g) x2',NULL,'2025-10-30 16:56:50'),(11,'TK-260606-015','Taro Yamamoto','2026-06-06 06:26:19','2026-06-06 07:28:19','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','Spherical Roller Bearing 22215 x1',NULL,'2026-06-06 04:28:19'),(12,'TK-251113-016','Sara Lindqvist','2025-11-13 12:53:09','2025-11-13 13:56:09','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','Compressor Air Filter GA55 x1',NULL,'2025-11-13 11:56:09'),(13,'TK-260408-017','Sara Lindqvist','2026-04-08 16:06:12','2026-04-08 19:37:12','Utilities','Cooler matrix fouled, ambient extraction restricted','Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C','VFD 7.5kW 400V x1',NULL,'2026-04-08 16:37:12'),(14,'TK-260719-018','Sara Lindqvist','2026-07-19 18:13:19','2026-07-19 20:34:19','Utilities','Glycol concentration low and strainer partially blocked','Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K','E-Stop Mushroom Button 22mm x3',NULL,'2026-07-19 17:34:19'),(15,'TK-260516-019','Sara Lindqvist','2026-05-16 20:39:53','2026-05-16 22:43:53','Electrical','Limit switch contact oxidised','Replaced limit switch, tested overtravel stop, load tested at 5T','',NULL,'2026-05-16 19:43:53'),(16,'TK-260611-020','Sara Lindqvist','2026-06-11 23:18:04','2026-06-12 00:57:04','Mechanical','Filter cartridges loaded, pulse valve not firing','Replaced pulse valve diaphragm, cleaned cartridges, suction restored','Welding Torch Nozzle M8 x3',NULL,'2026-06-11 21:57:04'),(17,'TK-260308-021','Katerina Novak','2026-03-08 18:57:33','2026-03-08 20:35:33','Hydraulic','Oil level low, suction strainer drawing air','Topped VG46, replaced suction strainer, bled system','Spindle Grease NLGI 2 (400g) x2',NULL,'2026-03-08 18:35:33'),(18,'TK-260714-022','Jide Okafor','2026-07-14 10:09:24','2026-07-14 12:17:24','Safety','Safety relay channel fault after door impact','Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1','Spherical Roller Bearing 22215 x2',NULL,'2026-07-14 09:17:24'),(19,'TK-260405-023','Katerina Novak','2026-04-05 10:25:19','2026-04-05 14:44:19','Mechanical','Spindle bearing grease degraded past service life','Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve','Compressor Air Filter GA55 x1',NULL,'2026-04-05 11:44:19'),(20,'TK-251112-024','Sara Lindqvist','2025-11-12 09:47:11','2025-11-12 11:03:11','Hydraulic','Coolant filter blocked with fine swarf','Replaced filter bag, flushed lines, restored 4.2 bar at nozzle','VFD 7.5kW 400V x2',NULL,'2025-11-12 09:03:11'),(21,'TK-260529-025','Katerina Novak','2026-05-29 11:29:31','2026-05-29 14:39:31','Electrical','Encoder cable chafed inside drag chain','Replaced encoder cable, added protective sleeve, re-homed axis','E-Stop Mushroom Button 22mm x2',NULL,'2026-05-29 11:39:31'),(22,'TK-260717-026','Sara Lindqvist','2026-07-17 09:56:35','2026-07-17 13:39:35','Mechanical','Cam follower worn, carousel indexing out of tolerance','Replaced cam follower, re-timed carousel, ran 50 change cycles clean','Linear Guide Block HGH25 x2',NULL,'2026-07-17 10:39:35'),(23,'TK-260715-027','Katerina Novak','2026-07-15 10:48:38','2026-07-15 12:31:38','Mechanical','Tail pulley bearing seized, belt pulling right','Replaced 6205 bearing, re-tensioned and re-tracked belt','Welding Torch Nozzle M8 x3',NULL,'2026-07-15 09:31:38'),(24,'TK-260601-028','Taro Yamamoto','2026-06-01 19:18:53','2026-06-01 21:53:53','Electrical','Axis 3 brake contactor welded closed','Replaced contactor, verified brake test, re-mastered axis 3','Spindle Grease NLGI 2 (400g) x2',NULL,'2026-06-01 18:53:53'),(25,'TK-260501-029','Katerina Novak','2026-05-01 21:31:54','2026-05-01 22:26:54','Process','Contact tip worn and shielding gas flow low','Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed','',NULL,'2026-05-01 19:26:54'),(26,'TK-260511-030','Taro Yamamoto','2026-05-11 13:13:28','2026-05-11 15:41:28','Mechanical','Sheet not seated, height sensor calibration drifted','Replaced ceramic, recalibrated capacitive height sensor, re-ran nest','Compressor Air Filter GA55 x2',NULL,'2026-05-11 12:41:28'),(27,'TK-251122-031','Jide Okafor','2025-11-22 17:47:56','2025-11-22 23:43:56','Hydraulic','Main cylinder seal blown','Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability','VFD 7.5kW 400V x3',NULL,'2025-11-22 21:43:56'),(28,'TK-260520-032','Jide Okafor','2026-05-20 18:12:10','2026-05-20 20:17:10','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','E-Stop Mushroom Button 22mm x3',NULL,'2026-05-20 17:17:10'),(29,'TK-251220-033','Taro Yamamoto','2025-12-20 18:36:47','2025-12-20 19:54:47','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','Linear Guide Block HGH25 x2',NULL,'2025-12-20 17:54:47'),(30,'TK-260509-034','Taro Yamamoto','2026-05-09 17:43:48','2026-05-09 19:56:48','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','Welding Torch Nozzle M8 x3',NULL,'2026-05-09 16:56:48'),(31,'TK-260706-035','Katerina Novak','2026-07-06 22:35:51','2026-07-07 00:34:51','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','Spindle Grease NLGI 2 (400g) x1',NULL,'2026-07-06 21:34:51'),(32,'TK-260108-036','Jide Okafor','2026-01-08 21:18:53','2026-01-08 23:13:53','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','',NULL,'2026-01-08 21:13:53'),(33,'TK-260720-037','Sara Lindqvist','2026-07-20 20:21:51','2026-07-20 21:13:51','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','Compressor Air Filter GA55 x2',NULL,'2026-07-20 18:13:51'),(34,'TK-251217-038','Sara Lindqvist','2025-12-17 15:02:10','2025-12-17 15:54:10','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','VFD 7.5kW 400V x2',NULL,'2025-12-17 13:54:10'),(35,'TK-260505-039','Sara Lindqvist','2026-05-05 09:49:40','2026-05-05 13:05:40','Utilities','Cooler matrix fouled, ambient extraction restricted','Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C','E-Stop Mushroom Button 22mm x3',NULL,'2026-05-05 10:05:40'),(36,'TK-251225-040','Taro Yamamoto','2025-12-25 14:38:39','2025-12-25 16:20:39','Utilities','Glycol concentration low and strainer partially blocked','Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K','Linear Guide Block HGH25 x1',NULL,'2025-12-25 14:20:39'),(37,'TK-251118-041','Katerina Novak','2025-11-18 13:37:51','2025-11-18 15:59:51','Electrical','Limit switch contact oxidised','Replaced limit switch, tested overtravel stop, load tested at 5T','',NULL,'2025-11-18 13:59:51'),(38,'TK-260715-042','Taro Yamamoto','2026-07-15 13:34:15','2026-07-15 14:44:15','Mechanical','Filter cartridges loaded, pulse valve not firing','Replaced pulse valve diaphragm, cleaned cartridges, suction restored','',NULL,'2026-07-15 11:44:15'),(39,'TK-260517-043','Sara Lindqvist','2026-05-17 17:57:45','2026-05-17 18:59:45','Hydraulic','Oil level low, suction strainer drawing air','Topped VG46, replaced suction strainer, bled system','',NULL,'2026-05-17 15:59:45'),(40,'TK-260323-044','Katerina Novak','2026-03-23 11:17:19','2026-03-23 12:40:19','Safety','Safety relay channel fault after door impact','Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1','Compressor Air Filter GA55 x1',NULL,'2026-03-23 10:40:19'),(41,'TK-260319-045','Katerina Novak','2026-03-19 10:51:36','2026-03-19 14:40:36','Mechanical','Spindle bearing grease degraded past service life','Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve','',NULL,'2026-03-19 12:40:36'),(42,'TK-260513-046','Taro Yamamoto','2026-05-13 15:58:45','2026-05-13 17:15:45','Hydraulic','Coolant filter blocked with fine swarf','Replaced filter bag, flushed lines, restored 4.2 bar at nozzle','',NULL,'2026-05-13 14:15:45'),(43,'TK-260105-047','Taro Yamamoto','2026-01-05 06:40:19','2026-01-05 09:50:19','Electrical','Encoder cable chafed inside drag chain','Replaced encoder cable, added protective sleeve, re-homed axis','',NULL,'2026-01-05 07:50:19'),(44,'TK-260409-048','Sara Lindqvist','2026-04-09 08:37:16','2026-04-09 12:07:16','Mechanical','Cam follower worn, carousel indexing out of tolerance','Replaced cam follower, re-timed carousel, ran 50 change cycles clean','',NULL,'2026-04-09 09:07:16'),(45,'TK-260227-049','Sara Lindqvist','2026-02-27 13:08:46','2026-02-27 14:28:46','Mechanical','Tail pulley bearing seized, belt pulling right','Replaced 6205 bearing, re-tensioned and re-tracked belt','Spindle Grease NLGI 2 (400g) x2',NULL,'2026-02-27 12:28:46'),(46,'TK-260526-050','Sara Lindqvist','2026-05-26 15:17:04','2026-05-26 18:07:04','Electrical','Axis 3 brake contactor welded closed','Replaced contactor, verified brake test, re-mastered axis 3','',NULL,'2026-05-26 15:07:04'),(47,'TK-260711-051','Jide Okafor','2026-07-11 16:53:02','2026-07-11 17:51:02','Process','Contact tip worn and shielding gas flow low','Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed','Compressor Air Filter GA55 x3',NULL,'2026-07-11 14:51:02'),(48,'TK-260307-052','Jide Okafor','2026-03-07 11:13:29','2026-03-07 13:05:29','Mechanical','Sheet not seated, height sensor calibration drifted','Replaced ceramic, recalibrated capacitive height sensor, re-ran nest','',NULL,'2026-03-07 11:05:29'),(49,'TK-251112-053','Sara Lindqvist','2025-11-12 16:55:02','2025-11-12 23:17:02','Hydraulic','Main cylinder seal blown','Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability','E-Stop Mushroom Button 22mm x3',NULL,'2025-11-12 21:17:02'),(50,'TK-260710-054','Sara Lindqvist','2026-07-10 17:28:50','2026-07-10 19:40:50','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','Linear Guide Block HGH25 x3',NULL,'2026-07-10 16:40:50'),(51,'TK-260408-055','Sara Lindqvist','2026-04-08 12:26:29','2026-04-08 13:39:29','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','Welding Torch Nozzle M8 x3',NULL,'2026-04-08 10:39:29'),(52,'TK-251031-056','Taro Yamamoto','2025-10-31 21:41:25','2025-10-31 23:44:25','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','Spindle Grease NLGI 2 (400g) x1',NULL,'2025-10-31 21:44:25'),(53,'TK-260103-057','Sara Lindqvist','2026-01-03 16:00:43','2026-01-03 17:11:43','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','Spherical Roller Bearing 22215 x1',NULL,'2026-01-03 15:11:43'),(54,'TK-260312-058','Katerina Novak','2026-03-12 10:47:44','2026-03-12 13:49:44','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','Compressor Air Filter GA55 x1',NULL,'2026-03-12 11:49:44'),(55,'TK-260330-059','Sara Lindqvist','2026-03-30 20:18:03','2026-03-30 21:12:03','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','',NULL,'2026-03-30 18:12:03'),(56,'TK-251102-060','Katerina Novak','2025-11-02 10:19:13','2025-11-02 11:11:13','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','E-Stop Mushroom Button 22mm x3',NULL,'2025-11-02 09:11:13'),(57,'TK-260506-061','Sara Lindqvist','2026-05-06 22:01:34','2026-05-07 00:48:34','Utilities','Cooler matrix fouled, ambient extraction restricted','Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C','Linear Guide Block HGH25 x1',NULL,'2026-05-06 21:48:34'),(58,'TK-251108-062','Katerina Novak','2025-11-08 16:36:48','2025-11-08 19:01:48','Utilities','Glycol concentration low and strainer partially blocked','Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K','Welding Torch Nozzle M8 x2',NULL,'2025-11-08 17:01:48'),(59,'TK-251130-063','Taro Yamamoto','2025-11-30 17:41:52','2025-11-30 19:25:52','Electrical','Limit switch contact oxidised','Replaced limit switch, tested overtravel stop, load tested at 5T','Spindle Grease NLGI 2 (400g) x3',NULL,'2025-11-30 17:25:52'),(60,'TK-260428-064','Jide Okafor','2026-04-28 18:02:37','2026-04-28 19:13:37','Mechanical','Filter cartridges loaded, pulse valve not firing','Replaced pulse valve diaphragm, cleaned cartridges, suction restored','Spherical Roller Bearing 22215 x1',NULL,'2026-04-28 16:13:37'),(61,'TK-260518-065','Sara Lindqvist','2026-05-18 13:06:17','2026-05-18 14:05:17','Hydraulic','Oil level low, suction strainer drawing air','Topped VG46, replaced suction strainer, bled system','Compressor Air Filter GA55 x2',NULL,'2026-05-18 11:05:17'),(62,'TK-260208-066','Katerina Novak','2026-02-08 17:42:39','2026-02-08 19:46:39','Safety','Safety relay channel fault after door impact','Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1','VFD 7.5kW 400V x2',NULL,'2026-02-08 17:46:39'),(63,'TK-260218-067','Sara Lindqvist','2026-02-18 13:46:32','2026-02-18 18:17:32','Mechanical','Spindle bearing grease degraded past service life','Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve','',NULL,'2026-02-18 16:17:32'),(64,'TK-260517-068','Sara Lindqvist','2026-05-17 19:32:32','2026-05-17 20:57:32','Hydraulic','Coolant filter blocked with fine swarf','Replaced filter bag, flushed lines, restored 4.2 bar at nozzle','Linear Guide Block HGH25 x1',NULL,'2026-05-17 17:57:32'),(65,'TK-260408-069','Katerina Novak','2026-04-08 16:47:11','2026-04-08 20:25:11','Electrical','Encoder cable chafed inside drag chain','Replaced encoder cable, added protective sleeve, re-homed axis','Welding Torch Nozzle M8 x3',NULL,'2026-04-08 17:25:11'),(66,'TK-251113-070','Katerina Novak','2025-11-13 07:28:07','2025-11-13 10:22:07','Mechanical','Cam follower worn, carousel indexing out of tolerance','Replaced cam follower, re-timed carousel, ran 50 change cycles clean','',NULL,'2025-11-13 08:22:07'),(67,'TK-260424-071','Taro Yamamoto','2026-04-24 09:46:02','2026-04-24 11:03:02','Mechanical','Tail pulley bearing seized, belt pulling right','Replaced 6205 bearing, re-tensioned and re-tracked belt','Spherical Roller Bearing 22215 x3',NULL,'2026-04-24 08:03:02'),(68,'TK-260716-072','Katerina Novak','2026-07-16 10:40:40','2026-07-16 13:18:40','Electrical','Axis 3 brake contactor welded closed','Replaced contactor, verified brake test, re-mastered axis 3','Compressor Air Filter GA55 x2',NULL,'2026-07-16 10:18:40'),(69,'TK-260710-073','Katerina Novak','2026-07-10 15:52:01','2026-07-10 16:56:01','Process','Contact tip worn and shielding gas flow low','Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed','VFD 7.5kW 400V x3',NULL,'2026-07-10 13:56:01'),(70,'TK-251226-074','Katerina Novak','2025-12-26 09:23:44','2025-12-26 11:49:44','Mechanical','Sheet not seated, height sensor calibration drifted','Replaced ceramic, recalibrated capacitive height sensor, re-ran nest','E-Stop Mushroom Button 22mm x2',NULL,'2025-12-26 09:49:44'),(71,'TK-260627-075','Katerina Novak','2026-06-27 06:43:29','2026-06-27 12:35:29','Hydraulic','Main cylinder seal blown','Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability','Linear Guide Block HGH25 x1',NULL,'2026-06-27 09:35:29'),(72,'TK-260324-076','Katerina Novak','2026-03-24 21:26:05','2026-03-24 23:11:05','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','Welding Torch Nozzle M8 x3',NULL,'2026-03-24 21:11:05'),(73,'TK-260307-077','Katerina Novak','2026-03-07 07:39:20','2026-03-07 08:45:20','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','Spindle Grease NLGI 2 (400g) x1',NULL,'2026-03-07 06:45:20'),(74,'TK-251109-078','Sara Lindqvist','2025-11-09 21:53:47','2025-11-10 00:21:47','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','Spherical Roller Bearing 22215 x2',NULL,'2025-11-09 22:21:47'),(75,'TK-251121-079','Katerina Novak','2025-11-21 19:39:23','2025-11-21 21:38:23','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','Compressor Air Filter GA55 x3',NULL,'2025-11-21 19:38:23'),(76,'TK-260413-080','Jide Okafor','2026-04-13 07:51:06','2026-04-13 10:20:06','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','',NULL,'2026-04-13 07:20:06'),(77,'TK-260313-081','Taro Yamamoto','2026-03-13 16:32:26','2026-03-13 17:31:26','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','',NULL,'2026-03-13 15:31:26'),(78,'TK-260201-082','Taro Yamamoto','2026-02-01 19:14:55','2026-02-01 20:15:55','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','',NULL,'2026-02-01 18:15:55'),(79,'TK-260223-083','Jide Okafor','2026-02-23 17:07:38','2026-02-23 20:02:38','Utilities','Cooler matrix fouled, ambient extraction restricted','Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C','Welding Torch Nozzle M8 x3',NULL,'2026-02-23 18:02:38'),(80,'TK-260720-084','Jide Okafor','2026-07-20 11:51:42','2026-07-20 14:35:42','Utilities','Glycol concentration low and strainer partially blocked','Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K','Spindle Grease NLGI 2 (400g) x2',NULL,'2026-07-20 11:35:42'),(81,'TK-260605-085','Katerina Novak','2026-06-05 20:03:40','2026-06-05 22:08:40','Electrical','Limit switch contact oxidised','Replaced limit switch, tested overtravel stop, load tested at 5T','Spherical Roller Bearing 22215 x3',NULL,'2026-06-05 19:08:40'),(82,'TK-260709-086','Sara Lindqvist','2026-07-09 20:12:05','2026-07-09 21:43:05','Mechanical','Filter cartridges loaded, pulse valve not firing','Replaced pulse valve diaphragm, cleaned cartridges, suction restored','',NULL,'2026-07-09 18:43:05'),(83,'TK-260105-087','Taro Yamamoto','2026-01-05 08:08:23','2026-01-05 09:30:23','Hydraulic','Oil level low, suction strainer drawing air','Topped VG46, replaced suction strainer, bled system','VFD 7.5kW 400V x1',NULL,'2026-01-05 07:30:23'),(84,'TK-260423-088','Katerina Novak','2026-04-23 10:09:12','2026-04-23 12:20:12','Safety','Safety relay channel fault after door impact','Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1','E-Stop Mushroom Button 22mm x2',NULL,'2026-04-23 09:20:12'),(85,'TK-260125-089','Jide Okafor','2026-01-25 19:21:40','2026-01-25 23:58:40','Mechanical','Spindle bearing grease degraded past service life','Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve','Linear Guide Block HGH25 x2',NULL,'2026-01-25 21:58:40'),(86,'TK-260508-090','Katerina Novak','2026-05-08 21:21:39','2026-05-08 22:36:39','Hydraulic','Coolant filter blocked with fine swarf','Replaced filter bag, flushed lines, restored 4.2 bar at nozzle','Welding Torch Nozzle M8 x2',NULL,'2026-05-08 19:36:39'),(87,'TK-260310-091','Taro Yamamoto','2026-03-10 10:42:29','2026-03-10 13:49:29','Electrical','Encoder cable chafed inside drag chain','Replaced encoder cable, added protective sleeve, re-homed axis','Spindle Grease NLGI 2 (400g) x1',NULL,'2026-03-10 11:49:29'),(88,'TK-260718-092','Sara Lindqvist','2026-07-18 22:06:08','2026-07-19 01:12:08','Mechanical','Cam follower worn, carousel indexing out of tolerance','Replaced cam follower, re-timed carousel, ran 50 change cycles clean','Spherical Roller Bearing 22215 x2',NULL,'2026-07-18 22:12:08'),(89,'TK-260506-093','Taro Yamamoto','2026-05-06 12:08:25','2026-05-06 13:28:25','Mechanical','Tail pulley bearing seized, belt pulling right','Replaced 6205 bearing, re-tensioned and re-tracked belt','Compressor Air Filter GA55 x3',NULL,'2026-05-06 10:28:25'),(90,'TK-260706-094','Sara Lindqvist','2026-07-06 17:44:21','2026-07-06 19:48:21','Electrical','Axis 3 brake contactor welded closed','Replaced contactor, verified brake test, re-mastered axis 3','VFD 7.5kW 400V x1',NULL,'2026-07-06 16:48:21'),(91,'TK-251124-095','Taro Yamamoto','2025-11-24 11:29:39','2025-11-24 12:26:39','Process','Contact tip worn and shielding gas flow low','Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed','E-Stop Mushroom Button 22mm x3',NULL,'2025-11-24 10:26:39'),(92,'TK-260507-096','Taro Yamamoto','2026-05-07 20:29:28','2026-05-07 22:24:28','Mechanical','Sheet not seated, height sensor calibration drifted','Replaced ceramic, recalibrated capacitive height sensor, re-ran nest','Linear Guide Block HGH25 x2',NULL,'2026-05-07 19:24:28'),(93,'TK-260616-097','Katerina Novak','2026-06-16 17:18:40','2026-06-16 23:56:40','Hydraulic','Main cylinder seal blown','Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability','',NULL,'2026-06-16 20:56:40'),(94,'TK-260513-098','Katerina Novak','2026-05-13 08:01:37','2026-05-13 10:21:37','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','Spindle Grease NLGI 2 (400g) x3',NULL,'2026-05-13 07:21:37'),(95,'TK-260208-099','Taro Yamamoto','2026-02-08 14:48:11','2026-02-08 16:13:11','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','Spherical Roller Bearing 22215 x3',NULL,'2026-02-08 14:13:11'),(96,'TK-251228-100','Taro Yamamoto','2025-12-28 12:07:46','2025-12-28 14:06:46','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','Compressor Air Filter GA55 x2',NULL,'2025-12-28 12:06:46'),(97,'TK-251207-101','Jide Okafor','2025-12-07 13:17:13','2025-12-07 14:46:13','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','',NULL,'2025-12-07 12:46:13'),(98,'TK-260122-102','Taro Yamamoto','2026-01-22 10:37:26','2026-01-22 13:16:26','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','',NULL,'2026-01-22 11:16:26'),(99,'TK-260716-103','Katerina Novak','2026-07-16 16:36:32','2026-07-16 17:25:32','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','Linear Guide Block HGH25 x2',NULL,'2026-07-16 14:25:32'),(100,'TK-260512-104','Jide Okafor','2026-05-12 18:22:24','2026-05-12 19:13:24','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','Welding Torch Nozzle M8 x2',NULL,'2026-05-12 16:13:24'),(101,'TK-260527-105','Jide Okafor','2026-05-27 13:27:41','2026-05-27 15:50:41','Utilities','Cooler matrix fouled, ambient extraction restricted','Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C','Spindle Grease NLGI 2 (400g) x3',NULL,'2026-05-27 12:50:41'),(102,'TK-251105-106','Sara Lindqvist','2025-11-05 15:55:07','2025-11-05 18:16:07','Utilities','Glycol concentration low and strainer partially blocked','Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K','Spherical Roller Bearing 22215 x1',NULL,'2025-11-05 16:16:07'),(103,'TK-260311-107','Katerina Novak','2026-03-11 19:21:43','2026-03-11 21:51:43','Electrical','Limit switch contact oxidised','Replaced limit switch, tested overtravel stop, load tested at 5T','Compressor Air Filter GA55 x2',NULL,'2026-03-11 19:51:43'),(104,'TK-260531-108','Taro Yamamoto','2026-05-31 18:41:11','2026-05-31 20:17:11','Mechanical','Filter cartridges loaded, pulse valve not firing','Replaced pulse valve diaphragm, cleaned cartridges, suction restored','VFD 7.5kW 400V x3',NULL,'2026-05-31 17:17:11'),(105,'TK-251214-109','Jide Okafor','2025-12-14 06:48:21','2025-12-14 07:56:21','Hydraulic','Oil level low, suction strainer drawing air','Topped VG46, replaced suction strainer, bled system','',NULL,'2025-12-14 05:56:21'),(106,'TK-260404-110','Taro Yamamoto','2026-04-04 12:31:52','2026-04-04 14:35:52','Safety','Safety relay channel fault after door impact','Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1','Linear Guide Block HGH25 x1',NULL,'2026-04-04 11:35:52'),(107,'TK-260629-111','Sara Lindqvist','2026-06-29 16:43:51','2026-06-29 21:08:51','Mechanical','Spindle bearing grease degraded past service life','Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve','Welding Torch Nozzle M8 x2',NULL,'2026-06-29 18:08:51'),(108,'TK-260609-112','Katerina Novak','2026-06-09 19:57:08','2026-06-09 21:08:08','Hydraulic','Coolant filter blocked with fine swarf','Replaced filter bag, flushed lines, restored 4.2 bar at nozzle','Spindle Grease NLGI 2 (400g) x3',NULL,'2026-06-09 18:08:08'),(109,'TK-260109-113','Jide Okafor','2026-01-09 22:21:06','2026-01-10 01:24:06','Electrical','Encoder cable chafed inside drag chain','Replaced encoder cable, added protective sleeve, re-homed axis','',NULL,'2026-01-09 23:24:06'),(110,'TK-260608-114','Taro Yamamoto','2026-06-08 09:17:33','2026-06-08 13:06:33','Mechanical','Cam follower worn, carousel indexing out of tolerance','Replaced cam follower, re-timed carousel, ran 50 change cycles clean','Compressor Air Filter GA55 x3',NULL,'2026-06-08 10:06:33'),(111,'TK-260219-115','Katerina Novak','2026-02-19 12:03:31','2026-02-19 13:15:31','Mechanical','Tail pulley bearing seized, belt pulling right','Replaced 6205 bearing, re-tensioned and re-tracked belt','VFD 7.5kW 400V x1',NULL,'2026-02-19 11:15:31'),(112,'TK-260405-116','Jide Okafor','2026-04-05 11:50:20','2026-04-05 14:15:20','Electrical','Axis 3 brake contactor welded closed','Replaced contactor, verified brake test, re-mastered axis 3','',NULL,'2026-04-05 11:15:20'),(113,'TK-251228-117','Jide Okafor','2025-12-28 11:26:41','2025-12-28 12:26:41','Process','Contact tip worn and shielding gas flow low','Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed','',NULL,'2025-12-28 10:26:41'),(114,'TK-260514-118','Sara Lindqvist','2026-05-14 12:34:51','2026-05-14 14:12:51','Mechanical','Sheet not seated, height sensor calibration drifted','Replaced ceramic, recalibrated capacitive height sensor, re-ran nest','Welding Torch Nozzle M8 x1',NULL,'2026-05-14 11:12:51'),(115,'TK-260714-119','Jide Okafor','2026-07-14 12:10:57','2026-07-14 18:03:57','Hydraulic','Main cylinder seal blown','Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability','',NULL,'2026-07-14 15:03:57'),(116,'TK-260703-120','Jide Okafor','2026-07-03 21:16:00','2026-07-03 23:30:00','Process','Reference volume drifted, test fixture O-ring cracked','Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk','Spherical Roller Bearing 22215 x1',NULL,'2026-07-03 20:30:00'),(117,'TK-260403-121','Jide Okafor','2026-04-03 22:15:47','2026-04-03 23:17:47','Process','Transducer calibration expired','Recalibrated transducer, re-ran capability on 30 joints, released cell','',NULL,'2026-04-03 20:17:47'),(118,'TK-251128-122','Sara Lindqvist','2025-11-28 12:51:19','2025-11-28 14:37:19','Electrical','Load cell signal noise from unshielded run','Rerouted load cell cable away from VFD, added shield bonding, error cleared','VFD 7.5kW 400V x1',NULL,'2025-11-28 12:37:19'),(119,'TK-260326-123','Katerina Novak','2026-03-26 12:53:06','2026-03-26 14:23:06','Process','Ambient light change after lamp replacement','Re-taught vision model, fitted shroud, false reject back under 0.5 percent','E-Stop Mushroom Button 22mm x1',NULL,'2026-03-26 12:23:06'),(120,'TK-260403-124','Jide Okafor','2026-04-03 15:13:08','2026-04-03 18:01:08','Electrical','Heater band open circuit on zone 3','Replaced heater band and thermocouple, re-tuned PID for zone 3','Linear Guide Block HGH25 x2',NULL,'2026-04-03 15:01:08'),(121,'TK-260217-125','Taro Yamamoto','2026-02-17 07:29:40','2026-02-17 08:32:40','Process','Printhead partially clogged, solvent low','Ran printhead clean cycle, topped solvent, replaced filter','Welding Torch Nozzle M8 x3',NULL,'2026-02-17 06:32:40'),(122,'TK-251203-126','Taro Yamamoto','2025-12-03 07:42:11','2025-12-03 08:49:11','Electrical','Photoelectric sensor misaligned by pallet strike','Realigned and reinforced sensor bracket, verified pattern over 20 pallets','Spindle Grease NLGI 2 (400g) x2',NULL,'2025-12-03 06:49:11');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_comments`
--

LOCK TABLES `ticket_comments` WRITE;
/*!40000 ALTER TABLE `ticket_comments` DISABLE KEYS */;
INSERT INTO `ticket_comments` VALUES (1,'TK-260527-012','Marc Dubois','Spare was on the shelf, good catch by stores.','2026-05-27 08:54:06'),(2,'TK-260606-015','Rui Silva','Same symptom as last month, worth a PM interval review.','2026-06-06 05:28:19'),(3,'TK-260611-020','Elise Moreau','Same symptom as last month, worth a PM interval review.','2026-06-11 22:57:04'),(4,'TK-260405-023','Rui Silva','Same symptom as last month, worth a PM interval review.','2026-04-05 12:44:19'),(5,'TK-260529-025','Marc Dubois','Spare was on the shelf, good catch by stores.','2026-05-29 12:39:31'),(6,'TK-260601-028','Priya Nair','Same symptom as last month, worth a PM interval review.','2026-06-01 19:53:53'),(7,'TK-260511-030','Priya Nair','Operator briefed on the restart procedure afterwards.','2026-05-11 13:41:28'),(8,'TK-251122-031','Rui Silva','Same symptom as last month, worth a PM interval review.','2025-11-22 22:43:56'),(9,'TK-251220-033','Marc Dubois','Line was held for this - please prioritise next time.','2025-12-20 18:54:47'),(10,'TK-260706-035','Priya Nair','Operator briefed on the restart procedure afterwards.','2026-07-06 22:34:51'),(11,'TK-260720-037','Elise Moreau','Same symptom as last month, worth a PM interval review.','2026-07-20 19:13:51'),(12,'TK-260715-042','Rui Silva','Spare was on the shelf, good catch by stores.','2026-07-15 12:44:15'),(13,'TK-260319-045','Priya Nair','Operator briefed on the restart procedure afterwards.','2026-03-19 13:40:36'),(14,'TK-260409-048','Elise Moreau','Spare was on the shelf, good catch by stores.','2026-04-09 10:07:16'),(15,'TK-251112-053','Elise Moreau','Spare was on the shelf, good catch by stores.','2025-11-12 22:17:02'),(16,'TK-251031-056','Priya Nair','Operator briefed on the restart procedure afterwards.','2025-10-31 22:44:25'),(17,'TK-260506-061','Priya Nair','Same symptom as last month, worth a PM interval review.','2026-05-06 22:48:34'),(18,'TK-260218-067','Rui Silva','Operator briefed on the restart procedure afterwards.','2026-02-18 17:17:32'),(19,'TK-260517-068','Rui Silva','Operator briefed on the restart procedure afterwards.','2026-05-17 18:57:32'),(20,'TK-260324-076','Priya Nair','Line was held for this - please prioritise next time.','2026-03-24 22:11:05'),(21,'TK-260307-077','Elise Moreau','Line was held for this - please prioritise next time.','2026-03-07 07:45:20'),(22,'TK-251109-078','Rui Silva','Same symptom as last month, worth a PM interval review.','2025-11-09 23:21:47'),(23,'TK-251121-079','Priya Nair','Same symptom as last month, worth a PM interval review.','2025-11-21 20:38:23'),(24,'TK-260313-081','Elise Moreau','Line was held for this - please prioritise next time.','2026-03-13 16:31:26'),(25,'TK-260423-088','Elise Moreau','Same symptom as last month, worth a PM interval review.','2026-04-23 10:20:12'),(26,'TK-260706-094','Marc Dubois','Line was held for this - please prioritise next time.','2026-07-06 17:48:21'),(27,'TK-260311-107','Rui Silva','Spare was on the shelf, good catch by stores.','2026-03-11 20:51:43'),(28,'TK-260608-114','Marc Dubois','Spare was on the shelf, good catch by stores.','2026-06-08 11:06:33'),(29,'TK-260703-120','Marc Dubois','Same symptom as last month, worth a PM interval review.','2026-07-03 21:30:00');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_skills`
--

LOCK TABLES `user_skills` WRITE;
/*!40000 ALTER TABLE `user_skills` DISABLE KEYS */;
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
  `admin_layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ordered JSON array of admin_panel tile ids; NULL = default order' CHECK (json_valid(`admin_layout_json`) or `admin_layout_json` is null),
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

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'a.rivera','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',4,NULL,NULL,'2025-09-25 08:13:39',NULL,NULL,NULL,'alex.rivera@meridian-works.example','Alex Rivera','+31 6 20687387','Maintenance','active','2026-07-22 18:43:15',NULL,NULL,NULL,'2026-07-22 15:43:15',0,'IB-10001',NULL),(2,'p.nair','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',3,NULL,NULL,'2025-08-29 12:22:07',NULL,NULL,NULL,'priya.nair@meridian-works.example','Priya Nair','+31 6 70419031','Maintenance','active','2026-07-22 18:43:16',NULL,NULL,NULL,'2026-07-22 15:43:16',0,'IB-10002',NULL),(3,'m.dubois','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',3,NULL,NULL,'2025-09-18 12:04:15',NULL,NULL,NULL,'marc.dubois@meridian-works.example','Marc Dubois','+31 6 42909129','Production','active','2026-07-21 08:10:06',NULL,NULL,NULL,'2026-07-22 15:39:40',0,'IB-10003',NULL),(4,'j.okafor','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',2,NULL,NULL,'2025-06-30 11:46:16',NULL,NULL,NULL,'jide.okafor@meridian-works.example','Jide Okafor','+31 6 33249810','Maintenance','active','2026-07-22 18:43:17',NULL,NULL,NULL,'2026-07-22 15:43:17',0,'IB-10004',NULL),(5,'s.lindqvist','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',2,NULL,NULL,'2025-08-28 15:43:36',NULL,NULL,NULL,'sara.lindqvist@meridian-works.example','Sara Lindqvist','+31 6 43385498','Maintenance','active','2026-07-19 14:46:58',NULL,NULL,NULL,'2026-07-22 15:39:40',0,'IB-10005',NULL),(6,'t.yamamoto','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',2,NULL,NULL,'2025-08-08 09:02:38',NULL,NULL,NULL,'taro.yamamoto@meridian-works.example','Taro Yamamoto','+31 6 75403091','Maintenance','active','2026-07-20 20:17:54',NULL,NULL,NULL,'2026-07-22 15:39:40',0,'IB-10006',NULL),(7,'k.novak','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',2,NULL,NULL,'2025-09-10 03:06:37',NULL,NULL,NULL,'katerina.novak@meridian-works.example','Katerina Novak','+31 6 20231879','Maintenance','active','2026-07-20 10:01:08',NULL,NULL,NULL,'2026-07-22 15:39:40',0,'IB-10007',NULL),(8,'r.silva','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',1,NULL,NULL,'2025-09-16 06:09:07',NULL,NULL,NULL,'rui.silva@meridian-works.example','Rui Silva','+31 6 39212026','Production','active','2026-07-22 18:43:18',NULL,NULL,NULL,'2026-07-22 15:43:18',0,'IB-10008',NULL),(9,'e.moreau','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',1,NULL,NULL,'2025-07-07 12:33:26',NULL,NULL,NULL,'elise.moreau@meridian-works.example','Elise Moreau','+31 6 49457662','Production','active','2026-07-22 19:44:47',NULL,NULL,NULL,'2026-07-22 15:39:40',0,'IB-10009',NULL),(10,'h.bakker','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',6,NULL,NULL,'2025-08-18 17:38:58',NULL,NULL,NULL,'hendrik.bakker@meridian-works.example','Hendrik Bakker','+31 6 27675234','Stores','active','2026-07-22 18:43:19',NULL,NULL,NULL,'2026-07-22 15:43:19',0,'IB-10010',NULL),(11,'c.whitfield','$2y$10$d55b2ky.SX7.TKptsnUoF.FhnExa09xWgLQDqILUV1zWlhKMYMmrO',5,NULL,NULL,'2025-07-19 07:18:49',NULL,NULL,NULL,'claire.whitfield@meridian-works.example','Claire Whitfield','+31 6 42096677','Finance','active','2026-07-22 18:43:19',NULL,NULL,NULL,'2026-07-22 15:43:19',0,'IB-10011',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors_suppliers`
--

LOCK TABLES `vendors_suppliers` WRITE;
/*!40000 ALTER TABLE `vendors_suppliers` DISABLE KEYS */;
INSERT INTO `vendors_suppliers` VALUES (1,'Nordwerk Industrial Supply','Ingrid Sørensen','orders@nordwerk.example','+47 22 55 10 40',NULL,'Net 30','Oslo, NO','3-5 days','Distributor',NULL,4.60,'2025-07-22 13:47:48'),(2,'SKF Authorised Partner BV','Ruud van Dijk','sales@skf-partner.example','+31 20 441 9020',NULL,'Net 45','Utrecht, NL','2-4 days','OEM',NULL,4.80,'2025-07-15 03:22:25'),(3,'Siemens Drive Services','Klaus Berger','service@siemens-ds.example','+49 911 895 220',NULL,'Net 60','Nürnberg, DE','5-10 days','OEM',NULL,4.40,'2025-07-26 16:52:58'),(4,'Atlas Pneumatic Group','Marta Kowalska','support@atlaspg.example','+48 22 310 8800',NULL,'Net 30','Warsaw, PL','4-7 days','OEM',NULL,4.10,'2025-07-02 03:06:45'),(5,'Baltic Bearing & Seal','Tomas Petrauskas','info@balticbs.example','+370 5 210 3344',NULL,'Net 30','Vilnius, LT','2-3 days','Distributor',NULL,3.90,'2025-07-10 08:37:27'),(6,'Hydratech Fluid Power','Elena Rossi','orders@hydratech.example','+39 02 8940 1177',NULL,'Net 45','Milan, IT','6-9 days','Distributor',NULL,4.20,'2025-07-07 11:06:21'),(7,'Volt & Circuit Electricals','Peter Hoffmann','desk@voltcircuit.example','+49 40 3070 5511',NULL,'Net 30','Hamburg, DE','1-2 days','Local',NULL,4.70,'2025-07-11 06:50:03'),(8,'ToolCraft Precision Ltd','Sian Roberts','quotes@toolcraft.example','+44 121 555 0188',NULL,'Net 60','Birmingham, UK','7-14 days','OEM',NULL,4.00,'2025-07-25 05:38:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_orders`
--

LOCK TABLES `work_orders` WRITE;
/*!40000 ALTER TABLE `work_orders` DISABLE KEYS */;
INSERT INTO `work_orders` VALUES (1,'Annual thermographic survey of panels','Planned maintenance task raised from the PM programme.',2,'[{\"part_id\":1,\"qty\":4}]',NULL,6,'Completed','2026-05-17 16:25:00',6,'2026-05-17','2026-05-17 14:42:00',NULL),(2,'Replace worn tool changer cam follower','Planned maintenance task raised from the PM programme.',7,'[{\"part_id\":4,\"qty\":1}]',NULL,5,'Completed','2026-07-08 15:56:00',5,'2026-07-08','2026-07-08 11:19:00',NULL),(3,'Coolant system deep clean and refill','Planned maintenance task raised from the PM programme.',12,'[{\"part_id\":7,\"qty\":3}]',NULL,7,'Completed','2026-07-05 13:27:00',7,'2026-07-05','2026-07-05 11:53:00',NULL),(4,'Replace heater band, thermoformer zone 3','Planned maintenance task raised from the PM programme.',17,'[{\"part_id\":10,\"qty\":2}]',NULL,4,'Completed','2026-06-14 17:00:00',4,'2026-06-14','2026-06-14 13:20:00',NULL),(5,'Replace conveyor tail bearing','Planned maintenance task raised from the PM programme.',22,'[{\"part_id\":13,\"qty\":2}]',NULL,4,'Completed','2025-12-29 13:54:00',4,'2025-12-29','2025-12-29 12:03:00',NULL),(6,'Chiller glycol top-up and strainer clean','Planned maintenance task raised from the PM programme.',3,'[{\"part_id\":16,\"qty\":2}]',NULL,7,'Completed','2026-01-27 11:21:00',7,'2026-01-27','2026-01-27 07:00:00',NULL),(7,'Monthly PM - lubrication and inspection','Planned maintenance task raised from the PM programme.',8,'[{\"part_id\":19,\"qty\":3}]',NULL,4,'Completed','2025-12-26 11:22:00',4,'2025-12-26','2025-12-26 08:19:00',NULL),(8,'Quarterly PM - belt and drive inspection','Planned maintenance task raised from the PM programme.',13,'[{\"part_id\":22,\"qty\":2}]',NULL,6,'Completed','2026-01-19 17:28:00',6,'2026-01-19','2026-01-19 14:49:00',NULL),(9,'Robot gearbox oil change','Planned maintenance task raised from the PM programme.',18,'[{\"part_id\":25,\"qty\":3}]',NULL,5,'Completed','2026-02-13 16:34:00',5,'2026-02-13','2026-02-13 12:32:00',NULL),(10,'Recalibrate torque transducer','Planned maintenance task raised from the PM programme.',23,'[{\"part_id\":28,\"qty\":2}]',NULL,7,'Completed','2026-07-04 14:49:00',7,'2026-07-04','2026-07-04 12:04:00',NULL),(11,'Safety interlock verification','Planned maintenance task raised from the PM programme.',4,'[{\"part_id\":31,\"qty\":2}]',NULL,4,'Completed','2026-05-01 12:20:00',4,'2026-05-01','2026-05-01 07:41:00',NULL),(12,'Replace worn tool changer cam follower','Planned maintenance task raised from the PM programme.',9,'[{\"part_id\":34,\"qty\":1}]',NULL,4,'Completed','2025-12-30 14:50:00',4,'2025-12-30','2025-12-30 10:50:00',NULL),(13,'Robot gearbox oil change','Planned maintenance task raised from the PM programme.',14,'[{\"part_id\":2,\"qty\":4}]',NULL,5,'Completed','2026-04-26 14:12:00',5,'2026-04-26','2026-04-26 11:43:00',NULL),(14,'Replace conveyor tail bearing','Planned maintenance task raised from the PM programme.',19,'[{\"part_id\":5,\"qty\":4}]',NULL,5,'Completed','2025-12-27 18:04:00',5,'2025-12-27','2025-12-27 13:54:00',NULL),(15,'Quarterly PM - belt and drive inspection','Planned maintenance task raised from the PM programme.',24,'[{\"part_id\":8,\"qty\":2}]',NULL,6,'Completed','2025-12-10 11:10:00',6,'2025-12-10','2025-12-10 08:36:00',NULL),(16,'Vision system re-teach after lamp change','Planned maintenance task raised from the PM programme.',5,'[{\"part_id\":11,\"qty\":3}]',NULL,4,'Completed','2026-06-01 14:58:00',4,'2026-06-01','2026-06-01 12:25:00',NULL),(17,'Recalibrate torque transducer','Planned maintenance task raised from the PM programme.',10,'[{\"part_id\":14,\"qty\":1}]',NULL,4,'Completed','2026-05-02 14:35:00',4,'2026-05-02','2026-05-02 11:55:00',NULL),(18,'Robot gearbox oil change','Planned maintenance task raised from the PM programme.',15,'[{\"part_id\":17,\"qty\":2}]',NULL,5,'Completed','2026-06-09 12:40:00',5,'2026-06-09','2026-06-09 10:45:00',NULL),(19,'Safety interlock verification','Planned maintenance task raised from the PM programme.',20,'[{\"part_id\":20,\"qty\":2}]',NULL,5,'Completed','2026-07-05 16:00:00',5,'2026-07-05','2026-07-05 12:17:00',NULL),(20,'Recalibrate torque transducer','Planned maintenance task raised from the PM programme.',1,'[{\"part_id\":23,\"qty\":2}]',NULL,6,'Completed','2026-06-14 10:30:00',6,'2026-06-14','2026-06-14 08:20:00',NULL),(21,'Replace heater band, thermoformer zone 3','Planned maintenance task raised from the PM programme.',6,'[{\"part_id\":26,\"qty\":4}]',NULL,7,'Completed','2025-12-20 15:01:00',7,'2025-12-20','2025-12-20 12:21:00',NULL),(22,'Monthly PM - lubrication and inspection','Planned maintenance task raised from the PM programme.',11,'[{\"part_id\":29,\"qty\":3}]',NULL,7,'Completed','2025-12-03 13:24:00',7,'2025-12-03','2025-12-03 11:02:00',NULL),(23,'Compressor cooler matrix clean','Planned maintenance task raised from the PM programme.',16,'[{\"part_id\":32,\"qty\":3}]',NULL,7,'Completed','2026-04-10 14:08:00',7,'2026-04-10','2026-04-10 13:09:00',NULL),(24,'Annual thermographic survey of panels','Planned maintenance task raised from the PM programme.',21,'[{\"part_id\":35,\"qty\":1}]',NULL,7,'Completed','2026-07-07 13:01:00',7,'2026-07-07','2026-07-07 08:29:00',NULL),(25,'Robot gearbox oil change','Planned maintenance task raised from the PM programme.',2,'[{\"part_id\":3,\"qty\":1}]',NULL,4,'Completed','2025-12-01 14:45:00',4,'2025-12-01','2025-12-01 12:33:00',NULL),(26,'Chiller glycol top-up and strainer clean','Planned maintenance task raised from the PM programme.',7,'[{\"part_id\":6,\"qty\":3}]',NULL,5,'Completed','2026-02-05 15:57:00',5,'2026-02-05','2026-02-05 14:22:00',NULL),(27,'Monthly PM - lubrication and inspection','Planned maintenance task raised from the PM programme.',12,'[{\"part_id\":9,\"qty\":1}]',NULL,7,'Completed','2026-06-02 12:19:00',7,'2026-06-02','2026-06-02 08:48:00',NULL),(28,'Safety interlock verification','Planned maintenance task raised from the PM programme.',17,'[{\"part_id\":12,\"qty\":1}]',NULL,5,'Completed','2026-02-12 18:28:00',5,'2026-02-12','2026-02-12 14:28:00',NULL),(29,'Robot gearbox oil change','Planned maintenance task raised from the PM programme.',22,'[{\"part_id\":15,\"qty\":4}]',NULL,6,'Completed','2026-02-15 15:02:00',6,'2026-02-15','2026-02-15 12:18:00',NULL),(30,'Coolant system deep clean and refill','Planned maintenance task raised from the PM programme.',3,'[{\"part_id\":18,\"qty\":1}]',NULL,6,'Completed','2026-06-18 11:34:00',6,'2026-06-18','2026-06-18 09:08:00',NULL),(31,'Recalibrate torque transducer','Planned maintenance task raised from the PM programme.',8,'[{\"part_id\":21,\"qty\":2}]',NULL,6,'Completed','2025-12-19 09:31:00',6,'2025-12-19','2025-12-19 08:09:00',NULL),(32,'Replace conveyor tail bearing','Planned maintenance task raised from the PM programme.',13,'[{\"part_id\":24,\"qty\":4}]',NULL,5,'Completed','2026-03-29 12:42:00',5,'2026-03-29','2026-03-29 08:45:00',NULL),(33,'Quarterly PM - belt and drive inspection','Planned maintenance task raised from the PM programme.',18,'[{\"part_id\":27,\"qty\":4}]',NULL,7,'Completed','2025-12-28 11:31:00',7,'2025-12-28','2025-12-28 07:21:00',NULL),(34,'Annual thermographic survey of panels','Planned maintenance task raised from the PM programme.',23,'[{\"part_id\":30,\"qty\":3}]',NULL,7,'Completed','2026-05-09 12:05:00',7,'2026-05-09','2026-05-09 08:59:00',NULL),(35,'Annual thermographic survey of panels','Work started, awaiting completion sign-off.',4,'[{\"part_id\":33,\"qty\":3}]',NULL,5,'In Progress',NULL,NULL,'2026-07-21','2026-07-21 09:05:00',NULL),(36,'Recalibrate torque transducer','Work started, awaiting completion sign-off.',9,'[{\"part_id\":1,\"qty\":2}]',NULL,4,'In Progress',NULL,NULL,'2026-07-20','2026-07-20 12:53:00',NULL),(37,'Coolant system deep clean and refill','Work started, awaiting completion sign-off.',14,'[{\"part_id\":4,\"qty\":2}]',NULL,7,'In Progress',NULL,NULL,'2026-07-22','2026-07-22 10:17:00',NULL),(38,'Crane annual load test and inspection','Work started, awaiting completion sign-off.',19,'[{\"part_id\":7,\"qty\":2}]',NULL,6,'In Progress',NULL,NULL,'2026-07-21','2026-07-21 08:47:00',NULL),(39,'Coolant system deep clean and refill','Upcoming planned task.',24,'[{\"part_id\":10,\"qty\":3}]',NULL,4,'Scheduled',NULL,NULL,'2026-08-10',NULL,NULL),(40,'Robot gearbox oil change','Upcoming planned task.',5,'[{\"part_id\":13,\"qty\":2}]',NULL,7,'Scheduled',NULL,NULL,'2026-08-25',NULL,NULL),(41,'Recalibrate torque transducer','Upcoming planned task.',10,'[{\"part_id\":16,\"qty\":4}]',NULL,4,'Scheduled',NULL,NULL,'2026-08-21',NULL,NULL),(42,'Chiller glycol top-up and strainer clean','Upcoming planned task.',15,'[{\"part_id\":19,\"qty\":4}]',NULL,6,'Scheduled',NULL,NULL,'2026-09-05',NULL,NULL),(43,'Replace conveyor tail bearing','Upcoming planned task.',20,'[{\"part_id\":22,\"qty\":3}]',NULL,6,'Scheduled',NULL,NULL,'2026-08-30',NULL,NULL),(44,'Vision system re-teach after lamp change','Upcoming planned task.',1,'[{\"part_id\":25,\"qty\":1}]',NULL,6,'Scheduled',NULL,NULL,'2026-08-25',NULL,NULL),(45,'Crane annual load test and inspection','Upcoming planned task.',6,'[{\"part_id\":28,\"qty\":4}]',NULL,5,'Scheduled',NULL,NULL,'2026-08-09',NULL,NULL),(46,'Robot gearbox oil change','Upcoming planned task.',11,'[{\"part_id\":31,\"qty\":1}]',NULL,7,'Scheduled',NULL,NULL,'2026-08-10',NULL,NULL),(47,'Replace conveyor tail bearing','Upcoming planned task.',16,'[{\"part_id\":34,\"qty\":3}]',NULL,4,'Scheduled',NULL,NULL,'2026-08-30',NULL,NULL),(48,'Safety interlock verification','Scheduled task not executed on the planned date.',21,'[{\"part_id\":2,\"qty\":2}]',NULL,5,'Missed',NULL,NULL,'2026-07-01',NULL,NULL),(49,'Compressor cooler matrix clean','Scheduled task not executed on the planned date.',2,'[{\"part_id\":5,\"qty\":2}]',NULL,7,'Missed',NULL,NULL,'2026-07-14',NULL,NULL),(50,'Replace heater band, thermoformer zone 3','Scheduled task not executed on the planned date.',7,'[{\"part_id\":8,\"qty\":3}]',NULL,5,'Missed',NULL,NULL,'2026-06-23',NULL,NULL),(51,'Quarterly PM - belt and drive inspection','Cancelled - asset taken off line for a project.',12,'[{\"part_id\":11,\"qty\":3}]',NULL,6,'Cancelled',NULL,NULL,'2026-05-07',NULL,NULL),(52,'Quarterly PM - belt and drive inspection','Cancelled - asset taken off line for a project.',17,'[{\"part_id\":14,\"qty\":2}]',NULL,4,'Cancelled',NULL,NULL,'2026-04-17',NULL,NULL);
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
INSERT INTO `workshops` VALUES (1,'Plant A — Machining & Fabrication','Building 1, North Yard','Active'),(2,'Plant B — Assembly & Packaging','Building 2, South Yard','Active');
/*!40000 ALTER TABLE `workshops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'workshop_db'
--

--
-- Dumping routines for database 'workshop_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-22 18:43:31
