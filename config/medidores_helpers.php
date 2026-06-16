<?php
/**
 * ============================================================================
 * config/medidores_helpers.php  (BitacoraMantenimiento)
 * ============================================================================
 * Funciones para el módulo de medidores (servicios/consumos).
 *
 *   medidor_tipos    → catálogo configurable (luz, agua, gas, diésel...)
 *   medidores        → cada aparato físico, con su tarifa por unidad
 *   medidor_lecturas → histórico; consumo y costo se calculan al guardar
 *
 * El consumo = lectura actual - lectura anterior (no se captura).
 * La tarifa y el costo se CONGELAN en cada lectura.
 * ============================================================================
 */

require_once __DIR__ . '/db.php';

// ============================================================================
// TIPOS DE MEDIDOR (catálogo)
// ============================================================================

function listar_tipos_medidor(bool $solo_activos = false): array {
    $where = $solo_activos ? 'WHERE activo = 1' : '';
    return db_all("SELECT * FROM medidor_tipos $where ORDER BY nombre ASC");
}

function obtener_tipo_medidor(int $id): ?array {
    return db_one("SELECT * FROM medidor_tipos WHERE id = :id", ['id' => $id]);
}

function crear_tipo_medidor(array $d): int {
    db_exec(
        "INSERT INTO medidor_tipos (nombre, unidad, icono, color, activo)
         VALUES (:n, :u, :i, :c, 1)",
        [
            'n' => trim((string) $d['nombre']),
            'u' => trim((string) $d['unidad']),
            'i' => !empty($d['icono']) ? trim((string) $d['icono']) : null,
            'c' => !empty($d['color']) ? trim((string) $d['color']) : null,
        ]
    );
    return (int) db_last_id();
}

function actualizar_tipo_medidor(int $id, array $d): void {
    db_exec(
        "UPDATE medidor_tipos SET nombre = :n, unidad = :u, icono = :i, color = :c WHERE id = :id",
        [
            'n' => trim((string) $d['nombre']),
            'u' => trim((string) $d['unidad']),
            'i' => !empty($d['icono']) ? trim((string) $d['icono']) : null,
            'c' => !empty($d['color']) ? trim((string) $d['color']) : null,
            'id' => $id,
        ]
    );
}

function cambiar_estado_tipo_medidor(int $id, bool $activo): void {
    db_exec("UPDATE medidor_tipos SET activo = :a WHERE id = :id", ['a' => $activo ? 1 : 0, 'id' => $id]);
}

/** ¿Cuántos medidores usan este tipo? (para no dejar borrar tipos en uso) */
function tipo_medidor_en_uso(int $id): int {
    $r = db_one("SELECT COUNT(*) n FROM medidores WHERE tipo_id = :id", ['id' => $id]);
    return (int) ($r['n'] ?? 0);
}

// ============================================================================
// MEDIDORES
// ============================================================================

/**
 * Lista medidores con su tipo, sucursal y la última lectura registrada.
 * $filtros: ['sucursal_id'=>int, 'tipo_id'=>int, 'solo_activos'=>bool, 'busqueda'=>string]
 */
