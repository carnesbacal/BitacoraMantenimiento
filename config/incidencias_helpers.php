<?php
/**
 * ============================================================================
 * config/incidencias_helpers.php
 * ============================================================================
 * Funciones compartidas para crear, editar y operar incidencias.
 * Incluido por incidencia_nueva.php, incidencia_editar.php e incidencia_ver.php
 * ============================================================================
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// ============================================================================
// Constantes
// ============================================================================
const ADJUNTOS_MAX_TAMANO  = 10 * 1024 * 1024;     // 10 MB por archivo
const ADJUNTOS_TOTAL_MAX   = 50 * 1024 * 1024;     // 50 MB en total por incidencia
const ADJUNTOS_MAX_ARCHIVOS = 10;
const ADJUNTOS_TIPOS_PERMITIDOS = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'text/plain', 'text/csv',
    'application/zip', 'application/x-zip-compressed',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

// ============================================================================
// Carga de catálogos
// ============================================================================

function cat_sucursales(): array {
    return db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre");
}

function cat_areas(): array {
    return db_all("SELECT id, nombre, color FROM areas WHERE activo=1 ORDER BY nombre");
}

function cat_categorias_con_subs(): array {
    $cats = db_all("SELECT id, nombre, color FROM categorias WHERE activo=1 ORDER BY nombre");
    foreach ($cats as &$c) {
        $c['subcategorias'] = db_all(
            "SELECT id, nombre FROM subcategorias WHERE categoria_id=:cid AND activo=1 ORDER BY nombre",
            ['cid' => $c['id']]
        );
    }
    return $cats;
}

function cat_tipos_trabajo(): array {
    return db_all("SELECT id, nombre, color FROM tipos_trabajo WHERE activo=1 ORDER BY nombre");
}

function cat_severidades(): array {
    return db_all("SELECT id, nombre, nivel, color, sla_horas FROM severidades WHERE activo=1 ORDER BY nivel");
}

function cat_estados(): array {
    return db_all("SELECT id, nombre, color, orden, es_inicial, es_final FROM estados WHERE activo=1 ORDER BY orden");
}

function cat_origenes(): array {
    return db_all("SELECT id, nombre FROM origenes_reporte WHERE activo=1 ORDER BY id");
}

function cat_tecnicos(): array {
    return db_all(
        "SELECT u.id, u.nombre_completo
         FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id
         WHERE u.activo=1 AND r.puede_resolver=1
         ORDER BY u.nombre_completo"
    );
}

function cat_equipos_de_sucursal(int $sucursal_id): array {
    return db_all(
        "SELECT id, codigo_inventario, nombre, tipo, marca, modelo
         FROM equipos
         WHERE sucursal_id=:sid AND activo=1
         ORDER BY nombre",
        ['sid' => $sucursal_id]
    );
}

// ============================================================================
// Detección de reincidencias
// ============================================================================

/**
 * Busca incidencias similares en los últimos N días con misma combinación de
 * área/equipo/categoría. Útil para sugerir al usuario que es reincidencia.
 *
 * @param int $area_id          Área de la incidencia actual
 * @param int|null $equipo_id   Equipo involucrado (opcional)
 * @param int|null $categoria_id Categoría técnica (opcional)
 * @param int $excluir_id       ID de incidencia a excluir (si estamos editando)
 * @param int $dias             Ventana de tiempo a considerar
 * @return array  Lista de incidencias candidatas
 */
function buscar_reincidencias_similares(
    int $area_id,
    ?int $equipo_id = null,
    ?int $categoria_id = null,
    int $excluir_id = 0,
    int $dias = 30
): array {
    $sql = "SELECT i.id, i.folio, i.titulo, i.fecha_evento, i.solucion, i.es_reincidencia,
                   est.nombre estado_nombre, est.color estado_color, est.es_final,
                   sev.nombre severidad_nombre, sev.color severidad_color,
                   eq.nombre equipo_nombre, eq.codigo_inventario equipo_codigo
            FROM incidencias i
            INNER JOIN estados est ON i.estado_id = est.id
            INNER JOIN severidades sev ON i.severidad_id = sev.id
            LEFT JOIN equipos eq ON i.equipo_id = eq.id
            WHERE i.fecha_evento >= DATE_SUB(NOW(), INTERVAL :dias DAY)
              AND i.area_id = :aid
              AND i.id <> :ex_id";
    $params = ['dias' => $dias, 'aid' => $area_id, 'ex_id' => $excluir_id];

    if ($equipo_id) {
        $sql .= " AND i.equipo_id = :eid";
        $params['eid'] = $equipo_id;
    }
    if ($categoria_id) {
        $sql .= " AND i.categoria_id = :cid";
        $params['cid'] = $categoria_id;
    }

    $sql .= " ORDER BY i.fecha_evento DESC LIMIT 10";
    return db_all($sql, $params);
}

