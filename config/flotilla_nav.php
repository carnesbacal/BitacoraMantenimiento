<?php
/**
 * config/flotilla_nav.php
 * Barra de navegación horizontal para todas las páginas del módulo Flotilla.
 * Incluir justo después de config/header.php en cada página flotilla_*.php
 *
 * Requiere que $pagina_activa esté definida con el valor específico de la página
 * (ej: 'flotilla_vehiculos', 'flotilla_conductores', etc.)
 */

$_fnav = [
    'flotilla_vehiculos'    => ['flotilla_vehiculos.php',    'car',              'Vehículos'],
    'flotilla_conductores'  => ['flotilla_conductores.php',  'user-check',       'Conductores'],
    'flotilla_combustible'  => ['flotilla_combustible.php',  'fuel',             'Combustible'],
    'flotilla_mantenimiento'=> ['flotilla_mantenimiento.php','wrench',           'Mantenimiento'],
    'flotilla_documentos'   => ['flotilla_documentos.php',   'file-check',       'Documentos'],
    'flotilla_checklist'    => ['flotilla_checklist.php',    'clipboard-check',  'Checklist'],
    'flotilla_siniestros'   => ['flotilla_siniestros.php',   'shield-alert',     'Siniestros'],
    'flotilla_multas'       => ['flotilla_multas.php',       'ticket-x',         'Multas'],
    'flotilla_reportes'     => ['flotilla_reportes.php',     'bar-chart-2',      'Reportes'],
];

// Links de importación (solo admins)
$_fnav_import = tiene_permiso('administrar') ? [
    'flotilla_importar_xiga'      => ['flotilla_importar_xiga.php',      'upload',    'Import. XIGA'],
    'flotilla_importar_vehiculos' => ['flotilla_importar_vehiculos.php', 'truck',     'Import. Flotilla'],
] : [];
?>
<nav class="flex items-center gap-1 overflow-x-auto pb-0.5 mb-5 border-b border-zinc-200 -mt-1" aria-label="Módulo Flotilla">
    <?php foreach ($_fnav as $key => [$archivo, $icono, $etiqueta]): ?>
    <?php $activo = ($pagina_activa === $key); ?>
    <a href="<?= url($archivo) ?>"
       class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors
              <?= $activo
                  ? 'border-bacal-700 text-bacal-700'
                  : 'border-transparent text-zinc-500 hover:text-zinc-900 hover:border-zinc-300' ?>">
        <i data-lucide="<?= $icono ?>" class="w-3.5 h-3.5"></i>
        <?= $etiqueta ?>
    </a>
    <?php endforeach; ?>
    <?php if ($_fnav_import): ?>
    <span class="w-px h-5 bg-zinc-200 mx-1 self-center shrink-0"></span>
    <?php foreach ($_fnav_import as $key => [$archivo, $icono, $etiqueta]): ?>
    <?php $activo = ($pagina_activa === $key); ?>
    <a href="<?= url($archivo) ?>"
       class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors
              <?= $activo
                  ? 'border-bacal-700 text-bacal-700'
                  : 'border-transparent text-zinc-400 hover:text-zinc-700 hover:border-zinc-300' ?>"
       title="Solo administradores">
        <i data-lucide="<?= $icono ?>" class="w-3.5 h-3.5"></i>
        <?= $etiqueta ?>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</nav>
