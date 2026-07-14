<?php
/**
 * ============================================================================
 * flotilla_reportes_export.php - Exportar reporte de flotilla a Excel (.xlsx)
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';
require_once __DIR__ . '/config/xlsx_writer.php';

requerir_login();
$u = usuario_actual();

$hoy   = date('Y-m-d');
$desde = trim((string) input('desde', date('Y-m-01')));
$hasta = trim((string) input('hasta', $hoy));
$f_suc = (int) input('sucursal_id', 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta))  $hasta = $hoy;
if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_suc = (int) $u['sucursal_id'];
}

$suf = $f_suc ? " AND v.sucursal_id = {$f_suc}" : '';

// ── Datos ────────────────────────────────────────────────────────────────────

// 1. Gastos detallados
$gastos = db_all(
    "SELECT g.fecha, v.placas, COALESCE(v.alias,'') alias, v.marca, v.modelo,
            cat.nombre categoria, g.concepto, g.monto, COALESCE(g.proveedor,'') proveedor,
            COALESCE(g.numero_factura,'') factura, COALESCE(g.km_odometro,0) km
     FROM flotilla_gastos g
     INNER JOIN flotilla_vehiculos v         ON g.vehiculo_id = v.id
     INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
     WHERE g.fecha BETWEEN :desde AND :hasta $suf
     ORDER BY g.fecha DESC, v.placas",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 2. Resumen por vehículo
$por_vehiculo = db_all(
    "SELECT v.placas, COALESCE(v.alias,'') alias, CONCAT(v.marca,' ',v.modelo) modelo, v.km_actual,
            COALESCE(SUM(g.monto),0) gasto_total,
            COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto END),0) combustible,
            COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto END),0) mantenimiento,
            COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Multa%' THEN g.monto END),0) multas,
            COUNT(DISTINCT g.id) registros
     FROM flotilla_vehiculos v
     LEFT JOIN flotilla_gastos g               ON g.vehiculo_id = v.id AND g.fecha BETWEEN :desde AND :hasta
     LEFT JOIN flotilla_categorias_gasto cat   ON g.categoria_id = cat.id
     WHERE v.activo = 1 $suf
     GROUP BY v.id
     HAVING gasto_total > 0
     ORDER BY gasto_total DESC",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 3. Combustible detallado
$combustible = db_all(
    "SELECT DATE(c.fecha) fecha, v.placas, COALESCE(v.alias,'') alias,
            c.litros, c.precio_litro, ROUND(c.litros * c.precio_litro,2) total,
            c.tipo_combustible, COALESCE(c.estacion,'') estacion,
            c.km_odometro, COALESCE(c.km_recorridos,0) km_recorridos,
            COALESCE(c.rendimiento_kml,0) kml,
            COALESCE(co.nombre_completo,'') conductor
     FROM flotilla_combustible c
     INNER JOIN flotilla_vehiculos v      ON c.vehiculo_id = v.id
     LEFT  JOIN flotilla_conductores co   ON c.conductor_id = co.id
     WHERE DATE(c.fecha) BETWEEN :desde AND :hasta $suf
     ORDER BY c.fecha DESC",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 4. Mantenimientos
$mantenimientos = db_all(
    "SELECT h.fecha, v.placas, COALESCE(v.alias,'') alias,
            h.nombre servicio, COALESCE(h.taller,'') taller,
            COALESCE(h.tecnico,'') tecnico, COALESCE(h.costo,0) costo,
            h.km_odometro, COALESCE(h.numero_orden,'') orden,
            COALESCE(h.proxima_fecha,'') proxima_fecha,
            COALESCE(h.proximo_km,0) proximo_km
     FROM flotilla_mant_historial h
     INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
     WHERE h.fecha BETWEEN :desde AND :hasta $suf
     ORDER BY h.fecha DESC",
    ['desde' => $desde, 'hasta' => $hasta]
);

// 5. Documentos
$documentos = db_all(
    "SELECT t.nombre tipo, COALESCE(v.placas,'') placas, COALESCE(v.alias,'') alias,
            COALESCE(co.nombre_completo,'') conductor,
            COALESCE(d.numero_documento,'') numero, COALESCE(d.proveedor,'') proveedor,
            COALESCE(d.fecha_inicio,'') inicio, COALESCE(d.fecha_vence,'') vence,
            d.estado, COALESCE(d.monto,0) monto
     FROM flotilla_documentos d
     INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
     LEFT  JOIN flotilla_vehiculos v        ON d.vehiculo_id = v.id
     LEFT  JOIN flotilla_conductores co     ON d.conductor_id = co.id
     ORDER BY FIELD(d.estado,'vencido','por_vencer','vigente','cancelado'), d.fecha_vence"
);

// 6. Rendimiento y km por unidad (odómetro manual, sin GPS).
$tiene_odo = (bool) db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'");
$join_odo  = $tiene_odo
    ? "LEFT JOIN (SELECT vehiculo_id, MAX(km) - MIN(km) km_periodo
                  FROM flotilla_odometro_historial
                  WHERE DATE(leido_en) BETWEEN :d2 AND :h2
                    AND (origen IS NULL OR origen <> 'gps')
                  GROUP BY vehiculo_id) o ON o.vehiculo_id = v.id"
    : "";
$km_expr  = $tiene_odo ? "COALESCE(MAX(o.km_periodo), SUM(c.km_recorridos), 0)"
                       : "COALESCE(SUM(c.km_recorridos), 0)";
$params_r = ['desde' => $desde, 'hasta' => $hasta];
if ($tiene_odo) { $params_r['d2'] = $desde; $params_r['h2'] = $hasta; }
$rendimiento = db_all(
    "SELECT v.placas, COALESCE(v.alias,'') alias, CONCAT(v.marca,' ',v.modelo) modelo,
            COUNT(c.id) cargas, ROUND(SUM(c.litros),1) litros,
            COALESCE(SUM(c.litros * c.precio_litro),0) costo_comb,
            $km_expr km_rec
     FROM flotilla_vehiculos v
     INNER JOIN flotilla_combustible c ON c.vehiculo_id = v.id AND DATE(c.fecha) BETWEEN :desde AND :hasta
     $join_odo
     WHERE 1 $suf
     GROUP BY v.id
     HAVING cargas >= 1
     ORDER BY km_rec DESC",
    $params_r
);
$flota_km    = array_sum(array_map(fn($r) => (int) $r['km_rec'], $rendimiento));
$flota_litros= array_sum(array_map(fn($r) => (float) $r['litros'], $rendimiento));
$flota_comb  = array_sum(array_map(fn($r) => (float) $r['costo_comb'], $rendimiento));

// ── Construir XLSX ───────────────────────────────────────────────────────────

$xlsx = new XlsxWriter();

$periodo_label = "Período: {$desde} al {$hasta}";
$gen_label     = "Generado: " . date('d/m/Y H:i') . " por {$u['nombre']}";

// ── Hoja 1: Resumen ─────────────────────────────────────────────────────────
$xlsx->addSheet('Resumen');
$xlsx->addHeaderRow(['REPORTE DE FLOTILLA VEHICULAR'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addRow([$gen_label]);
$xlsx->addBlankRow();

$total_gastos = array_sum(array_column($gastos, 'monto'));
$total_litros = array_sum(array_column($combustible, 'litros'));
$total_comb   = array_sum(array_column($combustible, 'total'));
$total_mant   = array_sum(array_column($mantenimientos, 'costo'));

$xlsx->addHeaderRow(['Indicador', 'Valor'], 1);
$xlsx->addRow(['Total registros de gasto',          count($gastos)]);
$xlsx->addRow(['Gasto total del período',           $total_gastos]);
$xlsx->addRow(['Gasto en combustible',              $total_comb]);
$xlsx->addRow(['Gasto en mantenimiento',            $total_mant]);
$xlsx->addRow(['Litros de combustible cargados',    round($total_litros, 2)]);
$xlsx->addRow(['Cargas de combustible registradas', count($combustible)]);
$xlsx->addRow(['Servicios de mantenimiento',        count($mantenimientos)]);
$xlsx->addRow(['Vehículos con actividad',           count($por_vehiculo)]);
$xlsx->addRow(['Km recorridos (capturas manuales)',  $flota_km]);
$xlsx->addRow(['Rendimiento de flota (km/L)',        ($flota_km > 0 && $flota_litros > 0) ? round($flota_km / $flota_litros, 2) : '']);
$xlsx->addRow(['Costo por km (combustible)',         $flota_km > 0 ? round($flota_comb / $flota_km, 2) : '']);
$xlsx->addBlankRow();

// Resumen por categoría
$xlsx->addHeaderRow(['RESUMEN POR CATEGORÍA'], 5);
$xlsx->addHeaderRow(['Categoría', 'Total', '% del gasto', 'Registros'], 1);
$por_cat = [];
foreach ($gastos as $g) {
    $c = $g['categoria'];
    $por_cat[$c]['total']      = ($por_cat[$c]['total'] ?? 0) + $g['monto'];
    $por_cat[$c]['registros']  = ($por_cat[$c]['registros'] ?? 0) + 1;
}
arsort($por_cat);
foreach ($por_cat as $cat => $d) {
    $pct = $total_gastos > 0 ? round($d['total'] / $total_gastos * 100, 1) : 0;
    $xlsx->addRow([$cat, $d['total'], $pct . '%', $d['registros']]);
}

// ── Hoja 2: Por vehículo ─────────────────────────────────────────────────────
$xlsx->addSheet('Por Vehículo');
$xlsx->addHeaderRow(['GASTO POR VEHÍCULO'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Placas', 'Alias', 'Modelo', 'Km actual', 'Gasto total', 'Combustible', 'Mantenimiento', 'Multas', 'Registros'], 1);
foreach ($por_vehiculo as $vg) {
    $xlsx->addRow([
        $vg['placas'], $vg['alias'], $vg['modelo'],
        (int)$vg['km_actual'],
        (float)$vg['gasto_total'],
        (float)$vg['combustible'],
        (float)$vg['mantenimiento'],
        (float)$vg['multas'],
        (int)$vg['registros'],
    ]);
}
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['', '', '', 'TOTALES', array_sum(array_column($por_vehiculo, 'gasto_total'))], 1);

// ── Hoja: Rendimiento por unidad ─────────────────────────────────────────────
$xlsx->addSheet('Rendimiento x Unidad');
$xlsx->addHeaderRow(['RENDIMIENTO Y KILOMETRAJE POR UNIDAD'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addRow(['Km recorridos = odómetro (máx - mín) capturado a mano en el período · sin GPS']);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Placas', 'Alias', 'Modelo', 'Cargas', 'Litros', 'Km recorridos', 'Km/L', '$/L prom', 'Costo comb.', 'Costo/km'], 1);
$tk = 0; $tl = 0.0; $tc = 0.0;
foreach ($rendimiento as $r) {
    $km  = (int) $r['km_rec'];
    $lit = (float) $r['litros'];
    $cc  = (float) $r['costo_comb'];
    $tk += $km; $tl += $lit; $tc += $cc;
    $xlsx->addRow([
        $r['placas'], $r['alias'], $r['modelo'],
        (int) $r['cargas'], $lit,
        $km > 0 ? $km : '',
        ($km > 0 && $lit > 0) ? round($km / $lit, 2) : '',
        $lit > 0 ? round($cc / $lit, 2) : '',
        $cc,
        $km > 0 ? round($cc / $km, 2) : '',
    ]);
}
$xlsx->addBlankRow();
$xlsx->addRow([
    '', '', 'TOTALES / FLOTA', '', round($tl, 1),
    $tk > 0 ? $tk : '',
    ($tk > 0 && $tl > 0) ? round($tk / $tl, 2) : '',
    '', round($tc, 2),
    $tk > 0 ? round($tc / $tk, 2) : '',
]);

// ── Hoja 3: Gastos detallados ────────────────────────────────────────────────
$xlsx->addSheet('Gastos');
$xlsx->addHeaderRow(['DETALLE DE GASTOS'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Vehículo', 'Categoría', 'Concepto', 'Monto', 'Proveedor', 'Factura', 'Km'], 1);
foreach ($gastos as $g) {
    $xlsx->addRow([
        $g['fecha'],
        $g['placas'],
        $g['alias'],
        trim($g['marca'] . ' ' . $g['modelo']),
        $g['categoria'],
        $g['concepto'],
        (float)$g['monto'],
        $g['proveedor'],
        $g['factura'],
        (int)$g['km'],
    ]);
}
$xlsx->addBlankRow();
$xlsx->addRow(['', '', '', '', '', 'TOTAL', array_sum(array_column($gastos, 'monto'))]);

// ── Hoja 4: Combustible ──────────────────────────────────────────────────────
$xlsx->addSheet('Combustible');
$xlsx->addHeaderRow(['REGISTRO DE COMBUSTIBLE'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Litros', 'Precio/L', 'Total', 'Tipo', 'Estación', 'Km odómetro', 'Km recorridos', 'Rend. km/L', 'Conductor'], 1);
foreach ($combustible as $c) {
    $xlsx->addRow([
        (string)$c['fecha'],
        $c['placas'],
        $c['alias'],
        (float)$c['litros'],
        (float)$c['precio_litro'],
        (float)$c['total'],
        $c['tipo_combustible'],
        $c['estacion'],
        (int)$c['km_odometro'],
        (int)$c['km_recorridos'],
        $c['kml'] > 0 ? (float)$c['kml'] : '',
        $c['conductor'],
    ]);
}
if (!empty($combustible)) {
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', 'TOTALES',
        round(array_sum(array_column($combustible, 'litros')), 3),
        '',
        round(array_sum(array_column($combustible, 'total')), 2),
    ]);
}

// ── Hoja 5: Mantenimiento ────────────────────────────────────────────────────
$xlsx->addSheet('Mantenimiento');
$xlsx->addHeaderRow(['HISTORIAL DE MANTENIMIENTO'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Servicio', 'Taller', 'Técnico', 'Costo', 'Km odómetro', 'No. orden', 'Próxima fecha', 'Próximo km'], 1);
foreach ($mantenimientos as $m) {
    $xlsx->addRow([
        (string)$m['fecha'],
        $m['placas'],
        $m['alias'],
        $m['servicio'],
        $m['taller'],
        $m['tecnico'],
        $m['costo'] > 0 ? (float)$m['costo'] : '',
        (int)$m['km_odometro'],
        $m['orden'],
        $m['proxima_fecha'] ?: '',
        $m['proximo_km'] > 0 ? (int)$m['proximo_km'] : '',
    ]);
}
if (!empty($mantenimientos)) {
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', '', '', '', 'TOTAL',
        round(array_sum(array_column($mantenimientos, 'costo')), 2),
    ]);
}

// ── Hoja 6: Documentos ──────────────────────────────────────────────────────
$xlsx->addSheet('Documentos');
$xlsx->addHeaderRow(['DOCUMENTOS VEHICULARES Y DE CONDUCTORES'], 5);
$xlsx->addRow(['Generado: ' . date('d/m/Y H:i')]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Tipo', 'Placas', 'Alias', 'Conductor', 'No. documento', 'Proveedor', 'Inicio', 'Vencimiento', 'Estado', 'Monto'], 1);
foreach ($documentos as $d) {
    $xlsx->addRow([
        $d['tipo'], $d['placas'], $d['alias'], $d['conductor'],
        $d['numero'], $d['proveedor'],
        $d['inicio'] ?: '', $d['vence'] ?: '',
        $d['estado'],
        $d['monto'] > 0 ? (float)$d['monto'] : '',
    ]);
}

// ── Descargar ────────────────────────────────────────────────────────────────
$filename = 'flotilla_reporte_' . str_replace('-', '', $desde) . '_' . str_replace('-', '', $hasta) . '.xlsx';
$xlsx->download($filename);
