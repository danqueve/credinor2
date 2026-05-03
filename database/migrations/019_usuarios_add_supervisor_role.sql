-- ============================================================
-- 019 - Agregar rol supervisor a usuarios
--      Acceso administrativo completo en modo solo lectura.
-- ============================================================
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('admin','supervisor','cobrador','cliente') NOT NULL DEFAULT 'cobrador';
