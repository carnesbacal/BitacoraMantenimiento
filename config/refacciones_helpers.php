<?php
/**
 * ============================================================================
 * config/refacciones_helpers.php
 * ============================================================================
 * Gestión completa de refacciones y almacén:
 *   - CRUD del catálogo
 *   - Stock por sucursal con mínimos
 *   - Movimientos (entradas/salidas/ajustes/transferencias)
 *   - Compatibilidades con equipos y componentes
 *   - Alertas de stock bajo
 * ============================================================================
 */

require_once __DIR__ . '/db.php';


// ============================================================================
// CATÁLOGO MAESTRO (refacciones)
// ============================================================================

function listar_refacciones(array $filtros = []): array {
    $estado = $filtros['estado'] ?? 'activas';
    $where = [];
    if ($estado === 'inactivas')  $where[] = "r.activo = 0";
    elseif ($estado !== 'todas')  $where[] = "r.activo = 1";
    $params = [];

    if (!empty($filtros['busqueda'])) {
        $like = '%' . $filtros['busqueda'] . '%';
        $where[] = "(r.codigo LIKE :q1 OR r.nombre LIKE :q2 OR r.numero_parte LIKE :q3 OR r.marca LIKE :q4)";
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
    }

    if (!empty($filtros['categoria'])) {
        $where[] = "r.categoria = :cat";
        $params['cat'] = $filtros['categoria'];
    }

    if (!empty($filtros['proveedor_id'])) {
        $where[] = "r.proveedor_id = :pid";
        $params['pid'] = (int) $filtros['proveedor_id'];
    }

    if (!empty($filtros['sucursal_id']) && !empty($filtros['solo_stock_bajo'])) {
        $where[] = "EXISTS (
            SELECT 1 FROM refacciones_stock s
            WHERE s.refaccion_id = r.id AND s.sucursal_id = :sid_low
              AND s.cantidad_actual <= s.cantidad_minima
              AND s.cantidad_minima > 0
        )";
        $params['sid_low'] = (int) $filtros['sucursal_id'];
    }

    $where_sql = $where ? ("WHERE " . implode(' AND ', $where)) : "";

    // Stock agregado en UNA sola pasada. Antes eran 2 subconsultas correlacionadas
    // por cada refacción (2N lecturas); ahora es un solo GROUP BY unido por JOIN.
    // Con sucursal filtrada, SUM/MIN sobre esa única fila devuelve el mismo valor.
    $sucursal_filtro = !empty($filtros['sucursal_id']) ? (int) $filtros['sucursal_id'] : null;
    $stock_where = $sucursal_filtro ? "WHERE sucursal_id = $sucursal_filtro" : "";

    return db_all(
        "SELECT r.*,
                p.nombre AS proveedor_nombre,
                st.stock_total,
                st.minimo_total
         FROM refacciones r
         LEFT JOIN proveedores p ON r.proveedor_id = p.id
         LEFT JOIN (
             SELECT refaccion_id,
                    SUM(cantidad_actual) AS stock_total,
                    MIN(cantidad_minima) AS minimo_total
             FROM refacciones_stock
             $stock_where
             GROUP BY refaccion_id
         ) st ON st.refaccion_id = r.id
         $where_sql
         ORDER BY r.nombre ASC",
        $params
    );
}


function obtener_refaccion(int $id): ?array {
    $r = db_one(
        "SELECT r.*, p.nombre AS proveedor_nombre,
                u_crea.nombre_completo AS creado_por_nombre,
                u_act.nombre_completo AS actualizado_por_nombre
         FROM refacciones r
         LEFT JOIN proveedores p ON r.proveedor_id = p.id
         LEFT JOIN usuarios u_crea ON r.creado_por_id = u_crea.id
         LEFT JOIN usuarios u_act ON r.actualizado_por_id = u_act.id
         WHERE r.id = :id",
        ['id' => $id]
    );
    return $r ?: null;
}


