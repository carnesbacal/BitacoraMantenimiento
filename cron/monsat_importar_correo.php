<?php
/**
 * ============================================================================
 * cron/monsat_importar_correo.php
 * ----------------------------------------------------------------------------
 * Importa los reportes de Monsat desde las cuentas IMAP configuradas en la app
 * (Flotilla → Import. Monsat → "Correo automático"). Toma los adjuntos XLS de
 * los correos nuevos y carga el km diario (idempotente).
 *
 * Uso (cPanel cron, CLI):
 *   /usr/local/bin/php /home/USUARIO/.../cron/monsat_importar_correo.php
 *
 * Requiere: extensión IMAP de PHP habilitada.
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/flotilla_helpers.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Solo CLI.\n");
}

if (!function_exists('imap_open')) {
    fwrite(STDERR, "La extensión IMAP de PHP no está habilitada (actívala en cPanel → Select PHP Version → Extensions → imap).\n");
    exit(1);
}

if (!db_one("SHOW TABLES LIKE 'flotilla_monsat_cuentas'")) {
    fwrite(STDERR, "Falta la tabla flotilla_monsat_cuentas. Corre migracion_monsat_cuentas.sql.\n");
    exit(1);
}

$cuentas = db_all("SELECT * FROM flotilla_monsat_cuentas WHERE activo = 1");
if (!$cuentas) {
    echo date('Y-m-d H:i:s') . " Sin cuentas de correo activas.\n";
    exit(0);
}

$g_correos = 0; $g_v = 0; $g_d = 0;

foreach ($cuentas as $c) {
    $res = flotilla_monsat_procesar_cuenta($c, true);
    $resumen = $res['ok']
        ? "{$res['correos']} correo(s), {$res['vehiculos']} veh, {$res['dias']} días"
        : ("ERROR: " . $res['error']);

    db_exec(
        "UPDATE flotilla_monsat_cuentas SET ultima_ejecucion = NOW(), ultimo_resultado = :r WHERE id = :id",
        ['r' => substr($resumen, 0, 255), 'id' => $c['id']]
    );

    echo date('Y-m-d H:i:s') . " [{$c['usuario']}] {$resumen}\n";

    if ($res['ok']) { $g_correos += $res['correos']; $g_v += $res['vehiculos']; $g_d += $res['dias']; }
}

echo date('Y-m-d H:i:s') . " TOTAL: {$g_correos} correo(s), {$g_v} vehículo(s), {$g_d} día(s).\n";
