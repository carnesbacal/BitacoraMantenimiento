<?php
/**
 * ============================================================================
 * refacciones.php - Catálogo de refacciones
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

// Filtros
$f_busqueda = trim((string) input('q', ''));
$f_categoria = (string) input('categoria', '');
$f_sucursal = (int) input('sucursal_id', 0);
$f_stock_bajo = (int) input('stock_bajo', 0);

// Si el usuario no puede ver todas las sucursales, fijar la suya
if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$filtros = [
    'busqueda' => $f_busqueda ?: null,
    'categoria' => $f_categoria ?: null,
    'sucursal_id' => $f_sucursal ?: null,
    'solo_stock_bajo' => $f_stock_bajo === 1,
];

// Procesar POST (crear refacción)
$errores = [];
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token inválido.';
    } else {
        $op = (string) input('op', '');
        if ($op === 'crear') {
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

            if ($datos['codigo'] === '') $errores[] = 'El código es obligatorio.';
            if ($datos['nombre'] === '') $errores[] = 'El nombre es obligatorio.';

            if (empty($errores)) {
                try {
                    $nuevo_id = crear_refaccion($datos, (int) $u['id']);
                    registrar_auditoria('crear_refaccion', 'refacciones', $nuevo_id, $datos['nombre']);
                    flash_set('success', "Refacción '{$datos['nombre']}' creada.");
                    header('Location: ' . url('refaccion_ver.php?id=' . $nuevo_id));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                    if (str_contains($e->getMessage(), 'Duplicate')) {
                        $errores[] = "El código '{$datos['codigo']}' ya existe.";
                    }
                }
            }
        }
    }
}

$refacciones = listar_refacciones($filtros);
$stats = stats_almacen($f_sucursal ?: null);

$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);
$proveedores = db_all("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre");

$titulo_pagina = 'Refacciones';
$pagina_activa = 'refacciones';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="package" class="w-6 h-6 text-bacal-700"></i>
                Refacciones
            </h2>
            <p class="text-xs text-zinc-500 mt-0.5">Catálogo maestro de piezas con stock por sucursal.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= url('almacen.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="warehouse" class="w-4 h-4"></i>
                Dashboard almacén
            </a>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal_nueva').showModal()"
                    class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva refacción
            </button>
            <?php endif; ?>
        </div>
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
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Total refacciones</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $stats['total_refacciones'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Unidades en stock</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($stats['unidades_stock'], 0) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4 <?= $stats['stock_bajo'] > 0 ? 'border-bacal-300 bg-bacal-50' : '' ?>">
            <div class="text-[10px] <?= $stats['stock_bajo'] > 0 ? 'text-bacal-700' : 'text-zinc-500' ?> uppercase tracking-wider font-bold">Con stock bajo</div>
            <div class="font-display text-2xl font-extrabold <?= $stats['stock_bajo'] > 0 ? 'text-bacal-700' : 'text-zinc-900' ?>"><?= (int) $stats['stock_bajo'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Valor inventario</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900">$<?= number_format($stats['valor_inventario'], 0) ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                <input type="text" name="q" value="<?= e($f_busqueda) ?>"
                       placeholder="Buscar por código, nombre, no. parte, marca..."
                       class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <select name="categoria" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="">Todas las categorías</option>
                <?php foreach (categorias_refacciones() as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $f_categoria === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if (tiene_permiso('ver_todas_sucursales')): ?>
            <select name="sucursal_id" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="0">Todas las sucursales</option>
                <?php foreach ($sucursales as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= e($s['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <label class="px-3 py-2 rounded-lg border text-sm font-medium cursor-pointer flex items-center gap-1.5
                          <?= $f_stock_bajo ? 'border-bacal-300 bg-bacal-50 text-bacal-800' : 'border-zinc-300 text-zinc-700 hover:bg-zinc-50' ?>">
                <input type="checkbox" name="stock_bajo" value="1" <?= $f_stock_bajo ? 'checked' : '' ?>
                       onchange="this.form.submit()" class="hidden">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                Stock bajo
            </label>

            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Filtrar</button>
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <?php if (empty($refacciones)): ?>
        <div class="px-6 py-16 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                <i data-lucide="package" class="w-8 h-8 text-zinc-400"></i>
            </div>
            <p class="text-sm font-semibold text-zinc-700 mb-1">Sin refacciones en el catálogo</p>
            <?php if ($puede_gestionar): ?>
            <p class="text-xs text-zinc-500 mb-4">Empieza creando el catálogo maestro de piezas.</p>
            <button onclick="document.getElementById('modal_nueva').showModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> Crear primera refacción
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Código</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Refacción</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Marca / No. parte</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Stock</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($refacciones as $r):
                    $stock = (float) ($r['stock_total'] ?? 0);
                    $minimo = (float) ($r['minimo_total'] ?? 0);
                    $stock_bajo = $minimo > 0 && $stock <= $minimo;
                ?>
                <tr class="hover:bg-zinc-50 cursor-pointer" onclick="window.location.href='<?= url('refaccion_ver.php?id=' . $r['id']) ?>'">
                    <td class="px-3 py-2.5">
                        <span class="font-mono text-xs font-bold text-zinc-900"><?= e($r['codigo']) ?></span>
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="font-semibold text-zinc-900"><?= e($r['nombre']) ?></div>
                        <?php if (!empty($r['descripcion'])): ?>
                        <div class="text-[10px] text-zinc-500 truncate max-w-md"><?= e(mb_substr($r['descripcion'], 0, 80)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs">
                        <?php if (!empty($r['marca'])): ?>
                        <div class="text-zinc-700"><?= e($r['marca']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['numero_parte'])): ?>
                        <div class="text-zinc-500 font-mono text-[10px]"><?= e($r['numero_parte']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5">
                        <?php if (!empty($r['categoria'])): ?>
                        <span class="text-[10px] font-medium text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded"><?= e($r['categoria']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        <div class="font-semibold text-sm <?= $stock_bajo ? 'text-bacal-700' : 'text-zinc-900' ?>">
                            <?= number_format($stock, ($stock == floor($stock)) ? 0 : 2) ?>
                            <span class="text-[10px] font-normal text-zinc-500"><?= e($r['unidad_medida']) ?></span>
                        </div>
                        <?php if ($stock_bajo): ?>
                        <div class="text-[9px] font-bold text-bacal-700">⚠ Stock bajo (min: <?= number_format($minimo, 0) ?>)</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-right text-xs text-zinc-700">
                        <?php if (!empty($r['costo_unitario'])): ?>
                        $<?= number_format($r['costo_unitario'], 2) ?>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($puede_gestionar): ?>
<!-- Modal Nueva Refacción -->
<dialog id="modal_nueva" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-2xl p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="crear">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-bacal-700"></i>
                Nueva refacción
            </h3>
            <button type="button" onclick="document.getElementById('modal_nueva').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Código *</label>
                    <input type="text" name="codigo" required maxlength="50"
                           placeholder="REF-001"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="200"
                           placeholder="ej. Rodamiento 6205, Banda B-50, Filtro de aceite"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Descripción</label>
                <textarea name="descripcion" rows="2"
                          placeholder="Detalle adicional, especificaciones, etc."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
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
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Categoría</label>
                    <select name="categoria_ref" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">—</option>
                        <?php foreach (categorias_refacciones() as $cat): ?>
                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Unidad de medida</label>
                    <select name="unidad_medida" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <?php foreach (unidades_medida() as $key => $lbl): ?>
                        <option value="<?= e($key) ?>" <?= $key === 'pieza' ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Costo unitario ($)</label>
                    <input type="number" name="costo_unitario" min="0" step="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
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

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_nueva').close()"
                    class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                Crear refacción
            </button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
