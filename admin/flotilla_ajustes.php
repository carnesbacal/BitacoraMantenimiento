<?php
/**
 * ============================================================================
 * admin/flotilla_ajustes.php - Ajustes configurables del módulo de flotilla
 * ============================================================================
 * Solo administradores (protegido por admin_helpers.php).
 * Por ahora: umbral de días para considerar el odómetro "desactualizado".
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/flotilla_helpers.php';

$errores = [];

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $umbral = (int) input('odometro_umbral_dias', 30);
        if ($umbral < 1)   $umbral = 1;
        if ($umbral > 365) $umbral = 365;
        config_set('odometro_umbral_dias', (string) $umbral);

        $umbral_foto = (int) input('foto_umbral_dias', 90);
        if ($umbral_foto < 1)   $umbral_foto = 1;
        if ($umbral_foto > 365) $umbral_foto = 365;
        config_set('foto_umbral_dias', (string) $umbral_foto);

        flash_set('exito', 'Ajustes de flotilla guardados.');
        header('Location: ' . url('admin/flotilla_ajustes.php'));
        exit;
    }
}

$umbral_actual = flotilla_odometro_umbral();
$foto_umbral_actual = flotilla_foto_umbral();
$config_lista  = (bool) db_one("SHOW TABLES LIKE 'configuracion'");

$titulo_pagina = 'Ajustes de flotilla';
$pagina_activa = 'flotilla';
require_once __DIR__ . '/../config/header.php';
?>

<div class="max-w-2xl mx-auto animate-fade-in">
    <?php render_admin_header('Ajustes de flotilla', 'Parámetros configurables del módulo de flotilla'); ?>

    <?php if (!$config_lista): ?>
    <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 border border-amber-300 text-sm text-amber-800">
        Aún no existe la tabla <strong>configuracion</strong> en la base de datos. Crea las tablas indicadas para poder guardar ajustes.
    </div>
    <?php endif; ?>

    <?php if ($errores): ?>
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $e): ?><div>✗ <?= e($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl border border-zinc-200 p-6 space-y-4">
        <?= csrf_input() ?>
        <div>
            <label class="text-sm font-semibold text-zinc-700 mb-1 flex items-center gap-2">
                <i data-lucide="gauge" class="w-4 h-4 text-bacal-700"></i>
                Umbral de odómetro desactualizado (días)
            </label>
            <p class="text-xs text-zinc-500 mb-2">
                Si un vehículo lleva más de estos días sin que se actualice su odómetro (manual o por carga de combustible),
                se marca como desactualizado en el Dashboard, la lista de Flotilla y la ficha del vehículo.
            </p>
            <input type="number" name="odometro_umbral_dias" min="1" max="365" value="<?= (int) $umbral_actual ?>" required
                   class="w-40 px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>
        <div>
            <label class="text-sm font-semibold text-zinc-700 mb-1 flex items-center gap-2">
                <i data-lucide="camera" class="w-4 h-4 text-bacal-700"></i>
                Umbral de foto del vehículo desactualizada (días)
            </label>
            <p class="text-xs text-zinc-500 mb-2">
                Si un vehículo lleva más de estos días sin una foto nueva, en su ficha se sugiere actualizarla. Recomendado: 90 días (trimestral).
            </p>
            <input type="number" name="foto_umbral_dias" min="1" max="365" value="<?= (int) $foto_umbral_actual ?>" required
                   class="w-40 px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-bacal-700 rounded-lg hover:bg-bacal-800 flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Guardar
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
