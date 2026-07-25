<?php
/**
 * ============================================================================
 * config/reportes_helpers.php
 * ============================================================================
 * Funciones compartidas para todos los reportes del sistema.
 * Centraliza las queries de métricas que se reutilizan entre reportes.
 * ============================================================================
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

requerir_login();
requerir_permiso('ver_reportes');

// ============================================================================
// PARSEO DE RANGO DE FECHAS
// ============================================================================

/**
 * Resuelve un rango de fechas desde el request (?periodo=mes_actual, etc.)
 * Devuelve ['desde' => 'YYYY-MM-DD', 'hasta' => 'YYYY-MM-DD', 'etiqueta' => '...']
 */
function resolver_periodo(): array {
    $periodo = (string) input('periodo', 'mes_actual');
    $hoy = date('Y-m-d');
    $meses_es = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    switch ($periodo) {
        case 'hoy':
            return ['desde' => $hoy, 'hasta' => $hoy, 'etiqueta' => 'Hoy · ' . date('d/m/Y')];
        case 'semana_actual':
            $lunes = date('Y-m-d', strtotime('monday this week'));
            return ['desde' => $lunes, 'hasta' => $hoy, 'etiqueta' => 'Semana actual'];
        case 'mes_anterior':
            $desde = date('Y-m-01', strtotime('first day of last month'));
            $hasta = date('Y-m-t', strtotime('last day of last month'));
            $mes = $meses_es[(int) date('n', strtotime($desde)) - 1];
            return ['desde' => $desde, 'hasta' => $hasta, 'etiqueta' => $mes . ' ' . date('Y', strtotime($desde))];
        case 'trimestre':
            $desde = date('Y-m-d', strtotime('-90 days'));
            return ['desde' => $desde, 'hasta' => $hoy, 'etiqueta' => 'Últimos 90 días'];
        case 'año_actual':
            return ['desde' => date('Y-01-01'), 'hasta' => $hoy, 'etiqueta' => 'Año ' . date('Y')];
        case 'personalizado':
            $desde = (string) input('desde', date('Y-m-01'));
            $hasta = (string) input('hasta', $hoy);
            return ['desde' => $desde, 'hasta' => $hasta, 'etiqueta' => "Del $desde al $hasta"];
        case 'mes_actual':
        default:
            $mes = $meses_es[(int) date('n') - 1];
            return ['desde' => date('Y-m-01'), 'hasta' => $hoy, 'etiqueta' => $mes . ' ' . date('Y')];
    }
}

// ============================================================================
// FILTRO DE SUCURSAL CON PERMISOS
// ============================================================================

/**
 * Resuelve el filtro de sucursal respetando los permisos del usuario.
 * Devuelve [sucursal_id|null, lista_sucursales, where_clause, params].
 */
function resolver_filtro_sucursal(): array {
    $u = usuario_actual();
    $ver_todas = tiene_permiso('ver_todas_sucursales');

    $sucursal_filtro = null;
    if ($ver_todas) {
        $val = input('sucursal');
        $sucursal_filtro = ($val !== null && $val !== '') ? (int) $val : null;
    } else {
        $sucursal_filtro = $u['sucursal_id'];
    }

    $sucursales = $ver_todas
        ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo = 1 ORDER BY nombre")
        : [];

    $where = '';
    $params = [];
    if ($sucursal_filtro) {
        $where = ' AND i.sucursal_id = :sid ';
        $params['sid'] = $sucursal_filtro;
    }

    return [$sucursal_filtro, $sucursales, $where, $params];
}

// ============================================================================
// MÉTRICAS GENERALES (reutilizadas en varios reportes)
// ============================================================================

/**
 * KPIs principales de un período.
 */
