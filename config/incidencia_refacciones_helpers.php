<?php
/**
 * ============================================================================
 * config/incidencia_refacciones_helpers.php
 * ============================================================================
 * Gestión de refacciones usadas en incidencias (órdenes de trabajo):
 *   - Registrar refacción usada (descuenta stock automáticamente)
 *   - Devolver refacción al stock (cancela el uso)
 *   - Listar refacciones de una incidencia
 *   - Auto-vincular como compatible con el equipo
 * ============================================================================
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/refacciones_helpers.php';


/**
 * Registra una refacción usada en una incidencia.
 * - Verifica stock disponible
 * - Crea movimiento de salida automático
 * - Vincula al componente si aplica
 * - Auto-agrega como compatible con el equipo (si no lo estaba)
 *
 * Retorna el ID del registro creado.
 */
function registrar_refaccion_en_incidencia(array $datos, int $usuario_id): int {
    $incidencia_id = (int) $datos['incidencia_id'];
    $refaccion_id = (int) $datos['refaccion_id'];
    $cantidad = (float) $datos['cantidad'];
    $componente_id = !empty($datos['componente_id']) ? (int) $datos['componente_id'] : null;

    // Validaciones básicas
    if ($cantidad <= 0) {
        throw new RuntimeException('La cantidad debe ser mayor a 0.');
    }

    // Obtener datos de la incidencia (sucursal)
    $inc = db_one(
        "SELECT i.id, i.folio, i.titulo, i.equipo_id, i.sucursal_id
         FROM incidencias i WHERE i.id = :id",
        ['id' => $incidencia_id]
    );
    if (!$inc) {
        throw new RuntimeException('Incidencia no encontrada.');
    }

    // Obtener datos de la refacción
    $ref = obtener_refaccion($refaccion_id);
    if (!$ref) {
        throw new RuntimeException('Refacción no encontrada.');
    }

    // Verificar stock disponible en la sucursal de la incidencia
    $stock = obtener_stock($refaccion_id, (int) $inc['sucursal_id']);
    $disponible = $stock ? (float) $stock['cantidad_actual'] : 0;
    if ($disponible < $cantidad) {
        throw new RuntimeException(
            "Stock insuficiente. Disponible: $disponible " . $ref['unidad_medida'] .
            ", necesitas: $cantidad."
        );
    }

    // Costo al momento
    $costo_unitario = !empty($datos['costo_unitario'])
        ? (float) $datos['costo_unitario']
        : (!empty($ref['costo_unitario']) ? (float) $ref['costo_unitario'] : null);
    $costo_total = $costo_unitario !== null ? round($costo_unitario * $cantidad, 2) : null;

    // Iniciar transacción manual con begin/commit
    db_exec("START TRANSACTION");

    try {
        // 1. Crear movimiento de salida
        $mov_id = registrar_movimiento([
            'refaccion_id' => $refaccion_id,
            'sucursal_id' => (int) $inc['sucursal_id'],
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'motivo' => 'uso_mantenimiento',
            'notas' => "Usada en orden {$inc['folio']}: {$inc['titulo']}",
            'incidencia_id' => $incidencia_id,
            'componente_id' => $componente_id,
            'costo_unitario' => $costo_unitario,
            'usuario_id' => $usuario_id,
        ]);

        // 2. Insertar en incidencia_refacciones
        db_exec(
            "INSERT INTO incidencia_refacciones
             (incidencia_id, refaccion_id, cantidad, costo_unitario, costo_total,
              movimiento_id, componente_id, notas, usuario_id)
             VALUES
             (:iid, :rid, :cant, :cu, :ct, :mid, :cid, :notas, :uid)",
            [
                'iid' => $incidencia_id,
                'rid' => $refaccion_id,
                'cant' => $cantidad,
                'cu' => $costo_unitario,
                'ct' => $costo_total,
                'mid' => $mov_id,
                'cid' => $componente_id,
                'notas' => $datos['notas'] ?? null,
                'uid' => $usuario_id,
            ]
        );
        $reg_id = (int) db_last_id();

        // 3. Auto-vincular como compatible con el equipo (si no estaba)
        if (!empty($inc['equipo_id'])) {
            $existe = db_one(
                "SELECT id FROM refacciones_compatibles
                 WHERE refaccion_id = :rid AND equipo_id = :eid",
                ['rid' => $refaccion_id, 'eid' => $inc['equipo_id']]
            );
            if (!$existe) {
                try {
                    agregar_compatibilidad(
                        $refaccion_id,
                        (int) $inc['equipo_id'],
                        null,
                        "Auto-vinculada desde {$inc['folio']}"
                    );
                } catch (Throwable $e) { /* ignorar */ }
            }
        }

        db_exec("COMMIT");
        return $reg_id;

    } catch (Throwable $e) {
        db_exec("ROLLBACK");
        throw $e;
    }
}


