-- ============================================================================
-- migracion_escala_interfaz.sql
-- Preferencia por usuario: escala de la interfaz (% del tamaño de fuente raíz).
-- Idempotente (MariaDB 10.3+). Correr una vez en phpMyAdmin.
-- Valores usados por la app: 90, 100, 110, 125.
-- ============================================================================

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `escala_interfaz` SMALLINT NOT NULL DEFAULT 100
  COMMENT 'Escala de la interfaz en % (font-size raíz)';
