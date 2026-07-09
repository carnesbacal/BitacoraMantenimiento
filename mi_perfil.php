<?php
/**
 * ============================================================================
 * mi_perfil.php - Edición del perfil del usuario actual
 * ============================================================================
 * El usuario logueado puede editar sus propios datos, subir foto y ver sus
 * estadísticas personales.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/notificaciones_helpers.php';

requerir_login();

$u = usuario_actual();
$id = (int) $u['id'];

$errores = [];

// ----------------------------------------------------------------------------
// Procesar POST (actualizar datos básicos)
// ----------------------------------------------------------------------------
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');
        try {
            if ($op === 'datos') {
                $nombre   = trim((string) input('nombre_completo', ''));
                $email    = trim((string) input('email', ''));
                $telefono = trim((string) input('telefono', ''));
                $puesto   = trim((string) input('puesto', ''));
                $pagina   = (string) input('pagina_inicio_preferida', 'dashboard.php');

                $paginas_validas = ['dashboard.php', 'bitacora.php', 'incidencia_nueva.php', 'notificaciones.php', 'base_conocimiento.php'];
                if (!in_array($pagina, $paginas_validas, true)) $pagina = 'dashboard.php';

                if ($nombre === '') $errores[] = 'El nombre completo es obligatorio.';
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errores[] = 'El email no parece válido.';
                }

                $escala = (int) input('escala_interfaz', 100);
                if (!in_array($escala, [90, 100, 110, 125], true)) $escala = 100;

                if (empty($errores)) {
                    $esc_col = ''; $esc_param = [];
                    if (db_one("SHOW COLUMNS FROM usuarios LIKE 'escala_interfaz'")) {
                        $esc_col = ', escala_interfaz = :esc'; $esc_param['esc'] = $escala;
                    }
                    db_exec(
                        "UPDATE usuarios SET
                            nombre_completo = :n, email = :e, telefono = :t, puesto = :pu,
                            pagina_inicio_preferida = :pag{$esc_col}
                         WHERE id = :id",
                        array_merge(['n' => $nombre, 'e' => $email ?: null, 't' => $telefono ?: null,
                         'pu' => $puesto ?: null, 'pag' => $pagina, 'id' => $id], $esc_param)
                    );

                    // Actualizar la sesión con los nuevos datos
                    $_SESSION['usuario']['nombre'] = $nombre;
                    $_SESSION['usuario']['nombre_completo'] = $nombre;
                    $_SESSION['usuario']['email'] = $email ?: null;
                    $_SESSION['usuario']['telefono'] = $telefono ?: null;
                    $_SESSION['usuario']['puesto'] = $puesto ?: null;
                    $_SESSION['usuario']['pagina_inicio_preferida'] = $pagina;
                    $_SESSION['usuario']['escala_interfaz'] = $escala;

                    registrar_auditoria('editar_perfil', 'usuarios', $id, 'Editó su perfil');
                    flash_set('success', 'Perfil actualizado correctamente.');
                    header('Location: ' . url('mi_perfil.php'));
                    exit;
                }
            } elseif ($op === 'notificaciones') {
                // ── Guardar Telegram Chat ID ──────────────────────────────────
                $telegram_chat_id = trim((string) input('telegram_chat_id', ''));
                // Solo dígitos y opcionalmente guión (IDs negativos para grupos)
                if ($telegram_chat_id !== '' && !preg_match('/^-?\d+$/', $telegram_chat_id)) {
                    $errores[] = 'El Chat ID de Telegram solo debe contener números.';
                }
                if (empty($errores)) {
                    db_exec(
                        "UPDATE usuarios SET telegram_chat_id = :tc WHERE id = :id",
                        ['tc' => $telegram_chat_id ?: null, 'id' => $id]
                    );
                    // ── Guardar preferencias por tipo ─────────────────────────
                    $tipos_notif = array_keys(NOTIF_TIPOS);
                    foreach ($tipos_notif as $tipo) {
                        $canal_email    = (bool) input("pref_email_{$tipo}", false) ? 1 : 0;
                        $canal_telegram = (bool) input("pref_tg_{$tipo}", false) ? 1 : 0;
                        db_exec(
                            "INSERT INTO notificacion_preferencias (usuario_id, tipo, canal_inapp, canal_email, canal_telegram)
                             VALUES (:uid, :tipo, 1, :email, :tg)
                             ON DUPLICATE KEY UPDATE canal_email = :email2, canal_telegram = :tg2",
                            ['uid' => $id, 'tipo' => $tipo, 'email' => $canal_email, 'tg' => $canal_telegram,
                             'email2' => $canal_email, 'tg2' => $canal_telegram]
                        );
                    }
                    registrar_auditoria('editar_perfil', 'usuarios', $id, 'Actualizó preferencias de notificación');
                    flash_set('success', 'Preferencias de notificación guardadas.');
                    header('Location: ' . url('mi_perfil.php') . '#notif');
                    exit;
                }
            } elseif ($op === 'eliminar_avatar') {
                // Borrar archivo físico si existe
                if (!empty($u['avatar_url'])) {
                    $ruta_disco = __DIR__ . '/' . $u['avatar_url'];
                    if (file_exists($ruta_disco)) @unlink($ruta_disco);
                }
                db_exec("UPDATE usuarios SET avatar_url = NULL WHERE id = :id", ['id' => $id]);
                $_SESSION['usuario']['avatar_url'] = null;
                registrar_auditoria('eliminar_avatar', 'usuarios', $id, 'Eliminó su foto de perfil');
                flash_set('success', 'Foto de perfil eliminada.');
                header('Location: ' . url('mi_perfil.php'));
                exit;
            }
        } catch (Throwable $e) {
            $errores[] = 'Error: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------------------------------
// Cargar datos actualizados desde la BD (por si cambió algo desde otro tab)
// ----------------------------------------------------------------------------
$u_data = db_one(
    "SELECT u.*, r.nombre rol_nombre, s.nombre sucursal_nombre, a.nombre area_nombre
     FROM usuarios u
     INNER JOIN roles r ON u.rol_id = r.id
     LEFT JOIN sucursales s ON u.sucursal_id = s.id
     LEFT JOIN areas a ON u.area_id = a.id
     WHERE u.id = :id",
    ['id' => $id]
);

// ----------------------------------------------------------------------------
// Estadísticas personales
// ----------------------------------------------------------------------------
$stats = db_one(
    "SELECT
        (SELECT COUNT(*) FROM incidencias WHERE reportado_por_id = :id1) AS total_creadas,
        (SELECT COUNT(*) FROM incidencias WHERE asignado_a_id = :id2) AS total_asignadas,
        (SELECT COUNT(*) FROM incidencias WHERE resuelto_por_id = :id3) AS total_resueltas,
        (SELECT AVG(tiempo_resolucion_min) FROM incidencias WHERE resuelto_por_id = :id4 AND tiempo_resolucion_min IS NOT NULL) AS avg_resolucion,
        (SELECT COUNT(*) FROM incidencias_comentarios WHERE usuario_id = :id5) AS total_comentarios,
        (SELECT COUNT(*) FROM incidencias WHERE asignado_a_id = :id6 AND estado_id IN (SELECT id FROM estados WHERE es_final = 0)) AS abiertas_actuales",
    array_fill_keys(['id1','id2','id3','id4','id5','id6'], $id)
);

// ----------------------------------------------------------------------------
// Actividad reciente del usuario (últimos 10 eventos en auditoría)
// ----------------------------------------------------------------------------
$actividad = db_all(
    "SELECT * FROM auditoria_sistema
     WHERE usuario_id = :id
     ORDER BY creado_en DESC
     LIMIT 10",
    ['id' => $id]
);

// ── Preferencias de notificación del usuario ────────────────────────────────
$notif_prefs_raw = db_all(
    "SELECT tipo, canal_email, canal_telegram FROM notificacion_preferencias WHERE usuario_id = :uid",
    ['uid' => $id]
);
$notif_prefs = [];
foreach ($notif_prefs_raw as $r) {
    $notif_prefs[$r['tipo']] = $r;
}

// Canales disponibles (solo mostrar email si SMTP activo, Telegram si bot activo)
$cfg_notif = db_one("SELECT smtp_activo, telegram_activo FROM configuracion_notificaciones WHERE id = 1") ?? [];
$canal_email_activo    = !empty($cfg_notif['smtp_activo']);
$canal_telegram_activo = !empty($cfg_notif['telegram_activo']);

// Tamaños máximos de avatar
$MAX_AVATAR_BYTES = 5 * 1024 * 1024; // 5 MB

$titulo_pagina = 'Mi perfil';
$pagina_activa = 'mi_perfil';
require_once __DIR__ . '/config/header.php';
?>

<div class="max-w-5xl mx-auto animate-fade-in space-y-5"
     x-data="{ tabActivo: 'datos' }">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-2">
        <?= render_avatar($u_data, 'w-16 h-16') ?>
        <div class="flex-1">
            <h2 class="font-display text-2xl font-extrabold text-zinc-900"><?= e($u_data['nombre_completo']) ?></h2>
            <p class="text-xs text-zinc-500 mt-0.5">
                <span class="font-mono"><?= e($u_data['usuario']) ?></span> ·
                <?= e($u_data['rol_nombre']) ?>
                <?php if ($u_data['sucursal_nombre']): ?> · <?= e($u_data['sucursal_nombre']) ?><?php endif; ?>
                <?php if ($u_data['area_nombre']): ?> · <?= e($u_data['area_nombre']) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (!empty($errores)): ?>
    <div class="px-4 py-3 rounded-lg bg-bacal-50 border border-bacal-200 text-bacal-800 text-sm">
        <ul class="list-disc list-inside text-xs">
            <?php foreach ($errores as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="border-b border-zinc-200">
        <div class="flex gap-1 -mb-px overflow-x-auto">
            <?php
            $tabs = [
                'datos' => ['Datos personales', 'user'],
                'foto' => ['Foto de perfil', 'image'],
                'preferencias' => ['Preferencias', 'sliders-horizontal'],
                'estadisticas' => ['Mis estadísticas', 'bar-chart-3'],
                'actividad' => ['Mi actividad', 'history'],
            ];
            foreach ($tabs as $key => [$label, $icon]):
            ?>
            <button type="button" @click="tabActivo = '<?= $key ?>'"
                    class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
                    :class="tabActivo === '<?= $key ?>' ? 'border-bacal-700 text-bacal-700' : 'border-transparent text-zinc-500 hover:text-zinc-700'">
                <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                <?= e($label) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB: Datos personales -->
    <div x-show="tabActivo === 'datos'" x-cloak>
        <form method="POST" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="datos">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre de usuario</label>
                    <div class="px-3 py-2 rounded-lg border border-zinc-200 bg-zinc-50 text-sm text-zinc-700 font-mono">
                        <?= e($u_data['usuario']) ?>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-1">El nombre de usuario no se puede cambiar.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Rol</label>
                    <div class="px-3 py-2 rounded-lg border border-zinc-200 bg-zinc-50 text-sm text-zinc-700">
                        <?= e($u_data['rol_nombre']) ?>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-1">Solo el administrador puede cambiarlo.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Nombre completo *</label>
                    <input type="text" name="nombre_completo" required maxlength="150"
                           value="<?= e($u_data['nombre_completo']) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Email</label>
                    <input type="email" name="email" maxlength="150"
                           value="<?= e((string) $u_data['email']) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Teléfono</label>
                    <input type="text" name="telefono" maxlength="50"
                           value="<?= e((string) $u_data['telefono']) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-700 mb-1 uppercase tracking-wide">Puesto</label>
                    <input type="text" name="puesto" maxlength="100"
                           value="<?= e((string) $u_data['puesto']) ?>"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
            </div>

            <!-- Mantengo pagina_inicio en hidden para no perderla -->
            <input type="hidden" name="pagina_inicio_preferida" value="<?= e($u_data['pagina_inicio_preferida'] ?? 'dashboard.php') ?>">
            <input type="hidden" name="escala_interfaz" value="<?= (int) ($u_data['escala_interfaz'] ?? 100) ?>">

            <div class="flex justify-between items-center pt-3 border-t border-zinc-100">
                <a href="<?= url('cambiar_password.php') ?>" class="text-xs font-semibold text-bacal-700 hover:text-bacal-800 flex items-center gap-1.5">
                    <i data-lucide="key" class="w-3.5 h-3.5"></i> Cambiar mi contraseña
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <!-- TAB: Foto de perfil -->
    <div x-show="tabActivo === 'foto'" x-cloak>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6"
             x-data="avatarUpload()">

            <h3 class="font-display text-base font-bold text-zinc-900 mb-1">Foto de perfil</h3>
            <p class="text-xs text-zinc-500 mb-5">Imagen cuadrada o se recortará automáticamente. Máximo 5 MB. Formatos: JPG, PNG, WebP.</p>

            <div class="flex items-start gap-6">
                <!-- Vista actual -->
                <div class="flex-shrink-0">
                    <?= render_avatar($u_data, 'w-32 h-32', 'border-4 border-zinc-100') ?>
                </div>

                <!-- Acciones -->
                <div class="flex-1 space-y-3">
                    <input type="file" x-ref="inputFoto" accept="image/jpeg,image/png,image/webp"
                           @change="subir($event.target.files[0])" class="hidden">

                    <button type="button" @click="$refs.inputFoto.click()"
                            :disabled="subiendo"
                            class="px-4 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold flex items-center gap-2 disabled:opacity-50">
                        <template x-if="!subiendo">
                            <span class="flex items-center gap-2"><i data-lucide="upload" class="w-4 h-4"></i> Subir foto</span>
                        </template>
                        <template x-if="subiendo">
                            <span class="flex items-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Subiendo…</span>
                        </template>
                    </button>

                    <?php if (!empty($u_data['avatar_url'])): ?>
                    <form method="POST" onsubmit="return confirm('¿Eliminar tu foto de perfil?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="op" value="eliminar_avatar">
                        <button type="submit" class="px-4 py-2 rounded-lg border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50 flex items-center gap-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Quitar foto actual
                        </button>
                    </form>
                    <?php endif; ?>

                    <p class="text-[11px] text-zinc-500 leading-relaxed">
                        Si la imagen no es cuadrada, se recortará automáticamente desde el centro y se redimensionará a 400×400 píxeles.
                    </p>

                    <!-- Feedback de error -->
                    <div x-show="error" x-cloak class="text-xs text-bacal-700 bg-bacal-50 border border-bacal-200 rounded-lg px-3 py-2"
                         x-text="error"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: Preferencias -->
    <div x-show="tabActivo === 'preferencias'" x-cloak>
        <form method="POST" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="datos">
            <input type="hidden" name="nombre_completo" value="<?= e($u_data['nombre_completo']) ?>">
            <input type="hidden" name="email" value="<?= e((string) $u_data['email']) ?>">
            <input type="hidden" name="telefono" value="<?= e((string) $u_data['telefono']) ?>">
            <input type="hidden" name="puesto" value="<?= e((string) $u_data['puesto']) ?>">

            <h3 class="font-display text-base font-bold text-zinc-900 mb-3">Preferencias de uso</h3>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase tracking-wide">Página que abre al iniciar sesión</label>
                <select name="pagina_inicio_preferida"
                        class="w-full md:w-80 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <option value="dashboard.php" <?= $u_data['pagina_inicio_preferida'] === 'dashboard.php' ? 'selected' : '' ?>>Dashboard</option>
                    <option value="bitacora.php" <?= $u_data['pagina_inicio_preferida'] === 'bitacora.php' ? 'selected' : '' ?>>Bitácora</option>
                    <?php if (tiene_permiso('crear_solicitud')): ?>
                    <option value="incidencia_nueva.php" <?= $u_data['pagina_inicio_preferida'] === 'incidencia_nueva.php' ? 'selected' : '' ?>>Nueva incidencia</option>
                    <?php endif; ?>
                    <option value="notificaciones.php" <?= $u_data['pagina_inicio_preferida'] === 'notificaciones.php' ? 'selected' : '' ?>>Notificaciones</option>
                    <option value="base_conocimiento.php" <?= $u_data['pagina_inicio_preferida'] === 'base_conocimiento.php' ? 'selected' : '' ?>>Base de conocimiento</option>
                </select>
                <p class="text-[10px] text-zinc-500 mt-1">Cuando inicies sesión te dirigiremos directamente a esta página.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-700 mb-2 uppercase tracking-wide">Tamaño / escala de la interfaz</label>
                <select name="escala_interfaz"
                        onchange="document.documentElement.style.fontSize = (this.value == 100 ? '' : this.value + '%')"
                        class="w-full md:w-80 px-3 py-2 rounded-lg border border-zinc-300 bg-white text-sm focus:outline-none focus:border-bacal-700">
                    <?php $esc_actual = (int) ($u_data['escala_interfaz'] ?? 100); ?>
                    <option value="90"  <?= $esc_actual === 90  ? 'selected' : '' ?>>Compacta (90%)</option>
                    <option value="100" <?= $esc_actual === 100 ? 'selected' : '' ?>>Normal (100%)</option>
                    <option value="110" <?= $esc_actual === 110 ? 'selected' : '' ?>>Grande (110%)</option>
                    <option value="125" <?= $esc_actual === 125 ? 'selected' : '' ?>>Muy grande (125%)</option>
                </select>
                <p class="text-[10px] text-zinc-500 mt-1">Agranda o reduce todo (texto, botones, espaciado). El cambio se previsualiza al instante y se aplica en toda la app al guardar.</p>
            </div>

            <div class="flex justify-end pt-3 border-t border-zinc-100">
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar preferencias
                </button>
            </div>
        </form>

        <!-- ── Notificaciones externas ───────────────────────────────── -->
        <form method="POST" id="notif" class="bg-white rounded-xl border border-zinc-200 shadow-sm p-6 space-y-5 mt-4">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="notificaciones">

            <h3 class="font-display text-base font-bold text-zinc-900">Notificaciones externas</h3>
            <p class="text-xs text-zinc-500 -mt-2">Además de las notificaciones dentro de la app, puedes recibir alertas por correo y/o Telegram. El administrador debe activar cada canal primero.</p>

            <!-- Telegram Chat ID -->
            <div class="rounded-lg border border-zinc-200 p-4 space-y-3">
                <div class="flex items-center gap-2 text-sm font-semibold text-zinc-700">
                    <i data-lucide="send" class="w-4 h-4 text-sky-500"></i> Telegram
                    <?php if ($canal_telegram_activo): ?>
                    <span class="text-xs font-normal text-green-600 bg-green-50 border border-green-200 rounded px-1.5">Activo</span>
                    <?php else: ?>
                    <span class="text-xs font-normal text-zinc-400 bg-zinc-100 rounded px-1.5">Sin configurar por el admin</span>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 mb-1">Tu Chat ID de Telegram</label>
                    <input type="text" name="telegram_chat_id"
                           value="<?= e($u_data['telegram_chat_id'] ?? '') ?>"
                           placeholder="Ej: 123456789"
                           class="w-full md:w-64 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <p class="text-xs text-zinc-400 mt-1">Para obtenerlo: inicia conversación con el bot del sistema en Telegram, luego busca <strong>@userinfobot</strong> y envíale cualquier mensaje — te responderá con tu ID numérico.</p>
                </div>
            </div>

            <!-- Tabla de preferencias por tipo -->
            <?php
            $etiquetas_tipo = [
                'asignacion'              => 'Me asignan una incidencia',
                'cambio_estado'           => 'Cambia el estado de una incidencia mía',
                'comentario'              => 'Comentan en una incidencia mía',
                'mencion'                 => 'Me mencionan en un comentario',
                'reincidencia'            => 'Se detecta una reincidencia',
                'sla_vencido'             => 'Vence el SLA de una incidencia',
                'sla_riesgo'              => 'Una incidencia está en riesgo de vencer SLA',
                'incidencia_creada'       => 'Se crea una incidencia nueva (solo admins)',
                'incidencia_resuelta'     => 'Se resuelve una incidencia',
                'mantenimiento_proximo'   => 'Se acerca mantenimiento programado',
                'mantenimiento_vencido'   => 'Mantenimiento vencido sin atender',
                'mantenimiento_completado'=> 'Se completa un mantenimiento',
                'sistema'                 => 'Mensajes del sistema',
            ];
            $hay_canal = $canal_email_activo || $canal_telegram_activo;
            ?>
            <?php if ($hay_canal): ?>
            <div>
                <label class="block text-xs font-bold text-zinc-600 mb-2 uppercase tracking-wide">¿Para qué eventos quieres recibir notificaciones externas?</label>
                <div class="overflow-x-auto rounded-lg border border-zinc-200">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 text-xs text-zinc-500 border-b border-zinc-200">
                            <tr>
                                <th class="text-left px-3 py-2.5 font-semibold">Evento</th>
                                <th class="text-center px-3 py-2.5 font-semibold w-16">In-app</th>
                                <?php if ($canal_email_activo): ?>
                                <th class="text-center px-3 py-2.5 font-semibold w-20">
                                    <i data-lucide="mail" class="w-3.5 h-3.5 inline"></i> Email
                                </th>
                                <?php endif; ?>
                                <?php if ($canal_telegram_activo): ?>
                                <th class="text-center px-3 py-2.5 font-semibold w-24">
                                    <i data-lucide="send" class="w-3.5 h-3.5 inline"></i> Telegram
                                </th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                        <?php foreach (NOTIF_TIPOS as $tipo => $meta):
                            $pref_email = (int) ($notif_prefs[$tipo]['canal_email'] ?? 0);
                            $pref_tg    = (int) ($notif_prefs[$tipo]['canal_telegram'] ?? 0);
                            $label      = $etiquetas_tipo[$tipo] ?? ucwords(str_replace('_', ' ', $tipo));
                        ?>
                        <tr class="hover:bg-zinc-50/50">
                            <td class="px-3 py-2.5 text-zinc-700 flex items-center gap-2">
                                <span class="w-5 h-5 rounded flex items-center justify-center shrink-0"
                                      style="background-color:<?= e($meta['color']) ?>20">
                                    <i data-lucide="<?= e($meta['icono']) ?>" class="w-3 h-3" style="color:<?= e($meta['color']) ?>"></i>
                                </span>
                                <?= e($label) ?>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="text-green-500"><i data-lucide="check" class="w-4 h-4 inline"></i></span>
                            </td>
                            <?php if ($canal_email_activo): ?>
                            <td class="px-3 py-2.5 text-center">
                                <input type="checkbox" name="pref_email_<?= $tipo ?>" value="1" <?= $pref_email ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded text-bacal-700 focus:ring-bacal-600">
                            </td>
                            <?php endif; ?>
                            <?php if ($canal_telegram_activo): ?>
                            <td class="px-3 py-2.5 text-center">
                                <input type="checkbox" name="pref_tg_<?= $tipo ?>" value="1" <?= $pref_tg ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500">
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="rounded-lg bg-zinc-50 border border-zinc-200 px-4 py-3 text-sm text-zinc-500 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                El administrador aún no ha configurado ningún canal externo (email o Telegram). Cuando lo haga, podrás elegir aquí qué notificaciones recibir.
            </div>
            <?php endif; ?>

            <div class="flex justify-end pt-3 border-t border-zinc-100">
                <button type="submit" class="px-5 py-2 rounded-lg bg-bacal-700 hover:bg-bacal-800 text-white text-sm font-semibold">
                    Guardar preferencias de notificación
                </button>
            </div>
        </form>
    </div>

    <!-- TAB: Estadísticas -->
    <div x-show="tabActivo === 'estadisticas'" x-cloak class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <?php
            $kpis = [
                ['Incidencias creadas', $stats['total_creadas'], 'file-plus', '#2563EB'],
                ['Asignadas (total)', $stats['total_asignadas'], 'user-check', '#7C3AED'],
                ['Resueltas', $stats['total_resueltas'], 'check-circle-2', '#16A34A'],
                ['Abiertas ahora', $stats['abiertas_actuales'], 'clock', ((int) $stats['abiertas_actuales']) > 5 ? '#DC2626' : '#D97706'],
                ['Comentarios', $stats['total_comentarios'], 'message-square', '#0EA5E9'],
                ['T. promedio resolución', $stats['avg_resolucion'] !== null ? fmt_duracion((int) $stats['avg_resolucion']) : '—', 'timer', '#9333EA'],
            ];
            foreach ($kpis as [$label, $valor, $icono, $color]):
            ?>
            <div class="bg-white rounded-xl border border-zinc-200 p-4 shadow-sm">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2" style="background-color: <?= e($color) ?>15">
                    <i data-lucide="<?= e($icono) ?>" class="w-4 h-4" style="color: <?= e($color) ?>"></i>
                </div>
                <div class="font-display text-xl font-extrabold text-zinc-900 leading-none"><?= e((string) $valor) ?></div>
                <div class="text-[10px] text-zinc-500 mt-1.5 uppercase tracking-wider font-bold"><?= e($label) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (tiene_permiso('resolver') && (int) $stats['total_asignadas'] > 0): ?>
        <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-5 text-sm text-zinc-700">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="info" class="w-4 h-4 text-bacal-700"></i>
                <strong>Tip:</strong>
            </div>
            <p class="text-xs leading-relaxed">
                Puedes ver el detalle de todas las incidencias en las que has trabajado en
                <a href="<?= url('bitacora.php?asignado_a=' . $id) ?>" class="text-bacal-700 hover:underline font-semibold">la bitácora filtrada</a>.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: Actividad -->
    <div x-show="tabActivo === 'actividad'" x-cloak>
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm">
            <div class="px-5 py-3 border-b border-zinc-100">
                <h3 class="font-display text-base font-bold text-zinc-900">Mi actividad reciente</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Últimas 10 acciones que realizaste en el sistema.</p>
            </div>
            <div class="divide-y divide-zinc-100">
                <?php if (empty($actividad)): ?>
                <div class="px-5 py-10 text-center text-xs text-zinc-400 italic">Sin actividad registrada.</div>
                <?php else: ?>
                <?php foreach ($actividad as $act): ?>
                <div class="px-5 py-3 flex items-start gap-3">
                    <div class="w-2 h-2 rounded-full bg-bacal-600 mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-zinc-900"><?= e($act['descripcion'] ?? $act['accion']) ?></span>
                            <span class="text-[10px] font-mono text-zinc-400 bg-zinc-100 px-1.5 py-0.5 rounded"><?= e($act['accion']) ?></span>
                        </div>
                        <div class="text-[11px] text-zinc-500 mt-0.5">
                            <?= e(fmt_fecha($act['creado_en'])) ?> · IP: <?= e($act['ip'] ?? '—') ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function avatarUpload() {
    return {
        subiendo: false,
        error: '',

        async subir(archivo) {
            if (!archivo) return;
            this.error = '';

            // Validar tipo
            const tiposOk = ['image/jpeg', 'image/png', 'image/webp'];
            if (!tiposOk.includes(archivo.type)) {
                this.error = 'Solo se permiten imágenes JPG, PNG o WebP.';
                return;
            }
            // Validar tamaño
            if (archivo.size > <?= $MAX_AVATAR_BYTES ?>) {
                this.error = 'La imagen excede los 5 MB. Comprímela e intenta de nuevo.';
                return;
            }

            this.subiendo = true;
            const fd = new FormData();
            fd.append('_csrf', '<?= e(csrf_token()) ?>');
            fd.append('avatar', archivo);

            try {
                const resp = await fetch('<?= url('api/avatar_subir.php') ?>', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await resp.json();
                if (data.ok) {
                    window.location.reload();
                } else {
                    this.error = data.error || 'Error al subir la imagen.';
                }
            } catch (e) {
                this.error = 'Error de red: ' + e.message;
            }
            this.subiendo = false;
        }
    }
}
</script>

<?php require_once __DIR__ . '/config/footer.php'; ?>
