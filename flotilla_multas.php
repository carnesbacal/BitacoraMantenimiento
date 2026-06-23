<?php
/**
 * ============================================================================
 * flotilla_multas.php - Multas e infracciones de tránsito de la flotilla
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$f_vehiculo = (int) input('vehiculo_id', 0);
$f_estado   = (string) input('estado', '');
$f_desde    = trim((string) input('desde', ''));
$f_hasta    = trim((string) input('hasta', ''));

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
            $datos = [
                'vehiculo_id'        => (int) input('vehiculo_id_m', 0),
                'conductor_id'       => (int) input('conductor_id', 0) ?: null,
                'fecha_infraccion'   => trim((string) input('fecha_infraccion', '')),
                'numero_infraccion'  => trim((string) input('numero_infraccion', '')) ?: null,
                'autoridad'          => trim((string) input('autoridad', '')) ?: null,
                'motivo'             => trim((string) input('motivo', '')),
                'monto_original'     => (float) input('monto_original', 0),
                'monto_con_descuento'=> (float) input('monto_con_descuento', 0) ?: null,
                'fecha_vence_pago'   => trim((string) input('fecha_vence_pago', '')) ?: null,
                'fecha_pago'         => trim((string) input('fecha_pago', '')) ?: null,
                'monto_pagado'       => (float) input('monto_pagado', 0) ?: null,
                'responsable'        => (string) input('responsable', 'en_disputa'),
                'estado'             => (string) input('estado_m', 'pendiente'),
                'notas'              => trim((string) input('notas', '')) ?: null,
                'creado_por'         => $u['id'],
            ];

            if (!$datos['vehiculo_id'])    $errores[] = 'Selecciona el vehículo.';
            if ($datos['motivo'] === '')    $errores[] = 'El motivo/infracción es obligatorio.';
            if ($datos['fecha_infraccion'] === '') $errores[] = 'La fecha es obligatoria.';
            if ($datos['monto_original'] <= 0)     $errores[] = 'El monto original debe ser mayor a 0.';

            if (empty($errores)) {
                try {
                    if ($op === 'crear') {
                        $cols   = implode(',', array_keys($datos));
                        $params = ':' . implode(',:', array_keys($datos));
                        db_exec("INSERT INTO flotilla_multas ($cols) VALUES ($params)", $datos);
                        $nuevo_id = db_last_id();

                        // Gasto automático si ya está pagada
                        if ($datos['estado'] === 'pagada' && ($datos['monto_pagado'] ?? 0) > 0) {
                            $cat = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre LIKE '%Multa%' LIMIT 1");
                            if ($cat) {
                                db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,fecha,concepto,monto,creado_por)
                                         VALUES (:vid,:cat,:fecha,:con,:monto,:cp)",
                                    ['vid'=>$datos['vehiculo_id'],'cat'=>$cat['id'],
                                     'fecha'=>$datos['fecha_pago'] ?? $datos['fecha_infraccion'],
                                     'con'=>'Multa · ' . ($datos['numero_infraccion'] ?? $datos['motivo']),
                                     'monto'=>$datos['monto_pagado'],'cp'=>$u['id']]);
                            }
                        }
                        registrar_auditoria('crear_multa','flotilla_multas',$nuevo_id,'Multa registrada');
                        flash_set('exito', 'Multa registrada.');
                    } else {
                        $edit_id = (int) input('edit_id', 0);
                        unset($datos['creado_por']);
                        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($datos)));
                        $datos['id'] = $edit_id;
                        db_exec("UPDATE flotilla_multas SET $sets WHERE id = :id", $datos);
                        flash_set('exito', 'Multa actualizada.');
                    }
                    $redir = $f_vehiculo ? "flotilla_vehiculo_ver.php?id=$f_vehiculo&tab=multas" : 'flotilla_multas.php';
                    header('Location: ' . url($redir));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'pagar') {
            $mid           = (int) input('multa_id', 0);
            $fecha_pago    = trim((string) input('fecha_pago_r', date('Y-m-d')));
            $monto_pagado  = (float) input('monto_pagado_r', 0);

            db_exec("UPDATE flotilla_multas SET estado='pagada', fecha_pago=:fp, monto_pagado=:mp WHERE id=:id",
                    ['fp'=>$fecha_pago,'mp'=>$monto_pagado,'id'=>$mid]);

            if ($monto_pagado > 0) {
                $multa = db_one("SELECT * FROM flotilla_multas WHERE id=:id", ['id'=>$mid]);
                $cat   = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre LIKE '%Multa%' LIMIT 1");
                if ($cat && $multa) {
                    db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,fecha,concepto,monto,creado_por)
                             VALUES (:vid,:cat,:fecha,:con,:monto,:cp)",
                        ['vid'=>$multa['vehiculo_id'],'cat'=>$cat['id'],'fecha'=>$fecha_pago,
                         'con'=>'Pago multa · ' . ($multa['numero_infraccion'] ?? $multa['motivo']),
                         'monto'=>$monto_pagado,'cp'=>$u['id']]);
                }
            }
            flash_set('exito', 'Multa marcada como pagada.');
            header('Location: ' . url('flotilla_multas.php' . ($f_vehiculo ? "?vehiculo_id=$f_vehiculo" : '')));
            exit;
        }
    }
}

// Datos
$sid_forzado = flotilla_sucursal_forzada();
$where  = ['1=1'];
$params = [];
if ($f_vehiculo) { $where[] = 'm.vehiculo_id = :vid'; $params['vid'] = $f_vehiculo; }
if ($f_estado)   { $where[] = 'm.estado = :est';      $params['est'] = $f_estado; }
if ($f_desde)    { $where[] = 'DATE(m.fecha_infraccion) >= :desde'; $params['desde'] = $f_desde; }
if ($f_hasta)    { $where[] = 'DATE(m.fecha_infraccion) <= :hasta'; $params['hasta'] = $f_hasta; }
if ($sid_forzado){ $where[] = 'v.sucursal_id = :sid_f'; $params['sid_f'] = $sid_forzado; }
$sql_where = implode(' AND ', $where);

$multas = db_all(
    "SELECT m.*,
            v.placas, v.alias, v.marca, v.modelo,
            c.nombre_completo conductor_nombre,
            DATEDIFF(m.fecha_vence_pago, CURDATE()) dias_limite
     FROM flotilla_multas m
     INNER JOIN flotilla_vehiculos v  ON m.vehiculo_id  = v.id
     LEFT  JOIN flotilla_conductores c ON m.conductor_id = c.id
     WHERE $sql_where
     ORDER BY
         CASE m.estado WHEN 'pendiente' THEN 0 WHEN 'impugnada' THEN 1 ELSE 2 END,
         m.fecha_vence_pago ASC,
         m.fecha_infraccion DESC",
    $params
);

$v_where = $sid_forzado ? "activo=1 AND sucursal_id=$sid_forzado" : "activo=1";
$vehiculos   = db_all("SELECT id, placas, alias, marca, modelo FROM flotilla_vehiculos WHERE $v_where ORDER BY alias, placas");
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

// KPIs
$kpi_sid = $sid_forzado ? " AND v.sucursal_id = $sid_forzado" : "";
$kpis = db_one(
    "SELECT
        SUM(m.estado IN('pendiente','impugnada'))  pendientes,
        SUM(m.estado IN('pendiente','impugnada') AND m.fecha_vence_pago IS NOT NULL AND m.fecha_vence_pago <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)) urgentes,
        COALESCE(SUM(CASE WHEN m.estado='pagada' THEN m.monto_pagado END),0)                              total_pagado,
        COALESCE(SUM(CASE WHEN m.estado IN('pendiente','impugnada') THEN m.monto_original END),0)          total_pendiente
     FROM flotilla_multas m
     INNER JOIN flotilla_vehiculos v ON m.vehiculo_id = v.id
     WHERE v.activo = 1$kpi_sid" . ($f_vehiculo ? " AND m.vehiculo_id = $f_vehiculo" : "")
);

$titulo_pagina = 'Flotilla · Multas';
$pagina_activa = 'flotilla_multas';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';

$estados_multa = [
    'pendiente'  => ['bg-amber-100',  'text-amber-800',  'Pendiente'],
    'pagada'     => ['bg-emerald-100','text-emerald-800','Pagada'],
    'impugnada'  => ['bg-blue-100',   'text-blue-800',   'Impugnada'],
    'cancelada'  => ['bg-zinc-100',   'text-zinc-600',   'Cancelada'],
];
$responsable_ops = ['empresa'=>'La empresa','conductor'=>'El conductor','en_disputa'=>'En disputa'];
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <a href="<?= url('flotilla_vehiculos.php') ?>" class="text-zinc-500 hover:text-bacal-700 text-sm flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Flotilla
            </a>
            <span class="text-zinc-300">/</span>
            <h2 class="font-display text-xl font-extrabold text-zinc-900 flex items-center gap-2">
                <i data-lucide="ticket-x" class="w-5 h-5 text-bacal-700"></i>
                Multas e infracciones
            </h2>
        </div>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal-multa').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Registrar multa
        </button>
        <?php endif; ?>
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

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Pendientes</div>
            <div class="text-2xl font-bold text-amber-600"><?= $kpis['pendientes'] ?? 0 ?></div>
        </div>
        <div class="bg-white rounded-xl border <?= ($kpis['urgentes'] ?? 0) > 0 ? 'border-red-200 bg-red-50' : 'border-zinc-200' ?> p-4">
            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Urgentes (≤7 días)</div>
            <div class="text-2xl font-bold text-red-600"><?= $kpis['urgentes'] ?? 0 ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Por pagar</div>
            <div class="text-2xl font-bold text-zinc-900">$<?= number_format($kpis['total_pendiente'] ?? 0, 2) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Total pagado</div>
            <div class="text-2xl font-bold text-emerald-700">$<?= number_format($kpis['total_pagado'] ?? 0, 2) ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="flex flex-wrap gap-2">
        <?php if ($f_vehiculo): ?><input type="hidden" name="vehiculo_id" value="<?= $f_vehiculo ?>"><?php endif; ?>
        <select name="estado" onchange="this.form.submit()"
                class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            <option value="">Todos los estados</option>
            <?php foreach ($estados_multa as $v => [$bg, $tx, $label]): ?>
            <option value="<?= $v ?>" <?= $f_estado === $v ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="desde" value="<?= e($f_desde) ?>" title="Desde"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        <input type="date" name="hasta" value="<?= e($f_hasta) ?>" title="Hasta"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
        <button type="submit" class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Filtrar</button>
        <?php if ($f_estado || $f_desde || $f_hasta): ?>
        <a href="<?= url('flotilla_multas.php' . ($f_vehiculo ? "?vehiculo_id=$f_vehiculo" : '')) ?>"
           class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Lista -->
    <?php if (empty($multas)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="check-circle-2" class="w-12 h-12 mx-auto text-emerald-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin multas registradas</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100 text-sm js-tabla-orden">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase">Vehículo</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase">Infracción</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase" data-orden-tipo="fecha">Fecha</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase">Folio</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-zinc-500 uppercase" data-orden-tipo="num">Monto</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase">Estado</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase" data-orden-tipo="fecha">Vence</th>
                    <?php if ($puede_gestionar): ?>
                    <th class="px-4 py-3"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php foreach ($multas as $m):
                [$bg, $tx, $label] = $estados_multa[$m['estado']] ?? ['bg-zinc-100','text-zinc-600',$m['estado']];
                $urgente = $m['estado'] === 'pendiente' && $m['dias_limite'] !== null && $m['dias_limite'] <= 7;
                $vencida = $m['estado'] === 'pendiente' && $m['dias_limite'] !== null && $m['dias_limite'] < 0;
            ?>
            <tr class="hover:bg-zinc-50 <?= $urgente ? 'bg-amber-50/40' : '' ?>">
                <td class="px-4 py-3">
                    <div class="font-semibold text-zinc-900"><?= e($m['alias'] ?: "{$m['marca']} {$m['modelo']}") ?></div>
                    <div class="text-xs font-mono text-zinc-400"><?= e($m['placas']) ?></div>
                    <?php if ($m['conductor_nombre']): ?>
                    <div class="text-xs text-zinc-500 mt-0.5"><?= e($m['conductor_nombre']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 max-w-xs">
                    <div class="text-zinc-800 line-clamp-2"><?= e($m['motivo']) ?></div>
                    <?php if ($m['autoridad']): ?>
                    <div class="text-xs text-zinc-500 mt-0.5"><?= e($m['autoridad']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-zinc-600"><?= fmt_fecha($m['fecha_infraccion']) ?></td>
                <td class="px-4 py-3">
                    <?php if ($m['numero_infraccion']): ?>
                    <span class="font-mono text-xs text-zinc-700"><?= e($m['numero_infraccion']) ?></span>
                    <?php else: ?><span class="text-zinc-400">—</span><?php endif; ?>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <div class="font-semibold text-zinc-900">$<?= number_format($m['monto_original'], 2) ?></div>
                    <?php if ($m['monto_con_descuento']): ?>
                    <div class="text-xs text-emerald-600">c/dto: $<?= number_format($m['monto_con_descuento'], 2) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $bg ?> <?= $tx ?>"><?= $label ?></span>
                    <div class="text-xs text-zinc-500 mt-0.5"><?= $responsable_ops[$m['responsable']] ?? $m['responsable'] ?></div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <?php if ($m['fecha_vence_pago']): ?>
                        <?php if ($vencida): ?>
                        <span class="text-xs font-semibold text-red-600"><i data-lucide="alert-triangle" class="w-3 h-3 inline"></i> Vencida</span>
                        <?php elseif ($urgente): ?>
                        <span class="text-xs font-semibold text-amber-700"><i data-lucide="clock" class="w-3 h-3 inline"></i> <?= $m['dias_limite'] ?>d</span>
                        <?php else: ?>
                        <span class="text-xs text-zinc-600"><?= fmt_fecha($m['fecha_vence_pago']) ?></span>
                        <?php endif; ?>
                    <?php else: ?><span class="text-zinc-400">—</span><?php endif; ?>
                </td>
                <?php if ($puede_gestionar): ?>
                <td class="px-4 py-3">
                    <div class="flex gap-1 justify-end">
                        <button onclick="abrirEditarMulta(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)"
                                class="p-1.5 rounded-lg border border-zinc-300 text-zinc-500 hover:bg-zinc-50" title="Editar">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                        <?php if (in_array($m['estado'], ['pendiente','impugnada'])): ?>
                        <button onclick="abrirPagar(<?= $m['id'] ?>, <?= $m['monto_con_descuento'] ?: $m['monto_original'] ?>)"
                                class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                            Pagar
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal registrar/editar multa -->
<div id="modal-multa" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalMulta()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 id="multa-titulo" class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="ticket-x" class="w-4 h-4 text-bacal-700"></i> Registrar multa
            </h3>
            <button onclick="cerrarModalMulta()" class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" id="multa-op" value="crear">
            <input type="hidden" name="edit_id" id="multa-edit-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                    <select name="vehiculo_id_m" id="multa-vehiculo" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $f_vehiculo === (int)$v['id'] ? 'selected' : '' ?>>
                            <?= $v['alias'] ? e($v['alias']) . ' · ' : '' ?><?= e($v['placas']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor infractor</label>
                    <select name="conductor_id" id="multa-conductor"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha infracción <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_infraccion" id="multa-fecha" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Número de infracción / folio</label>
                    <input type="text" name="numero_infraccion" id="multa-folio" maxlength="60"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Motivo / infracción <span class="text-red-500">*</span></label>
                    <textarea name="motivo" id="multa-motivo" required rows="2" maxlength="200"
                              placeholder="Ej: Exceso de velocidad en Blvd. Cuauhtémoc…"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Autoridad / agencia</label>
                    <input type="text" name="autoridad" id="multa-autoridad" maxlength="100"
                           placeholder="Ej: Tránsito Municipal"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Monto original ($) <span class="text-red-500">*</span></label>
                    <input type="number" name="monto_original" id="multa-monto" required step="0.01" min="0.01"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Monto con descuento ($)</label>
                    <input type="number" name="monto_con_descuento" id="multa-descuento" step="0.01" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha límite de pago</label>
                    <input type="date" name="fecha_vence_pago" id="multa-limite"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Responsable</label>
                    <select name="responsable" id="multa-responsable"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="en_disputa">En disputa</option>
                        <option value="empresa">La empresa</option>
                        <option value="conductor">El conductor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Estado</label>
                    <select name="estado_m" id="multa-estado"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="pendiente">Pendiente</option>
                        <option value="impugnada">Impugnada</option>
                        <option value="pagada">Pagada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de pago</label>
                    <input type="date" name="fecha_pago" id="multa-fecha-pago"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Monto pagado ($)</label>
                    <input type="number" name="monto_pagado" id="multa-monto-pagado" step="0.01" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                    <textarea name="notas" id="multa-notas" rows="2" maxlength="500"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" onclick="cerrarModalMulta()"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Cancelar</button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pagar -->
<div id="modal-pagar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-pagar').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i> Registrar pago
        </h3>
        <form method="POST" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="pagar">
            <input type="hidden" name="multa_id" id="pagar-id" value="">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Monto pagado ($)</label>
                <input type="number" name="monto_pagado_r" id="pagar-monto" step="0.01" min="0" required
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de pago</label>
                <input type="date" name="fecha_pago_r" value="<?= date('Y-m-d') ?>" required
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-pagar').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50">Cancelar</button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Confirmar pago</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirEditarMulta(m) {
    document.getElementById('multa-op').value          = 'editar';
    document.getElementById('multa-edit-id').value     = m.id;
    document.getElementById('multa-titulo').innerHTML  = '<i data-lucide="pencil" class="w-4 h-4 text-bacal-700"></i> Editar multa';
    document.getElementById('multa-vehiculo').value    = m.vehiculo_id          || '';
    document.getElementById('multa-conductor').value   = m.conductor_id         || '';
    document.getElementById('multa-fecha').value       = m.fecha_infraccion     || '';
    document.getElementById('multa-folio').value       = m.numero_infraccion    || '';
    document.getElementById('multa-motivo').value      = m.motivo               || '';
    document.getElementById('multa-autoridad').value   = m.autoridad            || '';
    document.getElementById('multa-monto').value       = m.monto_original       || '';
    document.getElementById('multa-descuento').value   = m.monto_con_descuento  || '';
    document.getElementById('multa-limite').value      = m.fecha_vence_pago     || '';
    document.getElementById('multa-responsable').value = m.responsable          || 'en_disputa';
    document.getElementById('multa-estado').value      = m.estado               || 'pendiente';
    document.getElementById('multa-fecha-pago').value  = m.fecha_pago           || '';
    document.getElementById('multa-monto-pagado').value= m.monto_pagado         || '';
    document.getElementById('multa-notas').value       = m.notas                || '';
    document.getElementById('modal-multa').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}
function cerrarModalMulta() {
    document.getElementById('multa-op').value    = 'crear';
    document.getElementById('multa-titulo').innerHTML = '<i data-lucide="ticket-x" class="w-4 h-4 text-bacal-700"></i> Registrar multa';
    document.getElementById('modal-multa').classList.add('hidden');
}
function abrirPagar(id, monto) {
    document.getElementById('pagar-id').value    = id;
    document.getElementById('pagar-monto').value = monto || '';
    document.getElementById('modal-pagar').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
