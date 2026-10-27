-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: coffee_shop_db
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
-- Current Database: `coffee_shop_db`
--

/*!40000 DROP DATABASE IF EXISTS `coffee_shop_db`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `coffee_shop_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `coffee_shop_db`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_name` varchar(255) NOT NULL,
  `actor_role` varchar(255) NOT NULL,
  `actor_user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_user_id_foreign` (`actor_user_id`),
  KEY `audit_logs_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `audit_logs_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 17:56:44','2026-09-26 17:56:44'),(2,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 17:57:13','2026-09-26 17:57:13'),(3,'Carlos Reyes','supervisor',3,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 17:57:19','2026-09-26 17:57:19'),(4,'Carlos Reyes','supervisor',3,'completed_order','POS','App\\Models\\Order',2,'{\"order_number\":\"ORD-20260927-WSEG\",\"cashier_name\":\"Carlos Reyes\",\"total\":195,\"items\":1,\"payment\":\"online\"}',NULL,'127.0.0.1','2026-09-26 20:10:24','2026-09-26 20:10:24'),(5,'Carlos Reyes','supervisor',3,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:11:17','2026-09-26 20:11:17'),(6,'Carlos Reyes','supervisor',3,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:12:38','2026-09-26 20:12:38'),(7,'Carlos Reyes','supervisor',3,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:13:41','2026-09-26 20:13:41'),(8,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:13:47','2026-09-26 20:13:47'),(9,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:14:11','2026-09-26 20:14:11'),(10,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:14:16','2026-09-26 20:14:16'),(11,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:14:18','2026-09-26 20:14:18'),(12,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:14:57','2026-09-26 20:14:57'),(13,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:15:00','2026-09-26 20:15:00'),(14,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:33:39','2026-09-26 20:33:39'),(15,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:35:23','2026-09-26 20:35:23'),(16,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:35:30','2026-09-26 20:35:30'),(17,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-26 20:35:52','2026-09-26 20:35:52'),(18,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:38:57','2026-09-26 20:38:57'),(19,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-26 20:39:04','2026-09-26 20:39:04'),(20,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 03:42:48','2026-09-27 03:42:48'),(21,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 03:42:58','2026-09-27 03:42:58'),(22,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 03:44:12','2026-09-27 03:44:12'),(23,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 05:01:52','2026-09-27 05:01:52'),(24,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 05:06:57','2026-09-27 05:06:57'),(25,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 05:30:34','2026-09-27 05:30:34'),(26,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 05:30:44','2026-09-27 05:30:44'),(27,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:31:03','2026-09-27 05:31:03'),(28,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:31:07','2026-09-27 05:31:07'),(29,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:33:34','2026-09-27 05:33:34'),(30,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:34:52','2026-09-27 05:34:52'),(31,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:34:57','2026-09-27 05:34:57'),(32,'Admin Owner','owner',1,'completed_order','POS','App\\Models\\Order',3,'{\"order_number\":\"ORD-20260927-SWNW\",\"cashier_name\":\"Admin Owner\",\"total\":100,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-09-27 05:38:51','2026-09-27 05:38:51'),(33,'Admin Owner','owner',1,'completed_order','POS','App\\Models\\Order',4,'{\"order_number\":\"ORD-20260927-P8U5\",\"cashier_name\":\"Admin Owner\",\"total\":150,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-09-27 05:40:33','2026-09-27 05:40:33'),(34,'Admin Owner','owner',1,'completed_order','POS','App\\Models\\Order',5,'{\"order_number\":\"ORD-20260927-VW2X\",\"cashier_name\":\"Admin Owner\",\"total\":280,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-09-27 05:41:31','2026-09-27 05:41:31'),(35,'Admin Owner','owner',1,'updated_category','Products','App\\Models\\Category',8,'{\"category\":\"Buldak Series\"}',NULL,'127.0.0.1','2026-09-27 05:43:22','2026-09-27 05:43:22'),(36,'Admin Owner','owner',1,'toggled_product_status','Products','App\\Models\\Product',1,'{\"product\":\"Americano\",\"status\":\"inactive\"}',NULL,'127.0.0.1','2026-09-27 05:43:53','2026-09-27 05:43:53'),(37,'Admin Owner','owner',1,'toggled_product_status','Products','App\\Models\\Product',1,'{\"product\":\"Americano\",\"status\":\"active\"}',NULL,'127.0.0.1','2026-09-27 05:44:11','2026-09-27 05:44:11'),(38,'Admin Owner','owner',1,'toggled_product_status','Products','App\\Models\\Product',1,'{\"product\":\"Americano\",\"status\":\"inactive\"}',NULL,'127.0.0.1','2026-09-27 05:44:22','2026-09-27 05:44:22'),(39,'Admin Owner','owner',1,'stock_in','Inventory','App\\Models\\InventoryTransaction',26,'{\"ingredient\":\"Apple Juice\",\"quantity\":\"20\",\"unit\":\"ml\",\"previous_stock\":\"2500.000\",\"new_stock\":\"2520.000\",\"supplier_id\":null,\"reference_number\":null}',NULL,'127.0.0.1','2026-09-27 05:45:33','2026-09-27 05:45:33'),(40,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:50:33','2026-09-27 05:50:33'),(41,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:51:06','2026-09-27 05:51:06'),(42,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:51:10','2026-09-27 05:51:10'),(43,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-09-27\",\"to\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 05:51:25','2026-09-27 05:51:25'),(44,'Admin Owner','owner',1,'toggled_user_status','Users','App\\Models\\User',3,'{\"user\":\"Carlos Reyes\",\"status\":\"inactive\"}',NULL,'127.0.0.1','2026-09-27 05:51:40','2026-09-27 05:51:40'),(45,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-09-27\",\"end_date\":\"2026-09-27\"}',NULL,'127.0.0.1','2026-09-27 06:26:06','2026-09-27 06:26:06'),(46,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 08:41:00','2026-09-27 08:41:00'),(47,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 08:41:07','2026-09-27 08:41:07'),(48,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-27 15:20:06','2026-09-27 15:20:06'),(49,'Maria Santos','manager',2,'completed_order','POS','App\\Models\\Order',6,'{\"order_number\":\"ORD-20260927-APYK\",\"cashier_name\":\"Maria Santos\",\"total\":440,\"items\":3,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-09-27 15:21:14','2026-09-27 15:21:14'),(50,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-28 09:04:38','2026-09-28 09:04:38'),(51,'Maria Santos','manager',2,'updated_category','Products','App\\Models\\Category',8,'{\"category\":\"Buldak Series\"}',NULL,'127.0.0.1','2026-09-28 09:09:21','2026-09-28 09:09:21'),(52,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-28 09:10:30','2026-09-28 09:10:30'),(53,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-09-28 09:10:39','2026-09-28 09:10:39'),(54,'POS Cashier','cashier',4,'completed_order','POS','App\\Models\\Order',7,'{\"order_number\":\"ORD-20260928-O2VQ\",\"cashier_name\":\"POS Cashier\",\"total\":1080,\"items\":7,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-09-28 09:32:22','2026-09-28 09:32:22'),(55,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 03:45:47','2026-10-01 03:45:47'),(56,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 08:40:11','2026-10-01 08:40:11'),(57,'POS Cashier','cashier',4,'completed_order','POS','App\\Models\\Order',8,'{\"order_number\":\"GB-20261001-IFWS\",\"cashier_name\":\"POS Cashier\",\"total\":1175,\"items\":7,\"payment\":\"grabfood\"}',NULL,'127.0.0.1','2026-10-01 08:51:07','2026-10-01 08:51:07'),(58,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 08:54:33','2026-10-01 08:54:33'),(59,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 08:54:40','2026-10-01 08:54:40'),(60,'Admin Owner','owner',1,'updated_category','Products','App\\Models\\Category',8,'{\"category\":\"Buldak Series\"}',NULL,'127.0.0.1','2026-10-01 09:30:24','2026-10-01 09:30:24'),(61,'Admin Owner','owner',1,'updated_category','Products','App\\Models\\Category',8,'{\"category\":\"Buldak Series\"}',NULL,'127.0.0.1','2026-10-01 09:31:51','2026-10-01 09:31:51'),(62,'Admin Owner','owner',1,'toggled_user_status','Users','App\\Models\\User',3,'{\"user\":\"Carlos Reyes\",\"status\":\"active\"}',NULL,'127.0.0.1','2026-10-01 09:32:07','2026-10-01 09:32:07'),(63,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-01\"}',NULL,'127.0.0.1','2026-10-01 10:28:13','2026-10-01 10:28:13'),(64,'Admin Owner','owner',1,'completed_order','POS','App\\Models\\Order',9,'{\"order_number\":\"ORD-20261001-BDQV\",\"cashier_name\":\"Admin Owner\",\"total\":300,\"items\":2,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-10-01 10:29:07','2026-10-01 10:29:07'),(65,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:29:49','2026-10-01 10:29:49'),(66,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:30:12','2026-10-01 10:30:12'),(67,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:31:11','2026-10-01 10:31:11'),(68,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:31:16','2026-10-01 10:31:16'),(69,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:33:52','2026-10-01 10:33:52'),(70,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:33:57','2026-10-01 10:33:57'),(71,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:44:06','2026-10-01 10:44:06'),(72,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 10:44:12','2026-10-01 10:44:12'),(73,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 11:05:21','2026-10-01 11:05:21'),(74,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 11:05:26','2026-10-01 11:05:26'),(75,'POS Cashier','cashier',4,'shift_started','POS','App\\Models\\CashierShift',1,'{\"shift_id\":1,\"beginning_cash\":\"9000.00\"}',NULL,'127.0.0.1','2026-10-01 11:29:28','2026-10-01 11:29:28'),(76,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.140.0 Chrome\\/150.0.7871.250 Electron\\/43.7.3 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 11:59:04','2026-10-01 11:59:04'),(77,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 12:30:54','2026-10-01 12:30:54'),(78,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 12:31:02','2026-10-01 12:31:02'),(79,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-01\"}',NULL,'127.0.0.1','2026-10-01 12:35:10','2026-10-01 12:35:10'),(80,'Admin Owner','owner',1,'shift_started','POS','App\\Models\\CashierShift',2,'{\"shift_id\":2,\"cashier\":\"Admin Owner\",\"started_at\":\"2026-10-01T20:35:32+00:00\",\"beginning_cash\":5000}',NULL,'127.0.0.1','2026-10-01 12:35:32','2026-10-01 12:35:32'),(81,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 18:05:44','2026-10-01 18:05:44'),(82,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 18:05:56','2026-10-01 18:05:56'),(83,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',10,'{\"order_number\":\"GB-20261002-XHRX\",\"cashier_name\":\"Admin Owner\",\"total\":\"570.00\",\"items\":4}',NULL,'127.0.0.1','2026-10-01 18:07:02','2026-10-01 18:07:02'),(84,'Admin Owner','owner',1,'shift_ended','POS','App\\Models\\CashierShift',2,'{\"shift_id\":2,\"beginning_cash\":5000,\"cash_sales\":0,\"debt_cash_collections\":0,\"debt_online_collections\":0,\"cash_refunds\":0,\"cash_voids\":0,\"expected_cash\":5000,\"actual_cash\":90000,\"difference\":85000,\"notes\":\"end\",\"manager_override\":false,\"override_reason\":null}',NULL,'127.0.0.1','2026-10-01 18:08:51','2026-10-01 18:08:51'),(85,'Admin Owner','owner',1,'shift_started','POS','App\\Models\\CashierShift',3,'{\"shift_id\":3,\"cashier\":\"Admin Owner\",\"started_at\":\"2026-10-02T10:28:51+08:00\",\"beginning_cash\":2000}',NULL,'127.0.0.1','2026-10-01 18:28:51','2026-10-01 18:28:51'),(86,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.140.0 Chrome\\/150.0.7871.250 Electron\\/43.7.3 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-01 18:30:23','2026-10-01 18:30:23'),(87,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',11,'{\"order_number\":\"ORD-20261002-8KGD\",\"cashier_name\":\"Admin Owner\",\"total\":\"760.00\",\"items\":5}',NULL,'127.0.0.1','2026-10-01 19:01:44','2026-10-01 19:01:44'),(88,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:08:23','2026-10-01 19:08:23'),(89,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-02\",\"to\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:08:33','2026-10-01 19:08:33'),(90,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:08:37','2026-10-01 19:08:37'),(91,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:27:46','2026-10-01 19:27:46'),(92,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:28:23','2026-10-01 19:28:23'),(93,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-02\",\"to\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:29:40','2026-10-01 19:29:40'),(94,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:38:39','2026-10-01 19:38:39'),(95,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:38:54','2026-10-01 19:38:54'),(96,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:39:33','2026-10-01 19:39:33'),(97,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"weekly\",\"start_date\":\"2026-09-28\",\"end_date\":\"2026-10-04\"}',NULL,'127.0.0.1','2026-10-01 19:39:33','2026-10-01 19:39:33'),(98,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"weekly\",\"start_date\":\"2026-09-28\",\"end_date\":\"2026-10-04\"}',NULL,'127.0.0.1','2026-10-01 19:40:28','2026-10-01 19:40:28'),(99,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"custom\",\"start_date\":\"2026-10-05\",\"end_date\":\"2026-10-01\"}',NULL,'127.0.0.1','2026-10-01 19:40:58','2026-10-01 19:40:58'),(100,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"custom\",\"start_date\":\"2026-10-05\",\"end_date\":\"2026-10-01\"}',NULL,'127.0.0.1','2026-10-01 19:41:10','2026-10-01 19:41:10'),(101,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:42:20','2026-10-01 19:42:20'),(102,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:42:41','2026-10-01 19:42:41'),(103,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:43:00','2026-10-01 19:43:00'),(104,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-02\",\"end_date\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 19:43:25','2026-10-01 19:43:25'),(105,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"weekly\",\"start_date\":\"2026-09-28\",\"end_date\":\"2026-10-04\"}',NULL,'127.0.0.1','2026-10-01 19:43:26','2026-10-01 19:43:26'),(106,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"custom\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-05\"}',NULL,'127.0.0.1','2026-10-01 19:43:35','2026-10-01 19:43:35'),(107,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"custom\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-05\"}',NULL,'127.0.0.1','2026-10-01 19:43:36','2026-10-01 19:43:36'),(108,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-02\",\"to\":\"2026-10-02\"}',NULL,'127.0.0.1','2026-10-01 20:08:22','2026-10-01 20:08:22'),(109,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-02 05:52:30','2026-10-02 05:52:30'),(110,'Maria Santos','manager',2,'shift_started','POS','App\\Models\\CashierShift',4,'{\"shift_id\":4,\"cashier\":\"Maria Santos\",\"started_at\":\"2026-10-02T21:52:55+08:00\",\"beginning_cash\":50000}',NULL,'127.0.0.1','2026-10-02 05:52:55','2026-10-02 05:52:55'),(111,'Maria Santos','manager',2,'stock_adjustment','Inventory','App\\Models\\InventoryTransaction',155,'{\"ingredient\":\"Sola Raspberry Can 330ml\",\"type\":\"adjustment_deduct\",\"quantity\":179.983,\"unit\":\"pc\",\"reason\":\"Goes to the other branch\",\"previous_stock\":\"200.000\",\"new_stock\":\"20.017\"}','Goes to the other branch','127.0.0.1','2026-10-02 05:54:52','2026-10-02 05:54:52'),(112,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-05 05:07:11','2026-10-05 05:07:11'),(113,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',12,'{\"order_number\":\"ORD-20261005-9086\",\"cashier_name\":\"Admin Owner\",\"total\":\"470.00\",\"items\":3}',NULL,'127.0.0.1','2026-10-05 05:10:04','2026-10-05 05:10:04'),(114,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-05\",\"end_date\":\"2026-10-05\"}',NULL,'127.0.0.1','2026-10-05 07:52:54','2026-10-05 07:52:54'),(115,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-05\",\"to\":\"2026-10-05\"}',NULL,'127.0.0.1','2026-10-05 07:52:58','2026-10-05 07:52:58'),(116,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-05\",\"to\":\"2026-10-05\"}',NULL,'127.0.0.1','2026-10-05 07:53:55','2026-10-05 07:53:55'),(117,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',13,'{\"order_number\":\"ORD-20261005-5944\",\"cashier_name\":\"Admin Owner\",\"total\":\"470.00\",\"items\":3}',NULL,'127.0.0.1','2026-10-05 07:56:15','2026-10-05 07:56:15'),(118,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-05 16:11:24','2026-10-05 16:11:24'),(119,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-06\",\"end_date\":\"2026-10-06\"}',NULL,'127.0.0.1','2026-10-05 17:43:25','2026-10-05 17:43:25'),(120,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-05 19:47:38','2026-10-05 19:47:38'),(121,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-06\",\"end_date\":\"2026-10-06\"}',NULL,'127.0.0.1','2026-10-05 20:52:06','2026-10-05 20:52:06'),(122,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.140.0 Chrome\\/150.0.7871.250 Electron\\/43.7.3 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-05 21:32:08','2026-10-05 21:32:08'),(123,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',14,'{\"order_number\":\"ORD-20261006-7953\",\"cashier_name\":\"Admin Owner\",\"total\":\"140.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-06 00:31:56','2026-10-06 00:31:56'),(124,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',15,'{\"order_number\":\"ORD-20261006-0374\",\"cashier_name\":\"Admin Owner\",\"total\":\"140.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-06 00:33:28','2026-10-06 00:33:28'),(125,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',16,'{\"order_number\":\"ORD-20261006-7039\",\"cashier_name\":\"Admin Owner\",\"total\":\"280.00\",\"items\":2}',NULL,'127.0.0.1','2026-10-06 00:58:03','2026-10-06 00:58:03'),(126,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',17,'{\"order_number\":\"ORD-20261006-0999\",\"cashier_name\":\"Admin Owner\",\"total\":\"280.00\",\"items\":2}',NULL,'127.0.0.1','2026-10-06 00:58:39','2026-10-06 00:58:39'),(127,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-06 02:52:50','2026-10-06 02:52:50'),(128,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-06\",\"end_date\":\"2026-10-06\"}',NULL,'127.0.0.1','2026-10-06 03:02:12','2026-10-06 03:02:12'),(129,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-06\",\"to\":\"2026-10-06\"}',NULL,'127.0.0.1','2026-10-06 03:02:27','2026-10-06 03:02:27'),(130,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-06\",\"end_date\":\"2026-10-06\"}',NULL,'127.0.0.1','2026-10-06 03:02:58','2026-10-06 03:02:58'),(131,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 04:07:18','2026-10-08 04:07:18'),(132,'POS Cashier','cashier',4,'held_order','POS','App\\Models\\Order',18,'{\"order_number\":\"ORD-20261008-1574\",\"cashier_name\":\"POS Cashier\",\"total\":\"150.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-08 04:09:01','2026-10-08 04:09:01'),(133,'POS Cashier','cashier',4,'completed_order','POS','App\\Models\\Order',19,'{\"order_number\":\"ORD-20261008-1385\",\"cashier_name\":\"POS Cashier\",\"total\":150,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-10-08 04:09:18','2026-10-08 04:09:18'),(134,'POS Cashier','cashier',4,'completed_order','POS','App\\Models\\Order',20,'{\"order_number\":\"ORD-20261008-6301\",\"cashier_name\":\"POS Cashier\",\"total\":150,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-10-08 04:09:42','2026-10-08 04:09:42'),(135,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 04:14:40','2026-10-08 04:14:40'),(136,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 04:14:47','2026-10-08 04:14:47'),(137,'Maria Santos','manager',2,'completed_order','POS','App\\Models\\Order',21,'{\"order_number\":\"ORD-20261008-0675\",\"cashier_name\":\"Maria Santos\",\"total\":140,\"items\":1,\"payment\":\"cash\"}',NULL,'127.0.0.1','2026-10-08 04:15:00','2026-10-08 04:15:00'),(138,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 04:30:55','2026-10-08 04:30:55'),(139,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 04:31:01','2026-10-08 04:31:01'),(140,'POS Cashier','cashier',4,'completed_order','POS','App\\Models\\Order',22,'{\"order_number\":\"ORD-20261008-2885\",\"cashier_name\":\"POS Cashier\",\"total\":150,\"items\":1,\"payments\":1,\"payment_total\":150}',NULL,'127.0.0.1','2026-10-08 05:25:10','2026-10-08 05:25:10'),(141,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 05:27:54','2026-10-08 05:27:54'),(142,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 05:28:01','2026-10-08 05:28:01'),(143,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 05:48:27','2026-10-08 05:48:27'),(144,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 05:52:58','2026-10-08 05:52:58'),(145,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 05:53:13','2026-10-08 05:53:13'),(146,'Maria Santos','manager',2,'updated_product','Products','App\\Models\\Product',71,'{\"product\":\"Blueberry Lemonade (with popping bobba)\"}',NULL,'127.0.0.1','2026-10-08 05:53:52','2026-10-08 05:53:52'),(147,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-08\",\"end_date\":\"2026-10-08\"}',NULL,'127.0.0.1','2026-10-08 05:54:53','2026-10-08 05:54:53'),(148,'Maria Santos','manager',2,'held_order','POS','App\\Models\\Order',23,'{\"order_number\":\"ORD-20261008-2553\",\"cashier_name\":\"Maria Santos\",\"total\":\"259.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-08 06:20:47','2026-10-08 06:20:47'),(149,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 07:40:48','2026-10-08 07:40:48'),(150,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-08\",\"end_date\":\"2026-10-08\"}',NULL,'127.0.0.1','2026-10-08 07:43:32','2026-10-08 07:43:32'),(151,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-08\",\"to\":\"2026-10-08\"}',NULL,'127.0.0.1','2026-10-08 07:43:39','2026-10-08 07:43:39'),(152,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-08\",\"end_date\":\"2026-10-08\"}',NULL,'127.0.0.1','2026-10-08 07:43:45','2026-10-08 07:43:45'),(153,'Admin Owner','owner',1,'updated_addon','Menu','App\\Models\\ProductAddon',7,'{\"addon\":\"Cheesy Buffalo (Creamy Cheese Drip)\",\"price\":50}',NULL,'127.0.0.1','2026-10-08 07:46:32','2026-10-08 07:46:32'),(154,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 08:01:04','2026-10-08 08:01:04'),(155,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-08 08:01:23','2026-10-08 08:01:23'),(156,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:43:21','2026-10-09 01:43:21'),(157,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:43:33','2026-10-09 01:43:33'),(158,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:43:39','2026-10-09 01:43:39'),(159,'POS Cashier','cashier',4,'shift_ended','POS','App\\Models\\CashierShift',1,'{\"shift_id\":1,\"beginning_cash\":9000,\"cash_sales\":450,\"cash_refunds\":0,\"cash_voids\":0,\"expected_cash\":9450,\"actual_cash\":9999.86,\"difference\":549.86,\"notes\":\"all goods\",\"manager_override\":false,\"override_reason\":null}',NULL,'127.0.0.1','2026-10-09 01:44:59','2026-10-09 01:44:59'),(160,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:45:33','2026-10-09 01:45:33'),(161,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:45:39','2026-10-09 01:45:39'),(162,'Maria Santos','manager',2,'held_order','POS','App\\Models\\Order',25,'{\"order_number\":\"ORD-20261009-4371\",\"cashier_name\":\"Maria Santos\",\"total\":\"300.00\",\"items\":2}',NULL,'127.0.0.1','2026-10-09 01:49:59','2026-10-09 01:49:59'),(163,'Maria Santos','manager',2,'held_order','POS','App\\Models\\Order',26,'{\"order_number\":\"ORD-20261009-0339\",\"cashier_name\":\"Maria Santos\",\"total\":\"259.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-09 01:51:08','2026-10-09 01:51:08'),(164,'Maria Santos','manager',2,'voided_saved_ticket','POS',NULL,NULL,'{\"order_number\":\"ORD-20261009-0339\",\"reason\":\"testtt\"}','testtt','127.0.0.1','2026-10-09 01:51:27','2026-10-09 01:51:27'),(165,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 01:54:43','2026-10-09 01:54:43'),(166,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-09\",\"to\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 01:54:58','2026-10-09 01:54:58'),(167,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 01:55:11','2026-10-09 01:55:11'),(168,'Maria Santos','manager',2,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:57:24','2026-10-09 01:57:24'),(169,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:57:31','2026-10-09 01:57:31'),(170,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:57:51','2026-10-09 01:57:51'),(171,'POS Cashier','cashier',4,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 01:57:58','2026-10-09 01:57:58'),(172,'POS Cashier','cashier',4,'shift_started','POS','App\\Models\\CashierShift',6,'{\"shift_id\":6,\"cashier\":\"POS Cashier\",\"started_at\":\"2026-10-09T18:00:39+08:00\",\"beginning_cash\":2000}',NULL,'127.0.0.1','2026-10-09 02:00:39','2026-10-09 02:00:39'),(173,'POS Cashier','cashier',4,'shift_ended','POS','App\\Models\\CashierShift',6,'{\"shift_id\":6,\"beginning_cash\":2000,\"cash_sales\":0,\"cash_refunds\":0,\"cash_voids\":0,\"expected_cash\":2000,\"actual_cash\":2000,\"difference\":0,\"notes\":\"end\",\"manager_override\":false,\"override_reason\":null}',NULL,'127.0.0.1','2026-10-09 02:02:05','2026-10-09 02:02:05'),(174,'POS Cashier','cashier',4,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 02:03:51','2026-10-09 02:03:51'),(175,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 02:03:58','2026-10-09 02:03:58'),(176,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 02:04:24','2026-10-09 02:04:24'),(177,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 02:09:16','2026-10-09 02:09:16'),(178,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-09\",\"to\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 02:09:22','2026-10-09 02:09:22'),(179,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 02:09:29','2026-10-09 02:09:29'),(180,'Admin Owner','owner',1,'updated_product','Products','App\\Models\\Product',1,'{\"product\":\"Americano\"}',NULL,'127.0.0.1','2026-10-09 02:35:51','2026-10-09 02:35:51'),(181,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',27,'{\"order_number\":\"ORD-20261009-7615\",\"cashier_name\":\"Admin Owner\",\"total\":\"280.00\",\"items\":2}',NULL,'127.0.0.1','2026-10-09 02:50:10','2026-10-09 02:50:10'),(182,'Admin Owner','owner',1,'held_order','POS','App\\Models\\Order',28,'{\"order_number\":\"ORD-20261009-0489\",\"cashier_name\":\"Admin Owner\",\"total\":\"140.00\",\"items\":1}',NULL,'127.0.0.1','2026-10-09 02:50:30','2026-10-09 02:50:30'),(183,'Admin Owner','owner',1,'voided_saved_ticket','POS',NULL,NULL,'{\"order_number\":\"ORD-20261009-0489\",\"reason\":\"testing\"}','testing','127.0.0.1','2026-10-09 03:08:28','2026-10-09 03:08:28'),(184,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 03:09:48','2026-10-09 03:09:48'),(185,'Admin Owner','owner',1,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-09\",\"to\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 03:09:53','2026-10-09 03:09:53'),(186,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-09\",\"end_date\":\"2026-10-09\"}',NULL,'127.0.0.1','2026-10-09 03:09:56','2026-10-09 03:09:56'),(187,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 03:38:37','2026-10-09 03:38:37'),(188,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 06:09:09','2026-10-09 06:09:09'),(189,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 06:48:58','2026-10-09 06:48:58'),(190,'Admin Owner','owner',1,'created_sample_user','Users','App\\Models\\User',6,'{\"new_user\":\"Bangkal Sample Manager\",\"role\":\"manager\",\"branch\":\"Bangkal Branch\"}',NULL,'127.0.0.1','2026-10-09 08:31:44','2026-10-09 08:31:44'),(191,'Admin Owner','owner',1,'created_sample_user','Users','App\\Models\\User',7,'{\"new_user\":\"San Rafael Sample Manager\",\"role\":\"manager\",\"branch\":\"San Rafael Branch\"}',NULL,'127.0.0.1','2026-10-09 08:31:44','2026-10-09 08:31:44'),(192,'Admin Owner','owner',1,'branch_opening_stock_copied','Inventory',NULL,NULL,'{\"source_branch\":\"Bangkal Branch\",\"destination_branch\":\"San Rafael Branch\",\"ingredient_balances_created\":72,\"opening_stock_movements_created\":72,\"sales_or_consumption_copied\":false}',NULL,'127.0.0.1','2026-10-09 08:35:26','2026-10-09 08:35:26'),(193,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 08:44:22','2026-10-09 08:44:22'),(194,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/155.0.0.0 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-09 08:44:30','2026-10-09 08:44:30'),(195,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":null}',NULL,'127.0.0.1','2026-10-09 08:44:57','2026-10-09 08:44:57'),(196,'Admin Owner','owner',1,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":null}',NULL,'127.0.0.1','2026-10-09 08:45:21','2026-10-09 08:45:21'),(197,'Admin Owner','owner',1,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-10 01:12:17','2026-10-10 01:12:17'),(198,'Admin Owner','owner',1,'logged_out','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-10 01:14:25','2026-10-10 01:14:25'),(199,'Maria Santos','manager',2,'logged_in','Auth',NULL,NULL,'{\"ip\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Code\\/1.141.0 Chrome\\/150.0.7871.250 Electron\\/43.7.7 Safari\\/537.36\"}',NULL,'127.0.0.1','2026-10-10 01:14:44','2026-10-10 01:14:44'),(200,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:36:53','2026-10-10 02:36:53'),(201,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:37:59','2026-10-10 02:37:59'),(202,'Maria Santos','manager',2,'generated_inventory_report','Reports',NULL,NULL,'{\"from\":\"2026-10-10\",\"to\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:38:00','2026-10-10 02:38:00'),(203,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:41:23','2026-10-10 02:41:23'),(204,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:46:44','2026-10-10 02:46:44'),(205,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:46:44','2026-10-10 02:46:44'),(206,'Maria Santos','manager',2,'generated_sales_report','Reports',NULL,NULL,'{\"type\":\"daily\",\"start_date\":\"2026-10-10\",\"end_date\":\"2026-10-10\",\"branch_id\":1}',NULL,'127.0.0.1','2026-10-10 02:46:53','2026-10-10 02:46:53');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Bangkal Branch','BANGKAL',NULL,NULL,1,'2026-10-09 03:51:09','2026-10-09 07:24:11'),(3,'San Rafael Branch','SAN-RAFAEL',NULL,NULL,1,'2026-10-09 07:22:38','2026-10-09 07:22:38');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashier_shifts`
--

DROP TABLE IF EXISTS `cashier_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashier_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `cashier_name` varchar(255) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `beginning_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_refunds` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expected_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_cash` decimal(10,2) DEFAULT NULL,
  `difference` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `open_user_id` bigint(20) unsigned DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `denomination_count` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`denomination_count`)),
  `cash_voids` decimal(10,2) NOT NULL DEFAULT 0.00,
  `online_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grab_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grab_settlements` decimal(10,2) NOT NULL DEFAULT 0.00,
  `void_count` int(10) unsigned NOT NULL DEFAULT 0,
  `void_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dine_in_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `take_out_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cashier_shifts_open_user_id_unique` (`open_user_id`),
  KEY `cashier_shifts_user_id_foreign` (`user_id`),
  KEY `cashier_shifts_closed_by_foreign` (`closed_by`),
  KEY `cashier_shifts_reviewed_by_foreign` (`reviewed_by`),
  KEY `cashier_shifts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `cashier_shifts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cashier_shifts_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cashier_shifts_open_user_id_foreign` FOREIGN KEY (`open_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cashier_shifts_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cashier_shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_shifts`
--

LOCK TABLES `cashier_shifts` WRITE;
/*!40000 ALTER TABLE `cashier_shifts` DISABLE KEYS */;
INSERT INTO `cashier_shifts` VALUES (1,4,'POS Cashier','2026-10-01','2026-10-09 11:51:09','2026-10-09 01:44:59',9000.00,450.00,0.00,9450.00,9999.86,549.86,'all goods','closed','2026-10-01 11:29:28','2026-10-09 01:44:59',NULL,4,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,450.00,0.00,1),(2,1,'Admin Owner','2026-10-01','2026-10-09 11:51:09','2026-10-01 18:08:51',5000.00,0.00,0.00,5000.00,90000.00,85000.00,'end','closed','2026-10-01 12:35:32','2026-10-01 18:08:51',NULL,1,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,0.00,0.00,1),(3,1,'Admin Owner','2026-10-02','2026-10-09 11:51:09',NULL,2000.00,0.00,0.00,2000.00,NULL,NULL,NULL,'open','2026-10-01 18:28:51','2026-10-01 18:28:51',1,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,0.00,0.00,1),(4,2,'Maria Santos','2026-10-02','2026-10-09 11:51:09',NULL,50000.00,0.00,0.00,50000.00,NULL,NULL,NULL,'open','2026-10-02 05:52:55','2026-10-02 05:52:55',2,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,0.00,0.00,1),(5,5,'Charles Reyes','2026-10-08','2026-10-09 11:51:09',NULL,500.00,0.00,0.00,500.00,NULL,NULL,NULL,'open','2026-10-08 08:08:01','2026-10-08 08:08:01',NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,0.00,0.00,1),(6,4,'POS Cashier','2026-10-09','2026-10-09 11:51:09','2026-10-09 02:02:05',2000.00,0.00,0.00,2000.00,2000.00,0.00,'end','closed','2026-10-09 02:00:39','2026-10-09 02:02:05',NULL,4,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0,0.00,0.00,0.00,1);
/*!40000 ALTER TABLE `cashier_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Espresso','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(2,'Cold Brew','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(3,'Matcha Series','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(4,'Non-Coffee','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(5,'Refreshers','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(6,'Chicken Wings','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(7,'Spicy Korean Wings','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(8,'Buldak Series','active','2026-09-26 17:55:04','2026-10-01 09:31:51'),(9,'Chicken Burgers','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(10,'Quesadillas','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(11,'Desserts','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(12,'Drinks','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(13,'Coffee','active','2026-10-08 08:08:01','2026-10-08 08:08:01');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `minimum_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reorder_level` decimal(10,3) NOT NULL DEFAULT 0.000,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ingredients_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `ingredients_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (1,'Espresso Shot','ml',500.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(2,'Cold Brew Concentrate','ml',500.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(3,'Fresh Milk','ml',2000.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(4,'Oat Milk','ml',1000.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(5,'Heavy Cream','ml',500.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(6,'Simple Syrup','ml',500.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(7,'Caramel Syrup','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(8,'Hazelnut Syrup','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(9,'Cinnamon Syrup','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(10,'Honey','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(11,'Condensed Milk','ml',500.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(12,'Chocolate Powder','g',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(13,'Matcha Powder','g',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(14,'Milo Powder','g',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(15,'Hojicha Powder','g',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(16,'Cookie Crumb','g',100.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(17,'Strawberry Puree','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(18,'Orange Juice','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(19,'Lychee Syrup','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(20,'Apple Juice','ml',300.000,'active','2026-09-26 17:55:03','2026-09-27 05:45:59',0.000,0.00,NULL,NULL),(21,'Passion Fruit Syrup','ml',300.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(22,'Salted Cream Foam','ml',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(23,'Sea Salt','g',50.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(24,'Ice','g',5000.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(25,'Hot 8oz Cup','pc',100.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(26,'Iced 12oz Cup','pc',100.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(27,'Iced 16oz Cup','pc',100.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(28,'Lid','pc',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(29,'Straw','pc',200.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(30,'Hot Cup Sleeve','pc',100.000,'active','2026-09-26 17:55:03','2026-09-26 17:55:03',0.000,0.00,NULL,NULL),(31,'Chicken Wing','pc',100.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(32,'Burger Patty','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(33,'Burger Bun','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(34,'Cheese Slice','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(35,'Burger Mayo','ml',500.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(36,'Hashbrown','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(37,'French Fries','g',1000.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(38,'Quesadilla Tortilla','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(39,'Rice Portion','g',1000.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(40,'Korean Spice Mix','g',200.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(41,'Buldak Spicy Cheese Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(42,'Buldak Extra Spicy Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(43,'Buldak Carbonara Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(44,'Buffalo Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(45,'Garlic Parmesan Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(46,'Honey Glaze Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(47,'Salted Egg Sauce','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(48,'Sour Cream Dip','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(49,'Cheese Sauce Dip','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(50,'Cheesy Buffalo Drip','ml',200.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(51,'Double Cheese Drip','ml',200.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(52,'Brownie Slice','pc',30.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(53,'Ice Cream Scoop','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(54,'Mini Egg Tart','pc',30.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(55,'Matcha Cheese Tart Portion','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(56,'Guava Puree','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(57,'Blueberry Syrup','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(58,'Lemon Juice','ml',300.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(59,'Popping Bobba','g',200.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(60,'Coke 1.75L Bottle','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(61,'Coke 1.5L Bottle','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(62,'Mountain Dew 1.5L Bottle','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(63,'Coke Can 330ml','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(64,'Coke Zero Can 330ml','pc',30.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(65,'Sprite Can 330ml','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(66,'Royal Can 330ml','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(67,'Mountain Dew Can 330ml','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(68,'Sola Lemon Can 330ml','pc',30.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(69,'Sola Raspberry Can 330ml','pc',30.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(70,'Sola Lemon Bottle','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(71,'Sola Raspberry Bottle','pc',20.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL),(72,'Bottled Water 500ml','pc',50.000,'active','2026-09-26 17:55:04','2026-09-26 17:55:04',0.000,0.00,NULL,NULL);
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `current_stock` decimal(10,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_ingredient_id_branch_id_unique` (`ingredient_id`,`branch_id`),
  KEY `inventory_branch_id_foreign` (`branch_id`),
  CONSTRAINT `inventory_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,1,3950.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(2,2,4100.000,'2026-09-26 17:55:03','2026-10-01 10:29:07',1),(3,3,11850.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(4,4,7640.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(5,5,3920.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(6,6,4960.000,'2026-09-26 17:55:03','2026-10-01 08:51:07',1),(7,7,2980.000,'2026-09-26 17:55:03','2026-09-26 20:10:24',1),(8,8,2970.000,'2026-09-26 17:55:03','2026-10-01 08:51:07',1),(9,9,2945.000,'2026-09-26 17:55:03','2026-10-08 04:09:42',1),(10,10,2000.000,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(11,11,3820.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(12,12,2945.000,'2026-09-26 17:55:03','2026-10-01 08:51:07',1),(13,13,1992.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(14,14,2470.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(15,15,2000.000,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(16,16,1470.000,'2026-09-26 17:55:03','2026-10-01 08:51:07',1),(17,17,2960.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(18,18,2880.000,'2026-09-26 17:55:03','2026-09-27 05:40:33',1),(19,19,2460.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(20,20,2380.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(21,21,2500.000,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(22,22,1880.000,'2026-09-26 17:55:03','2026-10-01 10:29:07',1),(23,23,500.000,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(24,24,26720.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(25,25,990.000,'2026-09-26 17:55:03','2026-10-08 04:15:00',1),(26,26,985.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(27,27,995.000,'2026-09-26 17:55:03','2026-09-28 09:32:23',1),(28,28,1980.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(29,29,1980.000,'2026-09-26 17:55:03','2026-10-08 05:25:10',1),(30,30,990.000,'2026-09-26 17:55:03','2026-10-08 04:15:00',1),(31,31,1500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(32,32,500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(33,33,500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(34,34,500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(35,35,5000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(36,36,400.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(37,37,15000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(38,38,500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(39,39,20000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(40,40,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(41,41,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(42,42,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(43,43,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(44,44,4000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(45,45,4000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(46,46,4000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(47,47,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(48,48,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(49,49,4000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(50,50,2000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(51,51,2000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(52,52,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(53,53,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(54,54,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(55,55,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(56,56,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(57,57,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(58,58,3000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(59,59,2000.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(60,60,100.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(61,61,100.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(62,62,100.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(63,63,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(64,64,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(65,65,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(66,66,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(67,67,300.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(68,68,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(69,69,20.017,'2026-09-26 17:55:04','2026-10-02 05:54:52',1),(70,70,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(71,71,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(72,72,500.000,'2026-09-26 17:55:04','2026-09-26 17:55:04',1),(73,1,3950.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(74,2,4100.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(75,3,11850.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(76,4,7640.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(77,5,3920.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(78,6,4960.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(79,7,2980.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(80,8,2970.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(81,9,2945.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(82,10,2000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(83,11,3820.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(84,12,2945.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(85,13,1992.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(86,14,2470.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(87,15,2000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(88,16,1470.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(89,17,2960.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(90,18,2880.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(91,19,2460.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(92,20,2380.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(93,21,2500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(94,22,1880.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(95,23,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(96,24,26720.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(97,25,990.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(98,26,985.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(99,27,995.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(100,28,1980.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(101,29,1980.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(102,30,990.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(103,31,1500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(104,32,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(105,33,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(106,34,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(107,35,5000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(108,36,400.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(109,37,15000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(110,38,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(111,39,20000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(112,40,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(113,41,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(114,42,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(115,43,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(116,44,4000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(117,45,4000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(118,46,4000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(119,47,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(120,48,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(121,49,4000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(122,50,2000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(123,51,2000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(124,52,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(125,53,200.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(126,54,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(127,55,200.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(128,56,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(129,57,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(130,58,3000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(131,59,2000.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(132,60,100.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(133,61,100.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(134,62,100.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(135,63,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(136,64,200.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(137,65,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(138,66,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(139,67,300.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(140,68,200.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(141,69,20.017,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(142,70,150.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(143,71,150.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3),(144,72,500.000,'2026-10-09 08:35:26','2026-10-09 08:35:26',3);
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `type` enum('stock_in','sales_consumption','sales_return','waste','adjustment_add','adjustment_deduct') NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `previous_stock` decimal(10,3) NOT NULL,
  `new_stock` decimal(10,3) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `performed_by` varchar(255) NOT NULL,
  `performed_role` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_ingredient_id_foreign` (`ingredient_id`),
  KEY `inventory_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_transactions_supplier_id_foreign` (`supplier_id`),
  KEY `inventory_transactions_branch_id_foreign` (`branch_id`),
  CONSTRAINT `inventory_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `inventory_transactions_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
INSERT INTO `inventory_transactions` VALUES (5,1,'sales_consumption',60.000,5000.000,4940.000,'App\\Models\\Order',2,'Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(6,3,'sales_consumption',160.000,15000.000,14840.000,'App\\Models\\Order',2,'Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(7,7,'sales_consumption',20.000,3000.000,2980.000,'App\\Models\\Order',2,'Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(8,25,'sales_consumption',1.000,999.000,998.000,'App\\Models\\Order',2,'Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(9,30,'sales_consumption',1.000,999.000,998.000,'App\\Models\\Order',2,'Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(10,1,'sales_consumption',30.000,4940.000,4910.000,'App\\Models\\Order',2,'Addon \'Extra Shot\' — Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(11,4,'sales_consumption',180.000,8000.000,7820.000,'App\\Models\\Order',2,'Addon \'Oat Milk Substitute\' — Sale: ORD-20260927-WSEG','Carlos Reyes','supervisor',NULL,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,1),(12,1,'sales_consumption',60.000,4910.000,4850.000,'App\\Models\\Order',3,'Sale: ORD-20260927-SWNW','Admin Owner','owner',NULL,NULL,'2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,NULL,NULL,1),(13,25,'sales_consumption',1.000,998.000,997.000,'App\\Models\\Order',3,'Sale: ORD-20260927-SWNW','Admin Owner','owner',NULL,NULL,'2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,NULL,NULL,1),(14,30,'sales_consumption',1.000,998.000,997.000,'App\\Models\\Order',3,'Sale: ORD-20260927-SWNW','Admin Owner','owner',NULL,NULL,'2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,NULL,NULL,1),(15,1,'sales_consumption',60.000,4850.000,4790.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(16,6,'sales_consumption',10.000,5000.000,4990.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(17,18,'sales_consumption',120.000,3000.000,2880.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(18,24,'sales_consumption',150.000,30000.000,29850.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(19,26,'sales_consumption',1.000,1000.000,999.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(20,28,'sales_consumption',1.000,2000.000,1999.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(21,29,'sales_consumption',1.000,2000.000,1999.000,'App\\Models\\Order',4,'Sale: ORD-20260927-P8U5','Admin Owner','owner',NULL,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,1),(22,1,'sales_consumption',120.000,4790.000,4670.000,'App\\Models\\Order',5,'Sale: ORD-20260927-VW2X','Admin Owner','owner',NULL,NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,NULL,NULL,1),(23,3,'sales_consumption',360.000,14840.000,14480.000,'App\\Models\\Order',5,'Sale: ORD-20260927-VW2X','Admin Owner','owner',NULL,NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,NULL,NULL,1),(24,25,'sales_consumption',2.000,997.000,995.000,'App\\Models\\Order',5,'Sale: ORD-20260927-VW2X','Admin Owner','owner',NULL,NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,NULL,NULL,1),(25,30,'sales_consumption',2.000,997.000,995.000,'App\\Models\\Order',5,'Sale: ORD-20260927-VW2X','Admin Owner','owner',NULL,NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,NULL,NULL,1),(26,20,'stock_in',20.000,2500.000,2520.000,NULL,NULL,'Stock-in','Admin Owner','owner',NULL,NULL,'2026-09-27 05:45:33','2026-09-27 05:45:33',NULL,NULL,NULL,1),(27,2,'sales_consumption',120.000,5000.000,4880.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(28,3,'sales_consumption',60.000,14480.000,14420.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(29,11,'sales_consumption',30.000,4000.000,3970.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(30,24,'sales_consumption',150.000,29850.000,29700.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(31,26,'sales_consumption',1.000,999.000,998.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(32,28,'sales_consumption',1.000,1999.000,1998.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(33,29,'sales_consumption',1.000,1999.000,1998.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(34,1,'sales_consumption',60.000,4670.000,4610.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(35,3,'sales_consumption',150.000,14420.000,14270.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(36,22,'sales_consumption',30.000,2000.000,1970.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(37,24,'sales_consumption',150.000,29700.000,29550.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(38,26,'sales_consumption',1.000,998.000,997.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(39,28,'sales_consumption',1.000,1998.000,1997.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(40,29,'sales_consumption',1.000,1998.000,1997.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(41,1,'sales_consumption',60.000,4610.000,4550.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(42,3,'sales_consumption',140.000,14270.000,14130.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(43,8,'sales_consumption',15.000,3000.000,2985.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(44,12,'sales_consumption',10.000,3000.000,2990.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(45,25,'sales_consumption',1.000,995.000,994.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(46,30,'sales_consumption',1.000,995.000,994.000,'App\\Models\\Order',6,'Sale: ORD-20260927-APYK','Maria Santos','manager',NULL,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,1),(47,1,'sales_consumption',60.000,4550.000,4490.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(48,3,'sales_consumption',150.000,14130.000,13980.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(49,22,'sales_consumption',30.000,1970.000,1940.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(50,24,'sales_consumption',150.000,29550.000,29400.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(51,26,'sales_consumption',1.000,997.000,996.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(52,28,'sales_consumption',1.000,1997.000,1996.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(53,29,'sales_consumption',1.000,1997.000,1996.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(54,2,'sales_consumption',120.000,4880.000,4760.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(55,3,'sales_consumption',60.000,13980.000,13920.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(56,11,'sales_consumption',30.000,3970.000,3940.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(57,24,'sales_consumption',150.000,29400.000,29250.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(58,26,'sales_consumption',1.000,996.000,995.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(59,28,'sales_consumption',1.000,1996.000,1995.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(60,29,'sales_consumption',1.000,1996.000,1995.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(61,4,'sales_consumption',180.000,7820.000,7640.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(62,13,'sales_consumption',8.000,2000.000,1992.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(63,24,'sales_consumption',200.000,29250.000,29050.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(64,27,'sales_consumption',1.000,1000.000,999.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(65,28,'sales_consumption',1.000,1995.000,1994.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(66,29,'sales_consumption',1.000,1995.000,1994.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(67,3,'sales_consumption',100.000,13920.000,13820.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(68,5,'sales_consumption',80.000,4000.000,3920.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(69,17,'sales_consumption',40.000,3000.000,2960.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(70,24,'sales_consumption',200.000,29050.000,28850.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(71,27,'sales_consumption',1.000,999.000,998.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(72,28,'sales_consumption',1.000,1994.000,1993.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(73,29,'sales_consumption',1.000,1994.000,1993.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(74,3,'sales_consumption',200.000,13820.000,13620.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(75,12,'sales_consumption',20.000,2990.000,2970.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(76,16,'sales_consumption',15.000,1500.000,1485.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(77,24,'sales_consumption',200.000,28850.000,28650.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(78,27,'sales_consumption',1.000,998.000,997.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(79,28,'sales_consumption',1.000,1993.000,1992.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(80,29,'sales_consumption',1.000,1993.000,1992.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(81,3,'sales_consumption',200.000,13620.000,13420.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(82,14,'sales_consumption',30.000,2500.000,2470.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(83,24,'sales_consumption',200.000,28650.000,28450.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(84,27,'sales_consumption',1.000,997.000,996.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(85,28,'sales_consumption',1.000,1992.000,1991.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(86,29,'sales_consumption',1.000,1992.000,1991.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(87,19,'sales_consumption',40.000,2500.000,2460.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(88,20,'sales_consumption',140.000,2520.000,2380.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(89,24,'sales_consumption',200.000,28450.000,28250.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(90,27,'sales_consumption',1.000,996.000,995.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(91,28,'sales_consumption',1.000,1991.000,1990.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(92,29,'sales_consumption',1.000,1991.000,1990.000,'App\\Models\\Order',7,'Sale: ORD-20260928-O2VQ','POS Cashier','cashier',NULL,NULL,'2026-09-28 09:32:23','2026-09-28 09:32:23',NULL,NULL,NULL,1),(93,1,'sales_consumption',60.000,4490.000,4430.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(94,3,'sales_consumption',140.000,13420.000,13280.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(95,8,'sales_consumption',15.000,2985.000,2970.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(96,12,'sales_consumption',10.000,2970.000,2960.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(97,25,'sales_consumption',1.000,994.000,993.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(98,30,'sales_consumption',1.000,994.000,993.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(99,1,'sales_consumption',60.000,4430.000,4370.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(100,3,'sales_consumption',150.000,13280.000,13130.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(101,22,'sales_consumption',30.000,1940.000,1910.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(102,24,'sales_consumption',150.000,28250.000,28100.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(103,26,'sales_consumption',1.000,995.000,994.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(104,28,'sales_consumption',1.000,1990.000,1989.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(105,29,'sales_consumption',1.000,1990.000,1989.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(106,2,'sales_consumption',120.000,4760.000,4640.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(107,3,'sales_consumption',60.000,13130.000,13070.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(108,11,'sales_consumption',30.000,3940.000,3910.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(109,24,'sales_consumption',150.000,28100.000,27950.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(110,26,'sales_consumption',1.000,994.000,993.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(111,28,'sales_consumption',1.000,1989.000,1988.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(112,29,'sales_consumption',1.000,1989.000,1988.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(113,2,'sales_consumption',240.000,4640.000,4400.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(114,3,'sales_consumption',160.000,13070.000,12910.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(115,6,'sales_consumption',20.000,4990.000,4970.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(116,24,'sales_consumption',300.000,27950.000,27650.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(117,26,'sales_consumption',2.000,993.000,991.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(118,28,'sales_consumption',2.000,1988.000,1986.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(119,29,'sales_consumption',2.000,1988.000,1986.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(120,2,'sales_consumption',180.000,4400.000,4220.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(121,6,'sales_consumption',10.000,4970.000,4960.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(122,24,'sales_consumption',180.000,27650.000,27470.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(123,26,'sales_consumption',1.000,991.000,990.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(124,28,'sales_consumption',1.000,1986.000,1985.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(125,29,'sales_consumption',1.000,1986.000,1985.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(126,1,'sales_consumption',60.000,4370.000,4310.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(127,3,'sales_consumption',140.000,12910.000,12770.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(128,9,'sales_consumption',15.000,3000.000,2985.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(129,12,'sales_consumption',15.000,2960.000,2945.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(130,24,'sales_consumption',150.000,27470.000,27320.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(131,26,'sales_consumption',1.000,990.000,989.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(132,28,'sales_consumption',1.000,1985.000,1984.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(133,29,'sales_consumption',1.000,1985.000,1984.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(134,1,'sales_consumption',60.000,4310.000,4250.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(135,3,'sales_consumption',150.000,12770.000,12620.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(136,16,'sales_consumption',15.000,1485.000,1470.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(137,24,'sales_consumption',150.000,27320.000,27170.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(138,26,'sales_consumption',1.000,989.000,988.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(139,28,'sales_consumption',1.000,1984.000,1983.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(140,29,'sales_consumption',1.000,1984.000,1983.000,'App\\Models\\Order',8,'Sale: GB-20261001-IFWS','POS Cashier','cashier',NULL,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,1),(141,1,'sales_consumption',60.000,4250.000,4190.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(142,3,'sales_consumption',150.000,12620.000,12470.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(143,22,'sales_consumption',30.000,1910.000,1880.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(144,24,'sales_consumption',150.000,27170.000,27020.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(145,26,'sales_consumption',1.000,988.000,987.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(146,28,'sales_consumption',1.000,1983.000,1982.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(147,29,'sales_consumption',1.000,1983.000,1982.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(148,2,'sales_consumption',120.000,4220.000,4100.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(149,3,'sales_consumption',60.000,12470.000,12410.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(150,11,'sales_consumption',30.000,3910.000,3880.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(151,24,'sales_consumption',150.000,27020.000,26870.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(152,26,'sales_consumption',1.000,987.000,986.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(153,28,'sales_consumption',1.000,1982.000,1981.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(154,29,'sales_consumption',1.000,1982.000,1981.000,'App\\Models\\Order',9,'Sale: ORD-20261001-BDQV','Admin Owner','owner',NULL,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,1),(155,69,'adjustment_deduct',179.983,200.000,20.017,NULL,NULL,'Goes to the other branch','Maria Santos','manager',NULL,NULL,'2026-10-02 05:54:52','2026-10-02 05:54:52',NULL,NULL,NULL,1),(156,1,'sales_consumption',60.000,4190.000,4130.000,'App\\Models\\OrderItem',47,'Sale: ORD-20261008-1385','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,NULL,1),(157,3,'sales_consumption',160.000,12410.000,12250.000,'App\\Models\\OrderItem',47,'Sale: ORD-20261008-1385','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,NULL,1),(158,9,'sales_consumption',20.000,2985.000,2965.000,'App\\Models\\OrderItem',47,'Sale: ORD-20261008-1385','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,NULL,1),(159,25,'sales_consumption',1.000,993.000,992.000,'App\\Models\\OrderItem',47,'Sale: ORD-20261008-1385','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,NULL,1),(160,30,'sales_consumption',1.000,993.000,992.000,'App\\Models\\OrderItem',47,'Sale: ORD-20261008-1385','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,NULL,1),(161,1,'sales_consumption',60.000,4130.000,4070.000,'App\\Models\\OrderItem',48,'Sale: ORD-20261008-6301','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,NULL,1),(162,3,'sales_consumption',160.000,12250.000,12090.000,'App\\Models\\OrderItem',48,'Sale: ORD-20261008-6301','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,NULL,1),(163,9,'sales_consumption',20.000,2965.000,2945.000,'App\\Models\\OrderItem',48,'Sale: ORD-20261008-6301','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,NULL,1),(164,25,'sales_consumption',1.000,992.000,991.000,'App\\Models\\OrderItem',48,'Sale: ORD-20261008-6301','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,NULL,1),(165,30,'sales_consumption',1.000,992.000,991.000,'App\\Models\\OrderItem',48,'Sale: ORD-20261008-6301','POS Cashier','cashier',NULL,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,NULL,1),(166,1,'sales_consumption',60.000,4070.000,4010.000,'App\\Models\\OrderItem',49,'Sale: ORD-20261008-0675','Maria Santos','manager',NULL,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,NULL,1),(167,3,'sales_consumption',120.000,12090.000,11970.000,'App\\Models\\OrderItem',49,'Sale: ORD-20261008-0675','Maria Santos','manager',NULL,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,NULL,1),(168,11,'sales_consumption',30.000,3880.000,3850.000,'App\\Models\\OrderItem',49,'Sale: ORD-20261008-0675','Maria Santos','manager',NULL,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,NULL,1),(169,25,'sales_consumption',1.000,991.000,990.000,'App\\Models\\OrderItem',49,'Sale: ORD-20261008-0675','Maria Santos','manager',NULL,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,NULL,1),(170,30,'sales_consumption',1.000,991.000,990.000,'App\\Models\\OrderItem',49,'Sale: ORD-20261008-0675','Maria Santos','manager',NULL,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,NULL,1),(171,1,'sales_consumption',60.000,4010.000,3950.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(172,3,'sales_consumption',120.000,11970.000,11850.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(173,11,'sales_consumption',30.000,3850.000,3820.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(174,24,'sales_consumption',150.000,26870.000,26720.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(175,26,'sales_consumption',1.000,986.000,985.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(176,28,'sales_consumption',1.000,1981.000,1980.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(177,29,'sales_consumption',1.000,1981.000,1980.000,'App\\Models\\OrderItem',50,'Sale: ORD-20261008-2885','POS Cashier','cashier',NULL,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,NULL,1),(178,1,'adjustment_add',3950.000,0.000,3950.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(179,2,'adjustment_add',4100.000,0.000,4100.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(180,3,'adjustment_add',11850.000,0.000,11850.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(181,4,'adjustment_add',7640.000,0.000,7640.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(182,5,'adjustment_add',3920.000,0.000,3920.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(183,6,'adjustment_add',4960.000,0.000,4960.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(184,7,'adjustment_add',2980.000,0.000,2980.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(185,8,'adjustment_add',2970.000,0.000,2970.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(186,9,'adjustment_add',2945.000,0.000,2945.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(187,10,'adjustment_add',2000.000,0.000,2000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(188,11,'adjustment_add',3820.000,0.000,3820.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(189,12,'adjustment_add',2945.000,0.000,2945.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(190,13,'adjustment_add',1992.000,0.000,1992.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(191,14,'adjustment_add',2470.000,0.000,2470.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(192,15,'adjustment_add',2000.000,0.000,2000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(193,16,'adjustment_add',1470.000,0.000,1470.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(194,17,'adjustment_add',2960.000,0.000,2960.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(195,18,'adjustment_add',2880.000,0.000,2880.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(196,19,'adjustment_add',2460.000,0.000,2460.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(197,20,'adjustment_add',2380.000,0.000,2380.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(198,21,'adjustment_add',2500.000,0.000,2500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(199,22,'adjustment_add',1880.000,0.000,1880.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(200,23,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(201,24,'adjustment_add',26720.000,0.000,26720.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(202,25,'adjustment_add',990.000,0.000,990.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(203,26,'adjustment_add',985.000,0.000,985.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(204,27,'adjustment_add',995.000,0.000,995.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(205,28,'adjustment_add',1980.000,0.000,1980.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(206,29,'adjustment_add',1980.000,0.000,1980.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(207,30,'adjustment_add',990.000,0.000,990.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(208,31,'adjustment_add',1500.000,0.000,1500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(209,32,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(210,33,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(211,34,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(212,35,'adjustment_add',5000.000,0.000,5000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(213,36,'adjustment_add',400.000,0.000,400.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(214,37,'adjustment_add',15000.000,0.000,15000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(215,38,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(216,39,'adjustment_add',20000.000,0.000,20000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(217,40,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(218,41,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(219,42,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(220,43,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(221,44,'adjustment_add',4000.000,0.000,4000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(222,45,'adjustment_add',4000.000,0.000,4000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(223,46,'adjustment_add',4000.000,0.000,4000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(224,47,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(225,48,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(226,49,'adjustment_add',4000.000,0.000,4000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(227,50,'adjustment_add',2000.000,0.000,2000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(228,51,'adjustment_add',2000.000,0.000,2000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(229,52,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(230,53,'adjustment_add',200.000,0.000,200.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(231,54,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(232,55,'adjustment_add',200.000,0.000,200.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(233,56,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(234,57,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(235,58,'adjustment_add',3000.000,0.000,3000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(236,59,'adjustment_add',2000.000,0.000,2000.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(237,60,'adjustment_add',100.000,0.000,100.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(238,61,'adjustment_add',100.000,0.000,100.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(239,62,'adjustment_add',100.000,0.000,100.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(240,63,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(241,64,'adjustment_add',200.000,0.000,200.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(242,65,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(243,66,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(244,67,'adjustment_add',300.000,0.000,300.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(245,68,'adjustment_add',200.000,0.000,200.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(246,69,'adjustment_add',20.017,0.000,20.017,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(247,70,'adjustment_add',150.000,0.000,150.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(248,71,'adjustment_add',150.000,0.000,150.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3),(249,72,'adjustment_add',500.000,0.000,500.000,NULL,NULL,'Opening balance copied from Bangkal Branch during branch setup','Admin Owner','owner',NULL,NULL,'2026-10-09 08:35:26','2026-10-09 08:35:26',NULL,NULL,NULL,3);
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2024_01_01_000001_create_users_table',1),(4,'2024_01_01_000002_create_categories_table',1),(5,'2024_01_01_000003_create_products_table',1),(6,'2024_01_01_000004_create_inventory_table',1),(7,'2024_01_01_000005_create_recipes_table',1),(8,'2024_01_01_000006_create_orders_table',1),(9,'2024_01_01_000007_create_inventory_transactions_table',1),(10,'2024_01_01_000008_create_refunds_table',2),(11,'2024_01_01_000009_create_notifications_table',2),(12,'2024_01_01_000010_create_product_addon_ingredients_table',2),(13,'2024_01_01_000011_add_reference_number_to_payments_table',2),(14,'2024_01_01_000012_modify_method_column_in_payments_table',2),(15,'2024_01_01_000013_create_tax_settings_table',3),(16,'2024_01_01_000014_add_tax_and_discount_columns_to_orders_table',3),(17,'2024_01_01_000015_add_comment_and_status_to_order_items_table',4),(18,'2024_01_01_000016_add_held_and_pin_to_orders_table',4),(19,'2024_01_01_000017_create_void_logs_table',4),(20,'2024_01_01_000018_add_supervisor_to_users_role_table',5),(21,'2026_09_28_165528_create_debts_table',5),(22,'2026_09_28_165543_create_debt_payments_table',5),(23,'2026_10_01_000001_add_grab_orders_and_cashier_shifts_table',5),(24,'2026_10_02_000001_enable_split_order_payments',6),(25,'2026_10_02_000002_add_cash_refunds_to_shifts',6),(26,'2026_10_02_000003_add_ingredient_and_stock_in_details',6),(27,'2026_10_02_000004_add_void_requester_details',6),(29,'2026_10_06_000001_remove_supervisor_role',7),(30,'2026_10_02_000005_add_sales_return_transaction_type',8),(31,'2026_10_02_000006_add_refund_processing_details',9),(32,'2026_10_02_000007_complete_cashier_shift_workflow',10),(33,'2026_10_06_000002_drop_debts_and_pay_later_tables',11),(34,'2026_10_05_155409_add_archived_at_to_tax_settings_table',12),(35,'2026_10_08_000001_add_checkout_request_id_to_orders',13),(36,'2026_10_08_000002_create_notification_reads_table',14),(37,'2026_10_08_000002_add_substitution_source_to_addon_ingredients',15),(38,'2026_10_09_000001_allow_unapproved_void_logs',16),(39,'2026_10_09_000002_add_payment_request_id_to_payments_table',17),(40,'2026_10_09_000003_add_branch_scoping',18),(41,'2026_10_05_000001_guard_retired_debt_data',19);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_reads_notification_id_user_id_unique` (`notification_id`,`user_id`),
  KEY `notification_reads_user_id_foreign` (`user_id`),
  CONSTRAINT `notification_reads_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_reads`
--

LOCK TABLES `notification_reads` WRITE;
/*!40000 ALTER TABLE `notification_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('low_stock','out_of_stock','refund','adjustment','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_role` enum('owner','manager','supervisor','all') NOT NULL DEFAULT 'manager',
  `ingredient_id` bigint(20) unsigned DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_ingredient_id_foreign` (`ingredient_id`),
  KEY `notifications_branch_id_foreign` (`branch_id`),
  CONSTRAINT `notifications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'low_stock','🔔 Low Stock: Sola Raspberry Can 330ml','Sola Raspberry Can 330ml is running low (20.017 pc remaining, reorder threshold: 30 pc).','manager',69,0,NULL,'2026-10-05 20:51:56','2026-10-02 05:54:52','2026-10-05 20:51:56',1);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_adjustments`
--

DROP TABLE IF EXISTS `order_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `action` enum('cancel','edit','other') NOT NULL,
  `reason` text NOT NULL,
  `authorized_by` varchar(255) NOT NULL,
  `authorized_role` varchar(255) NOT NULL,
  `authorized_user_id` bigint(20) unsigned DEFAULT NULL,
  `before_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_state`)),
  `after_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_state`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_adjustments_order_id_foreign` (`order_id`),
  KEY `order_adjustments_authorized_user_id_foreign` (`authorized_user_id`),
  CONSTRAINT `order_adjustments_authorized_user_id_foreign` FOREIGN KEY (`authorized_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_adjustments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_adjustments`
--

LOCK TABLES `order_adjustments` WRITE;
/*!40000 ALTER TABLE `order_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_item_addons`
--

DROP TABLE IF EXISTS `order_item_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_item_addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `product_addon_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_addons_order_item_id_foreign` (`order_item_id`),
  KEY `order_item_addons_product_addon_id_foreign` (`product_addon_id`),
  CONSTRAINT `order_item_addons_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_addons_product_addon_id_foreign` FOREIGN KEY (`product_addon_id`) REFERENCES `product_addons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_item_addons`
--

LOCK TABLES `order_item_addons` WRITE;
/*!40000 ALTER TABLE `order_item_addons` DISABLE KEYS */;
INSERT INTO `order_item_addons` VALUES (2,2,1,25.00,'2026-09-26 20:10:24','2026-09-26 20:10:24'),(3,2,2,30.00,'2026-09-26 20:10:24','2026-09-26 20:10:24');
/*!40000 ALTER TABLE `order_item_addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_size_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assigned_to` varchar(150) DEFAULT NULL,
  `payable_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_product_size_id_foreign` (`product_size_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_items_product_size_id_foreign` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (2,2,5,9,1,195.00,195.00,NULL,'active','2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,0.00),(3,3,1,1,1,130.00,130.00,NULL,'active','2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,0.00),(4,4,10,17,1,150.00,150.00,NULL,'active','2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,0.00),(5,5,2,3,2,140.00,280.00,NULL,'active','2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,0.00),(6,6,16,23,1,150.00,150.00,NULL,'active','2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,0.00),(7,6,11,18,1,150.00,150.00,NULL,'active','2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,0.00),(8,6,6,11,1,140.00,140.00,NULL,'active','2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,0.00),(9,7,11,18,1,150.00,150.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(10,7,16,23,1,150.00,150.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(11,7,31,39,1,180.00,180.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(12,7,26,34,1,160.00,160.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(13,7,25,33,1,160.00,160.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(14,7,24,32,1,150.00,150.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(15,7,19,26,1,130.00,130.00,NULL,'active','2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,0.00),(16,8,6,11,1,140.00,140.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(17,8,11,18,1,150.00,150.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(18,8,16,23,1,150.00,150.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(19,8,15,22,2,140.00,280.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(20,8,14,21,1,135.00,135.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(21,8,13,20,1,160.00,160.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(22,8,12,19,1,160.00,160.00,NULL,'active','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,0.00),(23,9,11,18,1,150.00,150.00,NULL,'active','2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,150.00),(24,9,16,23,1,150.00,150.00,NULL,'active','2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,150.00),(47,19,7,13,1,150.00,150.00,NULL,'active','2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,150.00),(48,20,7,13,1,150.00,150.00,NULL,'active','2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,150.00),(49,21,4,7,1,140.00,140.00,NULL,'active','2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,140.00),(50,22,4,8,1,150.00,150.00,NULL,'active','2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,150.00),(52,25,4,8,1,150.00,150.00,NULL,'active','2026-10-09 01:49:59','2026-10-09 01:49:59',NULL,0.00),(53,25,3,6,1,150.00,150.00,NULL,'active','2026-10-09 01:49:59','2026-10-09 01:49:59',NULL,0.00),(55,27,4,7,1,140.00,140.00,NULL,'active','2026-10-09 02:50:10','2026-10-09 02:50:10',NULL,0.00),(56,27,3,5,1,140.00,140.00,NULL,'active','2026-10-09 02:50:10','2026-10-09 02:50:10',NULL,0.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) NOT NULL,
  `order_type` varchar(255) NOT NULL DEFAULT 'dine_in',
  `grab_order_code` varchar(255) DEFAULT NULL,
  `rider_code` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `cashier_name` varchar(255) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(255) DEFAULT NULL,
  `discount_label` varchar(255) DEFAULT NULL,
  `discount_id_number` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_name` varchar(255) NOT NULL DEFAULT 'VAT',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 12.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vatable_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_exempt_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `zero_rated_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `held_at` timestamp NULL DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `checkout_request_id` char(36) DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  UNIQUE KEY `orders_checkout_request_id_unique` (`checkout_request_id`),
  KEY `orders_shift_id_foreign` (`shift_id`),
  KEY `orders_branch_id_foreign` (`branch_id`),
  CONSTRAINT `orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (2,'ORD-20260927-WSEG','dine_in',NULL,NULL,NULL,NULL,'Carlos Reyes',195.00,0.00,NULL,NULL,NULL,195.00,'VAT',12.00,0.00,0.00,0.00,0.00,'completed',NULL,0,NULL,'2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,1),(3,'ORD-20260927-SWNW','dine_in',NULL,NULL,NULL,NULL,'Admin Owner',130.00,30.00,NULL,NULL,NULL,100.00,'VAT',12.00,0.00,0.00,0.00,0.00,'completed',NULL,0,NULL,'2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,1),(4,'ORD-20260927-P8U5','dine_in',NULL,NULL,NULL,NULL,'Admin Owner',150.00,0.00,NULL,NULL,NULL,150.00,'VAT',12.00,0.00,0.00,0.00,0.00,'completed',NULL,0,NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,1),(5,'ORD-20260927-VW2X','dine_in',NULL,NULL,NULL,NULL,'Admin Owner',280.00,0.00,NULL,NULL,NULL,280.00,'VAT',12.00,0.00,0.00,0.00,0.00,'completed',NULL,0,NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,1),(6,'ORD-20260927-APYK','dine_in',NULL,NULL,NULL,NULL,'Maria Santos',440.00,0.00,'none',NULL,NULL,440.00,'VAT',12.00,47.14,392.86,0.00,0.00,'completed',NULL,0,NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,1),(7,'ORD-20260928-O2VQ','dine_in',NULL,NULL,NULL,NULL,'POS Cashier',1080.00,0.00,'none',NULL,NULL,1080.00,'VAT',12.00,115.71,964.29,0.00,0.00,'completed',NULL,0,NULL,'2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,1),(8,'GB-20261001-IFWS','grab','FGHJKHGHGFDSAS','FGHFGMVFG','gwapo',NULL,'POS Cashier',1175.00,0.00,'none',NULL,NULL,1175.00,'VAT',12.00,125.89,1049.11,0.00,0.00,'completed',NULL,0,NULL,'2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,1),(9,'ORD-20261001-BDQV','dine_in',NULL,NULL,NULL,NULL,'Admin Owner',300.00,0.00,'none',NULL,NULL,300.00,'VAT',12.00,32.14,267.86,0.00,0.00,'completed',NULL,0,NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,1),(19,'ORD-20261008-1385','dine_in',NULL,NULL,NULL,1,'POS Cashier',150.00,0.00,'none',NULL,NULL,150.00,'VAT',12.00,16.07,133.93,0.00,0.00,'completed',NULL,0,NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,1),(20,'ORD-20261008-6301','dine_in',NULL,NULL,NULL,1,'POS Cashier',150.00,0.00,'none',NULL,NULL,150.00,'VAT',12.00,16.07,133.93,0.00,0.00,'completed',NULL,0,NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,1),(21,'ORD-20261008-0675','dine_in',NULL,NULL,NULL,4,'Maria Santos',140.00,0.00,'none',NULL,NULL,140.00,'VAT',12.00,15.00,125.00,0.00,0.00,'completed',NULL,0,NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,1),(22,'ORD-20261008-2885','dine_in',NULL,NULL,NULL,1,'POS Cashier',150.00,0.00,'none',NULL,NULL,150.00,'VAT',12.00,16.07,133.93,0.00,0.00,'completed',NULL,0,NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10','443e87ea-cac9-4773-b815-0439097d4e75',1),(24,'GB-TEST-1','grab','GF-CASH-001','RDR-CASH-001','Grab Customer',5,'Charles Reyes',170.00,0.00,NULL,NULL,NULL,170.00,'VAT',12.00,0.00,0.00,0.00,0.00,'completed',NULL,0,NULL,'2026-10-08 08:08:01','2026-10-08 08:08:01',NULL,1),(25,'ORD-20261009-4371','dine_in',NULL,NULL,'kai',4,'Maria Santos',300.00,0.00,'none',NULL,NULL,300.00,'VAT',12.00,32.14,267.86,0.00,0.00,'held','2026-10-09 01:49:59',0,NULL,'2026-10-09 01:49:59','2026-10-09 01:49:59',NULL,1),(27,'ORD-20261009-7615','dine_in',NULL,NULL,NULL,3,'Admin Owner',280.00,0.00,'none',NULL,NULL,280.00,'VAT',12.00,30.00,250.00,0.00,0.00,'held','2026-10-09 02:50:10',1,NULL,'2026-10-09 02:50:10','2026-10-09 03:35:19',NULL,1);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `method` varchar(50) NOT NULL DEFAULT 'cash',
  `amount_received` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `reference_number` varchar(255) DEFAULT NULL COMMENT 'Required for GCash / Maya / Card / bank transfers',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `request_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_request_id_unique` (`request_id`),
  KEY `payments_order_id_index` (`order_id`),
  KEY `payments_shift_id_method_status_index` (`shift_id`,`method`,`status`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (2,2,'online',195.00,195.00,0.00,'paid','098765456789','2026-09-26 20:10:24','2026-09-26 20:10:24',NULL,NULL,NULL,NULL),(3,3,'cash',1000.00,100.00,900.00,'paid',NULL,'2026-09-27 05:38:51','2026-09-27 05:38:51',NULL,NULL,NULL,NULL),(4,4,'cash',500.00,150.00,350.00,'paid',NULL,'2026-09-27 05:40:33','2026-09-27 05:40:33',NULL,NULL,NULL,NULL),(5,5,'cash',500.00,280.00,220.00,'paid',NULL,'2026-09-27 05:41:31','2026-09-27 05:41:31',NULL,NULL,NULL,NULL),(6,6,'cash',1000.00,440.00,560.00,'paid',NULL,'2026-09-27 15:21:14','2026-09-27 15:21:14',NULL,NULL,NULL,NULL),(7,7,'cash',6000.00,1080.00,4920.00,'paid',NULL,'2026-09-28 09:32:22','2026-09-28 09:32:22',NULL,NULL,NULL,NULL),(8,8,'grabfood',1175.00,1175.00,0.00,'paid','FGHJKHGHGFDSAS','2026-10-01 08:51:07','2026-10-01 08:51:07',NULL,NULL,NULL,NULL),(9,9,'cash',1000.00,300.00,700.00,'paid',NULL,'2026-10-01 10:29:07','2026-10-01 10:29:07',NULL,NULL,NULL,NULL),(10,19,'cash',150.00,150.00,0.00,'paid',NULL,'2026-10-08 04:09:18','2026-10-08 04:09:18',NULL,NULL,1,NULL),(11,20,'cash',150.00,150.00,0.00,'paid',NULL,'2026-10-08 04:09:42','2026-10-08 04:09:42',NULL,NULL,1,NULL),(12,21,'cash',140.00,140.00,0.00,'paid',NULL,'2026-10-08 04:15:00','2026-10-08 04:15:00',NULL,NULL,4,NULL),(13,22,'cash',1000.00,150.00,850.00,'paid',NULL,'2026-10-08 05:25:10','2026-10-08 05:25:10',NULL,NULL,1,NULL),(14,24,'cash',200.00,170.00,30.00,'paid',NULL,'2026-10-08 08:08:01','2026-10-08 08:08:01',NULL,NULL,5,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_addon_ingredients`
--

DROP TABLE IF EXISTS `product_addon_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_addon_ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_addon_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `replaces_ingredient_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL COMMENT 'Amount consumed per 1 addon unit',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_addon_ingredients_product_addon_id_ingredient_id_unique` (`product_addon_id`,`ingredient_id`),
  UNIQUE KEY `addon_ingredients_replacement_unique` (`product_addon_id`,`replaces_ingredient_id`),
  KEY `product_addon_ingredients_ingredient_id_foreign` (`ingredient_id`),
  KEY `product_addon_ingredients_replaces_ingredient_id_foreign` (`replaces_ingredient_id`),
  CONSTRAINT `product_addon_ingredients_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `product_addon_ingredients_product_addon_id_foreign` FOREIGN KEY (`product_addon_id`) REFERENCES `product_addons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_addon_ingredients_replaces_ingredient_id_foreign` FOREIGN KEY (`replaces_ingredient_id`) REFERENCES `ingredients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_addon_ingredients`
--

LOCK TABLES `product_addon_ingredients` WRITE;
/*!40000 ALTER TABLE `product_addon_ingredients` DISABLE KEYS */;
INSERT INTO `product_addon_ingredients` VALUES (1,1,1,NULL,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(2,2,4,3,180.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(3,3,6,NULL,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(4,5,5,NULL,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(5,6,39,NULL,150.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(6,7,50,NULL,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(7,8,51,NULL,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05');
/*!40000 ALTER TABLE `product_addon_ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_addons`
--

DROP TABLE IF EXISTS `product_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_addons`
--

LOCK TABLES `product_addons` WRITE;
/*!40000 ALTER TABLE `product_addons` DISABLE KEYS */;
INSERT INTO `product_addons` VALUES (1,'Extra Shot',25.00,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(2,'Oat Milk Substitute',30.00,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(3,'Extra Syrup',15.00,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(4,'Less Ice',0.00,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(5,'Extra Cream',20.00,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(6,'Extra Rice',20.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(7,'Cheesy Buffalo (Creamy Cheese Drip)',50.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(8,'Double Cheese (Creamy Cheese Drip)',50.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(9,'Flavor: Classic (Plain)',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(10,'Flavor: Buffalo Wings',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(11,'Flavor: Sour Cream',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(12,'Flavor: Salted Egg',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(13,'Flavor: Cheese',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(14,'Flavor: Honey Glazed',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(15,'Flavor: Garlic Parmesan',0.00,'active','2026-09-26 17:55:05','2026-09-26 17:55:05');
/*!40000 ALTER TABLE `product_addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_sizes`
--

DROP TABLE IF EXISTS `product_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_sizes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `size_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `grab_price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_sizes_product_id_foreign` (`product_id`),
  CONSTRAINT `product_sizes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_sizes`
--

LOCK TABLES `product_sizes` WRITE;
/*!40000 ALTER TABLE `product_sizes` DISABLE KEYS */;
INSERT INTO `product_sizes` VALUES (1,1,'Hot 8oz',130.00,140.00,'active','2026-09-26 17:55:03','2026-10-09 02:35:51'),(2,1,'Iced 12oz',135.00,150.00,'active','2026-09-26 17:55:03','2026-10-09 02:35:51'),(3,2,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(4,2,'Iced 12oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(5,3,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(6,3,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(7,4,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(8,4,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(9,5,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(10,5,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(11,6,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(12,6,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(13,7,'Hot 8oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(14,7,'Iced 12oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(15,8,'Iced 12oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(16,9,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(17,10,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(18,11,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(19,12,'Iced 12oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(20,13,'Iced 12oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(21,14,'Iced 12oz',135.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(22,15,'Iced 12oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(23,16,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(24,17,'Iced 12oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(25,18,'Iced 12oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(26,19,'Iced 16oz',130.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(27,20,'Iced 16oz',130.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(28,21,'Iced 16oz',130.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(29,22,'Iced 16oz',130.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(30,23,'Hot 8oz',140.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(31,23,'Iced 16oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(32,24,'Iced 16oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(33,25,'Iced 16oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(34,26,'Iced 16oz',160.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(35,27,'Iced 16oz',180.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(36,28,'Iced 16oz',190.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(37,29,'Hot 8oz',150.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(38,30,'Iced 16oz',170.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(39,31,'Iced 16oz',180.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(40,32,'Iced 16oz',190.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(41,33,'Regular',189.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(42,34,'Regular',259.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(43,35,'Regular',359.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(44,36,'Regular',529.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(45,37,'Regular',699.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(46,38,'Regular',1199.00,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(47,39,'Regular',259.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(48,40,'Regular',259.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(49,41,'Regular',209.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(50,42,'Regular',289.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(51,43,'Regular',389.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(52,44,'Regular',589.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(53,45,'Regular',779.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(54,46,'Regular',1349.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(55,47,'Regular',279.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(56,48,'Regular',279.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(57,49,'Regular',189.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(58,50,'Regular',189.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(59,51,'Regular',189.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(60,52,'Regular',299.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(61,53,'Regular',95.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(62,54,'Regular',95.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(63,55,'Regular',115.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(64,56,'Regular',115.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(65,57,'Regular',95.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(66,58,'Regular',205.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(67,59,'Regular',250.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(68,60,'Regular',299.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(69,61,'Regular',115.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(70,62,'Regular',125.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(71,63,'Regular',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(72,64,'Regular',65.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(73,65,'Regular',65.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(74,66,'Regular',40.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(75,67,'Regular',65.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(76,68,'Regular',120.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(77,69,'Regular',170.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(78,70,'Iced 16oz',90.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(79,71,'Iced 16oz',90.00,120.00,'active','2026-09-26 17:55:05','2026-10-08 05:53:52'),(80,72,'Bottle 1.75L',120.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(81,73,'Bottle 1.5L',100.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(82,74,'Bottle 1.5L',100.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(83,75,'Can',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(84,76,'Can',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(85,77,'Can',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(86,78,'Can',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(87,79,'Can',55.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(88,80,'Can',80.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(89,81,'Can',80.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(90,82,'Bottle',120.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(91,83,'Bottle',120.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(92,84,'Bottle 500ml',25.00,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(93,85,'16oz',150.00,170.00,'active','2026-10-08 08:08:01','2026-10-08 08:08:01');
/*!40000 ALTER TABLE `product_sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Americano',NULL,NULL,'inactive','2026-09-26 17:55:03','2026-09-27 05:44:22'),(2,1,'Latte',NULL,NULL,'active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(3,1,'Mocha',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(4,1,'Spanish Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(5,1,'Caramel Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(6,1,'Hazelnut Mocha',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(7,1,'Cinnamon Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(8,1,'Cinnamon Oat Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(9,1,'Honey Oat Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(10,1,'Orange Espresso',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(11,1,'Salted Cream',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(12,1,'Cookie Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(13,1,'Solace',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(14,2,'Cold Brew',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(15,2,'Cold Brew Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(16,2,'Spanish Cold Brew',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(17,2,'Orange Cold Brew',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(18,2,'Lychee Apple Cold Brew',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(19,5,'Lychee Apple',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(20,5,'Strawberry',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(21,5,'Passion Fruit',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(22,5,'Orange',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(23,4,'Choco',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(24,4,'Milo Dino',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(25,4,'Chocolate Cookie',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(26,4,'Strawberry Cream',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(27,4,'Strawberry Cream Cookie',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(28,4,'Hojicha',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(29,3,'Hot Matcha Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(30,3,'Iced Matcha',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(31,3,'Matcha Oat Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(32,3,'Seasalt Matcha Oat Latte',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(33,6,'Lone',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(34,6,'Wingman',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(35,6,'Wingstreak',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(36,6,'Trio',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(37,6,'Squad',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(38,6,'Winner Chicken Dinner',NULL,NULL,'active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(39,6,'Chicken Wings & Hashbrown',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(40,6,'Chicken Wings & Fries',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(41,7,'Spicy Korean Lone',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(42,7,'Spicy Korean Wingman',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(43,7,'Spicy Korean Wingstreak',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(44,7,'Spicy Korean Trio',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(45,7,'Spicy Korean Squad',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(46,7,'Spicy Korean Winner Chicken Dinner',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(47,7,'Spicy Korean Chicken Wings & Hashbrown',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(48,7,'Spicy Korean Chicken Wings & Fries',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(49,8,'Buldak Spicy Cheese (yellow)',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(50,8,'Buldak Extra Spicy (black)',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(51,8,'Buldak Spicy Carbonara (pink)',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(52,8,'Buldak with 4pcs Wings (yellow/black/pink)',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(53,9,'Chicken Burger',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(54,9,'Chicken Burger Spicy',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(55,9,'Chicken Burger Spicy with Cheese',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(56,9,'Chicken Burger with Cheese',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(57,9,'Chicken Burger Plain Mayo',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(58,9,'Chicken Burger Double Patty',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(59,9,'Chicken Burger Double Patty & Cheese',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(60,9,'Chicken Burger Double Patty, Cheese & Hashbrown',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(61,10,'Triple Cheese Quesadilla',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(62,10,'Chicken Quesadilla',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(63,11,'OG Fudgy Brownie',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(64,11,'Matcha Brownie',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(65,11,'Strawberry Brownie',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(66,11,'Mini Dimsum Egg Tart',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(67,11,'Matcha Cheese Tart',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(68,11,'OG Brownie Ala Mode',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(69,11,'Matcha or Strawberry Brownie Ala Mode',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(70,12,'Pink Guava',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(71,12,'Blueberry Lemonade (with popping bobba)',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(72,12,'Coke 1.75',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(73,12,'Coke 1.5',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(74,12,'Mountain Dew 1.5',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(75,12,'Coke in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(76,12,'Coke Zero in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(77,12,'Sprite in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(78,12,'Royal in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(79,12,'Mountain Dew in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(80,12,'Sola Lemon in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(81,12,'Sola Raspberry in can',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(82,12,'Sola Lemon in bottle',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(83,12,'Sola Raspberry in bottle',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(84,12,'Mineral Water',NULL,NULL,'active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(85,13,'Iced Latte',NULL,NULL,'active','2026-10-08 08:08:01','2026-10-08 08:08:01');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `purchase_order_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `status` enum('draft','received','cancelled') NOT NULL DEFAULT 'draft',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipe_ingredients_recipe_id_ingredient_id_unique` (`recipe_id`,`ingredient_id`),
  KEY `recipe_ingredients_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `recipe_ingredients_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `recipe_ingredients_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_ingredients`
--

LOCK TABLES `recipe_ingredients` WRITE;
/*!40000 ALTER TABLE `recipe_ingredients` DISABLE KEYS */;
INSERT INTO `recipe_ingredients` VALUES (1,1,1,60.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(2,1,25,1.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(3,1,30,1.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(4,2,1,60.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(5,2,24,150.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(6,2,26,1.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(7,2,28,1.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(8,2,29,1.000,'2026-09-26 17:55:03','2026-09-26 17:55:03'),(9,3,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(10,3,3,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(11,3,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(12,3,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(13,4,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(14,4,3,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(15,4,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(16,4,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(17,4,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(18,4,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(19,5,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(20,5,3,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(21,5,12,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(22,5,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(23,5,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(24,6,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(25,6,3,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(26,6,12,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(27,6,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(28,6,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(29,6,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(30,6,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(31,7,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(32,7,3,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(33,7,11,30.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(34,7,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(35,7,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(36,8,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(37,8,3,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(38,8,11,30.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(39,8,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(40,8,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(41,8,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(42,8,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(43,9,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(44,9,3,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(45,9,7,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(46,9,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(47,9,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(48,10,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(49,10,3,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(50,10,7,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(51,10,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(52,10,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(53,10,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(54,10,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(55,11,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(56,11,3,140.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(57,11,12,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(58,11,8,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(59,11,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(60,11,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(61,12,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(62,12,3,140.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(63,12,12,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(64,12,8,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(65,12,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(66,12,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(67,12,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(68,12,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(69,13,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(70,13,3,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(71,13,9,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(72,13,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(73,13,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(74,14,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(75,14,3,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(76,14,9,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(77,14,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(78,14,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(79,14,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(80,14,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(81,15,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(82,15,4,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(83,15,9,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(84,15,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(85,15,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(86,15,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(87,15,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(88,16,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(89,16,4,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(90,16,10,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(91,16,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(92,16,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(93,16,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(94,16,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(95,17,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(96,17,18,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(97,17,6,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(98,17,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(99,17,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(100,17,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(101,17,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(102,18,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(103,18,3,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(104,18,22,30.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(105,18,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(106,18,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(107,18,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(108,18,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(109,19,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(110,19,3,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(111,19,16,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(112,19,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(113,19,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(114,19,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(115,19,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(116,20,1,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(117,20,3,140.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(118,20,12,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(119,20,9,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(120,20,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(121,20,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(122,20,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(123,20,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(124,21,2,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(125,21,6,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(126,21,24,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(127,21,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(128,21,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(129,21,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(130,22,2,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(131,22,3,80.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(132,22,6,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(133,22,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(134,22,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(135,22,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(136,22,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(137,23,2,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(138,23,3,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(139,23,11,30.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(140,23,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(141,23,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(142,23,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(143,23,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(144,24,2,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(145,24,18,80.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(146,24,6,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(147,24,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(148,24,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(149,24,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(150,24,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(151,25,2,120.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(152,25,19,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(153,25,20,60.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(154,25,24,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(155,25,26,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(156,25,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(157,25,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(158,26,19,40.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(159,26,20,140.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(160,26,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(161,26,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(162,26,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(163,26,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(164,27,17,50.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(165,27,6,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(166,27,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(167,27,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(168,27,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(169,27,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(170,28,21,50.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(171,28,6,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(172,28,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(173,28,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(174,28,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(175,28,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(176,29,18,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(177,29,6,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(178,29,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(179,29,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(180,29,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(181,29,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(182,30,12,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(183,30,3,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(184,30,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(185,30,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(186,31,12,25.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(187,31,3,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(188,31,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(189,31,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(190,31,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(191,31,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(192,32,14,30.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(193,32,3,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(194,32,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(195,32,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(196,32,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(197,32,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(198,33,12,20.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(199,33,16,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(200,33,3,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(201,33,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(202,33,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(203,33,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(204,33,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(205,34,17,40.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(206,34,5,80.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(207,34,3,100.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(208,34,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(209,34,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(210,34,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(211,34,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(212,35,17,40.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(213,35,5,80.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(214,35,16,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(215,35,3,100.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(216,35,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(217,35,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(218,35,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(219,35,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(220,36,15,10.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(221,36,3,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(222,36,6,15.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(223,36,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(224,36,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(225,36,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(226,36,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(227,37,13,8.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(228,37,3,160.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(229,37,25,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(230,37,30,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(231,38,13,8.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(232,38,3,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(233,38,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(234,38,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(235,38,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(236,38,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(237,39,13,8.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(238,39,4,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(239,39,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(240,39,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(241,39,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(242,39,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(243,40,13,8.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(244,40,4,180.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(245,40,23,2.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(246,40,22,25.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(247,40,24,200.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(248,40,27,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(249,40,28,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(250,40,29,1.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(251,41,31,4.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(252,41,39,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(253,42,31,6.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(254,43,31,6.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(255,43,37,150.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(256,44,31,12.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(257,45,31,16.000,'2026-09-26 17:55:04','2026-09-26 17:55:04'),(258,46,31,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(259,47,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(260,47,36,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(261,48,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(262,48,37,150.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(263,49,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(264,49,39,150.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(265,49,40,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(266,50,31,6.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(267,50,40,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(268,51,31,6.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(269,51,37,150.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(270,51,40,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(271,52,31,12.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(272,52,40,35.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(273,53,31,16.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(274,53,40,45.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(275,54,31,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(276,54,40,80.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(277,55,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(278,55,36,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(279,55,40,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(280,56,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(281,56,37,150.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(282,56,40,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(283,57,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(284,57,41,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(285,58,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(286,58,42,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(287,59,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(288,59,43,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(289,60,31,4.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(290,60,41,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(291,61,32,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(292,61,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(293,61,35,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(294,62,32,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(295,62,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(296,62,35,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(297,62,40,5.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(298,63,32,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(299,63,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(300,63,34,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(301,63,35,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(302,63,40,5.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(303,64,32,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(304,64,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(305,64,34,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(306,64,35,20.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(307,65,32,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(308,65,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(309,65,35,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(310,66,32,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(311,66,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(312,66,35,25.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(313,67,32,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(314,67,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(315,67,34,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(316,67,35,25.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(317,68,32,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(318,68,33,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(319,68,34,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(320,68,36,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(321,68,35,25.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(322,69,38,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(323,69,34,3.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(324,69,35,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(325,70,38,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(326,70,31,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(327,70,34,2.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(328,70,35,15.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(329,71,52,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(330,72,52,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(331,73,52,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(332,74,54,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(333,75,55,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(334,76,52,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(335,76,53,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(336,77,52,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(337,77,53,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(338,78,56,80.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(339,79,57,30.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(340,79,58,25.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(341,79,59,25.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(342,80,60,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(343,81,61,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(344,82,62,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(345,83,63,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(346,84,64,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(347,85,65,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(348,86,66,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(349,87,67,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(350,88,68,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(351,89,69,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(352,90,70,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(353,91,71,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05'),(354,92,72,1.000,'2026-09-26 17:55:05','2026-09-26 17:55:05');
/*!40000 ALTER TABLE `recipe_ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_size_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipes_product_size_id_unique` (`product_size_id`),
  CONSTRAINT `recipes_product_size_id_foreign` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,1,'Americano Hot 8oz','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(2,2,'Americano Iced 12oz','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(3,3,'Latte Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(4,4,'Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(5,5,'Mocha Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(6,6,'Mocha Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(7,7,'Spanish Latte Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(8,8,'Spanish Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(9,9,'Caramel Latte Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(10,10,'Caramel Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(11,11,'Hazelnut Mocha Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(12,12,'Hazelnut Mocha Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(13,13,'Cinnamon Latte Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(14,14,'Cinnamon Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(15,15,'Cinnamon Oat Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(16,16,'Honey Oat Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(17,17,'Orange Espresso Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(18,18,'Salted Cream Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(19,19,'Cookie Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(20,20,'Solace Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(21,21,'Cold Brew Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(22,22,'Cold Brew Latte Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(23,23,'Spanish Cold Brew Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(24,24,'Orange Cold Brew Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(25,25,'Lychee Apple Cold Brew Iced 12oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(26,26,'Lychee Apple Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(27,27,'Strawberry Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(28,28,'Passion Fruit Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(29,29,'Orange Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(30,30,'Choco Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(31,31,'Choco Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(32,32,'Milo Dino Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(33,33,'Chocolate Cookie Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(34,34,'Strawberry Cream Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(35,35,'Strawberry Cream Cookie Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(36,36,'Hojicha Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(37,37,'Hot Matcha Latte Hot 8oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(38,38,'Iced Matcha Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(39,39,'Matcha Oat Latte Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(40,40,'Seasalt Matcha Oat Latte Iced 16oz','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(41,41,'Lone Regular','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(42,42,'Wingman Regular','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(43,43,'Wingstreak Regular','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(44,44,'Trio Regular','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(45,45,'Squad Regular','active','2026-09-26 17:55:04','2026-09-26 17:55:04'),(46,46,'Winner Chicken Dinner Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(47,47,'Chicken Wings & Hashbrown Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(48,48,'Chicken Wings & Fries Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(49,49,'Spicy Korean Lone Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(50,50,'Spicy Korean Wingman Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(51,51,'Spicy Korean Wingstreak Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(52,52,'Spicy Korean Trio Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(53,53,'Spicy Korean Squad Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(54,54,'Spicy Korean Winner Chicken Dinner Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(55,55,'Spicy Korean Chicken Wings & Hashbrown Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(56,56,'Spicy Korean Chicken Wings & Fries Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(57,57,'Buldak Spicy Cheese (yellow) Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(58,58,'Buldak Extra Spicy (black) Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(59,59,'Buldak Spicy Carbonara (pink) Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(60,60,'Buldak with 4pcs Wings (yellow/black/pink) Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(61,61,'Chicken Burger Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(62,62,'Chicken Burger Spicy Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(63,63,'Chicken Burger Spicy with Cheese Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(64,64,'Chicken Burger with Cheese Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(65,65,'Chicken Burger Plain Mayo Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(66,66,'Chicken Burger Double Patty Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(67,67,'Chicken Burger Double Patty & Cheese Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(68,68,'Chicken Burger Double Patty, Cheese & Hashbrown Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(69,69,'Triple Cheese Quesadilla Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(70,70,'Chicken Quesadilla Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(71,71,'OG Fudgy Brownie Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(72,72,'Matcha Brownie Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(73,73,'Strawberry Brownie Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(74,74,'Mini Dimsum Egg Tart Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(75,75,'Matcha Cheese Tart Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(76,76,'OG Brownie Ala Mode Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(77,77,'Matcha or Strawberry Brownie Ala Mode Regular','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(78,78,'Pink Guava Iced 16oz','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(79,79,'Blueberry Lemonade (with popping bobba) Iced 16oz','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(80,80,'Coke 1.75 Bottle 1.75L','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(81,81,'Coke 1.5 Bottle 1.5L','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(82,82,'Mountain Dew 1.5 Bottle 1.5L','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(83,83,'Coke in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(84,84,'Coke Zero in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(85,85,'Sprite in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(86,86,'Royal in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(87,87,'Mountain Dew in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(88,88,'Sola Lemon in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(89,89,'Sola Raspberry in can Can','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(90,90,'Sola Lemon in bottle Bottle','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(91,91,'Sola Raspberry in bottle Bottle','active','2026-09-26 17:55:05','2026-09-26 17:55:05'),(92,92,'Mineral Water Bottle 500ml','active','2026-09-26 17:55:05','2026-09-26 17:55:05');
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `authorized_by` varchar(255) NOT NULL,
  `authorized_role` varchar(255) NOT NULL,
  `authorized_user_id` bigint(20) unsigned DEFAULT NULL,
  `stock_restored` tinyint(1) NOT NULL DEFAULT 0,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `method` varchar(20) NOT NULL DEFAULT 'cash',
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refunds_order_id_foreign` (`order_id`),
  KEY `refunds_authorized_user_id_foreign` (`authorized_user_id`),
  KEY `refunds_payment_id_foreign` (`payment_id`),
  KEY `refunds_shift_id_method_status_index` (`shift_id`,`method`,`status`),
  CONSTRAINT `refunds_authorized_user_id_foreign` FOREIGN KEY (`authorized_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('AxfrfSuFI9OzZipbI6LKmlod4MKMT7tgFufdGzvg',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWXBuMkdsMVk5enBvaHJzV29yT3I3MUxrYzBkcElOaVFoOUdnOXVlaiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovLzEyNy4wLjAuMTo4MTc0L2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgxNzQvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1791624035),('e6iBw6c6v1pOfNXf0BCjAbjrsJ0TWUDlDhFZ0zm2',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUjhSbEl1dXNHZ2hsMVpTa2VKMjlQeUM5Mk1WM3EwT1BuMXFnNEdpTyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly8xMjcuMC4wLjE6ODE3NC9wb3Mvc2F2ZWQtb3JkZXJzIjtzOjU6InJvdXRlIjtzOjE1OiJwb3MuaGVsZC1vcmRlcnMiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1791629680),('EFGyQmkSmMqqGnlBh4s7DAvkGTkBMzj0qIHDVSSC',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVG5LbDUzaHo1ZFNyY3Qwd29CZGJHeG16bklONWdoWmxrT2hQU2hHRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODE3NC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791622868),('eVOgUQozvxylIBdhre1qIimf7mSANzCo1CYa70uK',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0VhVjJJWmFqNEtrUGhWRzRvMnJDRERwbmpabDR5Q2hTR0ZoYTNXRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjEvY29mZmVlLXNob3Atc3lzdGVtL3B1YmxpYy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791629679),('O8ZrGJYdzJm3vfvHEYPLUdnuaOesALjKNYnP4lr9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYkpOVm1LVFRSZUtSenhjV0RuVkpYcmp0eHY4TkVySmhBSEJyNDN5YiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODE3NC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791623511),('t0hzJg0jhArP2OOhfNlj4wLmGONYl22NZe44iobg',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNUJJWnJCbFFZWlVKMzhSUTdFRjhnWHQzVFdURGp1UjNyVVRhMTcwUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjEvY29mZmVlLXNob3Atc3lzdGVtL3B1YmxpYy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791629706),('t9brNVwvavK7TfQMalFXGshiWDSHraEZV9hD8uOT',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUlp4RW9yUXhqRmJwZVdpeVVGR1B5THAxRWtoNGMybVNRU2xabVZOTyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNToiaHR0cDovLzEyNy4wLjAuMTo4MTc0L3JlcG9ydHMvc2FsZXMiO31zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czoyNzoiaHR0cDovLzEyNy4wLjAuMTo4MTc0L2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1791628907),('TxfwCqNvZ5FuFzbsgYArbvDMvcMSjEICM4OR0K55',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMVpnSXpqSkRmbGdUUUxLdHhjVEhKYjBQcEdhNXI3b1E5TTROZUFWaiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODE3NC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1791633483);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_cash_movements`
--

DROP TABLE IF EXISTS `shift_cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shift_cash_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned NOT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'adjustment',
  `amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_cash_movements_recorded_by_foreign` (`recorded_by`),
  KEY `shift_cash_movements_shift_id_type_index` (`shift_id`,`type`),
  CONSTRAINT `shift_cash_movements_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  CONSTRAINT `shift_cash_movements_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_cash_movements`
--

LOCK TABLES `shift_cash_movements` WRITE;
/*!40000 ALTER TABLE `shift_cash_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_cash_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Metro Coffee Supplies','09171234567','Manila, Philippines','active','2026-09-26 17:55:03','2026-09-26 17:55:03'),(2,'The Fryers Food Logistics','09181234567','Quezon City, Philippines','active','2026-09-26 17:55:04','2026-09-26 17:55:04');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_settings`
--

DROP TABLE IF EXISTS `tax_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'VAT',
  `rate` decimal(5,2) NOT NULL DEFAULT 12.00,
  `is_inclusive` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_settings`
--

LOCK TABLES `tax_settings` WRITE;
/*!40000 ALTER TABLE `tax_settings` DISABLE KEYS */;
INSERT INTO `tax_settings` VALUES (1,'VAT',12.00,1,1,NULL,'2026-09-27 08:43:35','2026-09-27 08:43:35');
/*!40000 ALTER TABLE `tax_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','manager','cashier') NOT NULL DEFAULT 'cashier',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Owner','owner@coffee.com',NULL,'$2y$12$/AlCEL0WMp6KNdlbRU5iC.4Ib9XunR9tSwpfyfGoamFhiX6MDiuku','owner','active',NULL,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(2,'Maria Santos','manager@coffee.com',NULL,'$2y$12$zqPlYnEtgzDCyC99Hz2V3.0r3gl2doy1rrQJ1C9BBcZAYUut2qt2C','manager','active',NULL,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(3,'Carlos Reyes','supervisor@coffee.com',NULL,'$2y$12$YGCv0.9YEEGWvAPgGv4CouYG9f1MTYOspp6ygPEYuNxCURSuPKSyi','manager','active',NULL,'2026-09-26 17:55:03','2026-10-01 09:32:07',1),(4,'POS Cashier','cashier@coffee.com',NULL,'$2y$12$ug7lOz5RJE7DFInplLZJae.DVv.AXAEeZn.gnaCQ.bqn3gLcBaFDK','cashier','active',NULL,'2026-09-26 17:55:03','2026-09-26 17:55:03',1),(5,'Charles Reyes','pkuphal@example.com','2026-10-08 08:08:01','$2y$12$RajFkIVMxq/QuIzbOXehCOYnluIrw6uhcZetnZz06w0/J.pOnCJRG','cashier','active','Irc6f43xMg','2026-10-08 08:08:01','2026-10-08 08:08:01',1),(6,'Bangkal Sample Manager','bangkal.manager@example.test',NULL,'$2y$12$5iybOh2lfQJJIIn5sRaCR.4UaoRAagT4KhYS8Lhzed1g9FzfI7t2y','manager','active',NULL,'2026-10-09 08:31:44','2026-10-09 08:31:44',1),(7,'San Rafael Sample Manager','sanrafael.manager@example.test',NULL,'$2y$12$G22uYyXH.xXK7QSszKTBOeuPlPG0sp8I5AvZblmpcthcddQ8QWAzq','manager','active',NULL,'2026-10-09 08:31:44','2026-10-09 08:31:44',3);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `void_logs`
--

DROP TABLE IF EXISTS `void_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `void_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `void_type` varchar(20) NOT NULL DEFAULT 'order',
  `reason` text NOT NULL,
  `cashier_name` varchar(255) NOT NULL,
  `requested_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `requested_role` varchar(255) DEFAULT NULL,
  `authorized_user_id` bigint(20) unsigned DEFAULT NULL,
  `authorized_by` varchar(255) DEFAULT NULL,
  `authorized_role` varchar(255) DEFAULT NULL,
  `stock_restored` tinyint(1) NOT NULL DEFAULT 0,
  `voided_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `void_logs_order_id_foreign` (`order_id`),
  KEY `void_logs_order_item_id_foreign` (`order_item_id`),
  KEY `void_logs_authorized_user_id_foreign` (`authorized_user_id`),
  KEY `void_logs_requested_by_user_id_foreign` (`requested_by_user_id`),
  KEY `void_logs_shift_id_index` (`shift_id`),
  CONSTRAINT `void_logs_authorized_user_id_foreign` FOREIGN KEY (`authorized_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `void_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `void_logs_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `void_logs_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `void_logs_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `void_logs`
--

LOCK TABLES `void_logs` WRITE;
/*!40000 ALTER TABLE `void_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `void_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-10 19:58:27
