<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/flotilla_helpers.php';

requerir_login();
if (!tiene_permiso('administrar')) {
    flash_set('error', 'Solo administradores pueden editar vehículos.');
    header('Location: ' . url('flotilla_vehiculos.php'));
    exit;
}

$id = (int) input('id', 0);
$vehiculo = $id > 0 ? flotilla_vehiculo($id) : null;
if (!$vehiculo) {
    flash_set('error', 'Vehículo no encontrado.');
    header('Location: ' . url('flotilla_vehiculos.php'));
    exit;
}

$errores = [];

if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $d = [
            'id'                    => $id,
            'alias'                 => trim((string) input('alias', '')) ?: null,
            'tipo_id'               => (int) input('tipo_id', 0) ?: null,
            'sucursal_id'           => (int) input('sucursal_id', 0) ?: null,
            'conductor_asignado_id' => (int) input('conductor_asignado_id', 0) ?: null,
            'marca'                 => trim((string) input('marca', '')),
            'modelo'                => trim((string) input('modelo', '')),
            'anio'                  => (int) input('anio', 0) ?: (int) $vehiculo['anio'],
            'color'                 => trim((string) input('color', '')) ?: null,
            'placas'                => strtoupper(trim((string) input('placas', ''))),
            'numero_serie'          => trim((string) input('numero_serie', '')) ?: null,
            'numero_motor'          => trim((string) input('numero_motor', '')) ?: null,
            'combustible_tipo'      => trim((string) input('combustible_tipo', 'diesel')),
            'tiene_refrigeracion'   => (int) input('tiene_refrigeracion', 0),
            'temp_min_c'            => trim((string) input('temp_min_c', '')) !== '' ? (float) input('temp_min_c', 0) : null,
            'temp_max_c'            => trim((string) input('temp_max_c', '')) !== '' ? (float) input('temp_max_c', 0) : null,
            'capacidad_carga_kg'    => trim((string) input('capacidad_carga_kg', '')) !== '' ? (float) input('capacidad_carga_kg', 0) : null,
            'km_inicial'            => (int) input('km_inicial', 0),
            'es_propio'             => (int) input('es_propio', 1),
            'proveedor_renta'       => trim((string) input('proveedor_renta', '')) ?: null,
            'fecha_adquisicion'     => trim((string) input('fecha_adquisicion', '')) ?: null,
            'costo_adquisicion'     => trim((string) input('costo_adquisicion', '')) !== '' ? (float) input('costo_adquisicion', 0) : null,
            'estado'                => trim((string) input('estado', 'activo')),
            'notas'                 => trim((string) input('notas', '')) ?: null,
        ];

        if (!$d['marca'])  $errores[] = 'La marca es obligatoria.';
        if (!$d['modelo']) $errores[] = 'El modelo es obligatorio.';
        if (!$d['placas']) $errores[] = 'Las placas son obligatorias.';

        if (empty($errores)) {
            $dup = db_one("SELECT id FROM flotilla_vehiculos WHERE placas=:p AND id<>:id", ['p' => $d['placas'], 'id' => $id]);
            if ($dup) $errores[] = 'Ya existe otro vehículo con esas placas.';
        }

        if (empty($errores)) {
            db_exec("UPDATE flotilla_vehiculos SET
                alias=:alias, tipo_id=:tipo_id, sucursal_id=:sucursal_id,
                conductor_asignado_id=:conductor_asignado_id,
                marca=:marca, modelo=:modelo, anio=:anio, color=:color, placas=:placas,
                numero_serie=:numero_serie, numero_motor=:numero_motor,
                combustible_tipo=:combustible_tipo,
                tiene_refrigeracion=:tiene_refrigeracion,
                temp_min_c=:temp_min_c, temp_max_c=:temp_max_c,
                capacidad_carga_kg=:capacidad_carga_kg,
                km_inicial=:km_inicial, es_propio=:es_propio,
                proveedor_renta=:proveedor_renta,
                fecha_adquisicion=:fecha_adquisicion,
                costo_adquisicion=:costo_adquisicion,
                estado=:estado, notas=:notas
                WHERE id=:id", $d);
            flash_set('exito', 'Vehículo actualizado correctamente.');
            header('Location: ' . url("flotilla_vehiculo_ver.php?id=$id"));
            exit;
        }
    }
}

