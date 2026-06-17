<?php
/**
 * ============================================================================
 * config/flotilla_helpers.php - Helpers del módulo Flotilla Vehicular
 * ============================================================================
 */

// ----------------------------------------------------------------------------
// SEGURIDAD POR SUCURSAL
// ----------------------------------------------------------------------------

/**
 * Retorna el sucursal_id al que el usuario está restringido, o null si puede ver todo.
 */
function flotilla_sucursal_forzada(): ?int {
    if (tiene_permiso('ver_todas_sucursales')) return null;
    $u = usuario_actual();
    $sid = (int) ($u['sucursal_id'] ?? 0);
    return $sid > 0 ? $sid : null;
}

/**
 * Verifica si el usuario puede ver un vehículo cargado.
 * Redirige con flash si no tiene acceso.
 */
function flotilla_puede_ver_vehiculo(array $vehiculo): bool {
    $forzada = flotilla_sucursal_forzada();
    if ($forzada === null) return true;
    return (int) $vehiculo['sucursal_id'] === $forzada;
}

// ----------------------------------------------------------------------------
// VEHÍCULOS
// ----------------------------------------------------------------------------

/**
 * Obtiene un vehículo completo con joins a tipo, sucursal y conductor.
 */
function flotilla_vehiculo(int $id): ?array {
    return db_one(
        "SELECT v.*,
                t.nombre  tipo_nombre,
                s.nombre  sucursal_nombre,
                s.codigo  sucursal_codigo,
                c.nombre_completo conductor_nombre,
                c.telefono        conductor_telefono
         FROM flotilla_vehiculos v
         INNER JOIN flotilla_tipos_vehiculo t ON v.tipo_id = t.id
         LEFT  JOIN sucursales             s ON v.sucursal_id = s.id
         LEFT  JOIN flotilla_conductores   c ON v.conductor_asignado_id = c.id
         WHERE v.id = :id",
        ['id' => $id]
    );
}

/**
 * Lista vehículos con filtros opcionales.
 */
function flotilla_listar_vehiculos(array $filtros = []): array {
    $where  = ['1=1'];
    $params = [];

    if (!empty($filtros['sucursal_id'])) {
        $where[]              = 'v.sucursal_id = :sid';
        $params['sid']        = $filtros['sucursal_id'];
    }
    if (!empty($filtros['estado'])) {
        $where[]              = 'v.estado = :estado';
        $params['estado']     = $filtros['estado'];
    }
    if (!empty($filtros['tipo_id'])) {
        $where[]              = 'v.tipo_id = :tipo_id';
        $params['tipo_id']    = $filtros['tipo_id'];
    }
    if (!empty($filtros['q'])) {
        $where[]              = '(v.placas LIKE :q OR v.alias LIKE :q OR v.marca LIKE :q OR v.modelo LIKE :q)';
        $params['q']          = '%' . $filtros['q'] . '%';
    }
    if (isset($filtros['activo'])) {
        $where[]              = 'v.activo = :activo';
        $params['activo']     = (int) $filtros['activo'];
    } else {
        $where[] = 'v.activo = 1';
    }

    $sql_where = implode(' AND ', $where);

    return db_all(
        "SELECT v.*,
                t.nombre  tipo_nombre,
                s.nombre  sucursal_nombre,
                s.codigo  sucursal_codigo,
                c.nombre_completo conductor_nombre
         FROM flotilla_vehiculos v
         INNER JOIN flotilla_tipos_vehiculo t ON v.tipo_id = t.id
         LEFT  JOIN sucursales             s ON v.sucursal_id = s.id
         LEFT  JOIN flotilla_conductores   c ON v.conductor_asignado_id = c.id
         WHERE $sql_where
         ORDER BY v.estado ASC, v.alias ASC, v.placas ASC",
        $params
    );
}

/**
 * Stats del dashboard de flotilla.
 */
