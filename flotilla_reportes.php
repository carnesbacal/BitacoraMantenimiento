<?php
/**
 * ============================================================================
 * flotilla_reportes.php - Reportes y análisis de la flotilla vehicular
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();

// ── Filtros de período ──────────────────────────────────────────────────────
$hoy       = date('Y-m-d');
$default_desde = date('Y-01-01');
$default_hasta = date('Y-12-31');

$desde  = trim((string) input('desde', $default_desde));
$hasta  = trim((string) input('hasta', $hoy));
$f_suc  = (int) input('sucursal_id', 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = $default_desde;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta))  $hasta = $hoy;
if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_suc = (int) $u['sucursal_id'];
}

$suc_filter_gastos = $f_suc ? "AND v.sucursal_id = {$f_suc}" : '';

// Período legible
$label_periodo = fmt_fecha($desde) . ' – ' . fmt_fecha($hasta);
$dias_periodo  = (int)(new DateTime($hasta))->diff(new DateTime($desde))->days + 1;

// ── Accesos rápidos de período ──────────────────────────────────────────────
$periodos_rapidos = [
    'Este mes'     => [date('Y-m-01'), date('Y-m-d')],
    'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')),
                       date('Y-m-t',  strtotime('last day of last month'))],
    'Este año'     => [date('Y-01-01'), date('Y-12-31')],
    'Últimos 90d'  => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')],
];

// ============================================================================
// Consultas
// ============================================================================

// 1. KPIs globales
$kpis = db_one(
    "SELECT
        COALESCE(SUM(g.monto),0)                                                          gasto_total,
        COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto END),0)       gasto_combustible,
        COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto END),0) gasto_mant,
        COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Multa%' THEN g.monto END),0)             gasto_multas,
        COALESCE(SUM(CASE WHEN cat.nombre NOT LIKE '%Combustible%' AND cat.nombre NOT LIKE '%Mantenimiento%'
                           AND cat.nombre NOT LIKE '%Refacc%' AND cat.nombre NOT LIKE '%Multa%' THEN g.monto END),0) gasto_otros,
        COUNT(DISTINCT g.vehiculo_id)                                                     vehiculos_con_gasto,
        COUNT(g.id)                                                                       total_registros
     FROM flotilla_gastos g
     INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
     INNER JOIN flotilla_vehiculos v          ON g.vehiculo_id   = v.id
     WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos",
    ['desde' => $desde, 'hasta' => $hasta]
) ?? [];

// 2. KPIs combustible
$kpi_comb = db_one(
    "SELECT
        COUNT(*)                           total_cargas,
        COALESCE(SUM(c.litros),0)          total_litros,
        COALESCE(SUM(c.litros*c.precio_litro),0) total_costo,
        COALESCE(AVG(NULLIF(c.rendimiento_kml,0)),0) avg_kml,
        COALESCE(SUM(c.km_recorridos),0)   km_recorridos
     FROM flotilla_combustible c
     INNER JOIN flotilla_vehiculos v ON c.vehiculo_id = v.id
     WHERE DATE(c.fecha) BETWEEN :desde AND :hasta $suc_filter_gastos",
    ['desde' => $desde, 'hasta' => $hasta]
) ?? [];

// 3. KPIs mantenimiento
$kpi_mant = db_one(
    "SELECT
        COUNT(*)                         total_servicios,
        COALESCE(SUM(h.costo),0)         total_costo,
        COUNT(DISTINCT h.vehiculo_id)    vehiculos_atendidos
     FROM flotilla_mant_historial h
     INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
     WHERE h.fecha BETWEEN :desde AND :hasta $suc_filter_gastos",
    ['desde' => $desde, 'hasta' => $hasta]
) ?? [];

// 4. Gasto por categoría
$por_categoria = db_all(
    "SELECT cat.nombre, cat.color,
            COALESCE(SUM(g.monto),0) total,
            COUNT(*) registros
     FROM flotilla_gastos g
     INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
     INNER JOIN flotilla_vehiculos v          ON g.vehiculo_id = v.id
     WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos
     GROUP BY cat.id
     ORDER BY total DESC",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 5. Gasto por vehículo (top 15)
$por_vehiculo = db_all(
    "SELECT v.id, v.placas, v.alias, v.marca, v.modelo, v.km_actual,
            COALESCE(SUM(g.monto),0) gasto_total,
            COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto END),0) comb,
            COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto END),0) mant,
            COUNT(DISTINCT g.id) num_registros
     FROM flotilla_vehiculos v
     LEFT JOIN flotilla_gastos g               ON g.vehiculo_id = v.id AND g.fecha BETWEEN :desde AND :hasta
     LEFT JOIN flotilla_categorias_gasto cat   ON g.categoria_id = cat.id
     WHERE v.activo = 1 $suc_filter_gastos
     GROUP BY v.id
     HAVING gasto_total > 0
     ORDER BY gasto_total DESC
     LIMIT 15",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 6. Rendimiento combustible por vehículo
$rendimiento = db_all(
    "SELECT v.id, v.placas, v.alias, v.marca, v.modelo,
            COUNT(c.id) cargas,
            ROUND(AVG(NULLIF(c.rendimiento_kml,0)),2) rend_prom,
            ROUND(SUM(c.litros),1) total_litros,
            COALESCE(SUM(c.litros * c.precio_litro),0) costo_comb,
            COALESCE(SUM(c.km_recorridos),0) km_recorridos
     FROM flotilla_vehiculos v
     INNER JOIN flotilla_combustible c ON c.vehiculo_id = v.id
     WHERE DATE(c.fecha) BETWEEN :desde AND :hasta
       AND c.es_tanque_lleno = 1 $suc_filter_gastos
     GROUP BY v.id
     HAVING cargas >= 1
     ORDER BY rend_prom DESC",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 7. Mantenimientos del período
$mantenimientos = db_all(
    "SELECT h.nombre, COUNT(*) veces, COALESCE(SUM(h.costo),0) costo_total
     FROM flotilla_mant_historial h
     INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
     WHERE h.fecha BETWEEN :desde AND :hasta $suc_filter_gastos
     GROUP BY h.nombre
     ORDER BY costo_total DESC
     LIMIT 10",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 7b. Proveedores de flotilla más caros (gasto de mantenimiento por proveedor)
$prov_flota = flotilla_gasto_proveedores($desde, $hasta, $suc_filter_gastos, 15);

// 8. Tendencia mensual (mes a mes entre desde y hasta)
$tendencia = db_all(
    "SELECT DATE_FORMAT(g.fecha,'%Y-%m') periodo,
            SUM(g.monto) total,
            SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto ELSE 0 END) comb,
            SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto ELSE 0 END) mant
     FROM flotilla_gastos g
     INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
     INNER JOIN flotilla_vehiculos v          ON g.vehiculo_id = v.id
     WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos
     GROUP BY DATE_FORMAT(g.fecha,'%Y-%m')
     ORDER BY periodo",
    ['desde' => $desde, 'hasta' => $hasta]
);
$max_tend = !empty($tendencia) ? max(array_column($tendencia, 'total')) : 1;

// 9. Alertas activas
$docs_vencidos   = (int)(db_one("SELECT COUNT(*) c FROM flotilla_documentos WHERE estado='vencido'")['c'] ?? 0);
$docs_por_vencer = (int)(db_one("SELECT COUNT(*) c FROM flotilla_documentos WHERE estado='por_vencer'")['c'] ?? 0);
$multas_pend     = db_one("SELECT COUNT(*) c, COALESCE(SUM(monto_original),0) monto FROM flotilla_multas WHERE estado IN('pendiente','impugnada')") ?? [];
$siniestros_act  = (int)(db_one("SELECT COUNT(*) c FROM flotilla_siniestros WHERE estado IN('reportado','en_proceso')")['c'] ?? 0);
$mant_vencidos   = (int)(db_one("SELECT COUNT(*) c FROM flotilla_mant_historial WHERE proxima_fecha IS NOT NULL AND proxima_fecha < CURDATE()")['c'] ?? 0);

$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")
    : [];

$titulo_pagina = 'Flotilla · Reportes';
$pagina_activa = 'flotilla_reportes';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';

$max_vehiculo = !empty($por_vehiculo) ? max(array_column($por_vehiculo, 'gasto_total')) : 1;
$total_cat    = array_sum(array_column($por_categoria, 'total'));
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="w-6 h-6 text-bacal-700"></i>
            Reportes de flotilla
        </h2>
        <a href="<?= url("flotilla_reportes_export.php?desde={$desde}&hasta={$hasta}" . ($f_suc ? "&sucursal_id={$f_suc}" : '')) ?>"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-sm font-semibold transition-colors">
            <i data-lucide="download" class="w-4 h-4"></i>
            Exportar Excel
        </a>
    </div>

    <!-- Flash -->
    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium
        <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>

    <!-- Filtros de período -->
    <div class="bg-white rounded-xl border border-zinc-200 p-4 space-y-3">
        <!-- Accesos rápidos -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($periodos_rapidos as $label => [$pd, $ph]): ?>
            <a href="<?= url("flotilla_reportes.php?desde={$pd}&hasta={$ph}" . ($f_suc ? "&sucursal_id={$f_suc}" : '')) ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors
                      <?= ($desde === $pd && $hasta === $ph) ? 'bg-bacal-700 text-white border-bacal-700' : 'border-zinc-300 text-zinc-600 hover:bg-zinc-50' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <!-- Rango personalizado -->
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs font-bold text-zinc-500 mb-1">Desde</label>
                <input type="date" name="desde" value="<?= e($desde) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 mb-1">Hasta</label>
                <input type="date" name="hasta" value="<?= e($hasta) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <?php if (tiene_permiso('ver_todas_sucursales') && $sucursales): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-500 mb-1">Sucursal</label>
                <select name="sucursal_id" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
                    <option value="">Todas</option>
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_suc === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
                Aplicar
            </button>
            <p class="self-end text-xs text-zinc-400 pb-2">
                <i data-lucide="calendar-range" class="w-3.5 h-3.5 inline"></i>
                <?= $label_periodo ?> · <?= $dias_periodo ?> días
            </p>
        </form>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <?php
        $gt = (float)($kpis['gasto_total'] ?? 0);
        $gc = (float)($kpis['gasto_combustible'] ?? 0);
        $gm = (float)($kpis['gasto_mant'] ?? 0);
        $gmu = (float)($kpis['gasto_multas'] ?? 0);
        $kpis_main = [
            ['Gasto total',     '$' . number_format($gt, 2),  'banknote',       'zinc',    false,  ($kpis['vehiculos_con_gasto'] ?? 0) . ' vehículos'],
            ['Combustible',     '$' . number_format($gc, 2),  'fuel',           'amber',   false,  $gt > 0 ? round($gc/$gt*100) . '% del total' : '—'],
            ['Mantenimiento',   '$' . number_format($gm, 2),  'wrench',         'blue',    false,  $gt > 0 ? round($gm/$gt*100) . '% del total' : '—'],
            ['Multas',          '$' . number_format($gmu, 2), 'ticket-x',       'red',     $gmu > 0, $gt > 0 && $gmu > 0 ? round($gmu/$gt*100) . '% del total' : '—'],
        ];
        foreach ($kpis_main as [$label, $val, $icon, $color, $alert, $sub]):
        ?>
        <div class="bg-white rounded-xl border <?= $alert ? "border-{$color}-200 bg-{$color}-50" : 'border-zinc-200' ?> p-4">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="<?= $icon ?>" class="w-4 h-4 text-<?= $color ?>-500 shrink-0"></i>
                <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide"><?= $label ?></span>
            </div>
            <div class="font-display text-xl font-extrabold <?= $alert ? "text-{$color}-700" : 'text-zinc-900' ?>"><?= $val ?></div>
            <div class="text-xs text-zinc-400 mt-1"><?= $sub ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- KPIs secundarios: combustible + mantenimiento -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $litros  = (float)($kpi_comb['total_litros'] ?? 0);
        $kml     = (float)($kpi_comb['avg_kml'] ?? 0);
        $kms_rec = (int)($kpi_comb['km_recorridos'] ?? 0);
        $costo_km = ($kms_rec > 0 && $gc > 0) ? $gc / $kms_rec : 0;
        $kpis2 = [
            ['Litros cargados',  number_format($litros, 1) . ' L',            'droplets',      'sky'],
            ['Rend. promedio',   number_format($kml, 2) . ' km/L',            'gauge',         'emerald'],
            ['Servicios mant.',  (int)($kpi_mant['total_servicios'] ?? 0),    'clipboard-list','violet'],
            ['Costo por km',     $costo_km > 0 ? '$' . number_format($costo_km, 3) : '—', 'route', 'orange'],
        ];
        foreach ($kpis2 as [$label, $val, $icon, $color]):
        ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="<?= $icon ?>" class="w-4 h-4 text-<?= $color ?>-500 shrink-0"></i>
                <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide"><?= $label ?></span>
            </div>
            <div class="font-display text-xl font-extrabold text-zinc-900"><?= $val ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Alertas activas -->
    <?php $hay_alertas = ($docs_vencidos + $docs_por_vencer + (int)($multas_pend['c'] ?? 0) + $siniestros_act + $mant_vencidos) > 0; ?>
    <?php if ($hay_alertas): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-3 font-semibold text-amber-800 text-sm">
            <i data-lucide="bell-ring" class="w-4 h-4"></i> Alertas activas
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <?php if ($docs_vencidos > 0): ?>
            <a href="<?= url('flotilla_documentos.php?estado=vencido') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-100 text-red-800 font-medium hover:bg-red-200">
                <i data-lucide="file-x" class="w-3.5 h-3.5"></i> <?= $docs_vencidos ?> doc<?= $docs_vencidos !== 1 ? 's' : '' ?> vencido<?= $docs_vencidos !== 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
            <?php if ($docs_por_vencer > 0): ?>
            <a href="<?= url('flotilla_documentos.php?estado=por_vencer') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 font-medium hover:bg-amber-200">
                <i data-lucide="file-clock" class="w-3.5 h-3.5"></i> <?= $docs_por_vencer ?> por vencer
            </a>
            <?php endif; ?>
            <?php if (($multas_pend['c'] ?? 0) > 0): ?>
            <a href="<?= url('flotilla_multas.php') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-100 text-orange-800 font-medium hover:bg-orange-200">
                <i data-lucide="ticket-x" class="w-3.5 h-3.5"></i>
                <?= $multas_pend['c'] ?> multas ($<?= number_format((float)($multas_pend['monto'] ?? 0), 2) ?>)
            </a>
            <?php endif; ?>
            <?php if ($siniestros_act > 0): ?>
            <a href="<?= url('flotilla_siniestros.php') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-100 text-red-800 font-medium hover:bg-red-200">
                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i> <?= $siniestros_act ?> siniestro<?= $siniestros_act !== 1 ? 's' : '' ?> activo<?= $siniestros_act !== 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
            <?php if ($mant_vencidos > 0): ?>
            <a href="<?= url('flotilla_mantenimiento.php?vista=pendientes') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-100 text-purple-800 font-medium hover:bg-purple-200">
                <i data-lucide="wrench" class="w-3.5 h-3.5"></i> <?= $mant_vencidos ?> mant. vencido<?= $mant_vencidos !== 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tendencia + Categorías -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Tendencia mensual -->
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-4 flex items-center gap-2">
                <i data-lucide="trending-up" class="w-4 h-4 text-bacal-700"></i>
                Gasto por mes
            </h3>
            <?php if (empty($tendencia)): ?>
            <p class="text-sm text-zinc-400 text-center py-10">Sin gastos en el período</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($tendencia as $t):
                    $pct = $max_tend > 0 ? ($t['total'] / $max_tend) * 100 : 0;
                    $pct_c = $t['total'] > 0 ? ($t['comb'] / $t['total']) * 100 : 0;
                    $pct_m = $t['total'] > 0 ? ($t['mant'] / $t['total']) * 100 : 0;
                    [$y, $m] = explode('-', $t['periodo']);
                    $meses_es = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
                                 '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                    $label_mes = ($meses_es[$m] ?? $m) . ' ' . $y;
                ?>
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-zinc-700 w-16"><?= $label_mes ?></span>
                        <div class="flex-1 mx-3">
                            <div class="w-full bg-zinc-100 rounded-full h-3 relative overflow-hidden">
                                <div class="h-3 rounded-full bg-amber-400 absolute left-0 top-0"
                                     style="width:<?= $pct_c ?>%"></div>
                                <div class="h-3 rounded-full bg-blue-400 absolute top-0"
                                     style="left:<?= $pct_c ?>%;width:<?= $pct_m ?>%"></div>
                                <div class="h-3 rounded-full bg-zinc-300 absolute top-0"
                                     style="left:<?= $pct_c + $pct_m ?>%;width:<?= max(0, $pct - $pct_c - $pct_m) ?>%"></div>
                            </div>
                        </div>
                        <span class="font-bold text-zinc-900 w-20 text-right">$<?= number_format($t['total']/1000, 1) ?>k</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-3 mt-3 pt-3 border-t border-zinc-100 text-xs text-zinc-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 inline-block"></span> Combustible</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400 inline-block"></span> Mantenimiento</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-zinc-300 inline-block"></span> Otros</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Categorías -->
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-4 flex items-center gap-2">
                <i data-lucide="pie-chart" class="w-4 h-4 text-bacal-700"></i>
                Gasto por categoría
            </h3>
            <?php if (empty($por_categoria)): ?>
            <p class="text-sm text-zinc-400 text-center py-10">Sin gastos en el período</p>
            <?php else: ?>
            <div class="space-y-2.5">
                <?php foreach ($por_categoria as $cat):
                    $pct = $total_cat > 0 ? ($cat['total'] / $total_cat) * 100 : 0;
                    $col = $cat['color'] ?: '#6b7280';
                ?>
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="flex items-center gap-1.5 font-medium text-zinc-700">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background:<?= htmlspecialchars($col) ?>"></span>
                            <?= e($cat['nombre']) ?>
                        </span>
                        <span class="text-zinc-500">$<?= number_format($cat['total'], 2) ?> · <?= round($pct) ?>%</span>
                    </div>
                    <div class="w-full bg-zinc-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all"
                             style="width:<?= $pct ?>%;background:<?= htmlspecialchars($col) ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gasto por vehículo -->
    <?php if (!empty($por_vehiculo)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 p-5">
        <h3 class="font-semibold text-sm text-zinc-800 mb-4 flex items-center gap-2">
            <i data-lucide="car" class="w-4 h-4 text-bacal-700"></i>
            Gasto por vehículo
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-zinc-500 uppercase tracking-wide border-b border-zinc-100">
                        <th class="text-left pb-2 font-semibold pr-4">Vehículo</th>
                        <th class="text-right pb-2 font-semibold">Total</th>
                        <th class="text-right pb-2 font-semibold hidden md:table-cell">Combustible</th>
                        <th class="text-right pb-2 font-semibold hidden md:table-cell">Mantenimiento</th>
                        <th class="pb-2 w-36 hidden lg:table-cell"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($por_vehiculo as $vg):
                    $pct_v = $max_vehiculo > 0 ? ($vg['gasto_total'] / $max_vehiculo) * 100 : 0;
                ?>
                <tr>
                    <td class="py-2.5 pr-4">
                        <a href="<?= url("flotilla_vehiculo_ver.php?id={$vg['id']}") ?>" class="hover:underline">
                            <div class="font-semibold text-zinc-900">
                                <?= e($vg['alias'] ?: "{$vg['marca']} {$vg['modelo']}") ?>
                            </div>
                            <div class="text-xs font-mono text-zinc-400"><?= e($vg['placas']) ?></div>
                        </a>
                    </td>
                    <td class="py-2.5 text-right font-bold text-zinc-900">$<?= number_format($vg['gasto_total'], 2) ?></td>
                    <td class="py-2.5 text-right text-amber-700 hidden md:table-cell">$<?= number_format($vg['comb'], 2) ?></td>
                    <td class="py-2.5 text-right text-blue-700 hidden md:table-cell">$<?= number_format($vg['mant'], 2) ?></td>
                    <td class="py-2.5 hidden lg:table-cell pl-4">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-zinc-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-bacal-700" style="width:<?= $pct_v ?>%"></div>
                            </div>
                            <span class="text-xs text-zinc-400 w-7 text-right"><?= round($pct_v) ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Rendimiento combustible + Top mantenimientos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Rendimiento km/L -->
        <?php if (!empty($rendimiento)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-4 flex items-center gap-2">
                <i data-lucide="fuel" class="w-4 h-4 text-bacal-700"></i>
                Rendimiento combustible (km/L)
            </h3>
            <?php $max_rend = max(array_column($rendimiento, 'rend_prom') ?: [1]); ?>
            <div class="space-y-3">
            <?php foreach ($rendimiento as $rv):
                $r     = (float)$rv['rend_prom'];
                $pct_r = $max_rend > 0 ? ($r / $max_rend) * 100 : 0;
                $color_rend = $r >= 12 ? '#059669' : ($r >= 8 ? '#d97706' : '#dc2626');
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <a href="<?= url("flotilla_vehiculo_ver.php?id={$rv['id']}") ?>"
                       class="font-medium text-zinc-700 hover:underline truncate max-w-[180px]">
                        <?= e($rv['alias'] ?: "{$rv['marca']} {$rv['modelo']}") ?>
                        <span class="font-mono text-zinc-400 ml-1"><?= e($rv['placas']) ?></span>
                    </a>
                    <span class="font-bold ml-2 shrink-0" style="color:<?= $color_rend ?>">
                        <?= number_format($r, 2) ?> km/L
                    </span>
                </div>
                <div class="w-full bg-zinc-100 rounded-full h-2">
                    <div class="h-2 rounded-full" style="width:<?= $pct_r ?>%;background:<?= $color_rend ?>"></div>
                </div>
                <div class="text-[10px] text-zinc-400 mt-0.5">
                    <?= $rv['cargas'] ?> cargas · <?= number_format($rv['total_litros'], 1) ?> L ·
                    <?= number_format($rv['km_recorridos']) ?> km recorridos
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <div class="flex gap-3 mt-3 pt-3 border-t border-zinc-100 text-xs text-zinc-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> ≥12 km/L</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-500 inline-block"></span> 8–12</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-500 inline-block"></span> &lt;8</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top mantenimientos -->
        <?php if (!empty($mantenimientos)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-4 flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4 text-bacal-700"></i>
                Servicios de mantenimiento (top 10)
            </h3>
            <?php $max_mant = max(array_column($mantenimientos, 'costo_total') ?: [1]); ?>
            <div class="space-y-2.5">
            <?php foreach ($mantenimientos as $mt):
                $pct_mt = $max_mant > 0 ? ($mt['costo_total'] / $max_mant) * 100 : 0;
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-medium text-zinc-700 truncate max-w-[200px]"><?= e($mt['nombre']) ?></span>
                    <span class="text-zinc-500 ml-2 shrink-0">
                        <?= $mt['veces'] ?>× · $<?= number_format($mt['costo_total'], 2) ?>
                    </span>
                </div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-blue-500" style="width:<?= $pct_mt ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Proveedores de flotilla más caros -->
    <?php if (!empty($prov_flota)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="truck" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Proveedores de flotilla más caros</h3>
            <span class="text-xs text-zinc-500">(<?= count($prov_flota) ?>)</span>
            <span class="ml-auto text-[11px] text-zinc-400">Mantenimiento de vehículos en el período</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Proveedor / Taller</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Servicios</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Vehículos</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Promedio</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($prov_flota as $idx => $pf):
                        $reg = (int) $pf['registros'];
                        $prom = $reg > 0 ? (float) $pf['total'] / $reg : 0;
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 text-zinc-400 font-mono text-xs"><?= $idx + 1 ?></td>
                        <td class="px-4 py-2.5 font-semibold text-sm text-zinc-900"><?= e($pf['proveedor']) ?></td>
                        <td class="px-4 py-2.5 text-center text-sm text-zinc-700"><?= $reg ?></td>
                        <td class="px-4 py-2.5 text-center text-sm text-zinc-700"><?= (int) $pf['vehiculos'] ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600">$<?= number_format($prom, 2) ?></td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-bacal-700">$<?= number_format((float) $pf['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
