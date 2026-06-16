-- ============================================================================
-- mantenimiento_bacal_B1.sql
-- ============================================================================
-- BLOQUE 1: Estructura completa de BD + catálogos de Mantenimiento
--
-- Este script:
--   1. Crea la BD mantenimiento_bacal desde cero
--   2. Crea todas las tablas (estructura idéntica a la BD actual)
--   3. Siembra catálogos adaptados para Mantenimiento
--   4. Crea usuario admin inicial
--   5. Siembra vault con categorías de mantenimiento
--
-- IMPORTANTE: Si ya existe la BD 'mantenimiento_bacal', SE ELIMINARÁ.
--             Asegúrate de que es lo que quieres antes de ejecutar.
-- ============================================================================

DROP DATABASE IF EXISTS mantenimiento_bacal;
CREATE DATABASE mantenimiento_bacal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mantenimiento_bacal;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- ESTRUCTURA DE TABLAS (idéntica a la BD de Sistemas)
-- ============================================================================

CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `contenido` text NOT NULL,
  `tipo` enum('info','aviso','urgente','exito') NOT NULL DEFAULT 'info',
  `icono` varchar(50) DEFAULT 'megaphone',
  `sucursal_id` int(11) DEFAULT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL = sin fecha límite',
  `fijado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = se fija arriba',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activo_vigencia` (`activo`,`fecha_inicio`,`fecha_fin`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_rol` (`rol_id`),
  KEY `fk_anun_creador` (`creado_por_id`),
  CONSTRAINT `fk_anun_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_anun_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_anun_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `anuncios_lecturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anuncio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `leido_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_anuncio_usuario` (`anuncio_id`,`usuario_id`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_lect_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lect_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#6B7280',
  `icono` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `entidad` varchar(50) DEFAULT NULL,
  `entidad_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_fecha` (`creado_en`),
  CONSTRAINT `auditoria_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `backups_realizados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(255) NOT NULL,
  `tamano_bytes` bigint(20) NOT NULL DEFAULT 0,
  `tipo` enum('manual','automatico') NOT NULL DEFAULT 'manual',
  `realizado_por_id` int(11) DEFAULT NULL COMMENT 'Null si fue automatico',
  `notas` varchar(255) DEFAULT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT 1,
  `mensaje_error` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_creado` (`creado_en`),
  KEY `fk_backup_usuario` (`realizado_por_id`),
  CONSTRAINT `fk_backup_usuario` FOREIGN KEY (`realizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#6B7280',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias_palabras_clave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `palabra` varchar(60) NOT NULL COMMENT 'Palabra o frase clave (lowercase, sin acentos)',
  `peso` int(11) NOT NULL DEFAULT 1 COMMENT 'Mayor peso = más específica',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cat_palabra` (`categoria_id`,`palabra`),
  KEY `idx_palabra` (`palabra`),
  CONSTRAINT `fk_kw_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comentario_reacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `emoji` varchar(10) NOT NULL COMMENT 'Emoji unicode',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_react` (`comentario_id`,`usuario_id`,`emoji`),
  KEY `idx_comentario` (`comentario_id`),
  KEY `fk_react_usuario` (`usuario_id`),
  CONSTRAINT `fk_react_comentario` FOREIGN KEY (`comentario_id`) REFERENCES `incidencias_comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_react_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipo_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL COMMENT 'Ruta relativa (assets/equipos/...)',
  `descripcion` varchar(255) DEFAULT NULL,
  `es_portada` tinyint(1) NOT NULL DEFAULT 0,
  `subido_por_id` int(11) DEFAULT NULL,
  `tamano_bytes` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipo` (`equipo_id`),
  KEY `fk_foto_usuario` (`subido_por_id`),
  CONSTRAINT `fk_foto_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_foto_usuario` FOREIGN KEY (`subido_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipo_transferencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `sucursal_origen_id` int(11) DEFAULT NULL COMMENT 'Null si era equipo nuevo recien llegado',
  `sucursal_destino_id` int(11) NOT NULL,
  `area_origen_id` int(11) DEFAULT NULL,
  `area_destino_id` int(11) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_transferencia` date NOT NULL,
  `realizado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipo` (`equipo_id`),
  KEY `idx_fecha` (`fecha_transferencia`),
  KEY `fk_trans_origen` (`sucursal_origen_id`),
  KEY `fk_trans_destino` (`sucursal_destino_id`),
  KEY `fk_trans_area_origen` (`area_origen_id`),
  KEY `fk_trans_area_destino` (`area_destino_id`),
  KEY `fk_trans_usuario` (`realizado_por_id`),
  CONSTRAINT `fk_trans_area_destino` FOREIGN KEY (`area_destino_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trans_area_origen` FOREIGN KEY (`area_origen_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trans_destino` FOREIGN KEY (`sucursal_destino_id`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `fk_trans_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trans_origen` FOREIGN KEY (`sucursal_origen_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trans_usuario` FOREIGN KEY (`realizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_inventario` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `sucursal_id` int(11) NOT NULL,
  `planta_id` int(11) DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `costo_compra` decimal(12,2) DEFAULT NULL,
  `vida_util_meses` int(11) DEFAULT NULL COMMENT 'Vida util estimada en meses (60 = 5 años)',
  `fecha_baja` date DEFAULT NULL,
  `motivo_baja` varchar(255) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `estado_vida` enum('nuevo','en_uso','en_reparacion','dado_de_baja') NOT NULL DEFAULT 'en_uso',
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pos_x` decimal(5,2) DEFAULT NULL COMMENT '% desde el borde izquierdo',
  `pos_y` decimal(5,2) DEFAULT NULL COMMENT '% desde el borde superior',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_inventario` (`codigo_inventario`),
  KEY `area_id` (`area_id`),
  KEY `responsable_id` (`responsable_id`),
  KEY `idx_sucursal_area` (`sucursal_id`,`area_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `fk_equipo_proveedor` (`proveedor_id`),
  KEY `idx_pos` (`pos_x`,`pos_y`),
  KEY `fk_equipo_planta` (`planta_id`),
  CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `equipos_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipos_ibfk_3` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipo_planta` FOREIGN KEY (`planta_id`) REFERENCES `sucursal_plantas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipo_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `orden` int(11) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#6B7280',
  `es_inicial` tinyint(1) NOT NULL DEFAULT 0,
  `es_final` tinyint(1) NOT NULL DEFAULT 0,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `importaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('usuarios','equipos','incidencias') NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `total_filas` int(11) NOT NULL DEFAULT 0,
  `exitosos` int(11) NOT NULL DEFAULT 0,
  `fallidos` int(11) NOT NULL DEFAULT 0,
  `errores_json` text DEFAULT NULL COMMENT 'JSON con detalles de errores por fila',
  `realizado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`creado_en`),
  KEY `fk_import_usuario` (`realizado_por_id`),
  CONSTRAINT `fk_import_usuario` FOREIGN KEY (`realizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `subcategoria_id` int(11) DEFAULT NULL,
  `tipo_trabajo_id` int(11) DEFAULT NULL,
  `severidad_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `origen_reporte_id` int(11) DEFAULT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `reportado_por_id` int(11) NOT NULL,
  `reportante_nombre` varchar(150) DEFAULT NULL,
  `reportante_puesto` varchar(100) DEFAULT NULL,
  `asignado_a_id` int(11) DEFAULT NULL,
  `proveedor_escalado_id` int(11) DEFAULT NULL,
  `resuelto_por_id` int(11) DEFAULT NULL,
  `causa_raiz` text DEFAULT NULL,
  `solucion` text DEFAULT NULL,
  `recomendaciones` text DEFAULT NULL,
  `acciones_preventivas` text DEFAULT NULL,
  `es_reincidencia` tinyint(1) NOT NULL DEFAULT 0,
  `incidencia_padre_id` int(11) DEFAULT NULL,
  `veces_recurrida` int(11) NOT NULL DEFAULT 0,
  `fecha_evento` datetime NOT NULL,
  `fecha_atencion` datetime DEFAULT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `tiempo_respuesta_min` int(11) DEFAULT NULL,
  `tiempo_resolucion_min` int(11) DEFAULT NULL,
  `sla_cumplido` tinyint(1) DEFAULT NULL,
  `fecha_limite_sla` datetime DEFAULT NULL,
  `confirmado_por_reportante` tinyint(1) DEFAULT 0,
  `fecha_confirmacion` datetime DEFAULT NULL,
  `calificacion_servicio` int(11) DEFAULT NULL,
  `comentario_reportante` text DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `creado_por_id` int(11) NOT NULL,
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actualizado_por_id` int(11) DEFAULT NULL,
  `archivada` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si está archivada (resuelta hace >1 año)',
  `fecha_archivado` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `categoria_id` (`categoria_id`),
  KEY `subcategoria_id` (`subcategoria_id`),
  KEY `tipo_trabajo_id` (`tipo_trabajo_id`),
  KEY `estado_id` (`estado_id`),
  KEY `origen_reporte_id` (`origen_reporte_id`),
  KEY `resuelto_por_id` (`resuelto_por_id`),
  KEY `incidencia_padre_id` (`incidencia_padre_id`),
  KEY `creado_por_id` (`creado_por_id`),
  KEY `actualizado_por_id` (`actualizado_por_id`),
  KEY `idx_folio` (`folio`),
  KEY `idx_sucursal_estado` (`sucursal_id`,`estado_id`),
  KEY `idx_area` (`area_id`),
  KEY `idx_severidad` (`severidad_id`),
  KEY `idx_asignado` (`asignado_a_id`),
  KEY `idx_reportado_por` (`reportado_por_id`),
  KEY `idx_equipo` (`equipo_id`),
  KEY `idx_fecha_evento` (`fecha_evento`),
  KEY `idx_reincidencia` (`es_reincidencia`,`incidencia_padre_id`),
  KEY `idx_busqueda_reincidencia` (`equipo_id`,`categoria_id`,`fecha_evento`),
  KEY `fk_incidencia_proveedor` (`proveedor_escalado_id`),
  KEY `idx_archivada` (`archivada`),
  CONSTRAINT `fk_incidencia_proveedor` FOREIGN KEY (`proveedor_escalado_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `incidencias_ibfk_10` FOREIGN KEY (`reportado_por_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `incidencias_ibfk_11` FOREIGN KEY (`asignado_a_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_12` FOREIGN KEY (`resuelto_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_13` FOREIGN KEY (`incidencia_padre_id`) REFERENCES `incidencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_14` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `incidencias_ibfk_15` FOREIGN KEY (`actualizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`),
  CONSTRAINT `incidencias_ibfk_3` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_4` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_5` FOREIGN KEY (`tipo_trabajo_id`) REFERENCES `tipos_trabajo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_6` FOREIGN KEY (`severidad_id`) REFERENCES `severidades` (`id`),
  CONSTRAINT `incidencias_ibfk_7` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`),
  CONSTRAINT `incidencias_ibfk_8` FOREIGN KEY (`origen_reporte_id`) REFERENCES `origenes_reporte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidencias_ibfk_9` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias_adjuntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incidencia_id` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta` varchar(500) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano_bytes` int(11) DEFAULT NULL,
  `momento` varchar(20) DEFAULT 'durante',
  `descripcion` varchar(255) DEFAULT NULL,
  `subido_por_id` int(11) NOT NULL,
  `subido_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subido_por_id` (`subido_por_id`),
  KEY `idx_incidencia` (`incidencia_id`),
  CONSTRAINT `incidencias_adjuntos_ibfk_1` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidencias_adjuntos_ibfk_2` FOREIGN KEY (`subido_por_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias_comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incidencia_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `es_interno` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_incidencia` (`incidencia_id`),
  CONSTRAINT `incidencias_comentarios_ibfk_1` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidencias_comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias_etiquetas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incidencia_id` int(11) NOT NULL,
  `etiqueta` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_incidencia_etiqueta` (`incidencia_id`,`etiqueta`),
  KEY `idx_etiqueta` (`etiqueta`),
  CONSTRAINT `incidencias_etiquetas_ibfk_1` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incidencias_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incidencia_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `campo` varchar(100) DEFAULT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_incidencia` (`incidencia_id`),
  KEY `idx_fecha` (`creado_en`),
  CONSTRAINT `incidencias_historial_ibfk_1` FOREIGN KEY (`incidencia_id`) REFERENCES `incidencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidencias_historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_programada` date NOT NULL,
  `hora_programada` time DEFAULT NULL,
  `asignado_a_id` int(11) DEFAULT NULL COMMENT 'Tecnico asignado',
  `proveedor_id` int(11) DEFAULT NULL COMMENT 'Si lo hace un proveedor externo',
  `estado` enum('programado','proximo','en_progreso','completado','cancelado','vencido') NOT NULL DEFAULT 'programado',
  `es_recurrente` tinyint(1) NOT NULL DEFAULT 0,
  `recurrencia_tipo` enum('dias','semanas','meses','anios') DEFAULT NULL,
  `recurrencia_valor` int(11) DEFAULT NULL COMMENT 'Cada cuantas unidades (ej. 3 meses)',
  `mantenimiento_padre_id` int(11) DEFAULT NULL COMMENT 'Si fue auto-generado, apunta al original',
  `fecha_inicio_real` datetime DEFAULT NULL,
  `fecha_completado` datetime DEFAULT NULL,
  `realizado_por_id` int(11) DEFAULT NULL COMMENT 'Quien lo ejecuto realmente',
  `resultado` text DEFAULT NULL COMMENT 'Notas de lo que se hizo',
  `costo` decimal(10,2) DEFAULT NULL,
  `incidencia_generada_id` int(11) DEFAULT NULL COMMENT 'Si se convirtio en incidencia',
  `creado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_equipo` (`equipo_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_programada`),
  KEY `idx_asignado` (`asignado_a_id`),
  KEY `idx_padre` (`mantenimiento_padre_id`),
  KEY `fk_mant_proveedor` (`proveedor_id`),
  KEY `fk_mant_realizado` (`realizado_por_id`),
  KEY `fk_mant_creador` (`creado_por_id`),
  KEY `fk_mant_incidencia` (`incidencia_generada_id`),
  CONSTRAINT `fk_mant_asignado` FOREIGN KEY (`asignado_a_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mant_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mant_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mant_incidencia` FOREIGN KEY (`incidencia_generada_id`) REFERENCES `incidencias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mant_padre` FOREIGN KEY (`mantenimiento_padre_id`) REFERENCES `mantenimientos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mant_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mant_realizado` FOREIGN KEY (`realizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `enlace` varchar(500) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `leida_en` datetime DEFAULT NULL,
  `creada_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_leida` (`usuario_id`,`leida`),
  KEY `idx_fecha` (`creada_en`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `origenes_reporte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plantillas_incidencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Para mostrar en la lista de plantillas',
  `icono` varchar(50) DEFAULT 'file-text' COMMENT 'Nombre del icono Lucide',
  `color` varchar(7) DEFAULT '#6B7280',
  `titulo` varchar(255) DEFAULT NULL,
  `descripcion_inc` text DEFAULT NULL COMMENT 'Descripcion del problema pre-rellenada',
  `area_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `subcategoria_id` int(11) DEFAULT NULL,
  `tipo_trabajo_id` int(11) DEFAULT NULL,
  `severidad_id` int(11) DEFAULT NULL,
  `origen_reporte_id` int(11) DEFAULT NULL,
  `solucion_sugerida` text DEFAULT NULL COMMENT 'Solucion tipica para este problema',
  `usos` int(11) NOT NULL DEFAULT 0 COMMENT 'Veces que se ha usado esta plantilla',
  `creado_por_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_usos` (`usos`),
  KEY `fk_plantilla_area` (`area_id`),
  KEY `fk_plantilla_categoria` (`categoria_id`),
  KEY `fk_plantilla_subcategoria` (`subcategoria_id`),
  KEY `fk_plantilla_tipo` (`tipo_trabajo_id`),
  KEY `fk_plantilla_severidad` (`severidad_id`),
  KEY `fk_plantilla_origen` (`origen_reporte_id`),
  KEY `fk_plantilla_creador` (`creado_por_id`),
  CONSTRAINT `fk_plantilla_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_origen` FOREIGN KEY (`origen_reporte_id`) REFERENCES `origenes_reporte` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_severidad` FOREIGN KEY (`severidad_id`) REFERENCES `severidades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_subcategoria` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plantilla_tipo` FOREIGN KEY (`tipo_trabajo_id`) REFERENCES `tipos_trabajo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor_contactos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre de la persona contacto',
  `puesto` varchar(100) DEFAULT NULL COMMENT 'ej. Asesor de basculas, Soporte',
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL COMMENT 'ej. Solo turno matutino',
  `es_principal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marca el contacto principal',
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_contacto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor_marcas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proveedor_marca` (`proveedor_id`,`marca`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_marca_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor_tipos_equipo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proveedor_tipo` (`proveedor_id`,`tipo`),
  KEY `idx_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_tipo_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre comercial',
  `razon_social` varchar(200) DEFAULT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `servicio` varchar(255) DEFAULT NULL COMMENT 'Descripcion corta del servicio que ofrece',
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sitio_web` varchar(200) DEFAULT NULL,
  `horario_atencion` varchar(255) DEFAULT NULL COMMENT 'ej. Lun-Vie 9-18hr',
  `calificacion` tinyint(3) unsigned DEFAULT NULL COMMENT '1-5 estrellas',
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nombre` (`nombre`),
  KEY `idx_activo` (`activo`),
  KEY `fk_proveedor_creador` (`creado_por_id`),
  CONSTRAINT `fk_proveedor_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recordatorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL COMMENT 'A quién se le envía',
  `titulo` varchar(200) NOT NULL,
  `mensaje` varchar(500) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL COMMENT 'Cuándo enviar',
  `enlace` varchar(255) DEFAULT NULL,
  `entidad` varchar(50) DEFAULT NULL,
  `entidad_id` int(11) DEFAULT NULL,
  `enviado` tinyint(1) NOT NULL DEFAULT 0,
  `enviado_en` timestamp NULL DEFAULT NULL,
  `creado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_enviar` (`enviado`,`fecha_envio`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `fk_rec_creador` (`creado_por_id`),
  CONSTRAINT `fk_rec_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rec_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reglas_asignacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre descriptivo de la regla',
  `descripcion` varchar(255) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `tipo_trabajo_id` int(11) DEFAULT NULL,
  `severidad_id` int(11) DEFAULT NULL,
  `asignar_a_id` int(11) NOT NULL,
  `prioridad` int(11) NOT NULL DEFAULT 100 COMMENT 'Menor = se evalúa antes',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `veces_aplicada` int(11) NOT NULL DEFAULT 0,
  `creado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activa_prioridad` (`activa`,`prioridad`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_area` (`area_id`),
  KEY `fk_regla_categoria` (`categoria_id`),
  KEY `fk_regla_tipo` (`tipo_trabajo_id`),
  KEY `fk_regla_severidad` (`severidad_id`),
  KEY `fk_regla_asignar` (`asignar_a_id`),
  KEY `fk_regla_creador` (`creado_por_id`),
  CONSTRAINT `fk_regla_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regla_asignar` FOREIGN KEY (`asignar_a_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regla_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regla_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_regla_severidad` FOREIGN KEY (`severidad_id`) REFERENCES `severidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regla_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regla_tipo` FOREIGN KEY (`tipo_trabajo_id`) REFERENCES `tipos_trabajo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `puede_administrar` tinyint(1) NOT NULL DEFAULT 0,
  `puede_ver_todas_sucursales` tinyint(1) NOT NULL DEFAULT 0,
  `puede_resolver` tinyint(1) NOT NULL DEFAULT 0,
  `puede_crear_solicitud` tinyint(1) NOT NULL DEFAULT 1,
  `puede_ver_reportes` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL COMMENT 'PHP session_id()',
  `ip` varchar(45) DEFAULT NULL COMMENT 'IPv4 o IPv6',
  `user_agent` varchar(500) DEFAULT NULL,
  `dispositivo` varchar(100) DEFAULT NULL COMMENT 'Dispositivo detectado (Windows, Mac, Android, iPhone, etc)',
  `navegador` varchar(50) DEFAULT NULL COMMENT 'Navegador detectado',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `motivo_cierre` varchar(100) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cerrada_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_id` (`session_id`),
  KEY `idx_usuario_activa` (`usuario_id`,`activa`),
  KEY `idx_creado` (`creado_en`),
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `severidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `nivel` int(11) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#6B7280',
  `sla_horas` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `nivel` (`nivel`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subcategorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categoria_nombre` (`categoria_id`,`nombre`),
  CONSTRAINT `subcategorias_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sucursal_plantas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL COMMENT 'Ej: Planta baja, Piso 1, Bodega',
  `orden` int(11) NOT NULL DEFAULT 0 COMMENT 'Para ordenar las pestañas',
  `plano_url` varchar(255) DEFAULT NULL,
  `plano_subido_en` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sucursal` (`sucursal_id`,`orden`),
  CONSTRAINT `fk_planta_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `responsable` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipos_trabajo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#6B7280',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL COMMENT 'Ruta relativa de la foto de perfil',
  `pagina_inicio_preferida` varchar(100) DEFAULT 'dashboard.php',
  `telefono` varchar(50) DEFAULT NULL,
  `rol_id` int(11) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `puesto` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime DEFAULT current_timestamp(),
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `rol_id` (`rol_id`),
  KEY `idx_usuario_activo` (`usuario`,`activo`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_area` (`area_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_accesos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` enum('ver_password','copiar_password','ver_entrada') NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entrada` (`entrada_id`,`creado_en`),
  KEY `idx_usuario` (`usuario_id`,`creado_en`),
  CONSTRAINT `fk_acc_entrada` FOREIGN KEY (`entrada_id`) REFERENCES `vault_entradas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `familia` varchar(60) NOT NULL COMMENT 'Grupo visual: Acceso, Infraestructura, etc.',
  `familia_orden` int(11) NOT NULL DEFAULT 0,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(50) DEFAULT 'folder',
  `color` varchar(20) DEFAULT '#71717a',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_familia` (`familia`,`orden`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `usuario` varchar(200) DEFAULT NULL,
  `password_cifrado` text DEFAULT NULL COMMENT 'AES-256 encrypted',
  `notas` text DEFAULT NULL COMMENT 'Markdown libre',
  `archivos` text DEFAULT NULL COMMENT 'Rutas UNC u observaciones, editable',
  `version_build` varchar(100) DEFAULT NULL COMMENT 'Para instaladores/drivers',
  `vencimiento` date DEFAULT NULL COMMENT 'Para licencias/certificados',
  `tags` varchar(500) DEFAULT NULL COMMENT 'Coma-separadas',
  `sucursal_id` int(11) DEFAULT NULL COMMENT 'NULL = todas / N/A',
  `sensibilidad` enum('normal','alta','critica') NOT NULL DEFAULT 'normal',
  `permisos_tipo` enum('todos','rol','sucursal','usuarios','admin') NOT NULL DEFAULT 'admin' COMMENT 'todos=visible para todos / rol=por roles_ids / sucursal=por sucursales_ids / usuarios=lista específica / admin=solo admin',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por_id` int(11) DEFAULT NULL,
  `actualizado_por_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_activo` (`activo`),
  KEY `fk_vault_creador` (`creado_por_id`),
  KEY `fk_vault_actualizador` (`actualizado_por_id`),
  CONSTRAINT `fk_vault_actualizador` FOREIGN KEY (`actualizado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vault_cat` FOREIGN KEY (`categoria_id`) REFERENCES `vault_categorias` (`id`),
  CONSTRAINT `fk_vault_creador` FOREIGN KEY (`creado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vault_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_favoritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entrada_usuario` (`entrada_id`,`usuario_id`),
  KEY `fk_fav_usuario` (`usuario_id`),
  CONSTRAINT `fk_fav_entrada` FOREIGN KEY (`entrada_id`) REFERENCES `vault_entradas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` enum('crear','editar','eliminar','password_cambiada','permisos_cambiados') NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entrada` (`entrada_id`,`creado_en`),
  KEY `fk_hist_usuario` (`usuario_id`),
  CONSTRAINT `fk_hist_entrada` FOREIGN KEY (`entrada_id`) REFERENCES `vault_entradas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vault_permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `tipo` enum('rol','usuario','sucursal') NOT NULL,
  `referencia_id` int(11) NOT NULL COMMENT 'ID del rol, usuario o sucursal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_entrada_tipo_ref` (`entrada_id`,`tipo`,`referencia_id`),
  KEY `idx_entrada` (`entrada_id`),
  CONSTRAINT `fk_perm_entrada` FOREIGN KEY (`entrada_id`) REFERENCES `vault_entradas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ============================================================================
-- ============================================================================
-- DATOS BASE: roles, sucursales, severidades, estados (con columnas reales)
-- ============================================================================

INSERT INTO roles (id, nombre, descripcion, puede_administrar, puede_ver_todas_sucursales, puede_resolver, puede_crear_solicitud, puede_ver_reportes, activo, creado_en) VALUES
(1,'Administrador','Control total del sistema, configura todo',1,1,1,1,1,1,'2026-01-01 00:00:00'),
(2,'Técnico de Mantenimiento','Atiende y resuelve órdenes de trabajo en todas las plantas',0,1,1,1,1,1,'2026-01-01 00:00:00'),
(3,'Supervisor de Planta','Supervisa su planta y genera reportes',0,0,0,1,1,1,'2026-01-01 00:00:00'),
(4,'Operador / Reportante','Reporta fallas y da seguimiento a las órdenes de su área',0,0,0,1,0,1,'2026-01-01 00:00:00'),
(5,'Solo Lectura','Consulta y filtra sin modificar',0,1,0,0,1,1,'2026-01-01 00:00:00');

INSERT INTO sucursales (id, nombre, codigo, direccion, telefono, responsable, activo, creado_en, actualizado_en) VALUES
(1,'Bacal','BAC','Av. Cruz del Sur 2025, Fracc. Las Huertas 3ra. Sección, Tijuana','(664) 972 06 31','Alberto Martinez',1,'2026-01-01 00:00:00','2026-01-01 00:00:00'),
(2,'Ferias','FER','De las Ferias 84, Lomas Hipodromo, 22030 Tijuana, B.C.','664 104 1093','Omar',1,'2026-01-01 00:00:00','2026-01-01 00:00:00');

INSERT INTO severidades (id, nombre, nivel, color, sla_horas, descripcion, activo) VALUES
(1,'Crítica',1,'#DC2626',2,'Línea o equipo crítico detenido, afecta producción',1),
(2,'Alta',2,'#EA580C',8,'Afecta producción pero hay alternativas',1),
(3,'Media',3,'#D97706',24,'Sin afectación inmediata a producción',1),
(4,'Baja',4,'#16A34A',72,'Programable, sin urgencia',1);

INSERT INTO estados (id, nombre, orden, color, es_inicial, es_final, descripcion, activo) VALUES
(1,'Abierta',1,'#DC2626',1,0,'Recién registrada, sin atender',1),
(2,'Asignada',2,'#EA580C',0,0,'Asignada a un técnico',1),
(3,'En proceso',3,'#D97706',0,0,'Siendo atendida activamente',1),
(4,'En espera de refacción',4,'#8B5CF6',0,0,'Esperando pieza para continuar',1),
(5,'En espera',5,'#6B7280',0,0,'Esperando información o terceros',1),
(6,'Resuelta',6,'#0EA5E9',0,0,'Solucionada, pendiente de confirmación',1),
(7,'Completada',7,'#16A34A',0,1,'Confirmada y cerrada',1),
(8,'Cancelada',8,'#6B7280',0,1,'Anulada sin resolución',1);

-- ============================================================================
-- ÁREAS (zonas específicas de planta de cárnicos)
-- ============================================================================
INSERT INTO areas (nombre, color, activo) VALUES
('Recepción de carne',         '#0EA5E9', 1),
('Cámaras frigoríficas',       '#06B6D4', 1),
('Sala de despiece',           '#DC2626', 1),
('Línea de empaque',           '#8B5CF6', 1),
('Línea de embutidos',         '#7C3AED', 1),
('Hornos y ahumadores',        '#F59E0B', 1),
('Cuarto de máquinas',         '#0F766E', 1),
('Cuarto eléctrico',           '#EAB308', 1),
('Tratamiento de agua',        '#3B82F6', 1),
('Calderas',                   '#B91C1C', 1),
('Sistema de vapor',           '#991B1B', 1),
('Andén de carga/descarga',    '#71717A', 1),
('Oficinas',                   '#475569', 1),
('Taller de mantenimiento',    '#374151', 1),
('Estacionamiento',            '#52525B', 1),
('Áreas comunes',              '#9CA3AF', 1);

-- ============================================================================
-- CATEGORÍAS (disciplinas de mantenimiento)
-- ============================================================================
INSERT INTO categorias (nombre, color, activo) VALUES
('Mecánica',              '#DC2626', 1),
('Eléctrica',             '#F59E0B', 1),
('Electrónica/Control',   '#7C3AED', 1),
('Hidráulica',            '#3B82F6', 1),
('Neumática',             '#10B981', 1),
('Refrigeración',         '#06B6D4', 1),
('Vapor y calderas',      '#991B1B', 1),
('Soldadura',             '#EA580C', 1),
('Lubricación',           '#84CC16', 1),
('Limpieza industrial',   '#EC4899', 1),
('Edificación/Civil',     '#71717a', 1),
('Seguridad industrial',  '#EF4444', 1),
('Instrumentación',       '#0891B2', 1),
('Tratamiento de agua',   '#0284C7', 1),
('Otro',                  '#525252', 1);

-- ============================================================================
-- TIPOS DE TRABAJO
-- ============================================================================
INSERT INTO tipos_trabajo (nombre, color, activo) VALUES
('Correctivo',            '#DC2626', 1),
('Preventivo',            '#10B981', 1),
('Predictivo',            '#3B82F6', 1),
('Calibración',           '#F59E0B', 1),
('Inspección',            '#06B6D4', 1),
('Limpieza',              '#84CC16', 1),
('Lubricación',           '#8B5CF6', 1),
('Instalación',           '#71717a', 1),
('Modificación/Mejora',   '#EC4899', 1),
('Emergencia',            '#991B1B', 1);

-- ============================================================================
-- ORÍGENES DE REPORTE (sin color, según estructura real)
-- ============================================================================
INSERT INTO origenes_reporte (nombre, activo) VALUES
('Reporte de operador',       1),
('Inspección de rutina',      1),
('Mantenimiento programado',  1),
('Falla detectada en línea',  1),
('Auditoría',                 1),
('Solicitud de supervisor',   1),
('Alarma de sistema',         1),
('Otro',                      1);

-- ============================================================================
-- USUARIO ADMIN INICIAL
-- ============================================================================
-- Login: admin / Contraseña: admin123 (cámbiala al primer login)
INSERT INTO usuarios (id, usuario, password_hash, nombre_completo, email, pagina_inicio_preferida, rol_id, sucursal_id, area_id, puesto, activo, debe_cambiar_password, creado_en, actualizado_en) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@carnesbacal.com', 'dashboard.php', 1, 1, NULL, 'Administrador', 1, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

-- ============================================================================
-- PALABRAS CLAVE para sugerencia automática de categoría
-- ============================================================================

-- MECÁNICA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'rodamiento' palabra, 3 peso UNION ALL
    SELECT 'cojinete', 3 UNION ALL
    SELECT 'banda', 3 UNION ALL
    SELECT 'cadena', 3 UNION ALL
    SELECT 'engrane', 3 UNION ALL
    SELECT 'piñon', 3 UNION ALL
    SELECT 'polea', 3 UNION ALL
    SELECT 'eje', 2 UNION ALL
    SELECT 'motor', 2 UNION ALL
    SELECT 'reductor', 3 UNION ALL
    SELECT 'flecha', 2 UNION ALL
    SELECT 'tornillo', 1 UNION ALL
    SELECT 'desgaste', 2 UNION ALL
    SELECT 'vibracion', 3 UNION ALL
    SELECT 'ruido', 2 UNION ALL
    SELECT 'rotura', 3 UNION ALL
    SELECT 'fractura', 3 UNION ALL
    SELECT 'desbalance', 3 UNION ALL
    SELECT 'alineacion', 3
) p WHERE c.nombre = 'Mecánica' LIMIT 100;

-- ELÉCTRICA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'corto circuito' palabra, 3 peso UNION ALL
    SELECT 'fusible', 3 UNION ALL
    SELECT 'breaker', 3 UNION ALL
    SELECT 'interruptor', 3 UNION ALL
    SELECT 'contactor', 3 UNION ALL
    SELECT 'arrancador', 3 UNION ALL
    SELECT 'voltaje', 2 UNION ALL
    SELECT 'amperaje', 2 UNION ALL
    SELECT 'corriente', 2 UNION ALL
    SELECT 'panel', 2 UNION ALL
    SELECT 'tablero', 2 UNION ALL
    SELECT 'cable', 1 UNION ALL
    SELECT 'sin energia', 3 UNION ALL
    SELECT 'no enciende', 2 UNION ALL
    SELECT 'transformador', 3 UNION ALL
    SELECT 'subestacion', 3 UNION ALL
    SELECT 'tierra fisica', 3 UNION ALL
    SELECT 'relevador', 3 UNION ALL
    SELECT 'variador', 3
) p WHERE c.nombre = 'Eléctrica' LIMIT 100;

-- ELECTRÓNICA/CONTROL
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'plc' palabra, 3 peso UNION ALL
    SELECT 'sensor', 3 UNION ALL
    SELECT 'hmi', 3 UNION ALL
    SELECT 'pantalla tactil', 3 UNION ALL
    SELECT 'tarjeta', 2 UNION ALL
    SELECT 'modulo', 2 UNION ALL
    SELECT 'programacion', 2 UNION ALL
    SELECT 'logica', 2 UNION ALL
    SELECT 'fotocelda', 3 UNION ALL
    SELECT 'encoder', 3
) p WHERE c.nombre = 'Electrónica/Control' LIMIT 100;

-- HIDRÁULICA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'fuga' palabra, 3 peso UNION ALL
    SELECT 'aceite' , 2 UNION ALL
    SELECT 'manguera' , 2 UNION ALL
    SELECT 'cilindro' , 3 UNION ALL
    SELECT 'bomba hidraulica' , 3 UNION ALL
    SELECT 'valvula' , 2 UNION ALL
    SELECT 'piston' , 3 UNION ALL
    SELECT 'sin presion' , 3 UNION ALL
    SELECT 'manometro' , 2 UNION ALL
    SELECT 'sello hidraulico' , 3
) p WHERE c.nombre = 'Hidráulica' LIMIT 100;

-- NEUMÁTICA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'aire comprimido' palabra, 3 peso UNION ALL
    SELECT 'compresor de aire' , 3 UNION ALL
    SELECT 'electrovalvula' , 3 UNION ALL
    SELECT 'piston neumatico' , 3 UNION ALL
    SELECT 'manguera neumatica' , 2 UNION ALL
    SELECT 'fuga de aire' , 3 UNION ALL
    SELECT 'pistola' , 2 UNION ALL
    SELECT 'sin aire' , 3
) p WHERE c.nombre = 'Neumática' LIMIT 100;

-- REFRIGERACIÓN
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'camara fria' palabra, 3 peso UNION ALL
    SELECT 'camara frigorifica' , 3 UNION ALL
    SELECT 'refrigerador' , 3 UNION ALL
    SELECT 'congelador' , 3 UNION ALL
    SELECT 'compresor de refrigeracion' , 3 UNION ALL
    SELECT 'condensador' , 3 UNION ALL
    SELECT 'evaporador' , 3 UNION ALL
    SELECT 'gas refrigerante' , 3 UNION ALL
    SELECT 'no enfria' , 3 UNION ALL
    SELECT 'temperatura alta' , 3 UNION ALL
    SELECT 'descongelar' , 2 UNION ALL
    SELECT 'expansion' , 2 UNION ALL
    SELECT 'r404' , 3 UNION ALL
    SELECT 'r134' , 3 UNION ALL
    SELECT 'amoniaco' , 3
) p WHERE c.nombre = 'Refrigeración' LIMIT 100;

-- VAPOR Y CALDERAS
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'caldera' palabra, 3 peso UNION ALL
    SELECT 'vapor' , 3 UNION ALL
    SELECT 'quemador' , 3 UNION ALL
    SELECT 'trampa de vapor' , 3 UNION ALL
    SELECT 'tubo de caldera' , 3 UNION ALL
    SELECT 'condensado' , 2 UNION ALL
    SELECT 'agua de alimentacion' , 2
) p WHERE c.nombre = 'Vapor y calderas' LIMIT 100;

-- SOLDADURA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'soldar' palabra, 3 peso UNION ALL
    SELECT 'soldadura' , 3 UNION ALL
    SELECT 'electrodo' , 3 UNION ALL
    SELECT 'mig' , 3 UNION ALL
    SELECT 'tig' , 3 UNION ALL
    SELECT 'inox' , 2 UNION ALL
    SELECT 'cordon' , 2
) p WHERE c.nombre = 'Soldadura' LIMIT 100;

-- LUBRICACIÓN
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'lubricar' palabra, 3 peso UNION ALL
    SELECT 'engrasar' , 3 UNION ALL
    SELECT 'grasa' , 2 UNION ALL
    SELECT 'graseras' , 3 UNION ALL
    SELECT 'rutina de lubricacion' , 3
) p WHERE c.nombre = 'Lubricación' LIMIT 100;

-- INSTRUMENTACIÓN
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'calibrar' palabra, 3 peso UNION ALL
    SELECT 'calibracion' , 3 UNION ALL
    SELECT 'medidor' , 3 UNION ALL
    SELECT 'transductor' , 3 UNION ALL
    SELECT 'termopar' , 3 UNION ALL
    SELECT 'rtd' , 3 UNION ALL
    SELECT 'transmisor' , 3 UNION ALL
    SELECT 'controlador' , 2
) p WHERE c.nombre = 'Instrumentación' LIMIT 100;

-- TRATAMIENTO DE AGUA
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'osmosis' palabra, 3 peso UNION ALL
    SELECT 'suavizador' , 3 UNION ALL
    SELECT 'filtro de agua' , 3 UNION ALL
    SELECT 'bomba de agua' , 3 UNION ALL
    SELECT 'tinaco' , 2 UNION ALL
    SELECT 'cisterna' , 2
) p WHERE c.nombre = 'Tratamiento de agua' LIMIT 100;

-- SEGURIDAD INDUSTRIAL
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'extintor' palabra, 3 peso UNION ALL
    SELECT 'epp' , 3 UNION ALL
    SELECT 'guarda' , 3 UNION ALL
    SELECT 'paro de emergencia' , 3 UNION ALL
    SELECT 'señalizacion' , 2 UNION ALL
    SELECT 'loto' , 3 UNION ALL
    SELECT 'bloqueo etiquetado' , 3
) p WHERE c.nombre = 'Seguridad industrial' LIMIT 100;

-- LIMPIEZA INDUSTRIAL
INSERT INTO categorias_palabras_clave (categoria_id, palabra, peso)
SELECT id, p.palabra, p.peso FROM categorias c, (
    SELECT 'limpieza profunda' palabra, 3 peso UNION ALL
    SELECT 'sanitizar' , 3 UNION ALL
    SELECT 'cip' , 3 UNION ALL
    SELECT 'sip' , 3 UNION ALL
    SELECT 'lavado' , 2
) p WHERE c.nombre = 'Limpieza industrial' LIMIT 100;

-- ============================================================================
-- VAULT: Categorías adaptadas para Mantenimiento
-- ============================================================================

-- Familia 1: MECÁNICA
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Mecánica', 1, 'Manuales de fabricante (mecánica)', 'book-open', '#DC2626', 1, 1),
('Mecánica', 1, 'Despieces y explosionados',         'layers', '#B91C1C', 2, 1),
('Mecánica', 1, 'Fichas técnicas de equipos',        'file-text', '#991B1B', 3, 1);

-- Familia 2: ELÉCTRICA
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Eléctrica', 2, 'Planos eléctricos',                'workflow', '#F59E0B', 10, 1),
('Eléctrica', 2, 'Diagramas de tableros',            'layout-grid', '#D97706', 11, 1),
('Eléctrica', 2, 'Esquemas unifilares',              'git-branch', '#B45309', 12, 1);

-- Familia 3: REFRIGERACIÓN
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Refrigeración', 3, 'Manuales de compresores',      'snowflake', '#06B6D4', 20, 1),
('Refrigeración', 3, 'Cartas de refrigerante',       'thermometer', '#0891B2', 21, 1),
('Refrigeración', 3, 'Curvas presión-temperatura',   'line-chart', '#0E7490', 22, 1);

-- Familia 4: HIDRÁULICA/NEUMÁTICA
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Hidráulica/Neumática', 4, 'Manuales hidráulicos y neumáticos', 'droplet', '#3B82F6', 30, 1),
('Hidráulica/Neumática', 4, 'Diagramas de circuitos',            'workflow', '#2563EB', 31, 1),
('Hidráulica/Neumática', 4, 'Hojas técnicas de bombas',          'gauge', '#1D4ED8', 32, 1);

-- Familia 5: SEGURIDAD
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Seguridad', 5, 'MSDS / Hojas de seguridad',        'shield-alert', '#EF4444', 40, 1),
('Seguridad', 5, 'Protocolos y procedimientos seguros', 'shield-check', '#DC2626', 41, 1),
('Seguridad', 5, 'Bloqueo y etiquetado (LOTO)',      'lock', '#991B1B', 42, 1);

-- Familia 6: EQUIPOS CRÍTICOS
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Equipos críticos', 6, 'Garantías de equipos',      'badge-check', '#10B981', 50, 1),
('Equipos críticos', 6, 'Pólizas de servicio',       'file-check', '#059669', 51, 1),
('Equipos críticos', 6, 'Contactos de servicio especializado', 'phone', '#047857', 52, 1);

-- Familia 7: CALIBRACIONES
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Calibraciones', 7, 'Certificados de calibración',  'award', '#8B5CF6', 60, 1),
('Calibraciones', 7, 'Bitácora de calibraciones',    'clipboard-list', '#7C3AED', 61, 1),
('Calibraciones', 7, 'Patrones y trazabilidad',      'ruler', '#6D28D9', 62, 1);

-- Familia 8: REFACCIONES
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Refacciones', 8, 'Catálogos de refacciones',       'book', '#F59E0B', 70, 1),
('Refacciones', 8, 'Listas maestras',                'list', '#D97706', 71, 1),
('Refacciones', 8, 'Equivalencias entre marcas',     'arrow-left-right', '#B45309', 72, 1);

-- Familia 9: PROCEDIMIENTOS
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Procedimientos', 9, 'Arranque y paro de equipos',  'power', '#0EA5E9', 80, 1),
('Procedimientos', 9, 'Limpieza CIP/SIP',            'spray-can', '#0284C7', 81, 1),
('Procedimientos', 9, 'Cambios de turno',            'clock', '#0369A1', 82, 1);

-- Familia 10: REPORTES
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Reportes', 10, 'Plantillas de reporte',            'file-spreadsheet', '#16A34A', 90, 1),
('Reportes', 10, 'Bitácoras maestras',               'book-open', '#15803D', 91, 1),
('Reportes', 10, 'Inventarios',                      'boxes', '#166534', 92, 1);

-- Familia 11: LEGAL
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('Legal y administrativo', 11, 'Permisos y licencias',       'file-badge', '#7C2D12', 100, 1),
('Legal y administrativo', 11, 'Certificaciones (ISO, etc.)','award', '#92400E', 101, 1),
('Legal y administrativo', 11, 'Auditorías y dictámenes',    'gavel', '#78350F', 102, 1);

-- Familia 12: GENERAL
INSERT INTO vault_categorias (familia, familia_orden, nombre, icono, color, orden, activo) VALUES
('General', 12, 'Acceso rápido',                     'star', '#EAB308', 110, 1),
('General', 12, 'Otros / Archivado',                 'archive', '#71717A', 111, 1);


-- ============================================================================
-- VERIFICACIÓN
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;

SELECT '== BD mantenimiento_bacal lista ==' AS estado;
SELECT 'Roles:' tabla, COUNT(*) total FROM roles
UNION ALL SELECT 'Sucursales:', COUNT(*) FROM sucursales
UNION ALL SELECT 'Severidades:', COUNT(*) FROM severidades
UNION ALL SELECT 'Estados:', COUNT(*) FROM estados
UNION ALL SELECT 'Áreas:', COUNT(*) FROM areas
UNION ALL SELECT 'Categorías:', COUNT(*) FROM categorias
UNION ALL SELECT 'Tipos trabajo:', COUNT(*) FROM tipos_trabajo
UNION ALL SELECT 'Orígenes:', COUNT(*) FROM origenes_reporte
UNION ALL SELECT 'Palabras clave:', COUNT(*) FROM categorias_palabras_clave
UNION ALL SELECT 'Categorías Vault:', COUNT(*) FROM vault_categorias
UNION ALL SELECT 'Usuarios:', COUNT(*) FROM usuarios;

-- ============================================================================
-- SIGUIENTE PASO:
-- ============================================================================
-- 1. Configurar config/db.php en la copia del proyecto BitacoraMantenimiento:
--      DB_NAME = 'mantenimiento_bacal'
--      APP_NAME = 'Bitácora de Mantenimiento'
--
-- 2. Login con admin / admin123 (cambia la contraseña al entrar)
--
-- 3. Espera Bloque 2 para agregar:
--    - Componentes de equipo
--    - Refacciones / Almacén
--    - Herramientas
-- ============================================================================
