CREATE TABLE IF NOT EXISTS `caja_movimientos` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `tipo`          ENUM('ingreso','egreso') NOT NULL,
  `monto`         DECIMAL(12,2) NOT NULL,
  `concepto`      VARCHAR(255) NOT NULL,
  `fecha`         DATE NOT NULL,
  `observaciones` TEXT NULL,
  `created_by`    INT NOT NULL,
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_caja_fecha` (`fecha`),
  CONSTRAINT `fk_caja_creado_por` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