function metricas_generales(string $desde, string $hasta, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);

    $row = db_one(
        "SELECT
            COUNT(*) total,
            SUM(CASE WHEN est.es_final = 1 THEN 1 ELSE 0 END) cerradas,
            SUM(CASE WHEN est.es_final = 0 THEN 1 ELSE 0 END) abiertas,
            SUM(CASE WHEN i.es_reincidencia = 1 THEN 1 ELSE 0 END) reincidencias,
            SUM(CASE WHEN sev.nivel = 1 THEN 1 ELSE 0 END) criticas,
            AVG(CASE WHEN i.tiempo_resolucion_min IS NOT NULL THEN i.tiempo_resolucion_min END) avg_resolucion,
            AVG(CASE WHEN i.tiempo_respuesta_min IS NOT NULL THEN i.tiempo_respuesta_min END) avg_respuesta,
            SUM(CASE WHEN i.sla_cumplido = 1 THEN 1 ELSE 0 END) sla_cumplido,
            SUM(CASE WHEN i.sla_cumplido = 0 THEN 1 ELSE 0 END) sla_incumplido
         FROM incidencias i
         INNER JOIN estados est ON i.estado_id = est.id
         INNER JOIN severidades sev ON i.severidad_id = sev.id
         WHERE DATE(i.creado_en) BETWEEN :d AND :h $extra_where",
        $params
    );

    $total = (int) ($row['total'] ?? 0);
    $sla_eval = (int) ($row['sla_cumplido'] ?? 0) + (int) ($row['sla_incumplido'] ?? 0);

    return [
        'total'           => $total,
        'cerradas'        => (int) ($row['cerradas'] ?? 0),
        'abiertas'        => (int) ($row['abiertas'] ?? 0),
        'reincidencias'   => (int) ($row['reincidencias'] ?? 0),
        'criticas'        => (int) ($row['criticas'] ?? 0),
        'avg_resolucion'  => $row['avg_resolucion'] !== null ? (int) round($row['avg_resolucion']) : null,
        'avg_respuesta'   => $row['avg_respuesta'] !== null ? (int) round($row['avg_respuesta']) : null,
        'sla_cumplido'    => (int) ($row['sla_cumplido'] ?? 0),
        'sla_incumplido'  => (int) ($row['sla_incumplido'] ?? 0),
        'sla_pct'         => $sla_eval > 0 ? round(((int) $row['sla_cumplido'] / $sla_eval) * 100) : null,
        'pct_reincidencia'=> $total > 0 ? round(((int) $row['reincidencias'] / $total) * 100, 1) : 0,
        'pct_cierre'      => $total > 0 ? round(((int) $row['cerradas'] / $total) * 100) : 0,
    ];
}

/**
 * Datos diarios para gráficas de tendencia.
 */
function tendencia_diaria(string $desde, string $hasta, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);
    $rows = db_all(
        "SELECT DATE(i.creado_en) fecha,
                COUNT(*) total,
                SUM(CASE WHEN i.es_reincidencia = 1 THEN 1 ELSE 0 END) reincidencias,
                SUM(CASE WHEN sev.nivel = 1 THEN 1 ELSE 0 END) criticas
         FROM incidencias i
         INNER JOIN severidades sev ON i.severidad_id = sev.id
         WHERE DATE(i.creado_en) BETWEEN :d AND :h $extra_where
         GROUP BY DATE(i.creado_en)
         ORDER BY fecha",
        $params
    );

    // Rellenar días vacíos
    $map = [];
    foreach ($rows as $r) $map[$r['fecha']] = $r;

    $resultado = [];
    $cursor = strtotime($desde);
    $fin = strtotime($hasta);
    while ($cursor <= $fin) {
        $f = date('Y-m-d', $cursor);
        $resultado[] = [
            'fecha' => $f,
            'label' => date('d/m', $cursor),
            'total' => isset($map[$f]) ? (int) $map[$f]['total'] : 0,
            'reincidencias' => isset($map[$f]) ? (int) $map[$f]['reincidencias'] : 0,
            'criticas' => isset($map[$f]) ? (int) $map[$f]['criticas'] : 0,
        ];
        $cursor = strtotime('+1 day', $cursor);
        if (count($resultado) > 366) break; // safety
    }
    return $resultado;
}

/**
 * Distribución por categoría.
 */
function distribucion_por_categoria(string $desde, string $hasta, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);
    return db_all(
        "SELECT c.id, c.nombre, c.color, COUNT(i.id) total
         FROM categorias c
         INNER JOIN incidencias i ON i.categoria_id = c.id
            AND DATE(i.creado_en) BETWEEN :d AND :h $extra_where
         GROUP BY c.id, c.nombre, c.color
         HAVING total > 0
         ORDER BY total DESC",
        $params
    );
}

