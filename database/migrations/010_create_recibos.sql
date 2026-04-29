-- ============================================================
-- 010 — recibos  (PDF generado por cada pago)
-- ============================================================
CREATE TABLE IF NOT EXISTS recibos (
    id_recibo  INT          NOT NULL AUTO_INCREMENT,
    id_pago    INT          NOT NULL,
    numero     VARCHAR(20)  NOT NULL,
    pdf_path   VARCHAR(255) NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_recibo),
    UNIQUE KEY uq_recibos_pago   (id_pago),
    UNIQUE KEY uq_recibos_numero (numero),
    CONSTRAINT fk_recibo_pago
        FOREIGN KEY (id_pago) REFERENCES pagos(id_pago)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
