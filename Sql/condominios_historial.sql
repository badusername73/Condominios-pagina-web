-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: condominios
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `historial`
--

DROP TABLE IF EXISTS `historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial` (
  `ID_Movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Condominio` int(11) DEFAULT NULL,
  `ID_Cuota` int(11) DEFAULT NULL,
  `ID_Pagos` int(11) DEFAULT NULL,
  `ID_FechaPago` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Movimiento`),
  KEY `ID_Condominio` (`ID_Condominio`),
  KEY `ID_Cuota` (`ID_Cuota`),
  KEY `ID_Pagos` (`ID_Pagos`),
  KEY `ID_FechaPago` (`ID_FechaPago`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`ID_Condominio`) REFERENCES `condominios` (`ID_Condominio`),
  CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`ID_Cuota`) REFERENCES `cuotas` (`ID_Cuota`),
  CONSTRAINT `historial_ibfk_3` FOREIGN KEY (`ID_Pagos`) REFERENCES `pagos` (`ID_Pagos`),
  CONSTRAINT `historial_ibfk_4` FOREIGN KEY (`ID_FechaPago`) REFERENCES `fecha_cuotas` (`ID_FechaPago`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial`
--

LOCK TABLES `historial` WRITE;
/*!40000 ALTER TABLE `historial` DISABLE KEYS */;
INSERT INTO `historial` VALUES (1,1,1,1,12),(2,2,1,2,12),(3,2,1,3,12),(4,2,1,4,12),(5,2,3,5,14),(6,3,7,6,NULL),(7,3,7,7,NULL),(8,3,7,8,NULL),(9,3,7,9,NULL),(10,1,6,10,NULL),(11,1,1,11,12),(12,2,6,12,NULL),(13,2,6,13,NULL);
/*!40000 ALTER TABLE `historial` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-31 18:19:35
