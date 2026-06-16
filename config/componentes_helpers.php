<?php
/**
 * ============================================================================
 * config/componentes_helpers.php
 * ============================================================================
 * Gestión de componentes/partes de equipo:
 *   - CRUD básico
 *   - Historial de cambios/reemplazos
 *   - Alertas de revisión próxima
 * ============================================================================
 */

require_once __DIR__ . '/db.php';


// ============================================================================
// LISTADO
// ============================================================================

/**
 * Lista los componentes de un equipo.
 */
function listar_componentes_de_equipo(int $equipo_id, bool $incluir_inactivos = false): array {
    $where_act = $incluir_inactivos ? '' : 'AND c.activo = 1';

    return db_all(
        "SELECT c.*,
                p.nombre AS proveedor_nombre,
                u_crea.nombre_completo AS creado_por_nombre,
                u_act.nombre_completo AS actualizado_por_nombre
         FROM equipo_componentes c
         LEFT JOIN proveedores p ON c.proveedor_id = p.id
         LEFT JOIN usuarios u_crea ON c.creado_por_id = u_crea.id
         LEFT JOIN usuarios u_act ON c.actualizado_por_id = u_act.id
         WHERE c.equipo_id = :eid $where_act
         ORDER BY c.criticidad DESC, c.nombre ASC",
        ['eid' => $equipo_id]
    );
}


/**
 * Obtiene un componente por ID con datos del equipo padre.
 */
function obtener_componente(int $id): ?array {
    $r = db_one(
        "SELECT c.*,
                e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
                p.nombre AS proveedor_nombre,
                u_crea.nombre_completo AS creado_por_nombre,
                u_act.nombre_completo AS actualizado_por_nombre
         FROM equipo_componentes c
         INNER JOIN equipos e ON c.equipo_id = e.id
         LEFT JOIN proveedores p ON c.proveedor_id = p.id
         LEFT JOIN usuarios u_crea ON c.creado_por_id = u_crea.id
         LEFT JOIN usuarios u_act ON c.actualizado_por_id = u_act.id
         WHERE c.id = :id",
        ['id' => $id]
    );
    return $r ?: null;
}


// ============================================================================
// CRUD
// ============================================================================

