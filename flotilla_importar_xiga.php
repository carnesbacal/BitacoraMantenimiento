<?php
/**
 * ============================================================================
 * flotilla_importar_xiga.php - Importar consumos XIGA → flotilla_combustible
 * ============================================================================
 * Flujo:
 *   1. Subir el "Reporte de Consumos" descargado de XIGA (xlsx)
 *   2. Previsualización: match de placas, duplicados, resumen
 *   3. Confirmar → INSERT en flotilla_combustible + flotilla_gastos
 *
 * Solo accesible para administradores.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_permiso('administrar');
$u = usuario_actual();

// ============================================================================
// Funciones auxiliares de parseo XIGA
// ============================================================================

/**
 * Convierte letra(s) de columna Excel a índice 0-based (A→0, B→1, AA→26...)
 */
function xiga_col_idx(string $col): int {
    $col = strtoupper(trim($col));
    $r = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $r = $r * 26 + (ord($col[$i]) - 64);
    }
    return $r - 1;
}

/**
 * Lee el xlsx del Reporte de Consumos XIGA.
 * Requiere ZipArchive y SimpleXML (disponibles en PHP 7+/XAMPP).
 * Retorna array de filas (comenzando en la fila 6 del xlsx).
 * Cada fila es un array indexado por número de columna 0-based.
 */
function xiga_leer_xlsx(string $filepath) {
    if (!class_exists('ZipArchive')) {
        return 'ZipArchive no disponible (instalar ext-zip).';
    }

    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return 'No se pudo abrir el archivo xlsx.';
    }

    // Shared strings
    $strings = [];
    $ss_raw  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_raw) {
        $ss_clean = xiga_strip_ns($ss_raw);
        $xml = @simplexml_load_string($ss_clean);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } else {
                    $t = '';
                    foreach ($si->r as $r) {
                        if (isset($r->t)) $t .= (string) $r->t;
                    }
                    $strings[] = $t;
                }
            }
        }
    }

    // Sheet
    $sheet_raw = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if (!$sheet_raw) {
        return 'No se encontró la hoja de datos en el xlsx.';
    }

    $sheet_clean = xiga_strip_ns($sheet_raw);
    $xml = @simplexml_load_string($sheet_clean);
    if (!$xml) {
        return 'No se pudo parsear el XML de la hoja.';
    }

    $filas = [];
    foreach ($xml->sheetData->row as $row) {
        $r_num = (int) $row['r'];
        if ($r_num < 6) continue; // Filas 1-5 son encabezados XIGA

        $fila = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            preg_match('/^([A-Z]+)\d+$/', $ref, $m);
            if (!isset($m[1])) continue;
            $col_idx = xiga_col_idx($m[1]);
            $tipo    = (string) $cell['t'];
            if ($tipo === 'inlineStr') {
                $val = isset($cell->is->t) ? trim((string) $cell->is->t) : '';
            } else {
                $val = isset($cell->v) ? trim((string) $cell->v) : '';
                if ($tipo === 's' && $val !== '') {
                    $val = $strings[(int) $val] ?? '';
                }
            }
            $fila[$col_idx] = $val;
        }

        // Sólo incluir filas con fecha y ticket
        if (!empty($fila[0]) && !empty($fila[1])) {
            $filas[] = $fila;
        }
    }

    return $filas;
}

/**
 * Quita prefijos de namespace del XML (ej. <x:row> → <row>) para poder
 * usar simplexml sin registrar namespaces.
 */
function xiga_strip_ns(string $xml): string {
    $xml = preg_replace('/(<\/?)(\w+):/', '$1', $xml);
    $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
    return $xml;
}

/**
 * Parsea la fecha XIGA: "02/01/2024 09:58:50 a. m." → "2024-01-02 09:58:50"
 */
function xiga_parse_fecha(string $raw): ?string {
    $raw = trim($raw);
    // Normalizar AM/PM español
    $raw = str_ireplace(['a. m.', 'a.m.', ' am'], ' AM', $raw);
    $raw = str_ireplace(['p. m.', 'p.m.', ' pm'], ' PM', $raw);
    $raw = preg_replace('/\s+/', ' ', trim($raw));

    $dt = DateTime::createFromFormat('d/m/Y h:i:s A', $raw);
    if (!$dt) {
        // Intentar sin AM/PM (24h)
        $dt = DateTime::createFromFormat('d/m/Y H:i:s', $raw);
    }
    if (!$dt) return null;
    return $dt->format('Y-m-d H:i:s');
}

