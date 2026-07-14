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
function flotilla_combustible_vehiculo(int $vehiculo_id, int $limit = 20, ?string $desde = null, ?string $hasta = null): array {
    $where  = ['f.vehiculo_id = :vid'];
    $params = ['vid' => $vehiculo_id];
    if ($desde) { $where[] = 'DATE(f.fecha) >= :desde'; $params['desde'] = $desde; }
    if ($hasta) { $where[] = 'DATE(f.fecha) <= :hasta'; $params['hasta'] = $hasta; }
    $sql_where = implode(' AND ', $where);
    // Con filtro de fechas se muestran todas las del rango; sin filtro, las últimas $limit.
    $sql_limit = ($desde || $hasta) ? '' : 'LIMIT ' . (int) $limit;
    return db_all(
        "SELECT f.*, c.nombre_completo conductor_nombre
         FROM flotilla_combustible f
         LEFT JOIN flotilla_conductores c ON f.conductor_id = c.id
         WHERE $sql_where
         ORDER BY f.fecha DESC
         $sql_limit",
        $params
    );
}

/**
 * Actualiza el kilometraje (odómetro) de un vehículo.
 * Si el km nuevo es menor al actual, solo se permite cuando $es_admin && $forzar.
 * Devuelve ['ok'=>bool, 'error'=>?string, 'km_actual'=>int].
 */
/* ===========================================================================
 * Configuración global (clave/valor) — tabla `configuracion`
 * ======================================================================== */
function config_get(string $clave, $defecto = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            if (db_one("SHOW TABLES LIKE 'configuracion'")) {
                foreach (db_all("SELECT clave, valor FROM configuracion") as $r) {
                    $cache[$r['clave']] = $r['valor'];
                }
            }
        } catch (Throwable $e) {}
    }
    return array_key_exists($clave, $cache) ? $cache[$clave] : $defecto;
}
function config_set(string $clave, string $valor): void {
    if (!db_one("SHOW TABLES LIKE 'configuracion'")) return;
    db_exec("INSERT INTO configuracion (clave, valor) VALUES (:c, :v)
             ON DUPLICATE KEY UPDATE valor = :v2", ['c' => $clave, 'v' => $valor, 'v2' => $valor]);
}

/* ===========================================================================
 * Odómetro: historial de lecturas, antigüedad y umbral
 * ======================================================================== */
function flotilla_odometro_umbral(): int {
    return max(1, (int) config_get('odometro_umbral_dias', 30));
}

/** Fecha de la última lectura (historial manual o última carga de combustible con km). */
function flotilla_odometro_ultima_fecha(int $vid): ?string {
    $fechas = [];
    try {
        if (db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) {
            $r = db_one("SELECT MAX(leido_en) f FROM flotilla_odometro_historial WHERE vehiculo_id = :v", ['v' => $vid]);
            if (!empty($r['f'])) $fechas[] = $r['f'];
        }
    } catch (Throwable $e) {}
    try {
        $r = db_one("SELECT MAX(fecha) f FROM flotilla_combustible WHERE vehiculo_id = :v AND km_odometro > 0", ['v' => $vid]);
        if (!empty($r['f'])) $fechas[] = $r['f'];
    } catch (Throwable $e) {}
    return $fechas ? max($fechas) : null;
}

/** Días desde la última lectura del odómetro (null si nunca se ha registrado). */
function flotilla_odometro_dias(int $vid): ?int {
    $f = flotilla_odometro_ultima_fecha($vid);
    return $f ? (int) floor((time() - strtotime($f)) / 86400) : null;
}

/** Inserta una lectura en el historial (si la tabla existe). */
function flotilla_odometro_registrar(int $vid, int $km, string $origen, ?int $km_anterior, ?int $usuario_id): void {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) return;
        db_exec("INSERT INTO flotilla_odometro_historial (vehiculo_id, km, km_anterior, origen, usuario_id)
                 VALUES (:v, :km, :ka, :o, :u)",
                ['v' => $vid, 'km' => $km, 'ka' => $km_anterior, 'o' => $origen, 'u' => $usuario_id]);
    } catch (Throwable $e) {}
}

/**
 * Resincroniza el odómetro de un vehículo a partir de sus cargas de combustible.
 * Reconstruye las lecturas de historial con origen 'combustible' (una por carga)
 * y recalcula km_actual solo con fuentes MANUALES (nunca GPS). Idempotente:
 * se puede llamar tras crear, editar o eliminar una carga.
 */