// ============================================================================
// Guardado seguro de archivos adjuntos
// ============================================================================

/**
 * Detecta el tipo MIME real de un archivo subido SIN depender de la
 * extensión Fileinfo (finfo).
 *
 * Estrategia (en orden):
 *   1. Si la extensión Fileinfo está disponible (XAMPP / servidores que la
 *      tengan activada), la usa porque es la más precisa.
 *   2. Si no, valida imágenes con getimagesize() (lee el contenido real).
 *   3. Para PDF, ZIP y documentos de Office, verifica la "firma" de bytes
 *      del inicio del archivo + la extensión, para no confiar solo en el
 *      nombre que envía el navegador.
 *
 * Devuelve el MIME detectado, o null si no se pudo validar.
 */
function adjunto_detectar_mime(string $tmp, string $name): ?string {
    // 1. Preferir finfo si existe (más fiable; sigue funcionando en local)
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = @finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
    }

    // 2. Imágenes reales: getimagesize lee el contenido, no la extensión
    $info = @getimagesize($tmp);
    if ($info !== false && !empty($info['mime'])) {
        return $info['mime']; // image/jpeg, image/png, image/gif, image/webp...
    }

    // 3. Firma de bytes (magic numbers) + extensión para documentos
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $fh   = @fopen($tmp, 'rb');
    $head = $fh ? (string) fread($fh, 8) : '';
    if ($fh) {
        fclose($fh);
    }

    $es_pdf = (substr($head, 0, 4) === '%PDF');
    $es_zip = (substr($head, 0, 4) === "PK\x03\x04");      // docx, xlsx, zip
    $es_ole = (substr($head, 0, 4) === "\xD0\xCF\x11\xE0"); // doc, xls antiguos

    switch ($ext) {
        case 'pdf':  return $es_pdf ? 'application/pdf' : null;
        case 'zip':  return $es_zip ? 'application/zip' : null;
        case 'docx': return $es_zip ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : null;
        case 'xlsx': return $es_zip ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : null;
        case 'doc':  return $es_ole ? 'application/msword' : null;
        case 'xls':  return $es_ole ? 'application/vnd.ms-excel' : null;
        case 'txt':  return 'text/plain';
        case 'csv':  return 'text/csv';
    }

    return null;
}

/**
 * Procesa el array $_FILES['adjuntos'] (múltiple) y guarda los archivos válidos.
 * Devuelve [exitos, errores] donde exitos es lista de archivos guardados.
 */
