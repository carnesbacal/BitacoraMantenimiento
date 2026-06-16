<?php
/**
 * api/catalogo_crear_rapido.php
 * Crea un elemento de catálogo desde el formulario de incidencia nueva.
 * Requiere permiso: administrar
 * Método: POST
 * Parámetros:
 *   _csrf        string  Token CSRF
 *   tabla        string  area | categoria | subcategoria | tipo_trabajo | origen
 *   nombre       string  Nombre del nuevo elemento
 *   color        string  Color hex (opcional, para categoria y tipo_trabajo)
 *   categoria_id int     Requerido si tabla = subcategoria
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// Autenticación y permiso
if (!esta_logueado()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado.']);
    exit;
}
if (!tiene_permiso('administrar')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permiso para crear elementos de catálogo.']);
    exit;
}

// CSRF
if (!csrf_valido(input('_csrf'))) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido.']);
    exit;
}

$tabla_key = trim((string) input('tabla', ''));
$nombre    = trim((string) input('nombre', ''));
$color     = trim((string) input('color', '#6B7280'));
$cat_id    = (int) input('categoria_id', 0);

// Validar nombre
if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio.']);
    exit;
}
if (mb_strlen($nombre) > 100) {
    echo json_encode(['ok' => false, 'error' => 'El nombre no puede superar 100 caracteres.']);
    exit;
}

// Validar color básico
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $color = '#6B7280';
}

// Mapa de tablas permitidas
$tablas = [
    'area'         => ['tabla' => 'areas',           'cols' => ['nombre'],               'label' => 'Área'],
    'categoria'    => ['tabla' => 'categorias',       'cols' => ['nombre', 'color'],      'label' => 'Categoría'],
    'subcategoria' => ['tabla' => 'subcategorias',    'cols' => ['nombre', 'categoria_id'],'label' => 'Subcategoría'],
    'tipo_trabajo' => ['tabla' => 'tipos_trabajo',    'cols' => ['nombre', 'color'],      'label' => 'Tipo de trabajo'],
    'origen'       => ['tabla' => 'origenes_reporte', 'cols' => ['nombre'],               'label' => 'Origen'],
];

if (!isset($tablas[$tabla_key])) {
    echo json_encode(['ok' => false, 'error' => 'Tipo de catálogo no válido.']);
    exit;
}

$cfg   = $tablas[$tabla_key];
$tabla = $cfg['tabla'];
$label = $cfg['label'];

// Validación extra para subcategoría
if ($tabla_key === 'subcategoria') {
    if (!$cat_id) {
        echo json_encode(['ok' => false, 'error' => 'Debes seleccionar una categoría primero.']);
        exit;
    }
    $cat_existe = db_one("SELECT id FROM categorias WHERE id = :id AND activo = 1", ['id' => $cat_id]);
    if (!$cat_existe) {
        echo json_encode(['ok' => false, 'error' => 'La categoría seleccionada no existe o está inactiva.']);
        exit;
    }
}

// Verificar duplicado
$existe = db_one("SELECT id FROM $tabla WHERE LOWER(nombre) = LOWER(:n)", ['n' => $nombre]);
if ($existe) {
    echo json_encode(['ok' => false, 'error' => "Ya existe un(a) $label con ese nombre."]);
    exit;
}

try {
    $datos = ['nombre' => $nombre, 'activo' => 1];

    if (in_array('color', $cfg['cols'], true)) {
        $datos['color'] = $color;
    }
    if ($tabla_key === 'subcategoria') {
        $datos['categoria_id'] = $cat_id;
    }

    $cols   = implode(', ', array_keys($datos));
    $params = ':' . implode(', :', array_keys($datos));
    db_exec("INSERT INTO $tabla ($cols) VALUES ($params)", $datos);
    $nuevo_id = db_last_id();

    registrar_auditoria("crear_{$tabla}", $tabla, $nuevo_id, "$label: $nombre (alta rápida desde formulario)");

    echo json_encode([
        'ok'     => true,
        'id'     => $nuevo_id,
        'nombre' => $nombre,
        'color'  => $color,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
