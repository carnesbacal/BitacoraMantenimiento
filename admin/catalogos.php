<?php
/**
 * ============================================================================
 * admin/catalogos.php - Editor unificado de catálogos
 * ============================================================================
 * Una sola página con tabs para administrar:
 *   - Categorías y subcategorías
 *   - Tipos de trabajo
 *   - Severidades (con SLA)
 *   - Estados (con orden y flags inicial/final)
 *   - Orígenes de reporte
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';

$tab = (string) input('tab', 'categorias');
$tabs_validos = ['categorias', 'tipos', 'severidades', 'estados', 'origenes', 'medidor_tipos', 'estaciones'];
if (!in_array($tab, $tabs_validos, true)) $tab = 'categorias';

$errores = [];

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');
        $tabla_actual = (string) input('tabla', '');

        $tablas_map = [
            'categorias'    => ['tabla' => 'categorias', 'label' => 'Categoría', 'cols' => ['nombre', 'descripcion', 'color']],
            'subcategorias' => ['tabla' => 'subcategorias', 'label' => 'Subcategoría', 'cols' => ['nombre', 'descripcion', 'categoria_id']],
            'tipos'         => ['tabla' => 'tipos_trabajo', 'label' => 'Tipo de trabajo', 'cols' => ['nombre', 'descripcion', 'color']],
            'severidades'   => ['tabla' => 'severidades', 'label' => 'Severidad', 'cols' => ['nombre', 'nivel', 'color', 'sla_horas', 'descripcion']],
            'estados'       => ['tabla' => 'estados', 'label' => 'Estado', 'cols' => ['nombre', 'orden', 'color', 'es_inicial', 'es_final', 'descripcion']],
            'origenes'      => ['tabla' => 'origenes_reporte', 'label' => 'Origen', 'cols' => ['nombre']],
            'medidor_tipos' => ['tabla' => 'medidor_tipos', 'label' => 'Tipo de medidor', 'cols' => ['nombre', 'unidad', 'icono', 'color']],
            'estaciones'    => ['tabla' => 'flotilla_estaciones', 'label' => 'Estación', 'cols' => ['nombre', 'direccion']],
        ];

        if (!isset($tablas_map[$tabla_actual])) {
            $errores[] = 'Tabla no válida.';
        } else {
            $cfg = $tablas_map[$tabla_actual];
            $tabla = $cfg['tabla'];
            $label = $cfg['label'];

            try {
                if ($op === 'crear' || $op === 'editar') {
                    $datos = [];
                    foreach ($cfg['cols'] as $c) {
                        $valor = input($c, null);
                        if ($c === 'es_inicial' || $c === 'es_final') {
                            $datos[$c] = $valor ? 1 : 0;
                        } elseif (in_array($c, ['nivel', 'sla_horas', 'orden', 'categoria_id'], true)) {
                            $datos[$c] = $valor !== null && $valor !== '' ? (int) $valor : null;
                        } else {
                            $datos[$c] = $valor !== null ? trim((string) $valor) : null;
                            if ($datos[$c] === '') $datos[$c] = null;
                        }
                    }

                    if (empty($datos['nombre'])) {
                        $errores[] = 'El nombre es obligatorio.';
                    }

                    if (empty($errores)) {
                        if ($op === 'crear') {
                            $datos['activo'] = 1;
                            $cols   = implode(', ', array_keys($datos));
                            $params = ':' . implode(', :', array_keys($datos));
                            db_exec("INSERT INTO $tabla ($cols) VALUES ($params)", $datos);
                            $new_id = db_last_id();
                            registrar_auditoria("crear_$tabla", $tabla, $new_id, "$label {$datos['nombre']}");
                            flash_set('success', "$label \"{$datos['nombre']}\" creado.");
                        } else {
                            $eid = (int) input('id', 0);
                            $sets = [];
                            foreach (array_keys($datos) as $k) $sets[] = "$k = :$k";
                            $datos['id'] = $eid;
                            db_exec("UPDATE $tabla SET " . implode(', ', $sets) . " WHERE id = :id", $datos);
                            registrar_auditoria("editar_$tabla", $tabla, $eid, "$label {$datos['nombre']}");
                            flash_set('success', "$label actualizado.");
                        }
                    }
                } elseif ($op === 'toggle') {
                    $eid = (int) input('id', 0);
                    admin_toggle_activo($tabla, $eid, $label);
                }
            } catch (Throwable $e) {
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }

        if (empty($errores)) {
            header('Location: ' . url('admin/catalogos.php?tab=' . $tab));
            exit;
        }
    }
}

$titulo_pagina = 'Catálogos';
$pagina_activa = 'admin_catalogos';
require_once __DIR__ . '/../config/header.php';
?>

<div class="animate-fade-in">
    <?php render_admin_header('Catálogos', 'Categorías, tipos, severidades, estados, orígenes y tipos de medidor'); ?>

    <?php if (!empty($errores)): ?>
    <div class="mb-4 px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs"><?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="border-b border-zinc-200 mb-4">
        <div class="flex gap-1 -mb-px overflow-x-auto">
            <?php
            $tabs_labels = [
                'categorias' => ['Categorías', 'tags'],
                'tipos' => ['Tipos de trabajo', 'briefcase'],
                'severidades' => ['Severidades', 'zap'],
                'estados' => ['Estados', 'flag'],
                'origenes' => ['Orígenes', 'inbox'],
                'medidor_tipos' => ['Tipos de medidor', 'gauge'],
                'estaciones' => ['Estaciones', 'fuel'],
            ];
            foreach ($tabs_labels as $key => [$label, $icono]):
                $activo = $tab === $key;
            ?>
            <a href="<?= url('admin/catalogos.php?tab=' . $key) ?>"
               class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                      <?= $activo ? 'border-bacal-700 text-bacal-700' : 'border-transparent text-zinc-500 hover:text-zinc-700' ?>">
                <i data-lucide="<?= $icono ?>" class="w-4 h-4"></i>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contenido del tab activo -->
    <?php if ($tab === 'categorias'):
        $categorias = db_all("SELECT * FROM categorias ORDER BY activo DESC, nombre ASC");
        $todas_subs = db_all("SELECT s.*, c.nombre cat_nombre FROM subcategorias s INNER JOIN categorias c ON s.categoria_id=c.id ORDER BY c.nombre, s.nombre");
        $subs_por_cat = [];
        foreach ($todas_subs as $s) $subs_por_cat[$s['categoria_id']][] = $s;
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" x-data="{ editandoCat: null, subEditando: null, mostrarNuevaCat: false, mostrarNuevaSub: null }">

        <!-- Categorías -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm">
            <div class="px-4 py-3 border-b border-zinc-100 flex items-center justify-between">
                <h3 class="font-display text-base font-bold text-zinc-900">Categorías</h3>
                <button @click="mostrarNuevaCat = !mostrarNuevaCat" class="text-xs font-semibold text-bacal-700 hover:text-bacal-800 flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> <span x-text="mostrarNuevaCat ? 'Cancelar' : 'Nueva'"></span>
                </button>
            </div>

            <div x-show="mostrarNuevaCat" x-cloak class="p-4 bg-zinc-50 border-b border-zinc-200">
                <form method="POST" class="space-y-2">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="crear">
                    <input type="hidden" name="tabla" value="categorias">
                    <input type="text" name="nombre" placeholder="Nombre" required maxlength="100"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    <?= render_color_picker('color', '#6B7280') ?>
                    <button type="submit" class="px-3 py-1 rounded-md bg-bacal-700 text-white text-xs font-semibold">Crear</button>
                </form>
            </div>

            <div class="divide-y divide-zinc-100 max-h-[600px] overflow-y-auto">
                <?php foreach ($categorias as $c): ?>
                <div class="<?= !$c['activo'] ? 'opacity-50' : '' ?>">
                    <div x-show="editandoCat !== <?= $c['id'] ?>" class="px-4 py-2.5 flex items-center gap-2 group">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium"
                              style="background-color: <?= e($c['color']) ?>1f; color: <?= e($c['color']) ?>; border: 1px solid <?= e($c['color']) ?>40">
                            <?= e($c['nombre']) ?>
                        </span>
                        <span class="text-[10px] text-zinc-400 flex-1"><?= count($subs_por_cat[$c['id']] ?? []) ?> sub.</span>
                        <div class="flex gap-0.5 opacity-0 group-hover:opacity-100">
                            <button @click="editandoCat = <?= $c['id'] ?>" class="p-1 rounded text-zinc-400 hover:bg-zinc-100">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            </button>
                            <form method="POST" onsubmit="return confirm('¿Cambiar estado?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="toggle">
                                <input type="hidden" name="tabla" value="categorias">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="p-1 rounded text-zinc-400 hover:bg-zinc-100">
                                    <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div x-show="editandoCat === <?= $c['id'] ?>" x-cloak class="px-4 py-2.5 bg-zinc-50">
                        <form method="POST" class="space-y-2">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="editar">
                            <input type="hidden" name="tabla" value="categorias">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="text" name="nombre" value="<?= e($c['nombre']) ?>" required
                                   class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                            <?= render_color_picker('color', $c['color']) ?>
                            <div class="flex gap-1">
                                <button type="button" @click="editandoCat = null" class="px-2 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                                <button type="submit" class="px-2 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                            </div>
                        </form>
                    </div>

                    <!-- Subcategorías -->
                    <div class="px-4 pb-2">
                        <?php foreach ($subs_por_cat[$c['id']] ?? [] as $s): ?>
                        <div class="text-xs text-zinc-600 flex items-center gap-1 py-0.5 group/sub <?= !$s['activo'] ? 'opacity-50' : '' ?>">
                            <span class="text-zinc-300">└</span>
                            <span class="flex-1"><?= e($s['nombre']) ?></span>
                            <form method="POST" class="opacity-0 group-hover/sub:opacity-100">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="toggle">
                                <input type="hidden" name="tabla" value="subcategorias">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="text-zinc-400 hover:text-bacal-700"><i data-lucide="power" class="w-3 h-3"></i></button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <button @click="mostrarNuevaSub = (mostrarNuevaSub === <?= $c['id'] ?> ? null : <?= $c['id'] ?>)"
                                class="text-[10px] font-semibold text-bacal-700 hover:text-bacal-800 mt-1">+ subcategoría</button>

                        <form method="POST" x-show="mostrarNuevaSub === <?= $c['id'] ?>" x-cloak class="mt-2 flex gap-1">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="crear">
                            <input type="hidden" name="tabla" value="subcategorias">
                            <input type="hidden" name="categoria_id" value="<?= $c['id'] ?>">
                            <input type="text" name="nombre" placeholder="Nombre" required maxlength="100"
                                   class="flex-1 px-2 py-1 rounded border border-zinc-300 text-xs focus:outline-none focus:border-bacal-700">
                            <button type="submit" class="px-2 rounded bg-bacal-700 text-white text-xs font-semibold">+</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hint -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-blue-900">
            <h4 class="font-display font-bold mb-2 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4"></i> Sobre categorías
            </h4>
            <p class="text-blue-800 leading-relaxed">
                Las <strong>categorías</strong> son la clasificación técnica del incidente
                (Hardware, Software, Red, etc.). Cada una puede tener
                <strong>subcategorías</strong> opcionales más específicas
                (PC, Laptop, Periféricos…).
            </p>
            <p class="text-blue-800 mt-2 leading-relaxed">
                Pasa el cursor sobre cualquier elemento para ver opciones de editar o activar/desactivar.
            </p>
        </div>
    </div>

    <?php elseif ($tab === 'tipos'):
        $items = db_all("SELECT * FROM tipos_trabajo ORDER BY activo DESC, nombre ASC");
    ?>
    <?= render_lista_simple_catalogo('tipos_trabajo', 'Tipo de trabajo', $items) ?>

    <?php elseif ($tab === 'severidades'):
        $items = db_all("SELECT * FROM severidades ORDER BY activo DESC, nivel ASC");
    ?>
    <?= render_lista_severidades($items) ?>

    <?php elseif ($tab === 'estados'):
        $items = db_all("SELECT * FROM estados ORDER BY activo DESC, orden ASC");
    ?>
    <?= render_lista_estados($items) ?>

    <?php elseif ($tab === 'origenes'):
        $items = db_all("SELECT * FROM origenes_reporte ORDER BY activo DESC, id ASC");
    ?>
    <?= render_lista_origenes($items) ?>

    <?php elseif ($tab === 'medidor_tipos'):
        $items = db_all("SELECT * FROM medidor_tipos ORDER BY activo DESC, nombre ASC");
    ?>
    <?= render_lista_medidor_tipos($items) ?>

    <?php elseif ($tab === 'estaciones'):
        $estaciones_existe = (bool) db_one("SHOW TABLES LIKE 'flotilla_estaciones'");
        $items = $estaciones_existe ? db_all("SELECT * FROM flotilla_estaciones ORDER BY activo DESC, nombre ASC") : [];
    ?>
    <?php if (!$estaciones_existe): ?>
    <div class="px-4 py-3 rounded-lg bg-amber-50 border border-amber-300 text-sm text-amber-800">
        Falta crear la tabla <strong>flotilla_estaciones</strong> en la base de datos (corre la migración de combustible).
    </div>
    <?php else: ?>
    <?= render_lista_estaciones($items) ?>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php
// Helpers de renderizado para los catálogos más simples
function render_lista_simple_catalogo(string $tabla, string $label, array $items): string {
    ob_start(); ?>
    <div x-data="{ editandoId: null, mostrarNuevo: false }">
        <div class="mb-3">
            <button @click="mostrarNuevo = !mostrarNuevo" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="mostrarNuevo ? 'Cancelar' : 'Nuevo <?= e(strtolower($label)) ?>'"></span>
            </button>
        </div>

        <div x-show="mostrarNuevo" x-cloak class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 mb-4">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="crear">
                <input type="hidden" name="tabla" value="<?= e($tabla === 'tipos_trabajo' ? 'tipos' : $tabla) ?>">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="100"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Color</label>
                    <?= render_color_picker('color', '#6B7280') ?>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="px-3 py-1 rounded-md bg-bacal-700 text-white text-xs font-semibold">Crear</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
            <?php foreach ($items as $it): ?>
            <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
                <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-2.5 flex items-center gap-2 group">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                          style="background-color: <?= e($it['color']) ?>1f; color: <?= e($it['color']) ?>; border: 1px solid <?= e($it['color']) ?>40">
                        <?= e($it['nombre']) ?>
                    </span>
                    <div class="flex-1"></div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                        <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <form method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="toggle">
                            <input type="hidden" name="tabla" value="<?= e($tabla === 'tipos_trabajo' ? 'tipos' : $tabla) ?>">
                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                                <i data-lucide="power" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-3 bg-zinc-50">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="editar">
                        <input type="hidden" name="tabla" value="<?= e($tabla === 'tipos_trabajo' ? 'tipos' : $tabla) ?>">
                        <input type="hidden" name="id" value="<?= $it['id'] ?>">
                        <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required
                               class="px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <?= render_color_picker('color', $it['color']) ?>
                        <div class="md:col-span-2 flex justify-end gap-1">
                            <button type="button" @click="editandoId = null" class="px-2 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                            <button type="submit" class="px-2 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php return ob_get_clean();
}

function render_lista_severidades(array $items): string {
    ob_start(); ?>
    <div x-data="{ editandoId: null }" class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
        <?php foreach ($items as $it): ?>
        <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
            <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-3 flex items-center gap-3 group">
                <div class="w-8 h-8 rounded-md flex items-center justify-center font-bold text-white text-xs"
                     style="background-color: <?= e($it['color']) ?>">
                    <?= $it['nivel'] ?>
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-sm text-zinc-900"><?= e($it['nombre']) ?></div>
                    <div class="text-xs text-zinc-500">
                        Nivel <?= $it['nivel'] ?> · SLA <?= $it['sla_horas'] ? $it['sla_horas'] . 'h' : 'sin SLA' ?>
                        <?php if ($it['descripcion']): ?> · <?= e($it['descripcion']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                    <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-3 bg-zinc-50">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="editar">
                    <input type="hidden" name="tabla" value="severidades">
                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre</label>
                        <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nivel</label>
                        <input type="number" name="nivel" value="<?= $it['nivel'] ?>" min="1" max="9" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">SLA (h)</label>
                        <input type="number" name="sla_horas" value="<?= $it['sla_horas'] ?>" min="0" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Color</label>
                        <?= render_color_picker('color', $it['color']) ?>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Descripción</label>
                        <input type="text" name="descripcion" value="<?= e((string) $it['descripcion']) ?>" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-1">
                        <button type="button" @click="editandoId = null" class="px-3 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                        <button type="submit" class="px-3 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="text-xs text-zinc-500 mt-3 italic">No se pueden crear nuevas severidades desde aquí. Si necesitas agregar más, contacta al desarrollador.</p>
    <?php return ob_get_clean();
}

function render_lista_estados(array $items): string {
    ob_start(); ?>
    <div x-data="{ editandoId: null }" class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
        <?php foreach ($items as $it): ?>
        <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
            <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-3 flex items-center gap-3 group">
                <span class="font-mono text-xs font-bold text-zinc-400 w-6"><?= $it['orden'] ?></span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                      style="background-color: <?= e($it['color']) ?>1f; color: <?= e($it['color']) ?>; border: 1px solid <?= e($it['color']) ?>40">
                    <?= e($it['nombre']) ?>
                </span>
                <?php if ($it['es_inicial']): ?>
                <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">INICIAL</span>
                <?php endif; ?>
                <?php if ($it['es_final']): ?>
                <span class="text-[9px] font-bold text-zinc-700 bg-zinc-100 border border-zinc-300 px-1.5 py-0.5 rounded">FINAL</span>
                <?php endif; ?>
                <div class="flex-1 text-xs text-zinc-500 truncate"><?= e((string) $it['descripcion']) ?></div>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                    <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-3 bg-zinc-50">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="editar">
                    <input type="hidden" name="tabla" value="estados">
                    <input type="hidden" name="id" value="<?= $it['id'] ?>">
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre</label>
                        <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Orden</label>
                        <input type="number" name="orden" value="<?= $it['orden'] ?>" min="1" max="20" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Color</label>
                        <?= render_color_picker('color', $it['color']) ?>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Descripción</label>
                        <input type="text" name="descripcion" value="<?= e((string) $it['descripcion']) ?>" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                    </div>
                    <div class="md:col-span-3 flex items-center gap-4 text-xs">
                        <label class="flex items-center gap-1.5"><input type="checkbox" name="es_inicial" value="1" <?= $it['es_inicial'] ? 'checked' : '' ?>> Es estado inicial</label>
                        <label class="flex items-center gap-1.5"><input type="checkbox" name="es_final" value="1" <?= $it['es_final'] ? 'checked' : '' ?>> Es estado final</label>
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-1">
                        <button type="button" @click="editandoId = null" class="px-3 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                        <button type="submit" class="px-3 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

function render_lista_origenes(array $items): string {
    ob_start(); ?>
    <div x-data="{ editandoId: null, mostrarNuevo: false }">
        <div class="mb-3">
            <button @click="mostrarNuevo = !mostrarNuevo" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="mostrarNuevo ? 'Cancelar' : 'Nuevo origen'"></span>
            </button>
        </div>

        <div x-show="mostrarNuevo" x-cloak class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 mb-4">
            <form method="POST" class="flex gap-3 items-end">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="crear">
                <input type="hidden" name="tabla" value="origenes">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="100"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-md bg-bacal-700 text-white text-xs font-semibold">Crear</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
            <?php foreach ($items as $it): ?>
            <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
                <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-2.5 flex items-center gap-2 group">
                    <i data-lucide="inbox" class="w-4 h-4 text-zinc-400 shrink-0"></i>
                    <span class="flex-1 text-sm text-zinc-800"><?= e($it['nombre']) ?></span>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                        <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <form method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="toggle">
                            <input type="hidden" name="tabla" value="origenes">
                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                                <i data-lucide="power" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-2.5 bg-zinc-50">
                    <form method="POST" class="flex gap-2 items-center">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="editar">
                        <input type="hidden" name="tabla" value="origenes">
                        <input type="hidden" name="id" value="<?= $it['id'] ?>">
                        <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required
                               class="flex-1 px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <button type="button" @click="editandoId = null" class="px-2 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                        <button type="submit" class="px-2 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php return ob_get_clean();
}

function render_lista_medidor_tipos(array $items): string {
    $unidades_comunes = ['kWh', 'm3', 'L', 'GJ', 'kg', 'unidad'];
    ob_start(); ?>
    <div x-data="{ editandoId: null, mostrarNuevo: false }">
        <p class="text-xs text-zinc-500 mb-3">
            Tipos de medidor para el módulo de servicios (luz, agua, gas, diésel…). Cada tipo define su unidad de medida.
        </p>
        <div class="mb-3">
            <button @click="mostrarNuevo = !mostrarNuevo" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="mostrarNuevo ? 'Cancelar' : 'Nuevo tipo de medidor'"></span>
            </button>
        </div>

        <div x-show="mostrarNuevo" x-cloak class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 mb-4">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="crear">
                <input type="hidden" name="tabla" value="medidor_tipos">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="100" placeholder="ej. Luz"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Unidad *</label>
                    <input type="text" name="unidad" required maxlength="20" placeholder="ej. kWh" list="unidades_med"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    <datalist id="unidades_med"><?php foreach ($unidades_comunes as $u): ?><option value="<?= e($u) ?>"><?php endforeach; ?></datalist>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Icono</label>
                    <input type="text" name="icono" maxlength="50" placeholder="ej. zap"
                           class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Color</label>
                    <?= render_color_picker('color', '#6B7280') ?>
                </div>
                <div class="md:col-span-4 flex items-center justify-between">
                    <a href="https://lucide.dev/icons/" target="_blank" rel="noopener" class="text-[10px] text-zinc-400 hover:text-bacal-700 underline">Ver nombres de iconos disponibles ↗</a>
                    <button type="submit" class="px-3 py-1 rounded-md bg-bacal-700 text-white text-xs font-semibold">Crear</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
            <?php foreach ($items as $it): $color = $it['color'] ?: '#6B7280'; ?>
            <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
                <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-3 flex items-center gap-3 group">
                    <div class="w-8 h-8 rounded-md flex items-center justify-center shrink-0"
                         style="background-color: <?= e($color) ?>1f; color: <?= e($color) ?>; border: 1px solid <?= e($color) ?>40">
                        <i data-lucide="<?= e($it['icono'] ?: 'gauge') ?>" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-zinc-900"><?= e($it['nombre']) ?></div>
                        <div class="text-xs text-zinc-500">Unidad: <span class="font-mono"><?= e($it['unidad']) ?></span></div>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                        <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <form method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="toggle">
                            <input type="hidden" name="tabla" value="medidor_tipos">
                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100" title="Activar/desactivar">
                                <i data-lucide="power" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-3 bg-zinc-50">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="editar">
                        <input type="hidden" name="tabla" value="medidor_tipos">
                        <input type="hidden" name="id" value="<?= $it['id'] ?>">
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre</label>
                            <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required
                                   class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Unidad</label>
                            <input type="text" name="unidad" value="<?= e($it['unidad']) ?>" required list="unidades_med_<?= $it['id'] ?>"
                                   class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                            <datalist id="unidades_med_<?= $it['id'] ?>"><?php foreach ($unidades_comunes as $u): ?><option value="<?= e($u) ?>"><?php endforeach; ?></datalist>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Icono</label>
                            <input type="text" name="icono" value="<?= e((string) $it['icono']) ?>"
                                   class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Color</label>
                            <?= render_color_picker('color', $it['color'] ?: '#6B7280') ?>
                        </div>
                        <div class="md:col-span-4 flex justify-end gap-1">
                            <button type="button" @click="editandoId = null" class="px-3 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                            <button type="submit" class="px-3 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php return ob_get_clean();
}

function render_lista_estaciones(array $items): string {
    ob_start(); ?>
    <div x-data="{ editandoId: null, mostrarNuevo: false }">
        <div class="mb-3">
            <button @click="mostrarNuevo = !mostrarNuevo" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> <span x-text="mostrarNuevo ? 'Cancelar' : 'Nueva estación'"></span>
            </button>
        </div>
        <div x-show="mostrarNuevo" x-cloak class="bg-white rounded-xl border border-zinc-200 shadow-sm p-4 mb-4">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="crear">
                <input type="hidden" name="tabla" value="estaciones">
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Nombre *</label>
                    <input type="text" name="nombre" required maxlength="120" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-zinc-600 mb-1 uppercase">Dirección</label>
                    <input type="text" name="direccion" maxlength="255" class="w-full px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div class="md:col-span-2 text-right">
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-bacal-700 text-white text-xs font-semibold">Crear estación</button>
                </div>
            </form>
        </div>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm divide-y divide-zinc-100">
            <?php if (empty($items)): ?>
            <div class="px-4 py-8 text-center text-sm text-zinc-400">Aún no hay estaciones registradas.</div>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
            <div class="<?= !$it['activo'] ? 'opacity-50' : '' ?>">
                <div x-show="editandoId !== <?= $it['id'] ?>" class="px-4 py-2.5 flex items-center gap-2 group">
                    <i data-lucide="fuel" class="w-4 h-4 text-zinc-400 shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-zinc-800"><?= e($it['nombre']) ?></div>
                        <?php if (!empty($it['direccion'])): ?><div class="text-xs text-zinc-500"><?= e($it['direccion']) ?></div><?php endif; ?>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100">
                        <button @click="editandoId = <?= $it['id'] ?>" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                        <form method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="op" value="toggle">
                            <input type="hidden" name="tabla" value="estaciones">
                            <input type="hidden" name="id" value="<?= $it['id'] ?>">
                            <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100"><i data-lucide="power" class="w-4 h-4"></i></button>
                        </form>
                    </div>
                </div>
                <div x-show="editandoId === <?= $it['id'] ?>" x-cloak class="px-4 py-2.5 bg-zinc-50">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2 items-end">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="editar">
                        <input type="hidden" name="tabla" value="estaciones">
                        <input type="hidden" name="id" value="<?= $it['id'] ?>">
                        <input type="text" name="nombre" value="<?= e($it['nombre']) ?>" required placeholder="Nombre" class="px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <input type="text" name="direccion" value="<?= e($it['direccion'] ?? '') ?>" placeholder="Dirección" class="px-3 py-1.5 rounded-md border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        <div class="md:col-span-2 flex justify-end gap-2">
                            <button type="button" @click="editandoId = null" class="px-2 py-1 rounded border border-zinc-300 text-xs">Cancelar</button>
                            <button type="submit" class="px-2 py-1 rounded bg-bacal-700 text-white text-xs font-semibold">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php return ob_get_clean();
}
?>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
