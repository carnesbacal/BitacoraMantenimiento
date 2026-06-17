<?php
/**
 * ============================================================================
 * flotilla_conductores.php - Gestión de conductores de la flotilla
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$errores = [];

if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'crear' || $op === 'editar') {
            $datos = [
                'nombre_completo'  => trim((string) input('nombre_completo', '')),
                'numero_empleado'  => trim((string) input('numero_empleado', '')) ?: null,
                'telefono'         => trim((string) input('telefono', '')) ?: null,
                'email'            => trim((string) input('email', '')) ?: null,
                'sucursal_id'      => (int) input('sucursal_id', 0) ?: null,
                'licencia_numero'  => trim((string) input('licencia_numero', '')) ?: null,
                'licencia_tipo'    => trim((string) input('licencia_tipo', '')) ?: null,
                'licencia_vence'   => trim((string) input('licencia_vence', '')) ?: null,
                'notas'            => trim((string) input('notas', '')) ?: null,
                'activo'           => 1,
            ];

            if ($datos['nombre_completo'] === '') $errores[] = 'El nombre es obligatorio.';

            if (empty($errores)) {
                try {
                    if ($op === 'crear') {
                        $cols   = implode(',', array_keys($datos));
                        $params = ':' . implode(',:', array_keys($datos));
                        db_exec("INSERT INTO flotilla_conductores ($cols) VALUES ($params)", $datos);
                        flash_set('exito', 'Conductor registrado.');
                    } else {
                        $edit_id = (int) input('edit_id', 0);
                        $sets    = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($datos)));
                        $datos['id'] = $edit_id;
                        db_exec("UPDATE flotilla_conductores SET $sets WHERE id = :id", $datos);
                        flash_set('exito', 'Conductor actualizado.');
                    }
                    header('Location: ' . url('flotilla_conductores.php'));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'toggle' && tiene_permiso('administrar')) {
            $tid = (int) input('toggle_id', 0);
            $c = db_one("SELECT id, activo FROM flotilla_conductores WHERE id = :id", ['id' => $tid]);
            if ($c) {
                $nuevo = $c['activo'] ? 0 : 1;
                db_exec("UPDATE flotilla_conductores SET activo = :a WHERE id = :id", ['a' => $nuevo, 'id' => $tid]);
                flash_set('exito', $nuevo ? 'Conductor activado.' : 'Conductor desactivado.');
            }
            header('Location: ' . url('flotilla_conductores.php'));
            exit;
        }
    }
}

$conductores = db_all(
    "SELECT c.*, s.nombre sucursal_nombre,
            (SELECT COUNT(*) FROM flotilla_vehiculos WHERE conductor_asignado_id = c.id AND activo = 1) vehiculos_asignados,
            DATEDIFF(c.licencia_vence, CURDATE()) dias_licencia
     FROM flotilla_conductores c
     LEFT JOIN sucursales s ON c.sucursal_id = s.id
     ORDER BY c.activo DESC, c.nombre_completo ASC"
);
$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")
    : [];

$titulo_pagina = 'Flotilla · Conductores';
$pagina_activa = 'flotilla_conductores';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <a href="<?= url('flotilla_vehiculos.php') ?>"
               class="text-zinc-500 hover:text-bacal-700 flex items-center gap-1 text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Flotilla
            </a>
            <span class="text-zinc-300">/</span>
            <h2 class="font-display text-xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-bacal-700"></i>
                Conductores
            </h2>
        </div>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal-conductor').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo conductor
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash -->
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

    <!-- Lista -->
    <?php if (empty($conductores)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="user-x" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin conductores registrados</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 border-b border-zinc-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Conductor</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden md:table-cell">Licencia</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden lg:table-cell">Sucursal</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden md:table-cell">Vehículos</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Estado</th>
                    <?php if ($puede_gestionar): ?>
                    <th class="px-4 py-3"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <?php foreach ($conductores as $c):
                    $lic_dias = $c['dias_licencia'];
                    $lic_alerta = $lic_dias !== null && $lic_dias <= 60;
                    $lic_vencida = $lic_dias !== null && $lic_dias < 0;
                ?>
                <tr class="hover:bg-zinc-50 <?= !$c['activo'] ? 'opacity-50' : '' ?>">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-bacal-100 text-bacal-700 font-bold text-sm flex items-center justify-center flex-shrink-0">
                                <?= strtoupper(substr($c['nombre_completo'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-semibold text-zinc-900"><?= e($c['nombre_completo']) ?></div>
                                <div class="text-xs text-zinc-500 flex items-center gap-2">
                                    <?= $c['numero_empleado'] ? '#' . e($c['numero_empleado']) : '' ?>
                                    <?= $c['telefono'] ? '<i data-lucide="phone" class="w-3 h-3 inline"></i> ' . e($c['telefono']) : '' ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <?php if ($c['licencia_numero']): ?>
                        <div class="font-mono text-xs font-semibold <?= $lic_vencida ? 'text-red-700' : ($lic_alerta ? 'text-amber-700' : 'text-zinc-700') ?>">
                            <?= e($c['licencia_numero']) ?>
                            <?= $c['licencia_tipo'] ? " ({$c['licencia_tipo']})" : '' ?>
                        </div>
                        <?php if ($c['licencia_vence']): ?>
                        <div class="text-xs <?= $lic_vencida ? 'text-red-600 font-bold' : ($lic_alerta ? 'text-amber-600' : 'text-zinc-400') ?>">
                            Vence: <?= fmt_fecha($c['licencia_vence']) ?>
                            <?= $lic_vencida ? ' · VENCIDA' : ($lic_alerta ? " · {$lic_dias}d" : '') ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-zinc-400 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell text-zinc-600 text-sm">
                        <?= $c['sucursal_nombre'] ?? '—' ?>
                    </td>
                    <td class="px-4 py-3 text-center hidden md:table-cell">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full <?= $c['vehiculos_asignados'] > 0 ? 'bg-bacal-100 text-bacal-700 font-bold' : 'bg-zinc-100 text-zinc-400' ?> text-xs">
                            <?= $c['vehiculos_asignados'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($c['activo']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activo</span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($puede_gestionar): ?>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="abrirEditarConductor(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)"
                                    class="p-1.5 rounded-lg hover:bg-zinc-100 text-zinc-500 hover:text-zinc-700">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <?php if (tiene_permiso('administrar')): ?>
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="toggle">
                                <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                <button type="submit"
                                        class="p-1.5 rounded-lg hover:bg-zinc-100 text-zinc-400 hover:text-zinc-600"
                                        title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>">
                                    <i data-lucide="<?= $c['activo'] ? 'eye-off' : 'eye' ?>" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal crear/editar conductor -->
<div id="modal-conductor" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalConductor()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 id="modal-conductor-titulo" class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4 text-bacal-700"></i>
                Nuevo conductor
            </h3>
            <button onclick="cerrarModalConductor()" class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" id="conductor-op" value="crear">
            <input type="hidden" name="edit_id" id="conductor-edit-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_completo" id="cond-nombre" required maxlength="150"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Número de empleado</label>
                    <input type="text" name="numero_empleado" id="cond-empleado" maxlength="30"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Teléfono</label>
                    <input type="tel" name="telefono" id="cond-telefono" maxlength="20"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Email</label>
                    <input type="email" name="email" id="cond-email" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <?php if ($sucursales): ?>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Sucursal</label>
                    <select name="sucursal_id" id="cond-sucursal"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin asignar</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">No. Licencia</label>
                    <input type="text" name="licencia_numero" id="cond-licencia-num" maxlength="50"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo de licencia</label>
                    <select name="licencia_tipo" id="cond-licencia-tipo"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">—</option>
                        <option value="A">A - Motocicleta</option>
                        <option value="B">B - Auto particular</option>
                        <option value="C">C - Camión unitario</option>
                        <option value="D">D - Autobús</option>
                        <option value="E">E - Tractocamión</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Licencia vence</label>
                    <input type="date" name="licencia_vence" id="cond-licencia-vence"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                    <textarea name="notas" id="cond-notas" rows="2" maxlength="1000"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="cerrarModalConductor()"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Cancelar</button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirEditarConductor(c) {
    document.getElementById('conductor-op').value         = 'editar';
    document.getElementById('conductor-edit-id').value    = c.id;
    document.getElementById('modal-conductor-titulo').innerHTML =
        '<i data-lucide="pencil" class="w-4 h-4 text-bacal-700"></i> Editar conductor';
    document.getElementById('cond-nombre').value          = c.nombre_completo  || '';
    document.getElementById('cond-empleado').value        = c.numero_empleado  || '';
    document.getElementById('cond-telefono').value        = c.telefono         || '';
    document.getElementById('cond-email').value           = c.email            || '';
    document.getElementById('cond-licencia-num').value    = c.licencia_numero  || '';
    document.getElementById('cond-licencia-vence').value  = c.licencia_vence   || '';
    document.getElementById('cond-notas').value           = c.notas            || '';
    const st = document.getElementById('cond-sucursal');
    if (st) st.value = c.sucursal_id || '';
    const lt = document.getElementById('cond-licencia-tipo');
    if (lt) lt.value = c.licencia_tipo || '';
    document.getElementById('modal-conductor').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}
function cerrarModalConductor() {
    document.getElementById('conductor-op').value      = 'crear';
    document.getElementById('conductor-edit-id').value = '';
    document.getElementById('modal-conductor-titulo').innerHTML =
        '<i data-lucide="user-plus" class="w-4 h-4 text-bacal-700"></i> Nuevo conductor';
    document.getElementById('modal-conductor').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
