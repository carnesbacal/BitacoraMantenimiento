<?php
/**
 * ============================================================================
 * config/requisiciones_helpers.php
 * ----------------------------------------------------------------------------
 * Requisiciones de compra de mantenimiento (formato 0069-FRM Rev. B / ECO. 013).
 *
 * Flujo: borrador → enviada → autorizada → cerrada  (o cancelada).
 * Los renglones pueden venir del catálogo de refacciones o ser texto libre.
 * Requiere: migracion_requisiciones.sql
 * ============================================================================
 */

/** ¿Ya se corrió la migración? Evita errores si aún no existe la tabla. */
function requisiciones_disponible(): bool {
    static $ok = null;
    if ($ok === null) {
        try { $ok = (bool) db_one("SHOW TABLES LIKE 'refacciones_requisiciones'"); }
        catch (Throwable $e) { $ok = false; }
    }
    return $ok;
}

/** Estados de la requisición con su etiqueta y color. */
function requisicion_estados(): array {
    return [
        'borrador'   => ['label' => 'Borrador',   'color' => 'zinc'],
        'enviada'    => ['label' => 'Enviada',    'color' => 'amber'],
        'autorizada' => ['label' => 'Autorizada', 'color' => 'emerald'],
        'cerrada'    => ['label' => 'Cerrada',    'color' => 'blue'],
        'cancelada'  => ['label' => 'Cancelada',  'color' => 'red'],
    ];
}

/** Status de cada renglón (columna "Status" del formato). */
function requisicion_item_status(): array {
    return [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'amber'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'blue'],
        'comprado'  => ['label' => 'Recibido',  'color' => 'emerald'],
        'cancelado' => ['label' => 'Cancelado', 'color' => 'zinc'],
    ];
}

/**
 * Razones sociales disponibles para el formato impreso.
 * ---------------------------------------------------------------------------
 * Para AJUSTAR el nombre legal o cambiar el logo, edita este arreglo.
 * El logo es una ruta relativa dentro del proyecto (carpeta assets/img/).
 */
function requisicion_empresas(): array {
    return [
        'corral' => [
            'nombre' => 'GRUPO INDUSTRIAL CORRAL S. DE R.L. DE C.V.',
            'corto'  => 'Grupo Industrial Corral',
            'logo'   => 'assets/img/logo-corral.png',
        ],
        'bacal' => [
            'nombre' => 'CARNES BACAL S.A. DE C.V.',
            'corto'  => 'Carnes Bacal',
            'logo'   => 'assets/img/logo-negro.png',
        ],
    ];
}

/** Devuelve la empresa por clave, con respaldo a la primera. */
function requisicion_empresa(?string $clave): array {
    $emp = requisicion_empresas();
    return $emp[(string) $clave] ?? reset($emp);
}

/**
 * Genera el siguiente folio: REQ-{SUCURSAL}-{AÑO}-{consecutivo}
 * Ej. REQ-BEN-2026-0001. El consecutivo es independiente por sucursal y año.
 */
function requisicion_siguiente_folio(string $sucursal_codigo = ''): string {
    $anio = date('Y');
    $cod  = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $sucursal_codigo));
    $cod  = $cod !== '' ? substr($cod, 0, 6) : 'GRAL';
    $prefijo = "REQ-{$cod}-{$anio}-";

    $row = db_one(
        "SELECT folio FROM refacciones_requisiciones
          WHERE folio LIKE :p ORDER BY folio DESC LIMIT 1",
        ['p' => $prefijo . '%']
    );
    $n = 1;
    if ($row && preg_match('/-(\d+)$/', (string) $row['folio'], $m)) {
        $n = (int) $m[1] + 1;
    }
    return $prefijo . sprintf('%04d', $n);
}

