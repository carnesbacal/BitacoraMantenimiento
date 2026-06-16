<?php
/**
 * ============================================================================
 * base_conocimiento.php - Base de conocimiento
 * ============================================================================
 * Permite buscar soluciones aplicadas previamente a problemas similares.
 * Filtra incidencias cerradas con solución registrada y permite buscar por
 * texto, categoría, área o equipo.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

requerir_login();

$u = usuario_actual();
$titulo_pagina = 'Base de conocimiento';
$pagina_activa = 'base_conocimiento';

// Filtros
$q             = trim((string) input('q', ''));
$f_categoria   = (int) input('categoria', 0);
$f_area        = (int) input('area', 0);
$f_tipo        = (int) input('tipo', 0);

// Permisos: filtrar por sucursal si el usuario no ve todas
$where_extras = [];
$params = [];

if (!tiene_permiso('ver_todas_sucursales') && $u['sucursal_id']) {
    $where_extras[] = "i.sucursal_id = :sid";
    $params['sid'] = $u['sucursal_id'];
}

// Solo incidencias con solución registrada
$where_extras[] = "(i.solucion IS NOT NULL AND i.solucion <> '')";

if ($q !== '') {
    $where_extras[] = "(i.titulo LIKE :q1 OR i.descripcion LIKE :q2 OR i.solucion LIKE :q3 OR i.causa_raiz LIKE :q4 OR i.recomendaciones LIKE :q5)";
    $params['q1'] = "%$q%"; $params['q2'] = "%$q%"; $params['q3'] = "%$q%"; $params['q4'] = "%$q%"; $params['q5'] = "%$q%";
}
if ($f_categoria > 0) { $where_extras[] = "i.categoria_id = :cid"; $params['cid'] = $f_categoria; }
if ($f_area > 0)      { $where_extras[] = "i.area_id = :aid"; $params['aid'] = $f_area; }
if ($f_tipo > 0)      { $where_extras[] = "i.tipo_trabajo_id = :tid"; $params['tid'] = $f_tipo; }

$where_sql = !empty($where_extras) ? 'WHERE ' . implode(' AND ', $where_extras) : '';

$resultados = db_all(
    "SELECT i.id, i.folio, i.titulo, i.descripcion, i.solucion, i.causa_raiz, i.recomendaciones,
            i.creado_en, i.fecha_resolucion,
            a.nombre area_nombre, a.color area_color,
            c.nombre cat_nombre, c.color cat_color,
            tt.nombre tipo_nombre, tt.color tipo_color,
            sev.nombre sev_nombre, sev.color sev_color,
            eq.codigo_inventario equipo_codigo, eq.nombre equipo_nombre,
            s.nombre sucursal_nombre,
            usr.nombre_completo resuelto_por_nombre
     FROM incidencias i
     INNER JOIN areas a ON i.area_id = a.id
     LEFT JOIN categorias c ON i.categoria_id = c.id
     LEFT JOIN tipos_trabajo tt ON i.tipo_trabajo_id = tt.id
     INNER JOIN severidades sev ON i.severidad_id = sev.id
     LEFT JOIN equipos eq ON i.equipo_id = eq.id
     INNER JOIN sucursales s ON i.sucursal_id = s.id
     LEFT JOIN usuarios usr ON i.resuelto_por_id = usr.id
     $where_sql
     ORDER BY i.fecha_resolucion DESC, i.creado_en DESC
     LIMIT 50",
    $params
);

// Catálogos para filtros
$categorias = db_all("SELECT id, nombre FROM categorias WHERE activo=1 ORDER BY nombre");
$areas      = db_all("SELECT id, nombre FROM areas WHERE activo=1 ORDER BY nombre");
$tipos      = db_all("SELECT id, nombre FROM tipos_trabajo WHERE activo=1 ORDER BY nombre");

require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2">
            <i data-lucide="book-open" class="w-6 h-6 text-bacal-700"></i>
            <h2 class="font-display text-2xl font-extrabold text-zinc-900">Base de conocimiento</h2>
        </div>
        <p class="text-xs text-zinc-500">
            Busca cómo se resolvieron incidencias similares en el pasado.
            Solo se muestran incidencias con solución registrada.
        </p>
    </div>

    <!-- Búsqueda principal -->
    <form method="GET" class="mb-5">
        <div class="relative">
            <i data-lucide="search" class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400"></i>
            <input type="text" name="q" value="<?= e($q) ?>" autofocus
                   placeholder="Busca por palabras clave, ej: 'cámara de frío no enfría', 'compresor con fuga', 'banda transportadora atascada'..."
                   class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-zinc-200 bg-white text-base focus:outline-none focus:border-bacal-700 focus:ring-2 focus:ring-bacal-100">
        </div>

        <!-- Filtros adicionales -->
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <select name="area" onchange="this.form.submit()"
                    class="px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-xs focus:outline-none focus:border-bacal-700">
                <option value="">Todas las áreas</option>
                <?php foreach ($areas as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $f_area == $a['id'] ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="categoria" onchange="this.form.submit()"
                    class="px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-xs focus:outline-none focus:border-bacal-700">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $f_categoria == $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tipo" onchange="this.form.submit()"
                    class="px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-xs focus:outline-none focus:border-bacal-700">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $f_tipo == $t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($q !== '' || $f_area > 0 || $f_categoria > 0 || $f_tipo > 0): ?>
            <a href="<?= url('base_conocimiento.php') ?>" class="px-3 py-1.5 rounded-lg border border-zinc-300 text-zinc-700 text-xs hover:bg-zinc-50">
                Limpiar
            </a>
            <?php endif; ?>
            <button type="submit" class="ml-auto px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold">Buscar</button>
        </div>
    </form>

    <!-- Resultados -->
    <div class="text-xs text-zinc-500 mb-3">
        <?php if ($q !== ''): ?>
        <?= count($resultados) ?> resultado(s) para <strong class="text-zinc-700">"<?= e($q) ?>"</strong>
        <?php else: ?>
        Mostrando las <?= count($resultados) ?> soluciones más recientes
        <?php endif; ?>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-12 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-3">
            <i data-lucide="search-x" class="w-8 h-8 text-zinc-400"></i>
        </div>
        <p class="text-sm font-medium text-zinc-700 mb-1">Sin resultados</p>
        <p class="text-xs text-zinc-500">No encontramos incidencias resueltas que coincidan con tu búsqueda.</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($resultados as $r): ?>
        <article class="bg-white rounded-xl border border-zinc-200 shadow-sm hover:shadow-md transition-shadow p-5"
                 x-data="{ expandido: false }">
            <!-- Header del artículo -->
            <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <a href="<?= url('incidencia_ver.php?id=' . $r['id']) ?>"
                           class="font-mono text-[10px] font-bold text-zinc-500 hover:text-bacal-700">
                            <?= e($r['folio']) ?>
                        </a>
                        <?= badge($r['area_nombre'], $r['area_color']) ?>
                        <?php if ($r['cat_nombre']): ?>
                        <?= badge($r['cat_nombre'], $r['cat_color']) ?>
                        <?php endif; ?>
                        <?php if ($r['tipo_nombre']): ?>
                        <?= badge($r['tipo_nombre'], $r['tipo_color']) ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-display text-base font-bold text-zinc-900 leading-tight"><?= e($r['titulo']) ?></h3>
                    <div class="text-[11px] text-zinc-500 mt-1 flex items-center gap-2 flex-wrap">
                        <span>Resuelto <?= e(fmt_tiempo_relativo($r['fecha_resolucion'])) ?></span>
                        <?php if ($r['resuelto_por_nombre']): ?>
                        <span>·</span>
                        <span>Por <?= e($r['resuelto_por_nombre']) ?></span>
                        <?php endif; ?>
                        <?php if ($r['equipo_codigo']): ?>
                        <span>·</span>
                        <span class="font-mono"><?= e($r['equipo_codigo']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Descripción del problema -->
            <div class="mb-3">
                <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-1">Problema</div>
                <p class="text-sm text-zinc-700 leading-relaxed line-clamp-2" x-show="!expandido"><?= e($r['descripcion']) ?></p>
                <p class="text-sm text-zinc-700 leading-relaxed whitespace-pre-wrap" x-show="expandido" x-cloak><?= e($r['descripcion']) ?></p>
            </div>

            <!-- Solución -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-2">
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <i data-lucide="wrench" class="w-3 h-3"></i> Solución aplicada
                </div>
                <p class="text-sm text-emerald-900 leading-relaxed line-clamp-3" x-show="!expandido"><?= e($r['solucion']) ?></p>
                <p class="text-sm text-emerald-900 leading-relaxed whitespace-pre-wrap" x-show="expandido" x-cloak><?= e($r['solucion']) ?></p>
            </div>

            <!-- Causa raíz y recomendaciones (solo si expandido) -->
            <div x-show="expandido" x-cloak class="space-y-2">
                <?php if ($r['causa_raiz']): ?>
                <div class="bg-zinc-50 border border-zinc-200 rounded-lg p-3">
                    <div class="text-[10px] font-bold text-zinc-600 uppercase tracking-wider mb-1">Causa raíz</div>
                    <p class="text-xs text-zinc-700 whitespace-pre-wrap"><?= e($r['causa_raiz']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($r['recomendaciones']): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <i data-lucide="lightbulb" class="w-3 h-3"></i> Recomendaciones
                    </div>
                    <p class="text-xs text-amber-900 whitespace-pre-wrap"><?= e($r['recomendaciones']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-3 mt-3 border-t border-zinc-100">
                <button @click="expandido = !expandido"
                        class="text-xs font-semibold text-bacal-700 hover:text-bacal-800 flex items-center gap-1">
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="expandido ? 'rotate-180' : ''"></i>
                    <span x-text="expandido ? 'Ver menos' : 'Ver completo'"></span>
                </button>
                <a href="<?= url('incidencia_ver.php?id=' . $r['id']) ?>"
                   class="text-xs font-semibold text-zinc-500 hover:text-bacal-700 flex items-center gap-1">
                    Ver incidencia original <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
