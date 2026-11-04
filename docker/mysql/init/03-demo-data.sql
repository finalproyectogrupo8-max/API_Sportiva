-- MySQL dump 10.13  Distrib 8.4.11, for Linux (x86_64)
--
-- Host: localhost    Database: sportiva_platform
-- ------------------------------------------------------
-- Server version	8.4.11

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sportiva_platform`
--

/*!40000 DROP DATABASE IF EXISTS `sportiva_platform`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sportiva_platform` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `sportiva_platform`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `changes` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'tenant.created','tenant',1,'{\"name\": {\"to\": \"Club Demo Sportiva\", \"from\": null}}','127.0.0.1','2026-10-31 04:55:59'),(2,1,'user.created','user',3,'{\"email\": {\"to\": \"admin.club@sportiva.com\", \"from\": null}}','127.0.0.1','2026-10-31 04:55:59'),(3,1,'membership.created','membership',1,'{\"roles\": {\"to\": [\"Administrador del club\"], \"from\": []}}','127.0.0.1','2026-10-31 04:55:59'),(4,1,'auth.password_reset_requested','auth',6,NULL,'127.0.0.1','2026-10-31 04:55:59');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('sportiva-cache-demo:club_slug','s:4:\"demo\";',1825304160),('sportiva-cache-demo:seeded_at','s:25:\"2026-11-04T04:56:00+00:00\";',1825304160);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
INSERT INTO `cache_locks` VALUES ('sportiva-cache-demo:seed-lock','mSCofOEuCB6FpwWl',1793854560);
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
INSERT INTO `failed_jobs` VALUES (1,'1b954ce1-686f-454a-aa78-19597df17c61','database','default','{\"uuid\":\"23531b78-9a7b-4f77-b582-146682759ed6\",\"displayName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":\"10,60\",\"timeout\":60,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\ProvisionTenant\\\":2:{s:8:\\\"tenantId\\\";i:1;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2036-11-04 04:56:00.150750\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1793768160,\"delay\":315619200}','Demo: el alta del club tardó más de lo esperado (dato de ejemplo, reintentable).\n','2026-11-04 04:56:00');
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
INSERT INTO `job_batches` VALUES ('cf038a26-c5dc-4df4-99da-6765a7527d61','demo:provision-club',1,0,0,'[]',NULL,NULL,1793768159,1793768159);
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"23531b78-9a7b-4f77-b582-146682759ed6\",\"displayName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":\"10,60\",\"timeout\":60,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\ProvisionTenant\\\":2:{s:8:\\\"tenantId\\\";i:1;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2036-11-04 04:56:00.150750\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1793768160,\"delay\":315619200}',0,NULL,2109387360,1793768160);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_17_182514_create_tenants_table',1),(5,'2026_10_17_182632_create_tenant_memberships_table',1),(6,'2026_10_17_182759_add_is_platform_admin_to_users_table',1),(7,'2026_10_17_191332_create_personal_access_tokens_table',1),(8,'2026_10_17_202644_add_tenant_id_to_personal_access_tokens_table',1),(9,'2026_10_19_234242_add_soft_deletes_to_tenants_table',1),(10,'2026_10_21_212136_add_is_active_to_users_table',1),(11,'2026_10_21_212137_create_audit_logs_table',1),(12,'2026_10_27_220000_create_support_tickets_table',1),(13,'2026_10_28_171408_create_contacts_table',1),(14,'2026_10_28_184010_create_newsletters_table',1),(15,'2026_10_28_215828_add_role_to_users_table',1),(16,'2026_10_29_170000_move_contacts_to_support_tickets',1),(17,'2026_10_30_000001_drop_role_columns_from_users_and_memberships',1),(18,'2026_10_31_000001_add_profile_columns_to_tenants_table',1),(19,'2026_10_31_000002_add_logo_path_to_tenants_table',1),(20,'2026_11_02_000001_add_invitation_columns_to_users_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletters_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletters`
--

