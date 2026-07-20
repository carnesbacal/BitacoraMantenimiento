<?php
/**
 * ============================================================================
 * refacciones_requisiciones.php - Requisiciones de compra de mantenimiento
 * ============================================================================
 * Listado y alta de requisiciones (formato 0069-FRM Rev. B / ECO. 013).
 * Disponible para todas las sucursales; quien no la ocupe simplemente no la usa.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';
require_once __DIR__ . '/config/requisiciones_helpers.php';

requerir_login();
$u = usuario_actual();

$es_admin        = tiene_permiso('administrar');
$puede_gestionar = $es_admin || tiene_permiso('resolver');
$ver_todas       = tiene_permiso('ver_todas_sucursales');

$errores = [];
$tabla_ok = requisiciones_disponible();

// ----------------------------------------------------------------------------
// Alta de requisición
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar && $tabla_ok) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif ((string) input('op') === 'crear') {
        try {
            $sid = $ver_todas ? (int) input('sucursal_id', 0) : (int) $u['sucursal_id'];
            $req_id = requisicion_crear(
                $sid,
                trim((string) input('fecha', '')) ?: date('Y-m-d'),
                (int) $u['id'],
                trim((string) input('notas', '')) ?: null,
                (int) $u['id'],
                (string) input('razon_social', 'corral')
            );
            // Atajo: llenar de una vez con lo que está bajo mínimo
            if (input('prellenar')) {
                requisicion_prellenar_bajo_minimo($req_id, $sid);
            }
            registrar_auditoria('crear_requisicion', 'refacciones_requisiciones', $req_id, 'Requisición creada');
            flash_set('exito', 'Requisición creada. Agrega los renglones que necesites.');
            header('Location: ' . url('requisicion_ver.php?id=' . $req_id));
            exit;
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------------------------------
// Filtros y datos
// ----------------------------------------------------------------------------
$f_sucursal = $ver_todas ? (int) input('sucursal_id', 0) : (int) $u['sucursal_id'];
$f_estado   = trim((string) input('estado', ''));
$f_q        = trim((string) input('q', ''));
$f_desde    = trim((string) input('desde', ''));
$f_hasta    = trim((string) input('hasta', ''));

$requisiciones = $tabla_ok ? requisiciones_listar([
    'sucursal_id' => $f_sucursal ?: null,
    'estado'      => $f_estado ?: null,
    'q'           => $f_q ?: null,
    'desde'       => $f_desde ?: null,
    'hasta'       => $f_hasta ?: null,
]) : [];

$sucursales = $ver_todas
    ? db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre")
    : db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 AND id = :sid", ['sid' => $u['sucursal_id']]);

$estados = requisicion_estados();
$empresas = requisicion_empresas();

$titulo_pagina = 'Requisiciones de compra';
$pagina_activa = 'refacciones';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Encabezado -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="<?= url('refacciones.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-6 h-6 text-bacal-700"></i>
                    Requisiciones de compra
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">Solicitudes de compra de mantenimiento · formato 0069-FRM</p>
            </div>
        </div>
        <?php if ($puede_gestionar && $tabla_ok): ?>
        <button onclick="document.getElementById('modal-nueva-req').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5 self-start">
            <i data-lucide="plus" class="w-4 h-4"></i> Nueva requisición
        </button>
        <?php endif; ?>
    </div>

    <?php if (!$tabla_ok): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 flex items-start gap-2">
        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
        <span>Falta crear las tablas. Corre <strong>migracion_requisiciones.sql</strong> en phpMyAdmin para activar este módulo.</span>
    </div>
    <?php endif; ?>

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

    <!-- Filtros -->
    <?php if ($tabla_ok): ?>
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3 flex flex-wrap gap-2 items-end">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Buscar</label>
            <input type="text" name="q" value="<?= e($f_q) ?>" placeholder="Folio, solicitante, notas…"
                   class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
        </div>
        <?php if ($ver_todas): ?>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Sucursal</label>
            <select name="sucursal_id" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="0">Todas</option>
                <?php foreach ($sucursales as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Estado</label>
            <select name="estado" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                <option value="">Todos</option>
                <?php foreach ($estados as $k => $cfg): ?>
                <option value="<?= $k ?>" <?= $f_estado === $k ? 'selected' : '' ?>><?= e($cfg['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Desde</label>
            <input type="date" name="desde" value="<?= e($f_desde) ?>"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-zinc-500 uppercase mb-1">Hasta</label>
            <input type="date" name="hasta" value="<?= e($f_hasta) ?>"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Filtrar</button>
        <?php if ($f_q || $f_estado || $f_desde || $f_hasta): ?>
        <a href="<?= url('refacciones_requisiciones.php') ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Listado -->
    <?php if (empty($requisiciones)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin requisiciones</p>
        <p class="text-sm text-zinc-500 mt-1">Crea la primera para empezar a solicitar compras.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Folio</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="fecha">Fecha</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Solicitó</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Renglones</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-2.5" data-no-orden></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($requisiciones as $r):
                        $cfg = $estados[$r['estado']] ?? ['label' => $r['estado'], 'color' => 'zinc'];
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5">
                            <a href="<?= url('requisicion_ver.php?id=' . $r['id']) ?>"
                               class="font-mono text-xs font-bold text-bacal-700 hover:underline"><?= e($r['folio']) ?></a>
                        </td>
                        <td class="px-4 py-2.5 text-zinc-700" data-orden="<?= e($r['fecha']) ?>"><?= e(fmt_fecha($r['fecha'], false)) ?></td>
                        <td class="px-4 py-2.5">
                            <span class="font-mono text-[10px] bg-zinc-100 text-zinc-600 px-1.5 py-0.5 rounded font-bold"><?= e($r['sucursal_codigo']) ?></span>
                            <span class="text-xs text-zinc-700 ml-1"><?= e($r['sucursal_nombre']) ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-700"><?= e($r['solicito_nombre']) ?></td>
                        <td class="px-4 py-2.5 text-center" data-orden="<?= (int) $r['num_items'] ?>">
                            <span class="text-sm font-semibold text-zinc-900"><?= (int) $r['num_items'] ?></span>
                            <?php if ((int) $r['num_pendientes'] > 0): ?>
                            <div class="text-[10px] text-amber-600 font-semibold"><?= (int) $r['num_pendientes'] ?> pend.</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-<?= $cfg['color'] ?>-100 text-<?= $cfg['color'] ?>-800"><?= e($cfg['label']) ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                            <a href="<?= url('requisicion_imprimir.php?id=' . $r['id']) ?>" target="_blank"
                               class="inline-flex p-1.5 rounded hover:bg-zinc-100 text-zinc-400 hover:text-bacal-700" title="Imprimir / PDF">
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            </a>
                            <a href="<?= url('requisicion_ver.php?id=' . $r['id']) ?>"
                               class="inline-flex p-1.5 rounded hover:bg-zinc-100 text-zinc-400 hover:text-bacal-700" title="Abrir">
                                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal: nueva requisición -->
<?php if ($puede_gestionar && $tabla_ok): ?>
<div id="modal-nueva-req" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
            <i data-lucide="clipboard-list" class="w-4 h-4 text-bacal-700"></i> Nueva requisición
        </h3>
        <form method="POST" class="space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="crear">
            <?php if ($ver_todas): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Sucursal <span class="text-red-500">*</span></label>
                <select name="sucursal_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sucursal === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Razón social del formato <span class="text-red-500">*</span></label>
                <select name="razon_social" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm">
                    <?php foreach ($empresas as $k => $emp): ?>
                    <option value="<?= e($k) ?>"><?= e($emp['corto']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-zinc-400 mt-0.5">Define el logo y el nombre legal que saldrán impresos.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Notas (opcional)</label>
                <textarea name="notas" rows="2" maxlength="500"
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm"
                          placeholder="Referencia, motivo, etc."></textarea>
            </div>
            <label class="flex items-start gap-2 bg-bacal-50 border border-bacal-200 rounded-lg p-3 cursor-pointer">
                <input type="checkbox" name="prellenar" value="1" class="mt-0.5 w-4 h-4 rounded border-zinc-300 text-bacal-700 focus:ring-bacal-500">
                <span class="text-xs text-zinc-700">
                    <strong class="text-bacal-700">Llenar con lo que está bajo mínimo</strong><br>
                    Agrega automáticamente las refacciones cuyo stock está en o por debajo del mínimo en esa sucursal, con la cantidad sugerida.
                </span>
            </label>
            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-nueva-req').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Crear</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
