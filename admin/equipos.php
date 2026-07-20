<?php
/**
 * ============================================================================
 * admin/equipos.php - Gestión de equipos/activos
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/incidencia_costos_helpers.php';

$accion = (string) input('accion', 'listar');
$id     = (int) input('id', 0);

$equipo_edit = null;
if (in_array($accion, ['editar', 'toggle'], true) && $id > 0) {
    $equipo_edit = db_one("SELECT * FROM equipos WHERE id = :id", ['id' => $id]);
    if (!$equipo_edit) {
        flash_set('error', 'Equipo no encontrado.');
        header('Location: ' . url('admin/equipos.php'));
        exit;
    }
}

$errores = [];

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');
        try {
            if ($op === 'crear' || $op === 'editar') {
                $codigo = strtoupper(trim((string) input('codigo_inventario', '')));
                $nombre = trim((string) input('nombre', ''));
                $tipo   = trim((string) input('tipo', ''));
                $marca  = trim((string) input('marca', ''));
                $modelo = trim((string) input('modelo', ''));
                $serie  = trim((string) input('numero_serie', ''));
                $sid    = (int) input('sucursal_id', 0);
                $aid    = input('area_id', '') !== '' ? (int) input('area_id') : null;
                $ubic   = trim((string) input('ubicacion', ''));
                $notas  = trim((string) input('notas', ''));
                $proveedor_id = input('proveedor_id', '') !== '' ? (int) input('proveedor_id') : null;

                // Alta rápida de proveedor desde el propio formulario (solo administradores).
                $prov_nuevo_nombre = trim((string) input('prov_nuevo_nombre', ''));
                if ($prov_nuevo_nombre !== '' && tiene_permiso('administrar')) {
                    $proveedor_id = crear_proveedor_rapido([
                        'nombre'   => $prov_nuevo_nombre,
                        'servicio' => trim((string) input('prov_nuevo_servicio', '')),
                        'telefono' => trim((string) input('prov_nuevo_telefono', '')),
                    ], (int) (usuario_actual()['id'] ?? 0));
                }
                $fecha_compra = trim((string) input('fecha_compra', '')) ?: null;
                $costo_compra = trim((string) input('costo_compra', '')) !== '' ? (float) input('costo_compra') : null;
                $estado_vida  = (string) input('estado_vida', 'en_uso');
                $vida_util_meses = trim((string) input('vida_util_meses', '')) !== '' ? (int) input('vida_util_meses') : null;
                $fecha_baja  = trim((string) input('fecha_baja', '')) ?: null;
                $motivo_baja = trim((string) input('motivo_baja', '')) ?: null;

                // Validar estado_vida
                if (!in_array($estado_vida, ['nuevo','en_uso','en_reparacion','dado_de_baja'], true)) {
                    $estado_vida = 'en_uso';
                }

                if ($codigo === '') $errores[] = 'El código de inventario es obligatorio.';
                if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
                if ($sid <= 0)      $errores[] = 'La sucursal es obligatoria.';

                $check_id = $op === 'editar' ? (int) $equipo_edit['id'] : 0;
                $dup = db_one("SELECT id FROM equipos WHERE codigo_inventario = :c AND id <> :id",
                    ['c' => $codigo, 'id' => $check_id]);
                if ($dup) $errores[] = 'Ya existe un equipo con ese código de inventario.';

                if (empty($errores)) {
                    if ($op === 'crear') {
                        db_exec(
                            "INSERT INTO equipos
                             (codigo_inventario, nombre, tipo, marca, modelo, numero_serie,
                              sucursal_id, area_id, proveedor_id, fecha_compra, costo_compra,
                              vida_util_meses, estado_vida, fecha_baja, motivo_baja,
                              ubicacion, notas, activo)
                             VALUES (:c, :n, :t, :m, :mo, :ns, :s, :a, :pid, :fc, :cc,
                                     :vum, :ev, :fb, :mb, :u, :no, 1)",
                            ['c' => $codigo, 'n' => $nombre, 't' => $tipo ?: null, 'm' => $marca ?: null,
                             'mo' => $modelo ?: null, 'ns' => $serie ?: null,
                             's' => $sid, 'a' => $aid,
                             'pid' => $proveedor_id, 'fc' => $fecha_compra, 'cc' => $costo_compra,
                             'vum' => $vida_util_meses, 'ev' => $estado_vida,
                             'fb' => $fecha_baja, 'mb' => $motivo_baja,
                             'u' => $ubic ?: null, 'no' => $notas ?: null]
                        );
                        $new_id = db_last_id();
                        registrar_auditoria('crear_equipo', 'equipos', $new_id, "Equipo $codigo");
                        flash_set('success', "Equipo \"$nombre\" creado.");
                    } else {
                        db_exec(
                            "UPDATE equipos SET
                                codigo_inventario=:c, nombre=:n, tipo=:t, marca=:m, modelo=:mo,
                                numero_serie=:ns, sucursal_id=:s, area_id=:a,
                                proveedor_id=:pid, fecha_compra=:fc, costo_compra=:cc,
                                vida_util_meses=:vum, estado_vida=:ev, fecha_baja=:fb, motivo_baja=:mb,
                                ubicacion=:u, notas=:no
                             WHERE id=:id",
                            ['c' => $codigo, 'n' => $nombre, 't' => $tipo ?: null, 'm' => $marca ?: null,
                             'mo' => $modelo ?: null, 'ns' => $serie ?: null,
                             's' => $sid, 'a' => $aid,
                             'pid' => $proveedor_id, 'fc' => $fecha_compra, 'cc' => $costo_compra,
                             'vum' => $vida_util_meses, 'ev' => $estado_vida,
                             'fb' => $fecha_baja, 'mb' => $motivo_baja,
                             'u' => $ubic ?: null, 'no' => $notas ?: null,
                             'id' => $equipo_edit['id']]
                        );
                        registrar_auditoria('editar_equipo', 'equipos', $equipo_edit['id'], "Equipo $codigo");
                        flash_set('success', 'Equipo actualizado.');
                    }
                    header('Location: ' . url('admin/equipos.php'));
                    exit;
                }
            } elseif ($op === 'toggle' && $equipo_edit) {
                admin_toggle_activo('equipos', $equipo_edit['id'], "Equipo {$equipo_edit['codigo_inventario']}");
                header('Location: ' . url('admin/equipos.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

$sucursales = db_all("SELECT id, nombre, codigo FROM sucursales WHERE activo=1 ORDER BY nombre");
$areas      = db_all("SELECT id, nombre FROM areas WHERE activo=1 ORDER BY nombre");
$tipos_existentes = db_all("SELECT DISTINCT tipo FROM equipos WHERE tipo IS NOT NULL AND tipo <> '' ORDER BY tipo");
$proveedores_lista = db_all("SELECT id, nombre, servicio FROM proveedores WHERE activo=1 ORDER BY nombre");

$titulo_pagina = 'Equipos';
$pagina_activa = 'admin_equipos';
require_once __DIR__ . '/../config/header.php';

if ($accion === 'nuevo' || ($accion === 'editar' && $equipo_edit)):
    $es_edicion = ($accion === 'editar');
    $eq = $equipo_edit;
?>
<div class="max-w-3xl mx-auto animate-fade-in">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('admin/equipos.php') ?>" class="p-2 rounded-lg hover:bg-zinc-100 text-zinc-500">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h2 class="font-display text-2xl font-extrabold text-zinc-900"><?= $es_edicion ? 'Editar equipo' : 'Nuevo equipo' ?></h2>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="mb-4 px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs"><?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
        <?= csrf_input() ?>
        <input type="hidden" name="op" value="<?= $es_edicion ? 'editar' : 'crear' ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Código de inventario *</label>
                <input type="text" name="codigo_inventario" required maxlength="50"
                       value="<?= e($es_edicion ? $eq['codigo_inventario'] : (string) input('codigo_inventario', '')) ?>"
                       placeholder="ej. BAC-001"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono uppercase focus:outline-none focus:border-bacal-700"
                       style="text-transform: uppercase;">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre descriptivo *</label>
                <input type="text" name="nombre" required maxlength="150"
                       value="<?= e($es_edicion ? $eq['nombre'] : (string) input('nombre', '')) ?>"
                       placeholder="ej. PC Caja 1 Bacal"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Tipo</label>
                <input type="text" name="tipo" list="tipos-equipo" maxlength="50"
                       value="<?= e($es_edicion ? (string) $eq['tipo'] : (string) input('tipo', '')) ?>"
                       placeholder="ej. PC, Impresora, Cámara IP"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                <datalist id="tipos-equipo">
                    <?php foreach ($tipos_existentes as $t): ?>
                    <option value="<?= e($t['tipo']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Marca</label>
                <input type="text" name="marca" maxlength="100"
                       value="<?= e($es_edicion ? (string) $eq['marca'] : (string) input('marca', '')) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Modelo</label>
                <input type="text" name="modelo" maxlength="100"
                       value="<?= e($es_edicion ? (string) $eq['modelo'] : (string) input('modelo', '')) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Número de serie</label>
                <input type="text" name="numero_serie" maxlength="100"
                       value="<?= e($es_edicion ? (string) $eq['numero_serie'] : (string) input('numero_serie', '')) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm font-mono focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Sucursal *</label>
                <select name="sucursal_id" required class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($sucursales as $s):
                        $sel = $es_edicion ? $eq['sucursal_id'] : (int) input('sucursal_id', 0);
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $sel == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Área (opcional)</label>
                <select name="area_id" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">— Sin área —</option>
                    <?php foreach ($areas as $a):
                        $sel = $es_edicion ? $eq['area_id'] : (string) input('area_id', '');
                    ?>
                    <option value="<?= $a['id'] ?>" <?= (string) $sel === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
                $sel_pr_id    = $es_edicion ? (string) ($eq['proveedor_id'] ?? '') : (string) input('proveedor_id', '');
                $sel_pr_label = '';
                $prov_opts    = [];
                foreach ($proveedores_lista as $pr) {
                    $lbl = $pr['nombre'] . ($pr['servicio'] ? ' — ' . $pr['servicio'] : '');
                    $prov_opts[] = ['id' => (string) $pr['id'], 'label' => $lbl];
                    if ((string) $pr['id'] === $sel_pr_id) $sel_pr_label = $lbl;
                }
                $puede_alta_prov = tiene_permiso('administrar');
                $xdata_prov = '{ abrir:false, nuevo:false, q:"", lista:'
                    . json_encode($prov_opts, JSON_UNESCAPED_UNICODE)
                    . ', sel:' . json_encode(['id' => $sel_pr_id, 'label' => $sel_pr_label], JSON_UNESCAPED_UNICODE)
                    . ', filtrados(){ const q=this.q.toLowerCase().trim(); return q ? this.lista.filter(p=>p.label.toLowerCase().includes(q)) : this.lista; }'
                    . ', elegir(p){ this.sel={id:p.id||"",label:p.label||""}; this.abrir=false; this.q=""; } }';
            ?>
            <div class="md:col-span-2" x-data="<?= htmlspecialchars($xdata_prov, ENT_QUOTES) ?>">
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Proveedor (opcional)</label>
                <input type="hidden" name="proveedor_id" value="<?= e($sel_pr_id) ?>" :value="sel.id">

                <div class="relative">
                    <button type="button" @click="abrir = !abrir"
                            class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm text-left flex items-center justify-between gap-2 focus:outline-none focus:border-bacal-700">
                        <span x-text="sel.id ? sel.label : '— Sin proveedor —'" :class="sel.id ? 'text-zinc-900' : 'text-zinc-400'"></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 shrink-0"></i>
                    </button>

                    <!-- Se despliega hacia ABAJO, altura limitada y con scroll -->
                    <div x-show="abrir" @click.outside="abrir = false" x-cloak
                         class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-zinc-300 rounded-lg shadow-lg overflow-hidden">
                        <div class="p-2 border-b border-zinc-100">
                            <input type="text" x-model="q" placeholder="Buscar proveedor…"
                                   class="w-full px-2 py-1.5 rounded border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                        </div>
                        <ul class="max-h-48 overflow-y-auto py-1">
                            <li>
                                <button type="button" @click="elegir({id:'',label:''})"
                                        class="w-full text-left px-3 py-1.5 text-sm text-zinc-500 hover:bg-zinc-50">— Sin proveedor —</button>
                            </li>
                            <template x-for="p in filtrados()" :key="p.id">
                                <li>
                                    <button type="button" @click="elegir(p)"
                                            class="w-full text-left px-3 py-1.5 text-sm text-zinc-800 hover:bg-zinc-50" x-text="p.label"></button>
                                </li>
                            </template>
                            <li x-show="filtrados().length === 0" class="px-3 py-2 text-xs text-zinc-400">Sin resultados</li>
                        </ul>
                        <?php if ($puede_alta_prov): ?>
                        <div class="border-t border-zinc-100 p-2">
                            <button type="button" @click="nuevo = true; abrir = false"
                                    class="w-full flex items-center gap-1.5 text-xs font-semibold text-bacal-700 hover:underline">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Dar de alta un proveedor nuevo
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($puede_alta_prov): ?>
                <div x-show="nuevo" x-collapse x-cloak class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-amber-800">Nuevo proveedor — se agrega al catálogo al guardar</p>
                        <button type="button" @click="nuevo = false" class="text-[11px] text-zinc-500 hover:underline">Cancelar</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <input type="text" name="prov_nuevo_nombre" maxlength="150" placeholder="Nombre *"
                               value="<?= e((string) input('prov_nuevo_nombre', '')) ?>"
                               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <input type="text" name="prov_nuevo_servicio" maxlength="255" placeholder="Servicio (ej. Refrigeración)"
                               value="<?= e((string) input('prov_nuevo_servicio', '')) ?>"
                               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                        <input type="text" name="prov_nuevo_telefono" maxlength="50" placeholder="Teléfono"
                               value="<?= e((string) input('prov_nuevo_telefono', '')) ?>"
                               class="px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    </div>
                    <p class="text-[10px] text-amber-700 mt-1.5">Si escribes un nombre aquí se usa ese proveedor y se ignora el seleccionado arriba. Si ya existe uno con el mismo nombre, se reutiliza.</p>
                </div>
                <?php endif; ?>

                <p class="text-[10px] text-zinc-500 mt-1">¿Quién nos vendió o da servicio a este equipo?</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha de compra</label>
                <input type="date" name="fecha_compra"
                       value="<?= e($es_edicion && $eq['fecha_compra'] ? $eq['fecha_compra'] : (string) input('fecha_compra', '')) ?>"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Costo de compra (MXN)</label>
                <input type="number" name="costo_compra" step="0.01" min="0"
                       value="<?= e($es_edicion && $eq['costo_compra'] !== null ? $eq['costo_compra'] : (string) input('costo_compra', '')) ?>"
                       placeholder="0.00"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Vida útil estimada</label>
                <select name="vida_util_meses" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php
                    $vum_actual = $es_edicion ? (int) ($eq['vida_util_meses'] ?? 0) : 0;
                    $opciones_vida = [
                        '' => '— Sin especificar —',
                        '12' => '1 año (12 meses)',
                        '24' => '2 años (24 meses)',
                        '36' => '3 años (36 meses)',
                        '48' => '4 años (48 meses)',
                        '60' => '5 años (60 meses)',
                        '72' => '6 años (72 meses)',
                        '84' => '7 años (84 meses)',
                        '120' => '10 años (120 meses)',
                    ];
                    foreach ($opciones_vida as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= (string) $vum_actual === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-zinc-500 mt-1">Para calcular depreciación.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Estado del equipo</label>
                <select name="estado_vida" class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php
                    $ev_actual = $es_edicion ? ($eq['estado_vida'] ?? 'en_uso') : 'en_uso';
                    $opciones_estado = [
                        'nuevo' => '🆕 Nuevo',
                        'en_uso' => '✅ En uso',
                        'en_reparacion' => '🔧 En reparación',
                        'dado_de_baja' => '📦 Dado de baja',
                    ];
                    foreach ($opciones_estado as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= $ev_actual === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>

            <div x-data="{ esBaja: <?= ($es_edicion && ($eq['estado_vida'] ?? '') === 'dado_de_baja') ? 'true' : 'false' ?> }"
                 x-init="$watch('$el.parentElement.querySelector(\'select[name=estado_vida]\').value', v => esBaja = v === 'dado_de_baja')"
                 class="md:col-span-2"
                 @change="if ($event.target.name === 'estado_vida') esBaja = $event.target.value === 'dado_de_baja'">
                <div x-show="esBaja" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-zinc-50 border border-zinc-200 rounded-lg">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Fecha de baja</label>
                        <input type="date" name="fecha_baja"
                               value="<?= e($es_edicion && $eq['fecha_baja'] ? $eq['fecha_baja'] : '') ?>"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Motivo de baja</label>
                        <input type="text" name="motivo_baja" maxlength="255"
                               value="<?= e($es_edicion ? (string) ($eq['motivo_baja'] ?? '') : '') ?>"
                               placeholder="ej. Equipo obsoleto, daño irreparable, robo"
                               class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Ubicación física</label>
                <input type="text" name="ubicacion" maxlength="255"
                       value="<?= e($es_edicion ? (string) $eq['ubicacion'] : (string) input('ubicacion', '')) ?>"
                       placeholder="ej. Planta baja, sala de cajas, posición 1"
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Notas</label>
                <textarea name="notas" rows="2"
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700"><?= e($es_edicion ? (string) $eq['notas'] : (string) input('notas', '')) ?></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-zinc-100">
            <a href="<?= url('admin/equipos.php') ?>" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm">Cancelar</a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                <?= $es_edicion ? 'Guardar' : 'Crear equipo' ?>
            </button>
        </div>
    </form>
</div>

<?php else:
    // Filtros
    $f_sucursal = (int) input('sucursal', 0);
    $f_tipo     = trim((string) input('tipo', ''));
    $f_area     = (int) input('area', 0);
    $f_estado   = trim((string) input('estado', ''));
    $f_activo   = trim((string) input('activo', ''));
    $f_q        = trim((string) input('q', ''));

    $where = [];
    $params = [];
    if ($f_sucursal > 0) { $where[] = "e.sucursal_id = :sid"; $params['sid'] = $f_sucursal; }
    if ($f_tipo !== '')  { $where[] = "e.tipo = :t"; $params['t'] = $f_tipo; }
    if ($f_area > 0)     { $where[] = "e.area_id = :aid"; $params['aid'] = $f_area; }
    if (in_array($f_estado, ['nuevo','en_uso','en_reparacion','dado_de_baja'], true)) { $where[] = "e.estado_vida = :ev"; $params['ev'] = $f_estado; }
    if ($f_activo === '1' || $f_activo === '0') { $where[] = "e.activo = :act"; $params['act'] = (int) $f_activo; }
    if ($f_q !== '')     {
        $where[] = "(e.codigo_inventario LIKE :q1 OR e.nombre LIKE :q2 OR e.marca LIKE :q3 OR e.modelo LIKE :q4)";
        $params['q1'] = "%$f_q%"; $params['q2'] = "%$f_q%"; $params['q3'] = "%$f_q%"; $params['q4'] = "%$f_q%";
    }
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $equipos = db_all(
        "SELECT e.*, s.nombre sucursal_nombre, s.codigo sucursal_codigo, a.nombre area_nombre,
                pr.nombre proveedor_nombre,
                (SELECT COUNT(*) FROM incidencias WHERE equipo_id = e.id) AS incidencias_count,
                (SELECT COUNT(*) FROM equipo_componentes WHERE equipo_id = e.id) AS componentes_count
         FROM equipos e
         INNER JOIN sucursales s ON e.sucursal_id = s.id
         LEFT JOIN areas a ON e.area_id = a.id
         LEFT JOIN proveedores pr ON e.proveedor_id = pr.id
         $where_sql
         ORDER BY e.activo DESC, e.codigo_inventario ASC",
        $params
    );
?>

<?php render_admin_header('Equipos / activos', count($equipos) . ' equipo(s) en inventario', url('admin/equipos.php?accion=nuevo'), 'Nuevo equipo'); ?>

<!-- Selector rápido de sucursal (radial — usuarios con preferencia sucursal_selector=radio) -->
<?php if (tiene_permiso('ver_todas_sucursales') && usuario_prefiere_radio_sucursal()): ?>
<form method="GET" class="mb-4 inline-flex max-w-full items-center gap-x-4 gap-y-2 flex-wrap bg-white border border-zinc-300 rounded-lg px-3 py-2">
    <?php
    // Preservar los demás filtros al cambiar de sucursal
    foreach ($_GET as $k => $v) {
        if ($k === 'sucursal' || $k === 'p') continue;
        if ($v !== '' && $v !== '0') {
            echo '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">';
        }
    }
    ?>
    <span class="text-xs font-bold text-zinc-500 uppercase tracking-wide">Sucursal:</span>
    <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
        <input type="radio" name="sucursal" value="" onchange="this.form.submit()"
               <?= $f_sucursal <= 0 ? 'checked' : '' ?>
               class="text-bacal-700 focus:ring-bacal-700">
        Todas
    </label>
    <?php foreach ($sucursales as $s): ?>
    <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 cursor-pointer">
        <input type="radio" name="sucursal" value="<?= $s['id'] ?>" onchange="this.form.submit()"
               <?= $f_sucursal == $s['id'] ? 'checked' : '' ?>
               class="text-bacal-700 focus:ring-bacal-700">
        <?= e($s['nombre']) ?>
    </label>
    <?php endforeach; ?>
</form>
<?php endif; ?>

<!-- Filtros -->
<?php $usa_radio_suc = tiene_permiso('ver_todas_sucursales') && usuario_prefiere_radio_sucursal(); ?>
<?php $adv_count = ((!$usa_radio_suc && $f_sucursal>0)?1:0)+($f_tipo!==''?1:0)+($f_area>0?1:0)+($f_estado!==''?1:0)+($f_activo!==''?1:0); ?>
<form method="GET" class="mb-4" x-data="{ abierto: <?= $adv_count > 0 ? 'true' : 'false' ?> }">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"></i>
            <input type="text" name="q" value="<?= e($f_q) ?>" placeholder="Código, nombre, marca, modelo..."
                   class="w-full pl-9 pr-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
        </div>
        <button type="button" @click="abierto = !abierto"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-colors <?= $adv_count > 0 ? 'border-bacal-300 bg-bacal-50 text-bacal-700' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' ?>">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filtros
            <?php if ($adv_count > 0): ?><span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-bacal-700 text-white text-[10px] font-bold"><?= $adv_count ?></span><?php endif; ?>
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="abierto ? 'rotate-180' : ''"></i>
        </button>
        <?php if ($f_q !== '' || $adv_count > 0): ?>
        <a href="<?= url('admin/equipos.php') ?>" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm hover:bg-zinc-50">
            <i data-lucide="x" class="w-3.5 h-3.5"></i> Limpiar
        </a>
        <?php endif; ?>
    </div>

    <div x-show="abierto" x-collapse x-cloak class="mt-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 bg-zinc-50 border border-zinc-200 rounded-lg p-4">
            <?php if (!$usa_radio_suc): ?>
            <div>
                <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wide mb-1">Sucursal</label>
                <select name="sucursal" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todas</option>
                    <?php foreach ($sucursales as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $f_sucursal == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="sucursal" value="<?= $f_sucursal > 0 ? (int) $f_sucursal : '' ?>">
            <?php endif; ?>
            <div>
                <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wide mb-1">Tipo</label>
                <select name="tipo" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todos</option>
                    <?php foreach ($tipos_existentes as $t): ?>
                    <option value="<?= e($t['tipo']) ?>" <?= $f_tipo === $t['tipo'] ? 'selected' : '' ?>><?= e($t['tipo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wide mb-1">Área</label>
                <select name="area" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todas</option>
                    <?php foreach ($areas as $ar): ?>
                    <option value="<?= $ar['id'] ?>" <?= $f_area == $ar['id'] ? 'selected' : '' ?>><?= e($ar['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wide mb-1">Estado</label>
                <select name="estado" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Todos</option>
                    <?php foreach (['nuevo'=>'Nuevo','en_uso'=>'En uso','en_reparacion'=>'En reparación','dado_de_baja'=>'Dado de baja'] as $ev => $lbl): ?>
                    <option value="<?= $ev ?>" <?= $f_estado === $ev ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wide mb-1">Disponibilidad</label>
                <select name="activo" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="">Activos e inactivos</option>
                    <option value="1" <?= $f_activo === '1' ? 'selected' : '' ?>>Solo activos</option>
                    <option value="0" <?= $f_activo === '0' ? 'selected' : '' ?>>Solo inactivos</option>
                </select>
            </div>
        </div>
    </div>
</form>

<div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm js-tabla-orden">
            <thead class="bg-zinc-50 border-b border-zinc-200">
                <tr>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Código</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Equipo</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Sucursal</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Área</th>
                    <th class="px-4 py-2.5 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Proveedor</th>
                    <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Comp.</th>
                    <th class="px-4 py-2.5 text-center text-[10px] font-bold text-zinc-500 uppercase tracking-wider" data-orden-tipo="num">Fallas</th>
                    <th class="px-4 py-2.5" data-no-orden></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <?php foreach ($equipos as $eq): ?>
                <tr class="hover:bg-zinc-50 group cursor-pointer <?= !$eq['activo'] ? 'opacity-50' : '' ?>" data-href="<?= url('equipo_ver.php?id=' . $eq['id']) ?>">
                    <td class="px-4 py-2.5 font-mono text-xs font-bold">
                        <a href="<?= url('equipo_ver.php?id=' . $eq['id']) ?>" class="text-zinc-700 hover:text-bacal-700 hover:underline"><?= e($eq['codigo_inventario']) ?></a>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="font-semibold text-sm text-zinc-900"><?= e($eq['nombre']) ?></div>
                        <?php if ($eq['marca'] || $eq['modelo']): ?>
                        <div class="text-[10px] text-zinc-500"><?= e(trim(($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-zinc-700"><?= e((string) $eq['tipo']) ?: '—' ?></td>
                    <td class="px-4 py-2.5">
                        <span class="font-mono text-[10px] bg-zinc-100 text-zinc-600 px-1.5 py-0.5 rounded font-bold"><?= e($eq['sucursal_codigo']) ?></span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-zinc-700"><?= e($eq['area_nombre'] ?? '—') ?></td>
                    <td class="px-4 py-2.5 text-xs">
                        <?php if ($eq['proveedor_nombre']): ?>
                        <a href="<?= url('proveedor_ver.php?id=' . $eq['proveedor_id']) ?>"
                           class="text-zinc-700 hover:text-bacal-700 hover:underline truncate">
                            <?= e($eq['proveedor_nombre']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-zinc-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2.5 text-center" data-orden="<?= (int) ($eq['componentes_count'] ?? 0) ?>">
                        <a href="<?= url('equipo_componentes.php?id=' . $eq['id']) ?>"
                           class="inline-flex items-center gap-1 text-xs font-semibold <?= (int) ($eq['componentes_count'] ?? 0) > 0 ? 'text-bacal-700 hover:underline' : 'text-zinc-300 hover:text-bacal-700' ?>"
                           title="Ver / agregar componentes">
                            <i data-lucide="cpu" class="w-3.5 h-3.5"></i> <?= (int) ($eq['componentes_count'] ?? 0) ?>
                        </a>
                    </td>
                    <td class="px-4 py-2.5 text-center" data-orden="<?= (int) $eq['incidencias_count'] ?>">
                        <?php if ((int) $eq['incidencias_count'] > 0): ?>
                        <a href="<?= url('bitacora.php?equipo=' . $eq['id']) ?>"
                           class="inline-flex items-center gap-1 text-xs font-bold text-bacal-700 hover:underline">
                            <?= $eq['incidencias_count'] ?>
                            <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                        </a>
                        <?php else: ?>
                        <span class="text-zinc-400 text-xs">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="<?= url('equipo_ver.php?id=' . $eq['id']) ?>"
                               class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700" title="Ver detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <a href="<?= url('admin/equipos.php?accion=editar&id=' . $eq['id']) ?>"
                               class="p-1.5 rounded text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700" title="Editar">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" action="<?= url('admin/equipos.php?accion=toggle&id=' . $eq['id']) ?>"
                                  onsubmit="return confirm('¿<?= $eq['activo'] ? 'Desactivar' : 'Activar' ?> este equipo?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="op" value="toggle">
                                <button type="submit" class="p-1.5 rounded text-zinc-500 hover:bg-bacal-50 hover:text-bacal-700">
                                    <i data-lucide="<?= $eq['activo'] ? 'power' : 'power-off' ?>" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($equipos)): ?>
                <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-zinc-500 italic">Sin equipos que coincidan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Toda la fila lleva a la ficha del equipo (sin estorbar enlaces/botones internos).
document.querySelectorAll('tr[data-href]').forEach(function (tr) {
    tr.addEventListener('click', function (e) {
        if (e.target.closest('a, button, form, input, select, label')) return;
        window.location = tr.dataset.href;
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../config/footer.php'; ?>
