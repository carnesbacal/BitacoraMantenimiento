<?php
/**
 * ============================================================================
 * reportes/reporte_mensual.php - Reporte mensual ejecutivo
 * ============================================================================
 * Resumen completo del período: KPIs, tendencias, distribuciones,
 * comparativas y rankings.
 * Permite exportar a CSV y modo "impresión" para PDF (via Ctrl+P del navegador).
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/reportes_helpers.php';

// Resolver filtros
$periodo = resolver_periodo();
[$sucursal_filtro, $sucursales_lista, $where_sucursal, $params_sucursal] = resolver_filtro_sucursal();

// Si es exportación, no cargar header
$es_exportacion = (input('exportar') === 'csv');

// ============================================================================
// EXPORTACIÓN CSV
// ============================================================================
if ($es_exportacion) {
    csv_iniciar('reporte_mensual_' . date('Ymd_His') . '.csv');
    csv_fila(['REPORTE EJECUTIVO MENSUAL']);
    csv_fila(['Período:', $periodo['etiqueta']]);
    csv_fila(['Generado:', date('Y-m-d H:i')]);
    csv_fila(['']);

    $m = metricas_generales($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
    csv_fila(['MÉTRICAS GENERALES']);
    csv_fila(['Total incidencias', $m['total']]);
    csv_fila(['Cerradas', $m['cerradas']]);
    csv_fila(['Abiertas', $m['abiertas']]);
    csv_fila(['Críticas', $m['criticas']]);
    csv_fila(['Reincidencias', $m['reincidencias']]);
    csv_fila(['% Reincidencia', $m['pct_reincidencia'] . '%']);
    csv_fila(['Tiempo promedio resolución (min)', $m['avg_resolucion'] ?? '—']);
    csv_fila(['SLA cumplido', $m['sla_pct'] !== null ? $m['sla_pct'] . '%' : '—']);
    csv_fila(['']);

    csv_fila(['DISTRIBUCIÓN POR CATEGORÍA']);
    csv_fila(['Categoría', 'Total']);
    foreach (distribucion_por_categoria($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal) as $c) {
        csv_fila([$c['nombre'], $c['total']]);
    }
    csv_fila(['']);

    csv_fila(['TOP 10 ÁREAS']);
    csv_fila(['Área', 'Total']);
    foreach (top_areas($periodo['desde'], $periodo['hasta'], 10, $where_sucursal, $params_sucursal) as $a) {
        csv_fila([$a['nombre'], $a['total']]);
    }
    csv_fila(['']);

    csv_fila(['TOP 10 EQUIPOS CON MÁS FALLAS']);
    csv_fila(['Código', 'Equipo', 'Sucursal', 'Fallas']);
    foreach (top_equipos($periodo['desde'], $periodo['hasta'], 10, $where_sucursal, $params_sucursal) as $eq) {
        csv_fila([$eq['codigo_inventario'], $eq['nombre'], $eq['sucursal_nombre'], $eq['total']]);
    }
    exit;
}

// ============================================================================
// DATOS PARA VISTA
// ============================================================================
$metricas      = metricas_generales($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
$tendencia     = tendencia_diaria($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
$por_categoria = distribucion_por_categoria($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
$por_severidad = distribucion_por_severidad($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
$tops_areas    = top_areas($periodo['desde'], $periodo['hasta'], 10, $where_sucursal, $params_sucursal);
$tops_equipos  = top_equipos($periodo['desde'], $periodo['hasta'], 10, $where_sucursal, $params_sucursal);
$comparativa   = !$sucursal_filtro && tiene_permiso('ver_todas_sucursales')
    ? comparativa_sucursales($periodo['desde'], $periodo['hasta'])
    : [];

$max_areas    = !empty($tops_areas) ? max(array_column($tops_areas, 'total')) : 1;
$max_equipos  = !empty($tops_equipos) ? max(array_column($tops_equipos, 'total')) : 1;

$titulo_pagina = 'Reporte mensual';
$pagina_activa = 'reportes';
require_once __DIR__ . '/../config/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* Estilos optimizados para impresión */
@media print {
    .no-print { display: none !important; }
    aside, header.h-16 { display: none !important; }
    main { overflow: visible !important; }
    body { background: white !important; }
    .print-break { page-break-before: always; }
    .bg-white { box-shadow: none !important; border: 1px solid #e4e4e7 !important; }
}
</style>

<div class="animate-fade-in space-y-5">

    <!-- Header con controles -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 no-print">
        <div class="flex items-center gap-3">
            <a href="<?= url('reportes/reportes.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900">Reporte mensual ejecutivo</h2>
                <p class="text-xs text-zinc-500"><?= e($periodo['etiqueta']) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="printer" class="w-4 h-4"></i> Imprimir / PDF
            </button>
            <a href="<?= url('reportes/reporte_mensual.php?' . http_build_query(array_merge($_GET, ['exportar' => 'csv']))) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="download" class="w-4 h-4"></i> Excel/CSV
            </a>
        </div>
    </div>

    <!-- Selector de período y sucursal -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 no-print">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Período</label>
                <select name="periodo" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php
                    $opciones_periodo = [
                        'hoy' => 'Hoy', 'semana_actual' => 'Semana actual',
                        'mes_actual' => 'Mes actual', 'mes_anterior' => 'Mes anterior',
                        'trimestre' => 'Últimos 90 días', 'año_actual' => 'Año actual',
                        'personalizado' => 'Personalizado',
                    ];
                    $p_val = input('periodo', 'mes_actual');
                    foreach ($opciones_periodo as $k => $l): ?>
                    <option value="<?= $k ?>" <?= $p_val === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($p_val === 'personalizado'): ?>
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Desde</label>
                <input type="date" name="desde" value="<?= e($periodo['desde']) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Hasta</label>
                <input type="date" name="hasta" value="<?= e($periodo['hasta']) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <button type="submit" class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Aplicar</button>
            <?php endif; ?>

            <?php if (!empty($sucursales_lista)): ?>
            <div class="ml-auto">
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Sucursal</label>
                <select name="sucursal" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todas</option>
                    <?php foreach ($sucursales_lista as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $sucursal_filtro == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Header de impresión (solo visible al imprimir) -->
    <div class="hidden print:block">
        <div class="flex items-center justify-between border-b-2 border-zinc-900 pb-3 mb-4">
            <div>
                <div class="font-display text-2xl font-extrabold">Carnes Bacal</div>
                <div class="text-xs text-zinc-500 uppercase tracking-widest">Reporte Ejecutivo de Sistemas</div>
            </div>
            <div class="text-right text-xs">
                <div class="font-semibold"><?= e($periodo['etiqueta']) ?></div>
                <div class="text-zinc-500">Generado: <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
        <?php
        $kpis = [
            ['Total', $metricas['total'], 'inbox', '#71717a'],
            ['Cerradas', $metricas['cerradas'], 'check-circle', '#16A34A'],
            ['Abiertas', $metricas['abiertas'], 'circle-dot', '#D97706'],
            ['Críticas', $metricas['criticas'], 'zap', '#DC2626'],
            ['Reincidencias', $metricas['reincidencias'], 'rotate-ccw', '#7C3AED'],
            ['% Reincidencia', $metricas['pct_reincidencia'] . '%', 'percent', '#7C3AED'],
            ['T. resolución', $metricas['avg_resolucion'] !== null ? fmt_duracion($metricas['avg_resolucion']) : '—', 'timer', '#2563EB'],
            ['SLA cumplido', $metricas['sla_pct'] !== null ? $metricas['sla_pct'] . '%' : '—', 'target',
                ($metricas['sla_pct'] ?? 0) >= 80 ? '#16A34A' : (($metricas['sla_pct'] ?? 0) >= 50 ? '#D97706' : '#DC2626')],
        ];
        foreach ($kpis as [$label, $valor, $icono, $color]):
        ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4 shadow-sm">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-2"
                 style="background-color: <?= e($color) ?>15">
                <i data-lucide="<?= e($icono) ?>" class="w-4 h-4" style="color: <?= e($color) ?>"></i>
            </div>
            <div class="font-display text-xl font-extrabold text-zinc-900 leading-none"><?= e((string) $valor) ?></div>
            <div class="text-[10px] text-zinc-500 mt-1.5 uppercase tracking-wider font-bold"><?= e($label) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Gráficas: tendencia + severidades -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Tendencia del período</h3>
            <p class="text-xs text-zinc-500 mb-4">Incidencias creadas por día (total, reincidencias, críticas)</p>
            <div class="h-64"><canvas id="chartTendencia"></canvas></div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Por severidad</h3>
            <p class="text-xs text-zinc-500 mb-4">Distribución del período</p>
            <?php if (empty($por_severidad)): ?>
            <div class="flex items-center justify-center h-48 text-xs text-zinc-400 italic">Sin datos</div>
            <?php else: ?>
            <div class="h-48"><canvas id="chartSeveridades"></canvas></div>
            <div class="mt-3 space-y-1.5">
                <?php foreach ($por_severidad as $s): ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?= e($s['color']) ?>"></span>
                        <span class="text-zinc-700"><?= e($s['nombre']) ?></span>
                    </div>
                    <span class="font-semibold text-zinc-900"><?= $s['total'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categorías + áreas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Distribución por categoría</h3>
            <p class="text-xs text-zinc-500 mb-4">Clasificación técnica del período</p>
            <?php if (empty($por_categoria)): ?>
            <div class="text-xs text-zinc-400 italic text-center py-8">Sin datos</div>
            <?php else:
                $max_cat = max(array_column($por_categoria, 'total'));
            ?>
            <div class="space-y-3">
                <?php foreach ($por_categoria as $c):
                    $pct = ((int) $c['total'] / $max_cat) * 100;
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium"
                              style="background-color: <?= e($c['color']) ?>1f; color: <?= e($c['color']) ?>; border: 1px solid <?= e($c['color']) ?>40">
                            <?= e($c['nombre']) ?>
                        </span>
                        <span class="font-bold text-sm text-zinc-900"><?= $c['total'] ?></span>
                    </div>
                    <div class="h-1.5 bg-zinc-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: <?= $pct ?>%; background-color: <?= e($c['color']) ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Top áreas con más reportes</h3>
            <p class="text-xs text-zinc-500 mb-4">Las 10 áreas con mayor volumen</p>
            <?php if (empty($tops_areas)): ?>
            <div class="text-xs text-zinc-400 italic text-center py-8">Sin datos</div>
            <?php else: ?>
            <div class="space-y-2.5">
                <?php foreach ($tops_areas as $idx => $a):
                    $pct = ((int) $a['total'] / $max_areas) * 100;
                ?>
                <div class="flex items-center gap-2">
                    <span class="w-5 text-[10px] font-bold text-zinc-400 text-right"><?= $idx + 1 ?></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-xs font-medium text-zinc-700 truncate"><?= e($a['nombre']) ?></span>
                            <span class="text-xs font-bold text-zinc-900 ml-2"><?= $a['total'] ?></span>
                        </div>
                        <div class="h-1 bg-zinc-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: <?= $pct ?>%; background-color: <?= e($a['color']) ?>"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top equipos -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Top 10 equipos con más fallas</h3>
        <p class="text-xs text-zinc-500 mb-4">Equipos que requieren atención preventiva</p>
        <?php if (empty($tops_equipos)): ?>
        <div class="text-xs text-zinc-400 italic text-center py-8">Sin datos</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200">
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase w-8">#</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Código</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Equipo</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Tipo</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Fallas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($tops_equipos as $idx => $eq): ?>
                    <tr>
                        <td class="px-2 py-2 text-[10px] font-bold text-zinc-400"><?= $idx + 1 ?></td>
                        <td class="px-2 py-2 font-mono text-xs font-bold text-zinc-700"><?= e($eq['codigo_inventario']) ?></td>
                        <td class="px-2 py-2 text-sm text-zinc-900"><?= e($eq['nombre']) ?></td>
                        <td class="px-2 py-2 text-xs text-zinc-600"><?= e((string) $eq['tipo']) ?: '—' ?></td>
                        <td class="px-2 py-2 text-xs text-zinc-600"><?= e($eq['sucursal_nombre']) ?></td>
                        <td class="px-2 py-2 text-right">
                            <span class="font-display text-base font-extrabold <?= (int) $eq['total'] >= 5 ? 'text-bacal-700' : 'text-zinc-900' ?>"><?= $eq['total'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Comparativa entre sucursales -->
    <?php if (!empty($comparativa)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 print-break">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Comparativa por sucursal</h3>
        <p class="text-xs text-zinc-500 mb-4">Métricas paralelas del período</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200">
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Total</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Abiertas</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Críticas</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Reincid.</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">T. resol.</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">SLA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($comparativa as $s):
                        $sla_eval = (int) $s['sla_cumplido'] + (int) $s['sla_incumplido'];
                        $sla_pct = $sla_eval > 0 ? round(((int) $s['sla_cumplido'] / $sla_eval) * 100) : null;
                    ?>
                    <tr>
                        <td class="px-2 py-2">
                            <span class="font-mono text-[10px] bg-zinc-100 text-zinc-600 px-1.5 py-0.5 rounded font-bold"><?= e($s['codigo']) ?></span>
                            <span class="font-semibold text-zinc-900 ml-1"><?= e($s['nombre']) ?></span>
                        </td>
                        <td class="px-2 py-2 text-right font-bold text-zinc-900"><?= $s['total'] ?></td>
                        <td class="px-2 py-2 text-right text-zinc-600"><?= $s['abiertas'] ?></td>
                        <td class="px-2 py-2 text-right <?= (int) $s['criticas'] > 0 ? 'text-bacal-700 font-bold' : 'text-zinc-400' ?>"><?= $s['criticas'] ?></td>
                        <td class="px-2 py-2 text-right text-purple-700 font-medium"><?= $s['reincidencias'] ?></td>
                        <td class="px-2 py-2 text-right text-zinc-600 text-xs"><?= $s['avg_resolucion'] !== null ? fmt_duracion((int) $s['avg_resolucion']) : '—' ?></td>
                        <td class="px-2 py-2 text-right text-xs font-semibold <?= ($sla_pct ?? 0) >= 80 ? 'text-emerald-700' : (($sla_pct ?? 0) >= 50 ? 'text-amber-700' : ($sla_pct !== null ? 'text-bacal-700' : 'text-zinc-400')) ?>">
                            <?= $sla_pct !== null ? $sla_pct . '%' : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const tendenciaData = <?= json_encode($tendencia, JSON_UNESCAPED_UNICODE) ?>;
const severidadesData = <?= json_encode($por_severidad, JSON_UNESCAPED_UNICODE) ?>;

const ctxTend = document.getElementById('chartTendencia');
if (ctxTend) {
    new Chart(ctxTend, {
        type: 'line',
        data: {
            labels: tendenciaData.map(d => d.label),
            datasets: [
                { label: 'Total', data: tendenciaData.map(d => d.total),
                  borderColor: '#C8102E', backgroundColor: 'rgba(200,16,46,0.08)',
                  borderWidth: 2.5, tension: 0.35, fill: true, pointRadius: 0, pointHoverRadius: 5 },
                { label: 'Reincidencias', data: tendenciaData.map(d => d.reincidencias),
                  borderColor: '#A855F7', borderWidth: 2, borderDash: [4,4], tension: 0.35, fill: false, pointRadius: 0 },
                { label: 'Críticas', data: tendenciaData.map(d => d.criticas),
                  borderColor: '#EA580C', borderWidth: 2, tension: 0.35, fill: false, pointRadius: 0 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                       tooltip: { backgroundColor: '#18181b', padding: 10, cornerRadius: 8 } },
            scales: { x: { grid: { display: false }, ticks: { color: '#a1a1aa', font: { size: 10 } } },
                      y: { beginAtZero: true, grid: { color: '#f4f4f5' }, ticks: { color: '#a1a1aa', font: { size: 10 }, precision: 0 } } }
        }
    });
}

const ctxSev = document.getElementById('chartSeveridades');
if (ctxSev && severidadesData.length > 0) {
    new Chart(ctxSev, {
        type: 'doughnut',
        data: {
            labels: severidadesData.map(s => s.nombre),
            datasets: [{ data: severidadesData.map(s => s.total),
                         backgroundColor: severidadesData.map(s => s.color),
                         borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { display: false },
                       tooltip: { backgroundColor: '#18181b', padding: 10, cornerRadius: 8 } }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
