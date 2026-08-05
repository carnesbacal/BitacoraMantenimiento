<?php
/**
 * ============================================================================
 * flotilla_viajes.php - Viajes de toda la flota (vista global)
 * ============================================================================
 * Listado, KPIs y resumen de viajes de todas las unidades, con filtros y
 * cierre de viajes en ruta. Exporta a CSV y Excel.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');
$ver_todas       = tiene_permiso('ver_todas_sucursales');

$errores = [];

// ----------------------------------------------------------------------------
// Cerrar viaje en ruta
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif ((string) input('op') === 'viaje_cerrar') {
        $viaje_id = (int) input('viaje_id', 0);
        $km_ll    = (float) input('km_llegada', 0);
        $vj = db_one("SELECT id, vehiculo_id, km_salida FROM flotilla_viajes WHERE id = :id AND estado = 'en_ruta'", ['id' => $viaje_id]);
        if ($vj && $km_ll > 0) {
            $vid = (int) $vj['vehiculo_id'];
            db_exec("UPDATE flotilla_viajes SET km_llegada = :km, fecha_llegada = NOW(), estado = 'completado' WHERE id = :id",
                ['km' => $km_ll, 'id' => $viaje_id]);
            $veh = db_one("SELECT km_actual FROM flotilla_vehiculos WHERE id = :id", ['id' => $vid]);
            if ($veh && $km_ll > (int) $veh['km_actual']) {
                db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id AND km_actual < :km2",
                    ['km' => $km_ll, 'id' => $vid, 'km2' => $km_ll]);
                flotilla_odometro_registrar($vid, (int) round($km_ll), 'viaje', (int) $veh['km_actual'], $u['id']);
            }
            registrar_auditoria('cerrar_viaje', 'flotilla_viajes', $viaje_id, "Km llegada {$km_ll}");
            flash_set('exito', 'Viaje cerrado.');
        }
        header('Location: ' . url('flotilla_viajes.php?' . http_build_query(array_diff_key($_GET, ['op' => 1]))));
        exit;
    } elseif ((string) input('op') === 'viaje_eliminar') {
        $viaje_id = (int) input('viaje_id', 0);
        $res = flotilla_viaje_eliminar($viaje_id);
        if ($res['ok']) {
            registrar_auditoria('eliminar_viaje', 'flotilla_viajes', $viaje_id, '');
            flash_set('exito', 'Viaje eliminado.');
        } else {
            flash_set('error', 'No se pudo eliminar el viaje.');
        }
        header('Location: ' . url('flotilla_viajes.php?' . http_build_query(array_diff_key($_GET, ['op' => 1]))));
        exit;
    }
}

// ----------------------------------------------------------------------------
// Filtros
// ----------------------------------------------------------------------------
$hoy      = date('Y-m-d');
$f_desde  = trim((string) input('desde', date('Y-m-01')));
$f_hasta  = trim((string) input('hasta', $hoy));
$f_suc    = $ver_todas ? (int) input('sucursal_id', 0) : (int) $u['sucursal_id'];
$f_veh    = (int) input('vehiculo_id', 0);
$f_estado = trim((string) input('estado', ''));
$es_csv   = input('exportar') === 'csv';
$es_xlsx  = input('exportar') === 'xlsx';

$where  = ['1=1'];
$params = [];
// Viajes "en ruta" no se filtran por fecha (siguen abiertos); el resto sí por fecha de salida.
if ($f_estado === 'en_ruta') {
    $where[] = "v.estado = 'en_ruta'";
} elseif ($f_estado === 'completado') {
    $where[] = "v.estado = 'completado'";
    $where[] = 'DATE(v.fecha_salida) BETWEEN :d AND :h';
    $params['d'] = $f_desde; $params['h'] = $f_hasta;
} else {
    // Todos: en ruta (cualquier fecha) + completados del rango
    $where[] = "(v.estado = 'en_ruta' OR DATE(v.fecha_salida) BETWEEN :d AND :h)";
    $params['d'] = $f_desde; $params['h'] = $f_hasta;
}
if ($f_suc)  { $where[] = 've.sucursal_id = :suc'; $params['suc'] = $f_suc; }
if ($f_veh)  { $where[] = 'v.vehiculo_id = :veh'; $params['veh'] = $f_veh; }
$sql_where = implode(' AND ', $where);

$km_rec_expr = "(CASE WHEN v.km_llegada IS NOT NULL AND v.km_llegada >= v.km_salida
                     THEN v.km_llegada - v.km_salida ELSE NULL END)";

$viajes = db_all(
    "SELECT v.*, $km_rec_expr AS km_rec,
            ve.alias, ve.marca, ve.modelo, ve.placas,
            so.nombre AS suc_origen, sd.nombre AS suc_destino
       FROM flotilla_viajes v
       INNER JOIN flotilla_vehiculos ve ON v.vehiculo_id = ve.id
       LEFT  JOIN sucursales so ON v.sucursal_origen_id  = so.id
       LEFT  JOIN sucursales sd ON v.sucursal_destino_id = sd.id
      WHERE $sql_where
      ORDER BY (v.estado = 'en_ruta') DESC, v.fecha_salida DESC, v.id DESC
      LIMIT 1000",
    $params
);

// KPIs
$kpi_total   = count($viajes);
$kpi_ruta    = 0;
$kpi_km      = 0.0;
foreach ($viajes as $v) {
    if ($v['estado'] === 'en_ruta') $kpi_ruta++;
    $kpi_km += (float) ($v['km_rec'] ?? 0);
}

// ----------------------------------------------------------------------------
// Exportación
// ----------------------------------------------------------------------------
if ($es_csv) {
    csv_iniciar('flotilla_viajes_' . date('Ymd_His') . '.csv');
    csv_fila(['VIAJES DE FLOTA']);
    csv_fila(['Periodo:', "Del $f_desde al $f_hasta"]);
    csv_fila(['']);
    csv_fila(['Fecha salida', 'Unidad', 'Placas', 'Origen', 'Destino',
              'Km salida', 'Km llegada', 'Km recorridos', 'Propósito', 'Estado', 'Fecha llegada']);
    foreach ($viajes as $v) {
        csv_fila([
            $v['fecha_salida'],
            trim(($v['alias'] ? $v['alias'] . ' · ' : '') . $v['marca'] . ' ' . $v['modelo']),
            $v['placas'],
            $v['suc_origen'] ?? '',
            $v['suc_destino'] ?? ($v['destino_descripcion'] ?? ''),
            $v['km_salida'], $v['km_llegada'] ?? '', $v['km_rec'] ?? '',
            $v['proposito'] ?? '', $v['estado'], $v['fecha_llegada'] ?? '',
        ]);
    }
    exit;
}

if ($es_xlsx) {
    require_once __DIR__ . '/config/xlsx_writer.php';
    $xlsx = new XlsxWriter();
    $xlsx->addSheet('Viajes');
    $xlsx->addHeaderRow(['VIAJES DE FLOTA'], true);
    $xlsx->addRow(["Periodo: del $f_desde al $f_hasta"]);
    $xlsx->addRow(['Viajes: ' . $kpi_total . '  ·  En ruta: ' . $kpi_ruta . '  ·  Km recorridos: ' . round($kpi_km, 1)]);
    $xlsx->addBlankRow();
    $xlsx->addHeaderRow(['Fecha salida', 'Unidad', 'Placas', 'Origen', 'Destino',
        'Km salida', 'Km llegada', 'Km recorridos', 'Propósito', 'Estado', 'Fecha llegada'], true);
    foreach ($viajes as $v) {
        $xlsx->addRow([
            (string) $v['fecha_salida'],
            trim(($v['alias'] ? $v['alias'] . ' · ' : '') . $v['marca'] . ' ' . $v['modelo']),
            $v['placas'],
            $v['suc_origen'] ?? '',
            $v['suc_destino'] ?? ($v['destino_descripcion'] ?? ''),
            (float) $v['km_salida'],
            $v['km_llegada'] !== null ? (float) $v['km_llegada'] : '',
            $v['km_rec'] !== null ? (float) $v['km_rec'] : '',
            $v['proposito'] ?? '', $v['estado'], (string) ($v['fecha_llegada'] ?? ''),
        ]);
    }
    $xlsx->download('flotilla_viajes_' . date('Ymd_His') . '.xlsx');
    exit;
}

// Catálogos para filtros
$sucursales = $ver_todas
    ? db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);
$vehiculos   = db_all("SELECT id, alias, placas FROM flotilla_vehiculos WHERE activo=1 ORDER BY alias, placas");

$qs_export = http_build_query(array_filter([
    'desde' => $f_desde, 'hasta' => $f_hasta, 'sucursal_id' => $f_suc ?: null,
    'vehiculo_id' => $f_veh ?: null, 'estado' => $f_estado ?: null,
]));

$titulo_pagina = 'Flotilla · Viajes';
$pagina_activa = 'flotilla_viajes';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Encabezado -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-6 h-6 text-bacal-700"></i>
                Viajes de la flota
            </h2>
            <p class="text-xs text-zinc-500 mt-0.5">Todos los viajes de las unidades, con km recorridos y estado.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('flotilla_viajes.php?' . $qs_export . '&exportar=xlsx') ?>"
               class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="sheet" class="w-4 h-4"></i> Excel
            </a>
            <a href="<?= url('flotilla_viajes.php?' . $qs_export . '&exportar=csv') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white hover:bg-zinc-50 text-sm font-medium text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="download" class="w-4 h-4"></i> CSV
            </a>
        </div>
    </div>

    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($errores): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $err): ?><div>✗ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Viajes del periodo</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($kpi_total) ?></div>
        </div>
        <div class="bg-white rounded-xl border <?= $kpi_ruta > 0 ? 'border-blue-200 bg-blue-50' : 'border-zinc-200' ?> p-4">
            <div class="text-[10px] uppercase tracking-wider font-bold <?= $kpi_ruta > 0 ? 'text-blue-700' : 'text-zinc-500' ?>">En ruta ahora</div>
            <div class="font-display text-2xl font-extrabold <?= $kpi_ruta > 0 ? 'text-blue-700' : 'text-zinc-900' ?>"><?= number_format($kpi_ruta) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Km recorridos</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($kpi_km) ?> <span class="text-sm text-zinc-400">km</span></div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3 flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Desde</label>
            <input type="date" name="desde" value="<?= e($f_desde) ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Hasta</label>
            <input type="date" name="hasta" value="<?= e($f_hasta) ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm">
        </div>
        <?php if ($ver_todas): ?>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Sucursal</label>
            <select name="sucursal_id" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="0">Todas</option>
                <?php foreach ($sucursales as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $f_suc === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Vehículo</label>
            <select name="vehiculo_id" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="0">Todos</option>
                <?php foreach ($vehiculos as $vv): ?>
                <option value="<?= $vv['id'] ?>" <?= $f_veh === (int) $vv['id'] ? 'selected' : '' ?>>
                    <?= $vv['alias'] ? e($vv['alias']) . ' · ' : '' ?><?= e($vv['placas']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Estado</label>
            <select name="estado" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="">Todos</option>
                <option value="en_ruta" <?= $f_estado === 'en_ruta' ? 'selected' : '' ?>>En ruta</option>
                <option value="completado" <?= $f_estado === 'completado' ? 'selected' : '' ?>>Completado</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Filtrar</button>
        <a href="<?= url('flotilla_viajes.php') ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">Limpiar</a>
    </form>

    <!-- Tabla -->
    <?php if (empty($viajes)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="map-pin" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin viajes en el periodo</p>
        <p class="text-sm text-zinc-500 mt-1">Ajusta los filtros o registra viajes desde la ficha de cada unidad.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="fecha">Salida</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Unidad</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Ruta</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Km rec.</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider hidden lg:table-cell">Propósito</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-2.5" data-no-orden></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($viajes as $v):
                        $en_ruta = $v['estado'] === 'en_ruta';
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 whitespace-nowrap text-zinc-700" data-orden="<?= e((string) $v['fecha_salida']) ?>">
                            <?= fmt_fecha_hora($v['fecha_salida']) ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <a href="<?= url('flotilla_vehiculo_ver.php?id=' . $v['vehiculo_id'] . '&tab=viajes') ?>"
                               class="font-semibold text-zinc-900 hover:text-bacal-700 hover:underline">
                                <?= $v['alias'] ? e($v['alias']) . ' · ' : '' ?><?= e($v['marca']) ?> <?= e($v['modelo']) ?>
                            </a>
                            <div class="text-[11px] text-zinc-400 font-mono"><?= e($v['placas']) ?></div>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-700">
                            <?= $v['suc_origen'] ? e($v['suc_origen']) : 'Origen' ?>
                            <i data-lucide="arrow-right" class="w-3 h-3 inline text-zinc-400"></i>
                            <?= $v['suc_destino'] ? e($v['suc_destino']) : ($v['destino_descripcion'] ? e($v['destino_descripcion']) : '—') ?>
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono <?= $v['km_rec'] !== null ? 'text-zinc-800' : 'text-zinc-300' ?>"
                            data-orden="<?= $v['km_rec'] !== null ? (float) $v['km_rec'] : -1 ?>">
                            <?= $v['km_rec'] !== null ? number_format((float) $v['km_rec']) : '—' ?>
                        </td>
                        <td class="px-4 py-2.5 hidden lg:table-cell text-xs text-zinc-500"><?= e((string) ($v['proposito'] ?? '')) ?: '—' ?></td>
                        <td class="px-4 py-2.5">
                            <?php if ($en_ruta): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">En ruta</span>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 text-zinc-600">Completado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <?php if ($en_ruta && $puede_gestionar): ?>
                                <button type="button"
                                        onclick="cerrarViaje(<?= (int) $v['id'] ?>, <?= (float) $v['km_salida'] ?>)"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold">
                                    Cerrar
                                </button>
                                <?php endif; ?>
                                <?php if ($puede_gestionar): ?>
                                <form method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este viaje? Esta acción no se puede deshacer.');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="op" value="viaje_eliminar">
                                    <input type="hidden" name="viaje_id" value="<?= (int) $v['id'] ?>">
                                    <button type="submit" title="Eliminar viaje"
                                            class="p-1.5 rounded-lg text-zinc-400 hover:text-red-600 hover:bg-red-50"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal cerrar viaje -->
<?php if ($puede_gestionar): ?>
<div id="modal-cerrar-viaje" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i> Cerrar viaje
        </h3>
        <form method="POST" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="viaje_cerrar">
            <input type="hidden" name="viaje_id" id="cv_id">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Km de llegada <span class="text-red-500">*</span></label>
                <input type="number" name="km_llegada" id="cv_km" required min="0" step="0.1"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono">
                <p class="text-[10px] text-zinc-400 mt-0.5" id="cv_hint"></p>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-cerrar-viaje').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Confirmar llegada</button>
            </div>
        </form>
    </div>
</div>
<script>
function cerrarViaje(id, kmSalida) {
    document.getElementById('cv_id').value = id;
    var km = document.getElementById('cv_km');
    km.min = kmSalida;
    km.value = '';
    document.getElementById('cv_hint').textContent = 'Km de salida: ' + Number(kmSalida).toLocaleString('es-MX');
    document.getElementById('modal-cerrar-viaje').classList.remove('hidden');
    km.focus();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
