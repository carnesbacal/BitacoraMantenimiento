-- ============================================================================
-- mantenimiento_bacal_B2.sql
-- ============================================================================
-- BLOQUE 2: Componentes de equipo
--
-- Permite definir las partes que componen cada equipo (motor, banda,
-- rodamientos, sensores, etc.) con sus propios datos: marca, modelo,
-- número de parte, fecha de instalación, vida útil estimada.
-- ============================================================================

USE mantenimiento_bacal;

-- ============================================================================
-- Tabla: equipo_componentes
-- ============================================================================
CREATE TABLE equipo_componentes (
    id              INT NOT NULL AUTO_INCREMENT,
    equipo_id       INT NOT NULL,

    -- Identificación del componente
    nombre          VARCHAR(150) NOT NULL COMMENT 'ej. Motor eléctrico, Banda B-50, Filtro de aceite',
    tipo            VARCHAR(80) DEFAULT NULL COMMENT 'ej. Motor, Sensor, Filtro, Banda',
    marca           VARCHAR(100) DEFAULT NULL,
    modelo          VARCHAR(100) DEFAULT NULL,
    numero_parte    VARCHAR(100) DEFAULT NULL COMMENT 'Part number del fabricante',
    numero_serie    VARCHAR(100) DEFAULT NULL,

    -- Vida útil y mantenimiento
    fecha_instalacion DATE DEFAULT NULL,
    vida_util_meses INT DEFAULT NULL COMMENT 'Vida útil estimada en meses',
    proxima_revision DATE DEFAULT NULL,

    -- Costo y proveedor
    costo_unitario  DECIMAL(10,2) DEFAULT NULL,
    proveedor_id    INT DEFAULT NULL,

    -- Estado del componente
    estado          ENUM('operando','desgaste','falla','reemplazado','retirado') NOT NULL DEFAULT 'operando',
    criticidad      ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media'
                    COMMENT 'Impacto de su falla en el equipo padre',

    -- Notas y posición
    posicion        VARCHAR(100) DEFAULT NULL COMMENT 'Ubicación física dentro del equipo (ej. lado izquierdo, etapa 2)',
    notas           TEXT DEFAULT NULL,

    -- Control
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    creado_por_id   INT DEFAULT NULL,
    actualizado_por_id INT DEFAULT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_equipo (equipo_id),
    KEY idx_estado (estado),
    KEY idx_proxima_revision (proxima_revision),

    CONSTRAINT fk_comp_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comp_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    CONSTRAINT fk_comp_creador FOREIGN KEY (creado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_comp_actualizador FOREIGN KEY (actualizado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Tabla: equipo_componentes_historial
-- ============================================================================
-- Registra los cambios/reemplazos de componentes para tener trazabilidad.
-- ============================================================================
CREATE TABLE equipo_componentes_historial (
    id              INT NOT NULL AUTO_INCREMENT,
    componente_id   INT NOT NULL,
    accion          ENUM('instalado','reemplazado','reparado','retirado','revisado') NOT NULL,
    descripcion     VARCHAR(500) DEFAULT NULL,
    incidencia_id   INT DEFAULT NULL COMMENT 'Si el cambio fue por una incidencia/orden',
    usuario_id      INT DEFAULT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_componente (componente_id, creado_en DESC),
    KEY idx_incidencia (incidencia_id),

    CONSTRAINT fk_comp_hist_comp FOREIGN KEY (componente_id) REFERENCES equipo_componentes(id) ON DELETE CASCADE,
    CONSTRAINT fk_comp_hist_inc FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE SET NULL,
    CONSTRAINT fk_comp_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Verificación
-- ============================================================================
SELECT 'Bloque B2 instalado' AS estado;
SELECT 'equipo_componentes', COUNT(*) FROM equipo_componentes
UNION ALL SELECT 'equipo_componentes_historial', COUNT(*) FROM equipo_componentes_historial;
