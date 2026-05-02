-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: localhost    Database: credinor2
-- ------------------------------------------------------
-- Server version	8.4.7

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
-- Current Database: `credinor2`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `credinor2` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `credinor2`;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria` (
  `id_log` bigint NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ej: pago.create, credito.anular, login.fail',
  `entidad` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int DEFAULT NULL,
  `datos_antes` json DEFAULT NULL,
  `datos_despues` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  KEY `idx_auditoria_accion` (`accion`),
  KEY `idx_auditoria_created` (`created_at`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria`
--

LOCK TABLES `auditoria` WRITE;
/*!40000 ALTER TABLE `auditoria` DISABLE KEYS */;
INSERT INTO `auditoria` VALUES (1,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 13:58:48'),(2,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 15:07:41'),(3,NULL,'login.fail','usuarios',1,NULL,'{\"reason\": \"invalid_password\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 18:12:13'),(4,NULL,'login.fail',NULL,NULL,NULL,'{\"reason\": \"user_not_found\", \"username\": \"danqueve\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 19:32:07'),(5,NULL,'login.fail','usuarios',1,NULL,'{\"reason\": \"invalid_password\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 19:32:11'),(6,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-29 19:38:52'),(7,NULL,'login.fail',NULL,NULL,NULL,'{\"reason\": \"user_not_found\", \"username\": \"danqueve\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 15:54:12'),(8,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 15:55:22'),(9,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 16:32:31'),(10,1,'credito.create','creditos',1,NULL,'{\"codigo\": \"CR-2026-00001\", \"capital\": 10000, \"frecuencia\": \"diaria\", \"id_cliente\": 1, \"monto_total\": 120000, \"cantidad_cuotas\": 10}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 16:48:13'),(11,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 17:04:46'),(12,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 21:10:43'),(13,1,'pago.create','pagos',1,NULL,'{\"monto\": 30000, \"forma_pago\": \"efectivo\", \"id_credito\": 1, \"numero_recibo\": \"R-2026-00001\", \"fecha_pago_real\": \"2026-04-30\", \"cuotas_afectadas\": 3}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 21:36:24'),(14,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-30 21:42:18'),(15,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 15:14:58'),(16,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 16:15:30'),(17,1,'login.success','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 16:50:21'),(18,1,'crear_usuario','usuarios',3,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 16:50:40'),(19,1,'logout','usuarios',1,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 16:54:54'),(20,3,'login.success','usuarios',3,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 16:54:56'),(21,3,'login.success','usuarios',3,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 17:27:51'),(22,3,'login.success','usuarios',3,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-02 18:13:05');
/*!40000 ALTER TABLE `auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dni` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `barrio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordenadas_gps` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencias` text COLLATE utf8mb4_unicode_ci,
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_zona` int DEFAULT NULL,
  `referencia_domicilio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `telefono_principal` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `telefono_alternativo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score_interno` tinyint NOT NULL DEFAULT '3' COMMENT '1=muy malo, 5=excelente',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uq_clientes_dni` (`dni`),
  KEY `idx_clientes_apellido` (`apellido`),
  KEY `fk_cliente_zona` (`id_zona`),
  CONSTRAINT `fk_cliente_zona` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Abraham Erika Silvina','','40216602','Sáenz Peña 707 Manantial','','3812478060','','',NULL,1,NULL,NULL,NULL,'',NULL,3,NULL,'2026-04-30 15:59:53','2026-04-30 15:59:53',NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comisiones`
--

DROP TABLE IF EXISTS `comisiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comisiones` (
  `id_comision` int NOT NULL AUTO_INCREMENT,
  `id_personal` int NOT NULL,
  `periodo` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ej: 2026-04',
  `tipo` enum('venta','cobranza') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto_base` decimal(12,2) NOT NULL,
  `pct` decimal(5,2) NOT NULL,
  `monto_comision` decimal(12,2) NOT NULL,
  `pagada` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_comision`),
  KEY `idx_comisiones_personal` (`id_personal`),
  KEY `idx_comisiones_periodo` (`periodo`),
  CONSTRAINT `fk_comision_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comisiones`
--

LOCK TABLES `comisiones` WRITE;
/*!40000 ALTER TABLE `comisiones` DISABLE KEYS */;
/*!40000 ALTER TABLE `comisiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creditos`
--

DROP TABLE IF EXISTS `creditos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creditos` (
  `id_credito` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cliente` int NOT NULL,
  `id_vendedor` int DEFAULT NULL,
  `id_cobrador` int DEFAULT NULL,
  `capital` decimal(12,2) NOT NULL COMMENT 'Monto prestado en mano (input)',
  `cantidad_cuotas` smallint NOT NULL,
  `valor_cuota` decimal(12,2) NOT NULL COMMENT 'Valor de cada cuota (input)',
  `monto_total` decimal(12,2) NOT NULL COMMENT 'Calculado: cantidad_cuotas × valor_cuota',
  `interes_implicito` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'monto_total - capital - gastos_admin',
  `interes_implicito_pct` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT 'Porcentaje sobre capital',
  `gastos_admin` decimal(12,2) NOT NULL DEFAULT '0.00',
  `frecuencia` enum('diaria','semanal','quincenal','mensual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin_estimada` date DEFAULT NULL,
  `saldo_pendiente` decimal(12,2) NOT NULL DEFAULT '0.00',
  `destino_opcional` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','finalizado','anulado','refinanciado','incobrable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `id_credito_origen` int DEFAULT NULL COMMENT 'Si viene de una refinanciación',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_credito`),
  UNIQUE KEY `uq_creditos_codigo` (`codigo`),
  KEY `idx_creditos_cliente` (`id_cliente`),
  KEY `idx_creditos_estado` (`estado`),
  KEY `idx_creditos_cobrador` (`id_cobrador`),
  KEY `fk_credito_vendedor` (`id_vendedor`),
  KEY `fk_credito_creado_por` (`created_by`),
  KEY `fk_credito_updated_by` (`updated_by`),
  KEY `fk_credito_origen` (`id_credito_origen`),
  CONSTRAINT `fk_credito_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_credito_cobrador` FOREIGN KEY (`id_cobrador`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_credito_creado_por` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_credito_origen` FOREIGN KEY (`id_credito_origen`) REFERENCES `creditos` (`id_credito`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_credito_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_credito_vendedor` FOREIGN KEY (`id_vendedor`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creditos`
--

LOCK TABLES `creditos` WRITE;
/*!40000 ALTER TABLE `creditos` DISABLE KEYS */;
INSERT INTO `creditos` VALUES (1,'CR-2026-00001',1,1,1,10000.00,10,12000.00,120000.00,110000.00,1100.00,0.00,'diaria','2026-04-30','2026-05-11',90000.00,NULL,'activo',NULL,NULL,1,NULL,'2026-04-30 16:48:13','2026-04-30 21:36:19',NULL);
/*!40000 ALTER TABLE `creditos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuotas`
--

DROP TABLE IF EXISTS `cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuotas` (
  `id_cuota` int NOT NULL AUTO_INCREMENT,
  `id_credito` int NOT NULL,
  `numero_cuota` smallint NOT NULL COMMENT 'Nro de cuota: 1, 2, 3...',
  `fecha_vencimiento` date NOT NULL,
  `monto_esperado` decimal(12,2) NOT NULL COMMENT '= valor_cuota del crédito',
  `monto_pagado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `monto_recargo` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Siempre 0 mientras mora.habilitada=false',
  `estado` enum('pendiente','parcial','pagada','vencida','condonada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_pagada` date DEFAULT NULL COMMENT 'Última fecha de pago aplicada',
  PRIMARY KEY (`id_cuota`),
  KEY `idx_cuotas_credito` (`id_credito`),
  KEY `idx_cuotas_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cuotas_estado` (`estado`),
  CONSTRAINT `fk_cuota_credito` FOREIGN KEY (`id_credito`) REFERENCES `creditos` (`id_credito`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuotas`
--

LOCK TABLES `cuotas` WRITE;
/*!40000 ALTER TABLE `cuotas` DISABLE KEYS */;
INSERT INTO `cuotas` VALUES (1,1,1,'2026-04-30',12000.00,12000.00,0.00,'pagada','2026-04-30'),(2,1,2,'2026-05-01',12000.00,12000.00,0.00,'pagada','2026-04-30'),(3,1,3,'2026-05-02',12000.00,6000.00,0.00,'parcial',NULL),(4,1,4,'2026-05-04',12000.00,0.00,0.00,'pendiente',NULL),(5,1,5,'2026-05-05',12000.00,0.00,0.00,'pendiente',NULL),(6,1,6,'2026-05-06',12000.00,0.00,0.00,'pendiente',NULL),(7,1,7,'2026-05-07',12000.00,0.00,0.00,'pendiente',NULL),(8,1,8,'2026-05-08',12000.00,0.00,0.00,'pendiente',NULL),(9,1,9,'2026-05-09',12000.00,0.00,0.00,'pendiente',NULL),(10,1,10,'2026-05-11',12000.00,0.00,0.00,'pendiente',NULL);
/*!40000 ALTER TABLE `cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_cuotas`
--

DROP TABLE IF EXISTS `pago_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pago_cuotas` (
  `id_pago` int NOT NULL,
  `id_cuota` int NOT NULL,
  `monto_aplicado` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_pago`,`id_cuota`),
  KEY `fk_pagocuota_cuota` (`id_cuota`),
  CONSTRAINT `fk_pagocuota_cuota` FOREIGN KEY (`id_cuota`) REFERENCES `cuotas` (`id_cuota`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagocuota_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_cuotas`
--

LOCK TABLES `pago_cuotas` WRITE;
/*!40000 ALTER TABLE `pago_cuotas` DISABLE KEYS */;
INSERT INTO `pago_cuotas` VALUES (1,1,12000.00),(1,2,12000.00),(1,3,6000.00);
/*!40000 ALTER TABLE `pago_cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `id_credito` int NOT NULL,
  `id_cobrador` int DEFAULT NULL,
  `monto_pagado` decimal(12,2) NOT NULL,
  `forma_pago` enum('efectivo','transferencia','mp','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_externa` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nro de transferencia, etc.',
  `fecha_pago_real` date NOT NULL COMMENT 'Cuándo el cliente entregó la plata (elegido por Admin)',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se cargó al sistema (automático)',
  `id_rendicion` int DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `motivo_anulacion` text COLLATE utf8mb4_unicode_ci,
  `anulado_por` int DEFAULT NULL,
  `anulado_at` datetime DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `idx_pagos_credito` (`id_credito`),
  KEY `idx_pagos_cobrador` (`id_cobrador`),
  KEY `idx_pagos_rendicion` (`id_rendicion`),
  KEY `idx_pagos_fecha_real` (`fecha_pago_real`),
  KEY `fk_pago_anulado_por` (`anulado_por`),
  KEY `fk_pago_created_by` (`created_by`),
  CONSTRAINT `fk_pago_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_cobrador` FOREIGN KEY (`id_cobrador`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_credito` FOREIGN KEY (`id_credito`) REFERENCES `creditos` (`id_credito`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_rendicion` FOREIGN KEY (`id_rendicion`) REFERENCES `rendiciones` (`id_rendicion`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,1,1,30000.00,'efectivo',NULL,'2026-04-30','2026-04-30 21:36:19',NULL,NULL,0,NULL,NULL,NULL,1,'2026-04-30 21:36:19','2026-04-30 21:36:19',NULL);
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal`
--

DROP TABLE IF EXISTS `personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal` (
  `id_personal` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_operativo` enum('cobrador','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cobrador',
  `id_zona` int DEFAULT NULL,
  `comision_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_personal`),
  UNIQUE KEY `uq_personal_dni` (`dni`),
  KEY `fk_personal_zona` (`id_zona`),
  CONSTRAINT `fk_personal_zona` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id_zona`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal`
--

LOCK TABLES `personal` WRITE;
/*!40000 ALTER TABLE `personal` DISABLE KEYS */;
INSERT INTO `personal` VALUES (1,'Maxi','111','38','cobrador',1,5.00,'activo','2026-04-30 16:45:14','2026-05-02 16:16:37',NULL);
/*!40000 ALTER TABLE `personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recibos`
--

DROP TABLE IF EXISTS `recibos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recibos` (
  `id_recibo` int NOT NULL AUTO_INCREMENT,
  `id_pago` int NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_recibo`),
  UNIQUE KEY `uq_recibos_pago` (`id_pago`),
  UNIQUE KEY `uq_recibos_numero` (`numero`),
  CONSTRAINT `fk_recibo_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recibos`
--

LOCK TABLES `recibos` WRITE;
/*!40000 ALTER TABLE `recibos` DISABLE KEYS */;
INSERT INTO `recibos` VALUES (1,1,'R-2026-00001','storage/recibos/R-2026-00001.pdf','2026-04-30 21:36:19');
/*!40000 ALTER TABLE `recibos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rendiciones`
--

DROP TABLE IF EXISTS `rendiciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rendiciones` (
  `id_rendicion` int NOT NULL AUTO_INCREMENT,
  `id_cobrador` int NOT NULL,
  `fecha_rendicion` date NOT NULL,
  `total_efectivo_declarado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_transferencias_declarado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_declarado` decimal(12,2) GENERATED ALWAYS AS ((`total_efectivo_declarado` + `total_transferencias_declarado`)) STORED,
  `total_registrado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `diferencia` decimal(12,2) GENERATED ALWAYS AS (((`total_efectivo_declarado` + `total_transferencias_declarado`) - `total_registrado`)) STORED,
  `estado` enum('borrador','conciliada','con_diferencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rendicion`),
  KEY `idx_rendiciones_cobrador` (`id_cobrador`),
  KEY `idx_rendiciones_fecha` (`fecha_rendicion`),
  KEY `fk_rendicion_creado_por` (`created_by`),
  CONSTRAINT `fk_rendicion_cobrador` FOREIGN KEY (`id_cobrador`) REFERENCES `personal` (`id_personal`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rendicion_creado_por` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rendiciones`
--

LOCK TABLES `rendiciones` WRITE;
/*!40000 ALTER TABLE `rendiciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `rendiciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dni` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','cobrador','cliente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cobrador',
  `id_personal` int DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuarios_username` (`username`),
  KEY `fk_usuario_personal` (`id_personal`),
  KEY `fk_usuario_cliente` (`id_cliente`),
  CONSTRAINT `fk_usuario_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin',NULL,NULL,NULL,'$argon2id$v=19$m=65536,t=4,p=1$Q2xBam5UR2dwU1hQRUlzOQ$cs0T4wO0mApehhPZQnxvvU/nlApGXYI0hRnp8+KIo8k','admin',NULL,NULL,1,'2026-05-02 13:50:21',0,NULL,'2026-04-29 13:58:26','2026-05-02 16:50:21',NULL),(2,'40216602',NULL,NULL,NULL,'$2y$10$KQVKxqstSOQgOuXwkOp9DetYU5uw1FiVsfSidCaaVgx1nJfuuYiY6','cliente',NULL,1,1,NULL,0,NULL,'2026-05-02 16:16:53','2026-05-02 16:16:53',NULL),(3,'31812857','Queveod','Daniel','31812857','$2y$10$ze4mAuzGmQalPaGP3v06eeUqSfSo0Men//3CYAtfjij9mX.S5y6RC','admin',NULL,NULL,1,'2026-05-02 15:13:05',0,NULL,'2026-05-02 16:50:40','2026-05-02 18:13:05',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zonas`
--

DROP TABLE IF EXISTS `zonas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zonas` (
  `id_zona` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cobrador_default` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_zona`),
  KEY `fk_zona_cobrador` (`id_cobrador_default`),
  CONSTRAINT `fk_zona_cobrador` FOREIGN KEY (`id_cobrador_default`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zonas`
--

LOCK TABLES `zonas` WRITE;
/*!40000 ALTER TABLE `zonas` DISABLE KEYS */;
INSERT INTO `zonas` VALUES (1,'Catamarca',NULL,'2026-04-29 15:10:42','2026-04-29 15:10:42');
/*!40000 ALTER TABLE `zonas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-02 15:16:14