function flotilla_combustible_resync_odometro(int $vid): void {
    if ($vid <= 0) return;
    try {
        $tiene_hist = (bool) db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'");
        if ($tiene_hist) {
            // Las lecturas 'combustible' son un espejo de las cargas: se reconstruyen.
            db_exec("DELETE FROM flotilla_odometro_historial WHERE vehiculo_id = :v AND origen = 'combustible'", ['v' => $vid]);
            db_exec(
                "INSERT INTO flotilla_odometro_historial (vehiculo_id, km, km_anterior, origen, usuario_id, leido_en)
                 SELECT vehiculo_id, km_odometro, NULL, 'combustible', creado_por, fecha
                   FROM flotilla_combustible
                  WHERE vehiculo_id = :v AND km_odometro > 0",
                ['v' => $vid]
            );
            // km_actual = máximo de lecturas manuales (excluye GPS), sin bajar del inicial.
            db_exec(
                "UPDATE flotilla_vehiculos v
                    SET v.km_actual = GREATEST(
                        v.km_inicial,
                        COALESCE((SELECT MAX(h.km) FROM flotilla_odometro_historial h
                                   WHERE h.vehiculo_id = v.id AND h.origen <> 'gps'), 0)
                    )
                  WHERE v.id = :v",
                ['v' => $vid]
            );
        } else {
            // Sin tabla de historial: km_actual desde las cargas de combustible.
            db_exec(
                "UPDATE flotilla_vehiculos v
                    SET v.km_actual = GREATEST(
                        v.km_inicial,
                        COALESCE((SELECT MAX(c.km_odometro) FROM flotilla_combustible c WHERE c.vehiculo_id = v.id), 0)
                    )
                  WHERE v.id = :v",
                ['v' => $vid]
            );
        }
    } catch (Throwable $e) {}
}

/**
 * Km recorridos de un vehículo en un rango de fechas, COMPROBADOS por las cargas
 * de combustible (capturas manuales / Xiga). Nunca usa GPS. Es MAX(km) - MIN(km)
 * de las cargas del rango; requiere al menos 2 cargas con km para dar un dato.
 * Devuelve ['km' => int, 'cargas' => int].
 */
function flotilla_km_periodo_cargas(int $vid, ?string $desde = null, ?string $hasta = null): array {
    $w = ['vehiculo_id = :v', 'km_odometro > 0'];
    $p = ['v' => $vid];
    if ($desde) { $w[] = 'DATE(fecha) >= :d'; $p['d'] = $desde; }
    if ($hasta) { $w[] = 'DATE(fecha) <= :h'; $p['h'] = $hasta; }
    try {
        $r = db_one("SELECT MAX(km_odometro) mx, MIN(km_odometro) mn, COUNT(*) c
                     FROM flotilla_combustible WHERE " . implode(' AND ', $w), $p);
    } catch (Throwable $e) { $r = null; }
    $c  = (int) ($r['c'] ?? 0);
    $km = ($c >= 2) ? max(0, (int) $r['mx'] - (int) $r['mn']) : 0;
    return ['km' => $km, 'cargas' => $c];
}

/** Historial de lecturas del odómetro de un vehículo (más reciente primero). */
function flotilla_odometro_lista(int $vid, int $limite = 100): array {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) return [];
        $limite = max(1, $limite);
        return db_all(
            "SELECT h.*, u.nombre_completo AS usuario_nombre
             FROM flotilla_odometro_historial h
             LEFT JOIN usuarios u ON h.usuario_id = u.id
             WHERE h.vehiculo_id = :v
             ORDER BY h.leido_en DESC, h.id DESC
             LIMIT $limite",
            ['v' => $vid]
        );
    } catch (Throwable $e) {
        return [];
    }
}

/** Mensaje amigable tras actualizar el odómetro, con el recorrido desde la última lectura. */
function flotilla_odometro_mensaje(array $res, int $km_nuevo): string {
    $msg = 'Odómetro actualizado a ' . number_format($km_nuevo) . ' km.';
    if (($res['delta_km'] ?? null) !== null && $res['delta_km'] > 0 && ($res['delta_dias'] ?? null) !== null) {
        $d = (int) $res['delta_dias'];
        $tiempo = $d <= 0 ? 'el mismo día' : 'en ' . $d . ' día' . ($d == 1 ? '' : 's');
        $msg .= ' El vehículo recorrió ' . number_format((int) $res['delta_km']) . ' km ' . $tiempo . ' desde la última lectura.';
    }
    return $msg;
}

/**
 * Actualiza el kilometraje (odómetro) de un vehículo y lo registra en el historial.
 * Si el km nuevo es menor al actual, solo se permite cuando $es_admin && $forzar.
 * Devuelve ['ok', 'error', 'km_actual', 'delta_km', 'delta_dias', 'ultima_fecha'].
 */
