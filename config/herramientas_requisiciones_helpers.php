<?php
/**
 * ============================================================================
 * config/herramientas_requisiciones_helpers.php
 * ----------------------------------------------------------------------------
 * Requisiciones de compra de HERRAMIENTAS. Espejo del módulo de refacciones,
 * adaptado a activos únicos: al recibir, opcionalmente se da de alta la(s)
 * herramienta(s) en el catálogo (una por pieza) o solo se marca como comprado.
 *
 * Flujo: borrador → enviada → autorizada → cerrada  (o cancelada).
 * Requiere: migracion_requisiciones_herramientas.sql
 * Reutiliza estados/razones sociales del módulo de refacciones.
 * ============================================================================
 */

require_once __DIR__ . '/requisiciones_helpers.php';   // estados, item_status, empresas
require_once __DIR__ . '/herramientas_helpers.php';    // crear_herramienta

/** ¿Ya se corrió la migración? */
function hreq_disponible(): bool {
    try { return (bool) db_one("SHOW TABLES LIKE 'herramienta_requisiciones'"); }
    catch (Throwable $e) { return false; }
}

/** Siguiente folio: RQH-{SUCURSAL}-{AÑO}-{consecutivo}. Independiente por sucursal/año. */
function hreq_siguiente_folio(string $sucursal_codigo = ''): string {
    $anio = date('Y');
    $cod  = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $sucursal_codigo));
    $cod  = $cod !== '' ? substr($cod, 0, 6) : 'GRAL';
    $prefijo = "RQH-{$cod}-{$anio}-";
    $row = db_one(
        "SELECT folio FROM herramienta_requisiciones WHERE folio LIKE :p ORDER BY folio DESC LIMIT 1",
        ['p' => $prefijo . '%']
    );
    $n = 1;
    if ($row && preg_match('/-(\d+)$/', (string) $row['folio'], $m)) $n = (int) $m[1] + 1;
    return $prefijo . sprintf('%04d', $n);
}

