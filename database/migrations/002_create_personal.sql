-- ============================================================
-- 002 — personal
-- ============================================================
CREATE TABLE IF NOT EXISTS personal (
    id_personal    INT          NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(120) NOT NULL,
    dni            VARCHAR(15)  NOT NULL,
    telefono       VARCHAR(30)  NULL,
    rol_operativo  SET('vendedor','cobrador','ambos') NOT NULL,
    id_zona        INT          NULL,
    comision_pct   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP    NULL,
    PRIMARY KEY (id_personal),
    UNIQUE KEY uq_personal_dni (dni),
    CONSTRAINT fk_personal_zona
        FOREIGN KEY (id_zona) REFERENCES zonas(id_zona)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ahora que personal existe, agregamos la FK circular en zonas
ALTER TABLE zonas
    ADD CONSTRAINT fk_zona_cobrador
    FOREIGN KEY (id_cobrador_default) REFERENCES personal(id_personal)
    ON UPDATE CASCADE ON DELETE SET NULL;
