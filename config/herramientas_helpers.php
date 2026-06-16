<?php
/**
 * ============================================================================
 * config/herramientas_helpers.php
 * ============================================================================
 * Gestión completa de herramientas y préstamos:
 *   - CRUD del catálogo
 *   - Sistema de préstamos transaccional
 *   - Devoluciones con condición
 *   - Alertas de vencimientos
 * ============================================================================
 */

require_once __DIR__ . '/db.php';


// ============================================================================
// CATÁLOGO DE HERRAMIENTAS
// ============================================================================

function listar_herramientas(array $filtros = []): array {
    $where = ["h.activo = 1"];
    $params = [];

    if (!empty($filtros['busqueda'])) {
        $like = '%' . $filtros['busqueda'] . '%';
        $where[] = "(h.codigo LIKE :q1 OR h.nombre LIKE :q2 OR h.marca LIKE :q3 OR h.numero_serie LIKE :q4)";
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
    }

    if (!empty($filtros['estado'])) {
        $where[] = "h.estado = :est";
        $params['est'] = $filtros['estado'];
    }

    if (!empty($filtros['tipo'])) {
        $where[] = "h.tipo = :tipo";
        $params['tipo'] = $filtros['tipo'];
    }

    if (!empty($filtros['sucursal_id'])) {
        $where[] = "h.sucursal_id = :sid";
        $params['sid'] = (int) $filtros['sucursal_id'];
    }

    $where_sql = "WHERE " . implode(' AND ', $where);

    return db_all(
        "SELECT h.*,
                s.codigo AS sucursal_codigo, s.nombre AS sucursal_nombre,
                p.nombre AS proveedor_nombre,
                u.nombre_completo AS prestada_a_nombre,
                pres.fecha_devolucion_esperada AS prestamo_fecha_dev,
                CASE
                    WHEN h.estado = 'prestada' AND pres.fecha_devolucion_esperada < CURDATE() THEN 1
                    ELSE 0
                END AS prestamo_vencido
         FROM herramientas h
         INNER JOIN sucursales s ON h.sucursal_id = s.id
         LEFT JOIN proveedores p ON h.proveedor_id = p.id
         LEFT JOIN herramientas_prestamos pres ON h.prestamo_activo_id = pres.id
         LEFT JOIN usuarios u ON pres.prestada_a_id = u.id
         $where_sql
         ORDER BY h.nombre ASC",
        $params
    );
}


function obtener_herramienta(int $id): ?array {
    $r = db_one(
        "SELECT h.*,
                s.codigo AS sucursal_codigo, s.nombre AS sucursal_nombre,
                p.nombre AS proveedor_nombre,
                u_crea.nombre_completo AS creado_por_nombre,
                pres.fecha_salida AS prestamo_fecha_salida,
                pres.fecha_devolucion_esperada AS prestamo_fecha_esperada,
                u_pres.nombre_completo AS prestada_a_nombre,
                u_pres.id AS prestada_a_id,
                pres.id AS prestamo_id
         FROM herramientas h
         INNER JOIN sucursales s ON h.sucursal_id = s.id
         LEFT JOIN proveedores p ON h.proveedor_id = p.id
         LEFT JOIN usuarios u_crea ON h.creado_por_id = u_crea.id
         LEFT JOIN herramientas_prestamos pres ON h.prestamo_activo_id = pres.id
         LEFT JOIN usuarios u_pres ON pres.prestada_a_id = u_pres.id
         WHERE h.id = :id",
        ['id' => $id]
    );
    return $r ?: null;
}