/** Crea una requisición en borrador y devuelve su id. */
function requisicion_crear(int $sucursal_id, string $fecha, int $solicito_id, ?string $notas, int $usuario_id, string $razon_social = 'corral'): int {
    if ($sucursal_id <= 0) throw new RuntimeException('Selecciona la sucursal.');
    if ($fecha === '')     $fecha = date('Y-m-d');
    if (!array_key_exists($razon_social, requisicion_empresas())) $razon_social = 'corral';

    $suc = db_one("SELECT codigo FROM sucursales WHERE id = :id", ['id' => $sucursal_id]);
    $suc_codigo = (string) ($suc['codigo'] ?? '');

    // Reintenta si dos usuarios generan folio al mismo tiempo.
    for ($intento = 0; $intento < 5; $intento++) {
        try {
            db_exec(
                "INSERT INTO refacciones_requisiciones
                    (folio, sucursal_id, fecha, razon_social, solicito_id, estado, notas, creado_por_id)
                 VALUES (:f, :s, :fe, :rs, :sol, 'borrador', :n, :u)",
                ['f' => requisicion_siguiente_folio($suc_codigo), 's' => $sucursal_id, 'fe' => $fecha,
                 'rs' => $razon_social, 'sol' => $solicito_id, 'n' => $notas ?: null, 'u' => $usuario_id]
            );
            return (int) db_last_id();
        } catch (Throwable $e) {
            if ($intento === 4) throw $e;
            usleep(120000);
        }
    }
    throw new RuntimeException('No se pudo generar el folio.');
}

/** Una requisición con datos de sucursal y usuarios. */
function requisicion_obtener(int $id): ?array {
    return db_one(
        "SELECT r.*, s.nombre AS sucursal_nombre, s.codigo AS sucursal_codigo,
                sol.nombre_completo AS solicito_nombre,
                aut.nombre_completo AS autorizo_nombre
           FROM refacciones_requisiciones r
           INNER JOIN sucursales s ON r.sucursal_id = s.id
           INNER JOIN usuarios sol ON r.solicito_id = sol.id
           LEFT  JOIN usuarios aut ON r.autorizado_por_id = aut.id
          WHERE r.id = :id",
        ['id' => $id]
    );
}

/** Renglones de una requisición, en orden. */
function requisicion_items(int $requisicion_id): array {
    return db_all(
        "SELECT i.*, r.codigo AS refaccion_codigo, a.nombre AS area_nombre
           FROM refacciones_requisicion_items i
           LEFT JOIN refacciones r ON i.refaccion_id = r.id
           LEFT JOIN areas a       ON i.area_id = a.id
          WHERE i.requisicion_id = :id
          ORDER BY i.orden ASC, i.id ASC",
        ['id' => $requisicion_id]
    );
}

/** Listado con filtros: sucursal, estado, texto y rango de fechas. */
function requisiciones_listar(array $filtros = [], int $limite = 200): array {
    $where = []; $params = [];
    if (!empty($filtros['sucursal_id'])) { $where[] = 'r.sucursal_id = :sid'; $params['sid'] = (int) $filtros['sucursal_id']; }
    if (!empty($filtros['estado']))      { $where[] = 'r.estado = :est';      $params['est'] = $filtros['estado']; }
    if (!empty($filtros['desde']))       { $where[] = 'r.fecha >= :d';        $params['d']   = $filtros['desde']; }
    if (!empty($filtros['hasta']))       { $where[] = 'r.fecha <= :h';        $params['h']   = $filtros['hasta']; }
    if (!empty($filtros['q'])) {
        $where[] = '(r.folio LIKE :q1 OR r.notas LIKE :q2 OR sol.nombre_completo LIKE :q3)';
        $params['q1'] = '%' . $filtros['q'] . '%';
        $params['q2'] = '%' . $filtros['q'] . '%';
        $params['q3'] = '%' . $filtros['q'] . '%';
    }
    $sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $limite = max(1, min($limite, 500));

    return db_all(
        "SELECT r.*, s.nombre AS sucursal_nombre, s.codigo AS sucursal_codigo,
                sol.nombre_completo AS solicito_nombre,
                (SELECT COUNT(*) FROM refacciones_requisicion_items i WHERE i.requisicion_id = r.id) AS num_items,
                (SELECT COUNT(*) FROM refacciones_requisicion_items i WHERE i.requisicion_id = r.id AND i.status = 'pendiente') AS num_pendientes
           FROM refacciones_requisiciones r
           INNER JOIN sucursales s ON r.sucursal_id = s.id
           INNER JOIN usuarios sol ON r.solicito_id = sol.id
           $sql_where
          ORDER BY r.fecha DESC, r.id DESC
          LIMIT $limite",
        $params
    );
}

