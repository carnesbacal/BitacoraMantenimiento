<?php
/**
 * ============================================================================
 * admin/anuncios.php - CRUD de anuncios para el tablero
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/comunicacion_helpers.php';

$u = usuario_actual();

if (!puede_administrar_anuncios()) {
    flash_set('error', 'Solo administradores pueden gestionar anuncios.');
    header('Location: ' . url('dashboard.php'));
    exit;
}

$accion = (string) input('accion', 'listar');
$id     = (int) input('id', 0);

$anuncio_edit = null;
if (in_array($accion, ['editar','toggle','eliminar'], true) && $id > 0) {
    $anuncio_edit = db_one("SELECT * FROM anuncios WHERE id = :id", ['id' => $id]);
    if (!$anuncio_edit) {
        flash_set('error', 'Anuncio no encontrado.');
        header('Location: ' . url('admin/anuncios.php'));
        exit;
    }
}

$errores = [];

// ----------------------------------------------------------------------------
// Procesar POST
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token inválido.';
    } else {
        $op = (string) input('op', '');

        try {
            if ($op === 'crear' || $op === 'editar') {
                $datos = [
                    'titulo'       => trim((string) input('titulo', '')),
                    'contenido'    => trim((string) input('contenido', '')),
                    'tipo'         => (string) input('tipo', 'info'),
                    'icono'        => (string) input('icono', 'megaphone'),
                    'sucursal_id'  => (int) input('sucursal_id', 0) ?: null,
                    'rol_id'       => (int) input('rol_id', 0) ?: null,
                    'fecha_inicio' => (string) input('fecha_inicio', date('Y-m-d')),
                    'fecha_fin'    => trim((string) input('fecha_fin', '')) ?: null,
                    'fijado'       => input('fijado') ? 1 : 0,
                ];

                // Validaciones
                if ($datos['titulo'] === '') $errores[] = 'El título es obligatorio.';
                if ($datos['contenido'] === '') $errores[] = 'El contenido es obligatorio.';
                if (!isset(ANUNCIO_TIPOS[$datos['tipo']])) $datos['tipo'] = 'info';
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_inicio'])) $errores[] = 'Fecha de inicio inválida.';
                if ($datos['fecha_fin'] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_fin'])) $errores[] = 'Fecha de fin inválida.';
                if ($datos['fecha_fin'] && $datos['fecha_fin'] < $datos['fecha_inicio']) {
                    $errores[] = 'La fecha de fin no puede ser anterior a la de inicio.';
                }

                if (empty($errores)) {
                    if ($op === 'crear') {
                        $datos['activo'] = 1;
                        $datos['creado_por_id'] = $u['id'];
                        $cols = implode(', ', array_keys($datos));
                        $params = ':' . implode(', :', array_keys($datos));
                        db_exec("INSERT INTO anuncios ($cols) VALUES ($params)", $datos);
                        $new_id = db_last_id();
                        registrar_auditoria('crear_anuncio', 'anuncios', $new_id, "Anuncio: {$datos['titulo']}");
                        flash_set('success', "Anuncio publicado.");
                    } else {
                        $sets = [];
                        foreach (array_keys($datos) as $k) $sets[] = "$k = :$k";
                        $datos['id'] = $anuncio_edit['id'];
                        db_exec("UPDATE anuncios SET " . implode(', ', $sets) . " WHERE id = :id", $datos);
                        registrar_auditoria('editar_anuncio', 'anuncios', $anuncio_edit['id'], "Anuncio: {$datos['titulo']}");
                        flash_set('success', 'Anuncio actualizado.');
                    }
                    header('Location: ' . url('admin/anuncios.php'));
                    exit;
                }
            } elseif ($op === 'toggle' && $anuncio_edit) {
                $nuevo = (int) $anuncio_edit['activo'] === 1 ? 0 : 1;
                db_exec("UPDATE anuncios SET activo = :a WHERE id = :id",
                    ['a' => $nuevo, 'id' => $anuncio_edit['id']]);
                flash_set('success', "Anuncio " . ($nuevo ? 'activado' : 'desactivado') . ".");
                header('Location: ' . url('admin/anuncios.php'));
                exit;
            } elseif ($op === 'eliminar' && $anuncio_edit) {
                db_exec("DELETE FROM anuncios WHERE id = :id", ['id' => $anuncio_edit['id']]);
                registrar_auditoria('eliminar_anuncio', 'anuncios', (int) $anuncio_edit['id'], "Anuncio: {$anuncio_edit['titulo']}");
                flash_set('success', 'Anuncio eliminado.');
                header('Location: ' . url('admin/anuncios.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Catálogos
$sucursales = db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
$roles_lista = db_all("SELECT id, nombre FROM roles WHERE activo=1 ORDER BY id");

$titulo_pagina = 'Anuncios';
$pagina_activa = 'admin_anuncios';
require_once __DIR__ . '/../config/header.php';

// ============================================================================
// VISTA: FORMULARIO
// ============================================================================
if ($accion === 'nuevo' || ($accion === 'editar' && $anuncio_edit)):
    $es_edicion = ($accion === 'editar');
    $a = $anuncio_edit;
?>
<div class="max-w-3xl mx-auto animate-fade-in">

    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('admin/anuncios.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900">
                <?= $es_edicion ? 'Editar anuncio' : 'Nuevo anuncio' ?>
            </h2>
            <p class="text-xs text-zinc-500">Aparecerá en el dashboard de los usuarios destinatarios.</p>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs">
            <?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="<?= $es_edicion ? 'editar' : 'crear' ?>">

        <!-- Contenido -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="megaphone" class="w-4 h-4 text-bacal-700"></i> Contenido del anuncio
            </h3>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Título *</label>
                <input type="text" name="titulo" required maxlength="200"
                       value="<?= e($es_edicion ? $a['titulo'] : (string) input('titulo', '')) ?>"
                       placeholder="ej. Mantenimiento programado el viernes, Nuevas políticas de cobro"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Mensaje *</label>
                <textarea name="contenido" required rows="5"
                          placeholder="Escribe el contenido del anuncio. Puedes usar varias líneas."
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"><?= e($es_edicion ? $a['contenido'] : (string) input('contenido', '')) ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase tracking-wide">Tipo</label>
                <?php $tipo_actual = $es_edicion ? $a['tipo'] : (string) input('tipo', 'info'); ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2" x-data="{ tipoSel: '<?= e($tipo_actual) ?>' }">
                    <?php foreach (ANUNCIO_TIPOS as $key => $cfg): ?>
                    <label class="cursor-pointer select-none">
                        <input type="radio" name="tipo" value="<?= $key ?>" class="sr-only" x-model="tipoSel"
                               <?= $tipo_actual === $key ? 'checked' : '' ?>>
                        <div class="p-3 rounded-lg border-2 text-center transition-all"
                             :style="tipoSel === '<?= $key ?>' ? 'border-color: <?= $cfg['color'] ?>; background-color: <?= $cfg['color'] ?>15' : 'border-color: #e4e4e7; background-color: transparent'">
                            <i data-lucide="<?= e($cfg['icono']) ?>" class="w-5 h-5 mx-auto mb-1" style="color: <?= e($cfg['color']) ?>"></i>
                            <div class="text-xs font-semibold" style="color: <?= e($cfg['color']) ?>"><?= e($cfg['nombre']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Vigencia -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-bacal-700"></i> Vigencia
            </h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Desde *</label>
                    <input type="date" name="fecha_inicio" required
                           value="<?= e($es_edicion ? $a['fecha_inicio'] : date('Y-m-d')) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Hasta (opcional)</label>
                    <input type="date" name="fecha_fin"
                           value="<?= e($es_edicion ? (string) $a['fecha_fin'] : '') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    <p class="text-[10px] text-zinc-500 mt-1">Vacío = sin fecha de expiración</p>
                </div>
            </div>

            <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-zinc-100">
                <input type="checkbox" name="fijado" value="1" <?= $es_edicion && $a['fijado'] ? 'checked' : '' ?>
                       class="rounded text-bacal-700 focus:ring-bacal-500">
                <span class="text-sm text-zinc-700">
                    📌 <strong>Fijar arriba</strong>
                    <span class="text-[10px] text-zinc-500">(siempre visible, no se oculta al cerrar)</span>
                </span>
            </label>
        </div>

        <!-- Audiencia -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-bacal-700"></i> Audiencia
            </h3>
            <p class="text-xs text-zinc-500">Deja vacío para mostrar a TODOS. Especifica solo si quieres limitarlo.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Solo para sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">— Todas las sucursales —</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $es_edicion && (int) $a['sucursal_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Solo para rol</label>
                    <select name="rol_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">— Todos los roles —</option>
                        <?php foreach ($roles_lista as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $es_edicion && (int) $a['rol_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= e($r['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="<?= url('admin/anuncios.php') ?>" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
                <i data-lucide="<?= $es_edicion ? 'check' : 'send' ?>" class="w-4 h-4"></i>
                <?= $es_edicion ? 'Guardar cambios' : 'Publicar anuncio' ?>
            </button>
        </div>
    </form>
</div>

<?php
// ============================================================================
// VISTA: LISTADO
// ============================================================================
else:
    $anuncios = db_all(
        "SELECT a.*, u.nombre_completo creado_por_nombre,
                s.nombre sucursal_nombre, r.nombre rol_nombre,
                (SELECT COUNT(*) FROM anuncios_lecturas WHERE anuncio_id = a.id) AS lecturas
         FROM anuncios a
         LEFT JOIN usuarios u ON a.creado_por_id = u.id
         LEFT JOIN sucursales s ON a.sucursal_id = s.id
         LEFT JOIN roles r ON a.rol_id = r.id
         ORDER BY a.activo DESC, a.fijado DESC, a.creado_en DESC"
    );
?>

<?php render_admin_header(
    'Tablero de anuncios',
    'Publica avisos generales para los usuarios del sistema. ' . count($anuncios) . ' anuncio(s).',
    url('admin/anuncios.php?accion=nuevo'),
    'Nuevo anuncio'
); ?>

<?php if (empty($anuncios)): ?>
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-12 text-center">
    <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
        <i data-lucide="megaphone" class="w-8 h-8 text-zinc-400"></i>
    </div>
    <p class="text-sm font-medium text-zinc-700 mb-1">Aún no hay anuncios</p>
    <p class="text-xs text-zinc-500 mb-4">Crea anuncios para comunicar avisos generales al equipo.</p>
    <a href="<?= url('admin/anuncios.php?accion=nuevo') ?>"
       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
        <i data-lucide="plus" class="w-4 h-4"></i> Crear primero
    </a>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($anuncios as $a):
        $cfg = ANUNCIO_TIPOS[$a['tipo']] ?? ANUNCIO_TIPOS['info'];
        $vigente = $a['activo'] && $a['fecha_inicio'] <= date('Y-m-d') &&
                   ($a['fecha_fin'] === null || $a['fecha_fin'] >= date('Y-m-d'));
    ?>
    <div class="bg-white rounded-xl border <?= !$vigente ? 'border-zinc-200 opacity-60' : 'border-zinc-200' ?> shadow-sm p-5">
        <div class="flex items-start gap-3">
            <!-- Ícono -->
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background-color: <?= e($cfg['color']) ?>15">
                <i data-lucide="<?= e($cfg['icono']) ?>" class="w-5 h-5" style="color: <?= e($cfg['color']) ?>"></i>
            </div>

            <div class="flex-1 min-w-0">
                <!-- Header con badges -->
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <h3 class="font-display text-base font-bold text-zinc-900"><?= e($a['titulo']) ?></h3>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
                          style="color: <?= e($cfg['color']) ?>; background-color: <?= e($cfg['color']) ?>15">
                        <?= e($cfg['nombre']) ?>
                    </span>
                    <?php if ((int) $a['fijado'] === 1): ?>
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">📌 FIJADO</span>
                    <?php endif; ?>
                    <?php if (!$a['activo']): ?>
                    <span class="text-[10px] font-bold text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">INACTIVO</span>
                    <?php elseif (!$vigente): ?>
                    <span class="text-[10px] font-bold text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">FUERA DE VIGENCIA</span>
                    <?php endif; ?>
                </div>

                <!-- Contenido (preview) -->
                <p class="text-sm text-zinc-700 whitespace-pre-wrap line-clamp-3 mb-3"><?= e($a['contenido']) ?></p>

                <!-- Metadatos -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-zinc-500">
                    <span><i data-lucide="calendar" class="w-3 h-3 inline -mt-0.5"></i>
                        <?= e(date('d/M/Y', strtotime($a['fecha_inicio']))) ?>
                        <?php if ($a['fecha_fin']): ?>
                        → <?= e(date('d/M/Y', strtotime($a['fecha_fin']))) ?>
                        <?php else: ?>
                        → sin fin
                        <?php endif; ?>
                    </span>
                    <?php if ($a['sucursal_nombre']): ?>
                    <span><i data-lucide="map-pin" class="w-3 h-3 inline -mt-0.5"></i> <?= e($a['sucursal_nombre']) ?></span>
                    <?php endif; ?>
                    <?php if ($a['rol_nombre']): ?>
                    <span><i data-lucide="shield" class="w-3 h-3 inline -mt-0.5"></i> <?= e($a['rol_nombre']) ?></span>
                    <?php endif; ?>
                    <span><i data-lucide="eye" class="w-3 h-3 inline -mt-0.5"></i> <?= (int) $a['lecturas'] ?> lectura(s)</span>
                    <span class="ml-auto"><?= e(fmt_tiempo_relativo($a['creado_en'])) ?> por <?= e($a['creado_por_nombre'] ?? 'Sistema') ?></span>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-start gap-1 flex-shrink-0">
                <a href="<?= url('admin/anuncios.php?accion=editar&id=' . $a['id']) ?>"
                   class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100" title="Editar">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </a>
                <form method="POST" class="inline-block">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="toggle">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100"
                            title="<?= $a['activo'] ? 'Desactivar' : 'Activar' ?>">
                        <i data-lucide="<?= $a['activo'] ? 'power' : 'power-off' ?>" class="w-4 h-4"></i>
                    </button>
                </form>
                <form method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este anuncio permanentemente?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="op" value="eliminar">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-bacal-50 hover:text-bacal-700" title="Eliminar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
