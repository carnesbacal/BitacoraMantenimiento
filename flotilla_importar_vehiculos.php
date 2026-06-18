<?php
/**
 * ============================================================================
 * flotilla_importar_vehiculos.php - Importar/actualizar vehículos desde Excel
 * ============================================================================
 * Flujo:
 *   1. Subir el archivo FLOTILLA BACAL.xlsx (hoja "FLOTILLA")
 *   2. Previsualización: datos extraídos, tipo y sucursal seleccionables
 *   3. Confirmar → INSERT o UPDATE por placas en flotilla_vehiculos
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
// Funciones de parseo del Excel de Flotilla
// ============================================================================

/**
 * Convierte letra(s) de columna Excel a índice 0-based (A→0, B→1 ...)
 */
function flot_col_idx(string $col): int {
    $col = strtoupper(trim($col));
    $r   = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $r = $r * 26 + (ord($col[$i]) - 64);
    }
    return $r - 1;
}

/**
 * Quita prefijos de namespace XML para usar simplexml sin problemas.
 */
function flot_strip_ns(string $xml): string {
    $xml = preg_replace('/(<\/?)(\w+):/', '$1', $xml);
    $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
    return $xml;
}

/**
 * Lee la hoja FLOTILLA del xlsx.
 * - Encabezados en fila 6; datos desde fila 7.
 * - Columnas clave (0-based): 1=NO.ECO, 3=MARCA, 4=MODELO, 5=AÑO,
 *   7=PLACAS, 15=COMBUSTIBLE, 18=TIPO
 * Retorna array de vehículos o string de error.
 */
function flot_leer_xlsx(string $filepath) {
    if (!class_exists('ZipArchive')) {
        return 'ZipArchive no disponible (habilitar ext-zip en PHP).';
    }

    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return 'No se pudo abrir el archivo xlsx.';
    }

    // Shared strings
    $strings = [];
    $ss_raw  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_raw) {
        $xml = @simplexml_load_string(flot_strip_ns($ss_raw));
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

    // La hoja FLOTILLA está en sheet1.xml
    $sheet_raw = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if (!$sheet_raw) {
        return 'No se encontró la hoja de datos en el xlsx.';
    }

    $xml = @simplexml_load_string(flot_strip_ns($sheet_raw));
    if (!$xml) {
        return 'No se pudo leer el contenido del archivo.';
    }

    $vehiculos = [];
    foreach ($xml->sheetData->row as $row) {
        $r_num = (int) $row['r'];
        if ($r_num < 7) continue; // Fila 6 = encabezados, datos desde 7

        $fila = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            preg_match('/^([A-Z]+)\d+$/', $ref, $m);
            if (!isset($m[1])) continue;
            $col_idx = flot_col_idx($m[1]);
            $tipo    = (string) $cell['t'];
            if ($tipo === 'inlineStr') {
                // openpyxl y LibreOffice guardan strings inline: <is><t>valor</t></is>
                $val = isset($cell->is->t) ? trim((string) $cell->is->t) : '';
            } else {
                $val = isset($cell->v) ? trim((string) $cell->v) : '';
                if ($tipo === 's' && $val !== '') {
                    $val = $strings[(int) $val] ?? '';
                }
            }
            $fila[$col_idx] = $val;
        }

        $placas = strtoupper(trim($fila[7] ?? ''));
        if (!$placas) continue; // Fila sin placas = fin de datos

        $año = (int) ($fila[5] ?? 0);
        if (!$año) continue; // Saltar filas sin año (totales, etc.)

        $comb_raw = strtolower(trim($fila[15] ?? ''));
        $comb     = (strpos($comb_raw, 'diesel') !== false) ? 'diesel' : 'gasolina';

        $vehiculos[] = [
            'alias'          => trim($fila[1] ?? '') ?: null,
            'marca'          => ucfirst(strtolower(trim($fila[3] ?? ''))),
            'modelo'         => trim($fila[4] ?? ''),
            'anio'           => $año,
            'placas'         => $placas,
            'combustible_tipo'=> $comb,
            'tipo_key'       => strtolower(trim($fila[18] ?? '')),
        ];
    }

    if (empty($vehiculos)) {
        return 'No se encontraron vehículos en el archivo. Verifica que sea el Excel correcto (hoja FLOTILLA, datos desde fila 7).';
    }

    return $vehiculos;
}