function flotilla_actualizar_km(int $vehiculo_id, int $km_nuevo, bool $es_admin, bool $forzar = false): array {
    $veh = db_one("SELECT km_actual FROM flotilla_vehiculos WHERE id = :id", ['id' => $vehiculo_id]);
    if (!$veh) return ['ok' => false, 'error' => 'Vehículo no encontrado.', 'km_actual' => 0];
    $km_actual = (int) $veh['km_actual'];
    if ($km_nuevo < 0) {
        return ['ok' => false, 'error' => 'El kilometraje no puede ser negativo.', 'km_actual' => $km_actual];
    }
    if ($km_nuevo < $km_actual) {
        if (!$es_admin) {
            return ['ok' => false, 'error' => "El kilometraje no puede ser menor al actual ($km_actual km).", 'km_actual' => $km_actual];
        }
        if (!$forzar) {
            return ['ok' => false, 'error' => "El km es menor al actual ($km_actual km). Confirma para forzar el cambio.", 'km_actual' => $km_actual];
        }
    }
    $ultima_fecha = flotilla_odometro_ultima_fecha($vehiculo_id);
    $usuario = function_exists('usuario_actual') ? usuario_actual() : null;
    flotilla_odometro_registrar($vehiculo_id, $km_nuevo, 'manual', $km_actual, $usuario['id'] ?? null);
    db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id", ['km' => $km_nuevo, 'id' => $vehiculo_id]);
    return [
        'ok' => true, 'error' => null, 'km_actual' => $km_nuevo,
        'delta_km'   => $km_nuevo - $km_actual,
        'delta_dias' => $ultima_fecha ? (int) floor((time() - strtotime($ultima_fecha)) / 86400) : null,
        'ultima_fecha' => $ultima_fecha,
    ];
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

/**
 * Gasto de mantenimiento de flotilla agrupado por proveedor (texto).
 * Solo categorías de Mantenimiento / Refacciones; ignora combustible, multas, etc.
 * $suc_where: filtro extra ya armado, ej. " AND v.sucursal_id = 3" (usa alias v).
 * Devuelve: proveedor, total, registros, vehiculos.
 */
function flotilla_gasto_proveedores(?string $desde = null, ?string $hasta = null, string $suc_where = '', int $limite = 50): array {
    $params = [];
    $w = "g.proveedor IS NOT NULL AND TRIM(g.proveedor) <> '' "
       . "AND (cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%')";
    if ($desde) { $w .= ' AND g.fecha >= :desde'; $params['desde'] = $desde; }
    if ($hasta) { $w .= ' AND g.fecha <= :hasta'; $params['hasta'] = $hasta; }
    $limite = max(1, $limite);
    return db_all(
        "SELECT g.proveedor,
                COALESCE(SUM(g.monto), 0)       total,
                COUNT(*)                        registros,
                COUNT(DISTINCT g.vehiculo_id)   vehiculos
         FROM flotilla_gastos g
         INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
         INNER JOIN flotilla_vehiculos v          ON g.vehiculo_id  = v.id
         WHERE $w $suc_where
         GROUP BY g.proveedor
         ORDER BY total DESC
         LIMIT $limite",
        $params
    );
}

/**
 * Total gastado en mantenimiento/refacciones de flotilla en un período.
 * Para una nota indicativa rápida.
 */
function flotilla_mant_gasto_total(?string $desde = null, ?string $hasta = null, ?int $sucursal_id = null): float {
    $params = [];
    $w = "(cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%')";
    if ($desde) { $w .= ' AND g.fecha >= :desde'; $params['desde'] = $desde; }
    if ($hasta) { $w .= ' AND g.fecha <= :hasta'; $params['hasta'] = $hasta; }
    $suc = '';
    if ($sucursal_id) { $suc = ' AND v.sucursal_id = :sid'; $params['sid'] = $sucursal_id; }
    $row = db_one(
        "SELECT COALESCE(SUM(g.monto), 0) total
         FROM flotilla_gastos g
         INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
         INNER JOIN flotilla_vehiculos v          ON g.vehiculo_id  = v.id
         WHERE $w $suc",
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


/* ===========================================================================
 * Estaciones de combustible y recibos
 * ======================================================================== */

/** Estaciones activas del catálogo (vacío si la tabla no existe aún). */
function flotilla_estaciones_activas(): array {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_estaciones'")) return [];
        return db_all("SELECT id, nombre, direccion FROM flotilla_estaciones WHERE activo = 1 ORDER BY nombre");
    } catch (Throwable $e) {
        return [];
    }
}

/** Valida que el archivo subido sea imagen o PDF (por contenido, no por nombre). */
function flotilla_recibo_valido(string $tmp, string $name): bool {
    $info = @getimagesize($tmp);
    if ($info !== false && !empty($info['mime'])
        && in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        return true;
    }
    $fh = @fopen($tmp, 'rb');
    $head = $fh ? (string) fread($fh, 4) : '';
    if ($fh) fclose($fh);
    return substr($head, 0, 4) === '%PDF';
}

/**
 * Guarda el recibo/factura de una carga. Devuelve ['ruta'=>?string, 'error'=>?string].
 * Si no se subió archivo, ['ruta'=>null,'error'=>null] (no es error).
 */
function flotilla_guardar_recibo(array $file): array {
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return ['ruta' => null, 'error' => null];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ruta' => null, 'error' => 'No se pudo subir el recibo.'];
    }
    if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
        return ['ruta' => null, 'error' => 'El recibo excede el tamaño máximo (10 MB).'];
    }
    if (!flotilla_recibo_valido($file['tmp_name'], $file['name'])) {
        return ['ruta' => null, 'error' => 'El recibo debe ser una imagen (JPG, PNG, WEBP, GIF) o PDF.'];
    }
    $dir_base   = __DIR__ . '/../assets/uploads';
    $subcarpeta = date('Y/m');
    $dir_final  = "$dir_base/$subcarpeta";
    if (!is_dir($dir_final)) @mkdir($dir_final, 0755, true);
    $ext    = preg_replace('/[^a-z0-9]/i', '', strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)));
    $nombre = 'recibo_' . bin2hex(random_bytes(12)) . ($ext ? ".$ext" : '');
    if (!move_uploaded_file($file['tmp_name'], "$dir_final/$nombre")) {
        return ['ruta' => null, 'error' => 'No se pudo guardar el recibo en el servidor.'];
    }
    return ['ruta' => "uploads/$subcarpeta/$nombre", 'error' => null];
}