/**
 * Mapea el producto XIGA al tipo_combustible del sistema.
 */
function xiga_tipo_combustible(string $producto): string {
    $p = strtolower(trim($producto));
    return match(true) {
        in_array($p, ['magna', 'regular'])           => 'gasolina_regular',
        in_array($p, ['extra', 'premium', 'plus'])   => 'gasolina_premium',
        (strpos($p, 'diesel') === 0)                 => 'diesel',
        (strpos($p, 'gas')    === 0)                 => 'gas',
        default                                       => 'gasolina_regular',
    };
}

/**
 * Mapea una fila de datos xlsx a los campos de flotilla_combustible.
 * Columnas XIGA (0-based):
 *   0=Fecha, 1=No.Ticket, 9=Placas, 13=Estacion, 15=Lts, 16=Producto,
 *   17=Kms(siempre 9999), 19=Precio, 20=Factura, 24=Total
 */
function xiga_mapear_fila(array $row): array {
    return [
        'fecha'           => xiga_parse_fecha($row[0] ?? ''),
        'ticket_numero'   => trim($row[1] ?? ''),
        'placas_raw'      => strtoupper(trim($row[9] ?? '')),
        'estacion'        => substr(trim($row[13] ?? ''), 0, 100) ?: null,
        'litros'          => (float) str_replace(',', '.', $row[15] ?? '0'),
        'tipo_combustible'=> xiga_tipo_combustible($row[16] ?? ''),
        'precio_litro'    => (float) str_replace(',', '.', $row[19] ?? '0'),
        'factura'         => trim($row[20] ?? '') ?: null,
        'monto_xiga'      => (float) str_replace(',', '.', $row[24] ?? '0'),
    ];
}

// ============================================================================
// Lógica de pasos
// ============================================================================

$paso    = 'subir';    // subir | previa | resultado
$errores = [];
$filas_preview  = [];
$resumen        = [];

// Cargar vehículos de la BD para match de placas
$vehiculos_bd = [];
foreach (db_all("SELECT id, placas, alias, marca, modelo FROM flotilla_vehiculos WHERE activo=1") as $v) {
    $vehiculos_bd[strtoupper(trim($v['placas']))] = $v;
}

// Tickets ya importados
$tickets_bd = [];
$rows_tick = db_all("SELECT ticket_numero FROM flotilla_combustible WHERE ticket_numero IS NOT NULL AND ticket_numero != ''");
foreach ($rows_tick as $t) {
    $tickets_bd[$t['ticket_numero']] = true;
}

// Categoría de combustible para gastos
$cat_comb = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre = 'Combustible' LIMIT 1");

// ============================================================================
// PASO 2: archivo subido → previsualizar
// ============================================================================
if (es_post() && (string)input('op') === 'previa') {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif (empty($_FILES['archivo_xiga']['tmp_name'])) {
        $errores[] = 'No se recibió ningún archivo.';
    } else {
        $file      = $_FILES['archivo_xiga'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'xlsx') {
            $errores[] = 'El archivo debe ser .xlsx (Reporte de Consumos de XIGA).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Error al subir el archivo (código: ' . $file['error'] . ').';
        } else {
            // Guardar temporal para reutilizar en confirmación.
            // Usamos carpeta dentro del proyecto para evitar restricciones
            // de open_basedir en cPanel (que puede bloquear sys_get_temp_dir).
            $tmp_dir  = __DIR__ . '/assets/tmp';
            if (!is_dir($tmp_dir)) @mkdir($tmp_dir, 0755, true);
            $tmp_name = $tmp_dir . DIRECTORY_SEPARATOR . 'xiga_' . session_id() . '.xlsx';
            move_uploaded_file($file['tmp_name'], $tmp_name);

            $parsed = xiga_leer_xlsx($tmp_name);

            if (is_string($parsed)) {
                $errores[] = 'Error al leer el archivo: ' . $parsed;
                @unlink($tmp_name);
            } else {
                $_SESSION['xiga_tmp_file'] = $tmp_name;
                $_SESSION['xiga_tmp_orig'] = basename($file['name']);

                foreach ($parsed as $raw_fila) {
                    $f = xiga_mapear_fila($raw_fila);
                    if (!$f['fecha'] || !$f['ticket_numero'] || !$f['placas_raw']) continue;

                    $es_dup     = isset($tickets_bd[$f['ticket_numero']]);
                    $vehiculo   = $vehiculos_bd[$f['placas_raw']] ?? null;

                    $f['vehiculo_id']   = $vehiculo ? (int)$vehiculo['id'] : null;
                    $f['vehiculo_desc'] = $vehiculo
                        ? (($vehiculo['alias'] ?? '') . ' ' . $vehiculo['marca'] . ' ' . $vehiculo['modelo'])
                        : null;
                    $f['es_duplicado']  = $es_dup;
                    $f['sin_match']     = ($vehiculo === null);

                    $filas_preview[] = $f;
                }

                $resumen = [
                    'total'      => count($filas_preview),
                    'nuevas'     => count(array_filter($filas_preview, fn($f) => !$f['es_duplicado'] && !$f['sin_match'])),
                    'duplicadas' => count(array_filter($filas_preview, fn($f) =>  $f['es_duplicado'])),
                    'sin_match'  => count(array_filter($filas_preview, fn($f) => !$f['es_duplicado'] &&  $f['sin_match'])),
                    'min_fecha'  => '',
                    'max_fecha'  => '',
                ];

                $fechas = array_filter(array_column($filas_preview, 'fecha'));
                if ($fechas) {
                    sort($fechas);
                    $resumen['min_fecha'] = substr(reset($fechas), 0, 10);
                    $resumen['max_fecha'] = substr(end($fechas),   0, 10);
                }

                $paso = 'previa';
            }
        }
    }
}

