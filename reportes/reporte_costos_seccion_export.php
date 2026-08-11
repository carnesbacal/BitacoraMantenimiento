<?php
/**
 * ============================================================================
 * reportes/reporte_costos_seccion_export.php
 * Exporta UNA sección del reporte de costos a Excel, con los filtros actuales
 * (período + sucursal), en formato listo para imprimir (ajustado a una hoja).
 *   ?seccion=resumen|tendencia|incidencias|proveedores|flotilla|adquisiciones|sucursales
 *   &periodo=...&desde=...&hasta=...&sucursal=...&agrupar=...
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/reportes_helpers.php';
require_once __DIR__ . '/../config/incidencia_costos_helpers.php';
require_once __DIR__ . '/../config/flotilla_helpers.php';
require_once __DIR__ . '/../config/xlsx_writer.php';

requerir_login();
$u = usuario_actual();

$periodo = resolver_periodo();
[$sucursal_filtro, $sucursales_lista, $where_sucursal, $params_sucursal] = resolver_filtro_sucursal();
$desde = $periodo['desde'];
$hasta = $periodo['hasta'];

$agrupar = (string) input('agrupar', 'mes');
if (!in_array($agrupar, ['dia', 'semana', 'mes'], true)) $agrupar = 'mes';

$secciones = [
    'resumen'       => 'Resumen del período',
    'tendencia'     => 'Tendencia de costos',
    'incidencias'   => 'Incidencias más caras',
    'proveedores'   => 'Proveedores más caros',
    'flotilla'      => 'Proveedores de flotilla',
    'adquisiciones' => 'Adquisiciones del mes',
    'sucursales'    => 'Costos por sucursal',
];
$seccion = (string) input('seccion', '');
if (!isset($secciones[$seccion])) die('Sección no válida.');

// Etiqueta de sucursal
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

$m  = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // dinero: número real con formato $ (sumable)
$pc = fn($v) => ['v' => round((float) $v, 1), 's' => 8];   // porcentaje: número real con formato %

$xlsx = new XlsxWriter();
$xlsx->addSheet(mb_substr($secciones[$seccion], 0, 28));
// Secciones de resumen → todo en una hoja; detalle largo → ajusta ancho, fluye alto.
if (in_array($seccion, ['incidencias', 'proveedores'], true)) {
    $xlsx->setPageSetup(1, 0, 'landscape');
} else {
    $xlsx->setPageSetup(1, 1, 'landscape');
}
$xlsx->addHeaderRow([mb_strtoupper($secciones[$seccion])], true);
$xlsx->addRow(['Período: ' . $periodo['etiqueta']]);
$xlsx->addRow(['Sucursal: ' . $suc_label]);
$xlsx->addRow(['Generado: ' . date('d/m/Y H:i') . (($u['nombre'] ?? '') ? ' por ' . $u['nombre'] : '')]);
$xlsx->addBlankRow();

// ---------------------------------------------------------------------------
switch ($seccion) {

case 'resumen':
    $resumen     = costos_resumen_periodo($desde, $hasta, $where_sucursal, $params_sucursal);
    $adq_refacc  = adquisiciones_refacciones($desde, $hasta, (int) $sucursal_filtro, 100000);
    $adq_equipos = adquisiciones_equipos($desde, $hasta, (int) $sucursal_filtro, 100000);
    $adq_total   = (float) $adq_refacc['total'] + (float) $adq_equipos['total'];
    $gran_total  = (float) $resumen['total'] + $adq_total;
    $flota_prov  = function_exists('flotilla_gasto_proveedores') ? flotilla_gasto_proveedores($desde, $hasta, '', 500) : [];
    $flota_total = 0.0; foreach ($flota_prov as $fp) { $flota_total += (float) $fp['total']; }

    $xlsx->addHeaderRow(['Indicador', 'Valor'], true);
    $xlsx->addRow(['Costo de incidencias (interno + proveedores)', $m($resumen['total'])]);
    $xlsx->addRow(['Costo externo (proveedores)', $m($resumen['externo'])]);
    $xlsx->addRow(['  Mano de obra', $m($resumen['mano_obra'])]);
    $xlsx->addRow(['  Materiales proveedor', $m($resumen['materiales'])]);
    $xlsx->addRow(['Costo interno (refacciones)', $m($resumen['interno'])]);
    $xlsx->addRow(['Adquisiciones · refacciones compradas (incl. requisiciones)', $m($adq_refacc['total'])]);
    $xlsx->addRow(['Adquisiciones · equipos comprados', $m($adq_equipos['total'])]);
    $xlsx->addRow(['TOTAL DEL MES (incidencias + adquisiciones)', $m($gran_total)]);
    $xlsx->addRow(['Gasto flotilla (por separado, NO incluido en el total)', $m($flota_total)]);
    $xlsx->addRow(['Incidencias en el período', (int) $resumen['num_total']]);
    $xlsx->addRow(['  Internas', (int) $resumen['num_total'] - (int) $resumen['con_proveedor']]);
    $xlsx->addRow(['  Externas (con proveedor)', (int) $resumen['con_proveedor']]);
    $xlsx->addRow(['  Con costo', (int) $resumen['con_costo']]);
    $xlsx->addRow(['Costo promedio por incidencia con costo', $m($resumen['promedio'])]);

    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['DESGLOSE DEL TOTAL DEL MES', 'Monto', '% del total'], true);
    $desglose = [
        ['Incidencias · refacciones internas (consumo)', (float) $resumen['interno']],
        ['Incidencias · proveedores (mano de obra + materiales)', (float) $resumen['externo']],
        ['Refacciones compradas', (float) $adq_refacc['total']],
        ['Equipos comprados', (float) $adq_equipos['total']],
    ];
    foreach ($desglose as [$lbl, $val]) {
        $pct = $gran_total > 0 ? $val / $gran_total * 100 : 0;
        $xlsx->addRow([$lbl, $m($val), $pc($pct)]);
    }
    $xlsx->addRow(['TOTAL DEL MES', $m($gran_total), $pc(100)]);
    break;

case 'tendencia':
    $tendencia = costos_tendencia($desde, $hasta, $agrupar, $where_sucursal, $params_sucursal);
    $xlsx->addHeaderRow(['Período (' . $agrupar . ')', 'Externo (proveedores)', 'Interno (refacciones)', 'Total'], true);
    $te = 0.0; $ti = 0.0;
    foreach ($tendencia as $t) {
        $ext = (float) $t['externo']; $int = (float) $t['interno'];
        $te += $ext; $ti += $int;
        $xlsx->addRow([$t['label'], $m($ext), $m($int), $m($ext + $int)]);
    }
    $xlsx->addRow(['TOTAL', $m($te), $m($ti), $m($te + $ti)]);
    break;

case 'incidencias':
    $inc = costos_ranking_incidencias($desde, $hasta, 100000, $where_sucursal, $params_sucursal);
    $xlsx->addHeaderRow(['Fecha', 'Folio', 'Título', 'Sucursal', 'Atendió', 'Mano obra', 'Materiales', 'Refacciones', 'Mat. comprados', 'MO interna', 'Total'], true);
    foreach ($inc as $r) {
        $xlsx->addRow([
            date('Y-m-d', strtotime($r['fecha_evento'])),
            $r['folio'], $r['titulo'], $r['sucursal_nombre'],
            $r['proveedor_nombre'] ?: ($r['proveedor_externo_info'] ?: 'Interno'),
            $m($r['mano_obra']), $m($r['materiales']), $m($r['refacciones']),
            $m($r['materiales_comprados']), $m($r['mano_obra_interna']), $m($r['total']),
        ]);
    }
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', '', '', 'TOTAL',
        $m(array_sum(array_map(fn($x) => (float) $x['mano_obra'], $inc))),
        $m(array_sum(array_map(fn($x) => (float) $x['materiales'], $inc))),
        $m(array_sum(array_map(fn($x) => (float) $x['refacciones'], $inc))),
        $m(array_sum(array_map(fn($x) => (float) $x['materiales_comprados'], $inc))),
        $m(array_sum(array_map(fn($x) => (float) $x['mano_obra_interna'], $inc))),
        $m(array_sum(array_map(fn($x) => (float) $x['total'], $inc))),
    ]);
    break;

case 'proveedores':
    $prov = costos_ranking_proveedores($desde, $hasta, 500, $where_sucursal, $params_sucursal);
    $xlsx->addHeaderRow(['Proveedor', 'Servicio', 'Incidencias', 'Mano obra', 'Materiales', 'Total'], true);
    $t = 0.0;
    foreach ($prov as $p) {
        $t += (float) $p['total'];
        $xlsx->addRow([
            $p['nombre'], $p['servicio'] ?? '', (int) $p['num_incidencias'],
            $m($p['mano_obra']), $m($p['materiales']), $m($p['total']),
        ]);
    }
    $xlsx->addRow(['TOTAL', '', '', '', '', $m($t)]);
    break;

case 'flotilla':
    $flota = function_exists('flotilla_gasto_proveedores') ? flotilla_gasto_proveedores($desde, $hasta, '', 500) : [];
    $xlsx->addHeaderRow(['Proveedor / Taller', 'Servicios', 'Vehículos', 'Promedio', 'Total'], true);
    $t = 0.0;
    foreach ($flota as $pf) {
        $reg = (int) $pf['registros']; $t += (float) $pf['total'];
        $xlsx->addRow([
            $pf['proveedor'], $reg, (int) $pf['vehiculos'],
            $m($reg > 0 ? (float) $pf['total'] / $reg : 0), $m($pf['total']),
        ]);
    }
    $xlsx->addRow(['TOTAL FLOTILLA', '', '', '', $m($t)]);
    break;

case 'adquisiciones':
    $adq_refacc  = adquisiciones_refacciones($desde, $hasta, (int) $sucursal_filtro, 100000);
    $adq_equipos = adquisiciones_equipos($desde, $hasta, (int) $sucursal_filtro, 100000);
    $xlsx->addRow(['Incluye requisiciones recibidas (entradas de almacén tipo compra) + equipos comprados.']);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Compras de refacciones', 'Código', 'Piezas', 'Movimientos', 'Costo'], true);
    foreach ($adq_refacc['detalle'] as $ar) {
        $xlsx->addRow([$ar['nombre'], $ar['codigo'], ['v' => round((float) $ar['piezas'], 2), 's' => 7], (int) $ar['movimientos'], $m($ar['total'])]);
    }
    $xlsx->addRow(['Subtotal refacciones', '', '', '', $m($adq_refacc['total'])]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Equipos adquiridos', 'Código', 'Fecha compra', '', 'Costo'], true);
    foreach ($adq_equipos['detalle'] as $ae) {
        $xlsx->addRow([$ae['nombre'], $ae['codigo_inventario'], (string) $ae['fecha_compra'], '', $m($ae['costo_compra'])]);
    }
    $xlsx->addRow(['Subtotal equipos', '', '', '', $m($adq_equipos['total'])]);
    $xlsx->addBlankRow();
    $xlsx->addRow(['TOTAL ADQUISICIONES', '', '', '', $m((float) $adq_refacc['total'] + (float) $adq_equipos['total'])]);
    break;

case 'sucursales':
    $por_suc = costos_por_sucursal($desde, $hasta);
    $xlsx->addHeaderRow(['Sucursal', 'Incidencias', 'Externo', 'Interno', 'Total'], true);
    $te = 0.0; $ti = 0.0; $tt = 0.0;
    foreach ($por_suc as $s) {
        $te += (float) $s['externo']; $ti += (float) $s['interno']; $tt += (float) $s['total'];
        $xlsx->addRow([$s['nombre'], (int) $s['num_incidencias'], $m($s['externo']), $m($s['interno']), $m($s['total'])]);
    }
    $xlsx->addRow(['TOTAL', '', $m($te), $m($ti), $m($tt)]);
    break;
}

$xlsx->download('reporte_costos_' . $seccion . '_' . date('Ymd_His') . '.xlsx');