function procesar_adjuntos(int $incidencia_id, array $files, int $usuario_id): array {
    $exitos = [];
    $errores = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return [$exitos, $errores];
    }

    $total = count($files['name']);
    if ($total > ADJUNTOS_MAX_ARCHIVOS) {
        $errores[] = "Máximo " . ADJUNTOS_MAX_ARCHIVOS . " archivos por incidencia.";
        return [$exitos, $errores];
    }

    // Crear carpeta del año/mes si no existe
    $dir_base   = __DIR__ . '/../assets/uploads';
    $subcarpeta = date('Y/m');
    $dir_final  = "$dir_base/$subcarpeta";
    if (!is_dir($dir_final)) {
        @mkdir($dir_final, 0755, true);
    }

    for ($i = 0; $i < $total; $i++) {
        $name     = $files['name'][$i] ?? '';
        $tmp      = $files['tmp_name'][$i] ?? '';
        $error    = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $size     = (int) ($files['size'][$i] ?? 0);

        if ($error === UPLOAD_ERR_NO_FILE || $name === '') continue;

        if ($error !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir \"$name\".";
            continue;
        }

        if ($size > ADJUNTOS_MAX_TAMANO) {
            $errores[] = "\"$name\" excede el tamaño máximo (10 MB).";
            continue;
        }

        // Validar tipo real (no confiar en el nombre). Funciona con o sin Fileinfo.
        $mime = adjunto_detectar_mime($tmp, $name);

        if ($mime === null || !in_array($mime, ADJUNTOS_TIPOS_PERMITIDOS, true)) {
            $detalle = $mime ? " ($mime)" : "";
            $errores[] = "\"$name\" tiene un tipo de archivo no permitido$detalle.";
            continue;
        }

        // Generar nombre seguro
        $ext         = pathinfo($name, PATHINFO_EXTENSION);
        $ext         = preg_replace('/[^a-z0-9]/i', '', strtolower($ext));
        $nombre_seg  = bin2hex(random_bytes(16)) . ($ext ? ".$ext" : '');
        $ruta_disco  = "$dir_final/$nombre_seg";
        $ruta_db     = "uploads/$subcarpeta/$nombre_seg";

        if (!move_uploaded_file($tmp, $ruta_disco)) {
            $errores[] = "No se pudo guardar \"$name\".";
            continue;
        }

        // Guardar en BD
        db_exec(
            "INSERT INTO incidencias_adjuntos
             (incidencia_id, nombre_original, nombre_archivo, ruta, tipo_mime, tamano_bytes, subido_por_id)
             VALUES (:iid, :ori, :archivo, :ruta, :mime, :size, :uid)",
            [
                'iid'     => $incidencia_id,
                'ori'     => mb_substr($name, 0, 250),
                'archivo' => $nombre_seg,
                'ruta'    => $ruta_db,
                'mime'    => $mime,
                'size'    => $size,
                'uid'     => $usuario_id,
            ]
        );

        $exitos[] = ['nombre' => $name, 'mime' => $mime, 'size' => $size];
    }

    return [$exitos, $errores];
}

// ============================================================================
// Cálculo de tiempos automáticos
// ============================================================================

/**
 * Calcula y actualiza tiempo_respuesta_min y tiempo_resolucion_min para una
 * incidencia, basándose en las fechas registradas.
 */
function recalcular_tiempos_incidencia(int $incidencia_id): void {
    $row = db_one(
        "SELECT fecha_evento, fecha_atencion, fecha_resolucion, fecha_limite_sla
         FROM incidencias WHERE id = :id",
        ['id' => $incidencia_id]
    );
    if (!$row) return;

    $tr  = null;
    $tres = null;
    $sla = null;

    if ($row['fecha_evento'] && $row['fecha_atencion']) {
        $diff = strtotime($row['fecha_atencion']) - strtotime($row['fecha_evento']);
        $tr   = max(0, (int) round($diff / 60));
    }
    if ($row['fecha_atencion'] && $row['fecha_resolucion']) {
        $diff = strtotime($row['fecha_resolucion']) - strtotime($row['fecha_atencion']);
        $tres = max(0, (int) round($diff / 60));
    }
    if ($row['fecha_resolucion'] && $row['fecha_limite_sla']) {
        $sla = strtotime($row['fecha_resolucion']) <= strtotime($row['fecha_limite_sla']) ? 1 : 0;
    }

    db_exec(
        "UPDATE incidencias
         SET tiempo_respuesta_min = :tr,
             tiempo_resolucion_min = :tres,
             sla_cumplido = :sla
         WHERE id = :id",
        ['tr' => $tr, 'tres' => $tres, 'sla' => $sla, 'id' => $incidencia_id]
    );
}

// ============================================================================
// Registro de historial de cambios
// ============================================================================

function registrar_historial(
    int $incidencia_id,
    int $usuario_id,
    string $accion,
    ?string $campo = null,
    ?string $valor_anterior = null,
    ?string $valor_nuevo = null,
    ?string $descripcion = null
): void {
    db_exec(
        "INSERT INTO incidencias_historial
         (incidencia_id, usuario_id, accion, campo, valor_anterior, valor_nuevo, descripcion)
         VALUES (:iid, :uid, :acc, :campo, :va, :vn, :desc)",
        [
            'iid' => $incidencia_id, 'uid' => $usuario_id,
            'acc' => $accion, 'campo' => $campo,
            'va' => $valor_anterior, 'vn' => $valor_nuevo,
            'desc' => $descripcion,
        ]
    );
}

/**
 * Compara dos snapshots de una incidencia y registra el historial de cambios.
 * $antes y $despues son arrays asociativos con los campos relevantes.
 */
