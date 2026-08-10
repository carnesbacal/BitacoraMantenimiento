<?php
/**
 * ============================================================================
 * cron/backup_diario.php
 * ============================================================================
 * Script para tarea programada que genera un backup automático.
 *
 * Configuración en Windows Task Scheduler:
 *   Programa:    C:\xampp\php\php.exe
 *   Argumentos:  "C:\xampp\htdocs\UtilidadesBacal\BitacoraSistemas\cron\backup_diario.php"
 *   Frecuencia:  Diaria a las 2:00 AM (recomendado)
 *
 * También puedes correrlo manualmente desde una terminal:
 *   php cron/backup_diario.php
 *
 * No requiere autenticación (no es accesible desde web por estar en carpeta cron/).
 * ============================================================================
 */

// Solo permitir ejecución desde CLI (línea de comandos)
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Este script solo puede ejecutarse desde la línea de comandos.\n");
}

// Cargar dependencias
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/backups_helpers.php';

// Encabezado
$inicio = microtime(true);
$fecha = date('Y-m-d H:i:s');

echo "============================================================\n";
echo "Backup automático - " . EMPRESA_NOMBRE . " Bitácora\n";
echo "Inicio: $fecha\n";
echo "============================================================\n";

// Detectar método
$mysqldump = detectar_mysqldump();
echo "Método: " . ($mysqldump !== null ? "mysqldump ($mysqldump)" : "PHP puro (fallback)") . "\n\n";

// Generar
echo "Generando backup...\n";
$resultado = generar_backup('automatico', null, 'Backup automático programado');

$duracion = round(microtime(true) - $inicio, 2);

if ($resultado['ok']) {
    $tam = fmt_bytes($resultado['tamano']);
    echo "✓ ÉXITO\n";
    echo "  Archivo: {$resultado['archivo']}\n";
    echo "  Tamaño:  $tam\n";
    echo "  Tiempo:  {$duracion}s\n";
    exit(0);
} else {
    echo "✗ ERROR\n";
    echo "  Mensaje: {$resultado['mensaje']}\n";
    echo "  Tiempo:  {$duracion}s\n";

    // Salir con código de error para que Task Scheduler lo marque como fallido
    exit(1);
}
