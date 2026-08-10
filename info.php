<?php
/**
 * ============================================================================
 * info.php - Vista PÚBLICA (sin login) de un artículo, para el QR de etiqueta
 * ============================================================================
 * ?tipo=equipo|herramienta|refaccion & t=TOKEN
 * Solo lectura. Para gestionar hay que iniciar sesión en SIGMA.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/qr_helpers.php';

$ocultar_costos = false;   // ← ponlo en true si NO quieres mostrar costos en público

$tipo  = (string) ($_GET['tipo'] ?? '');
$token = (string) ($_GET['t'] ?? '');
$cfg   = qr_tipo_cfg($tipo);
$item  = $cfg ? qr_buscar($tipo, $token) : null;

// Helpers de lookup por id
$nombre_de = function (string $tabla, string $col, $id): string {
    if (empty($id)) return '';
    $r = db_one("SELECT {$col} v FROM {$tabla} WHERE id = :id", ['id' => (int) $id]);
    return (string) ($r['v'] ?? '');
};
$dinero = fn($v) => ($v !== null && $v !== '' && (float) $v != 0) ? '$' . number_format((float) $v, 2) : '';

$titulo = 'Artículo no encontrado';
$codigo = '';
$foto   = null;
$estado_txt = ''; $estado_color = 'zinc';
$campos = [];

if ($item) {
    if ($tipo === 'equipo') {
        $titulo = $item['nombre'];
        $codigo = $item['codigo_inventario'];
        try {
            $frow = db_one("SELECT ruta FROM equipo_fotos WHERE equipo_id = :id ORDER BY es_portada DESC, creado_en DESC LIMIT 1", ['id' => $item['id']]);
            if ($frow && !empty($frow['ruta'])) $foto = url($frow['ruta']);
        } catch (Throwable $e) { $foto = null; }
        $map_ev = ['nuevo' => 'Nuevo', 'en_uso' => 'En uso', 'en_reparacion' => 'En reparación', 'dado_de_baja' => 'Dado de baja'];
        $estado_txt = $map_ev[$item['estado_vida']] ?? (string) $item['estado_vida'];
        $estado_color = $item['estado_vida'] === 'dado_de_baja' ? 'red' : ($item['estado_vida'] === 'en_reparacion' ? 'amber' : 'emerald');
        $campos = [
            ['Código de inventario', $item['codigo_inventario']],
            ['Nombre', $item['nombre']],
            ['Tipo', $item['tipo']],
            ['Marca', $item['marca']],
            ['Modelo', $item['modelo']],
            ['No. de serie', $item['numero_serie']],
            ['Sucursal', $nombre_de('sucursales', 'nombre', $item['sucursal_id'])],
            ['Área', $nombre_de('areas', 'nombre', $item['area_id'])],
            ['Ubicación', $item['ubicacion']],
            ['Responsable', $nombre_de('usuarios', 'nombre_completo', $item['responsable_id'])],
            ['Proveedor', $nombre_de('proveedores', 'nombre', $item['proveedor_id'])],
            ['Fecha de compra', !empty($item['fecha_compra']) ? fmt_fecha($item['fecha_compra'], false) : ''],
            ['Costo de compra', $ocultar_costos ? '' : $dinero($item['costo_compra'])],
            ['Vida útil (meses)', $item['vida_util_meses']],
            ['Notas', $item['notas']],
        ];
    } elseif ($tipo === 'herramienta') {
        $titulo = $item['nombre'];
        $codigo = $item['codigo'];
        if (!empty($item['foto_url'])) $foto = url('assets/' . $item['foto_url']);
        $map_h = ['disponible' => 'Disponible', 'prestada' => 'Prestada', 'en_reparacion' => 'En reparación', 'extraviada' => 'Extraviada', 'baja' => 'Baja'];
        $estado_txt = $map_h[$item['estado']] ?? (string) $item['estado'];
        $estado_color = in_array($item['estado'], ['extraviada', 'baja'], true) ? 'red' : ($item['estado'] === 'disponible' ? 'emerald' : 'amber');
        $campos = [
            ['Código', $item['codigo']],
            ['Nombre', $item['nombre']],
            ['Descripción', $item['descripcion']],
            ['Tipo', $item['tipo']],
            ['Marca', $item['marca']],
            ['Modelo', $item['modelo']],
            ['No. de serie', $item['numero_serie']],
            ['Sucursal', $nombre_de('sucursales', 'nombre', $item['sucursal_id'])],
            ['Ubicación', $item['ubicacion']],
            ['Proveedor', $nombre_de('proveedores', 'nombre', $item['proveedor_id'])],
            ['Fecha de adquisición', !empty($item['fecha_adquisicion']) ? fmt_fecha($item['fecha_adquisicion'], false) : ''],
            ['Costo', $ocultar_costos ? '' : $dinero($item['costo'])],
            ['Notas', $item['notas']],
        ];
    } elseif ($tipo === 'refaccion') {
        $titulo = $item['nombre'];
        $codigo = $item['codigo'];
        if (!empty($item['foto_url'])) $foto = url('assets/' . $item['foto_url']);
        $existencia = null;
        try {
            $er = db_one("SELECT COALESCE(SUM(cantidad_actual),0) s FROM refacciones_stock WHERE refaccion_id = :id", ['id' => $item['id']]);
            $existencia = $er ? rtrim(rtrim(number_format((float) $er['s'], 2), '0'), '.') : null;
        } catch (Throwable $e) { $existencia = null; }
        $estado_txt = 'En catálogo'; $estado_color = 'emerald';
        $campos = [
            ['Código', $item['codigo']],
            ['Nombre', $item['nombre']],
            ['Descripción', $item['descripcion']],
            ['Marca', $item['marca']],
            ['Modelo', $item['modelo']],
            ['No. de parte', $item['numero_parte']],
            ['Categoría', $item['categoria']],
            ['Unidad de medida', $item['unidad_medida']],
            ['Existencia total', $existencia !== null ? ($existencia . ' ' . $item['unidad_medida']) : ''],
            ['Proveedor', $nombre_de('proveedores', 'nombre', $item['proveedor_id'])],
            ['Costo unitario', $ocultar_costos ? '' : $dinero($item['costo_unitario'])],
        ];
    }
    // Quitar campos vacíos
    $campos = array_values(array_filter($campos, fn($c) => trim((string) $c[1]) !== ''));
}

$tipo_label = $cfg['label'] ?? 'Artículo';
$ficha_url  = ($item && $cfg) ? url($cfg['ficha'] . '?id=' . (int) $item['id']) : url('login.php');
$empresa    = defined('EMPRESA_NOMBRE') ? EMPRESA_NOMBRE : 'SIGMA';
if (!$item) http_response_code(404);
?><!DOCTYPE html>
<html lang="es" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($item ? ($codigo . ' · ' . $titulo) : 'Artículo no encontrado') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@700;800&display=swap" rel="stylesheet">
<script>
tailwind.config = { theme: { extend: {
    fontFamily: { display: ['Bricolage Grotesque','sans-serif'] },
    colors: { bacal: { 50:'#fef2f2',100:'#fee2e2',200:'#fecaca',600:'#dc2626',700:'#c8102e',800:'#a80d26' } }
} } };
</script>
<style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="min-h-full bg-zinc-100 text-zinc-900">

<div class="max-w-lg mx-auto p-4 sm:p-6">

    <!-- Marca -->
    <div class="flex items-center gap-2 justify-center mb-4">
        <div class="w-8 h-8 rounded-lg bg-bacal-700 text-white font-display font-bold text-sm flex items-center justify-center"><?= e(defined('EMPRESA_CORTO') ? EMPRESA_CORTO : 'S') ?></div>
        <div class="leading-tight">
            <div class="font-display font-extrabold text-bacal-700 text-sm">SIGMA</div>
            <div class="text-[11px] text-zinc-500 -mt-0.5"><?= e($empresa) ?></div>
        </div>
    </div>

    <?php if (!$item): ?>
    <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm p-8 text-center">
        <div class="text-5xl mb-3">🔍</div>
        <h1 class="font-display text-xl font-extrabold text-zinc-900">Artículo no encontrado</h1>
        <p class="text-sm text-zinc-500 mt-1">El código QR no es válido o el artículo fue eliminado.</p>
        <a href="<?= url('login.php') ?>" class="inline-block mt-5 px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">Ir a SIGMA</a>
    </div>
    <?php else: ?>

    <div class="bg-white rounded-2xl border border-zinc-200 shadow-sm overflow-hidden">
        <?php if ($foto): ?>
        <div class="w-full aspect-video bg-zinc-100 border-b border-zinc-100">
            <img src="<?= e($foto) ?>" alt="<?= e($titulo) ?>" class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <div class="p-5">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="text-[11px] font-bold uppercase tracking-wider text-bacal-700 bg-bacal-50 px-2 py-0.5 rounded"><?= e($tipo_label) ?></span>
                <?php if ($estado_txt): ?>
                <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-<?= $estado_color ?>-100 text-<?= $estado_color ?>-800"><?= e($estado_txt) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="font-display text-2xl font-extrabold text-zinc-900 leading-tight"><?= e($titulo) ?></h1>
            <?php if ($codigo): ?>
            <p class="font-mono text-sm font-bold text-zinc-500 mt-0.5"><?= e($codigo) ?></p>
            <?php endif; ?>

            <dl class="mt-4 divide-y divide-zinc-100">
                <?php foreach ($campos as [$lbl, $val]): ?>
                <div class="py-2 flex gap-3 text-sm">
                    <dt class="text-zinc-500 w-40 shrink-0"><?= e($lbl) ?></dt>
                    <dd class="font-medium text-zinc-900 flex-1"><?= e((string) $val) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <div class="px-5 py-4 bg-zinc-50 border-t border-zinc-100">
            <a href="<?= e($ficha_url) ?>"
               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                Iniciar sesión en SIGMA para gestionar →
            </a>
            <p class="text-[11px] text-zinc-400 text-center mt-2">Vista pública de solo lectura. Para ver más o registrar movimientos, inicia sesión.</p>
        </div>
    </div>
    <?php endif; ?>

    <p class="text-center text-[11px] text-zinc-400 mt-4">© <?= date('Y') ?> <?= e($empresa) ?> · SIGMA</p>
</div>

</body>
</html>