function listar_medidores(array $filtros = []): array {
    $where = [];
    $params = [];
    if (!empty($filtros['sucursal_id'])) { $where[] = 'm.sucursal_id = :sid'; $params['sid'] = (int) $filtros['sucursal_id']; }
    if (!empty($filtros['tipo_id']))     { $where[] = 'm.tipo_id = :tid';     $params['tid'] = (int) $filtros['tipo_id']; }
    if (!empty($filtros['solo_activos'])) { $where[] = 'm.activo = 1'; }
    if (!empty($filtros['busqueda'])) {
        $where[] = '(m.nombre LIKE :q OR m.numero_serie LIKE :q OR m.ubicacion LIKE :q)';
        $params['q'] = '%' . $filtros['busqueda'] . '%';
    }
    $wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    return db_all(
        "SELECT m.*,
            t.nombre tipo_nombre, t.unidad, t.icono tipo_icono, t.color tipo_color,
            s.nombre sucursal_nombre, s.codigo sucursal_codigo,
            a.nombre area_nombre,
            (SELECT l.valor_lectura FROM medidor_lecturas l WHERE l.medidor_id = m.id ORDER BY l.fecha_lectura DESC, l.id DESC LIMIT 1) ultima_valor,
            (SELECT l.fecha_lectura FROM medidor_lecturas l WHERE l.medidor_id = m.id ORDER BY l.fecha_lectura DESC, l.id DESC LIMIT 1) ultima_fecha,
            (SELECT l.consumo FROM medidor_lecturas l WHERE l.medidor_id = m.id ORDER BY l.fecha_lectura DESC, l.id DESC LIMIT 1) ultimo_consumo
         FROM medidores m
         INNER JOIN medidor_tipos t ON m.tipo_id = t.id
         INNER JOIN sucursales s ON m.sucursal_id = s.id
         LEFT JOIN areas a ON m.area_id = a.id
         $wsql
         ORDER BY t.nombre ASC, m.nombre ASC",
        $params
    );
}

function obtener_medidor(int $id): ?array {
    return db_one(
        "SELECT m.*,
            t.nombre tipo_nombre, t.unidad, t.icono tipo_icono, t.color tipo_color,
            s.nombre sucursal_nombre, s.codigo sucursal_codigo,
            a.nombre area_nombre
         FROM medidores m
         INNER JOIN medidor_tipos t ON m.tipo_id = t.id
         INNER JOIN sucursales s ON m.sucursal_id = s.id
         LEFT JOIN areas a ON m.area_id = a.id
         WHERE m.id = :id",
        ['id' => $id]
    );
}

function medidores_por_sucursal(int $sucursal_id): array {
    return listar_medidores(['sucursal_id' => $sucursal_id, 'solo_activos' => true]);
}

function crear_medidor(array $d, int $usuario_id): int {
    db_exec(
        "INSERT INTO medidores
         (tipo_id, nombre, numero_serie, sucursal_id, area_id, ubicacion, tarifa, valor_inicial, activo, notas, creado_por_id)
         VALUES (:t, :n, :ns, :s, :a, :ub, :tar, :vi, 1, :no, :uid)",
        [
            't'   => (int) $d['tipo_id'],
            'n'   => trim((string) $d['nombre']),
            'ns'  => !empty($d['numero_serie']) ? trim((string) $d['numero_serie']) : null,
            's'   => (int) $d['sucursal_id'],
            'a'   => !empty($d['area_id']) ? (int) $d['area_id'] : null,
            'ub'  => !empty($d['ubicacion']) ? trim((string) $d['ubicacion']) : null,
            'tar' => ($d['tarifa'] ?? '') !== '' ? (float) $d['tarifa'] : null,
            'vi'  => ($d['valor_inicial'] ?? '') !== '' ? (float) $d['valor_inicial'] : null,
            'no'  => !empty($d['notas']) ? trim((string) $d['notas']) : null,
            'uid' => $usuario_id,
        ]
    );
    return (int) db_last_id();
}

function actualizar_medidor(int $id, array $d): void {
    db_exec(
        "UPDATE medidores SET
            tipo_id = :t, nombre = :n, numero_serie = :ns, sucursal_id = :s,
            area_id = :a, ubicacion = :ub, tarifa = :tar, notas = :no
         WHERE id = :id",
        [
            't'   => (int) $d['tipo_id'],
            'n'   => trim((string) $d['nombre']),
            'ns'  => !empty($d['numero_serie']) ? trim((string) $d['numero_serie']) : null,
            's'   => (int) $d['sucursal_id'],
            'a'   => !empty($d['area_id']) ? (int) $d['area_id'] : null,
            'ub'  => !empty($d['ubicacion']) ? trim((string) $d['ubicacion']) : null,
            'tar' => ($d['tarifa'] ?? '') !== '' ? (float) $d['tarifa'] : null,
            'no'  => !empty($d['notas']) ? trim((string) $d['notas']) : null,
            'id'  => $id,
        ]
    );
}

