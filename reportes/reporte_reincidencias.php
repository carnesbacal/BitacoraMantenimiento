<?php
/**
 * ============================================================================
 * reportes/reporte_reincidencias.php - Análisis de reincidencias
 * ============================================================================
 * Identifica patrones de problemas recurrentes:
 *   - Equipos que fallan repetidamente
 *   - Áreas con incidencias recurrentes
 *   - Cadenas de reincidencias (incidencia original → reincidencias)
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/reportes_helpers.php';

$periodo = resolver_periodo();
[$sucursal_filtro, $sucursales_lista, $where_sucursal, $params_sucursal] = resolver_filtro_sucursal();

$es_exportacion = (input('exportar') === 'csv');

// ============================================================================
// EQUIPOS RECURRENTES
// ============================================================================
$equipos_recurrentes = db_all(
    "SELECT eq.id, eq.codigo_inventario, eq.nombre, eq.tipo,
            s.nombre sucursal_nombre, s.codigo sucursal_codigo,
            a.nombre area_actual,
            COUNT(i.id) total_incidencias,
            SUM(CASE WHEN i.es_reincidencia = 1 THEN 1 ELSE 0 END) reincidencias,
            MAX(i.fecha_evento) ultima_falla
     FROM equipos eq
     INNER JOIN incidencias i ON i.equipo_id = eq.id
        AND DATE(i.creado_en) BETWEEN :d AND :h $where_sucursal
     INNER JOIN sucursales s ON eq.sucursal_id = s.id
     LEFT JOIN areas a ON eq.area_id = a.id
     GROUP BY eq.id, eq.codigo_inventario, eq.nombre, eq.tipo, s.nombre, s.codigo, a.nombre
     HAVING total_incidencias >= 2
     ORDER BY total_incidencias DESC, reincidencias DESC
     LIMIT 30",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']], $params_sucursal)
);

// ============================================================================
// ÁREAS RECURRENTES (con misma categoría/subcategoría)
// ============================================================================
$areas_recurrentes = db_all(
    "SELECT a.nombre area_nombre, a.color area_color,
            c.nombre categoria_nombre, c.color categoria_color,
            COUNT(i.id) total,
            SUM(CASE WHEN i.es_reincidencia = 1 THEN 1 ELSE 0 END) reincidencias
     FROM incidencias i
     INNER JOIN areas a ON i.area_id = a.id
     LEFT JOIN categorias c ON i.categoria_id = c.id
     WHERE DATE(i.creado_en) BETWEEN :d AND :h $where_sucursal
       AND i.categoria_id IS NOT NULL
     GROUP BY a.id, a.nombre, a.color, c.id, c.nombre, c.color
     HAVING total >= 3
     ORDER BY total DESC, reincidencias DESC
     LIMIT 20",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']], $params_sucursal)
);

// ============================================================================
// CADENAS DE REINCIDENCIAS (incidencia padre con sus hijas)
// ============================================================================
$cadenas = db_all(
    "SELECT padre.id, padre.folio, padre.titulo, padre.fecha_evento padre_fecha,
            est_p.nombre padre_estado, est_p.color padre_estado_color,
            sev_p.nombre padre_severidad, sev_p.color padre_severidad_color,
            s.nombre sucursal_nombre, a.nombre area_nombre,
            COUNT(hijas.id) total_reincidencias,
            MAX(hijas.fecha_evento) ultima_reincidencia
     FROM incidencias padre
     INNER JOIN incidencias hijas ON hijas.incidencia_padre_id = padre.id
     INNER JOIN estados est_p ON padre.estado_id = est_p.id
     INNER JOIN severidades sev_p ON padre.severidad_id = sev_p.id
     INNER JOIN sucursales s ON padre.sucursal_id = s.id
     INNER JOIN areas a ON padre.area_id = a.id
     WHERE DATE(hijas.creado_en) BETWEEN :d AND :h
       " . ($sucursal_filtro ? 'AND padre.sucursal_id = :sid' : '') . "
     GROUP BY padre.id, padre.folio, padre.titulo, padre.fecha_evento,
              est_p.nombre, est_p.color, sev_p.nombre, sev_p.color,
              s.nombre, a.nombre
     HAVING total_reincidencias >= 1
     ORDER BY total_reincidencias DESC, ultima_reincidencia DESC
     LIMIT 30",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']],
        $sucursal_filtro ? ['sid' => $sucursal_filtro] : [])
);

// ============================================================================
// TOTALES GENERALES
// ============================================================================
$tot_row = db_one(
    "SELECT COUNT(*) total, SUM(CASE WHEN es_reincidencia=1 THEN 1 ELSE 0 END) reinc
     FROM incidencias i
     WHERE DATE(i.creado_en) BETWEEN :d AND :h $where_sucursal",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']], $params_sucursal)
);
$tot_general = (int) ($tot_row['total'] ?? 0);
$tot_reinc = (int) ($tot_row['reinc'] ?? 0);
$pct_reinc = $tot_general > 0 ? round(($tot_reinc / $tot_general) * 100, 1) : 0;

// ============================================================================
// EXPORTACIÓN CSV
// ============================================================================
if ($es_exportacion) {
    csv_iniciar('reincidencias_' . date('Ymd_His') . '.csv');
    csv_fila(['REPORTE DE REINCIDENCIAS']);
    csv_fila(['Período:', $periodo['etiqueta']]);
    csv_fila(['']);
    csv_fila(['Total incidencias del período:', $tot_general]);
    csv_fila(['Reincidencias del período:', $tot_reinc]);
    csv_fila(['% del total:', $pct_reinc . '%']);
    csv_fila(['']);

    csv_fila(['EQUIPOS CON MÁS INCIDENCIAS']);
    csv_fila(['Código', 'Equipo', 'Tipo', 'Sucursal', 'Total', 'Reincidencias', 'Última falla']);
    foreach ($equipos_recurrentes as $eq) {
        csv_fila([$eq['codigo_inventario'], $eq['nombre'], $eq['tipo'] ?? '',
                  $eq['sucursal_nombre'], $eq['total_incidencias'], $eq['reincidencias'], $eq['ultima_falla']]);
    }
    csv_fila(['']);

    csv_fila(['ÁREAS CON PATRÓN RECURRENTE']);
    csv_fila(['Área', 'Categoría', 'Total', 'Reincidencias']);
    foreach ($areas_recurrentes as $ar) {
        csv_fila([$ar['area_nombre'], $ar['categoria_nombre'], $ar['total'], $ar['reincidencias']]);
    }
    csv_fila(['']);

    csv_fila(['CADENAS DE REINCIDENCIAS (incidencia padre + hijas)']);
    csv_fila(['Folio padre', 'Título', 'Sucursal', 'Área', '# reincidencias', 'Última']);
    foreach ($cadenas as $c) {
        csv_fila([$c['folio'], $c['titulo'], $c['sucursal_nombre'], $c['area_nombre'],
                  $c['total_reincidencias'], $c['ultima_reincidencia']]);
    }
    exit;
}

$titulo_pagina = 'Reporte de reincidencias';
$pagina_activa = 'reportes';
require_once __DIR__ . '/../config/header.php';
?>

<style>
@media print { .no-print { display: none !important; } aside, header.h-16 { display: none !important; } body { background: white !important; } }
</style>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 no-print">
        <div class="flex items-center gap-3">
            <a href="<?= url('reportes/reportes.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900">Análisis de reincidencias</h2>
                <p class="text-xs text-zinc-500"><?= e($periodo['etiqueta']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="printer" class="w-4 h-4"></i> Imprimir
            </button>
            <a href="<?= url('reportes/reporte_reincidencias.php?' . http_build_query(array_merge($_GET, ['exportar' => 'csv']))) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="download" class="w-4 h-4"></i> CSV
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 no-print">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Período</label>
                <select name="periodo" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php $p_val = input('periodo', 'mes_actual');
                    foreach (['hoy'=>'Hoy','semana_actual'=>'Semana','mes_actual'=>'Mes actual','mes_anterior'=>'Mes anterior','trimestre'=>'90 días','año_actual'=>'Año actual'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= $p_val === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($sucursales_lista)): ?>
            <div class="ml-auto">
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Sucursal</label>
                <select name="sucursal" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todas</option>
                    <?php foreach ($sucursales_lista as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $sucursal_filtro == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-zinc-200 p-5 shadow-sm">
            <div class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-2">Total incidencias</div>
            <div class="font-display text-3xl font-extrabold text-zinc-900"><?= $tot_general ?></div>
        </div>
        <div class="bg-purple-50 rounded-xl border border-purple-200 p-5 shadow-sm">
            <div class="text-xs text-purple-700 uppercase tracking-wider font-bold mb-2">Reincidencias</div>
            <div class="font-display text-3xl font-extrabold text-purple-900"><?= $tot_reinc ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-5 shadow-sm">
            <div class="text-xs text-zinc-500 uppercase tracking-wider font-bold mb-2">% de reincidencia</div>
            <div class="font-display text-3xl font-extrabold <?= $pct_reinc >= 20 ? 'text-bacal-700' : ($pct_reinc >= 10 ? 'text-amber-600' : 'text-zinc-900') ?>"><?= $pct_reinc ?>%</div>
        </div>
    </div>

    <!-- Equipos recurrentes -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="box" class="w-4 h-4 text-bacal-700"></i> Equipos con más incidencias
        </h3>
        <p class="text-xs text-zinc-500 mb-4">Equipos con 2 o más incidencias en el período. Candidatos a mantenimiento preventivo o reemplazo.</p>
        <?php if (empty($equipos_recurrentes)): ?>
        <p class="text-xs text-zinc-400 italic text-center py-8">No se detectaron equipos con incidencias recurrentes.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Equipo</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Tipo</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Total</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Reinc.</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Última</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($equipos_recurrentes as $eq): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-2 py-2">
                            <a href="<?= url('bitacora.php?equipo=' . $eq['id']) ?>" class="block">
                                <div class="font-mono text-[10px] font-bold text-zinc-500"><?= e($eq['codigo_inventario']) ?></div>
                                <div class="font-semibold text-sm text-zinc-900 hover:text-bacal-700"><?= e($eq['nombre']) ?></div>
                            </a>
                        </td>
                        <td class="px-2 py-2 text-xs text-zinc-600"><?= e((string) $eq['tipo']) ?: '—' ?></td>
                        <td class="px-2 py-2 text-xs">
                            <span class="font-mono text-[10px] bg-zinc-100 text-zinc-600 px-1.5 py-0.5 rounded font-bold"><?= e($eq['sucursal_codigo']) ?></span>
                        </td>
                        <td class="px-2 py-2 text-right">
                            <span class="font-display text-base font-extrabold <?= (int) $eq['total_incidencias'] >= 5 ? 'text-bacal-700' : 'text-zinc-900' ?>"><?= $eq['total_incidencias'] ?></span>
                        </td>
                        <td class="px-2 py-2 text-right">
                            <?php if ((int) $eq['reincidencias'] > 0): ?>
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded-md">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i> <?= $eq['reincidencias'] ?>
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-zinc-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-2 py-2 text-xs text-zinc-600"><?= e(fmt_tiempo_relativo($eq['ultima_falla'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Áreas recurrentes -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="layers" class="w-4 h-4 text-bacal-700"></i> Combinaciones área/categoría con patrón recurrente
        </h3>
        <p class="text-xs text-zinc-500 mb-4">Combinaciones con 3+ incidencias del mismo tipo en la misma área. Útil para identificar problemas sistémicos.</p>
        <?php if (empty($areas_recurrentes)): ?>
        <p class="text-xs text-zinc-400 italic text-center py-8">Sin combinaciones recurrentes detectadas.</p>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($areas_recurrentes as $ar): ?>
            <div class="flex items-center justify-between px-3 py-2 bg-zinc-50 rounded-lg">
                <div class="flex items-center gap-2 flex-wrap">
                    <?= badge($ar['area_nombre'], $ar['area_color']) ?>
                    <i data-lucide="arrow-right" class="w-3 h-3 text-zinc-300"></i>
                    <?= badge($ar['categoria_nombre'] ?? '—', $ar['categoria_color'] ?? '#6B7280') ?>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="text-zinc-600"><strong class="text-zinc-900"><?= $ar['total'] ?></strong> incidencias</span>
                    <?php if ((int) $ar['reincidencias'] > 0): ?>
                    <span class="text-purple-700"><i data-lucide="rotate-ccw" class="w-3 h-3 inline"></i> <?= $ar['reincidencias'] ?> reinc.</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cadenas -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="git-branch" class="w-4 h-4 text-bacal-700"></i> Cadenas de reincidencias
        </h3>
        <p class="text-xs text-zinc-500 mb-4">Incidencias "padre" que han generado reincidencias en el período seleccionado.</p>
        <?php if (empty($cadenas)): ?>
        <p class="text-xs text-zinc-400 italic text-center py-8">No hay cadenas de reincidencias en este período.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($cadenas as $c): ?>
            <a href="<?= url('incidencia_ver.php?id=' . $c['id']) ?>"
               class="block bg-purple-50 border border-purple-200 rounded-lg p-3 hover:bg-purple-100 transition-colors">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-600 text-white flex items-center justify-center flex-shrink-0">
                        <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="font-mono text-[10px] font-bold text-purple-700"><?= e($c['folio']) ?></span>
                            <?= badge($c['padre_severidad'], $c['padre_severidad_color']) ?>
                            <?= badge($c['padre_estado'], $c['padre_estado_color']) ?>
                            <span class="text-[10px] text-purple-600">en <?= e($c['sucursal_nombre']) ?> · <?= e($c['area_nombre']) ?></span>
                        </div>
                        <div class="font-semibold text-sm text-zinc-900 mb-1"><?= e($c['titulo']) ?></div>
                        <div class="flex items-center gap-3 text-xs text-purple-700">
                            <span class="font-semibold flex items-center gap-1">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                <?= $c['total_reincidencias'] ?> reincidencia(s)
                            </span>
                            <span class="text-purple-600">Original: <?= e(fmt_fecha($c['padre_fecha'], false)) ?></span>
                            <span class="text-purple-600">Última: <?= e(fmt_tiempo_relativo($c['ultima_reincidencia'])) ?></span>
                        </div>
                    </div>
                    <i data-lucide="arrow-up-right" class="w-4 h-4 text-purple-400 flex-shrink-0"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