function registrar_diferencias(int $incidencia_id, int $usuario_id, array $antes, array $despues): void {
    $etiquetas = [
        'titulo' => 'Título',
        'descripcion' => 'Descripción',
        'sucursal_id' => 'Sucursal',
        'area_id' => 'Área',
        'categoria_id' => 'Categoría',
        'subcategoria_id' => 'Subcategoría',
        'tipo_trabajo_id' => 'Tipo de trabajo',
        'severidad_id' => 'Severidad',
        'estado_id' => 'Estado',
        'origen_reporte_id' => 'Origen del reporte',
        'equipo_id' => 'Equipo',
        'asignado_a_id' => 'Asignado a',
        'es_reincidencia' => 'Marca de reincidencia',
        'solucion' => 'Solución',
        'recomendaciones' => 'Recomendaciones',
        'causa_raiz' => 'Causa raíz',
        'reportante_nombre' => 'Reportante',
    ];

    foreach ($etiquetas as $campo => $label) {
        $va = $antes[$campo] ?? null;
        $vn = $despues[$campo] ?? null;
        if ((string) $va !== (string) $vn) {
            registrar_historial(
                $incidencia_id, $usuario_id, 'campo_cambiado', $campo,
                (string) $va, (string) $vn,
                "$label modificado"
            );
        }
    }
}

// ============================================================================
// Permisos sobre una incidencia específica
// ============================================================================

/**
 * Verifica si el usuario actual puede ver una incidencia.
 * Reglas:
 *   - Admin y quien ve todas las sucursales: SÍ
 *   - Gerentes/jefes: solo si la sucursal coincide con la suya
 *   - Reportante o asignado de la incidencia: SÍ aunque sea de otra sucursal
 */
function puede_ver_incidencia(array $incidencia): bool {
    $u = usuario_actual();
    if (!$u) return false;
    if (!empty($u['permisos']['ver_todas_sucursales'])) return true;
    if ($u['sucursal_id'] && (int) $u['sucursal_id'] === (int) $incidencia['sucursal_id']) return true;
    if ((int) $u['id'] === (int) $incidencia['reportado_por_id']) return true;
    if (!empty($incidencia['asignado_a_id']) && (int) $u['id'] === (int) $incidencia['asignado_a_id']) return true;
    return false;
}

/**
 * Verifica si el usuario actual puede EDITAR una incidencia.
 * Reglas:
 *   - Admin: SÍ
 *   - Quien puede resolver y tiene acceso a la sucursal: SÍ
 *   - Reportante mientras esté abierta: SÍ (solo ciertos campos, controlado en UI)
 */
function puede_editar_incidencia(array $incidencia): bool {
    $u = usuario_actual();
    if (!$u) return false;
    if (!empty($u['permisos']['administrar'])) return true;
    if (!empty($u['permisos']['resolver']) && puede_ver_incidencia($incidencia)) return true;
    if ((int) $u['id'] === (int) $incidencia['reportado_por_id'] && empty($incidencia['estado_es_final'])) return true;
    return false;
}

// ============================================================================
// Carga completa de una incidencia
// ============================================================================

/**
 * Carga una incidencia con todas sus relaciones (sucursal, área, categorías, etc.)
 */
function cargar_incidencia(int $id): ?array {
    return db_one(
        "SELECT i.*,
                s.nombre sucursal_nombre, s.codigo sucursal_codigo,
                a.nombre area_nombre, a.color area_color,
                c.nombre categoria_nombre, c.color categoria_color,
                sc.nombre subcategoria_nombre,
                tt.nombre tipo_trabajo_nombre, tt.color tipo_trabajo_color,
                sev.nombre severidad_nombre, sev.color severidad_color, sev.nivel severidad_nivel, sev.sla_horas,
                est.nombre estado_nombre, est.color estado_color, est.es_final estado_es_final, est.orden estado_orden,
                eq.codigo_inventario equipo_codigo, eq.nombre equipo_nombre,
                eq.tipo equipo_tipo, eq.marca equipo_marca, eq.modelo equipo_modelo,
                rep.nombre_completo reportado_por_nombre, rep.email reportado_por_email,
                asig.nombre_completo asignado_a_nombre, asig.email asignado_a_email,
                res.nombre_completo resuelto_por_nombre,
                origen.nombre origen_nombre,
                padre.folio incidencia_padre_folio, padre.titulo incidencia_padre_titulo
         FROM incidencias i
         INNER JOIN sucursales s ON i.sucursal_id = s.id
         INNER JOIN areas a ON i.area_id = a.id
         LEFT JOIN categorias c ON i.categoria_id = c.id
         LEFT JOIN subcategorias sc ON i.subcategoria_id = sc.id
         LEFT JOIN tipos_trabajo tt ON i.tipo_trabajo_id = tt.id
         INNER JOIN severidades sev ON i.severidad_id = sev.id
         INNER JOIN estados est ON i.estado_id = est.id
         LEFT JOIN equipos eq ON i.equipo_id = eq.id
         INNER JOIN usuarios rep ON i.reportado_por_id = rep.id
         LEFT JOIN usuarios asig ON i.asignado_a_id = asig.id
         LEFT JOIN usuarios res ON i.resuelto_por_id = res.id
         LEFT JOIN origenes_reporte origen ON i.origen_reporte_id = origen.id
         LEFT JOIN incidencias padre ON i.incidencia_padre_id = padre.id
         WHERE i.id = :id",
        ['id' => $id]
    );
}

