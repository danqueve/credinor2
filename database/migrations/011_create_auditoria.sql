-- ============================================================
-- 011 — auditoria  (log inmutable)
-- ============================================================
CREATE TABLE IF NOT EXISTS auditoria (
    id_log       BIGINT       NOT NULL AUTO_INCREMENT,
    id_usuario   INT          NULL,
    accion       VARCHAR(60)  NOT NULL COMMENT 'ej: pago.create, credito.anular, login.fail',
    entidad      VARCHAR(40)  NULL,
    entidad_id   INT          NULL,
    datos_antes  JSON         NULL,
    datos_despues JSON        NULL,
    ip           VARCHAR(45)  NULL,
    user_agent   VARCHAR(255) NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    KEY idx_auditoria_usuario (id_usuario),
    KEY idx_auditoria_accion  (accion),
    KEY idx_auditoria_created (created_at),
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
