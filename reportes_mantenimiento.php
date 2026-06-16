<?php
/**
 * ============================================================================
 * reportes_mantenimiento.php
 * ============================================================================
 * Reportes específicos del área de mantenimiento:
 *   - Refacciones más consumidas
 *   - Costos por equipo / categoría / mes
 *   - Equipos con más fallas
 *   - Herramientas más prestadas
 *   - Componentes problemáticos
 *   - MTBF (tiempo medio entre fallas)
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/reportes_helpers.php';
require_once __DIR__ . '/config/medidores_helpers.php';

requerir_login();
$u = usuario_actual();

if (!tiene_permiso('ver_reportes')) {
    flash_set('error', 'Sin permiso para ver reportes.');
    header('Location: ' . url('dashboard.php'));
    exit;
}

// ¿Es una solicitud de exportación?
$es_exportacion = (input('exportar') === 'csv');

// ----------------------------------------------------------------------------
// Filtros de rango de fechas
// ----------------------------------------------------------------------------
$f_desde = trim((string) input('desde', '')) ?: date('Y-m-d', strtotime('-3 months'));
$f_hasta = trim((string) input('hasta', '')) ?: date('Y-m-d');
$f_sucursal = (int) input('sucursal_id', 0);

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$cond_suc = $f_sucursal > 0 ? "AND i.sucursal_id = " . $f_sucursal : "";
$cond_suc_eq = $f_sucursal > 0 ? "AND e.sucursal_id = " . $f_sucursal : "";

// ----------------------------------------------------------------------------
// 1) REFACCIONES MÁS CONSUMIDAS
// ----------------------------------------------------------------------------
$top_refacciones = db_all(
    "SELECT r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria,
            COUNT(ir.id) AS veces_usada,
            SUM(ir.cantidad) AS cantidad_total,
            COALESCE(SUM(ir.costo_total), 0) AS costo_total
     FROM incidencia_refacciones ir
     INNER JOIN refacciones r ON ir.refaccion_id = r.id
     INNER JOIN incidencias i ON ir.incidencia_id = i.id
     WHERE DATE(ir.creado_en) BETWEEN :desde AND :hasta
       $cond_suc
     GROUP BY r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria
     ORDER BY cantidad_total DESC, veces_usada DESC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 2) COSTO POR EQUIPO (refacciones usadas)
// ----------------------------------------------------------------------------
$costo_por_equipo = db_all(
    "SELECT e.id, e.codigo_inventario, e.nombre AS equipo_nombre,
            COUNT(DISTINCT i.id) AS num_ordenes,
            COUNT(ir.id) AS lineas_refacciones,
            COALESCE(SUM(ir.cantidad), 0) AS unidades_total,
            COALESCE(SUM(ir.costo_total), 0) AS costo_total
     FROM incidencia_refacciones ir
     INNER JOIN incidencias i ON ir.incidencia_id = i.id
     INNER JOIN equipos e ON i.equipo_id = e.id
     WHERE DATE(ir.creado_en) BETWEEN :desde AND :hasta
       $cond_suc
     GROUP BY e.id, e.codigo_inventario, e.nombre
     ORDER BY costo_total DESC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 3) COSTO POR CATEGORÍA DE MANTENIMIENTO
// ----------------------------------------------------------------------------
$costo_por_categoria = db_all(
    "SELECT c.nombre AS categoria_nombre, c.color,
            COUNT(DISTINCT i.id) AS num_ordenes,
            COALESCE(SUM(ir.costo_total), 0) AS costo_refacciones
     FROM incidencias i
     LEFT JOIN categorias c ON i.categoria_id = c.id
     LEFT JOIN incidencia_refacciones ir ON ir.incidencia_id = i.id
     WHERE DATE(i.creado_en) BETWEEN :desde AND :hasta
       $cond_suc
     GROUP BY c.id, c.nombre, c.color
     ORDER BY costo_refacciones DESC, num_ordenes DESC",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 4) COSTO MENSUAL (últimos 12 meses)
// ----------------------------------------------------------------------------
$costo_mensual = db_all(
    "SELECT DATE_FORMAT(ir.creado_en, '%Y-%m') AS mes,
            COUNT(DISTINCT ir.incidencia_id) AS ordenes,
            COUNT(ir.id) AS lineas,
            COALESCE(SUM(ir.cantidad), 0) AS unidades,
            COALESCE(SUM(ir.costo_total), 0) AS costo_total
     FROM incidencia_refacciones ir
     INNER JOIN incidencias i ON ir.incidencia_id = i.id
     WHERE ir.creado_en >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
       $cond_suc
     GROUP BY DATE_FORMAT(ir.creado_en, '%Y-%m')
     ORDER BY mes ASC"
);

// ----------------------------------------------------------------------------
// 5) EQUIPOS CON MÁS FALLAS (incidencias)
// ----------------------------------------------------------------------------
$equipos_problematicos = db_all(
    "SELECT e.id, e.codigo_inventario, e.nombre AS equipo_nombre,
            s.codigo AS sucursal_codigo,
            COUNT(i.id) AS num_incidencias,
            SUM(CASE WHEN i.estado_id IN (6,7) THEN 1 ELSE 0 END) AS resueltas,
            AVG(CASE
                WHEN i.fecha_resolucion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, i.creado_en, i.fecha_resolucion)
                ELSE NULL END) AS horas_promedio_resolucion
     FROM incidencias i
     INNER JOIN equipos e ON i.equipo_id = e.id
     INNER JOIN sucursales s ON e.sucursal_id = s.id
     WHERE DATE(i.creado_en) BETWEEN :desde AND :hasta
       $cond_suc
     GROUP BY e.id, e.codigo_inventario, e.nombre, s.codigo
     HAVING num_incidencias >= 1
     ORDER BY num_incidencias DESC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 6) HERRAMIENTAS MÁS PRESTADAS
// ----------------------------------------------------------------------------
$herramientas_top = db_all(
    "SELECT h.id, h.codigo, h.nombre, h.tipo,
            h.estado,
            COUNT(p.id) AS num_prestamos,
            SUM(CASE WHEN p.estado = 'devuelta_con_dano' THEN 1 ELSE 0 END) AS prestamos_con_dano,
            SUM(CASE WHEN p.estado = 'extraviada' THEN 1 ELSE 0 END) AS extravios
     FROM herramientas_prestamos p
     INNER JOIN herramientas h ON p.herramienta_id = h.id
     WHERE DATE(p.fecha_salida) BETWEEN :desde AND :hasta
     GROUP BY h.id, h.codigo, h.nombre, h.tipo, h.estado
     ORDER BY num_prestamos DESC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 7) PRÉSTAMOS POR TÉCNICO
// ----------------------------------------------------------------------------
$prestamos_por_tecnico = db_all(
    "SELECT u.id, u.nombre_completo,
            COUNT(p.id) AS total_prestamos,
            SUM(CASE WHEN p.estado = 'activo' THEN 1 ELSE 0 END) AS activos,
            SUM(CASE WHEN p.estado = 'devuelta' THEN 1 ELSE 0 END) AS devueltos,
            SUM(CASE WHEN p.estado = 'devuelta_con_dano' THEN 1 ELSE 0 END) AS con_dano,
            SUM(CASE WHEN p.estado = 'extraviada' THEN 1 ELSE 0 END) AS extraviados,
            SUM(CASE
                WHEN p.estado = 'activo'
                  AND p.fecha_devolucion_esperada IS NOT NULL
                  AND p.fecha_devolucion_esperada < CURDATE()
                THEN 1 ELSE 0 END) AS vencidos
     FROM herramientas_prestamos p
     INNER JOIN usuarios u ON p.prestada_a_id = u.id
     WHERE DATE(p.fecha_salida) BETWEEN :desde AND :hasta
     GROUP BY u.id, u.nombre_completo
     ORDER BY total_prestamos DESC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 8) COMPONENTES EN MAL ESTADO
// ----------------------------------------------------------------------------
$componentes_problema = db_all(
    "SELECT c.id, c.nombre AS componente_nombre, c.tipo, c.estado, c.criticidad,
            c.proxima_revision,
            e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
            s.codigo AS sucursal_codigo,
            DATEDIFF(c.proxima_revision, CURDATE()) AS dias_para_revision
     FROM equipo_componentes c
     INNER JOIN equipos e ON c.equipo_id = e.id
     INNER JOIN sucursales s ON e.sucursal_id = s.id
     WHERE c.activo = 1
       AND (c.estado IN ('falla','desgaste') OR c.criticidad IN ('alta','critica'))
       $cond_suc_eq
     ORDER BY
        FIELD(c.estado, 'falla', 'desgaste', 'operando', 'reemplazado', 'retirado'),
        FIELD(c.criticidad, 'critica', 'alta', 'media', 'baja'),
        c.proxima_revision ASC
     LIMIT 20"
);

// ----------------------------------------------------------------------------
// 9) MTBF — Tiempo Medio Entre Fallas por equipo (en días)
// ----------------------------------------------------------------------------
// Calculado: rango de fechas / número de fallas
$mtbf_equipos = db_all(
    "SELECT e.id, e.codigo_inventario, e.nombre AS equipo_nombre,
            COUNT(i.id) AS num_fallas,
            MIN(i.creado_en) AS primera_falla,
            MAX(i.creado_en) AS ultima_falla,
            DATEDIFF(MAX(i.creado_en), MIN(i.creado_en)) AS dias_periodo,
            CASE
                WHEN COUNT(i.id) > 1
                THEN ROUND(DATEDIFF(MAX(i.creado_en), MIN(i.creado_en)) / (COUNT(i.id) - 1), 1)
                ELSE NULL
            END AS mtbf_dias
     FROM equipos e
     INNER JOIN incidencias i ON i.equipo_id = e.id
     WHERE DATE(i.creado_en) BETWEEN :desde AND :hasta
       $cond_suc_eq
     GROUP BY e.id, e.codigo_inventario, e.nombre
     HAVING num_fallas >= 2
     ORDER BY mtbf_dias ASC
     LIMIT 15",
    ['desde' => $f_desde, 'hasta' => $f_hasta]
);

// ----------------------------------------------------------------------------
// 10) COMPONENTES PRÓXIMOS A VENCER REVISIÓN
// ----------------------------------------------------------------------------
$componentes_vencer = db_all(
    "SELECT c.id, c.nombre AS componente_nombre, c.estado, c.criticidad,
            c.proxima_revision,
            DATEDIFF(c.proxima_revision, CURDATE()) AS dias_restantes,
            e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
            s.codigo AS sucursal_codigo
     FROM equipo_componentes c
     INNER JOIN equipos e ON c.equipo_id = e.id
     INNER JOIN sucursales s ON e.sucursal_id = s.id
     WHERE c.activo = 1
       AND c.proxima_revision IS NOT NULL
       AND c.proxima_revision <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
       $cond_suc_eq
     ORDER BY c.proxima_revision ASC
     LIMIT 20"
);

// ----------------------------------------------------------------------------
// TOTALES GENERALES DEL PERIODO
// ----------------------------------------------------------------------------
$totales = db_one(
    "SELECT
        (SELECT COUNT(*) FROM incidencias i WHERE DATE(i.creado_en) BETWEEN :desde AND :hasta $cond_suc) AS total_ordenes,
        (SELECT COUNT(*) FROM incidencia_refacciones ir
         INNER JOIN incidencias i ON ir.incidencia_id = i.id
         WHERE DATE(ir.creado_en) BETWEEN :desde2 AND :hasta2 $cond_suc) AS total_movimientos_refacciones,
        (SELECT COALESCE(SUM(ir.costo_total), 0) FROM incidencia_refacciones ir
         INNER JOIN incidencias i ON ir.incidencia_id = i.id
         WHERE DATE(ir.creado_en) BETWEEN :desde3 AND :hasta3 $cond_suc) AS costo_total_refacciones,
        (SELECT COUNT(*) FROM herramientas_prestamos p WHERE DATE(p.fecha_salida) BETWEEN :desde4 AND :hasta4) AS total_prestamos",
    [
        'desde' => $f_desde, 'hasta' => $f_hasta,
        'desde2' => $f_desde, 'hasta2' => $f_hasta,
        'desde3' => $f_desde, 'hasta3' => $f_hasta,
        'desde4' => $f_desde, 'hasta4' => $f_hasta,
    ]
);

$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);

// ----------------------------------------------------------------------------
// CONSUMOS DE SERVICIOS (MEDIDORES)
// ----------------------------------------------------------------------------
$med_resumen  = consumo_resumen_periodo($f_desde, $f_hasta, $f_sucursal ?: null);
$med_por_tipo = consumo_por_tipo($f_desde, $f_hasta, $f_sucursal ?: null);

// ----------------------------------------------------------------------------
// EXPORTACIÓN A EXCEL (CSV con BOM UTF-8, abre directo en Excel)
// ----------------------------------------------------------------------------
if ($es_exportacion) {
    csv_iniciar('reporte_mantenimiento_' . date('Ymd_His') . '.csv');

    csv_fila(['REPORTE DE MANTENIMIENTO']);
    csv_fila(['Generado:', date('d/m/Y H:i')]);
    csv_fila(['Periodo:', "Del $f_desde al $f_hasta"]);
    if ($f_sucursal > 0) {
        $suc_nom = db_one("SELECT nombre FROM sucursales WHERE id = :id", ['id' => $f_sucursal]);
        csv_fila(['Sucursal:', $suc_nom['nombre'] ?? '']);
    } else {
        csv_fila(['Sucursal:', 'Todas']);
    }
    csv_fila(['']);

    // KPIs
    csv_fila(['RESUMEN DEL PERIODO']);
    csv_fila(['Órdenes del periodo:', (int) $totales['total_ordenes']]);
    csv_fila(['Movimientos de refacciones:', (int) $totales['total_movimientos_refacciones']]);
    csv_fila(['Costo total en refacciones ($):', number_format($totales['costo_total_refacciones'], 2, '.', '')]);
    csv_fila(['Préstamos de herramientas:', (int) $totales['total_prestamos']]);
    csv_fila(['']);

    // 1. Refacciones más consumidas
    csv_fila(['REFACCIONES MÁS CONSUMIDAS']);
    csv_fila(['Código', 'Nombre', 'Categoría', 'Veces usada', 'Unidades totales', 'Unidad', 'Costo total ($)']);
    foreach ($top_refacciones as $r) {
        csv_fila([
            $r['codigo'], $r['nombre'], $r['categoria'] ?? '',
            (int) $r['veces_usada'], number_format($r['cantidad_total'], 2, '.', ''),
            $r['unidad_medida'], number_format($r['costo_total'], 2, '.', ''),
        ]);
    }
    csv_fila(['']);

    // 2. Costo por equipo
    csv_fila(['EQUIPOS MÁS CAROS DE MANTENER']);
    csv_fila(['Código', 'Equipo', 'Órdenes', 'Líneas refacciones', 'Unidades', 'Costo total ($)']);
    foreach ($costo_por_equipo as $e) {
        csv_fila([
            $e['codigo_inventario'], $e['equipo_nombre'],
            (int) $e['num_ordenes'], (int) $e['lineas_refacciones'],
            number_format($e['unidades_total'], 2, '.', ''),
            number_format($e['costo_total'], 2, '.', ''),
        ]);
    }
    csv_fila(['']);

    // 3. Por categoría
    csv_fila(['DISTRIBUCIÓN POR DISCIPLINA']);
    csv_fila(['Categoría', 'Órdenes', 'Costo refacciones ($)']);
    foreach ($costo_por_categoria as $c) {
        csv_fila([
            $c['categoria_nombre'] ?? '— Sin categoría —',
            (int) $c['num_ordenes'],
            number_format($c['costo_refacciones'], 2, '.', ''),
        ]);
    }
    csv_fila(['']);

    // 4. Mensual
    csv_fila(['TENDENCIA MENSUAL (ÚLTIMOS 12 MESES)']);
    csv_fila(['Mes', 'Órdenes', 'Líneas', 'Unidades', 'Costo total ($)']);
    foreach ($costo_mensual as $m) {
        csv_fila([
            date('M Y', strtotime($m['mes'] . '-01')),
            (int) $m['ordenes'], (int) $m['lineas'],
            number_format($m['unidades'], 2, '.', ''),
            number_format($m['costo_total'], 2, '.', ''),
        ]);
    }
    csv_fila(['']);

    // 5. Equipos con más fallas
    csv_fila(['EQUIPOS CON MÁS FALLAS']);
    csv_fila(['Código', 'Equipo', 'Sucursal', 'Fallas', 'Resueltas', 'Horas prom. resolución']);
    foreach ($equipos_problematicos as $e) {
        csv_fila([
            $e['codigo_inventario'], $e['equipo_nombre'], $e['sucursal_codigo'],
            (int) $e['num_incidencias'], (int) $e['resueltas'],
            !empty($e['horas_promedio_resolucion']) ? number_format($e['horas_promedio_resolucion'], 1, '.', '') : '',
        ]);
    }
    csv_fila(['']);

    // 6. MTBF
    csv_fila(['MTBF - TIEMPO MEDIO ENTRE FALLAS']);
    csv_fila(['Código', 'Equipo', 'Núm. fallas', 'Primera falla', 'Última falla', 'MTBF (días)']);
    foreach ($mtbf_equipos as $m) {
        csv_fila([
            $m['codigo_inventario'], $m['equipo_nombre'],
            (int) $m['num_fallas'],
            date('d/m/Y', strtotime($m['primera_falla'])),
            date('d/m/Y', strtotime($m['ultima_falla'])),
            !empty($m['mtbf_dias']) ? number_format($m['mtbf_dias'], 1, '.', '') : '',
        ]);
    }
    csv_fila(['']);

    // 7. Herramientas más prestadas
    csv_fila(['HERRAMIENTAS MÁS PRESTADAS']);
    csv_fila(['Código', 'Herramienta', 'Tipo', 'Estado actual', 'Núm. préstamos', 'Daños', 'Extravíos']);
    foreach ($herramientas_top as $h) {
        csv_fila([
            $h['codigo'], $h['nombre'], $h['tipo'] ?? '', $h['estado'],
            (int) $h['num_prestamos'],
            (int) $h['prestamos_con_dano'],
            (int) $h['extravios'],
        ]);
    }
    csv_fila(['']);

    // 8. Préstamos por técnico
    csv_fila(['PRÉSTAMOS POR TÉCNICO']);
    csv_fila(['Técnico', 'Total', 'Activos', 'Devueltos', 'Con daño', 'Extraviados', 'Vencidos']);
    foreach ($prestamos_por_tecnico as $t) {
        csv_fila([
            $t['nombre_completo'],
            (int) $t['total_prestamos'],
            (int) $t['activos'],
            (int) $t['devueltos'],
            (int) $t['con_dano'],
            (int) $t['extraviados'],
            (int) $t['vencidos'],
        ]);
    }
    csv_fila(['']);

    // 9. Componentes problemáticos
    csv_fila(['COMPONENTES EN MAL ESTADO O CRÍTICOS']);
    csv_fila(['Componente', 'Tipo', 'Equipo', 'Sucursal', 'Estado', 'Criticidad', 'Próxima revisión']);
    foreach ($componentes_problema as $c) {
        csv_fila([
            $c['componente_nombre'], $c['tipo'] ?? '',
            $c['equipo_nombre'], $c['sucursal_codigo'],
            $c['estado'], $c['criticidad'],
            !empty($c['proxima_revision']) ? date('d/m/Y', strtotime($c['proxima_revision'])) : '',
        ]);
    }
    csv_fila(['']);

    // 10. Próximas revisiones
    csv_fila(['PRÓXIMAS REVISIONES (60 DÍAS)']);
    csv_fila(['Componente', 'Equipo', 'Sucursal', 'Estado', 'Criticidad', 'Fecha revisión', 'Días restantes']);
    foreach ($componentes_vencer as $c) {
        csv_fila([
            $c['componente_nombre'], $c['equipo_nombre'], $c['sucursal_codigo'],
            $c['estado'], $c['criticidad'],
            date('d/m/Y', strtotime($c['proxima_revision'])),
            (int) $c['dias_restantes'],
        ]);
    }
    csv_fila(['']);

    // 11. Consumos de servicios (medidores)
    csv_fila(['CONSUMOS DE SERVICIOS (MEDIDORES)']);
    csv_fila(['Lecturas del periodo:', (int) $med_resumen['num_lecturas']]);
    csv_fila(['Medidores con lectura:', (int) $med_resumen['medidores_activos']]);
    csv_fila(['Costo estimado total ($):', number_format($med_resumen['costo_total'], 2, '.', '')]);
    csv_fila(['']);
    csv_fila(['Tipo', 'Unidad', 'Lecturas', 'Consumo total', 'Costo estimado ($)']);
    foreach ($med_por_tipo as $t) {
        csv_fila([
            $t['nombre'], $t['unidad'], (int) $t['num_lecturas'],
            number_format((float) $t['consumo_total'], 3, '.', ''),
            number_format((float) $t['costo_total'], 2, '.', ''),
        ]);
    }

    exit;
}

$titulo_pagina = 'Reportes de mantenimiento';
$pagina_activa = 'reportes_mant';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="line-chart" class="w-6 h-6 text-bacal-700"></i>
                Reportes de mantenimiento
            </h2>
            <p class="text-xs text-zinc-500 mt-0.5">Análisis específico del área: refacciones, costos, equipos, herramientas.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= url('reportes_mantenimiento.php?' . http_build_query(['desde' => $f_desde, 'hasta' => $f_hasta, 'sucursal_id' => $f_sucursal, 'exportar' => 'csv'])) ?>"
               class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Exportar a Excel
            </a>
            <a href="<?= url('reportes/reportes.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Reportes generales
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-[10px] font-bold text-zinc-700 mb-1 uppercase tracking-wide">Desde</label>
                <input type="date" name="desde" value="<?= e($f_desde) ?>"
                       class="px-3 py-1.5 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-700 mb-1 uppercase tracking-wide">Hasta</label>
                <input type="date" name="hasta" value="<?= e($f_hasta) ?>"
                       class="px-3 py-1.5 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <?php if (tiene_permiso('ver_todas_sucursales')): ?>
            <div>
                <label class="block text-[10px] font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal</label>
                <select name="sucursal_id" class="px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-sm">
                    <option value="0">Todas</option>
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" class="px-4 py-1.5 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                Aplicar
            </button>
        </form>
    </div>

    <!-- KPIs generales del periodo -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Órdenes del periodo</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $totales['total_ordenes'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Movimientos de refacciones</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $totales['total_movimientos_refacciones'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold">Costo en refacciones</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700">$<?= number_format($totales['costo_total_refacciones'], 0) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Préstamos de herramientas</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $totales['total_prestamos'] ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- 1. Refacciones más consumidas -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-bacal-700"></i>
                    Refacciones más consumidas
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Qué piezas debes tener siempre en stock</p>
            </div>
            <?php if (empty($top_refacciones)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Refacción</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Usos</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Unidades</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($top_refacciones as $r): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <a href="<?= url('refaccion_ver.php?id=' . $r['id']) ?>"
                           class="font-semibold text-xs text-zinc-900 hover:text-bacal-700">
                            <?= e($r['nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500 font-mono"><?= e($r['codigo']) ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-bold"><?= (int) $r['veces_usada'] ?></td>
                    <td class="px-3 py-2 text-right text-xs">
                        <?= number_format($r['cantidad_total'], 0) ?>
                        <span class="text-[10px] text-zinc-500"><?= e($r['unidad_medida']) ?></span>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">$<?= number_format($r['costo_total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 2. Costo por equipo -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="cog" class="w-4 h-4 text-bacal-700"></i>
                    Equipos más caros de mantener
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Candidatos a reemplazo o atención especial</p>
            </div>
            <?php if (empty($costo_por_equipo)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Equipo</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Órdenes</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($costo_por_equipo as $e): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <a href="<?= url('equipo_ver.php?id=' . $e['id']) ?>"
                           class="font-semibold text-xs text-zinc-900 hover:text-bacal-700">
                            <?= e($e['equipo_nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500 font-mono"><?= e($e['codigo_inventario']) ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs"><?= (int) $e['num_ordenes'] ?></td>
                    <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">$<?= number_format($e['costo_total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 3. Costo por categoría -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-bacal-700"></i>
                    Distribución por disciplina
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Cómo se reparte el costo entre mecánica, eléctrica, etc.</p>
            </div>
            <?php if (empty($costo_por_categoria)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Categoría</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Órdenes</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo refacciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($costo_por_categoria as $c): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <span class="inline-flex items-center gap-1.5">
                            <?php if (!empty($c['color'])): ?>
                            <span class="w-2 h-2 rounded-full" style="background-color: <?= e($c['color']) ?>"></span>
                            <?php endif; ?>
                            <span class="text-xs font-semibold text-zinc-900"><?= e($c['categoria_nombre'] ?? '— Sin categoría —') ?></span>
                        </span>
                    </td>
                    <td class="px-3 py-2 text-right text-xs"><?= (int) $c['num_ordenes'] ?></td>
                    <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">$<?= number_format($c['costo_refacciones'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 4. Costo mensual -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-bacal-700"></i>
                    Tendencia de gasto mensual
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Últimos 12 meses (no usa filtros de fecha)</p>
            </div>
            <?php if (empty($costo_mensual)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos históricos aún.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Mes</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Órdenes</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Unidades</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($costo_mensual as $m):
                    $mes_nombre = date('M Y', strtotime($m['mes'] . '-01'));
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2 text-xs font-semibold"><?= e($mes_nombre) ?></td>
                    <td class="px-3 py-2 text-right text-xs"><?= (int) $m['ordenes'] ?></td>
                    <td class="px-3 py-2 text-right text-xs"><?= number_format($m['unidades'], 0) ?></td>
                    <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-700">$<?= number_format($m['costo_total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 5. Equipos con más fallas -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-bacal-700"></i>
                    Equipos con más fallas
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Top equipos con incidencias recurrentes</p>
            </div>
            <?php if (empty($equipos_problematicos)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Equipo</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Fallas</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Hrs prom.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($equipos_problematicos as $e): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <a href="<?= url('equipo_ver.php?id=' . $e['id']) ?>"
                           class="font-semibold text-xs text-zinc-900 hover:text-bacal-700">
                            <?= e($e['equipo_nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500"><?= e($e['codigo_inventario']) ?> · <?= e($e['sucursal_codigo']) ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-bold"><?= (int) $e['num_incidencias'] ?></td>
                    <td class="px-3 py-2 text-right text-xs text-zinc-600">
                        <?= !empty($e['horas_promedio_resolucion']) ? number_format($e['horas_promedio_resolucion'], 1) . 'h' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 6. MTBF -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="activity" class="w-4 h-4 text-bacal-700"></i>
                    MTBF — Tiempo entre fallas
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Días promedio entre fallas (menor = peor confiabilidad)</p>
            </div>
            <?php if (empty($mtbf_equipos)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Necesitas equipos con 2+ fallas en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Equipo</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Fallas</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">MTBF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($mtbf_equipos as $m):
                    $mtbf = (float) ($m['mtbf_dias'] ?? 0);
                    $color = $mtbf < 7 ? 'text-bacal-700 font-bold' : ($mtbf < 30 ? 'text-amber-700 font-semibold' : 'text-emerald-700 font-semibold');
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <a href="<?= url('equipo_ver.php?id=' . $m['id']) ?>"
                           class="font-semibold text-xs text-zinc-900 hover:text-bacal-700">
                            <?= e($m['equipo_nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500 font-mono"><?= e($m['codigo_inventario']) ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs"><?= (int) $m['num_fallas'] ?></td>
                    <td class="px-3 py-2 text-right text-xs <?= $color ?>">
                        <?= number_format($mtbf, 1) ?> días
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 7. Herramientas más prestadas -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="hammer" class="w-4 h-4 text-bacal-700"></i>
                    Herramientas más prestadas
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Candidatas a duplicar o mantenimiento preventivo</p>
            </div>
            <?php if (empty($herramientas_top)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Herramienta</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Préstamos</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Daños</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($herramientas_top as $h): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <a href="<?= url('herramienta_ver.php?id=' . $h['id']) ?>"
                           class="font-semibold text-xs text-zinc-900 hover:text-bacal-700">
                            <?= e($h['nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500"><?= e($h['codigo']) ?><?= !empty($h['tipo']) ? ' · ' . e($h['tipo']) : '' ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-bold"><?= (int) $h['num_prestamos'] ?></td>
                    <td class="px-3 py-2 text-right text-xs">
                        <?php if ((int) $h['prestamos_con_dano'] > 0 || (int) $h['extravios'] > 0): ?>
                        <span class="text-bacal-700 font-bold">
                            <?= (int) $h['prestamos_con_dano'] + (int) $h['extravios'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 8. Préstamos por técnico -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-bacal-700"></i>
                    Préstamos por técnico
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Quién usa más herramientas y su responsabilidad</p>
            </div>
            <?php if (empty($prestamos_por_tecnico)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin datos en el periodo.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Técnico</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Total</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Activos</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Vencidos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($prestamos_por_tecnico as $t): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2 text-xs font-semibold"><?= e($t['nombre_completo']) ?></td>
                    <td class="px-3 py-2 text-right text-xs font-bold"><?= (int) $t['total_prestamos'] ?></td>
                    <td class="px-3 py-2 text-right text-xs"><?= (int) $t['activos'] ?></td>
                    <td class="px-3 py-2 text-right text-xs">
                        <?php if ((int) $t['vencidos'] > 0): ?>
                        <span class="text-bacal-700 font-bold"><?= (int) $t['vencidos'] ?></span>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 9. Componentes problemáticos -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-bacal-700"></i>
                    Componentes en mal estado o críticos
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Requieren atención prioritaria</p>
            </div>
            <?php if (empty($componentes_problema)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin componentes problemáticos registrados. 🎉</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Componente</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Estado</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Criticidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($componentes_problema as $c):
                    $est_color = match ($c['estado']) {
                        'falla' => '#DC2626',
                        'desgaste' => '#F59E0B',
                        default => '#71717A',
                    };
                    $crit_color = match ($c['criticidad']) {
                        'critica' => '#DC2626',
                        'alta' => '#EA580C',
                        'media' => '#F59E0B',
                        default => '#16A34A',
                    };
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <div class="font-semibold text-xs text-zinc-900"><?= e($c['componente_nombre']) ?></div>
                        <a href="<?= url('equipo_ver.php?id=' . $c['id']) ?>" class="text-[10px] text-zinc-500 hover:text-bacal-700">
                            <?= e($c['equipo_nombre']) ?> · <?= e($c['sucursal_codigo']) ?>
                        </a>
                    </td>
                    <td class="px-3 py-2">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                              style="color: <?= e($est_color) ?>; background-color: <?= e($est_color) ?>15">
                            <?= e($c['estado']) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                              style="color: <?= e($crit_color) ?>; background-color: <?= e($crit_color) ?>15">
                            <?= e($c['criticidad']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- 10. Componentes próximos a revisar -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-4 h-4 text-bacal-700"></i>
                    Próximas revisiones (60 días)
                </h3>
                <p class="text-[10px] text-zinc-500 mt-0.5">Planea visitas preventivas</p>
            </div>
            <?php if (empty($componentes_vencer)): ?>
            <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin revisiones programadas en los próximos 60 días.</div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Componente</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Fecha</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Días</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($componentes_vencer as $c):
                    $dias = (int) $c['dias_restantes'];
                    $color = $dias < 0 ? 'text-bacal-700 font-bold' : ($dias < 14 ? 'text-amber-700 font-semibold' : 'text-zinc-700');
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2">
                        <div class="font-semibold text-xs text-zinc-900"><?= e($c['componente_nombre']) ?></div>
                        <div class="text-[10px] text-zinc-500"><?= e($c['equipo_nombre']) ?> · <?= e($c['sucursal_codigo']) ?></div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs"><?= e(date('d/M/Y', strtotime($c['proxima_revision']))) ?></td>
                    <td class="px-3 py-2 text-right text-xs <?= $color ?>">
                        <?= $dias < 0 ? 'VENCIDO ' . abs($dias) . 'd' : $dias . 'd' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- ====================== CONSUMOS DE SERVICIOS (MEDIDORES) ====================== -->
    <div class="mt-8 mb-3">
        <h3 class="font-display text-lg font-bold text-zinc-900 flex items-center gap-2">
            <i data-lucide="gauge" class="w-5 h-5 text-bacal-700"></i>
            Consumos de servicios
        </h3>
        <p class="text-xs text-zinc-500 mt-0.5">Luz, agua, gas, diésel y demás medidores del período. Costo estimado (consumo × tarifa).</p>
    </div>

    <!-- KPIs de consumo -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Lecturas del periodo</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $med_resumen['num_lecturas'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Medidores con lectura</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $med_resumen['medidores_activos'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold">Costo estimado total</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700">$<?= number_format($med_resumen['costo_total'], 0) ?></div>
        </div>
    </div>

    <!-- Consumo y costo por tipo -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-100">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="layers" class="w-4 h-4 text-bacal-700"></i>
                Consumo por tipo de servicio
            </h3>
            <p class="text-[10px] text-zinc-500 mt-0.5">Dónde se concentra el gasto de servicios</p>
        </div>
        <?php if (empty($med_por_tipo)): ?>
        <div class="px-5 py-10 text-center text-xs text-zinc-500">Sin lecturas con consumo en el periodo.</div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 border-b border-zinc-100">
                <tr>
                    <th class="px-4 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Servicio</th>
                    <th class="px-4 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Lecturas</th>
                    <th class="px-4 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Consumo total</th>
                    <th class="px-4 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo estimado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <?php foreach ($med_por_tipo as $t): $color = $t['color'] ?: '#6B7280'; ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-2.5">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded"
                              style="background-color: <?= e($color) ?>15; color: <?= e($color) ?>">
                            <i data-lucide="<?= e($t['icono'] ?: 'gauge') ?>" class="w-3.5 h-3.5"></i>
                            <?= e($t['nombre']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-right text-xs text-zinc-600"><?= (int) $t['num_lecturas'] ?></td>
                    <td class="px-4 py-2.5 text-right text-xs font-mono text-zinc-900">
                        <?= e(fmt_lectura((float) $t['consumo_total'])) ?> <span class="text-[10px] text-zinc-400"><?= e($t['unidad']) ?></span>
                    </td>
                    <td class="px-4 py-2.5 text-right text-xs font-semibold text-emerald-700">$<?= number_format((float) $t['costo_total'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
