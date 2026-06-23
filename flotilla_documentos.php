<?php
/**
 * ============================================================================
 * flotilla_documentos.php - Documentos vehiculares y de conductores
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

// Actualizar estados de documentos
flotilla_actualizar_estado_documentos();

$f_estado     = (string) input('estado', '');
$f_tipo       = (int) input('tipo_id', 0);
$f_vehiculo   = (int) input('vehiculo_id', 0);
$f_conductor  = (int) input('conductor_id', 0);
$f_sucursal   = (int) input('sucursal_id', 0);
$f_desde      = trim((string) input('desde', ''));
$f_hasta      = trim((string) input('hasta', ''));

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$errores = [];

// ----------------------------------------------------------------------------
// POST
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'crear' || $op === 'editar') {
            $vehiculo_id  = (int) input('vehiculo_id', 0) ?: null;
            $conductor_id = (int) input('conductor_id', 0) ?: null;
            $tipo_id      = (int) input('tipo_id', 0);
            $num_doc      = trim((string) input('numero_documento', '')) ?: null;
            $proveedor    = trim((string) input('proveedor', '')) ?: null;
            $fecha_inicio = trim((string) input('fecha_inicio', '')) ?: null;
            $fecha_vence  = trim((string) input('fecha_vence', '')) ?: null;
            $monto        = (float) input('monto', 0) ?: null;
            $notas        = trim((string) input('notas', '')) ?: null;

            if (!$tipo_id)              $errores[] = 'El tipo de documento es obligatorio.';
            if (!$vehiculo_id && !$conductor_id) $errores[] = 'Asocia el documento a un vehículo o conductor.';

            if (empty($errores)) {
                // Determinar estado inicial basado en fecha de vencimiento
                $estado = 'vigente';
                if ($fecha_vence) {
                    $tipo_doc = db_one("SELECT dias_alerta FROM flotilla_tipos_documento WHERE id = :id", ['id' => $tipo_id]);
                    $dias_alerta = $tipo_doc ? (int)$tipo_doc['dias_alerta'] : 30;
                    $hoy   = new DateTime();
                    $vence = new DateTime($fecha_vence);
                    $diff  = $hoy->diff($vence)->days;
                    $futuro = $vence >= $hoy;
                    if (!$futuro)             $estado = 'vencido';
                    elseif ($diff <= $dias_alerta) $estado = 'por_vencer';
                }

                $datos = [
                    'vehiculo_id'      => $vehiculo_id,
                    'conductor_id'     => $conductor_id,
                    'tipo_id'          => $tipo_id,
                    'numero_documento' => $num_doc,
                    'proveedor'        => $proveedor,
                    'fecha_inicio'     => $fecha_inicio,
                    'fecha_vence'      => $fecha_vence,
                    'monto'            => $monto,
                    'estado'           => $estado,
                    'notas'            => $notas,
                ];

                try {
                    if ($op === 'crear') {
                        $datos['creado_por'] = $u['id'];
                        $cols   = implode(', ', array_keys($datos));
                        $pmarks = ':' . implode(', :', array_keys($datos));
                        db_exec("INSERT INTO flotilla_documentos ($cols) VALUES ($pmarks)", $datos);
                        $doc_id = db_last_id();
                        registrar_auditoria('crear_documento', 'flotilla_documentos', $doc_id, "Tipo ID: {$tipo_id}");
                        flash_set('exito', 'Documento registrado.');
                    } else {
                        $edit_id = (int) input('edit_id', 0);
                        $sets    = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($datos)));
                        $datos['id'] = $edit_id;
                        db_exec("UPDATE flotilla_documentos SET $sets WHERE id = :id", $datos);
                        registrar_auditoria('editar_documento', 'flotilla_documentos', $edit_id, "Tipo ID: {$tipo_id}");
                        flash_set('exito', 'Documento actualizado.');
                    }
                    header('Location: ' . url('flotilla_documentos.php'));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'eliminar' && tiene_permiso('administrar')) {
            $del_id = (int) input('del_id', 0);
            db_exec("DELETE FROM flotilla_documentos WHERE id = :id", ['id' => $del_id]);
            flash_set('exito', 'Documento eliminado.');
            header('Location: ' . url('flotilla_documentos.php'));
            exit;
        }
    }
}

// ----------------------------------------------------------------------------
// Cargar datos
// ----------------------------------------------------------------------------
$tipos_doc   = db_all("SELECT * FROM flotilla_tipos_documento WHERE activo=1 ORDER BY nombre");
$vehiculos   = db_all(
    "SELECT v.id, v.alias, v.marca, v.modelo, v.placas FROM flotilla_vehiculos v WHERE v.activo=1"
    . ($f_sucursal ? " AND v.sucursal_id = {$f_sucursal}" : '')
    . " ORDER BY v.alias, v.placas"
);
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

// KPIs
$kpi_vencidos   = (int)(db_one("SELECT COUNT(*) n FROM flotilla_documentos WHERE estado='vencido'")['n'] ?? 0);
$kpi_por_vencer = (int)(db_one("SELECT COUNT(*) n FROM flotilla_documentos WHERE estado='por_vencer'")['n'] ?? 0);
$kpi_vigentes   = (int)(db_one("SELECT COUNT(*) n FROM flotilla_documentos WHERE estado='vigente'")['n'] ?? 0);
$kpi_total      = $kpi_vencidos + $kpi_por_vencer + $kpi_vigentes;

// Filtrar documentos
$where  = ['1=1'];
$params = [];
if ($f_estado) {
    $where[]          = 'd.estado = :est';
    $params['est']    = $f_estado;
}
if ($f_tipo) {
    $where[]          = 'd.tipo_id = :tid';
    $params['tid']    = $f_tipo;
}
if ($f_vehiculo) {
    $where[]          = 'd.vehiculo_id = :vid';
    $params['vid']    = $f_vehiculo;
}
if ($f_conductor) {
    $where[]          = 'd.conductor_id = :cid';
    $params['cid']    = $f_conductor;
}
if ($f_sucursal) {
    $where[]          = 'v.sucursal_id = :sid';
    $params['sid']    = $f_sucursal;
}
if ($f_desde) {
    $where[]          = 'DATE(d.fecha_vence) >= :desde';
    $params['desde']  = $f_desde;
}
if ($f_hasta) {
    $where[]          = 'DATE(d.fecha_vence) <= :hasta';
    $params['hasta']  = $f_hasta;
}
$sql_where = implode(' AND ', $where);

$documentos = db_all(
    "SELECT d.*,
            t.nombre tipo_nombre, t.dias_alerta,
            v.alias v_alias, v.marca v_marca, v.modelo v_modelo, v.placas v_placas,
            c.nombre_completo c_nombre
       FROM flotilla_documentos d
       INNER JOIN flotilla_tipos_documento t ON d.tipo_id = t.id
       LEFT  JOIN flotilla_vehiculos v  ON d.vehiculo_id = v.id
       LEFT  JOIN flotilla_conductores c ON d.conductor_id = c.id
      WHERE $sql_where
      ORDER BY
          CASE d.estado WHEN 'vencido' THEN 0 WHEN 'por_vencer' THEN 1 WHEN 'vigente' THEN 2 ELSE 3 END,
          d.fecha_vence ASC
      LIMIT 300",
    $params
);

// Documento a editar
$doc_edit = null;
$edit_id  = (int) input('editar', 0);
if ($edit_id) {
    $doc_edit = db_one("SELECT * FROM flotilla_documentos WHERE id = :id", ['id' => $edit_id]);
}

$titulo_pagina = 'Flotilla · Documentos';
$pagina_activa = 'flotilla_documentos';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';

// Helper badge estado doc
function badge_doc(string $estado): string {
    return match($estado) {
        'vigente'    => '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold"><i data-lucide="check-circle" class="w-3 h-3"></i> Vigente</span>',
        'por_vencer' => '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold"><i data-lucide="clock" class="w-3 h-3"></i> Por vencer</span>',
        'vencido'    => '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold"><i data-lucide="x-circle" class="w-3 h-3"></i> Vencido</span>',
        'cancelado'  => '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-zinc-100 text-zinc-600 text-xs font-bold"><i data-lucide="minus-circle" class="w-3 h-3"></i> Cancelado</span>',
        default      => '<span class="text-zinc-400 text-xs">—</span>',
    };
}
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="file-check" class="w-6 h-6 text-bacal-700"></i>
            Documentos
        </h2>
        <?php if ($puede_gestionar): ?>
        <button onclick="abrirModalDoc()"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo documento
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash / Errores -->
    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium
        <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($errores): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $err): ?><div>✗ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $kpis_docs = [
            ['Total',       $kpi_total,      'file-text',    'zinc',    false],
            ['Vigentes',    $kpi_vigentes,   'check-circle', 'emerald', false],
            ['Por vencer',  $kpi_por_vencer, 'clock',        'amber',   $kpi_por_vencer > 0],
            ['Vencidos',    $kpi_vencidos,   'x-circle',     'red',     $kpi_vencidos > 0],
        ];
        foreach ($kpis_docs as [$label, $val, $icon, $color, $alert]):
        ?>
        <a href="<?= url('flotilla_documentos.php' . ($label !== 'Total' ? '?estado=' . strtolower(str_replace(' ', '_', $label === 'Por vencer' ? 'por_vencer' : $label)) : '')) ?>"
           class="bg-white rounded-xl border <?= $alert ? "border-{$color}-200 bg-{$color}-50" : 'border-zinc-200' ?> p-4 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
                <span class="font-display text-xl font-extrabold <?= $alert ? "text-{$color}-700" : 'text-zinc-900' ?>"><?= $val ?></span>
            </div>
            <div class="text-[11px] uppercase tracking-wide font-bold text-zinc-500"><?= $label ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-xl border border-zinc-200 p-3 flex flex-wrap gap-2 items-end">
        <select name="estado" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los estados</option>
            <?php foreach (['vigente'=>'Vigente','por_vencer'=>'Por vencer','vencido'=>'Vencido','cancelado'=>'Cancelado'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $f_estado === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tipo_id" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos_doc as $td): ?>
            <option value="<?= $td['id'] ?>" <?= $f_tipo === (int)$td['id'] ? 'selected' : '' ?>><?= e($td['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="vehiculo_id" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white flex-1 min-w-[160px]">
            <option value="">Todos los vehículos</option>
            <?php foreach ($vehiculos as $vv): ?>
            <option value="<?= $vv['id'] ?>" <?= $f_vehiculo === (int)$vv['id'] ? 'selected' : '' ?>>
                <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <div>
            <input type="date" name="desde" value="<?= e($f_desde) ?>" title="Vence desde"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        </div>
        <div>
            <input type="date" name="hasta" value="<?= e($f_hasta) ?>" title="Vence hasta"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">
            Filtrar
        </button>
        <?php if ($f_estado || $f_tipo || $f_vehiculo || $f_conductor): ?>
        <a href="<?= url('flotilla_documentos.php') ?>" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">
            Limpiar
        </a>
        <?php endif; ?>
    </form>

    <!-- Tabla de documentos -->
    <?php if (empty($documentos)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="file-check" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin documentos registrados</p>
        <p class="text-sm text-zinc-500 mt-1">Registra seguros, tarjetas de circulación, verificaciones y más.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Tipo</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Asociado a</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell">No. documento</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Proveedor</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="fecha">Vencimiento</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Estado</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell" data-orden-tipo="num">Monto</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($documentos as $d):
                        $dias_vence = null;
                        if ($d['fecha_vence']) {
                            $hoy   = new DateTime();
                            $vence = new DateTime($d['fecha_vence']);
                            $diff  = $hoy->diff($vence);
                            $dias_vence = $vence >= $hoy ? $diff->days : -$diff->days;
                        }
                    ?>
                    <tr class="hover:bg-zinc-50 transition-colors <?= $d['estado'] === 'vencido' ? 'bg-red-50/30' : ($d['estado'] === 'por_vencer' ? 'bg-amber-50/30' : '') ?>">
                        <td class="px-4 py-3 font-semibold text-zinc-900"><?= e($d['tipo_nombre']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($d['v_placas']): ?>
                            <div class="flex items-center gap-1 text-zinc-700">
                                <i data-lucide="car" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                                <span class="font-mono font-bold text-xs"><?= e($d['v_placas']) ?></span>
                                <span class="text-zinc-500 text-xs"><?= $d['v_alias'] ? '· ' . e($d['v_alias']) : '' ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($d['c_nombre']): ?>
                            <div class="flex items-center gap-1 text-zinc-700 <?= $d['v_placas'] ? 'mt-0.5' : '' ?>">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-zinc-400 shrink-0"></i>
                                <span class="text-xs"><?= e($d['c_nombre']) ?></span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell font-mono text-xs text-zinc-600">
                            <?= $d['numero_documento'] ? e($d['numero_documento']) : '—' ?>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-600">
                            <?= $d['proveedor'] ? e($d['proveedor']) : '—' ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($d['fecha_vence']): ?>
                            <div class="font-medium <?= $d['estado'] === 'vencido' ? 'text-red-700' : ($d['estado'] === 'por_vencer' ? 'text-amber-700' : 'text-zinc-700') ?>">
                                <?= fmt_fecha($d['fecha_vence']) ?>
                            </div>
                            <?php if ($dias_vence !== null): ?>
                            <div class="text-[11px] <?= $dias_vence < 0 ? 'text-red-500' : 'text-zinc-400' ?>">
                                <?= $dias_vence < 0 ? 'Venció hace ' . abs($dias_vence) . ' días' : "En {$dias_vence} días" ?>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= badge_doc($d['estado']) ?></td>
                        <td class="px-4 py-3 hidden md:table-cell text-right font-mono text-zinc-700">
                            <?= $d['monto'] ? '$' . number_format((float)$d['monto'], 2) : '—' ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if ($puede_gestionar): ?>
                                <a href="<?= url('flotilla_documentos.php?editar=' . $d['id']) ?>"
                                   class="p-1.5 rounded hover:bg-zinc-100 text-zinc-400 hover:text-zinc-700">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este documento?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="op" value="eliminar">
                                    <input type="hidden" name="del_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-zinc-400 hover:text-red-600">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- Modal: Nuevo / Editar documento                              -->
<!-- ============================================================ -->
<div id="modal-doc" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 id="modal-doc-title" class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="file-plus" class="w-4 h-4 text-bacal-700"></i>
                Nuevo documento
            </h3>
            <button type="button" onclick="document.getElementById('modal-doc').classList.add('hidden')"
                    class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" id="form-doc" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" id="doc_op" value="crear">
            <input type="hidden" name="edit_id" id="doc_edit_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo de documento <span class="text-red-500">*</span></label>
                    <select name="tipo_id" id="doc_tipo" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($tipos_doc as $td): ?>
                        <option value="<?= $td['id'] ?>"
                                data-vehiculo="<?= $td['aplica_vehiculo'] ?>"
                                data-conductor="<?= $td['aplica_conductor'] ?>">
                            <?= e($td['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo</label>
                    <select name="vehiculo_id" id="doc_vehiculo"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin asociar</option>
                        <?php foreach ($vehiculos as $vv): ?>
                        <option value="<?= $vv['id'] ?>">
                            <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id" id="doc_conductor"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Sin asociar</option>
                        <?php foreach ($conductores as $cd): ?>
                        <option value="<?= $cd['id'] ?>"><?= e($cd['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">No. de documento / póliza</label>
                    <input type="text" name="numero_documento" id="doc_numero" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Proveedor / Aseguradora</label>
                    <input type="text" name="proveedor" id="doc_proveedor" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de inicio</label>
                    <input type="date" name="fecha_inicio" id="doc_inicio"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de vencimiento</label>
                    <input type="date" name="fecha_vence" id="doc_vence"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Monto / Costo</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="monto" id="doc_monto" min="0" step="0.01"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500"
                               placeholder="0.00">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                    <textarea name="notas" id="doc_notas" rows="2" maxlength="500"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"
                              placeholder="Observaciones opcionales…"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-doc').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalDoc(data) {
    var modal = document.getElementById('modal-doc');
    var title = document.getElementById('modal-doc-title');
    if (data) {
        // Editar
        title.innerHTML = '<i data-lucide="file-edit" class="w-4 h-4 text-bacal-700 inline"></i> Editar documento';
        document.getElementById('doc_op').value       = 'editar';
        document.getElementById('doc_edit_id').value  = data.id;
        document.getElementById('doc_tipo').value     = data.tipo_id;
        document.getElementById('doc_vehiculo').value = data.vehiculo_id || '';
        document.getElementById('doc_conductor').value= data.conductor_id || '';
        document.getElementById('doc_numero').value   = data.numero_documento || '';
        document.getElementById('doc_proveedor').value= data.proveedor || '';
        document.getElementById('doc_inicio').value   = data.fecha_inicio || '';
        document.getElementById('doc_vence').value    = data.fecha_vence || '';
        document.getElementById('doc_monto').value    = data.monto || '';
        document.getElementById('doc_notas').value    = data.notas || '';
        if (window.lucide) lucide.createIcons();
    } else {
        // Nuevo
        title.innerHTML = '<i data-lucide="file-plus" class="w-4 h-4 text-bacal-700 inline"></i> Nuevo documento';
        document.getElementById('doc_op').value       = 'crear';
        document.getElementById('doc_edit_id').value  = '';
        document.getElementById('form-doc').reset();
        if (window.lucide) lucide.createIcons();
    }
    modal.classList.remove('hidden');
}

<?php if ($doc_edit): ?>
// Auto-abrir modal si viene ?editar=
window.addEventListener('DOMContentLoaded', function() {
    abrirModalDoc(<?= json_encode([
        'id'              => $doc_edit['id'],
        'tipo_id'         => $doc_edit['tipo_id'],
        'vehiculo_id'     => $doc_edit['vehiculo_id'],
        'conductor_id'    => $doc_edit['conductor_id'],
        'numero_documento'=> $doc_edit['numero_documento'],
        'proveedor'       => $doc_edit['proveedor'],
        'fecha_inicio'    => $doc_edit['fecha_inicio'],
        'fecha_vence'     => $doc_edit['fecha_vence'],
        'monto'           => $doc_edit['monto'],
        'notas'           => $doc_edit['notas'],
    ]) ?>);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
