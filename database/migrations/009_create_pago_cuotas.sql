-- ============================================================
-- 009 — pago_cuotas  (tabla puente: un pago → varias cuotas)
-- ============================================================
CREATE TABLE IF NOT EXISTS pago_cuotas (
    id_pago        INT           NOT NULL,
    id_cuota       INT           NOT NULL,
    monto_aplicado DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id_pago, id_cuota),
    CONSTRAINT fk_pagocuota_pago
        FOREIGN KEY (id_pago)  REFERENCES pagos(id_pago)   ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pagocuota_cuota
        FOREIGN KEY (id_cuota) REFERENCES cuotas(id_cuota) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