function cargar_adjuntos(int $incidencia_id): array {
    return db_all(
        "SELECT a.*, u.nombre_completo subido_por_nombre
         FROM incidencias_adjuntos a
         LEFT JOIN usuarios u ON a.subido_por_id = u.id
         WHERE a.incidencia_id = :iid
         ORDER BY a.subido_en DESC",
        ['iid' => $incidencia_id]
    );
}

function cargar_comentarios(int $incidencia_id): array {
    $comentarios = db_all(
        "SELECT c.*, u.nombre_completo usuario_nombre, u.avatar_url usuario_avatar
         FROM incidencias_comentarios c
         INNER JOIN usuarios u ON c.usuario_id = u.id
         WHERE c.incidencia_id = :iid
         ORDER BY c.creado_en ASC",
        ['iid' => $incidencia_id]
    );

    if (empty($comentarios)) return [];

    // Cargar reacciones de todos los comentarios en una sola query
    $ids = array_column($comentarios, 'id');
    $placeholders = implode(',', array_map(fn($i) => (int) $i, $ids));

    $reacciones = db_all(
        "SELECT comentario_id, emoji, COUNT(*) AS total,
                GROUP_CONCAT(usuario_id) AS usuarios_ids
         FROM comentario_reacciones
         WHERE comentario_id IN ($placeholders)
         GROUP BY comentario_id, emoji"
    );

    // Indexar reacciones por comentario_id
    $reacciones_por_com = [];
    foreach ($reacciones as $r) {
        $cid = (int) $r['comentario_id'];
        if (!isset($reacciones_por_com[$cid])) $reacciones_por_com[$cid] = [];
        $reacciones_por_com[$cid][] = [
            'emoji' => $r['emoji'],
            'total' => (int) $r['total'],
            'usuarios_ids' => array_map('intval', explode(',', $r['usuarios_ids'])),
        ];
    }

    // Adjuntar reacciones a cada comentario
    foreach ($comentarios as &$c) {
        $c['reacciones'] = $reacciones_por_com[(int) $c['id']] ?? [];
    }
    unset($c);

    return $comentarios;
}

function cargar_historial(int $incidencia_id): array {
    return db_all(
        "SELECT h.*, u.nombre_completo usuario_nombre
         FROM incidencias_historial h
         INNER JOIN usuarios u ON h.usuario_id = u.id
         WHERE h.incidencia_id = :iid
         ORDER BY h.creado_en DESC
         LIMIT 50",
        ['iid' => $incidencia_id]
    );
}

function cargar_incidencias_relacionadas(int $incidencia_id, ?int $padre_id = null): array {
    // Hermanas: misma incidencia_padre_id, o que tengan como padre a esta misma
    $sql = "SELECT i.id, i.folio, i.titulo, i.fecha_evento,
                   est.nombre estado_nombre, est.color estado_color, est.es_final
            FROM incidencias i
            INNER JOIN estados est ON i.estado_id = est.id
            WHERE (i.incidencia_padre_id = :id1
                   OR i.id = :id2
                   OR (:padre IS NOT NULL AND (i.id = :padre2 OR i.incidencia_padre_id = :padre3)))
              AND i.id <> :exid
            ORDER BY i.fecha_evento DESC
            LIMIT 20";
    return db_all($sql, [
        'id1' => $incidencia_id,
        'id2' => $padre_id ?? 0,
        'padre' => $padre_id,
        'padre2' => $padre_id ?? 0,
        'padre3' => $padre_id ?? 0,
        'exid' => $incidencia_id,
    ]);
}
