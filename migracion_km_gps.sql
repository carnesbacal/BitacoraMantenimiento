-- ============================================================================
-- migracion_km_gps.sql
-- Kilometraje diario por vehículo tomado de Monsat/GPS (reporte "Información general").
-- Alimenta rendimiento km/L y costo por km con datos reales.
-- Idempotente: única por (vehiculo_id, fecha); reimportar no duplica.
-- Correr una vez en phpMyAdmin.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `flotilla_km_gps` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `vehiculo_id` INT(11) NOT NULL,
  `fecha`       DATE NOT NULL,
  `km`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `litros`      DECIMAL(10,2) DEFAULT NULL COMMENT 'Si la unidad tiene sensor de combustible',
  `costo_comb`  DECIMAL(10,2) DEFAULT NULL,
  `fuente`      VARCHAR(20) NOT NULL DEFAULT 'monsat',
  `creado_en`   TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_veh_fecha` (`vehiculo_id`, `fecha`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `fk_kmgps_veh` FOREIGN KEY (`vehiculo_id`) REFERENCES `flotilla_vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kilometraje diario por vehículo (GPS/Monsat)';