// Helper: intentar match de tipo_key contra los tipos de BD
function flot_match_tipo_id(string $tipo_key, array $tipos_bd): ?int {
    $tk = strtolower(trim($tipo_key));
    foreach ($tipos_bd as $t) {
        if (strtolower($t['nombre']) === $tk) return (int) $t['id'];
    }
    foreach ($tipos_bd as $t) {
        $tn = strtolower($t['nombre']);
        if ($tk === 'pick up'   && (strpos($tn,'pick') !== false || strpos($tn,'camioneta') !== false)) return (int) $t['id'];
        if ($tk === 'camion'    && strpos($tn,'cam') !== false)  return (int) $t['id'];
        if ($tk === 'sedan'     && (strpos($tn,'sed') !== false || strpos($tn,'auto') !== false)) return (int) $t['id'];
        if ($tk === 'van'       && (strpos($tn,'van') !== false || strpos($tn,'furg') !== false)) return (int) $t['id'];
        if (strpos($tn, $tk) !== false) return (int) $t['id'];
    }
    return isset($tipos_bd[0]) ? (int) $tipos_bd[0]['id'] : null;
}

// ============================================================================
// Lógica de pasos
// ============================================================================
$paso    = 'subir';
$errores = [];
$vehiculos_parsed = [];

// Catálogos de BD
$tipos_bd   = db_all("SELECT id, nombre FROM flotilla_tipos_vehiculo WHERE activo=1 ORDER BY nombre");
$sucursales = db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");

// Placas ya existentes en BD
$placas_existentes = [];
foreach (db_all("SELECT placas, id, alias FROM flotilla_vehiculos") as $v) {
    $placas_existentes[strtoupper($v['placas'])] = $v;
}

// ============================================================================
// PASO 2: archivo subido → previsualizar
// ============================================================================
if (es_post() && (string) input('op') === 'previa') {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif (empty($_FILES['archivo_flotilla']['tmp_name'])) {
        $errores[] = 'No se recibió ningún archivo.';
    } else {
        $file = $_FILES['archivo_flotilla'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'xlsx') {
            $errores[] = 'El archivo debe ser .xlsx.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'Error al subir el archivo (código: ' . $file['error'] . ').';
        } else {
            $tmp_dir  = __DIR__ . '/assets/tmp';
            if (!is_dir($tmp_dir)) @mkdir($tmp_dir, 0755, true);
            $tmp_name = $tmp_dir . '/flotilla_' . session_id() . '.xlsx';
            move_uploaded_file($file['tmp_name'], $tmp_name);

            $parsed = flot_leer_xlsx($tmp_name);

            if (is_string($parsed)) {
                $errores[] = 'Error al leer el archivo: ' . $parsed;
                @unlink($tmp_name);
            } else {
                $_SESSION['flot_tmp_file'] = $tmp_name;
                $_SESSION['flot_tmp_orig'] = basename($file['name']);
                $vehiculos_parsed = $parsed;
                $paso = 'previa';
            }
        }
    }
}

