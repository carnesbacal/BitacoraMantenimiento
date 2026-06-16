<?php
/**
 * ============================================================================
 * lecturas_captura.php - Captura masiva de lecturas por sucursal
 * ============================================================================
 * Para la ronda diaria: muestra todos los medidores activos de una sucursal
 * con su última lectura y un campo para teclear la nueva. Captura varias de
 * un jalón. La foto y los reinicios se manejan en la captura individual.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/medidores_helpers.php';

requerir_login();

$puede_capturar = tiene_permiso('administrar') || tiene_permiso('resolver');
if (!$puede_capturar) { flash_set('error', 'No tienes permiso para capturar lecturas.'); header('Location: ' . url('medidores.php')); exit; }

$sucursal_id = (int) input('sucursal_id', 0);
$sucursales = db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo = 1 ORDER BY nombre ASC");
$errores = [];

// Restricción por sucursal: si el usuario no ve todas, limitamos a la suya
$restriccion_suc = medidor_sucursal_usuario();
if ($restriccion_suc !== null) {
    $sucursales = array_values(array_filter($sucursales, fn($s) => (int) $s['id'] === $restriccion_suc));
    // Auto-seleccionar su sucursal y bloquear cualquier otra
    if ($restriccion_suc > 0) $sucursal_id = $restriccion_suc;
    else $sucursal_id = 0;
}

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $fecha   = (string) input('fecha_lectura', date('Y-m-d'));
        $valores = (array) input('valor', []);   // [medidor_id => valor]
        $uid     = (int) usuario_actual()['id'];

        $guardadas = 0;
        $omitidas = [];
        foreach ($valores as $mid => $val) {
            $mid = (int) $mid;
            $val = trim((string) $val);
            if ($val === '' || !is_numeric($val)) continue; // sin captura → se ignora

            $medidor = obtener_medidor($mid);
            if (!$medidor || (int) $medidor['sucursal_id'] !== $sucursal_id) continue;

            $valor = (float) $val;
            $anterior = ultima_lectura($mid);
            // En captura masiva no manejamos reinicios: si baja, se omite y se avisa
            if ($anterior && $valor < (float) $anterior['valor_lectura']) {
                $omitidas[] = $medidor['nombre'];
                continue;
            }
            try {
                registrar_lectura($mid, ['valor_lectura' => $valor, 'fecha_lectura' => $fecha], $uid);
                $guardadas++;
            } catch (Throwable $e) {
                $omitidas[] = $medidor['nombre'] . ' (' . $e->getMessage() . ')';
            }
        }

        if ($guardadas > 0) registrar_auditoria('captura_masiva_lecturas', 'medidor_lecturas', $sucursal_id, "$guardadas lectura(s) en sucursal #$sucursal_id");

        $msg = "$guardadas lectura(s) registrada(s).";
        if ($omitidas) $msg .= ' Revisa por separado (lectura menor o error): ' . implode(', ', $omitidas) . '.';
        flash_set($guardadas > 0 ? 'success' : 'error', $msg);
        header('Location: ' . url('lecturas_captura.php?sucursal_id=' . $sucursal_id));
        exit;
    }
}

$medidores = $sucursal_id ? medidores_por_sucursal($sucursal_id) : [];

$titulo_pagina = 'Captura por sucursal';
$pagina_activa = 'medidores';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-3xl mx-auto animate-fade-in">

    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('medidores.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900">Captura por sucursal</h2>
            <p class="text-xs text-zinc-500">Ronda diaria: teclea las lecturas de todos los medidores de una sucursal.</p>
        </div>
    </div>

    <!-- Selector de sucursal -->
    <form method="GET" class="mb-5">
        <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal</label>
        <select name="sucursal_id" onchange="this.form.submit()"
                class="w-full max-w-sm px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
            <option value="0">— Selecciona una sucursal —</option>
            <?php foreach ($sucursales as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $sucursal_id === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?> (<?= e($s['codigo']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($sucursal_id && empty($medidores)): ?>
    <div class="px-4 py-8 text-center bg-white rounded-xl border border-zinc-200">
        <i data-lucide="gauge" class="w-8 h-8 text-zinc-400 mx-auto mb-2"></i>
        <p class="text-sm text-zinc-600">Esta sucursal no tiene medidores activos.</p>
    </div>
    <?php elseif ($sucursal_id): ?>

    <form method="POST" action="<?= url('lecturas_captura.php?sucursal_id=' . $sucursal_id) ?>">
        <?= csrf_input() ?>

        <div class="mb-4">
            <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha de la ronda</label>
            <input type="date" name="fecha_lectura" required value="<?= date('Y-m-d') ?>"
                   class="px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
            <?php foreach ($medidores as $m): $color = $m['tipo_color'] ?: '#6B7280'; ?>
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                     style="background-color: <?= e($color) ?>15; color: <?= e($color) ?>">
                    <i data-lucide="<?= e($m['tipo_icono'] ?: 'gauge') ?>" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm text-zinc-900 truncate"><?= e($m['nombre']) ?></div>
                    <div class="text-[11px] text-zinc-400">
                        <?= e($m['tipo_nombre']) ?>
                        <?php if ($m['ultima_valor'] !== null): ?>
                            · última: <span class="font-mono"><?= e(fmt_lectura((float) $m['ultima_valor'])) ?></span> <?= e($m['unidad']) ?> (<?= e(fmt_fecha($m['ultima_fecha'])) ?>)
                        <?php else: ?>
                            · sin lecturas
                        <?php endif; ?>
                    </div>
                </div>
                <div class="relative w-36 shrink-0">
                    <input type="number" name="valor[<?= $m['id'] ?>]" step="0.001" min="0"
                           placeholder="Lectura"
                           class="w-full pl-3 pr-12 py-2 rounded-lg border border-zinc-300 text-sm font-mono text-right focus:outline-none focus:border-bacal-700">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 text-[10px]"><?= e($m['unidad']) ?></span>
                </div>
                <a href="<?= url('lectura_nueva.php?medidor_id=' . $m['id']) ?>"
                   class="p-1.5 rounded text-zinc-400 hover:bg-zinc-100 hover:text-bacal-700 shrink-0" title="Captura individual (foto, reinicio)">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="text-xs text-zinc-500 mt-3">
            Deja en blanco los medidores que no leíste. Para adjuntar foto o registrar un reinicio, usa la captura individual (icono de cámara).
        </p>

        <div class="flex items-center justify-end gap-2 mt-4">
            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm">
                Guardar lecturas
            </button>
        </div>
    </form>

    <?php else: ?>
    <div class="px-4 py-8 text-center bg-white rounded-xl border border-zinc-200">
        <i data-lucide="building-2" class="w-8 h-8 text-zinc-400 mx-auto mb-2"></i>
        <p class="text-sm text-zinc-600">Selecciona una sucursal para empezar la ronda.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
