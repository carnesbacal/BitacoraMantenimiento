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
$f_estado = (string) input('estado', 'activas');
if (!in_array($f_estado, ['activas', 'inactivas', 'todas'], true)) $f_estado = 'activas';

// Si el usuario no puede ver todas las sucursales, fijar la suya
if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$filtros = [
    'busqueda' => $f_busqueda ?: null,
    'categoria' => $f_categoria ?: null,
    'sucursal_id' => $f_sucursal ?: null,
    'solo_stock_bajo' => $f_stock_bajo === 1,
    'estado' => $f_estado,
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

        if ($op === 'stock_multiple') {
            $sid_dest = (int) input('sucursal_destino', 0);
            if (!tiene_permiso('ver_todas_sucursales')) $sid_dest = (int) $u['sucursal_id'];
            $cants   = (array) input('cantidad', []);
            $costos  = (array) input('costo', []);
            $c_prov  = trim((string) input('compra_proveedor', ''));
            $c_fact  = trim((string) input('compra_factura', ''));
            $c_fecha = trim((string) input('compra_fecha', '')) ?: date('Y-m-d');
            if ($sid_dest <= 0) $errores[] = 'Selecciona la sucursal destino.';
            if (empty($errores)) {
                $notas = 'Compra' . ($c_prov ? " · {$c_prov}" : '') . ($c_fact ? " · Factura {$c_fact}" : '') . " · {$c_fecha}";
                $ok = 0; $errs = [];
                foreach ($cants as $rid => $c) {
                    $rid = (int) $rid; $c = (float) $c;
                    if ($rid <= 0 || $c <= 0) continue;
                    try {
                        registrar_movimiento([
                            'refaccion_id'   => $rid,
                            'sucursal_id'    => $sid_dest,
                            'tipo'           => 'entrada',
                            'cantidad'       => $c,
                            'motivo'         => 'compra',
                            'notas'          => $notas,
                            'costo_unitario' => ((float) ($costos[$rid] ?? 0)) ?: null,
                            'usuario_id'     => (int) $u['id'],
                        ]);
                        $ok++;
                    } catch (Throwable $e) { $errs[] = $e->getMessage(); }
                }
                registrar_auditoria('stock_multiple', 'refacciones', 0, "{$ok} entradas de stock");
                flash_set(empty($errs) ? 'success' : 'warning',
                    "Stock agregado a {$ok} refacción(es)." . (empty($errs) ? '' : ' Problemas: ' . implode(' ', $errs)));
                header('Location: ' . url('refacciones.php'));
                exit;
            }
        }

        if ($op === 'toggle_activo') {
            $rid = (int) input('refaccion_id', 0);
            $r = db_one("SELECT id, nombre, activo FROM refacciones WHERE id = :id", ['id' => $rid]);
            if ($r) {
                $nuevo = $r['activo'] ? 0 : 1;
                db_exec("UPDATE refacciones SET activo = :a WHERE id = :id", ['a' => $nuevo, 'id' => $rid]);
                registrar_auditoria($nuevo ? 'reactivar_refaccion' : 'desactivar_refaccion', 'refacciones', $rid, $r['nombre']);
                flash_set('success', $nuevo ? "Refacción '{$r['nombre']}' reactivada." : "Refacción '{$r['nombre']}' desactivada.");
            }
            header('Location: ' . url('refacciones.php' . ($f_estado !== 'activas' ? '?estado=' . $f_estado : '')));
            exit;
        }

        if ($op === 'bulk_activo') {
            $ids = array_values(array_filter(array_map('intval', (array) input('ids', [])), fn($x) => $x > 0));
            $set = (input('set_activo') === '1') ? 1 : 0;
            if ($ids) {
                $parts = []; $params = ['a' => $set];
                foreach ($ids as $i => $idv) { $k = "i{$i}"; $parts[] = ":{$k}"; $params[$k] = $idv; }
                db_exec("UPDATE refacciones SET activo = :a WHERE id IN (" . implode(',', $parts) . ")", $params);
                registrar_auditoria($set ? 'reactivar_refacciones_lote' : 'desactivar_refacciones_lote', 'refacciones', 0, count($ids) . ' refacciones');
                flash_set('success', count($ids) . ($set ? ' refacción(es) reactivada(s).' : ' refacción(es) desactivada(s).'));
            }
            header('Location: ' . url('refacciones.php' . ($f_estado !== 'activas' ? '?estado=' . $f_estado : '')));
            exit;
        }
    }
}