/**
 * Devuelve una refacción al stock (cancela el uso).
 * - Crea movimiento de entrada compensatorio
 * - Elimina el registro de incidencia_refacciones
 */
function devolver_refaccion_de_incidencia(int $registro_id, int $usuario_id, ?string $motivo_dev = null): void {
    $reg = db_one(
        "SELECT ir.*, i.folio AS incidencia_folio, i.sucursal_id
         FROM incidencia_refacciones ir
         INNER JOIN incidencias i ON ir.incidencia_id = i.id
         WHERE ir.id = :id",
        ['id' => $registro_id]
    );

    if (!$reg) {
        throw new RuntimeException('Registro no encontrado.');
    }

    db_exec("START TRANSACTION");

    try {
        // Crear movimiento compensatorio (entrada)
        registrar_movimiento([
            'refaccion_id' => (int) $reg['refaccion_id'],
            'sucursal_id' => (int) $reg['sucursal_id'],
            'tipo' => 'entrada',
            'cantidad' => (float) $reg['cantidad'],
            'motivo' => 'devolucion',
            'notas' => "Devolución de uso en {$reg['incidencia_folio']}" . ($motivo_dev ? " · $motivo_dev" : ''),
            'incidencia_id' => (int) $reg['incidencia_id'],
            'componente_id' => !empty($reg['componente_id']) ? (int) $reg['componente_id'] : null,
            'costo_unitario' => !empty($reg['costo_unitario']) ? (float) $reg['costo_unitario'] : null,
            'usuario_id' => $usuario_id,
        ]);

        // Eliminar el registro
        db_exec("DELETE FROM incidencia_refacciones WHERE id = :id", ['id' => $registro_id]);

        db_exec("COMMIT");
    } catch (Throwable $e) {
        db_exec("ROLLBACK");
        throw $e;
    }
}


/**
 * Lista las refacciones usadas en una incidencia.
 */
function listar_refacciones_de_incidencia(int $incidencia_id): array {
    return db_all(
        "SELECT ir.*,
                r.codigo AS refaccion_codigo, r.nombre AS refaccion_nombre,
                r.unidad_medida, r.categoria,
                c.nombre AS componente_nombre,
                u.nombre_completo AS usuario_nombre
         FROM incidencia_refacciones ir
         INNER JOIN refacciones r ON ir.refaccion_id = r.id
         LEFT JOIN equipo_componentes c ON ir.componente_id = c.id
         LEFT JOIN usuarios u ON ir.usuario_id = u.id
         WHERE ir.incidencia_id = :iid
         ORDER BY ir.creado_en DESC",
        ['iid' => $incidencia_id]
    );
}


/**
 * Stats de uso de refacciones en una incidencia.
 */
function stats_refacciones_incidencia(int $incidencia_id): array {
    $r = db_one(
        "SELECT COUNT(*) AS lineas,
                COALESCE(SUM(cantidad), 0) AS unidades_total,
                COALESCE(SUM(costo_total), 0) AS costo_total
         FROM incidencia_refacciones
         WHERE incidencia_id = :iid",
        ['iid' => $incidencia_id]
    );
    return [
        'lineas' => (int) ($r['lineas'] ?? 0),
        'unidades_total' => (float) ($r['unidades_total'] ?? 0),
        'costo_total' => (float) ($r['costo_total'] ?? 0),
    ];
}


/**
 * Refacciones más usadas históricamente para un equipo.
 * Útil para sugerir refacciones al abrir una nueva incidencia sobre el equipo.
 */
function refacciones_frecuentes_equipo(int $equipo_id, int $limite = 5): array {
    return db_all(
        "SELECT r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria,
                COUNT(ir.id) AS veces_usada,
                SUM(ir.cantidad) AS cantidad_total
         FROM incidencia_refacciones ir
         INNER JOIN refacciones r ON ir.refaccion_id = r.id
         INNER JOIN incidencias i ON ir.incidencia_id = i.id
         WHERE i.equipo_id = :eid AND r.activo = 1
         GROUP BY r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria
         ORDER BY veces_usada DESC, cantidad_total DESC
         LIMIT $limite",
        ['eid' => $equipo_id]
    );
}
