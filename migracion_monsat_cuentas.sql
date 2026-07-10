-- ============================================================================
-- migracion_monsat_cuentas.sql
-- Cuentas de correo (IMAP) desde donde se importan los reportes de Monsat.
-- Una por sucursal (sucursal_id NULL = aplica a toda la empresa / global).
-- La contraseña se guarda CIFRADA (AES, config/vault_helpers.php).
-- Correr una vez en phpMyAdmin.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `flotilla_monsat_cuentas` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id`      INT(11) DEFAULT NULL COMMENT 'NULL = global/todas',
  `nombre`           VARCHAR(80) NOT NULL DEFAULT 'Monsat',
  `host`             VARCHAR(150) NOT NULL,
  `port`             SMALLINT NOT NULL DEFAULT 993,
  `usuario`          VARCHAR(150) NOT NULL,
  `password_cifrada` TEXT DEFAULT NULL,
  `folder`           VARCHAR(80) NOT NULL DEFAULT 'INBOX',
  `remitente`        VARCHAR(150) DEFAULT NULL,
  `solo_no_leidos`   TINYINT(1) NOT NULL DEFAULT 1,
  `marcar_leidos`    TINYINT(1) NOT NULL DEFAULT 1,
  `activo`           TINYINT(1) NOT NULL DEFAULT 1,
  `ultima_ejecucion` DATETIME DEFAULT NULL,
  `ultimo_resultado` VARCHAR(255) DEFAULT NULL,
  `creado_en`        TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `actualizado_en`   TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sucursal` (`sucursal_id`),
  CONSTRAINT `fk_monsat_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuentas IMAP para importar reportes de Monsat por correo';