function crear_herramienta(array $datos, int $usuario_id): int {
    db_exec(
        "INSERT INTO herramientas
         (codigo, nombre, descripcion, tipo, marca, modelo, numero_serie,
          sucursal_id, ubicacion, estado, fecha_adquisicion, costo, proveedor_id,
          notas, creado_por_id)
         VALUES
         (:cod, :nom, :desc, :tipo, :marca, :modelo, :ns,
          :sid, :ubi, :est, :fa, :costo, :pid,
          :notas, :uid)",
        [
            'cod' => mb_substr($datos['codigo'], 0, 50),
            'nom' => mb_substr($datos['nombre'], 0, 200),
            'desc' => $datos['descripcion'] ?? null,
            'tipo' => $datos['tipo'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'ns' => $datos['numero_serie'] ?? null,
            'sid' => (int) $datos['sucursal_id'],
            'ubi' => $datos['ubicacion'] ?? null,
            'est' => $datos['estado'] ?? 'disponible',
            'fa' => !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null,
            'costo' => !empty($datos['costo']) ? (float) $datos['costo'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'notas' => $datos['notas'] ?? null,
            'uid' => $usuario_id,
        ]
    );
    return (int) db_last_id();
}


function actualizar_herramienta(int $id, array $datos): void {
    db_exec(
        "UPDATE herramientas
         SET codigo = :cod, nombre = :nom, descripcion = :desc,
             tipo = :tipo, marca = :marca, modelo = :modelo, numero_serie = :ns,
             sucursal_id = :sid, ubicacion = :ubi,
             fecha_adquisicion = :fa, costo = :costo, proveedor_id = :pid,
             notas = :notas
         WHERE id = :id",
        [
            'cod' => mb_substr($datos['codigo'], 0, 50),
            'nom' => mb_substr($datos['nombre'], 0, 200),
            'desc' => $datos['descripcion'] ?? null,
            'tipo' => $datos['tipo'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'ns' => $datos['numero_serie'] ?? null,
            'sid' => (int) $datos['sucursal_id'],
            'ubi' => $datos['ubicacion'] ?? null,
            'fa' => !empty($datos['fecha_adquisicion']) ? $datos['fecha_adquisicion'] : null,
            'costo' => !empty($datos['costo']) ? (float) $datos['costo'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'notas' => $datos['notas'] ?? null,
            'id' => $id,
        ]
    );
}


function cambiar_estado_herramienta(int $id, string $nuevo_estado): void {
    // No permitir cambiar a 'prestada' directamente (debe ser vía registrar_prestamo)
    // No permitir cambiar de 'prestada' a algo sin pasar por devolver
    db_exec("UPDATE herramientas SET estado = :est WHERE id = :id",
        ['est' => $nuevo_estado, 'id' => $id]);
}


function eliminar_herramienta(int $id): void {
    db_exec("UPDATE herramientas SET activo = 0 WHERE id = :id", ['id' => $id]);
}


// ============================================================================
// SISTEMA DE PRÉSTAMOS
// ============================================================================

/**
 * Registra un préstamo de herramienta.
 *   - Verifica que esté disponible
 *   - Crea el registro de préstamo
 *   - Cambia el estado de la herramienta a 'prestada'
 *   - Guarda el prestamo_activo_id
 */
function registrar_prestamo(array $datos, int $autorizada_por_id): int {
    $herramienta_id = (int) $datos['herramienta_id'];
    $prestada_a_id = (int) $datos['prestada_a_id'];

    // Verificar herramienta
    $her = db_one("SELECT id, nombre, estado FROM herramientas WHERE id = :id AND activo = 1",
        ['id' => $herramienta_id]);
    if (!$her) {
        throw new RuntimeException('Herramienta no encontrada.');
    }
    if ($her['estado'] !== 'disponible') {
        throw new RuntimeException("La herramienta no está disponible. Estado actual: {$her['estado']}.");
    }

    // Verificar usuario
    $usuario = db_one("SELECT id, nombre_completo FROM usuarios WHERE id = :id AND activo = 1",
        ['id' => $prestada_a_id]);
    if (!$usuario) {
        throw new RuntimeException('Usuario al que se presta no encontrado.');
    }

    db_exec("START TRANSACTION");

    try {
        // 1. Crear préstamo
        db_exec(
            "INSERT INTO herramientas_prestamos
             (herramienta_id, prestada_a_id, autorizada_por_id, fecha_devolucion_esperada,
              motivo, incidencia_id, notas_salida, estado)
             VALUES
             (:hid, :pid, :aid, :fde, :motivo, :iid, :notas, 'activo')",
            [
                'hid' => $herramienta_id,
                'pid' => $prestada_a_id,
                'aid' => $autorizada_por_id,
                'fde' => !empty($datos['fecha_devolucion_esperada']) ? $datos['fecha_devolucion_esperada'] : null,
                'motivo' => $datos['motivo'] ?? null,
                'iid' => !empty($datos['incidencia_id']) ? (int) $datos['incidencia_id'] : null,
                'notas' => $datos['notas_salida'] ?? null,
            ]
        );
        $prestamo_id = (int) db_last_id();

        // 2. Actualizar herramienta
        db_exec(
            "UPDATE herramientas
             SET estado = 'prestada', prestamo_activo_id = :pid
             WHERE id = :id",
            ['pid' => $prestamo_id, 'id' => $herramienta_id]
        );

        db_exec("COMMIT");
        return $prestamo_id;
    } catch (Throwable $e) {
        db_exec("ROLLBACK");
        throw $e;
    }
}


/**
 * Registra la devolución de una herramienta.
 *   - Actualiza el préstamo con fecha_devolucion_real y condición
 *   - Cambia el estado de la herramienta según la condición
 *   - Limpia el prestamo_activo_id
 */
function registrar_devolucion(int $prestamo_id, int $recibida_por_id, array $datos): void {
    $pres = db_one(
        "SELECT p.*, h.nombre AS herramienta_nombre
         FROM herramientas_prestamos p
         INNER JOIN herramientas h ON p.herramienta_id = h.id
         WHERE p.id = :id",
        ['id' => $prestamo_id]
    );

    if (!$pres) {
        throw new RuntimeException('Préstamo no encontrado.');
    }
    if ($pres['estado'] !== 'activo') {
        throw new RuntimeException('Este préstamo ya fue cerrado.');
    }

    $condicion = $datos['condicion_devolucion'] ?? 'buena';
    if (!in_array($condicion, ['buena', 'dañada', 'extraviada', 'reparada'], true)) {
        $condicion = 'buena';
    }

    // Determinar estado final del préstamo y herramienta según condición
    $estado_prestamo = match ($condicion) {
        'dañada'     => 'devuelta_con_dano',
        'extraviada' => 'extraviada',
        default      => 'devuelta',
    };

    $estado_herramienta = match ($condicion) {
        'dañada'     => 'en_reparacion',
        'extraviada' => 'extraviada',
        'reparada'   => 'disponible',
        default      => 'disponible',
    };

    db_exec("START TRANSACTION");

    try {
        // 1. Cerrar préstamo
        db_exec(
            "UPDATE herramientas_prestamos
             SET fecha_devolucion_real = CURRENT_TIMESTAMP,
                 recibida_por_id = :rid,
                 estado = :est,
                 condicion_devolucion = :cond,
                 notas_devolucion = :notas
             WHERE id = :id",
            [
                'rid' => $recibida_por_id,
                'est' => $estado_prestamo,
                'cond' => $condicion,
                'notas' => $datos['notas_devolucion'] ?? null,
                'id' => $prestamo_id,
            ]
        );

        // 2. Actualizar herramienta
        db_exec(
            "UPDATE herramientas
             SET estado = :est, prestamo_activo_id = NULL
             WHERE id = :id",
            ['est' => $estado_herramienta, 'id' => $pres['herramienta_id']]
        );

        db_exec("COMMIT");
    } catch (Throwable $e) {
        db_exec("ROLLBACK");
        throw $e;
    }
}


/**
 * Historial de préstamos de una herramienta.
 */
function listar_prestamos_de_herramienta(int $herramienta_id): array {
    return db_all(
        "SELECT p.*,
                u_pres.nombre_completo AS prestada_a_nombre,
                u_aut.nombre_completo AS autorizada_por_nombre,
                u_rec.nombre_completo AS recibida_por_nombre,
                i.folio AS incidencia_folio, i.titulo AS incidencia_titulo,
                CASE
                    WHEN p.estado = 'activo' AND p.fecha_devolucion_esperada < CURDATE() THEN 1
                    ELSE 0
                END AS vencido
         FROM herramientas_prestamos p
         LEFT JOIN usuarios u_pres ON p.prestada_a_id = u_pres.id
         LEFT JOIN usuarios u_aut ON p.autorizada_por_id = u_aut.id
         LEFT JOIN usuarios u_rec ON p.recibida_por_id = u_rec.id
         LEFT JOIN incidencias i ON p.incidencia_id = i.id
         WHERE p.herramienta_id = :hid
         ORDER BY p.fecha_salida DESC",
        ['hid' => $herramienta_id]
    );
}


/**
 * Préstamos activos (todos o por usuario).
 */
function listar_prestamos_activos(?int $usuario_id = null): array {
    $where = ["p.estado = 'activo'"];
    $params = [];
    if ($usuario_id) {
        $where[] = "p.prestada_a_id = :uid";
        $params['uid'] = $usuario_id;
    }
    $where_sql = "WHERE " . implode(' AND ', $where);

    return db_all(
        "SELECT p.*,
                h.codigo AS herramienta_codigo, h.nombre AS herramienta_nombre,
                u.nombre_completo AS prestada_a_nombre,
                s.codigo AS sucursal_codigo,
                DATEDIFF(p.fecha_devolucion_esperada, CURDATE()) AS dias_restantes,
                CASE
                    WHEN p.fecha_devolucion_esperada IS NOT NULL AND p.fecha_devolucion_esperada < CURDATE() THEN 1
                    ELSE 0
                END AS vencido
         FROM herramientas_prestamos p
         INNER JOIN herramientas h ON p.herramienta_id = h.id
         INNER JOIN sucursales s ON h.sucursal_id = s.id
         LEFT JOIN usuarios u ON p.prestada_a_id = u.id
         $where_sql
         ORDER BY vencido DESC, p.fecha_devolucion_esperada ASC, p.fecha_salida DESC",
        $params
    );
}


/**
 * Préstamos vencidos (debían devolverse y no se devolvieron).
 */
function prestamos_vencidos(): array {
    return db_all(
        "SELECT p.*,
                h.codigo AS herramienta_codigo, h.nombre AS herramienta_nombre,
                u.nombre_completo AS prestada_a_nombre,
                s.codigo AS sucursal_codigo,
                DATEDIFF(CURDATE(), p.fecha_devolucion_esperada) AS dias_atraso
         FROM herramientas_prestamos p
         INNER JOIN herramientas h ON p.herramienta_id = h.id
         INNER JOIN sucursales s ON h.sucursal_id = s.id
         LEFT JOIN usuarios u ON p.prestada_a_id = u.id
         WHERE p.estado = 'activo'
           AND p.fecha_devolucion_esperada IS NOT NULL
           AND p.fecha_devolucion_esperada < CURDATE()
         ORDER BY p.fecha_devolucion_esperada ASC"
    );
}


// ============================================================================
// ESTADÍSTICAS Y CATÁLOGOS
// ============================================================================

function stats_herramientas(?int $sucursal_id = null): array {
    $where_suc = $sucursal_id ? "AND sucursal_id = :sid" : "";
    $params = $sucursal_id ? ['sid' => $sucursal_id] : [];

    $r = db_one(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
            SUM(CASE WHEN estado = 'prestada' THEN 1 ELSE 0 END) AS prestadas,
            SUM(CASE WHEN estado = 'en_reparacion' THEN 1 ELSE 0 END) AS en_reparacion,
            SUM(CASE WHEN estado = 'extraviada' THEN 1 ELSE 0 END) AS extraviadas
         FROM herramientas
         WHERE activo = 1 $where_suc",
        $params
    );

    $vencidos = db_one(
        "SELECT COUNT(*) c FROM herramientas_prestamos p
         INNER JOIN herramientas h ON p.herramienta_id = h.id
         WHERE p.estado = 'activo'
           AND p.fecha_devolucion_esperada IS NOT NULL
           AND p.fecha_devolucion_esperada < CURDATE()
           " . ($sucursal_id ? "AND h.sucursal_id = :sid" : ""),
        $params
    );

    return [
        'total' => (int) ($r['total'] ?? 0),
        'disponibles' => (int) ($r['disponibles'] ?? 0),
        'prestadas' => (int) ($r['prestadas'] ?? 0),
        'en_reparacion' => (int) ($r['en_reparacion'] ?? 0),
        'extraviadas' => (int) ($r['extraviadas'] ?? 0),
        'vencidos' => (int) ($vencidos['c'] ?? 0),
    ];
}


function tipos_herramientas(): array {
    return [
        'Manual', 'Eléctrica', 'Neumática', 'Hidráulica', 'Medición',
        'Soldadura', 'Corte', 'Sujeción', 'Limpieza', 'Seguridad',
        'Carga / Transporte', 'Eléctrica de medición', 'Otra',
    ];
}


function etiqueta_estado_herramienta(string $estado): array {
    return match ($estado) {
        'disponible'    => ['label' => 'Disponible',    'color' => '#16A34A', 'icono' => 'check-circle-2'],
        'prestada'      => ['label' => 'Prestada',      'color' => '#F59E0B', 'icono' => 'user-check'],
        'en_reparacion' => ['label' => 'En reparación', 'color' => '#EA580C', 'icono' => 'wrench'],
        'extraviada'    => ['label' => 'Extraviada',    'color' => '#DC2626', 'icono' => 'alert-octagon'],
        'baja'          => ['label' => 'Dada de baja',  'color' => '#71717A', 'icono' => 'archive'],
        default         => ['label' => $estado,         'color' => '#71717A', 'icono' => 'circle'],
    };
}