function crear_componente(array $datos, int $usuario_id): int {
    db_exec(
        "INSERT INTO equipo_componentes
         (equipo_id, nombre, tipo, marca, modelo, numero_parte, numero_serie,
          fecha_instalacion, vida_util_meses, proxima_revision, costo_unitario, proveedor_id,
          estado, criticidad, posicion, notas, creado_por_id, actualizado_por_id)
         VALUES
         (:eid, :nom, :tipo, :marca, :modelo, :np, :ns,
          :fi, :vum, :pr, :cu, :pid,
          :est, :crit, :pos, :notas, :uid1, :uid2)",
        [
            'eid' => (int) $datos['equipo_id'],
            'nom' => mb_substr($datos['nombre'], 0, 150),
            'tipo' => $datos['tipo'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'np' => $datos['numero_parte'] ?? null,
            'ns' => $datos['numero_serie'] ?? null,
            'fi' => !empty($datos['fecha_instalacion']) ? $datos['fecha_instalacion'] : null,
            'vum' => !empty($datos['vida_util_meses']) ? (int) $datos['vida_util_meses'] : null,
            'pr' => !empty($datos['proxima_revision']) ? $datos['proxima_revision'] : null,
            'cu' => !empty($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'est' => $datos['estado'] ?? 'operando',
            'crit' => $datos['criticidad'] ?? 'media',
            'pos' => $datos['posicion'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'uid1' => $usuario_id,
            'uid2' => $usuario_id,
        ]
    );
    $id = (int) db_last_id();

    // Registrar en historial
    registrar_historial_componente($id, 'instalado', "Componente registrado: {$datos['nombre']}", null, $usuario_id);

    return $id;
}


function actualizar_componente(int $id, array $datos, int $usuario_id): void {
    db_exec(
        "UPDATE equipo_componentes
         SET nombre = :nom, tipo = :tipo, marca = :marca, modelo = :modelo,
             numero_parte = :np, numero_serie = :ns,
             fecha_instalacion = :fi, vida_util_meses = :vum, proxima_revision = :pr,
             costo_unitario = :cu, proveedor_id = :pid,
             estado = :est, criticidad = :crit, posicion = :pos, notas = :notas,
             actualizado_por_id = :uid
         WHERE id = :id",
        [
            'nom' => mb_substr($datos['nombre'], 0, 150),
            'tipo' => $datos['tipo'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'np' => $datos['numero_parte'] ?? null,
            'ns' => $datos['numero_serie'] ?? null,
            'fi' => !empty($datos['fecha_instalacion']) ? $datos['fecha_instalacion'] : null,
            'vum' => !empty($datos['vida_util_meses']) ? (int) $datos['vida_util_meses'] : null,
            'pr' => !empty($datos['proxima_revision']) ? $datos['proxima_revision'] : null,
            'cu' => !empty($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'est' => $datos['estado'] ?? 'operando',
            'crit' => $datos['criticidad'] ?? 'media',
            'pos' => $datos['posicion'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'uid' => $usuario_id,
            'id' => $id,
        ]
    );
}


function eliminar_componente(int $id, int $usuario_id): void {
    $comp = obtener_componente($id);
    if (!$comp) return;
    db_exec("UPDATE equipo_componentes SET activo = 0, actualizado_por_id = :uid WHERE id = :id",
        ['uid' => $usuario_id, 'id' => $id]);
    registrar_historial_componente($id, 'retirado', "Componente eliminado", null, $usuario_id);
}


// ============================================================================
// HISTORIAL
// ============================================================================

function registrar_historial_componente(int $componente_id, string $accion, ?string $descripcion = null,
                                         ?int $incidencia_id = null, ?int $usuario_id = null): void {
    db_exec(
        "INSERT INTO equipo_componentes_historial (componente_id, accion, descripcion, incidencia_id, usuario_id)
         VALUES (:cid, :acc, :desc, :iid, :uid)",
        [
            'cid' => $componente_id,
            'acc' => $accion,
            'desc' => $descripcion,
            'iid' => $incidencia_id,
            'uid' => $usuario_id,
        ]
    );
}


function listar_historial_componente(int $componente_id): array {
    return db_all(
        "SELECT h.*, u.nombre_completo AS usuario_nombre,
                i.folio AS incidencia_folio, i.titulo AS incidencia_titulo
         FROM equipo_componentes_historial h
         LEFT JOIN usuarios u ON h.usuario_id = u.id
         LEFT JOIN incidencias i ON h.incidencia_id = i.id
         WHERE h.componente_id = :cid
         ORDER BY h.creado_en DESC",
        ['cid' => $componente_id]
    );
}


// ============================================================================
// ALERTAS Y ESTADÍSTICAS
// ============================================================================

/**
 * Componentes con próxima revisión vencida o cercana (en los próximos N días).
 */
function componentes_por_revisar(int $dias = 30): array {
    return db_all(
        "SELECT c.*,
                e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
                e.sucursal_id,
                DATEDIFF(c.proxima_revision, CURDATE()) AS dias_restantes
         FROM equipo_componentes c
         INNER JOIN equipos e ON c.equipo_id = e.id
         WHERE c.activo = 1
           AND c.proxima_revision IS NOT NULL
           AND c.proxima_revision <= DATE_ADD(CURDATE(), INTERVAL :d DAY)
         ORDER BY c.proxima_revision ASC",
        ['d' => $dias]
    );
}


/**
 * Componentes en estado crítico (falla o desgaste con criticidad alta/crítica).
 */
function componentes_criticos(): array {
    return db_all(
        "SELECT c.*,
                e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre
         FROM equipo_componentes c
         INNER JOIN equipos e ON c.equipo_id = e.id
         WHERE c.activo = 1
           AND (c.estado IN ('falla','desgaste')
                OR (c.criticidad IN ('alta','critica') AND c.estado = 'desgaste'))
         ORDER BY
            FIELD(c.estado, 'falla', 'desgaste') ASC,
            FIELD(c.criticidad, 'critica', 'alta', 'media', 'baja') ASC"
    );
}


/**
 * Etiquetas legibles para mostrar en UI.
 */
function etiqueta_estado_componente(string $estado): array {
    return match ($estado) {
        'operando'    => ['label' => 'Operando',    'color' => '#16A34A', 'icono' => 'check-circle-2'],
        'desgaste'    => ['label' => 'Con desgaste','color' => '#F59E0B', 'icono' => 'alert-triangle'],
        'falla'       => ['label' => 'En falla',    'color' => '#DC2626', 'icono' => 'alert-octagon'],
        'reemplazado' => ['label' => 'Reemplazado', 'color' => '#0EA5E9', 'icono' => 'refresh-cw'],
        'retirado'    => ['label' => 'Retirado',    'color' => '#71717A', 'icono' => 'archive'],
        default       => ['label' => $estado,       'color' => '#71717A', 'icono' => 'circle'],
    };
}


function etiqueta_criticidad_componente(string $criticidad): array {
    return match ($criticidad) {
        'baja'    => ['label' => 'Baja',     'color' => '#16A34A'],
        'media'   => ['label' => 'Media',    'color' => '#F59E0B'],
        'alta'    => ['label' => 'Alta',     'color' => '#EA580C'],
        'critica' => ['label' => 'Crítica',  'color' => '#DC2626'],
        default   => ['label' => $criticidad,'color' => '#71717A'],
    };
}
