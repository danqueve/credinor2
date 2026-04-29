-- ============================================================
-- 004 — clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente            INT           NOT NULL AUTO_INCREMENT,
    nombre                VARCHAR(80)   NOT NULL,
    apellido              VARCHAR(80)   NOT NULL,
    dni                   VARCHAR(15)   NOT NULL,
    direccion             VARCHAR(255)  NOT NULL DEFAULT '',
    id_zona               INT           NULL,
    referencia_domicilio  VARCHAR(255)  NULL,
    latitud               DECIMAL(10,7) NULL,
    longitud              DECIMAL(10,7) NULL,
    telefono_principal    VARCHAR(30)   NOT NULL,
    telefono_alternativo  VARCHAR(30)   NULL,
    score_interno         TINYINT       NOT NULL DEFAULT 3 COMMENT '1=muy malo, 5=excelente',
    observaciones         TEXT          NULL,
    created_at            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP     NULL,
    PRIMARY KEY (id_cliente),
    UNIQUE KEY uq_clientes_dni (dni),
    KEY idx_clientes_apellido (apellido),
    CONSTRAINT fk_cliente_zona
        FOREIGN KEY (id_zona) REFERENCES zonas(id_zona)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
