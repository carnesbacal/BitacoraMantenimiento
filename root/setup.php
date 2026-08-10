<?php
/**
 * ============================================================================
 * SETUP INICIAL - CARNES BACAL BITÁCORA
 * ============================================================================
 *
 * USO:
 * 1. Importa primero el archivo carnes_bacal.sql en phpMyAdmin o vía consola.
 * 2. Coloca este archivo en tu carpeta htdocs (XAMPP) y ábrelo en el navegador:
 *    http://localhost/carnes-bacal/setup.php
 * 3. Sigue las instrucciones en pantalla.
 * 4. Elimina o renombra este archivo después del setup por seguridad.
 *
 * Este script:
 * - Genera el hash bcrypt correcto para la contraseña del admin
 * - Actualiza el usuario admin en la base de datos
 * - Verifica que las tablas estén correctamente creadas
 * ============================================================================
 */

// --- Configuración de conexión (ajusta si tu XAMPP usa otros valores) ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'carnes_bacal';

// --- Credenciales iniciales que se asignarán al admin ---
$admin_user = 'admin';
$admin_password = 'admin123';   // El sistema obligará a cambiarla en el primer login

// Nombre de la empresa: centralizado en config/app.php, con respaldo si se ejecuta suelto.
@include_once __DIR__ . '/../config/app.php';
if (!defined('EMPRESA_NOMBRE')) define('EMPRESA_NOMBRE', 'Carnes Bacal');

// --- HTML básico para la respuesta ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup - <?= EMPRESA_NOMBRE ?> Bitácora</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10">
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-3xl font-bold text-red-700 mb-2">Setup del Sistema</h1>
    <p class="text-gray-600 mb-6"><?= EMPRESA_NOMBRE ?> · Bitácora de Incidencias</p>
    <div class="space-y-3 text-sm">
<?php

function msg($texto, $tipo = 'info') {
    $colores = [
        'ok'    => 'bg-green-50 border-green-400 text-green-800',
        'error' => 'bg-red-50 border-red-400 text-red-800',
        'info'  => 'bg-blue-50 border-blue-400 text-blue-800',
        'warn'  => 'bg-yellow-50 border-yellow-400 text-yellow-800',
    ];
    $clase = $colores[$tipo] ?? $colores['info'];
    echo "<div class='border-l-4 p-3 rounded {$clase}'>{$texto}</div>";
}

// 1. Probar conexión
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        msg("❌ No se pudo conectar a la base de datos: " . $conn->connect_error, 'error');
        msg("Verifica que importaste primero el archivo <strong>carnes_bacal.sql</strong> en phpMyAdmin.", 'warn');
        echo "</div></div></body></html>";
        exit;
    }
    $conn->set_charset('utf8mb4');
    msg("✅ Conexión a la base de datos <strong>{$db_name}</strong> exitosa", 'ok');
} catch (Exception $e) {
    msg("❌ Error: " . $e->getMessage(), 'error');
    echo "</div></div></body></html>";
    exit;
}

// 2. Verificar tablas
$tablas_esperadas = [
    'roles', 'sucursales', 'areas', 'categorias', 'subcategorias',
    'tipos_trabajo', 'severidades', 'estados', 'origenes_reporte',
    'usuarios', 'sesiones', 'equipos', 'incidencias',
    'incidencias_adjuntos', 'incidencias_comentarios', 'incidencias_historial',
    'incidencias_etiquetas', 'auditoria_sistema', 'notificaciones',
];

$res = $conn->query("SHOW TABLES");
$tablas_actuales = [];
while ($row = $res->fetch_array()) {
    $tablas_actuales[] = $row[0];
}

$faltantes = array_diff($tablas_esperadas, $tablas_actuales);
if (count($faltantes) > 0) {
    msg("⚠️ Faltan tablas: " . implode(', ', $faltantes) . ". Importa de nuevo el SQL.", 'error');
    echo "</div></div></body></html>";
    exit;
}
msg("✅ Las " . count($tablas_esperadas) . " tablas del sistema están presentes", 'ok');

// 3. Verificar datos semilla
$conteos = [
    'roles'            => $conn->query("SELECT COUNT(*) c FROM roles")->fetch_assoc()['c'],
    'sucursales'       => $conn->query("SELECT COUNT(*) c FROM sucursales")->fetch_assoc()['c'],
    'áreas'            => $conn->query("SELECT COUNT(*) c FROM areas")->fetch_assoc()['c'],
    'categorías'       => $conn->query("SELECT COUNT(*) c FROM categorias")->fetch_assoc()['c'],
    'tipos de trabajo' => $conn->query("SELECT COUNT(*) c FROM tipos_trabajo")->fetch_assoc()['c'],
    'severidades'      => $conn->query("SELECT COUNT(*) c FROM severidades")->fetch_assoc()['c'],
    'estados'          => $conn->query("SELECT COUNT(*) c FROM estados")->fetch_assoc()['c'],
];
$detalle = [];
foreach ($conteos as $nombre => $n) {
    $detalle[] = "<strong>{$n}</strong> {$nombre}";
}
msg("📦 Datos semilla cargados: " . implode(' · ', $detalle), 'info');

// 4. Generar hash y actualizar admin
$hash = password_hash($admin_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE usuario = ?");
$stmt->bind_param('ss', $hash, $admin_user);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        msg("✅ Contraseña del administrador configurada correctamente", 'ok');
    } else {
        // Verificar si ya tiene un hash válido
        $check = $conn->query("SELECT password_hash FROM usuarios WHERE usuario = '" . $conn->real_escape_string($admin_user) . "'");
        $row = $check->fetch_assoc();
        if ($row && password_verify($admin_password, $row['password_hash'])) {
            msg("ℹ️ La contraseña del admin ya estaba configurada correctamente", 'info');
        } else {
            msg("⚠️ No se actualizó la contraseña (¿usuario admin no existe?)", 'warn');
        }
    }
} else {
    msg("❌ Error al actualizar: " . $stmt->error, 'error');
}

// 5. Mensaje final
?>
    </div>

    <div class="mt-8 p-5 bg-red-50 border-2 border-red-200 rounded-lg">
        <h2 class="text-lg font-bold text-red-800 mb-3">🎉 Setup completado</h2>
        <p class="text-sm text-gray-700 mb-3">Ya puedes iniciar sesión con:</p>
        <div class="bg-white p-3 rounded border border-red-200 font-mono text-sm">
            <div>Usuario: <strong class="text-red-700">admin</strong></div>
            <div>Contraseña: <strong class="text-red-700">admin123</strong></div>
        </div>
        <p class="text-xs text-gray-600 mt-3">
            ⚠️ El sistema te obligará a cambiar la contraseña en el primer ingreso.<br>
            🔒 <strong>Elimina o renombra este archivo (setup.php)</strong> después del setup para evitar accesos no autorizados.
        </p>
    </div>

    <div class="mt-4 text-center text-xs text-gray-500">
        <?= EMPRESA_NOMBRE ?> · Sistema de Bitácora de Incidencias
    </div>
</div>
</body>
</html>