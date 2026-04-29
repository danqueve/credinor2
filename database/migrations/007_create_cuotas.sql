-- ============================================================
-- 007 — cuotas  (calendario de vencimientos)
-- ============================================================
CREATE TABLE IF NOT EXISTS cuotas (
    id_cuota          INT           NOT NULL AUTO_INCREMENT,
    id_credito        INT           NOT NULL,
    numero_cuota      SMALLINT      NOT NULL COMMENT 'Nro de cuota: 1, 2, 3...',
    fecha_vencimiento DATE          NOT NULL,
    monto_esperado    DECIMAL(12,2) NOT NULL COMMENT '= valor_cuota del crédito',
    monto_pagado      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    monto_recargo     DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Siempre 0 mientras mora.habilitada=false',
    estado            ENUM('pendiente','parcial','pagada','vencida','condonada') NOT NULL DEFAULT 'pendiente',
    fecha_pagada      DATE          NULL COMMENT 'Última fecha de pago aplicada',
    PRIMARY KEY (id_cuota),
    KEY idx_cuotas_credito    (id_credito),
    KEY idx_cuotas_vencimiento (fecha_vencimiento),
    KEY idx_cuotas_estado     (estado),
    CONSTRAINT fk_cuota_credito
        FOREIGN KEY (id_credito) REFERENCES creditos(id_credito)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
