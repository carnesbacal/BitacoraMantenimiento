<?php
/**
 * ============================================================================
 * config/qr_helpers.php - Etiquetas QR y páginas públicas de artículos
 * ----------------------------------------------------------------------------
 * Cada equipo/herramienta/refacción tiene un token público (columna
 * public_token). El QR apunta a info.php?tipo=...&t=TOKEN, una vista de solo
 * lectura que NO requiere login. Requiere: migracion_qr_tokens.sql
 * ============================================================================
 */

/** Tipos válidos y su configuración. La clave es la que viaja en la URL. */
function qr_tipos(): array {
    return [
        'equipo'      => ['tabla' => 'equipos',      'ficha' => 'equipo_ver.php',      'icono' => 'box',    'label' => 'Equipo'],
        'herramienta' => ['tabla' => 'herramientas', 'ficha' => 'herramienta_ver.php', 'icono' => 'wrench', 'label' => 'Herramienta'],
        'refaccion'   => ['tabla' => 'refacciones',  'ficha' => 'refaccion_ver.php',   'icono' => 'package', 'label' => 'Refacción'],
    ];
}

/** Configuración de un tipo (o null si no es válido). Blinda contra inyección de tabla. */
function qr_tipo_cfg(string $tipo): ?array {
    $t = qr_tipos();
    return $t[$tipo] ?? null;
}

/** ¿La tabla del tipo ya tiene la columna public_token? */
function qr_disponible(string $tipo): bool {
    $cfg = qr_tipo_cfg($tipo);
    if (!$cfg) return false;
    try {
        return (bool) db_one(
            "SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'public_token'",
            ['t' => $cfg['tabla']]
        );
    } catch (Throwable $e) { return false; }
}

/** Devuelve el token público del artículo; si no tiene, lo genera y lo guarda. */
function qr_token_de(string $tipo, int $id): ?string {
    $cfg = qr_tipo_cfg($tipo);
    if (!$cfg || $id <= 0 || !qr_disponible($tipo)) return null;
    $tabla = $cfg['tabla'];

    $row = db_one("SELECT public_token FROM {$tabla} WHERE id = :id", ['id' => $id]);
    if (!$row) return null;
    if (!empty($row['public_token'])) return (string) $row['public_token'];

    // Generar token único (reintenta ante colisión, muy improbable).
    for ($i = 0; $i < 5; $i++) {
        $token = bin2hex(random_bytes(12));   // 24 hex
        if (db_one("SELECT id FROM {$tabla} WHERE public_token = :t", ['t' => $token])) continue;
        db_exec("UPDATE {$tabla} SET public_token = :t WHERE id = :id AND (public_token IS NULL OR public_token = '')",
            ['t' => $token, 'id' => $id]);
        $actual = db_one("SELECT public_token FROM {$tabla} WHERE id = :id", ['id' => $id]);
        return (string) ($actual['public_token'] ?? $token);
    }
    return null;
}

/** URL pública absoluta del artículo (para el QR). */
function qr_url_publica(string $tipo, string $token): string {
    $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
    return $base . '/info.php?tipo=' . rawurlencode($tipo) . '&t=' . rawurlencode($token);
}

/** Busca el artículo por tipo + token público. Devuelve el registro completo o null. */
function qr_buscar(string $tipo, string $token): ?array {
    $cfg = qr_tipo_cfg($tipo);
    if (!$cfg || $token === '' || !qr_disponible($tipo)) return null;
    $tabla = $cfg['tabla'];
    return db_one("SELECT * FROM {$tabla} WHERE public_token = :t LIMIT 1", ['t' => $token]);
}
