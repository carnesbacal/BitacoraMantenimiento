<?php
/**
 * ============================================================================
 * equipo_componentes.php
 * ============================================================================
 * Listado, creación y edición de componentes de un equipo.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/componentes_helpers.php';

requerir_login();
$u = usuario_actual();
$es_admin = tiene_permiso('administrar');
$puede_gestionar = $es_admin || tiene_permiso('resolver'); // técnicos también

$equipo_id = (int) input('id', 0);
if ($equipo_id <= 0) {
    flash_set('error', 'Equipo no especificado.');
    header('Location: ' . url('equipos.php'));
    exit;
}

$equipo = db_one(
    "SELECT e.*, s.codigo AS sucursal_codigo, s.nombre AS sucursal_nombre,
            a.nombre AS area_nombre
     FROM equipos e
     LEFT JOIN sucursales s ON e.sucursal_id = s.id
     LEFT JOIN areas a ON e.area_id = a.id
     WHERE e.id = :id AND e.activo = 1",
    ['id' => $equipo_id]
);

if (!$equipo) {
    flash_set('error', 'Equipo no encontrado.');
    header('Location: ' . url('equipos.php'));
    exit;
}

// Permisos por sucursal
if (!tiene_permiso('ver_todas_sucursales') && (int) $u['sucursal_id'] !== (int) $equipo['sucursal_id']) {
    flash_set('error', 'No tienes permiso para ver este equipo.');
    header('Location: ' . url('equipos.php'));
    exit;
}

$errores = [];

// ----------------------------------------------------------------------------
// Procesar POST
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token inválido.';
    } else {
        $op = (string) input('op', '');

        try {
            if ($op === 'crear') {
                $datos = [
                    'equipo_id' => $equipo_id,
                    'nombre' => trim((string) input('nombre', '')),
                    'tipo' => trim((string) input('tipo', '')) ?: null,
                    'marca' => trim((string) input('marca', '')) ?: null,
                    'modelo' => trim((string) input('modelo', '')) ?: null,
                    'numero_parte' => trim((string) input('numero_parte', '')) ?: null,
                    'numero_serie' => trim((string) input('numero_serie', '')) ?: null,
                    'fecha_instalacion' => trim((string) input('fecha_instalacion', '')) ?: null,
                    'vida_util_meses' => (int) input('vida_util_meses', 0) ?: null,
                    'proxima_revision' => trim((string) input('proxima_revision', '')) ?: null,
                    'costo_unitario' => (float) input('costo_unitario', 0) ?: null,
                    'proveedor_id' => (int) input('proveedor_id', 0) ?: null,
                    'estado' => (string) input('estado', 'operando'),
                    'criticidad' => (string) input('criticidad', 'media'),
                    'posicion' => trim((string) input('posicion', '')) ?: null,
                    'notas' => trim((string) input('notas', '')) ?: null,
                ];
                if ($datos['nombre'] === '') {
                    $errores[] = 'El nombre del componente es obligatorio.';
                } else {
                    crear_componente($datos, (int) $u['id']);
                    registrar_auditoria('crear_componente', 'equipo_componentes', $equipo_id, $datos['nombre']);
                    flash_set('success', "Componente '{$datos['nombre']}' agregado.");
                    header('Location: ' . url("equipo_componentes.php?id=$equipo_id"));
                    exit;
                }
            } elseif ($op === 'actualizar_estado') {
                $cid = (int) input('componente_id', 0);
                $nuevo_estado = (string) input('estado', '');
                if ($cid > 0 && in_array($nuevo_estado, ['operando','desgaste','falla','reemplazado','retirado'], true)) {
                    db_exec("UPDATE equipo_componentes SET estado = :est, actualizado_por_id = :uid WHERE id = :id",
                        ['est' => $nuevo_estado, 'uid' => $u['id'], 'id' => $cid]);
                    registrar_historial_componente($cid, 'revisado', "Estado cambiado a: $nuevo_estado", null, (int) $u['id']);
                    flash_set('success', 'Estado actualizado.');
                    header('Location: ' . url("equipo_componentes.php?id=$equipo_id"));
                    exit;
                }
            } elseif ($op === 'eliminar') {
                $cid = (int) input('componente_id', 0);
                if ($cid > 0) {
                    eliminar_componente($cid, (int) $u['id']);
                    flash_set('success', 'Componente eliminado.');
                    header('Location: ' . url("equipo_componentes.php?id=$equipo_id"));
                    exit;
                }
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

$componentes = listar_componentes_de_equipo($equipo_id);
$proveedores = db_all("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre");

// Stats rápidos
$stats = [
    'total' => count($componentes),
    'operando' => count(array_filter($componentes, fn($c) => $c['estado'] === 'operando')),
    'desgaste' => count(array_filter($componentes, fn($c) => $c['estado'] === 'desgaste')),
    'falla' => count(array_filter($componentes, fn($c) => $c['estado'] === 'falla')),
    'criticos' => count(array_filter($componentes, fn($c) => in_array($c['criticidad'], ['alta','critica'], true))),
];

$titulo_pagina = 'Componentes · ' . $equipo['nombre'];
$pagina_activa = 'equipos';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header con datos del equipo -->
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?= url('equipo_ver.php?id=' . $equipo_id) ?>"
           class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-0.5">
                <i data-lucide="cpu" class="w-3 h-3"></i>
                <span class="font-mono"><?= e($equipo['codigo_inventario']) ?></span>
                <span>·</span>
                <span><?= e($equipo['sucursal_codigo']) ?></span>
                <?php if (!empty($equipo['area_nombre'])): ?>
                <span>·</span>
                <span><?= e($equipo['area_nombre']) ?></span>
                <?php endif; ?>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 truncate">
                Componentes de <?= e($equipo['nombre']) ?>
            </h2>
        </div>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal_nuevo').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nuevo componente
        </button>
        <?php endif; ?>
    </div>

    <!-- Errores -->
    <?php if (!empty($errores)): ?>
    <div class="px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs">
            <?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- KPIs rápidos -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Total</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= $stats['total'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold">Operando</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700"><?= $stats['operando'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-amber-700 uppercase tracking-wider font-bold">Con desgaste</div>
            <div class="font-display text-2xl font-extrabold text-amber-700"><?= $stats['desgaste'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-bacal-700 uppercase tracking-wider font-bold">En falla</div>
            <div class="font-display text-2xl font-extrabold text-bacal-700"><?= $stats['falla'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Críticos</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= $stats['criticos'] ?></div>
        </div>
    </div>

    <!-- Listado -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <?php if (empty($componentes)): ?>
        <div class="px-6 py-16 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                <i data-lucide="boxes" class="w-8 h-8 text-zinc-400"></i>
            </div>
            <p class="text-sm font-semibold text-zinc-700 mb-1">Sin componentes registrados</p>
            <?php if ($puede_gestionar): ?>
            <p class="text-xs text-zinc-500 mb-4">Agrega motor, bandas, rodamientos, sensores, filtros, etc. para llevar control individual.</p>
            <button onclick="document.getElementById('modal_nuevo').showModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> Agregar primer componente
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Componente</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Marca / Modelo</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">No. Parte</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Estado</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Criticidad</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Próx. revisión</th>
                        <?php if ($puede_gestionar): ?>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($componentes as $c):
                    $est_lbl = etiqueta_estado_componente($c['estado']);
                    $crit_lbl = etiqueta_criticidad_componente($c['criticidad']);
                    $rev_color = '';
                    $rev_texto = '—';
                    if (!empty($c['proxima_revision'])) {
                        $ts = strtotime($c['proxima_revision']);
                        $rev_texto = date('d/M/Y', $ts);
                        $diff = $ts - time();
                        if ($diff < 0) $rev_color = 'text-bacal-700 font-bold';
                        elseif ($diff < 30 * 86400) $rev_color = 'text-amber-700 font-bold';
                    }
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2.5">
                        <div class="font-semibold text-zinc-900"><?= e($c['nombre']) ?></div>
                        <?php if (!empty($c['tipo'])): ?>
                        <div class="text-[10px] text-zinc-500"><?= e($c['tipo']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['posicion'])): ?>
                        <div class="text-[10px] text-zinc-400">📍 <?= e($c['posicion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-zinc-700">
                        <?php if (!empty($c['marca']) || !empty($c['modelo'])): ?>
                        <?= e(trim(($c['marca'] ?? '') . ' ' . ($c['modelo'] ?? ''))) ?>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs font-mono text-zinc-600">
                        <?= !empty($c['numero_parte']) ? e($c['numero_parte']) : '<span class="text-zinc-400">—</span>' ?>
                    </td>
                    <td class="px-3 py-2.5">
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                              style="color: <?= e($est_lbl['color']) ?>; background-color: <?= e($est_lbl['color']) ?>15">
                            <i data-lucide="<?= e($est_lbl['icono']) ?>" class="w-3 h-3"></i>
                            <?= e($est_lbl['label']) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2.5">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                              style="color: <?= e($crit_lbl['color']) ?>; background-color: <?= e($crit_lbl['color']) ?>15">
                            <?= e($crit_lbl['label']) ?>
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-xs <?= $rev_color ?>">
                        <?= $rev_texto ?>
                    </td>
                    <?php if ($puede_gestionar): ?>
                    <td class="px-3 py-2.5 text-right">
                        <div class="flex justify-end gap-1">
                            <!-- Cambiar estado rápido -->
                            <details class="relative">
                                <summary class="cursor-pointer p-1.5 rounded hover:bg-zinc-100 text-zinc-500 list-none">
                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                </summary>
                                <div class="absolute right-0 top-full mt-1 bg-white border border-zinc-200 rounded-lg shadow-lg p-1 z-20 w-48">
                                    <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider px-2 py-1">Cambiar estado</div>
                                    <?php foreach (['operando','desgaste','falla','reemplazado','retirado'] as $est_op):
                                        if ($est_op === $c['estado']) continue;
                                        $lbl = etiqueta_estado_componente($est_op);
                                    ?>
                                    <form method="POST" class="inline-block w-full">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="op" value="actualizar_estado">
                                        <input type="hidden" name="componente_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="estado" value="<?= e($est_op) ?>">
                                        <button type="submit" class="w-full text-left px-2 py-1 rounded hover:bg-zinc-50 text-xs flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full" style="background-color: <?= e($lbl['color']) ?>"></span>
                                            <?= e($lbl['label']) ?>
                                        </button>
                                    </form>
                                    <?php endforeach; ?>
                                    <hr class="my-1 border-zinc-100">
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este componente?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="op" value="eliminar">
                                        <input type="hidden" name="componente_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="w-full text-left px-2 py-1 rounded hover:bg-bacal-50 text-bacal-700 text-xs flex items-center gap-1.5">
                                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </details>
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

</div>

<?php if ($puede_gestionar): ?>
<!-- Modal: Nuevo componente -->
<dialog id="modal_nuevo" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-2xl p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="crear">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-bacal-700"></i>
                Nuevo componente
            </h3>
            <button type="button" onclick="document.getElementById('modal_nuevo').close()"
                    class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="150"
                           placeholder="ej. Motor eléctrico, Banda de transmisión, Filtro de aceite"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Tipo</label>
                    <input type="text" name="tipo" maxlength="80"
                           placeholder="Motor, Sensor, Filtro, Banda..."
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Posición</label>
                    <input type="text" name="posicion" maxlength="100"
                           placeholder="Lado izquierdo, etapa 2..."
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Marca</label>
                    <input type="text" name="marca" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Modelo</label>
                    <input type="text" name="modelo" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">No. parte</label>
                    <input type="text" name="numero_parte" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">No. serie</label>
                    <input type="text" name="numero_serie" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Proveedor habitual</label>
                    <select name="proveedor_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">— Ninguno —</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha instalación</label>
                    <input type="date" name="fecha_instalacion"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Vida útil (meses)</label>
                    <input type="number" name="vida_util_meses" min="0" max="600"
                           placeholder="ej. 60"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Próxima revisión</label>
                    <input type="date" name="proxima_revision"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Costo unitario ($)</label>
                    <input type="number" name="costo_unitario" min="0" step="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Estado inicial</label>
                    <select name="estado" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="operando" selected>Operando</option>
                        <option value="desgaste">Con desgaste</option>
                        <option value="falla">En falla</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Criticidad</label>
                    <select name="criticidad" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Notas</label>
                <textarea name="notas" rows="3"
                          placeholder="Especificaciones, observaciones, recordatorios..."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_nuevo').close()"
                    class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                Crear componente
            </button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
