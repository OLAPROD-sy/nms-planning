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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_stock`
--

LOCK TABLES `mouvements_stock` WRITE;
/*!40000 ALTER TABLE `mouvements_stock` DISABLE KEYS */;
INSERT INTO `mouvements_stock` VALUES (1,6,'ENTREE',2,'2026-02-15 02:08:07',NULL,8,1,'Pierre Martin'),(2,5,'SORTIE',5,'2026-02-15 02:08:59',NULL,8,1,'Pierre Martin'),(3,7,'ENTREE',2,'2026-02-15 02:09:16',NULL,8,1,'Pierre Martin'),(4,8,'ENTREE',12,'2026-02-15 22:35:15',NULL,8,1,'Pierre Martin'),(5,8,'SORTIE',6,'2026-02-15 22:35:51',NULL,8,1,'Pierre Martin'),(6,9,'ENTREE',23,'2026-02-15 22:55:03',NULL,11,2,'ARISTIDE GOGAN'),(7,9,'ENTREE',12,'2026-02-17 01:23:37',NULL,11,2,'ARISTIDE GOGAN');
/*!40000 ALTER TABLE `mouvements_stock` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (34,6,10,'arrivee','Arrivée enregistrée pour 2026-02-16 à 20:18',1,'2026-02-16 20:18:12'),(35,8,10,'arrivee','Arrivée enregistrée pour 2026-02-16 à 20:18',0,'2026-02-16 20:18:12'),(36,11,10,'arrivee','Arrivée enregistrée pour 2026-02-16 à 20:18',0,'2026-02-16 20:18:12'),(37,6,10,'arrivee','Départ enregistré pour 2026-02-16 à 20:33 - Durée: 0h 15min',1,'2026-02-16 20:33:34'),(38,8,10,'arrivee','Départ enregistré pour 2026-02-16 à 20:33 - Durée: 0h 15min',0,'2026-02-16 20:33:34'),(39,11,10,'arrivee','Départ enregistré pour 2026-02-16 à 20:33 - Durée: 0h 15min',0,'2026-02-16 20:33:34'),(40,6,10,'arrivee','Urgence signalée: Congé maladie (2026-02-16)',1,'2026-02-16 22:41:30'),(41,8,10,'arrivee','Urgence signalée: Congé maladie (2026-02-16)',0,'2026-02-16 22:41:30'),(42,11,10,'arrivee','Urgence signalée: Congé maladie (2026-02-16)',0,'2026-02-16 22:41:30'),(43,6,7,'arrivee','Arrivée enregistrée pour 2026-02-16 à 22:55',1,'2026-02-16 22:55:20'),(44,8,7,'arrivee','Arrivée enregistrée pour 2026-02-16 à 22:55',0,'2026-02-16 22:55:20'),(45,11,7,'arrivee','Arrivée enregistrée pour 2026-02-16 à 22:55',0,'2026-02-16 22:55:20'),(46,6,7,'arrivee','Urgence signalée: Autre (2026-02-16)',1,'2026-02-16 22:56:27'),(47,8,7,'arrivee','Urgence signalée: Autre (2026-02-16)',0,'2026-02-16 22:56:27'),(48,11,7,'arrivee','Urgence signalée: Autre (2026-02-16)',0,'2026-02-16 22:56:27'),(49,6,7,'arrivee','Départ enregistré pour 2026-02-16 à 22:58 - Durée: 0h 3min',1,'2026-02-16 22:58:56'),(50,8,7,'arrivee','Départ enregistré pour 2026-02-16 à 22:58 - Durée: 0h 3min',0,'2026-02-16 22:58:57'),(51,11,7,'arrivee','Départ enregistré pour 2026-02-16 à 22:58 - Durée: 0h 3min',0,'2026-02-16 22:58:57'),(52,6,7,'arrivee','Urgence signalée: Absence justifiée (2026-02-16)',1,'2026-02-16 23:03:30'),(53,8,7,'arrivee','Urgence signalée: Absence justifiée (2026-02-16)',0,'2026-02-16 23:03:30'),(54,11,7,'arrivee','Urgence signalée: Absence justifiée (2026-02-16)',0,'2026-02-16 23:03:30'),(55,6,9,'arrivee','Arrivée enregistrée pour 2026-02-16 à 23:27',1,'2026-02-16 23:27:46'),(56,8,9,'arrivee','Arrivée enregistrée pour 2026-02-16 à 23:27',0,'2026-02-16 23:27:46'),(57,11,9,'arrivee','Arrivée enregistrée pour 2026-02-16 à 23:27',0,'2026-02-16 23:27:46'),(58,6,9,'arrivee','Départ enregistré pour 2026-02-16 à 23:28 - Durée: 0h',1,'2026-02-16 23:28:36'),(59,8,9,'arrivee','Départ enregistré pour 2026-02-16 à 23:28 - Durée: 0h',0,'2026-02-16 23:28:36'),(60,11,9,'arrivee','Départ enregistré pour 2026-02-16 à 23:28 - Durée: 0h',1,'2026-02-16 23:28:36'),(61,6,11,'arrivee','Arrivée enregistrée pour 2026-02-17 à 00:00',1,'2026-02-17 00:00:26'),(62,8,11,'arrivee','Arrivée enregistrée pour 2026-02-17 à 00:00',0,'2026-02-17 00:00:26'),(63,11,11,'arrivee','Arrivée enregistrée pour 2026-02-17 à 00:00',0,'2026-02-17 00:00:26'),(64,6,11,'arrivee','Urgence signalée: Congé maladie (2026-02-17)',1,'2026-02-17 00:17:18'),(65,8,11,'arrivee','Urgence signalée: Congé maladie (2026-02-17)',0,'2026-02-17 00:17:18'),(66,11,11,'arrivee','Urgence signalée: Congé maladie (2026-02-17)',0,'2026-02-17 00:17:18'),(67,6,11,'arrivee','Départ enregistré pour 2026-02-17 à 00:22 - Durée: 0h 21min',1,'2026-02-17 00:22:16'),(68,8,11,'arrivee','Départ enregistré pour 2026-02-17 à 00:22 - Durée: 0h 21min',0,'2026-02-17 00:22:16'),(69,11,11,'arrivee','Départ enregistré pour 2026-02-17 à 00:22 - Durée: 0h 21min',0,'2026-02-17 00:22:16'),(70,6,7,'arrivee','Arrivée enregistrée pour 2026-02-17 à 20:25',1,'2026-02-17 20:25:21'),(71,8,7,'arrivee','Arrivée enregistrée pour 2026-02-17 à 20:25',0,'2026-02-17 20:25:21'),(72,11,7,'arrivee','Arrivée enregistrée pour 2026-02-17 à 20:25',0,'2026-02-17 20:25:21'),(73,6,7,'arrivee','Départ enregistré pour 2026-02-17 à 21:55 - Durée: 1h 29min',1,'2026-02-17 21:55:00'),(74,8,7,'arrivee','Départ enregistré pour 2026-02-17 à 21:55 - Durée: 1h 29min',0,'2026-02-17 21:55:00'),(75,11,7,'arrivee','Départ enregistré pour 2026-02-17 à 21:55 - Durée: 1h 29min',0,'2026-02-17 21:55:00'),(76,6,7,'arrivee','L\'agent Dupont Jean est arrivé sur le site de Gbégamey le 18/02/2026 à 08h02min',1,'2026-02-18 08:02:22'),(77,8,7,'arrivee','L\'agent Dupont Jean est arrivé sur le site de Gbégamey le 18/02/2026 à 08h02min',0,'2026-02-18 08:02:22'),(78,11,7,'arrivee','L\'agent Dupont Jean est arrivé sur le site de Gbégamey le 18/02/2026 à 08h02min',0,'2026-02-18 08:02:22'),(79,6,7,'arrivee','Le agent Dupont Jean a quitté le site de Gbégamey le 18/02/2026 à 08h09min donc il a travaillé 0h00.',1,'2026-02-18 08:09:09'),(80,8,7,'arrivee','Le agent Dupont Jean a quitté le site de Gbégamey le 18/02/2026 à 08h09min donc il a travaillé 0h00.',0,'2026-02-18 08:09:09'),(81,11,7,'arrivee','Le agent Dupont Jean a quitté le site de Gbégamey le 18/02/2026 à 08h09min donc il a travaillé 0h00.',0,'2026-02-18 08:09:09'),(85,6,8,'arrivee','L\'superviseur Martin Pierre est arrivé sur le site de Gbégamey le 18/02/2026 à 16h31min',0,'2026-02-18 16:31:50'),(86,8,8,'arrivee','L\'superviseur Martin Pierre est arrivé sur le site de Gbégamey le 18/02/2026 à 16h31min',0,'2026-02-18 16:31:50'),(87,11,8,'arrivee','L\'superviseur Martin Pierre est arrivé sur le site de Gbégamey le 18/02/2026 à 16h31min',0,'2026-02-18 16:31:50'),(88,6,7,'arrivee','agent Dupont Jean a signalé une urgence: Congé personnel (2026-02-18) à 18h00min - 16h00min. Commentaire: ',1,'2026-02-18 17:23:34'),(89,8,7,'arrivee','agent Dupont Jean a signalé une urgence: Congé personnel (2026-02-18) à 18h00min - 16h00min. Commentaire: ',0,'2026-02-18 17:23:34'),(90,11,7,'arrivee','agent Dupont Jean a signalé une urgence: Congé personnel (2026-02-18) à 18h00min - 16h00min. Commentaire: ',0,'2026-02-18 17:23:34');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pointages`
--

