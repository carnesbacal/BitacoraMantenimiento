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

// Celdas numéricas SUMABLES: la unidad va en el encabezado (km, L, km/L) o en el
// formato de moneda ($). El valor de cada celda es un número real.
$money = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // pesos "$"#,##0.00
$f_lts = fn($v) => ['v' => round((float) $v, 1), 's' => 6];   // litros #,##0.0
$f_km  = fn($v) => ['v' => (int) round((float) $v), 's' => 2]; // km #,##0
$f_kml = fn($v) => ['v' => round((float) $v, 2), 's' => 7];   // km/L
$f_pkm = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // $/km (moneda)
$f_pl  = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // $/L (moneda)
$f_pct = fn($v) => ['v' => round((float) $v, 1), 's' => 8];   // porcentaje

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

// 6. Rendimiento y km por unidad (odómetro manual, sin GPS) — km robusto por avances.
$km_map = flotilla_km_recorridos_periodo($desde, $hasta, $f_suc);
$rendimiento = db_all(
    "SELECT v.id, v.placas, COALESCE(v.alias,'') alias, CONCAT(v.marca,' ',v.modelo) modelo,
            COUNT(c.id) cargas, ROUND(SUM(c.litros),1) litros,
            COALESCE(SUM(c.litros * c.precio_litro),0) costo_comb,
            COALESCE(SUM(c.km_recorridos),0) km_cargas
     FROM flotilla_vehiculos v
     INNER JOIN flotilla_combustible c ON c.vehiculo_id = v.id AND DATE(c.fecha) BETWEEN :desde AND :hasta
     WHERE 1 $suf
     GROUP BY v.id
     HAVING cargas >= 1",
    ['desde' => $desde, 'hasta' => $hasta]
);
foreach ($rendimiento as &$r) {
    $km = (int) ($km_map[(int) $r['id']] ?? 0);
    if ($km <= 0) $km = (int) $r['km_cargas'];   // respaldo: km capturado por carga
    $r['km_rec'] = $km;
}
unset($r);
usort($rendimiento, fn($a, $b) => ((int) $b['km_rec'] <=> (int) $a['km_rec']));
$flota_km    = array_sum(array_map(fn($r) => (int) $r['km_rec'], $rendimiento));
$flota_litros= array_sum(array_map(fn($r) => (float) $r['litros'], $rendimiento));
$flota_comb  = array_sum(array_map(fn($r) => (float) $r['costo_comb'], $rendimiento));

// ── Construir XLSX ───────────────────────────────────────────────────────────

$xlsx = new XlsxWriter();

$periodo_label = "Período: {$desde} al {$hasta}";
$gen_label     = "Generado: " . date('d/m/Y H:i') . " por {$u['nombre']}";

// ── Hoja 1: Resumen ─────────────────────────────────────────────────────────
$xlsx->addSheet('Resumen');
$xlsx->setPageSetup(1, 1);
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
$xlsx->addRow(['Gasto total del período',           $money($total_gastos)]);
$xlsx->addRow(['Gasto en combustible',              $money($total_comb)]);
$xlsx->addRow(['Gasto en mantenimiento',            $money($total_mant)]);
$xlsx->addRow(['Litros de combustible cargados (L)', $f_lts($total_litros)]);
$xlsx->addRow(['Cargas de combustible registradas', count($combustible)]);
$xlsx->addRow(['Servicios de mantenimiento',        count($mantenimientos)]);
$xlsx->addRow(['Vehículos con actividad',           count($por_vehiculo)]);
$xlsx->addRow(['Km recorridos (km, capturas manuales)', $flota_km > 0 ? $f_km($flota_km) : '—']);
$xlsx->addRow(['Rendimiento de flota (km/L)',        ($flota_km > 0 && $flota_litros > 0) ? $f_kml($flota_km / $flota_litros) : '—']);
$xlsx->addRow(['Costo por km (combustible, $/km)',   $flota_km > 0 ? $f_pkm($flota_comb / $flota_km) : '—']);
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
    $xlsx->addRow([$cat, $money($d['total']), $f_pct($pct), $d['registros']]);
}

