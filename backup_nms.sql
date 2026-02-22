-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: nms_planning
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.2

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
-- Table structure for table `mouvements_stock`
--

DROP TABLE IF EXISTS `mouvements_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mouvements_stock` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `type_mouvement` enum('ENTREE','SORTIE') COLLATE utf8mb4_general_ci NOT NULL,
  `quantite` int NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `id_user` int DEFAULT NULL,
  `id_site` int DEFAULT NULL,
  `responsable_nom` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_mouvement`),
  KEY `id_produit` (`id_produit`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_stock`
--

LOCK TABLES `mouvements_stock` WRITE;
/*!40000 ALTER TABLE `mouvements_stock` DISABLE KEYS */;
INSERT INTO `mouvements_stock` VALUES (1,6,'ENTREE',2,'2026-02-15 02:08:07',NULL,8,1,'Pierre Martin'),(2,5,'SORTIE',5,'2026-02-15 02:08:59',NULL,8,1,'Pierre Martin'),(3,7,'ENTREE',2,'2026-02-15 02:09:16',NULL,8,1,'Pierre Martin'),(4,8,'ENTREE',12,'2026-02-15 22:35:15',NULL,8,1,'Pierre Martin'),(5,8,'SORTIE',6,'2026-02-15 22:35:51',NULL,8,1,'Pierre Martin'),(6,9,'ENTREE',23,'2026-02-15 22:55:03',NULL,11,2,'ARISTIDE GOGAN'),(7,9,'ENTREE',12,'2026-02-17 01:23:37',NULL,11,2,'ARISTIDE GOGAN'),(8,9,'ENTREE',1,'2026-02-19 01:38:37',NULL,11,2,'ARISTIDE GOGAN'),(9,8,'SORTIE',6,'2026-02-19 01:49:37',NULL,8,1,'Pierre Martin'),(10,6,'ENTREE',8,'2026-02-19 01:57:29',NULL,8,1,'Pierre Martin'),(11,9,'ENTREE',7,'2026-02-19 01:59:24',NULL,11,2,'ARISTIDE GOGAN'),(12,9,'SORTIE',48,'2026-02-19 01:59:59',NULL,11,2,'ARISTIDE GOGAN'),(13,9,'ENTREE',16,'2026-02-19 02:25:06',NULL,11,2,'ARISTIDE GOGAN'),(14,9,'ENTREE',3,'2026-02-19 02:46:32',NULL,11,2,'ARISTIDE GOGAN'),(15,9,'SORTIE',20,'2026-02-19 02:46:53',NULL,11,2,'ARISTIDE GOGAN'),(16,9,'ENTREE',2,'2026-02-19 02:55:51',NULL,11,2,'ARISTIDE GOGAN'),(17,9,'ENTREE',23,'2026-02-19 02:56:10',NULL,11,2,'ARISTIDE GOGAN'),(18,9,'SORTIE',20,'2026-02-19 03:10:24',NULL,11,2,'ARISTIDE GOGAN'),(19,9,'SORTIE',5,'2026-02-19 03:19:29',NULL,11,2,'ARISTIDE GOGAN'),(20,9,'ENTREE',45,'2026-02-19 03:28:46',NULL,11,2,'ARISTIDE GOGAN'),(21,10,'SORTIE',7,'2026-02-19 03:51:11',NULL,11,2,'ARISTIDE GOGAN'),(22,9,'SORTIE',42,'2026-02-19 03:51:40',NULL,11,2,'ARISTIDE GOGAN');
/*!40000 ALTER TABLE `mouvements_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements_stock_admin`
--

DROP TABLE IF EXISTS `mouvements_stock_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mouvements_stock_admin` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_produit_admin` int NOT NULL,
  `type_mouvement` enum('ENTREE','SORTIE') COLLATE utf8mb4_general_ci NOT NULL,
  `quantite` int NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `commentaire` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_mouvement`),
  KEY `id_produit_admin` (`id_produit_admin`),
  CONSTRAINT `mouvements_stock_admin_ibfk_1` FOREIGN KEY (`id_produit_admin`) REFERENCES `produits_admin` (`id_produit_admin`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_stock_admin`
--

LOCK TABLES `mouvements_stock_admin` WRITE;
/*!40000 ALTER TABLE `mouvements_stock_admin` DISABLE KEYS */;
INSERT INTO `mouvements_stock_admin` VALUES (1,1,'ENTREE',17,'2026-02-19 20:47:15',''),(2,1,'SORTIE',4,'2026-02-19 21:35:10',''),(3,1,'SORTIE',4,'2026-02-19 21:35:50',''),(4,1,'SORTIE',4,'2026-02-19 21:38:38',''),(5,2,'ENTREE',6,'2026-02-19 21:39:33',''),(6,2,'ENTREE',6,'2026-02-19 21:39:39',''),(7,2,'ENTREE',6,'2026-02-19 21:39:53',''),(8,4,'ENTREE',4,'2026-02-19 21:48:17',''),(9,3,'ENTREE',14,'2026-02-19 21:48:32',''),(10,3,'ENTREE',14,'2026-02-19 21:55:08',''),(11,2,'SORTIE',1,'2026-02-19 23:53:40',''),(12,7,'ENTREE',12,'2026-02-20 01:41:58',''),(13,8,'ENTREE',76,'2026-02-20 02:45:22',''),(14,8,'ENTREE',76,'2026-02-20 02:51:16',''),(15,2,'ENTREE',1,'2026-02-20 02:51:40',''),(16,2,'SORTIE',18,'2026-02-20 02:51:49',''),(17,2,'SORTIE',12,'2026-02-20 02:53:42','');
/*!40000 ALTER TABLE `mouvements_stock_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id_notify` int unsigned NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `from_user` int DEFAULT NULL,
  `type` enum('arrivee','depart','urgence') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notify`),
  KEY `idx_notifications_user` (`id_user`),
  KEY `idx_notifications_from` (`from_user`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (91,6,9,'urgence','🚨 agent ALLAGBE Sylvanus a signalé une urgence (Congé maladie) le 18/02/2026. Arrivée: 18h09min - Départ: 08h07min. Commentaire: Olatundji',0,'2026-02-18 23:11:53'),(92,8,9,'urgence','🚨 agent ALLAGBE Sylvanus a signalé une urgence (Congé maladie) le 18/02/2026. Arrivée: 18h09min - Départ: 08h07min. Commentaire: Olatundji',0,'2026-02-18 23:11:53'),(93,11,9,'urgence','🚨 agent ALLAGBE Sylvanus a signalé une urgence (Congé maladie) le 18/02/2026. Arrivée: 18h09min - Départ: 08h07min. Commentaire: Olatundji',1,'2026-02-18 23:11:53'),(94,6,9,'urgence','agent ALLAGBE Sylvanus a signalé une urgence (Congé personnel) le 18/02/2026. Arrivée: 19h00min - Départ: 01h09min. Commentaire: Compte rendu',0,'2026-02-18 23:24:58'),(95,11,9,'urgence','agent ALLAGBE Sylvanus a signalé une urgence (Congé personnel) le 18/02/2026. Arrivée: 19h00min - Départ: 01h09min. Commentaire: Compte rendu',1,'2026-02-18 23:24:58'),(96,6,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 19/02/2026 à 00h01min',0,'2026-02-19 00:01:55'),(97,8,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 19/02/2026 à 00h01min',0,'2026-02-19 00:01:55'),(98,6,11,'arrivee','superviseur GOGAN ARISTIDE est arrivé sur le site de Calavi le 19/02/2026 à 00h03min',1,'2026-02-19 00:03:22'),(99,11,11,'arrivee','superviseur GOGAN ARISTIDE est arrivé sur le site de Calavi le 19/02/2026 à 00h03min',1,'2026-02-19 00:03:22'),(100,6,7,'depart','agent Dupont Jean a quitté le site de Gbégamey le 19/02/2026 à 00h04min donc il a travaillé 0h00.',0,'2026-02-19 00:04:31'),(101,8,7,'depart','agent Dupont Jean a quitté le site de Gbégamey le 19/02/2026 à 00h04min donc il a travaillé 0h00.',0,'2026-02-19 00:04:31'),(102,6,11,'depart','superviseur GOGAN ARISTIDE a quitté le site de Calavi le 19/02/2026 à 01h03min donc il a travaillé 0h00.',1,'2026-02-19 01:03:39'),(103,11,11,'depart','superviseur GOGAN ARISTIDE a quitté le site de Calavi le 19/02/2026 à 01h03min donc il a travaillé 0h00.',1,'2026-02-19 01:03:39'),(104,6,7,'urgence','agent Dupont Jean a signalé une urgence (Absence justifiée) le 19/02/2026. Arrivée: 19h00min - Départ: 18h00min. Commentaire: Jean Marie',0,'2026-02-19 11:37:38'),(105,8,7,'urgence','agent Dupont Jean a signalé une urgence (Absence justifiée) le 19/02/2026. Arrivée: 19h00min - Départ: 18h00min. Commentaire: Jean Marie',0,'2026-02-19 11:37:38'),(106,6,10,'arrivee','agent ALAO Ayouba est arrivé sur le site de ASIN le 19/02/2026 à 12h06min',0,'2026-02-19 12:06:00'),(107,6,10,'depart','agent ALAO Ayouba a quitté ASIN à 13:28',0,'2026-02-19 12:28:29'),(108,6,10,'urgence','agent ALAO Ayouba a signalé une urgence (Congé maladie) le 19/02/2026.  - Départ: 12h09min. Commentaire: En tout cas',0,'2026-02-19 12:29:28'),(109,6,8,'arrivee','superviseur Martin Pierre est arrivé sur le site de Gbégamey le 19/02/2026 à 12h33min',0,'2026-02-19 12:33:33'),(110,8,8,'arrivee','superviseur Martin Pierre est arrivé sur le site de Gbégamey le 19/02/2026 à 12h33min',0,'2026-02-19 12:33:33'),(111,6,8,'depart','superviseur Martin Pierre a quitté le site de Gbégamey à 13:41',0,'2026-02-19 12:41:48'),(112,8,8,'depart','superviseur Martin Pierre a quitté le site de Gbégamey à 13:41',0,'2026-02-19 12:41:48'),(113,6,8,'urgence','🚨 URGENCE : Martin Pierre (Congé personnel)',0,'2026-02-19 12:42:19'),(114,8,8,'urgence','🚨 URGENCE : Martin Pierre (Congé personnel)',0,'2026-02-19 12:42:19'),(115,6,9,'arrivee','agent ALLAGBE Sylvanus est arrivé sur le site de Calavi le 19/02/2026 à 13:49',0,'2026-02-19 12:49:08'),(116,11,9,'arrivee','agent ALLAGBE Sylvanus est arrivé sur le site de Calavi le 19/02/2026 à 13:49',1,'2026-02-19 12:49:08'),(117,6,9,'urgence','🚨 URGENCE : ALLAGBE Sylvanus (Congé maladie - Paludisme)',0,'2026-02-19 12:49:52'),(118,11,9,'urgence','🚨 URGENCE : ALLAGBE Sylvanus (Congé maladie - Paludisme)',1,'2026-02-19 12:49:52'),(119,6,9,'depart','agent ALLAGBE Sylvanus a quitté le site de Calavi à 15:54',0,'2026-02-19 14:54:47'),(120,11,9,'depart','agent ALLAGBE Sylvanus a quitté le site de Calavi à 15:54',0,'2026-02-19 14:54:47'),(121,6,11,'arrivee','superviseur GOGAN ARISTIDE est arrivé sur le site de Calavi le 20/02/2026 à 15:17',0,'2026-02-20 14:17:42'),(122,11,11,'arrivee','superviseur GOGAN ARISTIDE est arrivé sur le site de Calavi le 20/02/2026 à 15:17',0,'2026-02-20 14:17:42'),(123,6,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 21/02/2026 à 18:16',0,'2026-02-21 17:16:47'),(124,8,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 21/02/2026 à 18:16',0,'2026-02-21 17:16:47'),(125,6,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 22/02/2026 à 01:13',0,'2026-02-22 00:13:54'),(126,8,7,'arrivee','agent Dupont Jean est arrivé sur le site de Gbégamey le 22/02/2026 à 01:13',0,'2026-02-22 00:13:54'),(127,6,7,'urgence','🚨 URGENCE : Dupont Jean (Télétravail - Mon enfant est malade)',0,'2026-02-22 00:16:03'),(128,8,7,'urgence','🚨 URGENCE : Dupont Jean (Télétravail - Mon enfant est malade)',0,'2026-02-22 00:16:03'),(129,6,7,'depart','agent Dupont Jean a quitté le site de Gbégamey à 01:16',0,'2026-02-22 00:16:34'),(130,8,7,'depart','agent Dupont Jean a quitté le site de Gbégamey à 01:16',0,'2026-02-22 00:16:34');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pointages`
--

DROP TABLE IF EXISTS `pointages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pointages` (
  `id_pointage` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `date_pointage` date NOT NULL,
  `heure_arrivee` time DEFAULT NULL,
  `heure_depart` time DEFAULT NULL,
  `type` enum('NORMAL','URGENCE') COLLATE utf8mb4_general_ci DEFAULT 'NORMAL',
  `motif_urgence` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_site` int DEFAULT NULL,
  PRIMARY KEY (`id_pointage`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `pointages_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pointages`
--

LOCK TABLES `pointages` WRITE;
/*!40000 ALTER TABLE `pointages` DISABLE KEYS */;
INSERT INTO `pointages` VALUES (1,6,'2026-02-14','15:36:44','15:37:25','NORMAL',NULL,'2026-02-14 15:36:44',NULL),(2,7,'2026-02-15','08:00:00','18:00:00','URGENCE','Absence justifiée - Urgence familiale','2026-02-15 12:05:38',1),(3,10,'2026-02-15','13:15:00','13:14:00','URGENCE','Réunion externe - Réuion au poste','2026-02-15 12:13:32',6),(4,9,'2026-02-15','12:32:42','12:36:46','NORMAL',NULL,'2026-02-15 12:32:42',2),(5,8,'2026-02-15','12:43:34','12:43:37','NORMAL',NULL,'2026-02-15 12:43:34',1),(6,7,'2026-02-15','13:11:00',NULL,'NORMAL',NULL,'2026-02-15 13:11:00',1),(7,7,'2026-02-15',NULL,'13:11:04','NORMAL',NULL,'2026-02-15 13:11:04',1),(8,10,'2026-02-15','13:13:26','13:13:28','NORMAL',NULL,'2026-02-15 13:13:26',6),(9,11,'2026-02-15','23:06:26','23:11:09','NORMAL',NULL,'2026-02-15 23:06:26',2),(10,11,'2026-02-15','00:10:00','00:09:00','URGENCE','Autre - Course perso','2026-02-15 23:10:11',2),(11,10,'2026-02-16','20:18:12','20:33:34','NORMAL',NULL,'2026-02-16 20:18:12',6),(12,10,'2026-02-16','23:40:00','21:00:00','URGENCE','Congé maladie - Paludisme','2026-02-16 22:41:30',6),(13,7,'2026-02-16','22:55:20','22:58:54','NORMAL',NULL,'2026-02-16 22:55:20',1),(14,7,'2026-02-16','23:56:00','23:57:00','URGENCE','Autre - EXCUSE','2026-02-16 22:56:27',1),(15,7,'2026-02-16','18:00:00','08:00:00','URGENCE','Absence justifiée - JYUFTCG','2026-02-16 23:03:30',1),(16,9,'2026-02-16','23:27:46','23:28:36','NORMAL',NULL,'2026-02-16 23:27:46',2),(17,11,'2026-02-17','00:00:25','00:22:16','NORMAL',NULL,'2026-02-17 00:00:26',2),(18,11,'2026-02-17','02:09:00','01:09:00','URGENCE','Congé maladie - ouverture','2026-02-17 00:17:18',2),(19,7,'2026-02-17','20:25:21','21:55:00','NORMAL',NULL,'2026-02-17 20:25:21',1),(20,7,'2026-02-18','08:02:21','08:09:09','NORMAL',NULL,'2026-02-18 08:02:21',1),(21,10,'2026-02-18','08:39:15','15:54:12','NORMAL',NULL,'2026-02-18 08:39:15',6),(22,9,'2026-02-18','16:10:43','16:17:06','NORMAL',NULL,'2026-02-18 16:10:43',2),(23,11,'2026-02-18','16:18:54','16:26:07','NORMAL',NULL,'2026-02-18 16:18:54',2),(24,8,'2026-02-18','16:31:50',NULL,'NORMAL',NULL,'2026-02-18 16:31:50',1),(25,7,'2026-02-18','18:00:00','16:00:00','URGENCE','Congé personnel','2026-02-18 17:23:34',1),(26,7,'2026-02-18','18:00:00','10:08:00','URGENCE','Congé personnel','2026-02-18 21:08:07',1),(27,9,'2026-02-18','17:00:00','14:00:00','URGENCE','Absence justifiée','2026-02-18 21:09:26',2),(28,8,'2026-02-18','16:00:00','10:00:00','URGENCE','Congé maladie - Un peu souffrant','2026-02-18 21:40:13',1),(29,11,'2026-02-18','20:00:00','18:00:00','URGENCE','Absence justifiée - lu','2026-02-18 21:48:23',2),(30,11,'2026-02-18','18:18:00','10:10:00','URGENCE','Congé maladie - loupe','2026-02-18 22:11:30',2),(31,10,'2026-02-18','23:00:00','19:00:00','URGENCE','Absence justifiée - KRUBK','2026-02-18 22:16:54',6),(32,9,'2026-02-18','18:09:00','08:07:00','URGENCE','Congé maladie - Olatundji','2026-02-18 23:11:53',2),(33,9,'2026-02-18','19:00:00','01:09:00','URGENCE','Congé personnel - Compte rendu','2026-02-18 23:24:58',2),(34,7,'2026-02-19','00:01:55','00:04:31','NORMAL',NULL,'2026-02-19 00:01:55',1),(35,11,'2026-02-19','00:03:22','01:03:39','NORMAL',NULL,'2026-02-19 00:03:22',2),(36,7,'2026-02-19','19:00:00','18:00:00','URGENCE','Absence justifiée - Jean Marie','2026-02-19 11:37:38',1),(37,10,'2026-02-19','12:05:59','13:28:29','NORMAL',NULL,'2026-02-19 12:05:59',6),(38,10,'2026-02-19',NULL,'12:09:00','URGENCE','Congé maladie - En tout cas','2026-02-19 12:29:28',6),(39,8,'2026-02-19','12:33:33','13:41:48','NORMAL',NULL,'2026-02-19 12:33:33',1),(40,8,'2026-02-19','20:00:00','10:00:00','URGENCE','Congé personnel - Bien vu','2026-02-19 12:42:19',1),(41,9,'2026-02-19','13:49:08','15:54:46','NORMAL',NULL,'2026-02-19 12:49:08',2),(42,9,'2026-02-19','18:00:00','13:00:00','URGENCE','Congé maladie - Paludisme','2026-02-19 12:49:52',2),(43,11,'2026-02-20','15:17:41',NULL,'NORMAL',NULL,'2026-02-20 14:17:41',2),(44,7,'2026-02-21','18:16:47',NULL,'NORMAL',NULL,'2026-02-21 17:16:47',1),(45,7,'2026-02-22','01:13:54','01:16:33','NORMAL',NULL,'2026-02-22 00:13:54',1),(46,7,'2026-02-22',NULL,'01:15:00','URGENCE','Télétravail - Mon enfant est malade','2026-02-22 00:16:03',1);
/*!40000 ALTER TABLE `pointages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `postes`
--

DROP TABLE IF EXISTS `postes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `postes` (
  `id_poste` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `id_site` int NOT NULL,
  PRIMARY KEY (`id_poste`),
  KEY `fk_poste_site` (`id_site`),
  CONSTRAINT `fk_poste_site` FOREIGN KEY (`id_site`) REFERENCES `sites` (`id_site`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `postes`
--

LOCK TABLES `postes` WRITE;
/*!40000 ALTER TABLE `postes` DISABLE KEYS */;
INSERT INTO `postes` VALUES (1,'cheminé','bien siégé',1),(2,'Escalié',NULL,1),(3,'marche',NULL,1),(5,'essca',NULL,1),(7,'OLE',NULL,1),(8,'Chimel',NULL,2),(10,'paudium','Nouveau poste',2),(12,'Acceuil','Nouveau poste',2),(15,'Bas esca','Nouveau poste',2);
/*!40000 ALTER TABLE `postes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produits` (
  `id_produit` int NOT NULL AUTO_INCREMENT,
  `nom_produit` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `quantite_actuelle` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `quantite_alerte` int DEFAULT NULL,
  `id_site` int DEFAULT NULL,
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `nom_produit` (`nom_produit`),
  KEY `id_site` (`id_site`),
  CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_site`) REFERENCES `sites` (`id_site`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
INSERT INTO `produits` VALUES (5,'Serviettes',45,'2026-02-13 22:49:12',5,1),(6,'Savon',40,'2026-02-13 22:49:12',5,1),(7,'Papier toilette',102,'2026-02-13 22:49:12',5,1),(8,'Javel',4,'2026-02-15 03:05:20',5,1),(9,'Désodorisant',5,'2026-02-15 03:07:09',5,2),(10,'Gants',8,'2026-02-19 03:50:58',5,2);
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits_admin`
--

DROP TABLE IF EXISTS `produits_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produits_admin` (
  `id_produit_admin` int NOT NULL AUTO_INCREMENT,
  `nom_produit` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantite_globale` int DEFAULT '0',
  `seuil_alerte` int DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produit_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits_admin`
--

LOCK TABLES `produits_admin` WRITE;
/*!40000 ALTER TABLE `produits_admin` DISABLE KEYS */;
INSERT INTO `produits_admin` VALUES (1,'Gants',5,5,'2026-02-19 20:46:59'),(2,'Cartouche',-12,5,'2026-02-19 21:39:09'),(3,'cole',28,5,'2026-02-19 21:45:22'),(4,'cole',4,5,'2026-02-19 21:46:00'),(5,'cole',0,5,'2026-02-19 21:46:36'),(6,'cole',0,5,'2026-02-19 21:47:21'),(7,'Torche',12,5,'2026-02-20 01:41:39'),(8,'Couche',152,5,'2026-02-20 02:45:03');
/*!40000 ALTER TABLE `produits_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programmations`
--

DROP TABLE IF EXISTS `programmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programmations` (
  `id_programmation` int NOT NULL AUTO_INCREMENT,
  `id_agent` int NOT NULL,
  `id_site` int NOT NULL,
  `id_poste` int NOT NULL,
  `id_semaine` int NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `id_superviseur` int DEFAULT NULL,
  `date_planning` date NOT NULL,
  PRIMARY KEY (`id_programmation`),
  KEY `fk_prog_semaine` (`id_semaine`),
  KEY `fk_prog_agent` (`id_agent`),
  KEY `fk_prog_site` (`id_site`),
  KEY `fk_prog_poste` (`id_poste`),
  KEY `idx_prog_jour` (`jour`),
  KEY `fk_prog_superviseur` (`id_superviseur`),
  CONSTRAINT `fk_prog_agent` FOREIGN KEY (`id_agent`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_poste` FOREIGN KEY (`id_poste`) REFERENCES `postes` (`id_poste`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_semaine` FOREIGN KEY (`id_semaine`) REFERENCES `semaines` (`id_semaine`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_site` FOREIGN KEY (`id_site`) REFERENCES `sites` (`id_site`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_superviseur` FOREIGN KEY (`id_superviseur`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programmations`
--

LOCK TABLES `programmations` WRITE;
/*!40000 ALTER TABLE `programmations` DISABLE KEYS */;
INSERT INTO `programmations` VALUES (11,7,1,1,1,'Mardi','2026-02-17 05:34:19','08:00:00','18:00:00',8,'2026-02-17'),(12,7,1,1,1,'Vendredi','2026-02-17 05:34:19','08:00:00','18:00:00',8,'2026-02-17'),(13,9,2,8,3,'Lundi','2026-02-17 05:35:26','08:00:00','18:00:00',11,'2026-02-17'),(14,9,2,8,3,'Mercredi','2026-02-17 05:35:26','08:00:00','18:00:00',11,'2026-02-17'),(20,9,2,8,4,'Lundi','2026-02-19 23:18:57','08:00:00','18:00:00',11,'2026-02-23'),(21,7,1,1,6,'Lundi','2026-02-22 01:18:49','08:00:00','18:00:00',8,'2026-02-23'),(22,7,1,2,6,'Mardi','2026-02-22 01:19:13','08:00:00','18:00:00',8,'2026-02-24'),(23,7,1,3,6,'Mercredi','2026-02-22 01:19:24','08:00:00','18:00:00',8,'2026-02-25'),(25,7,1,7,6,'Vendredi','2026-02-22 01:19:49','08:00:00','18:00:00',8,'2026-02-27');
/*!40000 ALTER TABLE `programmations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semaines`
--

DROP TABLE IF EXISTS `semaines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semaines` (
  `id_semaine` int NOT NULL AUTO_INCREMENT,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `id_site` int NOT NULL,
  PRIMARY KEY (`id_semaine`),
  UNIQUE KEY `date_debut` (`date_debut`,`id_site`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semaines`
--

LOCK TABLES `semaines` WRITE;
/*!40000 ALTER TABLE `semaines` DISABLE KEYS */;
INSERT INTO `semaines` VALUES (1,'2026-02-16','2026-02-22',1),(2,'2026-02-16','2026-02-22',2),(3,'2026-02-17','2026-02-23',2),(4,'2026-02-23','2026-03-01',2),(5,'2026-03-02','2026-03-08',2),(6,'2026-02-23','2026-03-01',1);
/*!40000 ALTER TABLE `semaines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sites` (
  `id_site` int NOT NULL AUTO_INCREMENT,
  `nom_site` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `localisation` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_site`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,'Gbégamey','Cotonou','bien siégé'),(2,'Calavi',NULL,NULL),(3,'Parakou',NULL,NULL),(4,'Pehunco',NULL,NULL),(6,'ASIN','Cotonou','Société de renom'),(7,'ONG TECHNOSERVE',NULL,NULL),(8,'JJ TELECOM',NULL,NULL),(10,'Olaprod-digit',NULL,NULL),(11,'Marché Cadjehoun','Cotonou','Marché Public'),(12,'NMS','Cotonou,BENIN','Société de nettoyage'),(13,'PRÉSIDENCE',NULL,NULL);
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('ADMIN','SUPERVISEUR','AGENT') COLLATE utf8mb4_general_ci NOT NULL,
  `id_site` int DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cv` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `contact` int DEFAULT NULL,
  `username` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (6,'ALLAGBE','Ferdinand','allagbesylvanus1@gmail.com','$2y$10$VgzbOipdmL8fbhGMn4RthuhH3RTQ27qP97KNsrffIywiJEymVTqFK','ADMIN',NULL,'2024-01-01',NULL,NULL,'2026-02-14 15:20:11',600000000,'admin',1),(7,'Dupont','Jean','agent@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','AGENT',1,'2024-06-01',NULL,NULL,'2026-02-14 15:20:26',687654321,'agent',1),(8,'Martin','Pierre','supervisor@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','SUPERVISEUR',1,'2024-06-01',NULL,NULL,'2026-02-14 15:23:44',612345678,'supervisor',1),(9,'ALLAGBE','Sylvanus','sylva@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','AGENT',2,'2022-06-01','/var/www/html/nms-planning/assets/images/dimanche.png',NULL,'2026-02-14 21:23:48',141545298,'sylva',0),(10,'ALAO','Ayouba','ayou@nms.com','$2y$10$FyibMWXP0V8ccG.CLW7vput.90MM2e9FBjfL26CfYYJsRH7vIiLqC','AGENT',6,'2026-02-06','uploads/photos/1771149234_6423564e4bc42117.png','uploads/cv/1771149234_d1a343bf175c5b46.pdf','2026-02-15 09:53:54',NULL,'ayoub',1),(11,'GOGAN','ARISTIDE','ari@nms.com','$2y$10$M2IPmR9LFBL/Nu15PaOtIOpSOtBaPQ2nr7/kkubzn5xTxWka3vSgi','SUPERVISEUR',2,'2019-11-08','uploads/photos/1771195649_48ea5d716545e62c.png','uploads/cv/1771195649_a4ae8baa065c492a.pdf','2026-02-15 22:47:29',NULL,'aristide',1),(12,'ALLAGBE','Olatundji','allagbesylvanus0@gmail.com','$2y$10$cZfONaSbjmvjR.d1q6HlLOaSgpP9k363Ue2kAyCjGFfj0hs4VxLUy','AGENT',1,'2026-02-03','uploads/photos/1771721376_b9b2d66bb16fd5db.jpg','uploads/cv/1771721376_d1be9d479b01cdef.pdf','2026-02-22 00:49:37',141545298,'Ola',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilisateurs` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin') COLLATE utf8mb4_general_ci DEFAULT 'admin',
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES (1,'Responsable','NMS','admin','$2y$10$3m0FyY5JkvQGjkeg5nEzUuLeOkz9MQa07Fe4kz3lVPU4FD./l.8x.','admin',1);
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-22 12:26:07
