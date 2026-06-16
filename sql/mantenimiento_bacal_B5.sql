-- ============================================================================
-- mantenimiento_bacal_B5.sql
-- ============================================================================
-- BLOQUE 5: Herramientas y préstamos
--
-- 2 tablas:
--   herramientas             - Catálogo de herramientas con estado
--   herramientas_prestamos   - Historial de préstamos a técnicos
-- ============================================================================

USE mantenimiento_bacal;

-- ============================================================================
-- Tabla: herramientas
-- ============================================================================
CREATE TABLE herramientas (
    id              INT NOT NULL AUTO_INCREMENT,

    -- Identificación
    codigo          VARCHAR(50) NOT NULL COMMENT 'Código interno único, ej. HER-001',
    nombre          VARCHAR(200) NOT NULL,
    descripcion     TEXT DEFAULT NULL,

    -- Datos técnicos
    tipo            VARCHAR(80) DEFAULT NULL COMMENT 'Eléctrica, manual, medición, etc.',
    marca           VARCHAR(100) DEFAULT NULL,
    modelo          VARCHAR(100) DEFAULT NULL,
    numero_serie    VARCHAR(100) DEFAULT NULL,

    -- Ubicación habitual y sucursal
    sucursal_id     INT NOT NULL COMMENT 'Sucursal donde se almacena',
    ubicacion       VARCHAR(150) DEFAULT NULL COMMENT 'ej. Taller, Anaquel B-2',

    -- Estado y disponibilidad
    estado          ENUM('disponible','prestada','en_reparacion','extraviada','baja') NOT NULL DEFAULT 'disponible',

    -- Préstamo activo (referencia rápida)
    prestamo_activo_id INT DEFAULT NULL COMMENT 'ID del préstamo activo si está prestada',

    -- Comercial
    fecha_adquisicion DATE DEFAULT NULL,
    costo           DECIMAL(10,2) DEFAULT NULL,
    proveedor_id    INT DEFAULT NULL,

    -- Multimedia
    foto_url        VARCHAR(255) DEFAULT NULL,

    -- Notas
    notas           TEXT DEFAULT NULL,

    -- Control
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    creado_por_id   INT DEFAULT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo),
    KEY idx_estado (estado),
    KEY idx_sucursal (sucursal_id),
    KEY idx_tipo (tipo),

    CONSTRAINT fk_her_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE RESTRICT,
    CONSTRAINT fk_her_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    CONSTRAINT fk_her_creador FOREIGN KEY (creado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Tabla: herramientas_prestamos
-- ============================================================================
CREATE TABLE herramientas_prestamos (
    id              INT NOT NULL AUTO_INCREMENT,
    herramienta_id  INT NOT NULL,

    -- A quién se prestó
    prestada_a_id   INT NOT NULL COMMENT 'Usuario al que se le prestó',
    autorizada_por_id INT NOT NULL COMMENT 'Quien autorizó el préstamo',

    -- Fechas
    fecha_salida    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_esperada DATE DEFAULT NULL,
    fecha_devolucion_real TIMESTAMP NULL DEFAULT NULL,
    recibida_por_id INT DEFAULT NULL COMMENT 'Quien recibe la devolución',

    -- Motivo y contexto
    motivo          VARCHAR(255) DEFAULT NULL COMMENT 'Para qué se necesita',
    incidencia_id   INT DEFAULT NULL COMMENT 'Si se presta para una orden específica',

    -- Estado del préstamo
    estado          ENUM('activo','devuelta','devuelta_con_dano','extraviada') NOT NULL DEFAULT 'activo',
    condicion_devolucion ENUM('buena','dañada','extraviada','reparada') DEFAULT NULL,
    notas_salida    TEXT DEFAULT NULL,
    notas_devolucion TEXT DEFAULT NULL,

    PRIMARY KEY (id),
    KEY idx_herramienta (herramienta_id, fecha_salida DESC),
    KEY idx_usuario (prestada_a_id, estado),
    KEY idx_estado_fecha (estado, fecha_devolucion_esperada),
    KEY idx_incidencia (incidencia_id),

    CONSTRAINT fk_pres_herramienta FOREIGN KEY (herramienta_id) REFERENCES herramientas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pres_usuario FOREIGN KEY (prestada_a_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pres_autoriza FOREIGN KEY (autorizada_por_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pres_recibe FOREIGN KEY (recibida_por_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_pres_incidencia FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- FK ciclica: prestamo_activo_id en herramientas referencia herramientas_prestamos
-- (Se agrega DESPUÉS de crear ambas tablas)
-- ============================================================================
ALTER TABLE herramientas
    ADD CONSTRAINT fk_her_prestamo_activo
    FOREIGN KEY (prestamo_activo_id) REFERENCES herramientas_prestamos(id) ON DELETE SET NULL;


-- ============================================================================
-- Verificación
-- ============================================================================
SELECT 'Bloque B5 instalado' AS estado;
SELECT 'herramientas', COUNT(*) FROM herramientas
UNION ALL SELECT 'herramientas_prestamos', COUNT(*) FROM herramientas_prestamos;
