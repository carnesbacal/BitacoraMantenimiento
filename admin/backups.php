<?php
/**
 * ============================================================================
 * admin/backups.php - Gestión de respaldos de la base de datos
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/backups_helpers.php';

$u = usuario_actual();

// ----------------------------------------------------------------------------
// Procesar acciones
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        flash_set('error', 'Token de seguridad inválido.');
    } else {
        $op = (string) input('op', '');

        if ($op === 'generar') {
            $notas = trim((string) input('notas', ''));
            $resultado = generar_backup('manual', (int) $u['id'], $notas);

            if ($resultado['ok']) {
                $tam = fmt_bytes($resultado['tamano']);
                registrar_auditoria('generar_backup', null, null,
                    "Backup manual generado: {$resultado['archivo']} ($tam, método: {$resultado['metodo']})");
                flash_set('success', "Backup generado: {$resultado['archivo']} ($tam) — método: {$resultado['metodo']}");
            } else {
                flash_set('error', $resultado['mensaje']);
            }
        } elseif ($op === 'eliminar') {
            $bid = (int) input('id', 0);
            if ($bid > 0 && eliminar_backup($bid)) {
                registrar_auditoria('eliminar_backup', 'backups_realizados', $bid, 'Eliminó backup');
                flash_set('success', 'Backup eliminado.');
            }
        } elseif ($op === 'limpiar_viejos') {
            $borrados = limpiar_backups_viejos();
            flash_set('success', "Se eliminaron $borrados archivos viejos.");
        }
    }
    header('Location: ' . url('admin/backups.php'));
    exit;
}

// ----------------------------------------------------------------------------
// Descarga directa de un backup (?descargar=N)
// ----------------------------------------------------------------------------
$descargar = (int) input('descargar', 0);
if ($descargar > 0) {
    $b = db_one("SELECT * FROM backups_realizados WHERE id = :id", ['id' => $descargar]);
    if ($b && backup_existe_en_disco($b['nombre_archivo'])) {
        $ruta = BACKUPS_DIR . '/' . basename($b['nombre_archivo']);

        registrar_auditoria('descargar_backup', 'backups_realizados', (int) $b['id'],
            "Descargó backup {$b['nombre_archivo']}");

        // Enviar el archivo
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $b['nombre_archivo'] . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    } else {
        flash_set('error', 'El archivo solicitado no existe en disco.');
        header('Location: ' . url('admin/backups.php'));
        exit;
    }
}

// ----------------------------------------------------------------------------
// Datos para la vista
// ----------------------------------------------------------------------------
$backups = listar_backups(100);
$mysqldump_disponible = detectar_mysqldump() !== null;

// Estadísticas
$stats = db_one(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN exitoso = 1 THEN 1 ELSE 0 END) AS exitosos,
        SUM(CASE WHEN exitoso = 0 THEN 1 ELSE 0 END) AS fallidos,
        SUM(CASE WHEN tipo = 'automatico' THEN 1 ELSE 0 END) AS automaticos,
        SUM(tamano_bytes) AS bytes_totales,
        MAX(creado_en) AS ultimo
     FROM backups_realizados"
);

// Tamaño en disco real (archivos físicos)
$bytes_en_disco = 0;
$archivos_disco = is_dir(BACKUPS_DIR) ? (glob(BACKUPS_DIR . '/backup_*.sql.gz') ?: []) : [];
foreach ($archivos_disco as $a) $bytes_en_disco += filesize($a);

$titulo_pagina = 'Backups';
$pagina_activa = 'admin_backups';
require_once __DIR__ . '/../config/header.php';
?>

<div class="max-w-6xl mx-auto animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900">Respaldos de la base de datos</h2>
            <p class="text-xs text-zinc-500 mt-0.5">Genera, descarga y administra los respaldos de la BD <code class="font-mono bg-zinc-100 px-1 rounded text-[10px]">carnes_bacal</code>.</p>
        </div>

        <!-- Generar backup manual -->
        <form method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span>Generando, espera…</span>';">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="generar">
            <button type="submit"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm transition-colors">
                <i data-lucide="hard-drive-download" class="w-4 h-4"></i>
                <span>Generar backup ahora</span>
            </button>
        </form>
    </div>

    <!-- Banner sobre el método -->
    <div class="<?= $mysqldump_disponible ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' ?> border rounded-xl p-4 flex items-start gap-3">
        <i data-lucide="<?= $mysqldump_disponible ? 'check-circle-2' : 'alert-triangle' ?>" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <div class="text-xs flex-1">
            <?php if ($mysqldump_disponible): ?>
            <strong>mysqldump detectado.</strong> Los backups se generarán con el método rápido y completo (recomendado).
            <?php else: ?>
            <strong>mysqldump no encontrado.</strong> Los backups usarán el método PHP (más lento pero funcional).
            Si quieres acelerarlos, asegura que <code class="font-mono bg-amber-100 px-1 rounded">C:\xampp\mysql\bin\mysqldump.exe</code> exista.
            <?php endif; ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Total backups</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $stats['total'] ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold mb-1">Exitosos</div>
            <div class="font-display text-2xl font-extrabold text-emerald-700"><?= (int) $stats['exitosos'] ?></div>
        </div>
        <?php if ((int) $stats['fallidos'] > 0): ?>
        <div class="bg-white rounded-xl border border-bacal-200 p-4">
            <div class="text-[10px] text-bacal-700 uppercase tracking-wider font-bold mb-1">Fallidos</div>
            <div class="font-display text-2xl font-extrabold text-bacal-700"><?= (int) $stats['fallidos'] ?></div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Automáticos</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= (int) $stats['automaticos'] ?></div>
        </div>
        <?php endif; ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Espacio en disco</div>
            <div class="font-display text-2xl font-extrabold text-zinc-900"><?= e(fmt_bytes($bytes_en_disco)) ?></div>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 p-4">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Último backup</div>
            <div class="font-display text-sm font-extrabold text-zinc-900 leading-tight">
                <?= $stats['ultimo'] ? e(fmt_tiempo_relativo($stats['ultimo'])) : '<span class="text-zinc-400">Nunca</span>' ?>
            </div>
        </div>
    </div>

    <!-- Tabla de backups -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-100 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900">Historial de respaldos</h3>
            <form method="POST" onsubmit="return confirm('Esto eliminará archivos físicos más antiguos a <?= BACKUPS_RETENCION_DIAS ?> días o que excedan el máximo de <?= BACKUPS_MAX_GUARDAR ?>. ¿Continuar?');">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="limpiar_viejos">
                <button type="submit" class="text-xs font-medium text-zinc-500 hover:text-bacal-700 flex items-center gap-1.5">
                    <i data-lucide="broom" class="w-3.5 h-3.5"></i> Limpiar viejos
                </button>
            </form>
        </div>

        <?php if (empty($backups)): ?>
        <div class="px-5 py-12 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                <i data-lucide="database" class="w-8 h-8 text-zinc-400"></i>
            </div>
            <p class="text-sm font-medium text-zinc-700 mb-1">Aún no hay respaldos</p>
            <p class="text-xs text-zinc-500">Haz clic en "Generar backup ahora" para crear el primero.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Archivo</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tamaño</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Realizado por</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-2.5 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($backups as $b):
                        $existe_archivo = (int) $b['exitoso'] === 1 && backup_existe_en_disco($b['nombre_archivo']);
                    ?>
                    <tr class="hover:bg-zinc-50 <?= (int) $b['exitoso'] === 0 ? 'bg-bacal-50/20' : '' ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="<?= (int) $b['exitoso'] === 1 ? 'file-archive' : 'alert-circle' ?>"
                                   class="w-4 h-4 <?= (int) $b['exitoso'] === 1 ? 'text-zinc-500' : 'text-bacal-700' ?>"></i>
                                <span class="font-mono text-xs <?= (int) $b['exitoso'] === 0 ? 'text-bacal-700' : 'text-zinc-700' ?>"><?= e($b['nombre_archivo']) ?></span>
                                <?php if ((int) $b['exitoso'] === 1 && !$existe_archivo): ?>
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded" title="El registro existe pero el archivo fue borrado del disco">SOLO REGISTRO</span>
                                <?php endif; ?>
                                <?php if ((int) $b['exitoso'] === 0): ?>
                                <span class="text-[10px] font-bold text-bacal-700 bg-bacal-50 border border-bacal-200 px-1.5 py-0.5 rounded" title="<?= e((string) $b['mensaje_error']) ?>">FALLÓ</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($b['notas']): ?>
                            <div class="text-[10px] text-zinc-500 mt-0.5 ml-6"><?= e($b['notas']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($b['tipo'] === 'automatico'): ?>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded">
                                <i data-lucide="zap" class="w-2.5 h-2.5"></i> Automático
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-zinc-700 bg-zinc-100 px-1.5 py-0.5 rounded">
                                <i data-lucide="user" class="w-2.5 h-2.5"></i> Manual
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-700 font-mono">
                            <?= (int) $b['exitoso'] === 1 ? e(fmt_bytes((int) $b['tamano_bytes'])) : '—' ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-700">
                            <?= e($b['realizado_por_nombre'] ?? 'Sistema') ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-500">
                            <?= e(fmt_fecha($b['creado_en'])) ?>
                            <div class="text-[10px] text-zinc-400 mt-0.5"><?= e(fmt_tiempo_relativo($b['creado_en'])) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 justify-end">
                                <?php if ($existe_archivo): ?>
                                <a href="<?= url('admin/backups.php?descargar=' . $b['id']) ?>"
                                   class="p-1.5 rounded text-zinc-500 hover:bg-bacal-50 hover:text-bacal-700" title="Descargar">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('¿Eliminar este backup? Esta acción no se puede deshacer.');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="op" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-bacal-50 hover:text-bacal-700" title="Eliminar">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info sobre tareas programadas -->
    <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-5">
        <h3 class="font-display text-sm font-bold text-zinc-900 mb-2 flex items-center gap-2">
            <i data-lucide="clock" class="w-4 h-4 text-bacal-700"></i> Backup automático diario
        </h3>
        <p class="text-xs text-zinc-600 leading-relaxed mb-3">
            Para que el sistema haga respaldos automáticos cada día sin tu intervención, configura una tarea
            programada en Windows que ejecute el script <code class="font-mono bg-zinc-200 px-1 rounded">cron/backup_diario.php</code>.
        </p>
        <details class="text-xs text-zinc-700">
            <summary class="cursor-pointer font-semibold text-bacal-700 hover:underline">Ver instrucciones para Windows Task Scheduler</summary>
            <div class="mt-3 space-y-2 leading-relaxed">
                <p>1. Abre el <strong>Programador de tareas</strong> de Windows (Win + R, escribe <code class="font-mono bg-zinc-200 px-1">taskschd.msc</code>).</p>
                <p>2. Click derecho en "Biblioteca del programador de tareas" → <strong>Crear tarea básica</strong>.</p>
                <p>3. Nombre: <code class="font-mono">Backup Bitácora <?= EMPRESA_NOMBRE ?></code>.</p>
                <p>4. Activador: <strong>Diariamente</strong>, hora sugerida 2:00 AM (sistema con poco uso).</p>
                <p>5. Acción: <strong>Iniciar un programa</strong>.</p>
                <p>6. Programa: <code class="font-mono bg-zinc-200 px-1">C:\xampp\php\php.exe</code></p>
                <p>7. Argumentos: <code class="font-mono bg-zinc-200 px-1">"<?= e(str_replace('/', '\\', __DIR__) . '\..\cron\backup_diario.php') ?>"</code></p>
                <p>8. Finalizar. La tarea correrá cada día a la hora elegida y guardará el backup automáticamente.</p>
            </div>
        </details>
    </div>

    <!-- Recordatorio de seguridad -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <i data-lucide="lightbulb" class="w-5 h-5 text-amber-700 flex-shrink-0 mt-0.5"></i>
        <div class="text-xs text-amber-900 flex-1">
            <strong>Tip de seguridad:</strong> Los backups se guardan en <code class="font-mono bg-amber-100 px-1 rounded">backups/</code> dentro del proyecto.
            Te recomendamos **copiarlos periódicamente a otra ubicación** (USB, OneDrive, Google Drive) por si el disco del servidor falla.
            Los backups se mantienen automáticamente por <?= BACKUPS_RETENCION_DIAS ?> días o un máximo de <?= BACKUPS_MAX_GUARDAR ?> archivos.
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