// ============================================================================
// PASO 3: confirmar → insertar / actualizar
// ============================================================================
if (es_post() && (string) input('op') === 'importar') {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif (empty($_SESSION['flot_tmp_file']) || !file_exists($_SESSION['flot_tmp_file'])) {
        $errores[] = 'Sesión expirada o archivo temporal no encontrado. Vuelve a subir el archivo.';
    } else {
        $parsed = flot_leer_xlsx($_SESSION['flot_tmp_file']);

        if (is_string($parsed)) {
            $errores[] = 'Error al releer el archivo: ' . $parsed;
        } else {
            $insertados  = 0;
            $actualizados = 0;
            $omitidos    = 0;

            foreach ($parsed as $idx => $veh) {
                $tipo_id     = (int) input("tipo_id_{$idx}", 0);
                $sucursal_id = (int) input("sucursal_id_{$idx}", 0) ?: null;
                $omitir      = (string) input("omitir_{$idx}", '') === '1';

                if ($omitir) { $omitidos++; continue; }
                if (!$tipo_id) { $errores[] = "Vehículo {$veh['placas']}: tipo no seleccionado."; continue; }

                $existente = $placas_existentes[$veh['placas']] ?? null;

                if ($existente) {
                    db_exec(
                        "UPDATE flotilla_vehiculos
                         SET tipo_id=:tipo, alias=:alias, marca=:marca, modelo=:modelo,
                             anio=:anio, combustible_tipo=:comb,
                             sucursal_id=COALESCE(:sid, sucursal_id)
                         WHERE placas=:placas",
                        [
                            'tipo'   => $tipo_id,
                            'alias'  => $veh['alias'],
                            'marca'  => $veh['marca'],
                            'modelo' => $veh['modelo'],
                            'anio'   => $veh['anio'],
                            'comb'   => $veh['combustible_tipo'],
                            'sid'    => $sucursal_id,
                            'placas' => $veh['placas'],
                        ]
                    );
                    registrar_auditoria('editar_vehiculo', 'flotilla_vehiculos', (int) $existente['id'],
                        "Actualizado vía importación Excel: {$veh['placas']}");
                    $actualizados++;
                } else {
                    db_exec(
                        "INSERT INTO flotilla_vehiculos
                            (tipo_id, sucursal_id, alias, marca, modelo, anio, placas,
                             combustible_tipo, estado, activo)
                         VALUES
                            (:tipo, :sid, :alias, :marca, :modelo, :anio, :placas,
                             :comb, 'activo', 1)",
                        [
                            'tipo'   => $tipo_id,
                            'sid'    => $sucursal_id,
                            'alias'  => $veh['alias'],
                            'marca'  => $veh['marca'],
                            'modelo' => $veh['modelo'],
                            'anio'   => $veh['anio'],
                            'placas' => $veh['placas'],
                            'comb'   => $veh['combustible_tipo'],
                        ]
                    );
                    $nuevo_id = db_last_id();
                    registrar_auditoria('crear_vehiculo', 'flotilla_vehiculos', $nuevo_id,
                        "Creado vía importación Excel: {$veh['placas']}");
                    $insertados++;
                }
            }

            @unlink($_SESSION['flot_tmp_file']);
            unset($_SESSION['flot_tmp_file'], $_SESSION['flot_tmp_orig']);

            if (empty($errores)) {
                $paso = 'resultado';
                $resumen_final = compact('insertados', 'actualizados', 'omitidos');
            }
        }
    }
}

