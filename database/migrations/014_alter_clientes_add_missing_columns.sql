-- ============================================================
-- 014 — Agrega columnas faltantes a clientes
--       requeridas por ClienteRepository y Cliente model
-- ============================================================
ALTER TABLE clientes
    ADD COLUMN barrio           VARCHAR(100)  NULL  AFTER direccion,
    ADD COLUMN telefono         VARCHAR(30)   NULL  AFTER barrio,
    ADD COLUMN coordenadas_gps  VARCHAR(100)  NULL  AFTER telefono,
    ADD COLUMN referencias      TEXT          NULL  AFTER coordenadas_gps,
    ADD COLUMN foto_url         VARCHAR(255)  NULL  AFTER referencias;
