<?php
/**
 * ============================================================================
 * equipo_refacciones.php
 * ============================================================================
 * Gestiona las refacciones compatibles con un equipo:
 *   - Lista las refacciones vinculadas (directa o vía componentes)
 *   - Permite agregar nuevas compatibilidades
 *   - Permite quitar vínculos
 *   - Muestra refacciones frecuentemente usadas en este equipo
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';
require_once __DIR__ . '/config/incidencia_refacciones_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$equipo_id = (int) input('id', 0);
$equipo = $equipo_id > 0
    ? db_one("SELECT e.*, s.codigo AS sucursal_codigo, s.nombre AS sucursal_nombre, a.nombre AS area_nombre
              FROM equipos e
              LEFT JOIN sucursales s ON e.sucursal_id = s.id
              LEFT JOIN areas a ON e.area_id = a.id
              WHERE e.id = :id AND e.activo = 1", ['id' => $equipo_id])
    : null;

if (!$equipo) {
    flash_set('error', 'Equipo no encontrado.');
    header('Location: ' . url('equipos.php'));
    exit;
}

if (!tiene_permiso('ver_todas_sucursales') && (int) $u['sucursal_id'] !== (int) $equipo['sucursal_id']) {
    flash_set('error', 'No tienes permiso para este equipo.');
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
            if ($op === 'agregar_compat') {
                $refaccion_id = (int) input('refaccion_id', 0);
                $componente_id = (int) input('componente_id', 0) ?: null;
                $notas = trim((string) input('notas', '')) ?: null;

                if ($refaccion_id <= 0) {
                    $errores[] = 'Selecciona una refacción.';
                } else {
                    if ($componente_id) {
                        agregar_compatibilidad($refaccion_id, null, $componente_id, $notas);
                    } else {
                        agregar_compatibilidad($refaccion_id, $equipo_id, null, $notas);
                    }
                    flash_set('success', 'Compatibilidad agregada.');
                    header('Location: ' . url("equipo_refacciones.php?id=$equipo_id"));
                    exit;
                }
            } elseif ($op === 'eliminar_compat') {
                $compat_id = (int) input('compat_id', 0);
                if ($compat_id > 0) {
                    eliminar_compatibilidad($compat_id);
                    flash_set('success', 'Compatibilidad eliminada.');
                    header('Location: ' . url("equipo_refacciones.php?id=$equipo_id"));
                    exit;
                }
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Datos para la vista
// Compatibilidades vinculadas al equipo directamente
$compat_equipo = db_all(
    "SELECT rc.id AS compat_id, rc.notas AS compat_notas,
            r.id AS refaccion_id, r.codigo, r.nombre, r.unidad_medida, r.categoria, r.costo_unitario,
            (SELECT cantidad_actual FROM refacciones_stock
             WHERE refaccion_id = r.id AND sucursal_id = :sid) AS stock
     FROM refacciones_compatibles rc
     INNER JOIN refacciones r ON rc.refaccion_id = r.id
     WHERE rc.equipo_id = :eid AND r.activo = 1
     ORDER BY r.nombre",
    ['eid' => $equipo_id, 'sid' => $equipo['sucursal_id']]
);

// Compatibilidades vinculadas a componentes del equipo
$compat_componentes = db_all(
    "SELECT rc.id AS compat_id, rc.notas AS compat_notas,
            r.id AS refaccion_id, r.codigo, r.nombre, r.unidad_medida, r.categoria, r.costo_unitario,
            c.id AS componente_id, c.nombre AS componente_nombre,
            (SELECT cantidad_actual FROM refacciones_stock
             WHERE refaccion_id = r.id AND sucursal_id = :sid) AS stock
     FROM refacciones_compatibles rc
     INNER JOIN refacciones r ON rc.refaccion_id = r.id
     INNER JOIN equipo_componentes c ON rc.componente_id = c.id
     WHERE c.equipo_id = :eid AND r.activo = 1
     ORDER BY c.nombre, r.nombre",
    ['eid' => $equipo_id, 'sid' => $equipo['sucursal_id']]
);

// Frecuentes
$frecuentes = refacciones_frecuentes_equipo($equipo_id, 8);

// Componentes para el selector
$componentes_equipo = db_all(
    "SELECT id, nombre, tipo FROM equipo_componentes
     WHERE equipo_id = :eid AND activo = 1
     ORDER BY nombre",
    ['eid' => $equipo_id]
);

// Catálogo de refacciones para el modal
$todas_refacciones = db_all(
    "SELECT id, codigo, nombre, unidad_medida FROM refacciones
     WHERE activo = 1 ORDER BY nombre"
);

$titulo_pagina = 'Refacciones · ' . $equipo['nombre'];
$pagina_activa = 'equipos';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?= url('equipo_ver.php?id=' . $equipo_id) ?>"
           class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-0.5">
                <span class="font-mono"><?= e($equipo['codigo_inventario']) ?></span>
                <span>·</span>
                <span><?= e($equipo['sucursal_codigo']) ?></span>
                <?php if (!empty($equipo['area_nombre'])): ?>
                <span>·</span>
                <span><?= e($equipo['area_nombre']) ?></span>
                <?php endif; ?>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 truncate">
                Refacciones para <?= e($equipo['nombre']) ?>
            </h2>
        </div>

        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal_compat').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Vincular refacción
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Compatibles con el equipo -->
        <div class="lg:col-span-2 space-y-4">

            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="link" class="w-4 h-4 text-bacal-700"></i>
                        Refacciones vinculadas al equipo
                        <span class="text-xs font-normal text-zinc-500">(<?= count($compat_equipo) ?>)</span>
                    </h3>
                </div>

                <?php if (empty($compat_equipo)): ?>
                <div class="px-5 py-10 text-center">
                    <i data-lucide="link-2" class="w-10 h-10 mx-auto text-zinc-300 mb-2"></i>
                    <p class="text-sm font-semibold text-zinc-700 mb-1">Sin refacciones vinculadas</p>
                    <p class="text-xs text-zinc-500">Vincula refacciones para tenerlas a mano al crear órdenes.</p>
                </div>
                <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 border-b border-zinc-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Refacción</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Categoría</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Stock</th>
                            <?php if ($puede_gestionar): ?>
                            <th class="px-3 py-2"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($compat_equipo as $c):
                        $stock = (float) ($c['stock'] ?? 0);
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-3 py-2.5">
                            <a href="<?= url('refaccion_ver.php?id=' . $c['refaccion_id']) ?>"
                               class="font-semibold text-zinc-900 hover:text-bacal-700">
                                <?= e($c['nombre']) ?>
                            </a>
                            <div class="text-[10px] text-zinc-500 font-mono"><?= e($c['codigo']) ?></div>
                            <?php if (!empty($c['compat_notas'])): ?>
                            <div class="text-[10px] text-zinc-500 italic mt-0.5"><?= e($c['compat_notas']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5">
                            <?php if (!empty($c['categoria'])): ?>
                            <span class="text-[10px] font-medium text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded"><?= e($c['categoria']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <div class="font-bold text-sm <?= $stock <= 0 ? 'text-bacal-700' : 'text-zinc-900' ?>">
                                <?= number_format($stock, 0) ?>
                                <span class="text-[10px] font-normal text-zinc-500"><?= e($c['unidad_medida']) ?></span>
                            </div>
                        </td>
                        <?php if ($puede_gestionar): ?>
                        <td class="px-3 py-2.5 text-right">
                            <form method="POST" onsubmit="return confirm('¿Eliminar este vínculo?');" class="inline-block">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="eliminar_compat">
                                <input type="hidden" name="compat_id" value="<?= $c['compat_id'] ?>">
                                <button type="submit" class="p-1.5 rounded text-zinc-400 hover:text-bacal-700 hover:bg-zinc-100" title="Quitar vínculo">
                                    <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Compatibles vía componentes -->
            <?php if (!empty($compat_componentes)): ?>
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="cpu" class="w-4 h-4 text-bacal-700"></i>
                        Refacciones de componentes
                        <span class="text-xs font-normal text-zinc-500">(<?= count($compat_componentes) ?>)</span>
                    </h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 border-b border-zinc-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Refacción</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Componente</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Stock</th>
                            <?php if ($puede_gestionar): ?>
                            <th class="px-3 py-2"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($compat_componentes as $c):
                        $stock = (float) ($c['stock'] ?? 0);
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-3 py-2.5">
                            <a href="<?= url('refaccion_ver.php?id=' . $c['refaccion_id']) ?>"
                               class="font-semibold text-zinc-900 hover:text-bacal-700">
                                <?= e($c['nombre']) ?>
                            </a>
                            <div class="text-[10px] text-zinc-500 font-mono"><?= e($c['codigo']) ?></div>
                        </td>
                        <td class="px-3 py-2.5 text-xs text-zinc-700">
                            <?= e($c['componente_nombre']) ?>
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <span class="font-bold text-sm"><?= number_format($stock, 0) ?></span>
                            <span class="text-[10px] text-zinc-500"><?= e($c['unidad_medida']) ?></span>
                        </td>
                        <?php if ($puede_gestionar): ?>
                        <td class="px-3 py-2.5 text-right">
                            <form method="POST" onsubmit="return confirm('¿Eliminar este vínculo?');" class="inline-block">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="eliminar_compat">
                                <input type="hidden" name="compat_id" value="<?= $c['compat_id'] ?>">
                                <button type="submit" class="p-1.5 rounded text-zinc-400 hover:text-bacal-700 hover:bg-zinc-100" title="Quitar">
                                    <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: frecuentes -->
        <div class="space-y-4">
            <?php if (!empty($frecuentes)): ?>
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 border-b border-zinc-100 bg-amber-50">
                    <h3 class="font-display text-xs font-bold text-zinc-900 flex items-center gap-1.5 uppercase tracking-wider">
                        <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-600"></i>
                        Más usadas históricamente
                    </h3>
                </div>
                <div>
                <?php foreach ($frecuentes as $f): ?>
                    <a href="<?= url('refaccion_ver.php?id=' . $f['id']) ?>"
                       class="block px-3 py-2 border-b border-zinc-100 last:border-b-0 hover:bg-zinc-50">
                        <div class="text-xs font-semibold text-zinc-900 truncate"><?= e($f['nombre']) ?></div>
                        <div class="text-[10px] text-zinc-500">
                            <?= e($f['codigo']) ?> · usada <?= $f['veces_usada'] ?> veces
                            (<?= number_format($f['cantidad_total'], 0) ?> total)
                        </div>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información del equipo -->
            <div class="bg-zinc-50 rounded-xl border border-zinc-200 p-4 text-xs text-zinc-600 space-y-2">
                <h4 class="font-display font-bold text-zinc-900 uppercase tracking-wider text-[10px] mb-1">Equipo</h4>
                <?php if (!empty($equipo['marca']) || !empty($equipo['modelo'])): ?>
                <div><strong>Marca/Modelo:</strong> <?= e(trim(($equipo['marca'] ?? '') . ' ' . ($equipo['modelo'] ?? ''))) ?></div>
                <?php endif; ?>
                <?php if (!empty($equipo['numero_serie'])): ?>
                <div><strong>S/N:</strong> <span class="font-mono"><?= e($equipo['numero_serie']) ?></span></div>
                <?php endif; ?>
                <div><strong>Sucursal:</strong> <?= e($equipo['sucursal_nombre']) ?></div>
                <?php if (count($componentes_equipo) > 0): ?>
                <div><strong>Componentes:</strong> <?= count($componentes_equipo) ?> registrados</div>
                <?php endif; ?>

                <div class="pt-2 border-t border-zinc-200 flex gap-2 flex-wrap">
                    <a href="<?= url('equipo_componentes.php?id=' . $equipo_id) ?>"
                       class="text-bacal-700 hover:underline font-semibold">Ver componentes →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($puede_gestionar): ?>
<!-- Modal: agregar compatibilidad -->
<dialog id="modal_compat" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-md p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="agregar_compat">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Vincular refacción</h3>
            <button type="button" onclick="document.getElementById('modal_compat').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Refacción *</label>
                <select name="refaccion_id" required
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($todas_refacciones as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= e($r['codigo']) ?> · <?= e($r['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($componentes_equipo)): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Específicamente para componente</label>
                <select name="componente_id"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Vincular al equipo completo —</option>
                    <?php foreach ($componentes_equipo as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-zinc-500 mt-1">Si la refacción es para una parte específica (ej. solo para el motor), selecciónala. Si no, déjalo vacío.</p>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas (opcional)</label>
                <input type="text" name="notas" maxlength="255"
                       placeholder="ej. Para etapa 2, lado izquierdo"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_compat').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Vincular</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
