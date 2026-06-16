<?php
/**
 * ============================================================================
 * medidor_ver.php - Histórico y detalle de un medidor
 * ============================================================================
 * Muestra KPIs de consumo, una gráfica de tendencia (Chart.js) y la tabla
 * completa de lecturas, con detección de consumos anómalos.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/medidores_helpers.php';

requerir_login();

$puede_capturar = tiene_permiso('administrar') || tiene_permiso('resolver');
$puede_eliminar = tiene_permiso('administrar');

$id = (int) input('id', 0);
$medidor = $id ? obtener_medidor($id) : null;
if (!$medidor) { flash_set('error', 'Medidor no encontrado.'); header('Location: ' . url('medidores.php')); exit; }
if (!puede_ver_medidor($medidor)) { flash_set('error', 'Ese medidor pertenece a otra sucursal.'); header('Location: ' . url('medidores.php')); exit; }

// Eliminar una lectura (solo admin)
if (es_post() && (string) input('op', '') === 'eliminar_lectura') {
    if (!$puede_eliminar) { flash_set('error', 'No tienes permiso para eliminar lecturas.'); }
    elseif (!csrf_valido(input('_csrf'))) { flash_set('error', 'Token inválido.'); }
    else {
        $lid = (int) input('lectura_id', 0);
        $lec = obtener_lectura($lid);
        if ($lec && (int) $lec['medidor_id'] === $id) {
            if (!empty($lec['foto'])) { $fp = __DIR__ . '/' . $lec['foto']; if (is_file($fp)) @unlink($fp); }
            eliminar_lectura($lid);
            registrar_auditoria('eliminar_lectura', 'medidor_lecturas', $lid, "Lectura de {$medidor['nombre']}");
            flash_set('success', 'Lectura eliminada.');
        }
    }
    header('Location: ' . url('medidor_ver.php?id=' . $id));
    exit;
}

$agrupar = (string) input('agrupar', 'dia');
if (!in_array($agrupar, ['dia', 'mes'], true)) $agrupar = 'dia';

$stats     = medidor_stats($id, 30);
$tendencia = consumo_tendencia($id, $agrupar, $agrupar === 'mes' ? 24 : 60);
$lecturas  = lecturas_medidor($id, 200);

$color = $medidor['tipo_color'] ?: '#6B7280';

// Datos para la gráfica
$g_labels   = array_map(fn($r) => $r['label'], $tendencia);
$g_consumos = array_map(fn($r) => round((float) $r['consumo'], 3), $tendencia);
$g_costos   = array_map(fn($r) => round((float) $r['costo'], 2), $tendencia);

$titulo_pagina = $medidor['nombre'];
$pagina_activa = 'medidores';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= url('medidores.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="w-11 h-11 rounded-lg flex items-center justify-center"
                 style="background-color: <?= e($color) ?>15; color: <?= e($color) ?>">
                <i data-lucide="<?= e($medidor['tipo_icono'] ?: 'gauge') ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-2xl font-extrabold text-zinc-900"><?= e($medidor['nombre']) ?></h2>
                <p class="text-xs text-zinc-500">
                    <?= e($medidor['tipo_nombre']) ?> · <?= e($medidor['sucursal_nombre']) ?>
                    <?= $medidor['area_nombre'] ? ' · ' . e($medidor['area_nombre']) : '' ?>
                    <?= $medidor['ubicacion'] ? ' · ' . e($medidor['ubicacion']) : '' ?>
                    <?php if (!$medidor['activo']): ?><span class="ml-1 text-[10px] font-bold text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">INACTIVO</span><?php endif; ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($puede_capturar): ?>
            <a href="<?= url('lectura_nueva.php?medidor_id=' . $id) ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Capturar lectura
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Última lectura</div>
            <div class="font-display text-xl font-extrabold text-zinc-900 leading-none">
                <?= e(fmt_lectura($medidor['ultima_valor'] ?? null)) ?> <span class="text-xs text-zinc-400"><?= e($medidor['unidad']) ?></span>
            </div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= $medidor['ultima_fecha'] ?? null ? e(fmt_fecha($medidor['ultima_fecha'])) : 'sin lecturas' ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Consumo prom.</div>
            <div class="font-display text-xl font-extrabold text-zinc-900 leading-none">
                <?= $stats['consumo_prom'] !== null ? e(fmt_lectura($stats['consumo_prom'])) : '—' ?> <span class="text-xs text-zinc-400"><?= e($medidor['unidad']) ?></span>
            </div>
            <div class="text-[10px] text-zinc-400 mt-1.5">últimas <?= $stats['num'] ?> lecturas</div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Consumo total</div>
            <div class="font-display text-xl font-extrabold text-zinc-900 leading-none">
                <?= e(fmt_lectura($stats['consumo_total'])) ?> <span class="text-xs text-zinc-400"><?= e($medidor['unidad']) ?></span>
            </div>
            <div class="text-[10px] text-zinc-400 mt-1.5">acumulado del histórico reciente</div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4">
            <div class="text-[11px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Costo estimado</div>
            <div class="font-display text-xl font-extrabold text-emerald-700 leading-none">
                $<?= e(number_format($stats['costo_total'], 2)) ?>
            </div>
            <div class="text-[10px] text-zinc-400 mt-1.5"><?= $medidor['tarifa'] !== null ? '$' . number_format((float) $medidor['tarifa'], 4) . ' / ' . e($medidor['unidad']) : 'sin tarifa configurada' ?></div>
        </div>
    </div>

    <!-- Gráfica de tendencia -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-base font-bold text-zinc-900">Tendencia de consumo</h3>
            <div class="inline-flex rounded-lg border border-zinc-300 bg-white p-0.5 text-xs font-semibold">
                <a href="<?= url('medidor_ver.php?id=' . $id . '&agrupar=dia') ?>"
                   class="px-3 py-1 rounded-md <?= $agrupar === 'dia' ? 'bg-bacal-700 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>">Por día</a>
                <a href="<?= url('medidor_ver.php?id=' . $id . '&agrupar=mes') ?>"
                   class="px-3 py-1 rounded-md <?= $agrupar === 'mes' ? 'bg-bacal-700 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>">Por mes</a>
            </div>
        </div>
        <?php if (count($tendencia) >= 1): ?>
        <div style="height: 280px"><canvas id="graficaConsumo"></canvas></div>
        <?php else: ?>
        <div class="py-12 text-center text-sm text-zinc-400">
            Aún no hay consumos para graficar. Captura al menos dos lecturas para ver la tendencia.
        </div>
        <?php endif; ?>
    </div>

    <!-- Histórico de lecturas -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-100">
            <h3 class="font-display text-base font-bold text-zinc-900">Histórico de lecturas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Lectura</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Consumo</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Leído por</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Foto</th>
                        <?php if ($puede_eliminar): ?><th class="px-4 py-2.5"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($lecturas as $l):
                        $c = $l['consumo'] !== null ? (float) $l['consumo'] : null;
                        $anom = ($c !== null) ? consumo_anomalo($id, $c) : ['anomalo' => false, 'nivel' => 'normal'];
                    ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2.5 text-zinc-700"><?= e(fmt_fecha($l['fecha_lectura'])) ?></td>
                        <td class="px-4 py-2.5 text-right font-mono text-zinc-900"><?= e(fmt_lectura((float) $l['valor_lectura'])) ?></td>
                        <td class="px-4 py-2.5 text-right">
                            <?php if ($l['es_reinicio']): ?>
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">REINICIO</span>
                            <?php elseif ($c !== null): ?>
                                <span class="font-mono <?= $anom['anomalo'] ? 'font-bold' : 'text-zinc-700' ?>"
                                      style="<?= $anom['nivel'] === 'muy_alto' ? 'color:#DC2626' : ($anom['nivel'] === 'alto' ? 'color:#D97706' : '') ?>">
                                    <?= e(fmt_lectura($c)) ?>
                                </span>
                                <?php if ($anom['anomalo']): ?>
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline -mt-0.5 <?= $anom['nivel'] === 'muy_alto' ? 'text-red-600' : 'text-amber-600' ?>"
                                       title="Consumo <?= $anom['nivel'] === 'muy_alto' ? 'muy alto' : 'alto' ?> vs. el promedio"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-zinc-300">base</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-right text-zinc-600"><?= $l['costo'] !== null ? '$' . e(number_format((float) $l['costo'], 2)) : '—' ?></td>
                        <td class="px-4 py-2.5 text-zinc-500 text-xs"><?= e($l['leido_por_nombre'] ?? '—') ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <?php if (!empty($l['foto'])): ?>
                            <a href="<?= url($l['foto']) ?>" target="_blank" rel="noopener" title="Ver foto">
                                <img src="<?= url($l['foto']) ?>" alt="foto" class="w-8 h-8 rounded object-cover inline-block border border-zinc-200">
                            </a>
                            <?php else: ?><span class="text-zinc-300">—</span><?php endif; ?>
                        </td>
                        <?php if ($puede_eliminar): ?>
                        <td class="px-4 py-2.5 text-right">
                            <form method="POST" onsubmit="return confirm('¿Eliminar esta lectura? No se puede deshacer.');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="eliminar_lectura">
                                <input type="hidden" name="lectura_id" value="<?= $l['id'] ?>">
                                <button type="submit" class="p-1.5 rounded text-zinc-400 hover:bg-red-50 hover:text-red-600" title="Eliminar lectura">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php if (!empty($l['nota'])): ?>
                    <tr><td colspan="<?= $puede_eliminar ? 7 : 6 ?>" class="px-4 pb-2 -mt-1 text-[11px] text-zinc-400 italic">↳ <?= e($l['nota']) ?></td></tr>
                    <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (empty($lecturas)): ?>
                    <tr><td colspan="<?= $puede_eliminar ? 7 : 6 ?>" class="px-4 py-12 text-center text-sm text-zinc-400">
                        Sin lecturas registradas.
                        <?php if ($puede_capturar): ?><a href="<?= url('lectura_nueva.php?medidor_id=' . $id) ?>" class="text-bacal-700 font-semibold hover:underline">Captura la primera</a>.<?php endif; ?>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (count($tendencia) >= 1): ?>
<script>
(function () {
    function initGrafica() {
        if (typeof Chart === 'undefined') { setTimeout(initGrafica, 100); return; }
        var el = document.getElementById('graficaConsumo');
        if (!el) return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels: <?= json_encode($g_labels) ?>,
                datasets: [{
                    label: 'Consumo (<?= e($medidor['unidad']) ?>)',
                    data: <?= json_encode($g_consumos) ?>,
                    backgroundColor: '<?= e($color) ?>cc',
                    borderColor: '<?= e($color) ?>',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (ctx) {
                                var costos = <?= json_encode($g_costos) ?>;
                                var v = costos[ctx.dataIndex];
                                return v ? 'Costo: $' + v.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                    x: { ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 0 }, grid: { display: false } }
                }
            }
        });
    }
    initGrafica();
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
