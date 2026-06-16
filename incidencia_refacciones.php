<?php
/**
 * ============================================================================
 * incidencia_refacciones.php
 * ============================================================================
 * Gestiona las refacciones usadas en una incidencia (orden de trabajo).
 * - Agregar refacciones con descuento automático de stock
 * - Ver historial de refacciones usadas
 * - Devolver refacción al stock
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

$incidencia_id = (int) input('id', 0);
if ($incidencia_id <= 0) {
    flash_set('error', 'Incidencia no especificada.');
    header('Location: ' . url('bitacora.php'));
    exit;
}

// Cargar incidencia
$inc = db_one(
    "SELECT i.*, e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
            s.codigo AS sucursal_codigo, s.nombre AS sucursal_nombre,
            est.nombre AS estado_nombre, est.color AS estado_color,
            sev.nombre AS severidad_nombre, sev.color AS severidad_color
     FROM incidencias i
     LEFT JOIN equipos e ON i.equipo_id = e.id
     LEFT JOIN sucursales s ON i.sucursal_id = s.id
     LEFT JOIN estados est ON i.estado_id = est.id
     LEFT JOIN severidades sev ON i.severidad_id = sev.id
     WHERE i.id = :id",
    ['id' => $incidencia_id]
);

if (!$inc) {
    flash_set('error', 'Incidencia no encontrada.');
    header('Location: ' . url('bitacora.php'));
    exit;
}

// Permisos
if (!tiene_permiso('ver_todas_sucursales') && (int) $u['sucursal_id'] !== (int) $inc['sucursal_id']) {
    flash_set('error', 'No tienes permiso para esta incidencia.');
    header('Location: ' . url('bitacora.php'));
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
            if ($op === 'agregar') {
                $refaccion_id = (int) input('refaccion_id', 0);
                $cantidad = (float) input('cantidad', 0);
                $componente_id = (int) input('componente_id', 0) ?: null;
                $notas = trim((string) input('notas', '')) ?: null;
                $costo_unitario = (float) input('costo_unitario', 0) ?: null;

                if ($refaccion_id <= 0 || $cantidad <= 0) {
                    $errores[] = 'Selecciona una refacción y una cantidad mayor a 0.';
                } else {
                    registrar_refaccion_en_incidencia([
                        'incidencia_id' => $incidencia_id,
                        'refaccion_id' => $refaccion_id,
                        'cantidad' => $cantidad,
                        'componente_id' => $componente_id,
                        'notas' => $notas,
                        'costo_unitario' => $costo_unitario,
                    ], (int) $u['id']);

                    registrar_auditoria('refaccion_en_incidencia', 'incidencia_refacciones',
                        $incidencia_id, "Refacción id=$refaccion_id cant=$cantidad");
                    flash_set('success', 'Refacción registrada y stock descontado.');
                    header('Location: ' . url("incidencia_refacciones.php?id=$incidencia_id"));
                    exit;
                }
            } elseif ($op === 'devolver') {
                $reg_id = (int) input('registro_id', 0);
                $motivo = trim((string) input('motivo_dev', '')) ?: null;
                if ($reg_id > 0) {
                    devolver_refaccion_de_incidencia($reg_id, (int) $u['id'], $motivo);
                    flash_set('success', 'Refacción devuelta al stock.');
                    header('Location: ' . url("incidencia_refacciones.php?id=$incidencia_id"));
                    exit;
                }
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Datos para la vista
$refacciones_usadas = listar_refacciones_de_incidencia($incidencia_id);
$stats = stats_refacciones_incidencia($incidencia_id);

// Refacciones sugeridas (compatibles con el equipo + frecuentes)
$sugeridas = [];
$frecuentes = [];
if (!empty($inc['equipo_id'])) {
    $sugeridas = refacciones_compatibles_con_equipo((int) $inc['equipo_id']);
    $frecuentes = refacciones_frecuentes_equipo((int) $inc['equipo_id'], 5);
}

// Componentes del equipo (para vincular refacción a un componente específico)
$componentes_equipo = [];
if (!empty($inc['equipo_id'])) {
    $componentes_equipo = db_all(
        "SELECT id, nombre, tipo FROM equipo_componentes
         WHERE equipo_id = :eid AND activo = 1
         ORDER BY nombre",
        ['eid' => $inc['equipo_id']]
    );
}

// Catálogo completo de refacciones (para el select del modal)
$todas_refacciones = db_all(
    "SELECT r.id, r.codigo, r.nombre, r.unidad_medida, r.costo_unitario,
            (SELECT cantidad_actual FROM refacciones_stock
             WHERE refaccion_id = r.id AND sucursal_id = :sid) AS stock_disponible
     FROM refacciones r
     WHERE r.activo = 1
     ORDER BY r.nombre ASC",
    ['sid' => $inc['sucursal_id']]
);

$titulo_pagina = 'Refacciones · ' . $inc['folio'];
$pagina_activa = 'bitacora';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-6xl mx-auto animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?= url('incidencia_ver.php?id=' . $incidencia_id) ?>"
           class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-0.5">
                <span class="font-mono font-bold"><?= e($inc['folio']) ?></span>
                <span>·</span>
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                      style="color: <?= e($inc['estado_color']) ?>; background-color: <?= e($inc['estado_color']) ?>15">
                    <?= e($inc['estado_nombre']) ?>
                </span>
                <?php if (!empty($inc['equipo_codigo'])): ?>
                <span>·</span>
                <span class="font-mono"><?= e($inc['equipo_codigo']) ?></span>
                <?php endif; ?>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 truncate">
                Refacciones de <?= e($inc['titulo']) ?>
            </h2>
        </div>

        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal_agregar').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Agregar refacción
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

    <!-- KPIs -->
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Líneas</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= $stats['lineas'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Unidades</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($stats['unidades_total'], 0) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold">Costo total</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700">$<?= number_format($stats['costo_total'], 2) ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Listado principal -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-bacal-700"></i>
                    Refacciones usadas
                </h3>
            </div>

            <?php if (empty($refacciones_usadas)): ?>
            <div class="px-5 py-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                    <i data-lucide="package" class="w-8 h-8 text-zinc-400"></i>
                </div>
                <p class="text-sm font-semibold text-zinc-700 mb-1">Sin refacciones registradas</p>
                <?php if ($puede_gestionar): ?>
                <p class="text-xs text-zinc-500 mb-4">Cuando uses piezas para resolver esta orden, regístralas aquí.</p>
                <button onclick="document.getElementById('modal_agregar').showModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    <i data-lucide="plus" class="w-4 h-4"></i> Agregar primera refacción
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Refacción</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Cantidad</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Costo</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Fecha</th>
                        <?php if ($puede_gestionar): ?>
                        <th class="px-3 py-2"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($refacciones_usadas as $r): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-3 py-2.5">
                        <a href="<?= url('refaccion_ver.php?id=' . $r['refaccion_id']) ?>"
                           class="font-semibold text-zinc-900 hover:text-bacal-700">
                            <?= e($r['refaccion_nombre']) ?>
                        </a>
                        <div class="text-[10px] text-zinc-500 font-mono"><?= e($r['refaccion_codigo']) ?></div>
                        <?php if (!empty($r['componente_nombre'])): ?>
                        <div class="text-[10px] text-zinc-600 mt-0.5">
                            <i data-lucide="cpu" class="w-3 h-3 inline -mt-0.5"></i>
                            Componente: <?= e($r['componente_nombre']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($r['notas'])): ?>
                        <div class="text-[10px] text-zinc-500 italic mt-0.5"><?= e($r['notas']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        <span class="font-bold text-zinc-900"><?= number_format($r['cantidad'], 0) ?></span>
                        <span class="text-[10px] text-zinc-500"><?= e($r['unidad_medida']) ?></span>
                    </td>
                    <td class="px-3 py-2.5 text-right text-xs">
                        <?php if (!empty($r['costo_total'])): ?>
                        <div class="font-semibold text-zinc-900">$<?= number_format($r['costo_total'], 2) ?></div>
                        <?php if (!empty($r['costo_unitario'])): ?>
                        <div class="text-[10px] text-zinc-500">$<?= number_format($r['costo_unitario'], 2) ?> c/u</div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-zinc-600">
                        <?= e(date('d/M H:i', strtotime($r['creado_en']))) ?>
                        <div class="text-[10px] text-zinc-400"><?= e($r['usuario_nombre']) ?></div>
                    </td>
                    <?php if ($puede_gestionar): ?>
                    <td class="px-3 py-2.5 text-right">
                        <form method="POST" onsubmit="return confirm('¿Devolver esta refacción al stock? Se creará un movimiento de entrada compensatorio.');" class="inline-block">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="devolver">
                            <input type="hidden" name="registro_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="p-1.5 rounded text-zinc-400 hover:text-bacal-700 hover:bg-zinc-100" title="Devolver al stock">
                                <i data-lucide="undo-2" class="w-3.5 h-3.5"></i>
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

        <!-- Sidebar: sugerencias -->
        <div class="space-y-4">

            <!-- Frecuentes en este equipo -->
            <?php if (!empty($frecuentes)): ?>
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 border-b border-zinc-100 bg-amber-50">
                    <h3 class="font-display text-xs font-bold text-zinc-900 flex items-center gap-1.5 uppercase tracking-wider">
                        <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-600"></i>
                        Más usadas en este equipo
                    </h3>
                </div>
                <div>
                <?php foreach ($frecuentes as $f): ?>
                    <div class="px-3 py-2 border-b border-zinc-100 last:border-b-0 flex items-center gap-2 hover:bg-zinc-50">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-zinc-900 truncate"><?= e($f['nombre']) ?></div>
                            <div class="text-[10px] text-zinc-500"><?= e($f['codigo']) ?> · usada <?= $f['veces_usada'] ?> veces</div>
                        </div>
                        <?php if ($puede_gestionar): ?>
                        <button onclick="agregarSugerida(<?= $f['id'] ?>, '<?= e(addslashes($f['nombre'])) ?>')"
                                class="p-1.5 rounded text-zinc-400 hover:text-bacal-700 hover:bg-zinc-100" title="Agregar">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Compatibles con este equipo -->
            <?php if (!empty($sugeridas)): ?>
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 border-b border-zinc-100">
                    <h3 class="font-display text-xs font-bold text-zinc-900 flex items-center gap-1.5 uppercase tracking-wider">
                        <i data-lucide="link" class="w-3.5 h-3.5 text-blue-600"></i>
                        Compatibles con este equipo
                    </h3>
                </div>
                <div class="max-h-[400px] overflow-y-auto">
                <?php foreach ($sugeridas as $s):
                    $stock = (float) ($s['stock_total'] ?? 0);
                ?>
                    <div class="px-3 py-2 border-b border-zinc-100 last:border-b-0 flex items-center gap-2 hover:bg-zinc-50">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-zinc-900 truncate"><?= e($s['nombre']) ?></div>
                            <div class="text-[10px] text-zinc-500">
                                <?= e($s['codigo']) ?> · stock: <strong><?= number_format($stock, 0) ?></strong>
                            </div>
                        </div>
                        <?php if ($puede_gestionar && $stock > 0): ?>
                        <button onclick="agregarSugerida(<?= $s['id'] ?>, '<?= e(addslashes($s['nombre'])) ?>')"
                                class="p-1.5 rounded text-zinc-400 hover:text-bacal-700 hover:bg-zinc-100" title="Agregar">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($puede_gestionar): ?>
<!-- Modal: agregar refacción -->
<dialog id="modal_agregar" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-lg p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="agregar">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Agregar refacción usada</h3>
            <button type="button" onclick="document.getElementById('modal_agregar').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Refacción *</label>
                <select name="refaccion_id" id="select_refaccion" required
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($todas_refacciones as $r):
                        $stock = (float) ($r['stock_disponible'] ?? 0);
                    ?>
                    <option value="<?= $r['id'] ?>"
                            data-stock="<?= $stock ?>"
                            data-unidad="<?= e($r['unidad_medida']) ?>"
                            data-costo="<?= e($r['costo_unitario'] ?? '') ?>"
                            <?= $stock <= 0 ? 'disabled' : '' ?>>
                        <?= e($r['codigo']) ?> · <?= e($r['nombre']) ?>
                        — Stock: <?= number_format($stock, 0) ?> <?= e($r['unidad_medida']) ?>
                        <?= $stock <= 0 ? '(SIN STOCK)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p id="stock_info" class="text-[10px] text-zinc-500 mt-1"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Cantidad *</label>
                    <input type="number" name="cantidad" id="input_cantidad" required min="0.01" step="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Costo unitario</label>
                    <input type="number" name="costo_unitario" id="input_costo" min="0" step="0.01"
                           placeholder="opcional"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <?php if (!empty($componentes_equipo)): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Reemplaza componente (opcional)</label>
                <select name="componente_id"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Ninguno —</option>
                    <?php foreach ($componentes_equipo as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= e($c['nombre']) ?>
                        <?php if (!empty($c['tipo'])): ?>(<?= e($c['tipo']) ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas</label>
                <textarea name="notas" rows="2"
                          placeholder="Detalles del uso..."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>

            <div class="text-[10px] text-zinc-500 bg-blue-50 px-3 py-2 rounded">
                💡 Al guardar, se descontará automáticamente del stock de <strong><?= e($inc['sucursal_nombre']) ?></strong> y se agregará al historial del equipo.
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_agregar').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Registrar uso</button>
        </div>
    </form>
</dialog>

<script>
// Auto-llenar costo cuando cambia la refacción
document.getElementById('select_refaccion').addEventListener('change', function(e) {
    const opt = e.target.options[e.target.selectedIndex];
    if (!opt.value) {
        document.getElementById('stock_info').textContent = '';
        return;
    }
    const stock = opt.dataset.stock;
    const unidad = opt.dataset.unidad;
    const costo = opt.dataset.costo;

    document.getElementById('stock_info').innerHTML =
        `📦 Disponible en <?= e($inc['sucursal_codigo']) ?>: <strong>${parseFloat(stock).toFixed(0)} ${unidad}</strong>`;

    if (costo) {
        document.getElementById('input_costo').value = costo;
    }
});

// Función que se llama desde las sugerencias
function agregarSugerida(refaccionId, nombre) {
    document.getElementById('select_refaccion').value = refaccionId;
    document.getElementById('select_refaccion').dispatchEvent(new Event('change'));
    document.getElementById('input_cantidad').value = 1;
    document.getElementById('modal_agregar').showModal();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
