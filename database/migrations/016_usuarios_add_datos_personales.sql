-- ============================================================
-- 016 — Agregar datos personales a usuarios del sistema
-- ============================================================
ALTER TABLE usuarios
    ADD COLUMN apellido VARCHAR(80) NULL AFTER username,
    ADD COLUMN nombre   VARCHAR(80) NULL AFTER apellido,
    ADD COLUMN dni      VARCHAR(15) NULL AFTER nombre;
