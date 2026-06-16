-- ============================================================================
-- mantenimiento_bacal_B4.sql
-- ============================================================================
-- BLOQUE 4: Integración refacciones <-> incidencias
--
-- 1 tabla nueva:
--   incidencia_refacciones - Refacciones usadas en cada orden de trabajo
--                            con descuento automático de stock
-- ============================================================================

USE mantenimiento_bacal;

-- ============================================================================
-- Tabla: incidencia_refacciones
-- ============================================================================
CREATE TABLE incidencia_refacciones (
    id              INT NOT NULL AUTO_INCREMENT,
    incidencia_id   INT NOT NULL,
    refaccion_id    INT NOT NULL,

    -- Cantidad y costo
    cantidad        DECIMAL(10,2) NOT NULL,
    costo_unitario  DECIMAL(10,2) DEFAULT NULL COMMENT 'Costo al momento de usar',
    costo_total     DECIMAL(12,2) DEFAULT NULL COMMENT 'cantidad * costo_unitario',

    -- Vinculación con movimiento de stock
    movimiento_id   INT DEFAULT NULL COMMENT 'ID del movimiento de salida generado',
    componente_id   INT DEFAULT NULL COMMENT 'Si reemplazó un componente específico',

    -- Notas
    notas           VARCHAR(500) DEFAULT NULL,

    -- Auditoría
    usuario_id      INT NOT NULL,
    creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_incidencia (incidencia_id),
    KEY idx_refaccion (refaccion_id),
    KEY idx_componente (componente_id),

    CONSTRAINT fk_incref_incidencia FOREIGN KEY (incidencia_id) REFERENCES incidencias(id) ON DELETE CASCADE,
    CONSTRAINT fk_incref_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE RESTRICT,
    CONSTRAINT fk_incref_movimiento FOREIGN KEY (movimiento_id) REFERENCES refacciones_movimientos(id) ON DELETE SET NULL,
    CONSTRAINT fk_incref_componente FOREIGN KEY (componente_id) REFERENCES equipo_componentes(id) ON DELETE SET NULL,
    CONSTRAINT fk_incref_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- Verificación
-- ============================================================================
SELECT 'Bloque B4 instalado' AS estado;
SELECT 'incidencia_refacciones', COUNT(*) FROM incidencia_refacciones;