/** Agrega un renglón. $refaccion_id NULL = texto libre. */
function requisicion_item_agregar(int $requisicion_id, array $datos): int {
    $desc = trim((string) ($datos['descripcion'] ?? ''));
    if ($desc === '') throw new RuntimeException('La descripción del renglón es obligatoria.');
    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad debe ser mayor a 0.');

    $orden = (int) (db_one(
        "SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM refacciones_requisicion_items WHERE requisicion_id = :id",
        ['id' => $requisicion_id]
    )['n'] ?? 1);

    db_exec(
        "INSERT INTO refacciones_requisicion_items
            (requisicion_id, refaccion_id, descripcion, cantidad, unidad, area_id, status, notas, orden)
         VALUES (:req, :ref, :d, :c, :u, :a, 'pendiente', :n, :o)",
        [
            'req' => $requisicion_id,
            'ref' => !empty($datos['refaccion_id']) ? (int) $datos['refaccion_id'] : null,
            'd'   => $desc,
            'c'   => $cant,
            'u'   => !empty($datos['unidad']) ? trim((string) $datos['unidad']) : null,
            'a'   => !empty($datos['area_id']) ? (int) $datos['area_id'] : null,
            'n'   => !empty($datos['notas']) ? trim((string) $datos['notas']) : null,
            'o'   => $orden,
        ]
    );
    return (int) db_last_id();
}

/** Actualiza un renglón existente. */
function requisicion_item_actualizar(int $item_id, array $datos): void {
    $desc = trim((string) ($datos['descripcion'] ?? ''));
    if ($desc === '') throw new RuntimeException('La descripción del renglón es obligatoria.');
    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad debe ser mayor a 0.');
    $status = (string) ($datos['status'] ?? 'pendiente');
    if (!array_key_exists($status, requisicion_item_status())) $status = 'pendiente';

    db_exec(
        "UPDATE refacciones_requisicion_items
            SET descripcion = :d, cantidad = :c, unidad = :u, area_id = :a, status = :s, notas = :n
          WHERE id = :id",
        [
            'd' => $desc, 'c' => $cant,
            'u' => !empty($datos['unidad']) ? trim((string) $datos['unidad']) : null,
            'a' => !empty($datos['area_id']) ? (int) $datos['area_id'] : null,
            's' => $status,
            'n' => !empty($datos['notas']) ? trim((string) $datos['notas']) : null,
            'id' => $item_id,
        ]
    );
}

/** Elimina un renglón. */
function requisicion_item_eliminar(int $item_id): void {
    db_exec("DELETE FROM refacciones_requisicion_items WHERE id = :id", ['id' => $item_id]);
}

/** Cambia el estado de la requisición. Al autorizar guarda quién y cuándo. */
function requisicion_cambiar_estado(int $id, string $estado, int $usuario_id): void {
    if (!array_key_exists($estado, requisicion_estados())) {
        throw new RuntimeException('Estado no válido.');
    }
    if ($estado === 'autorizada') {
        db_exec(
            "UPDATE refacciones_requisiciones
                SET estado = 'autorizada', autorizado_por_id = :u, autorizado_en = NOW()
              WHERE id = :id",
            ['u' => $usuario_id, 'id' => $id]
        );
    } else {
        db_exec("UPDATE refacciones_requisiciones SET estado = :e WHERE id = :id",
            ['e' => $estado, 'id' => $id]);
    }
}

