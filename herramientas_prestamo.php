<?php
/**
 * ============================================================================
 * herramientas_prestamo.php - Procesa préstamos y devoluciones
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/herramientas_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

if (!$puede_gestionar) {
    flash_set('error', 'Sin permiso.');
    header('Location: ' . url('herramientas.php'));
    exit;
}

if (!es_post() || !csrf_valido(input('_csrf'))) {
    flash_set('error', 'Token inválido.');
    header('Location: ' . url('herramientas.php'));
    exit;
}

$op = (string) input('op', '');

try {
    if ($op === 'prestar') {
        $herramienta_id = (int) input('herramienta_id', 0);
        $prestada_a_id = (int) input('prestada_a_id', 0);

        if ($herramienta_id <= 0 || $prestada_a_id <= 0) {
            throw new RuntimeException('Datos del préstamo incompletos.');
        }

        $datos = [
            'herramienta_id' => $herramienta_id,
            'prestada_a_id' => $prestada_a_id,
            'fecha_devolucion_esperada' => trim((string) input('fecha_devolucion_esperada', '')) ?: null,
            'motivo' => trim((string) input('motivo', '')) ?: null,
            'incidencia_id' => (int) input('incidencia_id', 0) ?: null,
            'notas_salida' => trim((string) input('notas_salida', '')) ?: null,
        ];

        $prestamo_id = registrar_prestamo($datos, (int) $u['id']);

        registrar_auditoria('prestar_herramienta', 'herramientas_prestamos', $prestamo_id,
            "Préstamo de herramienta $herramienta_id a usuario $prestada_a_id");

        flash_set('success', 'Préstamo registrado.');
        header('Location: ' . url("herramienta_ver.php?id=$herramienta_id"));
        exit;

    } elseif ($op === 'devolver') {
        $prestamo_id = (int) input('prestamo_id', 0);
        if ($prestamo_id <= 0) {
            throw new RuntimeException('Préstamo no especificado.');
        }

        // Obtener herramienta_id para redirect
        $pres = db_one("SELECT herramienta_id FROM herramientas_prestamos WHERE id = :id",
            ['id' => $prestamo_id]);
        if (!$pres) {
            throw new RuntimeException('Préstamo no encontrado.');
        }

        $datos = [
            'condicion_devolucion' => (string) input('condicion_devolucion', 'buena'),
            'notas_devolucion' => trim((string) input('notas_devolucion', '')) ?: null,
        ];

        registrar_devolucion($prestamo_id, (int) $u['id'], $datos);

        registrar_auditoria('devolver_herramienta', 'herramientas_prestamos', $prestamo_id,
            "Devolución condición: {$datos['condicion_devolucion']}");

        flash_set('success', 'Devolución registrada.');
        header('Location: ' . url("herramienta_ver.php?id=" . $pres['herramienta_id']));
        exit;

    } else {
        throw new RuntimeException('Operación no válida.');
    }
} catch (Throwable $e) {
    flash_set('error', 'Error: ' . $e->getMessage());
    header('Location: ' . url('herramientas.php'));
    exit;
}