/* ===========================================================================
 * Mantenimiento: flujo abierto/cerrado, vehículo en taller, gasto
 * ======================================================================== */

/** Pone (true) o quita (false) al vehículo de "En taller", sin tocar inactivo/baja. */
function flotilla_vehiculo_taller(int $vid, bool $en_taller): void {
    if ($en_taller) {
        db_exec("UPDATE flotilla_vehiculos SET estado='taller' WHERE id=:id AND estado='activo'", ['id'=>$vid]);
    } else {
        db_exec("UPDATE flotilla_vehiculos SET estado='activo' WHERE id=:id AND estado='taller'", ['id'=>$vid]);
    }
}

/** Proveedores activos (para autocompletado / datalist). */
function flotilla_proveedores_lista(): array {
    try {
        return db_all("SELECT nombre FROM proveedores WHERE activo = 1 ORDER BY nombre");
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Crea o actualiza el gasto ligado a un mantenimiento (categoría "Mantenimiento").
 * Usa flotilla_mant_historial.gasto_id para no duplicar. Devuelve el gasto_id.
 */
function flotilla_mant_gasto_sync(int $mant_id, int $vehiculo_id, ?float $costo, ?string $proveedor,
                                  string $concepto, string $fecha, ?string $factura, ?int $km, ?int $usuario_id): ?int {
    $tiene_gasto_id = (bool) db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'gasto_id'");
    $gasto_id = null;
    if ($tiene_gasto_id) {
        $r = db_one("SELECT gasto_id FROM flotilla_mant_historial WHERE id=:id", ['id'=>$mant_id]);
        $gasto_id = $r['gasto_id'] ?? null;
    }
    if (!$costo || $costo <= 0) {
        // Si quitaron el costo y había un gasto ligado, se elimina.
        if ($gasto_id) {
            db_exec("DELETE FROM flotilla_gastos WHERE id = :id", ['id' => $gasto_id]);
            if ($tiene_gasto_id) db_exec("UPDATE flotilla_mant_historial SET gasto_id = NULL WHERE id = :id", ['id' => $mant_id]);
        }
        return null;
    }
    $cat = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre = 'Mantenimiento' LIMIT 1");
    if (!$cat) return $gasto_id;

    if ($gasto_id) {
        db_exec("UPDATE flotilla_gastos SET fecha=:f, concepto=:c, monto=:m, proveedor=:p, numero_factura=:fac, km_odometro=:km WHERE id=:id",
            ['f'=>$fecha, 'c'=>$concepto, 'm'=>$costo, 'p'=>$proveedor, 'fac'=>$factura, 'km'=>$km, 'id'=>$gasto_id]);
    } else {
        db_exec("INSERT INTO flotilla_gastos (vehiculo_id, categoria_id, fecha, concepto, monto, proveedor, numero_factura, km_odometro, creado_por)
                 VALUES (:v,:cat,:f,:c,:m,:p,:fac,:km,:cp)",
            ['v'=>$vehiculo_id, 'cat'=>$cat['id'], 'f'=>$fecha, 'c'=>$concepto, 'm'=>$costo, 'p'=>$proveedor, 'fac'=>$factura, 'km'=>$km, 'cp'=>$usuario_id]);
        $gasto_id = (int) db_last_id();
        if ($tiene_gasto_id) {
            db_exec("UPDATE flotilla_mant_historial SET gasto_id=:g WHERE id=:id", ['g'=>$gasto_id, 'id'=>$mant_id]);
        }
    }
    return $gasto_id;
}

/** Mantenimientos abiertos (sin fecha_fin) con días en taller. Vacío si aún no existe la columna. */
function flotilla_mant_abiertos(?int $vehiculo_id = null): array {
    try {
        if (!db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'fecha_fin'")) return [];
        $where = 'h.fecha_fin IS NULL';
        $params = [];
        if ($vehiculo_id) { $where .= ' AND h.vehiculo_id = :v'; $params['v'] = $vehiculo_id; }
        return db_all(
            "SELECT h.*, v.alias, v.placas, v.marca, v.modelo,
                    GREATEST(0, DATEDIFF(CURDATE(), h.fecha)) AS dias_taller
             FROM flotilla_mant_historial h
             INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
             WHERE $where
             ORDER BY h.fecha ASC",
            $params
        );
    } catch (Throwable $e) {
        return [];
    }
}


/* ===========================================================================
 * Fotos del vehículo (historial / evolución)
 * ======================================================================== */

/** Guarda una foto (solo imágenes). Devuelve ['ruta'=>?string, 'error'=>?string]. */
function flotilla_guardar_foto(array $file): array {
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE || empty($file['name'])) return ['ruta' => null, 'error' => null];
    if ($err !== UPLOAD_ERR_OK) return ['ruta' => null, 'error' => 'No se pudo subir la foto.'];
    if ((int) ($file['size'] ?? 0) > 15 * 1024 * 1024) return ['ruta' => null, 'error' => 'La foto excede el tamaño máximo (15 MB).'];
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || empty($info['mime'])
        || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        return ['ruta' => null, 'error' => 'El archivo debe ser una imagen (JPG, PNG, WEBP o GIF).'];
    }
    $sub = date('Y/m');
    $dir = __DIR__ . '/../assets/uploads/' . $sub;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ext = preg_replace('/[^a-z0-9]/i', '', strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))) ?: 'jpg';
    $nombre = 'foto_' . bin2hex(random_bytes(12)) . ".$ext";
    if (!move_uploaded_file($file['tmp_name'], "$dir/$nombre")) {
        return ['ruta' => null, 'error' => 'No se pudo guardar la foto en el servidor.'];
    }
    return ['ruta' => "uploads/$sub/$nombre", 'error' => null];
}

