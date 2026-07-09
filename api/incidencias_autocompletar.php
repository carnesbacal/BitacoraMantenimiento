<?php
/**
 * ============================================================================
 * api/incidencias_autocompletar.php
 * ============================================================================
 * Autocompletado de incidencias por folio o título (para vincular reincidencias).
 * Devuelve hasta 10 coincidencias en JSON: id, folio, titulo, fecha_evento.
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

requerir_login();
header('Content-Type: application/json; charset=utf-8');

$q       = trim((string) input('q', ''));
$excluir = (int) input('excluir', 0);

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';
$rows = db_all(
    "SELECT i.id, i.folio, i.titulo, i.fecha_evento, s.codigo AS sucursal_codigo
     FROM incidencias i
     LEFT JOIN sucursales s ON i.sucursal_id = s.id
     WHERE (i.folio LIKE :q1 OR i.titulo LIKE :q2) AND i.id <> :ex
     ORDER BY i.fecha_evento DESC
     LIMIT 10",
    ['q1' => $like, 'q2' => $like, 'ex' => $excluir]
);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
