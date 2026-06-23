<?php
/**
 * ============================================================================
 * flotilla_siniestros.php - Siniestros y accidentes de la flotilla
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

// Vehículo pre-seleccionado (cuando se llega desde vehiculo_ver)
$f_vehiculo = (int) input('vehiculo_id', 0);
$f_estado   = (string) input('estado', '');
$f_desde    = trim((string) input('desde', ''));
$f_hasta    = trim((string) input('hasta', ''));

$errores = [];

// ----------------------------------------------------------------------------
// POST
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'crear' || $op === 'editar') {
            $datos = [
                'vehiculo_id'               => (int) input('vehiculo_id_s', 0),
                'conductor_id'              => (int) input('conductor_id', 0) ?: null,
                'fecha'                     => trim((string) input('fecha', '')),
                'tipo'                      => (string) input('tipo', 'colision'),
                'descripcion'               => trim((string) input('descripcion', '')),
                'lugar'                     => trim((string) input('lugar', '')) ?: null,
                'hay_terceros'              => (int) input('hay_terceros', 0),
                'descripcion_terceros'      => trim((string) input('descripcion_terceros', '')) ?: null,
                'numero_siniestro_aseg'     => trim((string) input('numero_siniestro_aseg', '')) ?: null,
                'aseguradora'               => trim((string) input('aseguradora', '')) ?: null,
                'fecha_reporte_aseguradora' => trim((string) input('fecha_reporte_aseguradora', '')) ?: null,
                'monto_deducible'           => (float) input('monto_deducible', 0) ?: null,
                'monto_reparacion'          => (float) input('monto_reparacion', 0) ?: null,
                'monto_cubierto_seguro'     => (float) input('monto_cubierto_seguro', 0) ?: null,
                'estado'                    => (string) input('estado_sin', 'reportado'),
                'notas'                     => trim((string) input('notas', '')) ?: null,
                'creado_por'                => $u['id'],
            ];

            if (!$datos['vehiculo_id'])     $errores[] = 'Selecciona el vehículo.';
            if ($datos['descripcion'] === '') $errores[] = 'La descripción es obligatoria.';
            if ($datos['fecha'] === '')       $errores[] = 'La fecha es obligatoria.';

            if (empty($errores)) {
                try {
                    if ($op === 'crear') {
                        $cols   = implode(',', array_keys($datos));
                        $params = ':' . implode(',:', array_keys($datos));
                        db_exec("INSERT INTO flotilla_siniestros ($cols) VALUES ($params)", $datos);
                        $nuevo_id = db_last_id();

                        // Gasto automático si hay monto de deducible
                        if ($datos['monto_deducible'] > 0) {
                            $cat = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre LIKE '%Siniestro%' LIMIT 1");
                            if ($cat) {
                                db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,fecha,concepto,monto,siniestro_id,creado_por)
                                         VALUES (:vid,:cat,:fecha,:con,:monto,:sin_id,:cp)",
                                    ['vid'=>$datos['vehiculo_id'],'cat'=>$cat['id'],
                                     'fecha'=>date('Y-m-d',strtotime($datos['fecha'])),
                                     'con'=>'Deducible siniestro · ' . ($datos['lugar'] ?? 'sin lugar'),
                                     'monto'=>$datos['monto_deducible'],'sin_id'=>$nuevo_id,'cp'=>$u['id']]);
                            }
                        }
                        registrar_auditoria('crear_siniestro','flotilla_siniestros',$nuevo_id,'Siniestro registrado');
                        flash_set('exito', 'Siniestro registrado.');
                    } else {
                        $edit_id = (int) input('edit_id', 0);
                        unset($datos['creado_por']);
                        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($datos)));
                        $datos['id'] = $edit_id;
                        db_exec("UPDATE flotilla_siniestros SET $sets WHERE id = :id", $datos);
                        flash_set('exito', 'Siniestro actualizado.');
                    }
                    $redir = $f_vehiculo ? "flotilla_vehiculo_ver.php?id=$f_vehiculo&tab=siniestros" : 'flotilla_siniestros.php';
                    header('Location: ' . url($redir));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'cerrar') {
            $sin_id = (int) input('sin_id', 0);
            db_exec("UPDATE flotilla_siniestros SET estado = 'cerrado', fecha_resolucion = CURDATE() WHERE id = :id", ['id' => $sin_id]);
            flash_set('exito', 'Siniestro cerrado.');
            header('Location: ' . url('flotilla_siniestros.php' . ($f_vehiculo ? "?vehiculo_id=$f_vehiculo" : '')));
            exit;
        }
    }
}

// Datos
$where  = ['1=1'];
$params = [];
if ($f_vehiculo) { $where[] = 's.vehiculo_id = :vid'; $params['vid'] = $f_vehiculo; }
if ($f_estado)   { $where[] = 's.estado = :est';      $params['est'] = $f_estado; }
if ($f_desde)    { $where[] = 'DATE(s.fecha) >= :desde'; $params['desde'] = $f_desde; }
if ($f_hasta)    { $where[] = 'DATE(s.fecha) <= :hasta'; $params['hasta'] = $f_hasta; }

$sid_forzado = flotilla_sucursal_forzada();
if ($sid_forzado) { $where[] = 'v.sucursal_id = :sid_f'; $params['sid_f'] = $sid_forzado; }

$sql_where = implode(' AND ', $where);

$siniestros = db_all(
    "SELECT s.*,
            v.placas, v.alias, v.marca, v.modelo,
            c.nombre_completo conductor_nombre
     FROM flotilla_siniestros s
     INNER JOIN flotilla_vehiculos v  ON s.vehiculo_id  = v.id
     LEFT  JOIN flotilla_conductores c ON s.conductor_id = c.id
     WHERE $sql_where
     ORDER BY s.fecha DESC",
    $params
);

$v_where = $sid_forzado ? "activo=1 AND sucursal_id=$sid_forzado" : "activo=1";
$vehiculos   = db_all("SELECT id, placas, alias, marca, modelo FROM flotilla_vehiculos WHERE $v_where ORDER BY alias, placas");
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

$titulo_pagina = 'Flotilla · Siniestros';
$pagina_activa = 'flotilla_siniestros';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';

$estados_sin = [
    'reportado'  => ['bg-amber-100',  'text-amber-800',  'Reportado'],
    'en_proceso' => ['bg-blue-100',   'text-blue-800',   'En proceso'],
    'resuelto'   => ['bg-emerald-100','text-emerald-800','Resuelto'],
    'cerrado'    => ['bg-zinc-100',   'text-zinc-600',   'Cerrado'],
];
$tipos_sin = [
    'colision'         => 'Colisión',
    'robo_parcial'     => 'Robo parcial',
    'robo_total'       => 'Robo total',
    'vandalismo'       => 'Vandalismo',
    'fenomeno_natural' => 'Fenómeno natural',
    'otro'             => 'Otro',
];
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <a href="<?= url('flotilla_vehiculos.php') ?>" class="text-zinc-500 hover:text-bacal-700 text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Flotilla
            </a>
            <span class="text-zinc-300">/</span>
            <h2 class="font-display text-xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-5 h-5 text-bacal-700"></i>
                Siniestros
            </h2>
        </div>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal-sin').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Registrar siniestro
        </button>
        <?php endif; ?>
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

    <!-- Filtros -->
    <form method="GET" class="flex flex-wrap gap-2">
        <?php if ($f_vehiculo): ?><input type="hidden" name="vehiculo_id" value="<?= $f_vehiculo ?>"><?php endif; ?>
        <select name="estado" onchange="this.form.submit()"
                class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los estados</option>
            <?php foreach ($estados_sin as $v => [$bg, $tx, $label]): ?>
            <option value="<?= $v ?>" <?= $f_estado === $v ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="desde" value="<?= e($f_desde) ?>" title="Desde"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        <input type="date" name="hasta" value="<?= e($f_hasta) ?>" title="Hasta"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        <button type="submit" class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Filtrar</button>
        <?php if ($f_estado || $f_desde || $f_hasta): ?>
        <a href="<?= url('flotilla_siniestros.php' . ($f_vehiculo ? "?vehiculo_id=$f_vehiculo" : '')) ?>"
           class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Lista -->
    <?php if (empty($siniestros)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="shield-check" class="w-12 h-12 mx-auto text-emerald-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin siniestros registrados</p>
        <p class="text-sm text-zinc-500 mt-1"><?= $f_estado ? 'Prueba con otro filtro.' : '¡Buena señal!' ?></p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($siniestros as $s):
            [$bg, $tx, $label] = $estados_sin[$s['estado']] ?? ['bg-zinc-100','text-zinc-600',$s['estado']];
            $abierto = in_array($s['estado'], ['reportado','en_proceso']);
        ?>
        <div class="bg-white rounded-xl border <?= $abierto ? 'border-amber-200' : 'border-zinc-200' ?> shadow-sm overflow-hidden">
            <div class="p-4 flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl <?= $abierto ? 'bg-amber-100 text-amber-700' : 'bg-zinc-100 text-zinc-500' ?> flex items-center justify-center flex-shrink-0">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-zinc-900"><?= $tipos_sin[$s['tipo']] ?? $s['tipo'] ?></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $bg ?> <?= $tx ?>"><?= $label ?></span>
                        </div>
                        <div class="text-sm text-zinc-600 mt-0.5">
                            <span class="font-semibold"><?= e($s['alias'] ?: "{$s['marca']} {$s['modelo']}") ?></span>
                            <span class="font-mono text-xs text-zinc-400 ml-1"><?= e($s['placas']) ?></span>
                        </div>
                        <div class="text-xs text-zinc-500 mt-1 space-y-0.5">
                            <div><?= fmt_fecha_hora($s['fecha']) ?><?= $s['lugar'] ? ' · ' . e($s['lugar']) : '' ?></div>
                            <?php if ($s['conductor_nombre']): ?>
                            <div><i data-lucide="user" class="w-3 h-3 inline mr-1"></i><?= e($s['conductor_nombre']) ?></div>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-zinc-700 mt-2 max-w-xl"><?= e($s['descripcion']) ?></p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2 flex-shrink-0 text-right text-xs">
                    <?php if ($s['monto_reparacion']): ?>
                    <div>
                        <div class="text-zinc-500">Reparación</div>
                        <div class="font-bold text-zinc-900 text-sm">$<?= number_format($s['monto_reparacion'], 2) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['monto_cubierto_seguro']): ?>
                    <div>
                        <div class="text-zinc-500">Cubierto seguro</div>
                        <div class="font-semibold text-emerald-700">$<?= number_format($s['monto_cubierto_seguro'], 2) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['numero_siniestro_aseg']): ?>
                    <div class="text-zinc-500">Siniestro: <span class="font-mono font-semibold text-zinc-700"><?= e($s['numero_siniestro_aseg']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($abierto && $puede_gestionar): ?>
                    <div class="flex gap-1 mt-1">
                        <button onclick="abrirEditarSiniestro(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                                class="px-2.5 py-1.5 rounded-lg border border-zinc-300 text-xs font-semibold text-zinc-600 hover:bg-zinc-50">
                            <i data-lucide="pencil" class="w-3.5 h-3.5 inline"></i> Editar
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('¿Cerrar este siniestro?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="cerrar">
                            <input type="hidden" name="sin_id" value="<?= $s['id'] ?>">
                            <button type="submit"
                                    class="px-2.5 py-1.5 rounded-lg bg-zinc-700 text-white text-xs font-semibold hover:bg-zinc-800">
                                Cerrar
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($s['hay_terceros'] && $s['descripcion_terceros']): ?>
            <div class="px-4 pb-3 border-t border-zinc-100 pt-2">
                <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide">Terceros: </span>
                <span class="text-xs text-zinc-700"><?= e($s['descripcion_terceros']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal siniestro -->
<div id="modal-sin" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalSin()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 id="sin-titulo" class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4 text-bacal-700"></i> Registrar siniestro
            </h3>
            <button onclick="cerrarModalSin()" class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" id="sin-op" value="crear">
            <input type="hidden" name="edit_id" id="sin-edit-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                    <select name="vehiculo_id_s" id="sin-vehiculo" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $f_vehiculo === (int)$v['id'] ? 'selected' : '' ?>>
                            <?= $v['alias'] ? e($v['alias']) . ' · ' : '' ?><?= e($v['placas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id" id="sin-conductor"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha y hora <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="fecha" id="sin-fecha" required value="<?= date('Y-m-d\TH:i') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo</label>
                    <select name="tipo" id="sin-tipo"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <?php foreach ($tipos_sin as $v => $l): ?>
                        <option value="<?= $v ?>"><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Lugar</label>
                    <input type="text" name="lugar" id="sin-lugar" maxlength="200"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                    <textarea name="descripcion" id="sin-descripcion" required rows="3" maxlength="2000"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-zinc-700 cursor-pointer">
                        <input type="checkbox" name="hay_terceros" id="sin-terceros" value="1" class="rounded text-bacal-700">
                        ¿Involucra terceros?
                    </label>
                    <textarea name="descripcion_terceros" id="sin-desc-terceros" rows="2"
                              placeholder="Describe a los terceros involucrados…"
                              class="w-full mt-2 px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500 hidden"></textarea>
                </div>
            </div>

            <div class="border-t border-zinc-100 pt-4">
                <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wide mb-3">Información del seguro</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Aseguradora</label>
                        <input type="text" name="aseguradora" id="sin-aseguradora" maxlength="100"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Número de siniestro</label>
                        <input type="text" name="numero_siniestro_aseg" id="sin-num-aseg" maxlength="60"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Deducible ($)</label>
                        <input type="number" name="monto_deducible" id="sin-deducible" step="0.01" min="0"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Monto de reparación ($)</label>
                        <input type="number" name="monto_reparacion" id="sin-reparacion" step="0.01" min="0"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Monto cubierto por seguro ($)</label>
                        <input type="number" name="monto_cubierto_seguro" id="sin-cubierto" step="0.01" min="0"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Estado del trámite</label>
                        <select name="estado_sin" id="sin-estado"
                                class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                            <option value="reportado">Reportado</option>
                            <option value="en_proceso">En proceso</option>
                            <option value="resuelto">Resuelto</option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="cerrarModalSin()"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Cancelar</button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('sin-terceros').addEventListener('change', function() {
    document.getElementById('sin-desc-terceros').classList.toggle('hidden', !this.checked);
});
function abrirEditarSiniestro(s) {
    document.getElementById('sin-op').value           = 'editar';
    document.getElementById('sin-edit-id').value      = s.id;
    document.getElementById('sin-titulo').innerHTML   = '<i data-lucide="pencil" class="w-4 h-4 text-bacal-700"></i> Editar siniestro';
    document.getElementById('sin-vehiculo').value     = s.vehiculo_id  || '';
    document.getElementById('sin-conductor').value    = s.conductor_id || '';
    document.getElementById('sin-fecha').value        = s.fecha ? s.fecha.replace(' ','T').substring(0,16) : '';
    document.getElementById('sin-tipo').value         = s.tipo         || 'colision';
    document.getElementById('sin-lugar').value        = s.lugar        || '';
    document.getElementById('sin-descripcion').value  = s.descripcion  || '';
    document.getElementById('sin-aseguradora').value  = s.aseguradora  || '';
    document.getElementById('sin-num-aseg').value     = s.numero_siniestro_aseg || '';
    document.getElementById('sin-deducible').value    = s.monto_deducible       || '';
    document.getElementById('sin-reparacion').value   = s.monto_reparacion      || '';
    document.getElementById('sin-cubierto').value     = s.monto_cubierto_seguro || '';
    document.getElementById('sin-estado').value       = s.estado       || 'reportado';
    const hasTerceros = !!parseInt(s.hay_terceros);
    document.getElementById('sin-terceros').checked   = hasTerceros;
    document.getElementById('sin-desc-terceros').value = s.descripcion_terceros || '';
    document.getElementById('sin-desc-terceros').classList.toggle('hidden', !hasTerceros);
    document.getElementById('modal-sin').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}
function cerrarModalSin() {
    document.getElementById('sin-op').value    = 'crear';
    document.getElementById('sin-titulo').innerHTML = '<i data-lucide="shield-alert" class="w-4 h-4 text-bacal-700"></i> Registrar siniestro';
    document.getElementById('modal-sin').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
