-- ============================================================================
-- migracion_notificaciones.sql
-- Sistema de notificaciones por email y Telegram
-- Ejecutar una sola vez sobre la base de datos.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Configuración global de canales (administrador)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion_notificaciones` (
    `id`                  INT NOT NULL AUTO_INCREMENT,
    -- SMTP
    `smtp_host`           VARCHAR(255)  DEFAULT NULL   COMMENT 'Servidor SMTP, ej: mail.tudominio.com',
    `smtp_port`           SMALLINT UNSIGNED DEFAULT 587 COMMENT '465=SSL, 587=TLS, 25=sin cifrado',
    `smtp_seguridad`      ENUM('tls','ssl','none') DEFAULT 'tls',
    `smtp_usuario`        VARCHAR(255)  DEFAULT NULL,
    `smtp_password`       VARCHAR(255)  DEFAULT NULL   COMMENT 'Contraseña en texto plano (idealmente cifrada a futuro)',
    `smtp_from_email`     VARCHAR(255)  DEFAULT NULL   COMMENT 'Dirección remitente',
    `smtp_from_nombre`    VARCHAR(150)  DEFAULT 'Bitácora Mantenimiento',
    `smtp_activo`         TINYINT(1)   NOT NULL DEFAULT 0,
    -- Telegram
    `telegram_bot_token`  VARCHAR(255)  DEFAULT NULL   COMMENT 'Token del bot de @BotFather',
    `telegram_activo`     TINYINT(1)   NOT NULL DEFAULT 0,
    -- Control
    `actualizado_en`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `actualizado_por`     INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo debe existir 1 fila de configuración global
INSERT IGNORE INTO `configuracion_notificaciones` (`id`) VALUES (1);

-- ----------------------------------------------------------------------------
-- 2. Telegram Chat ID por usuario (en tabla usuarios)
-- ----------------------------------------------------------------------------
ALTER TABLE `usuarios`
    ADD COLUMN IF NOT EXISTS `telegram_chat_id` VARCHAR(50) DEFAULT NULL COMMENT 'Chat ID de Telegram del usuario (obtenido via @userinfobot)';

-- Si tu versión de MySQL no soporta ADD COLUMN IF NOT EXISTS, usa esto en su lugar:
-- ALTER TABLE `usuarios` ADD COLUMN `telegram_chat_id` VARCHAR(50) DEFAULT NULL;

-- ----------------------------------------------------------------------------
-- 3. Preferencias de notificación por usuario y tipo de evento
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacion_preferencias` (
    `id`           INT          NOT NULL AUTO_INCREMENT,
    `usuario_id`   INT          NOT NULL,
    `tipo`         VARCHAR(60)  NOT NULL COMMENT 'Tipo de notificación (NOTIF_TIPOS)',
    `canal_inapp`  TINYINT(1)  NOT NULL DEFAULT 1 COMMENT 'Notificación in-app activada',
    `canal_email`  TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'Notificación por email activada',
    `canal_telegram` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Notificación por Telegram activada',
    `creado_en`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuario_tipo` (`usuario_id`, `tipo`),
    KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Log de envíos externos (para debugging y evitar reenvíos)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacion_envios` (
    `id`              INT NOT NULL AUTO_INCREMENT,
    `notificacion_id` INT DEFAULT NULL COMMENT 'ID en tabla notificaciones (si existe)',
    `usuario_id`      INT NOT NULL,
    `canal`           ENUM('email','telegram') NOT NULL,
    `tipo`            VARCHAR(60)  DEFAULT NULL,
    `asunto`          VARCHAR(255) DEFAULT NULL,
    `estado`          ENUM('ok','error') NOT NULL DEFAULT 'ok',
    `error_detalle`   TEXT         DEFAULT NULL,
    `enviado_en`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usuario_canal` (`usuario_id`, `canal`),
    KEY `idx_enviado_en`    (`enviado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
