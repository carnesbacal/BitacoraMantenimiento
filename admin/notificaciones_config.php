<?php
/**
 * ============================================================================
 * admin/notificaciones_config.php
 * Configuración de canales de notificación: SMTP y Telegram
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/admin_helpers.php';
require_once __DIR__ . '/../config/notificaciones_canales.php';

requerir_permiso('administrar');
$u = usuario_actual();

$exito  = '';
$errores = [];

// ── POST ─────────────────────────────────────────────────────────────────────
if (es_post()) {
    if (!csrf_valido(input('_csrf'))) {
        $errores[] = 'Token de seguridad inválido.';
    } else {
        $op = (string) input('op', '');

        // ── Guardar configuración ──────────────────────────────────────────
        if ($op === 'guardar') {
            $smtp_host        = trim((string) input('smtp_host', ''));
            $smtp_port        = (int) input('smtp_port', 587);
            $smtp_seguridad   = (string) input('smtp_seguridad', 'tls');
            $smtp_usuario     = trim((string) input('smtp_usuario', ''));
            $smtp_password    = trim((string) input('smtp_password', ''));
            $smtp_from_email  = trim((string) input('smtp_from_email', ''));
            $smtp_from_nombre = trim((string) input('smtp_from_nombre', 'Bitácora Mantenimiento'));
            $smtp_activo      = (int) (bool) input('smtp_activo', false);

            $telegram_token   = trim((string) input('telegram_bot_token', ''));
            $telegram_activo  = (int) (bool) input('telegram_activo', false);

            if (!in_array($smtp_seguridad, ['tls', 'ssl', 'none'], true)) $smtp_seguridad = 'tls';
            if ($smtp_port < 1 || $smtp_port > 65535) $smtp_port = 587;

            if ($smtp_activo && (!$smtp_host || !$smtp_from_email)) {
                $errores[] = 'Para activar email debes configurar el servidor SMTP y el correo remitente.';
            }
            if ($telegram_activo && !$telegram_token) {
                $errores[] = 'Para activar Telegram debes ingresar el token del bot.';
            }

            // Si la contraseña enviada está vacía, conservar la existente
            $pass_sql = '';
            $params_extra = [];
            if ($smtp_password !== '') {
                $pass_sql = ', smtp_password = :smtp_pass';
                $params_extra['smtp_pass'] = $smtp_password;
            }

            if (empty($errores)) {
                db_exec(
                    "UPDATE configuracion_notificaciones SET
                        smtp_host        = :smtp_host,
                        smtp_port        = :smtp_port,
                        smtp_seguridad   = :smtp_seg,
                        smtp_usuario     = :smtp_user,
                        smtp_from_email  = :smtp_from,
                        smtp_from_nombre = :smtp_nombre,
                        smtp_activo      = :smtp_act,
                        telegram_bot_token = :tg_token,
                        telegram_activo  = :tg_act,
                        actualizado_por  = :uid
                        {$pass_sql}
                     WHERE id = 1",
                    array_merge([
                        'smtp_host'   => $smtp_host   ?: null,
                        'smtp_port'   => $smtp_port,
                        'smtp_seg'    => $smtp_seguridad,
                        'smtp_user'   => $smtp_usuario ?: null,
                        'smtp_from'   => $smtp_from_email ?: null,
                        'smtp_nombre' => $smtp_from_nombre ?: 'Bitácora Mantenimiento',
                        'smtp_act'    => $smtp_activo,
                        'tg_token'    => $telegram_token ?: null,
                        'tg_act'      => $telegram_activo,
                        'uid'         => (int) $u['id'],
                    ], $params_extra)
                );
                flash_set('ok', 'Configuración guardada correctamente.');
                header('Location: ' . url('admin/notificaciones_config.php'));
                exit;
            }
        }

        // ── Test Email ────────────────────────────────────────────────────
        if ($op === 'test_email') {
            $dest = trim((string) input('test_email_dest', ''));
            if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'Ingresa un email válido para la prueba.';
            } else {
                $res = nc_test_canal('email', $dest);
                if ($res['ok']) {
                    $exito = "Email de prueba enviado a <strong>" . e($dest) . "</strong>.";
                } else {
                    $errores[] = "Error al enviar email de prueba: " . e($res['error'] ?? 'desconocido');
                }
            }
        }

        // ── Test Telegram ─────────────────────────────────────────────────
        if ($op === 'test_telegram') {
            $dest = trim((string) input('test_tg_dest', ''));
            if (!$dest) {
                $errores[] = 'Ingresa un Chat ID para la prueba.';
            } else {
                $res = nc_test_canal('telegram', $dest);
                if ($res['ok']) {
                    $exito = "Mensaje de prueba enviado al Chat ID <strong>" . e($dest) . "</strong>.";
                } else {
                    $errores[] = "Error al enviar mensaje de Telegram: " . e($res['error'] ?? 'desconocido');
                }
            }
        }
    }
}

// ── Leer config actual ────────────────────────────────────────────────────────
$cfg = db_one("SELECT * FROM configuracion_notificaciones WHERE id = 1") ?: [];

$flash_msgs = flash_get();

$pagina_activa = 'admin_notificaciones_config';
require_once __DIR__ . '/../config/header.php';
?>

<div class="max-w-3xl mx-auto">
<?php render_admin_header('Notificaciones', 'Configuración de email y Telegram'); ?>

<?php foreach ($flash_msgs as $fm):
    $fm_tipo = $fm['tipo'] ?? 'ok';
    $fm_msg  = e($fm['mensaje'] ?? '');
?>
<div class="mb-4 rounded-lg <?= $fm_tipo === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800' ?> border px-4 py-3 text-sm flex items-center gap-2">
    <i data-lucide="<?= $fm_tipo === 'error' ? 'alert-circle' : 'check-circle-2' ?>" class="w-4 h-4 shrink-0"></i> <?= $fm_msg ?>
</div>
<?php endforeach; ?>
<?php if ($errores): ?>
<div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm space-y-1">
    <?php foreach ($errores as $err): ?>
    <div class="flex items-start gap-2"><i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i><?= e($err) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($exito): ?>
<div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-blue-800 text-sm flex items-center gap-2">
    <i data-lucide="send" class="w-4 h-4 shrink-0"></i> <?= $exito ?>
</div>
<?php endif; ?>

<form method="post" action="<?= url('admin/notificaciones_config.php') ?>">
<?= csrf_input() ?>
<input type="hidden" name="op" value="guardar">

<!-- ═══════════════════════════════════════════════════════════════
     SECCIÓN: EMAIL / SMTP
════════════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm mb-6">
    <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100">
        <div class="flex items-center gap-2">
            <i data-lucide="mail" class="w-5 h-5 text-bacal-700"></i>
            <h2 class="font-semibold text-zinc-900">Correo electrónico (SMTP)</h2>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <span class="text-sm text-zinc-500">Activar</span>
            <input type="hidden" name="smtp_activo" value="0">
            <input type="checkbox" name="smtp_activo" value="1" <?= !empty($cfg['smtp_activo']) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded text-bacal-700 focus:ring-bacal-600">
        </label>
    </div>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-zinc-600 mb-1">Servidor SMTP <span class="text-zinc-400">(ej: mail.tudominio.com, smtp.gmail.com)</span></label>
                <input type="text" name="smtp_host" value="<?= e($cfg['smtp_host'] ?? '') ?>"
                       placeholder="mail.tudominio.com"
                       class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-600 mb-1">Puerto</label>
                <input type="number" name="smtp_port" value="<?= e($cfg['smtp_port'] ?? 587) ?>"
                       min="1" max="65535"
                       class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Seguridad</label>
            <select name="smtp_seguridad" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                <option value="tls"  <?= ($cfg['smtp_seguridad'] ?? 'tls') === 'tls'  ? 'selected' : '' ?>>TLS (STARTTLS) — Puerto 587 recomendado</option>
                <option value="ssl"  <?= ($cfg['smtp_seguridad'] ?? '') === 'ssl'  ? 'selected' : '' ?>>SSL — Puerto 465</option>
                <option value="none" <?= ($cfg['smtp_seguridad'] ?? '') === 'none' ? 'selected' : '' ?>>Sin cifrado — Puerto 25 (no recomendado)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Usuario SMTP</label>
            <input type="text" name="smtp_usuario" value="<?= e($cfg['smtp_usuario'] ?? '') ?>"
                   autocomplete="off"
                   placeholder="usuario@tudominio.com"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Contraseña SMTP <span class="text-zinc-400">(deja en blanco para no cambiar)</span></label>
            <input type="password" name="smtp_password" value=""
                   autocomplete="new-password"
                   placeholder="••••••••"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Email remitente (From)</label>
            <input type="email" name="smtp_from_email" value="<?= e($cfg['smtp_from_email'] ?? '') ?>"
                   placeholder="notificaciones@tudominio.com"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Nombre remitente</label>
            <input type="text" name="smtp_from_nombre" value="<?= e($cfg['smtp_from_nombre'] ?? 'Bitácora Mantenimiento') ?>"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
        </div>

        <!-- Notas de configuración rápida -->
        <div class="sm:col-span-2">
            <details class="text-xs text-zinc-500 bg-zinc-50 rounded-lg border border-zinc-200 p-3">
                <summary class="cursor-pointer font-medium text-zinc-600">Ayuda rápida por proveedor</summary>
                <div class="mt-2 space-y-1">
                    <p><strong>cPanel / Hosting propio (carnesbacal.com.mx):</strong> Servidor = mail.carnesbacal.com.mx · <strong>Puerto 465 · SSL</strong></p>
                    <p><strong>Gmail:</strong> Servidor = smtp.gmail.com · Puerto 587 · TLS · Usuario = tu@gmail.com · Contraseña = Contraseña de aplicación (requiere 2FA activado en Google)</p>
                    <p><strong>Outlook/Hotmail:</strong> Servidor = smtp.office365.com · Puerto 587 · TLS</p>
                    <p><strong>Yahoo:</strong> Servidor = smtp.mail.yahoo.com · Puerto 587 · TLS · Contraseña de aplicación requerida</p>
                </div>
            </details>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     SECCIÓN: TELEGRAM
════════════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm mb-6">
    <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100">
        <div class="flex items-center gap-2">
            <i data-lucide="send" class="w-5 h-5 text-sky-600"></i>
            <h2 class="font-semibold text-zinc-900">Telegram</h2>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <span class="text-sm text-zinc-500">Activar</span>
            <input type="hidden" name="telegram_activo" value="0">
            <input type="checkbox" name="telegram_activo" value="1" <?= !empty($cfg['telegram_activo']) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500">
        </label>
    </div>
    <div class="p-5 space-y-5">

        <div>
            <label class="block text-xs font-medium text-zinc-600 mb-1">Token del Bot</label>
            <input type="text" name="telegram_bot_token"
                   value="<?= e($cfg['telegram_bot_token'] ?? '') ?>"
                   placeholder="1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghi"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-400">
            <p class="text-xs text-zinc-400 mt-1">El token tiene el formato <code>número:letras</code> y lo entrega @BotFather.</p>
        </div>

        <!-- Instrucciones para crear el bot -->
        <div class="rounded-lg border border-sky-200 bg-sky-50 p-4">
            <p class="text-sm font-semibold text-sky-800 flex items-center gap-2 mb-3">
                <i data-lucide="info" class="w-4 h-4"></i>
                ¿Cómo crear un bot de Telegram?
            </p>
            <ol class="text-sm text-sky-900 space-y-2 list-none">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                    <span>Abre Telegram y busca el contacto <strong>@BotFather</strong> (el oficial tiene palomita azul).</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">2</span>
                    <span>Envíale el comando <code class="bg-white/60 px-1 rounded">/newbot</code> y sigue las instrucciones: elige un nombre y un usuario (debe terminar en <em>bot</em>, ej: <em>BitacoraBacalBot</em>).</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">3</span>
                    <span>@BotFather te responderá con el <strong>token del bot</strong> (una cadena larga). Cópialo y pégalo en el campo de arriba.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">4</span>
                    <span>Cada usuario que quiera recibir notificaciones debe <strong>iniciar una conversación</strong> con el bot (buscar su nombre y hacer click en <em>Iniciar / Start</em>), y luego obtener su Chat ID.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">5</span>
                    <span>Para obtener el Chat ID, el usuario debe enviar cualquier mensaje al bot y luego buscar <strong>@userinfobot</strong> en Telegram — le mostrará su ID numérico. Ese número se ingresa en el perfil de cada usuario.</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-bold">6</span>
                    <span><strong>Importante:</strong> Cada usuario debe enviar al menos un mensaje al bot antes de que el bot pueda escribirle. Si no lo hace, los mensajes fallarán con "chat not found".</span>
                </li>
            </ol>
        </div>

    </div>
</div>

<!-- Botón guardar -->
<div class="flex justify-end mb-6">
    <button type="submit" class="flex items-center gap-2 bg-bacal-700 hover:bg-bacal-800 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
        <i data-lucide="save" class="w-4 h-4"></i> Guardar configuración
    </button>
</div>

</form>

<!-- ═══════════════════════════════════════════════════════════════
     TEST DE CANALES
════════════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
        <i data-lucide="flask-conical" class="w-5 h-5 text-zinc-500"></i>
        <h2 class="font-semibold text-zinc-900">Probar canales</h2>
    </div>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

        <!-- Test email -->
        <form method="post" action="<?= url('admin/notificaciones_config.php') ?>" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="test_email">
            <label class="block text-xs font-medium text-zinc-600">Enviar email de prueba a:</label>
            <div class="flex gap-2">
                <input type="email" name="test_email_dest" value="<?= e($u['email'] ?? '') ?>"
                       placeholder="destinatario@ejemplo.com"
                       class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-bacal-500">
                <button type="submit" class="flex items-center gap-1.5 bg-zinc-700 hover:bg-zinc-800 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> Probar
                </button>
            </div>
        </form>

        <!-- Test Telegram -->
        <form method="post" action="<?= url('admin/notificaciones_config.php') ?>" class="space-y-2">
            <?= csrf_input() ?>
            <input type="hidden" name="op" value="test_telegram">
            <label class="block text-xs font-medium text-zinc-600">Enviar mensaje de prueba al Chat ID:</label>
            <div class="flex gap-2">
                <input type="text" name="test_tg_dest"
                       placeholder="123456789"
                       class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-sky-400">
                <button type="submit" class="flex items-center gap-1.5 bg-sky-600 hover:bg-sky-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i> Probar
                </button>
            </div>
        </form>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     LOG DE ENVÍOS RECIENTES
════════════════════════════════════════════════════════════════ -->
<?php
$logs = db_all(
    "SELECT e.canal, e.tipo, e.asunto, e.estado, e.error_detalle, e.enviado_en,
            COALESCE(u.nombre_completo, u.usuario) dest_nombre
     FROM notificacion_envios e
     LEFT JOIN usuarios u ON e.usuario_id = u.id
     ORDER BY e.enviado_en DESC
     LIMIT 30"
);
?>
<?php if ($logs): ?>
<div class="bg-white rounded-xl border border-zinc-200 shadow-sm mb-6">
    <div class="px-5 py-4 border-b border-zinc-100 flex items-center gap-2">
        <i data-lucide="list-checks" class="w-5 h-5 text-zinc-500"></i>
        <h2 class="font-semibold text-zinc-900">Últimos envíos externos</h2>
        <span class="ml-auto text-xs text-zinc-400">últimos 30</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-zinc-500 text-xs border-b border-zinc-100">
                <tr>
                    <th class="text-left px-4 py-2.5">Canal</th>
                    <th class="text-left px-4 py-2.5">Destinatario</th>
                    <th class="text-left px-4 py-2.5">Asunto</th>
                    <th class="text-left px-4 py-2.5">Estado</th>
                    <th class="text-left px-4 py-2.5">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-zinc-50/50">
                <td class="px-4 py-2">
                    <?php if ($log['canal'] === 'email'): ?>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 bg-blue-50 rounded px-1.5 py-0.5">
                        <i data-lucide="mail" class="w-3 h-3"></i> Email
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-sky-700 bg-sky-50 rounded px-1.5 py-0.5">
                        <i data-lucide="send" class="w-3 h-3"></i> Telegram
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-zinc-600"><?= e($log['dest_nombre'] ?? '') ?></td>
                <td class="px-4 py-2 text-zinc-600 max-w-xs truncate" title="<?= e($log['asunto'] ?? '') ?>"><?= e($log['asunto'] ?? '') ?></td>
                <td class="px-4 py-2">
                    <?php if ($log['estado'] === 'ok'): ?>
                    <span class="text-xs font-medium text-green-700 bg-green-50 rounded px-1.5 py-0.5">OK</span>
                    <?php else: ?>
                    <span class="text-xs font-medium text-red-700 bg-red-50 rounded px-1.5 py-0.5" title="<?= e($log['error_detalle'] ?? '') ?>">Error</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-zinc-400 text-xs"><?= e(fmt_fecha($log['enviado_en'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- /.max-w-3xl -->

<?php require_once __DIR__ . '/../config/footer.php'; ?>