LOCK TABLES `newsletters` WRITE;
/*!40000 ALTER TABLE `newsletters` DISABLE KEYS */;
INSERT INTO `newsletters` VALUES (1,'familia.perez@example.com','2026-11-04 04:56:00','2026-11-04 04:56:00'),(2,'socios.norte@example.com','2026-11-04 04:56:00','2026-11-04 04:56:00'),(3,'prensa.club@example.com','2026-11-04 04:56:00','2026-11-04 04:56:00'),(4,'entrenador.libre@example.com','2026-11-04 04:56:00','2026-11-04 04:56:00'),(5,'hincha.tigre@example.com','2026-11-04 04:56:00','2026-11-04 04:56:00');
/*!40000 ALTER TABLE `newsletters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('profe@sportiva.com','$2y$12$2pU2lz5JymGVIOn.2TQ9IOzBtKBiazQk9lafEgllTXdNdaoq98c.C','2026-11-04 04:56:00');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`),
  KEY `personal_access_tokens_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `personal_access_tokens_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',3,NULL,'demo-local','651d07a62e27a7481cede86293815201cd66d2cdfadff112c45e6fcb96b10878','[\"tenant:select\",\"tenant:operate\"]',NULL,'2026-12-04 04:55:59','2026-11-04 04:55:59','2026-11-04 04:55:59'),(2,'App\\Models\\User',6,NULL,'demo-local','6ae8ac215099e8fe4190b45fa347b707ecf7b579a28fbe50df84afbdb5cfde09','[\"tenant:select\",\"tenant:operate\"]',NULL,'2026-12-04 04:55:59','2026-11-04 04:55:59','2026-11-04 04:55:59');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
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
INSERT INTO `sessions` VALUES ('SXRDBQdnUi9Hcp0uIjwdJgnUJ2SAajINpcVEtz5u',3,'127.0.0.1','Demo seed','YTowOnt9',1793768159);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `club_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `handled_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_tickets_handled_by_foreign` (`handled_by`),
  KEY `support_tickets_status_index` (`status`),
  KEY `support_tickets_created_at_index` (`created_at`),
  CONSTRAINT `support_tickets_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
INSERT INTO `support_tickets` VALUES (1,'Dr. Damion Koch PhD','leopoldo.harber@example.com','Torphy PLC','At aperiam natus non.','Quod sint quod beatae deserunt aut. Recusandae pariatur labore in consequatur voluptates hic. Quia rerum distinctio qui fugiat soluta consectetur culpa.\n\nVoluptas quas qui ut numquam. Iusto quis ea doloremque quia. Fugiat nulla debitis consequatur earum. Ut dolores alias quidem eos nihil dolor sed.','new',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(2,'Mrs. Josefa West','mnolan@example.net','Zboncak and Sons','Praesentium est quia quasi et.','Repudiandae nemo delectus sit qui placeat. Veritatis a id illo omnis eius ipsum. Velit quae excepturi quo et tempora similique quae. Reiciendis cupiditate dolores aut adipisci praesentium molestias doloremque.\n\nEt expedita quod quia consequuntur quidem. Pariatur consectetur officiis voluptas tenetur deleniti provident.','new',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(3,'Theo Rau','vkihn@example.org',NULL,'Itaque labore ipsum in cumque error.','Sed non atque laudantium laborum et dolorem magnam. Dolorum quia ut velit quia qui. Culpa aliquid quo et consequuntur distinctio. Ab voluptates sit amet aut.\n\nEt vel quis voluptatem ipsa doloribus fugiat. Est autem ea magnam. Vel aut occaecati reprehenderit aut quasi quo. Atque aliquid ducimus sed dolores quia sit voluptatum.','new',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(4,'Harold Carter','mayert.alexandria@example.com',NULL,'Ea iste quo consequatur accusamus.','Ipsa eos et sint voluptate et expedita. Sit et facere et saepe aut temporibus. Esse suscipit et tenetur fugit.\n\nSunt sed exercitationem distinctio esse aperiam et repellendus. Illum vitae dolorum autem laudantium. Consequatur voluptates rerum eos omnis.','new',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(5,'Joelle Borer','ruecker.maeve@example.net',NULL,'Quae consequatur officiis ipsa expedita impedit.','Mollitia alias voluptatibus incidunt repellat sit. Magnam laudantium non dolore eligendi praesentium. Ea molestiae voluptatem modi nihil. Rerum quas autem ea molestias similique quae nihil.\n\nAut aut mollitia facilis laudantium. Sit ipsum ea id eos saepe molestiae sunt.','new',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(6,'Mrs. Lela Pfannerstill PhD','gkiehn@example.net','Will-Pfeffer','Ullam dolores labore dolorem occaecati.','Rem nam repudiandae numquam ut impedit libero dicta. Incidunt vero non cum earum. Deserunt nihil perspiciatis amet sint quia.\n\nSit dolore ipsa ut accusamus consequatur et. Quia unde modi perferendis. Et aut fugit explicabo commodi.','in_review',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(7,'Hillary Hermiston','sasha.skiles@example.net','Block Ltd','Eos fugiat libero est incidunt.','Voluptatem similique rem et officia. Id qui et quo eligendi. Facere consequatur quo quisquam qui ullam consequatur aut natus. Ratione commodi et dolor.\n\nMollitia voluptates magni quia ut. Sint quod error ipsa quasi. Ducimus accusamus vel vitae aut nesciunt delectus facilis.','in_review',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(8,'Rey Schinner','mlarkin@example.net',NULL,'Quis blanditiis nihil exercitationem.','Ut sed corrupti eligendi facere tempore odio suscipit omnis. Cumque non quia cupiditate voluptatibus temporibus. Omnis optio consequatur debitis non.\n\nAut vero sint et aut aliquam optio quo. Suscipit et et vel. Sint quo ut officia. Aut laudantium et nesciunt ut illum voluptatem beatae.','in_review',NULL,NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(9,'Deshawn Wiegand','lkuhic@example.org',NULL,'Quia necessitatibus asperiores deleniti.','Ea veritatis officiis omnis architecto. Excepturi earum delectus animi. Qui quia sit aliquam. Temporibus natus consequatur numquam eveniet repellat id.\n\nVoluptatem est voluptatem quis dolorum ipsa. Quis ratione dolorem vel enim omnis quae quis. Non perspiciatis quam placeat veniam placeat pariatur. Laborum impedit earum nostrum et maiores et quod nihil.','resolved','Quia recusandae quo ea quibusdam reiciendis nobis.',1,'2026-11-04 04:55:44','2026-11-04 04:55:44','2026-11-04 04:55:44'),(10,'Coty Haley','dicki.maddison@example.net',NULL,'Veniam soluta quam eveniet.','Cumque officiis et quia asperiores fuga iusto non consequatur. Sapiente voluptate dolores delectus dolores optio quis.\n\nEt sit consequuntur officia. Tempore aut consequatur aut facilis. Iusto laborum magni iure qui.','resolved','Aut omnis non quia assumenda illum molestiae cum.',1,'2026-11-04 04:55:44','2026-11-04 04:55:44','2026-11-04 04:55:44'),(11,'Tracey Greenholt','tohara@example.net','Stoltenberg, Wuckert and Welch','Exercitationem rerum eligendi est sint enim.','Amet porro maiores aut quaerat officia iure qui. Voluptate labore quisquam rem voluptates enim laborum. Pariatur libero est autem qui.\n\nOfficiis sunt nesciunt illo aperiam aperiam. Magnam commodi aliquid quam rem. Et asperiores natus ad.','resolved','Beatae itaque in accusantium voluptas.',1,'2026-11-04 04:55:44','2026-11-04 04:55:44','2026-11-04 04:55:44'),(12,'Mrs. Alana Larson MD','vita.dare@example.net',NULL,'Eaque mollitia quis labore.','Ut soluta expedita blanditiis enim. Iste at voluptatem reprehenderit expedita rerum. Et non incidunt consequuntur a non voluptatem eum saepe.\n\nRepellendus nulla sunt culpa laboriosam beatae. Distinctio assumenda reiciendis aliquid mollitia culpa. Laudantium aut id ut enim eum commodi et sequi.','resolved','Ex eum inventore delectus commodi.',1,'2026-11-04 04:55:44','2026-11-04 04:55:44','2026-11-04 04:55:44'),(13,'Enos Schoen DVM','baby24@example.org',NULL,'Odit minima ullam omnis consequatur.','Facilis veniam impedit id quia et nisi consectetur cupiditate. Maiores perspiciatis accusantium fuga accusantium officiis praesentium autem. Cupiditate voluptatem alias sed enim dolores. Beatae aliquam qui omnis harum quidem est.\n\nDolorem quo facere ut consequatur. Quisquam totam est maxime temporibus et animi provident. Voluptas ut aperiam blanditiis ut et earum reprehenderit totam.','discarded','Descartado por duplicado o spam.',NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44'),(14,'Mae Wiegand','ivah73@example.com','Reilly, Schuster and Steuber','Aut eligendi in nesciunt aperiam.','Eum voluptatibus aut et quae aut earum. Doloremque assumenda animi aut illo. Ipsum sint voluptatibus modi.\n\nPossimus odit molestias qui eum pariatur delectus fuga. Consequuntur non aut voluptatem aut illo. Dolorem aliquid rem qui repudiandae.','discarded','Descartado por duplicado o spam.',NULL,NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44');
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenant_memberships`
--

DROP TABLE IF EXISTS `tenant_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_memberships_user_id_tenant_id_unique` (`user_id`,`tenant_id`),
  KEY `tenant_memberships_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `tenant_memberships_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_memberships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_memberships`
--

LOCK TABLES `tenant_memberships` WRITE;
/*!40000 ALTER TABLE `tenant_memberships` DISABLE KEYS */;
INSERT INTO `tenant_memberships` VALUES (1,3,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,4,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,5,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,6,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,7,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,8,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,9,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `tenant_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `founded_year` smallint unsigned DEFAULT NULL,
  `primary_color` char(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `database_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  UNIQUE KEY `tenants_database_name_unique` (`database_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
INSERT INTO `tenants` VALUES (1,'demo','Club Demo Sportiva','CDS','Tigre, Buenos Aires',1985,'#1d4ed8',NULL,'club_demo','active','2026-11-04 04:55:46','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_platform_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `invited_at` timestamp NULL DEFAULT NULL,
  `invite_accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@sportiva.com',NULL,'$2y$12$y.UB8eUKVovik8mtRTvalezFqNe5B9321qx9VeSDw36OmWjj80sbC',NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44',1,1,NULL,NULL),(2,'General User','general@sportiva.com',NULL,'$2y$12$BhUgpEI7SBCi4MqrmodMyuD7TaJWQyD7yY.qqyJwx3PvPwN2lxiH6',NULL,'2026-11-04 04:55:44','2026-11-04 04:55:44',0,1,NULL,NULL),(3,'Laura Gómez','admin.club@sportiva.com',NULL,'$2y$12$IJGMLzQpremEQUdtpDGEuuolzh31mlKFIKA1VmlEfVphQ5tA766Za',NULL,'2026-11-04 04:55:45','2026-11-04 04:55:45',0,1,NULL,NULL),(4,'Diego Herrera','coordinador@sportiva.com',NULL,'$2y$12$BeFQSyFKtNQYyEnSxkLKj.mRUfRAMyABm89.gWQMXzSTKq4uiP732',NULL,'2026-11-04 04:55:45','2026-11-04 04:55:45',0,1,NULL,NULL),(5,'Pablo Ruiz','coordinador.futbol@sportiva.com',NULL,'$2y$12$eDv2bFFmRfGPQ/ItROb1beDUwa5G364LGv8KuI8vfyFAAob1piObu',NULL,'2026-11-04 04:55:45','2026-11-04 04:55:45',0,1,NULL,NULL),(6,'Martín Ledesma','profe@sportiva.com',NULL,'$2y$12$kYAndD6hgbAgGrTidafImejXVwTDMpakCi07oKykGHS00AsUiWy4S',NULL,'2026-11-04 04:55:45','2026-11-04 04:55:45',0,1,NULL,NULL),(7,'Sofía Bianchi','preparador@sportiva.com',NULL,'$2y$12$GY6nviYlrBCIAZkzwFQJE.F5RFU1h5rouVErALpV.RkDax9ZtfBmm',NULL,'2026-11-04 04:55:45','2026-11-04 04:55:45',0,1,NULL,NULL),(8,'Nicolás Paz','utilero@sportiva.com',NULL,'$2y$12$uPjBCeoUbUc4KHf2lI1hkeob1Sp.oIM6N8XN15gMV3I4xk.PI0SEW',NULL,'2026-11-04 04:55:46','2026-11-04 04:55:46',0,1,NULL,NULL),(9,'Ramiro Castro','mantenimiento@sportiva.com',NULL,'$2y$12$EpV1p5gxVoqKdIRoLuR6v.0O42IUJK4Xwa.7EQmVkneiqY9gMzpOy',NULL,'2026-11-04 04:55:46','2026-11-04 04:55:46',0,1,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `club_demo`
--

/*!40000 DROP DATABASE IF EXISTS `club_demo`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `club_demo` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `club_demo`;

--
-- Table structure for table `alert_records`
--

DROP TABLE IF EXISTS `alert_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `dedup_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_records_dedup_key_unique` (`dedup_key`),
  KEY `alert_records_alert_type_subject_type_subject_id_index` (`alert_type`,`subject_type`,`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_records`
--

LOCK TABLES `alert_records` WRITE;
/*!40000 ALTER TABLE `alert_records` DISABLE KEYS */;
INSERT INTO `alert_records` VALUES (1,'attendance_drop','player',6,'attendance_drop:player:6','2026-11-02 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,'registration_closing','competition',1,'registration_closing:competition:1','2026-11-03 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `alert_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_recipients`
--

DROP TABLE IF EXISTS `announcement_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcement_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sources` json NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_recipients_announcement_id_email_unique` (`announcement_id`,`email`),
  KEY `announcement_recipients_announcement_id_status_index` (`announcement_id`,`status`),
  CONSTRAINT `announcement_recipients_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_recipients`
--

LOCK TABLES `announcement_recipients` WRITE;
/*!40000 ALTER TABLE `announcement_recipients` DISABLE KEYS */;
INSERT INTO `announcement_recipients` VALUES (1,1,'lucía.gómez.familia@example.com','Familia de Lucía','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-31 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,'valentina.pereyra.familia@example.com','Familia de Valentina','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-31 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,'julieta.navarro.familia@example.com','Familia de Julieta','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-31 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,'catalina.ibarra.familia@example.com','Familia de Catalina','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-31 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,1,'mía.domínguez.familia@example.com','Familia de Mía','[{\"id\": 2, \"type\": \"category\"}]','failed','Dirección rebotada por el proveedor.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,1,'emma.quiroga.familia@example.com','Familia de Emma','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-31 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `announcement_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` json NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned NOT NULL,
  `sent_by` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `recipients_count` int unsigned NOT NULL DEFAULT '0',
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `failed_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_sent_by_foreign` (`sent_by`),
  KEY `announcements_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `club_users` (`id`),
  CONSTRAINT `announcements_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Cambio de horario del entrenamiento del jueves','Hola familias:\n\nEl entrenamiento del jueves de Sub-15 se mueve a las 17:30 en el Parque Deportivo Norte.\n\nGracias.','{\"type\": \"category\", \"category_ids\": [2]}','sent',2,2,'2026-10-31 04:55:57',6,5,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Recordatorio: traer la autorización firmada','Borrador: recordar a las familias que traigan la autorización firmada antes del torneo.','{\"type\": \"club\"}','draft',2,NULL,NULL,0,0,0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apparel_items`
--

DROP TABLE IF EXISTS `apparel_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `apparel_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apparel_items`
--

LOCK TABLES `apparel_items` WRITE;
/*!40000 ALTER TABLE `apparel_items` DISABLE KEYS */;
INSERT INTO `apparel_items` VALUES (1,'Camiseta titular','Camiseta de juego, con escudo bordado.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Short de juego','Short con bolsillo lateral.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,'Buzo de entrenamiento','Buzo con capucha, cierre completo.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `apparel_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `apparel_order_items`
--

DROP TABLE IF EXISTS `apparel_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `apparel_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `player_id` bigint unsigned NOT NULL,
  `apparel_item_id` bigint unsigned NOT NULL,
  `size` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shirt_number` smallint DEFAULT NULL,
  `alias` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int unsigned NOT NULL,
  `delivered_quantity` int unsigned NOT NULL DEFAULT '0',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apparel_order_items_order_id_foreign` (`order_id`),
  KEY `apparel_order_items_player_id_foreign` (`player_id`),
  KEY `apparel_order_items_apparel_item_id_foreign` (`apparel_item_id`),
  CONSTRAINT `apparel_order_items_apparel_item_id_foreign` FOREIGN KEY (`apparel_item_id`) REFERENCES `apparel_items` (`id`),
  CONSTRAINT `apparel_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `apparel_order_items_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  CONSTRAINT `apparel_order_items_delivered_check` CHECK ((`delivered_quantity` <= `quantity`)),
  CONSTRAINT `apparel_order_items_quantity_check` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apparel_order_items`
--

LOCK TABLES `apparel_order_items` WRITE;
/*!40000 ALTER TABLE `apparel_order_items` DISABLE KEYS */;
INSERT INTO `apparel_order_items` VALUES (1,1,1,1,'10',1,NULL,1,1,NULL),(2,1,2,2,'M',2,NULL,1,1,NULL),(3,1,3,3,'L',3,NULL,1,1,'Talle a confirmar con la familia.'),(4,2,7,1,'10',1,NULL,1,1,NULL),(5,2,8,2,'M',2,NULL,1,0,NULL),(6,2,9,3,'L',3,NULL,1,0,'Talle a confirmar con la familia.'),(7,3,13,1,'10',1,NULL,1,0,NULL),(8,3,14,2,'M',2,NULL,1,0,NULL),(9,3,15,3,'L',3,NULL,1,0,'Talle a confirmar con la familia.'),(10,4,1,1,'10',1,NULL,1,0,NULL),(11,4,2,2,'M',2,NULL,1,0,NULL),(12,4,3,3,'L',3,NULL,1,0,'Talle a confirmar con la familia.');
/*!40000 ALTER TABLE `apparel_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_logs_club_user_id_created_at_index` (`club_user_id`,`created_at`),
  CONSTRAINT `audit_logs_club_user_id_foreign` FOREIGN KEY (`club_user_id`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'club_user.roles_updated','club_user',2,'{\"name\": \"Diego Herrera\", \"roles\": {\"to\": [\"Coordinador deportivo\"], \"from\": [\"Profe\"]}}','127.0.0.1','2026-10-26 04:55:57'),(2,2,'order.approved','order',1,'{\"number\": \"0001\", \"status\": {\"to\": \"approved\"}}','127.0.0.1','2026-10-10 04:55:57'),(3,2,'player.deleted','player',99,'{\"name\": \"Jugador dado de baja\"}','127.0.0.1','2026-10-31 04:55:57'),(4,1,'announcement.sent','announcement',1,'{\"subject\": \"Cambio de horario del entrenamiento del jueves\", \"recipients\": 6}','127.0.0.1','2026-10-31 04:55:57');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sport_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_age` smallint DEFAULT NULL,
  `max_age` smallint DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `calendar_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_sport_id_season_id_name_unique` (`sport_id`,`season_id`,`name`),
  UNIQUE KEY `categories_calendar_token_unique` (`calendar_token`),
  KEY `categories_season_id_foreign` (`season_id`),
  CONSTRAINT `categories_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `categories_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,1,2,'Sub-13 Masculino','male',12,13,1,'b0tF5JhaCTzkAQA7lvkjeXmSI9gTHwiZGGNz5nnS','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,2,'Sub-15 Femenino','female',14,15,1,'r3W8FliLOQfQCib7egRgJLtAMAO2OOFv7I0eVLdu','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,2,2,'Sub-17 Mixto','mixed',16,17,1,'0jtgBEkb7yWE34oIiCYcO2CxHmlcH0JRfpBuS9lC','2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,1,'Sub-13 Masculino 2025','male',12,13,0,NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_coaches`
--

DROP TABLE IF EXISTS `category_coaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_coaches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `coach_id` bigint unsigned NOT NULL,
  `assignment_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_coaches_coach_id_foreign` (`coach_id`),
  KEY `category_coaches_category_id_coach_id_assignment_type_index` (`category_id`,`coach_id`,`assignment_type`),
  CONSTRAINT `category_coaches_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_coaches_coach_id_foreign` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_coaches`
--

LOCK TABLES `category_coaches` WRITE;
/*!40000 ALTER TABLE `category_coaches` DISABLE KEYS */;
INSERT INTO `category_coaches` VALUES (1,1,1,'head_coach','2026-03-01',NULL),(2,2,1,'head_coach','2026-03-01',NULL),(3,2,3,'assistant','2026-03-01',NULL),(4,3,3,'head_coach','2026-03-01',NULL),(5,1,2,'physical_trainer','2026-03-01',NULL),(6,2,2,'physical_trainer','2026-03-01',NULL);
/*!40000 ALTER TABLE `category_coaches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_competitions`
--

DROP TABLE IF EXISTS `category_competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_competitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `competition_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_competitions_category_id_competition_id_unique` (`category_id`,`competition_id`),
  KEY `category_competitions_competition_id_foreign` (`competition_id`),
  CONSTRAINT `category_competitions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_competitions_competition_id_foreign` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_competitions`
--

LOCK TABLES `category_competitions` WRITE;
/*!40000 ALTER TABLE `category_competitions` DISABLE KEYS */;
INSERT INTO `category_competitions` VALUES (1,1,1),(2,2,1),(3,3,2);
/*!40000 ALTER TABLE `category_competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `club_user_roles`
--

DROP TABLE IF EXISTS `club_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_user_roles` (
  `club_user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`club_user_id`,`role_id`),
  KEY `club_user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `club_user_roles_club_user_id_foreign` FOREIGN KEY (`club_user_id`) REFERENCES `club_users` (`id`),
  CONSTRAINT `club_user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club_user_roles`
--

LOCK TABLES `club_user_roles` WRITE;
/*!40000 ALTER TABLE `club_user_roles` DISABLE KEYS */;
INSERT INTO `club_user_roles` VALUES (1,1,'2026-11-04 04:55:57'),(2,2,'2026-11-04 04:55:57'),(3,2,'2026-11-04 04:55:57'),(4,3,'2026-11-04 04:55:57'),(5,4,'2026-11-04 04:55:57'),(6,5,'2026-11-04 04:55:57'),(7,6,'2026-11-04 04:55:57');
/*!40000 ALTER TABLE `club_user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `club_user_sports`
--

DROP TABLE IF EXISTS `club_user_sports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_user_sports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_user_id` bigint unsigned NOT NULL,
  `sport_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `club_user_sports_club_user_id_sport_id_unique` (`club_user_id`,`sport_id`),
  KEY `club_user_sports_sport_id_foreign` (`sport_id`),
  CONSTRAINT `club_user_sports_club_user_id_foreign` FOREIGN KEY (`club_user_id`) REFERENCES `club_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `club_user_sports_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club_user_sports`
--

LOCK TABLES `club_user_sports` WRITE;
/*!40000 ALTER TABLE `club_user_sports` DISABLE KEYS */;
INSERT INTO `club_user_sports` VALUES (1,3,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,2,2,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `club_user_sports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `club_users`
--

DROP TABLE IF EXISTS `club_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `global_user_id` bigint unsigned NOT NULL,
  `display_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `club_users_global_user_id_unique` (`global_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club_users`
--

LOCK TABLES `club_users` WRITE;
/*!40000 ALTER TABLE `club_users` DISABLE KEYS */;
INSERT INTO `club_users` VALUES (1,3,'Laura Gómez','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,4,'Diego Herrera','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,5,'Pablo Ruiz','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,6,'Martín Ledesma','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,7,'Sofía Bianchi','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,8,'Nicolás Paz','active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,9,'Ramiro Castro','active','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `club_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coaches`
--

DROP TABLE IF EXISTS `coaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coaches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_user_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coaches_club_user_id_unique` (`club_user_id`),
  CONSTRAINT `coaches_club_user_id_foreign` FOREIGN KEY (`club_user_id`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coaches`
--

LOCK TABLES `coaches` WRITE;
/*!40000 ALTER TABLE `coaches` DISABLE KEYS */;
INSERT INTO `coaches` VALUES (1,4,'Martín','Ledesma','+54 11 4000-1001','profe@sportiva.com','active','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,5,'Sofía','Bianchi','+54 11 4000-1002','preparador@sportiva.com','active','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,NULL,'Gonzalo','Ferreyra','+54 11 4000-1003',NULL,'active','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `coaches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competition_requirements`
--

DROP TABLE IF EXISTS `competition_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competition_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `validity_months` smallint unsigned DEFAULT NULL,
  `requires_file` tinyint(1) NOT NULL DEFAULT '0',
  `minors_only` tinyint(1) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competition_requirements_competition_id_foreign` (`competition_id`),
  CONSTRAINT `competition_requirements_competition_id_foreign` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_requirements`
--

LOCK TABLES `competition_requirements` WRITE;
/*!40000 ALTER TABLE `competition_requirements` DISABLE KEYS */;
INSERT INTO `competition_requirements` VALUES (1,1,'Apto médico',12,1,0,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,'Ficha de inscripción firmada',NULL,1,0,1,2,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,'Copia del DNI',NULL,1,0,1,3,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,'Autorización de los padres',NULL,1,1,1,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,2,'Apto médico',12,1,0,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,2,'Copia del DNI',NULL,1,0,0,2,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `competition_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competition_standings`
--

DROP TABLE IF EXISTS `competition_standings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competition_standings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `position` smallint unsigned NOT NULL,
  `team_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_own` tinyint(1) NOT NULL DEFAULT '0',
  `played` smallint unsigned NOT NULL DEFAULT '0',
  `won` smallint unsigned NOT NULL DEFAULT '0',
  `drawn` smallint unsigned NOT NULL DEFAULT '0',
  `lost` smallint unsigned NOT NULL DEFAULT '0',
  `goals_for` smallint unsigned NOT NULL DEFAULT '0',
  `goals_against` smallint unsigned NOT NULL DEFAULT '0',
  `points` smallint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `competition_standings_competition_id_category_id_position_unique` (`competition_id`,`category_id`,`position`),
  KEY `competition_standings_category_id_foreign` (`category_id`),
  CONSTRAINT `competition_standings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `competition_standings_competition_id_foreign` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competition_standings`
--

LOCK TABLES `competition_standings` WRITE;
/*!40000 ALTER TABLE `competition_standings` DISABLE KEYS */;
INSERT INTO `competition_standings` VALUES (1,1,1,1,'Club Demo Sportiva',1,5,4,0,1,14,5,12,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,1,2,'Racing de Tigre',0,5,3,1,1,11,6,10,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,1,3,'Deportivo San Fernando',0,5,2,2,1,9,7,8,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,1,4,'Unión de Benavídez',0,5,1,2,2,6,9,5,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,1,1,5,'Club Atlético Escobar',0,5,0,3,2,4,12,3,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `competition_standings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competitions`
--

DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sport_id` bigint unsigned NOT NULL,
  `season_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organizer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competitions_sport_id_foreign` (`sport_id`),
  KEY `competitions_season_id_sport_id_index` (`season_id`,`sport_id`),
  CONSTRAINT `competitions_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `competitions_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competitions`
--

LOCK TABLES `competitions` WRITE;
/*!40000 ALTER TABLE `competitions` DISABLE KEYS */;
INSERT INTO `competitions` VALUES (1,1,2,'Liga Metropolitana Infantil','Liga Metropolitana de Fútbol','2026-11-30','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,2,2,'Torneo Juvenil de Básquet','Federación Básquet Zona Norte','2026-12-05','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documentable_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documentable_id` bigint unsigned NOT NULL,
  `kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `documents_documentable_type_documentable_id_kind_index` (`documentable_type`,`documentable_id`,`kind`),
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `club_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'player',1,'medical','apto-medico.txt','local','demo/players/1/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'player',2,'medical','apto-medico.txt','local','demo/players/2/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,'player',3,'medical','apto-medico.txt','local','demo/players/3/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,'player',4,'medical','apto-medico.txt','local','demo/players/4/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(5,'player',5,'medical','apto-medico.txt','local','demo/players/5/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(6,'player',6,'medical','apto-medico.txt','local','demo/players/6/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(7,'player',7,'medical','apto-medico.txt','local','demo/players/7/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(8,'player',8,'medical','apto-medico.txt','local','demo/players/8/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(9,'player',9,'medical','apto-medico.txt','local','demo/players/9/apto-medico.txt','text/plain',43,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(10,'player',10,'medical','apto-medico.txt','local','demo/players/10/apto-medico.txt','text/plain',44,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(11,'player',11,'medical','apto-medico.txt','local','demo/players/11/apto-medico.txt','text/plain',44,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(12,'player',12,'medical','apto-medico.txt','local','demo/players/12/apto-medico.txt','text/plain',44,4,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Cancha 1','court','Sede principal, sector norte','active','Arcos reforzados en mayo de 2026.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Vestuarios A','locker_room','Sede principal, edificio del quincho','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_components`
--

DROP TABLE IF EXISTS `facility_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_inspected_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facility_components_facility_id_foreign` (`facility_id`),
  CONSTRAINT `facility_components_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_components`
--

LOCK TABLES `facility_components` WRITE;
/*!40000 ALTER TABLE `facility_components` DISABLE KEYS */;
INSERT INTO `facility_components` VALUES (1,1,'Arco norte','review','2026-10-25 04:55:57','Red con un desgarro.','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,'Iluminación','ok','2026-10-05 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,2,'Duchas','out_of_service','2026-11-02 04:55:57','Canilla de la ducha 3 sin agua caliente.','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `facility_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_balances`
--

DROP TABLE IF EXISTS `inventory_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `material_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_balances_material_id_location_id_unique` (`material_id`,`location_id`),
  KEY `inventory_balances_location_id_foreign` (`location_id`),
  CONSTRAINT `inventory_balances_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `storage_locations` (`id`),
  CONSTRAINT `inventory_balances_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `inventory_balances_quantity_check` CHECK ((`quantity` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_balances`
--

LOCK TABLES `inventory_balances` WRITE;
/*!40000 ALTER TABLE `inventory_balances` DISABLE KEYS */;
INSERT INTO `inventory_balances` VALUES (1,1,1,24.00,'2026-11-04 04:55:57'),(2,1,2,0.00,'2026-11-04 04:55:57'),(3,2,1,4.00,'2026-11-04 04:55:57'),(4,2,2,3.00,'2026-11-04 04:55:57'),(5,3,1,16.00,'2026-11-04 04:55:57'),(6,3,2,0.00,'2026-11-04 04:55:57'),(7,4,1,1.00,'2026-11-04 04:55:57'),(8,4,2,1.00,'2026-11-04 04:55:57');
/*!40000 ALTER TABLE `inventory_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_movements`
--

DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `material_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `movement_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `transfer_group_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `performed_by` bigint unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_location_id_foreign` (`location_id`),
  KEY `inventory_movements_order_id_foreign` (`order_id`),
  KEY `inventory_movements_performed_by_foreign` (`performed_by`),
  KEY `inventory_movements_material_id_location_id_created_at_index` (`material_id`,`location_id`,`created_at`),
  KEY `inventory_movements_transfer_group_id_index` (`transfer_group_id`),
  CONSTRAINT `inventory_movements_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `storage_locations` (`id`),
  CONSTRAINT `inventory_movements_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `inventory_movements_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `inventory_movements_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_movements`
--

LOCK TABLES `inventory_movements` WRITE;
/*!40000 ALTER TABLE `inventory_movements` DISABLE KEYS */;
INSERT INTO `inventory_movements` VALUES (1,1,1,'in',24.00,NULL,NULL,6,'Compra inicial de temporada','FACT-0001','2026-10-05 04:55:57'),(2,2,1,'in',6.00,NULL,NULL,6,'Reposición',NULL,'2026-10-15 04:55:57'),(3,2,1,'transfer_out',3.00,'cea998ba-1a50-40a2-862b-9a120ddd6d8b',NULL,6,'Envío al vestuario',NULL,'2026-10-30 04:55:57'),(4,2,2,'transfer_in',3.00,'cea998ba-1a50-40a2-862b-9a120ddd6d8b',NULL,6,'Envío desde depósito principal',NULL,'2026-10-30 04:55:57'),(5,3,1,'adjustment',-4.00,NULL,NULL,6,'Inventario físico: dos chalecos rotos',NULL,'2026-11-02 04:55:57'),(6,4,2,'out',1.00,NULL,NULL,6,'Uso en partido',NULL,'2026-11-03 04:55:57');
/*!40000 ALTER TABLE `inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_records`
--

DROP TABLE IF EXISTS `maintenance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `repair_request_id` bigint unsigned DEFAULT NULL,
  `facility_id` bigint unsigned NOT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `performed_at` datetime NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_records_repair_request_id_foreign` (`repair_request_id`),
  KEY `maintenance_records_facility_id_foreign` (`facility_id`),
  KEY `maintenance_records_performed_by_foreign` (`performed_by`),
  CONSTRAINT `maintenance_records_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `maintenance_records_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `club_users` (`id`),
  CONSTRAINT `maintenance_records_repair_request_id_foreign` FOREIGN KEY (`repair_request_id`) REFERENCES `repair_requests` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_records`
--

LOCK TABLES `maintenance_records` WRITE;
/*!40000 ALTER TABLE `maintenance_records` DISABLE KEYS */;
INSERT INTO `maintenance_records` VALUES (1,2,2,7,'2026-10-29 04:55:57','Se cambió la canilla y se probó el agua caliente.',18500.00,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,1,7,'2026-11-03 04:55:57','Se pidió presupuesto de red nueva al proveedor.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `maintenance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_callups`
--

DROP TABLE IF EXISTS `match_callups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_callups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint unsigned NOT NULL,
  `player_id` bigint unsigned NOT NULL,
  `shirt_number` smallint unsigned DEFAULT NULL,
  `response` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `response_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `match_callups_match_id_player_id_unique` (`match_id`,`player_id`),
  UNIQUE KEY `match_callups_token_unique` (`token`),
  KEY `match_callups_player_id_foreign` (`player_id`),
  CONSTRAINT `match_callups_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `match_callups_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_callups`
--

LOCK TABLES `match_callups` WRITE;
/*!40000 ALTER TABLE `match_callups` DISABLE KEYS */;
INSERT INTO `match_callups` VALUES (1,1,1,1,'going',NULL,'2026-11-03 23:55:57','2026-11-03 04:55:57','UBbA769LCDz4Fr2hV3p5QDG4t97LZOVn','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,2,2,'going',NULL,'2026-11-03 23:55:57','2026-11-03 04:55:57','Zh0DZoOTtC1GUYdQ2OBDGMUbOJINwJ6f','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,3,3,'not_going','Viaja con la familia ese fin de semana.','2026-11-03 23:55:57','2026-11-03 04:55:57','XXlZGoY3RXfsfj2BGPtGfdAtzkwSq8uR','2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,4,4,'going',NULL,'2026-11-03 23:55:57','2026-11-03 04:55:57','5N5BeMZstEonAZaz0DQ1zVLRvpQhrYP6','2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,1,5,5,'pending',NULL,NULL,'2026-11-03 04:55:57','3agBXPFK7cnqz4XwqQO1iGQTIwqvQWn6','2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,1,6,6,'going',NULL,'2026-11-03 23:55:57','2026-11-03 04:55:57','AufPdyYATJRJ90S2Sv1uZ4C6V2GNQzTQ','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `match_callups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `competition_id` bigint unsigned DEFAULT NULL,
  `opponent` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `match_date` datetime NOT NULL,
  `meet_at` datetime DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `venue` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_home` tinyint(1) NOT NULL,
  `uniform` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uniform_color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `what_to_bring` text COLLATE utf8mb4_unicode_ci,
  `our_score` smallint unsigned DEFAULT NULL,
  `opponent_score` smallint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `callup_published_at` timestamp NULL DEFAULT NULL,
  `public_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_after_publish` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matches_public_token_unique` (`public_token`),
  KEY `matches_category_id_match_date_index` (`category_id`,`match_date`),
  KEY `matches_competition_id_foreign` (`competition_id`),
  KEY `matches_venue_id_foreign` (`venue_id`),
  CONSTRAINT `matches_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `matches_competition_id_foreign` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `matches_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES (1,1,1,'Racing de Tigre','2026-11-09 10:00:00','2026-11-09 09:15:00',1,'Cancha Principal','https://maps.google.com/?q=-34.4226,-58.5796',1,'home','Azul','Botines, canilleras y un buzo.',NULL,NULL,'scheduled','Confirmar arbitraje.','2026-11-03 04:55:57','U8FO1XrtZuBAz45N0QVwSwKaQWQBkuxz',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,1,1,'Deportivo San Fernando','2026-10-28 10:00:00',NULL,NULL,'Estadio Municipal de San Fernando',NULL,0,'away','Blanco',NULL,3,1,'played',NULL,'2026-10-26 04:55:57','vrQeRyT8UZteLHveX48cwbFDquOpwq1v',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,2,1,'Unión de Benavídez','2026-11-13 11:00:00',NULL,2,'Parque Deportivo Norte',NULL,1,'home','Celeste',NULL,NULL,NULL,'postponed','Se reprograma por lluvia.',NULL,'pGVNgiap4wDetRgfr7Kc2kBv99smjk8a',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,3,2,'Club Atlético Escobar','2026-10-21 20:00:00',NULL,NULL,'Gimnasio Central',NULL,1,'home','Verde',NULL,62,58,'played',NULL,NULL,'Ig58f4oLfexFeWrDvIAHRHFm1rA0ooDQ',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(5,3,2,'Club Social San Isidro','2026-11-16 20:00:00',NULL,NULL,'Club San Isidro',NULL,0,NULL,NULL,NULL,NULL,NULL,'cancelled','Cancelado por la federación.',NULL,'PBVKYdS8BH9nBOkwzYikkawfzx5Olujg',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(6,1,1,'Tigre Juniors','2026-08-15 10:00:00','2026-08-15 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,2,1,'played',NULL,'2026-08-13 10:00:00','slEZQTzzYXAl92rb4c7UgwUJNwf9dJUl',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(7,1,1,'Club Náutico Hacoaj','2026-08-29 10:00:00','2026-08-29 09:15:00',NULL,'Cancha de Club Náutico Hacoaj',NULL,0,'away','Blanco',NULL,0,0,'played',NULL,'2026-08-27 10:00:00','W7wXtTEFQNe6vYjLq7ucjhJqmEzq1G8z',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(8,1,1,'Atlético Pacheco','2026-09-12 10:00:00','2026-09-12 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,1,2,'played',NULL,'2026-09-10 10:00:00','RAmOpfAeKzDgqnY10Q2NbLWV1tx3KrgU',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(9,1,1,'San Fernando FC','2026-09-26 10:00:00','2026-09-26 09:15:00',NULL,'Cancha de San Fernando FC',NULL,0,'away','Blanco',NULL,3,0,'played',NULL,'2026-09-24 10:00:00','AEG1V1KZUiLkmAyDL0cMYL5oFpwIZcf1',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(10,1,1,'Villa La Ñata','2026-10-10 10:00:00','2026-10-10 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,2,2,'played',NULL,'2026-10-08 10:00:00','WbkPUqaFu0alhksZq4bL4Tdygd8UMkBW',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(11,1,1,'Don Torcuato','2026-10-24 10:00:00','2026-10-24 09:15:00',NULL,'Cancha de Don Torcuato',NULL,0,'away','Blanco',NULL,1,0,'played',NULL,'2026-10-22 10:00:00','BKvIefBl6hfxL1vzw2jB8AZPeGT3Cx5f',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(12,2,1,'Las Leonas de Nordelta','2026-08-15 10:00:00','2026-08-15 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,1,3,'played',NULL,'2026-08-13 10:00:00','rW3LaHlGa3Lfkj2ZEAllxkCkZVyGKhY3',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(13,2,1,'Club Benavídez','2026-08-29 10:00:00','2026-08-29 09:15:00',NULL,'Cancha de Club Benavídez',NULL,0,'away','Blanco',NULL,2,2,'played',NULL,'2026-08-27 10:00:00','icCGWMPjeIdE5XSsUHDPxr3Dt9nLtbNl',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(14,2,1,'Escobar Fútbol','2026-09-12 10:00:00','2026-09-12 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,2,0,'played',NULL,'2026-09-10 10:00:00','XebPZBE6obwQCo4es6a29N7G1wwBqygX',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(15,2,1,'Unión Pacheco','2026-09-26 10:00:00','2026-09-26 09:15:00',NULL,'Cancha de Unión Pacheco',NULL,0,'away','Blanco',NULL,0,1,'played',NULL,'2026-09-24 10:00:00','7NxNi4QrbIqD92CyTN31vehkuhKVEejF',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(16,2,1,'Rincón FC','2026-10-10 10:00:00','2026-10-10 09:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,3,1,'played',NULL,'2026-10-08 10:00:00','gfuuG5DpUXIRy5uukVuMzwXVXyssu9uz',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(17,2,1,'Garín Sport','2026-10-24 10:00:00','2026-10-24 09:15:00',NULL,'Cancha de Garín Sport',NULL,0,'away','Blanco',NULL,2,1,'played',NULL,'2026-10-22 10:00:00','5cEJLDbfQGBSbZBVobzo2MYT413dsmOo',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(18,3,2,'Náutico San Isidro','2026-08-14 20:00:00','2026-08-14 19:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,58,61,'played',NULL,'2026-08-12 20:00:00','3SjnzlYJMB7CfB0dMdvTTS6EIzjy8UML',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(19,3,2,'Atenas de Tigre','2026-08-28 20:00:00','2026-08-28 19:15:00',NULL,'Cancha de Atenas de Tigre',NULL,0,'away','Blanco',NULL,72,64,'played',NULL,'2026-08-26 20:00:00','Iy7sRZXCyaYajhuLz0vdZYoKoRg3kwnl',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(20,3,2,'Club Pilar Básquet','2026-09-11 20:00:00','2026-09-11 19:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,66,66,'played',NULL,'2026-09-09 20:00:00','Ht4QzUqle0WWqWZkNrzQUHlf52diEYtu',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(21,3,2,'Olimpo Escobar','2026-09-25 20:00:00','2026-09-25 19:15:00',NULL,'Cancha de Olimpo Escobar',NULL,0,'away','Blanco',NULL,70,55,'played',NULL,'2026-09-23 20:00:00','tdjESno49qmKVwCnGGQ3kcSNiw6TI4UI',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(22,3,2,'Don Bosco','2026-10-09 20:00:00','2026-10-09 19:15:00',1,'Cancha Principal',NULL,1,'home','Azul',NULL,61,68,'played',NULL,'2026-10-07 20:00:00','RnarIGhLKtKDBEyoBC7oxds5bEEvfSyU',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(23,3,2,'Racing de Garín','2026-10-23 20:00:00','2026-10-23 19:15:00',NULL,'Cancha de Racing de Garín',NULL,0,'away','Blanco',NULL,77,70,'played',NULL,'2026-10-21 20:00:00','u0GdoW6OPR1aexerP6vLy6f7fAUF8rPM',0,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_order_items`
--

DROP TABLE IF EXISTS `material_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `material_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `delivered_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_order_items_order_id_foreign` (`order_id`),
  KEY `material_order_items_material_id_foreign` (`material_id`),
  CONSTRAINT `material_order_items_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `material_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_order_items_delivered_check` CHECK ((`delivered_quantity` <= `quantity`)),
  CONSTRAINT `material_order_items_quantity_check` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_order_items`
--

LOCK TABLES `material_order_items` WRITE;
/*!40000 ALTER TABLE `material_order_items` DISABLE KEYS */;
INSERT INTO `material_order_items` VALUES (1,5,1,6.00,6.00,NULL),(2,6,4,6.00,0.00,NULL);
/*!40000 ALTER TABLE `material_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_stock` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `materials_name_unique` (((case when (`deleted_at` is null) then `name` end)))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,'Conos de entrenamiento','Set de 20 conos naranjas.','unidad',10.00,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Pelotas de fútbol N°5','Pelotas oficiales de la liga.','unidad',6.00,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,'Chalecos de peto','Chalecos para partidillos.','unidad',12.00,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,'Botiquín de primeros auxilios',NULL,'kit',2.00,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_10_17_231953_create_players_table',1),(2,'2026_10_17_232500_create_sports_table',1),(3,'2026_10_17_232501_create_seasons_table',1),(4,'2026_10_17_232502_create_categories_table',1),(5,'2026_10_25_000001_create_coaches_table',1),(6,'2026_10_25_000002_create_club_users_table',1),(7,'2026_10_25_000003_create_roles_table',1),(8,'2026_10_25_000004_create_permissions_table',1),(9,'2026_10_25_000005_create_club_user_roles_table',1),(10,'2026_10_25_000006_create_role_permissions_table',1),(11,'2026_10_25_000007_add_club_user_foreign_key_to_coaches_table',1),(12,'2026_10_25_000008_create_player_category_memberships_table',1),(13,'2026_10_25_000009_create_category_coaches_table',1),(14,'2026_10_25_000010_create_storage_locations_table',1),(15,'2026_10_25_000011_create_materials_table',1),(16,'2026_10_25_000012_create_apparel_items_table',1),(17,'2026_10_25_000013_create_facilities_table',1),(18,'2026_10_25_000014_create_facility_components_table',1),(19,'2026_10_25_000015_create_matches_table',1),(20,'2026_10_25_000016_create_trainings_table',1),(21,'2026_10_25_000017_create_player_attendance_table',1),(22,'2026_10_25_000018_create_orders_table',1),(23,'2026_10_25_000019_create_apparel_order_items_table',1),(24,'2026_10_25_000020_create_material_order_items_table',1),(25,'2026_10_25_000021_create_order_status_history_table',1),(26,'2026_10_25_000022_create_inventory_balances_table',1),(27,'2026_10_25_000023_create_inventory_movements_table',1),(28,'2026_10_25_000024_create_physical_evaluations_table',1),(29,'2026_10_25_000025_create_physical_measurements_table',1),(30,'2026_10_25_000026_create_repair_requests_table',1),(31,'2026_10_25_000027_create_maintenance_records_table',1),(32,'2026_10_25_000028_create_audit_logs_table',1),(33,'2026_10_25_000029_create_notifications_table',1),(34,'2026_10_30_000001_add_catalog_columns_to_roles_and_permissions',1),(35,'2026_10_31_000001_create_player_contacts_table',1),(36,'2026_10_31_000002_add_callup_details_to_matches_table',1),(37,'2026_10_31_000003_create_competitions_table',1),(38,'2026_10_31_000004_create_category_competitions_table',1),(39,'2026_10_31_000005_create_competition_requirements_table',1),(40,'2026_10_31_000006_add_competition_id_to_matches_table',1),(41,'2026_10_31_000007_create_documents_table',1),(42,'2026_10_31_000008_create_player_requirements_table',1),(43,'2026_10_31_000009_create_match_callups_table',1),(44,'2026_10_31_000010_add_callup_publication_to_matches_table',1),(45,'2026_10_31_000011_add_minimum_stock_to_materials_table',1),(46,'2026_10_31_000012_create_venues_table',1),(47,'2026_10_31_000013_add_venue_to_matches_table',1),(48,'2026_11_01_000001_create_club_user_sports_table',1),(49,'2026_11_01_000002_add_type_and_data_to_notifications_table',1),(50,'2026_11_01_000003_create_alert_records_table',1),(51,'2026_11_02_000001_add_notified_at_to_match_callups_table',1),(52,'2026_11_02_000001_create_season_objectives_table',1),(53,'2026_11_03_000001_add_branch_to_categories_table',1),(54,'2026_11_03_000002_add_gender_to_players_table',1),(55,'2026_11_03_000003_create_announcements_tables',1),(56,'2026_11_04_000001_create_competition_standings_table',1),(57,'2026_11_05_000001_add_calendar_token_to_categories_table',1),(58,'2026_11_06_000001_create_positions_table',1),(59,'2026_11_06_000002_create_player_positions_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_user_id` bigint unsigned NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_club_user_id_read_at_index` (`club_user_id`,`read_at`),
  KEY `notifications_club_user_id_type_index` (`club_user_id`,`type`),
  CONSTRAINT `notifications_club_user_id_foreign` FOREIGN KEY (`club_user_id`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,4,'callup_pending','Faltan respuestas a la convocatoria','Dos convocados todavía no respondieron al partido del próximo sábado.','{\"match_id\": 1}',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,4,'attendance_drop','Asistencia baja: Santino Correa','Faltó a los últimos dos entrenamientos.','{\"player_id\": 6}','2026-11-03 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,2,'requirement_expiring','Apto médico por vencer','El apto de Julieta Navarro vence en 30 días.','{\"player_id\": 9}',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,7,'repair_assigned','Te asignaron una reparación','Red del arco norte rota.','{\"repair_request_id\": 1}',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,6,'order_approved','Pedido aprobado','El pedido de materiales fue aprobado y está en preparación.','[]','2026-11-02 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `changed_by` bigint unsigned NOT NULL,
  `old_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_status_history_changed_by_foreign` (`changed_by`),
  KEY `order_status_history_order_id_created_at_index` (`order_id`,`created_at`),
  CONSTRAINT `order_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `club_users` (`id`),
  CONSTRAINT `order_status_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
INSERT INTO `order_status_history` VALUES (1,1,4,NULL,'submitted',NULL,'2026-10-05 04:55:57'),(2,1,2,'submitted','approved',NULL,'2026-10-05 05:55:57'),(3,1,2,'approved','preparing',NULL,'2026-10-05 06:55:57'),(4,1,2,'preparing','delivered',NULL,'2026-10-05 07:55:57'),(5,2,4,NULL,'submitted',NULL,'2026-10-11 04:55:57'),(6,2,2,'submitted','approved',NULL,'2026-10-11 05:55:57'),(7,2,2,'approved','partially_delivered',NULL,'2026-10-11 06:55:57'),(8,3,4,NULL,'submitted',NULL,'2026-10-17 04:55:57'),(9,4,4,NULL,'submitted',NULL,'2026-10-23 04:55:57'),(10,4,2,'submitted','rejected','Ya existe un pedido igual para la misma categoría.','2026-10-23 05:55:57'),(11,5,6,NULL,'submitted',NULL,'2026-10-23 04:55:57'),(12,5,2,'submitted','approved',NULL,'2026-10-23 05:55:57'),(13,5,2,'approved','delivered',NULL,'2026-10-23 06:55:57'),(14,6,6,NULL,'submitted',NULL,'2026-10-23 04:55:57'),(15,6,2,'submitted','approved',NULL,'2026-10-23 05:55:57'),(16,6,2,'approved','preparing',NULL,'2026-10-23 06:55:57');
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `description` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_requested_by_foreign` (`requested_by`),
  KEY `orders_category_id_foreign` (`category_id`),
  KEY `orders_approved_by_foreign` (`approved_by`),
  KEY `orders_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `club_users` (`id`),
  CONSTRAINT `orders_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `orders_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'2026-0001','apparel',4,1,'delivered','normal','Indumentaria para el inicio de temporada Sub-13.',2,'2026-10-06 04:55:57','2026-10-10 04:55:57','2026-10-05 04:55:57','2026-10-05 04:55:57',NULL),(2,'2026-0002','apparel',4,2,'partially_delivered','high','Camisetas para Sub-15 (faltan dos talles).',2,'2026-10-12 04:55:57',NULL,'2026-10-11 04:55:57','2026-10-11 04:55:57',NULL),(3,'2026-0003','apparel',4,3,'submitted','low','Buzos para el plantel de Sub-17.',NULL,NULL,NULL,'2026-10-17 04:55:57','2026-10-17 04:55:57',NULL),(4,'2026-0004','apparel',4,1,'rejected','normal','Pedido duplicado de camisetas.',NULL,NULL,NULL,'2026-10-23 04:55:57','2026-10-23 04:55:57',NULL),(5,'2026-0005','material',6,NULL,'delivered','normal','Reposición de pelotas para la semana.',2,'2026-10-24 04:55:57','2026-10-28 04:55:57','2026-10-23 04:55:57','2026-10-23 04:55:57',NULL),(6,'2026-0006','material',6,NULL,'preparing','urgent','Botiquín para la Cancha 1.',2,'2026-10-24 04:55:57',NULL,'2026-10-23 04:55:57','2026-10-23 04:55:57',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'players.view','jugadores','Jugadores · Jugadores: Ver'),(2,'players.create','jugadores','Jugadores · Jugadores: Crear'),(3,'players.update','jugadores','Jugadores · Jugadores: Editar'),(4,'players.delete','jugadores','Jugadores · Jugadores: Eliminar'),(5,'positions.view','jugadores','Jugadores · Posiciones por deporte: Ver'),(6,'positions.create','jugadores','Jugadores · Posiciones por deporte: Crear'),(7,'positions.update','jugadores','Jugadores · Posiciones por deporte: Editar'),(8,'positions.delete','jugadores','Jugadores · Posiciones por deporte: Eliminar'),(9,'categories.manage_players','jugadores','Jugadores · Planteles: Armar planteles'),(10,'player_contacts.view','jugadores','Jugadores · Contactos y tutores: Ver'),(11,'player_contacts.update','jugadores','Jugadores · Contactos y tutores: Editar'),(12,'player_documents.view','jugadores','Jugadores · Documentos (apto, ficha, DNI): Ver'),(13,'player_documents.upload','jugadores','Jugadores · Documentos (apto, ficha, DNI): Subir'),(14,'player_documents.delete','jugadores','Jugadores · Documentos (apto, ficha, DNI): Eliminar'),(15,'coaches.view','profes','Profes · Profes: Ver'),(16,'coaches.create','profes','Profes · Profes: Crear'),(17,'coaches.update','profes','Profes · Profes: Editar'),(18,'coaches.delete','profes','Profes · Profes: Eliminar'),(19,'categories.manage_coaches','profes','Profes · Profes por categoría: Asignar profes'),(20,'matches.view','partidos','Partidos · Partidos: Ver'),(21,'matches.create','partidos','Partidos · Partidos: Crear'),(22,'matches.update','partidos','Partidos · Partidos: Editar'),(23,'matches.delete','partidos','Partidos · Partidos: Eliminar'),(24,'trainings.view','partidos','Partidos · Entrenamientos: Ver'),(25,'trainings.create','partidos','Partidos · Entrenamientos: Crear'),(26,'trainings.update','partidos','Partidos · Entrenamientos: Editar'),(27,'trainings.delete','partidos','Partidos · Entrenamientos: Eliminar'),(28,'attendance.view','partidos','Partidos · Asistencia: Ver'),(29,'attendance.record','partidos','Partidos · Asistencia: Registrar'),(30,'callups.view','partidos','Partidos · Convocatorias: Ver'),(31,'callups.update','partidos','Partidos · Convocatorias: Editar'),(32,'callups.publish','partidos','Partidos · Convocatorias: Publicar'),(33,'physical_evaluations.view','analisis','Análisis · Evaluaciones físicas: Ver'),(34,'physical_evaluations.create','analisis','Análisis · Evaluaciones físicas: Crear'),(35,'physical_evaluations.update','analisis','Análisis · Evaluaciones físicas: Editar'),(36,'physical_evaluations.delete','analisis','Análisis · Evaluaciones físicas: Eliminar'),(37,'project.view','competencia','Proyecto · Objetivos y tabla: Ver'),(38,'project.update','competencia','Proyecto · Objetivos y tabla: Editar'),(39,'competitions.view','habilitacion','Habilitación · Competencias y requisitos: Ver'),(40,'competitions.create','habilitacion','Habilitación · Competencias y requisitos: Crear'),(41,'competitions.update','habilitacion','Habilitación · Competencias y requisitos: Editar'),(42,'competitions.delete','habilitacion','Habilitación · Competencias y requisitos: Eliminar'),(43,'player_requirements.view','habilitacion','Habilitación · Papeles de cada jugador: Ver'),(44,'player_requirements.update','habilitacion','Habilitación · Papeles de cada jugador: Editar'),(45,'player_requirements.approve','habilitacion','Habilitación · Papeles de cada jugador: Aprobar'),(46,'apparel_items.view','indumentaria','Indumentaria · Catálogo de prendas: Ver'),(47,'apparel_items.create','indumentaria','Indumentaria · Catálogo de prendas: Crear'),(48,'apparel_items.update','indumentaria','Indumentaria · Catálogo de prendas: Editar'),(49,'apparel_items.delete','indumentaria','Indumentaria · Catálogo de prendas: Eliminar'),(50,'apparel_orders.view','indumentaria','Indumentaria · Pedidos de indumentaria: Ver'),(51,'apparel_orders.create','indumentaria','Indumentaria · Pedidos de indumentaria: Crear'),(52,'apparel_orders.update','indumentaria','Indumentaria · Pedidos de indumentaria: Editar'),(53,'apparel_orders.delete','indumentaria','Indumentaria · Pedidos de indumentaria: Eliminar'),(54,'apparel_orders.approve','indumentaria','Indumentaria · Pedidos de indumentaria: Aprobar'),(55,'apparel_orders.deliver','indumentaria','Indumentaria · Pedidos de indumentaria: Entregar'),(56,'materials.view','materiales','Materiales · Materiales: Ver'),(57,'materials.create','materiales','Materiales · Materiales: Crear'),(58,'materials.update','materiales','Materiales · Materiales: Editar'),(59,'materials.delete','materiales','Materiales · Materiales: Eliminar'),(60,'storage_locations.view','materiales','Materiales · Depósitos: Ver'),(61,'storage_locations.create','materiales','Materiales · Depósitos: Crear'),(62,'storage_locations.update','materiales','Materiales · Depósitos: Editar'),(63,'storage_locations.delete','materiales','Materiales · Depósitos: Eliminar'),(64,'inventory.view','materiales','Materiales · Stock: Ver'),(65,'inventory.adjust','materiales','Materiales · Stock: Ajustar'),(66,'inventory.transfer','materiales','Materiales · Stock: Transferir'),(67,'material_orders.view','materiales','Materiales · Pedidos de materiales: Ver'),(68,'material_orders.create','materiales','Materiales · Pedidos de materiales: Crear'),(69,'material_orders.update','materiales','Materiales · Pedidos de materiales: Editar'),(70,'material_orders.delete','materiales','Materiales · Pedidos de materiales: Eliminar'),(71,'material_orders.approve','materiales','Materiales · Pedidos de materiales: Aprobar'),(72,'material_orders.deliver','materiales','Materiales · Pedidos de materiales: Entregar'),(73,'facilities.view','canchas','Canchas · Canchas e instalaciones: Ver'),(74,'facilities.create','canchas','Canchas · Canchas e instalaciones: Crear'),(75,'facilities.update','canchas','Canchas · Canchas e instalaciones: Editar'),(76,'facilities.delete','canchas','Canchas · Canchas e instalaciones: Eliminar'),(77,'repairs.view','canchas','Canchas · Reparaciones: Ver'),(78,'repairs.create','canchas','Canchas · Reparaciones: Crear'),(79,'repairs.update','canchas','Canchas · Reparaciones: Editar'),(80,'repairs.delete','canchas','Canchas · Reparaciones: Eliminar'),(81,'repairs.assign','canchas','Canchas · Reparaciones: Asignar'),(82,'maintenance.view','canchas','Canchas · Mantenimiento: Ver'),(83,'maintenance.create','canchas','Canchas · Mantenimiento: Crear'),(84,'maintenance.update','canchas','Canchas · Mantenimiento: Editar'),(85,'maintenance.delete','canchas','Canchas · Mantenimiento: Eliminar'),(86,'club.update','configuracion','Configuración · Perfil del club: Editar'),(87,'sports.view','configuracion','Configuración · Deportes: Ver'),(88,'sports.create','configuracion','Configuración · Deportes: Crear'),(89,'sports.update','configuracion','Configuración · Deportes: Editar'),(90,'sports.delete','configuracion','Configuración · Deportes: Eliminar'),(91,'seasons.view','configuracion','Configuración · Temporadas: Ver'),(92,'seasons.create','configuracion','Configuración · Temporadas: Crear'),(93,'seasons.update','configuracion','Configuración · Temporadas: Editar'),(94,'seasons.delete','configuracion','Configuración · Temporadas: Eliminar'),(95,'categories.view','configuracion','Configuración · Categorías: Ver'),(96,'categories.create','configuracion','Configuración · Categorías: Crear'),(97,'categories.update','configuracion','Configuración · Categorías: Editar'),(98,'categories.delete','configuracion','Configuración · Categorías: Eliminar'),(99,'venues.view','configuracion','Configuración · Sedes: Ver'),(100,'venues.create','configuracion','Configuración · Sedes: Crear'),(101,'venues.update','configuracion','Configuración · Sedes: Editar'),(102,'venues.delete','configuracion','Configuración · Sedes: Eliminar'),(103,'club_users.view','equipo','Equipo del club · Usuarios: Ver'),(104,'club_users.invite','equipo','Equipo del club · Usuarios: Invitar'),(105,'club_users.update','equipo','Equipo del club · Usuarios: Editar'),(106,'club_users.disable','equipo','Equipo del club · Usuarios: Deshabilitar'),(107,'roles.view','equipo','Equipo del club · Roles: Ver'),(108,'roles.create','equipo','Equipo del club · Roles: Crear'),(109,'roles.update','equipo','Equipo del club · Roles: Editar'),(110,'roles.delete','equipo','Equipo del club · Roles: Eliminar'),(111,'roles.assign','equipo','Equipo del club · Roles: Asignar'),(112,'audit.view','equipo','Equipo del club · Registro de actividad: Ver'),(113,'announcements.view','comunicados','Comunicados · Comunicados: Ver'),(114,'announcements.send','comunicados','Comunicados · Comunicados: Enviar');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_evaluations`
--

DROP TABLE IF EXISTS `physical_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_evaluations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `evaluator_coach_id` bigint unsigned NOT NULL,
  `evaluated_at` datetime NOT NULL,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `recommendations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `physical_evaluations_category_id_foreign` (`category_id`),
  KEY `physical_evaluations_evaluator_coach_id_foreign` (`evaluator_coach_id`),
  KEY `physical_evaluations_player_id_evaluated_at_index` (`player_id`,`evaluated_at`),
  CONSTRAINT `physical_evaluations_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `physical_evaluations_evaluator_coach_id_foreign` FOREIGN KEY (`evaluator_coach_id`) REFERENCES `coaches` (`id`),
  CONSTRAINT `physical_evaluations_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_evaluations`
--

LOCK TABLES `physical_evaluations` WRITE;
/*!40000 ALTER TABLE `physical_evaluations` DISABLE KEYS */;
INSERT INTO `physical_evaluations` VALUES (1,7,2,2,'2026-10-15 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,8,2,2,'2026-10-16 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,9,2,2,'2026-10-17 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,1,1,2,'2026-08-16 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(5,1,1,2,'2026-09-15 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(6,2,1,2,'2026-08-15 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(7,2,1,2,'2026-09-14 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(8,3,1,2,'2026-08-14 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(9,3,1,2,'2026-09-13 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(10,4,1,2,'2026-08-13 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(11,4,1,2,'2026-09-12 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(12,5,1,2,'2026-08-12 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(13,5,1,2,'2026-09-11 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(14,6,1,2,'2026-08-11 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(15,6,1,2,'2026-09-10 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(16,7,2,2,'2026-08-10 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(17,7,2,2,'2026-09-09 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(18,8,2,2,'2026-08-09 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(19,8,2,2,'2026-09-08 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(20,9,2,2,'2026-08-08 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(21,9,2,2,'2026-09-07 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(22,10,2,2,'2026-08-07 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(23,10,2,2,'2026-09-06 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(24,11,2,2,'2026-08-06 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(25,11,2,2,'2026-09-05 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(26,12,2,2,'2026-08-05 17:00:00','Evaluación de inicio de temporada.','Trabajo de fuerza y técnica de carrera.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(27,12,2,2,'2026-09-04 17:00:00','Control de mitad de temporada: viene mejorando.','Sumar pasadas cortas al final del entrenamiento.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `physical_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_measurements`
--

DROP TABLE IF EXISTS `physical_measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_measurements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint unsigned NOT NULL,
  `metric_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numeric_value` decimal(10,3) DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `physical_measurements_evaluation_id_metric_key_unique` (`evaluation_id`,`metric_key`),
  KEY `physical_measurements_metric_key_index` (`metric_key`),
  CONSTRAINT `physical_measurements_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `physical_evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `physical_measurements_value_check` CHECK (((`numeric_value` is not null) or (`text_value` is not null)))
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_measurements`
--

LOCK TABLES `physical_measurements` WRITE;
/*!40000 ALTER TABLE `physical_measurements` DISABLE KEYS */;
INSERT INTO `physical_measurements` VALUES (1,1,'sprint_30m','Sprint 30 m',4.600,'s',NULL,NULL),(2,1,'salto_horizontal','Salto horizontal',160.000,'cm',NULL,NULL),(3,1,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL),(4,2,'sprint_30m','Sprint 30 m',4.700,'s',NULL,NULL),(5,2,'salto_horizontal','Salto horizontal',164.000,'cm',NULL,NULL),(6,2,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL),(7,3,'sprint_30m','Sprint 30 m',4.800,'s',NULL,NULL),(8,3,'salto_horizontal','Salto horizontal',168.000,'cm',NULL,NULL),(9,3,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL),(10,4,'sprint_30m','Sprint 30 m',4.900,'s',NULL,NULL),(11,4,'salto_horizontal','Salto horizontal',150.000,'cm',NULL,NULL),(12,5,'sprint_30m','Sprint 30 m',4.750,'s',NULL,NULL),(13,5,'salto_horizontal','Salto horizontal',156.000,'cm',NULL,NULL),(14,6,'sprint_30m','Sprint 30 m',5.000,'s',NULL,NULL),(15,6,'salto_horizontal','Salto horizontal',153.000,'cm',NULL,NULL),(16,7,'sprint_30m','Sprint 30 m',4.850,'s',NULL,NULL),(17,7,'salto_horizontal','Salto horizontal',159.000,'cm',NULL,NULL),(18,8,'sprint_30m','Sprint 30 m',5.100,'s',NULL,NULL),(19,8,'salto_horizontal','Salto horizontal',156.000,'cm',NULL,NULL),(20,9,'sprint_30m','Sprint 30 m',4.950,'s',NULL,NULL),(21,9,'salto_horizontal','Salto horizontal',162.000,'cm',NULL,NULL),(22,10,'sprint_30m','Sprint 30 m',4.900,'s',NULL,NULL),(23,10,'salto_horizontal','Salto horizontal',159.000,'cm',NULL,NULL),(24,11,'sprint_30m','Sprint 30 m',4.750,'s',NULL,NULL),(25,11,'salto_horizontal','Salto horizontal',165.000,'cm',NULL,NULL),(26,12,'sprint_30m','Sprint 30 m',5.000,'s',NULL,NULL),(27,12,'salto_horizontal','Salto horizontal',162.000,'cm',NULL,NULL),(28,13,'sprint_30m','Sprint 30 m',4.850,'s',NULL,NULL),(29,13,'salto_horizontal','Salto horizontal',168.000,'cm',NULL,NULL),(30,14,'sprint_30m','Sprint 30 m',5.100,'s',NULL,NULL),(31,14,'salto_horizontal','Salto horizontal',165.000,'cm',NULL,NULL),(32,15,'sprint_30m','Sprint 30 m',4.950,'s',NULL,NULL),(33,15,'salto_horizontal','Salto horizontal',171.000,'cm',NULL,NULL),(34,16,'sprint_30m','Sprint 30 m',4.900,'s',NULL,NULL),(35,16,'salto_horizontal','Salto horizontal',168.000,'cm',NULL,NULL),(36,17,'sprint_30m','Sprint 30 m',4.750,'s',NULL,NULL),(37,17,'salto_horizontal','Salto horizontal',174.000,'cm',NULL,NULL),(38,18,'sprint_30m','Sprint 30 m',5.000,'s',NULL,NULL),(39,18,'salto_horizontal','Salto horizontal',171.000,'cm',NULL,NULL),(40,19,'sprint_30m','Sprint 30 m',4.850,'s',NULL,NULL),(41,19,'salto_horizontal','Salto horizontal',177.000,'cm',NULL,NULL),(42,20,'sprint_30m','Sprint 30 m',5.100,'s',NULL,NULL),(43,20,'salto_horizontal','Salto horizontal',174.000,'cm',NULL,NULL),(44,21,'sprint_30m','Sprint 30 m',4.950,'s',NULL,NULL),(45,21,'salto_horizontal','Salto horizontal',180.000,'cm',NULL,NULL),(46,22,'sprint_30m','Sprint 30 m',4.900,'s',NULL,NULL),(47,22,'salto_horizontal','Salto horizontal',177.000,'cm',NULL,NULL),(48,23,'sprint_30m','Sprint 30 m',4.750,'s',NULL,NULL),(49,23,'salto_horizontal','Salto horizontal',183.000,'cm',NULL,NULL),(50,24,'sprint_30m','Sprint 30 m',5.000,'s',NULL,NULL),(51,24,'salto_horizontal','Salto horizontal',180.000,'cm',NULL,NULL),(52,25,'sprint_30m','Sprint 30 m',4.850,'s',NULL,NULL),(53,25,'salto_horizontal','Salto horizontal',186.000,'cm',NULL,NULL),(54,26,'sprint_30m','Sprint 30 m',5.100,'s',NULL,NULL),(55,26,'salto_horizontal','Salto horizontal',183.000,'cm',NULL,NULL),(56,27,'sprint_30m','Sprint 30 m',4.950,'s',NULL,NULL),(57,27,'salto_horizontal','Salto horizontal',189.000,'cm',NULL,NULL);
/*!40000 ALTER TABLE `physical_measurements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_attendance`
--

DROP TABLE IF EXISTS `player_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `training_id` bigint unsigned DEFAULT NULL,
  `match_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_attendance_player_id_training_id_unique` (`player_id`,`training_id`),
  UNIQUE KEY `player_attendance_player_id_match_id_unique` (`player_id`,`match_id`),
  KEY `player_attendance_training_id_foreign` (`training_id`),
  KEY `player_attendance_match_id_foreign` (`match_id`),
  KEY `player_attendance_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `player_attendance_match_id_foreign` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  CONSTRAINT `player_attendance_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  CONSTRAINT `player_attendance_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `club_users` (`id`),
  CONSTRAINT `player_attendance_training_id_foreign` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`),
  CONSTRAINT `player_attendance_event_check` CHECK (((`training_id` is null) <> (`match_id` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=553 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_attendance`
--

LOCK TABLES `player_attendance` WRITE;
/*!40000 ALTER TABLE `player_attendance` DISABLE KEYS */;
INSERT INTO `player_attendance` VALUES (1,1,1,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,NULL,2,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,2,1,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,2,NULL,2,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,3,1,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,3,NULL,2,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,4,1,NULL,'excused','Certificado de la escuela.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,4,NULL,2,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,5,1,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,5,NULL,2,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,6,1,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,6,NULL,2,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,1,5,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(14,2,5,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(15,3,5,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(16,4,5,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(17,5,5,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(18,6,5,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(19,1,6,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(20,2,6,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(21,3,6,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(22,4,6,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(23,5,6,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(24,6,6,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(25,7,7,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(26,8,7,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(27,9,7,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(28,10,7,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(29,11,7,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(30,12,7,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(31,7,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(32,8,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(33,9,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(34,10,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(35,11,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(36,12,8,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(37,13,9,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(38,14,9,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(39,15,9,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(40,16,9,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(41,17,9,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(42,18,9,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(43,13,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(44,14,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(45,15,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(46,16,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(47,17,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(48,18,10,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(49,1,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(50,2,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(51,3,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(52,4,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(53,5,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(54,6,11,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(55,1,12,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(56,2,12,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(57,3,12,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(58,4,12,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(59,5,12,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(60,6,12,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(61,7,13,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(62,8,13,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(63,9,13,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(64,10,13,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(65,11,13,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(66,12,13,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(67,7,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(68,8,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(69,9,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(70,10,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(71,11,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(72,12,14,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(73,13,15,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(74,14,15,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(75,15,15,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(76,16,15,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(77,17,15,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(78,18,15,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(79,13,16,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(80,14,16,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(81,15,16,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(82,16,16,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(83,17,16,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(84,18,16,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(85,1,17,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(86,2,17,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(87,3,17,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(88,4,17,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(89,5,17,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(90,6,17,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(91,1,18,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(92,2,18,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(93,3,18,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(94,4,18,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(95,5,18,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(96,6,18,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(97,7,19,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(98,8,19,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(99,9,19,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(100,10,19,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(101,11,19,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(102,12,19,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(103,7,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(104,8,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(105,9,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(106,10,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(107,11,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(108,12,20,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(109,13,21,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(110,14,21,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(111,15,21,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(112,16,21,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(113,17,21,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(114,18,21,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(115,13,22,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(116,14,22,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(117,15,22,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(118,16,22,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(119,17,22,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(120,18,22,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(121,1,23,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(122,2,23,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(123,3,23,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(124,4,23,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(125,5,23,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(126,6,23,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(127,1,24,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(128,2,24,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(129,3,24,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(130,4,24,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(131,5,24,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(132,6,24,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(133,7,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(134,8,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(135,9,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(136,10,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(137,11,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(138,12,25,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(139,7,26,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(140,8,26,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(141,9,26,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(142,10,26,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(143,11,26,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(144,12,26,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(145,13,27,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(146,14,27,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(147,15,27,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(148,16,27,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(149,17,27,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(150,18,27,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(151,13,28,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(152,14,28,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(153,15,28,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(154,16,28,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(155,17,28,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(156,18,28,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(157,1,29,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(158,2,29,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(159,3,29,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(160,4,29,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(161,5,29,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(162,6,29,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(163,1,30,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(164,2,30,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(165,3,30,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(166,4,30,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(167,5,30,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(168,6,30,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(169,7,31,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(170,8,31,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(171,9,31,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(172,10,31,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(173,11,31,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(174,12,31,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(175,7,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(176,8,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(177,9,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(178,10,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(179,11,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(180,12,32,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(181,13,33,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(182,14,33,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(183,15,33,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(184,16,33,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(185,17,33,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(186,18,33,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(187,13,34,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(188,14,34,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(189,15,34,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(190,16,34,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(191,17,34,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(192,18,34,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(193,1,35,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(194,2,35,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(195,3,35,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(196,4,35,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(197,5,35,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(198,6,35,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(199,1,36,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(200,2,36,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(201,3,36,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(202,4,36,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(203,5,36,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(204,6,36,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(205,7,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(206,8,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(207,9,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(208,10,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(209,11,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(210,12,37,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(211,7,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(212,8,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(213,9,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(214,10,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(215,11,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(216,12,38,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(217,13,39,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(218,14,39,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(219,15,39,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(220,16,39,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(221,17,39,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(222,18,39,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(223,13,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(224,14,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(225,15,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(226,16,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(227,17,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(228,18,40,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(229,1,41,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(230,2,41,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(231,3,41,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(232,4,41,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(233,5,41,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(234,6,41,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(235,1,42,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(236,2,42,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(237,3,42,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(238,4,42,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(239,5,42,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(240,6,42,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(241,7,43,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(242,8,43,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(243,9,43,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(244,10,43,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(245,11,43,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(246,12,43,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(247,7,44,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(248,8,44,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(249,9,44,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(250,10,44,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(251,11,44,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(252,12,44,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(253,13,45,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(254,14,45,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(255,15,45,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(256,16,45,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(257,17,45,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(258,18,45,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(259,13,46,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(260,14,46,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(261,15,46,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(262,16,46,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(263,17,46,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(264,18,46,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(265,1,47,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(266,2,47,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(267,3,47,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(268,4,47,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(269,5,47,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(270,6,47,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(271,1,48,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(272,2,48,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(273,3,48,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(274,4,48,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(275,5,48,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(276,6,48,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(277,7,49,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(278,8,49,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(279,9,49,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(280,10,49,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(281,11,49,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(282,12,49,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(283,7,50,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(284,8,50,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(285,9,50,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(286,10,50,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(287,11,50,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(288,12,50,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(289,13,51,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(290,14,51,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(291,15,51,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(292,16,51,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(293,17,51,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(294,18,51,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(295,13,52,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(296,14,52,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(297,15,52,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(298,16,52,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(299,17,52,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(300,18,52,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(301,1,53,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(302,2,53,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(303,3,53,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(304,4,53,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(305,5,53,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(306,6,53,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(307,1,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(308,2,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(309,3,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(310,4,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(311,5,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(312,6,54,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(313,7,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(314,8,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(315,9,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(316,10,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(317,11,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(318,12,55,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(319,7,56,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(320,8,56,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(321,9,56,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(322,10,56,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(323,11,56,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(324,12,56,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(325,13,57,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(326,14,57,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(327,15,57,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(328,16,57,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(329,17,57,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(330,18,57,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(331,13,58,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(332,14,58,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(333,15,58,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(334,16,58,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(335,17,58,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(336,18,58,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(337,1,59,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(338,2,59,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(339,3,59,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(340,4,59,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(341,5,59,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(342,6,59,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(343,1,60,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(344,2,60,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(345,3,60,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(346,4,60,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(347,5,60,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(348,6,60,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(349,7,61,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(350,8,61,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(351,9,61,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(352,10,61,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(353,11,61,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(354,12,61,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(355,7,62,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(356,8,62,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(357,9,62,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(358,10,62,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(359,11,62,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(360,12,62,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(361,13,63,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(362,14,63,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(363,15,63,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(364,16,63,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(365,17,63,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(366,18,63,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(367,13,64,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(368,14,64,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(369,15,64,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(370,16,64,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(371,17,64,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(372,18,64,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(373,1,65,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(374,2,65,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(375,3,65,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(376,4,65,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(377,5,65,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(378,6,65,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(379,1,66,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(380,2,66,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(381,3,66,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(382,4,66,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(383,5,66,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(384,6,66,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(385,7,67,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(386,8,67,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(387,9,67,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(388,10,67,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(389,11,67,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(390,12,67,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(391,7,68,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(392,8,68,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(393,9,68,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(394,10,68,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(395,11,68,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(396,12,68,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(397,13,69,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(398,14,69,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(399,15,69,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(400,16,69,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(401,17,69,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(402,18,69,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(403,13,70,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(404,14,70,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(405,15,70,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(406,16,70,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(407,17,70,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(408,18,70,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(409,1,71,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(410,2,71,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(411,3,71,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(412,4,71,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(413,5,71,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(414,6,71,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(415,1,72,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(416,2,72,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(417,3,72,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(418,4,72,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(419,5,72,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(420,6,72,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(421,7,73,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(422,8,73,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(423,9,73,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(424,10,73,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(425,11,73,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(426,12,73,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(427,7,74,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(428,8,74,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(429,9,74,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(430,10,74,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(431,11,74,NULL,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(432,12,74,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(433,13,75,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(434,14,75,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(435,15,75,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(436,16,75,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(437,17,75,NULL,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(438,18,75,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(439,13,76,NULL,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(440,14,76,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(441,15,76,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(442,16,76,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(443,17,76,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(444,18,76,NULL,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(445,1,NULL,6,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(446,2,NULL,6,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(447,3,NULL,6,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(448,4,NULL,6,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(449,5,NULL,6,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(450,6,NULL,6,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(451,1,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(452,2,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(453,3,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(454,4,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(455,5,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(456,6,NULL,7,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(457,1,NULL,8,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(458,2,NULL,8,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(459,3,NULL,8,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(460,4,NULL,8,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(461,5,NULL,8,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(462,6,NULL,8,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(463,1,NULL,9,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(464,2,NULL,9,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(465,3,NULL,9,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(466,4,NULL,9,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(467,5,NULL,9,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(468,6,NULL,9,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(469,1,NULL,10,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(470,2,NULL,10,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(471,3,NULL,10,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(472,4,NULL,10,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(473,5,NULL,10,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(474,6,NULL,10,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(475,1,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(476,2,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(477,3,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(478,4,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(479,5,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(480,6,NULL,11,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(481,7,NULL,12,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(482,8,NULL,12,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(483,9,NULL,12,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(484,10,NULL,12,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(485,11,NULL,12,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(486,12,NULL,12,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(487,7,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(488,8,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(489,9,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(490,10,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(491,11,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(492,12,NULL,13,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(493,7,NULL,14,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(494,8,NULL,14,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(495,9,NULL,14,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(496,10,NULL,14,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(497,11,NULL,14,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(498,12,NULL,14,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(499,7,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(500,8,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(501,9,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(502,10,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(503,11,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(504,12,NULL,15,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(505,7,NULL,16,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(506,8,NULL,16,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(507,9,NULL,16,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(508,10,NULL,16,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(509,11,NULL,16,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(510,12,NULL,16,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(511,7,NULL,17,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(512,8,NULL,17,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(513,9,NULL,17,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(514,10,NULL,17,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(515,11,NULL,17,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(516,12,NULL,17,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(517,13,NULL,18,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(518,14,NULL,18,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(519,15,NULL,18,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(520,16,NULL,18,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(521,17,NULL,18,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(522,18,NULL,18,'absent',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(523,13,NULL,19,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(524,14,NULL,19,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(525,15,NULL,19,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(526,16,NULL,19,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(527,17,NULL,19,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(528,18,NULL,19,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(529,13,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(530,14,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(531,15,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(532,16,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(533,17,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(534,18,NULL,20,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(535,13,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(536,14,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(537,15,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(538,16,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(539,17,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(540,18,NULL,21,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(541,13,NULL,22,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(542,14,NULL,22,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(543,15,NULL,22,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(544,16,NULL,22,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(545,17,NULL,22,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(546,18,NULL,22,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(547,13,NULL,23,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(548,14,NULL,23,'late',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(549,15,NULL,23,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(550,16,NULL,23,'excused','Avisó por WhatsApp.',4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(551,17,NULL,23,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(552,18,NULL,23,'present',NULL,4,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `player_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_category_memberships`
--

DROP TABLE IF EXISTS `player_category_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_category_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `shirt_number` smallint DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_category_memberships_category_id_foreign` (`category_id`),
  KEY `player_category_memberships_player_id_category_id_status_index` (`player_id`,`category_id`,`status`),
  CONSTRAINT `player_category_memberships_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `player_category_memberships_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_category_memberships`
--

LOCK TABLES `player_category_memberships` WRITE;
/*!40000 ALTER TABLE `player_category_memberships` DISABLE KEYS */;
INSERT INTO `player_category_memberships` VALUES (1,1,1,'2026-03-01',NULL,1,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,2,1,'2026-03-01',NULL,2,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,3,1,'2026-03-01',NULL,3,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,4,1,'2026-03-01',NULL,4,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,5,1,'2026-03-01',NULL,5,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,6,1,'2026-03-01',NULL,6,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,7,2,'2026-03-01',NULL,1,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,8,2,'2026-03-01',NULL,2,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,9,2,'2026-03-01',NULL,3,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,10,2,'2026-03-01',NULL,4,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,11,2,'2026-03-01',NULL,5,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,12,2,'2026-03-01',NULL,6,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,13,3,'2026-03-01',NULL,1,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(14,14,3,'2026-03-01',NULL,2,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(15,15,3,'2026-03-01',NULL,3,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(16,16,3,'2026-03-01',NULL,4,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(17,17,3,'2026-03-01',NULL,5,'active','2026-11-04 04:55:57','2026-11-04 04:55:57'),(18,18,3,'2026-03-01',NULL,6,'active','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `player_category_memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_contacts`
--

DROP TABLE IF EXISTS `player_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_contacts_player_id_is_primary_index` (`player_id`,`is_primary`),
  CONSTRAINT `player_contacts_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_contacts`
--

LOCK TABLES `player_contacts` WRITE;
/*!40000 ALTER TABLE `player_contacts` DISABLE KEYS */;
INSERT INTO `player_contacts` VALUES (1,1,'Familiar de Tomás','father','+54 11 5555-1000','tomás.acosta.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,'Abuela de Tomás','guardian',NULL,NULL,0,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,2,'Familiar de Benjamín','mother','+54 11 5555-1001','benjamín.ríos.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,3,'Familiar de Lautaro','father','+54 11 5555-1002','lautaro.mendoza.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,4,'Familiar de Felipe','mother','+54 11 5555-1003','felipe.suárez.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,5,'Familiar de Thiago','father','+54 11 5555-1004','thiago.villalba.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,5,'Abuela de Thiago','guardian',NULL,NULL,0,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,6,'Familiar de Santino','mother','+54 11 5555-1005','santino.correa.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,7,'Familiar de Lucía','father','+54 11 5555-1006','lucía.gómez.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,8,'Familiar de Valentina','mother','+54 11 5555-1007','valentina.pereyra.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,9,'Familiar de Julieta','father','+54 11 5555-1008','julieta.navarro.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,9,'Abuela de Julieta','guardian',NULL,NULL,0,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,10,'Familiar de Catalina','mother','+54 11 5555-1009','catalina.ibarra.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(14,11,'Familiar de Mía','father','+54 11 5555-1010','mía.domínguez.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(15,12,'Familiar de Emma','mother','+54 11 5555-1011','emma.quiroga.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(16,13,'Familiar de Matías','father','+54 11 5555-1012','matías.ortega.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(17,13,'Abuela de Matías','guardian',NULL,NULL,0,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(18,14,'Familiar de Joaquín','mother','+54 11 5555-1013','joaquín.sosa.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(19,15,'Familiar de Facundo','father','+54 11 5555-1014','facundo.medina.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(20,16,'Familiar de Agustín','mother','+54 11 5555-1015','agustín.luna.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(21,17,'Familiar de Bruno','father','+54 11 5555-1016','bruno.castillo.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(22,17,'Abuela de Bruno','guardian',NULL,NULL,0,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(23,18,'Familiar de Ignacio','mother','+54 11 5555-1017','ignacio.vera.familia@example.com',1,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `player_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_positions`
--

DROP TABLE IF EXISTS `player_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `position_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_positions_player_id_position_id_unique` (`player_id`,`position_id`),
  KEY `player_positions_position_id_foreign` (`position_id`),
  CONSTRAINT `player_positions_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_positions_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_positions`
--

LOCK TABLES `player_positions` WRITE;
/*!40000 ALTER TABLE `player_positions` DISABLE KEYS */;
INSERT INTO `player_positions` VALUES (1,1,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,2,2,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,3,3,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,4,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,5,3,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,6,2,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,7,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,8,3,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,9,2,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,10,1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,11,3,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,12,4,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,13,5,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(14,14,6,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(15,15,7,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(16,16,9,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(17,17,5,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(18,18,6,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `player_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_requirements`
--

DROP TABLE IF EXISTS `player_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `player_id` bigint unsigned NOT NULL,
  `competition_requirement_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_id` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_requirements_player_id_competition_requirement_id_unique` (`player_id`,`competition_requirement_id`),
  KEY `player_requirements_competition_requirement_id_foreign` (`competition_requirement_id`),
  KEY `player_requirements_document_id_foreign` (`document_id`),
  KEY `player_requirements_reviewed_by_foreign` (`reviewed_by`),
  KEY `player_requirements_status_expires_at_index` (`status`,`expires_at`),
  CONSTRAINT `player_requirements_competition_requirement_id_foreign` FOREIGN KEY (`competition_requirement_id`) REFERENCES `competition_requirements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_requirements_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_requirements_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `player_requirements_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `club_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_requirements`
--

LOCK TABLES `player_requirements` WRITE;
/*!40000 ALTER TABLE `player_requirements` DISABLE KEYS */;
INSERT INTO `player_requirements` VALUES (1,1,1,'approved',1,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,2,1,'approved',2,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,2,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,2,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,2,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,3,1,'approved',3,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,3,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,3,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,3,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,4,1,'pending',NULL,NULL,'2027-07-04',NULL,NULL,NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(14,4,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(15,4,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(16,4,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(17,5,1,'approved',5,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(18,5,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(19,5,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(20,5,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(21,6,1,'approved',6,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(22,6,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(23,6,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(24,6,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(25,7,1,'approved',7,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(26,7,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(27,7,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(28,7,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(29,8,1,'approved',8,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(30,8,2,'rejected',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57','La firma no coincide con el DNI. Volver a presentarla.','2026-11-04 04:55:57','2026-11-04 04:55:57'),(31,8,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(32,8,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(33,9,1,'approved',9,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(34,9,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(35,9,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(36,9,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(37,10,1,'approved',10,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(38,10,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(39,10,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(40,10,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(41,11,1,'expired',11,'2026-10-15 04:55:57','2026-10-04',NULL,NULL,NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(42,11,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(43,11,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(44,11,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(45,12,1,'approved',12,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(46,12,2,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(47,12,3,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(48,12,4,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(49,13,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(50,13,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(51,14,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(52,14,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(53,15,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(54,15,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(55,16,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(56,16,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(57,17,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(58,17,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(59,18,5,'approved',NULL,'2026-10-15 04:55:57','2027-07-04',2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(60,18,6,'approved',NULL,'2026-10-15 04:55:57',NULL,2,'2026-10-20 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `player_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `players` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dominant_side` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `players_last_name_first_name_index` (`last_name`,`first_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (1,'Tomás','Acosta','2013-04-12','male','40000000','FED-2026000','Arquero','right','active','Llegó de la escuela de la zona en 2025.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Benjamín','Ríos','2013-07-02','male','40000137','FED-2026001','Defensor','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,'Lautaro','Mendoza','2013-01-19','male','40000274','FED-2026002','Mediocampista','left','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,'Felipe','Suárez','2014-03-30','male','40000411','FED-2026003','Delantero','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(5,'Thiago','Villalba','2013-09-08','male','40000548','FED-2026004','Mediocampista','both','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(6,'Santino','Correa','2014-05-21','male','40000685','FED-2026005','Defensor','right','active','Llegó de la escuela de la zona en 2025.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(7,'Lucía','Gómez','2011-06-14','female','40000822','FED-2026006','Delantera','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(8,'Valentina','Pereyra','2011-02-03','female','40000959','FED-2026007','Mediocampista','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(9,'Julieta','Navarro','2012-08-27','female','40001096','FED-2026008','Defensora','left','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(10,'Catalina','Ibarra','2011-11-11','female','40001233','FED-2026009','Arquera','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(11,'Mía','Domínguez','2012-04-05','female','40001370','FED-2026010','Mediocampista','right','active','Llegó de la escuela de la zona en 2025.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(12,'Emma','Quiroga','2011-09-18','female','40001507','FED-2026011','Delantera','left','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(13,'Matías','Ortega','2009-10-01','male','40001644','FED-2026012','Base','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(14,'Joaquín','Sosa','2010-03-15','male','40001781','FED-2026013','Escolta','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(15,'Facundo','Medina','2009-12-22','male','40001918','FED-2026014','Alero','left','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(16,'Agustín','Luna','2010-07-07','male','40002055','FED-2026015','Pivot','right','active','Llegó de la escuela de la zona en 2025.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(17,'Bruno','Castillo','2009-05-30','male','40002192','FED-2026016','Base','right','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(18,'Ignacio','Vera','2010-01-25','male','40002329','FED-2026017','Escolta','both','active',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sport_id` bigint unsigned NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `positions_sport_id_name_unique` (`sport_id`,`name`),
  KEY `positions_sport_id_is_active_index` (`sport_id`,`is_active`),
  CONSTRAINT `positions_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,1,'Arquero',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,1,'Defensor',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,1,'Mediocampista',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,1,'Delantero',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,2,'Base',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,2,'Escolta',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(7,2,'Alero',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(8,2,'Ala-pívot',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(9,2,'Pivot',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(10,3,'Arquera',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(11,3,'Defensora',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(12,3,'Volante',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(13,3,'Delantera',1,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_requests`
--

DROP TABLE IF EXISTS `repair_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` bigint unsigned NOT NULL,
  `component_id` bigint unsigned DEFAULT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reported_at` datetime NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `repair_requests_facility_id_foreign` (`facility_id`),
  KEY `repair_requests_component_id_foreign` (`component_id`),
  KEY `repair_requests_reported_by_foreign` (`reported_by`),
  KEY `repair_requests_assigned_to_foreign` (`assigned_to`),
  KEY `repair_requests_status_priority_index` (`status`,`priority`),
  CONSTRAINT `repair_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `club_users` (`id`),
  CONSTRAINT `repair_requests_component_id_foreign` FOREIGN KEY (`component_id`) REFERENCES `facility_components` (`id`),
  CONSTRAINT `repair_requests_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `repair_requests_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `club_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_requests`
--

LOCK TABLES `repair_requests` WRITE;
/*!40000 ALTER TABLE `repair_requests` DISABLE KEYS */;
INSERT INTO `repair_requests` VALUES (1,1,1,2,7,'Red del arco norte rota','Desgarro de unos 40 cm en la parte baja de la red.','high','in_progress','2026-11-01 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,2,3,2,7,'Ducha 3 sin agua caliente','La canilla de la ducha 3 no sale agua caliente.','normal','resolved','2026-10-27 04:55:57','2026-10-29 04:55:57','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,1,2,7,NULL,'Revisar foco de la torre de luz','Uno de los focos parpadea.','low','open','2026-11-03 04:55:57',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `repair_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `scope` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,'all'),(1,2,'all'),(1,3,'all'),(1,4,'all'),(1,5,'all'),(1,6,'all'),(1,7,'all'),(1,8,'all'),(1,9,'all'),(1,10,'all'),(1,11,'all'),(1,12,'all'),(1,13,'all'),(1,14,'all'),(1,15,'all'),(1,16,'all'),(1,17,'all'),(1,18,'all'),(1,19,'all'),(1,20,'all'),(1,21,'all'),(1,22,'all'),(1,23,'all'),(1,24,'all'),(1,25,'all'),(1,26,'all'),(1,27,'all'),(1,28,'all'),(1,29,'all'),(1,30,'all'),(1,31,'all'),(1,32,'all'),(1,33,'all'),(1,34,'all'),(1,35,'all'),(1,36,'all'),(1,37,'all'),(1,38,'all'),(1,39,'all'),(1,40,'all'),(1,41,'all'),(1,42,'all'),(1,43,'all'),(1,44,'all'),(1,45,'all'),(1,46,'all'),(1,47,'all'),(1,48,'all'),(1,49,'all'),(1,50,'all'),(1,51,'all'),(1,52,'all'),(1,53,'all'),(1,54,'all'),(1,55,'all'),(1,56,'all'),(1,57,'all'),(1,58,'all'),(1,59,'all'),(1,60,'all'),(1,61,'all'),(1,62,'all'),(1,63,'all'),(1,64,'all'),(1,65,'all'),(1,66,'all'),(1,67,'all'),(1,68,'all'),(1,69,'all'),(1,70,'all'),(1,71,'all'),(1,72,'all'),(1,73,'all'),(1,74,'all'),(1,75,'all'),(1,76,'all'),(1,77,'all'),(1,78,'all'),(1,79,'all'),(1,80,'all'),(1,81,'all'),(1,82,'all'),(1,83,'all'),(1,84,'all'),(1,85,'all'),(1,86,'all'),(1,87,'all'),(1,88,'all'),(1,89,'all'),(1,90,'all'),(1,91,'all'),(1,92,'all'),(1,93,'all'),(1,94,'all'),(1,95,'all'),(1,96,'all'),(1,97,'all'),(1,98,'all'),(1,99,'all'),(1,100,'all'),(1,101,'all'),(1,102,'all'),(1,103,'all'),(1,104,'all'),(1,105,'all'),(1,106,'all'),(1,107,'all'),(1,108,'all'),(1,109,'all'),(1,110,'all'),(1,111,'all'),(1,112,'all'),(1,113,'all'),(1,114,'all'),(2,39,'sport'),(2,91,'all'),(2,95,'sport'),(3,1,'own'),(3,10,'own'),(3,12,'own'),(3,13,'own'),(3,15,'own'),(3,20,'own'),(3,21,'own'),(3,22,'own'),(3,24,'own'),(3,25,'own'),(3,26,'own'),(3,28,'own'),(3,29,'own'),(3,30,'own'),(3,31,'own'),(3,32,'own'),(3,33,'own'),(3,37,'all'),(3,39,'own'),(3,43,'own'),(3,44,'own'),(3,46,'all'),(3,50,'own'),(3,51,'own'),(3,52,'own'),(3,67,'own'),(3,68,'own'),(3,69,'own'),(3,73,'all'),(3,77,'all'),(3,78,'all'),(3,95,'own'),(3,99,'all'),(3,113,'own'),(3,114,'own'),(4,1,'own'),(4,15,'own'),(4,20,'own'),(4,24,'own'),(4,28,'own'),(4,30,'own'),(4,33,'own'),(4,34,'own'),(4,35,'own'),(4,36,'own'),(4,37,'all'),(4,39,'own'),(4,43,'own'),(4,67,'own'),(4,68,'own'),(4,69,'own'),(4,73,'all'),(4,77,'all'),(4,78,'all'),(4,95,'own'),(4,99,'all'),(5,20,'all'),(5,24,'all'),(5,30,'all'),(5,46,'all'),(5,47,'all'),(5,48,'all'),(5,49,'all'),(5,50,'all'),(5,51,'all'),(5,52,'all'),(5,55,'all'),(5,56,'all'),(5,57,'all'),(5,58,'all'),(5,59,'all'),(5,60,'all'),(5,61,'all'),(5,62,'all'),(5,63,'all'),(5,64,'all'),(5,65,'all'),(5,66,'all'),(5,67,'all'),(5,68,'all'),(5,69,'all'),(5,72,'all'),(5,73,'all'),(5,77,'all'),(5,78,'all'),(5,99,'all'),(6,20,'all'),(6,24,'all'),(6,73,'all'),(6,74,'all'),(6,75,'all'),(6,76,'all'),(6,77,'all'),(6,78,'all'),(6,79,'all'),(6,80,'all'),(6,81,'all'),(6,82,'all'),(6,83,'all'),(6,84,'all'),(6,85,'all'),(6,99,'all');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrador del club','Comisión directiva o gerente. Tiene todos los permisos.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,'sport_coordinator','Coordinador de deporte','Consulta temporadas, categorías y competencias de sus deportes asignados.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,'coach','Profe','Entrenador de una o más categorías.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(4,'physical_trainer','Preparador físico','Evaluaciones físicas de sus categorías.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(5,'inventory_manager','Utilero / Encargado','Materiales, indumentaria y entregas.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(6,'maintenance_manager','Mantenimiento','Canchas e instalaciones.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `season_objectives`
--

DROP TABLE IF EXISTS `season_objectives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `season_objectives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `season_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `target_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `season_objectives_category_id_foreign` (`category_id`),
  KEY `season_objectives_season_id_category_id_index` (`season_id`,`category_id`),
  CONSTRAINT `season_objectives_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `season_objectives_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `season_objectives`
--

LOCK TABLES `season_objectives` WRITE;
/*!40000 ALTER TABLE `season_objectives` DISABLE KEYS */;
INSERT INTO `season_objectives` VALUES (1,2,NULL,'Llegar a la final de la liga','Clasificar entre los cuatro primeros.',4.00,2.00,'posición','in_progress','2026-12-01','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,2,2,'Asistencia promedio del 85%',NULL,85.00,88.00,'%','achieved','2026-11-15','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,2,3,'Ganar 10 partidos',NULL,10.00,4.00,'partidos','pending','2026-12-10','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `season_objectives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `seasons_dates_check` CHECK ((`end_date` >= `start_date`))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons`
--

LOCK TABLES `seasons` WRITE;
/*!40000 ALTER TABLE `seasons` DISABLE KEYS */;
INSERT INTO `seasons` VALUES (1,'2025','2025-03-01','2025-12-15','closed','2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,'2026 - segunda mitad','2026-03-01','2026-12-15','active','2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `seasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sports`
--

DROP TABLE IF EXISTS `sports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sports_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sports`
--

LOCK TABLES `sports` WRITE;
/*!40000 ALTER TABLE `sports` DISABLE KEYS */;
INSERT INTO `sports` VALUES (1,'Fútbol',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(2,'Básquet',1,'2026-11-04 04:55:57','2026-11-04 04:55:57'),(3,'Hockey',1,'2026-11-04 04:55:57','2026-11-04 04:55:57');
/*!40000 ALTER TABLE `sports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `storage_locations`
--

DROP TABLE IF EXISTS `storage_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `storage_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `storage_locations_location_code_unique` (`location_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `storage_locations`
--

LOCK TABLES `storage_locations` WRITE;
/*!40000 ALTER TABLE `storage_locations` DISABLE KEYS */;
INSERT INTO `storage_locations` VALUES (1,'Depósito principal','DEP-01','Estante A, sede principal.',1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Vestuario visitante','VES-02',NULL,1,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `storage_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainings`
--

DROP TABLE IF EXISTS `trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `coach_id` bigint unsigned DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `location` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objectives` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainings_coach_id_foreign` (`coach_id`),
  KEY `trainings_category_id_starts_at_index` (`category_id`,`starts_at`),
  CONSTRAINT `trainings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `trainings_coach_id_foreign` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`),
  CONSTRAINT `trainings_dates_check` CHECK ((`ends_at` > `starts_at`))
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
INSERT INTO `trainings` VALUES (1,1,1,'2026-11-01 18:00:00','2026-11-01 19:30:00','Cancha Principal','Circulación y pressing alto.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,2,1,'2026-11-06 17:30:00','2026-11-06 19:00:00','Parque Deportivo Norte','Definición en el área.','Traer botines de repuesto.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(3,3,3,'2026-11-05 19:00:00','2026-11-05 20:30:00','Gimnasio Central',NULL,NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(4,1,2,'2026-10-25 18:00:00','2026-10-25 19:00:00','Cancha Principal','Test de velocidad.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(5,1,1,'2026-08-11 18:00:00','2026-08-11 19:30:00','Cancha Principal','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(6,1,1,'2026-08-13 18:00:00','2026-08-13 19:30:00','Cancha Principal','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(7,2,1,'2026-08-11 18:00:00','2026-08-11 19:30:00','Parque Deportivo Norte','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(8,2,1,'2026-08-13 18:00:00','2026-08-13 19:30:00','Parque Deportivo Norte','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(9,3,3,'2026-08-10 19:00:00','2026-08-10 20:30:00','Gimnasio Central','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(10,3,3,'2026-08-12 19:00:00','2026-08-12 20:30:00','Gimnasio Central','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(11,1,1,'2026-08-18 18:00:00','2026-08-18 19:30:00','Cancha Principal','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(12,1,1,'2026-08-20 18:00:00','2026-08-20 19:30:00','Cancha Principal','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(13,2,1,'2026-08-18 18:00:00','2026-08-18 19:30:00','Parque Deportivo Norte','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(14,2,1,'2026-08-20 18:00:00','2026-08-20 19:30:00','Parque Deportivo Norte','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(15,3,3,'2026-08-17 19:00:00','2026-08-17 20:30:00','Gimnasio Central','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(16,3,3,'2026-08-19 19:00:00','2026-08-19 20:30:00','Gimnasio Central','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(17,1,1,'2026-08-25 18:00:00','2026-08-25 19:30:00','Cancha Principal','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(18,1,1,'2026-08-27 18:00:00','2026-08-27 19:30:00','Cancha Principal','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(19,2,1,'2026-08-25 18:00:00','2026-08-25 19:30:00','Parque Deportivo Norte','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(20,2,1,'2026-08-27 18:00:00','2026-08-27 19:30:00','Parque Deportivo Norte','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(21,3,3,'2026-08-24 19:00:00','2026-08-24 20:30:00','Gimnasio Central','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(22,3,3,'2026-08-26 19:00:00','2026-08-26 20:30:00','Gimnasio Central','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(23,1,1,'2026-09-01 18:00:00','2026-09-01 19:30:00','Cancha Principal','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(24,1,1,'2026-09-03 18:00:00','2026-09-03 19:30:00','Cancha Principal','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(25,2,1,'2026-09-01 18:00:00','2026-09-01 19:30:00','Parque Deportivo Norte','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(26,2,1,'2026-09-03 18:00:00','2026-09-03 19:30:00','Parque Deportivo Norte','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(27,3,3,'2026-08-31 19:00:00','2026-08-31 20:30:00','Gimnasio Central','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(28,3,3,'2026-09-02 19:00:00','2026-09-02 20:30:00','Gimnasio Central','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(29,1,1,'2026-09-08 18:00:00','2026-09-08 19:30:00','Cancha Principal','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(30,1,1,'2026-09-10 18:00:00','2026-09-10 19:30:00','Cancha Principal','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(31,2,1,'2026-09-08 18:00:00','2026-09-08 19:30:00','Parque Deportivo Norte','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(32,2,1,'2026-09-10 18:00:00','2026-09-10 19:30:00','Parque Deportivo Norte','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(33,3,3,'2026-09-07 19:00:00','2026-09-07 20:30:00','Gimnasio Central','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(34,3,3,'2026-09-09 19:00:00','2026-09-09 20:30:00','Gimnasio Central','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(35,1,1,'2026-09-15 18:00:00','2026-09-15 19:30:00','Cancha Principal','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(36,1,1,'2026-09-17 18:00:00','2026-09-17 19:30:00','Cancha Principal','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(37,2,1,'2026-09-15 18:00:00','2026-09-15 19:30:00','Parque Deportivo Norte','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(38,2,1,'2026-09-17 18:00:00','2026-09-17 19:30:00','Parque Deportivo Norte','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(39,3,3,'2026-09-14 19:00:00','2026-09-14 20:30:00','Gimnasio Central','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(40,3,3,'2026-09-16 19:00:00','2026-09-16 20:30:00','Gimnasio Central','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(41,1,1,'2026-09-22 18:00:00','2026-09-22 19:30:00','Cancha Principal','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(42,1,1,'2026-09-24 18:00:00','2026-09-24 19:30:00','Cancha Principal','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(43,2,1,'2026-09-22 18:00:00','2026-09-22 19:30:00','Parque Deportivo Norte','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(44,2,1,'2026-09-24 18:00:00','2026-09-24 19:30:00','Parque Deportivo Norte','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(45,3,3,'2026-09-21 19:00:00','2026-09-21 20:30:00','Gimnasio Central','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(46,3,3,'2026-09-23 19:00:00','2026-09-23 20:30:00','Gimnasio Central','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(47,1,1,'2026-09-29 18:00:00','2026-09-29 19:30:00','Cancha Principal','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(48,1,1,'2026-10-01 18:00:00','2026-10-01 19:30:00','Cancha Principal','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(49,2,1,'2026-09-29 18:00:00','2026-09-29 19:30:00','Parque Deportivo Norte','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(50,2,1,'2026-10-01 18:00:00','2026-10-01 19:30:00','Parque Deportivo Norte','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(51,3,3,'2026-09-28 19:00:00','2026-09-28 20:30:00','Gimnasio Central','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(52,3,3,'2026-09-30 19:00:00','2026-09-30 20:30:00','Gimnasio Central','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(53,1,1,'2026-10-06 18:00:00','2026-10-06 19:30:00','Cancha Principal','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(54,1,1,'2026-10-08 18:00:00','2026-10-08 19:30:00','Cancha Principal','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(55,2,1,'2026-10-06 18:00:00','2026-10-06 19:30:00','Parque Deportivo Norte','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(56,2,1,'2026-10-08 18:00:00','2026-10-08 19:30:00','Parque Deportivo Norte','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(57,3,3,'2026-10-05 19:00:00','2026-10-05 20:30:00','Gimnasio Central','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(58,3,3,'2026-10-07 19:00:00','2026-10-07 20:30:00','Gimnasio Central','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(59,1,1,'2026-10-13 18:00:00','2026-10-13 19:30:00','Cancha Principal','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(60,1,1,'2026-10-15 18:00:00','2026-10-15 19:30:00','Cancha Principal','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(61,2,1,'2026-10-13 18:00:00','2026-10-13 19:30:00','Parque Deportivo Norte','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(62,2,1,'2026-10-15 18:00:00','2026-10-15 19:30:00','Parque Deportivo Norte','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(63,3,3,'2026-10-12 19:00:00','2026-10-12 20:30:00','Gimnasio Central','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(64,3,3,'2026-10-14 19:00:00','2026-10-14 20:30:00','Gimnasio Central','Defensa en bloque.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(65,1,1,'2026-10-20 18:00:00','2026-10-20 19:30:00','Cancha Principal','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(66,1,1,'2026-10-22 18:00:00','2026-10-22 19:30:00','Cancha Principal','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(67,2,1,'2026-10-20 18:00:00','2026-10-20 19:30:00','Parque Deportivo Norte','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(68,2,1,'2026-10-22 18:00:00','2026-10-22 19:30:00','Parque Deportivo Norte','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(69,3,3,'2026-10-19 19:00:00','2026-10-19 20:30:00','Gimnasio Central','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(70,3,3,'2026-10-21 19:00:00','2026-10-21 20:30:00','Gimnasio Central','Pase y control orientado.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(71,1,1,'2026-10-27 18:00:00','2026-10-27 19:30:00','Cancha Principal','Transiciones rápidas.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(72,1,1,'2026-10-29 18:00:00','2026-10-29 19:30:00','Cancha Principal','Resistencia con pelota.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(73,2,1,'2026-10-27 18:00:00','2026-10-27 19:30:00','Parque Deportivo Norte','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(74,2,1,'2026-10-29 18:00:00','2026-10-29 19:30:00','Parque Deportivo Norte','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(75,3,3,'2026-10-26 19:00:00','2026-10-26 20:30:00','Gimnasio Central','Pelota parada.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(76,3,3,'2026-10-28 19:00:00','2026-10-28 20:30:00','Gimnasio Central','Juego reducido 4 vs 4.',NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `trainings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venues`
--

DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venues`
--

LOCK TABLES `venues` WRITE;
/*!40000 ALTER TABLE `venues` DISABLE KEYS */;
INSERT INTO `venues` VALUES (1,'Cancha Principal','Av. Libertador 1234, Tigre, Buenos Aires',-34.4226000,-58.5796000,1,'Cancha de césped sintético con iluminación.','2026-11-04 04:55:57','2026-11-04 04:55:57',NULL),(2,'Parque Deportivo Norte','Calle Los Ceibos 450, Benavídez, Buenos Aires',-34.4118000,-58.6358000,1,NULL,'2026-11-04 04:55:57','2026-11-04 04:55:57',NULL);
/*!40000 ALTER TABLE `venues` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-04  4:56:08
