<?php
/**
 * ============================================================================
 * lectura_nueva.php - Captura individual de una lectura de medidor
 * ============================================================================
 * Recibe ?medidor_id=X. Muestra la última lectura como referencia, calcula
 * el consumo y el costo en vivo, y permite adjuntar una foto opcional.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/medidores_helpers.php';

requerir_login();

$puede_capturar = tiene_permiso('administrar') || tiene_permiso('resolver');
if (!$puede_capturar) { flash_set('error', 'No tienes permiso para capturar lecturas.'); header('Location: ' . url('medidores.php')); exit; }

$medidor_id = (int) input('medidor_id', 0);
$medidor = $medidor_id ? obtener_medidor($medidor_id) : null;
if (!$medidor) { flash_set('error', 'Medidor no encontrado.'); header('Location: ' . url('medidores.php')); exit; }
if (!puede_ver_medidor($medidor)) { flash_set('error', 'Ese medidor pertenece a otra sucursal.'); header('Location: ' . url('medidores.php')); exit; }

$anterior = ultima_lectura($medidor_id);
$errores = [];

/**
 * Sube una foto de evidencia de forma segura. Devuelve la ruta relativa o null.
 */
function _subir_foto_lectura(array $file): ?string {
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) throw new RuntimeException('Error al subir la foto.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('La foto supera el límite de 8 MB.');

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) throw new RuntimeException('El archivo no es una imagen válida.');
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = $info['mime'] ?? '';
    if (!isset($ext_map[$mime])) throw new RuntimeException('Formato no permitido. Usa JPG, PNG, WEBP o GIF.');

    $rel_dir = 'uploads/medidores/' . date('Y/m');
    $abs_dir = __DIR__ . '/' . $rel_dir;
    if (!is_dir($abs_dir) && !@mkdir($abs_dir, 0775, true) && !is_dir($abs_dir)) {
        throw new RuntimeException('No se pudo crear la carpeta de fotos.');
    }
    // Protege la carpeta contra ejecución de scripts (compatible con mod_php y PHP-FPM/cPanel)
    $htaccess = __DIR__ . '/uploads/.htaccess';
    $htaccess_content = "<FilesMatch \"\.(php|phtml|phar|cgi|pl|py|sh|shtml)$\">\n    Require all denied\n</FilesMatch>\nOptions -ExecCGI -Indexes\nRemoveHandler .php .phtml .phar\nRemoveType .php .phtml .phar\n";
    @file_put_contents($htaccess, $htaccess_content);

    $nombre = bin2hex(random_bytes(8)) . '.' . $ext_map[$mime];
    $destino = $abs_dir . '/' . $nombre;
    if (!move_uploaded_file($file['tmp_name'], $destino)) throw new RuntimeException('No se pudo guardar la foto.');

    return $rel_dir . '/' . $nombre;
}

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $valor_raw   = trim((string) input('valor_lectura', ''));
        $fecha       = (string) input('fecha_lectura', date('Y-m-d'));
        $es_reinicio = (int) input('es_reinicio', 0) ? 1 : 0;
        $nota        = trim((string) input('nota', '')) ?: null;

        if ($valor_raw === '' || !is_numeric($valor_raw)) {
            $errores[] = 'Captura un valor de lectura válido.';
        }
        $valor = (float) $valor_raw;

        // Evita guardar una lectura menor que la anterior si no es un reinicio
        if (empty($errores) && $anterior && !$es_reinicio && $valor < (float) $anterior['valor_lectura']) {
            $errores[] = 'La lectura (' . $valor . ') es menor que la anterior (' . (float) $anterior['valor_lectura']
                . '). Si el medidor se reinició o se reemplazó, marca la casilla correspondiente; si no, corrige el valor.';
        }

        $foto_path = null;
        if (empty($errores)) {
            try {
                if (!empty($_FILES['foto'])) $foto_path = _subir_foto_lectura($_FILES['foto']);

                $res = registrar_lectura($medidor_id, [
                    'valor_lectura' => $valor,
                    'fecha_lectura' => $fecha,
                    'es_reinicio'   => $es_reinicio,
                    'nota'          => $nota,
                    'foto'          => $foto_path,
                ], (int) usuario_actual()['id']);

                registrar_auditoria('registrar_lectura', 'medidor_lecturas', $res['id'], "Lectura de {$medidor['nombre']}");

                $msg = 'Lectura registrada.';
                if ($res['consumo'] !== null) {
                    $msg .= ' Consumo: ' . fmt_consumo($res['consumo'], $medidor['unidad']);
                    if ($res['costo'] !== null) $msg .= ' · Costo estimado: $' . number_format($res['costo'], 2);
                } elseif ($es_reinicio) {
                    $msg .= ' (marcada como reinicio, sin consumo).';
                } else {
                    $msg .= ' (primera lectura, queda como base).';
                }
                flash_set('success', $msg);
                header('Location: ' . url('medidor_ver.php?id=' . $medidor_id));
                exit;
            } catch (Throwable $e) {
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

$color = $medidor['tipo_color'] ?: '#6B7280';

$titulo_pagina = 'Capturar lectura';
$pagina_activa = 'medidores';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-2xl mx-auto animate-fade-in"
     x-data="{
        valor: '',
        anterior: <?= $anterior ? (float) $anterior['valor_lectura'] : 'null' ?>,
        tarifa: <?= $medidor['tarifa'] !== null ? (float) $medidor['tarifa'] : 'null' ?>,
        reinicio: false,
        get consumo() {
            if (this.anterior === null || this.valor === '' || this.reinicio) return null;
            const c = parseFloat(this.valor) - this.anterior;
            return isNaN(c) ? null : c;
        },
        get costo() {
            if (this.consumo === null || this.tarifa === null || this.consumo < 0) return null;
            return this.consumo * this.tarifa;
        }
     }">

    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('medidores.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                 style="background-color: <?= e($color) ?>15; color: <?= e($color) ?>">
                <i data-lucide="<?= e($medidor['tipo_icono'] ?: 'gauge') ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-xl font-extrabold text-zinc-900"><?= e($medidor['nombre']) ?></h2>
                <p class="text-xs text-zinc-500"><?= e($medidor['tipo_nombre']) ?> · <?= e($medidor['sucursal_nombre']) ?><?= $medidor['ubicacion'] ? ' · ' . e($medidor['ubicacion']) : '' ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
        <ul class="list-disc list-inside text-xs"><?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Referencia: última lectura -->
    <div class="mb-4 px-4 py-3 rounded-lg bg-zinc-50 border border-zinc-200 flex items-center justify-between text-sm">
        <span class="text-zinc-500">Última lectura</span>
        <?php if ($anterior): ?>
        <span class="text-zinc-900 font-semibold font-mono"><?= e(fmt_lectura((float) $anterior['valor_lectura'])) ?> <?= e($medidor['unidad']) ?>
            <span class="text-xs text-zinc-400 font-sans">· <?= e(fmt_fecha($anterior['fecha_lectura'])) ?></span></span>
        <?php else: ?>
        <span class="text-zinc-400">Sin lecturas previas — esta será la base</span>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-5">
        <?= csrf_input() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha *</label>
                <input type="date" name="fecha_lectura" required value="<?= e((string) input('fecha_lectura', date('Y-m-d'))) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Lectura del medidor *</label>
                <div class="relative">
                    <input type="number" name="valor_lectura" step="0.001" min="0" required x-model="valor"
                           placeholder="Número que marca"
                           class="w-full pl-3 pr-14 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"><?= e($medidor['unidad']) ?></span>
                </div>
            </div>
        </div>

        <!-- Cálculo en vivo -->
        <div class="grid grid-cols-2 gap-3" x-show="consumo !== null" x-cloak>
            <div class="px-3 py-2 rounded-lg bg-bacal-50 border border-bacal-100 text-center">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider">Consumo</div>
                <div class="font-display font-bold text-bacal-800" :class="consumo < 0 ? 'text-red-600' : ''">
                    <span x-text="consumo !== null ? consumo.toLocaleString(undefined,{maximumFractionDigits:3}) : '—'"></span>
                    <span class="text-xs font-normal"><?= e($medidor['unidad']) ?></span>
                </div>
            </div>
            <div class="px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-100 text-center" x-show="costo !== null">
                <div class="text-[10px] text-zinc-500 uppercase tracking-wider">Costo estimado</div>
                <div class="font-display font-bold text-emerald-700">
                    $<span x-text="costo !== null ? costo.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) : '—'"></span>
                </div>
            </div>
        </div>
        <p class="text-xs text-amber-600" x-show="consumo !== null && consumo < 0 && !reinicio" x-cloak>
            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline -mt-0.5"></i>
            La lectura es menor que la anterior. Marca "reinicio" si el medidor se reemplazó.
        </p>

        <label class="flex items-center gap-2 text-sm text-zinc-700">
            <input type="checkbox" name="es_reinicio" value="1" x-model="reinicio" class="rounded border-zinc-300">
            El medidor se reinició o se reemplazó (no calcular consumo esta vez)
        </label>

        <div>
            <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Foto <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
            <input type="file" name="foto" accept="image/*" capture="environment"
                   class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-bacal-50 file:text-bacal-700 hover:file:bg-bacal-100">
            <p class="text-[10px] text-zinc-400 mt-1">JPG, PNG, WEBP o GIF, hasta 8 MB. En el celular puedes tomarla al momento.</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nota <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
            <input type="text" name="nota" maxlength="300" value="<?= e((string) input('nota', '')) ?>"
                   class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="<?= url('medidores.php') ?>" class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-600 hover:bg-zinc-50">Cancelar</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold shadow-sm">Guardar lectura</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
