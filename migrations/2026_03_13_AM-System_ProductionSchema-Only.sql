-- MySQL dump 10.13  Distrib 8.0.36, for Linux (x86_64)
--
-- Host: localhost    Database: asservat
-- ------------------------------------------------------
-- Server version	8.0.36-0ubuntu0.22.04.1

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
-- Table structure for table `ams_Datentraeger`
--

DROP TABLE IF EXISTS `ams_Datentraeger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_Datentraeger` (
  `barcode_id` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `formfaktor` longtext COLLATE utf8mb3_unicode_ci,
  `bauart` longtext COLLATE utf8mb3_unicode_ci,
  `groesse` int DEFAULT NULL,
  `hersteller` longtext COLLATE utf8mb3_unicode_ci,
  `modell` longtext COLLATE utf8mb3_unicode_ci,
  `sn` longtext COLLATE utf8mb3_unicode_ci,
  `pn` longtext COLLATE utf8mb3_unicode_ci,
  `anschluss` longtext COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`barcode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_Fall`
--

DROP TABLE IF EXISTS `ams_Fall`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_Fall` (
  `beschreibung` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `zeitstempel_beginn` datetime NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `ist_aktiv` tinyint(1) NOT NULL DEFAULT '1',
  `case_id` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `zeitstempel_ende` datetime DEFAULT NULL,
  `dos` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1063 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_Historie_Objekt`
--

DROP TABLE IF EXISTS `ams_Historie_Objekt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_Historie_Objekt` (
  `barcode_id` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `zeitstempel` datetime NOT NULL,
  `nutzer_id` int NOT NULL,
  `reserviert_von` int DEFAULT NULL,
  `status_id` smallint NOT NULL,
  `verwendung` longtext COLLATE utf8mb3_unicode_ci,
  `zeitstempelderumsetzung` datetime NOT NULL,
  `Standort` varchar(9) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `historie_id` int NOT NULL AUTO_INCREMENT,
  `fall_id` int DEFAULT NULL,
  `systemaktion` tinyint(1) NOT NULL,
  PRIMARY KEY (`historie_id`),
  KEY `IDX_5ECC311F2D6287FB` (`nutzer_id`),
  KEY `IDX_5ECC311F1DDBE3D0` (`reserviert_von`),
  KEY `IDX_5ECC311F7DEEAE9` (`Standort`),
  KEY `FK_5ECC311F290B48B` (`fall_id`),
  CONSTRAINT `FK_5ECC311F1DDBE3D0` FOREIGN KEY (`reserviert_von`) REFERENCES `ams_Nutzer` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5ECC311F290B48B` FOREIGN KEY (`fall_id`) REFERENCES `ams_Fall` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5ECC311F2D6287FB` FOREIGN KEY (`nutzer_id`) REFERENCES `ams_Nutzer` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_5ECC311FFEA888BF` FOREIGN KEY (`Standort`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32771 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_Nutzer`
--

DROP TABLE IF EXISTS `ams_Nutzer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_Nutzer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(180) COLLATE utf8mb3_unicode_ci NOT NULL,
  `fullname` varchar(180) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb3_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `roles` longtext COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
  `language` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `notify_case_creation` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_981B3FFAF85E0677` (`username`),
  UNIQUE KEY `UNIQ_981B3FFAE7927C74` (`email`),
  UNIQUE KEY `UNIQ_981B3FFA91657DAE` (`fullname`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_Objekt`
--

DROP TABLE IF EXISTS `ams_Objekt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_Objekt` (
  `barcode_id` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `kategorie_id` smallint NOT NULL,
  `nutzer_id` int NOT NULL,
  `reserviert_von` int DEFAULT NULL,
  `status_id` smallint NOT NULL,
  `name` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `verwendung` longtext COLLATE utf8mb3_unicode_ci,
  `zeitstempel` datetime NOT NULL,
  `zeitstempelderumsetzung` datetime NOT NULL,
  `Standort` varchar(9) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `notiz` longtext COLLATE utf8mb3_unicode_ci,
  `fall_id` int DEFAULT NULL,
  `systemaktion` tinyint(1) NOT NULL,
  PRIMARY KEY (`barcode_id`),
  KEY `IDX_51DBEB972D6287FB` (`nutzer_id`),
  KEY `IDX_51DBEB971DDBE3D0` (`reserviert_von`),
  KEY `IDX_51DBEB977DEEAE9` (`Standort`),
  KEY `FK_51DBEB97290B48B` (`fall_id`),
  CONSTRAINT `FK_51DBEB971DDBE3D0` FOREIGN KEY (`reserviert_von`) REFERENCES `ams_Nutzer` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_51DBEB97290B48B` FOREIGN KEY (`fall_id`) REFERENCES `ams_Fall` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_51DBEB972D6287FB` FOREIGN KEY (`nutzer_id`) REFERENCES `ams_Nutzer` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_51DBEB97FEA888BF` FOREIGN KEY (`Standort`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_ObjektBlob`
--

DROP TABLE IF EXISTS `ams_ObjektBlob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_ObjektBlob` (
  `barcode_id` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `bild` longtext COLLATE utf8mb3_unicode_ci,
  `bild_pfad` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`barcode_id`),
  CONSTRAINT `FK_EBCD911829439E58` FOREIGN KEY (`barcode_id`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_ZuordnungImageToHDD`
--

DROP TABLE IF EXISTS `ams_ZuordnungImageToHDD`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_ZuordnungImageToHDD` (
  `image` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  `hdd` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`image`,`hdd`),
  KEY `IDX_859FDA9CC243A19E` (`image`),
  KEY `IDX_859FDA9CF2CB4868` (`hdd`),
  CONSTRAINT `FK_859FDA9CC243A19E` FOREIGN KEY (`image`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_859FDA9CF2CB4868` FOREIGN KEY (`hdd`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ams_image_objekt`
--

DROP TABLE IF EXISTS `ams_image_objekt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ams_image_objekt` (
  `historie_id` int NOT NULL,
  `Barcode_id` varchar(9) COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`historie_id`,`Barcode_id`),
  KEY `IDX_C9EAAD35779817B8` (`historie_id`),
  KEY `IDX_C9EAAD35661E9D88` (`Barcode_id`),
  CONSTRAINT `FK_C9EAAD35661E9D88` FOREIGN KEY (`Barcode_id`) REFERENCES `ams_Objekt` (`barcode_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `FK_C9EAAD35779817B8` FOREIGN KEY (`historie_id`) REFERENCES `ams_Historie_Objekt` (`historie_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-13 15:37:00
