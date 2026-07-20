<?php
/**
 * ============================================================================
 * requisicion_ver.php - Detalle de una requisición de compra
 * ============================================================================
 * Renglones (catálogo o texto libre), status por renglón, autorización (admin)
 * y exportación al formato impreso.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';
require_once __DIR__ . '/config/requisiciones_helpers.php';

requerir_login();
$u = usuario_actual();

if (!requisiciones_disponible()) {
    flash_set('error', 'Falta correr migracion_requisiciones.sql.');
    header('Location: ' . url('refacciones.php'));
    exit;
}

$id  = (int) input('id', 0);
$req = requisicion_obtener($id);
if (!$req) {
    flash_set('error', 'Requisición no encontrada.');
    header('Location: ' . url('refacciones_requisiciones.php'));
    exit;
}

$es_admin        = tiene_permiso('administrar');
$puede_gestionar = $es_admin || tiene_permiso('resolver');

// Si no puede ver todas las sucursales, solo la suya
if (!tiene_permiso('ver_todas_sucursales') && (int) $req['sucursal_id'] !== (int) $u['sucursal_id']) {
    flash_set('error', 'No tienes acceso a esa requisición.');
    header('Location: ' . url('refacciones_requisiciones.php'));
    exit;
}

$errores  = [];
$editable = $puede_gestionar && in_array($req['estado'], ['borrador', 'enviada'], true);
$puede_recibir = $puede_gestionar && in_array($req['estado'], ['enviada', 'autorizada', 'cerrada'], true);

// ----------------------------------------------------------------------------
// Acciones
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');
        try {
            if ($op === 'item_agregar' && $editable) {
                requisicion_item_agregar($id, [
                    'refaccion_id' => input('refaccion_id', '') !== '' ? (int) input('refaccion_id') : null,
                    'descripcion'  => input('descripcion', ''),
                    'cantidad'     => input('cantidad', 0),
                    'unidad'       => input('unidad', ''),
                    'area_id'      => input('area_id', '') !== '' ? (int) input('area_id') : null,
                    'notas'        => input('notas_item', ''),
                ]);
                flash_set('exito', 'Renglón agregado.');

            } elseif ($op === 'item_editar' && $editable) {
                requisicion_item_actualizar((int) input('item_id', 0), [
                    'descripcion' => input('descripcion', ''),
                    'cantidad'    => input('cantidad', 0),
                    'unidad'      => input('unidad', ''),
                    'area_id'     => input('area_id', '') !== '' ? (int) input('area_id') : null,
                    'status'      => input('status', 'pendiente'),
                    'notas'       => input('notas_item', ''),
                ]);
                flash_set('exito', 'Renglón actualizado.');

            } elseif ($op === 'item_status') {
                // Marcar comprado/cancelado se permite aunque ya esté autorizada
                $item_id = (int) input('item_id', 0);
                $it = db_one("SELECT * FROM refacciones_requisicion_items WHERE id = :i AND requisicion_id = :r",
                    ['i' => $item_id, 'r' => $id]);
                if ($it) {
                    requisicion_item_actualizar($item_id, [
                        'descripcion' => $it['descripcion'],
                        'cantidad'    => $it['cantidad'],
                        'unidad'      => $it['unidad'],
                        'area_id'     => $it['area_id'],
                        'status'      => input('status', 'pendiente'),
                        'notas'       => $it['notas'],
                    ]);
                }

            } elseif ($op === 'item_recibir' && $puede_recibir) {
                $res = requisicion_item_recibir((int) input('item_id', 0), [
                    'cantidad'        => input('cantidad', 0),
                    'costo_unitario'  => input('costo_unitario', ''),
                    'notas'           => input('notas_item', ''),
                    'crear_refaccion' => input('crear_refaccion') ? 1 : 0,
                    'nuevo_codigo'    => input('nuevo_codigo', ''),
                    'nueva_categoria' => input('nueva_categoria', ''),
                ], (int) $u['id']);
                registrar_auditoria('recibir_requisicion_item', 'refacciones_requisicion_items', (int) input('item_id', 0), $req['folio']);
                flash_set('exito', $res['afecto_stock']
                    ? ('Recepción registrada. Se dio entrada al almacén'
                        . ($res['creo_refaccion'] ? ' y se creó la refacción en el catálogo.' : '.'))
                    : 'Recepción registrada como informativa (no afectó el almacén).');

            } elseif ($op === 'item_eliminar' && $editable) {
                requisicion_item_eliminar((int) input('item_id', 0));
                flash_set('exito', 'Renglón eliminado.');

            } elseif ($op === 'prellenar' && $editable) {
                $n = requisicion_prellenar_bajo_minimo($id, (int) $req['sucursal_id']);
                flash_set('exito', $n > 0
                    ? "Se agregaron {$n} renglón(es) con refacciones bajo mínimo."
                    : 'No hay refacciones bajo mínimo pendientes de agregar.');

            } elseif ($op === 'cabecera' && $editable) {
                requisicion_actualizar($id, trim((string) input('fecha', '')), trim((string) input('notas', '')), (string) input('razon_social', ''));
                flash_set('exito', 'Requisición actualizada.');

            } elseif ($op === 'estado') {
                $nuevo = (string) input('estado', '');
                if ($nuevo === 'autorizada' && !$es_admin) {
                    $errores[] = 'Solo un administrador puede autorizar la requisición.';
                } else {
                    requisicion_cambiar_estado($id, $nuevo, (int) $u['id']);
                    registrar_auditoria('estado_requisicion', 'refacciones_requisiciones', $id, "Estado: {$nuevo}");
                    flash_set('exito', 'Estado actualizado.');
                }

            } elseif ($op === 'eliminar' && $es_admin) {
                requisicion_eliminar($id);
                registrar_auditoria('eliminar_requisicion', 'refacciones_requisiciones', $id, $req['folio']);
                flash_set('exito', 'Requisición eliminada.');
                header('Location: ' . url('refacciones_requisiciones.php'));
                exit;
            }

            if (empty($errores)) {
                header('Location: ' . url('requisicion_ver.php?id=' . $id));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Recargar por si cambió
$req    = requisicion_obtener($id);
$items  = requisicion_items($id);
$editable = $puede_gestionar && in_array($req['estado'], ['borrador', 'enviada'], true);
$puede_recibir = $puede_gestionar && in_array($req['estado'], ['enviada', 'autorizada', 'cerrada'], true);
$informativos  = requisicion_items_informativos($id);
$pendientes    = requisicion_items_pendientes($id);

$catalogo  = db_all("SELECT id, codigo, nombre, unidad_medida FROM refacciones WHERE activo = 1 ORDER BY nombre");
$areas     = db_all("SELECT id, nombre FROM areas WHERE activo = 1 ORDER BY nombre");
$unidades  = unidades_medida();
$estados   = requisicion_estados();
$statuses  = requisicion_item_status();
$cfg_est   = $estados[$req['estado']] ?? ['label' => $req['estado'], 'color' => 'zinc'];
$empresas  = requisicion_empresas();
$emp_actual = requisicion_empresa($req['razon_social'] ?? null);

$titulo_pagina = 'Requisición ' . $req['folio'];
$pagina_activa = 'refacciones';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Encabezado -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="<?= url('refacciones_requisiciones.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="font-display text-2xl font-extrabold text-zinc-900"><?= e($req['folio']) ?></h2>
                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-<?= $cfg_est['color'] ?>-100 text-<?= $cfg_est['color'] ?>-800"><?= e($cfg_est['label']) ?></span>
                </div>
                <p class="text-xs text-zinc-500 mt-0.5">
                    <?= e($req['sucursal_nombre']) ?> · <?= e(fmt_fecha($req['fecha'], false)) ?> · Solicitó: <?= e($req['solicito_nombre']) ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= url('requisicion_imprimir.php?id=' . $id . '&formato=controlado') ?>" target="_blank"
               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 flex items-center gap-1.5">
                <i data-lucide="printer" class="w-4 h-4"></i> Formato 0069-FRM
            </a>
            <a href="<?= url('requisicion_imprimir.php?id=' . $id . '&formato=simple') ?>" target="_blank"
               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-4 h-4"></i> Formato simple
            </a>
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

    <!-- Datos + flujo de estado -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-3">Datos de la requisición</h3>
            <?php if ($editable): ?>
            <form method="POST" class="flex flex-wrap gap-3 items-end">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="cabecera">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Fecha</label>
                    <input type="date" name="fecha" value="<?= e($req['fecha']) ?>"
                           class="px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Razón social</label>
                    <select name="razon_social" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <?php foreach ($empresas as $k => $emp): ?>
                        <option value="<?= e($k) ?>" <?= ($req['razon_social'] ?? 'corral') === $k ? 'selected' : '' ?>><?= e($emp['corto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Notas</label>
                    <input type="text" name="notas" maxlength="500" value="<?= e((string) $req['notas']) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Guardar</button>
            </form>
            <?php else: ?>
            <dl class="text-sm space-y-1">
                <div class="flex gap-2"><dt class="text-zinc-500 w-24">Fecha:</dt><dd class="font-medium"><?= e(fmt_fecha($req['fecha'], false)) ?></dd></div>
                <div class="flex gap-2"><dt class="text-zinc-500 w-24">Razón social:</dt><dd class="font-medium"><?= e($emp_actual['corto']) ?></dd></div>
                <div class="flex gap-2"><dt class="text-zinc-500 w-24">Notas:</dt><dd class="font-medium"><?= e((string) $req['notas']) ?: '—' ?></dd></div>
            </dl>
            <?php endif; ?>

            <?php if ($req['autorizado_por_id']): ?>
            <div class="mt-4 pt-3 border-t border-zinc-100 text-xs text-emerald-700 flex items-center gap-1.5">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                Autorizó <strong><?= e($req['autorizo_nombre']) ?></strong> el <?= e(fmt_fecha($req['autorizado_en'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
            <h3 class="font-semibold text-sm text-zinc-800 mb-3">Estado</h3>
            <?php if ($puede_gestionar): ?>
            <div class="flex flex-wrap gap-2">
                <?php
                // Transiciones permitidas según el estado actual
                $siguientes = match ($req['estado']) {
                    'borrador'   => ['enviada' => 'Enviar', 'cancelada' => 'Cancelar'],
                    'enviada'    => ($es_admin ? ['autorizada' => 'Autorizar'] : []) + ['borrador' => 'Regresar a borrador', 'cancelada' => 'Cancelar'],
                    'autorizada' => ['cerrada' => 'Cerrar', 'cancelada' => 'Cancelar'],
                    'cerrada'    => ['autorizada' => 'Reabrir'],
                    default      => ['borrador' => 'Reactivar'],
                };
                foreach ($siguientes as $val => $lbl):
                    $primario = in_array($val, ['enviada', 'autorizada', 'cerrada'], true);
                ?>
                <form method="POST" class="inline"
                      <?= ($val === 'cerrada' && $pendientes > 0) ? 'onsubmit="return confirm(\'Quedan ' . $pendientes . ' renglón(es) sin recibir por completo. ¿Cerrar de todas formas?\')"' : '' ?>>
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="estado">
                    <input type="hidden" name="estado" value="<?= e($val) ?>">
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $primario ? 'bg-bacal-700 text-white hover:bg-bacal-800' : 'border border-zinc-300 text-zinc-700 hover:bg-zinc-50' ?>">
                        <?= e($lbl) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
            <?php if ($req['estado'] === 'enviada' && !$es_admin): ?>
            <p class="text-[11px] text-zinc-400 mt-2">La autorización la realiza un administrador.</p>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($es_admin): ?>
            <form method="POST" class="mt-4 pt-3 border-t border-zinc-100" onsubmit="return confirm('¿Eliminar esta requisición y todos sus renglones?')">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="eliminar">
                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Eliminar requisición
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Renglones -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2 flex-wrap">
            <i data-lucide="list" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Renglones</h3>
            <span class="text-xs text-zinc-500">(<?= count($items) ?>)</span>
            <?php if ($editable): ?>
            <form method="POST" class="ml-auto">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="prellenar">
                <button type="submit" class="px-3 py-1.5 rounded-lg border border-bacal-300 bg-bacal-50 text-bacal-700 text-xs font-semibold hover:bg-bacal-100 flex items-center gap-1.5">
                    <i data-lucide="wand-2" class="w-3.5 h-3.5"></i> Agregar lo que está bajo mínimo
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($informativos > 0): ?>
        <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 text-xs text-amber-800 flex items-start gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span><strong><?= $informativos ?> artículo(s)</strong> de esta requisición no están en el catálogo de refacciones, así que <strong>no afectan el almacén</strong>. Al recibirlos puedes darlos de alta en el catálogo (y entonces sí suman al stock) o dejarlos solo como informativos.</span>
        </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
        <div class="px-5 py-10 text-center text-sm text-zinc-500">Sin renglones todavía.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-24">Cantidad</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-24">Recibido</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-28">Unidad</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Área</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider w-32">Status</th>
                        <th class="px-4 py-2.5 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($items as $it):
                        $st = $statuses[$it['status']] ?? ['label' => $it['status'], 'color' => 'zinc'];
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 text-right font-mono font-semibold text-zinc-900">
                            <?= rtrim(rtrim(number_format((float) $it['cantidad'], 2), '0'), '.') ?>
                        </td>
                        <?php $rec = (float) ($it['cantidad_recibida'] ?? 0); ?>
                        <td class="px-4 py-2.5 text-right font-mono <?= $rec > 0 ? 'text-emerald-700 font-semibold' : 'text-zinc-300' ?>">
                            <?= $rec > 0 ? rtrim(rtrim(number_format($rec, 2), '0'), '.') : '—' ?>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-600"><?= e($unidades[$it['unidad']] ?? (string) $it['unidad']) ?: '—' ?></td>
                        <td class="px-4 py-2.5">
                            <div class="text-sm text-zinc-900"><?= e($it['descripcion']) ?></div>
                            <?php if ($it['refaccion_codigo']): ?>
                            <div class="text-[10px] text-zinc-400 font-mono">Catálogo: <?= e($it['refaccion_codigo']) ?></div>
                            <?php else: ?>
                            <div class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">
                                <i data-lucide="alert-triangle" class="w-3 h-3"></i> No está en catálogo · no afecta stock
                            </div>
                            <?php endif; ?>
                            <?php if ($it['notas']): ?>
                            <div class="text-[10px] text-zinc-500 italic"><?= e($it['notas']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-600"><?= e((string) $it['area_nombre']) ?: '—' ?></td>
                        <td class="px-4 py-2.5">
                            <?php if ($puede_gestionar && $req['estado'] !== 'cancelada'): ?>
                            <form method="POST" class="inline">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="item_status">
                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                <select name="status" onchange="this.form.submit()"
                                        class="px-2 py-1 rounded border border-zinc-300 bg-white text-xs font-semibold text-<?= $st['color'] ?>-700">
                                    <?php foreach ($statuses as $k => $cfg): ?>
                                    <option value="<?= $k ?>" <?= $it['status'] === $k ? 'selected' : '' ?>><?= e($cfg['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <?php else: ?>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-<?= $st['color'] ?>-100 text-<?= $st['color'] ?>-800"><?= e($st['label']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <?php if ($puede_recibir && $it['status'] !== 'cancelado' && (float) ($it['cantidad_recibida'] ?? 0) < (float) $it['cantidad']): ?>
                            <button type="button" title="Registrar recepción"
                                    onclick="recibirItem(<?= htmlspecialchars(json_encode([
                                        'id' => (int) $it['id'],
                                        'descripcion' => $it['descripcion'],
                                        'pendiente' => max(0, (float) $it['cantidad'] - (float) ($it['cantidad_recibida'] ?? 0)),
                                        'unidad' => (string) $it['unidad'],
                                        'catalogo' => !empty($it['refaccion_id']),
                                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)"
                                    class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold">
                                Recibir
                            </button>
                            <?php endif; ?>
                            <?php if ($editable): ?>
                            <button type="button" title="Editar renglón"
                                    onclick="editarItem(<?= htmlspecialchars(json_encode([
                                        'id' => (int) $it['id'],
                                        'descripcion' => $it['descripcion'],
                                        'cantidad' => (float) $it['cantidad'],
                                        'unidad' => (string) $it['unidad'],
                                        'area_id' => (int) ($it['area_id'] ?? 0),
                                        'status' => $it['status'],
                                        'notas' => (string) $it['notas'],
                                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)"
                                    class="p-1.5 rounded hover:bg-bacal-50 text-zinc-400 hover:text-bacal-700">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este renglón?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="item_eliminar">
                                <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-zinc-400 hover:text-red-600">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Alta de renglón -->
        <?php if ($editable): ?>
        <div class="border-t border-zinc-200 bg-zinc-50 p-4">
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end" id="form-item">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="item_agregar">

                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Del catálogo (opcional)</label>
                    <select name="refaccion_id" id="sel_refaccion" onchange="autollenar()"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <option value="">— Texto libre —</option>
                        <?php foreach ($catalogo as $c): ?>
                        <option value="<?= $c['id'] ?>"
                                data-nombre="<?= e($c['nombre'] . ' (' . $c['codigo'] . ')') ?>"
                                data-unidad="<?= e((string) $c['unidad_medida']) ?>">
                            <?= e($c['codigo']) ?> — <?= e($c['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Descripción <span class="text-red-500">*</span></label>
                    <input type="text" name="descripcion" id="inp_desc" required maxlength="300"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm" placeholder="Qué se necesita">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Cant. <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" required step="0.01" min="0.01" value="1"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Unidad</label>
                    <select name="unidad" id="sel_unidad" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <option value="">—</option>
                        <?php foreach ($unidades as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Área</label>
                    <select name="area_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <option value="">—</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= e($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-10">
                    <input type="text" name="notas_item" maxlength="255" placeholder="Nota del renglón (opcional)"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center justify-center gap-1.5">
                        <i data-lucide="plus" class="w-4 h-4"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal editar renglón -->
<?php if ($editable): ?>
<div id="modal-item" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-4">Editar renglón</h3>
        <form method="POST" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="item_editar">
            <input type="hidden" name="item_id" id="e_item_id">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                <input type="text" name="descripcion" id="e_desc" required maxlength="300"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" id="e_cant" required step="0.01" min="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Unidad</label>
                    <select name="unidad" id="e_unidad" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <option value="">—</option>
                        <?php foreach ($unidades as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Status</label>
                    <select name="status" id="e_status" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                        <?php foreach ($statuses as $k => $cfg): ?>
                        <option value="<?= $k ?>"><?= e($cfg['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Área</label>
                <select name="area_id" id="e_area" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                    <option value="">—</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= e($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                <input type="text" name="notas_item" id="e_notas" maxlength="255"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-item').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal recibir renglón -->
<?php if ($puede_recibir): ?>
<div id="modal-recibir" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-1 flex items-center gap-2">
            <i data-lucide="package-check" class="w-4 h-4 text-emerald-600"></i> Registrar recepción
        </h3>
        <p id="r_desc" class="text-xs text-zinc-500 mb-4"></p>

        <form method="POST" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="item_recibir">
            <input type="hidden" name="item_id" id="r_item_id">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Cantidad recibida <span class="text-red-500">*</span></label>
                    <input type="number" name="cantidad" id="r_cant" required step="0.01" min="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
                    <p class="text-[10px] text-zinc-400 mt-0.5">Puedes recibir menos y completar después.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Costo unitario</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="costo_unitario" id="r_costo" step="0.01" min="0"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Notas de la recepción</label>
                <input type="text" name="notas_item" maxlength="255" placeholder="Factura, proveedor, etc."
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
            </div>

            <!-- Solo para renglones que no están en catálogo -->
            <div id="r_alta" class="hidden bg-amber-50 border border-amber-200 rounded-lg p-3 space-y-2">
                <p class="text-xs text-amber-800">
                    Este artículo <strong>no está en el catálogo</strong>, por lo que de momento no afecta el almacén.
                </p>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="crear_refaccion" id="r_crear" value="1" onchange="toggleAlta()"
                           class="mt-0.5 w-4 h-4 rounded border-zinc-300 text-bacal-700 focus:ring-bacal-500">
                    <span class="text-xs font-semibold text-zinc-800">Darlo de alta en el catálogo y cargarlo al stock</span>
                </label>
                <div id="r_alta_campos" class="hidden grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 uppercase mb-1">Código <span class="text-red-500">*</span></label>
                        <input type="text" name="nuevo_codigo" id="r_codigo" maxlength="50"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 uppercase mb-1">Categoría</label>
                        <select name="nueva_categoria" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                            <option value="">—</option>
                            <?php foreach (categorias_refacciones() as $ck => $cl): ?>
                            <option value="<?= e(is_int($ck) ? $cl : $ck) ?>"><?= e($cl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="text-[10px] text-amber-700">Si lo dejas sin marcar, la recepción se registra solo como informativa.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-recibir').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">
                    Registrar recepción
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Autocompletar descripción y unidad al elegir del catálogo
function autollenar() {
    var sel = document.getElementById('sel_refaccion');
    if (!sel) return;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    var d = document.getElementById('inp_desc');
    var u = document.getElementById('sel_unidad');
    if (d && !d.value) d.value = opt.dataset.nombre || '';
    if (u && opt.dataset.unidad) u.value = opt.dataset.unidad;
}

function toggleAlta() {
    var chk = document.getElementById('r_crear');
    var campos = document.getElementById('r_alta_campos');
    if (!chk || !campos) return;
    campos.classList.toggle('hidden', !chk.checked);
    var cod = document.getElementById('r_codigo');
    if (cod) cod.required = chk.checked;
}

function recibirItem(d) {
    document.getElementById('r_item_id').value = d.id;
    document.getElementById('r_desc').textContent = d.descripcion + ' · pendiente: ' + d.pendiente + (d.unidad ? ' ' + d.unidad : '');
    document.getElementById('r_cant').value = d.pendiente;
    document.getElementById('r_costo').value = '';
    var alta = document.getElementById('r_alta');
    var chk  = document.getElementById('r_crear');
    if (chk) chk.checked = false;
    if (alta) alta.classList.toggle('hidden', !!d.catalogo);
    toggleAlta();
    document.getElementById('modal-recibir').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function editarItem(d) {
    document.getElementById('e_item_id').value = d.id;
    document.getElementById('e_desc').value    = d.descripcion || '';
    document.getElementById('e_cant').value    = d.cantidad;
    document.getElementById('e_unidad').value  = d.unidad || '';
    document.getElementById('e_status').value  = d.status || 'pendiente';
    document.getElementById('e_area').value    = d.area_id ? String(d.area_id) : '';
    document.getElementById('e_notas').value   = d.notas || '';
    document.getElementById('modal-item').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
