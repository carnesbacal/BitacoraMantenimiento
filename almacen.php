<?php
/**
 * ============================================================================
 * almacen.php - Dashboard del almacén de refacciones
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';

requerir_login();
$u = usuario_actual();

// Filtro de sucursal
$f_sucursal = (int) input('sucursal_id', 0);
if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);

$stats = stats_almacen($f_sucursal ?: null);
$stock_bajo = refacciones_stock_bajo($f_sucursal ?: null);
$movimientos = listar_movimientos_recientes($f_sucursal ?: null, 15);

$titulo_pagina = 'Almacén';
$pagina_activa = 'almacen';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="warehouse" class="w-6 h-6 text-bacal-700"></i>
                    Dashboard del almacén
                </h2>
                <?php if (tiene_permiso('ver_todas_sucursales') && count($sucursales) > 1 && usuario_prefiere_radio_sucursal()): ?>
                <form method="GET" class="flex items-center gap-2 flex-wrap bg-white border border-zinc-300 rounded-lg px-3 py-1.5">
                    <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide">Sucursal:</span>
                    <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
                        <input type="radio" name="sucursal_id" value="0" onchange="this.form.submit()"
                               <?= $f_sucursal === 0 ? 'checked' : '' ?>
                               class="text-bacal-700 focus:ring-bacal-700">
                        Todas
                    </label>
                    <?php foreach ($sucursales as $s): ?>
                    <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
                        <input type="radio" name="sucursal_id" value="<?= $s['id'] ?>" onchange="this.form.submit()"
                               <?= $f_sucursal === (int) $s['id'] ? 'checked' : '' ?>
                               class="text-bacal-700 focus:ring-bacal-700">
                        <?= e($s['nombre']) ?>
                    </label>
                    <?php endforeach; ?>
                </form>
                <?php endif; ?>
            </div>
            <p class="text-xs text-zinc-500 mt-0.5">Vista general de stock, alertas y movimientos.</p>
        </div>

        <div class="flex items-center gap-2">
            <?php if (tiene_permiso('ver_todas_sucursales') && count($sucursales) > 1 && !usuario_prefiere_radio_sucursal()): ?>
            <form method="GET">
                <select name="sucursal_id" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-semibold">
                    <option value="0">Todas las sucursales</option>
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <a href="<?= url('refacciones.php') ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold text-zinc-700 flex items-center gap-1.5">
                <i data-lucide="package" class="w-4 h-4"></i>
                Ir al catálogo
            </a>
        </div>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <div class="w-9 h-9 rounded-lg bg-zinc-100 text-zinc-700 flex items-center justify-center mb-3">
                <i data-lucide="package" class="w-4 h-4"></i>
            </div>
            <div class="font-display text-3xl font-extrabold text-zinc-900"><?= (int) $stats['total_refacciones'] ?></div>
            <div class="text-[11px] text-zinc-500 mt-2 uppercase tracking-wider font-bold">Refacciones catálogo</div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mb-3">
                <i data-lucide="boxes" class="w-4 h-4"></i>
            </div>
            <div class="font-display text-3xl font-extrabold text-zinc-900"><?= number_format($stats['unidades_stock'], 0) ?></div>
            <div class="text-[11px] text-zinc-500 mt-2 uppercase tracking-wider font-bold">Unidades en stock</div>
        </div>

        <div class="bg-white rounded-xl border <?= $stats['stock_bajo'] > 0 ? 'border-bacal-300 bg-bacal-50' : 'border-zinc-200' ?> p-5">
            <div class="w-9 h-9 rounded-lg bg-bacal-100 text-bacal-700 flex items-center justify-center mb-3">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </div>
            <div class="font-display text-3xl font-extrabold <?= $stats['stock_bajo'] > 0 ? 'text-bacal-700' : 'text-zinc-900' ?>">
                <?= (int) $stats['stock_bajo'] ?>
            </div>
            <div class="text-[11px] text-zinc-500 mt-2 uppercase tracking-wider font-bold">Con stock bajo</div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 p-5">
            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                <i data-lucide="dollar-sign" class="w-4 h-4"></i>
            </div>
            <div class="font-display text-3xl font-extrabold text-zinc-900">$<?= number_format($stats['valor_inventario'], 0) ?></div>
            <div class="text-[11px] text-zinc-500 mt-2 uppercase tracking-wider font-bold">Valor inventario</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Alertas de stock bajo -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-bacal-700"></i>
                    Alertas de stock bajo
                </h3>
                <?php if (count($stock_bajo) > 0): ?>
                <span class="text-xs font-bold text-bacal-700 bg-bacal-50 px-2 py-0.5 rounded-full"><?= count($stock_bajo) ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($stock_bajo)): ?>
            <div class="px-5 py-10 text-center">
                <i data-lucide="check-circle-2" class="w-10 h-10 mx-auto text-emerald-500 mb-2"></i>
                <p class="text-sm font-semibold text-zinc-700">Todo bajo control</p>
                <p class="text-xs text-zinc-500">No hay refacciones con stock bajo en este momento.</p>
            </div>
            <?php else: ?>
            <div class="max-h-[500px] overflow-y-auto">
                <?php foreach ($stock_bajo as $r): ?>
                <a href="<?= url('refaccion_ver.php?id=' . $r['id']) ?>"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 border-b border-zinc-100 last:border-b-0">
                    <div class="w-8 h-8 rounded-lg bg-bacal-100 text-bacal-700 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="package" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-zinc-900 truncate"><?= e($r['nombre']) ?></div>
                        <div class="flex items-center gap-2 text-[10px] text-zinc-500">
                            <span class="font-mono"><?= e($r['codigo']) ?></span>
                            <span>·</span>
                            <span><?= e($r['sucursal_codigo']) ?></span>
                            <?php if (!empty($r['ubicacion'])): ?>
                            <span>·</span>
                            <span>📍 <?= e($r['ubicacion']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-bold text-bacal-700">
                            <?= number_format($r['cantidad_actual'], 0) ?> <span class="text-[10px] font-normal text-zinc-500"><?= e($r['unidad_medida']) ?></span>
                        </div>
                        <div class="text-[10px] text-zinc-500">
                            Mín: <?= number_format($r['cantidad_minima'], 0) ?>
                            <?php if ($r['cantidad_optima']): ?>
                            · Óptimo: <?= number_format($r['cantidad_optima'], 0) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
            <div class="px-5 py-10 text-center">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto text-zinc-300 mb-2"></i>
                <p class="text-sm font-semibold text-zinc-700">Sin movimientos</p>
                <p class="text-xs text-zinc-500">Los movimientos aparecerán aquí cuando registres entradas o salidas.</p>
            </div>
            <?php else: ?>
            <div class="max-h-[500px] overflow-y-auto">
                <?php foreach ($movimientos as $m):
                    $tipo_color = match ($m['tipo']) {
                        'entrada' => '#16A34A',
                        'salida' => '#DC2626',
                        'ajuste' => '#F59E0B',
                        'transferencia' => '#0EA5E9',
                        default => '#71717A',
                    };
                    $signo = match ($m['tipo']) {
                        'entrada' => '+', 'salida' => '−', 'ajuste' => '=', default => '→',
                    };
                ?>
                <a href="<?= url('refaccion_ver.php?id=' . $m['refaccion_id']) ?>"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 border-b border-zinc-100 last:border-b-0">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background-color: <?= e($tipo_color) ?>15">
                        <span class="font-bold text-sm" style="color: <?= e($tipo_color) ?>"><?= $signo ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-zinc-900 truncate"><?= e($m['refaccion_nombre']) ?></div>
                        <div class="flex items-center gap-2 text-[10px] text-zinc-500 flex-wrap">
                            <span class="font-mono"><?= e($m['refaccion_codigo']) ?></span>
                            <span>·</span>
                            <span class="uppercase font-bold" style="color: <?= e($tipo_color) ?>"><?= e($m['tipo']) ?></span>
                            <?php if (!empty($m['motivo'])): ?>
                            <span>·</span>
                            <span><?= e($m['motivo']) ?></span>
                            <?php endif; ?>
                            <span>·</span>
                            <span><?= e($m['sucursal_codigo']) ?></span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-sm font-bold" style="color: <?= e($tipo_color) ?>">
                            <?= $signo ?><?= number_format($m['cantidad'], 0) ?>
                        </div>
                        <div class="text-[10px] text-zinc-500"><?= e(fmt_tiempo_relativo($m['creado_en'])) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
