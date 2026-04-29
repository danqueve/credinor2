-- ============================================================
-- 006 — creditos
-- ============================================================
CREATE TABLE IF NOT EXISTS creditos (
    id_credito           INT           NOT NULL AUTO_INCREMENT,
    codigo               VARCHAR(20)   NOT NULL,
    id_cliente           INT           NOT NULL,
    id_vendedor          INT           NULL,
    id_cobrador          INT           NULL,
    capital              DECIMAL(12,2) NOT NULL COMMENT 'Monto prestado en mano (input)',
    cantidad_cuotas      SMALLINT      NOT NULL,
    valor_cuota          DECIMAL(12,2) NOT NULL COMMENT 'Valor de cada cuota (input)',
    monto_total          DECIMAL(12,2) NOT NULL COMMENT 'Calculado: cantidad_cuotas × valor_cuota',
    interes_implicito    DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'monto_total - capital - gastos_admin',
    interes_implicito_pct DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje sobre capital',
    gastos_admin         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    frecuencia           ENUM('diaria','semanal','quincenal','mensual') NOT NULL,
    fecha_inicio         DATE          NOT NULL,
    fecha_fin_estimada   DATE          NULL,
    saldo_pendiente      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    destino_opcional     VARCHAR(120)  NULL,
    estado               ENUM('activo','finalizado','anulado','refinanciado','incobrable') NOT NULL DEFAULT 'activo',
    id_credito_origen    INT           NULL COMMENT 'Si viene de una refinanciación',
    observaciones        TEXT          NULL,
    created_by           INT           NOT NULL,
    updated_by           INT           NULL,
    created_at           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at           TIMESTAMP     NULL,
    PRIMARY KEY (id_credito),
    UNIQUE KEY uq_creditos_codigo (codigo),
    KEY idx_creditos_cliente  (id_cliente),
    KEY idx_creditos_estado   (estado),
    KEY idx_creditos_cobrador (id_cobrador),
    CONSTRAINT fk_credito_cliente
        FOREIGN KEY (id_cliente)   REFERENCES clientes(id_cliente)   ON UPDATE CASCADE,
    CONSTRAINT fk_credito_vendedor
        FOREIGN KEY (id_vendedor)  REFERENCES personal(id_personal)  ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_credito_cobrador
        FOREIGN KEY (id_cobrador)  REFERENCES personal(id_personal)  ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_credito_creado_por
        FOREIGN KEY (created_by)   REFERENCES usuarios(id_usuario)   ON UPDATE CASCADE,
    CONSTRAINT fk_credito_updated_by
        FOREIGN KEY (updated_by)   REFERENCES usuarios(id_usuario)   ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK auto-referencial (refinanciación)
ALTER TABLE creditos
    ADD CONSTRAINT fk_credito_origen
    FOREIGN KEY (id_credito_origen) REFERENCES creditos(id_credito)
    ON UPDATE CASCADE ON DELETE SET NULL;
