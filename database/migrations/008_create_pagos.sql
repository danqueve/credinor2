-- ============================================================
-- 008 — pagos
-- ============================================================
CREATE TABLE IF NOT EXISTS pagos (
    id_pago            INT           NOT NULL AUTO_INCREMENT,
    id_credito         INT           NOT NULL,
    id_cobrador        INT           NULL,
    monto_pagado       DECIMAL(12,2) NOT NULL,
    forma_pago         ENUM('efectivo','transferencia','mp','otro') NOT NULL,
    referencia_externa VARCHAR(60)   NULL COMMENT 'Nro de transferencia, etc.',
    fecha_pago_real    DATE          NOT NULL COMMENT 'Cuándo el cliente entregó la plata (elegido por Admin)',
    fecha_registro     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se cargó al sistema (automático)',
    id_rendicion       INT           NULL,
    observaciones      TEXT          NULL,
    anulado            TINYINT(1)    NOT NULL DEFAULT 0,
    motivo_anulacion   TEXT          NULL,
    anulado_por        INT           NULL,
    anulado_at         DATETIME      NULL,
    created_by         INT           NOT NULL,
    created_at         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at         TIMESTAMP     NULL,
    PRIMARY KEY (id_pago),
    KEY idx_pagos_credito    (id_credito),
    KEY idx_pagos_cobrador   (id_cobrador),
    KEY idx_pagos_rendicion  (id_rendicion),
    KEY idx_pagos_fecha_real (fecha_pago_real),
    CONSTRAINT fk_pago_credito
        FOREIGN KEY (id_credito)   REFERENCES creditos(id_credito)    ON UPDATE CASCADE,
    CONSTRAINT fk_pago_cobrador
        FOREIGN KEY (id_cobrador)  REFERENCES personal(id_personal)   ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pago_rendicion
        FOREIGN KEY (id_rendicion) REFERENCES rendiciones(id_rendicion) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pago_anulado_por
        FOREIGN KEY (anulado_por)  REFERENCES usuarios(id_usuario)    ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pago_created_by
        FOREIGN KEY (created_by)   REFERENCES usuarios(id_usuario)    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
