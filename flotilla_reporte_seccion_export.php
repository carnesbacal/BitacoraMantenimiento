<?php
/**
 * ============================================================================
 * flotilla_reporte_seccion_export.php
 * Exporta UNA sección del reporte de flotilla a Excel, con los filtros de fecha
 * actuales, en formato listo para imprimir (ajustado a una hoja).
 *   ?seccion=gasto_mes|gasto_categoria|rendimiento|uso_flota|proveedores|anomalias|viajes
 *   &desde=YYYY-MM-DD&hasta=YYYY-MM-DD&sucursal_id=ID
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
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta))  $hasta = $hoy;
if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

$f_suc = (int) input('sucursal_id', 0);
if (!tiene_permiso('ver_todas_sucursales')) $f_suc = (int) $u['sucursal_id'];
$suc_filter_gastos = $f_suc ? " AND v.sucursal_id = {$f_suc}" : '';

$secciones = [
    'resumen'         => 'Resumen del período',
    'gasto_mes'       => 'Gasto por mes',
    'gasto_categoria' => 'Gasto por categoría',
    'gasto_vehiculo'  => 'Gasto por vehículo',
    'rendimiento'     => 'Rendimiento y kilometraje por unidad',
    'uso_flota'       => 'Uso de la flota (km recorridos)',
    'proveedores'     => 'Proveedores de flotilla',
    'anomalias'       => 'Anomalías detectadas',
    'viajes'          => 'Viajes de la flota',
];
$seccion = (string) input('seccion', '');
if (!isset($secciones[$seccion])) die('Sección no válida.');

$suc_label = 'Todas las sucursales';
if ($f_suc) {
    $sr = db_one("SELECT nombre FROM sucursales WHERE id = :id", ['id' => $f_suc]);
    if ($sr) $suc_label = $sr['nombre'];
}
// Celdas numéricas SUMABLES: la unidad va en el encabezado (km, L, km/L) o en el
// formato de moneda ($). El valor de cada celda es un número real.
$dinero = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // pesos "$"#,##0.00
$f_lts  = fn($v) => ['v' => round((float) $v, 1), 's' => 6];   // litros #,##0.0
$f_km   = fn($v) => ['v' => (int) round((float) $v), 's' => 2]; // kilómetros #,##0
$f_kml  = fn($v) => ['v' => round((float) $v, 2), 's' => 7];   // rendimiento km/L
$f_pkm  = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // $/km (moneda)
$f_pl   = fn($v) => ['v' => round((float) $v, 2), 's' => 3];   // $/L (moneda)
$f_pct  = fn($v) => ['v' => round((float) $v, 1), 's' => 8];   // porcentaje
$f_int  = fn($v, $sing = '', $plur = null) => (int) $v;         // conteo (entero)

$xlsx = new XlsxWriter();
$xlsx->addSheet(mb_substr($secciones[$seccion], 0, 28));
$xlsx->setPageSetup(1, 1, 'landscape');   // ← todo en una sola hoja al imprimir
$xlsx->addHeaderRow([mb_strtoupper($secciones[$seccion])], true);
$xlsx->addRow(["Período: {$desde} al {$hasta}"]);
$xlsx->addRow(["Sucursal: {$suc_label}"]);
$xlsx->addRow(['Generado: ' . date('d/m/Y H:i') . (($u['nombre'] ?? '') ? ' por ' . $u['nombre'] : '')]);
$xlsx->addBlankRow();

// ---------------------------------------------------------------------------
switch ($seccion) {

case 'resumen':
    $k = db_one(
        "SELECT COALESCE(SUM(g.monto),0) total,
                COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto END),0) comb,
                COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto END),0) mant,
                COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Multa%' THEN g.monto END),0) multas,
                COUNT(DISTINCT g.vehiculo_id) vehiculos, COUNT(g.id) registros
           FROM flotilla_gastos g
           INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
           INNER JOIN flotilla_vehiculos v ON g.vehiculo_id = v.id
          WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos",
        ['desde' => $desde, 'hasta' => $hasta]) ?: [];
    $litros = (float) (db_one(
        "SELECT COALESCE(SUM(c.litros),0) l FROM flotilla_combustible c
           INNER JOIN flotilla_vehiculos v ON c.vehiculo_id = v.id
          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta $suc_filter_gastos",
        ['desde' => $desde, 'hasta' => $hasta])['l'] ?? 0);
    $km  = (int) array_sum(flotilla_km_recorridos_periodo($desde, $hasta, $f_suc));
    $gc  = (float) ($k['comb'] ?? 0);
    $kml = ($km > 0 && $litros > 0) ? $km / $litros : 0;
    $incompleto = ($km <= 0) || ($kml > 0 && ($kml < 0.5 || $kml > 40));
    $ckm = ($km > 0 && $gc > 0) ? $gc / $km : 0;
    $xlsx->addHeaderRow(['Indicador', 'Valor'], true);
    $xlsx->addRow(['Gasto total',                 $dinero($k['total'] ?? 0)]);
    $xlsx->addRow(['Combustible',                 $dinero($gc)]);
    $xlsx->addRow(['Mantenimiento',               $dinero($k['mant'] ?? 0)]);
    $xlsx->addRow(['Multas',                      $dinero($k['multas'] ?? 0)]);
    $xlsx->addRow(['Vehículos con gasto',         $f_int($k['vehiculos'] ?? 0, 'vehículo', 'vehículos')]);
    $xlsx->addRow(['Registros de gasto',          $f_int($k['registros'] ?? 0, 'registro', 'registros')]);
    $xlsx->addRow(['Litros cargados (L)',         $f_lts($litros)]);
    $xlsx->addRow(['Km recorridos (km)',          $incompleto ? '—' : $f_km($km)]);
    $xlsx->addRow(['Rendimiento promedio (km/L)', ($kml > 0 && !$incompleto) ? $f_kml($kml) : '—']);
    $xlsx->addRow(['Costo por km ($/km)',         ($ckm > 0 && !$incompleto) ? $f_pkm($ckm) : '—']);
    break;

case 'gasto_mes':
    $rows = db_all(
        "SELECT DATE_FORMAT(g.fecha,'%Y-%m') periodo, SUM(g.monto) total,
                SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto ELSE 0 END) comb,
                SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto ELSE 0 END) mant
           FROM flotilla_gastos g
           INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
           INNER JOIN flotilla_vehiculos v ON g.vehiculo_id = v.id
          WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos
          GROUP BY DATE_FORMAT(g.fecha,'%Y-%m') ORDER BY periodo",
        ['desde' => $desde, 'hasta' => $hasta]
    );
    $xlsx->addHeaderRow(['Mes', 'Combustible', 'Mantenimiento', 'Total'], true);
    $t = 0;
    foreach ($rows as $r) {
        $t += (float) $r['total'];
        $xlsx->addRow([$r['periodo'], $dinero($r['comb']), $dinero($r['mant']), $dinero($r['total'])]);
    }
    $xlsx->addRow(['TOTAL', '', '', $dinero($t)]);
    break;

case 'gasto_categoria':
    $rows = db_all(
        "SELECT cat.nombre, COALESCE(SUM(g.monto),0) total, COUNT(*) registros
           FROM flotilla_gastos g
           INNER JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
           INNER JOIN flotilla_vehiculos v ON g.vehiculo_id = v.id
          WHERE g.fecha BETWEEN :desde AND :hasta $suc_filter_gastos
          GROUP BY cat.id ORDER BY total DESC",
        ['desde' => $desde, 'hasta' => $hasta]
    );
    $tot = array_sum(array_map(fn($x) => (float) $x['total'], $rows)) ?: 1;
    $xlsx->addHeaderRow(['Categoría', 'Total', '% del gasto', 'Registros'], true);
    foreach ($rows as $r) {
        $xlsx->addRow([$r['nombre'], $dinero($r['total']), $f_pct((float) $r['total'] / $tot * 100), (int) $r['registros']]);
    }
    $xlsx->addRow(['TOTAL', $dinero($tot === 1 ? 0 : $tot), '', '']);
    break;

case 'gasto_vehiculo':
    $rows = db_all(
        "SELECT v.placas, v.alias, v.marca, v.modelo,
                COALESCE(SUM(g.monto),0) gasto_total,
                COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Combustible%' THEN g.monto END),0) comb,
                COALESCE(SUM(CASE WHEN cat.nombre LIKE '%Mantenimiento%' OR cat.nombre LIKE '%Refacc%' THEN g.monto END),0) mant,
                COUNT(DISTINCT g.id) num_registros
           FROM flotilla_vehiculos v
           LEFT JOIN flotilla_gastos g             ON g.vehiculo_id = v.id AND g.fecha BETWEEN :desde AND :hasta
           LEFT JOIN flotilla_categorias_gasto cat ON g.categoria_id = cat.id
          WHERE v.activo = 1 $suc_filter_gastos
          GROUP BY v.id HAVING gasto_total > 0 ORDER BY gasto_total DESC",
        ['desde' => $desde, 'hasta' => $hasta]
    );
    $tot = array_sum(array_map(fn($x) => (float) $x['gasto_total'], $rows)) ?: 1;
    $xlsx->addHeaderRow(['Vehículo', 'Placas', 'Total', 'Combustible', 'Mantenimiento', 'Registros', '% del total'], true);
    foreach ($rows as $r) {
        $xlsx->addRow([
            trim(($r['alias'] ? $r['alias'] . ' · ' : '') . $r['marca'] . ' ' . $r['modelo']),
            $r['placas'], $dinero($r['gasto_total']), $dinero($r['comb']), $dinero($r['mant']),
            (int) $r['num_registros'], $f_pct((float) $r['gasto_total'] / $tot * 100),
        ]);
    }
    $xlsx->addRow(['TOTAL', '', $dinero($tot === 1 ? 0 : $tot), '', '', '', '']);
    break;

case 'rendimiento':
    $km_map = flotilla_km_recorridos_periodo($desde, $hasta, $f_suc);
    $rows = db_all(
        "SELECT v.id, v.placas, v.alias, v.marca, v.modelo,
                COUNT(c.id) cargas, ROUND(SUM(c.litros),1) litros,
                COALESCE(SUM(c.litros * c.precio_litro),0) costo_comb,
                COALESCE(SUM(c.km_recorridos),0) km_cargas
           FROM flotilla_vehiculos v
           INNER JOIN flotilla_combustible c ON c.vehiculo_id = v.id AND DATE(c.fecha) BETWEEN :desde AND :hasta
          WHERE 1 $suc_filter_gastos GROUP BY v.id HAVING cargas >= 1",
        ['desde' => $desde, 'hasta' => $hasta]
    );
    foreach ($rows as &$r) {
        $km = (int) ($km_map[(int) $r['id']] ?? 0);
        if ($km <= 0) $km = (int) $r['km_cargas'];
        $r['km'] = $km;
        $r['kml'] = ($km > 0 && (float) $r['litros'] > 0) ? round($km / (float) $r['litros'], 2) : 0;
        $r['costo_km'] = $km > 0 ? round((float) $r['costo_comb'] / $km, 2) : 0;
        $r['sl'] = (float) $r['litros'] > 0 ? round((float) $r['costo_comb'] / (float) $r['litros'], 2) : 0;
    }
    unset($r);
    usort($rows, fn($a, $b) => ($b['kml'] <=> $a['kml']));
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Cargas', 'Litros (L)', 'Km recorridos (km)', 'Rendimiento (km/L)', 'Precio prom. ($/L)', 'Costo comb. ($)', 'Costo/km ($/km)'], true);
    foreach ($rows as $r) {
        $xlsx->addRow([
            trim(($r['alias'] ? $r['alias'] . ' · ' : '') . $r['marca'] . ' ' . $r['modelo']),
            $r['placas'], (int) $r['cargas'], $f_lts($r['litros']), $f_km($r['km']),
            $f_kml($r['kml']), $f_pl($r['sl']), $dinero($r['costo_comb']), $f_pkm($r['costo_km']),
        ]);
    }
    break;

case 'uso_flota':
    $km_map = flotilla_km_recorridos_periodo($desde, $hasta, $f_suc);
    $rows = db_all(
        "SELECT v.id, v.alias, v.placas, v.marca, v.modelo,
                (SELECT COALESCE(SUM(monto),0) FROM flotilla_gastos gx WHERE gx.vehiculo_id = v.id AND gx.fecha BETWEEN :d2 AND :h2) gasto
           FROM flotilla_vehiculos v WHERE v.activo = 1 $suc_filter_gastos",
        ['d2' => $desde, 'h2' => $hasta]
    );
    $lista = [];
    foreach ($rows as $r) {
        $km = (int) ($km_map[(int) $r['id']] ?? 0);
        if ($km <= 0) continue;
        $r['km'] = $km; $r['costo_km'] = $km > 0 ? round((float) $r['gasto'] / $km, 2) : 0;
        $lista[] = $r;
    }
    usort($lista, fn($a, $b) => ($b['km'] <=> $a['km']));
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Km recorridos (km)', 'Gasto total ($)', 'Costo / km ($/km)'], true);
    foreach ($lista as $r) {
        $xlsx->addRow([
            trim(($r['alias'] ? $r['alias'] . ' · ' : '') . $r['marca'] . ' ' . $r['modelo']),
            $r['placas'], $f_km($r['km']), $dinero($r['gasto']), $f_pkm($r['costo_km']),
        ]);
    }
    break;

case 'proveedores':
    $prov = function_exists('flotilla_gasto_proveedores')
        ? flotilla_gasto_proveedores($desde, $hasta, $suc_filter_gastos, 100) : [];
    $xlsx->addHeaderRow(['Proveedor / Taller', 'Servicios', 'Vehículos', 'Promedio', 'Total'], true);
    $t = 0;
    foreach ($prov as $p) {
        $reg = (int) $p['registros']; $t += (float) $p['total'];
        $xlsx->addRow([$p['proveedor'], $reg, (int) $p['vehiculos'],
            $dinero($reg > 0 ? (float) $p['total'] / $reg : 0), $dinero($p['total'])]);
    }
    $xlsx->addRow(['TOTAL', '', '', '', $dinero($t)]);
    break;

case 'anomalias':
    $xlsx->addRow(['Solo datos manuales (odómetro + combustible). Sin GPS.']);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Anomalía', 'Detalle'], true);
    if (db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) {
        $rows_a = db_all(
            "SELECT v.id, v.alias, v.placas, v.marca, v.modelo,
                (SELECT (MAX(h.km) - MIN(h.km)) FROM flotilla_odometro_historial h
                  WHERE h.vehiculo_id = v.id AND DATE(h.leido_en) BETWEEN :d AND :h AND (h.origen IS NULL OR h.origen <> 'gps')) km,
                (SELECT COALESCE(SUM(c.litros),0) FROM flotilla_combustible c
                  WHERE c.vehiculo_id = v.id AND DATE(c.fecha) BETWEEN :d2 AND :h2) litros
             FROM flotilla_vehiculos v WHERE v.activo = 1 $suc_filter_gastos ORDER BY v.alias",
            ['d' => $desde, 'h' => $hasta, 'd2' => $desde, 'h2' => $hasta]
        );
        foreach ($rows_a as $ra) {
            $km = (float) ($ra['km'] ?? 0); $lt = (float) $ra['litros'];
            $uni = trim(($ra['alias'] ? $ra['alias'] . ' · ' : '') . $ra['marca'] . ' ' . $ra['modelo']);
            if ($km > 300 && $lt <= 0)
                $xlsx->addRow([$uni, $ra['placas'], 'Km sin combustible', 'Recorrió ' . number_format($km) . ' km sin cargas registradas.']);
            if ($lt > 5 && $km <= 0)
                $xlsx->addRow([$uni, $ra['placas'], 'Combustible sin km', 'Cargó ' . number_format($lt, 0) . ' L sin avance de odómetro.']);
            if ($km > 0 && $lt > 0) {
                $rend = $km / $lt;
                if ($rend < 1.5 || $rend > 30)
                    $xlsx->addRow([$uni, $ra['placas'], 'Rendimiento fuera de rango', number_format($rend, 1) . ' km/L (' . number_format($km) . ' km / ' . number_format($lt, 0) . ' L).']);
            }
        }
    }
    break;

case 'viajes':
    if (!db_one("SHOW TABLES LIKE 'flotilla_viajes'")) { $xlsx->addRow(['Módulo de viajes no disponible.']); break; }
    $km_sql = "SUM(CASE WHEN t.km_llegada IS NOT NULL AND t.km_llegada >= t.km_salida THEN t.km_llegada - t.km_salida ELSE 0 END)";
    $v2 = flotilla_viajes_col('nombre') && (bool) db_one("SHOW TABLES LIKE 'flotilla_viaje_clientes'");

    // Por unidad
    $xlsx->addHeaderRow(['KM RECORRIDOS POR UNIDAD'], true);
    $xlsx->addHeaderRow(['Unidad', 'Placas', 'Viajes', 'Km (km)'], true);
    foreach (db_all(
        "SELECT v.alias, v.placas, v.marca, v.modelo, COUNT(*) num_viajes, $km_sql km
           FROM flotilla_viajes t INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
          WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :desde AND :hasta $suc_filter_gastos
          GROUP BY v.id HAVING km > 0 ORDER BY km DESC LIMIT 30",
        ['desde' => $desde, 'hasta' => $hasta]) as $r) {
        $xlsx->addRow([trim(($r['alias'] ? $r['alias'] . ' · ' : '') . $r['marca'] . ' ' . $r['modelo']), $r['placas'], (int) $r['num_viajes'], $f_km($r['km'])]);
    }

    if ($v2) {
        $rep_expr = "COALESCE(c.nombre_completo, NULLIF(t.repartidor_nombre, ''), 'Sin repartidor')";
        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['VIAJES POR REPARTIDOR'], true);
        $xlsx->addHeaderRow(['Repartidor', 'Viajes', 'Entregas', 'Km (km)'], true);
        foreach (db_all(
            "SELECT $rep_expr repartidor, COUNT(*) num_viajes, $km_sql km,
                    COALESCE(SUM((SELECT COUNT(*) FROM flotilla_viaje_clientes cl WHERE cl.viaje_id = t.id)),0) entregas
               FROM flotilla_viajes t INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
               LEFT JOIN flotilla_conductores c ON t.conductor_id = c.id
              WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :desde AND :hasta $suc_filter_gastos
              GROUP BY repartidor ORDER BY num_viajes DESC LIMIT 30",
            ['desde' => $desde, 'hasta' => $hasta]) as $r) {
            $xlsx->addRow([$r['repartidor'], (int) $r['num_viajes'], (int) $r['entregas'], $f_km($r['km'])]);
        }

        $xlsx->addBlankRow();
        $xlsx->addHeaderRow(['CLIENTES MÁS ATENDIDOS'], true);
        $xlsx->addHeaderRow(['Cliente', 'Entregas'], true);
        foreach (db_all(
            "SELECT cl.cliente, COUNT(*) entregas
               FROM flotilla_viaje_clientes cl
               INNER JOIN flotilla_viajes t ON cl.viaje_id = t.id
               INNER JOIN flotilla_vehiculos v ON t.vehiculo_id = v.id
              WHERE t.estado='completado' AND DATE(t.fecha_salida) BETWEEN :desde AND :hasta $suc_filter_gastos
              GROUP BY cl.cliente ORDER BY entregas DESC LIMIT 40",
            ['desde' => $desde, 'hasta' => $hasta]) as $r) {
            $xlsx->addRow([$r['cliente'], (int) $r['entregas']]);
        }
    }
    break;
}

$xlsx->download('flotilla_' . $seccion . '_' . date('Ymd_His') . '.xlsx');
