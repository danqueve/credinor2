-- ============================================================
-- 015 — Actualización de roles: usuarios + personal + id_cliente
-- ============================================================

-- 1. Ampliar ENUM para poder migrar datos antes de quitar 'consulta'
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('admin','consulta','supervisor','cobrador','cliente') NOT NULL DEFAULT 'cobrador';

-- 2. Renombrar 'consulta' → 'cobrador'
UPDATE usuarios SET rol = 'cobrador' WHERE rol = 'consulta';

-- 3. Dejar solo los 3 roles definitivos
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('admin','supervisor','cobrador','cliente') NOT NULL DEFAULT 'cobrador';

-- 4. Agregar columna id_cliente (para usuarios tipo cliente)
ALTER TABLE usuarios
    ADD COLUMN id_cliente INT NULL AFTER id_personal,
    ADD CONSTRAINT fk_usuario_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- 5. Migrar personal.rol_operativo: quitar vendedor/ambos
UPDATE personal SET rol_operativo = 'cobrador'
    WHERE FIND_IN_SET('vendedor', rol_operativo) > 0
       OR rol_operativo = 'ambos';

-- 6. Cambiar SET → ENUM en personal.rol_operativo
ALTER TABLE personal
    MODIFY COLUMN rol_operativo ENUM('cobrador','admin') NOT NULL DEFAULT 'cobrador';