$tipos      = db_all("SELECT id, nombre FROM flotilla_tipos_vehiculo WHERE activo=1 ORDER BY nombre");
$sucursales = db_all("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
$conductores= db_all("SELECT id, nombre_completo FROM flotilla_conductores WHERE activo=1 ORDER BY nombre_completo");

$titulo_pagina = 'Editar vehículo · ' . ($vehiculo['alias'] ?: $vehiculo['placas']);
$pagina_activa = 'flotilla';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-3xl mx-auto animate-fade-in">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-500 mb-4">
        <a href="<?= url('flotilla_vehiculos.php') ?>" class="hover:text-bacal-700">Flotilla</a>
        <span>›</span>
        <a href="<?= url("flotilla_vehiculo_ver.php?id=$id") ?>" class="hover:text-bacal-700">
            <?= e($vehiculo['alias'] ?: $vehiculo['placas']) ?>
        </a>
        <span>›</span>
        <span class="text-zinc-900 font-semibold">Editar</span>
    </div>

    <!-- Errores -->
    <?php if ($errores): ?>
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-300 text-sm text-red-800">
        <?php foreach ($errores as $e): ?><div>✗ <?= e($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" class="space-y-6">
        <?= csrf_input() ?>

        <!-- IDENTIFICACIÓN -->
        <div class="bg-white rounded-xl border border-zinc-200 p-6">
            <h2 class="font-display font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="car" class="w-5 h-5 text-bacal-700"></i> Identificación
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Alias / No. Eco</label>
                    <input type="text" name="alias" value="<?= e($vehiculo['alias'] ?? '') ?>" maxlength="80"
                           placeholder="Ej. B-04"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Placas <span class="text-red-500">*</span></label>
                    <input type="text" name="placas" value="<?= e($vehiculo['placas']) ?>" required maxlength="20"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Marca <span class="text-red-500">*</span></label>
                    <input type="text" name="marca" value="<?= e($vehiculo['marca']) ?>" required maxlength="80"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Modelo <span class="text-red-500">*</span></label>
                    <input type="text" name="modelo" value="<?= e($vehiculo['modelo']) ?>" required maxlength="80"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Año</label>
                    <input type="number" name="anio" value="<?= e($vehiculo['anio'] ?? '') ?>" min="1990" max="<?= date('Y')+1 ?>"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Color</label>
                    <input type="text" name="color" value="<?= e($vehiculo['color'] ?? '') ?>" maxlength="50"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Número de serie (VIN)</label>
                    <input type="text" name="numero_serie" value="<?= e($vehiculo['numero_serie'] ?? '') ?>" maxlength="50"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Número de motor</label>
                    <input type="text" name="numero_motor" value="<?= e($vehiculo['numero_motor'] ?? '') ?>" maxlength="50"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
        </div>

        <!-- CLASIFICACIÓN -->
        <div class="bg-white rounded-xl border border-zinc-200 p-6">
            <h2 class="font-display font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="tag" class="w-5 h-5 text-bacal-700"></i> Clasificación
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Tipo de vehículo</label>
                    <select name="tipo_id" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin tipo —</option>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int)($vehiculo['tipo_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                            <?= e($t['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Combustible</label>
                    <select name="combustible_tipo" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <?php foreach ([
                            'diesel'    => 'Diesel',
                            'gasolina'  => 'Gasolina',
                            'gas'       => 'Gas',
                            'electrico' => 'Eléctrico',
                            'hibrido'   => 'Híbrido',
                        ] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($vehiculo['combustible_tipo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin sucursal —</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)($vehiculo['sucursal_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Conductor asignado</label>
                    <select name="conductor_asignado_id" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="">— Sin conductor fijo —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)($vehiculo['conductor_asignado_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre_completo']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Estado</label>
                    <select name="estado" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <?php foreach (['activo'=>'Activo','taller'=>'En taller','inactivo'=>'Inactivo','baja'=>'Baja'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($vehiculo['estado'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Propiedad</label>
                    <select name="es_propio" class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                        <option value="1" <?= ($vehiculo['es_propio'] ?? 1) ? 'selected' : '' ?>>Propio</option>
                        <option value="0" <?= !($vehiculo['es_propio'] ?? 1) ? 'selected' : '' ?>>Rentado</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Proveedor de renta (si aplica)</label>
                    <input type="text" name="proveedor_renta" value="<?= e($vehiculo['proveedor_renta'] ?? '') ?>" maxlength="150"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
        </div>

        <!-- CARGA Y REFRIGERACIÓN -->
        <div class="bg-white rounded-xl border border-zinc-200 p-6">
            <h2 class="font-display font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="thermometer" class="w-5 h-5 text-bacal-700"></i> Carga y refrigeración
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Cap. de carga (kg)</label>
                    <input type="number" name="capacidad_carga_kg" value="<?= e($vehiculo['capacidad_carga_kg'] ?? '') ?>" min="0" step="0.1"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="tiene_refrigeracion" value="1"
                               <?= ($vehiculo['tiene_refrigeracion'] ?? 0) ? 'checked' : '' ?>
                               class="w-4 h-4 rounded border-zinc-300">
                        <span class="text-sm font-semibold text-zinc-700">Refrigeración</span>
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Temp. mínima (°C)</label>
                    <input type="number" name="temp_min_c" value="<?= e($vehiculo['temp_min_c'] ?? '') ?>" step="0.5"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Temp. máxima (°C)</label>
                    <input type="number" name="temp_max_c" value="<?= e($vehiculo['temp_max_c'] ?? '') ?>" step="0.5"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
        </div>

        <!-- ADQUISICIÓN -->
        <div class="bg-white rounded-xl border border-zinc-200 p-6">
            <h2 class="font-display font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="receipt" class="w-5 h-5 text-bacal-700"></i> Adquisición y km
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Km inicial</label>
                    <input type="number" name="km_inicial" value="<?= e($vehiculo['km_inicial'] ?? 0) ?>" min="0"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Fecha adquisición</label>
                    <input type="date" name="fecha_adquisicion" value="<?= e($vehiculo['fecha_adquisicion'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1">Costo adquisición ($)</label>
                    <input type="number" name="costo_adquisicion" value="<?= e($vehiculo['costo_adquisicion'] ?? '') ?>" min="0" step="0.01"
                           class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                </div>
            </div>
        </div>

        <!-- NOTAS -->
        <div class="bg-white rounded-xl border border-zinc-200 p-6">
            <h2 class="font-display font-bold text-zinc-900 mb-4 flex items-center gap-2">
                <i data-lucide="sticky-note" class="w-5 h-5 text-bacal-700"></i> Notas internas
            </h2>
            <textarea name="notas" rows="4" maxlength="1000"
                      class="w-full px-3 py-2 border border-zinc-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500 resize-none"><?= e($vehiculo['notas'] ?? '') ?></textarea>
        </div>

        <!-- BOTONES -->
        <div class="flex items-center justify-end gap-3 pb-6">
            <a href="<?= url("flotilla_vehiculo_ver.php?id=$id") ?>"
               class="px-5 py-2.5 text-sm font-semibold text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-bacal-700 rounded-lg hover:bg-bacal-800 flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Guardar cambios
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/config/footer.php'; ?>