// Gasto por mes
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['GASTO POR MES'], 5);
$xlsx->addHeaderRow(['Mes', 'Combustible', 'Mantenimiento', 'Total'], 1);
foreach (db_all(
    "SELECT DATE_FORMAT(g.fecha,'%Y-%m') periodo, SUM(g.monto) total,
            SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto ELSE 0 END) comb,
            SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto ELSE 0 END) mant
       FROM flotilla_gastos g
       INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
       INNER JOIN flotilla_vehiculos v ON g.vehiculo_id = v.id
      WHERE g.fecha BETWEEN :d AND :h $suf GROUP BY periodo ORDER BY periodo",
    ['d' => $desde, 'h' => $hasta]) as $tm) {
    $xlsx->addRow([$tm['periodo'], $money($tm['comb']), $money($tm['mant']), $money($tm['total'])]);
}

// ── Hoja 2: Por vehículo ─────────────────────────────────────────────────────
$xlsx->addSheet('Por Vehículo');
$xlsx->addHeaderRow(['GASTO POR VEHÍCULO'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Placas', 'Alias', 'Modelo', 'Km actual (km)', 'Gasto total ($)', 'Combustible ($)', 'Mantenimiento ($)', 'Multas ($)', 'Registros'], 1);
foreach ($por_vehiculo as $vg) {
    $xlsx->addRow([
        $vg['placas'], $vg['alias'], $vg['modelo'],
        (int)$vg['km_actual'] > 0 ? $f_km($vg['km_actual']) : '',
        $money($vg['gasto_total']),
        $money($vg['combustible']),
        $money($vg['mantenimiento']),
        $money($vg['multas']),
        (int)$vg['registros'],
    ]);
}
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['', '', '', 'TOTALES', $money(array_sum(array_column($por_vehiculo, 'gasto_total')))], 1);

// ── Hoja: Rendimiento por unidad ─────────────────────────────────────────────
$xlsx->addSheet('Rendimiento x Unidad');
$xlsx->addHeaderRow(['RENDIMIENTO Y KILOMETRAJE POR UNIDAD'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addRow(['Km recorridos = odómetro (suma de avances consecutivos plausibles) capturado a mano · sin GPS']);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Placas', 'Alias', 'Modelo', 'Cargas', 'Litros (L)', 'Km recorridos (km)', 'Rendimiento (km/L)', 'Precio prom. ($/L)', 'Costo comb. ($)', 'Costo/km ($/km)'], 1);
$tk = 0; $tl = 0.0; $tc = 0.0;
foreach ($rendimiento as $r) {
    $km  = (int) $r['km_rec'];
    $lit = (float) $r['litros'];
    $cc  = (float) $r['costo_comb'];
    $tk += $km; $tl += $lit; $tc += $cc;
    $xlsx->addRow([
        $r['placas'], $r['alias'], $r['modelo'],
        (int) $r['cargas'], $f_lts($lit),
        $km > 0 ? $f_km($km) : '',
        ($km > 0 && $lit > 0) ? $f_kml($km / $lit) : '',
        $lit > 0 ? $f_pl($cc / $lit) : '',
        $money($cc),
        $km > 0 ? $f_pkm($cc / $km) : '',
    ]);
}
$xlsx->addBlankRow();
$xlsx->addRow([
    '', '', 'TOTALES / FLOTA', '', $f_lts($tl),
    $tk > 0 ? $f_km($tk) : '',
    ($tk > 0 && $tl > 0) ? $f_kml($tk / $tl) : '',
    '', $money($tc),
    $tk > 0 ? $f_pkm($tc / $tk) : '',
]);

// ── Hoja 3: Gastos detallados ────────────────────────────────────────────────
$xlsx->addSheet('Gastos');
$xlsx->addHeaderRow(['DETALLE DE GASTOS'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Vehículo', 'Categoría', 'Concepto', 'Monto ($)', 'Proveedor', 'Factura', 'Km (km)'], 1);
foreach ($gastos as $g) {
    $xlsx->addRow([
        $g['fecha'],
        $g['placas'],
        $g['alias'],
        trim($g['marca'] . ' ' . $g['modelo']),
        $g['categoria'],
        $g['concepto'],
        $money($g['monto']),
        $g['proveedor'],
        $g['factura'],
        (int)$g['km'] > 0 ? $f_km($g['km']) : '',
    ]);
}
$xlsx->addBlankRow();
$xlsx->addRow(['', '', '', '', '', 'TOTAL', $money(array_sum(array_column($gastos, 'monto')))]);

// ── Hoja 4: Combustible ──────────────────────────────────────────────────────
$xlsx->addSheet('Combustible');
$xlsx->addHeaderRow(['REGISTRO DE COMBUSTIBLE'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Litros (L)', 'Precio/L ($/L)', 'Total ($)', 'Tipo', 'Estación', 'Km odómetro (km)', 'Km recorridos (km)', 'Rend. (km/L)', 'Conductor'], 1);
foreach ($combustible as $c) {
    $xlsx->addRow([
        (string)$c['fecha'],
        $c['placas'],
        $c['alias'],
        $f_lts($c['litros']),
        $f_pl($c['precio_litro']),
        $money($c['total']),
        $c['tipo_combustible'],
        $c['estacion'],
        (int)$c['km_odometro'] > 0 ? $f_km($c['km_odometro']) : '',
        (int)$c['km_recorridos'] > 0 ? $f_km($c['km_recorridos']) : '',
        $c['kml'] > 0 ? $f_kml($c['kml']) : '',
        $c['conductor'],
    ]);
}
if (!empty($combustible)) {
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', 'TOTALES',
        $f_lts(array_sum(array_column($combustible, 'litros'))),
        '',
        $money(array_sum(array_column($combustible, 'total'))),
    ]);
}

// ── Hoja 5: Mantenimiento ────────────────────────────────────────────────────
$xlsx->addSheet('Mantenimiento');
$xlsx->addHeaderRow(['HISTORIAL DE MANTENIMIENTO'], 5);
$xlsx->addRow([$periodo_label]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Fecha', 'Placas', 'Alias', 'Servicio', 'Taller', 'Técnico', 'Costo ($)', 'Km odómetro (km)', 'No. orden', 'Próxima fecha', 'Próximo km (km)'], 1);
foreach ($mantenimientos as $m) {
    $xlsx->addRow([
        (string)$m['fecha'],
        $m['placas'],
        $m['alias'],
        $m['servicio'],
        $m['taller'],
        $m['tecnico'],
        $m['costo'] > 0 ? $money($m['costo']) : '',
        (int)$m['km_odometro'] > 0 ? $f_km($m['km_odometro']) : '',
        $m['orden'],
        $m['proxima_fecha'] ?: '',
        $m['proximo_km'] > 0 ? $f_km($m['proximo_km']) : '',
    ]);
}
if (!empty($mantenimientos)) {
    $xlsx->addBlankRow();
    $xlsx->addRow(['', '', '', '', '', 'TOTAL',
        $money(array_sum(array_column($mantenimientos, 'costo'))),
    ]);
}

// ── Hoja 6: Documentos ──────────────────────────────────────────────────────
$xlsx->addSheet('Documentos');
$xlsx->addHeaderRow(['DOCUMENTOS VEHICULARES Y DE CONDUCTORES'], 5);
$xlsx->addRow(['Generado: ' . date('d/m/Y H:i')]);
$xlsx->addBlankRow();
$xlsx->addHeaderRow(['Tipo', 'Placas', 'Alias', 'Conductor', 'No. documento', 'Proveedor', 'Inicio', 'Vencimiento', 'Estado', 'Monto ($)'], 1);
foreach ($documentos as $d) {
    $xlsx->addRow([
        $d['tipo'], $d['placas'], $d['alias'], $d['conductor'],
        $d['numero'], $d['proveedor'],
        $d['inicio'] ?: '', $d['vence'] ?: '',
        $d['estado'],
        $d['monto'] > 0 ? $money($d['monto']) : '',
    ]);
}

// ── Hoja: Viajes ─────────────────────────────────────────────────────────────
if (db_one("SHOW TABLES LIKE 'flotilla_viajes'")) {
    $xlsx->addSheet('Viajes');
    $xlsx->setPageSetup(1, 1);
    $xlsx->addHeaderRow(['VIAJES DE LA FLOTA'], 5);
    $xlsx->addRow([$periodo_label]);
    $xlsx->addBlankRow();
    $km_sql = "SUM(CASE WHEN t.km_llegada IS NOT NULL AND t.km_llegada >= t.km_salida THEN t.km_llegada - t.km_salida ELSE 0 END)";
    $xlsx->addHeaderRow(['KM RECORRIDOS POR UNIDAD'], 5);
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Viajes', 'Km (km)'], 1);
    foreach (db_all(
        "SELECT v.alias, v.placas, v.marca, v.modelo, COUNT(*) num_viajes, $km_sql km
           FROM flotilla_viajes t INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
          WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :d AND :h $suf
          GROUP BY v.id HAVING km > 0 ORDER BY km DESC",
        ['d' => $desde, 'h' => $hasta]) as $r) {
        $xlsx->addRow([trim(($r['alias'] ? $r['alias'] . ' · ' : '') . $r['marca'] . ' ' . $r['modelo']), $r['placas'], (int) $r['num_viajes'], $f_km($r['km'])]);
    }
    if (flotilla_viajes_col('nombre') && (bool) db_one("SHOW TABLES LIKE 'flotilla_viaje_clientes'")) {
        $rep = "COALESCE(c.nombre_completo, NULLIF(t.repartidor_nombre, ''), 'Sin repartidor')";
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['VIAJES POR REPARTIDOR'], 5);
        $xlsx->addHeaderRow(['Repartidor', 'Viajes', 'Entregas', 'Km (km)'], 1);
        foreach (db_all(
            "SELECT $rep repartidor, COUNT(*) num_viajes, $km_sql km,
                    COALESCE(SUM((SELECT COUNT(*) FROM flotilla_viaje_clientes cl WHERE cl.viaje_id = t.id)),0) entregas
               FROM flotilla_viajes t INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
               LEFT JOIN flotilla_conductores c ON t.conductor_id = c.id
              WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :d AND :h $suf
              GROUP BY repartidor ORDER BY num_viajes DESC",
            ['d' => $desde, 'h' => $hasta]) as $r) {
            $xlsx->addRow([$r['repartidor'], (int) $r['num_viajes'], (int) $r['entregas'], $f_km($r['km'])]);
        }
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['CLIENTES MÁS ATENDIDOS'], 5);
        $xlsx->addHeaderRow(['Cliente', 'Entregas'], 1);
        foreach (db_all(
            "SELECT cl.cliente, COUNT(*) entregas FROM flotilla_viaje_clientes cl
               INNER JOIN flotilla_viajes t ON cl.viaje_id = t.id
               INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
              WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :d AND :h $suf
              GROUP BY cl.cliente ORDER BY entregas DESC",
            ['d' => $desde, 'h' => $hasta]) as $r) {
            $xlsx->addRow([$r['cliente'], (int) $r['entregas']]);
        }
    }
}

// ── Hoja: Anomalías ──────────────────────────────────────────────────────────
if (db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) {
    $xlsx->addSheet('Anomalías');
    $xlsx->setPageSetup(1, 1);
    $xlsx->addHeaderRow(['ANOMALÍAS DETECTADAS (odómetro + combustible, sin GPS)'], 5);
    $xlsx->addRow([$periodo_label]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Anomalía', 'Detalle'], 1);
    foreach (db_all(
        "SELECT v.alias, v.placas, v.marca, v.modelo,
            (SELECT (MAX(h.km) - MIN(h.km)) FROM flotilla_odometro_historial h
              WHERE h.vehiculo_id = v.id AND DATE(h.leido_en) BETWEEN :d AND :h AND (h.origen IS NULL OR h.origen <> 'gps')) km,
            (SELECT COALESCE(SUM(cc.litros),0) FROM flotilla_combustible cc
              WHERE cc.vehiculo_id = v.id AND DATE(cc.fecha) BETWEEN :d2 AND :h2) litros
         FROM flotilla_vehiculos v WHERE v.activo = 1 $suf ORDER BY v.alias",
        ['d' => $desde, 'h' => $hasta, 'd2' => $desde, 'h2' => $hasta]) as $ra) {
        $km = (float) ($ra['km'] ?? 0); $lt = (float) $ra['litros'];
        $uni = trim(($ra['alias'] ? $ra['alias'] . ' · ' : '') . $ra['marca'] . ' ' . $ra['modelo']);
        if ($km > 300 && $lt <= 0) $xlsx->addRow([$uni, $ra['placas'], 'Km sin combustible', 'Recorrió ' . number_format($km) . ' km sin cargas registradas.']);
        if ($lt > 5 && $km <= 0)   $xlsx->addRow([$uni, $ra['placas'], 'Combustible sin km', 'Cargó ' . number_format($lt, 0) . ' L sin avance de odómetro.']);
        if ($km > 0 && $lt > 0) { $rr = $km / $lt; if ($rr < 1.5 || $rr > 30) $xlsx->addRow([$uni, $ra['placas'], 'Rendimiento fuera de rango', number_format($rr, 1) . ' km/L (' . number_format($km) . ' km / ' . number_format($lt, 0) . ' L).']); }
    }
}

// ── Descargar ────────────────────────────────────────────────────────────────
$filename = 'flotilla_reporte_' . str_replace('-', '', $desde) . '_' . str_replace('-', '', $hasta) . '.xlsx';
$xlsx->download($filename);
