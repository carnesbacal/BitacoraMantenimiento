-- ============================================================================
-- mantenimiento_bacal_B3.sql
-- ============================================================================
-- BLOQUE 3: Refacciones y Almacén
--
-- 4 tablas:
--   refacciones              - Catálogo maestro de piezas
--   refacciones_stock        - Cantidad por sucursal con mínimos
--   refacciones_movimientos  - Entradas/salidas/ajustes con motivos
--   refacciones_compatibles  - Qué refacción sirve para qué equipo/componente
-- ============================================================================

USE mantenimiento_bacal;

-- ============================================================================
-- Tabla: refacciones (catálogo maestro)
-- ============================================================================
CREATE TABLE refacciones (
    id              INT NOT NULL AUTO_INCREMENT,

    -- Identificación
    codigo          VARCHAR(50) NOT NULL COMMENT 'Código interno único',
    nombre          VARCHAR(200) NOT NULL,
    descripcion     TEXT DEFAULT NULL,

    -- Datos técnicos
    marca           VARCHAR(100) DEFAULT NULL,
    modelo          VARCHAR(100) DEFAULT NULL,
    numero_parte    VARCHAR(100) DEFAULT NULL COMMENT 'Part number del fabricante',
    categoria       VARCHAR(80) DEFAULT NULL COMMENT 'Mecánica, Eléctrica, etc.',

    -- Comercial
    unidad_medida   VARCHAR(20) DEFAULT 'pieza' COMMENT 'pieza, metro, kg, litro',
    costo_unitario  DECIMAL(10,2) DEFAULT NULL,
    proveedor_id    INT DEFAULT NULL,

    -- Multimedia
    foto_url        VARCHAR(255) DEFAULT NULL,

    -- Control
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    creado_por_id   INT DEFAULT NULL,
    actualizado_por_id INT DEFAULT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo),
    KEY idx_nombre (nombre),
    KEY idx_categoria (categoria),
    KEY idx_proveedor (proveedor_id),

    CONSTRAINT fk_ref_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    CONSTRAINT fk_ref_creador FOREIGN KEY (creado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_ref_actualizador FOREIGN KEY (actualizado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Tabla: refacciones_stock (cantidad por sucursal)
-- ============================================================================
CREATE TABLE refacciones_stock (
    id              INT NOT NULL AUTO_INCREMENT,
    refaccion_id    INT NOT NULL,
    sucursal_id     INT NOT NULL,

    cantidad_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
    cantidad_minima DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Alerta cuando stock <= mínimo',
    cantidad_optima DECIMAL(10,2) DEFAULT NULL COMMENT 'Cantidad ideal sugerida',

    ubicacion       VARCHAR(150) DEFAULT NULL COMMENT 'ej. Anaquel A-3, Pasillo 2, Caja 14',

    actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_refaccion_sucursal (refaccion_id, sucursal_id),
    KEY idx_sucursal (sucursal_id),
    KEY idx_stock_bajo (sucursal_id, cantidad_actual),

    CONSTRAINT fk_stock_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Tabla: refacciones_movimientos (entradas/salidas)
-- ============================================================================
CREATE TABLE refacciones_movimientos (
    id              INT NOT NULL AUTO_INCREMENT,
    refaccion_id    INT NOT NULL,
    sucursal_id     INT NOT NULL,

    tipo            ENUM('entrada','salida','ajuste','transferencia') NOT NULL,
    cantidad        DECIMAL(10,2) NOT NULL COMMENT 'Positivo siempre, el tipo define el signo',
    cantidad_antes  DECIMAL(10,2) NOT NULL,
    cantidad_despues DECIMAL(10,2) NOT NULL,

    -- Contexto del movimiento
    motivo          VARCHAR(80) DEFAULT NULL COMMENT 'compra, devolucion, uso_mantenimiento, ajuste_inventario, merma',
    notas           TEXT DEFAULT NULL,
    incidencia_id   INT DEFAULT NULL COMMENT 'Si el movimiento es por una orden de trabajo',
    componente_id   INT DEFAULT NULL COMMENT 'Si reemplaza un componente específico',

    -- Para transferencias entre sucursales
    sucursal_destino_id INT DEFAULT NULL,

    -- Comercial (en entradas: precio compra, en salidas: opcional)
    costo_unitario  DECIMAL(10,2) DEFAULT NULL,

    -- Auditoría
    usuario_id      INT NOT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_refaccion (refaccion_id, creado_en DESC),
    KEY idx_sucursal (sucursal_id, creado_en DESC),
    KEY idx_incidencia (incidencia_id),
    KEY idx_tipo (tipo, creado_en DESC),

    CONSTRAINT fk_mov_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    CONSTRAINT fk_mov_suc_destino FOREIGN KEY (sucursal_destino_id) REFERENCES sucursales(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_incidencia FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_componente FOREIGN KEY (componente_id) REFERENCES equipo_componentes(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Tabla: refacciones_compatibles (qué refacción sirve para qué equipo)
-- ============================================================================
-- Permite vincular una refacción con múltiples equipos o componentes específicos.
-- Útil para que al abrir una incidencia de "Compresor #1" sepas qué refacciones
-- son compatibles antes de pedirlas.
-- ============================================================================
CREATE TABLE refacciones_compatibles (
    id              INT NOT NULL AUTO_INCREMENT,
    refaccion_id    INT NOT NULL,

    -- Una compatibilidad apunta a UN equipo O a UN componente (no ambos)
    equipo_id       INT DEFAULT NULL,
    componente_id   INT DEFAULT NULL,

    notas           VARCHAR(255) DEFAULT NULL COMMENT 'ej. Para etapa 2, lado izquierdo',
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_ref_equipo (refaccion_id, equipo_id),
    UNIQUE KEY uk_ref_componente (refaccion_id, componente_id),
    KEY idx_equipo (equipo_id),
    KEY idx_componente (componente_id),

    CONSTRAINT fk_comp_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_comp_equipo_compat FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    CONSTRAINT fk_comp_componente_compat FOREIGN KEY (componente_id) REFERENCES equipo_componentes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Verificación
-- ============================================================================
SELECT 'Bloque B3 instalado' AS estado;
SELECT 'refacciones', COUNT(*) FROM refacciones
UNION ALL SELECT 'refacciones_stock', COUNT(*) FROM refacciones_stock
UNION ALL SELECT 'refacciones_movimientos', COUNT(*) FROM refacciones_movimientos
UNION ALL SELECT 'refacciones_compatibles', COUNT(*) FROM refacciones_compatibles;