LOCK TABLES `pointages` WRITE;
/*!40000 ALTER TABLE `pointages` DISABLE KEYS */;
INSERT INTO `pointages` VALUES (1,6,'2026-02-14','15:36:44','15:37:25','NORMAL',NULL,'2026-02-14 15:36:44',NULL),(2,7,'2026-02-15','08:00:00','18:00:00','URGENCE','Absence justifiée - Urgence familiale','2026-02-15 12:05:38',1),(3,10,'2026-02-15','13:15:00','13:14:00','URGENCE','Réunion externe - Réuion au poste','2026-02-15 12:13:32',6),(4,9,'2026-02-15','12:32:42','12:36:46','NORMAL',NULL,'2026-02-15 12:32:42',2),(5,8,'2026-02-15','12:43:34','12:43:37','NORMAL',NULL,'2026-02-15 12:43:34',1),(6,7,'2026-02-15','13:11:00',NULL,'NORMAL',NULL,'2026-02-15 13:11:00',1),(7,7,'2026-02-15',NULL,'13:11:04','NORMAL',NULL,'2026-02-15 13:11:04',1),(8,10,'2026-02-15','13:13:26','13:13:28','NORMAL',NULL,'2026-02-15 13:13:26',6),(9,11,'2026-02-15','23:06:26','23:11:09','NORMAL',NULL,'2026-02-15 23:06:26',2),(10,11,'2026-02-15','00:10:00','00:09:00','URGENCE','Autre - Course perso','2026-02-15 23:10:11',2),(11,10,'2026-02-16','20:18:12','20:33:34','NORMAL',NULL,'2026-02-16 20:18:12',6),(12,10,'2026-02-16','23:40:00','21:00:00','URGENCE','Congé maladie - Paludisme','2026-02-16 22:41:30',6),(13,7,'2026-02-16','22:55:20','22:58:54','NORMAL',NULL,'2026-02-16 22:55:20',1),(14,7,'2026-02-16','23:56:00','23:57:00','URGENCE','Autre - EXCUSE','2026-02-16 22:56:27',1),(15,7,'2026-02-16','18:00:00','08:00:00','URGENCE','Absence justifiée - JYUFTCG','2026-02-16 23:03:30',1),(16,9,'2026-02-16','23:27:46','23:28:36','NORMAL',NULL,'2026-02-16 23:27:46',2),(17,11,'2026-02-17','00:00:25','00:22:16','NORMAL',NULL,'2026-02-17 00:00:26',2),(18,11,'2026-02-17','02:09:00','01:09:00','URGENCE','Congé maladie - ouverture','2026-02-17 00:17:18',2),(19,7,'2026-02-17','20:25:21','21:55:00','NORMAL',NULL,'2026-02-17 20:25:21',1),(20,7,'2026-02-18','08:02:21','08:09:09','NORMAL',NULL,'2026-02-18 08:02:21',1),(21,10,'2026-02-18','08:39:15','15:54:12','NORMAL',NULL,'2026-02-18 08:39:15',6),(22,9,'2026-02-18','16:10:43','16:17:06','NORMAL',NULL,'2026-02-18 16:10:43',2),(23,11,'2026-02-18','16:18:54','16:26:07','NORMAL',NULL,'2026-02-18 16:18:54',2),(24,8,'2026-02-18','16:31:50',NULL,'NORMAL',NULL,'2026-02-18 16:31:50',1),(25,7,'2026-02-18','18:00:00','16:00:00','URGENCE','Congé personnel','2026-02-18 17:23:34',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `postes`
--

LOCK TABLES `postes` WRITE;
/*!40000 ALTER TABLE `postes` DISABLE KEYS */;
INSERT INTO `postes` VALUES (1,'cheminé','bien siégé',1),(2,'Escalié',NULL,1),(3,'marche',NULL,1),(4,'essca',NULL,1),(5,'essca',NULL,1),(6,'essca',NULL,1),(7,'OLE',NULL,1),(8,'Chimel',NULL,2),(9,'Chimel',NULL,2),(10,'paudium','Nouveau poste',2),(11,'cheminé','Nouveau poste',2);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
INSERT INTO `produits` VALUES (5,'Serviettes',45,'2026-02-13 22:49:12',5,1),(6,'Savon',32,'2026-02-13 22:49:12',5,1),(7,'Papier toilette',102,'2026-02-13 22:49:12',5,1),(8,'Javel',10,'2026-02-15 03:05:20',5,1),(9,'Désodorisant',43,'2026-02-15 03:07:09',5,2);
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programmations`
--

LOCK TABLES `programmations` WRITE;
/*!40000 ALTER TABLE `programmations` DISABLE KEYS */;
INSERT INTO `programmations` VALUES (11,7,1,1,1,'Mardi','2026-02-17 05:34:19','08:00:00','18:00:00',8,'2026-02-17'),(12,7,1,1,1,'Vendredi','2026-02-17 05:34:19','08:00:00','18:00:00',8,'2026-02-17'),(13,9,2,8,3,'Lundi','2026-02-17 05:35:26','08:00:00','18:00:00',11,'2026-02-17'),(14,9,2,8,3,'Mercredi','2026-02-17 05:35:26','08:00:00','18:00:00',11,'2026-02-17');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semaines`
--

LOCK TABLES `semaines` WRITE;
/*!40000 ALTER TABLE `semaines` DISABLE KEYS */;
INSERT INTO `semaines` VALUES (1,'2026-02-16','2026-02-22',1),(2,'2026-02-16','2026-02-22',2),(3,'2026-02-17','2026-02-23',2);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,'Gbégamey','Cotonou','bien siégé'),(2,'Calavi',NULL,NULL),(3,'Parakou',NULL,NULL),(4,'Pehunco',NULL,NULL),(5,'OUSSA',NULL,NULL),(6,'ASIN',NULL,NULL),(7,'ONG TECHNOSERVE',NULL,NULL),(8,'JJ TELECOM',NULL,NULL),(9,'Olaprid',NULL,NULL),(10,'Olaprod-digit',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (6,'Admin','Système','admin@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ADMIN',NULL,'2024-01-01',NULL,NULL,'2026-02-14 15:20:11',600000000,'admin',1),(7,'Dupont','Jean','agent@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','AGENT',1,'2024-06-01',NULL,NULL,'2026-02-14 15:20:26',687654321,'agent',1),(8,'Martin','Pierre','supervisor@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','SUPERVISEUR',1,'2024-06-01',NULL,NULL,'2026-02-14 15:23:44',612345678,'supervisor',1),(9,'ALLAGBE','Sylvanus','sylva@nms.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','AGENT',2,'2022-06-01','/var/www/html/nms-planning/assets/images/dimanche.png',NULL,'2026-02-14 21:23:48',141545298,'sylva',1),(10,'ALAO','Ayouba','ayou@nms.com','$2y$10$FyibMWXP0V8ccG.CLW7vput.90MM2e9FBjfL26CfYYJsRH7vIiLqC','AGENT',6,'2026-02-06','uploads/photos/1771149234_6423564e4bc42117.png','uploads/cv/1771149234_d1a343bf175c5b46.pdf','2026-02-15 09:53:54',NULL,'ayoub',1),(11,'GOGAN','ARISTIDE','ari@nms.com','$2y$10$M2IPmR9LFBL/Nu15PaOtIOpSOtBaPQ2nr7/kkubzn5xTxWka3vSgi','SUPERVISEUR',2,'2019-11-08','uploads/photos/1771195649_48ea5d716545e62c.png','uploads/cv/1771195649_a4ae8baa065c492a.pdf','2026-02-15 22:47:29',NULL,'aristide',1);
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

-- Dump completed on 2026-02-18 20:56:16
