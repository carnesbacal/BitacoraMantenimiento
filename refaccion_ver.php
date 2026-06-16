<?php
/**
 * ============================================================================
 * refaccion_ver.php - Ficha de una refacción
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';

requerir_login();
$u = usuario_actual();
$es_admin = tiene_permiso('administrar');
$puede_gestionar = $es_admin || tiene_permiso('resolver');

$id = (int) input('id', 0);
$ref = $id > 0 ? obtener_refaccion($id) : null;

if (!$ref) {
    flash_set('error', 'Refacción no encontrada.');
    header('Location: ' . url('refacciones.php'));
    exit;
}

$errores = [];

// Procesar POST
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token inválido.';
    } else {
        $op = (string) input('op', '');

        try {
            if ($op === 'actualizar') {
                $datos = [
                    'codigo' => trim((string) input('codigo', '')),
                    'nombre' => trim((string) input('nombre', '')),
                    'descripcion' => trim((string) input('descripcion', '')) ?: null,
                    'marca' => trim((string) input('marca', '')) ?: null,
                    'modelo' => trim((string) input('modelo', '')) ?: null,
                    'numero_parte' => trim((string) input('numero_parte', '')) ?: null,
                    'categoria' => (string) input('categoria_ref', ''),
                    'unidad_medida' => (string) input('unidad_medida', 'pieza'),
                    'costo_unitario' => (float) input('costo_unitario', 0) ?: null,
                    'proveedor_id' => (int) input('proveedor_id', 0) ?: null,
                ];
                if ($datos['codigo'] === '' || $datos['nombre'] === '') {
                    $errores[] = 'Código y nombre son obligatorios.';
                } else {
                    actualizar_refaccion($id, $datos, (int) $u['id']);
                    flash_set('success', 'Refacción actualizada.');
                    header('Location: ' . url("refaccion_ver.php?id=$id"));
                    exit;
                }
            } elseif ($op === 'actualizar_minimos') {
                $sid = (int) input('sucursal_id', 0);
                $minimo = (float) input('cantidad_minima', 0);
                $optima = (float) input('cantidad_optima', 0) ?: null;
                $ubicacion = trim((string) input('ubicacion', '')) ?: null;
                if ($sid > 0) {
                    actualizar_minimos_stock($id, $sid, $minimo, $optima, $ubicacion);
                    flash_set('success', 'Configuración de stock actualizada.');
                    header('Location: ' . url("refaccion_ver.php?id=$id"));
                    exit;
                }
            } elseif ($op === 'eliminar') {
                eliminar_refaccion($id, (int) $u['id']);
                flash_set('success', 'Refacción eliminada.');
                header('Location: ' . url('refacciones.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

$stock_sucursales = listar_stock_de_refaccion($id);
$movimientos = listar_movimientos_de_refaccion($id, 30);
$compatibilidades = listar_compatibilidades_refaccion($id);

$sucursales = db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre");
$proveedores = db_all("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre");

$titulo_pagina = $ref['nombre'];
$pagina_activa = 'refacciones';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-6xl mx-auto animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?= url('refacciones.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-0.5">
                <span class="font-mono font-bold"><?= e($ref['codigo']) ?></span>
                <?php if (!empty($ref['categoria'])): ?>
                <span>·</span>
                <span><?= e($ref['categoria']) ?></span>
                <?php endif; ?>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 truncate"><?= e($ref['nombre']) ?></h2>
        </div>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal_movimiento').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="package-plus" class="w-4 h-4"></i>
            Registrar movimiento
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

        <!-- Columna izquierda: información -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Información general -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-bacal-700"></i> Información
                    </h3>
                    <?php if ($puede_gestionar): ?>
                    <button onclick="document.getElementById('modal_editar').showModal()"
                            class="text-xs text-bacal-700 hover:underline font-semibold flex items-center gap-1">
                        <i data-lucide="edit-3" class="w-3 h-3"></i> Editar
                    </button>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Marca</div>
                        <div class="font-semibold text-zinc-900"><?= !empty($ref['marca']) ? e($ref['marca']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Modelo</div>
                        <div class="font-semibold text-zinc-900"><?= !empty($ref['modelo']) ? e($ref['modelo']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">No. parte</div>
                        <div class="font-mono text-zinc-900"><?= !empty($ref['numero_parte']) ? e($ref['numero_parte']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Unidad</div>
                        <div class="font-semibold text-zinc-900"><?= e(unidades_medida()[$ref['unidad_medida']] ?? $ref['unidad_medida']) ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Costo unitario</div>
                        <div class="font-semibold text-zinc-900">
                            <?= !empty($ref['costo_unitario']) ? '$' . number_format($ref['costo_unitario'], 2) : '—' ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Proveedor</div>
                        <div class="font-semibold text-zinc-900"><?= !empty($ref['proveedor_nombre']) ? e($ref['proveedor_nombre']) : '—' ?></div>
                    </div>
                </div>

                <?php if (!empty($ref['descripcion'])): ?>
                <div class="mt-3 pt-3 border-t border-zinc-100">
                    <div class="text-[10px] font-bold text-zinc-500 uppercase mb-1">Descripción</div>
                    <div class="text-xs text-zinc-700 whitespace-pre-wrap"><?= e($ref['descripcion']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stock por sucursal -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100 flex items-center justify-between">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="warehouse" class="w-4 h-4 text-bacal-700"></i> Stock por sucursal
                    </h3>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 border-b border-zinc-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Stock</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Mínimo</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Óptimo</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Ubicación</th>
                            <?php if ($puede_gestionar): ?>
                            <th class="px-3 py-2"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                    <?php
                    // Mostrar también sucursales sin stock todavía
                    $stock_por_suc = [];
                    foreach ($stock_sucursales as $s) $stock_por_suc[$s['sucursal_id']] = $s;
                    foreach ($sucursales as $suc):
                        $s = $stock_por_suc[$suc['id']] ?? null;
                        $cant = $s ? (float) $s['cantidad_actual'] : 0;
                        $min = $s ? (float) $s['cantidad_minima'] : 0;
                        $opt = $s && !empty($s['cantidad_optima']) ? (float) $s['cantidad_optima'] : null;
                        $alerta = $min > 0 && $cant <= $min;
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-3 py-2.5">
                            <span class="font-semibold text-zinc-900"><?= e($suc['nombre']) ?></span>
                            <span class="text-[10px] text-zinc-500">(<?= e($suc['codigo']) ?>)</span>
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <span class="font-bold <?= $alerta ? 'text-bacal-700' : 'text-zinc-900' ?>">
                                <?= number_format($cant, ($cant == floor($cant)) ? 0 : 2) ?>
                            </span>
                            <?php if ($alerta): ?>
                            <i data-lucide="alert-triangle" class="w-3 h-3 inline text-bacal-700"></i>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs text-zinc-700"><?= $min > 0 ? number_format($min, 0) : '—' ?></td>
                        <td class="px-3 py-2.5 text-right text-xs text-zinc-700"><?= $opt !== null ? number_format($opt, 0) : '—' ?></td>
                        <td class="px-3 py-2.5 text-xs text-zinc-600"><?= !empty($s['ubicacion']) ? e($s['ubicacion']) : '—' ?></td>
                        <?php if ($puede_gestionar): ?>
                        <td class="px-3 py-2.5 text-right">
                            <button onclick="abrirEditarMinimos(<?= $suc['id'] ?>, '<?= e($suc['nombre']) ?>', <?= $min ?>, <?= $opt ?? 'null' ?>, '<?= e(addslashes($s['ubicacion'] ?? '')) ?>')"
                                    class="text-zinc-400 hover:text-bacal-700 p-1">
                                <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Movimientos recientes -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="arrow-left-right" class="w-4 h-4 text-bacal-700"></i>
                        Movimientos recientes
                    </h3>
                </div>

                <?php if (empty($movimientos)): ?>
                <div class="px-5 py-8 text-center text-xs text-zinc-500">
                    Sin movimientos registrados aún.
                </div>
                <?php else: ?>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 border-b border-zinc-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Fecha</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Tipo</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase">Cantidad</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Motivo</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Sucursal</th>
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($movimientos as $m):
                        $tipo_color = match ($m['tipo']) {
                            'entrada' => '#16A34A',
                            'salida' => '#DC2626',
                            'ajuste' => '#F59E0B',
                            'transferencia' => '#0EA5E9',
                            default => '#71717A',
                        };
                        $signo = match ($m['tipo']) {
                            'entrada' => '+',
                            'salida' => '−',
                            'ajuste' => '=',
                            default => '→',
                        };
                    ?>
                    <tr>
                        <td class="px-3 py-2 text-xs text-zinc-600"><?= e(date('d/M H:i', strtotime($m['creado_en']))) ?></td>
                        <td class="px-3 py-2">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                                  style="color: <?= e($tipo_color) ?>; background-color: <?= e($tipo_color) ?>15">
                                <?= e($m['tipo']) ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <span class="font-bold text-sm" style="color: <?= e($tipo_color) ?>">
                                <?= $signo ?><?= number_format($m['cantidad'], 0) ?>
                            </span>
                            <div class="text-[10px] text-zinc-500"><?= number_format($m['cantidad_antes'], 0) ?> → <?= number_format($m['cantidad_despues'], 0) ?></div>
                        </td>
                        <td class="px-3 py-2 text-xs text-zinc-700"><?= e($m['motivo'] ?? '—') ?></td>
                        <td class="px-3 py-2 text-xs text-zinc-600"><?= e($m['sucursal_codigo']) ?></td>
                        <td class="px-3 py-2 text-xs text-zinc-600"><?= e($m['usuario_nombre']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>

        <!-- Columna derecha: compatibilidades + metadata -->
        <div class="space-y-4">

            <!-- Compatibilidades -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2 mb-3">
                    <i data-lucide="link" class="w-4 h-4 text-bacal-700"></i>
                    Compatible con
                </h3>

                <?php if (empty($compatibilidades)): ?>
                <p class="text-xs text-zinc-500 mb-3">Aún no vinculada con ningún equipo o componente.</p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($compatibilidades as $c): ?>
                    <div class="flex items-start gap-2 p-2 rounded-lg border border-zinc-100 bg-zinc-50/50">
                        <i data-lucide="<?= !empty($c['equipo_id']) ? 'monitor' : 'cpu' ?>" class="w-3.5 h-3.5 text-zinc-400 mt-0.5"></i>
                        <div class="flex-1 min-w-0 text-xs">
                            <?php if (!empty($c['equipo_id'])): ?>
                            <div class="font-semibold text-zinc-900"><?= e($c['equipo_nombre']) ?></div>
                            <div class="text-[10px] text-zinc-500 font-mono"><?= e($c['equipo_codigo']) ?></div>
                            <?php elseif (!empty($c['componente_id'])): ?>
                            <div class="font-semibold text-zinc-900"><?= e($c['componente_nombre']) ?></div>
                            <div class="text-[10px] text-zinc-500">
                                de <span class="font-mono"><?= e($c['comp_equipo_codigo']) ?></span> · <?= e($c['comp_equipo_nombre']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($c['notas'])): ?>
                            <div class="text-[10px] text-zinc-600 mt-0.5 italic"><?= e($c['notas']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Metadata -->
            <div class="bg-zinc-50 rounded-xl border border-zinc-200 p-4 text-xs text-zinc-600 space-y-1">
                <?php if (!empty($ref['creado_por_nombre'])): ?>
                <div>Creado por <?= e($ref['creado_por_nombre']) ?> · <?= e(fmt_tiempo_relativo($ref['creado_en'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($ref['actualizado_por_nombre'])): ?>
                <div>Actualizado por <?= e($ref['actualizado_por_nombre']) ?> · <?= e(fmt_tiempo_relativo($ref['actualizado_en'])) ?></div>
                <?php endif; ?>
            </div>

            <?php if ($es_admin): ?>
            <form method="POST" onsubmit="return confirm('¿Eliminar esta refacción del catálogo?');" class="text-right">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="eliminar">
                <button type="submit" class="text-xs text-zinc-500 hover:text-bacal-700 inline-flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3 h-3"></i> Eliminar refacción
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($puede_gestionar): ?>
<!-- Modal: editar refacción -->
<dialog id="modal_editar" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-2xl p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="actualizar">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Editar refacción</h3>
            <button type="button" onclick="document.getElementById('modal_editar').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Código *</label>
                    <input type="text" name="codigo" required value="<?= e($ref['codigo']) ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required value="<?= e($ref['nombre']) ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Descripción</label>
                <textarea name="descripcion" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"><?= e($ref['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Marca</label>
                    <input type="text" name="marca" value="<?= e($ref['marca'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Modelo</label>
                    <input type="text" name="modelo" value="<?= e($ref['modelo'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">No. parte</label>
                    <input type="text" name="numero_parte" value="<?= e($ref['numero_parte'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Categoría</label>
                    <select name="categoria_ref" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">—</option>
                        <?php foreach (categorias_refacciones() as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $ref['categoria'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Unidad</label>
                    <select name="unidad_medida" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <?php foreach (unidades_medida() as $key => $lbl): ?>
                        <option value="<?= e($key) ?>" <?= $ref['unidad_medida'] === $key ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Costo unitario</label>
                    <input type="number" name="costo_unitario" min="0" step="0.01" value="<?= e($ref['costo_unitario'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Proveedor habitual</label>
                <select name="proveedor_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Ninguno —</option>
                    <?php foreach ($proveedores as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int) $ref['proveedor_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_editar').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Guardar</button>
        </div>
    </form>
</dialog>

<!-- Modal: editar mínimos de stock -->
<dialog id="modal_minimos" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-md p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="actualizar_minimos">
        <input type="hidden" name="sucursal_id" id="minimos_sucursal_id" value="">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">
                Configurar stock · <span id="minimos_sucursal_nombre"></span>
            </h3>
            <button type="button" onclick="document.getElementById('modal_minimos').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Cantidad mínima (alerta)</label>
                <input type="number" name="cantidad_minima" id="minimos_cantidad" min="0" step="0.01" required
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                <p class="text-[10px] text-zinc-500 mt-1">Cuando el stock baje a este nivel, se alertará. 0 = sin alerta.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Cantidad óptima (opcional)</label>
                <input type="number" name="cantidad_optima" id="minimos_optima" min="0" step="0.01"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                <p class="text-[10px] text-zinc-500 mt-1">Cantidad ideal a tener en stock.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Ubicación física</label>
                <input type="text" name="ubicacion" id="minimos_ubicacion" maxlength="150"
                       placeholder="ej. Anaquel A-3, Pasillo 2"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_minimos').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Guardar</button>
        </div>
    </form>
</dialog>

<!-- Modal: registrar movimiento -->
<dialog id="modal_movimiento" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-md p-0">
    <form method="POST" action="<?= url('refacciones_movimientos.php') ?>" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="refaccion_id" value="<?= $id ?>">
        <input type="hidden" name="redirect_a" value="refaccion_ver.php?id=<?= $id ?>">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Registrar movimiento</h3>
            <button type="button" onclick="document.getElementById('modal_movimiento').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3" x-data="{ tipo: 'entrada' }">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase">Tipo de movimiento</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="tipo" value="entrada" x-model="tipo" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 border-zinc-200 text-zinc-600">
                            <i data-lucide="arrow-down-circle" class="w-4 h-4 mx-auto mb-1"></i>
                            Entrada
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="tipo" value="salida" x-model="tipo" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-bacal-500 peer-checked:bg-bacal-50 peer-checked:text-bacal-700 border-zinc-200 text-zinc-600">
                            <i data-lucide="arrow-up-circle" class="w-4 h-4 mx-auto mb-1"></i>
                            Salida
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="tipo" value="ajuste" x-model="tipo" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 border-zinc-200 text-zinc-600">
                            <i data-lucide="settings" class="w-4 h-4 mx-auto mb-1"></i>
                            Ajuste
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Sucursal *</label>
                <select name="sucursal_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">
                    Cantidad *
                    <span x-show="tipo === 'ajuste'" class="text-zinc-400 normal-case font-normal">(valor final absoluto)</span>
                </label>
                <input type="number" name="cantidad" required min="0" step="0.01"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Motivo</label>
                <select name="motivo" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Seleccionar —</option>
                    <template x-if="tipo === 'entrada'">
                        <optgroup label="Entrada">
                            <?php foreach (motivos_movimiento()['entrada'] as $k => $v): ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </template>
                    <template x-if="tipo === 'salida'">
                        <optgroup label="Salida">
                            <?php foreach (motivos_movimiento()['salida'] as $k => $v): ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </template>
                    <template x-if="tipo === 'ajuste'">
                        <optgroup label="Ajuste">
                            <?php foreach (motivos_movimiento()['ajuste'] as $k => $v): ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas</label>
                <textarea name="notas" rows="2"
                          placeholder="Detalle adicional, número de factura, OT relacionada..."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_movimiento').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Registrar movimiento</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<script>
function abrirEditarMinimos(sid, nombre, minimo, optima, ubicacion) {
    document.getElementById('minimos_sucursal_id').value = sid;
    document.getElementById('minimos_sucursal_nombre').textContent = nombre;
    document.getElementById('minimos_cantidad').value = minimo || 0;
    document.getElementById('minimos_optima').value = optima || '';
    document.getElementById('minimos_ubicacion').value = ubicacion || '';
    document.getElementById('modal_minimos').showModal();
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