function flotilla_stats(?int $sucursal_id = null): array {
    $where  = $sucursal_id ? 'AND v.sucursal_id = :sid' : '';
    $params = $sucursal_id ? ['sid' => $sucursal_id] : [];

    $row = db_one(
        "SELECT
            COUNT(*)                                              AS total,
            SUM(v.estado = 'activo')                             AS activos,
            SUM(v.estado = 'taller')                             AS en_taller,
            SUM(v.estado = 'inactivo' OR v.estado = 'baja')     AS inactivos
         FROM flotilla_vehiculos v
         WHERE v.activo = 1 $where",
        $params
    );

    // Documentos por vencer en los próximos 30 días
    $docs_alerta = db_one(
        "SELECT COUNT(*) c
         FROM flotilla_documentos d
         INNER JOIN flotilla_vehiculos v ON d.vehiculo_id = v.id
         WHERE v.activo = 1
           AND d.estado IN ('vigente','por_vencer')
           AND d.fecha_vence IS NOT NULL
           AND d.fecha_vence <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
           " . ($sucursal_id ? "AND v.sucursal_id = :sid" : ""),
        $params
    );

    // Multas pendientes
    $multas_pendientes = db_one(
        "SELECT COUNT(*) c
         FROM flotilla_multas m
         INNER JOIN flotilla_vehiculos v ON m.vehiculo_id = v.id
         WHERE v.activo = 1 AND m.estado IN('pendiente','impugnada')
           " . ($sucursal_id ? "AND v.sucursal_id = :sid" : ""),
        $params
    );

    // Siniestros en proceso
    $siniestros_activos = db_one(
        "SELECT COUNT(*) c
         FROM flotilla_siniestros s
         INNER JOIN flotilla_vehiculos v ON s.vehiculo_id = v.id
         WHERE v.activo = 1 AND s.estado IN ('reportado','en_proceso')
           " . ($sucursal_id ? "AND v.sucursal_id = :sid" : ""),
        $params
    );

    return [
        'total'              => (int) ($row['total']     ?? 0),
        'activos'            => (int) ($row['activos']   ?? 0),
        'en_taller'          => (int) ($row['en_taller'] ?? 0),
        'inactivos'          => (int) ($row['inactivos'] ?? 0),
        'docs_alerta'        => (int) ($docs_alerta['c']        ?? 0),
        'multas_pendientes'  => (int) ($multas_pendientes['c']  ?? 0),
        'siniestros_activos' => (int) ($siniestros_activos['c'] ?? 0),
    ];
}

// ----------------------------------------------------------------------------
// DOCUMENTOS
// ----------------------------------------------------------------------------

/**
 * Documentos de un vehículo con estado calculado.
 */
function flotilla_documentos_vehiculo(int $vehiculo_id): array {
    return db_all(
        "SELECT d.*, t.nombre tipo_nombre, t.dias_alerta
         FROM flotilla_documentos d
         INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
         WHERE d.vehiculo_id = :vid
         ORDER BY d.fecha_vence ASC",
        ['vid' => $vehiculo_id]
    );
}

/**
 * Todos los documentos próximos a vencer o ya vencidos (para alertas globales).
 */
function flotilla_alertas_documentos(int $dias = 45): array {
    return db_all(
        "SELECT d.*, t.nombre tipo_nombre, t.dias_alerta,
                v.placas, v.alias, v.marca, v.modelo,
                DATEDIFF(d.fecha_vence, CURDATE()) dias_restantes
         FROM flotilla_documentos d
         INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
         INNER JOIN flotilla_vehiculos       v ON d.vehiculo_id = v.id
         WHERE v.activo = 1
           AND d.estado IN ('vigente','por_vencer','vencido')
           AND d.fecha_vence IS NOT NULL
           AND d.fecha_vence <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
         ORDER BY d.fecha_vence ASC",
        ['dias' => $dias]
    );
}

/**
 * Actualiza el estado de todos los documentos según fecha actual (ejecutar en cron o al cargar).
 */
function flotilla_actualizar_estado_documentos(): void {
    // Vencidos
    db_exec(
        "UPDATE flotilla_documentos d
         INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
         SET d.estado = 'vencido'
         WHERE d.fecha_vence < CURDATE()
           AND d.estado NOT IN ('cancelado','vencido')"
    );
    // Por vencer (dentro del umbral de días_alerta del tipo)
    db_exec(
        "UPDATE flotilla_documentos d
         INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
         SET d.estado = 'por_vencer'
         WHERE d.fecha_vence >= CURDATE()
           AND DATEDIFF(d.fecha_vence, CURDATE()) <= t.dias_alerta
           AND d.estado = 'vigente'"
    );
}

// ----------------------------------------------------------------------------
// COMBUSTIBLE
// ----------------------------------------------------------------------------

/**
 * Últimas N cargas de combustible de un vehículo.
 */
function flotilla_combustible_vehiculo(int $vehiculo_id, int $limit = 20): array {
    return db_all(
        "SELECT f.*, c.nombre_completo conductor_nombre
         FROM flotilla_combustible f
         LEFT JOIN flotilla_conductores c ON f.conductor_id = c.id
         WHERE f.vehiculo_id = :vid
         ORDER BY f.fecha DESC
         LIMIT $limit",
        ['vid' => $vehiculo_id]
    );
}

/**
 * Rendimiento promedio de un vehículo (últimas N cargas con tanque lleno).
 */
function flotilla_rendimiento_promedio(int $vehiculo_id, int $cargas = 5): ?float {
    $row = db_one(
        "SELECT AVG(rendimiento_kml) avg_rend
         FROM (
             SELECT rendimiento_kml
             FROM flotilla_combustible
             WHERE vehiculo_id = :vid
               AND es_tanque_lleno = 1
               AND rendimiento_kml IS NOT NULL
               AND rendimiento_kml > 0
             ORDER BY fecha DESC
             LIMIT $cargas
         ) t",
        ['vid' => $vehiculo_id]
    );
    return $row && $row['avg_rend'] !== null ? round((float)$row['avg_rend'], 2) : null;
}

// ----------------------------------------------------------------------------
// MANTENIMIENTO PREVENTIVO
// ----------------------------------------------------------------------------

/**
 * Próximos mantenimientos pendientes de un vehículo (basado en km/fecha actual).
 */
