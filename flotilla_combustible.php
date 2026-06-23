<?php
/**
 * ============================================================================
 * flotilla_combustible.php - Registro de cargas de combustible
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$f_vehiculo_id = (int) input('vehiculo_id', 0);
$f_desde       = (string) input('desde', date('Y-m-01'));
$f_hasta       = (string) input('hasta', date('Y-m-t'));
$f_sucursal    = (int) input('sucursal_id', 0);

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$errores = [];

// ----------------------------------------------------------------------------
// POST: crear carga de combustible
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        // --- Actualizar kilometraje (odómetro) ---
        if ($op === 'actualizar_km') {
            $vid_km   = (int) input('vehiculo_id', 0);
            $km_nuevo = (int) input('km_nuevo', 0);
            $forzar   = (int) input('forzar', 0) === 1;
            $res = flotilla_actualizar_km($vid_km, $km_nuevo, tiene_permiso('administrar'), $forzar);
            if ($res['ok']) flash_set('exito', flotilla_odometro_mensaje($res, $km_nuevo));
            else            flash_set('error', $res['error']);
            header('Location: ' . url('flotilla_combustible.php'));
            exit;
        }

        if ($op === 'crear') {
            $vid           = (int) input('vehiculo_id', 0);
            $conductor_id  = (int) input('conductor_id', 0) ?: null;
            $fecha         = trim((string) input('fecha', ''));
            $km_odometro   = (int) input('km_odometro', 0);
            $litros        = (float) input('litros', 0);
            $costo_modo    = (string) input('costo_modo', 'precio');
            if ($costo_modo === 'monto') {
                $monto_total  = (float) input('monto_total', 0);
                $precio_litro = ($litros > 0) ? round($monto_total / $litros, 3) : 0;
            } else {
                $precio_litro = (float) input('precio_litro', 0);
            }
            $tipo_comb     = (string) input('tipo_combustible', 'diesel');
            $estacion_id   = (int) input('estacion_id', 0) ?: null;
            $estacion      = null;
            if ($estacion_id) {
                $est_row  = db_one("SELECT nombre FROM flotilla_estaciones WHERE id = :id", ['id' => $estacion_id]);
                $estacion = $est_row['nombre'] ?? null;
            }
            $ticket        = trim((string) input('ticket_numero', '')) ?: null;
            $es_lleno      = (int) input('es_tanque_lleno', 1);
            $notas         = trim((string) input('notas', '')) ?: null;

            if (!$vid)           $errores[] = 'Selecciona un vehículo.';
            if (!$fecha)         $errores[] = 'La fecha es obligatoria.';
            if ($km_odometro <= 0) $errores[] = 'El km del odómetro es obligatorio.';
            if ($litros <= 0)    $errores[] = 'Los litros deben ser mayores a 0.';
            if ($precio_litro <= 0) $errores[] = 'Captura el precio por litro o el monto total pagado.';

            $recibo_url = null;
            if (empty($errores)) {
                $rec = flotilla_guardar_recibo($_FILES['recibo'] ?? []);
                if ($rec['error']) $errores[] = $rec['error'];
                else $recibo_url = $rec['ruta'];
            }

            if (empty($errores)) {
                try {
                    // Calcular km recorridos vs última carga
                    $ultima = db_one(
                        "SELECT km_odometro FROM flotilla_combustible
                          WHERE vehiculo_id = :vid ORDER BY fecha DESC, id DESC LIMIT 1",
                        ['vid' => $vid]
                    );
                    $km_recorridos     = null;
                    $rendimiento_kml   = null;
                    if ($ultima && $km_odometro > $ultima['km_odometro']) {
                        $km_recorridos   = $km_odometro - $ultima['km_odometro'];
                        if ($litros > 0 && $es_lleno) {
                            $rendimiento_kml = round($km_recorridos / $litros, 3);
                        }
                    }

                    $total = round($litros * $precio_litro, 2);

                    // Columnas opcionales (solo si ya corrió la migración)
                    $cols_x = ''; $vals_x = ''; $params_x = [];
                    if (db_one("SHOW COLUMNS FROM flotilla_combustible LIKE 'estacion_id'")) {
                        $cols_x .= ', estacion_id'; $vals_x .= ', :estacion_id'; $params_x['estacion_id'] = $estacion_id;
                    }
                    if (db_one("SHOW COLUMNS FROM flotilla_combustible LIKE 'recibo_url'")) {
                        $cols_x .= ', recibo_url'; $vals_x .= ', :recibo'; $params_x['recibo'] = $recibo_url;
                    }
                    db_exec(
                        "INSERT INTO flotilla_combustible
                            (vehiculo_id, conductor_id, fecha, km_odometro, litros, precio_litro,
                             tipo_combustible, estacion, ticket_numero, es_tanque_lleno,
                             km_recorridos, rendimiento_kml, notas, creado_por{$cols_x})
                         VALUES
                            (:vid, :cond, :fecha, :km, :litros, :precio,
                             :tipo, :estacion, :ticket, :lleno,
                             :km_rec, :rend, :notas, :creado_por{$vals_x})",
                        array_merge([
                            'vid'        => $vid,
                            'cond'       => $conductor_id,
                            'fecha'      => $fecha,
                            'km'         => $km_odometro,
                            'litros'     => $litros,
                            'precio'     => $precio_litro,
                            'tipo'       => $tipo_comb,
                            'estacion'   => $estacion,
                            'ticket'     => $ticket,
                            'lleno'      => $es_lleno,
                            'km_rec'     => $km_recorridos,
                            'rend'       => $rendimiento_kml,
                            'notas'      => $notas,
                            'creado_por' => $u['id'],
                        ], $params_x)
                    );
                    $comb_id = db_last_id();

                    // Actualizar km_actual del vehículo si es mayor
                    $veh = db_one("SELECT km_actual FROM flotilla_vehiculos WHERE id = :id", ['id' => $vid]);
                    if ($veh && $km_odometro > $veh['km_actual']) {
                        db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id",
                            ['km' => $km_odometro, 'id' => $vid]);
                    }

                    // Crear gasto automático
                    $cat_comb = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre = 'Combustible' LIMIT 1");
                    if ($cat_comb) {
                        db_exec(
                            "INSERT INTO flotilla_gastos
                                (vehiculo_id, categoria_id, conductor_id, fecha, concepto, monto,
                                 km_odometro, combustible_id, creado_por)
                             VALUES (:vid, :cat, :cond, :fecha, :concepto, :monto, :km, :comb_id, :cp)",
                            [
                                'vid'      => $vid,
                                'cat'      => $cat_comb['id'],
                                'cond'     => $conductor_id,
                                'fecha'    => substr($fecha, 0, 10),
                                'concepto' => "Combustible – {$litros} L ({$tipo_comb})" . ($estacion ? " en {$estacion}" : ''),
                                'monto'    => $total,
                                'km'       => $km_odometro,
                                'comb_id'  => $comb_id,
                                'cp'       => $u['id'],
                            ]
                        );
                    }

                    registrar_auditoria('crear_combustible', 'flotilla_combustible', $comb_id,
                        "Vehículo ID {$vid}: {$litros}L @ \${$precio_litro}");
                    flash_set('exito', 'Carga de combustible registrada.');
                    header('Location: ' . url("flotilla_combustible.php?vehiculo_id={$vid}&desde={$f_desde}&hasta={$f_hasta}"));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'eliminar' && tiene_permiso('administrar')) {
            $del_id = (int) input('del_id', 0);
            db_exec("DELETE FROM flotilla_combustible WHERE id = :id", ['id' => $del_id]);
            flash_set('exito', 'Registro eliminado.');
            header('Location: ' . url("flotilla_combustible.php?vehiculo_id={$f_vehiculo_id}&desde={$f_desde}&hasta={$f_hasta}"));
            exit;
        }
    }
}

// ----------------------------------------------------------------------------
// Cargar datos
// ----------------------------------------------------------------------------
$vehiculos   = db_all(
    "SELECT v.id, v.alias, v.marca, v.modelo, v.placas, v.km_actual, v.combustible_tipo,
            t.nombre tipo_nombre
       FROM flotilla_vehiculos v
       INNER JOIN flotilla_tipos_vehiculo t ON v.tipo_id = t.id
      WHERE v.activo = 1"
    . ($f_sucursal ? " AND v.sucursal_id = {$f_sucursal}" : '')
    . " ORDER BY v.alias, v.placas"
);
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

// Filtrar registros
$where  = ['1=1'];
$params = [];
if ($f_vehiculo_id) {
    $where[]             = 'c.vehiculo_id = :vid';
    $params['vid']       = $f_vehiculo_id;
}
if ($f_desde) {
    $where[]             = "DATE(c.fecha) >= :desde";
    $params['desde']     = $f_desde;
}
if ($f_hasta) {
    $where[]             = "DATE(c.fecha) <= :hasta";
    $params['hasta']     = $f_hasta;
}
if ($f_sucursal) {
    $where[]             = 'v.sucursal_id = :sid';
    $params['sid']       = $f_sucursal;
}
$sql_where = implode(' AND ', $where);

$registros = db_all(
    "SELECT c.*,
            (c.litros * c.precio_litro)    AS total_calc,
            v.alias, v.marca, v.modelo, v.placas,
            co.nombre_completo conductor_nombre
       FROM flotilla_combustible c
       INNER JOIN flotilla_vehiculos v   ON c.vehiculo_id = v.id
       LEFT  JOIN flotilla_conductores co ON c.conductor_id = co.id
      WHERE $sql_where
      ORDER BY c.fecha DESC, c.id DESC
      LIMIT 200",
    $params
);

// KPIs del mes
$kpi = db_one(
    "SELECT COUNT(*) total_cargas,
            COALESCE(SUM(c.litros),0) total_litros,
            COALESCE(SUM(c.litros * c.precio_litro),0) total_costo,
            COALESCE(AVG(NULLIF(c.rendimiento_kml,0)),0) avg_rendimiento
       FROM flotilla_combustible c
       INNER JOIN flotilla_vehiculos v ON c.vehiculo_id = v.id
      WHERE $sql_where",
    $params
) ?? [];

$titulo_pagina = 'Flotilla · Combustible';
$pagina_activa = 'flotilla_combustible';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="fuel" class="w-6 h-6 text-bacal-700"></i>
            Combustible
        </h2>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal-nueva-carga').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Registrar carga
        </button>
        <button type="button" onclick="document.getElementById('modal-km-comb').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg border border-zinc-300 bg-white hover:bg-zinc-50 text-zinc-700 text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="gauge" class="w-4 h-4"></i> Actualizar odómetro
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash / Errores -->
    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium
        <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($errores): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $err): ?><div>✗ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $kpis_data = [
            ['Cargas',          $kpi['total_cargas']   ?? 0,  'fuel',     'amber',   false],
            ['Litros (periodo)',    number_format((float)($kpi['total_litros'] ?? 0), 1) . ' L',   'droplets', 'blue',    false],
            ['Costo (periodo)',    '$' . number_format((float)($kpi['total_costo'] ?? 0), 2), 'banknote',  'emerald', false],
            ['Rend. prom.',    number_format((float)($kpi['avg_rendimiento'] ?? 0), 2) . ' km/L', 'gauge', 'violet',  false],
        ];
        foreach ($kpis_data as [$label, $val, $icon, $color, $alert]):
        ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
                <span class="font-display text-xl font-extrabold text-zinc-900"><?= $val ?></span>
            </div>
            <div class="text-[11px] uppercase tracking-wide font-bold text-zinc-500"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 p-3 flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-xs font-bold text-zinc-500 mb-1">Desde</label>
            <input type="date" name="desde" value="<?= e($f_desde) ?>"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-zinc-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="<?= e($f_hasta) ?>"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-bold text-zinc-500 mb-1">Vehículo</label>
            <select name="vehiculo_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
                <option value="">Todos los vehículos</option>
                <?php foreach ($vehiculos as $vv): ?>
                <option value="<?= $vv['id'] ?>" <?= $f_vehiculo_id === (int)$vv['id'] ? 'selected' : '' ?>>
                    <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
            Filtrar
        </button>
        <?php if ($f_vehiculo_id || $f_desde !== date('Y-m-01') || $f_hasta !== date('Y-m-t')): ?>
        <a href="<?= url('flotilla_combustible.php') ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">
            Limpiar
        </a>
        <?php endif; ?>
    </form>

    <!-- Tabla de registros -->
    <?php if (empty($registros)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="fuel" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin registros de combustible</p>
        <p class="text-sm text-zinc-500 mt-1">Registra la primera carga para comenzar a llevar el control.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="fecha">Fecha</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Vehículo</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="num">Litros</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell" data-orden-tipo="num">Precio/L</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="num">Total</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell" data-orden-tipo="num">Km odómetro</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell" data-orden-tipo="num">Rend. km/L</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell">Tipo</th>
                        <th class="px-4 py-3" data-no-orden></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($registros as $r):
                        $rend = (float)($r['rendimiento_kml'] ?? 0);
                        $rend_color = $rend >= 12 ? 'emerald' : ($rend >= 8 ? 'amber' : ($rend > 0 ? 'red' : 'zinc'));
                        $tipo_labels = [
                            'gasolina_regular'  => 'Gasolina reg.',
                            'gasolina_premium'  => 'Gasolina prem.',
                            'diesel'            => 'Diesel',
                            'gas'               => 'Gas',
                        ];
                    ?>
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-zinc-900"><?= fmt_fecha($r['fecha']) ?></div>
                            <?php if (!$r['es_tanque_lleno']): ?>
                            <div class="text-[11px] text-amber-600 font-semibold">Parcial</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900">
                                <?= $r['alias'] ? e($r['alias']) . ' · ' : '' ?><?= e($r['marca']) ?> <?= e($r['modelo']) ?>
                            </div>
                            <div class="text-xs text-zinc-500 font-mono"><?= e($r['placas']) ?></div>
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-zinc-800">
                            <?= number_format((float)$r['litros'], 2) ?> L
                        </td>
                        <td class="px-4 py-3 text-right hidden md:table-cell text-zinc-600 font-mono">
                            $<?= number_format((float)$r['precio_litro'], 3) ?>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-900">
                            $<?= number_format((float)$r['total_calc'], 2) ?>
                        </td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell font-mono text-zinc-600">
                            <?= number_format((int)$r['km_odometro']) ?> km
                            <?php if ($r['km_recorridos']): ?>
                            <div class="text-[11px] text-zinc-400">(+<?= number_format($r['km_recorridos']) ?> km)</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell">
                            <?php if ($rend > 0): ?>
                            <span class="font-semibold text-<?= $rend_color ?>-600"><?= number_format($rend, 2) ?></span>
                            <?php else: ?>
                            <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                <?= $tipo_labels[$r['tipo_combustible']] ?? e($r['tipo_combustible']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if (!empty($r['recibo_url'])): ?>
                            <a href="<?= url('assets/' . $r['recibo_url']) ?>" target="_blank" class="inline-flex p-1.5 rounded hover:bg-zinc-100 text-bacal-700 align-middle" title="Ver recibo">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puede_gestionar): ?>
                            <form method="POST" class="inline"
                                  onsubmit="return confirm('¿Eliminar este registro?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="eliminar">
                                <input type="hidden" name="del_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-zinc-400 hover:text-red-600">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- Modal: Registrar carga                                       -->
<!-- ============================================================ -->
<div id="modal-nueva-carga" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="fuel" class="w-4 h-4 text-bacal-700"></i>
                Registrar carga de combustible
            </h3>
            <button type="button" onclick="document.getElementById('modal-nueva-carga').classList.add('hidden')"
                    class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="crear">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                    <select name="vehiculo_id" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar vehículo…</option>
                        <?php foreach ($vehiculos as $vv): ?>
                        <option value="<?= $vv['id'] ?>" <?= $f_vehiculo_id === (int)$vv['id'] ? 'selected' : '' ?>
                                data-km="<?= $vv['km_actual'] ?>" data-tipo="<?= e($vv['combustible_tipo']) ?>">
                            <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
                            (<?= e($vv['marca']) ?> <?= e($vv['modelo']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin asignar</option>
                        <?php foreach ($conductores as $cd): ?>
                        <option value="<?= $cd['id'] ?>"><?= e($cd['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha y hora <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="fecha" required
                           value="<?= date('Y-m-d\TH:i') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro <span class="text-red-500">*</span></label>
                    <input type="number" name="km_odometro" id="km_odometro" required min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500"
                           placeholder="Ej: 45200">
                    <p id="km_hint" class="text-xs text-zinc-400 mt-0.5 hidden">Último km registrado: <span id="km_last"></span></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo de combustible <span class="text-red-500">*</span></label>
                    <select name="tipo_combustible" id="tipo_combustible"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="diesel">Diesel</option>
                        <option value="gasolina_regular">Gasolina regular</option>
                        <option value="gasolina_premium">Gasolina premium</option>
                        <option value="gas">Gas</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Litros cargados <span class="text-red-500">*</span></label>
                    <input type="number" name="litros" id="litros" required min="0.1" step="0.001"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500"
                           placeholder="0.000">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Capturar costo por</label>
                    <select name="costo_modo" id="costo_modo" onchange="cargaCostoModo()"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="precio">Precio por litro</option>
                        <option value="monto">Monto total pagado</option>
                    </select>
                </div>
                <div id="campo_precio">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Precio por litro <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="precio_litro" id="precio_litro" min="0.001" step="0.001"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500" placeholder="0.000">
                    </div>
                </div>
                <div id="campo_monto" class="hidden">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Monto total pagado <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="monto_total" id="monto_total" min="0.01" step="0.01"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500" placeholder="0.00">
                    </div>
                </div>

                <!-- Total calculado -->
                <div class="sm:col-span-2 bg-bacal-50 border border-bacal-200 rounded-lg px-4 py-2 flex items-center justify-between">
                    <span class="text-sm font-semibold text-bacal-700">Total estimado</span>
                    <span id="total_calc" class="font-display text-lg font-extrabold text-bacal-700">$0.00</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Estación / Gasolinera</label>
                    <select name="estacion_id"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach (flotilla_estaciones_activas() as $est): ?>
                        <option value="<?= $est['id'] ?>"><?= e($est['nombre']) ?><?= $est['direccion'] ? ' · ' . e($est['direccion']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (tiene_permiso('administrar')): ?>
                    <p class="text-[11px] text-zinc-400 mt-0.5">¿Falta una? Agrégala en <a href="<?= url('admin/catalogos.php?tab=estaciones') ?>" class="text-bacal-700 hover:underline">Catálogos › Estaciones</a>.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">No. ticket / factura</label>
                    <input type="text" name="ticket_numero" maxlength="50"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Recibo / factura (imagen o PDF)</label>
                    <input type="file" name="recibo" accept="image/*,application/pdf"
                           class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>

                <div class="sm:col-span-2 flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="es_tanque_lleno" value="1" checked
                               class="w-4 h-4 rounded border-zinc-300 text-bacal-700 focus:ring-bacal-500">
                        <span class="text-sm font-medium text-zinc-700">Tanque lleno (para calcular rendimiento)</span>
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                    <textarea name="notas" rows="2" maxlength="300"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"
                              placeholder="Observaciones opcionales…"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-nueva-carga').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar carga
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-calcular total
function calcTotal() {
    var l = parseFloat(document.getElementById('litros')?.value) || 0;
    var modo = document.getElementById('costo_modo') ? document.getElementById('costo_modo').value : 'precio';
    var total = (modo === 'monto')
        ? (parseFloat(document.getElementById('monto_total')?.value) || 0)
        : l * (parseFloat(document.getElementById('precio_litro')?.value) || 0);
    var el = document.getElementById('total_calc');
    if (el) el.textContent = '$' + total.toFixed(2);
}
function cargaCostoModo() {
    var modo = document.getElementById('costo_modo').value;
    document.getElementById('campo_precio')?.classList.toggle('hidden', modo !== 'precio');
    document.getElementById('campo_monto')?.classList.toggle('hidden', modo !== 'monto');
    calcTotal();
}
document.getElementById('litros')?.addEventListener('input', calcTotal);
document.getElementById('precio_litro')?.addEventListener('input', calcTotal);
document.getElementById('monto_total')?.addEventListener('input', calcTotal);

// Sugerir tipo combustible y km al seleccionar vehículo
document.querySelector('select[name="vehiculo_id"]')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var km  = opt.dataset.km;
    var tipo = opt.dataset.tipo;
    var hint = document.getElementById('km_hint');
    var kmLast = document.getElementById('km_last');
    if (km && hint && kmLast) {
        kmLast.textContent = parseInt(km).toLocaleString() + ' km';
        hint.classList.remove('hidden');
        var kmInput = document.getElementById('km_odometro');
        if (kmInput && !kmInput.value) kmInput.value = km;
    }
    if (tipo) {
        var tipoMap = {
            'diesel':'diesel','gasolina':'gasolina_regular','gas':'gas','electrico':'diesel','hibrido':'gasolina_regular'
        };
        var sel = document.getElementById('tipo_combustible');
        if (sel) sel.value = tipoMap[tipo] || 'diesel';
    }
});
</script>

<?php if ($puede_gestionar): ?>
<div id="modal-km-comb" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="border-b border-zinc-200 px-6 py-4 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="gauge" class="w-4 h-4 text-bacal-700"></i> Actualizar odómetro
            </h3>
            <button type="button" onclick="document.getElementById('modal-km-comb').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-600 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" onsubmit="return validarKmComb(this)">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="actualizar_km">
            <input type="hidden" name="forzar" value="0">
            <div class="p-6 space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Vehículo</label>
                    <select name="vehiculo_id" id="km_comb_veh" required onchange="kmCombSync()"
                            class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm bg-white">
                        <option value="">Selecciona…</option>
                        <?php foreach ($vehiculos as $vv): ?>
                        <option value="<?= $vv['id'] ?>" data-km="<?= (int)$vv['km_actual'] ?>" <?= $f_vehiculo_id === (int)$vv['id'] ? 'selected' : '' ?>>
                            <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?> (<?= number_format($vv['km_actual']) ?> km)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Nuevo kilometraje</label>
                    <input type="number" name="km_nuevo" id="km_comb_nuevo" min="0" required
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    <p id="km_comb_hint" class="text-xs text-zinc-400 mt-1"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-zinc-200">
                <button type="button" onclick="document.getElementById('modal-km-comb').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-zinc-700 bg-zinc-100 rounded-lg hover:bg-zinc-200">Cancelar</button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-bacal-700 rounded-lg hover:bg-bacal-800">Guardar</button>
            </div>
        </form>
    </div>
</div>
<script>
var KM_COMB_ADMIN = <?= tiene_permiso('administrar') ? 'true' : 'false' ?>;
function kmCombActual(){
    var sel = document.getElementById('km_comb_veh');
    var opt = sel.options[sel.selectedIndex];
    return opt ? parseInt(opt.getAttribute('data-km') || '0', 10) : 0;
}
function kmCombSync(){
    var actual = kmCombActual();
    var hint = document.getElementById('km_comb_hint');
    var inp = document.getElementById('km_comb_nuevo');
    if (document.getElementById('km_comb_veh').value){
        hint.textContent = 'Km actual: ' + actual.toLocaleString() + ' km';
        if (!inp.value) inp.value = actual;
    } else { hint.textContent = ''; }
}
function validarKmComb(form){
    if (!form.vehiculo_id.value){ alert('Selecciona un vehículo.'); return false; }
    var nuevo = parseInt(form.km_nuevo.value || '0', 10);
    if (isNaN(nuevo)) return false;
    var actual = kmCombActual();
    if (nuevo < actual){
        if (!KM_COMB_ADMIN){ alert('El kilometraje ('+nuevo.toLocaleString()+') no puede ser menor al actual ('+actual.toLocaleString()+' km).'); return false; }
        if (!confirm('El km capturado ('+nuevo.toLocaleString()+') es MENOR al actual ('+actual.toLocaleString()+' km). ¿Forzar el cambio?')) return false;
        form.forzar.value = '1';
    }
    return true;
}
document.addEventListener('DOMContentLoaded', kmCombSync);
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
