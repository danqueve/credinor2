-- ============================================================
-- 001 — zonas
-- ============================================================
CREATE TABLE IF NOT EXISTS zonas (
    id_zona   INT          NOT NULL AUTO_INCREMENT,
    nombre    VARCHAR(80)  NOT NULL,
    id_cobrador_default INT NULL,            -- FK a personal, se agrega después
    created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