/**
 * Distribución por severidad.
 */
function distribucion_por_severidad(string $desde, string $hasta, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);
    return db_all(
        "SELECT s.id, s.nombre, s.color, s.nivel, COUNT(i.id) total
         FROM severidades s
         INNER JOIN incidencias i ON i.severidad_id = s.id
            AND DATE(i.creado_en) BETWEEN :d AND :h $extra_where
         GROUP BY s.id, s.nombre, s.color, s.nivel
         ORDER BY s.nivel ASC",
        $params
    );
}

/**
 * Top áreas.
 */
function top_areas(string $desde, string $hasta, int $limite = 10, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);
    return db_all(
        "SELECT a.id, a.nombre, a.color, COUNT(i.id) total
         FROM areas a
         INNER JOIN incidencias i ON i.area_id = a.id
            AND DATE(i.creado_en) BETWEEN :d AND :h $extra_where
         GROUP BY a.id, a.nombre, a.color
         ORDER BY total DESC
         LIMIT $limite",
        $params
    );
}

/**
 * Top equipos con más fallas.
 */
function top_equipos(string $desde, string $hasta, int $limite = 10, string $extra_where = '', array $extra_params = []): array {
    $params = array_merge(['d' => $desde, 'h' => $hasta], $extra_params);
    return db_all(
        "SELECT eq.id, eq.codigo_inventario, eq.nombre, eq.tipo, s.nombre sucursal_nombre,
                COUNT(i.id) total
         FROM equipos eq
         INNER JOIN incidencias i ON i.equipo_id = eq.id
            AND DATE(i.creado_en) BETWEEN :d AND :h $extra_where
         INNER JOIN sucursales s ON eq.sucursal_id = s.id
         GROUP BY eq.id, eq.codigo_inventario, eq.nombre, eq.tipo, s.nombre
         ORDER BY total DESC
         LIMIT $limite",
        $params
    );
}

/**
 * Comparativa por sucursal.
 */
function comparativa_sucursales(string $desde, string $hasta): array {
    return db_all(
        "SELECT s.id, s.nombre, s.codigo,
                COUNT(i.id) total,
                SUM(CASE WHEN est.es_final = 0 THEN 1 ELSE 0 END) abiertas,
                SUM(CASE WHEN sev.nivel = 1 THEN 1 ELSE 0 END) criticas,
                SUM(CASE WHEN i.es_reincidencia = 1 THEN 1 ELSE 0 END) reincidencias,
                AVG(i.tiempo_resolucion_min) avg_resolucion,
                SUM(CASE WHEN i.sla_cumplido = 1 THEN 1 ELSE 0 END) sla_cumplido,
                SUM(CASE WHEN i.sla_cumplido = 0 THEN 1 ELSE 0 END) sla_incumplido
         FROM sucursales s
         LEFT JOIN incidencias i ON i.sucursal_id = s.id
            AND DATE(i.creado_en) BETWEEN :d AND :h
         LEFT JOIN estados est ON i.estado_id = est.id
         LEFT JOIN severidades sev ON i.severidad_id = sev.id
         WHERE s.activo = 1
         GROUP BY s.id, s.nombre, s.codigo
         ORDER BY total DESC",
        ['d' => $desde, 'h' => $hasta]
    );
}

// ============================================================================
// ADQUISICIONES DE MANTENIMIENTO (compras del período)
// ============================================================================

/**
 * Compras de refacciones (entradas de almacén con motivo 'compra') en el período.
 * Incluye automáticamente las requisiciones recibidas, porque al recibirlas el
 * sistema genera un movimiento de entrada 'compra' con su costo. Solo suma
 * Cada entrada se valúa con el costo del movimiento y, si viene vacío, con el
 * costo unitario del catálogo de la refacción (COALESCE), para que las compras
 * cuenten aunque no se haya capturado el costo en el movimiento.
 * Devuelve total ($), número de movimientos, piezas y desglose por refacción.
 */