/** Actualiza los datos de cabecera (fecha, notas). */
function requisicion_actualizar(int $id, string $fecha, ?string $notas, ?string $razon_social = null): void {
    if ($razon_social !== null && array_key_exists($razon_social, requisicion_empresas())) {
        db_exec("UPDATE refacciones_requisiciones SET fecha = :f, notas = :n, razon_social = :rs WHERE id = :id",
            ['f' => $fecha ?: date('Y-m-d'), 'n' => $notas ?: null, 'rs' => $razon_social, 'id' => $id]);
        return;
    }
    db_exec("UPDATE refacciones_requisiciones SET fecha = :f, notas = :n WHERE id = :id",
        ['f' => $fecha ?: date('Y-m-d'), 'n' => $notas ?: null, 'id' => $id]);
}

/** Elimina la requisición completa (los renglones caen por cascada). */
function requisicion_eliminar(int $id): void {
    db_exec("DELETE FROM refacciones_requisiciones WHERE id = :id", ['id' => $id]);
}

/**
 * Prellena la requisición con las refacciones bajo mínimo de su sucursal.
 * Sugiere la cantidad para llegar a la óptima (o al mínimo si no hay óptima).
 * No duplica: omite refacciones que ya estén en la requisición.
 * Devuelve cuántos renglones agregó.
 */
function requisicion_prellenar_bajo_minimo(int $requisicion_id, int $sucursal_id): int {
    $ya = db_all(
        "SELECT refaccion_id FROM refacciones_requisicion_items
          WHERE requisicion_id = :id AND refaccion_id IS NOT NULL",
        ['id' => $requisicion_id]
    );
    $existentes = array_map(fn($r) => (int) $r['refaccion_id'], $ya);

    $agregados = 0;
    foreach (refacciones_stock_bajo($sucursal_id) as $r) {
        if (in_array((int) $r['id'], $existentes, true)) continue;
        $objetivo  = (float) ($r['cantidad_optima'] ?? 0) > 0
            ? (float) $r['cantidad_optima']
            : (float) $r['cantidad_minima'];
        $sugerida  = max(1, (float) $objetivo - (float) $r['cantidad_actual']);
        requisicion_item_agregar($requisicion_id, [
            'refaccion_id' => (int) $r['id'],
            'descripcion'  => trim($r['nombre'] . ' (' . $r['codigo'] . ')'),
            'cantidad'     => $sugerida,
            'unidad'       => $r['unidad_medida'],
            'notas'        => 'Bajo mínimo: ' . rtrim(rtrim(number_format((float) $r['cantidad_actual'], 2), '0'), '.')
                              . ' de ' . rtrim(rtrim(number_format((float) $r['cantidad_minima'], 2), '0'), '.'),
        ]);
        $agregados++;
    }
    return $agregados;
}


/**
 * Registra la RECEPCIÓN de un renglón.
 * - Si el renglón está ligado al catálogo, genera una ENTRADA al almacén de la
 *   sucursal de la requisición (motivo "compra") y actualiza el stock.
 * - Si es texto libre, opcionalmente da de alta la refacción en el catálogo
 *   ($datos['crear_refaccion']) y entonces sí afecta stock. Si no, solo queda
 *   registrado como informativo (no toca almacén).
 * Admite recepciones parciales y acumula lo ya recibido.
 */