function cambiar_estado_medidor(int $id, bool $activo): void {
    db_exec("UPDATE medidores SET activo = :a WHERE id = :id", ['a' => $activo ? 1 : 0, 'id' => $id]);
}

// ============================================================================
// LECTURAS
// ============================================================================

/** Última lectura registrada de un medidor (la más reciente). */
function ultima_lectura(int $medidor_id): ?array {
    return db_one(
        "SELECT * FROM medidor_lecturas
         WHERE medidor_id = :id
         ORDER BY fecha_lectura DESC, id DESC LIMIT 1",
        ['id' => $medidor_id]
    );
}

/**
 * Registra una lectura. Calcula el consumo (vs la lectura anterior) y
 * congela tarifa y costo. Devuelve [id, consumo, costo, advertencia].
 *
 * $datos: valor_lectura (req), fecha_lectura, es_reinicio, nota, foto
 */
function registrar_lectura(int $medidor_id, array $datos, int $usuario_id): array {
    $medidor = obtener_medidor($medidor_id);
    if (!$medidor) throw new RuntimeException('Medidor no encontrado.');

    $valor = (float) $datos['valor_lectura'];
    $fecha = !empty($datos['fecha_lectura']) ? (string) $datos['fecha_lectura'] : date('Y-m-d');
    $es_reinicio = !empty($datos['es_reinicio']) ? 1 : 0;

    $anterior = ultima_lectura($medidor_id);

    $consumo = null;
    $advertencia = null;
    if ($es_reinicio) {
        $consumo = null; // tras un reinicio no se calcula consumo
    } elseif ($anterior) {
        $val_ant = (float) $anterior['valor_lectura'];
        if ($valor >= $val_ant) {
            $consumo = round($valor - $val_ant, 3);
        } else {
            // La lectura es menor que la anterior: probable error o reinicio
            $advertencia = 'La lectura es menor que la anterior (' . $val_ant . '). '
                . 'Si el medidor se reinició o se reemplazó, márcalo como reinicio.';
        }
    }
    // Si no hay anterior, es la primera lectura: consumo queda null (lectura base)

    $tarifa = ($medidor['tarifa'] !== null) ? (float) $medidor['tarifa'] : null;
    $costo = ($consumo !== null && $tarifa !== null) ? round($consumo * $tarifa, 2) : null;

    db_exec(
        "INSERT INTO medidor_lecturas
         (medidor_id, fecha_lectura, valor_lectura, consumo, tarifa_aplicada, costo, es_reinicio, leido_por_id, foto, nota)
         VALUES (:m, :f, :v, :c, :tar, :costo, :r, :uid, :foto, :nota)",
        [
            'm' => $medidor_id, 'f' => $fecha, 'v' => $valor,
            'c' => $consumo, 'tar' => $tarifa, 'costo' => $costo, 'r' => $es_reinicio,
            'uid' => $usuario_id,
            'foto' => !empty($datos['foto']) ? (string) $datos['foto'] : null,
            'nota' => !empty($datos['nota']) ? trim((string) $datos['nota']) : null,
        ]
    );

    return [
        'id' => (int) db_last_id(),
        'consumo' => $consumo,
        'costo' => $costo,
        'advertencia' => $advertencia,
    ];
}

function obtener_lectura(int $id): ?array {
    return db_one(
        "SELECT l.*, u.nombre_completo leido_por_nombre,
                m.nombre medidor_nombre, t.unidad
         FROM medidor_lecturas l
         LEFT JOIN usuarios u ON l.leido_por_id = u.id
         INNER JOIN medidores m ON l.medidor_id = m.id
         INNER JOIN medidor_tipos t ON m.tipo_id = t.id
         WHERE l.id = :id",
        ['id' => $id]
    );
}