/**
 * Guarda las fotos "antes" y "después" de un mantenimiento (ambas opcionales).
 * Lee $_FILES['foto_antes'] y $_FILES['foto_despues'] y devuelve
 * ['antes'=>ruta|null, 'despues'=>ruta|null, 'error'=>msg|null].
 */
function flotilla_mant_guardar_fotos(): array {
    $out = ['antes' => null, 'despues' => null, 'error' => null];
    foreach (['antes', 'despues'] as $k) {
        $r = flotilla_guardar_foto($_FILES['foto_' . $k] ?? []);
        if ($r['error']) { $out['error'] = 'Foto ' . $k . ': ' . $r['error']; return $out; }
        $out[$k] = $r['ruta'];
    }
    return $out;
}

/** Historial de fotos de un vehículo (más reciente primero). */
function flotilla_vehiculo_fotos(int $vid): array {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_vehiculo_fotos'")) return [];
        return db_all(
            "SELECT f.*, u.nombre_completo AS usuario_nombre
             FROM flotilla_vehiculo_fotos f
             LEFT JOIN usuarios u ON f.usuario_id = u.id
             WHERE f.vehiculo_id = :v
             ORDER BY f.tomada_en DESC, f.id DESC",
            ['v' => $vid]
        );
    } catch (Throwable $e) {
        return [];
    }
}

/** Días desde la última foto (null si no hay). */
function flotilla_vehiculo_foto_dias(int $vid): ?int {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_vehiculo_fotos'")) return null;
        $r = db_one("SELECT MAX(tomada_en) f FROM flotilla_vehiculo_fotos WHERE vehiculo_id = :v", ['v' => $vid]);
        if (empty($r['f'])) return null;
        return (int) floor((time() - strtotime($r['f'])) / 86400);
    } catch (Throwable $e) {
        return null;
    }
}

/** Umbral (días) para considerar la foto desactualizada. Default 90 (trimestral). */
function flotilla_foto_umbral(): int {
    return max(1, (int) config_get('foto_umbral_dias', 90));
}


/**
 * Parsea un reporte "Información general" de Monsat/GPSWOX (HTML con extensión .xls).
 * Devuelve ['dispositivo'=>string|null, 'periodo'=>string|null,
 *           'filas'=>[ ['fecha'=>'Y-m-d','km'=>float,'litros'=>?float,'costo'=>?float], ... ]].
 */