function requisicion_item_recibir(int $item_id, array $datos, int $usuario_id): array {
    $it = db_one(
        "SELECT i.*, r.sucursal_id, r.folio
           FROM refacciones_requisicion_items i
           INNER JOIN refacciones_requisiciones r ON i.requisicion_id = r.id
          WHERE i.id = :id",
        ['id' => $item_id]
    );
    if (!$it) throw new RuntimeException('Renglón no encontrado.');

    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad recibida debe ser mayor a 0.');

    $costo = (isset($datos['costo_unitario']) && $datos['costo_unitario'] !== '')
        ? (float) $datos['costo_unitario'] : null;

    $refaccion_id   = !empty($it['refaccion_id']) ? (int) $it['refaccion_id'] : null;
    $creo_refaccion = false;

    // Alta opcional en catálogo para renglones de texto libre
    if (!$refaccion_id && !empty($datos['crear_refaccion'])) {
        $codigo = trim((string) ($datos['nuevo_codigo'] ?? ''));
        if ($codigo === '') throw new RuntimeException('Captura el código para dar de alta la refacción.');

        $existe = db_one("SELECT id FROM refacciones WHERE codigo = :c", ['c' => $codigo]);
        if ($existe) {
            $refaccion_id = (int) $existe['id'];
        } else {
            $refaccion_id = crear_refaccion([
                'codigo'         => $codigo,
                'nombre'         => mb_substr((string) $it['descripcion'], 0, 200),
                'unidad_medida'  => $it['unidad'] ?: 'pieza',
                'categoria'      => !empty($datos['nueva_categoria']) ? $datos['nueva_categoria'] : null,
                'costo_unitario' => $costo,
            ], $usuario_id);
            $creo_refaccion = true;
        }
        db_exec("UPDATE refacciones_requisicion_items SET refaccion_id = :r WHERE id = :id",
            ['r' => $refaccion_id, 'id' => $item_id]);
    }

    // Entrada al almacén (solo si hay refacción de catálogo)
    $mov_id = null;
    if ($refaccion_id) {
        $mov_id = registrar_movimiento([
            'refaccion_id'   => $refaccion_id,
            'sucursal_id'    => (int) $it['sucursal_id'],
            'tipo'           => 'entrada',
            'cantidad'       => $cant,
            'motivo'         => 'compra',
            'notas'          => 'Requisición ' . $it['folio']
                                . (!empty($datos['notas']) ? ' · ' . trim((string) $datos['notas']) : ''),
            'costo_unitario' => $costo,
            'usuario_id'     => $usuario_id,
        ]);
    }

    $recibida = (float) $it['cantidad_recibida'] + $cant;
    $status   = ($recibida + 0.0001) >= (float) $it['cantidad'] ? 'comprado' : 'parcial';

    db_exec(
        "UPDATE refacciones_requisicion_items
            SET cantidad_recibida = :cr, status = :s,
                movimiento_id = COALESCE(:m, movimiento_id)
          WHERE id = :id",
        ['cr' => $recibida, 's' => $status, 'm' => $mov_id, 'id' => $item_id]
    );

    return [
        'afecto_stock'   => $mov_id !== null,
        'movimiento_id'  => $mov_id,
        'refaccion_id'   => $refaccion_id,
        'creo_refaccion' => $creo_refaccion,
        'recibida_total' => $recibida,
        'status'         => $status,
    ];
}

/** Cuántos renglones NO están ligados al catálogo (no afectan almacén). */
function requisicion_items_informativos(int $requisicion_id): int {
    $r = db_one(
        "SELECT COUNT(*) n FROM refacciones_requisicion_items
          WHERE requisicion_id = :id AND refaccion_id IS NULL",
        ['id' => $requisicion_id]
    );
    return (int) ($r['n'] ?? 0);
}

/** ¿Quedan renglones sin recibir por completo? (para avisar al cerrar) */
function requisicion_items_pendientes(int $requisicion_id): int {
    $r = db_one(
        "SELECT COUNT(*) n FROM refacciones_requisicion_items
          WHERE requisicion_id = :id AND status IN ('pendiente','parcial')",
        ['id' => $requisicion_id]
    );
    return (int) ($r['n'] ?? 0);
}
