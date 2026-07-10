<?php
/**
 * ============================================================================
 * config/monsat_correo.example.php
 * ----------------------------------------------------------------------------
 * PLANTILLA. Copia este archivo a  config/monsat_correo.php  y pon los datos
 * reales del buzón que recibe los reportes de Monsat. El archivo real NO se
 * sube al repositorio (agrégalo a .gitignore).
 * ============================================================================
 */
return [
    // Servidor IMAP del correo (en cPanel suele ser mail.TUDOMINIO o el hostname del servidor).
    'host'           => 'mail.granodeoro.com.mx',
    'port'           => 993,                         // 993 = IMAP con SSL
    'user'           => 'monsat@granodeoro.com.mx',  // el buzón dedicado
    'pass'           => 'PON_AQUI_LA_CONTRASENA',
    'folder'         => 'INBOX',

    // Solo procesar correos NO leídos (recomendado) y marcarlos como leídos al terminar.
    'solo_no_leidos' => true,
    'marcar_leidos'  => true,

    // (Opcional) filtrar por remitente. Deja '' para no filtrar.
    'remitente'      => '',   // p. ej. 'monsat.com.mx'

    // Token para poder dispararlo por web (si el cron usa wget/curl en vez de CLI).
    // Deja '' para permitir SOLO ejecución por línea de comandos (más seguro).
    'token'          => '',
];