function flotilla_monsat_parse(string $html): array {
    // Un archivo puede traer VARIOS dispositivos. Devuelve una lista de bloques:
    // [ ['dispositivo'=>str, 'periodo'=>str|null,
    //    'filas'=>[['fecha'=>'Y-m-d','km'=>float,'litros'=>?float,'costo'=>?float], ...]], ... ]
    $bloques = [];
    $idx = -1;
    $col = null;

    $dec = fn($c) => trim(html_entity_decode(strip_tags($c), ENT_QUOTES | ENT_HTML5));
    $num = static function ($txt): float {
        $t = preg_replace('/[^\d.]/', '', strip_tags((string) $txt));
        return $t === '' ? 0.0 : (float) $t;
    };

    foreach (preg_split('/<\/tr>/i', $html) as $r) {
        preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $r, $cm);
        if (empty($cm[1])) continue;
        $vals = array_map($dec, $cm[1]);

        // ¿Inicio de bloque de un dispositivo?
        $pd = array_search('Dispositivo:', $vals, true);
        if ($pd !== false && isset($vals[$pd + 1])) {
            $bloques[] = ['dispositivo' => $vals[$pd + 1], 'periodo' => null, 'filas' => []];
            $idx = count($bloques) - 1;
            $col = null;
            continue;
        }
        if ($idx < 0) continue;

        // Período del bloque.
        $pp = array_search('Período:', $vals, true);
        if ($pp === false) $pp = array_search('Periodo:', $vals, true);
        if ($pp !== false && isset($vals[$pp + 1])) { $bloques[$idx]['periodo'] = $vals[$pp + 1]; continue; }

        // Encabezado de columnas del bloque.
        if (in_array('Largo', $vals, true) && in_array('Tiempo', $vals, true)) {
            $col = [];
            foreach ($vals as $i => $name) $col[$name] = $i;
            continue;
        }

        // Fila de datos (primera celda = fecha). Excluye la fila de total.
        if ($col !== null && isset($vals[0]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vals[0])) {
            $iL = $col['Largo'] ?? null;
            if ($iL === null) continue;
            $iC  = $col['Combustible'] ?? null;
            $iCo = null;
            foreach ($col as $name => $i) {
                if (stripos($name, 'Costo de combustible') !== false) $iCo = $i;
            }
            $bloques[$idx]['filas'][] = [
                'fecha'  => $vals[0],
                'km'     => isset($vals[$iL]) ? $num($vals[$iL]) : 0.0,
                'litros' => ($iC  !== null && isset($vals[$iC]))  ? $num($vals[$iC])  : null,
                'costo'  => ($iCo !== null && isset($vals[$iCo])) ? $num($vals[$iCo]) : null,
            ];
        }
    }
    return $bloques;
}

/** Km recorridos según GPS (Monsat) en un período. */
function flotilla_km_gps_total(int $vid, ?string $desde = null, ?string $hasta = null): float {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_km_gps'")) return 0.0;
        $w = 'vehiculo_id = :v'; $params = ['v' => $vid];
        if ($desde) { $w .= ' AND fecha >= :d'; $params['d'] = $desde; }
        if ($hasta) { $w .= ' AND fecha <= :h'; $params['h'] = $hasta; }
        $r = db_one("SELECT COALESCE(SUM(km),0) km FROM flotilla_km_gps WHERE {$w}", $params);
        return (float) ($r['km'] ?? 0);
    } catch (Throwable $e) { return 0.0; }
}

/**
 * Avanza el odómetro (km_actual) de un vehículo con el km del GPS.
 * Ancla en la última lectura REAL (manual/papel) y le suma el km GPS posterior a esa fecha.
 * Nunca baja el km_actual. Mantiene una sola lectura 'gps' con el estimado vigente
 * (para que las alertas/antigüedad del odómetro y el mantenimiento por km funcionen).
 */
function flotilla_odometro_sync_gps(int $vid): void {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_km_gps'")) return;
        $ult = db_one("SELECT MAX(fecha) f FROM flotilla_km_gps WHERE vehiculo_id = :v", ['v' => $vid]);
        $ult_fecha = $ult['f'] ?? null;
        if (!$ult_fecha) return;

        $veh = db_one("SELECT km_actual, km_inicial FROM flotilla_vehiculos WHERE id = :id", ['id' => $vid]);
        if (!$veh) return;
        $km_actual = (float) $veh['km_actual'];

        // Ancla: última lectura real del odómetro (no GPS). Si no hay, km_inicial.
        $ancla = null;
        $tiene_odo = (bool) db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'");
        if ($tiene_odo) {
            $ancla = db_one(
                "SELECT km, leido_en FROM flotilla_odometro_historial
                 WHERE vehiculo_id = :v AND (origen IS NULL OR origen <> 'gps')
                 ORDER BY leido_en DESC, id DESC LIMIT 1",
                ['v' => $vid]
            );
        }
        if ($ancla) {
            $km_ancla    = (float) $ancla['km'];
            $fecha_ancla = substr((string) $ancla['leido_en'], 0, 10);
        } else {
            $km_ancla    = (float) ($veh['km_inicial'] ?? 0);
            $fecha_ancla = '2000-01-01';
        }

        $r = db_one(
            "SELECT COALESCE(SUM(km),0) km FROM flotilla_km_gps WHERE vehiculo_id = :v AND fecha > :d",
            ['v' => $vid, 'd' => $fecha_ancla]
        );
        $nuevo = (int) round($km_ancla + (float) ($r['km'] ?? 0));
        if ($nuevo <= $km_actual) return; // nunca baja

        db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id", ['km' => $nuevo, 'id' => $vid]);
        if ($tiene_odo) {
            db_exec("DELETE FROM flotilla_odometro_historial WHERE vehiculo_id = :v AND origen = 'gps'", ['v' => $vid]);
            db_exec(
                "INSERT INTO flotilla_odometro_historial (vehiculo_id, km, km_anterior, origen, notas, leido_en)
                 VALUES (:v, :km, :ka, 'gps', 'Estimado por GPS (Monsat)', :fecha)",
                ['v' => $vid, 'km' => $nuevo, 'ka' => (int) $km_actual, 'fecha' => $ult_fecha . ' 12:00:00']
            );
        }
    } catch (Throwable $e) { /* silencioso */ }
}

