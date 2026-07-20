<?php
/**
 * ============================================================================
 * reportes/reporte_sla.php - Análisis de SLA
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/reportes_helpers.php';
require_once __DIR__ . '/../config/reportes_export.php';

$periodo = resolver_periodo();
[$sucursal_filtro, $sucursales_lista, $where_sucursal, $params_sucursal] = resolver_filtro_sucursal();

$es_exportacion = (input('exportar') === 'csv');
$es_xlsx        = (input('exportar') === 'xlsx');

// SLA por severidad
$por_severidad = db_all(
    "SELECT sev.id, sev.nombre, sev.color, sev.nivel, sev.sla_horas,
            COUNT(i.id) total_cerradas,
            SUM(CASE WHEN i.sla_cumplido = 1 THEN 1 ELSE 0 END) cumplidos,
            SUM(CASE WHEN i.sla_cumplido = 0 THEN 1 ELSE 0 END) incumplidos,
            AVG(i.tiempo_resolucion_min) avg_resolucion
     FROM severidades sev
     LEFT JOIN incidencias i ON i.severidad_id = sev.id
        AND DATE(i.creado_en) BETWEEN :d AND :h $where_sucursal
        AND i.sla_cumplido IS NOT NULL
     WHERE sev.activo = 1
     GROUP BY sev.id, sev.nombre, sev.color, sev.nivel, sev.sla_horas
     ORDER BY sev.nivel ASC",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']], $params_sucursal)
);

foreach ($por_severidad as &$s) {
    $tot = (int) $s['cumplidos'] + (int) $s['incumplidos'];
    $s['pct_cumplido'] = $tot > 0 ? round(((int) $s['cumplidos'] / $tot) * 100) : null;
}
unset($s);

// SLA actualmente en riesgo (en tiempo real, no histórico)
$sla_riesgo_actual = db_all(
    "SELECT i.id, i.folio, i.titulo, i.fecha_evento, i.fecha_limite_sla,
            sev.nombre severidad_nombre, sev.color severidad_color,
            est.nombre estado_nombre, est.color estado_color,
            s.nombre sucursal_nombre, a.nombre area_nombre,
            asig.nombre_completo asignado_a_nombre,
            TIMESTAMPDIFF(MINUTE, NOW(), i.fecha_limite_sla) minutos_restantes
     FROM incidencias i
     INNER JOIN estados est ON i.estado_id = est.id
     INNER JOIN severidades sev ON i.severidad_id = sev.id
     INNER JOIN sucursales s ON i.sucursal_id = s.id
     INNER JOIN areas a ON i.area_id = a.id
     LEFT JOIN usuarios asig ON i.asignado_a_id = asig.id
     WHERE est.es_final = 0
       AND i.fecha_limite_sla IS NOT NULL
       " . ($sucursal_filtro ? 'AND i.sucursal_id = :sid' : '') . "
     ORDER BY i.fecha_limite_sla ASC
     LIMIT 30",
    $sucursal_filtro ? ['sid' => $sucursal_filtro] : []
);

// Incidencias con SLA incumplido (cerradas tarde)
$sla_incumplidas = db_all(
    "SELECT i.id, i.folio, i.titulo, i.fecha_evento, i.fecha_resolucion, i.fecha_limite_sla,
            i.tiempo_resolucion_min,
            sev.nombre severidad_nombre, sev.color severidad_color, sev.sla_horas,
            s.nombre sucursal_nombre, a.nombre area_nombre,
            asig.nombre_completo asignado_a_nombre,
            TIMESTAMPDIFF(MINUTE, i.fecha_limite_sla, i.fecha_resolucion) minutos_excedidos
     FROM incidencias i
     INNER JOIN severidades sev ON i.severidad_id = sev.id
     INNER JOIN sucursales s ON i.sucursal_id = s.id
     INNER JOIN areas a ON i.area_id = a.id
     LEFT JOIN usuarios asig ON i.asignado_a_id = asig.id
     WHERE i.sla_cumplido = 0
       AND DATE(i.creado_en) BETWEEN :d AND :h $where_sucursal
     ORDER BY minutos_excedidos DESC
     LIMIT 30",
    array_merge(['d' => $periodo['desde'], 'h' => $periodo['hasta']], $params_sucursal)
);

// Resumen global
$tot_eval = 0;
$tot_cumpl = 0;
foreach ($por_severidad as $s) {
    $tot_eval += (int) $s['cumplidos'] + (int) $s['incumplidos'];
    $tot_cumpl += (int) $s['cumplidos'];
}
$pct_global = $tot_eval > 0 ? round(($tot_cumpl / $tot_eval) * 100) : null;

$suc_label = 'Todas las sucursales';
if ($sucursal_filtro) {
    foreach ($sucursales_lista as $sl) { if ((int) $sl['id'] === (int) $sucursal_filtro) { $suc_label = $sl['nombre']; break; } }
    if ($suc_label === 'Todas las sucursales') { $sr = db_one("SELECT nombre FROM sucursales WHERE id = :id", ['id' => $sucursal_filtro]); if ($sr) $suc_label = $sr['nombre']; }
}
$rep_user     = usuario_actual()['nombre'] ?? '';
$pdf_filename = 'reporte_sla_' . date('Ymd_His') . '.pdf';

if ($es_exportacion) {
    csv_iniciar('reporte_sla_' . date('Ymd_His') . '.csv');
    csv_fila(['ANÁLISIS DE SLA']);
    csv_fila(['Período:', $periodo['etiqueta']]);
    csv_fila(['Cumplimiento global:', ($pct_global !== null ? $pct_global . '%' : '—')]);
    csv_fila(['']);
    csv_fila(['DESGLOSE POR SEVERIDAD']);
    csv_fila(['Severidad', 'SLA (horas)', 'Total cerradas', 'Cumplidos', 'Incumplidos', '% Cumplido', 'T. resolución prom.']);
    foreach ($por_severidad as $s) {
        csv_fila([$s['nombre'], $s['sla_horas'], $s['total_cerradas'],
                  $s['cumplidos'], $s['incumplidos'],
                  $s['pct_cumplido'] !== null ? $s['pct_cumplido'] . '%' : '—',
                  $s['avg_resolucion'] !== null ? fmt_duracion((int) $s['avg_resolucion']) : '—']);
    }
    csv_fila(['']);
    csv_fila(['INCIDENCIAS CON SLA INCUMPLIDO']);
    csv_fila(['Folio', 'Título', 'Severidad', 'Sucursal', 'Área', 'Excedido (min)', 'Asignado']);
    foreach ($sla_incumplidas as $i) {
        csv_fila([$i['folio'], $i['titulo'], $i['severidad_nombre'],
                  $i['sucursal_nombre'], $i['area_nombre'],
                  $i['minutos_excedidos'], $i['asignado_a_nombre'] ?? '']);
    }
    exit;
}

if ($es_xlsx) {
    require_once __DIR__ . '/../config/xlsx_writer.php';
    $xlsx = new XlsxWriter();
    $xlsx->addSheet('Resumen SLA');
    $xlsx->addHeaderRow(['ANÁLISIS DE SLA'], true);
    $xlsx->addRow(['Período: ' . $periodo['etiqueta']]);
    $xlsx->addRow(['Sucursal: ' . $suc_label]);
    $xlsx->addRow(['Cumplimiento global: ' . ($pct_global !== null ? $pct_global . '%' : 'n/d')]);
    $xlsx->addRow(['Incidencias evaluadas: ' . $tot_eval . ' (cumplidas ' . $tot_cumpl . ')']);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Severidad', 'SLA (h)', 'Cerradas', 'Cumplidos', 'Incumplidos', '% Cumplido', 'T. resol. prom (min)'], true);
    foreach ($por_severidad as $sv) {
        $xlsx->addRow([$sv['nombre'], (int) $sv['sla_horas'], (int) $sv['total_cerradas'], (int) $sv['cumplidos'],
            (int) $sv['incumplidos'], $sv['pct_cumplido'] !== null ? $sv['pct_cumplido'] . '%' : '',
            $sv['avg_resolucion'] !== null ? round((float) $sv['avg_resolucion']) : '']);
    }

    $xlsx->addSheet('Incumplidas');
    $xlsx->addHeaderRow(['INCIDENCIAS CON SLA INCUMPLIDO'], true);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Folio', 'Título', 'Severidad', 'Sucursal', 'Área', 'Asignado', 'Excedido (min)'], true);
    foreach ($sla_incumplidas as $i) {
        $xlsx->addRow([$i['folio'], $i['titulo'], $i['severidad_nombre'], $i['sucursal_nombre'], $i['area_nombre'],
            $i['asignado_a_nombre'] ?? '', (int) $i['minutos_excedidos']]);
    }

    if (!empty($sla_riesgo_actual)) {
        $xlsx->addSheet('En riesgo (ahora)');
        $xlsx->addHeaderRow(['INCIDENCIAS ABIERTAS - ESTADO SLA EN TIEMPO REAL'], true);
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['Folio', 'Título', 'Severidad', 'Sucursal', 'Área', 'Asignado', 'Minutos restantes'], true);
        foreach ($sla_riesgo_actual as $i) {
            $xlsx->addRow([$i['folio'], $i['titulo'], $i['severidad_nombre'], $i['sucursal_nombre'], $i['area_nombre'],
                $i['asignado_a_nombre'] ?? '', (int) $i['minutos_restantes']]);
        }
    }
    $xlsx->download('reporte_sla_' . date('Ymd_His') . '.xlsx');
    exit;
}

$titulo_pagina = 'Análisis de SLA';
$pagina_activa = 'reportes';
require_once __DIR__ . '/../config/header.php';
?>

<?php reporte_export_assets(); ?>

<div id="rep-area" data-pdf="<?= e($pdf_filename) ?>" class="animate-fade-in space-y-5">

    <?php reporte_doc_header('Análisis de SLA', $periodo['etiqueta'] . ' · ' . $suc_label . ' · Cumplimiento ' . ($pct_global !== null ? $pct_global . '%' : 'n/d'), $rep_user); ?>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 no-print">
        <div class="flex items-center gap-3">
            <a href="<?= url('reportes/reportes.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900">Análisis de SLA</h2>
                <p class="text-xs text-zinc-500"><?= e($periodo['etiqueta']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php reporte_print_button(); ?>
            <?php reporte_pdf_button(); ?>
            <a href="<?= url('reportes/reporte_sla.php?' . http_build_query(array_merge($_GET, ['exportar' => 'xlsx']))) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">
                <i data-lucide="sheet" class="w-4 h-4"></i> Excel
            </a>
            <a href="<?= url('reportes/reporte_sla.php?' . http_build_query(array_merge($_GET, ['exportar' => 'csv']))) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="download" class="w-4 h-4"></i> CSV
            </a>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 no-print">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Período</label>
                <select name="periodo" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php $p_val = input('periodo', 'mes_actual');
                    foreach (['hoy'=>'Hoy','semana_actual'=>'Semana','mes_actual'=>'Mes actual','mes_anterior'=>'Mes anterior','trimestre'=>'90 días','año_actual'=>'Año'] as $k=>$l): ?>
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

    <!-- Cumplimiento global -->
    <div class="bg-gradient-to-br <?= ($pct_global ?? 0) >= 80 ? 'from-emerald-50 to-white border-emerald-200' : (($pct_global ?? 0) >= 50 ? 'from-amber-50 to-white border-amber-200' : 'from-bacal-50 to-white border-bacal-200') ?> rounded-xl border p-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl <?= ($pct_global ?? 0) >= 80 ? 'bg-emerald-500' : (($pct_global ?? 0) >= 50 ? 'bg-amber-500' : 'bg-bacal-700') ?> text-white flex items-center justify-center font-display text-2xl font-extrabold">
                <?= $pct_global !== null ? $pct_global . '%' : '—' ?>
            </div>
            <div class="flex-1">
                <div class="text-xs uppercase tracking-wider font-bold <?= ($pct_global ?? 0) >= 80 ? 'text-emerald-700' : (($pct_global ?? 0) >= 50 ? 'text-amber-700' : 'text-bacal-700') ?>">Cumplimiento de SLA</div>
                <div class="font-display text-xl font-bold text-zinc-900">
                    <?= $tot_cumpl ?> de <?= $tot_eval ?> incidencias evaluadas
                </div>
                <div class="text-xs text-zinc-600 mt-1">
                    <?php if ($pct_global === null): ?>
                    Sin incidencias cerradas en el período para evaluar SLA.
                    <?php elseif ($pct_global >= 80): ?>
                    Excelente desempeño. El equipo cumple consistentemente los acuerdos de nivel de servicio (SLA).
                    <?php elseif ($pct_global >= 50): ?>
                    Desempeño aceptable, pero con margen de mejora. Revisa las severidades con menor cumplimiento.
                    <?php else: ?>
                    Necesita atención urgente. El SLA se está incumpliendo en más de la mitad de los casos.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose por severidad -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Cumplimiento por severidad</h3>
        <p class="text-xs text-zinc-500 mb-4">Cómo se cumple el SLA en cada nivel de prioridad</p>
        <div class="space-y-4">
            <?php foreach ($por_severidad as $s):
                $tot = (int) $s['cumplidos'] + (int) $s['incumplidos'];
            ?>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <?= badge($s['nombre'], $s['color']) ?>
                        <span class="text-[10px] text-zinc-500">SLA: <?= $s['sla_horas'] ?>h</span>
                    </div>
                    <div class="text-xs">
                        <span class="font-bold text-zinc-900"><?= $s['cumplidos'] ?></span>
                        <span class="text-zinc-400">/</span>
                        <span class="text-zinc-600"><?= $tot ?></span>
                        <span class="text-zinc-500 ml-2">
                            <?php if ($s['pct_cumplido'] !== null): ?>
                            <span class="font-bold <?= $s['pct_cumplido'] >= 80 ? 'text-emerald-700' : ($s['pct_cumplido'] >= 50 ? 'text-amber-700' : 'text-bacal-700') ?>">
                                <?= $s['pct_cumplido'] ?>%
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php if ($tot > 0):
                    $pct_cumpl = ((int) $s['cumplidos'] / $tot) * 100;
                ?>
                <div class="flex h-2 bg-zinc-100 rounded-full overflow-hidden">
                    <div class="bg-emerald-500" style="width: <?= $pct_cumpl ?>%"></div>
                    <div class="bg-bacal-500" style="width: <?= (100 - $pct_cumpl) ?>%"></div>
                </div>
                <?php else: ?>
                <div class="h-2 bg-zinc-100 rounded-full"></div>
                <div class="text-[10px] text-zinc-400 italic mt-0.5">Sin datos en el período</div>
                <?php endif; ?>
                <?php if ($s['avg_resolucion'] !== null): ?>
                <div class="text-[10px] text-zinc-500 mt-1">Tiempo promedio de resolución: <strong class="text-zinc-700"><?= e(fmt_duracion((int) $s['avg_resolucion'])) ?></strong></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SLA en riesgo (tiempo real) -->
    <?php if (!empty($sla_riesgo_actual)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 no-print">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="clock-alert" class="w-4 h-4 text-amber-600"></i>
            Incidencias abiertas (estado de SLA en tiempo real)
        </h3>
        <p class="text-xs text-zinc-500 mb-4">Estas incidencias siguen abiertas y se ordenan por urgencia del SLA</p>
        <div class="space-y-2">
            <?php foreach (array_slice($sla_riesgo_actual, 0, 10) as $i):
                $min = (int) $i['minutos_restantes'];
                if ($min < 0) {
                    $clase = 'bg-bacal-50 border-bacal-200';
                    $icon = 'flame'; $color = 'text-bacal-700';
                    $estado_txt = 'Vencido hace ' . fmt_duracion(abs($min));
                } elseif ($min < 120) {
                    $clase = 'bg-amber-50 border-amber-200';
                    $icon = 'clock-alert'; $color = 'text-amber-700';
                    $estado_txt = 'Vence en ' . fmt_duracion($min);
                } else {
                    $clase = 'bg-zinc-50 border-zinc-200';
                    $icon = 'clock'; $color = 'text-zinc-600';
                    $estado_txt = 'Vence en ' . fmt_duracion($min);
                }
            ?>
            <a href="<?= url('incidencia_ver.php?id=' . $i['id']) ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg border <?= $clase ?> hover:shadow-sm transition-shadow">
                <i data-lucide="<?= $icon ?>" class="w-4 h-4 flex-shrink-0 <?= $color ?>"></i>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                        <span class="font-mono text-[10px] font-bold text-zinc-500"><?= e($i['folio']) ?></span>
                        <?= badge($i['severidad_nombre'], $i['severidad_color']) ?>
                        <?= badge($i['estado_nombre'], $i['estado_color']) ?>
                    </div>
                    <div class="font-semibold text-sm text-zinc-900 truncate"><?= e($i['titulo']) ?></div>
                    <div class="text-[10px] text-zinc-500"><?= e($i['sucursal_nombre']) ?> · <?= e($i['area_nombre']) ?> · <?= e($i['asignado_a_nombre'] ?? 'sin asignar') ?></div>
                </div>
                <div class="text-right text-xs">
                    <div class="font-bold <?= $color ?>"><?= e($estado_txt) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SLA incumplidos (histórico) -->
    <?php if (!empty($sla_incumplidas)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="x-circle" class="w-4 h-4 text-bacal-700"></i>
            Incidencias con SLA incumplido del período
        </h3>
        <p class="text-xs text-zinc-500 mb-4">Top 30 incidencias que tardaron más en cerrarse que su SLA permitía</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="border-b border-zinc-200">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Folio</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Título</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Severidad</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Asignado</th>
                        <th class="px-2 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase" data-orden-tipo="num">Excedido por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($sla_incumplidas as $i): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-2 py-2">
                            <a href="<?= url('incidencia_ver.php?id=' . $i['id']) ?>" class="font-mono text-xs font-bold text-zinc-700 hover:text-bacal-700">
                                <?= e($i['folio']) ?>
                            </a>
                        </td>
                        <td class="px-2 py-2 text-sm text-zinc-900 max-w-xs truncate"><?= e($i['titulo']) ?></td>
                        <td class="px-2 py-2"><?= badge($i['severidad_nombre'], $i['severidad_color']) ?></td>
                        <td class="px-2 py-2 text-xs text-zinc-700"><?= e($i['sucursal_nombre']) ?></td>
                        <td class="px-2 py-2 text-xs text-zinc-700"><?= e($i['asignado_a_nombre'] ?? '—') ?></td>
                        <td class="px-2 py-2 text-right" data-orden="<?= (int) $i['minutos_excedidos'] ?>">
                            <span class="font-bold text-bacal-700"><?= e(fmt_duracion((int) $i['minutos_excedidos'])) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