// ============================================================================
// PASO 3: confirmar → insertar
// ============================================================================
if (es_post() && (string)input('op') === 'confirmar') {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif (empty($_SESSION['xiga_tmp_file']) || !file_exists($_SESSION['xiga_tmp_file'])) {
        $errores[] = 'Sesión expirada o archivo temporal no encontrado. Vuelve a subir el archivo.';
    } else {
        $tmp_name = $_SESSION['xiga_tmp_file'];
        $parsed   = xiga_leer_xlsx($tmp_name);

        if (is_string($parsed)) {
            $errores[] = 'Error al releer el archivo: ' . $parsed;
        } else {
            $insertados    = 0;
            $duplicados    = 0;
            $sin_match     = 0;
            $con_error     = 0;
            $importar_smatch = (string)input('importar_sin_match') === '1';

            foreach ($parsed as $raw_fila) {
                $f = xiga_mapear_fila($raw_fila);
                if (!$f['fecha'] || !$f['ticket_numero'] || !$f['placas_raw']) continue;

                // Revalidar duplicado (en tiempo real)
                $ya_existe = db_one(
                    "SELECT id FROM flotilla_combustible WHERE ticket_numero = :t LIMIT 1",
                    ['t' => $f['ticket_numero']]
                );
                if ($ya_existe) { $duplicados++; continue; }

                $vehiculo = $vehiculos_bd[$f['placas_raw']] ?? null;
                if (!$vehiculo) {
                    if ($importar_smatch) {
                        $sin_match++;
                    } else {
                        $sin_match++;
                    }
                    continue; // siempre omitir sin match (no hay vehiculo_id)
                }

                try {
                    $monto = round($f['litros'] * $f['precio_litro'], 2);
                    $notas = $f['factura'] ? "Factura XIGA: {$f['factura']}" : null;

                    db_exec(
                        "INSERT INTO flotilla_combustible
                            (vehiculo_id, fecha, km_odometro, litros, precio_litro,
                             tipo_combustible, estacion, ticket_numero, es_tanque_lleno,
                             km_recorridos, rendimiento_kml, notas, creado_por)
                         VALUES
                            (:vid, :fecha, :km, :litros, :precio,
                             :tipo, :est, :ticket, 1,
                             NULL, NULL, :notas, :cp)",
                        [
                            'vid'    => $vehiculo['id'],
                            'fecha'  => $f['fecha'],
                            'km'     => 0,  // XIGA no registra odómetro real
                            'litros' => $f['litros'],
                            'precio' => $f['precio_litro'],
                            'tipo'   => $f['tipo_combustible'],
                            'est'    => $f['estacion'],
                            'ticket' => $f['ticket_numero'],
                            'notas'  => $notas,
                            'cp'     => $u['id'],
                        ]
                    );
                    $comb_id = db_last_id();

                    // Registrar gasto asociado
                    if ($cat_comb) {
                        $tipo_label = match($f['tipo_combustible']) {
                            'gasolina_regular'  => 'Gasolina reg.',
                            'gasolina_premium'  => 'Gasolina prem.',
                            'diesel'            => 'Diesel',
                            default             => $f['tipo_combustible'],
                        };
                        db_exec(
                            "INSERT INTO flotilla_gastos
                                (vehiculo_id, categoria_id, fecha, concepto, monto,
                                 km_odometro, combustible_id, creado_por)
                             VALUES (:vid, :cat, :fecha, :concepto, :monto,
                                     0, :cid, :cp)",
                            [
                                'vid'     => $vehiculo['id'],
                                'cat'     => $cat_comb['id'],
                                'fecha'   => substr($f['fecha'], 0, 10),
                                'concepto'=> "Combustible – {$f['litros']} L ({$tipo_label})"
                                           . ($f['estacion'] ? " en {$f['estacion']}" : '')
                                           . " [XIGA {$f['ticket_numero']}]",
                                'monto'   => $monto,
                                'cid'     => $comb_id,
                                'cp'      => $u['id'],
                            ]
                        );
                    }

                    $insertados++;
                } catch (Throwable $e) {
                    $con_error++;
                    error_log("[XIGA] Error importando ticket {$f['ticket_numero']}: " . $e->getMessage());
                }
            }

            // Limpiar temporal
            @unlink($tmp_name);
            unset($_SESSION['xiga_tmp_file'], $_SESSION['xiga_tmp_orig']);

            registrar_auditoria('importar_xiga', 'flotilla_combustible', null,
                "XIGA import: {$insertados} insertados, {$duplicados} duplicados, {$sin_match} sin match");

            $paso = 'resultado';
            $resumen = compact('insertados','duplicados','sin_match','con_error');
        }
    }
}