$refacciones = listar_refacciones($filtros);
$ref_map = [];
foreach ($refacciones as $r) {
    $ref_map[(int) $r['id']] = [
        'codigo' => $r['codigo'],
        'nombre' => $r['nombre'],
        'costo'  => $r['costo_unitario'] !== null ? (float) $r['costo_unitario'] : '',
    ];
}
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
            <a href="<?= url('refacciones_requisiciones.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                Requisiciones
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

            <select name="estado" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="activas"   <?= $f_estado === 'activas'   ? 'selected' : '' ?>>Activas</option>
                <option value="inactivas" <?= $f_estado === 'inactivas' ? 'selected' : '' ?>>Inactivas</option>
                <option value="todas"     <?= $f_estado === 'todas'     ? 'selected' : '' ?>>Todas</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Filtrar</button>
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden" x-data="refStock()">
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
        <?php if ($puede_gestionar): ?>
        <div x-show="sel.length > 0" x-cloak class="flex flex-wrap items-center gap-3 px-4 py-2.5 bg-bacal-50 border-b border-bacal-200">
            <span class="text-sm font-semibold text-bacal-800"><span x-text="sel.length"></span> seleccionada(s)</span>
            <button type="button" @click="abrir()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold hover:bg-bacal-800">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Agregar stock
            </button>
            <form method="POST" x-ref="formBulk" class="contents">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="bulk_activo">
                <input type="hidden" name="set_activo" :value="bulkVal">
                <template x-for="id in sel" :key="'b'+id"><input type="hidden" name="ids[]" :value="id"></template>
                <button type="button"
                        @click="if (sel.length && confirm('¿Desactivar ' + sel.length + ' refacción(es)?')) { bulkVal = '0'; $nextTick(() => $refs.formBulk.submit()); }"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-zinc-700 text-xs font-semibold hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                    <i data-lucide="ban" class="w-3.5 h-3.5"></i> Desactivar
                </button>
                <button type="button"
                        @click="if (sel.length && confirm('¿Reactivar ' + sel.length + ' refacción(es)?')) { bulkVal = '1'; $nextTick(() => $refs.formBulk.submit()); }"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-emerald-700 text-xs font-semibold hover:bg-emerald-50 hover:border-emerald-200">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reactivar
                </button>
            </form>
            <button type="button" @click="sel = []" class="text-xs font-medium text-zinc-500 hover:text-zinc-700">Limpiar selección</button>
        </div>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <?php if ($puede_gestionar): ?>
                        <th class="px-3 py-2 w-8"><input type="checkbox" @click.stop="toggleTodos($event)" :checked="sel.length > 0 && sel.length === Object.keys(refData).length" title="Seleccionar todo"></th>
                        <?php endif; ?>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Código</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Refacción</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Marca / No. parte</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Stock</th>
                        <th class="px-3 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Costo</th>
                        <?php if ($puede_gestionar): ?><th class="px-3 py-2"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($refacciones as $r):
                    $stock = (float) ($r['stock_total'] ?? 0);
                    $minimo = (float) ($r['minimo_total'] ?? 0);
                    $stock_bajo = $minimo > 0 && $stock <= $minimo;
                ?>
                <tr class="hover:bg-zinc-50 cursor-pointer<?= empty($r['activo']) ? ' opacity-50' : '' ?>" onclick="window.location.href='<?= url('refaccion_ver.php?id=' . $r['id']) ?>'">
                    <?php if ($puede_gestionar): ?>
                    <td class="px-3 py-2.5" @click.stop>
                        <input type="checkbox" :checked="sel.includes(<?= (int) $r['id'] ?>)" @click.stop="toggle(<?= (int) $r['id'] ?>)">
                    </td>
                    <?php endif; ?>
                    <td class="px-3 py-2.5">
                        <span class="font-mono text-xs font-bold text-zinc-900"><?= e($r['codigo']) ?></span>
                        <?php if (empty($r['activo'])): ?><span class="ml-1 text-[9px] font-bold text-zinc-400 uppercase">(inactiva)</span><?php endif; ?>
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
                    <?php if ($puede_gestionar): ?>
                    <td class="px-3 py-2.5 text-right" @click.stop>
                        <form method="POST" onclick="event.stopPropagation()" onsubmit="return confirm('<?= empty($r['activo']) ? 'Reactivar' : 'Desactivar' ?> esta refacción?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="toggle_activo">
                            <input type="hidden" name="refaccion_id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" title="<?= empty($r['activo']) ? 'Reactivar' : 'Desactivar' ?>"
                                    class="p-1.5 rounded hover:bg-zinc-100 <?= empty($r['activo']) ? 'text-emerald-600 hover:text-emerald-700' : 'text-zinc-400 hover:text-red-600' ?>">
                                <i data-lucide="<?= empty($r['activo']) ? 'rotate-ccw' : 'ban' ?>" class="w-4 h-4"></i>
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

        <?php if ($puede_gestionar): ?>
        <!-- Modal: alta de stock múltiple -->
        <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="modal = false"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <form method="POST" class="flex flex-col overflow-hidden">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="stock_multiple">
                    <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                            <i data-lucide="package-plus" class="w-4 h-4 text-bacal-700"></i>
                            Agregar stock (<span x-text="sel.length"></span>)
                        </h3>
                        <button type="button" @click="modal = false" class="p-1 rounded hover:bg-zinc-100 text-zinc-500"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <?php if (tiene_permiso('ver_todas_sucursales')): ?>
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal destino *</label>
                                <select name="sucursal_destino" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="sucursal_destino" value="<?= (int) $u['sucursal_id'] ?>">
                            <?php endif; ?>
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Proveedor</label>
                                <input type="text" name="compra_proveedor" list="lista-prov-compra" autocomplete="off" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                                <datalist id="lista-prov-compra"><?php foreach ($proveedores as $p): ?><option value="<?= e($p['nombre']) ?>"></option><?php endforeach; ?></datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">No. factura</label>
                                <input type="text" name="compra_factura" maxlength="60" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha compra</label>
                                <input type="date" name="compra_fecha" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                            </div>
                        </div>
                        <div class="border-t border-zinc-100 pt-3">
                            <div class="grid grid-cols-12 gap-2 text-[10px] font-bold text-zinc-400 uppercase tracking-wide px-1 mb-1">
                                <div class="col-span-6">Refacción</div>
                                <div class="col-span-3 text-right">Cantidad</div>
                                <div class="col-span-3 text-right">Costo unit.</div>
                            </div>
                            <div class="space-y-2 max-h-[40vh] overflow-y-auto">
                                <template x-for="id in sel" :key="id">
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-6 min-w-0">
                                            <div class="text-sm font-semibold text-zinc-900 truncate" x-text="refData[id] ? refData[id].nombre : id"></div>
                                            <div class="text-[10px] font-mono text-zinc-400" x-text="refData[id] ? refData[id].codigo : ''"></div>
                                        </div>
                                        <div class="col-span-3">
                                            <input type="number" min="0" step="0.01" :name="'cantidad['+id+']'" x-model="cants[id]"
                                                   class="w-full px-2 py-1.5 rounded-lg border border-zinc-300 text-sm text-right font-mono">
                                        </div>
                                        <div class="col-span-3">
                                            <div class="relative">
                                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-zinc-400 text-xs">$</span>
                                                <input type="number" min="0" step="0.01" :name="'costo['+id+']'" x-model="costos[id]"
                                                       class="w-full pl-5 pr-2 py-1.5 rounded-lg border border-zinc-300 text-sm text-right font-mono">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
                        <button type="button" @click="modal = false" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Registrar entradas</button>
                    </div>
                </form>
            </div>
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

<script>
function refStock() {
    return {
        sel: [],
        cants: {},
        costos: {},
        modal: false,
        bulkVal: '0',
        refData: <?= json_encode($ref_map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        toggle(id) {
            const i = this.sel.indexOf(id);
            if (i >= 0) this.sel.splice(i, 1); else this.sel.push(id);
        },
        toggleTodos(e) {
            this.sel = e.target.checked ? Object.keys(this.refData).map(Number) : [];
        },
        abrir() {
            this.sel.forEach(id => {
                if (this.cants[id] === undefined || this.cants[id] === '') this.cants[id] = 1;
                if (this.costos[id] === undefined) this.costos[id] = (this.refData[id] && this.refData[id].costo !== '') ? this.refData[id].costo : '';
            });
            this.modal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
    };
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
