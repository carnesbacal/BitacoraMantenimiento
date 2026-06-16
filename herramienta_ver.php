<?php
/**
 * ============================================================================
 * herramienta_ver.php - Ficha de una herramienta
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/herramientas_helpers.php';

requerir_login();
$u = usuario_actual();
$es_admin = tiene_permiso('administrar');
$puede_gestionar = $es_admin || tiene_permiso('resolver');

$id = (int) input('id', 0);
$her = $id > 0 ? obtener_herramienta($id) : null;

if (!$her) {
    flash_set('error', 'Herramienta no encontrada.');
    header('Location: ' . url('herramientas.php'));
    exit;
}

if (!tiene_permiso('ver_todas_sucursales') && (int) $u['sucursal_id'] !== (int) $her['sucursal_id']) {
    flash_set('error', 'No tienes permiso para ver esta herramienta.');
    header('Location: ' . url('herramientas.php'));
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
                    'tipo' => (string) input('tipo_her', '') ?: null,
                    'marca' => trim((string) input('marca', '')) ?: null,
                    'modelo' => trim((string) input('modelo', '')) ?: null,
                    'numero_serie' => trim((string) input('numero_serie', '')) ?: null,
                    'sucursal_id' => (int) input('sucursal_her', $her['sucursal_id']),
                    'ubicacion' => trim((string) input('ubicacion', '')) ?: null,
                    'fecha_adquisicion' => trim((string) input('fecha_adquisicion', '')) ?: null,
                    'costo' => (float) input('costo', 0) ?: null,
                    'proveedor_id' => (int) input('proveedor_id', 0) ?: null,
                    'notas' => trim((string) input('notas', '')) ?: null,
                ];
                if ($datos['codigo'] === '' || $datos['nombre'] === '') {
                    $errores[] = 'Código y nombre son obligatorios.';
                } else {
                    actualizar_herramienta($id, $datos);
                    flash_set('success', 'Herramienta actualizada.');
                    header('Location: ' . url("herramienta_ver.php?id=$id"));
                    exit;
                }
            } elseif ($op === 'cambiar_estado') {
                $nuevo = (string) input('nuevo_estado', '');
                if (in_array($nuevo, ['disponible','en_reparacion','extraviada','baja'], true)) {
                    cambiar_estado_herramienta($id, $nuevo);
                    flash_set('success', 'Estado actualizado.');
                    header('Location: ' . url("herramienta_ver.php?id=$id"));
                    exit;
                }
            } elseif ($op === 'eliminar' && $es_admin) {
                eliminar_herramienta($id);
                flash_set('success', 'Herramienta eliminada.');
                header('Location: ' . url('herramientas.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

$prestamos = listar_prestamos_de_herramienta($id);
$est_lbl = etiqueta_estado_herramienta($her['estado']);

$sucursales = db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre");
$proveedores = db_all("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre");

$titulo_pagina = $her['nombre'];
$pagina_activa = 'herramientas';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-6xl mx-auto animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?= url('herramientas.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-0.5">
                <span class="font-mono font-bold"><?= e($her['codigo']) ?></span>
                <?php if (!empty($her['tipo'])): ?>
                <span>·</span><span><?= e($her['tipo']) ?></span>
                <?php endif; ?>
                <span>·</span><span><?= e($her['sucursal_codigo']) ?></span>
            </div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 truncate"><?= e($her['nombre']) ?></h2>
        </div>

        <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg uppercase tracking-wider"
              style="color: <?= e($est_lbl['color']) ?>; background-color: <?= e($est_lbl['color']) ?>15">
            <i data-lucide="<?= e($est_lbl['icono']) ?>" class="w-4 h-4"></i>
            <?= e($est_lbl['label']) ?>
        </span>

        <?php if ($puede_gestionar && $her['estado'] === 'disponible'): ?>
        <button onclick="document.getElementById('modal_prestamo').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="user-check" class="w-4 h-4"></i>
            Prestar
        </button>
        <?php endif; ?>

        <?php if ($puede_gestionar && $her['estado'] === 'prestada'): ?>
        <button onclick="document.getElementById('modal_devolver').showModal()"
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="undo-2" class="w-4 h-4"></i>
            Registrar devolución
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

    <!-- Préstamo activo (banner destacado) -->
    <?php if ($her['estado'] === 'prestada' && !empty($her['prestada_a_nombre'])):
        $vencido = !empty($her['prestamo_fecha_esperada']) && strtotime($her['prestamo_fecha_esperada']) < time();
    ?>
    <div class="rounded-xl border-2 p-4 <?= $vencido ? 'border-bacal-300 bg-bacal-50' : 'border-amber-300 bg-amber-50' ?>">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center">
                <i data-lucide="user-check" class="w-6 h-6 <?= $vencido ? 'text-bacal-700' : 'text-amber-700' ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[10px] font-bold uppercase tracking-wider <?= $vencido ? 'text-bacal-700' : 'text-amber-700' ?>">
                    Préstamo activo <?= $vencido ? '· VENCIDO' : '' ?>
                </div>
                <div class="font-display text-base font-bold text-zinc-900">
                    Prestada a <?= e($her['prestada_a_nombre']) ?>
                </div>
                <div class="text-xs text-zinc-600 mt-0.5">
                    Salida: <?= e(date('d/M/Y H:i', strtotime($her['prestamo_fecha_salida']))) ?>
                    <?php if (!empty($her['prestamo_fecha_esperada'])): ?>
                    · Debe devolver: <strong class="<?= $vencido ? 'text-bacal-700' : '' ?>"><?= e(date('d/M/Y', strtotime($her['prestamo_fecha_esperada']))) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Columna principal -->
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
                        <div class="font-semibold text-zinc-900"><?= !empty($her['marca']) ? e($her['marca']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Modelo</div>
                        <div class="font-semibold text-zinc-900"><?= !empty($her['modelo']) ? e($her['modelo']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">No. serie</div>
                        <div class="font-mono text-zinc-900"><?= !empty($her['numero_serie']) ? e($her['numero_serie']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Ubicación</div>
                        <div class="font-semibold text-zinc-900"><?= !empty($her['ubicacion']) ? e($her['ubicacion']) : '—' ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Adquisición</div>
                        <div class="font-semibold text-zinc-900">
                            <?= !empty($her['fecha_adquisicion']) ? e(date('d/M/Y', strtotime($her['fecha_adquisicion']))) : '—' ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase">Costo</div>
                        <div class="font-semibold text-zinc-900">
                            <?= !empty($her['costo']) ? '$' . number_format($her['costo'], 2) : '—' ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($her['descripcion']) || !empty($her['notas'])): ?>
                <div class="mt-3 pt-3 border-t border-zinc-100 space-y-2">
                    <?php if (!empty($her['descripcion'])): ?>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase mb-1">Descripción</div>
                        <div class="text-xs text-zinc-700 whitespace-pre-wrap"><?= e($her['descripcion']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($her['notas'])): ?>
                    <div>
                        <div class="text-[10px] font-bold text-zinc-500 uppercase mb-1">Notas</div>
                        <div class="text-xs text-zinc-700 whitespace-pre-wrap"><?= e($her['notas']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Historial de préstamos -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-zinc-100">
                    <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-bacal-700"></i>
                        Historial de préstamos
                        <span class="text-xs font-normal text-zinc-500">(<?= count($prestamos) ?>)</span>
                    </h3>
                </div>

                <?php if (empty($prestamos)): ?>
                <div class="px-5 py-10 text-center text-xs text-zinc-500">
                    Sin préstamos registrados aún.
                </div>
                <?php else: ?>
                <div class="max-h-[500px] overflow-y-auto">
                <?php foreach ($prestamos as $p):
                    $estado_color = match ($p['estado']) {
                        'activo' => !empty($p['vencido']) && $p['vencido'] ? '#DC2626' : '#F59E0B',
                        'devuelta' => '#16A34A',
                        'devuelta_con_dano' => '#EA580C',
                        'extraviada' => '#DC2626',
                        default => '#71717A',
                    };
                    $estado_label = match ($p['estado']) {
                        'activo' => !empty($p['vencido']) && $p['vencido'] ? 'VENCIDO' : 'ACTIVO',
                        'devuelta' => 'DEVUELTA',
                        'devuelta_con_dano' => 'DEVUELTA C/ DAÑO',
                        'extraviada' => 'EXTRAVIADA',
                        default => strtoupper($p['estado']),
                    };
                ?>
                <div class="px-5 py-3 border-b border-zinc-100 last:border-b-0">
                    <div class="flex items-start gap-3 mb-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                                      style="color: <?= e($estado_color) ?>; background-color: <?= e($estado_color) ?>15">
                                    <?= e($estado_label) ?>
                                </span>
                                <span class="text-xs font-semibold text-zinc-900">
                                    <?= e($p['prestada_a_nombre']) ?>
                                </span>
                                <?php if (!empty($p['incidencia_folio'])): ?>
                                <a href="<?= url('incidencia_ver.php?id=' . $p['incidencia_id']) ?>"
                                   class="text-[10px] font-mono text-bacal-700 hover:underline">
                                    <?= e($p['incidencia_folio']) ?>
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-[10px] text-zinc-500">
                                Salida: <?= e(date('d/M/Y H:i', strtotime($p['fecha_salida']))) ?>
                                · Autorizó: <?= e($p['autorizada_por_nombre']) ?>
                            </div>
                            <?php if (!empty($p['fecha_devolucion_real'])): ?>
                            <div class="text-[10px] text-zinc-500">
                                Devuelta: <?= e(date('d/M/Y H:i', strtotime($p['fecha_devolucion_real']))) ?>
                                <?php if (!empty($p['recibida_por_nombre'])): ?>
                                · Recibió: <?= e($p['recibida_por_nombre']) ?>
                                <?php endif; ?>
                                <?php if (!empty($p['condicion_devolucion'])): ?>
                                · Condición: <strong><?= e($p['condicion_devolucion']) ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php elseif (!empty($p['fecha_devolucion_esperada'])): ?>
                            <div class="text-[10px] <?= !empty($p['vencido']) ? 'text-bacal-700 font-bold' : 'text-zinc-500' ?>">
                                Debe devolver: <?= e(date('d/M/Y', strtotime($p['fecha_devolucion_esperada']))) ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($p['motivo'])): ?>
                            <div class="text-[10px] text-zinc-600 italic mt-0.5">Motivo: <?= e($p['motivo']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($p['notas_salida'])): ?>
                            <div class="text-[10px] text-zinc-600 italic mt-0.5">Notas salida: <?= e($p['notas_salida']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($p['notas_devolucion'])): ?>
                            <div class="text-[10px] text-zinc-600 italic mt-0.5">Notas devolución: <?= e($p['notas_devolucion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Cambiar estado rápido -->
            <?php if ($puede_gestionar && $her['estado'] !== 'prestada'): ?>
            <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
                <h3 class="font-display text-sm font-bold text-zinc-900 mb-3 flex items-center gap-1.5">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    Cambiar estado
                </h3>
                <div class="space-y-1">
                    <?php foreach (['disponible','en_reparacion','extraviada','baja'] as $est_op):
                        if ($est_op === $her['estado']) continue;
                        $lbl = etiqueta_estado_herramienta($est_op);
                    ?>
                    <form method="POST">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="cambiar_estado">
                        <input type="hidden" name="nuevo_estado" value="<?= e($est_op) ?>">
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg hover:bg-zinc-50 text-xs font-medium flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" style="background-color: <?= e($lbl['color']) ?>"></span>
                            Marcar como <?= e($lbl['label']) ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Metadata -->
            <div class="bg-zinc-50 rounded-xl border border-zinc-200 p-4 text-xs text-zinc-600 space-y-1">
                <?php if (!empty($her['creado_por_nombre'])): ?>
                <div>Creado por <?= e($her['creado_por_nombre']) ?> · <?= e(fmt_tiempo_relativo($her['creado_en'])) ?></div>
                <?php endif; ?>
                <div>Actualizado · <?= e(fmt_tiempo_relativo($her['actualizado_en'])) ?></div>
            </div>

            <?php if ($es_admin): ?>
            <form method="POST" onsubmit="return confirm('¿Eliminar esta herramienta?');" class="text-right">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="eliminar">
                <button type="submit" class="text-xs text-zinc-500 hover:text-bacal-700 inline-flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3 h-3"></i> Eliminar herramienta
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($puede_gestionar): ?>

<!-- Modal: Prestar herramienta -->
<?php if ($her['estado'] === 'disponible'):
    $usuarios_lista = db_all("SELECT id, nombre_completo, usuario FROM usuarios WHERE activo=1 AND id != :uid ORDER BY nombre_completo", ['uid' => 1]);
?>
<dialog id="modal_prestamo" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-lg p-0">
    <form method="POST" action="<?= url('herramientas_prestamo.php') ?>" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="prestar">
        <input type="hidden" name="herramienta_id" value="<?= $id ?>">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Prestar herramienta</h3>
            <button type="button" onclick="document.getElementById('modal_prestamo').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div class="bg-blue-50 px-3 py-2 rounded text-xs text-blue-900">
                Vas a prestar: <strong><?= e($her['nombre']) ?></strong> (<?= e($her['codigo']) ?>)
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Prestar a *</label>
                <select name="prestada_a_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Selecciona técnico —</option>
                    <?php foreach ($usuarios_lista as $usr): ?>
                    <option value="<?= $usr['id'] ?>"><?= e($usr['nombre_completo']) ?> (<?= e($usr['usuario']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Devolución esperada</label>
                <input type="date" name="fecha_devolucion_esperada"
                       value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                <p class="text-[10px] text-zinc-500 mt-1">Cuando el préstamo pase de esta fecha y siga activo, aparecerá como vencido.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Motivo</label>
                <input type="text" name="motivo" maxlength="255"
                       placeholder="ej. Mantenimiento del compresor 3"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas salida</label>
                <textarea name="notas_salida" rows="2"
                          placeholder="Estado en que sale, accesorios incluidos..."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_prestamo').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Registrar préstamo</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<!-- Modal: Devolución -->
<?php if ($her['estado'] === 'prestada'): ?>
<dialog id="modal_devolver" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-lg p-0">
    <form method="POST" action="<?= url('herramientas_prestamo.php') ?>" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="devolver">
        <input type="hidden" name="prestamo_id" value="<?= $her['prestamo_id'] ?>">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Registrar devolución</h3>
            <button type="button" onclick="document.getElementById('modal_devolver').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3">
            <div class="bg-emerald-50 px-3 py-2 rounded text-xs text-emerald-900">
                Devolución de <strong><?= e($her['nombre']) ?></strong> por <strong><?= e($her['prestada_a_nombre']) ?></strong>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase">Condición al devolver *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="condicion_devolucion" value="buena" checked class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 border-zinc-200 text-zinc-600">
                            ✓ Buena
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="condicion_devolucion" value="dañada" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 border-zinc-200 text-zinc-600">
                            ⚠ Dañada
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="condicion_devolucion" value="extraviada" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-bacal-500 peer-checked:bg-bacal-50 peer-checked:text-bacal-700 border-zinc-200 text-zinc-600">
                            ✗ Extraviada
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="condicion_devolucion" value="reparada" class="sr-only peer">
                        <div class="px-3 py-2 rounded-lg border-2 text-center text-xs font-semibold peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-zinc-200 text-zinc-600">
                            🔧 Reparada
                        </div>
                    </label>
                </div>
                <p class="text-[10px] text-zinc-500 mt-2">
                    • <strong>Buena</strong>: vuelve a disponible<br>
                    • <strong>Dañada</strong>: pasa a en reparación<br>
                    • <strong>Extraviada</strong>: marca como extraviada<br>
                    • <strong>Reparada</strong>: vuelve a disponible (si la reparó el técnico)
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas devolución</label>
                <textarea name="notas_devolucion" rows="2"
                          placeholder="Estado real al recibirla, daños observados, etc."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_devolver').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Registrar devolución</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<!-- Modal: editar -->
<dialog id="modal_editar" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-2xl p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="actualizar">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Editar herramienta</h3>
            <button type="button" onclick="document.getElementById('modal_editar').close()" class="p-1 rounded hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Código *</label>
                    <input type="text" name="codigo" required value="<?= e($her['codigo']) ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required value="<?= e($her['nombre']) ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Descripción</label>
                <textarea name="descripcion" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"><?= e($her['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Tipo</label>
                    <select name="tipo_her" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">—</option>
                        <?php foreach (tipos_herramientas() as $t): ?>
                        <option value="<?= e($t) ?>" <?= $her['tipo'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Marca</label>
                    <input type="text" name="marca" value="<?= e($her['marca'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Modelo</label>
                    <input type="text" name="modelo" value="<?= e($her['modelo'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">No. serie</label>
                    <input type="text" name="numero_serie" value="<?= e($her['numero_serie'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Sucursal *</label>
                    <select name="sucursal_her" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) $her['sucursal_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Ubicación</label>
                    <input type="text" name="ubicacion" value="<?= e($her['ubicacion'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Fecha adquisición</label>
                    <input type="date" name="fecha_adquisicion" value="<?= e($her['fecha_adquisicion'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Costo</label>
                    <input type="number" name="costo" min="0" step="0.01" value="<?= e($her['costo'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Proveedor</label>
                    <select name="proveedor_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">— Ninguno —</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int) $her['proveedor_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase">Notas</label>
                <textarea name="notas" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"><?= e($her['notas'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_editar').close()" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Guardar</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
