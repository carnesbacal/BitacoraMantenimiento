-- ============================================================
-- Combustible: catálogo de estaciones + enlace y recibo en cargas
-- Ejecutar UNA sola vez en phpMyAdmin (las columnas ALTER fallan si ya existen).
-- ============================================================

-- 1) Catálogo de estaciones / gasolineras (alta solo por admin)
CREATE TABLE IF NOT EXISTS `flotilla_estaciones` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(120) NOT NULL,
  `direccion`  VARCHAR(255) DEFAULT NULL,
  `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `creado_por` INT(11)      DEFAULT NULL,
  `creado_en`  TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de estaciones/gasolineras de carga';

-- 2) En la carga de combustible: enlace a la estación del catálogo y recibo adjunto
ALTER TABLE `flotilla_combustible`
  ADD COLUMN `estacion_id` INT(11)      DEFAULT NULL AFTER `estacion`,
  ADD COLUMN `recibo_url`  VARCHAR(255) DEFAULT NULL AFTER `ticket_numero`;
