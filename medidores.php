<?php
/**
 * ============================================================================
 * medidores.php - Catálogo de medidores (luz, agua, gas, diésel...)
 * ============================================================================
 * Lista, alta y edición de los medidores físicos.
 * Acciones (query string): listar (default), nuevo, editar, toggle.
 *
 * Crear/editar: admin + ingenieros (puede_resolver)
 * Activar/desactivar: solo admin
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/medidores_helpers.php';

requerir_login();

$puede_crear_editar = tiene_permiso('administrar') || tiene_permiso('resolver');
$puede_desactivar   = tiene_permiso('administrar');

$accion = (string) input('accion', 'listar');
$id     = (int) input('id', 0);

if ($accion === 'nuevo' && !$puede_crear_editar) { flash_set('error', 'No tienes permiso para crear medidores.'); header('Location: ' . url('medidores.php')); exit; }
if ($accion === 'editar' && !$puede_crear_editar) { flash_set('error', 'No tienes permiso para editar medidores.'); header('Location: ' . url('medidores.php')); exit; }
if ($accion === 'toggle' && !$puede_desactivar) { flash_set('error', 'No tienes permiso para esta acción.'); header('Location: ' . url('medidores.php')); exit; }

$errores = [];

// Cargar el medidor a editar (si aplica)
$medidor_edit = null;
if (($accion === 'editar' || $accion === 'toggle') && $id) {
    $medidor_edit = obtener_medidor($id);
    if (!$medidor_edit) { flash_set('error', 'Medidor no encontrado.'); header('Location: ' . url('medidores.php')); exit; }
    if (!puede_ver_medidor($medidor_edit)) { flash_set('error', 'Ese medidor pertenece a otra sucursal.'); header('Location: ' . url('medidores.php')); exit; }
}

// ----------------------------------------------------------------------------
// PROCESAR POST
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');
        try {
            if ($op === 'crear' || $op === 'editar') {
                $datos = [
                    'tipo_id'       => (int) input('tipo_id', 0),
                    'nombre'        => trim((string) input('nombre', '')),
                    'numero_serie'  => trim((string) input('numero_serie', '')) ?: null,
                    'sucursal_id'   => (int) input('sucursal_id', 0),
                    'area_id'       => (int) input('area_id', 0) ?: null,
                    'ubicacion'     => trim((string) input('ubicacion', '')) ?: null,
                    'tarifa'        => trim((string) input('tarifa', '')),
                    'notas'         => trim((string) input('notas', '')) ?: null,
                ];

                if ($datos['nombre'] === '')      $errores[] = 'El nombre es obligatorio.';
                if ($datos['tipo_id'] <= 0)       $errores[] = 'Selecciona el tipo de medidor.';
                if ($datos['sucursal_id'] <= 0)   $errores[] = 'Selecciona la sucursal.';

                // No permitir asignar a una sucursal fuera del alcance del usuario
                $rsuc = medidor_sucursal_usuario();
                if ($rsuc !== null && $rsuc !== (int) $datos['sucursal_id']) {
                    $errores[] = 'No puedes registrar medidores en esa sucursal.';
                }

                if (empty($errores)) {
                    $uid = (int) usuario_actual()['id'];
                    if ($op === 'crear') {
                        $nuevo_id = crear_medidor($datos, $uid);

                        // Lectura inicial opcional: queda como base del histórico
                        $valor_inicial = trim((string) input('valor_inicial', ''));
                        if ($valor_inicial !== '' && is_numeric($valor_inicial)) {
                            registrar_lectura($nuevo_id, [
                                'valor_lectura' => (float) $valor_inicial,
                                'fecha_lectura' => date('Y-m-d'),
                                'nota' => 'Lectura inicial al dar de alta el medidor',
                            ], $uid);
                        }

                        registrar_auditoria('crear_medidor', 'medidores', $nuevo_id, "Medidor {$datos['nombre']}");
                        flash_set('success', "Medidor \"{$datos['nombre']}\" creado.");
                    } else {
                        actualizar_medidor($id, $datos);
                        registrar_auditoria('editar_medidor', 'medidores', $id, "Medidor {$datos['nombre']}");
                        flash_set('success', "Medidor actualizado.");
                    }
                    header('Location: ' . url('medidores.php'));
                    exit;
                }
            } elseif ($op === 'toggle' && $medidor_edit) {
                $nuevo = !$medidor_edit['activo'];
                cambiar_estado_medidor($id, $nuevo);
                registrar_auditoria('toggle_medidor', 'medidores', $id,
                    ($nuevo ? 'Activación' : 'Desactivación') . " de medidor {$medidor_edit['nombre']}");
                flash_set('success', "Medidor " . ($nuevo ? 'activado' : 'desactivado') . '.');
                header('Location: ' . url('medidores.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

$titulo_pagina = 'Medidores';
$pagina_activa = 'medidores';
require_once __DIR__ . '/config/header.php';

// Catálogos para los selects
$tipos      = listar_tipos_medidor(true);
$sucursales = db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo = 1 ORDER BY nombre ASC");
$areas      = db_all("SELECT id, nombre FROM areas WHERE activo = 1 ORDER BY nombre ASC");

// Restricción por sucursal: si el usuario no ve todas, limitamos a la suya
$restriccion_suc = medidor_sucursal_usuario();
if ($restriccion_suc !== null) {
    $sucursales = array_values(array_filter($sucursales, fn($s) => (int) $s['id'] === $restriccion_suc));
}

// Mapa tipo_id => unidad (para mostrar la unidad junto a la tarifa con Alpine)
$tipos_unidad = [];
foreach ($tipos as $t) $tipos_unidad[(int) $t['id']] = $t['unidad'];
?>

<?php
// ============================================================================
// VISTA: FORMULARIO (crear o editar)
// ============================================================================
if ($accion === 'nuevo' || ($accion === 'editar' && $medidor_edit)):
    $es_edicion = ($accion === 'editar');
    $m = $medidor_edit;
?>
<div class="max-w-3xl mx-auto animate-fade-in"
     x-data="{ unidades: <?= htmlspecialchars(json_encode($tipos_unidad), ENT_QUOTES) ?>,
               tipoSel: <?= (int) ($es_edicion ? $m['tipo_id'] : (int) input('tipo_id', 0)) ?>,
               get unidad() { return this.unidades[this.tipoSel] || 'unidad'; } }">

    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('medidores.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900">
                <?= $es_edicion ? 'Editar medidor' : 'Nuevo medidor' ?>
            </h2>
            <p class="text-xs text-zinc-500"><?= $es_edicion ? e($m['nombre']) : 'Registra un medidor físico (luz, agua, gas, diésel…)' ?></p>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs">
            <?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (empty($tipos)): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
        No hay tipos de medidor activos. Primero crea al menos uno en
        <a href="<?= url('admin/catalogos.php?tab=medidor_tipos') ?>" class="underline font-semibold">Catálogos → Tipos de medidor</a>.
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="<?= $es_edicion ? 'editar' : 'crear' ?>">

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="gauge" class="w-4 h-4 text-bacal-700"></i> Datos del medidor
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Tipo *</label>
                    <select name="tipo_id" required x-model.number="tipoSel"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:border-bacal-700">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int) ($es_edicion ? $m['tipo_id'] : (int) input('tipo_id', 0)) === (int) $t['id'] ? 'selected' : '' ?>>
                            <?= e($t['nombre']) ?> (<?= e($t['unidad']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre / etiqueta *</label>
                    <input type="text" name="nombre" required maxlength="150"
                           value="<?= e($es_edicion ? $m['nombre'] : (string) input('nombre', '')) ?>"
                           placeholder="ej. Agua - Cocina, Luz - Cámaras frío"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal *</label>
                    <select name="sucursal_id" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:border-bacal-700">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) ($es_edicion ? $m['sucursal_id'] : (int) input('sucursal_id', 0)) === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nombre']) ?> (<?= e($s['codigo']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Área <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <select name="area_id"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:border-bacal-700">
                        <option value="">— Sin área —</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= (int) ($es_edicion ? (int) $m['area_id'] : (int) input('area_id', 0)) === (int) $a['id'] ? 'selected' : '' ?>>
                            <?= e($a['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Ubicación <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="text" name="ubicacion" maxlength="255"
                           value="<?= e($es_edicion ? (string) $m['ubicacion'] : (string) input('ubicacion', '')) ?>"
                           placeholder="ej. Patio trasero, entrada principal"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Número de serie <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="text" name="numero_serie" maxlength="100"
                           value="<?= e($es_edicion ? (string) $m['numero_serie'] : (string) input('numero_serie', '')) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="banknote" class="w-4 h-4 text-bacal-700"></i> Costo y lectura inicial
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Tarifa por unidad <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="tarifa" step="0.0001" min="0"
                               value="<?= e($es_edicion && $m['tarifa'] !== null ? $m['tarifa'] : (string) input('tarifa', '')) ?>"
                               placeholder="0.0000"
                               class="w-full pl-7 pr-16 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs">/ <span x-text="unidad"></span></span>
                    </div>
                    <p class="text-[10px] text-zinc-400 mt-1">Costo estimado = consumo × tarifa. Puedes actualizarla después.</p>
                </div>

                <?php if (!$es_edicion): ?>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Lectura inicial <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <div class="relative">
                        <input type="number" name="valor_inicial" step="0.001" min="0"
                               value="<?= e((string) input('valor_inicial', '')) ?>"
                               placeholder="Número que marca hoy"
                               class="w-full pl-3 pr-16 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"><span x-text="unidad"></span></span>
                    </div>
                    <p class="text-[10px] text-zinc-400 mt-1">Punto de partida del histórico. Si lo dejas vacío, la primera lectura que captures será la base.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Notas <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
            <textarea name="notas" rows="2"
                      class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"
                      placeholder="ej. Medidor de respaldo, requiere escalera para leerlo, etc."><?= e($es_edicion ? (string) $m['notas'] : (string) input('notas', '')) ?></textarea>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="<?= url('medidores.php') ?>" class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-600 hover:bg-zinc-50">Cancelar</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm">
                <?= $es_edicion ? 'Guardar cambios' : 'Crear medidor' ?>
            </button>
        </div>
    </form>
</div>

<?php
// ============================================================================
// VISTA: LISTA
// ============================================================================
else:
    $q           = trim((string) input('q', ''));
    $f_sucursal  = (int) input('sucursal_id', 0);
    $f_tipo      = (int) input('tipo_id', 0);

    // Si el usuario está limitado a una sucursal, forzamos el filtro
    if ($restriccion_suc !== null) $f_sucursal = $restriccion_suc;

    $medidores = listar_medidores([
        'busqueda'    => $q !== '' ? $q : null,
        'sucursal_id' => $f_sucursal ?: null,
        'tipo_id'     => $f_tipo ?: null,
    ]);
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="font-display text-2xl font-extrabold text-zinc-900">Medidores</h2>
        <p class="text-xs text-zinc-500 mt-0.5">Medidores de servicios y consumos. <?= count($medidores) ?> registro(s).</p>
    </div>
    <?php if ($puede_crear_editar): ?>
    <div class="flex items-center gap-2">
        <a href="<?= url('lecturas_captura.php') ?>"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-bacal-200 bg-bacal-50 hover:bg-bacal-100 text-bacal-700 text-sm font-semibold transition-colors">
            <i data-lucide="clipboard-list" class="w-4 h-4"></i> Captura por sucursal
        </a>
        <a href="<?= url('medidores.php?accion=nuevo') ?>"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo medidor
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filtros -->
<form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
    <div class="relative flex-1 max-w-md">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
        <input type="text" name="q" value="<?= e($q) ?>"
               placeholder="Buscar por nombre, serie o ubicación..."
               class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
    </div>
    <?php if ($restriccion_suc === null): ?>
    <select name="sucursal_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
        <option value="0">Todas las sucursales</option>
        <?php foreach ($sucursales as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <input type="hidden" name="sucursal_id" value="<?= (int) $restriccion_suc ?>">
    <?php endif; ?>
    <select name="tipo_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
        <option value="0">Todos los tipos</option>
        <?php foreach ($tipos as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $f_tipo === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm font-semibold text-zinc-600 hover:bg-zinc-50">Filtrar</button>
</form>

<!-- Tabla -->
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50">
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Medidor</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                    <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Última lectura</th>
                    <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tarifa</th>
                    <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <?php foreach ($medidores as $m): $color = $m['tipo_color'] ?: '#6B7280'; ?>
                <tr class="hover:bg-zinc-50 <?= !$m['activo'] ? 'opacity-50' : '' ?>">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded"
                              style="background-color: <?= e($color) ?>15; color: <?= e($color) ?>">
                            <i data-lucide="<?= e($m['tipo_icono'] ?: 'gauge') ?>" class="w-3.5 h-3.5"></i>
                            <?= e($m['tipo_nombre']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="<?= url('medidor_ver.php?id=' . $m['id']) ?>" class="font-semibold text-zinc-900 hover:text-bacal-700"><?= e($m['nombre']) ?></a>
                        <?php if ($m['ubicacion'] || $m['numero_serie']): ?>
                        <div class="text-[11px] text-zinc-400">
                            <?= e($m['ubicacion'] ?? '') ?><?php if ($m['ubicacion'] && $m['numero_serie']): ?> · <?php endif; ?>
                            <?php if ($m['numero_serie']): ?>S/N <?= e($m['numero_serie']) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-zinc-600">
                        <?= e($m['sucursal_codigo']) ?>
                        <?php if ($m['area_nombre']): ?><span class="text-zinc-400 text-xs">· <?= e($m['area_nombre']) ?></span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <?php if ($m['ultima_valor'] !== null): ?>
                        <div class="font-mono font-semibold text-zinc-900"><?= e(fmt_lectura((float) $m['ultima_valor'])) ?> <span class="text-[10px] text-zinc-400"><?= e($m['unidad']) ?></span></div>
                        <div class="text-[10px] text-zinc-400"><?= e(fmt_fecha($m['ultima_fecha'])) ?></div>
                        <?php else: ?>
                        <span class="text-xs text-zinc-400">Sin lecturas</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right text-zinc-600">
                        <?php if ($m['tarifa'] !== null): ?>
                            $<?= e(number_format((float) $m['tarifa'], 4)) ?>
                        <?php else: ?><span class="text-zinc-300">—</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="<?= url('lectura_nueva.php?medidor_id=' . $m['id']) ?>"
                               class="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-semibold text-bacal-700 hover:bg-bacal-50" title="Capturar lectura">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Lectura
                            </a>
                            <a href="<?= url('medidor_ver.php?id=' . $m['id']) ?>"
                               class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100" title="Ver histórico">
                                <i data-lucide="line-chart" class="w-4 h-4"></i>
                            </a>
                            <?php if ($puede_crear_editar): ?>
                            <a href="<?= url('medidores.php?accion=editar&id=' . $m['id']) ?>"
                               class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100" title="Editar">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puede_desactivar): ?>
                            <form method="POST" action="<?= url('medidores.php?accion=toggle&id=' . $m['id']) ?>"
                                  onsubmit="return confirm('¿<?= $m['activo'] ? 'Desactivar' : 'Activar' ?> este medidor?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="toggle">
                                <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100" title="<?= $m['activo'] ? 'Desactivar' : 'Activar' ?>">
                                    <i data-lucide="<?= $m['activo'] ? 'power' : 'power-off' ?>" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($medidores)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center">
                        <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                            <i data-lucide="gauge" class="w-8 h-8 text-zinc-400"></i>
                        </div>
                        <p class="text-sm font-medium text-zinc-700"><?= ($q !== '' || $f_sucursal || $f_tipo) ? 'Sin resultados con esos filtros' : 'Sin medidores registrados' ?></p>
                        <?php if ($puede_crear_editar && $q === '' && !$f_sucursal && !$f_tipo): ?>
                        <a href="<?= url('medidores.php?accion=nuevo') ?>"
                           class="mt-4 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                            <i data-lucide="plus" class="w-4 h-4"></i> Agregar el primero
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
