-- MySQL dump 10.13  Distrib 8.0.45-36, for Linux (x86_64)
--
-- Host: localhost    Database: cm903759_purelife
-- ------------------------------------------------------
-- Server version	8.0.45-36

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
/*!50717 SELECT COUNT(*) INTO @rocksdb_has_p_s_session_variables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'performance_schema' AND TABLE_NAME = 'session_variables' */;
/*!50717 SET @rocksdb_get_is_supported = IF (@rocksdb_has_p_s_session_variables, 'SELECT COUNT(*) INTO @rocksdb_is_supported FROM performance_schema.session_variables WHERE VARIABLE_NAME=\'rocksdb_bulk_load\'', 'SELECT 0') */;
/*!50717 PREPARE s FROM @rocksdb_get_is_supported */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;
/*!50717 SET @rocksdb_enable_bulk_load = IF (@rocksdb_is_supported, 'SET SESSION rocksdb_bulk_load = 1', 'SET @rocksdb_dummy_bulk_load = 0') */;
/*!50717 PREPARE s FROM @rocksdb_enable_bulk_load */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT '0.0',
  `years_experience` int DEFAULT '0',
  `is_absent` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (5,'Ира',NULL,NULL,'/assets/images/employees/Ira.jpg',2.0,5,0,'2026-05-10 22:48:17'),(6,'Иван',NULL,NULL,'/assets/images/employees/Ivan.jpg',4.0,7,0,'2026-05-10 22:48:27'),(7,'Степан','killer1337228@mail.ru','+7 (999) 999-99-99','/assets/images/employees/Stepan.jpg',5.0,3,1,'2026-05-10 22:48:39'),(8,'Джон',NULL,NULL,'/assets/images/employees/Djon.jpg',1.0,4,0,'2026-05-10 22:48:55'),(9,'Денис',NULL,NULL,'/assets/images/employees/Denis.jpg',5.0,8,0,'2026-05-10 22:49:16'),(10,'Константин',NULL,NULL,'/assets/images/employees/Constantin.jpg',4.0,10,0,'2026-05-10 22:49:28'),(11,'Осас',NULL,NULL,'/assets/images/employees/Osas.jpg',5.0,6,0,'2026-05-10 22:50:11');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `service_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (55,26,2,1,3500.00),(56,26,3,1,5000.00),(57,27,4,1,2500.00),(58,27,5,1,1200.00),(59,28,3,1,5000.00),(60,29,5,1,1200.00),(61,30,1,1,1500.00),(62,30,2,1,3500.00),(63,31,1,1,1500.00),(64,32,1,1,1500.00),(65,33,4,1,2500.00),(72,36,1,1,1500.00),(73,36,2,1,3500.00),(74,36,3,1,5000.00),(105,58,3,1,5000.00),(106,59,2,1,3500.00),(107,59,3,1,5000.00),(109,61,3,1,5000.00),(110,62,3,1,5000.00),(111,63,3,1,5000.00),(112,63,4,1,2500.00),(115,66,18,1,1200.00),(116,67,2,1,3500.00),(117,67,3,1,5000.00),(119,69,18,1,1200.00),(120,69,2,1,3500.00),(123,71,1,1,1500.00),(124,71,2,1,3500.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `status` enum('Новый','В работе','Выполнен','Отменён') NOT NULL DEFAULT 'Новый',
  `total_price` decimal(10,2) NOT NULL,
  `address` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `property_type` enum('Квартира','Офис','Дом') NOT NULL DEFAULT 'Квартира',
  `employee_id` int DEFAULT NULL,
  `payment_type` enum('cash','online') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','cash') NOT NULL DEFAULT 'pending',
  `is_canceled` tinyint(1) DEFAULT '0',
  `canceled_at` timestamp NULL DEFAULT NULL,
  `rescheduled_from` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (26,7,'Выполнен',8500.00,'пос. Парголово, ул. Луговая, д. 15','2026-05-15','11:11:00','','2026-05-10 22:57:54','Дом',NULL,'cash','pending',0,NULL,NULL),(27,6,'Выполнен',3700.00,'ул. Рубинштейна, д. 23, кв. 45','2026-05-15','19:30:00','','2026-05-10 23:05:01','Квартира',NULL,'cash','pending',0,NULL,NULL),(28,6,'Выполнен',5000.00,'пос. Шушары, ул. Первомайская, д. 7','2026-05-30','13:30:00','Электричество выключено','2026-05-10 23:05:58','Дом',NULL,'cash','pending',0,NULL,NULL),(29,5,'Выполнен',1200.00,'ул. Восстания, д. 10, кв. 78','2026-05-22','15:36:00','','2026-05-10 23:07:02','Квартира',NULL,'cash','pending',0,NULL,NULL),(30,5,'Выполнен',5000.00,'Лиговский проспект, д. 87, оф. 305','2026-05-22','18:00:00','Начать уборку после ухода работников.','2026-05-10 23:07:49','Офис',NULL,'cash','pending',0,NULL,NULL),(31,4,'В работе',1500.00,'ул. Марата, д. 38, кв. 21','2026-05-31','17:00:00','','2026-05-10 23:08:40','Квартира',NULL,'cash','pending',0,NULL,NULL),(32,4,'В работе',1500.00,'ул. Жуковского, д. 22, бизнес-центр \"Невский\", оф. 12','2026-05-25','08:00:00','','2026-05-10 23:09:03','Офис',NULL,'cash','pending',0,NULL,NULL),(33,7,'В работе',2500.00,'наб. реки Фонтанки, д. 48, оф. 201','2026-05-29','12:00:00','','2026-05-10 23:11:28','Офис',NULL,'cash','pending',0,NULL,NULL),(36,7,'В работе',9500.00,'ул. Невский проспект, д. 56, кв. 12','2026-05-14','13:11:00','Звоните на номер телефона, если не получится по домофону.','2026-05-11 17:11:58','Квартира',NULL,'cash','pending',1,'2026-05-11 20:49:00',35),(58,2,'Новый',5000.00,'1','2026-06-06','11:11:00','','2026-05-12 22:36:53','Квартира',NULL,'cash','pending',0,NULL,NULL),(59,2,'Новый',8500.00,'2','2026-06-05','11:11:00','','2026-05-12 22:37:07','Квартира',NULL,'cash','pending',0,NULL,NULL),(61,2,'Выполнен',5000.00,'ыыыыыыы','2026-06-06','11:11:00','','2026-05-12 22:44:22','Квартира',NULL,'cash','paid',0,NULL,NULL),(62,2,'Выполнен',4900.00,'ddddddddddd','2026-06-06','11:01:00','','2026-05-13 08:52:58','Квартира',6,'online','paid',0,NULL,NULL),(63,2,'Новый',7200.00,'ывыфвввввввввввввввввввв','2026-06-06','11:11:00','выфвыфвфы','2026-05-13 09:41:58','Дом',NULL,'cash','pending',0,NULL,NULL),(66,2,'Новый',1152.00,'Заказа на уборку дом 3','2026-05-15','11:45:00','','2026-05-14 04:45:48','Дом',NULL,'cash','pending',0,NULL,NULL),(67,2,'Отменён',8160.00,'Gggg','2026-05-15','18:22:00','','2026-05-14 13:23:00','Квартира',NULL,'online','pending',1,'2026-05-18 12:47:54',NULL),(69,17,'Новый',4700.00,'Светлая17','2026-05-20','12:12:00','Вынести мусор и искупать черепашку','2026-05-18 18:13:40','Дом',NULL,'online','paid',0,NULL,NULL),(71,5,'Новый',4850.00,'sssssssssssss','2026-05-21','12:11:00','','2026-05-19 17:30:00','Квартира',NULL,'cash','pending',0,NULL,70);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,4,'6744c6f01b421bc824b149c916945d1cf03c84abe1786ecc6e11da11479e8813','2026-05-18 23:00:16','2026-05-18 19:00:16');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` tinyint DEFAULT NULL,
  `text` text,
  `moderation_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `employee_rating` tinyint DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (16,4,31,5,'Класс','approved','2026-05-11 20:47:01',5,9),(17,5,30,4,'Норм','approved','2026-05-11 20:47:22',4,10),(18,5,29,5,'Можно было и лучше','approved','2026-05-11 20:47:38',5,11),(19,6,28,5,'Шустро и эффективно убрались','approved','2026-05-11 20:48:13',5,11),(20,6,27,3,'Чё-то накосячили и сломали фен...','approved','2026-05-11 20:48:43',2,5),(21,7,33,5,'Молодцы! Орлы!','approved','2026-05-11 20:49:36',5,7),(22,7,26,1,'УЖАСНО!','approved','2026-05-11 20:50:11',1,8),(24,2,62,5,'very good','rejected','2026-05-13 08:54:39',4,6),(25,2,61,5,'Супер-пупер!','rejected','2026-05-13 15:46:33',4,NULL);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Поддерживающая уборка','Регулярная уборка для поддержания чистоты',1500.00,1),(2,'Генеральная уборка','Полная уборка всех помещений',3500.00,2),(3,'Уборка после ремонта','Удаление пыли, строительного мусора',5000.00,3),(4,'Химчистка мебели','Глубокая чистка диванов, кресел',2500.00,4),(5,'Мытьё окон','Чистка окон и подоконников',1200.00,5),(18,'Чистка домашнего животного','Почистим вашего питомца от шерсти.',1200.00,0);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','manager','client') DEFAULT 'client',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_token_expires_at` datetime DEFAULT NULL,
  `last_verification_sent` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `verification_token` (`verification_token`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'info@purelife.site','$2y$12$q3zV7UlSlOQ30sc5LE00YeSRMe8gW01OjiXEYfCGLk3HhJkaO75X.','Slava','+7 (965) 759-71-42','admin',1,0,NULL,NULL,NULL,'2026-05-06 20:17:39'),(3,'manager@mail.ru','$2y$10$6BQdV68EIErFBar3qNd8c.5f31kQSZEsjaULINLhNl7DYuieoj1mW','Тест','+7 (965) 777-71-71','manager',1,0,NULL,NULL,NULL,'2026-05-06 20:55:49'),(4,'sos@mail.ru','$2y$12$q3zV7UlSlOQ30sc5LE00YeSRMe8gW01OjiXEYfCGLk3HhJkaO75X.','Слава','+7 (965) 444-44-42','client',1,1,NULL,NULL,NULL,'2026-05-07 15:47:05'),(5,'sahka@mail.ru','$2y$12$1frD6487ZAdOABK2ZG.Af.qyZQZJojhwqhFHfEfFdM3AF4l2T8U3e','Саша','+7 (965) 759-71-42','client',1,0,NULL,NULL,NULL,'2026-05-10 22:41:48'),(6,'Voka@mail.ru','$2y$12$JbvrNMl8wH5M/.619UM95.1n4wh0pgozO51ASnhDpxwo4DFvRH7z2','Вова','+7 (965) 759-71-42','client',1,0,NULL,NULL,NULL,'2026-05-10 22:42:08'),(7,'Masha@yandex.ru','$2y$12$262k2Ozz6o3txevGKf8iROmkV/vhDCckBLTfs.UrpMEG8ZJpKG4BO','Маша','+7 (965) 759-71-42','client',1,0,NULL,NULL,NULL,'2026-05-10 22:42:40'),(17,'kek1@mail.ru','$2y$12$r2SMoMr2jgauLNlS.ks.POcIYbnNvBCD/DNJVyKXxfOb66IbtNdZi','Евгеша','+7 (906) 265-60-52','client',1,0,NULL,NULL,NULL,'2026-05-18 18:07:30'),(20,'pro212@mail.ru','$2y$12$F2V5JG9/aZHKVLYhuyTn5eH6RUO2fIaTwhIHriXRPbAiomeE3MjSe','pro212@mail.ru','+7 (111) 111-11-11','client',0,0,'4ea6e831af598a3f1d4b3fad174d7bc19f8aee4576637ca3a8bf768099390383','2026-05-20 16:14:27','2026-05-19 16:14:27','2026-05-19 13:14:27'),(26,'slava.kbe@yandex.ru','$2y$12$52lNzrbE7pR/GWaqOUoSZuQY6HIaIZQDZwYgrQ33H9UxfcoFlaJh.','slava.kbe@yandex.ru','+7 (111) 111-11-11','client',0,0,'d0bb2c7014c54d5678fc87979791018316b4b8ea5c05ce6423bf089f10a76bb5','2026-05-20 17:17:44','2026-05-19 17:17:44','2026-05-19 14:17:44'),(28,'slava.kbem@mail.ru','$2y$12$ZbqdN4AnfZdUVOG4YBhCIOF1k7dNBoZ9d7Vc8YZNV0cXEgk05z9ny','slava.kbem@mail.ru','+7 (111) 111-11-11','client',0,0,'0647b4096aafa2b4f7d18ffbc3ef5570f7aa81bf4de906e112561aa3a771d735','2026-05-20 17:54:07','2026-05-19 17:54:07','2026-05-19 14:54:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50112 SET @disable_bulk_load = IF (@is_rocksdb_supported, 'SET SESSION rocksdb_bulk_load = @old_rocksdb_bulk_load', 'SET @dummy_rocksdb_bulk_load = 0') */;
/*!50112 PREPARE s FROM @disable_bulk_load */;
/*!50112 EXECUTE s */;
/*!50112 DEALLOCATE PREPARE s */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 22:30:02
