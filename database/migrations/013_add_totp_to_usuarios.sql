-- ============================================================
-- 013 — TOTP 2FA para usuarios admin
-- ============================================================
ALTER TABLE usuarios
    ADD COLUMN totp_secret VARCHAR(64) NULL DEFAULT NULL
        COMMENT 'Base32 TOTP secret. NULL = 2FA desactivado'
    AFTER password_hash;