/** Histórico de lecturas de un medidor (más reciente primero). */
function lecturas_medidor(int $medidor_id, int $limite = 100, ?string $desde = null, ?string $hasta = null): array {
    $where = ['l.medidor_id = :id'];
    $params = ['id' => $medidor_id];
    if ($desde) { $where[] = 'l.fecha_lectura >= :d'; $params['d'] = $desde; }
    if ($hasta) { $where[] = 'l.fecha_lectura <= :h'; $params['h'] = $hasta; }
    $wsql = 'WHERE ' . implode(' AND ', $where);

    return db_all(
        "SELECT l.*, u.nombre_completo leido_por_nombre
         FROM medidor_lecturas l
         LEFT JOIN usuarios u ON l.leido_por_id = u.id
         $wsql
         ORDER BY l.fecha_lectura DESC, l.id DESC
         LIMIT $limite",
        $params
    );
}

function eliminar_lectura(int $id): void {
    db_exec("DELETE FROM medidor_lecturas WHERE id = :id", ['id' => $id]);
}

// ============================================================================
// ESTADÍSTICAS Y ANOMALÍAS
// ============================================================================

/** Stats de consumo de un medidor (promedio, máximo, total) sobre últimas N lecturas. */
function medidor_stats(int $medidor_id, int $ultimas = 30): array {
    $row = db_one(
        "SELECT
            COUNT(*) num,
            AVG(consumo) consumo_prom,
            MAX(consumo) consumo_max,
            SUM(consumo) consumo_total,
            SUM(costo) costo_total
         FROM (
            SELECT consumo, costo FROM medidor_lecturas
            WHERE medidor_id = :id AND consumo IS NOT NULL
            ORDER BY fecha_lectura DESC, id DESC LIMIT $ultimas
         ) t",
        ['id' => $medidor_id]
    );
    return [
        'num'           => (int) ($row['num'] ?? 0),
        'consumo_prom'  => $row['consumo_prom'] !== null ? round((float) $row['consumo_prom'], 3) : null,
        'consumo_max'   => $row['consumo_max'] !== null ? (float) $row['consumo_max'] : null,
        'consumo_total' => (float) ($row['consumo_total'] ?? 0),
        'costo_total'   => (float) ($row['costo_total'] ?? 0),
    ];
}

/**
 * Evalúa si un consumo es anómalo comparado con el promedio histórico.
 * Devuelve ['anomalo'=>bool, 'nivel'=>'normal|alto|muy_alto', 'prom'=>float|null].
 */
function consumo_anomalo(int $medidor_id, float $consumo, float $factor_alto = 1.5, float $factor_muy_alto = 2.5): array {
    $stats = medidor_stats($medidor_id, 30);
    $prom = $stats['consumo_prom'];
    if ($prom === null || $prom <= 0 || $stats['num'] < 3) {
        return ['anomalo' => false, 'nivel' => 'normal', 'prom' => $prom];
    }
    if ($consumo >= $prom * $factor_muy_alto) {
        return ['anomalo' => true, 'nivel' => 'muy_alto', 'prom' => $prom];
    }
    if ($consumo >= $prom * $factor_alto) {
        return ['anomalo' => true, 'nivel' => 'alto', 'prom' => $prom];
    }
    return ['anomalo' => false, 'nivel' => 'normal', 'prom' => $prom];
}

/** Tendencia de consumo/costo de un medidor agrupada por día/mes (para gráficas). */
function consumo_tendencia(int $medidor_id, string $agrupar = 'dia', int $limite = 60): array {
    $grupo = $agrupar === 'mes'
        ? "DATE_FORMAT(fecha_lectura, '%Y-%m')"
        : "fecha_lectura";
    $label = $agrupar === 'mes'
        ? "DATE_FORMAT(fecha_lectura, '%m/%Y')"
        : "DATE_FORMAT(fecha_lectura, '%d/%m')";

    $rows = db_all(
        "SELECT $grupo periodo, MIN($label) label,
                SUM(consumo) consumo, SUM(costo) costo
         FROM medidor_lecturas
         WHERE medidor_id = :id AND consumo IS NOT NULL
         GROUP BY $grupo
         ORDER BY periodo DESC
         LIMIT $limite",
        ['id' => $medidor_id]
    );
    return array_reverse($rows);
}

