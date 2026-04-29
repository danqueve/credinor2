-- ============================================================
-- 003 — usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario       INT          NOT NULL AUTO_INCREMENT,
    username         VARCHAR(50)  NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    rol              ENUM('admin','consulta') NOT NULL DEFAULT 'consulta',
    id_personal      INT          NULL,
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_login     DATETIME     NULL,
    intentos_fallidos TINYINT     NOT NULL DEFAULT 0,
    bloqueado_hasta  DATETIME     NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       TIMESTAMP    NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_username (username),
    CONSTRAINT fk_usuario_personal
        FOREIGN KEY (id_personal) REFERENCES personal(id_personal)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
