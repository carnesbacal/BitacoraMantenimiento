<?php
/**
 * ============================================================================
 * api/buscar_global.php
 * ============================================================================
 * Búsqueda global con resultados agrupados por tipo.
 * Busca en: incidencias (folio, título, descripción), equipos (código, nombre),
 *           usuarios, base de conocimiento.
 *
 * Respeta permisos: solo muestra incidencias/equipos de la sucursal del usuario
 * a menos que tenga permiso de ver_todas_sucursales.
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

requerir_login();
header('Content-Type: application/json; charset=utf-8');

$q = trim((string) input('q', ''));
if (mb_strlen($q) < 2) {
    echo json_encode(['ok' => true, 'q' => $q, 'grupos' => []]);
    exit;
}

$u = usuario_actual();
$ver_todas = tiene_permiso('ver_todas_sucursales');
$ver_kb = tiene_permiso('administrar') || (int) ($u['sucursal_id'] ?? 0) > 0; // todos los logueados

$like = '%' . $q . '%';
$resultados = [
    'incidencias'   => [], 'equipos'      => [], 'usuarios'     => [], 'kb'       => [],
    'mantenimientos'=> [], 'refacciones'  => [], 'herramientas' => [], 'medidores'=> [],
    'recordatorios' => [], 'vehiculos'    => [], 'conductores'  => [], 'proveedores' => [],
];

// ============================================================================
// INCIDENCIAS
// ============================================================================
try {
    $where_suc = $ver_todas ? '' : 'AND i.sucursal_id = :sid';
    $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
    if (!$ver_todas) $params['sid'] = (int) $u['sucursal_id'];

    $resultados['incidencias'] = db_all(
        "SELECT i.id, i.folio, i.titulo, i.creado_en, i.archivada,
                est.nombre AS estado_nombre, est.color AS estado_color, est.es_final,
                s.codigo AS sucursal_codigo,
                sv.nombre AS severidad_nombre, sv.color AS severidad_color
         FROM incidencias i
         INNER JOIN estados est ON i.estado_id = est.id
         INNER JOIN sucursales s ON i.sucursal_id = s.id
         INNER JOIN severidades sv ON i.severidad_id = sv.id
         WHERE (i.folio LIKE :q1 OR i.titulo LIKE :q2 OR i.descripcion LIKE :q3)
           $where_suc
         ORDER BY i.archivada ASC, i.creado_en DESC
         LIMIT 8",
        $params
    );
} catch (Throwable $e) {
    // si falla un grupo, no rompemos toda la búsqueda
}

// ============================================================================
// EQUIPOS
// ============================================================================
try {
    $where_suc = $ver_todas ? '' : 'AND e.sucursal_id = :sid';
    $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
    if (!$ver_todas) $params['sid'] = (int) $u['sucursal_id'];

    $resultados['equipos'] = db_all(
        "SELECT e.id, e.codigo_inventario, e.nombre, e.tipo, e.estado_vida,
                s.codigo AS sucursal_codigo,
                a.nombre AS area_nombre
         FROM equipos e
         LEFT JOIN sucursales s ON e.sucursal_id = s.id
         LEFT JOIN areas a ON e.area_id = a.id
         WHERE (e.codigo_inventario LIKE :q1 OR e.nombre LIKE :q2 OR e.numero_serie LIKE :q3)
           AND e.activo = 1
           $where_suc
         ORDER BY e.nombre ASC
         LIMIT 6",
        $params
    );
} catch (Throwable $e) {}

// ============================================================================
// USUARIOS (solo admin)
// ============================================================================
if (tiene_permiso('administrar')) {
    try {
        $resultados['usuarios'] = db_all(
            "SELECT u.id, u.usuario, u.nombre_completo, u.email, u.avatar_url, u.activo,
                    r.nombre AS rol_nombre,
                    s.codigo AS sucursal_codigo
             FROM usuarios u
             LEFT JOIN roles r ON u.rol_id = r.id
             LEFT JOIN sucursales s ON u.sucursal_id = s.id
             WHERE (u.usuario LIKE :q1 OR u.nombre_completo LIKE :q2 OR u.email LIKE :q3)
             ORDER BY u.activo DESC, u.nombre_completo ASC
             LIMIT 5",
            ['q1' => $like, 'q2' => $like, 'q3' => $like]
        );
    } catch (Throwable $e) {}
}

// ============================================================================
// BASE DE CONOCIMIENTO
// ============================================================================
if ($ver_kb) {
    try {
        // Verificar que la tabla exista (puede no estar en algunas instalaciones)
        $tabla = db_one("SHOW TABLES LIKE 'kb_articulos'");
        if ($tabla) {
            $resultados['kb'] = db_all(
                "SELECT id, titulo, resumen, slug
                 FROM kb_articulos
                 WHERE activo = 1 AND (titulo LIKE :q1 OR resumen LIKE :q2 OR contenido LIKE :q3)
                 ORDER BY actualizado_en DESC
                 LIMIT 5",
                ['q1' => $like, 'q2' => $like, 'q3' => $like]
            );
        }
    } catch (Throwable $e) {}
}

// ============================================================================
// VAULT (bóveda) - respetando permisos del usuario
// ============================================================================
try {
    // Verificar que las tablas del vault existan
    $tabla_vault = db_one("SHOW TABLES LIKE 'vault_entradas'");
    if ($tabla_vault) {
        require_once __DIR__ . '/../config/vault_helpers.php';

        $perm = vault_clausula_permisos($u);
        $params_v = array_merge(
            ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like],
            $perm['params']
        );

        $resultados['vault'] = db_all(
            "SELECT e.id, e.nombre, e.usuario, e.sensibilidad,
                    c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color,
                    c.familia
             FROM vault_entradas e
             INNER JOIN vault_categorias c ON e.categoria_id = c.id
             WHERE e.activo = 1
               AND (e.nombre LIKE :q1 OR e.usuario LIKE :q2 OR e.tags LIKE :q3 OR e.notas LIKE :q4)
               {$perm['sql']}
             ORDER BY e.actualizado_en DESC
             LIMIT 6",
            $params_v
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// MANTENIMIENTOS (preventivos/correctivos sobre equipos)
// ============================================================================
try {
    $where_suc_m = $ver_todas ? '' : 'AND e.sucursal_id = :sid';
    $params_m = ['q1' => $like, 'q2' => $like, 'q3' => $like];
    if (!$ver_todas) $params_m['sid'] = (int) $u['sucursal_id'];

    $resultados['mantenimientos'] = db_all(
        "SELECT m.id, m.titulo, m.descripcion, m.estado, m.fecha_programada,
                e.codigo_inventario equipo_codigo, e.nombre equipo_nombre,
                s.codigo sucursal_codigo
         FROM mantenimientos m
         INNER JOIN equipos e ON m.equipo_id = e.id
         INNER JOIN sucursales s ON e.sucursal_id = s.id
         WHERE (m.titulo LIKE :q1 OR m.descripcion LIKE :q2 OR e.nombre LIKE :q3)
           $where_suc_m
         ORDER BY m.fecha_programada DESC
         LIMIT 6",
        $params_m
    );
} catch (Throwable $e) {}

// ============================================================================
// REFACCIONES
// ============================================================================
try {
    $tabla_ref = db_one("SHOW TABLES LIKE 'refacciones'");
    if ($tabla_ref) {
        $resultados['refacciones'] = db_all(
            "SELECT r.id, r.codigo, r.nombre, r.descripcion, r.marca, r.numero_parte
             FROM refacciones r
             WHERE (r.codigo LIKE :q1 OR r.nombre LIKE :q2 OR r.numero_parte LIKE :q3 OR r.descripcion LIKE :q4)
             ORDER BY r.nombre ASC
             LIMIT 6",
            ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// HERRAMIENTAS
// ============================================================================
try {
    $tabla_her = db_one("SHOW TABLES LIKE 'herramientas'");
    if ($tabla_her) {
        $where_suc_h = $ver_todas ? '' : 'AND h.sucursal_id = :sid';
        $params_h = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        if (!$ver_todas) $params_h['sid'] = (int) $u['sucursal_id'];

        $resultados['herramientas'] = db_all(
            "SELECT h.id, h.codigo, h.nombre, h.marca, h.numero_serie, h.estado,
                    s.codigo sucursal_codigo
             FROM herramientas h
             LEFT JOIN sucursales s ON h.sucursal_id = s.id
             WHERE (h.codigo LIKE :q1 OR h.nombre LIKE :q2 OR h.numero_serie LIKE :q3 OR h.marca LIKE :q4)
               AND h.estado != 'baja'
               $where_suc_h
             ORDER BY h.nombre ASC
             LIMIT 6",
            $params_h
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// MEDIDORES
// ============================================================================
try {
    $tabla_med = db_one("SHOW TABLES LIKE 'medidores'");
    if ($tabla_med) {
        $where_suc_me = $ver_todas ? '' : 'AND m.sucursal_id = :sid';
        $params_me = ['q1' => $like, 'q2' => $like];
        if (!$ver_todas) $params_me['sid'] = (int) $u['sucursal_id'];

        $resultados['medidores'] = db_all(
            "SELECT m.id, m.nombre, m.numero_serie, m.activo,
                    s.codigo sucursal_codigo
             FROM medidores m
             LEFT JOIN sucursales s ON m.sucursal_id = s.id
             WHERE (m.nombre LIKE :q1 OR m.numero_serie LIKE :q2)
               $where_suc_me
             ORDER BY m.nombre ASC
             LIMIT 5",
            $params_me
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// RECORDATORIOS (solo los del usuario actual, o admin ve todos)
// ============================================================================
try {
    $where_rec = tiene_permiso('administrar') ? '' : 'AND r.usuario_id = :uid';
    $params_rec = ['q1' => $like, 'q2' => $like];
    if (!tiene_permiso('administrar')) $params_rec['uid'] = (int) $u['id'];

    $resultados['recordatorios'] = db_all(
        "SELECT r.id, r.titulo, r.mensaje, r.fecha_envio, r.enviado
         FROM recordatorios r
         WHERE (r.titulo LIKE :q1 OR r.mensaje LIKE :q2)
           $where_rec
         ORDER BY r.enviado ASC, r.fecha_envio ASC
         LIMIT 5",
        $params_rec
    );
} catch (Throwable $e) {}

// ============================================================================
// FLOTILLA — VEHÍCULOS
// ============================================================================
try {
    $tabla_flot = db_one("SHOW TABLES LIKE 'flotilla_vehiculos'");
    if ($tabla_flot) {
        $resultados['vehiculos'] = db_all(
            "SELECT v.id, v.placas, v.alias, v.marca, v.modelo, v.anio, v.activo,
                    t.nombre AS tipo_nombre
             FROM flotilla_vehiculos v
             LEFT JOIN flotilla_tipos_vehiculo t ON v.tipo_id = t.id
             WHERE (v.placas LIKE :q1 OR v.alias LIKE :q2 OR v.marca LIKE :q3 OR v.modelo LIKE :q4)
             ORDER BY v.activo DESC, v.placas ASC
             LIMIT 6",
            ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// FLOTILLA — CONDUCTORES
// ============================================================================
try {
    $tabla_cond = db_one("SHOW TABLES LIKE 'flotilla_conductores'");
    if ($tabla_cond) {
        $resultados['conductores'] = db_all(
            "SELECT c.id, c.nombre_completo, c.licencia_numero, c.licencia_tipo,
                    c.telefono, c.activo
             FROM flotilla_conductores c
             WHERE (c.nombre_completo LIKE :q1 OR c.licencia_numero LIKE :q2)
             ORDER BY c.activo DESC, c.nombre_completo ASC
             LIMIT 5",
            ['q1' => $like, 'q2' => $like]
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// PROVEEDORES
// ============================================================================
try {
    $tabla_prov = db_one("SHOW TABLES LIKE 'proveedores'");
    if ($tabla_prov) {
        $resultados['proveedores'] = db_all(
            "SELECT p.id, p.nombre, p.activo
             FROM proveedores p
             WHERE p.nombre LIKE :q1
             ORDER BY p.activo DESC, p.nombre ASC
             LIMIT 5",
            ['q1' => $like]
        );
    }
} catch (Throwable $e) {}

// ============================================================================
// Construir respuesta agrupada
// ============================================================================
$grupos = [];

if (!empty($resultados['incidencias'])) {
    $items = [];
    foreach ($resultados['incidencias'] as $r) {
        $items[] = [
            'tipo' => 'incidencia',
            'titulo' => $r['folio'] . ' · ' . $r['titulo'],
            'subtitulo' => $r['estado_nombre'] . ' · ' . $r['sucursal_codigo'] . ' · ' . fmt_tiempo_relativo($r['creado_en']) .
                          ((int) $r['archivada'] === 1 ? ' · 📦 Archivada' : ''),
            'badge' => $r['severidad_nombre'],
            'badge_color' => $r['severidad_color'],
            'url' => url_relativa('incidencia_ver.php?id=' . $r['id']),
            'icono' => 'alert-circle',
        ];
    }
    $grupos[] = ['nombre' => 'Incidencias', 'icono' => 'clipboard-list', 'items' => $items];
}

if (!empty($resultados['equipos'])) {
    $items = [];
    foreach ($resultados['equipos'] as $r) {
        $color_estado = match ($r['estado_vida']) {
            'en_uso' => '#16A34A',
            'en_mantenimiento' => '#F59E0B',
            'baja' => '#71717a',
            default => '#0EA5E9',
        };
        $items[] = [
            'tipo' => 'equipo',
            'titulo' => $r['codigo_inventario'] . ' · ' . $r['nombre'],
            'subtitulo' => ($r['tipo'] ?: 'Equipo') . ' · ' . ($r['sucursal_codigo'] ?? '?') .
                          ($r['area_nombre'] ? ' · ' . $r['area_nombre'] : ''),
            'badge' => $r['estado_vida'],
            'badge_color' => $color_estado,
            'url' => url_relativa('equipo_ver.php?id=' . $r['id']),
            'icono' => 'monitor',
        ];
    }
    $grupos[] = ['nombre' => 'Equipos', 'icono' => 'monitor', 'items' => $items];
}

if (!empty($resultados['usuarios'])) {
    $items = [];
    foreach ($resultados['usuarios'] as $r) {
        $items[] = [
            'tipo' => 'usuario',
            'titulo' => $r['nombre_completo'],
            'subtitulo' => '@' . $r['usuario'] . ' · ' . ($r['rol_nombre'] ?? '?') .
                          ($r['sucursal_codigo'] ? ' · ' . $r['sucursal_codigo'] : ''),
            'badge' => (int) $r['activo'] === 1 ? null : 'INACTIVO',
            'badge_color' => '#71717a',
            'url' => url_relativa('admin/usuarios.php?accion=editar&id=' . $r['id']),
            'icono' => 'user',
        ];
    }
    $grupos[] = ['nombre' => 'Usuarios', 'icono' => 'users', 'items' => $items];
}

if (!empty($resultados['kb'])) {
    $items = [];
    foreach ($resultados['kb'] as $r) {
        $items[] = [
            'tipo' => 'kb',
            'titulo' => $r['titulo'],
            'subtitulo' => $r['resumen'] ?: 'Artículo de base de conocimiento',
            'badge' => null,
            'badge_color' => null,
            'url' => url_relativa('kb_articulo.php?slug=' . urlencode($r['slug'])),
            'icono' => 'book-open',
        ];
    }
    $grupos[] = ['nombre' => 'Base de conocimiento', 'icono' => 'book-open', 'items' => $items];
}

if (!empty($resultados['mantenimientos'])) {
    $estado_color = [
        'programado'  => '#0EA5E9',
        'proximo'     => '#F59E0B',
        'en_progreso' => '#8B5CF6',
        'completado'  => '#16A34A',
        'cancelado'   => '#71717a',
        'vencido'     => '#DC2626',
    ];
    $items = [];
    foreach ($resultados['mantenimientos'] as $r) {
        $color = $estado_color[$r['estado']] ?? '#71717a';
        $items[] = [
            'tipo'        => 'mantenimiento',
            'titulo'      => $r['titulo'],
            'subtitulo'   => ($r['equipo_codigo'] ? $r['equipo_codigo'] . ' · ' : '') .
                             $r['equipo_nombre'] . ' · ' . $r['sucursal_codigo'] .
                             ' · ' . fmt_fecha($r['fecha_programada']),
            'badge'       => $r['estado'],
            'badge_color' => $color,
            'url'         => url_relativa('mantenimiento_ver.php?id=' . $r['id']),
            'icono'       => 'wrench',
        ];
    }
    $grupos[] = ['nombre' => 'Mantenimientos', 'icono' => 'wrench', 'items' => $items];
}

if (!empty($resultados['refacciones'])) {
    $items = [];
    foreach ($resultados['refacciones'] as $r) {
        $sub = [];
        if ($r['marca'])       $sub[] = $r['marca'];
        if ($r['numero_parte']) $sub[] = 'P/N: ' . $r['numero_parte'];
        if ($r['descripcion']) $sub[] = mb_substr($r['descripcion'], 0, 60) . (mb_strlen($r['descripcion']) > 60 ? '…' : '');
        $items[] = [
            'tipo'        => 'refaccion',
            'titulo'      => $r['codigo'] . ' · ' . $r['nombre'],
            'subtitulo'   => $sub ? implode(' · ', $sub) : 'Refacción',
            'badge'       => null,
            'badge_color' => null,
            'url'         => url_relativa('refaccion_ver.php?id=' . $r['id']),
            'icono'       => 'package',
        ];
    }
    $grupos[] = ['nombre' => 'Refacciones', 'icono' => 'package', 'items' => $items];
}

if (!empty($resultados['herramientas'])) {
    $estado_color_h = [
        'disponible'    => '#16A34A',
        'prestada'      => '#F59E0B',
        'en_reparacion' => '#8B5CF6',
        'extraviada'    => '#DC2626',
    ];
    $items = [];
    foreach ($resultados['herramientas'] as $r) {
        $color = $estado_color_h[$r['estado']] ?? '#71717a';
        $items[] = [
            'tipo'        => 'herramienta',
            'titulo'      => $r['codigo'] . ' · ' . $r['nombre'],
            'subtitulo'   => ($r['marca'] ? $r['marca'] . ' · ' : '') .
                             ($r['sucursal_codigo'] ?? ''),
            'badge'       => $r['estado'] !== 'disponible' ? $r['estado'] : null,
            'badge_color' => $color,
            'url'         => url_relativa('herramienta_ver.php?id=' . $r['id']),
            'icono'       => 'wrench',
        ];
    }
    $grupos[] = ['nombre' => 'Herramientas', 'icono' => 'hammer', 'items' => $items];
}

if (!empty($resultados['medidores'])) {
    $items = [];
    foreach ($resultados['medidores'] as $r) {
        $items[] = [
            'tipo'        => 'medidor',
            'titulo'      => $r['nombre'],
            'subtitulo'   => ($r['numero_serie'] ? 'S/N: ' . $r['numero_serie'] . ' · ' : '') .
                             ($r['sucursal_codigo'] ?? ''),
            'badge'       => (int) $r['activo'] === 0 ? 'INACTIVO' : null,
            'badge_color' => '#71717a',
            'url'         => url_relativa('medidor_ver.php?id=' . $r['id']),
            'icono'       => 'gauge',
        ];
    }
    $grupos[] = ['nombre' => 'Medidores', 'icono' => 'gauge', 'items' => $items];
}

if (!empty($resultados['recordatorios'])) {
    $items = [];
    foreach ($resultados['recordatorios'] as $r) {
        $items[] = [
            'tipo'        => 'recordatorio',
            'titulo'      => $r['titulo'],
            'subtitulo'   => ($r['mensaje'] ? mb_substr($r['mensaje'], 0, 80) . (mb_strlen($r['mensaje']) > 80 ? '…' : '') : '') .
                             ' · ' . fmt_fecha($r['fecha_envio']),
            'badge'       => (int) $r['enviado'] === 1 ? 'ENVIADO' : 'PENDIENTE',
            'badge_color' => (int) $r['enviado'] === 1 ? '#16A34A' : '#F59E0B',
            'url'         => url_relativa('recordatorios.php'),
            'icono'       => 'bell',
        ];
    }
    $grupos[] = ['nombre' => 'Recordatorios', 'icono' => 'bell', 'items' => $items];
}

if (!empty($resultados['vehiculos'])) {
    $items = [];
    foreach ($resultados['vehiculos'] as $r) {
        $label_activo = (int) $r['activo'] === 0 ? 'BAJA' : null;
        $items[] = [
            'tipo'        => 'vehiculo',
            'titulo'      => strtoupper($r['placas']) . ($r['alias'] ? ' · ' . $r['alias'] : ''),
            'subtitulo'   => trim(($r['marca'] ?? '') . ' ' . ($r['modelo'] ?? '') . ($r['anio'] ? ' ' . $r['anio'] : '')) .
                             ($r['tipo_nombre'] ? ' · ' . $r['tipo_nombre'] : ''),
            'badge'       => $label_activo,
            'badge_color' => '#71717a',
            'url'         => url_relativa('flotilla_vehiculo_ver.php?id=' . $r['id']),
            'icono'       => 'car',
        ];
    }
    $grupos[] = ['nombre' => 'Vehículos', 'icono' => 'car', 'items' => $items];
}

if (!empty($resultados['conductores'])) {
    $items = [];
    foreach ($resultados['conductores'] as $r) {
        $sub = [];
        if ($r['licencia_numero']) $sub[] = 'Lic. ' . $r['licencia_numero'] . ($r['licencia_tipo'] ? ' (' . $r['licencia_tipo'] . ')' : '');
        if ($r['telefono'])        $sub[] = $r['telefono'];
        $items[] = [
            'tipo'        => 'conductor',
            'titulo'      => $r['nombre_completo'],
            'subtitulo'   => $sub ? implode(' · ', $sub) : 'Conductor',
            'badge'       => (int) $r['activo'] === 0 ? 'INACTIVO' : null,
            'badge_color' => '#71717a',
            'url'         => url_relativa('flotilla_conductores.php'),
            'icono'       => 'user-check',
        ];
    }
    $grupos[] = ['nombre' => 'Conductores', 'icono' => 'user-check', 'items' => $items];
}

if (!empty($resultados['proveedores'])) {
    $items = [];
    foreach ($resultados['proveedores'] as $r) {
        $items[] = [
            'tipo'        => 'proveedor',
            'titulo'      => $r['nombre'],
            'subtitulo'   => 'Proveedor',
            'badge'       => (int) $r['activo'] === 0 ? 'INACTIVO' : null,
            'badge_color' => '#71717a',
            'url'         => url_relativa('proveedores.php'),
            'icono'       => 'building-2',
        ];
    }
    $grupos[] = ['nombre' => 'Proveedores', 'icono' => 'building-2', 'items' => $items];
}

if (!empty($resultados['vault'])) {
    $items = [];
    foreach ($resultados['vault'] as $r) {
        $sens_label = match ($r['sensibilidad']) {
            'critica' => 'CRÍTICA',
            'alta' => 'ALTA',
            default => null,
        };
        $sens_color = match ($r['sensibilidad']) {
            'critica' => '#DC2626',
            'alta' => '#F59E0B',
            default => '#71717a',
        };
        $items[] = [
            'tipo' => 'vault',
            'titulo' => $r['nombre'],
            'subtitulo' => $r['categoria_nombre'] . ' · ' . $r['familia'] .
                          (!empty($r['usuario']) ? ' · @' . $r['usuario'] : ''),
            'badge' => $sens_label,
            'badge_color' => $sens_color,
            'url' => url_relativa('vault_entrada.php?id=' . $r['id']),
            'icono' => $r['categoria_icono'] ?: 'shield',
        ];
    }
    $grupos[] = ['nombre' => 'Bóveda', 'icono' => 'shield', 'items' => $items];
}

echo json_encode([
    'ok' => true,
    'q' => $q,
    'grupos' => $grupos,
    'total' => array_sum(array_map(fn($g) => count($g['items']), $grupos)),
], JSON_UNESCAPED_UNICODE);