/** Siguiente código de herramienta tipo HER-#### para altas automáticas al recibir. */
function hreq_siguiente_codigo_herramienta(): string {
    for ($i = 0; $i < 50; $i++) {
        $row = db_one(
            "SELECT codigo FROM herramientas WHERE codigo LIKE 'HER-%' ORDER BY LENGTH(codigo) DESC, codigo DESC LIMIT 1"
        );
        $n = 1;
        if ($row && preg_match('/(\d+)$/', (string) $row['codigo'], $m)) $n = (int) $m[1] + 1 + $i;
        else $n = 1 + $i;
        $codigo = 'HER-' . sprintf('%04d', $n);
        if (!db_one("SELECT id FROM herramientas WHERE codigo = :c", ['c' => $codigo])) return $codigo;
    }
    return 'HER-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/** Crea una requisición en borrador y devuelve su id. */
function hreq_crear(int $sucursal_id, string $fecha, int $solicito_id, ?string $notas, int $usuario_id, string $razon_social = 'corral'): int {
    if ($sucursal_id <= 0) throw new RuntimeException('Selecciona la sucursal.');
    if ($fecha === '')     $fecha = date('Y-m-d');
    if (!array_key_exists($razon_social, requisicion_empresas())) $razon_social = 'corral';

    $suc = db_one("SELECT codigo FROM sucursales WHERE id = :id", ['id' => $sucursal_id]);
    $suc_codigo = (string) ($suc['codigo'] ?? '');

    for ($intento = 0; $intento < 5; $intento++) {
        try {
            db_exec(
                "INSERT INTO herramienta_requisiciones
                    (folio, sucursal_id, fecha, razon_social, solicito_id, estado, notas, creado_por_id)
                 VALUES (:f, :s, :fe, :rs, :sol, 'borrador', :n, :u)",
                ['f' => hreq_siguiente_folio($suc_codigo), 's' => $sucursal_id, 'fe' => $fecha,
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
function hreq_obtener(int $id): ?array {
    return db_one(
        "SELECT r.*, s.nombre AS sucursal_nombre, s.codigo AS sucursal_codigo,
                sol.nombre_completo AS solicito_nombre,
                aut.nombre_completo AS autorizo_nombre
           FROM herramienta_requisiciones r
           INNER JOIN sucursales s ON r.sucursal_id = s.id
           INNER JOIN usuarios sol ON r.solicito_id = sol.id
           LEFT  JOIN usuarios aut ON r.autorizado_por_id = aut.id
          WHERE r.id = :id",
        ['id' => $id]
    );
}

/** Renglones de una requisición, en orden. */
function hreq_items(int $requisicion_id): array {
    return db_all(
        "SELECT i.*, h.codigo AS herramienta_codigo, h.nombre AS herramienta_nombre, a.nombre AS area_nombre
           FROM herramienta_requisicion_items i
           LEFT JOIN herramientas h ON i.herramienta_id = h.id
           LEFT JOIN areas a        ON i.area_id = a.id
          WHERE i.requisicion_id = :id
          ORDER BY i.orden ASC, i.id ASC",
        ['id' => $requisicion_id]
    );
}

/** Listado con filtros: sucursal, estado, texto y rango de fechas. */
function hreq_listar(array $filtros = [], int $limite = 200): array {
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
                (SELECT COUNT(*) FROM herramienta_requisicion_items i WHERE i.requisicion_id = r.id) AS num_items,
                (SELECT COUNT(*) FROM herramienta_requisicion_items i WHERE i.requisicion_id = r.id AND i.status = 'pendiente') AS num_pendientes
           FROM herramienta_requisiciones r
           INNER JOIN sucursales s ON r.sucursal_id = s.id
           INNER JOIN usuarios sol ON r.solicito_id = sol.id
           $sql_where
          ORDER BY r.fecha DESC, r.id DESC
          LIMIT $limite",
        $params
    );
}

/** Agrega un renglón. $herramienta_id NULL = texto libre. */
function hreq_item_agregar(int $requisicion_id, array $datos): int {
    $desc = trim((string) ($datos['descripcion'] ?? ''));
    if ($desc === '') throw new RuntimeException('La descripción del renglón es obligatoria.');
    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad debe ser mayor a 0.');

    $orden = (int) (db_one(
        "SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM herramienta_requisicion_items WHERE requisicion_id = :id",
        ['id' => $requisicion_id]
    )['n'] ?? 1);

    db_exec(
        "INSERT INTO herramienta_requisicion_items
            (requisicion_id, herramienta_id, descripcion, cantidad, unidad, area_id, status, notas, orden)
         VALUES (:req, :her, :d, :c, :u, :a, 'pendiente', :n, :o)",
        [
            'req' => $requisicion_id,
            'her' => !empty($datos['herramienta_id']) ? (int) $datos['herramienta_id'] : null,
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
function hreq_item_actualizar(int $item_id, array $datos): void {
    $desc = trim((string) ($datos['descripcion'] ?? ''));
    if ($desc === '') throw new RuntimeException('La descripción del renglón es obligatoria.');
    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad debe ser mayor a 0.');
    $status = (string) ($datos['status'] ?? 'pendiente');
    if (!array_key_exists($status, requisicion_item_status())) $status = 'pendiente';

    db_exec(
        "UPDATE herramienta_requisicion_items
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
function hreq_item_eliminar(int $item_id): void {
    db_exec("DELETE FROM herramienta_requisicion_items WHERE id = :id", ['id' => $item_id]);
}

/** Cambia el estado de la requisición. Al autorizar guarda quién y cuándo. */
function hreq_cambiar_estado(int $id, string $estado, int $usuario_id): void {
    if (!array_key_exists($estado, requisicion_estados())) {
        throw new RuntimeException('Estado no válido.');
    }
    if ($estado === 'autorizada') {
        db_exec(
            "UPDATE herramienta_requisiciones
                SET estado = 'autorizada', autorizado_por_id = :u, autorizado_en = NOW()
              WHERE id = :id",
            ['u' => $usuario_id, 'id' => $id]
        );
    } else {
        db_exec("UPDATE herramienta_requisiciones SET estado = :e WHERE id = :id",
            ['e' => $estado, 'id' => $id]);
    }
}

/** Actualiza los datos de cabecera (fecha, notas, razón social). */
function hreq_actualizar(int $id, string $fecha, ?string $notas, ?string $razon_social = null): void {
    if ($razon_social !== null && array_key_exists($razon_social, requisicion_empresas())) {
        db_exec("UPDATE herramienta_requisiciones SET fecha = :f, notas = :n, razon_social = :rs WHERE id = :id",
            ['f' => $fecha ?: date('Y-m-d'), 'n' => $notas ?: null, 'rs' => $razon_social, 'id' => $id]);
        return;
    }
    db_exec("UPDATE herramienta_requisiciones SET fecha = :f, notas = :n WHERE id = :id",
        ['f' => $fecha ?: date('Y-m-d'), 'n' => $notas ?: null, 'id' => $id]);
}

/** Elimina la requisición completa y sus renglones. */
function hreq_eliminar(int $id): void {
    db_exec("DELETE FROM herramienta_requisicion_items WHERE requisicion_id = :id", ['id' => $id]);
    db_exec("DELETE FROM herramienta_requisiciones WHERE id = :id", ['id' => $id]);
}

/**
 * Registra la RECEPCIÓN de un renglón.
 * - Siempre acumula lo recibido y ajusta el status (parcial/comprado).
 * - Si $datos['crear_herramienta'] es verdadero, da de alta en el catálogo UNA
 *   herramienta por cada pieza recibida (código autogenerado HER-####), con la
 *   sucursal de la requisición, el costo y el tipo indicados. Si no, solo marca.
 * Devuelve ['recibida_total','status','creadas'=>int,'ids'=>[...]].
 */
function hreq_item_recibir(int $item_id, array $datos, int $usuario_id): array {
    $it = db_one(
        "SELECT i.*, r.sucursal_id, r.folio
           FROM herramienta_requisicion_items i
           INNER JOIN herramienta_requisiciones r ON i.requisicion_id = r.id
          WHERE i.id = :id",
        ['id' => $item_id]
    );
    if (!$it) throw new RuntimeException('Renglón no encontrado.');

    $cant = (float) ($datos['cantidad'] ?? 0);
    if ($cant <= 0) throw new RuntimeException('La cantidad recibida debe ser mayor a 0.');

    $costo = (isset($datos['costo_unitario']) && $datos['costo_unitario'] !== '')
        ? (float) $datos['costo_unitario'] : null;

    $creadas = 0; $ids = [];

    if (!empty($datos['crear_herramienta'])) {
        $piezas = max(1, (int) round($cant));
        $nombre = mb_substr((string) $it['descripcion'], 0, 200);
        $tipo   = !empty($datos['tipo']) ? trim((string) $datos['tipo']) : null;
        $ubic   = !empty($datos['ubicacion']) ? trim((string) $datos['ubicacion']) : null;
        for ($k = 0; $k < $piezas; $k++) {
            $nid = crear_herramienta([
                'codigo'            => hreq_siguiente_codigo_herramienta(),
                'nombre'            => $nombre,
                'tipo'              => $tipo,
                'sucursal_id'       => (int) $it['sucursal_id'],
                'ubicacion'         => $ubic,
                'estado'            => 'disponible',
                'fecha_adquisicion' => date('Y-m-d'),
                'costo'             => $costo,
                'notas'             => 'Alta por requisición ' . $it['folio'],
            ], $usuario_id);
            $creadas++; $ids[] = $nid;
        }
    }

    $recibida = (float) $it['cantidad_recibida'] + $cant;
    $status   = ($recibida + 0.0001) >= (float) $it['cantidad'] ? 'comprado' : 'parcial';

    db_exec(
        "UPDATE herramienta_requisicion_items SET cantidad_recibida = :cr, status = :s WHERE id = :id",
        ['cr' => $recibida, 's' => $status, 'id' => $item_id]
    );

    return ['recibida_total' => $recibida, 'status' => $status, 'creadas' => $creadas, 'ids' => $ids];
}

/** ¿Quedan renglones sin recibir por completo? (para avisar al cerrar) */
function hreq_items_pendientes(int $requisicion_id): int {
    $r = db_one(
        "SELECT COUNT(*) n FROM herramienta_requisicion_items
          WHERE requisicion_id = :id AND status IN ('pendiente','parcial')",
        ['id' => $requisicion_id]
    );
    return (int) ($r['n'] ?? 0);
}
