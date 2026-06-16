<?php
/**
 * ============================================================================
 * refacciones_movimientos.php - Procesa movimientos de stock
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$redirect_a = (string) input('redirect_a', 'refacciones.php');
$redirect_url = url($redirect_a);

if (!$puede_gestionar) {
    flash_set('error', 'Sin permiso para registrar movimientos.');
    header('Location: ' . $redirect_url);
    exit;
}

if (!es_post() || !csrf_valido(input('_csrf'))) {
    flash_set('error', 'Token inválido.');
    header('Location: ' . $redirect_url);
    exit;
}

$refaccion_id = (int) input('refaccion_id', 0);
$sucursal_id = (int) input('sucursal_id', 0);
$tipo = (string) input('tipo', '');
$cantidad = (float) input('cantidad', 0);
$motivo = trim((string) input('motivo', '')) ?: null;
$notas = trim((string) input('notas', '')) ?: null;
$incidencia_id = (int) input('incidencia_id', 0) ?: null;
$componente_id = (int) input('componente_id', 0) ?: null;
$sucursal_destino_id = (int) input('sucursal_destino_id', 0) ?: null;
$costo_unitario = (float) input('costo_unitario', 0) ?: null;

if ($refaccion_id <= 0 || $sucursal_id <= 0 || $cantidad < 0 || !in_array($tipo, ['entrada','salida','ajuste','transferencia'], true)) {
    flash_set('error', 'Datos del movimiento incompletos o inválidos.');
    header('Location: ' . $redirect_url);
    exit;
}

if ($cantidad == 0 && $tipo !== 'ajuste') {
    flash_set('error', 'La cantidad debe ser mayor a 0.');
    header('Location: ' . $redirect_url);
    exit;
}

try {
    $mov_id = registrar_movimiento([
        'refaccion_id' => $refaccion_id,
        'sucursal_id' => $sucursal_id,
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'motivo' => $motivo,
        'notas' => $notas,
        'incidencia_id' => $incidencia_id,
        'componente_id' => $componente_id,
        'sucursal_destino_id' => $sucursal_destino_id,
        'costo_unitario' => $costo_unitario,
        'usuario_id' => (int) $u['id'],
    ]);

    registrar_auditoria('movimiento_refaccion', 'refacciones_movimientos', $mov_id,
        ucfirst($tipo) . " · refaccion=$refaccion_id cantidad=$cantidad");

    flash_set('success', 'Movimiento registrado.');
} catch (Throwable $e) {
    flash_set('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $redirect_url);
exit;
