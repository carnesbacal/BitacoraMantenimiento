<?php
/**
 * ============================================================================
 * reportes/reporte_costos.php - Análisis de costos de mantenimiento
 * ============================================================================
 * Reporte completo y filtrable de costos:
 *   - KPIs: total, externo (proveedores), interno (refacciones), promedio
 *   - Tendencia por día / semana / mes
 *   - Desglose interno vs externo
 *   - Ranking de incidencias más caras
 *   - Ranking de proveedores más caros
 *   - Costos por sucursal
 *   - Exportación CSV
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/reportes_helpers.php';
require_once __DIR__ . '/../config/incidencia_costos_helpers.php';
require_once __DIR__ . '/../config/flotilla_helpers.php';

$periodo = resolver_periodo();
[$sucursal_filtro, $sucursales_lista, $where_sucursal, $params_sucursal] = resolver_filtro_sucursal();

// Agrupación de tendencia
$agrupar = (string) input('agrupar', 'mes');
if (!in_array($agrupar, ['dia', 'semana', 'mes'], true)) $agrupar = 'mes';

$es_exportacion = (input('exportar') === 'csv');
$es_xlsx        = (input('exportar') === 'xlsx');

// ----------------------------------------------------------------------------
// Cargar datos
// ----------------------------------------------------------------------------
$resumen     = costos_resumen_periodo($periodo['desde'], $periodo['hasta'], $where_sucursal, $params_sucursal);
$ranking_inc = costos_ranking_incidencias($periodo['desde'], $periodo['hasta'], 20, $where_sucursal, $params_sucursal);
$ranking_prov= costos_ranking_proveedores($periodo['desde'], $periodo['hasta'], 20, $where_sucursal, $params_sucursal);
$tendencia   = costos_tendencia($periodo['desde'], $periodo['hasta'], $agrupar, $where_sucursal, $params_sucursal);

// Gasto de flotilla (mantenimiento de vehículos) por proveedor — sección separada,
// no se mezcla con el ranking de incidencias porque son gastos distintos.
$prov_flota = function_exists('flotilla_gasto_proveedores')
    ? flotilla_gasto_proveedores($periodo['desde'], $periodo['hasta'], '', 20)
    : [];
$flota_total = 0.0;
foreach ($prov_flota as $fp) { $flota_total += (float) $fp['total']; }
$por_sucursal= $sucursal_filtro ? [] : costos_por_sucursal($periodo['desde'], $periodo['hasta']);

// Etiqueta de sucursal y datos para encabezados / exportación (impresión y PDF).
$suc_label = 'Todas las sucursales';
if ($sucursal_filtro) {
    foreach ($sucursales_lista as $sl) {
        if ((int) $sl['id'] === (int) $sucursal_filtro) { $suc_label = $sl['nombre']; break; }
    }
    if ($suc_label === 'Todas las sucursales') {
        $srow = db_one("SELECT nombre FROM sucursales WHERE id = :id", ['id' => $sucursal_filtro]);
        if ($srow) $suc_label = $srow['nombre'];
    }
}
$rep_user     = usuario_actual()['nombre'] ?? '';
$pdf_filename = 'reporte_costos_' . date('Ymd_His') . '.pdf';

// Comparativa contra el periodo anterior (misma duración, inmediatamente antes).
$prev_hasta   = date('Y-m-d', strtotime($periodo['desde'] . ' -1 day'));
$dur_dias     = (int) (new DateTime($periodo['hasta']))->diff(new DateTime($periodo['desde']))->days;
$prev_desde   = date('Y-m-d', strtotime($prev_hasta . ' -' . $dur_dias . ' days'));
$resumen_prev = costos_resumen_periodo($prev_desde, $prev_hasta, $where_sucursal, $params_sucursal);

$delta_html = function (float $cur, float $prev, bool $neutral = false): string {
    if ($prev <= 0) return $cur > 0 ? '<span class="text-zinc-400">sin base previa</span>' : '';
    $d = ($cur - $prev) / $prev * 100;
    if (abs($d) < 0.1) return '<span class="text-zinc-400">igual que el anterior</span>';
    $up = $d > 0;
    $cls = $neutral ? 'text-zinc-500' : ($up ? 'text-red-600' : 'text-emerald-600');
    return '<span class="' . $cls . ' font-semibold">' . ($up ? '&#9650;' : '&#9660;') . ' '
        . number_format(abs($d), 1) . '% vs anterior</span>';
};

// ----------------------------------------------------------------------------
// Exportación CSV
// ----------------------------------------------------------------------------
if ($es_exportacion) {
    csv_iniciar('reporte_costos_' . date('Ymd_His') . '.csv');
    csv_fila(['REPORTE DE COSTOS DE MANTENIMIENTO']);
    csv_fila(['Período:', $periodo['etiqueta']]);
    csv_fila(['Generado:', date('Y-m-d H:i')]);
    csv_fila(['Sucursal:', $suc_label]);
    csv_fila(['']);

    csv_fila(['RESUMEN']);
    csv_fila(['Costo total', number_format($resumen['total'], 2, '.', '')]);
    csv_fila(['Costo externo (proveedores)', number_format($resumen['externo'], 2, '.', '')]);
    csv_fila(['  Mano de obra', number_format($resumen['mano_obra'], 2, '.', '')]);
    csv_fila(['  Materiales proveedor', number_format($resumen['materiales'], 2, '.', '')]);
    csv_fila(['Costo interno (refacciones)', number_format($resumen['interno'], 2, '.', '')]);
    csv_fila(['Gasto flotilla (mantenimiento de vehículos)', number_format($flota_total, 2, '.', '')]);
    csv_fila(['TOTAL GENERAL (mantenimiento + flotilla)', number_format((float) $resumen['total'] + $flota_total, 2, '.', '')]);
    csv_fila(['Incidencias en el período', $resumen['num_total']]);
    csv_fila(['  Internas', $resumen['num_total'] - $resumen['con_proveedor']]);
    csv_fila(['  Externas (con proveedor)', $resumen['con_proveedor']]);
    csv_fila(['Incidencias con costo', $resumen['con_costo']]);
    csv_fila(['']);

    $inc_export = costos_ranking_incidencias($periodo['desde'], $periodo['hasta'], 100000, $where_sucursal, $params_sucursal);
    csv_fila(['INCIDENCIAS CON COSTO (DETALLE COMPLETO)', count($inc_export) . ' incidencias']);
    csv_fila(['Fecha', 'Folio', 'Título', 'Sucursal', 'Proveedor', 'Mano obra', 'Materiales', 'Refacciones', 'Total']);
    foreach ($inc_export as $r) {
        csv_fila([
            date('Y-m-d', strtotime($r['fecha_evento'])),
            $r['folio'], $r['titulo'], $r['sucursal_nombre'],
            $r['proveedor_nombre'] ?? $r['proveedor_externo_info'] ?? '',
            number_format((float) $r['mano_obra'], 2, '.', ''),
            number_format((float) $r['materiales'], 2, '.', ''),
            number_format((float) $r['refacciones'], 2, '.', ''),
            number_format((float) $r['total'], 2, '.', ''),
        ]);
    }
    csv_fila(['']);

    csv_fila(['PROVEEDORES MÁS CAROS']);
    csv_fila(['Proveedor', 'Servicio', 'Incidencias', 'Mano obra', 'Materiales', 'Total']);
    foreach ($ranking_prov as $p) {
        csv_fila([
            $p['nombre'], $p['servicio'] ?? '', $p['num_incidencias'],
            number_format((float) $p['mano_obra'], 2, '.', ''),
            number_format((float) $p['materiales'], 2, '.', ''),
            number_format((float) $p['total'], 2, '.', ''),
        ]);
    }

    if (!empty($prov_flota)) {
        csv_fila(['']);
        csv_fila(['PROVEEDORES DE FLOTILLA (MANTENIMIENTO DE VEHÍCULOS)']);
        csv_fila(['Proveedor / Taller', 'Servicios', 'Vehículos', 'Promedio', 'Total']);
        foreach ($prov_flota as $pf) {
            $reg = (int) $pf['registros'];
            csv_fila([
                $pf['proveedor'], $reg, (int) $pf['vehiculos'],
                number_format($reg > 0 ? (float) $pf['total'] / $reg : 0, 2, '.', ''),
                number_format((float) $pf['total'], 2, '.', ''),
            ]);
        }
    }

    if (!empty($por_sucursal)) {
        csv_fila(['']);
        csv_fila(['COSTOS POR SUCURSAL']);
        csv_fila(['Sucursal', 'Incidencias', 'Externo', 'Interno', 'Total']);
        foreach ($por_sucursal as $s) {
            csv_fila([
                $s['nombre'], $s['num_incidencias'],
                number_format((float) $s['externo'], 2, '.', ''),
                number_format((float) $s['interno'], 2, '.', ''),
                number_format((float) $s['total'], 2, '.', ''),
            ]);
        }
    }
    exit;
}

// ----------------------------------------------------------------------------
// Exportación Excel (.xlsx) multi-hoja
// ----------------------------------------------------------------------------
if ($es_xlsx) {
    require_once __DIR__ . '/../config/xlsx_writer.php';
    $inc_full  = costos_ranking_incidencias($periodo['desde'], $periodo['hasta'], 100000, $where_sucursal, $params_sucursal);
    $prov_full = costos_ranking_proveedores($periodo['desde'], $periodo['hasta'], 500, $where_sucursal, $params_sucursal);

    $xlsx = new XlsxWriter();
    $periodo_label = 'Período: ' . $periodo['etiqueta'];

    // Hoja 1: Resumen + comparativa
    $xlsx->addSheet('Resumen');
    $xlsx->addHeaderRow(['REPORTE DE COSTOS DE MANTENIMIENTO'], true);
    $xlsx->addRow([$periodo_label]);
    $xlsx->addRow(['Sucursal: ' . $suc_label]);
    $xlsx->addRow(['Generado: ' . date('d/m/Y H:i') . ($rep_user ? ' por ' . $rep_user : '')]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Indicador', 'Valor'], true);
    $xlsx->addRow(['Costo total', round((float) $resumen['total'], 2)]);
    $xlsx->addRow(['Costo externo (proveedores)', round((float) $resumen['externo'], 2)]);
    $xlsx->addRow(['  Mano de obra', round((float) $resumen['mano_obra'], 2)]);
    $xlsx->addRow(['  Materiales proveedor', round((float) $resumen['materiales'], 2)]);
    $xlsx->addRow(['Costo interno (refacciones)', round((float) $resumen['interno'], 2)]);
    $xlsx->addRow(['Incidencias en el período', (int) $resumen['num_total']]);
    $xlsx->addRow(['  Con costo', (int) $resumen['con_costo']]);
    $xlsx->addRow(['  Con proveedor', (int) $resumen['con_proveedor']]);
    $xlsx->addRow(['Costo promedio por incidencia con costo', round((float) $resumen['promedio'], 2)]);
    $xlsx->addRow(['Gasto flotilla (mantenimiento de vehículos)', round((float) $flota_total, 2)]);
    $xlsx->addRow(['TOTAL GENERAL (mantenimiento + flotilla)', round((float) $resumen['total'] + (float) $flota_total, 2)]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['COMPARATIVA VS PERIODO ANTERIOR (' . $prev_desde . ' a ' . $prev_hasta . ')'], true);
    $xlsx->addHeaderRow(['Indicador', 'Actual', 'Anterior', 'Variación %'], true);
    $cmp = function ($cur, $prev) {
        $cur = (float) $cur; $prev = (float) $prev;
        $var = $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) . '%' : 'n/d';
        return [round($cur, 2), round($prev, 2), $var];
    };
    $xlsx->addRow(array_merge(['Costo total'],           $cmp($resumen['total'],     $resumen_prev['total'])));
    $xlsx->addRow(array_merge(['Externo (proveedores)'], $cmp($resumen['externo'],   $resumen_prev['externo'])));
    $xlsx->addRow(array_merge(['Interno (refacciones)'], $cmp($resumen['interno'],   $resumen_prev['interno'])));
    $xlsx->addRow(array_merge(['Incidencias'],           $cmp($resumen['num_total'], $resumen_prev['num_total'])));

    // Hoja 2: Incidencias con costo (todas)
    $xlsx->addSheet('Incidencias');
    $xlsx->addHeaderRow(['INCIDENCIAS CON COSTO (' . count($inc_full) . ')'], true);
    $xlsx->addRow([$periodo_label]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Fecha', 'Folio', 'Título', 'Sucursal', 'Atendió', 'Mano obra', 'Materiales', 'Refacciones', 'Mat. comprados', 'MO interna', 'Total'], true);
    foreach ($inc_full as $r) {
        $xlsx->addRow([
            date('Y-m-d', strtotime($r['fecha_evento'])),
            $r['folio'], $r['titulo'], $r['sucursal_nombre'],
            $r['proveedor_nombre'] ?: ($r['proveedor_externo_info'] ?: 'Interno'),
            round((float) $r['mano_obra'], 2),
            round((float) $r['materiales'], 2),
            round((float) $r['refacciones'], 2),
            round((float) $r['materiales_comprados'], 2),
            round((float) $r['mano_obra_interna'], 2),
            round((float) $r['total'], 2),
        ]);
    }
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', '', '', 'TOTAL',
        round(array_sum(array_map(fn($x) => (float) $x['mano_obra'], $inc_full)), 2),
        round(array_sum(array_map(fn($x) => (float) $x['materiales'], $inc_full)), 2),
        round(array_sum(array_map(fn($x) => (float) $x['refacciones'], $inc_full)), 2),
        round(array_sum(array_map(fn($x) => (float) $x['materiales_comprados'], $inc_full)), 2),
        round(array_sum(array_map(fn($x) => (float) $x['mano_obra_interna'], $inc_full)), 2),
        round(array_sum(array_map(fn($x) => (float) $x['total'], $inc_full)), 2),
    ]);

    // Hoja 3: Proveedores
    $xlsx->addSheet('Proveedores');
    $xlsx->addHeaderRow(['PROVEEDORES CON GASTO (' . count($prov_full) . ')'], true);
    $xlsx->addRow([$periodo_label]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Proveedor', 'Servicio', 'Incidencias', 'Mano obra', 'Materiales', 'Total'], true);
    foreach ($prov_full as $p) {
        $xlsx->addRow([
            $p['nombre'], $p['servicio'] ?? '', (int) $p['num_incidencias'],
            round((float) $p['mano_obra'], 2), round((float) $p['materiales'], 2), round((float) $p['total'], 2),
        ]);
    }

    // Hoja 4: Flotilla
    if (!empty($prov_flota)) {
        $xlsx->addSheet('Flotilla');
        $xlsx->addHeaderRow(['PROVEEDORES DE FLOTILLA (MANTENIMIENTO DE VEHÍCULOS)'], true);
        $xlsx->addRow([$periodo_label]);
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['Proveedor / Taller', 'Servicios', 'Vehículos', 'Promedio', 'Total'], true);
        foreach ($prov_flota as $pf) {
            $reg = (int) $pf['registros'];
            $xlsx->addRow([
                $pf['proveedor'], $reg, (int) $pf['vehiculos'],
                round($reg > 0 ? (float) $pf['total'] / $reg : 0, 2),
                round((float) $pf['total'], 2),
            ]);
        }
        $xlsx->addBlankRow();
        $xlsx->addRow(['', '', '', 'TOTAL', round((float) $flota_total, 2)]);
    }

    // Hoja 5: Por sucursal
    if (!empty($por_sucursal)) {
        $xlsx->addSheet('Por sucursal');
        $xlsx->addHeaderRow(['COSTOS POR SUCURSAL'], true);
        $xlsx->addRow([$periodo_label]);
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['Sucursal', 'Incidencias', 'Externo', 'Interno', 'Total'], true);
        foreach ($por_sucursal as $sx) {
            $xlsx->addRow([
                $sx['nombre'], (int) $sx['num_incidencias'],
                round((float) $sx['externo'], 2), round((float) $sx['interno'], 2), round((float) $sx['total'], 2),
            ]);
        }
    }

    // Hoja 6: Tendencia
    if (!empty($tendencia)) {
        $xlsx->addSheet('Tendencia');
        $xlsx->addHeaderRow(['TENDENCIA DE COSTOS (' . $agrupar . ')'], true);
        $xlsx->addRow([$periodo_label]);
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['Período', 'Externo', 'Interno', 'Total'], true);
        foreach ($tendencia as $t) {
            $ext = (float) $t['externo']; $int = (float) $t['interno'];
            $xlsx->addRow([$t['label'], round($ext, 2), round($int, 2), round($ext + $int, 2)]);
        }
    }

    $xlsx->download('reporte_costos_' . date('Ymd_His') . '.xlsx');
    exit;
}

// Datos para gráficas
$tend_labels = array_map(fn($t) => $t['label'], $tendencia);
$tend_externo = array_map(fn($t) => round((float) $t['externo'], 2), $tendencia);
$tend_interno = array_map(fn($t) => round((float) $t['interno'], 2), $tendencia);

$titulo_pagina = 'Reporte de costos';
$pagina_activa = 'reportes';
require_once __DIR__ . '/../config/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
    .solo-print { display: none; }
    /* Durante la generación del PDF: mostrar encabezado de documento, ocultar controles y liberar overflow */
    body.modo-pdf .no-print { display: none !important; }
    body.modo-pdf .solo-print { display: block !important; }
    body.modo-pdf main,
    body.modo-pdf .overflow-hidden,
    body.modo-pdf .overflow-y-auto,
    body.modo-pdf .overflow-x-auto { overflow: visible !important; height: auto !important; max-height: none !important; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        .no-print { display: none !important; }
        .solo-print { display: block !important; }
        aside, header.h-16 { display: none !important; }
        html, body { background: #fff !important; height: auto !important; overflow: visible !important; }
        main,
        .overflow-hidden, .overflow-y-auto, .overflow-x-auto {
            overflow: visible !important; height: auto !important; max-height: none !important;
        }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .rounded-xl, table, tr, thead, tfoot, canvas { break-inside: avoid; }
        a { color: inherit !important; text-decoration: none !important; }
    }
</style>

<div id="rep-area" class="animate-fade-in space-y-5">

    <!-- Encabezado para impresión / PDF -->
    <div class="solo-print" style="margin-bottom:16px;">
        <table style="width:100%;border-bottom:2px solid #E94E1B;padding-bottom:6px;">
            <tr>
                <td style="text-align:left;vertical-align:top;">
                    <div style="font-size:18px;font-weight:800;color:#18181b;">Reporte de costos de mantenimiento</div>
                    <div style="font-size:12px;color:#52525b;margin-top:2px;"><?= e($periodo['etiqueta']) ?> &middot; <?= e($suc_label) ?></div>
                </td>
                <td style="text-align:right;vertical-align:top;font-size:11px;color:#52525b;">
                    <div style="font-size:13px;font-weight:800;color:#E94E1B;">SIGMA &middot; Carnes Bacal</div>
                    <div>Generado: <?= date('d/m/Y H:i') ?></div>
                    <?php if ($rep_user): ?><div>Por: <?= e($rep_user) ?></div><?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Encabezado -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 no-print">
        <div class="flex items-center gap-3">
            <a href="<?= url('reportes/reportes.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900">Reporte de costos</h2>
                <p class="text-xs text-zinc-500"><?= e($periodo['etiqueta']) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="printer" class="w-4 h-4"></i> Imprimir
            </button>
            <button onclick="descargarPDF()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                <i data-lucide="file-down" class="w-4 h-4"></i> PDF
            </button>
            <a href="<?= url('reportes/reporte_costos.php?' . http_build_query(array_merge($_GET, ['exportar' => 'xlsx']))) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                <i data-lucide="sheet" class="w-4 h-4"></i> Excel
            </a>
            <a href="<?= url('reportes/reporte_costos.php?' . http_build_query(array_merge($_GET, ['exportar' => 'csv']))) ?>"
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
                    foreach (['hoy'=>'Hoy','semana_actual'=>'Semana','mes_actual'=>'Mes actual','mes_anterior'=>'Mes anterior','trimestre'=>'90 días','año_actual'=>'Año','personalizado'=>'Personalizado'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= $p_val === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (input('periodo') === 'personalizado'): ?>
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Desde</label>
                <input type="date" name="desde" value="<?= e(input('desde', date('Y-m-01'))) ?>" onchange="this.form.submit()"
                       class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Hasta</label>
                <input type="date" name="hasta" value="<?= e(input('hasta', date('Y-m-d'))) ?>" onchange="this.form.submit()"
                       class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Agrupar tendencia</label>
                <select name="agrupar" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php foreach (['dia'=>'Por día','semana'=>'Por semana','mes'=>'Por mes'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= $agrupar === $k ? 'selected' : '' ?>><?= e($l) ?></option>
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

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-bacal-50 to-white rounded-xl border border-bacal-200 shadow-sm p-5">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Costo total</div>
            <div class="font-display text-3xl font-extrabold text-bacal-700 leading-none"><?= e(fmt_dinero_corto($resumen['total'])) ?></div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= e(fmt_dinero($resumen['total'])) ?></div>
            <div class="text-[10px] mt-1"><?= $delta_html((float) $resumen['total'], (float) $resumen_prev['total']) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Proveedores</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900 leading-none"><?= e(fmt_dinero_corto($resumen['externo'])) ?></div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= $resumen['pct_externo'] ?>% · MO <?= e(fmt_dinero_corto($resumen['mano_obra'])) ?> + Mat <?= e(fmt_dinero_corto($resumen['materiales'])) ?></div>
            <div class="text-[10px] mt-1"><?= $delta_html((float) $resumen['externo'], (float) $resumen_prev['externo']) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Refacciones internas</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900 leading-none"><?= e(fmt_dinero_corto($resumen['interno'])) ?></div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= $resumen['pct_interno'] ?>% del total</div>
            <div class="text-[10px] mt-1"><?= $delta_html((float) $resumen['interno'], (float) $resumen_prev['interno']) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Incidencias</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900 leading-none"><?= number_format($resumen['num_total']) ?></div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= number_format($resumen['num_total'] - $resumen['con_proveedor']) ?> internas · <?= number_format($resumen['con_proveedor']) ?> externas</div>
            <div class="text-[10px] text-zinc-400 mt-0.5">Prom. <?= e(fmt_dinero($resumen['promedio'])) ?> por incidencia con costo</div>
            <div class="text-[10px] mt-1"><?= $delta_html((float) $resumen['num_total'], (float) $resumen_prev['num_total'], true) ?></div>
        </div>
    </div>

    <!-- Tendencia + Desglose -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-3">Tendencia de costos</h3>
            <?php if (array_sum($tend_externo) + array_sum($tend_interno) > 0): ?>
            <div class="h-64"><canvas id="chartTendencia"></canvas></div>
            <?php else: ?>
            <div class="h-64 flex items-center justify-center text-sm text-zinc-400">Sin costos en el período.</div>
            <?php endif; ?>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-3">Externo vs Interno</h3>
            <?php if ($resumen['total'] > 0): ?>
            <div class="h-48 flex items-center justify-center"><canvas id="chartDesglose"></canvas></div>
            <div class="mt-4 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-bacal-600"></span> Proveedores</span>
                    <span class="font-semibold"><?= e(fmt_dinero($resumen['externo'])) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-zinc-400"></span> Refacciones</span>
                    <span class="font-semibold"><?= e(fmt_dinero($resumen['interno'])) ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="h-48 flex items-center justify-center text-sm text-zinc-400">Sin datos.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ranking incidencias más caras -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="trending-up" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Incidencias más caras</h3>
            <span class="text-xs text-zinc-500">(<?= count($ranking_inc) ?>)</span>
        </div>
        <?php if (empty($ranking_inc)): ?>
        <div class="px-5 py-10 text-center text-sm text-zinc-400">Sin incidencias con costo en el período.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-8" data-no-orden>#</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Incidencia</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Atendió</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Mano obra</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Materiales</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Refacc.</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($ranking_inc as $idx => $r): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 text-zinc-400 font-mono text-xs"><?= $idx + 1 ?></td>
                        <td class="px-4 py-2.5">
                            <a href="<?= url('incidencia_ver.php?id=' . $r['id']) ?>" class="block group">
                                <span class="font-mono text-[10px] font-bold text-zinc-500"><?= e($r['folio']) ?></span>
                                <div class="font-semibold text-sm text-zinc-900 group-hover:text-bacal-700 truncate max-w-xs"><?= e($r['titulo']) ?></div>
                                <div class="text-[10px] text-zinc-400"><?= e($r['sucursal_nombre']) ?> · <?= e(date('d/m/Y', strtotime($r['fecha_evento']))) ?></div>
                            </a>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-600">
                            <?php if ($r['proveedor_nombre']): ?>
                                <span class="inline-flex items-center gap-1"><i data-lucide="truck" class="w-3 h-3"></i><?= e($r['proveedor_nombre']) ?></span>
                            <?php elseif ($r['proveedor_externo_info']): ?>
                                <span class="text-zinc-500"><?= e($r['proveedor_externo_info']) ?></span>
                            <?php else: ?>
                                <span class="text-zinc-400 italic">Interno</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600" data-orden="<?= (float) $r['mano_obra'] ?>"><?= (float) $r['mano_obra'] > 0 ? e(fmt_dinero((float) $r['mano_obra'])) : '—' ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600" data-orden="<?= (float) $r['materiales'] ?>"><?= (float) $r['materiales'] > 0 ? e(fmt_dinero((float) $r['materiales'])) : '—' ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600" data-orden="<?= (float) $r['refacciones'] ?>"><?= (float) $r['refacciones'] > 0 ? e(fmt_dinero((float) $r['refacciones'])) : '—' ?></td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-bacal-700" data-orden="<?= (float) $r['total'] ?>"><?= e(fmt_dinero((float) $r['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-zinc-50 border-t border-zinc-200">
                    <tr>
                        <td class="px-4 py-2.5" colspan="3"><span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Suma de las <?= count($ranking_inc) ?> mostradas</span></td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-zinc-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['mano_obra'], $ranking_inc)))) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-zinc-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['materiales'], $ranking_inc)))) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-zinc-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['refacciones'], $ranking_inc)))) ?></td>
                        <td class="px-4 py-2.5 text-right text-sm font-extrabold text-bacal-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['total'], $ranking_inc)))) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ranking proveedores más caros -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="truck" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Proveedores más caros</h3>
            <span class="text-xs text-zinc-500">(<?= count($ranking_prov) ?>)</span>
        </div>
        <?php if (empty($ranking_prov)): ?>
        <div class="px-5 py-10 text-center text-sm text-zinc-400">Sin gastos a proveedores registrados en el período.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-8" data-no-orden>#</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Proveedor</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Incid.</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Mano obra</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Materiales</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($ranking_prov as $idx => $p): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 text-zinc-400 font-mono text-xs"><?= $idx + 1 ?></td>
                        <td class="px-4 py-2.5">
                            <div class="font-semibold text-sm text-zinc-900"><?= e($p['nombre']) ?></div>
                            <?php if ($p['servicio']): ?><div class="text-[10px] text-zinc-400"><?= e($p['servicio']) ?></div><?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-center text-sm text-zinc-700" data-orden="<?= (int) $p['num_incidencias'] ?>"><?= (int) $p['num_incidencias'] ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600" data-orden="<?= (float) $p['mano_obra'] ?>"><?= e(fmt_dinero((float) $p['mano_obra'])) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600" data-orden="<?= (float) $p['materiales'] ?>"><?= e(fmt_dinero((float) $p['materiales'])) ?></td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-bacal-700" data-orden="<?= (float) $p['total'] ?>"><?= e(fmt_dinero((float) $p['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-zinc-50 border-t border-zinc-200">
                    <tr>
                        <td class="px-4 py-2.5" colspan="2"><span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Suma de los <?= count($ranking_prov) ?> mostrados</span></td>
                        <td class="px-4 py-2.5 text-center text-sm font-bold text-zinc-700"><?= array_sum(array_map(fn($x) => (int) $x['num_incidencias'], $ranking_prov)) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-zinc-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['mano_obra'], $ranking_prov)))) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-zinc-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['materiales'], $ranking_prov)))) ?></td>
                        <td class="px-4 py-2.5 text-right text-sm font-extrabold text-bacal-700"><?= e(fmt_dinero(array_sum(array_map(fn($x) => (float) $x['total'], $ranking_prov)))) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Proveedores de flotilla (mantenimiento de vehículos) -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="truck" class="w-5 h-5 text-blue-600"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Proveedores de flotilla más caros</h3>
            <span class="text-xs text-zinc-500">(<?= count($prov_flota) ?>)</span>
            <span class="ml-auto text-[11px] text-zinc-400">Mantenimiento de vehículos · gasto independiente de incidencias</span>
        </div>
        <?php if (empty($prov_flota)): ?>
        <div class="px-5 py-10 text-center text-sm text-zinc-400">Sin gastos de mantenimiento de flotilla en el período.</div>
        <?php else: ?>
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
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600"><?= e(fmt_dinero($prom)) ?></td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-blue-700"><?= e(fmt_dinero((float) $pf['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-zinc-50 border-t border-zinc-200">
                    <tr>
                        <td colspan="5" class="px-4 py-2.5 text-right text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Total flotilla</td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-blue-700"><?= e(fmt_dinero((float) $flota_total)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Costos por sucursal -->
    <?php if (!empty($por_sucursal)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="map-pin" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Costos por sucursal</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Incid.</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Externo</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Interno</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($por_sucursal as $s): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 font-semibold text-zinc-900"><?= e($s['nombre']) ?> <span class="text-[10px] text-zinc-400 font-mono"><?= e($s['codigo']) ?></span></td>
                        <td class="px-4 py-2.5 text-center text-zinc-700"><?= (int) $s['num_incidencias'] ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600"><?= e(fmt_dinero((float) $s['externo'])) ?></td>
                        <td class="px-4 py-2.5 text-right text-xs text-zinc-600"><?= e(fmt_dinero((float) $s['interno'])) ?></td>
                        <td class="px-4 py-2.5 text-right font-bold text-sm text-bacal-700"><?= e(fmt_dinero((float) $s['total'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fmtMoney = (v) => '$' + Number(v).toLocaleString('es-MX', {minimumFractionDigits: 0, maximumFractionDigits: 0});

    // Tendencia (barras apiladas)
    const ctxT = document.getElementById('chartTendencia');
    if (ctxT) {
        new Chart(ctxT, {
            type: 'bar',
            data: {
                labels: <?= json_encode($tend_labels) ?>,
                datasets: [
                    {
                        label: 'Proveedores',
                        data: <?= json_encode($tend_externo) ?>,
                        backgroundColor: '#E94E1B',
                        borderRadius: 4,
                    },
                    {
                        label: 'Refacciones internas',
                        data: <?= json_encode($tend_interno) ?>,
                        backgroundColor: '#a1a1aa',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, ticks: { callback: (v) => fmtMoney(v) } }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fmtMoney(c.raw) } }
                }
            }
        });
    }

    // Desglose (dona)
    const ctxD = document.getElementById('chartDesglose');
    if (ctxD) {
        new Chart(ctxD, {
            type: 'doughnut',
            data: {
                labels: ['Proveedores', 'Refacciones'],
                datasets: [{
                    data: [<?= round($resumen['externo'], 2) ?>, <?= round($resumen['interno'], 2) ?>],
                    backgroundColor: ['#E94E1B', '#a1a1aa'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (c) => c.label + ': ' + fmtMoney(c.raw) } }
                }
            }
        });
    }
});
</script>

<script>
function descargarPDF() {
    var el = document.getElementById('rep-area');
    if (typeof html2pdf === 'undefined' || !el) { window.print(); return; }
    document.body.classList.add('modo-pdf');
    var opt = {
        margin:      [10, 8, 12, 8],
        filename:    <?= json_encode($pdf_filename) ?>,
        image:       { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollY: 0 },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:   { mode: ['css', 'legacy'], avoid: ['tr', 'thead', 'canvas'] }
    };
    html2pdf().set(opt).from(el).save()
        .then(function () { document.body.classList.remove('modo-pdf'); })
        .catch(function () { document.body.classList.remove('modo-pdf'); window.print(); });
}
</script>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