// ============================================================================
// Vista
// ============================================================================
$titulo_pagina = 'Flotilla · Importar XIGA';
$pagina_activa = 'flotilla_importar_xiga';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="fuel" class="w-6 h-6 text-bacal-700"></i>
            Importar Consumos XIGA
        </h2>
        <a href="<?= url('flotilla_combustible.php') ?>"
           class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50 flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a Combustible
        </a>
    </div>

    <?php if ($errores): ?>
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-800 text-sm space-y-1">
        <?php foreach ($errores as $em): ?>
        <p class="flex gap-2"><i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i><?= e($em) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ===================== PASO 1: SUBIR ARCHIVO ===================== -->
    <?php if ($paso === 'subir'): ?>

    <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800 flex gap-3">
        <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5 text-blue-500"></i>
        <div>
            <p class="font-semibold mb-1">¿Qué archivo necesito?</p>
            <p>En el portal XIGA (<code>cardsystem.xiga.com.mx</code>), descarga el <strong>Reporte de Consumos</strong> en formato Excel (.xlsx). Este reporte incluye cada transacción con fecha, ticket, placas, litros y precio.</p>
            <p class="mt-1 text-blue-600">Los registros duplicados (mismo No. de Ticket) se detectan automáticamente y no se vuelven a insertar.</p>
        </div>
    </div>

    <!-- Archivo de ejemplo -->
    <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-green-700"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-zinc-800">Archivo de ejemplo</p>
                <p class="text-xs text-zinc-500">Muestra el formato exacto que debe tener el Reporte de Consumos XIGA, con las columnas clave marcadas.</p>
            </div>
        </div>
        <a href="<?= url('assets/FORMATO_XIGA_ejemplo.xlsx') ?>"
           download="FORMATO_XIGA_ejemplo.xlsx"
           class="flex items-center gap-2 px-4 py-2 rounded-lg border border-zinc-300 bg-white hover:bg-zinc-50 text-sm font-medium text-zinc-700 whitespace-nowrap">
            <i data-lucide="download" class="w-4 h-4 text-green-600"></i>
            Descargar ejemplo .xlsx
        </a>
    </div>

    <div class="bg-white rounded-xl border border-zinc-200 p-6 max-w-lg mx-auto">
        <form method="post" action="<?= url('flotilla_importar_xiga.php') ?>"
              enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="op" value="previa">

            <div>
                <label class="block text-sm font-semibold text-zinc-700 mb-2">
                    Archivo Reporte de Consumos (.xlsx)
                </label>
                <div x-data="{ nombre: '' }"
                     class="border-2 border-dashed border-zinc-300 hover:border-bacal-500 rounded-xl p-6 text-center transition-colors cursor-pointer"
                     onclick="document.getElementById('f_xiga').click()">
                    <i data-lucide="upload-cloud" class="w-10 h-10 mx-auto text-zinc-400 mb-2"></i>
                    <p class="text-sm text-zinc-500" id="f_label">Haz clic para seleccionar el archivo xlsx</p>
                    <input type="file" id="f_xiga" name="archivo_xiga" accept=".xlsx"
                           class="hidden"
                           onchange="document.getElementById('f_label').textContent = this.files[0]?.name || 'Sin archivo'">
                </div>
            </div>

            <button type="submit"
                    class="w-full px-4 py-2.5 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white font-semibold flex items-center justify-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                Analizar archivo
            </button>
        </form>
    </div>

    <!-- ===================== PASO 2: PREVISUALIZACIÓN ===================== -->
    <?php elseif ($paso === 'previa'): ?>

    <!-- Resumen de tarjetas -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4 text-center">
            <div class="text-2xl font-bold text-zinc-900"><?= number_format($resumen['total']) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5">Registros totales</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= number_format($resumen['nuevas']) ?></div>
            <div class="text-xs text-green-600 mt-0.5">A importar</div>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 text-center">
            <div class="text-2xl font-bold text-amber-700"><?= number_format($resumen['duplicadas']) ?></div>
            <div class="text-xs text-amber-600 mt-0.5">Ya importados (omitir)</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?= number_format($resumen['sin_match']) ?></div>
            <div class="text-xs text-red-600 mt-0.5">Placas sin coincidencia</div>
        </div>
    </div>

    <?php if ($resumen['min_fecha']): ?>
    <p class="text-sm text-zinc-500">
        Período del reporte: <strong><?= e($resumen['min_fecha']) ?></strong> al <strong><?= e($resumen['max_fecha']) ?></strong>
        &nbsp;·&nbsp; Archivo: <strong><?= e($_SESSION['xiga_tmp_orig'] ?? 'reporte.xlsx') ?></strong>
    </p>
    <?php endif; ?>

    <?php if ($resumen['sin_match'] > 0): ?>
    <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 flex gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0 mt-0.5 text-amber-500"></i>
        <div>
            <p class="font-semibold"><?= $resumen['sin_match'] ?> registros sin coincidencia de placas</p>
            <p>Estas placas no existen en el catálogo de vehículos y no se importarán. Puedes agregarlas manualmente en <a href="<?= url('flotilla_vehiculos.php') ?>" class="underline">Vehículos</a> y volver a importar.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de preview -->
    <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between gap-3">
            <h3 class="font-semibold text-zinc-800 text-sm">Previsualización del reporte</h3>
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-1.5 text-xs text-zinc-600 cursor-pointer">
                    <input type="checkbox" id="chk_todos" class="rounded border-zinc-300"
                           onchange="filtrarTabla(this.checked)"> Mostrar solo nuevos
                </label>
            </div>
        </div>
        <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
            <table class="min-w-full text-xs" id="tbl-preview">
                <thead class="sticky top-0">
                    <tr class="bg-zinc-50 border-b border-zinc-200 text-zinc-600 font-semibold uppercase tracking-wide">
                        <th class="px-3 py-2 text-left">Estado</th>
                        <th class="px-3 py-2 text-left">Ticket</th>
                        <th class="px-3 py-2 text-left">Fecha</th>
                        <th class="px-3 py-2 text-left">Placas</th>
                        <th class="px-3 py-2 text-left">Vehículo</th>
                        <th class="px-3 py-2 text-left">Estación</th>
                        <th class="px-3 py-2 text-right">Litros</th>
                        <th class="px-3 py-2 text-left">Tipo</th>
                        <th class="px-3 py-2 text-right">Precio/L</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($filas_preview as $f): ?>
                    <?php
                        if ($f['es_duplicado']) {
                            $estado_cls  = 'bg-zinc-50';
                            $badge_cls   = 'bg-zinc-100 text-zinc-500';
                            $badge_txt   = 'Ya importado';
                            $data_estado = 'dup';
                        } elseif ($f['sin_match']) {
                            $estado_cls  = 'bg-red-50/50';
                            $badge_cls   = 'bg-red-100 text-red-700';
                            $badge_txt   = 'Sin match';
                            $data_estado = 'nomatch';
                        } else {
                            $estado_cls  = '';
                            $badge_cls   = 'bg-green-100 text-green-700';
                            $badge_txt   = 'Nuevo';
                            $data_estado = 'nuevo';
                        }
                    ?>
                    <tr class="hover:bg-zinc-50 <?= $estado_cls ?>" data-estado="<?= $data_estado ?>">
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium <?= $badge_cls ?>">
                                <?= $badge_txt ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 font-mono text-zinc-600"><?= e($f['ticket_numero']) ?></td>
                        <td class="px-3 py-2 whitespace-nowrap text-zinc-600"><?= e(substr($f['fecha'] ?? '', 0, 16)) ?></td>
                        <td class="px-3 py-2 font-mono font-semibold text-zinc-800"><?= e($f['placas_raw']) ?></td>
                        <td class="px-3 py-2 text-zinc-600"><?= e($f['vehiculo_desc'] ?? '—') ?></td>
                        <td class="px-3 py-2 text-zinc-500"><?= e($f['estacion'] ?? '') ?></td>
                        <td class="px-3 py-2 text-right font-medium"><?= number_format($f['litros'], 3) ?></td>
                        <td class="px-3 py-2">
                            <?php
                            $tl = ['gasolina_regular'=>'Reg.','gasolina_premium'=>'Prem.','diesel'=>'Diesel','gas'=>'Gas'];
                            echo e($tl[$f['tipo_combustible']] ?? $f['tipo_combustible']);
                            ?>
                        </td>
                        <td class="px-3 py-2 text-right">$<?= number_format($f['precio_litro'], 3) ?></td>
                        <td class="px-3 py-2 text-right font-semibold">$<?= number_format($f['monto_xiga'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Botones de acción -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <a href="<?= url('flotilla_importar_xiga.php') ?>"
           class="px-4 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50 flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Subir otro archivo
        </a>

        <?php if ($resumen['nuevas'] > 0): ?>
        <form method="post" action="<?= url('flotilla_importar_xiga.php') ?>">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="op" value="confirmar">
            <input type="hidden" name="importar_sin_match" value="0">
            <button type="submit"
                    onclick="return confirm('¿Confirmar la importación de <?= $resumen['nuevas'] ?> registros nuevos?')"
                    class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white font-semibold flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                Importar <?= number_format($resumen['nuevas']) ?> registros nuevos
            </button>
        </form>
        <?php else: ?>
        <div class="text-sm text-zinc-500 italic">No hay registros nuevos para importar.</div>
        <?php endif; ?>
    </div>

    <!-- ===================== PASO 3: RESULTADO ===================== -->
    <?php elseif ($paso === 'resultado'): ?>

    <div class="bg-white rounded-xl border border-zinc-200 p-6 max-w-lg space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-7 h-7 text-green-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-zinc-900">Importación completada</h3>
                <p class="text-sm text-zinc-500">Los consumos XIGA se han cargado en el sistema.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-green-50 border border-green-200 p-3 text-center">
                <div class="text-xl font-bold text-green-700"><?= number_format($resumen['insertados']) ?></div>
                <div class="text-xs text-green-600 mt-0.5">Registros importados</div>
            </div>
            <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-3 text-center">
                <div class="text-xl font-bold text-zinc-500"><?= number_format($resumen['duplicados']) ?></div>
                <div class="text-xs text-zinc-400 mt-0.5">Ya existían (omitidos)</div>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-center">
                <div class="text-xl font-bold text-amber-700"><?= number_format($resumen['sin_match']) ?></div>
                <div class="text-xs text-amber-600 mt-0.5">Sin match de placas</div>
            </div>
            <?php if ($resumen['con_error'] > 0): ?>
            <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-center">
                <div class="text-xl font-bold text-red-700"><?= number_format($resumen['con_error']) ?></div>
                <div class="text-xs text-red-600 mt-0.5">Con errores</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex gap-3 flex-wrap">
            <a href="<?= url('flotilla_combustible.php') ?>"
               class="flex-1 px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold text-center">
                Ver combustible
            </a>
            <a href="<?= url('flotilla_importar_xiga.php') ?>"
               class="flex-1 px-4 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-700 hover:bg-zinc-50 text-center">
                Importar otro archivo
            </a>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
function filtrarTabla(soloNuevos) {
    document.querySelectorAll('#tbl-preview tbody tr').forEach(function(tr) {
        if (soloNuevos && tr.dataset.estado !== 'nuevo') {
            tr.style.display = 'none';
        } else {
            tr.style.display = '';
        }
    });
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
