<?php
/**
 * ============================================================================
 * flotilla_vehiculo_ver.php - Vista detallada de un vehículo
 * ============================================================================
 * Tabs: Información · Documentos · Combustible · Mantenimientos · Gastos · Viajes
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');
$es_admin        = tiene_permiso('administrar');

$id = (int) input('id', 0);
$vehiculo = $id > 0 ? flotilla_vehiculo($id) : null;

if ($vehiculo && !flotilla_puede_ver_vehiculo($vehiculo)) {
    flash_set('error', 'No tienes permiso para ver ese vehículo.');
    header('Location: ' . url('flotilla_vehiculos.php'));
    exit;
}

if (!$vehiculo) {
    $titulo_pagina = 'Vehículo no encontrado';
    require_once __DIR__ . '/config/header.php';
    ?>
    <div class="max-w-md mx-auto text-center py-20">
        <div class="w-16 h-16 mx-auto rounded-full bg-zinc-100 flex items-center justify-center mb-4">
            <i data-lucide="search-x" class="w-8 h-8 text-zinc-400"></i>
        </div>
        <h2 class="font-display text-xl font-bold text-zinc-900 mb-2">Vehículo no encontrado</h2>
        <a href="<?= url('flotilla_vehiculos.php') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-bacal-700 text-white text-sm font-semibold rounded-lg">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a flotilla
        </a>
    </div>
    <?php
    require_once __DIR__ . '/config/footer.php';
    exit;
}

flotilla_actualizar_estado_documentos();

$errores = [];

// ----------------------------------------------------------------------------
// POST: registrar documento, combustible, gasto, viaje o mantenimiento
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        // --- Documento ---
        if ($op === 'doc_crear') {
            $dd = [
                'vehiculo_id'     => $id,
                'tipo_id'         => (int) input('tipo_id', 0),
                'numero_documento'=> trim((string) input('numero_documento', '')) ?: null,
                'proveedor'       => trim((string) input('proveedor', '')) ?: null,
                'fecha_inicio'    => trim((string) input('fecha_inicio', '')) ?: null,
                'fecha_vence'     => trim((string) input('fecha_vence', '')) ?: null,
                'monto'           => (float) input('monto', 0) ?: null,
                'notas'           => trim((string) input('notas', '')) ?: null,
                'estado'          => 'vigente',
                'creado_por'      => $u['id'],
            ];
            if (!$dd['tipo_id']) { $errores[] = 'Selecciona el tipo de documento.'; }
            if (empty($errores)) {
                db_exec("INSERT INTO flotilla_documentos (vehiculo_id,tipo_id,numero_documento,proveedor,fecha_inicio,fecha_vence,monto,notas,estado,creado_por)
                         VALUES (:vehiculo_id,:tipo_id,:numero_documento,:proveedor,:fecha_inicio,:fecha_vence,:monto,:notas,:estado,:creado_por)", $dd);
                flash_set('exito', 'Documento registrado.');
                header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=documentos"));
                exit;
            }
        }

        // --- Combustible ---
        if ($op === 'combustible_crear') {
            $km_nuevo = (int) input('km_odometro', 0);
            $litros   = (float) input('litros', 0);
            $precio   = (float) input('precio_litro', 0);

            if ($km_nuevo < $vehiculo['km_actual'])
                $errores[] = 'El odómetro no puede ser menor al km actual del vehículo.';
            if ($litros <= 0) $errores[] = 'Los litros deben ser mayor a 0.';
            if ($precio <= 0) $errores[] = 'El precio por litro debe ser mayor a 0.';

            if (empty($errores)) {
                // Calcular rendimiento vs carga anterior
                $ultima = db_one(
                    "SELECT km_odometro FROM flotilla_combustible WHERE vehiculo_id = :vid AND es_tanque_lleno = 1 ORDER BY fecha DESC LIMIT 1",
                    ['vid' => $id]
                );
                $km_recorridos = null;
                $rendimiento   = null;
                $es_lleno      = (int) input('es_tanque_lleno', 1);
                if ($ultima && $es_lleno) {
                    $km_recorridos = $km_nuevo - (int)$ultima['km_odometro'];
                    if ($km_recorridos > 0 && $litros > 0) {
                        $rendimiento = round($km_recorridos / $litros, 3);
                    }
                }

                $comb = [
                    'vehiculo_id'      => $id,
                    'conductor_id'     => (int) input('conductor_id', 0) ?: null,
                    'fecha'            => trim((string) input('fecha_carga', date('Y-m-d H:i:s'))),
                    'km_odometro'      => $km_nuevo,
                    'litros'           => $litros,
                    'precio_litro'     => $precio,
                    'tipo_combustible' => (string) input('tipo_combustible', $vehiculo['combustible_tipo']),
                    'estacion'         => trim((string) input('estacion', '')) ?: null,
                    'ticket_numero'    => trim((string) input('ticket_numero', '')) ?: null,
                    'es_tanque_lleno'  => $es_lleno,
                    'km_recorridos'    => $km_recorridos,
                    'rendimiento_kml'  => $rendimiento,
                    'notas'            => trim((string) input('notas_comb', '')) ?: null,
                    'creado_por'       => $u['id'],
                ];
                db_exec("INSERT INTO flotilla_combustible (vehiculo_id,conductor_id,fecha,km_odometro,litros,precio_litro,tipo_combustible,estacion,ticket_numero,es_tanque_lleno,km_recorridos,rendimiento_kml,notas,creado_por)
                         VALUES (:vehiculo_id,:conductor_id,:fecha,:km_odometro,:litros,:precio_litro,:tipo_combustible,:estacion,:ticket_numero,:es_tanque_lleno,:km_recorridos,:rendimiento_kml,:notas,:creado_por)", $comb);

                // Actualizar km_actual del vehículo
                if ($km_nuevo > $vehiculo['km_actual']) {
                    db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id AND km_actual < :km2",
                        ['km' => $km_nuevo, 'id' => $id, 'km2' => $km_nuevo]);
                }

                // Registrar gasto automático
                $cat_comb = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre LIKE '%Combustible%' LIMIT 1");
                if ($cat_comb) {
                    db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,fecha,concepto,monto,km_odometro,creado_por)
                             VALUES (:vid,:cat,:fecha,:concepto,:monto,:km,:cp)",
                        ['vid'=>$id,'cat'=>$cat_comb['id'],'fecha'=>date('Y-m-d',strtotime($comb['fecha'])),
                         'concepto'=>"Combustible · {$litros}L · " . ($comb['estacion'] ?? 'Sin estación'),
                         'monto'=>round($litros*$precio,2),'km'=>$km_nuevo,'cp'=>$u['id']]);
                }

                flash_set('exito', 'Carga de combustible registrada.');
                header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=combustible"));
                exit;
            }
        }

        // --- Gasto ---
        if ($op === 'gasto_crear') {
            $gd = [
                'vehiculo_id'  => $id,
                'categoria_id' => (int) input('categoria_id', 0),
                'conductor_id' => (int) input('conductor_id_gasto', 0) ?: null,
                'fecha'        => trim((string) input('fecha_gasto', date('Y-m-d'))),
                'concepto'     => trim((string) input('concepto', '')),
                'monto'        => (float) input('monto_gasto', 0),
                'proveedor'    => trim((string) input('proveedor_gasto', '')) ?: null,
                'numero_factura'=> trim((string) input('numero_factura', '')) ?: null,
                'km_odometro'  => (int) input('km_gasto', 0) ?: null,
                'notas'        => trim((string) input('notas_gasto', '')) ?: null,
                'creado_por'   => $u['id'],
            ];
            if (!$gd['categoria_id']) $errores[] = 'Selecciona una categoría.';
            if ($gd['concepto'] === '') $errores[] = 'El concepto es obligatorio.';
            if ($gd['monto'] <= 0)     $errores[] = 'El monto debe ser mayor a 0.';

            if (empty($errores)) {
                db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,conductor_id,fecha,concepto,monto,proveedor,numero_factura,km_odometro,notas,creado_por)
                         VALUES (:vehiculo_id,:categoria_id,:conductor_id,:fecha,:concepto,:monto,:proveedor,:numero_factura,:km_odometro,:notas,:creado_por)", $gd);
                flash_set('exito', 'Gasto registrado.');
                header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=gastos"));
                exit;
            }
        }

        // --- Mantenimiento ---
        if ($op === 'mant_crear') {
            $km_mant = (int) input('km_mant', 0);
            $md = [
                'vehiculo_id'  => $id,
                'programa_id'  => (int) input('programa_id', 0) ?: null,
                'nombre'       => trim((string) input('nombre_mant', '')),
                'descripcion'  => trim((string) input('descripcion_mant', '')) ?: null,
                'fecha'        => trim((string) input('fecha_mant', date('Y-m-d'))),
                'km_odometro'  => $km_mant,
                'taller'       => trim((string) input('taller', '')) ?: null,
                'tecnico'      => trim((string) input('tecnico_mant', '')) ?: null,
                'costo'        => (float) input('costo_mant', 0) ?: null,
                'numero_orden' => trim((string) input('numero_orden', '')) ?: null,
                'notas'        => trim((string) input('notas_mant', '')) ?: null,
                'creado_por'   => $u['id'],
            ];

            // Calcular próximos
            if ($md['programa_id']) {
                $prog = db_one("SELECT * FROM flotilla_mant_programas WHERE id = :id", ['id' => $md['programa_id']]);
                if ($prog) {
                    $md['proximo_km']    = $prog['intervalo_km']   ? $km_mant + (int)$prog['intervalo_km']   : null;
                    $md['proxima_fecha'] = $prog['intervalo_dias']
                        ? date('Y-m-d', strtotime($md['fecha'] . " +{$prog['intervalo_dias']} days"))
                        : null;
                }
            }

            if ($md['nombre'] === '') $errores[] = 'El nombre del mantenimiento es obligatorio.';
            if ($km_mant <= 0)        $errores[] = 'El km del odómetro es obligatorio.';

            if (empty($errores)) {
                $cols = implode(',', array_keys($md));
                $phs  = ':' . implode(',:', array_keys($md));
                db_exec("INSERT INTO flotilla_mant_historial ($cols) VALUES ($phs)", $md);

                // Actualizar km si aplica
                if ($km_mant > $vehiculo['km_actual']) {
                    db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id AND km_actual < :km2",
                        ['km' => $km_mant, 'id' => $id, 'km2' => $km_mant]);
                }

                // Gasto automático si tiene costo
                if ($md['costo'] > 0) {
                    $cat_mant = db_one("SELECT id FROM flotilla_categorias_gasto WHERE nombre LIKE '%Mantenimiento%' LIMIT 1");
                    if ($cat_mant) {
                        db_exec("INSERT INTO flotilla_gastos (vehiculo_id,categoria_id,fecha,concepto,monto,km_odometro,creado_por)
                                 VALUES (:vid,:cat,:fecha,:con,:monto,:km,:cp)",
                            ['vid'=>$id,'cat'=>$cat_mant['id'],'fecha'=>$md['fecha'],
                             'con'=>$md['nombre'],'monto'=>$md['costo'],'km'=>$km_mant,'cp'=>$u['id']]);
                    }
                }

                flash_set('exito', 'Mantenimiento registrado.');
                header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=mantenimientos"));
                exit;
            }
        }

        // --- Viaje ---
        if ($op === 'viaje_crear') {
            $km_sal = (int) input('km_salida', 0);
            $vd = [
                'vehiculo_id'           => $id,
                'conductor_id'          => (int) input('conductor_id_viaje', 0) ?: null,
                'sucursal_origen_id'    => (int) input('sucursal_origen_id', 0) ?: null,
                'sucursal_destino_id'   => (int) input('sucursal_destino_id', 0) ?: null,
                'destino_descripcion'   => trim((string) input('destino_desc', '')) ?: null,
                'fecha_salida'          => trim((string) input('fecha_salida', date('Y-m-d H:i:s'))),
                'km_salida'             => $km_sal,
                'proposito'             => trim((string) input('proposito', '')) ?: null,
                'carga_descripcion'     => trim((string) input('carga_desc', '')) ?: null,
                'carga_peso_kg'         => (float) input('carga_peso', 0) ?: null,
                'estado'                => 'en_ruta',
                'observaciones'         => trim((string) input('obs_viaje', '')) ?: null,
                'creado_por'            => $u['id'],
            ];
            if ($km_sal <= 0) $errores[] = 'El km de salida es obligatorio.';
            if (empty($errores)) {
                $cols = implode(',', array_keys($vd));
                $phs  = ':' . implode(',:', array_keys($vd));
                db_exec("INSERT INTO flotilla_viajes ($cols) VALUES ($phs)", $vd);
                flash_set('exito', 'Viaje registrado.');
                header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=viajes"));
                exit;
            }
        }

        // --- Cerrar viaje ---
        if ($op === 'viaje_cerrar') {
            $viaje_id = (int) input('viaje_id', 0);
            $km_ll    = (int) input('km_llegada', 0);
            if ($km_ll > 0) {
                db_exec("UPDATE flotilla_viajes SET km_llegada = :km, fecha_llegada = NOW(), estado = 'completado' WHERE id = :id AND vehiculo_id = :vid",
                    ['km' => $km_ll, 'id' => $viaje_id, 'vid' => $id]);
                if ($km_ll > $vehiculo['km_actual']) {
                    db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id AND km_actual < :km2",
                        ['km' => $km_ll, 'id' => $id, 'km2' => $km_ll]);
                }
                flash_set('exito', 'Viaje cerrado.');
            }
            header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id&tab=viajes"));
            exit;
        }
    }
}

// Cargar datos para las tabs
$tab         = (string) input('tab', 'info');
$documentos  = flotilla_documentos_vehiculo($id);
$combustible = flotilla_combustible_vehiculo($id, 10);
$mantenimts  = flotilla_mantenimientos_pendientes($id);
$gastos      = db_all("SELECT g.*, c.nombre cat_nombre, c.color cat_color
                        FROM flotilla_gastos g
                        INNER JOIN flotilla_categorias_gasto c ON g.categoria_id = c.id
                        WHERE g.vehiculo_id = :vid ORDER BY g.fecha DESC LIMIT 30", ['vid' => $id]);
$viajes      = db_all("SELECT v.*, c.nombre_completo conductor_nombre,
                               so.nombre suc_origen, sd.nombre suc_destino
                        FROM flotilla_viajes v
                        LEFT JOIN flotilla_conductores c  ON v.conductor_id = c.id
                        LEFT JOIN sucursales so ON v.sucursal_origen_id  = so.id
                        LEFT JOIN sucursales sd ON v.sucursal_destino_id = sd.id
                        WHERE v.vehiculo_id = :vid ORDER BY v.fecha_salida DESC LIMIT 20", ['vid' => $id]);

$tipos_doc   = db_all("SELECT * FROM flotilla_tipos_documento WHERE aplica_vehiculo=1 AND activo=1 ORDER BY nombre");
$categorias_gasto = db_all("SELECT * FROM flotilla_categorias_gasto WHERE activo=1 ORDER BY nombre");
$conductores = db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");
$programas   = db_all("SELECT * FROM flotilla_mant_programas WHERE activo=1 ORDER BY nombre");
$sucursales  = db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");

$rendimiento_prom = flotilla_rendimiento_promedio($id);
$gasto_mes   = flotilla_gasto_total($id, date('Y-m-01'), date('Y-m-t'));
$gasto_anio  = flotilla_gasto_total($id, date('Y-01-01'), date('Y-12-31'));

$alias_o_marca = $vehiculo['alias'] ?: "{$vehiculo['marca']} {$vehiculo['modelo']}";
$titulo_pagina = "Flotilla · {$alias_o_marca}";
$pagina_activa = 'flotilla';
require_once __DIR__ . '/config/header.php';
?>

<div class="animate-fade-in space-y-4">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <a href="<?= url('flotilla_vehiculos.php') ?>" class="hover:text-bacal-700 flex items-center gap-1">
            <i data-lucide="car" class="w-4 h-4"></i> Flotilla
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span class="text-zinc-900 font-semibold"><?= e($alias_o_marca) ?></span>
    </div>

    <!-- Flash + errores -->
    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($errores): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $err): ?><div>✗ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Header del vehículo -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-5">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-zinc-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="<?= $vehiculo['tiene_refrigeracion'] ? 'thermometer-snowflake' : 'car' ?>"
                       class="w-7 h-7 text-bacal-700"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-display text-xl font-extrabold text-zinc-900">
                            <?= $vehiculo['alias'] ? e($vehiculo['alias']) . ' · ' : '' ?>
                            <?= e($vehiculo['marca']) ?> <?= e($vehiculo['modelo']) ?>
                        </h2>
                        <?= flotilla_badge_estado($vehiculo['estado']) ?>
                        <?php if (!$vehiculo['es_propio']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">Rentado</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3 mt-1 text-sm text-zinc-500 flex-wrap">
                        <span class="font-mono font-bold text-zinc-700"><?= e($vehiculo['placas']) ?></span>
                        <span>·</span>
                        <span><?= e($vehiculo['anio']) ?></span>
                        <span>·</span>
                        <span><?= e($vehiculo['tipo_nombre']) ?></span>
                        <?php if ($vehiculo['sucursal_nombre']): ?>
                        <span>·</span>
                        <span><?= e($vehiculo['sucursal_nombre']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap text-center">
                <div class="bg-zinc-50 rounded-lg px-4 py-2">
                    <div class="font-display text-xl font-extrabold text-zinc-900"><?= number_format($vehiculo['km_actual']) ?></div>
                    <div class="text-[10px] uppercase tracking-wide font-bold text-zinc-500">Km actual</div>
                </div>
                <?php if ($rendimiento_prom): ?>
                <div class="bg-zinc-50 rounded-lg px-4 py-2">
                    <div class="font-display text-xl font-extrabold text-emerald-700"><?= number_format($rendimiento_prom, 1) ?></div>
                    <div class="text-[10px] uppercase tracking-wide font-bold text-zinc-500">km/L prom.</div>
                </div>
                <?php endif; ?>
                <div class="bg-zinc-50 rounded-lg px-4 py-2">
                    <div class="font-display text-xl font-extrabold text-zinc-900">$<?= number_format($gasto_mes, 0) ?></div>
                    <div class="text-[10px] uppercase tracking-wide font-bold text-zinc-500">Gasto mes</div>
                </div>
                <div class="bg-zinc-50 rounded-lg px-4 py-2">
                    <div class="font-display text-xl font-extrabold text-zinc-900">$<?= number_format($gasto_anio, 0) ?></div>
                    <div class="text-[10px] uppercase tracking-wide font-bold text-zinc-500">Gasto año</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <?php
    $tabs = [
        'info'          => ['Información',    'info'],
        'documentos'    => ['Documentos',     'file-text'],
        'combustible'   => ['Combustible',    'fuel'],
        'mantenimientos'=> ['Mantenimientos', 'wrench'],
        'gastos'        => ['Gastos',         'receipt'],
        'viajes'        => ['Viajes',         'map-pin'],
        'siniestros'    => ['Siniestros',     'shield-alert'],
        'multas'        => ['Multas',         'ticket-x'],
        'checklist'     => ['Checklist',      'clipboard-check'],
    ];
    // Alertas en tabs
    $docs_alerta   = count(array_filter($documentos, fn($d) => in_array($d['estado'], ['por_vencer','vencido'])));
    $mant_alerta   = count(array_filter($mantenimts, fn($m) => ($m['dias_restantes'] !== null && $m['dias_restantes'] <= 15) || ($m['km_restantes'] !== null && $m['km_restantes'] <= 500)));
    $viaje_activo  = count(array_filter($viajes, fn($v) => $v['estado'] === 'en_ruta'));
    $sin_activos   = (int)(db_one("SELECT COUNT(*) c FROM flotilla_siniestros WHERE vehiculo_id=:vid AND estado IN('reportado','en_proceso')", ['vid'=>$id])['c'] ?? 0);
    $multas_pend   = (int)(db_one("SELECT COUNT(*) c FROM flotilla_multas WHERE vehiculo_id=:vid AND estado IN('pendiente','impugnada')", ['vid'=>$id])['c'] ?? 0);
    ?>
    <div class="flex gap-1 border-b border-zinc-200 overflow-x-auto">
        <?php foreach ($tabs as $key => [$label, $icon]): ?>
        <?php
            $badge = match($key) {
                'documentos'     => $docs_alerta,
                'mantenimientos' => $mant_alerta,
                'viajes'         => $viaje_activo,
                'siniestros'     => $sin_activos,
                'multas'         => $multas_pend,
                default          => 0,
            };
        ?>
        <a href="<?= url("flotilla_vehiculo_ver.php?id=$id&tab=$key") ?>"
           class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                  <?= $tab === $key ? 'border-bacal-700 text-bacal-700' : 'border-transparent text-zinc-600 hover:text-zinc-900 hover:border-zinc-300' ?>">
            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
            <?= $label ?>
            <?php if ($badge > 0): ?>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700"><?= $badge ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Información                                                  -->
    <!-- ================================================================ -->
    <?php if ($tab === 'info'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-3">
            <h3 class="font-display font-bold text-zinc-900 text-sm uppercase tracking-wide flex items-center gap-2">
                <i data-lucide="car" class="w-4 h-4 text-bacal-700"></i> Datos del vehículo
            </h3>
            <?php
            $campos = [
                'Tipo'             => $vehiculo['tipo_nombre'],
                'Marca'            => $vehiculo['marca'],
                'Modelo'           => $vehiculo['modelo'],
                'Año'              => $vehiculo['anio'],
                'Color'            => $vehiculo['color'] ?? '—',
                'Placas'           => $vehiculo['placas'],
                'Número de serie'  => $vehiculo['numero_serie'] ?? '—',
                'Número de motor'  => $vehiculo['numero_motor'] ?? '—',
                'Combustible'      => ucfirst($vehiculo['combustible_tipo']),
                'Refrigeración'    => $vehiculo['tiene_refrigeracion'] ? "Sí ({$vehiculo['temp_min_c']}°C / {$vehiculo['temp_max_c']}°C)" : 'No',
                'Cap. de carga'    => $vehiculo['capacidad_carga_kg'] ? number_format($vehiculo['capacidad_carga_kg']) . ' kg' : '—',
                'Km inicial'       => number_format($vehiculo['km_inicial']) . ' km',
                'Km actual'        => number_format($vehiculo['km_actual']) . ' km',
                'Propiedad'        => $vehiculo['es_propio'] ? 'Propio' : 'Rentado (' . ($vehiculo['proveedor_renta'] ?? '—') . ')',
                'Adquisición'      => $vehiculo['fecha_adquisicion'] ? fmt_fecha($vehiculo['fecha_adquisicion']) : '—',
                'Costo adquisición'=> $vehiculo['costo_adquisicion'] ? '$' . number_format($vehiculo['costo_adquisicion'], 2) : '—',
            ];
            foreach ($campos as $label => $val): ?>
            <div class="flex justify-between text-sm border-b border-zinc-50 pb-2 last:border-0 last:pb-0">
                <span class="text-zinc-500 font-medium"><?= $label ?></span>
                <span class="text-zinc-900 font-semibold text-right"><?= e((string)$val) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-zinc-200 p-5 space-y-3">
                <h3 class="font-display font-bold text-zinc-900 text-sm uppercase tracking-wide flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-bacal-700"></i> Conductor asignado
                </h3>
                <?php if ($vehiculo['conductor_nombre']): ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-bacal-100 text-bacal-700 flex items-center justify-center font-bold text-sm">
                        <?= strtoupper(substr($vehiculo['conductor_nombre'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-semibold text-zinc-900"><?= e($vehiculo['conductor_nombre']) ?></div>
                        <?php if ($vehiculo['conductor_telefono']): ?>
                        <div class="text-xs text-zinc-500"><?= e($vehiculo['conductor_telefono']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-sm text-zinc-500">Sin conductor fijo asignado.</p>
                <?php endif; ?>
            </div>

            <?php if ($vehiculo['notas']): ?>
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
                <h3 class="font-bold text-amber-900 text-xs uppercase tracking-wide mb-2 flex items-center gap-1">
                    <i data-lucide="sticky-note" class="w-3.5 h-3.5"></i> Notas
                </h3>
                <p class="text-sm text-amber-800"><?= nl2br(e($vehiculo['notas'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Mantenimientos urgentes -->
            <?php if ($mant_alerta > 0): ?>
            <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                <h3 class="font-bold text-red-800 text-xs uppercase tracking-wide mb-2 flex items-center gap-1.5">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    <?= $mant_alerta ?> mantenimiento<?= $mant_alerta > 1 ? 's' : '' ?> urgente<?= $mant_alerta > 1 ? 's' : '' ?>
                </h3>
                <?php foreach (array_filter($mantenimts, fn($m) => ($m['dias_restantes'] !== null && $m['dias_restantes'] <= 15) || ($m['km_restantes'] !== null && $m['km_restantes'] <= 500)) as $m): ?>
                <div class="text-xs text-red-700 font-medium py-0.5">· <?= e($m['nombre']) ?></div>
                <?php endforeach; ?>
                <a href="?id=<?= $id ?>&tab=mantenimientos" class="text-xs text-red-700 font-bold underline mt-1 inline-block">Ver mantenimientos →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Documentos                                                   -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'documentos'): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Documentos del vehículo</h3>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-doc').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Agregar documento
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($documentos)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center">
            <i data-lucide="file-text" class="w-10 h-10 mx-auto text-zinc-300 mb-3"></i>
            <p class="text-sm text-zinc-500">Sin documentos registrados.</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Documento</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden md:table-cell">Número / Proveedor</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Vence</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($documentos as $doc): ?>
                    <?php $dias = $doc['fecha_vence'] ? (int) floor((strtotime($doc['fecha_vence']) - time()) / 86400) : null; ?>
                    <tr class="hover:bg-zinc-50 <?= $doc['estado'] === 'vencido' ? 'bg-red-50' : ($doc['estado'] === 'por_vencer' ? 'bg-amber-50' : '') ?>">
                        <td class="px-4 py-3 font-semibold text-zinc-900"><?= e($doc['tipo_nombre']) ?></td>
                        <td class="px-4 py-3 hidden md:table-cell text-zinc-600">
                            <?= $doc['numero_documento'] ? '<span class="font-mono text-xs">' . e($doc['numero_documento']) . '</span>' : '' ?>
                            <?= $doc['proveedor'] ? '<span class="text-zinc-400 mx-1">·</span>' . e($doc['proveedor']) : '' ?>
                        </td>
                        <td class="px-4 py-3 text-zinc-700">
                            <?= $doc['fecha_vence'] ? fmt_fecha($doc['fecha_vence']) : '—' ?>
                        </td>
                        <td class="px-4 py-3"><?= flotilla_badge_doc($doc['estado'], $dias) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal agregar documento -->
    <div id="modal-doc" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6" onclick.stop>
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="file-plus" class="w-4 h-4 text-bacal-700"></i> Agregar documento
            </h3>
            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="doc_crear">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select name="tipo_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($tipos_doc as $td): ?>
                        <option value="<?= $td['id'] ?>"><?= e($td['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Número de documento</label>
                    <input type="text" name="numero_documento" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Proveedor / Aseguradora</label>
                    <input type="text" name="proveedor" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha inicio</label>
                        <input type="date" name="fecha_inicio"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha vencimiento</label>
                        <input type="date" name="fecha_vence"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Costo ($)</label>
                    <input type="number" name="monto" step="0.01" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button" onclick="document.getElementById('modal-doc').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Combustible                                                  -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'combustible'): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Cargas de combustible</h3>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-comb').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Registrar carga
            </button>
            <?php endif; ?>
        </div>

        <?php if ($rendimiento_prom): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="trending-up" class="w-5 h-5 text-emerald-600"></i>
            <span class="text-sm font-semibold text-emerald-800">
                Rendimiento promedio (últimas 5 cargas): <strong><?= number_format($rendimiento_prom, 2) ?> km/L</strong>
            </span>
        </div>
        <?php endif; ?>

        <?php if (empty($combustible)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center">
            <i data-lucide="fuel" class="w-10 h-10 mx-auto text-zinc-300 mb-3"></i>
            <p class="text-sm text-zinc-500">Sin cargas de combustible registradas.</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Fecha</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Km</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Litros</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Total</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden md:table-cell">km/L</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden lg:table-cell">Estación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($combustible as $c): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-3 text-zinc-700"><?= fmt_fecha_hora($c['fecha']) ?></td>
                        <td class="px-4 py-3 text-right font-mono"><?= number_format($c['km_odometro']) ?></td>
                        <td class="px-4 py-3 text-right font-semibold"><?= number_format($c['litros'], 2) ?>L</td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-900">$<?= number_format($c['total'], 2) ?></td>
                        <td class="px-4 py-3 text-right hidden md:table-cell">
                            <?php if ($c['rendimiento_kml']): ?>
                            <span class="font-semibold <?= $c['rendimiento_kml'] >= 8 ? 'text-emerald-700' : ($c['rendimiento_kml'] >= 6 ? 'text-amber-700' : 'text-red-700') ?>">
                                <?= number_format($c['rendimiento_kml'], 2) ?>
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-zinc-500 hidden lg:table-cell"><?= $c['estacion'] ? e($c['estacion']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal carga combustible -->
    <div id="modal-comb" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="fuel" class="w-4 h-4 text-bacal-700"></i> Registrar carga de combustible
            </h3>
            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="combustible_crear">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha y hora <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="fecha_carga" value="<?= date('Y-m-d\TH:i') ?>" required
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro <span class="text-red-500">*</span></label>
                        <input type="number" name="km_odometro" required min="<?= $vehiculo['km_actual'] ?>" value="<?= $vehiculo['km_actual'] ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Litros <span class="text-red-500">*</span></label>
                        <input type="number" name="litros" required step="0.001" min="0.1"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Precio / litro <span class="text-red-500">*</span></label>
                        <input type="number" name="precio_litro" required step="0.001" min="0.01"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">¿Tanque lleno?</label>
                        <select name="es_tanque_lleno" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                            <option value="1">Sí (tanque lleno)</option>
                            <option value="0">No (parcial)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Gasolinera / Estación</label>
                    <input type="text" name="estacion" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$vehiculo['conductor_asignado_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button" onclick="document.getElementById('modal-comb').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Mantenimientos                                               -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'mantenimientos'): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Mantenimientos preventivos y correctivos</h3>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-mant').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Registrar mantenimiento
            </button>
            <?php endif; ?>
        </div>

        <!-- Programas / próximos -->
        <?php if ($mantenimts): ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-100 bg-zinc-50">
                <h4 class="font-bold text-sm text-zinc-700 flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-4 h-4 text-bacal-700"></i>
                    Estado de programas preventivos
                </h4>
            </div>
            <div class="divide-y divide-zinc-100">
                <?php foreach ($mantenimts as $m):
                    $urgente = ($m['dias_restantes'] !== null && $m['dias_restantes'] <= 0)
                            || ($m['km_restantes'] !== null && $m['km_restantes'] <= 0);
                    $alerta  = !$urgente && (
                                ($m['dias_restantes'] !== null && $m['dias_restantes'] <= 15)
                             || ($m['km_restantes'] !== null && $m['km_restantes'] <= 500));
                ?>
                <div class="flex items-center justify-between px-4 py-3 <?= $urgente ? 'bg-red-50' : ($alerta ? 'bg-amber-50' : '') ?>">
                    <div>
                        <div class="font-semibold text-sm <?= $urgente ? 'text-red-800' : ($alerta ? 'text-amber-800' : 'text-zinc-900') ?>">
                            <?= e($m['nombre']) ?>
                        </div>
                        <div class="text-xs text-zinc-500 mt-0.5">
                            <?php if ($m['ult_fecha']): ?>
                            Último: <?= fmt_fecha($m['ult_fecha']) ?> · <?= number_format($m['ult_km'] ?? 0) ?> km
                            <?php else: ?>
                            Sin historial
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <?php if ($m['proximo_km']): ?>
                        <div class="font-semibold <?= $urgente ? 'text-red-700' : ($alerta ? 'text-amber-700' : 'text-zinc-600') ?>">
                            Próx. km: <?= number_format($m['proximo_km']) ?>
                            <?= $m['km_restantes'] !== null ? ' (' . number_format($m['km_restantes']) . ' restantes)' : '' ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($m['proxima_fecha']): ?>
                        <div class="text-zinc-500 mt-0.5">
                            Próx. fecha: <?= fmt_fecha($m['proxima_fecha']) ?>
                            <?= $m['dias_restantes'] !== null ? ' (' . $m['dias_restantes'] . 'd)' : '' ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!$m['proximo_km'] && !$m['proxima_fecha']): ?>
                        <span class="text-zinc-400">Nunca realizado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial -->
        <?php
        $historial = db_all(
            "SELECT h.*, p.nombre prog_nombre
             FROM flotilla_mant_historial h
             LEFT JOIN flotilla_mant_programas p ON h.programa_id = p.id
             WHERE h.vehiculo_id = :vid
             ORDER BY h.fecha DESC LIMIT 25",
            ['vid' => $id]
        );
        ?>
        <?php if ($historial): ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-100 bg-zinc-50">
                <h4 class="font-bold text-sm text-zinc-700">Historial de mantenimientos ejecutados</h4>
            </div>
            <div class="divide-y divide-zinc-100">
                <?php foreach ($historial as $h): ?>
                <div class="px-4 py-3 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-zinc-100 text-zinc-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-zinc-900"><?= e($h['nombre']) ?></div>
                            <div class="text-xs text-zinc-500 mt-0.5">
                                <?= fmt_fecha($h['fecha']) ?> · <?= number_format($h['km_odometro']) ?> km
                                <?= $h['taller'] ? ' · ' . e($h['taller']) : '' ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-sm flex-shrink-0">
                        <?php if ($h['costo']): ?>
                        <div class="font-semibold text-zinc-900">$<?= number_format($h['costo'], 2) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal mantenimiento -->
    <div id="modal-mant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4">Registrar mantenimiento</h3>
            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="mant_crear">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Programa preventivo (opcional)</label>
                    <select name="programa_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Mantenimiento correctivo / sin programa —</option>
                        <?php foreach ($programas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Nombre / descripción <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_mant" required maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_mant" required value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro <span class="text-red-500">*</span></label>
                        <input type="number" name="km_mant" required min="0" value="<?= $vehiculo['km_actual'] ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Taller</label>
                        <input type="text" name="taller" maxlength="100"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Costo ($)</label>
                        <input type="number" name="costo_mant" step="0.01" min="0"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button" onclick="document.getElementById('modal-mant').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Gastos                                                       -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'gastos'): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-zinc-900">Gastos vehiculares</h3>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Mes: <strong>$<?= number_format($gasto_mes, 2) ?></strong>
                    · Año: <strong>$<?= number_format($gasto_anio, 2) ?></strong>
                </p>
            </div>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-gasto').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Registrar gasto
            </button>
            <?php endif; ?>
        </div>
        <?php if (empty($gastos)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center">
            <i data-lucide="receipt" class="w-10 h-10 mx-auto text-zinc-300 mb-3"></i>
            <p class="text-sm text-zinc-500">Sin gastos registrados.</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Fecha</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Concepto</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase hidden md:table-cell">Categoría</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($gastos as $g): ?>
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-3 text-zinc-500"><?= fmt_fecha($g['fecha']) ?></td>
                        <td class="px-4 py-3 font-medium text-zinc-900"><?= e($g['concepto']) ?></td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                  style="background-color:<?= e($g['cat_color']) ?>20;color:<?= e($g['cat_color']) ?>">
                                <?= e($g['cat_nombre']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-900">$<?= number_format($g['monto'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal gasto -->
    <div id="modal-gasto" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4">Registrar gasto</h3>
            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="gasto_crear">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select name="categoria_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar…</option>
                        <?php foreach ($categorias_gasto as $cg): ?>
                        <option value="<?= $cg['id'] ?>"><?= e($cg['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Concepto <span class="text-red-500">*</span></label>
                    <input type="text" name="concepto" required maxlength="200"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_gasto" required value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Monto ($) <span class="text-red-500">*</span></label>
                        <input type="number" name="monto_gasto" required step="0.01" min="0.01"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Proveedor</label>
                    <input type="text" name="proveedor_gasto" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button" onclick="document.getElementById('modal-gasto').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Viajes                                                       -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'viajes'): ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Viajes y logística</h3>
            <?php if ($puede_gestionar): ?>
            <button onclick="document.getElementById('modal-viaje').classList.remove('hidden')"
                    class="px-3 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Registrar salida
            </button>
            <?php endif; ?>
        </div>
        <?php if (empty($viajes)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center">
            <i data-lucide="map-pin" class="w-10 h-10 mx-auto text-zinc-300 mb-3"></i>
            <p class="text-sm text-zinc-500">Sin viajes registrados.</p>
        </div>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($viajes as $v):
                $en_ruta = $v['estado'] === 'en_ruta';
            ?>
            <div class="bg-white rounded-xl border <?= $en_ruta ? 'border-blue-300 bg-blue-50' : 'border-zinc-200' ?> p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg <?= $en_ruta ? 'bg-blue-100 text-blue-700' : 'bg-zinc-100 text-zinc-600' ?> flex items-center justify-center flex-shrink-0">
                            <i data-lucide="<?= $en_ruta ? 'navigation' : 'map-pin' ?>" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-zinc-900">
                                <?= $v['suc_origen'] ? e($v['suc_origen']) : 'Origen' ?>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 inline text-zinc-400"></i>
                                <?= $v['suc_destino'] ? e($v['suc_destino']) : ($v['destino_descripcion'] ? e($v['destino_descripcion']) : 'Destino') ?>
                            </div>
                            <div class="text-xs text-zinc-500 mt-0.5">
                                <?= fmt_fecha_hora($v['fecha_salida']) ?>
                                <?= $v['conductor_nombre'] ? ' · ' . e($v['conductor_nombre']) : '' ?>
                                <?= $v['km_recorridos'] ? ' · ' . number_format($v['km_recorridos']) . ' km' : '' ?>
                                <?= $v['proposito'] ? ' · ' . e($v['proposito']) : '' ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if ($en_ruta): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">En ruta</span>
                        <?php if ($puede_gestionar): ?>
                        <button onclick="document.getElementById('cerrar-viaje-<?= $v['id'] ?>').classList.remove('hidden')"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                            Cerrar viaje
                        </button>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600">Completado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($en_ruta && $puede_gestionar): ?>
                <div id="cerrar-viaje-<?= $v['id'] ?>" class="hidden mt-3 pt-3 border-t border-blue-200">
                    <form method="POST" class="flex items-end gap-2">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="viaje_cerrar">
                        <input type="hidden" name="viaje_id" value="<?= $v['id'] ?>">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-zinc-700 mb-1">Km de llegada</label>
                            <input type="number" name="km_llegada" required min="<?= $v['km_salida'] + 1 ?>"
                                   placeholder="<?= $v['km_salida'] + 1 ?>+"
                                   class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 flex-shrink-0">
                            Confirmar llegada
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal viaje -->
    <div id="modal-viaje" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="font-display text-base font-bold text-zinc-900 mb-4">Registrar salida</h3>
            <form method="POST" class="space-y-3">
                <?= csrf_input() ?>
                <input type="hidden" name="op" value="viaje_crear">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Sucursal origen</label>
                        <select name="sucursal_origen_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                            <option value="">— Sin especificar —</option>
                            <?php foreach ($sucursales as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $vehiculo['sucursal_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Sucursal destino</label>
                        <select name="sucursal_destino_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                            <option value="">— Otro destino —</option>
                            <?php foreach ($sucursales as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Destino (si no es sucursal)</label>
                    <input type="text" name="destino_desc" maxlength="200" placeholder="Dirección o descripción"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha y hora salida</label>
                        <input type="datetime-local" name="fecha_salida" value="<?= date('Y-m-d\TH:i') ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Km salida <span class="text-red-500">*</span></label>
                        <input type="number" name="km_salida" required min="0" value="<?= $vehiculo['km_actual'] ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Conductor</label>
                    <select name="conductor_id_viaje" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$vehiculo['conductor_asignado_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Propósito / motivo</label>
                    <input type="text" name="proposito" maxlength="200" placeholder="Entrega, recogida, mantenimiento…"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button type="button" onclick="document.getElementById('modal-viaje').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Registrar salida</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Siniestros                                                   -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'siniestros'): ?>
    <?php
    $siniestros_veh = db_all(
        "SELECT s.*, c.nombre_completo conductor_nombre
         FROM flotilla_siniestros s
         LEFT JOIN flotilla_conductores c ON s.conductor_id = c.id
         WHERE s.vehiculo_id = :vid
         ORDER BY s.fecha DESC",
        ['vid' => $id]
    );
    $tipos_sin = ['colision'=>'Colisión','robo_parcial'=>'Robo parcial','robo_total'=>'Robo total',
                  'vandalismo'=>'Vandalismo','fenomeno_natural'=>'Fenómeno natural','otro'=>'Otro'];
    $estados_sin = ['reportado'=>['bg-amber-100','text-amber-800','Reportado'],
                    'en_proceso'=>['bg-blue-100','text-blue-800','En proceso'],
                    'resuelto'=>['bg-emerald-100','text-emerald-800','Resuelto'],
                    'cerrado'=>['bg-zinc-100','text-zinc-600','Cerrado']];
    ?>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Siniestros</h3>
            <a href="<?= url("flotilla_siniestros.php?vehiculo_id=$id") ?>"
               class="px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold hover:bg-bacal-800 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Registrar siniestro
            </a>
        </div>
        <?php if (empty($siniestros_veh)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center text-sm text-zinc-400">
            <i data-lucide="shield-check" class="w-10 h-10 mx-auto text-emerald-300 mb-2"></i>
            Sin siniestros registrados para este vehículo.
        </div>
        <?php else: ?>
        <?php foreach ($siniestros_veh as $s):
            [$bg, $tx, $label] = $estados_sin[$s['estado']] ?? ['bg-zinc-100','text-zinc-600',$s['estado']];
        ?>
        <div class="bg-white rounded-xl border border-zinc-200 p-4 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-zinc-900"><?= $tipos_sin[$s['tipo']] ?? $s['tipo'] ?></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $bg ?> <?= $tx ?>"><?= $label ?></span>
                </div>
                <div class="text-xs text-zinc-500"><?= fmt_fecha_hora($s['fecha']) ?><?= $s['lugar'] ? ' · ' . e($s['lugar']) : '' ?></div>
                <p class="text-sm text-zinc-700 mt-1"><?= e($s['descripcion']) ?></p>
            </div>
            <div class="text-right text-xs flex flex-col items-end gap-1">
                <?php if ($s['monto_reparacion']): ?>
                <div class="font-semibold text-zinc-800">Reparación: $<?= number_format($s['monto_reparacion'],2) ?></div>
                <?php endif; ?>
                <a href="<?= url("flotilla_siniestros.php?vehiculo_id=$id") ?>" class="text-bacal-700 hover:underline font-medium">Ver todos</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Multas                                                        -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'multas'): ?>
    <?php
    $multas_veh = db_all(
        "SELECT m.*, c.nombre_completo conductor_nombre
         FROM flotilla_multas m
         LEFT JOIN flotilla_conductores c ON m.conductor_id = c.id
         WHERE m.vehiculo_id = :vid
         ORDER BY CASE m.estado WHEN 'pendiente' THEN 0 ELSE 1 END, m.fecha_infraccion DESC",
        ['vid' => $id]
    );
    $estados_multa = ['pendiente'=>['bg-amber-100','text-amber-800','Pendiente'],
                      'pagada'=>['bg-emerald-100','text-emerald-800','Pagada'],
                      'impugnada'=>['bg-blue-100','text-blue-800','Impugnada'],
                      'cancelada'=>['bg-zinc-100','text-zinc-600','Cancelada']];
    ?>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Multas e infracciones</h3>
            <a href="<?= url("flotilla_multas.php?vehiculo_id=$id") ?>"
               class="px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold hover:bg-bacal-800 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Registrar multa
            </a>
        </div>
        <?php if (empty($multas_veh)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center text-sm text-zinc-400">
            <i data-lucide="check-circle-2" class="w-10 h-10 mx-auto text-emerald-300 mb-2"></i>
            Sin multas registradas para este vehículo.
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <table class="min-w-full divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold">Fecha</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Infracción</th>
                        <th class="text-right px-4 py-2.5 font-semibold">Monto</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Estado</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Límite</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($multas_veh as $m):
                    [$bg, $tx, $lbl] = $estados_multa[$m['estado']] ?? ['bg-zinc-100','text-zinc-600',$m['estado']];
                ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-2.5 text-zinc-600 whitespace-nowrap"><?= fmt_fecha($m['fecha_infraccion']) ?></td>
                    <td class="px-4 py-2.5 text-zinc-700 max-w-xs truncate"><?= e($m['motivo']) ?></td>
                    <td class="px-4 py-2.5 text-right font-semibold text-zinc-900">$<?= number_format($m['monto_original'],2) ?></td>
                    <td class="px-4 py-2.5"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $bg ?> <?= $tx ?>"><?= $lbl ?></span></td>
                    <td class="px-4 py-2.5 text-zinc-500 text-xs whitespace-nowrap"><?= $m['fecha_vence_pago'] ? fmt_fecha($m['fecha_vence_pago']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-right">
            <a href="<?= url("flotilla_multas.php?vehiculo_id=$id") ?>" class="text-xs text-bacal-700 hover:underline font-medium">
                Ver y gestionar todas las multas →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Checklist                                                     -->
    <!-- ================================================================ -->
    <?php elseif ($tab === 'checklist'): ?>
    <?php
    $checklists_veh = db_all(
        "SELECT ch.*, c.nombre_completo conductor_nombre
         FROM flotilla_checklists ch
         LEFT JOIN flotilla_conductores c ON ch.conductor_id = c.id
         WHERE ch.vehiculo_id = :vid
         ORDER BY ch.fecha DESC
         LIMIT 20",
        ['vid' => $id]
    );
    $tipos_ck = ['pre_viaje'=>'Pre-viaje','post_viaje'=>'Post-viaje','diario'=>'Diario'];
    ?>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-bold text-zinc-900">Historial de checklists</h3>
            <a href="<?= url("flotilla_checklist.php?modo=nuevo") ?>"
               class="px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold hover:bg-bacal-800 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Nuevo checklist
            </a>
        </div>
        <?php if (empty($checklists_veh)): ?>
        <div class="bg-white rounded-xl border border-zinc-200 py-12 text-center text-sm text-zinc-400">
            <i data-lucide="clipboard" class="w-10 h-10 mx-auto text-zinc-300 mb-2"></i>
            Sin checklists registrados para este vehículo.
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl border border-zinc-200 overflow-hidden">
            <table class="min-w-full divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold">Fecha</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Tipo</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Conductor</th>
                        <th class="text-left px-4 py-2.5 font-semibold">Resultado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                <?php foreach ($checklists_veh as $ck): ?>
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-2.5 text-zinc-600 whitespace-nowrap"><?= fmt_fecha_hora($ck['creado_en']) ?></td>
                    <td class="px-4 py-2.5 text-zinc-700"><?= $tipos_ck[$ck['tipo']] ?? $ck['tipo'] ?></td>
                    <td class="px-4 py-2.5 text-zinc-600"><?= $ck['conductor_nombre'] ? e($ck['conductor_nombre']) : '—' ?></td>
                    <td class="px-4 py-2.5">
                        <?php
                        $res_c = match($ck['resultado'] ?? 'ok') {
                            'ok'           => ['bg-emerald-100','text-emerald-800','check','Apto'],
                            'observaciones'=> ['bg-amber-100',  'text-amber-800',  'alert-circle','Obs.'],
                            'no_apto'      => ['bg-red-100',    'text-red-800',    'x-circle','No apto'],
                            default        => ['bg-zinc-100',   'text-zinc-600',   'minus','—'],
                        };
                        [$rb,$rt,$ri,$rl] = $res_c;
                        ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= $rb ?> <?= $rt ?>">
                            <i data-lucide="<?= $ri ?>" class="w-3 h-3"></i> <?= $rl ?>
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <a href="<?= url("flotilla_checklist.php?modo=ver&id={$ck['id']}") ?>"
                           class="text-xs text-bacal-700 hover:underline font-medium">Ver</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
