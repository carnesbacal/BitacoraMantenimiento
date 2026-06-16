<?php
/**
 * ============================================================================
 * herramientas.php - Catálogo de herramientas
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

// Filtros
$f_busqueda = trim((string) input('q', ''));
$f_estado = (string) input('estado', '');
$f_tipo = (string) input('tipo', '');
$f_sucursal = (int) input('sucursal_id', 0);

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$errores = [];

// Procesar POST (crear herramienta)
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
                'tipo' => (string) input('tipo_her', '') ?: null,
                'marca' => trim((string) input('marca', '')) ?: null,
                'modelo' => trim((string) input('modelo', '')) ?: null,
                'numero_serie' => trim((string) input('numero_serie', '')) ?: null,
                'sucursal_id' => (int) input('sucursal_her', $u['sucursal_id']),
                'ubicacion' => trim((string) input('ubicacion', '')) ?: null,
                'fecha_adquisicion' => trim((string) input('fecha_adquisicion', '')) ?: null,
                'costo' => (float) input('costo', 0) ?: null,
                'proveedor_id' => (int) input('proveedor_id', 0) ?: null,
                'notas' => trim((string) input('notas', '')) ?: null,
            ];

            if ($datos['codigo'] === '') $errores[] = 'El código es obligatorio.';
            if ($datos['nombre'] === '') $errores[] = 'El nombre es obligatorio.';

            if (empty($errores)) {
                try {
                    $id = crear_herramienta($datos, (int) $u['id']);
                    registrar_auditoria('crear_herramienta', 'herramientas', $id, $datos['nombre']);
                    flash_set('success', "Herramienta '{$datos['nombre']}' creada.");
                    header('Location: ' . url('herramienta_ver.php?id=' . $id));
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

$herramientas = listar_herramientas([
    'busqueda' => $f_busqueda ?: null,
    'estado' => $f_estado ?: null,
    'tipo' => $f_tipo ?: null,
    'sucursal_id' => $f_sucursal ?: null,
]);
$stats = stats_herramientas($f_sucursal ?: null);

$sucursales = tiene_permiso('ver_todas_sucursales')
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);
$proveedores = db_all("SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre");

$titulo_pagina = 'Herramientas';
$pagina_activa = 'herramientas';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="wrench" class="w-6 h-6 text-bacal-700"></i>
                Herramientas
            </h2>
            <p class="text-xs text-zinc-500 mt-0.5">Catálogo de herramientas con sistema de préstamos.</p>
        </div>

        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal_nueva').showModal()"
                class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Nueva herramienta
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
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold">Total</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= $stats['total'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold">Disponibles</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700"><?= $stats['disponibles'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-amber-700 uppercase tracking-wider font-bold">Prestadas</div>
            <div class="font-display text-2xl font-extrabold text-amber-700"><?= $stats['prestadas'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-orange-700 uppercase tracking-wider font-bold">Reparación</div>
            <div class="font-display text-2xl font-extrabold text-orange-700"><?= $stats['en_reparacion'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-3">
            <div class="text-[10px] text-bacal-700 uppercase tracking-wider font-bold">Extraviadas</div>
            <div class="font-display text-2xl font-extrabold text-bacal-700"><?= $stats['extraviadas'] ?></div>
        </div>
        <div class="bg-white rounded-xl border <?= $stats['vencidos'] > 0 ? 'border-bacal-300 bg-bacal-50' : 'border-zinc-200' ?> p-3">
            <div class="text-[10px] <?= $stats['vencidos'] > 0 ? 'text-bacal-700' : 'text-zinc-500' ?> uppercase tracking-wider font-bold">Vencidos</div>
            <div class="font-display text-2xl font-extrabold <?= $stats['vencidos'] > 0 ? 'text-bacal-700' : 'text-zinc-900' ?>"><?= $stats['vencidos'] ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
                <input type="text" name="q" value="<?= e($f_busqueda) ?>"
                       placeholder="Buscar por código, nombre, marca, serie..."
                       class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <select name="estado" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="">Todos los estados</option>
                <?php foreach (['disponible','prestada','en_reparacion','extraviada','baja'] as $est):
                    $lbl = etiqueta_estado_herramienta($est);
                ?>
                <option value="<?= e($est) ?>" <?= $f_estado === $est ? 'selected' : '' ?>><?= e($lbl['label']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="tipo" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="">Todos los tipos</option>
                <?php foreach (tipos_herramientas() as $t): ?>
                <option value="<?= e($t) ?>" <?= $f_tipo === $t ? 'selected' : '' ?>><?= e($t) ?></option>
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

            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Filtrar</button>
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <?php if (empty($herramientas)): ?>
        <div class="px-6 py-16 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                <i data-lucide="wrench" class="w-8 h-8 text-zinc-400"></i>
            </div>
            <p class="text-sm font-semibold text-zinc-700 mb-1">Sin herramientas registradas</p>
            <?php if ($puede_gestionar): ?>
            <p class="text-xs text-zinc-500 mb-4">Crea tu catálogo de herramientas para llevar control de préstamos.</p>
            <button onclick="document.getElementById('modal_nueva').showModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> Crear primera herramienta
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Código</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Herramienta</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Estado</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Prestada a</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($herramientas as $h):
                    $est_lbl = etiqueta_estado_herramienta($h['estado']);
                ?>
                <tr class="hover:bg-zinc-50 cursor-pointer" onclick="window.location.href='<?= url('herramienta_ver.php?id=' . $h['id']) ?>'">
                    <td class="px-3 py-2.5">
                        <span class="font-mono text-xs font-bold text-zinc-900"><?= e($h['codigo']) ?></span>
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="font-semibold text-zinc-900"><?= e($h['nombre']) ?></div>
                        <?php if (!empty($h['marca']) || !empty($h['modelo'])): ?>
                        <div class="text-[10px] text-zinc-500"><?= e(trim(($h['marca'] ?? '') . ' ' . ($h['modelo'] ?? ''))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-zinc-700">
                        <?= !empty($h['tipo']) ? e($h['tipo']) : '<span class="text-zinc-400">—</span>' ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-zinc-700">
                        <?= e($h['sucursal_codigo']) ?>
                        <?php if (!empty($h['ubicacion'])): ?>
                        <div class="text-[10px] text-zinc-500">📍 <?= e($h['ubicacion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5">
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase"
                              style="color: <?= e($est_lbl['color']) ?>; background-color: <?= e($est_lbl['color']) ?>15">
                            <i data-lucide="<?= e($est_lbl['icono']) ?>" class="w-3 h-3"></i>
                            <?= e($est_lbl['label']) ?>
                        </span>
                        <?php if (!empty($h['prestamo_vencido']) && $h['prestamo_vencido']): ?>
                        <div class="text-[10px] font-bold text-bacal-700 mt-0.5">⚠ Vencido</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2.5 text-xs text-zinc-700">
                        <?php if (!empty($h['prestada_a_nombre'])): ?>
                        <?= e($h['prestada_a_nombre']) ?>
                        <?php if (!empty($h['prestamo_fecha_dev'])): ?>
                        <div class="text-[10px] text-zinc-500">Devolver: <?= e(date('d/M/Y', strtotime($h['prestamo_fecha_dev']))) ?></div>
                        <?php endif; ?>
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
<!-- Modal Nueva Herramienta -->
<dialog id="modal_nueva" class="rounded-xl shadow-2xl backdrop:bg-black/50 w-full max-w-2xl p-0">
    <form method="POST" class="bg-white">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="crear">

        <div class="px-5 py-3 border-b border-zinc-200 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-bacal-700"></i>
                Nueva herramienta
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
                           placeholder="HER-001"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="200"
                           placeholder="ej. Multímetro digital, Llave inglesa 12in, Taladro percutor"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Descripción</label>
                <textarea name="descripcion" rows="2"
                          placeholder="Detalle adicional, especificaciones..."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Tipo</label>
                    <select name="tipo_her" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">—</option>
                        <?php foreach (tipos_herramientas() as $t): ?>
                        <option value="<?= e($t) ?>"><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">No. serie</label>
                    <input type="text" name="numero_serie" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal *</label>
                    <select name="sucursal_her" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) $u['sucursal_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Ubicación física</label>
                    <input type="text" name="ubicacion" maxlength="150"
                           placeholder="ej. Taller, Anaquel B-2"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha adquisición</label>
                    <input type="date" name="fecha_adquisicion"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Costo ($)</label>
                    <input type="number" name="costo" min="0" step="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Proveedor</label>
                    <select name="proveedor_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">— Ninguno —</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Notas</label>
                <textarea name="notas" rows="2"
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"></textarea>
            </div>
        </div>

        <div class="px-5 py-3 border-t border-zinc-200 flex justify-end gap-2 bg-zinc-50">
            <button type="button" onclick="document.getElementById('modal_nueva').close()"
                    class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</button>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                Crear herramienta
            </button>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
