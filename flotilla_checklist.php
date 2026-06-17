<?php
/**
 * ============================================================================
 * flotilla_checklist.php - Checklist diario pre/post viaje
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();

$errores = [];
$modo    = (string) input('modo', 'historial'); // historial | nuevo | ver
$ver_id  = (int) input('id', 0);

// ----------------------------------------------------------------------------
// POST: guardar checklist completado
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'guardar_checklist') {
            $vehiculo_id     = (int) input('vehiculo_id', 0);
            $conductor_id    = (int) input('conductor_id', 0) ?: null;
            $tipo            = (string) input('tipo', 'pre_viaje');
            $km_odometro     = (int) input('km_odometro', 0) ?: null;
            $observaciones   = trim((string) input('observaciones_gen', '')) ?: null;
            $respuestas      = input('resp', []);

            if (!$vehiculo_id) $errores[] = 'Selecciona el vehículo.';
            if (!in_array($tipo, ['pre_viaje','post_viaje','diario'])) $tipo = 'pre_viaje';

            if (empty($errores)) {
                try {
                    // Calcular resultado global
                    $tiene_falla = false;
                    $tiene_obs   = false;
                    foreach ($respuestas as $val) {
                        if ($val === 'falla')       $tiene_falla = true;
                        elseif ($val === 'observacion') $tiene_obs = true;
                    }
                    $resultado = $tiene_falla ? 'no_apto' : ($tiene_obs ? 'observaciones' : 'ok');

                    db_exec(
                        "INSERT INTO flotilla_checklists
                             (vehiculo_id,conductor_id,tipo,km_odometro,resultado,observaciones_gen)
                         VALUES (:vid,:cid,:tipo,:km,:res,:obs)",
                        ['vid'=>$vehiculo_id,'cid'=>$conductor_id,'tipo'=>$tipo,
                         'km'=>$km_odometro,'res'=>$resultado,'obs'=>$observaciones]
                    );
                    $checklist_id = db_last_id();

                    // Guardar respuestas
                    foreach ($respuestas as $item_id => $valor) {
                        $item_id   = (int)$item_id;
                        $nota_item = trim((string)(input("nota_{$item_id}", '')));
                        if (!in_array($valor, ['ok','observacion','falla'])) continue;
                        db_exec(
                            "INSERT INTO flotilla_checklist_respuestas (checklist_id,item_id,resultado,nota)
                             VALUES (:cid,:iid,:val,:nota)
                             ON DUPLICATE KEY UPDATE resultado=VALUES(resultado), nota=VALUES(nota)",
                            ['cid'=>$checklist_id,'iid'=>$item_id,'val'=>$valor,
                             'nota'=>$nota_item ?: null]
                        );
                    }

                    // Actualizar km del vehículo si es mayor
                    if ($km_odometro) {
                        db_exec("UPDATE flotilla_vehiculos SET km_actual=:km
                                 WHERE id=:vid AND km_actual < :km2",
                                ['km'=>$km_odometro,'vid'=>$vehiculo_id,'km2'=>$km_odometro]);
                    }

                    registrar_auditoria('checklist','flotilla_checklists',$checklist_id,'Checklist guardado');

                    $msg = match($resultado) {
                        'ok'           => 'Checklist guardado — vehículo apto para operar.',
                        'observaciones'=> '⚠ Checklist con observaciones guardado.',
                        'no_apto'      => '✗ Vehículo NO APTO — hay fallas registradas. Revisa antes de operar.',
                    };
                    flash_set('exito', $msg);
                    header('Location: ' . url("flotilla_checklist.php?modo=ver&id=$checklist_id"));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Datos comunes
$sid_forzado = flotilla_sucursal_forzada();
$v_where = $sid_forzado ? "activo=1 AND estado != 'baja' AND sucursal_id=$sid_forzado" : "activo=1 AND estado != 'baja'";
$vehiculos   = db_all("SELECT id, placas, alias, marca, modelo FROM flotilla_vehiculos WHERE $v_where ORDER BY alias, placas");
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

// Ítems agrupados por categoría (campo texto directo)
$items_raw = db_all("SELECT * FROM flotilla_checklist_items WHERE activo=1 ORDER BY categoria, orden");
$items_por_cat = [];
foreach ($items_raw as $item) {
    $items_por_cat[$item['categoria']][] = $item;
}

$titulo_pagina = 'Flotilla · Checklist';
$pagina_activa = 'flotilla_checklist';

// Modo ver
$checklist_ver  = null;
$respuestas_ver = [];
if ($modo === 'ver' && $ver_id) {
    $checklist_ver = db_one(
        "SELECT ch.*, v.placas, v.alias, v.marca, v.modelo, c.nombre_completo conductor_nombre
         FROM flotilla_checklists ch
         INNER JOIN flotilla_vehiculos v  ON ch.vehiculo_id  = v.id
         LEFT  JOIN flotilla_conductores c ON ch.conductor_id = c.id
         WHERE ch.id = :id",
        ['id' => $ver_id]
    );
    if ($checklist_ver) {
        $rows = db_all(
            "SELECT r.*, i.nombre item_nombre, i.categoria cat_nombre, i.obligatorio
             FROM flotilla_checklist_respuestas r
             INNER JOIN flotilla_checklist_items i ON r.item_id = i.id
             WHERE r.checklist_id = :cid
             ORDER BY i.categoria, i.orden",
            ['cid' => $ver_id]
        );
        foreach ($rows as $r) {
            $respuestas_ver[$r['cat_nombre']][] = $r;
        }
    }
}

// Historial
$historial = [];
if ($modo === 'historial') {
    $hist_where = $sid_forzado ? "WHERE v.sucursal_id = $sid_forzado" : "";
    $historial = db_all(
        "SELECT ch.*,
                v.placas, v.alias, v.marca, v.modelo,
                c.nombre_completo conductor_nombre
         FROM flotilla_checklists ch
         INNER JOIN flotilla_vehiculos v ON ch.vehiculo_id = v.id
         LEFT  JOIN flotilla_conductores c ON ch.conductor_id = c.id
         $hist_where
         ORDER BY ch.fecha DESC
         LIMIT 50"
    );
}

require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';

$tipos_label = ['pre_viaje'=>'Pre-viaje','post_viaje'=>'Post-viaje','diario'=>'Diario'];
$res_cfg     = [
    'ok'            => ['bg-emerald-100','text-emerald-800','check-circle-2','Apto'],
    'observaciones' => ['bg-amber-100',  'text-amber-800',  'alert-circle',  'Con observaciones'],
    'no_apto'       => ['bg-red-100',    'text-red-800',    'x-circle',      'No apto'],
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
                <i data-lucide="clipboard-check" class="w-5 h-5 text-bacal-700"></i>
                Checklist vehicular
            </h2>
        </div>
        <div class="flex gap-2">
            <?php if ($modo !== 'historial'): ?>
            <a href="<?= url('flotilla_checklist.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50 flex items-center gap-1.5">
                <i data-lucide="list" class="w-4 h-4"></i> Historial
            </a>
            <?php endif; ?>
            <?php if ($modo !== 'nuevo'): ?>
            <a href="<?= url('flotilla_checklist.php?modo=nuevo') ?>"
               class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Nuevo checklist
            </a>
            <?php endif; ?>
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

    <!-- ===== MODO: NUEVO CHECKLIST ===== -->
    <?php if ($modo === 'nuevo'): ?>
    <form method="POST" class="space-y-5">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="guardar_checklist">

        <!-- Cabecera del checklist -->
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <h3 class="font-semibold text-zinc-800 mb-4 text-sm">Datos generales</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                    <select name="vehiculo_id" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= $v['alias'] ? e($v['alias']) . ' · ' : '' ?><?= e($v['placas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo</label>
                    <select name="tipo"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="pre_viaje">Pre-viaje</option>
                        <option value="post_viaje">Post-viaje</option>
                        <option value="diario">Diario general</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro</label>
                    <input type="number" name="km_odometro" min="0" step="1"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
        </div>

        <!-- Ítems por categoría -->
        <?php if (empty($items_por_cat)): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
            Sin ítems de checklist configurados. Verifica que la tabla <code>flotilla_checklist_items</code> tenga datos.
        </div>
        <?php else: ?>
        <?php foreach ($items_por_cat as $cat_nombre => $cat_items): ?>
        <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <div class="bg-zinc-50 border-b border-zinc-200 px-5 py-3">
                <h3 class="font-semibold text-sm text-zinc-800"><?= e($cat_nombre) ?></h3>
            </div>
            <div class="divide-y divide-zinc-100">
                <?php foreach ($cat_items as $item): ?>
                <div class="px-5 py-3 flex items-center gap-4">
                    <div class="flex-1">
                        <div class="text-sm font-medium text-zinc-800">
                            <?= e($item['nombre']) ?>
                            <?php if ($item['obligatorio']): ?>
                            <span class="text-red-400 ml-1">*</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="resp[<?= $item['id'] ?>]" value="ok" checked
                                   class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-emerald-700">OK</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="resp[<?= $item['id'] ?>]" value="observacion"
                                   onchange="toggleNota(<?= $item['id'] ?>, this.value)"
                                   class="text-amber-600 focus:ring-amber-500">
                            <span class="text-sm font-medium text-amber-700">Obs.</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="resp[<?= $item['id'] ?>]" value="falla"
                                   onchange="toggleNota(<?= $item['id'] ?>, this.value)"
                                   class="text-red-600 focus:ring-red-500">
                            <span class="text-sm font-medium text-red-700">Falla</span>
                        </label>
                    </div>
                </div>
                <div id="nota-<?= $item['id'] ?>" class="hidden px-5 pb-3 -mt-2">
                    <input type="text" name="nota_<?= $item['id'] ?>" maxlength="200"
                           placeholder="Describe la observación o falla…"
                           class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-amber-50 text-sm text-amber-900 placeholder-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Observaciones generales -->
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <label class="block text-xs font-bold text-zinc-700 mb-1">Observaciones generales</label>
            <textarea name="observaciones_gen" rows="3" maxlength="1000"
                      class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?= url('flotilla_checklist.php') ?>"
               class="px-5 py-2.5 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Cancelar</a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Guardar checklist
            </button>
        </div>
    </form>

    <!-- ===== MODO: VER CHECKLIST ===== -->
    <?php elseif ($modo === 'ver' && $checklist_ver): ?>
    <?php [$rcbg, $rctx, $rcicon, $rclbl] = $res_cfg[$checklist_ver['resultado']] ?? $res_cfg['ok']; ?>
    <div class="space-y-4">
        <!-- Resumen -->
        <div class="bg-white rounded-xl border <?= $checklist_ver['resultado'] === 'no_apto' ? 'border-red-200' : ($checklist_ver['resultado'] === 'observaciones' ? 'border-amber-200' : 'border-emerald-200') ?> p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold <?= $rcbg ?> <?= $rctx ?>">
                            <i data-lucide="<?= $rcicon ?>" class="w-4 h-4"></i> <?= $rclbl ?>
                        </span>
                        <span class="text-sm text-zinc-500"><?= $tipos_label[$checklist_ver['tipo']] ?? $checklist_ver['tipo'] ?></span>
                    </div>
                    <div class="text-lg font-bold text-zinc-900">
                        <?= e($checklist_ver['alias'] ?: "{$checklist_ver['marca']} {$checklist_ver['modelo']}") ?>
                        <span class="font-mono text-sm text-zinc-400 ml-1"><?= e($checklist_ver['placas']) ?></span>
                    </div>
                    <?php if ($checklist_ver['conductor_nombre']): ?>
                    <div class="text-sm text-zinc-600 mt-0.5">Conductor: <?= e($checklist_ver['conductor_nombre']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="text-right text-sm text-zinc-500">
                    <div><?= fmt_fecha_hora($checklist_ver['fecha']) ?></div>
                    <?php if ($checklist_ver['km_odometro']): ?>
                    <div class="font-semibold text-zinc-700 mt-0.5"><?= number_format($checklist_ver['km_odometro']) ?> km</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($checklist_ver['observaciones_gen']): ?>
            <div class="mt-3 pt-3 border-t border-zinc-100 text-sm text-zinc-700">
                <span class="font-semibold">Observaciones: </span><?= e($checklist_ver['observaciones_gen']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Respuestas por categoría -->
        <?php foreach ($respuestas_ver as $cat => $resps): ?>
        <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <div class="bg-zinc-50 border-b border-zinc-200 px-5 py-3">
                <h3 class="font-semibold text-sm text-zinc-800"><?= e($cat) ?></h3>
            </div>
            <div class="divide-y divide-zinc-100">
                <?php foreach ($resps as $r): ?>
                <?php
                    [$ricon, $rcolor, $rlbl] = match($r['resultado']) {
                        'ok'         => ['check',          'text-emerald-600', 'OK'],
                        'observacion'=> ['alert-circle',   'text-amber-600',   'Observación'],
                        'falla'      => ['x',              'text-red-600',     'Falla'],
                        default      => ['minus',          'text-zinc-400',    $r['resultado']],
                    };
                ?>
                <div class="px-5 py-3 flex items-start gap-3">
                    <i data-lucide="<?= $ricon ?>" class="w-4 h-4 mt-0.5 flex-shrink-0 <?= $rcolor ?>"></i>
                    <div class="flex-1">
                        <div class="text-sm text-zinc-800 flex items-center gap-2">
                            <?= e($r['item_nombre']) ?>
                            <span class="text-xs font-semibold <?= $rcolor ?>"><?= $rlbl ?></span>
                        </div>
                        <?php if ($r['nota']): ?>
                        <div class="text-xs text-amber-800 mt-0.5 bg-amber-50 px-2 py-1 rounded"><?= e($r['nota']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="flex gap-2">
            <a href="<?= url('flotilla_checklist.php') ?>"
               class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">← Historial</a>
            <a href="<?= url('flotilla_vehiculo_ver.php?id=' . $checklist_ver['vehiculo_id']) ?>"
               class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Ver vehículo</a>
        </div>
    </div>

    <!-- ===== MODO: HISTORIAL ===== -->
    <?php else: ?>
    <?php if (empty($historial)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="clipboard" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin checklists registrados</p>
        <a href="<?= url('flotilla_checklist.php?modo=nuevo') ?>"
           class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo checklist
        </a>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100 text-sm">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Fecha</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Vehículo</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Tipo</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Conductor</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Km</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Resultado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php foreach ($historial as $h):
                [$hbg, $htx, $hicon, $hlbl] = $res_cfg[$h['resultado']] ?? $res_cfg['ok'];
            ?>
            <tr class="hover:bg-zinc-50">
                <td class="px-4 py-3 text-zinc-600 whitespace-nowrap"><?= fmt_fecha_hora($h['fecha']) ?></td>
                <td class="px-4 py-3">
                    <div class="font-semibold text-zinc-900"><?= e($h['alias'] ?: "{$h['marca']} {$h['modelo']}") ?></div>
                    <div class="text-xs font-mono text-zinc-400"><?= e($h['placas']) ?></div>
                </td>
                <td class="px-4 py-3 text-zinc-700"><?= $tipos_label[$h['tipo']] ?? $h['tipo'] ?></td>
                <td class="px-4 py-3 text-zinc-600"><?= $h['conductor_nombre'] ? e($h['conductor_nombre']) : '<span class="text-zinc-400">—</span>' ?></td>
                <td class="px-4 py-3 text-zinc-600"><?= $h['km_odometro'] ? number_format($h['km_odometro']) : '—' ?></td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= $hbg ?> <?= $htx ?>">
                        <i data-lucide="<?= $hicon ?>" class="w-3 h-3"></i> <?= $hlbl ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="<?= url("flotilla_checklist.php?modo=ver&id={$h['id']}") ?>"
                       class="text-xs font-semibold text-bacal-700 hover:underline">Ver</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
function toggleNota(itemId, val) {
    const div = document.getElementById('nota-' + itemId);
    if (div) div.classList.toggle('hidden', val === 'ok');
}
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio' && e.target.name && e.target.name.startsWith('resp[')) {
        const m = e.target.name.match(/\[(\d+)\]/);
        if (m) toggleNota(m[1], e.target.value);
    }
});
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
