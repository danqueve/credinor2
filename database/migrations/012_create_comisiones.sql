-- ============================================================
-- 012 — comisiones  (cálculo automático, liquidación mensual)
-- ============================================================
CREATE TABLE IF NOT EXISTS comisiones (
    id_comision    INT           NOT NULL AUTO_INCREMENT,
    id_personal    INT           NOT NULL,
    periodo        VARCHAR(7)    NOT NULL COMMENT 'ej: 2026-04',
    tipo           ENUM('venta','cobranza') NOT NULL,
    monto_base     DECIMAL(12,2) NOT NULL,
    pct            DECIMAL(5,2)  NOT NULL,
    monto_comision DECIMAL(12,2) NOT NULL,
    pagada         TINYINT(1)    NOT NULL DEFAULT 0,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_comision),
    KEY idx_comisiones_personal (id_personal),
    KEY idx_comisiones_periodo  (periodo),
    CONSTRAINT fk_comision_personal
        FOREIGN KEY (id_personal) REFERENCES personal(id_personal)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