/**
 * Estima el km del ODÓMETRO de un vehículo a una fecha dada, usando el GPS.
 * Ancla en la lectura real más cercana (antes o después) y suma/resta el km del GPS.
 * Solo estima si el GPS cubre esa fecha (fecha >= primer dato GPS del vehículo).
 * Devuelve el km estimado o null si no hay datos suficientes.
 */
function flotilla_km_odometro_estimado(int $vid, string $fecha): ?int {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_km_gps'")) return null;
        $min = db_one("SELECT MIN(fecha) f FROM flotilla_km_gps WHERE vehiculo_id = :v", ['v' => $vid]);
        if (empty($min['f']) || $fecha < $min['f']) return null; // fuera de cobertura GPS

        $tiene_odo = (bool) db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'");

        // 1) Ancla ANTERIOR o igual: km_ancla + GPS(ancla, fecha].
        if ($tiene_odo) {
            $ant = db_one(
                "SELECT km, leido_en FROM flotilla_odometro_historial
                 WHERE vehiculo_id=:v AND (origen IS NULL OR origen<>'gps') AND DATE(leido_en) <= :f
                 ORDER BY leido_en DESC, id DESC LIMIT 1",
                ['v' => $vid, 'f' => $fecha]
            );
            if ($ant) {
                $r = db_one("SELECT COALESCE(SUM(km),0) km FROM flotilla_km_gps WHERE vehiculo_id=:v AND fecha > :fa AND fecha <= :f",
                    ['v' => $vid, 'fa' => substr((string) $ant['leido_en'], 0, 10), 'f' => $fecha]);
                return (int) round((float) $ant['km'] + (float) ($r['km'] ?? 0));
            }
            // 2) Ancla POSTERIOR: km_post - GPS(fecha, post].
            $post = db_one(
                "SELECT km, leido_en FROM flotilla_odometro_historial
                 WHERE vehiculo_id=:v AND (origen IS NULL OR origen<>'gps') AND DATE(leido_en) >= :f
                 ORDER BY leido_en ASC, id ASC LIMIT 1",
                ['v' => $vid, 'f' => $fecha]
            );
            if ($post) {
                $r = db_one("SELECT COALESCE(SUM(km),0) km FROM flotilla_km_gps WHERE vehiculo_id=:v AND fecha > :f AND fecha <= :fp",
                    ['v' => $vid, 'f' => $fecha, 'fp' => substr((string) $post['leido_en'], 0, 10)]);
                $est = (int) round((float) $post['km'] - (float) ($r['km'] ?? 0));
                return $est > 0 ? $est : null;
            }
        }
        return null; // sin lectura real de referencia
    } catch (Throwable $e) {
        return null;
    }
}

/** Última fecha con dato de km GPS de un vehículo (o null). */
function flotilla_km_gps_ultima_fecha(int $vid): ?string {
    try {
        if (!db_one("SHOW TABLES LIKE 'flotilla_km_gps'")) return null;
        $r = db_one("SELECT MAX(fecha) f FROM flotilla_km_gps WHERE vehiculo_id = :v", ['v' => $vid]);
        return $r['f'] ?? null;
    } catch (Throwable $e) { return null; }
}

/**
 * Importa el contenido (HTML/XLS) de un reporte Monsat: parsea, mapea por alias,
 * hace upsert del km diario y avanza el odómetro. Devuelve un resumen.
 * Reutilizable por la página de importación y por el cron de correo.
 */
function flotilla_monsat_importar(string $html): array {
    $out = ['vehiculos' => 0, 'dias' => 0, 'no_reconocidos' => []];
    if (!db_one("SHOW TABLES LIKE 'flotilla_km_gps'")) return $out;

    $map = [];
    foreach (db_all("SELECT id, alias FROM flotilla_vehiculos") as $v) {
        if (!empty($v['alias'])) $map[flotilla_norm_economico($v['alias'])] = (int) $v['id'];
    }

    foreach (flotilla_monsat_parse($html) as $blq) {
        $disp = (string) ($blq['dispositivo'] ?? '');
        $vid  = $map[flotilla_norm_economico($disp)] ?? null;
        if (!$vid) {
            if ($disp !== '') $out['no_reconocidos'][$disp] = ($out['no_reconocidos'][$disp] ?? 0) + count($blq['filas']);
            continue;
        }
        $dias = 0;
        foreach ($blq['filas'] as $f) {
            if (empty($f['fecha'])) continue;
            db_exec(
                "INSERT INTO flotilla_km_gps (vehiculo_id, fecha, km, litros, costo_comb, fuente)
                 VALUES (:v, :f, :km, :l, :c, 'monsat')
                 ON DUPLICATE KEY UPDATE km = VALUES(km), litros = VALUES(litros),
                    costo_comb = VALUES(costo_comb), actualizado_en = CURRENT_TIMESTAMP",
                ['v' => $vid, 'f' => $f['fecha'], 'km' => $f['km'], 'l' => $f['litros'], 'c' => $f['costo']]
            );
            $dias++;
        }
        if ($dias > 0) {
            $out['vehiculos']++;
            $out['dias'] += $dias;
        }
    }
    return $out;
}