function flotilla_mantenimientos_pendientes(int $vehiculo_id): array {
    $vehiculo = db_one("SELECT km_actual FROM flotilla_vehiculos WHERE id = :id", ['id' => $vehiculo_id]);
    if (!$vehiculo) return [];

    $km_actual = (int) $vehiculo['km_actual'];

    return db_all(
        "SELECT p.*,
                h.fecha          ult_fecha,
                h.km_odometro    ult_km,
                h.proximo_km,
                h.proxima_fecha,
                -- Días restantes para la próxima fecha
                DATEDIFF(h.proxima_fecha, CURDATE()) dias_restantes,
                -- Km restantes
                (h.proximo_km - :km_actual) km_restantes
         FROM flotilla_mant_programas p
         LEFT JOIN (
             SELECT programa_id,
                    MAX(fecha) fecha,
                    km_odometro,
                    proximo_km,
                    proxima_fecha
             FROM flotilla_mant_historial
             WHERE vehiculo_id = :vid
             GROUP BY programa_id
         ) h ON h.programa_id = p.id
         WHERE p.activo = 1
           AND (p.aplica_tipo_vehiculo_id IS NULL
                OR p.aplica_tipo_vehiculo_id = (
                    SELECT tipo_id FROM flotilla_vehiculos WHERE id = :vid2
                ))
         ORDER BY
             CASE
                 WHEN h.proxima_fecha IS NOT NULL AND h.proxima_fecha <= CURDATE() THEN 0
                 WHEN h.proximo_km   IS NOT NULL AND h.proximo_km <= :km_actual2   THEN 0
                 ELSE 1
             END ASC,
             h.proxima_fecha ASC",
        [
            'vid'        => $vehiculo_id,
            'vid2'       => $vehiculo_id,
            'km_actual'  => $km_actual,
            'km_actual2' => $km_actual,
        ]
    );
}

// ----------------------------------------------------------------------------
// GASTOS
// ----------------------------------------------------------------------------

/**
 * Resumen de gastos de un vehículo agrupado por categoría en un período.
 */
function flotilla_gastos_resumen(int $vehiculo_id, ?string $desde = null, ?string $hasta = null): array {
    $params = ['vid' => $vehiculo_id];
    $where  = '';
    if ($desde) { $where .= ' AND g.fecha >= :desde'; $params['desde'] = $desde; }
    if ($hasta) { $where .= ' AND g.fecha <= :hasta'; $params['hasta'] = $hasta; }

    return db_all(
        "SELECT c.nombre categoria, c.color,
                SUM(g.monto) total,
                COUNT(*)     registros
         FROM flotilla_gastos g
         INNER JOIN flotilla_categorias_gasto c ON g.categoria_id = c.id
         WHERE g.vehiculo_id = :vid $where
         GROUP BY c.id
         ORDER BY total DESC",
        $params
    );
}

/**
 * Gasto total de un vehículo en un período.
 */
function flotilla_gasto_total(int $vehiculo_id, ?string $desde = null, ?string $hasta = null): float {
    $params = ['vid' => $vehiculo_id];
    $where  = '';
    if ($desde) { $where .= ' AND fecha >= :desde'; $params['desde'] = $desde; }
    if ($hasta) { $where .= ' AND fecha <= :hasta'; $params['hasta'] = $hasta; }

    $row = db_one(
        "SELECT COALESCE(SUM(monto), 0) total
         FROM flotilla_gastos
         WHERE vehiculo_id = :vid $where",
        $params
    );
    return (float) ($row['total'] ?? 0);
}

// ----------------------------------------------------------------------------
// HELPERS DE FORMATO
// ----------------------------------------------------------------------------

/**
 * Badge de color para el estado del vehículo.
 */
function flotilla_badge_estado(string $estado): string {
    $cfg = match($estado) {
        'activo'   => ['bg-emerald-100', 'text-emerald-800', 'Activo'],
        'taller'   => ['bg-amber-100',   'text-amber-800',   'En taller'],
        'inactivo' => ['bg-zinc-100',    'text-zinc-600',    'Inactivo'],
        'baja'     => ['bg-red-100',     'text-red-800',     'Baja'],
        default    => ['bg-zinc-100',    'text-zinc-600',    ucfirst($estado)],
    };
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {$cfg[0]} {$cfg[1]}\">{$cfg[2]}</span>";
}

/**
 * Badge para el estado de un documento.
 */
function flotilla_badge_doc(string $estado, ?int $dias = null): string {
    if ($estado === 'vencido') {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Vencido</span>';
    }
    if ($estado === 'por_vencer') {
        $txt = $dias !== null ? "Vence en {$dias}d" : 'Por vencer';
        return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800\">{$txt}</span>";
    }
    if ($estado === 'vigente') {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Vigente</span>';
    }
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600">Cancelado</span>';
}

/**
 * Icono Lucide para tipo de combustible.
 */
function flotilla_icono_combustible(string $tipo): string {
    return match($tipo) {
        'electrico' => 'zap',
        'hibrido'   => 'leaf',
        'gas'       => 'flame',
        default     => 'fuel',
    };
}
