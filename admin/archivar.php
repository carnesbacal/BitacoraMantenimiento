<?php
/**
 * ============================================================================
 * admin/archivar.php - Archivado de incidencias antiguas + Palabras clave
 * ============================================================================
 * Permite:
 *   - Ver cuántas incidencias resueltas hace >1 año pueden archivarse
 *   - Archivarlas manualmente
 *   - Configurar palabras clave de categorías para sugerencia automática
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/organizacion_helpers.php';

$u = usuario_actual();

// ----------------------------------------------------------------------------
// Procesar acciones
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        flash_set('error', 'Token inválido.');
    } else {
        $op = (string) input('op', '');

        try {
            if ($op === 'archivar') {
                $dias = max(30, (int) input('dias', 365));
                $archivadas = archivar_incidencias_antiguas($dias);
                if ($archivadas > 0) {
                    registrar_auditoria('archivar_masivo', null, null, "Archivado masivo: $archivadas incidencia(s) resueltas hace >$dias días");
                    flash_set('success', "Se archivaron $archivadas incidencia(s).");
                } else {
                    flash_set('info', 'No había incidencias por archivar con esos criterios.');
                }
            } elseif ($op === 'agregar_palabra') {
                $categoria_id = (int) input('categoria_id', 0);
                $palabra = trim((string) input('palabra', ''));
                $peso = (int) input('peso', 1);
                if ($categoria_id && $palabra !== '') {
                    if (agregar_palabra_clave($categoria_id, $palabra, $peso)) {
                        registrar_auditoria('agregar_palabra_clave', 'categorias', $categoria_id, "Palabra: $palabra (peso $peso)");
                        flash_set('success', 'Palabra clave agregada.');
                    } else {
                        flash_set('error', 'No se pudo agregar (¿duplicada o inválida?).');
                    }
                }
            } elseif ($op === 'eliminar_palabra') {
                $palabra_id = (int) input('palabra_id', 0);
                if ($palabra_id > 0) {
                    eliminar_palabra_clave($palabra_id);
                    flash_set('success', 'Palabra eliminada.');
                }
            }
        } catch (Throwable $e) {
            flash_set('error', 'Error: ' . $e->getMessage());
        }
    }
    header('Location: ' . url('admin/archivar.php'));
    exit;
}

// ----------------------------------------------------------------------------
// Datos para la vista
// ----------------------------------------------------------------------------
$conteos = contar_incidencias_archivables(365);
$stats_arch = stats_archivado();
$stats_kw = stats_palabras_clave();

// Categoría seleccionada para ver palabras clave
$cat_id = (int) input('categoria_id', 0);
$categorias = db_all("SELECT id, nombre, color FROM categorias WHERE activo = 1 ORDER BY nombre");

if ($cat_id > 0) {
    $cat_seleccionada = db_one("SELECT * FROM categorias WHERE id = :id", ['id' => $cat_id]);
    $palabras_cat = listar_palabras_clave_de_categoria($cat_id);
} else {
    $cat_seleccionada = null;
    $palabras_cat = [];
}

$titulo_pagina = 'Organización';
$pagina_activa = 'admin_archivar';
require_once __DIR__ . '/../config/header.php';
?>

<div class="max-w-6xl mx-auto animate-fade-in space-y-5"
     x-data="{ tab: 'archivar' }">

    <!-- Header -->
    <div>
        <h2 class="font-display text-2xl font-extrabold text-zinc-900">Organización</h2>
        <p class="text-xs text-zinc-500 mt-0.5">Archiva incidencias antiguas y gestiona palabras clave para sugerencias automáticas.</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-zinc-200">
        <div class="flex gap-1 -mb-px">
            <button @click="tab = 'archivar'"
                    class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                    :class="tab === 'archivar' ? 'border-bacal-700 text-bacal-700' : 'border-transparent text-zinc-500 hover:text-zinc-700'">
                <i data-lucide="archive" class="w-4 h-4"></i> Archivado de incidencias
            </button>
            <button @click="tab = 'palabras'"
                    class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors"
                    :class="tab === 'palabras' ? 'border-bacal-700 text-bacal-700' : 'border-transparent text-zinc-500 hover:text-zinc-700'">
                <i data-lucide="key" class="w-4 h-4"></i> Palabras clave (<?= $stats_kw['total_palabras'] ?>)
            </button>
        </div>
    </div>

    <!-- ============================================================
         TAB: ARCHIVADO
         ============================================================ -->
    <div x-show="tab === 'archivar'" x-cloak class="space-y-5">

        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Incidencias totales</div>
                <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($stats_arch['total']) ?></div>
            </div>
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-emerald-700 uppercase tracking-wider font-bold mb-1">Activas</div>
                <div class="font-display text-2xl font-extrabold text-emerald-700"><?= number_format($stats_arch['activas']) ?></div>
            </div>
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Archivadas</div>
                <div class="font-display text-2xl font-extrabold text-zinc-700"><?= number_format($stats_arch['archivadas']) ?></div>
            </div>
            <div class="bg-white rounded-xl border <?= $conteos['por_archivar'] > 0 ? 'border-amber-300 bg-amber-50' : 'border-zinc-200' ?> p-4">
                <div class="text-[10px] uppercase tracking-wider font-bold mb-1 <?= $conteos['por_archivar'] > 0 ? 'text-amber-700' : 'text-zinc-500' ?>">Por archivar</div>
                <div class="font-display text-2xl font-extrabold <?= $conteos['por_archivar'] > 0 ? 'text-amber-700' : 'text-zinc-900' ?>"><?= number_format((int) $conteos['por_archivar']) ?></div>
            </div>
        </div>

        <!-- Cómo funciona -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
            <i data-lucide="info" class="w-5 h-5 text-blue-700 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs text-blue-900 flex-1">
                <strong>Cómo funciona el archivado:</strong>
                <ul class="list-disc list-inside mt-1 space-y-0.5">
                    <li>Solo se archivan incidencias <strong>resueltas/cerradas</strong> con fecha de resolución hace más de <strong>1 año</strong>.</li>
                    <li>Las incidencias archivadas <strong>no se eliminan</strong>, solo se ocultan del listado principal.</li>
                    <li>Puedes verlas con el filtro "Incluir archivadas" en la bitácora.</li>
                    <li>El proceso se puede ejecutar manualmente aquí o automáticamente con un cron diario.</li>
                </ul>
            </div>
        </div>

        <!-- Acción de archivado -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="archive" class="w-4 h-4 text-bacal-700"></i> Ejecutar archivado manual
            </h3>

            <form method="POST" class="flex flex-wrap items-end gap-3"
                  onsubmit="return confirm('¿Confirmas archivar todas las incidencias resueltas hace más de los días indicados? Esto NO las elimina, solo las oculta del listado.');">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="archivar">

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Archivar resueltas hace más de</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="dias" min="30" max="3650" value="365"
                               class="w-32 px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <span class="text-sm text-zinc-600">días</span>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-1">Recomendado: 365 días (1 año)</p>
                </div>

                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                    <i data-lucide="archive" class="w-4 h-4"></i> Ejecutar archivado
                </button>
            </form>

            <?php if ($conteos['por_archivar'] > 0): ?>
            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                <strong>Hay <?= number_format((int) $conteos['por_archivar']) ?> incidencia(s)</strong> resueltas hace más de 1 año esperando ser archivadas.
            </div>
            <?php else: ?>
            <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-900">
                ✓ Todas las incidencias antiguas ya están archivadas.
            </div>
            <?php endif; ?>
        </div>

        <!-- Automatización -->
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-5">
            <h3 class="font-display text-sm font-bold text-zinc-900 mb-2 flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4 text-bacal-700"></i> Archivado automático diario
            </h3>
            <p class="text-xs text-zinc-600 leading-relaxed mb-3">
                Configura una tarea programada en Windows que ejecute <code class="font-mono bg-zinc-200 px-1 rounded">cron/archivar_automatico.php</code> diariamente.
            </p>
            <details class="text-xs text-zinc-700">
                <summary class="cursor-pointer font-semibold text-bacal-700 hover:underline">Ver instrucciones</summary>
                <div class="mt-3 space-y-2 leading-relaxed">
                    <p>1. Abre <strong>Programador de tareas</strong> (Win + R → <code class="font-mono bg-zinc-200 px-1">taskschd.msc</code>)</p>
                    <p>2. Crear tarea básica: <code class="font-mono">Archivado Bitácora <?= EMPRESA_NOMBRE ?></code></p>
                    <p>3. Activador: Diariamente a las 3:00 AM</p>
                    <p>4. Programa: <code class="font-mono bg-zinc-200 px-1">C:\xampp\php\php.exe</code></p>
                    <p>5. Argumentos: <code class="font-mono bg-zinc-200 px-1">"<?= e(str_replace('/', '\\', dirname(__DIR__))) ?>\cron\archivar_automatico.php"</code></p>
                </div>
            </details>
        </div>

    </div>

    <!-- ============================================================
         TAB: PALABRAS CLAVE
         ============================================================ -->
    <div x-show="tab === 'palabras'" x-cloak class="space-y-5">

        <!-- KPIs palabras -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Total palabras</div>
                <div class="font-display text-2xl font-extrabold text-zinc-900"><?= number_format($stats_kw['total_palabras']) ?></div>
            </div>
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Categorías con palabras</div>
                <div class="font-display text-2xl font-extrabold text-zinc-900">
                    <?= $stats_kw['categorias_con_palabras'] ?>
                    <span class="text-sm font-normal text-zinc-500">/ <?= $stats_kw['categorias_total'] ?></span>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-zinc-200 p-4">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider font-bold mb-1">Cobertura</div>
                <div class="font-display text-2xl font-extrabold text-zinc-900">
                    <?= $stats_kw['categorias_total'] > 0
                        ? round(($stats_kw['categorias_con_palabras'] / $stats_kw['categorias_total']) * 100)
                        : 0 ?>%
                </div>
            </div>
        </div>

        <!-- Cómo funciona -->
        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 flex items-start gap-3">
            <i data-lucide="sparkles" class="w-5 h-5 text-purple-700 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs text-purple-900 flex-1">
                <strong>Cómo funcionan las sugerencias de categoría:</strong>
                <ul class="list-disc list-inside mt-1 space-y-0.5">
                    <li>Cuando un usuario escribe el título y descripción de una incidencia, el sistema busca <strong>coincidencias</strong> con las palabras clave aquí registradas.</li>
                    <li>La categoría con mayor <strong>score</strong> (suma de pesos de palabras coincidentes) se sugiere automáticamente.</li>
                    <li><strong>Peso 3</strong> = palabra muy específica (ej. "báscula"). <strong>Peso 1</strong> = palabra general (ej. "error").</li>
                    <li>Las búsquedas son <strong>case-insensitive y sin acentos</strong>.</li>
                </ul>
            </div>
        </div>

        <!-- Selector de categoría -->
        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-zinc-700">Ver palabras de:</label>
            <select name="categoria_id" onchange="this.form.submit()"
                    class="flex-1 max-w-md px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                <option value="">— Selecciona una categoría —</option>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($cat_seleccionada): ?>
        <!-- Gestión de palabras de la categoría -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: <?= e($cat_seleccionada['color'] ?? '#71717a') ?>"></span>
                    <?= e($cat_seleccionada['nombre']) ?>
                    <span class="text-xs font-normal text-zinc-500">(<?= count($palabras_cat) ?> palabras)</span>
                </h3>
            </div>

            <!-- Agregar palabra -->
            <div class="p-5 bg-zinc-50 border-b border-zinc-200">
                <form method="POST" class="flex flex-wrap items-end gap-2">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="agregar_palabra">
                    <input type="hidden" name="categoria_id" value="<?= $cat_id ?>">

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Palabra o frase clave</label>
                        <input type="text" name="palabra" required maxlength="60"
                               placeholder="ej. impresora, no enciende, codigo de barras"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Peso</label>
                        <select name="peso" class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                            <option value="1">1 (general)</option>
                            <option value="2" selected>2 (medio)</option>
                            <option value="3">3 (específica)</option>
                            <option value="4">4 (muy específica)</option>
                            <option value="5">5 (única)</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-4 h-4"></i> Agregar
                    </button>
                </form>
            </div>

            <!-- Lista de palabras -->
            <?php if (empty($palabras_cat)): ?>
            <div class="px-5 py-12 text-center">
                <i data-lucide="key" class="w-10 h-10 mx-auto text-zinc-300 mb-2"></i>
                <p class="text-sm text-zinc-500">Esta categoría aún no tiene palabras clave.</p>
                <p class="text-xs text-zinc-400">Agrega algunas para que el sistema sepa cuándo sugerir esta categoría.</p>
            </div>
            <?php else: ?>
            <div class="p-5 flex flex-wrap gap-2">
                <?php foreach ($palabras_cat as $p): ?>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-zinc-200 bg-zinc-50 group">
                    <span class="text-xs font-medium text-zinc-900"><?= e($p['palabra']) ?></span>
                    <span class="text-[9px] font-bold text-zinc-500 bg-white px-1.5 py-0.5 rounded-full">×<?= (int) $p['peso'] ?></span>
                    <form method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar esta palabra?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="eliminar_palabra">
                        <input type="hidden" name="palabra_id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="text-zinc-400 hover:text-bacal-700 opacity-0 group-hover:opacity-100 transition-opacity">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-12 text-center">
            <i data-lucide="key" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
            <p class="text-sm font-medium text-zinc-700 mb-1">Selecciona una categoría arriba</p>
            <p class="text-xs text-zinc-500">para ver y gestionar sus palabras clave.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
