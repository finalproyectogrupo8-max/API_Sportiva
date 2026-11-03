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
INSERT INTO `audit_logs` VALUES (1,1,'tenant.created','tenant',1,'{\"name\": {\"to\": \"Club Demo Sportiva\", \"from\": null}}','127.0.0.1','2026-10-30 21:32:49'),(2,1,'user.created','user',3,'{\"email\": {\"to\": \"admin.club@sportiva.com\", \"from\": null}}','127.0.0.1','2026-10-30 21:32:49'),(3,1,'membership.created','membership',1,'{\"roles\": {\"to\": [\"Administrador del club\"], \"from\": []}}','127.0.0.1','2026-10-30 21:32:49'),(4,1,'auth.password_reset_requested','auth',6,NULL,'127.0.0.1','2026-10-30 21:32:49');
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
INSERT INTO `cache` VALUES ('sportiva-cache-443af823d48014860df4d744fe9201b3','i:1;',1793741669),('sportiva-cache-443af823d48014860df4d744fe9201b3:timer','i:1793741669;',1793741669),('sportiva-cache-demo:club_slug','s:4:\"demo\";',1825277569),('sportiva-cache-demo:seeded_at','s:25:\"2026-11-03T21:32:49+00:00\";',1825277569);
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
INSERT INTO `cache_locks` VALUES ('sportiva-cache-demo:seed-lock','ov938AoU33Cu73uc',1793827969);
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
INSERT INTO `failed_jobs` VALUES (1,'4fb31b12-f61e-4288-8808-9894df59c55b','database','default','{\"uuid\":\"0949918e-b47b-48b4-8ee7-ccb6c1e0d49d\",\"displayName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":\"10,60\",\"timeout\":60,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\ProvisionTenant\\\":2:{s:8:\\\"tenantId\\\";i:1;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2036-11-03 21:32:49.889158\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1793741569,\"delay\":315619200}','Demo: el alta del club tardó más de lo esperado (dato de ejemplo, reintentable).\n','2026-11-03 21:32:49');
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
INSERT INTO `job_batches` VALUES ('b0d69712-ed41-454c-8f57-325e8ae148e8','demo:provision-club',1,0,0,'[]',NULL,NULL,1793741569,1793741569);
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
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"0949918e-b47b-48b4-8ee7-ccb6c1e0d49d\",\"displayName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"countCrashesAsExceptions\":false,\"backoff\":\"10,60\",\"timeout\":60,\"retryUntil\":null,\"deleteWhenMissingModels\":false,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProvisionTenant\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\ProvisionTenant\\\":2:{s:8:\\\"tenantId\\\";i:1;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2036-11-03 21:32:49.889158\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1793741569,\"delay\":315619200}',0,NULL,2109360769,1793741569);
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
INSERT INTO `newsletters` VALUES (1,'familia.perez@example.com','2026-11-03 21:32:49','2026-11-03 21:32:49'),(2,'socios.norte@example.com','2026-11-03 21:32:49','2026-11-03 21:32:49'),(3,'prensa.club@example.com','2026-11-03 21:32:49','2026-11-03 21:32:49'),(4,'entrenador.libre@example.com','2026-11-03 21:32:49','2026-11-03 21:32:49'),(5,'hincha.tigre@example.com','2026-11-03 21:32:49','2026-11-03 21:32:49');
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
INSERT INTO `password_reset_tokens` VALUES ('profe@sportiva.com','$2y$12$uvY2iqkjvwNAMPisyo6DTOlqaRJQLIfKesPgybN711ZlAFZqCOse2','2026-11-03 21:32:49');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',3,NULL,'demo-local','50a02fe9a1468173fdc8f9eee6b35aa5cef07d7f7cc6411df7aedf7ee9c67020','[\"tenant:select\",\"tenant:operate\"]',NULL,'2026-12-03 21:32:49','2026-11-03 21:32:49','2026-11-03 21:32:49'),(2,'App\\Models\\User',6,NULL,'demo-local','19db087f486e112b76f5136de41998f77006a2d0962ee9077a1b85348975e82d','[\"tenant:select\",\"tenant:operate\"]',NULL,'2026-12-03 21:32:49','2026-11-03 21:32:49','2026-11-03 21:32:49'),(4,'App\\Models\\User',3,1,'tenant:demo','4f92d1a6504048b15987bc30d507f658b65745f45af8a0003fa91b11d3f8ff49','[\"tenant:select\",\"tenant:operate\"]','2026-11-03 21:33:31','2026-11-04 05:33:30','2026-11-03 21:33:30','2026-11-03 21:33:31');
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
INSERT INTO `sessions` VALUES ('KFRk08vjGpYgABLtje8aB5209esMKzrbTSwJqQQN',3,'127.0.0.1','Demo seed','YTowOnt9',1793741569);
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
INSERT INTO `support_tickets` VALUES (1,'Vada Wuckert','orutherford@example.net',NULL,'Vel aut quibusdam eum nesciunt.','Velit alias vitae rerum itaque aut. Excepturi molestiae in rem porro omnis voluptatem ea. Et repellendus similique consequatur ut at explicabo. Quia est voluptas error natus sit maiores ut aliquam.\n\nLaborum officiis odit enim mollitia nobis eveniet expedita. Est qui saepe maiores. Sint doloribus est quidem facilis quae. Exercitationem accusantium nobis saepe minus laboriosam enim.','new',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(2,'Dayna Larkin','brain.effertz@example.net','Leuschke Inc','Beatae quos similique.','Quae et numquam dicta dolores nam. Maiores sit rem non sed ut. Eligendi impedit et veritatis beatae dicta reprehenderit. Aut quae qui assumenda sequi odit.\n\nQuis omnis ea similique fuga ullam odit. Vel voluptates nam adipisci recusandae. Saepe voluptatem distinctio excepturi sint sint occaecati eveniet. Quaerat omnis explicabo velit reiciendis.','new',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(3,'Belle Blick','adolfo28@example.org',NULL,'Error fuga perspiciatis voluptas tenetur earum.','Repellat vel animi quaerat suscipit voluptate quo asperiores. Assumenda nesciunt esse quo consequatur error quia. Sed dolores qui dolores id. Dolorum quod aut quia.\n\nVel eius nostrum quam dolores dicta. Perspiciatis aliquid maiores et. Quos necessitatibus dignissimos dolor fugit.','new',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(4,'Pat Lockman','oma11@example.net','Lakin Group','Eum vel eligendi.','Commodi porro vel minus. Non itaque maxime consequatur maiores et expedita recusandae. Sed est sit quis harum id sit.\n\nAperiam aperiam quis voluptates doloribus. Ut ex beatae qui quia enim incidunt. Doloribus incidunt dolores et fugit. Tempora ex sapiente consectetur mollitia rerum.','new',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(5,'Prof. Annamarie McDermott','bud34@example.com',NULL,'Non et sed quis.','Ut praesentium hic magnam et asperiores in. Recusandae non repellat a veritatis similique.\n\nQui deserunt quod est ipsum. Non aperiam alias omnis. Nisi minus harum voluptatem facere.','new',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(6,'Berneice Smitham','weldon.jast@example.org','Robel-Runolfsdottir','Nam rerum maiores aut et.','Quae aut aliquam eligendi eveniet et voluptas. Rerum ducimus mollitia voluptatibus sequi eius. Ut provident quo culpa sed et est.\n\nVoluptates ducimus sit eius enim et. Quae deserunt dolorem voluptatibus iste minus ut.','in_review',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(7,'Lorenza Mills II','ibogan@example.net','Erdman, King and Weber','Saepe fuga voluptatem dolorum.','Nam sed similique aspernatur eum velit. Excepturi sapiente aut assumenda ea quo sed. Reprehenderit inventore dolores dolore consectetur quaerat cumque. Laborum blanditiis distinctio aut id occaecati ut ex.\n\nInventore perspiciatis amet ratione esse reprehenderit esse ipsa. Debitis nam minus quos optio. In commodi eligendi perspiciatis qui nam labore. Assumenda aut perspiciatis voluptatem qui totam consequatur.','in_review',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(8,'Rosalee Hackett DDS','sbogan@example.org',NULL,'Earum in dolores assumenda.','Blanditiis cumque nobis voluptatem rerum non. Quos est fuga rerum cum reprehenderit. Nemo repudiandae quis minima culpa placeat ipsam deserunt.\n\nConsectetur voluptatem eum cum quae quis. Occaecati et voluptatum non voluptatibus asperiores qui. Porro non alias voluptas quia sapiente facilis rerum. Reiciendis molestiae voluptatem laudantium et eos non aut.','in_review',NULL,NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(9,'Filomena Price','walsh.raquel@example.net','Lesch Group','Rem voluptas est perspiciatis.','Cumque facere illo id quo cum explicabo qui. Voluptates iusto eveniet autem sunt. Numquam eaque dolorum odit eligendi. Et doloremque assumenda minus.\n\nAnimi dolor quia reiciendis non. Fugit nulla quia minima sequi nostrum. Eum nobis laboriosam perferendis nam non. Eum sit sint deleniti.','resolved','Dolores deserunt officiis doloremque dolor ratione.',1,'2026-11-03 21:32:19','2026-11-03 21:32:19','2026-11-03 21:32:19'),(10,'Mabel Jakubowski','jed.renner@example.org','Kreiger, Kemmer and Rau','Eligendi expedita omnis.','Quaerat incidunt laudantium perspiciatis voluptatibus. A et aut libero et. Libero ipsam distinctio sint molestiae sunt numquam mollitia doloribus.\n\nDolorem soluta iste praesentium. Blanditiis atque sed vel similique quas voluptatem.','resolved','Blanditiis et fuga praesentium et eius sapiente in.',1,'2026-11-03 21:32:19','2026-11-03 21:32:19','2026-11-03 21:32:19'),(11,'Tom Hane','branson38@example.net',NULL,'Praesentium explicabo inventore voluptatem.','Earum cupiditate ut inventore quidem repellat qui quia. Repellat voluptatem fugit hic repellat ut. Impedit explicabo beatae culpa repudiandae voluptatibus vero nobis.\n\nEt natus qui nulla ratione esse amet quia. Modi iste iusto dolor quia occaecati tempore molestias ut. Rem quia accusantium ratione est. A expedita necessitatibus perspiciatis voluptas excepturi eius voluptatem. Voluptatem quia id quam earum voluptates eaque minima.','resolved','Facilis sed esse sed suscipit sed non.',1,'2026-11-03 21:32:19','2026-11-03 21:32:19','2026-11-03 21:32:19'),(12,'Kaden Crooks','elenor36@example.org','Smitham Inc','Commodi vel ab rerum et nihil.','Aperiam voluptatum ut commodi ipsa vero et. Et eius corrupti cupiditate eveniet. Repudiandae sit nihil aperiam dicta quos quas quidem aut. Aut repellat placeat sunt velit quos.\n\nSint eum vero ab perspiciatis eum. Error et quis iste ad ipsa. Explicabo ipsum autem corporis ut recusandae.','resolved','Consectetur temporibus alias laudantium.',1,'2026-11-03 21:32:19','2026-11-03 21:32:19','2026-11-03 21:32:19'),(13,'Prof. Baron West','cummings.leon@example.org',NULL,'Optio sunt beatae et libero.','Corrupti necessitatibus sit esse quis. Doloribus et explicabo est nisi reiciendis tenetur maxime. Eos fugit nisi ipsa reprehenderit error illum aspernatur. Sapiente dignissimos perferendis deserunt excepturi distinctio.\n\nAut recusandae et voluptas velit ipsa. Reiciendis necessitatibus ea sunt ipsum. Id voluptatem necessitatibus recusandae quis eius.','discarded','Descartado por duplicado o spam.',NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19'),(14,'Prof. Webster Zulauf','miracle.abernathy@example.net',NULL,'Et est voluptas velit.','Eum necessitatibus itaque et. Omnis incidunt sed iure aliquid reiciendis. Eaque deleniti repudiandae magni aperiam quis. Consequatur neque voluptatem porro.\n\nPariatur ut quasi velit impedit dolor. Dolores quos assumenda ad fugiat quia voluptatum et. Qui laboriosam temporibus consequuntur qui eos corrupti. Eligendi unde repellat omnis laborum.','discarded','Descartado por duplicado o spam.',NULL,NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19');
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
INSERT INTO `tenant_memberships` VALUES (1,3,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,4,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,5,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,6,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,7,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,8,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,9,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `tenants` VALUES (1,'demo','Club Demo Sportiva','CDS','Tigre, Buenos Aires',1985,'#1d4ed8',NULL,'club_demo','active','2026-11-03 21:32:21','2026-11-03 21:32:45',NULL);
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
INSERT INTO `users` VALUES (1,'Admin User','admin@sportiva.com',NULL,'$2y$12$XXnBdJqQZqdeNq1aDjC/eeyzW3na7b41SHBebdXXf5bDZvhqwMmw.',NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19',1,1,NULL,NULL),(2,'General User','general@sportiva.com',NULL,'$2y$12$jp.0fkxuxz4dNdfQDsFkMerNENvFs5E/xdSyLg295bjkE.qO8HtM.',NULL,'2026-11-03 21:32:19','2026-11-03 21:32:19',0,1,NULL,NULL),(3,'Laura Gómez','admin.club@sportiva.com',NULL,'$2y$12$HMKThDyTOBg.aIoep6P4aee3.W6zNYroV3RzAV9t3./R19jEs8qze',NULL,'2026-11-03 21:32:20','2026-11-03 21:32:20',0,1,NULL,NULL),(4,'Diego Herrera','coordinador@sportiva.com',NULL,'$2y$12$Pyn7OiNJnamrCKe8hLIOqejwwiqOc3x7fjE9DurGmEmAz1KpLedlm',NULL,'2026-11-03 21:32:20','2026-11-03 21:32:20',0,1,NULL,NULL),(5,'Pablo Ruiz','coordinador.futbol@sportiva.com',NULL,'$2y$12$KjV3FcLv4jGYJsd4Tre.OOOIRAxQftVTThuN2XAagcDFckGNyK7J2',NULL,'2026-11-03 21:32:20','2026-11-03 21:32:20',0,1,NULL,NULL),(6,'Martín Ledesma','profe@sportiva.com',NULL,'$2y$12$1eWFfou9MQ1ZInbmqrFOGu3SJwGuLoYTjfDHX3e1wSoXKssAdsQ2y',NULL,'2026-11-03 21:32:20','2026-11-03 21:32:20',0,1,NULL,NULL),(7,'Sofía Bianchi','preparador@sportiva.com',NULL,'$2y$12$BY5TMA3Girq0sIUaCsg6k.WQ2tLSMZwsHcblHs8Z/ytw4LER4jfLW',NULL,'2026-11-03 21:32:21','2026-11-03 21:32:21',0,1,NULL,NULL),(8,'Nicolás Paz','utilero@sportiva.com',NULL,'$2y$12$R4dz6xxvoKeTB0qYDYjo3.R9FvWlyoKI01281rG9l3aekQudZfjVG',NULL,'2026-11-03 21:32:21','2026-11-03 21:32:21',0,1,NULL,NULL),(9,'Ramiro Castro','mantenimiento@sportiva.com',NULL,'$2y$12$4WcjmhCpP5ABd7yErEkk/O45e8FS3WyU1Cd6porzvIM2xOQYZ22E2',NULL,'2026-11-03 21:32:21','2026-11-03 21:32:21',0,1,NULL,NULL);
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
INSERT INTO `alert_records` VALUES (1,'attendance_drop','player',6,'attendance_drop:player:6','2026-11-01 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,'registration_closing','competition',1,'registration_closing:competition:1','2026-11-02 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `announcement_recipients` VALUES (1,1,'lucía.gómez.familia@example.com','Familia de Lucía','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-30 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,'valentina.pereyra.familia@example.com','Familia de Valentina','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-30 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,1,'julieta.navarro.familia@example.com','Familia de Julieta','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-30 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,'catalina.ibarra.familia@example.com','Familia de Catalina','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-30 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,1,'mía.domínguez.familia@example.com','Familia de Mía','[{\"id\": 2, \"type\": \"category\"}]','failed','Dirección rebotada por el proveedor.',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,1,'emma.quiroga.familia@example.com','Familia de Emma','[{\"id\": 2, \"type\": \"category\"}]','sent',NULL,'2026-10-30 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `announcements` VALUES (1,'Cambio de horario del entrenamiento del jueves','Hola familias:\n\nEl entrenamiento del jueves de Sub-15 se mueve a las 17:30 en el Parque Deportivo Norte.\n\nGracias.','{\"type\": \"category\", \"category_ids\": [2]}','sent',2,2,'2026-10-30 21:32:46',6,5,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Recordatorio: traer la autorización firmada','Borrador: recordar a las familias que traigan la autorización firmada antes del torneo.','{\"type\": \"club\"}','draft',2,NULL,NULL,0,0,0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `apparel_items` VALUES (1,'Camiseta titular','Camiseta de juego, con escudo bordado.',1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Short de juego','Short con bolsillo lateral.',1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,'Buzo de entrenamiento','Buzo con capucha, cierre completo.',1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `audit_logs` VALUES (1,1,'club_user.roles_updated','club_user',2,'{\"name\": \"Diego Herrera\", \"roles\": {\"to\": [\"Coordinador deportivo\"], \"from\": [\"Profe\"]}}','127.0.0.1','2026-10-25 21:32:46'),(2,2,'order.approved','order',1,'{\"number\": \"0001\", \"status\": {\"to\": \"approved\"}}','127.0.0.1','2026-10-09 21:32:46'),(3,2,'player.deleted','player',99,'{\"name\": \"Jugador dado de baja\"}','127.0.0.1','2026-10-30 21:32:46'),(4,1,'announcement.sent','announcement',1,'{\"subject\": \"Cambio de horario del entrenamiento del jueves\", \"recipients\": 6}','127.0.0.1','2026-10-30 21:32:46');
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
INSERT INTO `categories` VALUES (1,1,2,'Sub-13 Masculino','male',12,13,1,'G7vsTylIqHvLsR57urA8RJkoF34N866qvt8VV1de','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,2,'Sub-15 Femenino','female',14,15,1,'etWxZ0uxd1duilCEV13MvRdneTE08iRXflAtbanK','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,2,2,'Sub-17 Mixto','mixed',16,17,1,'gNqMCG4gv55pfkXk7TkborXXwE3DbHpnl1SbPdMm','2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,1,'Sub-13 Masculino 2025','male',12,13,0,NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `club_user_roles` VALUES (1,1,'2026-11-03 21:32:46'),(2,2,'2026-11-03 21:32:46'),(3,3,'2026-11-03 21:32:46'),(4,4,'2026-11-03 21:32:46'),(5,5,'2026-11-03 21:32:46'),(6,6,'2026-11-03 21:32:46'),(7,7,'2026-11-03 21:32:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `club_user_sports`
--

LOCK TABLES `club_user_sports` WRITE;
/*!40000 ALTER TABLE `club_user_sports` DISABLE KEYS */;
INSERT INTO `club_user_sports` VALUES (1,3,1,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `club_users` VALUES (1,3,'Laura Gómez','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,4,'Diego Herrera','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,5,'Pablo Ruiz','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,6,'Martín Ledesma','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,7,'Sofía Bianchi','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,8,'Nicolás Paz','active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,9,'Ramiro Castro','active','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `coaches` VALUES (1,4,'Martín','Ledesma','+54 11 4000-1001','profe@sportiva.com','active','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,5,'Sofía','Bianchi','+54 11 4000-1002','preparador@sportiva.com','active','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,NULL,'Gonzalo','Ferreyra','+54 11 4000-1003',NULL,'active','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `competition_requirements` VALUES (1,1,'Apto médico',12,1,0,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,'Ficha de inscripción firmada',NULL,1,0,1,2,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,1,'Copia del DNI',NULL,1,0,1,3,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,'Autorización de los padres',NULL,1,1,1,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,2,'Apto médico',12,1,0,1,1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,2,'Copia del DNI',NULL,1,0,0,2,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `competition_standings` VALUES (1,1,1,1,'Club Demo Sportiva',1,5,4,0,1,14,5,12,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,1,2,'Racing de Tigre',0,5,3,1,1,11,6,10,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,1,1,3,'Deportivo San Fernando',0,5,2,2,1,9,7,8,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,1,4,'Unión de Benavídez',0,5,1,2,2,6,9,5,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,1,1,5,'Club Atlético Escobar',0,5,0,3,2,4,12,3,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `competitions` VALUES (1,1,2,'Liga Metropolitana Infantil','Liga Metropolitana de Fútbol','2026-11-30','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,2,2,'Torneo Juvenil de Básquet','Federación Básquet Zona Norte','2026-12-05','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `documents` VALUES (1,'player',1,'medical','apto-medico.txt','local','demo/players/1/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'player',2,'medical','apto-medico.txt','local','demo/players/2/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,'player',3,'medical','apto-medico.txt','local','demo/players/3/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(4,'player',4,'medical','apto-medico.txt','local','demo/players/4/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(5,'player',5,'medical','apto-medico.txt','local','demo/players/5/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(6,'player',6,'medical','apto-medico.txt','local','demo/players/6/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(7,'player',7,'medical','apto-medico.txt','local','demo/players/7/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(8,'player',8,'medical','apto-medico.txt','local','demo/players/8/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(9,'player',9,'medical','apto-medico.txt','local','demo/players/9/apto-medico.txt','text/plain',43,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(10,'player',10,'medical','apto-medico.txt','local','demo/players/10/apto-medico.txt','text/plain',44,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(11,'player',11,'medical','apto-medico.txt','local','demo/players/11/apto-medico.txt','text/plain',44,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(12,'player',12,'medical','apto-medico.txt','local','demo/players/12/apto-medico.txt','text/plain',44,4,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `facilities` VALUES (1,'Cancha 1','court','Sede principal, sector norte','active','Arcos reforzados en mayo de 2026.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Vestuarios A','locker_room','Sede principal, edificio del quincho','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `facility_components` VALUES (1,1,'Arco norte','review','2026-10-24 21:32:46','Red con un desgarro.','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,'Iluminación','ok','2026-10-04 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,2,'Duchas','out_of_service','2026-11-01 21:32:46','Canilla de la ducha 3 sin agua caliente.','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `inventory_balances` VALUES (1,1,1,24.00,'2026-11-03 21:32:46'),(2,1,2,0.00,'2026-11-03 21:32:46'),(3,2,1,4.00,'2026-11-03 21:32:46'),(4,2,2,3.00,'2026-11-03 21:32:46'),(5,3,1,16.00,'2026-11-03 21:32:46'),(6,3,2,0.00,'2026-11-03 21:32:46'),(7,4,1,1.00,'2026-11-03 21:32:46'),(8,4,2,1.00,'2026-11-03 21:32:46');
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
INSERT INTO `inventory_movements` VALUES (1,1,1,'in',24.00,NULL,NULL,6,'Compra inicial de temporada','FACT-0001','2026-10-04 21:32:46'),(2,2,1,'in',6.00,NULL,NULL,6,'Reposición',NULL,'2026-10-14 21:32:46'),(3,2,1,'transfer_out',3.00,'a662c30e-1264-426f-bced-6099bd149509',NULL,6,'Envío al vestuario',NULL,'2026-10-29 21:32:46'),(4,2,2,'transfer_in',3.00,'a662c30e-1264-426f-bced-6099bd149509',NULL,6,'Envío desde depósito principal',NULL,'2026-10-29 21:32:46'),(5,3,1,'adjustment',-4.00,NULL,NULL,6,'Inventario físico: dos chalecos rotos',NULL,'2026-11-01 21:32:46'),(6,4,2,'out',1.00,NULL,NULL,6,'Uso en partido',NULL,'2026-11-02 21:32:46');
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
INSERT INTO `maintenance_records` VALUES (1,2,2,7,'2026-10-28 21:32:46','Se cambió la canilla y se probó el agua caliente.',18500.00,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,1,7,'2026-11-02 21:32:46','Se pidió presupuesto de red nueva al proveedor.',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `match_callups` VALUES (1,1,1,1,'going',NULL,'2026-11-03 16:32:46','2026-11-02 21:32:46','qpomYSKkw35jfwLvd0ZdeWC9bsI7sMfb','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,2,2,'going',NULL,'2026-11-03 16:32:46','2026-11-02 21:32:46','BJqLIs8gKaJyyJRhN4WimfYqGFySvqW4','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,1,3,3,'not_going','Viaja con la familia ese fin de semana.','2026-11-03 16:32:46','2026-11-02 21:32:46','akLZAb1jnsRCQS92nBvo3rco1UJhdFlZ','2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,4,4,'going',NULL,'2026-11-03 16:32:46','2026-11-02 21:32:46','64hxUVq2HPaiw4i5rZCDaYKG00ZiP3qY','2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,1,5,5,'pending',NULL,NULL,'2026-11-02 21:32:46','Z8vBHfPLFG2jZUxhebxDzilxAyQcymCV','2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,1,6,6,'going',NULL,'2026-11-03 16:32:46','2026-11-02 21:32:46','G4WFfvLlzvgF7lE9LZqYC5zZF5t6KxUE','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES (1,1,1,'Racing de Tigre','2026-11-08 10:00:00','2026-11-08 09:15:00',1,'Cancha Principal','https://maps.google.com/?q=-34.4226,-58.5796',1,'home','Azul','Botines, canilleras y un buzo.',NULL,NULL,'scheduled','Confirmar arbitraje.','2026-11-02 21:32:46','u78fq3SNRTh6Z9NV6d3owXYT3RzRg8d0',0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,1,1,'Deportivo San Fernando','2026-10-27 10:00:00',NULL,NULL,'Estadio Municipal de San Fernando',NULL,0,'away','Blanco',NULL,3,1,'played',NULL,'2026-10-25 21:32:46','w0ufBV1CoBlb7SkvWbGywdcaya59xa6q',0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,2,1,'Unión de Benavídez','2026-11-12 11:00:00',NULL,2,'Parque Deportivo Norte',NULL,1,'home','Celeste',NULL,NULL,NULL,'postponed','Se reprograma por lluvia.',NULL,'6F2XRjgJIDLbkUiFC4bfQZ5ZbDDkpwaf',0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(4,3,2,'Club Atlético Escobar','2026-10-20 20:00:00',NULL,NULL,'Gimnasio Central',NULL,1,'home','Verde',NULL,62,58,'played',NULL,NULL,'aXIGumAqxzQUBiKjlst3z47LWisAMUda',0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(5,3,2,'Club Social San Isidro','2026-11-15 20:00:00',NULL,NULL,'Club San Isidro',NULL,0,NULL,NULL,NULL,NULL,NULL,'cancelled','Cancelado por la federación.',NULL,'kK8L8Wevc7pTwqvizL249QbiMsVZJSBW',0,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `materials` VALUES (1,'Conos de entrenamiento','Set de 20 conos naranjas.','unidad',10.00,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Pelotas de fútbol N°5','Pelotas oficiales de la liga.','unidad',6.00,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,'Chalecos de peto','Chalecos para partidillos.','unidad',12.00,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(4,'Botiquín de primeros auxilios',NULL,'kit',2.00,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_10_17_231953_create_players_table',1),(2,'2026_10_17_232500_create_sports_table',1),(3,'2026_10_17_232501_create_seasons_table',1),(4,'2026_10_17_232502_create_categories_table',1),(5,'2026_10_25_000001_create_coaches_table',1),(6,'2026_10_25_000002_create_club_users_table',1),(7,'2026_10_25_000003_create_roles_table',1),(8,'2026_10_25_000004_create_permissions_table',1),(9,'2026_10_25_000005_create_club_user_roles_table',1),(10,'2026_10_25_000006_create_role_permissions_table',1),(11,'2026_10_25_000007_add_club_user_foreign_key_to_coaches_table',1),(12,'2026_10_25_000008_create_player_category_memberships_table',1),(13,'2026_10_25_000009_create_category_coaches_table',1),(14,'2026_10_25_000010_create_storage_locations_table',1),(15,'2026_10_25_000011_create_materials_table',1),(16,'2026_10_25_000012_create_apparel_items_table',1),(17,'2026_10_25_000013_create_facilities_table',1),(18,'2026_10_25_000014_create_facility_components_table',1),(19,'2026_10_25_000015_create_matches_table',1),(20,'2026_10_25_000016_create_trainings_table',1),(21,'2026_10_25_000017_create_player_attendance_table',1),(22,'2026_10_25_000018_create_orders_table',1),(23,'2026_10_25_000019_create_apparel_order_items_table',1),(24,'2026_10_25_000020_create_material_order_items_table',1),(25,'2026_10_25_000021_create_order_status_history_table',1),(26,'2026_10_25_000022_create_inventory_balances_table',1),(27,'2026_10_25_000023_create_inventory_movements_table',1),(28,'2026_10_25_000024_create_physical_evaluations_table',1),(29,'2026_10_25_000025_create_physical_measurements_table',1),(30,'2026_10_25_000026_create_repair_requests_table',1),(31,'2026_10_25_000027_create_maintenance_records_table',1),(32,'2026_10_25_000028_create_audit_logs_table',1),(33,'2026_10_25_000029_create_notifications_table',1),(34,'2026_10_30_000001_add_catalog_columns_to_roles_and_permissions',1),(35,'2026_10_31_000001_create_player_contacts_table',1),(36,'2026_10_31_000002_add_callup_details_to_matches_table',1),(37,'2026_10_31_000003_create_competitions_table',1),(38,'2026_10_31_000004_create_category_competitions_table',1),(39,'2026_10_31_000005_create_competition_requirements_table',1),(40,'2026_10_31_000006_add_competition_id_to_matches_table',1),(41,'2026_10_31_000007_create_documents_table',1),(42,'2026_10_31_000008_create_player_requirements_table',1),(43,'2026_10_31_000009_create_match_callups_table',1),(44,'2026_10_31_000010_add_callup_publication_to_matches_table',1),(45,'2026_10_31_000011_add_minimum_stock_to_materials_table',1),(46,'2026_10_31_000012_create_venues_table',1),(47,'2026_10_31_000013_add_venue_to_matches_table',1),(48,'2026_11_01_000001_create_club_user_sports_table',1),(49,'2026_11_01_000002_add_type_and_data_to_notifications_table',1),(50,'2026_11_01_000003_create_alert_records_table',1),(51,'2026_11_02_000001_add_notified_at_to_match_callups_table',1),(52,'2026_11_02_000001_create_season_objectives_table',1),(53,'2026_11_03_000001_add_branch_to_categories_table',1),(54,'2026_11_03_000002_add_gender_to_players_table',1),(55,'2026_11_03_000003_create_announcements_tables',1),(56,'2026_11_04_000001_create_competition_standings_table',1),(57,'2026_11_05_000001_add_calendar_token_to_categories_table',1);
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
INSERT INTO `notifications` VALUES (1,4,'callup_pending','Faltan respuestas a la convocatoria','Dos convocados todavía no respondieron al partido del próximo sábado.','{\"match_id\": 1}',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,4,'attendance_drop','Asistencia baja: Santino Correa','Faltó a los últimos dos entrenamientos.','{\"player_id\": 6}','2026-11-02 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,2,'requirement_expiring','Apto médico por vencer','El apto de Julieta Navarro vence en 30 días.','{\"player_id\": 9}',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,7,'repair_assigned','Te asignaron una reparación','Red del arco norte rota.','{\"repair_request_id\": 1}',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,6,'order_approved','Pedido aprobado','El pedido de materiales fue aprobado y está en preparación.','[]','2026-11-01 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `order_status_history` VALUES (1,1,4,NULL,'submitted',NULL,'2026-10-04 21:32:46'),(2,1,2,'submitted','approved',NULL,'2026-10-04 22:32:46'),(3,1,2,'approved','preparing',NULL,'2026-10-04 23:32:46'),(4,1,2,'preparing','delivered',NULL,'2026-10-05 00:32:46'),(5,2,4,NULL,'submitted',NULL,'2026-10-10 21:32:46'),(6,2,2,'submitted','approved',NULL,'2026-10-10 22:32:46'),(7,2,2,'approved','partially_delivered',NULL,'2026-10-10 23:32:46'),(8,3,4,NULL,'submitted',NULL,'2026-10-16 21:32:46'),(9,4,4,NULL,'submitted',NULL,'2026-10-22 21:32:46'),(10,4,2,'submitted','rejected','Ya existe un pedido igual para la misma categoría.','2026-10-22 22:32:46'),(11,5,6,NULL,'submitted',NULL,'2026-10-22 21:32:46'),(12,5,2,'submitted','approved',NULL,'2026-10-22 22:32:46'),(13,5,2,'approved','delivered',NULL,'2026-10-22 23:32:46'),(14,6,6,NULL,'submitted',NULL,'2026-10-22 21:32:46'),(15,6,2,'submitted','approved',NULL,'2026-10-22 22:32:46'),(16,6,2,'approved','preparing',NULL,'2026-10-22 23:32:46');
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
INSERT INTO `orders` VALUES (1,'2026-0001','apparel',4,1,'delivered','normal','Indumentaria para el inicio de temporada Sub-13.',2,'2026-10-05 21:32:46','2026-10-09 21:32:46','2026-10-04 21:32:46','2026-10-04 21:32:46',NULL),(2,'2026-0002','apparel',4,2,'partially_delivered','high','Camisetas para Sub-15 (faltan dos talles).',2,'2026-10-11 21:32:46',NULL,'2026-10-10 21:32:46','2026-10-10 21:32:46',NULL),(3,'2026-0003','apparel',4,3,'submitted','low','Buzos para el plantel de Sub-17.',NULL,NULL,NULL,'2026-10-16 21:32:46','2026-10-16 21:32:46',NULL),(4,'2026-0004','apparel',4,1,'rejected','normal','Pedido duplicado de camisetas.',NULL,NULL,NULL,'2026-10-22 21:32:46','2026-10-22 21:32:46',NULL),(5,'2026-0005','material',6,NULL,'delivered','normal','Reposición de pelotas para la semana.',2,'2026-10-23 21:32:46','2026-10-27 21:32:46','2026-10-22 21:32:46','2026-10-22 21:32:46',NULL),(6,'2026-0006','material',6,NULL,'preparing','urgent','Botiquín para la Cancha 1.',2,'2026-10-23 21:32:46',NULL,'2026-10-22 21:32:46','2026-10-22 21:32:46',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'players.view','jugadores','Jugadores · Jugadores: Ver'),(2,'players.create','jugadores','Jugadores · Jugadores: Crear'),(3,'players.update','jugadores','Jugadores · Jugadores: Editar'),(4,'players.delete','jugadores','Jugadores · Jugadores: Eliminar'),(5,'categories.manage_players','jugadores','Jugadores · Planteles: Armar planteles'),(6,'player_contacts.view','jugadores','Jugadores · Contactos y tutores: Ver'),(7,'player_contacts.update','jugadores','Jugadores · Contactos y tutores: Editar'),(8,'player_documents.view','jugadores','Jugadores · Documentos (apto, ficha, DNI): Ver'),(9,'player_documents.upload','jugadores','Jugadores · Documentos (apto, ficha, DNI): Subir'),(10,'player_documents.delete','jugadores','Jugadores · Documentos (apto, ficha, DNI): Eliminar'),(11,'coaches.view','profes','Profes · Profes: Ver'),(12,'coaches.create','profes','Profes · Profes: Crear'),(13,'coaches.update','profes','Profes · Profes: Editar'),(14,'coaches.delete','profes','Profes · Profes: Eliminar'),(15,'categories.manage_coaches','profes','Profes · Profes por categoría: Asignar profes'),(16,'matches.view','partidos','Partidos · Partidos: Ver'),(17,'matches.create','partidos','Partidos · Partidos: Crear'),(18,'matches.update','partidos','Partidos · Partidos: Editar'),(19,'matches.delete','partidos','Partidos · Partidos: Eliminar'),(20,'trainings.view','partidos','Partidos · Entrenamientos: Ver'),(21,'trainings.create','partidos','Partidos · Entrenamientos: Crear'),(22,'trainings.update','partidos','Partidos · Entrenamientos: Editar'),(23,'trainings.delete','partidos','Partidos · Entrenamientos: Eliminar'),(24,'attendance.view','partidos','Partidos · Asistencia: Ver'),(25,'attendance.record','partidos','Partidos · Asistencia: Registrar'),(26,'callups.view','partidos','Partidos · Convocatorias: Ver'),(27,'callups.update','partidos','Partidos · Convocatorias: Editar'),(28,'callups.publish','partidos','Partidos · Convocatorias: Publicar'),(29,'physical_evaluations.view','analisis','Análisis · Evaluaciones físicas: Ver'),(30,'physical_evaluations.create','analisis','Análisis · Evaluaciones físicas: Crear'),(31,'physical_evaluations.update','analisis','Análisis · Evaluaciones físicas: Editar'),(32,'physical_evaluations.delete','analisis','Análisis · Evaluaciones físicas: Eliminar'),(33,'project.view','competencia','Proyecto · Objetivos y tabla: Ver'),(34,'project.update','competencia','Proyecto · Objetivos y tabla: Editar'),(35,'competitions.view','habilitacion','Habilitación · Competencias y requisitos: Ver'),(36,'competitions.create','habilitacion','Habilitación · Competencias y requisitos: Crear'),(37,'competitions.update','habilitacion','Habilitación · Competencias y requisitos: Editar'),(38,'competitions.delete','habilitacion','Habilitación · Competencias y requisitos: Eliminar'),(39,'player_requirements.view','habilitacion','Habilitación · Papeles de cada jugador: Ver'),(40,'player_requirements.update','habilitacion','Habilitación · Papeles de cada jugador: Editar'),(41,'player_requirements.approve','habilitacion','Habilitación · Papeles de cada jugador: Aprobar'),(42,'apparel_items.view','indumentaria','Indumentaria · Catálogo de prendas: Ver'),(43,'apparel_items.create','indumentaria','Indumentaria · Catálogo de prendas: Crear'),(44,'apparel_items.update','indumentaria','Indumentaria · Catálogo de prendas: Editar'),(45,'apparel_items.delete','indumentaria','Indumentaria · Catálogo de prendas: Eliminar'),(46,'apparel_orders.view','indumentaria','Indumentaria · Pedidos de indumentaria: Ver'),(47,'apparel_orders.create','indumentaria','Indumentaria · Pedidos de indumentaria: Crear'),(48,'apparel_orders.update','indumentaria','Indumentaria · Pedidos de indumentaria: Editar'),(49,'apparel_orders.delete','indumentaria','Indumentaria · Pedidos de indumentaria: Eliminar'),(50,'apparel_orders.approve','indumentaria','Indumentaria · Pedidos de indumentaria: Aprobar'),(51,'apparel_orders.deliver','indumentaria','Indumentaria · Pedidos de indumentaria: Entregar'),(52,'materials.view','materiales','Materiales · Materiales: Ver'),(53,'materials.create','materiales','Materiales · Materiales: Crear'),(54,'materials.update','materiales','Materiales · Materiales: Editar'),(55,'materials.delete','materiales','Materiales · Materiales: Eliminar'),(56,'storage_locations.view','materiales','Materiales · Depósitos: Ver'),(57,'storage_locations.create','materiales','Materiales · Depósitos: Crear'),(58,'storage_locations.update','materiales','Materiales · Depósitos: Editar'),(59,'storage_locations.delete','materiales','Materiales · Depósitos: Eliminar'),(60,'inventory.view','materiales','Materiales · Stock: Ver'),(61,'inventory.adjust','materiales','Materiales · Stock: Ajustar'),(62,'inventory.transfer','materiales','Materiales · Stock: Transferir'),(63,'material_orders.view','materiales','Materiales · Pedidos de materiales: Ver'),(64,'material_orders.create','materiales','Materiales · Pedidos de materiales: Crear'),(65,'material_orders.update','materiales','Materiales · Pedidos de materiales: Editar'),(66,'material_orders.delete','materiales','Materiales · Pedidos de materiales: Eliminar'),(67,'material_orders.approve','materiales','Materiales · Pedidos de materiales: Aprobar'),(68,'material_orders.deliver','materiales','Materiales · Pedidos de materiales: Entregar'),(69,'facilities.view','canchas','Canchas · Canchas e instalaciones: Ver'),(70,'facilities.create','canchas','Canchas · Canchas e instalaciones: Crear'),(71,'facilities.update','canchas','Canchas · Canchas e instalaciones: Editar'),(72,'facilities.delete','canchas','Canchas · Canchas e instalaciones: Eliminar'),(73,'repairs.view','canchas','Canchas · Reparaciones: Ver'),(74,'repairs.create','canchas','Canchas · Reparaciones: Crear'),(75,'repairs.update','canchas','Canchas · Reparaciones: Editar'),(76,'repairs.delete','canchas','Canchas · Reparaciones: Eliminar'),(77,'repairs.assign','canchas','Canchas · Reparaciones: Asignar'),(78,'maintenance.view','canchas','Canchas · Mantenimiento: Ver'),(79,'maintenance.create','canchas','Canchas · Mantenimiento: Crear'),(80,'maintenance.update','canchas','Canchas · Mantenimiento: Editar'),(81,'maintenance.delete','canchas','Canchas · Mantenimiento: Eliminar'),(82,'club.update','configuracion','Configuración · Perfil del club: Editar'),(83,'sports.view','configuracion','Configuración · Deportes: Ver'),(84,'sports.create','configuracion','Configuración · Deportes: Crear'),(85,'sports.update','configuracion','Configuración · Deportes: Editar'),(86,'sports.delete','configuracion','Configuración · Deportes: Eliminar'),(87,'seasons.view','configuracion','Configuración · Temporadas: Ver'),(88,'seasons.create','configuracion','Configuración · Temporadas: Crear'),(89,'seasons.update','configuracion','Configuración · Temporadas: Editar'),(90,'seasons.delete','configuracion','Configuración · Temporadas: Eliminar'),(91,'categories.view','configuracion','Configuración · Categorías: Ver'),(92,'categories.create','configuracion','Configuración · Categorías: Crear'),(93,'categories.update','configuracion','Configuración · Categorías: Editar'),(94,'categories.delete','configuracion','Configuración · Categorías: Eliminar'),(95,'venues.view','configuracion','Configuración · Sedes: Ver'),(96,'venues.create','configuracion','Configuración · Sedes: Crear'),(97,'venues.update','configuracion','Configuración · Sedes: Editar'),(98,'venues.delete','configuracion','Configuración · Sedes: Eliminar'),(99,'club_users.view','equipo','Equipo del club · Usuarios: Ver'),(100,'club_users.invite','equipo','Equipo del club · Usuarios: Invitar'),(101,'club_users.update','equipo','Equipo del club · Usuarios: Editar'),(102,'club_users.disable','equipo','Equipo del club · Usuarios: Deshabilitar'),(103,'roles.view','equipo','Equipo del club · Roles: Ver'),(104,'roles.create','equipo','Equipo del club · Roles: Crear'),(105,'roles.update','equipo','Equipo del club · Roles: Editar'),(106,'roles.delete','equipo','Equipo del club · Roles: Eliminar'),(107,'roles.assign','equipo','Equipo del club · Roles: Asignar'),(108,'audit.view','equipo','Equipo del club · Registro de actividad: Ver'),(109,'announcements.view','comunicados','Comunicados · Comunicados: Ver'),(110,'announcements.send','comunicados','Comunicados · Comunicados: Enviar');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_evaluations`
--

LOCK TABLES `physical_evaluations` WRITE;
/*!40000 ALTER TABLE `physical_evaluations` DISABLE KEYS */;
INSERT INTO `physical_evaluations` VALUES (1,7,2,2,'2026-10-14 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,8,2,2,'2026-10-15 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,9,2,2,'2026-10-16 17:00:00','Buena velocidad de salida; mejorar la resistencia en el segundo tiempo.','Trabajo de fondo dos veces por semana.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_measurements`
--

LOCK TABLES `physical_measurements` WRITE;
/*!40000 ALTER TABLE `physical_measurements` DISABLE KEYS */;
INSERT INTO `physical_measurements` VALUES (1,1,'sprint_30m','Sprint 30 m',4.600,'s',NULL,NULL),(2,1,'salto_horizontal','Salto horizontal',160.000,'cm',NULL,NULL),(3,1,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL),(4,2,'sprint_30m','Sprint 30 m',4.700,'s',NULL,NULL),(5,2,'salto_horizontal','Salto horizontal',164.000,'cm',NULL,NULL),(6,2,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL),(7,3,'sprint_30m','Sprint 30 m',4.800,'s',NULL,NULL),(8,3,'salto_horizontal','Salto horizontal',168.000,'cm',NULL,NULL),(9,3,'lesiones','Lesiones previas',NULL,NULL,'Ninguna',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_attendance`
--

LOCK TABLES `player_attendance` WRITE;
/*!40000 ALTER TABLE `player_attendance` DISABLE KEYS */;
INSERT INTO `player_attendance` VALUES (1,1,1,NULL,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,NULL,2,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,2,1,NULL,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,2,NULL,2,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,3,1,NULL,'late',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,3,NULL,2,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,4,1,NULL,'excused','Certificado de la escuela.',4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(8,4,NULL,2,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(9,5,1,NULL,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(10,5,NULL,2,'present',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(11,6,1,NULL,'absent',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(12,6,NULL,2,'absent',NULL,4,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `player_category_memberships` VALUES (1,1,1,'2026-03-01',NULL,1,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,2,1,'2026-03-01',NULL,2,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,3,1,'2026-03-01',NULL,3,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,4,1,'2026-03-01',NULL,4,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,5,1,'2026-03-01',NULL,5,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,6,1,'2026-03-01',NULL,6,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,7,2,'2026-03-01',NULL,1,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(8,8,2,'2026-03-01',NULL,2,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(9,9,2,'2026-03-01',NULL,3,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(10,10,2,'2026-03-01',NULL,4,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(11,11,2,'2026-03-01',NULL,5,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(12,12,2,'2026-03-01',NULL,6,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(13,13,3,'2026-03-01',NULL,1,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(14,14,3,'2026-03-01',NULL,2,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(15,15,3,'2026-03-01',NULL,3,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(16,16,3,'2026-03-01',NULL,4,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(17,17,3,'2026-03-01',NULL,5,'active','2026-11-03 21:32:46','2026-11-03 21:32:46'),(18,18,3,'2026-03-01',NULL,6,'active','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `player_contacts` VALUES (1,1,'Familiar de Tomás','father','+54 11 5555-1000','tomás.acosta.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,'Abuela de Tomás','guardian',NULL,NULL,0,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,2,'Familiar de Benjamín','mother','+54 11 5555-1001','benjamín.ríos.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,3,'Familiar de Lautaro','father','+54 11 5555-1002','lautaro.mendoza.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,4,'Familiar de Felipe','mother','+54 11 5555-1003','felipe.suárez.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,5,'Familiar de Thiago','father','+54 11 5555-1004','thiago.villalba.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,5,'Abuela de Thiago','guardian',NULL,NULL,0,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(8,6,'Familiar de Santino','mother','+54 11 5555-1005','santino.correa.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(9,7,'Familiar de Lucía','father','+54 11 5555-1006','lucía.gómez.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(10,8,'Familiar de Valentina','mother','+54 11 5555-1007','valentina.pereyra.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(11,9,'Familiar de Julieta','father','+54 11 5555-1008','julieta.navarro.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(12,9,'Abuela de Julieta','guardian',NULL,NULL,0,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(13,10,'Familiar de Catalina','mother','+54 11 5555-1009','catalina.ibarra.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(14,11,'Familiar de Mía','father','+54 11 5555-1010','mía.domínguez.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(15,12,'Familiar de Emma','mother','+54 11 5555-1011','emma.quiroga.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(16,13,'Familiar de Matías','father','+54 11 5555-1012','matías.ortega.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(17,13,'Abuela de Matías','guardian',NULL,NULL,0,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(18,14,'Familiar de Joaquín','mother','+54 11 5555-1013','joaquín.sosa.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(19,15,'Familiar de Facundo','father','+54 11 5555-1014','facundo.medina.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(20,16,'Familiar de Agustín','mother','+54 11 5555-1015','agustín.luna.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(21,17,'Familiar de Bruno','father','+54 11 5555-1016','bruno.castillo.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(22,17,'Abuela de Bruno','guardian',NULL,NULL,0,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(23,18,'Familiar de Ignacio','mother','+54 11 5555-1017','ignacio.vera.familia@example.com',1,'2026-11-03 21:32:46','2026-11-03 21:32:46');
/*!40000 ALTER TABLE `player_contacts` ENABLE KEYS */;
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
INSERT INTO `player_requirements` VALUES (1,1,1,'approved',1,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,1,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,1,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(4,1,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(5,2,1,'approved',2,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(6,2,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(7,2,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(8,2,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(9,3,1,'approved',3,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(10,3,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(11,3,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(12,3,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(13,4,1,'pending',NULL,NULL,'2027-07-03',NULL,NULL,NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(14,4,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(15,4,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(16,4,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(17,5,1,'approved',5,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(18,5,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(19,5,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(20,5,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(21,6,1,'approved',6,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(22,6,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(23,6,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(24,6,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(25,7,1,'approved',7,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(26,7,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(27,7,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(28,7,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(29,8,1,'approved',8,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(30,8,2,'rejected',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46','La firma no coincide con el DNI. Volver a presentarla.','2026-11-03 21:32:46','2026-11-03 21:32:46'),(31,8,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(32,8,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(33,9,1,'approved',9,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(34,9,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(35,9,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(36,9,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(37,10,1,'approved',10,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(38,10,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(39,10,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(40,10,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(41,11,1,'expired',11,'2026-10-14 21:32:46','2026-10-03',NULL,NULL,NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(42,11,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(43,11,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(44,11,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(45,12,1,'approved',12,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(46,12,2,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(47,12,3,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(48,12,4,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(49,13,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(50,13,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(51,14,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(52,14,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(53,15,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(54,15,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(55,16,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(56,16,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(57,17,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(58,17,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(59,18,5,'approved',NULL,'2026-10-14 21:32:46','2027-07-03',2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(60,18,6,'approved',NULL,'2026-10-14 21:32:46',NULL,2,'2026-10-19 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `players` VALUES (1,'Tomás','Acosta','2013-04-12','male','40000000','FED-2026000','Arquero','right','active','Llegó de la escuela de la zona en 2025.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Benjamín','Ríos','2013-07-02','male','40000137','FED-2026001','Defensor','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,'Lautaro','Mendoza','2013-01-19','male','40000274','FED-2026002','Mediocampista','left','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(4,'Felipe','Suárez','2014-03-30','male','40000411','FED-2026003','Delantero','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(5,'Thiago','Villalba','2013-09-08','male','40000548','FED-2026004','Mediocampista','both','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(6,'Santino','Correa','2014-05-21','male','40000685','FED-2026005','Defensor','right','active','Llegó de la escuela de la zona en 2025.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(7,'Lucía','Gómez','2011-06-14','female','40000822','FED-2026006','Delantera','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(8,'Valentina','Pereyra','2011-02-03','female','40000959','FED-2026007','Mediocampista','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(9,'Julieta','Navarro','2012-08-27','female','40001096','FED-2026008','Defensora','left','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(10,'Catalina','Ibarra','2011-11-11','female','40001233','FED-2026009','Arquera','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(11,'Mía','Domínguez','2012-04-05','female','40001370','FED-2026010','Mediocampista','right','active','Llegó de la escuela de la zona en 2025.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(12,'Emma','Quiroga','2011-09-18','female','40001507','FED-2026011','Delantera','left','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(13,'Matías','Ortega','2009-10-01','male','40001644','FED-2026012','Base','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(14,'Joaquín','Sosa','2010-03-15','male','40001781','FED-2026013','Escolta','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(15,'Facundo','Medina','2009-12-22','male','40001918','FED-2026014','Alero','left','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(16,'Agustín','Luna','2010-07-07','male','40002055','FED-2026015','Pivot','right','active','Llegó de la escuela de la zona en 2025.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(17,'Bruno','Castillo','2009-05-30','male','40002192','FED-2026016','Base','right','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(18,'Ignacio','Vera','2010-01-25','male','40002329','FED-2026017','Escolta','both','active',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
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
INSERT INTO `repair_requests` VALUES (1,1,1,2,7,'Red del arco norte rota','Desgarro de unos 40 cm en la parte baja de la red.','high','in_progress','2026-10-31 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,2,3,2,7,'Ducha 3 sin agua caliente','La canilla de la ducha 3 no sale agua caliente.','normal','resolved','2026-10-26 21:32:46','2026-10-28 21:32:46','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,1,2,7,NULL,'Revisar foco de la torre de luz','Uno de los focos parpadea.','low','open','2026-11-02 21:32:46',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `role_permissions` VALUES (1,1,'all'),(1,2,'all'),(1,3,'all'),(1,4,'all'),(1,5,'all'),(1,6,'all'),(1,7,'all'),(1,8,'all'),(1,9,'all'),(1,10,'all'),(1,11,'all'),(1,12,'all'),(1,13,'all'),(1,14,'all'),(1,15,'all'),(1,16,'all'),(1,17,'all'),(1,18,'all'),(1,19,'all'),(1,20,'all'),(1,21,'all'),(1,22,'all'),(1,23,'all'),(1,24,'all'),(1,25,'all'),(1,26,'all'),(1,27,'all'),(1,28,'all'),(1,29,'all'),(1,30,'all'),(1,31,'all'),(1,32,'all'),(1,33,'all'),(1,34,'all'),(1,35,'all'),(1,36,'all'),(1,37,'all'),(1,38,'all'),(1,39,'all'),(1,40,'all'),(1,41,'all'),(1,42,'all'),(1,43,'all'),(1,44,'all'),(1,45,'all'),(1,46,'all'),(1,47,'all'),(1,48,'all'),(1,49,'all'),(1,50,'all'),(1,51,'all'),(1,52,'all'),(1,53,'all'),(1,54,'all'),(1,55,'all'),(1,56,'all'),(1,57,'all'),(1,58,'all'),(1,59,'all'),(1,60,'all'),(1,61,'all'),(1,62,'all'),(1,63,'all'),(1,64,'all'),(1,65,'all'),(1,66,'all'),(1,67,'all'),(1,68,'all'),(1,69,'all'),(1,70,'all'),(1,71,'all'),(1,72,'all'),(1,73,'all'),(1,74,'all'),(1,75,'all'),(1,76,'all'),(1,77,'all'),(1,78,'all'),(1,79,'all'),(1,80,'all'),(1,81,'all'),(1,82,'all'),(1,83,'all'),(1,84,'all'),(1,85,'all'),(1,86,'all'),(1,87,'all'),(1,88,'all'),(1,89,'all'),(1,90,'all'),(1,91,'all'),(1,92,'all'),(1,93,'all'),(1,94,'all'),(1,95,'all'),(1,96,'all'),(1,97,'all'),(1,98,'all'),(1,99,'all'),(1,100,'all'),(1,101,'all'),(1,102,'all'),(1,103,'all'),(1,104,'all'),(1,105,'all'),(1,106,'all'),(1,107,'all'),(1,108,'all'),(1,109,'all'),(1,110,'all'),(2,1,'all'),(2,2,'all'),(2,3,'all'),(2,4,'all'),(2,5,'all'),(2,6,'all'),(2,7,'all'),(2,8,'all'),(2,9,'all'),(2,10,'all'),(2,11,'all'),(2,12,'all'),(2,13,'all'),(2,14,'all'),(2,15,'all'),(2,16,'all'),(2,17,'all'),(2,18,'all'),(2,19,'all'),(2,20,'all'),(2,21,'all'),(2,22,'all'),(2,23,'all'),(2,24,'all'),(2,25,'all'),(2,26,'all'),(2,27,'all'),(2,28,'all'),(2,29,'all'),(2,33,'all'),(2,34,'all'),(2,35,'all'),(2,36,'all'),(2,37,'all'),(2,38,'all'),(2,39,'all'),(2,40,'all'),(2,41,'all'),(2,42,'all'),(2,46,'all'),(2,47,'all'),(2,48,'all'),(2,50,'all'),(2,52,'all'),(2,56,'all'),(2,60,'all'),(2,63,'all'),(2,64,'all'),(2,65,'all'),(2,69,'all'),(2,73,'all'),(2,74,'all'),(2,78,'all'),(2,83,'all'),(2,84,'all'),(2,85,'all'),(2,86,'all'),(2,87,'all'),(2,88,'all'),(2,89,'all'),(2,90,'all'),(2,91,'all'),(2,92,'all'),(2,93,'all'),(2,94,'all'),(2,95,'all'),(2,96,'all'),(2,97,'all'),(2,98,'all'),(2,109,'all'),(2,110,'all'),(3,1,'sport'),(3,2,'sport'),(3,3,'sport'),(3,4,'sport'),(3,5,'sport'),(3,6,'sport'),(3,7,'sport'),(3,8,'sport'),(3,9,'sport'),(3,10,'all'),(3,11,'sport'),(3,12,'all'),(3,13,'all'),(3,14,'all'),(3,15,'all'),(3,16,'sport'),(3,17,'sport'),(3,18,'sport'),(3,19,'sport'),(3,20,'sport'),(3,21,'sport'),(3,22,'sport'),(3,23,'sport'),(3,24,'sport'),(3,25,'sport'),(3,26,'sport'),(3,27,'sport'),(3,28,'sport'),(3,29,'sport'),(3,33,'all'),(3,34,'all'),(3,35,'sport'),(3,36,'all'),(3,37,'all'),(3,38,'all'),(3,39,'sport'),(3,40,'sport'),(3,41,'all'),(3,42,'all'),(3,46,'sport'),(3,47,'sport'),(3,48,'sport'),(3,52,'all'),(3,56,'all'),(3,60,'all'),(3,63,'sport'),(3,64,'sport'),(3,65,'sport'),(3,69,'all'),(3,73,'all'),(3,74,'all'),(3,78,'all'),(3,83,'all'),(3,84,'all'),(3,85,'all'),(3,86,'all'),(3,87,'all'),(3,88,'all'),(3,89,'all'),(3,90,'all'),(3,91,'sport'),(3,92,'all'),(3,93,'all'),(3,94,'all'),(3,95,'all'),(3,96,'all'),(3,97,'all'),(3,98,'all'),(3,109,'sport'),(3,110,'sport'),(4,1,'own'),(4,6,'own'),(4,8,'own'),(4,9,'own'),(4,11,'own'),(4,16,'own'),(4,17,'own'),(4,18,'own'),(4,20,'own'),(4,21,'own'),(4,22,'own'),(4,24,'own'),(4,25,'own'),(4,26,'own'),(4,27,'own'),(4,28,'own'),(4,29,'own'),(4,33,'all'),(4,35,'own'),(4,39,'own'),(4,40,'own'),(4,42,'all'),(4,46,'own'),(4,47,'own'),(4,48,'own'),(4,63,'own'),(4,64,'own'),(4,65,'own'),(4,69,'all'),(4,73,'all'),(4,74,'all'),(4,91,'own'),(4,95,'all'),(4,109,'own'),(4,110,'own'),(5,1,'own'),(5,11,'own'),(5,16,'own'),(5,20,'own'),(5,24,'own'),(5,26,'own'),(5,29,'own'),(5,30,'own'),(5,31,'own'),(5,32,'own'),(5,33,'all'),(5,35,'own'),(5,39,'own'),(5,63,'own'),(5,64,'own'),(5,65,'own'),(5,69,'all'),(5,73,'all'),(5,74,'all'),(5,91,'own'),(5,95,'all'),(6,16,'all'),(6,20,'all'),(6,26,'all'),(6,42,'all'),(6,43,'all'),(6,44,'all'),(6,45,'all'),(6,46,'all'),(6,47,'all'),(6,48,'all'),(6,51,'all'),(6,52,'all'),(6,53,'all'),(6,54,'all'),(6,55,'all'),(6,56,'all'),(6,57,'all'),(6,58,'all'),(6,59,'all'),(6,60,'all'),(6,61,'all'),(6,62,'all'),(6,63,'all'),(6,64,'all'),(6,65,'all'),(6,68,'all'),(6,69,'all'),(6,73,'all'),(6,74,'all'),(6,95,'all'),(7,16,'all'),(7,20,'all'),(7,69,'all'),(7,70,'all'),(7,71,'all'),(7,72,'all'),(7,73,'all'),(7,74,'all'),(7,75,'all'),(7,76,'all'),(7,77,'all'),(7,78,'all'),(7,79,'all'),(7,80,'all'),(7,81,'all'),(7,95,'all');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrador del club','Comisión directiva o gerente. Tiene todos los permisos.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(2,'coordinator','Coordinador deportivo','Coordina categorías, profes y fixture.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(3,'sport_coordinator','Coordinador de deporte','Coordina sus deportes asignados: categorías, planteles, profes y partidos.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(4,'coach','Profe','Entrenador de una o más categorías.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(5,'physical_trainer','Preparador físico','Evaluaciones físicas de sus categorías.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(6,'inventory_manager','Utilero / Encargado','Materiales, indumentaria y entregas.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45'),(7,'maintenance_manager','Mantenimiento','Canchas e instalaciones.',1,'2026-11-03 21:32:45','2026-11-03 21:32:45');
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
INSERT INTO `season_objectives` VALUES (1,2,NULL,'Llegar a la final de la liga','Clasificar entre los cuatro primeros.',4.00,2.00,'posición','in_progress','2026-12-01','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,2,2,'Asistencia promedio del 85%',NULL,85.00,88.00,'%','achieved','2026-11-15','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,2,3,'Ganar 10 partidos',NULL,10.00,4.00,'partidos','pending','2026-12-10','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `seasons` VALUES (1,'2025','2025-03-01','2025-12-15','closed','2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,'2026 - segunda mitad','2026-03-01','2026-12-15','active','2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `sports` VALUES (1,'Fútbol',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(2,'Básquet',1,'2026-11-03 21:32:46','2026-11-03 21:32:46'),(3,'Hockey',1,'2026-11-03 21:32:46','2026-11-03 21:32:46');
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
INSERT INTO `storage_locations` VALUES (1,'Depósito principal','DEP-01','Estante A, sede principal.',1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Vestuario visitante','VES-02',NULL,1,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
INSERT INTO `trainings` VALUES (1,1,1,'2026-10-31 18:00:00','2026-10-31 19:30:00','Cancha Principal','Circulación y pressing alto.',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,2,1,'2026-11-05 17:30:00','2026-11-05 19:00:00','Parque Deportivo Norte','Definición en el área.','Traer botines de repuesto.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(3,3,3,'2026-11-04 19:00:00','2026-11-04 20:30:00','Gimnasio Central',NULL,NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(4,1,2,'2026-10-24 18:00:00','2026-10-24 19:00:00','Cancha Principal','Test de velocidad.',NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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
INSERT INTO `venues` VALUES (1,'Cancha Principal','Av. Libertador 1234, Tigre, Buenos Aires',-34.4226000,-58.5796000,1,'Cancha de césped sintético con iluminación.','2026-11-03 21:32:46','2026-11-03 21:32:46',NULL),(2,'Parque Deportivo Norte','Calle Los Ceibos 450, Benavídez, Buenos Aires',-34.4118000,-58.6358000,1,NULL,'2026-11-03 21:32:46','2026-11-03 21:32:46',NULL);
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

-- Dump completed on 2026-11-03 21:33:42