function adquisiciones_refacciones(string $desde, string $hasta, int $sucursal_id = 0, int $limite = 15): array {
    $vacio = ['total' => 0.0, 'movimientos' => 0, 'piezas' => 0.0, 'detalle' => []];
    if (!db_one("SHOW TABLES LIKE 'refacciones_movimientos'")) return $vacio;

    $where  = "m.tipo = 'entrada' AND m.motivo = 'compra'
               AND DATE(m.creado_en) BETWEEN :d AND :h";
    $params = ['d' => $desde, 'h' => $hasta];
    if ($sucursal_id > 0) { $where .= ' AND m.sucursal_id = :s'; $params['s'] = $sucursal_id; }

    // Valor de la compra: costo del movimiento o, en su defecto, el del catálogo.
    $valor = 'm.cantidad * COALESCE(m.costo_unitario, r.costo_unitario, 0)';

    $tot = db_one(
        "SELECT COALESCE(SUM($valor), 0) total,
                COUNT(*) movimientos,
                COALESCE(SUM(m.cantidad), 0) piezas
           FROM refacciones_movimientos m
           INNER JOIN refacciones r ON m.refaccion_id = r.id
          WHERE $where",
        $params
    ) ?: ['total' => 0, 'movimientos' => 0, 'piezas' => 0];

    $detalle = db_all(
        "SELECT r.codigo, r.nombre,
                COUNT(*) movimientos,
                COALESCE(SUM(m.cantidad), 0) piezas,
                COALESCE(SUM($valor), 0) total
           FROM refacciones_movimientos m
           INNER JOIN refacciones r ON m.refaccion_id = r.id
          WHERE $where
          GROUP BY r.id
          HAVING total > 0
          ORDER BY total DESC
          LIMIT " . (int) $limite,
        $params
    );

    return [
        'total'       => (float) $tot['total'],
        'movimientos' => (int) $tot['movimientos'],
        'piezas'      => (float) $tot['piezas'],
        'detalle'     => $detalle,
    ];
}

/**
 * Equipos adquiridos (comprados) en el período, según fecha_compra y costo_compra.
 * Devuelve total ($), número de equipos y desglose.
 */
function adquisiciones_equipos(string $desde, string $hasta, int $sucursal_id = 0, int $limite = 15): array {
    $vacio = ['total' => 0.0, 'equipos' => 0, 'detalle' => []];
    if (!db_one("SHOW TABLES LIKE 'equipos'")) return $vacio;

    $where  = "e.fecha_compra IS NOT NULL AND e.costo_compra IS NOT NULL AND e.costo_compra > 0
               AND e.fecha_compra BETWEEN :d AND :h";
    $params = ['d' => $desde, 'h' => $hasta];
    if ($sucursal_id > 0) { $where .= ' AND e.sucursal_id = :s'; $params['s'] = $sucursal_id; }

    $tot = db_one(
        "SELECT COALESCE(SUM(e.costo_compra), 0) total, COUNT(*) equipos
           FROM equipos e WHERE $where",
        $params
    ) ?: ['total' => 0, 'equipos' => 0];

    $detalle = db_all(
        "SELECT e.id, e.codigo_inventario, e.nombre, e.fecha_compra, e.costo_compra,
                s.nombre sucursal_nombre
           FROM equipos e
           LEFT JOIN sucursales s ON e.sucursal_id = s.id
          WHERE $where
          ORDER BY e.costo_compra DESC
          LIMIT " . (int) $limite,
        $params
    );

    return [
        'total'   => (float) $tot['total'],
        'equipos' => (int) $tot['equipos'],
        'detalle' => $detalle,
    ];
}

// ============================================================================
// EXPORTACIÓN A CSV
// ============================================================================

/**
 * Inicializa una salida CSV con BOM UTF-8.
 */
function csv_iniciar(string $filename): void {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo "\xEF\xBB\xBF"; // BOM UTF-8
}

/**
 * Escribe una fila CSV en la salida.
 */
function csv_fila(array $campos): void {
    $handle = fopen('php://output', 'a');
    fputcsv($handle, $campos);
    fclose($handle);
}