/** Extrae los adjuntos (nombre + contenido decodificado) de un correo IMAP. */
function flotilla_monsat_adjuntos($imap, int $num): array {
    $adjs = [];
    $struct = imap_fetchstructure($imap, $num);
    if (empty($struct->parts)) return $adjs;
    $walk = function (array $parts, string $prefix) use (&$walk, $imap, $num, &$adjs): void {
        foreach ($parts as $i => $part) {
            $partno = $prefix === '' ? (string) ($i + 1) : $prefix . '.' . ($i + 1);
            $nombre = '';
            if (!empty($part->ifdparameters)) foreach ($part->dparameters as $pp) if (strtolower($pp->attribute) === 'filename') $nombre = $pp->value;
            if ($nombre === '' && !empty($part->ifparameters)) foreach ($part->parameters as $pp) if (strtolower($pp->attribute) === 'name') $nombre = $pp->value;
            if ($nombre !== '') {
                $data = imap_fetchbody($imap, $num, $partno);
                $enc = (int) ($part->encoding ?? 0);
                if ($enc === 3) $data = base64_decode($data);
                elseif ($enc === 4) $data = quoted_printable_decode($data);
                $adjs[] = ['nombre' => $nombre, 'contenido' => (string) $data];
            }
            if (!empty($part->parts)) $walk($part->parts, $partno);
        }
    };
    $walk($struct->parts, '');
    return $adjs;
}

/**
 * Conecta a una cuenta IMAP de Monsat y procesa sus correos.
 * $cuenta: fila de flotilla_monsat_cuentas (con password_cifrada).
 * Si $importar es false, solo prueba la conexión y cuenta correos.
 * Devuelve ['ok','error','correos','vehiculos','dias','sin_match','encontrados'].
 */
function flotilla_monsat_procesar_cuenta(array $cuenta, bool $importar = true): array {
    require_once __DIR__ . '/vault_helpers.php';
    $out = ['ok' => false, 'error' => null, 'correos' => 0, 'vehiculos' => 0, 'dias' => 0, 'sin_match' => [], 'encontrados' => 0];

    if (!function_exists('imap_open')) { $out['error'] = 'La extensión IMAP de PHP no está habilitada.'; return $out; }

    $pass = vault_descifrar($cuenta['password_cifrada'] ?? null) ?? '';
    $host = trim((string) ($cuenta['host'] ?? ''));
    $port = (int) ($cuenta['port'] ?? 993) ?: 993;
    $mbox = '{' . $host . ':' . $port . '/imap/ssl}' . (trim((string) ($cuenta['folder'] ?? '')) ?: 'INBOX');

    $imap = @imap_open($mbox, (string) ($cuenta['usuario'] ?? ''), $pass);
    if (!$imap) { $out['error'] = 'No se pudo conectar: ' . imap_last_error(); return $out; }

    $crit = !empty($cuenta['solo_no_leidos']) ? 'UNSEEN' : 'ALL';
    if (!empty($cuenta['remitente'])) $crit .= ' FROM "' . str_replace('"', '', (string) $cuenta['remitente']) . '"';
    $ids = imap_search($imap, $crit) ?: [];
    $out['encontrados'] = count($ids);

    if ($importar) {
        foreach ($ids as $num) {
            $tuvo = false;
            foreach (flotilla_monsat_adjuntos($imap, (int) $num) as $a) {
                if (!preg_match('/\.(xls|xlsx|html?|htm)$/i', $a['nombre'])) continue;
                if (stripos($a['contenido'], '<') === false) continue;
                $r = flotilla_monsat_importar($a['contenido']);
                $out['vehiculos'] += $r['vehiculos'];
                $out['dias'] += $r['dias'];
                foreach ($r['no_reconocidos'] as $d => $cn) $out['sin_match'][$d] = ($out['sin_match'][$d] ?? 0) + $cn;
                $tuvo = true;
            }
            if ($tuvo) {
                $out['correos']++;
                if (!empty($cuenta['marcar_leidos'])) imap_setflag_full($imap, (string) $num, "\\Seen");
            }
        }
    }
    imap_close($imap);
    $out['ok'] = true;
    return $out;
}

/** Normaliza un económico/alias para comparar ("C-11", "C11", "c 11" -> "C11"). */
function flotilla_norm_economico(string $s): string {
    return strtoupper(preg_replace('/[^a-z0-9]/i', '', $s));
}

