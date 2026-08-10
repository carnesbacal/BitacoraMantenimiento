<?php
/**
 * ============================================================================
 * etiqueta.php - Etiqueta imprimible con QR para un artículo
 * ============================================================================
 * ?tipo=equipo|herramienta|refaccion & id=ID [ & empresa=corral|bacal ] [ & copias=N ]
 * El QR lleva a la vista pública info.php (sin login). Requiere sesión.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/qr_helpers.php';
require_once __DIR__ . '/config/requisiciones_helpers.php';   // razones sociales + logos

requerir_login();
$u = usuario_actual();

$tipo = (string) input('tipo', '');
$id   = (int) input('id', 0);
$cfg  = qr_tipo_cfg($tipo);
if (!$cfg) die('Tipo de artículo no válido.');
if (!qr_disponible($tipo)) die('Falta correr migracion_qr_tokens.sql en la base de datos.');

$col_codigo = $tipo === 'equipo' ? 'codigo_inventario' : 'codigo';
$row = db_one("SELECT id, {$col_codigo} AS codigo, nombre, marca, modelo FROM {$cfg['tabla']} WHERE id = :id", ['id' => $id]);
if (!$row) die('Artículo no encontrado.');

$subtitulo = trim(((string) $row['marca']) . ' ' . ((string) $row['modelo']));

$token       = qr_token_de($tipo, $id);
if (!$token) die('No se pudo generar el token del artículo.');
$url_publica = qr_url_publica($tipo, $token);

$emp_clave = trim((string) input('empresa', '')) ?: 'bacal';
$empresa   = requisicion_empresa($emp_clave);
$empresas  = requisicion_empresas();

$copias = max(1, min(60, (int) input('copias', 1)));
$archivo_pdf = 'etiqueta_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $row['codigo']) . '.pdf';

// URL base para los enlaces (conserva tipo/id/empresa y cambia copias)
$link_copias = fn(int $n) => url('etiqueta.php?tipo=' . rawurlencode($tipo) . '&id=' . $id . '&empresa=' . rawurlencode($emp_clave) . '&copias=' . $n);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiqueta <?= e($row['codigo']) ?></title>
<script src="<?= url('assets/js/qrcode.min.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #f4f4f5; font-family: Arial, Helvetica, sans-serif; color: #18181b; }
    .barra { background: #fff; border-bottom: 1px solid #d4d4d8; padding: 10px 16px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .barra a, .barra button { font: inherit; font-size: 13px; padding: 7px 10px; border-radius: 8px; border: 1px solid #d4d4d8; background: #fff; color: #3f3f46; cursor: pointer; text-decoration: none; }
    .barra .primario { background: #E94E1B; border-color: #E94E1B; color: #fff; font-weight: 700; }
    .barra .grp { display: flex; align-items: center; gap: 4px; border: 1px solid #e4e4e7; border-radius: 8px; padding: 3px; }
    .barra .grp .lbl { font-size: 11px; color: #71717a; padding: 0 6px; }
    .barra .grp a { border: 0; padding: 5px 9px; }
    .barra .grp a.on { background: #E94E1B; color: #fff; font-weight: 700; }
    .barra .sep { margin-left: auto; color: #71717a; font-size: 12px; }

    .area { padding: 18px; display: flex; flex-wrap: wrap; gap: 5mm; justify-content: center; align-content: flex-start; }

    .etiqueta {
        width: 90mm; height: 55mm; background: #fff; border: 1px solid #000; border-radius: 3mm;
        padding: 4mm; display: flex; flex-direction: column; overflow: hidden;
    }
    .et-cab { display: flex; align-items: center; min-height: 9mm; border-bottom: 0.4mm solid #e4e4e7; padding-bottom: 1.5mm; }
    .et-cab img { max-height: 8.5mm; max-width: 55mm; object-fit: contain; }
    .et-cab .rs-fb { font-size: 11pt; font-weight: bold; letter-spacing: .3pt; text-transform: uppercase; }

    .et-body { flex: 1; display: flex; gap: 3mm; padding-top: 2mm; min-height: 0; }
    .et-txt { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .et-tipo { font-size: 7pt; font-weight: bold; letter-spacing: .6pt; text-transform: uppercase; color: #c8102e; }
    .et-nombre { font-size: 12.5pt; font-weight: bold; line-height: 1.12; margin-top: 1mm;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .et-sub { font-size: 8.5pt; color: #52525b; margin-top: 1mm; line-height: 1.15;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .et-codigo { margin-top: auto; font-family: 'Courier New', monospace; font-size: 15pt; font-weight: bold; letter-spacing: .5pt; }

    .et-qr { width: 26mm; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; }
    .et-qr .box { width: 25mm; height: 25mm; }
    .et-qr .box svg { width: 100% !important; height: 100% !important; display: block; }
    .et-qr .cap { font-size: 6pt; color: #52525b; text-align: center; margin-top: 0.6mm; line-height: 1.1; }

    .et-pie { border-top: 0.4mm solid #e4e4e7; margin-top: 1.5mm; padding-top: 1mm; font-size: 6.5pt; color: #71717a; text-transform: uppercase; letter-spacing: .3pt; }

    @media print {
        @page { margin: 8mm; }
        .barra { display: none !important; }
        body { background: #fff; }
        .area { padding: 0; gap: 4mm; }
        .etiqueta { page-break-inside: avoid; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>
</head>
<body>

<div class="barra">
    <a href="<?= url($cfg['ficha'] . '?id=' . $id) ?>">← Volver</a>
    <button onclick="window.print()">Imprimir</button>
    <button class="primario" onclick="descargarPDF()">Descargar PDF</button>

    <div class="grp">
        <span class="lbl">Copias</span>
        <?php foreach ([1, 6, 10, 20, 40] as $n): ?>
        <a href="<?= $link_copias($n) ?>" class="<?= $copias === $n ? 'on' : '' ?>"><?= $n ?></a>
        <?php endforeach; ?>
    </div>

    <div class="grp">
        <span class="lbl">Razón social</span>
        <?php foreach ($empresas as $k => $emp): ?>
        <a href="<?= url('etiqueta.php?tipo=' . rawurlencode($tipo) . '&id=' . $id . '&empresa=' . rawurlencode($k) . '&copias=' . $copias) ?>"
           class="<?= $emp_clave === $k ? 'on' : '' ?>"><?= e($emp['corto']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($tipo !== 'refaccion'): ?>
    <a href="<?= url('etiquetas_lote.php?tipo=' . rawurlencode($tipo)) ?>" title="Imprimir etiquetas de varios artículos distintos">Varios distintos…</a>
    <?php endif; ?>
    <span class="sep"><?= e($cfg['label']) ?> · <?= e($row['codigo']) ?> · <?= (int) $copias ?> etiqueta(s)</span>
</div>

<div class="area" id="area">
    <?php for ($i = 0; $i < $copias; $i++): ?>
    <div class="etiqueta">
        <div class="et-cab">
            <img src="<?= url($empresa['logo']) ?>" alt=""
                 onerror="this.style.display='none';var t=this.parentNode.querySelector('.rs-fb');if(t)t.style.display='block';">
            <span class="rs-fb" style="display:none"><?= e($empresa['corto']) ?></span>
        </div>
        <div class="et-body">
            <div class="et-txt">
                <div class="et-tipo"><?= e($cfg['label']) ?></div>
                <div class="et-nombre"><?= e($row['nombre']) ?></div>
                <?php if ($subtitulo !== ''): ?>
                <div class="et-sub"><?= e($subtitulo) ?></div>
                <?php endif; ?>
                <div class="et-codigo"><?= e($row['codigo']) ?></div>
            </div>
            <div class="et-qr">
                <div class="box qr-box"></div>
                <div class="cap">Escanea para<br>ver información</div>
            </div>
        </div>
        <div class="et-pie">Propiedad de <?= e($empresa['corto']) ?> · Uso interno</div>
    </div>
    <?php endfor; ?>
</div>

<script>
(function () {
    var url = <?= json_encode($url_publica) ?>;
    var svg = '';
    try {
        var qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        svg = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    } catch (e) { svg = ''; }
    var boxes = document.querySelectorAll('.qr-box');
    for (var i = 0; i < boxes.length; i++) boxes[i].innerHTML = svg;
})();

function descargarPDF() {
    var el = document.getElementById('area');
    if (typeof html2pdf === 'undefined' || !el) { window.print(); return; }
    var opt = {
        margin:      6,
        filename:    <?= json_encode($archivo_pdf) ?>,
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 3, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF:       { unit: 'mm', format: 'letter', orientation: 'portrait' },
        pagebreak:   { mode: ['css', 'legacy'] }
    };
    html2pdf().set(opt).from(el).save().catch(function () { window.print(); });
}
</script>
</body>
</html>