// ============================================================================
// Vista
// ============================================================================
$titulo_pagina = 'Flotilla · Importar Vehículos';
$pagina_activa = 'flotilla_importar_vehiculos';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="truck" class="w-6 h-6 text-bacal-700"></i>
            Importar Vehículos desde Excel
        </h2>
        <a href="<?= url('flotilla_vehiculos.php') ?>"
           class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50 flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a Vehículos
        </a>
    </div>

    <?php if ($errores): ?>
    <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-800 text-sm space-y-1">
        <?php foreach ($errores as $em): ?>
        <p class="flex gap-2"><i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i><?= e($em) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ===================== PASO 1: SUBIR ===================== -->
    <?php if ($paso === 'subir'): ?>

    <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800 flex gap-3">
        <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5 text-blue-500"></i>
        <div>
            <p class="font-semibold mb-1">¿Qué archivo necesito?</p>
            <p>Sube el archivo <strong>FLOTILLA BACAL.xlsx</strong>. La importación lee la hoja <strong>"FLOTILLA"</strong> (encabezados en fila 6, datos desde fila 7). Si un vehículo ya existe por placas, se actualizan sus datos; si es nuevo, se crea.</p>
        </div>
    </div>

    <!-- Archivo de ejemplo -->
    <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-green-700"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-zinc-800">Formato de referencia</p>
                <p class="text-xs text-zinc-500">Ejemplo del Excel con las columnas clave marcadas: NO. ECO, MARCA, MODELO, AÑO, PLACAS, COMBUSTIBLE y TIPO.</p>
            </div>
        </div>
        <a href="<?= url('assets/FORMATO_FLOTILLA_ejemplo.xlsx') ?>"
           download="FORMATO_FLOTILLA_ejemplo.xlsx"
           class="flex items-center gap-2 px-4 py-2 rounded-lg border border-zinc-300 bg-white hover:bg-zinc-50 text-sm font-medium text-zinc-700 whitespace-nowrap">
            <i data-lucide="download" class="w-4 h-4 text-green-600"></i>
            Descargar ejemplo .xlsx
        </a>
    </div>

    <div class="bg-white rounded-xl border border-zinc-200 p-6 max-w-lg mx-auto">
        <form method="post" action="<?= url('flotilla_importar_vehiculos.php') ?>"
              enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="op" value="previa">

            <div>
                <label class="block text-sm font-semibold text-zinc-700 mb-2">
                    Archivo Excel de Flotilla (.xlsx)
                </label>
                <div class="border-2 border-dashed border-zinc-300 hover:border-bacal-500 rounded-xl p-6 text-center transition-colors cursor-pointer"
                     onclick="document.getElementById('f_flotilla').click()">
                    <i data-lucide="upload-cloud" class="w-10 h-10 mx-auto text-zinc-400 mb-2"></i>
                    <p class="text-sm text-zinc-500" id="f_label">Haz clic para seleccionar el archivo xlsx</p>
                    <input type="file" id="f_flotilla" name="archivo_flotilla" accept=".xlsx"
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

    <?php
    $nuevos  = 0; $a_upd = 0;
    $filas   = [];
    foreach ($vehiculos_parsed as $idx => $veh) {
        $existe = isset($placas_existentes[$veh['placas']]);
        if ($existe) $a_upd++; else $nuevos++;
        $filas[] = array_merge($veh, [
            'idx'       => $idx,
            'existe'    => $existe,
            'tipo_auto' => flot_match_tipo_id($veh['tipo_key'], $tipos_bd),
        ]);
    }
    ?>

    <!-- Resumen -->
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border border-zinc-200 p-4 text-center">
            <div class="text-2xl font-bold text-zinc-900"><?= count($filas) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5">Vehículos en el Excel</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= $nuevos ?></div>
            <div class="text-xs text-green-600 mt-0.5">Nuevos a crear</div>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 text-center">
            <div class="text-2xl font-bold text-amber-700"><?= $a_upd ?></div>
            <div class="text-xs text-amber-600 mt-0.5">A actualizar</div>
        </div>
    </div>

    <p class="text-sm text-zinc-500">
        Archivo: <strong><?= e($_SESSION['flot_tmp_orig'] ?? 'flotilla.xlsx') ?></strong>
        &nbsp;·&nbsp; Revisa el tipo de vehículo y la sucursal antes de confirmar.
    </p>

    <form method="post" action="<?= url('flotilla_importar_vehiculos.php') ?>">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="op" value="importar">

        <!-- Sucursal global -->
        <?php if ($sucursales): ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4 mb-4 flex items-center gap-4 flex-wrap">
            <span class="text-sm font-semibold text-zinc-700">Sucursal para todos:</span>
            <select id="sucursal_global" class="px-3 py-1.5 rounded-lg border border-zinc-300 text-sm bg-white">
                <option value="">— Sin asignar —</option>
                <?php foreach ($sucursales as $s): ?>
                <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="aplicarSucursalGlobal()"
                    class="px-3 py-1.5 rounded-lg bg-zinc-100 hover:bg-zinc-200 text-sm text-zinc-700">
                Aplicar a todos
            </button>
        </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 border-b border-zinc-200 text-xs font-semibold text-zinc-600 uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">No. Eco</th>
                            <th class="px-4 py-3 text-left">Vehículo</th>
                            <th class="px-4 py-3 text-left">Año</th>
                            <th class="px-4 py-3 text-left">Placas</th>
                            <th class="px-4 py-3 text-left">Combustible</th>
                            <th class="px-4 py-3 text-left">Tipo <span class="text-red-500">*</span></th>
                            <th class="px-4 py-3 text-left">Sucursal</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-center">Omitir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <?php foreach ($filas as $f): ?>
                        <tr class="hover:bg-zinc-50 transition-colors <?= $f['existe'] ? 'bg-amber-50/40' : '' ?>">
                            <td class="px-4 py-3 font-mono font-bold text-zinc-700"><?= e($f['alias'] ?? '—') ?></td>
                            <td class="px-4 py-3 font-medium text-zinc-900"><?= e($f['marca']) ?> <?= e($f['modelo']) ?></td>
                            <td class="px-4 py-3 text-zinc-600"><?= $f['anio'] ?></td>
                            <td class="px-4 py-3 font-mono text-zinc-800"><?= e($f['placas']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    <?= $f['combustible_tipo'] === 'diesel' ? 'bg-zinc-800 text-white' : 'bg-green-100 text-green-800' ?>">
                                    <?= $f['combustible_tipo'] === 'diesel' ? 'Diesel' : 'Gasolina' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <select name="tipo_id_<?= $f['idx'] ?>"
                                        class="px-2 py-1.5 rounded-lg border border-zinc-300 text-xs bg-white w-40">
                                    <option value="">— Selecciona —</option>
                                    <?php foreach ($tipos_bd as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                        <?= ((int) $t['id'] === (int) $f['tipo_auto']) ? 'selected' : '' ?>>
                                        <?= e($t['nombre']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <select name="sucursal_id_<?= $f['idx'] ?>"
                                        class="sucursal-sel px-2 py-1.5 rounded-lg border border-zinc-300 text-xs bg-white w-36">
                                    <option value="">— Sin asignar —</option>
                                    <?php foreach ($sucursales as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($f['existe']): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">
                                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Actualizar
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                                    <i data-lucide="plus-circle" class="w-3 h-3"></i> Nuevo
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <label class="inline-flex items-center justify-center">
                                    <input type="checkbox" name="omitir_<?= $f['idx'] ?>" value="1"
                                           class="w-4 h-4 rounded border-zinc-300 text-bacal-700">
                                </label>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 bg-zinc-50 border-t border-zinc-200 flex items-center justify-between gap-4 flex-wrap">
                <a href="<?= url('flotilla_importar_vehiculos.php') ?>"
                   class="px-4 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50 flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Subir otro archivo
                </a>
                <button type="submit"
                        onclick="return confirm('¿Confirmar la importación de <?= count($filas) ?> vehículos?')"
                        class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-2">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    Confirmar importación
                </button>
            </div>
        </div>
    </form>

    <!-- ===================== PASO 3: RESULTADO ===================== -->
    <?php elseif ($paso === 'resultado'): ?>

    <div class="bg-white rounded-xl border border-zinc-200 p-6 max-w-lg mx-auto space-y-5">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-7 h-7 text-green-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-zinc-900">Importación completada</h3>
                <p class="text-sm text-zinc-500">El catálogo de vehículos ha sido actualizado.</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl bg-green-50 border border-green-200 p-3 text-center">
                <div class="text-xl font-bold text-green-700"><?= $resumen_final['insertados'] ?></div>
                <div class="text-xs text-green-600 mt-0.5">Creados</div>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-center">
                <div class="text-xl font-bold text-amber-700"><?= $resumen_final['actualizados'] ?></div>
                <div class="text-xs text-amber-600 mt-0.5">Actualizados</div>
            </div>
            <div class="rounded-xl bg-zinc-50 border border-zinc-200 p-3 text-center">
                <div class="text-xl font-bold text-zinc-500"><?= $resumen_final['omitidos'] ?></div>
                <div class="text-xs text-zinc-400 mt-0.5">Omitidos</div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="<?= url('flotilla_vehiculos.php') ?>"
               class="flex-1 px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold text-center">
                Ver catálogo de vehículos
            </a>
            <a href="<?= url('flotilla_importar_vehiculos.php') ?>"
               class="flex-1 px-4 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-700 hover:bg-zinc-50 text-center">
                Importar otro archivo
            </a>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
function aplicarSucursalGlobal() {
    var val = document.getElementById('sucursal_global').value;
    document.querySelectorAll('.sucursal-sel').forEach(function(sel) {
        sel.value = val;
    });
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