/** Resumen de consumo/costo por tipo en un período (para reportes y dashboard). */
function consumo_por_tipo(string $desde, string $hasta, ?int $sucursal_id = null): array {
    $where = ['l.fecha_lectura BETWEEN :d AND :h', 'l.consumo IS NOT NULL'];
    $params = ['d' => $desde, 'h' => $hasta];
    if ($sucursal_id) { $where[] = 'm.sucursal_id = :s'; $params['s'] = $sucursal_id; }
    $wsql = 'WHERE ' . implode(' AND ', $where);

    return db_all(
        "SELECT t.id, t.nombre, t.unidad, t.icono, t.color,
                COUNT(l.id) num_lecturas,
                SUM(l.consumo) consumo_total,
                SUM(l.costo) costo_total
         FROM medidor_lecturas l
         INNER JOIN medidores m ON l.medidor_id = m.id
         INNER JOIN medidor_tipos t ON m.tipo_id = t.id
         $wsql
         GROUP BY t.id, t.nombre, t.unidad, t.icono, t.color
         ORDER BY costo_total DESC",
        $params
    );
}

/** Resumen general del período (totales para dashboard/KPIs). */
function consumo_resumen_periodo(string $desde, string $hasta, ?int $sucursal_id = null): array {
    $where = ['l.fecha_lectura BETWEEN :d AND :h', 'l.consumo IS NOT NULL'];
    $params = ['d' => $desde, 'h' => $hasta];
    if ($sucursal_id) { $where[] = 'm.sucursal_id = :s'; $params['s'] = $sucursal_id; }
    $wsql = 'WHERE ' . implode(' AND ', $where);

    $row = db_one(
        "SELECT COUNT(l.id) num_lecturas,
                COUNT(DISTINCT l.medidor_id) medidores_activos,
                SUM(l.costo) costo_total
         FROM medidor_lecturas l
         INNER JOIN medidores m ON l.medidor_id = m.id
         $wsql",
        $params
    );
    return [
        'num_lecturas'      => (int) ($row['num_lecturas'] ?? 0),
        'medidores_activos' => (int) ($row['medidores_activos'] ?? 0),
        'costo_total'       => (float) ($row['costo_total'] ?? 0),
    ];
}

// ============================================================================
// FORMATEO
// ============================================================================

/** Formatea un consumo con su unidad: 1234.5 + "kWh" → "1,234.5 kWh". */
function fmt_consumo(?float $valor, string $unidad = ''): string {
    if ($valor === null) return '—';
    $n = rtrim(rtrim(number_format($valor, 3, '.', ','), '0'), '.');
    return $unidad !== '' ? "$n $unidad" : $n;
}

function fmt_lectura(?float $valor): string {
    if ($valor === null) return '—';
    return rtrim(rtrim(number_format($valor, 3, '.', ','), '0'), '.');
}

// ============================================================================
// VISIBILIDAD POR SUCURSAL
// ============================================================================

/**
 * Sucursal a la que está restringido el usuario actual para ver medidores.
 *   - null  → ve TODAS (admin o permiso ver_todas_sucursales) — "ambas sucursales"
 *   - int>0 → solo esa sucursal (la suya)
 *   - 0     → sin sucursal asignada (no ve ninguna)
 */
function medidor_sucursal_usuario(): ?int {
    if (tiene_permiso('ver_todas_sucursales') || tiene_permiso('administrar')) return null;
    $u = usuario_actual();
    return !empty($u['sucursal_id']) ? (int) $u['sucursal_id'] : 0;
}

/** ¿Puede el usuario actual ver/usar este medidor según su sucursal? */
function puede_ver_medidor(array $medidor): bool {
    $r = medidor_sucursal_usuario();
    if ($r === null) return true;
    return $r > 0 && (int) $medidor['sucursal_id'] === $r;
}