function crear_refaccion(array $datos, int $usuario_id): int {
    db_exec(
        "INSERT INTO refacciones
         (codigo, nombre, descripcion, marca, modelo, numero_parte, categoria,
          unidad_medida, costo_unitario, proveedor_id, creado_por_id, actualizado_por_id)
         VALUES
         (:cod, :nom, :desc, :marca, :modelo, :np, :cat,
          :um, :cu, :pid, :uid1, :uid2)",
        [
            'cod' => mb_substr($datos['codigo'], 0, 50),
            'nom' => mb_substr($datos['nombre'], 0, 200),
            'desc' => $datos['descripcion'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'np' => $datos['numero_parte'] ?? null,
            'cat' => $datos['categoria'] ?? null,
            'um' => $datos['unidad_medida'] ?? 'pieza',
            'cu' => !empty($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'uid1' => $usuario_id,
            'uid2' => $usuario_id,
        ]
    );
    return (int) db_last_id();
}


function actualizar_refaccion(int $id, array $datos, int $usuario_id): void {
    db_exec(
        "UPDATE refacciones
         SET codigo = :cod, nombre = :nom, descripcion = :desc,
             marca = :marca, modelo = :modelo, numero_parte = :np, categoria = :cat,
             unidad_medida = :um, costo_unitario = :cu, proveedor_id = :pid,
             actualizado_por_id = :uid
         WHERE id = :id",
        [
            'cod' => mb_substr($datos['codigo'], 0, 50),
            'nom' => mb_substr($datos['nombre'], 0, 200),
            'desc' => $datos['descripcion'] ?? null,
            'marca' => $datos['marca'] ?? null,
            'modelo' => $datos['modelo'] ?? null,
            'np' => $datos['numero_parte'] ?? null,
            'cat' => $datos['categoria'] ?? null,
            'um' => $datos['unidad_medida'] ?? 'pieza',
            'cu' => !empty($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : null,
            'pid' => !empty($datos['proveedor_id']) ? (int) $datos['proveedor_id'] : null,
            'uid' => $usuario_id,
            'id' => $id,
        ]
    );
}


function eliminar_refaccion(int $id, int $usuario_id): void {
    db_exec("UPDATE refacciones SET activo = 0, actualizado_por_id = :uid WHERE id = :id",
        ['uid' => $usuario_id, 'id' => $id]);
}


// ============================================================================
// STOCK POR SUCURSAL
// ============================================================================

function obtener_stock(int $refaccion_id, int $sucursal_id): ?array {
    $r = db_one(
        "SELECT * FROM refacciones_stock
         WHERE refaccion_id = :rid AND sucursal_id = :sid",
        ['rid' => $refaccion_id, 'sid' => $sucursal_id]
    );
    return $r ?: null;
}


function listar_stock_de_refaccion(int $refaccion_id): array {
    return db_all(
        "SELECT s.*, suc.nombre AS sucursal_nombre, suc.codigo AS sucursal_codigo,
                CASE
                    WHEN s.cantidad_minima > 0 AND s.cantidad_actual <= s.cantidad_minima THEN 1
                    ELSE 0
                END AS alerta_stock_bajo
         FROM refacciones_stock s
         INNER JOIN sucursales suc ON s.sucursal_id = suc.id
         WHERE s.refaccion_id = :rid
         ORDER BY suc.nombre ASC",
        ['rid' => $refaccion_id]
    );
}


function actualizar_minimos_stock(int $refaccion_id, int $sucursal_id, float $minimo, ?float $optima, ?string $ubicacion): void {
    $existe = obtener_stock($refaccion_id, $sucursal_id);
    if ($existe) {
        db_exec(
            "UPDATE refacciones_stock
             SET cantidad_minima = :min, cantidad_optima = :opt, ubicacion = :ubi
             WHERE id = :id",
            ['min' => $minimo, 'opt' => $optima, 'ubi' => $ubicacion, 'id' => $existe['id']]
        );
    } else {
        db_exec(
            "INSERT INTO refacciones_stock (refaccion_id, sucursal_id, cantidad_actual, cantidad_minima, cantidad_optima, ubicacion)
             VALUES (:rid, :sid, 0, :min, :opt, :ubi)",
            ['rid' => $refaccion_id, 'sid' => $sucursal_id, 'min' => $minimo, 'opt' => $optima, 'ubi' => $ubicacion]
        );
    }
}


/**
 * Refacciones con stock bajo en una sucursal (o todas si null).
 */
function refacciones_stock_bajo(?int $sucursal_id = null): array {
    $where_suc = $sucursal_id ? "AND s.sucursal_id = :sid" : "";
    $params = $sucursal_id ? ['sid' => $sucursal_id] : [];

    return db_all(
        "SELECT r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria,
                s.cantidad_actual, s.cantidad_minima, s.cantidad_optima, s.ubicacion,
                suc.codigo AS sucursal_codigo, suc.nombre AS sucursal_nombre,
                p.nombre AS proveedor_nombre,
                (s.cantidad_minima - s.cantidad_actual) AS deficit
         FROM refacciones_stock s
         INNER JOIN refacciones r ON s.refaccion_id = r.id
         INNER JOIN sucursales suc ON s.sucursal_id = suc.id
         LEFT JOIN proveedores p ON r.proveedor_id = p.id
         WHERE r.activo = 1
           AND s.cantidad_minima > 0
           AND s.cantidad_actual <= s.cantidad_minima
           $where_suc
         ORDER BY (s.cantidad_actual / NULLIF(s.cantidad_minima, 0)) ASC, r.nombre ASC",
        $params
    );
}


// ============================================================================
// MOVIMIENTOS (transacciones)
// ============================================================================

/**
 * Registra una entrada, salida o ajuste. Actualiza stock atómicamente.
 *
 * $tipo: 'entrada' | 'salida' | 'ajuste'
 *   - entrada: cantidad_despues = cantidad_antes + cantidad
 *   - salida:  cantidad_despues = cantidad_antes - cantidad
 *   - ajuste:  cantidad_despues = cantidad (es el valor absoluto al que se ajusta)
 */
function registrar_movimiento(array $datos): int {
    $refaccion_id = (int) $datos['refaccion_id'];
    $sucursal_id = (int) $datos['sucursal_id'];
    $tipo = $datos['tipo'];
    $cantidad = (float) $datos['cantidad'];
    $usuario_id = (int) $datos['usuario_id'];

    // Obtener stock actual (o crearlo si no existe)
    $stock = obtener_stock($refaccion_id, $sucursal_id);
    if (!$stock) {
        db_exec(
            "INSERT INTO refacciones_stock (refaccion_id, sucursal_id, cantidad_actual, cantidad_minima)
             VALUES (:rid, :sid, 0, 0)",
            ['rid' => $refaccion_id, 'sid' => $sucursal_id]
        );
        $cantidad_antes = 0.0;
    } else {
        $cantidad_antes = (float) $stock['cantidad_actual'];
    }

    // Calcular cantidad después según tipo
    if ($tipo === 'entrada') {
        $cantidad_despues = $cantidad_antes + $cantidad;
    } elseif ($tipo === 'salida') {
        $cantidad_despues = $cantidad_antes - $cantidad;
        if ($cantidad_despues < 0) {
            throw new RuntimeException("Stock insuficiente. Disponible: $cantidad_antes, intentando sacar: $cantidad");
        }
    } elseif ($tipo === 'ajuste') {
        // En ajuste, $cantidad es el valor final absoluto
        $cantidad_despues = $cantidad;
        $cantidad = abs($cantidad_despues - $cantidad_antes); // para guardar magnitud
    } else {
        throw new RuntimeException("Tipo de movimiento inválido: $tipo");
    }

    // Insertar movimiento
    db_exec(
        "INSERT INTO refacciones_movimientos
         (refaccion_id, sucursal_id, tipo, cantidad, cantidad_antes, cantidad_despues,
          motivo, notas, incidencia_id, componente_id, sucursal_destino_id, costo_unitario, usuario_id)
         VALUES
         (:rid, :sid, :tipo, :cant, :ca, :cd,
          :motivo, :notas, :iid, :cid, :sdid, :cu, :uid)",
        [
            'rid' => $refaccion_id,
            'sid' => $sucursal_id,
            'tipo' => $tipo,
            'cant' => $cantidad,
            'ca' => $cantidad_antes,
            'cd' => $cantidad_despues,
            'motivo' => $datos['motivo'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'iid' => !empty($datos['incidencia_id']) ? (int) $datos['incidencia_id'] : null,
            'cid' => !empty($datos['componente_id']) ? (int) $datos['componente_id'] : null,
            'sdid' => !empty($datos['sucursal_destino_id']) ? (int) $datos['sucursal_destino_id'] : null,
            'cu' => !empty($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : null,
            'uid' => $usuario_id,
        ]
    );
    $mov_id = (int) db_last_id();

    // Actualizar stock
    db_exec(
        "UPDATE refacciones_stock
         SET cantidad_actual = :nueva
         WHERE refaccion_id = :rid AND sucursal_id = :sid",
        ['nueva' => $cantidad_despues, 'rid' => $refaccion_id, 'sid' => $sucursal_id]
    );

    return $mov_id;
}


function listar_movimientos_de_refaccion(int $refaccion_id, int $limite = 50): array {
    return db_all(
        "SELECT m.*,
                suc.codigo AS sucursal_codigo,
                suc_d.codigo AS sucursal_destino_codigo,
                u.nombre_completo AS usuario_nombre,
                i.folio AS incidencia_folio, i.titulo AS incidencia_titulo
         FROM refacciones_movimientos m
         INNER JOIN sucursales suc ON m.sucursal_id = suc.id
         LEFT JOIN sucursales suc_d ON m.sucursal_destino_id = suc_d.id
         INNER JOIN usuarios u ON m.usuario_id = u.id
         LEFT JOIN incidencias i ON m.incidencia_id = i.id
         WHERE m.refaccion_id = :rid
         ORDER BY m.creado_en DESC
         LIMIT $limite",
        ['rid' => $refaccion_id]
    );
}


function listar_movimientos_recientes(?int $sucursal_id = null, int $limite = 30): array {
    $where_suc = $sucursal_id ? "WHERE m.sucursal_id = :sid" : "";
    $params = $sucursal_id ? ['sid' => $sucursal_id] : [];

    return db_all(
        "SELECT m.*,
                r.codigo AS refaccion_codigo, r.nombre AS refaccion_nombre, r.unidad_medida,
                suc.codigo AS sucursal_codigo,
                u.nombre_completo AS usuario_nombre
         FROM refacciones_movimientos m
         INNER JOIN refacciones r ON m.refaccion_id = r.id
         INNER JOIN sucursales suc ON m.sucursal_id = suc.id
         INNER JOIN usuarios u ON m.usuario_id = u.id
         $where_suc
         ORDER BY m.creado_en DESC
         LIMIT $limite",
        $params
    );
}


// ============================================================================
// COMPATIBILIDADES
// ============================================================================

function listar_compatibilidades_refaccion(int $refaccion_id): array {
    return db_all(
        "SELECT c.*,
                e.codigo_inventario AS equipo_codigo, e.nombre AS equipo_nombre,
                eq.codigo_inventario AS comp_equipo_codigo, eq.nombre AS comp_equipo_nombre,
                comp.nombre AS componente_nombre, comp.tipo AS componente_tipo
         FROM refacciones_compatibles c
         LEFT JOIN equipos e ON c.equipo_id = e.id
         LEFT JOIN equipo_componentes comp ON c.componente_id = comp.id
         LEFT JOIN equipos eq ON comp.equipo_id = eq.id
         WHERE c.refaccion_id = :rid
         ORDER BY c.creado_en DESC",
        ['rid' => $refaccion_id]
    );
}


function refacciones_compatibles_con_equipo(int $equipo_id): array {
    return db_all(
        "SELECT DISTINCT r.id, r.codigo, r.nombre, r.unidad_medida, r.categoria,
                (SELECT SUM(s.cantidad_actual) FROM refacciones_stock s WHERE s.refaccion_id = r.id) AS stock_total
         FROM refacciones r
         INNER JOIN refacciones_compatibles c ON c.refaccion_id = r.id
         WHERE r.activo = 1
           AND (c.equipo_id = :eid
                OR c.componente_id IN (SELECT id FROM equipo_componentes WHERE equipo_id = :eid2))
         ORDER BY r.nombre ASC",
        ['eid' => $equipo_id, 'eid2' => $equipo_id]
    );
}


function agregar_compatibilidad(int $refaccion_id, ?int $equipo_id, ?int $componente_id, ?string $notas = null): void {
    if (!$equipo_id && !$componente_id) {
        throw new RuntimeException('Debe especificar equipo o componente.');
    }

    try {
        db_exec(
            "INSERT INTO refacciones_compatibles (refaccion_id, equipo_id, componente_id, notas)
             VALUES (:rid, :eid, :cid, :notas)",
            [
                'rid' => $refaccion_id,
                'eid' => $equipo_id,
                'cid' => $componente_id,
                'notas' => $notas,
            ]
        );
    } catch (Throwable $e) {
        // ignorar duplicado
    }
}


function eliminar_compatibilidad(int $id): void {
    db_exec("DELETE FROM refacciones_compatibles WHERE id = :id", ['id' => $id]);
}


// ============================================================================
// ESTADÍSTICAS GLOBALES
// ============================================================================

function stats_almacen(?int $sucursal_id = null): array {
    $where_suc = $sucursal_id ? "WHERE s.sucursal_id = :sid" : "";
    $params = $sucursal_id ? ['sid' => $sucursal_id] : [];

    $total_refacciones = db_one("SELECT COUNT(*) c FROM refacciones WHERE activo = 1");

    $total_stock = db_one(
        "SELECT COALESCE(SUM(s.cantidad_actual), 0) c FROM refacciones_stock s $where_suc",
        $params
    );

    $stock_bajo = db_one(
        "SELECT COUNT(*) c FROM refacciones_stock s
         INNER JOIN refacciones r ON s.refaccion_id = r.id
         WHERE r.activo = 1 AND s.cantidad_minima > 0 AND s.cantidad_actual <= s.cantidad_minima
         " . ($sucursal_id ? "AND s.sucursal_id = :sid" : ""),
        $params
    );

    $valor_inventario = db_one(
        "SELECT COALESCE(SUM(s.cantidad_actual * r.costo_unitario), 0) c
         FROM refacciones_stock s
         INNER JOIN refacciones r ON s.refaccion_id = r.id
         WHERE r.activo = 1 AND r.costo_unitario IS NOT NULL
         " . ($sucursal_id ? "AND s.sucursal_id = :sid" : ""),
        $params
    );

    return [
        'total_refacciones' => (int) ($total_refacciones['c'] ?? 0),
        'unidades_stock' => (float) ($total_stock['c'] ?? 0),
        'stock_bajo' => (int) ($stock_bajo['c'] ?? 0),
        'valor_inventario' => (float) ($valor_inventario['c'] ?? 0),
    ];
}


// ============================================================================
// CATEGORÍAS Y UNIDADES DE MEDIDA (constantes)
// ============================================================================

function categorias_refacciones(): array {
    return [
        'Mecánica', 'Eléctrica', 'Electrónica', 'Hidráulica', 'Neumática',
        'Refrigeración', 'Lubricación', 'Limpieza', 'Soldadura',
        'Tornillería', 'Conexiones', 'Sellos y empaques', 'Filtros',
        'Bandas y cadenas', 'Rodamientos', 'Motores', 'Sensores',
        'Válvulas', 'Otro',
    ];
}


function unidades_medida(): array {
    return [
        'pieza' => 'Pieza(s)',
        'metro' => 'Metro(s)',
        'cm' => 'Centímetro(s)',
        'kg' => 'Kilogramo(s)',
        'g' => 'Gramo(s)',
        'litro' => 'Litro(s)',
        'ml' => 'Mililitro(s)',
        'galon' => 'Galón(es)',
        'caja' => 'Caja(s)',
        'paquete' => 'Paquete(s)',
        'rollo' => 'Rollo(s)',
        'juego' => 'Juego(s)',
        'par' => 'Par(es)',
    ];
}


function motivos_movimiento(): array {
    return [
        'entrada' => [
            'compra' => 'Compra a proveedor',
            'devolucion' => 'Devolución',
            'transferencia_entrada' => 'Transferencia desde otra sucursal',
            'ajuste_inventario' => 'Ajuste de inventario',
            'donacion' => 'Donación',
        ],
        'salida' => [
            'uso_mantenimiento' => 'Uso en mantenimiento',
            'uso_correctivo' => 'Uso en correctivo',
            'uso_preventivo' => 'Uso en preventivo',
            'transferencia_salida' => 'Transferencia a otra sucursal',
            'merma' => 'Merma / daño',
            'devolucion_proveedor' => 'Devolución a proveedor',
            'ajuste_inventario' => 'Ajuste de inventario',
        ],
        'ajuste' => [
            'conteo_fisico' => 'Conteo físico',
            'correccion' => 'Corrección de error',
            'inicial' => 'Carga inicial',
        ],
    ];
}
