<?php
/**
 * ============================================================================
 * flotilla_mantenimiento.php - Mantenimiento preventivo y correctivo
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
$u = usuario_actual();
$puede_gestionar = tiene_permiso('administrar') || tiene_permiso('resolver');

$f_vehiculo_id = (int) input('vehiculo_id', 0);
$f_vista       = (string) input('vista', 'pendientes'); // pendientes | historial
$f_sucursal    = (int) input('sucursal_id', 0);
$f_desde       = trim((string) input('desde', ''));
$f_hasta       = trim((string) input('hasta', ''));

if (!tiene_permiso('ver_todas_sucursales')) {
    $f_sucursal = (int) $u['sucursal_id'];
}

$errores = [];

// ----------------------------------------------------------------------------
// POST
// ----------------------------------------------------------------------------
if (es_post() && $puede_gestionar) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        if ($op === 'registrar') {
            $vid         = (int) input('vehiculo_id', 0);
            $programa_id = (int) input('programa_id', 0) ?: null;
            $nombre      = trim((string) input('nombre', ''));
            $descripcion = trim((string) input('descripcion', '')) ?: null;
            $fecha       = trim((string) input('fecha', ''));
            $fecha_fin   = trim((string) input('fecha_fin', '')) ?: null;
            $km_odo      = (int) input('km_odometro', 0) ?: null;
            $taller      = trim((string) input('taller', '')) ?: null;
            $tecnico     = trim((string) input('tecnico', '')) ?: null;
            $costo       = (float) input('costo', 0) ?: null;
            $num_orden   = trim((string) input('numero_orden', '')) ?: null;
            $prox_km     = (int) input('proximo_km', 0) ?: null;
            $prox_fecha  = trim((string) input('proxima_fecha', '')) ?: null;
            $notas       = trim((string) input('notas', '')) ?: null;

            if (!$vid)    $errores[] = 'Selecciona un vehículo.';
            if (!$nombre) $errores[] = 'El nombre del mantenimiento es obligatorio.';
            if (!$fecha)  $errores[] = 'La fecha de inicio es obligatoria.';
            if ($fecha_fin && $fecha_fin < $fecha) $errores[] = 'La fecha de fin no puede ser anterior a la de inicio.';

            $proveedor_id = null;
            if ($taller) { $pr = db_one("SELECT id FROM proveedores WHERE nombre = :n LIMIT 1", ['n' => $taller]); $proveedor_id = $pr['id'] ?? null; }
            $factura_url = null;
            $fotos_m = ['antes' => null, 'despues' => null];
            if (empty($errores)) {
                $rec = flotilla_guardar_recibo($_FILES['factura'] ?? []);
                if ($rec['error']) $errores[] = $rec['error']; else $factura_url = $rec['ruta'];
                $fm = flotilla_mant_guardar_fotos();
                if ($fm['error']) $errores[] = $fm['error']; else $fotos_m = $fm;
            }

            if (empty($errores)) {
                try {
                    $cx=''; $vx=''; $px=[];
                    if (db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'fecha_fin'"))    { $cx.=', fecha_fin'; $vx.=', :fecha_fin'; $px['fecha_fin']=$fecha_fin; }
                    if (db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'proveedor_id'")) { $cx.=', proveedor_id'; $vx.=', :prov_id'; $px['prov_id']=$proveedor_id; }
                    if (db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_antes_url'"))   { $cx.=', foto_antes_url'; $vx.=', :fa'; $px['fa']=$fotos_m['antes']; }
                    if (db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_despues_url'")) { $cx.=', foto_despues_url'; $vx.=', :fd'; $px['fd']=$fotos_m['despues']; }
                    db_exec(
                        "INSERT INTO flotilla_mant_historial
                            (vehiculo_id, programa_id, nombre, descripcion, fecha, km_odometro,
                             taller, tecnico, costo, numero_orden, archivo_url,
                             proximo_km, proxima_fecha, notas, creado_por{$cx})
                         VALUES
                            (:vid, :prog, :nombre, :desc, :fecha, :km,
                             :taller, :tecnico, :costo, :orden, :archivo,
                             :prox_km, :prox_fecha, :notas, :cp{$vx})",
                        array_merge([
                            'vid'        => $vid,
                            'prog'       => $programa_id,
                            'nombre'     => $nombre,
                            'desc'       => $descripcion,
                            'fecha'      => $fecha,
                            'km'         => $km_odo,
                            'taller'     => $taller,
                            'tecnico'    => $tecnico,
                            'costo'      => $costo,
                            'orden'      => $num_orden,
                            'archivo'    => $factura_url,
                            'prox_km'    => $prox_km,
                            'prox_fecha' => $prox_fecha,
                            'notas'      => $notas,
                            'cp'         => $u['id'],
                        ], $px)
                    );
                    $mant_id = db_last_id();

                    if ($km_odo) {
                        $va_km = db_one("SELECT km_actual FROM flotilla_vehiculos WHERE id = :id", ['id' => $vid]);
                        db_exec("UPDATE flotilla_vehiculos SET km_actual = :km WHERE id = :id AND km_actual < :km2",
                            ['km' => $km_odo, 'id' => $vid, 'km2' => $km_odo]);
                        if ($va_km && $km_odo > (int) $va_km['km_actual']) {
                            flotilla_odometro_registrar($vid, $km_odo, 'mantenimiento', (int) $va_km['km_actual'], $u['id']);
                        }
                    }

                    // Estado del vehículo: abierto -> En taller; cerrado -> Activo
                    flotilla_vehiculo_taller($vid, $fecha_fin === null);

                    // Gasto por proveedor (si hay costo)
                    flotilla_mant_gasto_sync($mant_id, $vid, $costo, $taller,
                        $nombre . ($taller ? " – {$taller}" : ''), $fecha, $num_orden, $km_odo, $u['id']);

                    registrar_auditoria('registrar_mantenimiento', 'flotilla_mant_historial', $mant_id,
                        "Vehículo ID {$vid}: {$nombre}");
                    flash_set('exito', $fecha_fin === null
                        ? 'Mantenimiento abierto. El vehículo quedó "En taller".'
                        : 'Mantenimiento registrado correctamente.');
                    header('Location: ' . url("flotilla_mantenimiento.php?vehiculo_id={$vid}&vista=historial"));
                    exit;
                } catch (Throwable $e) {
                    $errores[] = 'Error: ' . $e->getMessage();
                }
            }
        }

        if ($op === 'mant_editar') {
            $mid_e  = (int) input('mant_id', 0);
            $mant_e = db_one("SELECT * FROM flotilla_mant_historial WHERE id = :id", ['id' => $mid_e]);
            if (!$mant_e) {
                $errores[] = 'Mantenimiento no encontrado.';
            } else {
                $nombre_e = trim((string) input('nombre', ''));
                $fecha_e  = trim((string) input('fecha', '')) ?: $mant_e['fecha'];
                $km_e     = (int) input('km_odometro', 0) ?: null;
                $taller_e = trim((string) input('taller', '')) ?: null;
                $tec_e    = trim((string) input('tecnico', '')) ?: null;
                $costo_e  = (float) input('costo', 0) ?: null;
                $orden_e  = trim((string) input('numero_orden', '')) ?: null;
                $desc_e   = trim((string) input('descripcion', '')) ?: null;
                $notas_e  = trim((string) input('notas', '')) ?: null;
                $prov_id_e = null;
                if ($taller_e) { $pre = db_one("SELECT id FROM proveedores WHERE nombre = :n LIMIT 1", ['n'=>$taller_e]); $prov_id_e = $pre['id'] ?? null; }
                if ($nombre_e === '') $errores[] = 'El nombre del mantenimiento es obligatorio.';
                $fac_e = $mant_e['archivo_url'];
                $re = flotilla_guardar_recibo($_FILES['factura'] ?? []);
                if ($re['error']) $errores[] = $re['error']; elseif ($re['ruta']) $fac_e = $re['ruta'];
                $fotos_e = flotilla_mant_guardar_fotos();
                if ($fotos_e['error']) $errores[] = $fotos_e['error'];
                if (empty($errores)) {
                    $cx_e = ''; $px_e = [];
                    if (db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'proveedor_id'")) { $cx_e = ', proveedor_id = :prov_id'; $px_e['prov_id'] = $prov_id_e; }
                    if (!empty($fotos_e['antes'])   && db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_antes_url'"))   { $cx_e .= ', foto_antes_url = :fa';   $px_e['fa'] = $fotos_e['antes']; }
                    if (!empty($fotos_e['despues']) && db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_despues_url'")) { $cx_e .= ', foto_despues_url = :fd'; $px_e['fd'] = $fotos_e['despues']; }
                    db_exec("UPDATE flotilla_mant_historial SET
                                nombre=:nombre, descripcion=:desc, fecha=:fecha, km_odometro=:km,
                                taller=:taller, tecnico=:tec, costo=:costo, numero_orden=:orden,
                                archivo_url=:au, notas=:notas{$cx_e}
                             WHERE id=:id",
                        array_merge([
                            'nombre'=>$nombre_e, 'desc'=>$desc_e, 'fecha'=>$fecha_e, 'km'=>$km_e,
                            'taller'=>$taller_e, 'tec'=>$tec_e, 'costo'=>$costo_e, 'orden'=>$orden_e,
                            'au'=>$fac_e, 'notas'=>$notas_e, 'id'=>$mid_e,
                        ], $px_e));
                    flotilla_mant_gasto_sync($mid_e, (int) $mant_e['vehiculo_id'], $costo_e, $taller_e,
                        $nombre_e . ($taller_e ? " – {$taller_e}" : ''), $fecha_e, $orden_e, $km_e, $u['id']);
                    flash_set('exito', 'Mantenimiento actualizado.');
                    header('Location: ' . url('flotilla_mantenimiento.php?vehiculo_id=' . (int) $mant_e['vehiculo_id']));
                    exit;
                }
            }
        }

        if ($op === 'cerrar') {
            $mid         = (int) input('mant_id', 0);
            $fecha_fin_c = trim((string) input('fecha_fin', '')) ?: date('Y-m-d');
            $costo_c     = (float) input('costo', 0) ?: null;
            $orden_c     = trim((string) input('numero_orden', '')) ?: null;
            $mant = db_one("SELECT * FROM flotilla_mant_historial WHERE id = :id", ['id' => $mid]);
            if ($mant) {
                if ($fecha_fin_c < $mant['fecha']) $fecha_fin_c = $mant['fecha'];
                $factura_url = $mant['archivo_url'];
                $rec = flotilla_guardar_recibo($_FILES['factura'] ?? []);
                if ($rec['error']) { $errores[] = $rec['error']; }
                elseif ($rec['ruta']) { $factura_url = $rec['ruta']; }
                $fotos_c = flotilla_mant_guardar_fotos();
                if ($fotos_c['error']) $errores[] = $fotos_c['error'];
                if (empty($errores)) {
                    $costo_final = $costo_c !== null ? $costo_c : ($mant['costo'] !== null ? (float) $mant['costo'] : null);
                    $set_fc = ''; $pfc = [];
                    if (!empty($fotos_c['antes'])   && db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_antes_url'"))   { $set_fc .= ', foto_antes_url = :fa';   $pfc['fa'] = $fotos_c['antes']; }
                    if (!empty($fotos_c['despues']) && db_one("SHOW COLUMNS FROM flotilla_mant_historial LIKE 'foto_despues_url'")) { $set_fc .= ', foto_despues_url = :fd'; $pfc['fd'] = $fotos_c['despues']; }
                    db_exec("UPDATE flotilla_mant_historial SET fecha_fin = :ff, costo = :c, numero_orden = COALESCE(:no, numero_orden), archivo_url = :au{$set_fc} WHERE id = :id",
                        array_merge(['ff' => $fecha_fin_c, 'c' => $costo_final, 'no' => $orden_c, 'au' => $factura_url, 'id' => $mid], $pfc));
                    flotilla_vehiculo_taller((int) $mant['vehiculo_id'], false);
                    flotilla_mant_gasto_sync($mid, (int) $mant['vehiculo_id'], $costo_final, $mant['taller'],
                        $mant['nombre'] . ($mant['taller'] ? " – {$mant['taller']}" : ''), $mant['fecha'],
                        $orden_c ?: $mant['numero_orden'], $mant['km_odometro'] !== null ? (int) $mant['km_odometro'] : null, $u['id']);
                    flash_set('exito', 'Mantenimiento cerrado. El vehículo regresó a "Activo".');
                    header('Location: ' . url('flotilla_mantenimiento.php?vehiculo_id=' . (int) $mant['vehiculo_id']));
                    exit;
                }
            }
        }

        if ($op === 'eliminar' && tiene_permiso('administrar')) {
            $del_id = (int) input('del_id', 0);
            db_exec("DELETE FROM flotilla_mant_historial WHERE id = :id", ['id' => $del_id]);
            flash_set('exito', 'Registro eliminado.');
            header('Location: ' . url("flotilla_mantenimiento.php?vehiculo_id={$f_vehiculo_id}&vista=historial"));
            exit;
        }
    }
}

// ----------------------------------------------------------------------------
// Cargar datos
// ----------------------------------------------------------------------------
$vehiculos = db_all(
    "SELECT v.id, v.alias, v.marca, v.modelo, v.placas, v.km_actual
       FROM flotilla_vehiculos v
      WHERE v.activo = 1"
    . ($f_sucursal ? " AND v.sucursal_id = {$f_sucursal}" : '')
    . " ORDER BY v.alias, v.placas"
);

$programas = db_all("SELECT * FROM flotilla_mant_programas WHERE activo=1 ORDER BY nombre");

// KPIs globales
$kpi_vencidos = (int)(db_one(
    "SELECT COUNT(DISTINCT h.vehiculo_id) n
       FROM flotilla_mant_historial h
      WHERE h.proxima_fecha IS NOT NULL AND h.proxima_fecha < CURDATE()"
    . ($f_vehiculo_id ? " AND h.vehiculo_id = {$f_vehiculo_id}" : '')
)['n'] ?? 0);

$kpi_proximos = (int)(db_one(
    "SELECT COUNT(*) n
       FROM flotilla_mant_historial h
      WHERE h.proxima_fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
    . ($f_vehiculo_id ? " AND h.vehiculo_id = {$f_vehiculo_id}" : '')
)['n'] ?? 0);

$kpi_historial = (int)(db_one(
    "SELECT COUNT(*) n FROM flotilla_mant_historial h WHERE YEAR(h.fecha) = YEAR(CURDATE())"
    . ($f_vehiculo_id ? " AND h.vehiculo_id = {$f_vehiculo_id}" : '')
)['n'] ?? 0);

$kpi_costo_anio = (float)(db_one(
    "SELECT COALESCE(SUM(h.costo),0) n FROM flotilla_mant_historial h WHERE YEAR(h.fecha) = YEAR(CURDATE())"
    . ($f_vehiculo_id ? " AND h.vehiculo_id = {$f_vehiculo_id}" : '')
)['n'] ?? 0);

// Vehículos con mantenimiento pendiente (por fecha o km)
$pendientes = db_all(
    "SELECT h.id, h.nombre, h.proxima_fecha, h.proximo_km, h.fecha ultima_fecha,
            v.id vehiculo_id, v.alias, v.marca, v.modelo, v.placas, v.km_actual,
            DATEDIFF(h.proxima_fecha, CURDATE()) dias_restantes,
            (h.proximo_km - v.km_actual) km_restantes
       FROM flotilla_mant_historial h
       INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
      WHERE v.activo = 1
        AND (h.proxima_fecha IS NOT NULL OR h.proximo_km IS NOT NULL)
        AND h.id = (
            SELECT h2.id FROM flotilla_mant_historial h2
             WHERE h2.vehiculo_id = h.vehiculo_id AND h2.nombre = h.nombre
             ORDER BY h2.fecha DESC, h2.id DESC LIMIT 1
        )"
    . ($f_vehiculo_id ? " AND h.vehiculo_id = {$f_vehiculo_id}" : '')
    . ($f_sucursal ? " AND v.sucursal_id = {$f_sucursal}" : '')
    . " ORDER BY h.proxima_fecha ASC, km_restantes ASC
       LIMIT 100"
);

// Historial
$where_h  = ['1=1'];
$params_h = [];
if ($f_vehiculo_id) {
    $where_h[]        = 'h.vehiculo_id = :vid';
    $params_h['vid']  = $f_vehiculo_id;
}
if ($f_sucursal) {
    $where_h[]        = 'v.sucursal_id = :sid';
    $params_h['sid']  = $f_sucursal;
}
if ($f_desde) {
    $where_h[]          = 'DATE(h.fecha) >= :desde';
    $params_h['desde']  = $f_desde;
}
if ($f_hasta) {
    $where_h[]          = 'DATE(h.fecha) <= :hasta';
    $params_h['hasta']  = $f_hasta;
}
$sql_where_h = implode(' AND ', $where_h);

$historial = db_all(
    "SELECT h.*, v.alias, v.marca, v.modelo, v.placas,
            p.nombre programa_nombre
       FROM flotilla_mant_historial h
       INNER JOIN flotilla_vehiculos v ON h.vehiculo_id = v.id
       LEFT  JOIN flotilla_mant_programas p ON h.programa_id = p.id
      WHERE $sql_where_h
      ORDER BY h.fecha DESC, h.id DESC
      LIMIT 200",
    $params_h
);

$titulo_pagina = 'Flotilla · Mantenimiento';
$mant_abiertos = flotilla_mant_abiertos(); // siempre todos los abiertos (panorama global)
$pagina_activa = 'flotilla_mantenimiento';
require_once __DIR__ . '/config/header.php';
require_once __DIR__ . '/config/flotilla_nav.php';
?>

<div class="animate-fade-in space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-display text-2xl font-extrabold text-zinc-900 flex items-center gap-2">
            <i data-lucide="wrench" class="w-6 h-6 text-bacal-700"></i>
            Mantenimiento
        </h2>
        <?php if ($puede_gestionar): ?>
        <button onclick="document.getElementById('modal-nuevo-mant').classList.remove('hidden')"
                class="px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> Registrar mantenimiento
        </button>
        <?php endif; ?>
    </div>

    <!-- Flash / Errores -->
    <?php foreach (flash_get() as $tipo => $msg): ?>
    <div class="px-4 py-3 rounded-lg text-sm font-medium
        <?= $tipo === 'exito' ? 'bg-emerald-50 border border-emerald-300 text-emerald-800' : 'bg-red-50 border border-red-300 text-red-800' ?>">
        <?= e($msg) ?>
    </div>
    <?php endforeach; ?>
    <?php if ($errores): ?>
    <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $err): ?><div>✗ <?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php
        $kpis_data = [
            ['Vencidos',       $kpi_vencidos,                                     'alert-triangle',   'red'],
            ['Próx. 30 días',  $kpi_proximos,                                     'clock',            'amber'],
            ['Este año',       $kpi_historial,                                    'clipboard-list',   'blue'],
            ['Costo (año)',    '$' . number_format($kpi_costo_anio, 2),           'banknote',         'emerald'],
        ];
        foreach ($kpis_data as [$label, $val, $icon, $color]):
            $alert = $color === 'red' && (int)$val > 0;
        ?>
        <div class="bg-white rounded-xl border <?= $alert ? 'border-red-200 bg-red-50' : 'border-zinc-200' ?> p-4">
            <div class="flex items-center justify-between mb-2">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
                <span class="font-display text-xl font-extrabold <?= $alert ? 'text-red-700' : 'text-zinc-900' ?>"><?= $val ?></span>
            </div>
            <div class="text-[11px] uppercase tracking-wide font-bold text-zinc-500"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros + toggle vista -->
    <div class="bg-white rounded-xl border border-zinc-200 p-3 flex flex-wrap gap-2 items-end justify-between">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <input type="hidden" name="vista" value="<?= e($f_vista) ?>">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-bold text-zinc-500 mb-1">Vehículo</label>
                <select name="vehiculo_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
                    <option value="">Todos los vehículos</option>
                    <?php foreach ($vehiculos as $vv): ?>
                    <option value="<?= $vv['id'] ?>" <?= $f_vehiculo_id === (int)$vv['id'] ? 'selected' : '' ?>>
                        <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($f_vista === 'historial'): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-500 mb-1">Desde</label>
                <input type="date" name="desde" value="<?= e($f_desde) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-500 mb-1">Hasta</label>
                <input type="date" name="hasta" value="<?= e($f_hasta) ?>"
                       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800 self-end">Filtrar</button>
            <?php if ($f_desde || $f_hasta): ?>
            <a href="<?= url('flotilla_mantenimiento.php?' . http_build_query(array_filter(['vista'=>'historial','vehiculo_id'=>$f_vehiculo_id]))) ?>"
               class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-600 hover:bg-zinc-50 self-end">Limpiar</a>
            <?php endif; ?>
            <?php endif; ?>
        </form>

        <!-- Toggle Pendientes / Historial -->
        <div class="inline-flex rounded-lg border border-zinc-300 bg-white p-0.5 shadow-sm self-end">
            <?php
            $vistas = ['pendientes' => ['clock', 'Pendientes'], 'historial' => ['history', 'Historial']];
            foreach ($vistas as $vk => [$vico, $vlabel]):
                $qs = ['vista' => $vk];
                if ($f_vehiculo_id) $qs['vehiculo_id'] = $f_vehiculo_id;
            ?>
            <a href="<?= url('flotilla_mantenimiento.php?' . http_build_query($qs)) ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors
                      <?= $f_vista === $vk ? 'bg-bacal-700 text-white' : 'text-zinc-600 hover:bg-zinc-100' ?>">
                <i data-lucide="<?= $vico ?>" class="w-4 h-4"></i> <?= $vlabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- En taller (mantenimientos abiertos) -->
    <?php if (!empty($mant_abiertos)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <h3 class="font-display font-bold text-amber-800 flex items-center gap-2 mb-3">
            <i data-lucide="wrench" class="w-4 h-4"></i> En taller — <?= count($mant_abiertos) ?> mantenimiento(s) abierto(s)
        </h3>
        <div class="space-y-2">
            <?php foreach ($mant_abiertos as $ma): ?>
            <div class="bg-white rounded-lg border border-amber-200 px-4 py-2.5 flex items-center gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-zinc-900 text-sm truncate">
                        <?= $ma['alias'] ? e($ma['alias']) . ' · ' : '' ?><?= e($ma['placas']) ?> — <?= e($ma['nombre']) ?>
                    </div>
                    <div class="text-xs text-zinc-500">
                        Inicio: <?= e(fmt_fecha($ma['fecha'], false)) ?><?= $ma['taller'] ? ' · ' . e($ma['taller']) : '' ?>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $ma['dias_taller'] >= 7 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' ?>">
                    <?= (int) $ma['dias_taller'] ?> día<?= (int) $ma['dias_taller'] === 1 ? '' : 's' ?> en taller
                </span>
                <?php if ($puede_gestionar): ?>
                <button type="button" onclick="abrirEditarMant(<?= (int) $ma['id'] ?>)"
                        class="px-3 py-1.5 rounded-lg border border-zinc-300 bg-white text-zinc-700 text-xs font-semibold hover:bg-zinc-50">Editar</button>
                <button type="button"
                        onclick='abrirCerrarMant(<?= (int) $ma['id'] ?>, <?= json_encode($ma['fecha']) ?>, <?= json_encode((string) ($ma['costo'] ?? '')) ?>, <?= json_encode($ma['nombre']) ?>)'
                        class="px-3 py-1.5 rounded-lg bg-bacal-700 text-white text-xs font-semibold hover:bg-bacal-800">Cerrar</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($f_vista === 'pendientes'): ?>
    <!-- ── Vista: Pendientes ── -->
    <?php if (empty($pendientes)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="check-circle" class="w-12 h-12 mx-auto text-emerald-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin mantenimientos pendientes</p>
        <p class="text-sm text-zinc-500 mt-1">Todos los vehículos están al día.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($pendientes as $p):
            $dias = $p['proxima_fecha'] ? (int)$p['dias_restantes'] : null;
            $kms  = $p['proximo_km']   ? (int)$p['km_restantes']   : null;
            $vencido_fecha = $dias !== null && $dias < 0;
            $vencido_km    = $kms !== null && $kms < 0;
            $urgente       = $vencido_fecha || $vencido_km || ($dias !== null && $dias <= 7) || ($kms !== null && $kms <= 500);
            $pronto        = !$urgente && (($dias !== null && $dias <= 30) || ($kms !== null && $kms <= 2000));
            $color = $urgente ? 'red' : ($pronto ? 'amber' : 'zinc');
        ?>
        <div class="bg-white rounded-xl border border-<?= $color ?>-<?= $urgente ? '300' : '200' ?> shadow-sm p-4 space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold text-zinc-900"><?= e($p['nombre']) ?></div>
                    <div class="text-xs text-zinc-500 mt-0.5 font-mono">
                        <?= $p['alias'] ? e($p['alias']) . ' · ' : '' ?><?= e($p['placas']) ?>
                    </div>
                </div>
                <?php if ($urgente): ?>
                <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i> Vencido
                </span>
                <?php elseif ($pronto): ?>
                <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
                    <i data-lucide="clock" class="w-3 h-3"></i> Próximo
                </span>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap gap-3 text-xs">
                <?php if ($p['proxima_fecha']): ?>
                <div class="flex items-center gap-1 <?= $vencido_fecha ? 'text-red-600 font-semibold' : 'text-zinc-600' ?>">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <?= fmt_fecha($p['proxima_fecha']) ?>
                    <?php if ($dias !== null): ?>
                    <span class="ml-1 <?= $vencido_fecha ? 'text-red-600' : 'text-zinc-400' ?>">
                        (<?= $dias < 0 ? abs($dias) . ' días atrás' : "en {$dias} días" ?>)
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($p['proximo_km']): ?>
                <div class="flex items-center gap-1 <?= $vencido_km ? 'text-red-600 font-semibold' : 'text-zinc-600' ?>">
                    <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                    <?= number_format($p['proximo_km']) ?> km
                    <?php if ($kms !== null): ?>
                    <span class="ml-1 <?= $vencido_km ? 'text-red-600' : 'text-zinc-400' ?>">
                        (<?= $kms < 0 ? number_format(abs($kms)) . ' km excedido' : number_format($kms) . ' km restantes' ?>)
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="text-xs text-zinc-400">
                Último: <?= $p['ultima_fecha'] ? fmt_fecha($p['ultima_fecha']) : '—' ?> ·
                Km actual: <span class="font-mono"><?= number_format($p['km_actual']) ?></span>
            </div>

            <?php if ($puede_gestionar): ?>
            <button onclick="abrirModalMant(<?= $p['vehiculo_id'] ?>, '<?= e(addslashes($p['nombre'])) ?>')"
                    class="w-full px-3 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-xs font-semibold flex items-center justify-center gap-1.5">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> Registrar como realizado
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ── Vista: Historial ── -->
    <?php if (empty($historial)): ?>
    <div class="bg-white rounded-xl border border-zinc-200 py-16 text-center">
        <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto text-zinc-300 mb-3"></i>
        <p class="font-semibold text-zinc-700">Sin historial de mantenimientos</p>
        <p class="text-sm text-zinc-500 mt-1">Registra el primer mantenimiento para empezar el seguimiento.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm js-tabla-orden">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="fecha">Fecha</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Servicio</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Vehículo</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden md:table-cell" data-orden-tipo="num">Km</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Taller</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide" data-orden-tipo="num">Costo</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Próximo</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-zinc-500 uppercase tracking-wide">Adjuntos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <?php foreach ($historial as $h): ?>
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-700"><?= fmt_fecha($h['fecha']) ?></td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900"><?= e($h['nombre']) ?></div>
                            <?php if ($h['programa_nombre']): ?>
                            <div class="text-xs text-zinc-400"><?= e($h['programa_nombre']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-zinc-900">
                                <?= $h['alias'] ? e($h['alias']) . ' · ' : '' ?><?= e($h['marca']) ?> <?= e($h['modelo']) ?>
                            </div>
                            <div class="text-xs text-zinc-500 font-mono"><?= e($h['placas']) ?></div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-right font-mono text-zinc-600">
                            <?= number_format($h['km_odometro']) ?>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-600"><?= $h['taller'] ? e($h['taller']) : '—' ?></td>
                        <td class="px-4 py-3 text-right font-semibold text-zinc-900">
                            <?= $h['costo'] ? '$' . number_format((float)$h['costo'], 2) : '—' ?>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-xs">
                            <?php if ($h['proxima_fecha'] || $h['proximo_km']): ?>
                            <div class="text-zinc-600">
                                <?= $h['proxima_fecha'] ? fmt_fecha($h['proxima_fecha']) : '' ?>
                                <?= $h['proximo_km'] ? ' · ' . number_format($h['proximo_km']) . ' km' : '' ?>
                            </div>
                            <?php else: ?>
                            <span class="text-zinc-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $h_fa  = $h['foto_antes_url']   ?? null;
                                $h_fd  = $h['foto_despues_url'] ?? null;
                                $h_fac = $h['archivo_url']      ?? null;
                            ?>
                            <div class="flex items-center gap-1.5">
                                <?php if ($h_fa): ?>
                                <a href="<?= url('assets/' . $h_fa) ?>" target="_blank" title="Foto antes">
                                    <img src="<?= url('assets/' . $h_fa) ?>" class="w-8 h-8 rounded object-cover border border-zinc-200 hover:ring-2 hover:ring-bacal-300">
                                </a>
                                <?php endif; ?>
                                <?php if ($h_fd): ?>
                                <a href="<?= url('assets/' . $h_fd) ?>" target="_blank" title="Foto después">
                                    <img src="<?= url('assets/' . $h_fd) ?>" class="w-8 h-8 rounded object-cover border border-zinc-200 hover:ring-2 hover:ring-bacal-300">
                                </a>
                                <?php endif; ?>
                                <?php if ($h_fac): ?>
                                <a href="<?= url('assets/' . $h_fac) ?>" target="_blank" title="Factura / recibo"
                                   class="w-8 h-8 rounded border border-zinc-200 flex items-center justify-center text-bacal-700 hover:bg-bacal-50">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (!$h_fa && !$h_fd && !$h_fac): ?><span class="text-zinc-300">—</span><?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <?php if ($puede_gestionar): ?>
                            <button type="button" onclick="abrirEditarMant(<?= (int) $h['id'] ?>)"
                                    class="p-1.5 rounded hover:bg-bacal-50 text-zinc-400 hover:text-bacal-700" title="Editar / adjuntar factura">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este registro?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="eliminar">
                                <input type="hidden" name="del_id" value="<?= $h['id'] ?>">
                                <button type="submit" class="p-1.5 rounded hover:bg-red-50 text-zinc-400 hover:text-red-600">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- Modal: Registrar mantenimiento                               -->
<!-- ============================================================ -->
<div id="modal-nuevo-mant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4 text-bacal-700"></i>
                Registrar mantenimiento
            </h3>
            <button type="button" onclick="document.getElementById('modal-nuevo-mant').classList.add('hidden')"
                    class="text-zinc-400 hover:text-zinc-600 p-1 rounded">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="registrar">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Vehículo <span class="text-red-500">*</span></label>
                    <select name="vehiculo_id" id="mant_vehiculo" required
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Seleccionar vehículo…</option>
                        <?php foreach ($vehiculos as $vv): ?>
                        <option value="<?= $vv['id'] ?>" <?= $f_vehiculo_id === (int)$vv['id'] ? 'selected' : '' ?>
                                data-km="<?= $vv['km_actual'] ?>">
                            <?= $vv['alias'] ? e($vv['alias']) . ' – ' : '' ?><?= e($vv['placas']) ?>
                            (<?= e($vv['marca']) ?> <?= e($vv['modelo']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Tipo de servicio</label>
                    <select name="programa_id" id="mant_programa"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">Otro / libre</option>
                        <?php foreach ($programas as $pg): ?>
                        <option value="<?= $pg['id'] ?>" data-nombre="<?= e($pg['nombre']) ?>">
                            <?= e($pg['nombre']) ?>
                            <?= $pg['intervalo_km'] ? ' (c/' . number_format($pg['intervalo_km']) . ' km)' : '' ?>
                            <?= $pg['intervalo_dias'] ? ' / ' . $pg['intervalo_dias'] . ' días' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Nombre del servicio <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" id="mant_nombre" required maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"
                           placeholder="Ej: Cambio de aceite, revisión de frenos…">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de inicio <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de fin <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="date" name="fecha_fin"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    <p class="text-[11px] text-zinc-400 mt-0.5">Vacío = sigue en taller (queda abierto)</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="number" name="km_odometro" id="mant_km" min="0"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Taller / Proveedor</label>
                    <input type="text" name="taller" maxlength="100" list="lista-proveedores" autocomplete="off"
                           placeholder="Elige uno o escribe nuevo…"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    <datalist id="lista-proveedores">
                        <?php foreach (flotilla_proveedores_lista() as $pv): ?>
                        <option value="<?= e($pv['nombre']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Técnico</label>
                    <input type="text" name="tecnico" maxlength="100"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Costo</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="costo" min="0" step="0.01"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500"
                               placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">No. orden de servicio</label>
                    <input type="text" name="numero_orden" maxlength="60"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Factura / recibo <span class="text-zinc-400 font-normal normal-case">(imagen o PDF)</span></label>
                    <input type="file" name="factura" accept="image/*,application/pdf"
                           class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>
                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Foto antes <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                        <input type="file" name="foto_antes" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1">Foto después <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                        <input type="file" name="foto_despues" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                    </div>
                </div>

                <div class="sm:col-span-2 border-t border-zinc-100 pt-3">
                    <p class="text-xs font-bold text-zinc-500 uppercase tracking-wide mb-3">Programar próximo mantenimiento</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 mb-1">Próxima fecha</label>
                            <input type="date" name="proxima_fecha"
                                   class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 mb-1">Próximo km</label>
                            <input type="number" name="proximo_km" id="mant_prox_km" min="0"
                                   class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Descripción / notas</label>
                    <textarea name="notas" rows="2"
                              class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"
                              placeholder="Observaciones, piezas reemplazadas, etc."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-nuevo-mant').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-rellenar nombre al seleccionar tipo de programa
document.getElementById('mant_programa')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var nom = document.getElementById('mant_nombre');
    if (nom && opt.dataset.nombre) nom.value = opt.dataset.nombre;
});

// Auto-rellenar km al seleccionar vehículo
document.getElementById('mant_vehiculo')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var km  = opt.dataset.km;
    var kmInput = document.getElementById('mant_km');
    if (kmInput && km && !kmInput.value) kmInput.value = km;
});

// Abrir modal con vehículo y nombre pre-cargado (desde tarjetas de pendientes)
function abrirModalMant(vid, nombre) {
    var sel = document.getElementById('mant_vehiculo');
    if (sel) sel.value = vid;
    var nm  = document.getElementById('mant_nombre');
    if (nm)  nm.value  = nombre;
    // Trigger change para km
    var opt = sel?.options[sel?.selectedIndex];
    var km  = opt?.dataset?.km;
    var kmInput = document.getElementById('mant_km');
    if (kmInput && km) kmInput.value = km;
    document.getElementById('modal-nuevo-mant').classList.remove('hidden');
}
</script>

<?php if ($puede_gestionar): ?>
<div id="modal-cerrar-mant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-cerrar-mant').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="border-b border-zinc-200 px-6 py-4 flex items-center justify-between">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-bacal-700"></i> Cerrar mantenimiento
            </h3>
            <button type="button" onclick="document.getElementById('modal-cerrar-mant').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="cerrar">
            <input type="hidden" name="mant_id" id="cerrar_mant_id">
            <p class="text-sm font-semibold text-zinc-700" id="cerrar_mant_nombre"></p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de fin <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha_fin" id="cerrar_fecha_fin" required value="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Costo final</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="costo" id="cerrar_costo" min="0" step="0.01"
                               class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">No. orden / factura</label>
                <input type="text" name="numero_orden" maxlength="60"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Factura / recibo <span class="text-zinc-400 font-normal normal-case">(imagen o PDF)</span></label>
                <input type="file" name="factura" accept="image/*,application/pdf"
                       class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Foto antes <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="file" name="foto_antes" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Foto después <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="file" name="foto_despues" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>
            </div>
            <p class="text-xs text-zinc-400">Al cerrar, el vehículo regresa a "Activo".</p>
            <div class="flex justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-cerrar-mant').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Cerrar mantenimiento</button>
            </div>
        </form>
    </div>
</div>
<script>
function abrirCerrarMant(id, fechaInicio, costo, nombre) {
    document.getElementById('cerrar_mant_id').value = id;
    document.getElementById('cerrar_mant_nombre').textContent = nombre || '';
    var ff = document.getElementById('cerrar_fecha_fin');
    if (fechaInicio && ff.value < fechaInicio) ff.value = fechaInicio;
    document.getElementById('cerrar_costo').value = costo || '';
    document.getElementById('modal-cerrar-mant').classList.remove('hidden');
}
</script>
<?php endif; ?>

<?php if ($puede_gestionar): ?>
<div id="modal-editar-mant" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('modal-editar-mant').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-zinc-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h3 class="font-display text-base font-bold text-zinc-900 flex items-center gap-2">
                <i data-lucide="pencil" class="w-4 h-4 text-bacal-700"></i> Editar mantenimiento
            </h3>
            <button type="button" onclick="document.getElementById('modal-editar-mant').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="form-mant-editar" class="p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="mant_editar">
            <input type="hidden" name="mant_id">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Nombre del servicio <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" required maxlength="100" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Fecha de inicio</label>
                    <input type="date" name="fecha" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Km odómetro <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="number" name="km_odometro" min="0" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Taller / Proveedor</label>
                    <input type="text" name="taller" maxlength="100" list="lista-proveedores" autocomplete="off" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Técnico</label>
                    <input type="text" name="tecnico" maxlength="100" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Costo</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">$</span>
                        <input type="number" name="costo" min="0" step="0.01" class="w-full pl-6 pr-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">No. orden</label>
                    <input type="text" name="numero_orden" maxlength="60" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Descripción</label>
                <textarea name="descripcion" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Notas</label>
                <textarea name="notas" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1">Factura / recibo <span class="text-zinc-400 font-normal normal-case">(opcional, reemplaza la actual)</span></label>
                <input type="file" name="factura" accept="image/*,application/pdf" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Foto antes <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="file" name="foto_antes" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1">Foto después <span class="text-zinc-400 font-normal normal-case">(opcional)</span></label>
                    <input type="file" name="foto_despues" accept="image/*" class="w-full text-sm text-zinc-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-bacal-50 file:text-bacal-700 file:text-xs file:font-semibold hover:file:bg-bacal-100">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2 border-t border-zinc-100">
                <button type="button" onclick="document.getElementById('modal-editar-mant').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-zinc-300 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 text-white text-sm font-semibold hover:bg-bacal-800">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
<script>
window.mantsAb = {
<?php
    // Todos los registros editables desde esta página (abiertos + historial mostrado).
    $editables_js = [];
    foreach (array_merge($mant_abiertos, $historial) as $ma) { $editables_js[(int) $ma['id']] = $ma; }
    foreach ($editables_js as $ma):
?>
    <?= (int) $ma['id'] ?>: <?= json_encode([
        'nombre' => $ma['nombre'],
        'fecha'  => $ma['fecha'],
        'km'     => $ma['km_odometro'],
        'taller' => (string) ($ma['taller'] ?? ''),
        'tecnico'=> (string) ($ma['tecnico'] ?? ''),
        'costo'  => ($ma['costo'] !== null ? (string) $ma['costo'] : ''),
        'orden'  => (string) ($ma['numero_orden'] ?? ''),
        'desc'   => (string) ($ma['descripcion'] ?? ''),
        'notas'  => (string) ($ma['notas'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
<?php endforeach; ?>
};
function abrirEditarMant(id){
    var d = window.mantsAb[id]; if(!d) return;
    var f = document.getElementById('form-mant-editar');
    f.mant_id.value = id;
    f.nombre.value = d.nombre || '';
    f.fecha.value = d.fecha || '';
    f.km_odometro.value = d.km || '';
    f.taller.value = d.taller || '';
    f.tecnico.value = d.tecnico || '';
    f.costo.value = d.costo || '';
    f.numero_orden.value = d.orden || '';
    f.descripcion.value = d.desc || '';
    f.notas.value = d.notas || '';
    if (f.foto_antes)   f.foto_antes.value = '';
    if (f.foto_despues) f.foto_despues.value = '';
    if (f.factura)      f.factura.value = '';
    document.getElementById('modal-editar-mant').classList.remove('hidden');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/config/footer.php'; ?>
