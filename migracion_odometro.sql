-- ============================================================
-- Odómetro: historial de lecturas + configuración global
-- Ejecutar UNA sola vez en phpMyAdmin (base de datos del sistema).
-- Es seguro re-ejecutarlo: usa IF NOT EXISTS / ON DUPLICATE KEY.
-- ============================================================

-- 1) Historial de lecturas de odómetro
CREATE TABLE IF NOT EXISTS `flotilla_odometro_historial` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `vehiculo_id` INT(11)      NOT NULL,
  `km`          INT(11)      NOT NULL,
  `km_anterior` INT(11)      DEFAULT NULL,
  `origen`      VARCHAR(20)  NOT NULL DEFAULT 'manual',  -- manual | combustible | gasto | viaje
  `usuario_id`  INT(11)      DEFAULT NULL,
  `notas`       VARCHAR(255) DEFAULT NULL,
  `leido_en`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  `creado_en`   TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_veh_fecha` (`vehiculo_id`, `leido_en`),
  CONSTRAINT `fk_odo_veh` FOREIGN KEY (`vehiculo_id`)
      REFERENCES `flotilla_vehiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lecturas del odómetro por vehículo (para medir uso y antigüedad)';

-- 2) Configuración global (clave/valor) — reutilizable para futuros ajustes
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave`          VARCHAR(60) NOT NULL,
  `valor`          TEXT        DEFAULT NULL,
  `actualizado_en` TIMESTAMP   NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Parámetros configurables del sistema';

-- 3) Umbral por defecto: 30 días sin actualizar = odómetro desactualizado
INSERT INTO `configuracion` (`clave`, `valor`) VALUES ('odometro_umbral_dias', '30')
  ON DUPLICATE KEY UPDATE `valor` = `valor`;
