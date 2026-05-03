-- Registro de intentos de cobranza por cuota (sin necesidad de registrar pago)
CREATE TABLE IF NOT EXISTS visitas_cobranza (
    id_visita     INT AUTO_INCREMENT PRIMARY KEY,
    id_cuota      INT NOT NULL,
    id_cobrador   INT NOT NULL,
    resultado     ENUM('intentada','no_contesta','promesa','cobrada') NOT NULL,
    observaciones TEXT NULL,
    geo_lat       DECIMAL(10,7) NULL,
    geo_lng       DECIMAL(10,7) NULL,
    fecha         DATE NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuota)    REFERENCES cuotas(id_cuota)   ON DELETE CASCADE,
    FOREIGN KEY (id_cobrador) REFERENCES personal(id_personal) ON DELETE CASCADE,
    INDEX idx_cuota    (id_cuota),
    INDEX idx_cobrador (id_cobrador, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
