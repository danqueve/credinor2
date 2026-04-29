-- ============================================================
-- 005 — rendiciones  (debe ir ANTES que pagos por FK)
-- ============================================================
CREATE TABLE IF NOT EXISTS rendiciones (
    id_rendicion                  INT           NOT NULL AUTO_INCREMENT,
    id_cobrador                   INT           NOT NULL,
    fecha_rendicion               DATE          NOT NULL,
    total_efectivo_declarado      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_transferencias_declarado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_declarado               DECIMAL(12,2) AS (total_efectivo_declarado + total_transferencias_declarado) STORED,
    total_registrado              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    diferencia                    DECIMAL(12,2) AS (total_efectivo_declarado + total_transferencias_declarado - total_registrado) STORED,
    estado                        ENUM('borrador','conciliada','con_diferencia') NOT NULL DEFAULT 'borrador',
    observaciones                 TEXT          NULL,
    created_by                    INT           NOT NULL,
    created_at                    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_rendicion),
    KEY idx_rendiciones_cobrador (id_cobrador),
    KEY idx_rendiciones_fecha    (fecha_rendicion),
    CONSTRAINT fk_rendicion_cobrador
        FOREIGN KEY (id_cobrador) REFERENCES personal(id_personal)
        ON UPDATE CASCADE,
    CONSTRAINT fk_rendicion_creado_por
        FOREIGN KEY (created_by) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
