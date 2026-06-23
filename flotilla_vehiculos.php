<?php
/**
 * ============================================================================
 * flotilla_vehiculos.php - Catálogo de vehículos de la flotilla
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

// Actualizar estados de documentos en cada carga
flotilla_actualizar_estado_documentos();

// Filtros
$f_q         = trim((string) input('q', ''));
$f_estado    = (string) input('estado', '');
$f_tipo      = (int) input('tipo_id', 0);
$f_sucursal  = (int) input('sucursal_id', 0);

$ver_todas   = tiene_permiso('ver_todas_sucursales');
$sid_forzado = flotilla_sucursal_forzada();
if ($sid_forzado !== null) {
    $f_sucursal = $sid_forzado;
}

$cat_sucursales = $ver_todas
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : [];

$filtros = [
    'q'          => $f_q        ?: null,
    'estado'     => $f_estado   ?: null,
    'tipo_id'    => $f_tipo     ?: null,
    'sucursal_id'=> $f_sucursal ?: null,
];

// Vista por defecto: respeta la preferencia del usuario (flotilla_vista) si la tiene.
$vista_default = (string) usuario_preferencia('flotilla_vista', 'tarjetas');
$vista = (string) input('vista', $vista_default);
if (!in_array($vista, ['tarjetas', 'lista'], true)) $vista = 'tarjetas';
$url_vista = function (string $v) use ($f_q, $f_estado, $f_tipo, $f_sucursal) {
    $qs = ['vista' => $v];
    if ($f_q !== '')      $qs['q']           = $f_q;
    if ($f_estado !== '') $qs['estado']       = $f_estado;
    if ($f_tipo > 0)      $qs['tipo_id']      = $f_tipo;
    if ($f_sucursal > 0)  $qs['sucursal_id']  = $f_sucursal;
    return url('flotilla_vehiculos.php?' . http_build_query($qs));
};

$errores = [];

// ----------------------------------------------------------------------------
// POST: crear o editar vehículo
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'crear' || $op === 'editar') {
            $datos = [
                'tipo_id'               => (int) input('tipo_id', 0),
                'sucursal_id'           => (int) input('sucursal_id', 0) ?: null,
                'conductor_asignado_id' => (int) input('conductor_asignado_id', 0) ?: null,
                'alias'                 => trim((string) input('alias', '')) ?: null,
                'marca'                 => trim((string) input('marca', '')),
                'modelo'                => trim((string) input('modelo', '')),
                'anio'                  => (int) input('anio', date('Y')),
                'color'                 => trim((string) input('color', '')) ?: null,
                'placas'                => strtoupper(trim((string) input('placas', ''))),
                'numero_serie'          => trim((string) input('numero_serie', '')) ?: null,
                'numero_motor'          => trim((string) input('numero_motor', '')) ?: null,
                'combustible_tipo'      => (string) input('combustible_tipo', 'diesel'),
                'capacidad_carga_kg'    => (float) input('capacidad_carga_kg', 0) ?: null,
                'tiene_refrigeracion'   => (int) input('tiene_refrigeracion', 0),
                'temp_min_c'            => trim((string) input('temp_min_c', '')) !== '' ? (float) input('temp_min_c') : null,
                'temp_max_c'            => trim((string) input('temp_max_c', '')) !== '' ? (float) input('temp_max_c') : null,
                'km_inicial'            => (int) input('km_inicial', 0),
                'km_actual'             => (int) input('km_actual', 0),
                'es_propio'             => (int) input('es_propio', 1),
                'proveedor_renta'       => trim((string) input('proveedor_renta', '')) ?: null,
                'fecha_adquisicion'     => trim((string) input('fecha_adquisicion', '')) ?: null,
                'costo_adquisicion'     => (float) input('costo_adquisicion', 0) ?: null,
                'estado'                => (string) input('estado_vehiculo', 'activo'),
                'notas'                 => trim((string) input('notas', '')) ?: null,
            ];

            if (!$datos['tipo_id'])        $errores[] = 'El tipo de vehículo es obligatorio.';
            if ($datos['marca'] === '')     $errores[] = 'La marca es obligatoria.';
            if ($datos['modelo'] === '')    $errores[] = 'El modelo es obligatorio.';
            if ($datos['placas'] === '')    $errores[] = 'Las placas son obligatorias.';
            if ($datos['anio'] < 1980 || $datos['anio'] > (int)date('Y') + 1)
                $errores[] = 'Año de vehículo no válido.';

            if (empty($errores)) {
                try {
                    if ($op === 'crear') {
                        // Verificar placas únicas
                        if (db_one("SELECT id FROM flotilla_vehiculos WHERE placas = :p", ['p' => $datos['placas']])) {
                            $errores[] = "Ya existe un vehículo con las placas {$datos['placas']}.";
                        } else {
                            $datos['creado_por'] = $u['id'];
                            $cols   = implode(', ', array_keys($datos));
                            $params = ':' . implode(', :', array_keys($datos));
                            db_exec("INSERT INTO flotilla_vehiculos ($cols) VALUES ($params)", $datos);
                            $nuevo_id = db_last_id();
                            registrar_auditoria('crear_vehiculo', 'flotilla_vehiculos', $nuevo_id, "Vehículo: {$datos['placas']} {$datos['marca']} {$datos['modelo']}");
                            flash_set('exito', 'Vehículo registrado correctamente.');
                            header('Location: ' . url("flotilla_vehiculo_ver.php?id=$nuevo_id"));
                            exit;
                        }
                    } else {
                        $edit_id = (int) input('edit_id', 0);
                        // Verificar placas únicas (excluyendo este)
                        $dup = db_one("SELECT id FROM flotilla_vehiculos WHERE placas = :p AND id != :id", ['p' => $datos['placas'], 'id' => $edit_id]);
                        if ($dup) {
                            $errores[] = "Ya existe otro vehículo con las placas {$datos['placas']}.";
                        } else {
                            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($datos)));
                            $datos['id'] = $edit_id;
                            db_exec("UPDATE flotilla_vehiculos SET $sets WHERE id = :id", $datos);
                            registrar_auditoria('editar_vehiculo', 'flotilla_vehiculos', $edit_id, "Vehículo editado: {$datos['placas']}");
                            flash_set('exito', 'Vehículo actualizado.');
                            header('Location: ' . url("flotilla_vehiculo_ver.php?id=$edit_id"));
                            exit;
                        }
                    }
                } catch (Throwable $e) {
                    $errores[] = 'Error al guardar: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'toggle' && tiene_permiso('administrar')) {
            $toggle_id = (int) input('toggle_id', 0);
            $v = db_one("SELECT id, activo, placas FROM flotilla_vehiculos WHERE id = :id", ['id' => $toggle_id]);
            if ($v) {
                $nuevo = $v['activo'] ? 0 : 1;
                db_exec("UPDATE flotilla_vehiculos SET activo = :a WHERE id = :id", ['a' => $nuevo, 'id' => $toggle_id]);
                flash_set('exito', $nuevo ? 'Vehículo activado.' : 'Vehículo desactivado.');
            }
            header('Location: ' . url('flotilla_vehiculos.php'));
            exit;
        }
    }
}

// Cargar datos
$vehiculos   = flotilla_listar_vehiculos($filtros);

// Odómetro: IDs de vehículos con lectura vencida (umbral configurable por admin)
$odo_umbral_lista = flotilla_odometro_umbral();
$odo_vencidos_ids = [];
if (db_one("SHOW TABLES LIKE 'flotilla_odometro_historial'")) {
    foreach (db_all(
        "SELECT v.id FROM flotilla_vehiculos v
         WHERE v.activo = 1
           AND COALESCE(GREATEST(
                 COALESCE((SELECT MAX(leido_en) FROM flotilla_odometro_historial WHERE vehiculo_id = v.id), '1970-01-01'),
                 COALESCE((SELECT MAX(fecha) FROM flotilla_combustible WHERE vehiculo_id = v.id AND km_odometro > 0), '1970-01-01')
               ), '1970-01-01') < DATE_SUB(NOW(), INTERVAL {$odo_umbral_lista} DAY)"
    ) as $r) { $odo_vencidos_ids[(int) $r['id']] = true; }
}
$stats       = flotilla_stats($f_sucursal ?: null);
$tipos       = db_all("SELECT * FROM flotilla_tipos_vehiculo WHERE activo=1 ORDER BY nombre");
$sucursales  = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")
    : [];
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

$titulo_pagina = 'Flotilla · Vehículos';
$pagina_activa = 'flotilla_vehiculos';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex flex-col <?= usuario_prefiere_radio_sucursal() ? 'xl:flex-row xl:items-start' : 'lg:flex-row lg:items-center' ?> lg:justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="car" class="w-6 h-6 text-bacal-700"></i>
                Flotilla vehicular
            </h2>

            <?php if ($ver_todas && usuario_prefiere_radio_sucursal()): ?>
            <form method="GET" class="flex items-center gap-2 flex-wrap bg-white border border-zinc-300 rounded-lg px-3 py-1.5">
                <?php foreach ($_GET as $k => $v) {
                    if ($k === 'sucursal_id' || $k === 'p') continue;
                    if ($v !== '' && $v !== '0') echo '<input type="hidden" name="'.e($k).'" value="'.e((string)$v).'">';
                } ?>
                <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide">Sucursal:</span>
                <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
                    <input type="radio" name="sucursal_id" value="" onchange="this.form.submit()"
                           <?= $f_sucursal <= 0 ? 'checked' : '' ?> class="text-bacal-700 focus:ring-bacal-700">
                    Todas
                </label>
                <?php foreach ($cat_sucursales as $s): ?>
                <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
                    <input type="radio" name="sucursal_id" value="<?= $s['id'] ?>" onchange="this.form.submit()"
                           <?= $f_sucursal == $s['id'] ? 'checked' : '' ?> class="text-bacal-700 focus:ring-bacal-700">
                    <?= e($s['nombre']) ?>
                </label>
                <?php endforeach; ?>
            </form>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($ver_todas && !usuario_prefiere_radio_sucursal()): ?>
            <form method="GET" class="relative">
                <?php foreach ($_GET as $k => $v) {
                    if ($k === 'sucursal_id' || $k === 'p') continue;
                    if ($v !== '' && $v !== '0') echo '<input type="hidden" name="'.e($k).'" value="'.e((string)$v).'">';
                } ?>
                <i data-lucide="store" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                <select name="sucursal_id" onchange="this.form.submit()"
                        class="pl-9 pr-8 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 focus:outline-none focus:border-bacal-700 appearance-none cursor-pointer">
                    <option value="">Todas las sucursales</option>
                    <?php foreach ($cat_sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sucursal == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none"></i>
            </form>
            <?php endif; ?>
            <?php if (tiene_permiso('administrar')): ?>
            <a href="<?= url('admin/flotilla_ajustes.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5"
               title="Ajustes de flotilla">
                <i data-lucide="settings-2" class="w-4 h-4"></i>
                Ajustes
            </a>
            <?php endif; ?>
            <a href="<?= url('flotilla_conductores.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="users" class="w-4 h-4"></i>
                Conductores
            </a>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-nuevo-vehiculo').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo vehículo
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash messages -->
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
        $kpis = [
            ['activos',            'Activos',             'check-circle',  'emerald'],
            ['en_taller',          'En taller',           'wrench',        'amber'],
            ['docs_alerta',        'Docs por vencer',     'file-warning',  'orange'],
            ['multas_pendientes',  'Multas pendientes',   'alert-triangle','red'],
        ];
        $colores = ['emerald'=>'emerald','amber'=>'amber','orange'=>'orange','red'=>'red'];
        foreach ($kpis as [$key, $label, $icon, $color]):
            $val = $stats[$key];
            $alert = $val > 0 && in_array($key, ['en_taller','docs_alerta','multas_pendientes']);
        ?>
        <div class="bg-white rounded-xl border <?= $alert ? "border-{$color}-200 bg-{$color}-50" : 'border-zinc-200' ?> p-4">
            <div class="flex items-center justify-between mb-2">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
                <span class="font-display text-2xl font-extrabold <?= $alert ? "text-{$color}-700" : 'text-zinc-900' ?>">
                    <?= $val ?>
                </span>
            </div>
            <div class="text-[11px] uppercase tracking-wide font-bold text-zinc-500"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 p-3 flex flex-wrap gap-2 items-end">
        <input type="hidden" name="vista" value="<?= e($vista) ?>">
        <input type="text" name="q" value="<?= e($f_q) ?>" placeholder="Placas, alias, marca…"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm flex-1 min-w-[160px] focus:outline-none focus:ring-2 focus:ring-bacal-500">
        <select name="estado" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los estados</option>
            <?php foreach (['activo'=>'Activo','taller'=>'En taller','inactivo'=>'Inactivo','baja'=>'Baja'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $f_estado === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tipo_id" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $f_tipo === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (tiene_permiso('ver_todas_sucursales') && $sucursales): ?>
        <select name="sucursal_id" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todas las sucursales</option>
            <?php foreach ($sucursales as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
            Filtrar
        </button>
        <?php if ($f_q || $f_estado || $f_tipo || $f_sucursal): ?>
        <a href="<?= url('flotilla_vehiculos.php?vista=' . $vista) ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">
            Limpiar
        </a>
        <?php endif; ?>
    </form>

    <!-- Toggle de vista -->
    <div class="flex items-center justify-between flex-wrap gap-2">
        <p class="text-sm text-zinc-500"><?= count($vehiculos) ?> vehículo<?= count($vehiculos) !== 1 ? 's' : '' ?></p>
        <div class="inline-flex rounded-lg border border-zinc-300 bg-white p-0.5 shadow-sm">
            <a href="<?= $url_vista('tarjetas') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors
                      <?= $vista === 'tarjetas' ? 'bg-bacal-700 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>"
               title="Vista de tarjetas">
                <i data-lucide="layout-grid" class="w-4 h-4"></i> Tarjetas
            </a>
            <a href="<?= $url_vista('lista') ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors
                      <?= $vista === 'lista' ? 'bg-bacal-700 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>"
               title="Lista en orden alfabético">
                <i data-lucide="list" class="w-4 h-4"></i> Lista
            </a>
        </div>
    </div>

    <!-- Vista de vehículos -->
    <?php if (empty($vehiculos)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="car" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">No hay vehículos registrados</p>
        <p class="text-sm text-zinc-500 mt-1">
            <?= $f_q || $f_estado || $f_tipo ? 'Prueba con otros filtros.' : 'Registra el primer vehículo de la flotilla.' ?>
        </p>
    </div>
    <?php elseif ($vista === 'lista'): ?>
    <!-- ── Vista Lista ── -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Vehículo</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Placas</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell">Tipo</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Sucursal</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Conductor</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell" data-orden-tipo="num">Km actual</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3" data-no-orden></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($vehiculos as $v): ?>
                    <tr class="hover:bg-zinc-50 transition-colors cursor-pointer"
                        onclick="window.location='<?= url('flotilla_vehiculo_ver.php?id=' . $v['id']) ?>'">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900">
                                <?= $v['alias'] ? e($v['alias']) . ' · ' : '' ?>
                                <?= e($v['marca']) ?> <?= e($v['modelo']) ?>
                                <?php if (isset($odo_vencidos_ids[(int) $v['id']])): ?>
                                <i data-lucide="gauge" class="w-3.5 h-3.5 inline text-amber-500 ml-1"
                                   title="Odómetro sin actualizar (más de <?= $odo_umbral_lista ?> días)"></i>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-zinc-500"><?= e($v['anio']) ?><?= $v['color'] ? ' · ' . e($v['color']) : '' ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono font-bold text-zinc-800"><?= e($v['placas']) ?></span>
                            <?php if ($v['tiene_refrigeracion']): ?>
                            <span class="ml-1" title="Refrigerado"><i data-lucide="thermometer-snowflake" class="w-3.5 h-3.5 inline text-blue-400"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-zinc-600"><?= e($v['tipo_nombre']) ?></td>
                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-600"><?= $v['sucursal_nombre'] ? e($v['sucursal_nombre']) : '—' ?></td>
                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-600"><?= $v['conductor_nombre'] ? e($v['conductor_nombre']) : '—' ?></td>
                        <td class="px-4 py-3 hidden md:table-cell text-right font-mono text-zinc-700">
                            <?= number_format($v['km_actual']) ?> km
                        </td>
                        <td class="px-4 py-3"><?= flotilla_badge_estado($v['estado']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('flotilla_vehiculo_ver.php?id=' . $v['id']) ?>"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-xs font-semibold text-zinc-700">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Vista Tarjetas ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($vehiculos as $v):
            $estado_colors = match($v['estado']) {
                'activo'   => ['border-emerald-300', 'bg-emerald-500'],
                'taller'   => ['border-amber-300',   'bg-amber-500'],
                'inactivo' => ['border-zinc-300',     'bg-zinc-400'],
                'baja'     => ['border-red-300',      'bg-red-500'],
                default    => ['border-zinc-200',     'bg-zinc-300'],
            };
        ?>
        <a href="<?= url('flotilla_vehiculo_ver.php?id=' . $v['id']) ?>"
           class="group bg-white rounded-xl border <?= $estado_colors[0] ?> shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden">
            <!-- Franja de color estado -->
            <div class="h-1.5 <?= $estado_colors[1] ?> w-full"></div>

            <div class="p-4 flex flex-col gap-3 flex-1">
                <!-- Título -->
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <?php if ($v['alias']): ?>
                        <div class="font-display font-extrabold text-zinc-900 text-base leading-tight group-hover:text-bacal-700 transition-colors">
                            <?= e($v['alias']) ?>
                        </div>
                        <div class="text-xs text-zinc-500 mt-0.5"><?= e($v['marca']) ?> <?= e($v['modelo']) ?> <?= e($v['anio']) ?></div>
                        <?php else: ?>
                        <div class="font-display font-extrabold text-zinc-900 text-base leading-tight group-hover:text-bacal-700 transition-colors">
                            <?= e($v['marca']) ?> <?= e($v['modelo']) ?>
                        </div>
                        <div class="text-xs text-zinc-500 mt-0.5"><?= e($v['anio']) ?><?= $v['color'] ? ' · ' . e($v['color']) : '' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($v['tiene_refrigeracion']): ?>
                    <span title="Refrigerado" class="mt-0.5 shrink-0">
                        <i data-lucide="thermometer-snowflake" class="w-4 h-4 text-blue-400"></i>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Placas -->
                <div class="font-mono font-bold text-lg text-zinc-800 tracking-wider bg-zinc-100 rounded-lg px-3 py-1.5 text-center border border-zinc-200">
                    <?= e($v['placas']) ?>
                </div>

                <!-- Datos -->
                <div class="space-y-1.5 text-xs text-zinc-600 flex-1">
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="truck" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                        <span><?= e($v['tipo_nombre']) ?></span>
                    </div>
                    <?php if ($v['conductor_nombre']): ?>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="user-check" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                        <span class="truncate"><?= e($v['conductor_nombre']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($v['sucursal_nombre']): ?>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                        <span class="truncate"><?= e($v['sucursal_nombre']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="gauge" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                        <span class="font-mono font-semibold text-zinc-700"><?= number_format($v['km_actual']) ?> km</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-2 border-t border-zinc-100 flex items-center justify-between">
                    <?= flotilla_badge_estado($v['estado']) ?>
                    <span class="text-xs font-semibold text-bacal-700 group-hover:underline flex items-center gap-1">
                        Ver detalle <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- Modal: Nuevo vehículo                                        -->
<!-- ============================================================ -->
<div id="modal-nuevo-vehiculo" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" onclick.stop>
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-bacal-700"></i>
                Registrar vehículo
            </h3>
            <button type="button" onclick="document.getElementById('modal-nuevo-vehiculo').classList.add('hidden')"
                    class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="crear">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo de vehículo <span class="text-red-500">*</span></label>
                    <select name="tipo_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Alias / nombre operativo</label>
                    <input type="text" name="alias" maxlength="60" placeholder="Ej: Unidad 01, Frío Norte"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Marca <span class="text-red-500">*</span></label>
                    <input type="text" name="marca" required maxlength="60" placeholder="Ford, Kenworth, Isuzu…"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Modelo <span class="text-red-500">*</span></label>
                    <input type="text" name="modelo" required maxlength="80"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Año <span class="text-red-500">*</span></label>
                    <input type="number" name="anio" required min="1980" max="<?= date('Y') + 1 ?>" value="<?= date('Y') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Placas <span class="text-red-500">*</span></label>
                    <input type="text" name="placas" required maxlength="20" placeholder="ABC-123-D" style="text-transform:uppercase"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Color</label>
                    <input type="text" name="color" maxlength="40"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Combustible</label>
                    <select name="combustible_tipo" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="diesel">Diesel</option>
                        <option value="gasolina">Gasolina</option>
                        <option value="gas">Gas</option>
                        <option value="electrico">Eléctrico</option>
                        <option value="hibrido">Híbrido</option>
                    </select>
                </div>

                <?php if (tiene_permiso('ver_todas_sucursales') && $sucursales): ?>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin asignar</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="sucursal_id" value="<?= $u['sucursal_id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor asignado</label>
                    <select name="conductor_asignado_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin conductor fijo</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km inicial (odómetro al registrar)</label>
                    <input type="number" name="km_inicial" value="0" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km actual</label>
                    <input type="number" name="km_actual" value="0" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Número de serie / VIN</label>
                    <input type="text" name="numero_serie" maxlength="50"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">¿Tiene refrigeración?</label>
                    <select name="tiene_refrigeracion" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">¿Es propio?</label>
                    <select name="es_propio" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="1">Propio</option>
                        <option value="0">Rentado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Estado</label>
                    <select name="estado_vehiculo" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="activo">Activo</option>
                        <option value="taller">En taller</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>

            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                <textarea name="notas" rows="2" maxlength="1000"
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-nuevo-vehiculo').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
                    Registrar vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
