-- ============================================================================
-- migracion_mant_fotos.sql
-- Fotos "antes" y "después" para los mantenimientos de flotilla.
-- Idempotente: usa ADD COLUMN IF NOT EXISTS (MariaDB 10.3+ / XAMPP).
-- Correr una vez en phpMyAdmin. La factura ya se guarda en archivo_url.
-- ============================================================================

ALTER TABLE `flotilla_mant_historial`
  ADD COLUMN IF NOT EXISTS `foto_antes_url`   VARCHAR(255) DEFAULT NULL COMMENT 'Foto antes del mantenimiento'   AFTER `archivo_url`,
  ADD COLUMN IF NOT EXISTS `foto_despues_url` VARCHAR(255) DEFAULT NULL COMMENT 'Foto despues del mantenimiento' AFTER `foto_antes_url`;
