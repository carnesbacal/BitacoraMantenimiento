<?php
/**
 * ============================================================================
 * flotilla_importar_monsat.php - Importa kilometraje diario desde Monsat/GPS
 * ============================================================================
 * Sube uno o varios reportes "Información general" (XLS = HTML) exportados de
 * Monsat. Lee el dispositivo de ADENTRO del archivo (no del nombre), lo mapea
 * al vehículo por su alias/económico y carga el km diario en flotilla_km_gps.
 * Idempotente: reimportar un periodo no duplica (upsert por vehiculo+fecha).
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
if (!tiene_permiso('administrar')) {
    flash_set('error', 'Solo administradores pueden importar.');
    header('Location: ' . url('flotilla_vehiculos.php'));
    exit;
}

$tabla_ok = (bool) db_one("SHOW TABLES LIKE 'flotilla_km_gps'");

// Mapa económico normalizado -> vehículo.
$vehiculos_bd = [];
foreach (db_all("SELECT id, alias, placas, marca, modelo FROM flotilla_vehiculos") as $v) {
    if (!empty($v['alias'])) $vehiculos_bd[flotilla_norm_economico($v['alias'])] = $v;
}

$resultados       = [];   // por dispositivo
$no_reconocidos   = [];   // dispositivos sin match
$dias_totales     = 0;
$errores          = [];

if (es_post() && (string) input('op') === 'importar') {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } elseif (!$tabla_ok) {
        $errores[] = 'Falta la tabla flotilla_km_gps. Corre la migración migracion_km_gps.sql.';
    } elseif (empty($_FILES['archivos']['name'][0])) {
        $errores[] = 'Selecciona al menos un archivo.';
    } else {
        $files = $_FILES['archivos'];
        $n = count($files['name']);
        for ($i = 0; $i < $n; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if ((int) ($files['size'][$i] ?? 0) > 8 * 1024 * 1024) {
                $errores[] = "El archivo {$files['name'][$i]} excede 8 MB.";
                continue;
            }
            $html = @file_get_contents($files['tmp_name'][$i]);
            if ($html === false || stripos($html, '<') === false) {
                $errores[] = "El archivo {$files['name'][$i]} no es un reporte válido (debe ser el XLS/HTML de Monsat).";
                continue;
            }

            $bloques = flotilla_monsat_parse($html);
            if (empty($bloques)) {
                $errores[] = "No se encontraron dispositivos en {$files['name'][$i]}.";
                continue;
            }

            // Un archivo puede traer VARIOS vehículos (reporte "Todos los seleccionados").
            foreach ($bloques as $blq) {
                $disp = $blq['dispositivo'];
                if (!$disp) continue;
                $veh = $vehiculos_bd[flotilla_norm_economico($disp)] ?? null;
                if (!$veh) {
                    $no_reconocidos[$disp] = ($no_reconocidos[$disp] ?? 0) + count($blq['filas']);
                    continue;
                }

                $dias = 0; $km_total = 0.0; $desde = null; $hasta = null;
                foreach ($blq['filas'] as $f) {
                    if (empty($f['fecha'])) continue;
                    db_exec(
                        "INSERT INTO flotilla_km_gps (vehiculo_id, fecha, km, litros, costo_comb, fuente)
                         VALUES (:v, :f, :km, :l, :c, 'monsat')
                         ON DUPLICATE KEY UPDATE km = VALUES(km), litros = VALUES(litros),
                            costo_comb = VALUES(costo_comb), actualizado_en = CURRENT_TIMESTAMP",
                        ['v' => (int) $veh['id'], 'f' => $f['fecha'], 'km' => $f['km'],
                         'l' => $f['litros'], 'c' => $f['costo']]
                    );
                    $dias++; $km_total += (float) $f['km'];
                    if ($desde === null || $f['fecha'] < $desde) $desde = $f['fecha'];
                    if ($hasta === null || $f['fecha'] > $hasta) $hasta = $f['fecha'];
                }
                if ($dias === 0) continue;
                $dias_totales += $dias;
                $resultados[] = [
                    'dispositivo' => $disp,
                    'vehiculo'    => trim(($veh['alias'] ? $veh['alias'] . ' · ' : '') . $veh['marca'] . ' ' . $veh['modelo']),
                    'placas'      => $veh['placas'],
                    'dias'        => $dias,
                    'km_total'    => $km_total,
                    'desde'       => $desde,
                    'hasta'       => $hasta,
                ];
            }
        }
        if ($resultados) {
            registrar_auditoria('importar_monsat', 'flotilla_km_gps', 0,
                count($resultados) . ' vehículos, ' . $dias_totales . ' días');
        }
    }
}

// ── Configuración de cuentas de correo (IMAP) ──────────────────────────────
$tabla_cuentas = (bool) db_one("SHOW TABLES LIKE 'flotilla_monsat_cuentas'");

if (es_post() && $tabla_cuentas) {
    $opc = (string) input('op', '');
    if (in_array($opc, ['cuenta_guardar', 'cuenta_eliminar', 'cuenta_toggle', 'cuenta_probar', 'cuenta_importar'], true)) {
        if (!csrf_valido(input('_csrf'))) {
            $errores[] = 'Token de seguridad inválido.';
        } else {
            require_once __DIR__ . '/config/vault_helpers.php';
            if ($opc === 'cuenta_guardar') {
                $cid  = (int) input('cuenta_id', 0);
                $host = trim((string) input('host', ''));
                $usr  = trim((string) input('usuario', ''));
                $pass_nueva = (string) input('password', '');
                if ($host === '' || $usr === '') { $errores[] = 'Host y usuario son obligatorios.'; }
                if (empty($errores)) {
                    $base = [
                        's'   => (int) input('sucursal_id', 0) ?: null,
                        'n'   => trim((string) input('nombre', '')) ?: 'Monsat',
                        'h'   => $host,
                        'p'   => (int) input('port', 993) ?: 993,
                        'u'   => $usr,
                        'f'   => trim((string) input('folder', '')) ?: 'INBOX',
                        'r'   => trim((string) input('remitente', '')) ?: null,
                        'snl' => input('solo_no_leidos') ? 1 : 0,
                        'ml'  => input('marcar_leidos') ? 1 : 0,
                        'a'   => input('activo') ? 1 : 0,
                    ];
                    if ($cid > 0) {
                        $setp = ''; $pp = [];
                        if ($pass_nueva !== '') { $setp = ', password_cifrada = :pc'; $pp['pc'] = vault_cifrar($pass_nueva); }
                        db_exec("UPDATE flotilla_monsat_cuentas SET sucursal_id=:s, nombre=:n, host=:h, port=:p, usuario=:u, folder=:f, remitente=:r, solo_no_leidos=:snl, marcar_leidos=:ml, activo=:a{$setp} WHERE id=:id",
                            array_merge($base, ['id' => $cid], $pp));
                    } else {
                        db_exec("INSERT INTO flotilla_monsat_cuentas (sucursal_id,nombre,host,port,usuario,password_cifrada,folder,remitente,solo_no_leidos,marcar_leidos,activo)
                                 VALUES (:s,:n,:h,:p,:u,:pc,:f,:r,:snl,:ml,:a)",
                            array_merge($base, ['pc' => vault_cifrar($pass_nueva)]));
                    }
                    flash_set('exito', 'Cuenta de correo guardada.');
                    header('Location: ' . url('flotilla_importar_monsat.php')); exit;
                }
            } elseif ($opc === 'cuenta_eliminar') {
                db_exec("DELETE FROM flotilla_monsat_cuentas WHERE id=:id", ['id' => (int) input('cuenta_id', 0)]);
                flash_set('exito', 'Cuenta eliminada.'); header('Location: ' . url('flotilla_importar_monsat.php')); exit;
            } elseif ($opc === 'cuenta_toggle') {
                db_exec("UPDATE flotilla_monsat_cuentas SET activo = 1 - activo WHERE id=:id", ['id' => (int) input('cuenta_id', 0)]);
                header('Location: ' . url('flotilla_importar_monsat.php')); exit;
            } elseif ($opc === 'cuenta_probar' || $opc === 'cuenta_importar') {
                $c = db_one("SELECT * FROM flotilla_monsat_cuentas WHERE id=:id", ['id' => (int) input('cuenta_id', 0)]);
                if ($c) {
                    $res = flotilla_monsat_procesar_cuenta($c, $opc === 'cuenta_importar');
                    if (!$res['ok']) {
                        flash_set('error', 'Prueba fallida: ' . $res['error']);
                    } elseif ($opc === 'cuenta_probar') {
                        flash_set('exito', "Conexión OK. Correos por procesar: {$res['encontrados']}.");
                    } else {
                        db_exec("UPDATE flotilla_monsat_cuentas SET ultima_ejecucion=NOW(), ultimo_resultado=:r WHERE id=:id",
                            ['r' => "{$res['correos']} correo(s), {$res['vehiculos']} veh, {$res['dias']} días", 'id' => $c['id']]);
                        flash_set('exito', "Importado: {$res['correos']} correo(s), {$res['vehiculos']} vehículo(s), {$res['dias']} día(s).");
                    }
                }
                header('Location: ' . url('flotilla_importar_monsat.php')); exit;
            }
        }
    }
}

$cuentas = $tabla_cuentas
    ? db_all("SELECT c.*, s.nombre suc_nombre FROM flotilla_monsat_cuentas c LEFT JOIN sucursales s ON c.sucursal_id = s.id ORDER BY c.id")
    : [];
$sucursales_lista = db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
$cuentas_js = [];
foreach ($cuentas as $c) {
    $cuentas_js[(int) $c['id']] = [
        'sucursal_id' => $c['sucursal_id'] !== null ? (int) $c['sucursal_id'] : '',
        'nombre' => $c['nombre'], 'host' => $c['host'], 'port' => (int) $c['port'],
        'usuario' => $c['usuario'], 'folder' => $c['folder'], 'remitente' => (string) ($c['remitente'] ?? ''),
        'solo_no_leidos' => (int) $c['solo_no_leidos'], 'marcar_leidos' => (int) $c['marcar_leidos'], 'activo' => (int) $c['activo'],
    ];
}

$titulo_pagina = 'Flotilla · Importar Monsat';
$pagina_activa = 'flotilla_importar_monsat';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5 max-w-4xl">

    <div>
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="route" class="w-6 h-6 text-bacal-700"></i> Importar kilometraje (Monsat)
        </h2>
        <p class="text-sm text-zinc-500 mt-1">
            Sube el reporte <strong>"Millaje (diario)"</strong> de Monsat en <strong>XLS</strong>, con <strong>"Todos los dispositivos"</strong> — un solo archivo trae todas las unidades.
            Se lee cada dispositivo y se carga su km diario por vehículo (también puedes subir varios archivos).
        </p>
    </div>

    <?php if (!$tabla_ok): ?>
    <div class="px-4 py-3 rounded-lg bg-amber-50 border border-amber-300 text-sm text-amber-800">
        Aún no existe la tabla <strong>flotilla_km_gps</strong>. Corre <strong>migracion_km_gps.sql</strong> en phpMyAdmin antes de importar.
    </div>
    <?php endif; ?>

    <?php foreach ($errores as $e): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">✗ <?= e($e) ?></div>
    <?php endforeach; ?>

    <!-- Resultado -->
    <?php if ($resultados || $no_reconocidos): ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Resultado de la importación</h3>
            <span class="ml-auto text-xs text-zinc-500"><?= count($resultados) ?> vehículo(s) · <?= number_format($dias_totales) ?> días cargados</span>
        </div>
        <?php if ($resultados): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Vehículo</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Periodo</th>
                        <th class="px-4 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Días</th>
                        <th class="px-4 py-2 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Km total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($resultados as $r): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-2 font-mono font-bold text-zinc-800"><?= e($r['dispositivo']) ?></td>
                        <td class="px-4 py-2 text-zinc-700"><?= e($r['vehiculo']) ?> <span class="text-[10px] font-mono text-zinc-400"><?= e($r['placas']) ?></span></td>
                        <td class="px-4 py-2 text-xs text-zinc-500"><?= e((string) $r['desde']) ?> → <?= e((string) $r['hasta']) ?></td>
                        <td class="px-4 py-2 text-right text-zinc-700"><?= number_format($r['dias']) ?></td>
                        <td class="px-4 py-2 text-right font-semibold text-zinc-900"><?= number_format($r['km_total'], 1) ?> km</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php if ($no_reconocidos): ?>
        <div class="px-5 py-3 bg-amber-50 border-t border-amber-200 text-sm text-amber-800">
            <div class="font-semibold mb-1 flex items-center gap-1.5"><i data-lucide="alert-triangle" class="w-4 h-4"></i> Dispositivos no reconocidos (no se cargaron)</div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                <?php foreach ($no_reconocidos as $d => $c): ?>
                <li><span class="font-mono font-bold"><?= e($d) ?></span> — <?= (int) $c ?> días. Ningún vehículo con ese económico (alias).</li>
                <?php endforeach; ?>
            </ul>
            <p class="text-[11px] mt-1.5">Estandariza el nombre del dispositivo en Monsat para que empate con el alias del vehículo (ej. "C-11"), o ajusta el alias del vehículo.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Formulario de carga -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6">
        <form method="post" action="<?= url('flotilla_importar_monsat.php') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="importar">

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase tracking-wide">Reportes de Monsat (XLS) — puedes seleccionar varios</label>
                <input type="file" name="archivos[]" multiple accept=".xls,.xlsx,.html,.htm" required
                       class="w-full text-sm text-zinc-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-sm file:font-semibold hover:file:bg-bacal-100">
                <p class="text-[11px] text-zinc-500 mt-2">
                    En Monsat: Tipo <strong>"Millaje (diario)"</strong>, Dispositivos <strong>"Todos los seleccionados"</strong>, Formato <strong>XLS</strong>. Un solo archivo trae todas las unidades. También sirve el tipo "Información general".
                </p>
            </div>

            <div class="flex justify-end pt-2 border-t border-zinc-100">
                <button type="submit" <?= $tabla_ok ? '' : 'disabled' ?>
                        class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-2 <?= $tabla_ok ? '' : 'opacity-50 cursor-not-allowed' ?>">
                    <i data-lucide="upload" class="w-4 h-4"></i> Importar
                </button>
            </div>
        </form>
    </div>

    <div class="text-[11px] text-zinc-400 flex items-start gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>
        <span>El "Largo" es distancia por GPS (puede diferir un poco del odómetro del tablero). Alimenta el rendimiento km/L y el costo por km con datos reales. La carga es idempotente: reimportar un periodo no duplica.</span>
    </div>

    <!-- Configuración del correo automático -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6" x-data="monsatCfg()">
        <div class="flex items-center gap-2 mb-1">
            <i data-lucide="mail" class="w-5 h-5 text-bacal-700"></i>
            <h3 class="font-display text-base font-bold text-zinc-900">Correo automático (importación diaria)</h3>
        </div>
        <p class="text-xs text-zinc-500 mb-4">Buzón donde Monsat envía los reportes. Con el cron configurado en cPanel, se importan solos. Puedes tener una cuenta por sucursal (o una global).</p>

        <?php if (!$tabla_cuentas): ?>
        <div class="px-4 py-3 rounded-lg bg-amber-50 border border-amber-300 text-sm text-amber-800">
            Corre <strong>migracion_monsat_cuentas.sql</strong> en phpMyAdmin para habilitar esta configuración.
        </div>
        <?php else: ?>

        <?php if ($cuentas): ?>
        <div class="overflow-x-auto mb-5">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Buzón</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Servidor</th>
                        <th class="px-3 py-2 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Estado</th>
                        <th class="px-3 py-2 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Última ejecución</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($cuentas as $c): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-3 py-2.5"><?= $c['suc_nombre'] ? e($c['suc_nombre']) : '<span class="text-zinc-400 italic">Global</span>' ?></td>
                        <td class="px-3 py-2.5"><span class="font-medium text-zinc-800"><?= e($c['usuario']) ?></span><div class="text-[10px] text-zinc-400"><?= e($c['nombre']) ?></div></td>
                        <td class="px-3 py-2.5 text-xs text-zinc-500 font-mono"><?= e($c['host']) ?>:<?= (int) $c['port'] ?></td>
                        <td class="px-3 py-2.5 text-center">
                            <?php if ($c['activo']): ?><span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Activa</span>
                            <?php else: ?><span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-500">Inactiva</span><?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5 text-xs text-zinc-500"><?= $c['ultima_ejecucion'] ? e(fmt_fecha($c['ultima_ejecucion'])) : '—' ?><?php if (!empty($c['ultimo_resultado'])): ?><div class="text-[10px] text-zinc-400"><?= e($c['ultimo_resultado']) ?></div><?php endif; ?></td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" @click="editar(<?= (int) $c['id'] ?>)" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100 hover:text-bacal-700" title="Editar"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                                <?php foreach (['cuenta_probar'=>['plug','Probar conexión'],'cuenta_importar'=>['download','Importar ahora'],'cuenta_toggle'=>[$c['activo']?'pause':'play',$c['activo']?'Desactivar':'Activar'],'cuenta_eliminar'=>['trash-2','Eliminar']] as $opx=>$mm): ?>
                                <form method="POST" class="inline"<?= $opx==='cuenta_eliminar' ? ' onsubmit="return confirm(\'¿Eliminar esta cuenta de correo?\')"' : '' ?>>
                                    <?= csrf_input() ?><input type="hidden" name="op" value="<?= $opx ?>"><input type="hidden" name="cuenta_id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100 <?= $opx==='cuenta_eliminar'?'hover:text-red-600':'hover:text-bacal-700' ?>" title="<?= $mm[1] ?>"><i data-lucide="<?= $mm[0] ?>" class="w-3.5 h-3.5"></i></button>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <form method="POST" class="border-t border-zinc-100 pt-4 space-y-3">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="cuenta_guardar">
            <input type="hidden" name="cuenta_id" x-model="f.id">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-zinc-800" x-text="f.id ? 'Editar cuenta' : 'Nueva cuenta de correo'"></h4>
                <button type="button" x-show="f.id" @click="nueva()" class="text-xs text-zinc-500 hover:underline">Cancelar edición</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Sucursal</label>
                    <select name="sucursal_id" x-model="f.sucursal_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <option value="">Global / Todas</option>
                        <?php foreach ($sucursales_lista as $sl): ?><option value="<?= $sl['id'] ?>"><?= e($sl['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Nombre</label><input name="nombre" x-model="f.nombre" maxlength="80" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Host IMAP</label><input name="host" x-model="f.host" placeholder="mail.granodeoro.com.mx" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Puerto</label><input type="number" name="port" x-model="f.port" placeholder="993" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Usuario (correo)</label><input name="usuario" x-model="f.usuario" placeholder="monsat@granodeoro.com.mx" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Contraseña</label><input type="password" name="password" autocomplete="new-password" :placeholder="f.id ? '•••• (dejar vacío para no cambiar)' : ''" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Carpeta</label><input name="folder" x-model="f.folder" placeholder="INBOX" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
                <div><label class="block text-[11px] font-bold text-zinc-600 mb-1 uppercase tracking-wide">Filtrar remitente <span class="text-zinc-400 normal-case font-normal">(opcional)</span></label><input name="remitente" x-model="f.remitente" placeholder="monsat.com.mx" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700"></div>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-1.5 text-xs text-zinc-700"><input type="checkbox" name="solo_no_leidos" value="1" x-model="f.solo_no_leidos"> Solo correos no leídos</label>
                <label class="flex items-center gap-1.5 text-xs text-zinc-700"><input type="checkbox" name="marcar_leidos" value="1" x-model="f.marcar_leidos"> Marcar como leídos al importar</label>
                <label class="flex items-center gap-1.5 text-xs text-zinc-700"><input type="checkbox" name="activo" value="1" x-model="f.activo"> Cuenta activa</label>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> Guardar cuenta</button>
            </div>
        </form>
        <p class="text-[11px] text-zinc-400 mt-3 flex items-start gap-1.5"><i data-lucide="shield" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i> La contraseña se guarda cifrada (AES). Usa un buzón dedicado solo para estos reportes. Requiere la extensión IMAP de PHP y el cron en cPanel (ver INSTRUCCIONES_MONSAT_CORREO.md).</p>
        <?php endif; ?>
    </div>

</div>

<script>
function monsatCfg() {
    const vacio = { id:'', sucursal_id:'', nombre:'Monsat', host:'', port:993, usuario:'', folder:'INBOX', remitente:'', solo_no_leidos:true, marcar_leidos:true, activo:true };
    return {
        cuentas: <?= json_encode($cuentas_js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        f: { ...vacio },
        nueva() { this.f = { ...vacio }; },
        editar(id) {
            const c = this.cuentas[id]; if (!c) return;
            this.f = { id: id, sucursal_id: c.sucursal_id, nombre: c.nombre, host: c.host, port: c.port, usuario: c.usuario, folder: c.folder, remitente: c.remitente, solo_no_leidos: !!c.solo_no_leidos, marcar_leidos: !!c.marcar_leidos, activo: !!c.activo };
            this.$nextTick(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }));
        },
    };
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
