<?php
/**
 * ============================================================================
 * requisicion_imprimir.php - Vista imprimible / PDF de una requisición
 * ============================================================================
 * ?formato=controlado  -> formato oficial 0069-FRM Rev. B / ECO. 013
 * ?formato=simple      -> formato corto (Descripción · Cantidad · Unidad)
 *
 * Página independiente (sin menú lateral) para que el papel salga idéntico.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/refacciones_helpers.php';
require_once __DIR__ . '/config/requisiciones_helpers.php';

requerir_login();
$u = usuario_actual();

if (!requisiciones_disponible()) {
    die('Falta correr migracion_requisiciones.sql.');
}

$id  = (int) input('id', 0);
$req = requisicion_obtener($id);
if (!$req) die('Requisición no encontrada.');

if (!tiene_permiso('ver_todas_sucursales') && (int) $req['sucursal_id'] !== (int) $u['sucursal_id']) {
    die('Sin acceso a esta requisición.');
}

$formato = input('formato', 'controlado') === 'simple' ? 'simple' : 'controlado';
$emp_clave = trim((string) input('empresa', '')) ?: (string) ($req['razon_social'] ?? 'corral');
$empresa   = requisicion_empresa($emp_clave);
$empresas  = requisicion_empresas();
$items   = requisicion_items($id);
$unidades = unidades_medida();

// Mínimo de renglones en blanco para que el formato luzca como el papel
$min_filas = $formato === 'controlado' ? 12 : 8;
$filas_vacias = max(0, $min_filas - count($items));

$archivo_pdf = 'requisicion_' . $req['folio'] . '.pdf';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Requisición <?= e($req['folio']) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #f4f4f5; font-family: Arial, Helvetica, sans-serif; color: #18181b; }
    .barra { background: #fff; border-bottom: 1px solid #d4d4d8; padding: 10px 16px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .barra a, .barra button { font: inherit; font-size: 13px; padding: 7px 12px; border-radius: 8px; border: 1px solid #d4d4d8; background: #fff; color: #3f3f46; cursor: pointer; text-decoration: none; }
    .barra .primario { background: #E94E1B; border-color: #E94E1B; color: #fff; font-weight: 700; }
    .barra .sep { margin-left: auto; color: #71717a; font-size: 12px; }

    .hoja { background: #fff; width: 216mm; min-height: 279mm; margin: 16px auto; padding: 14mm 12mm; box-shadow: 0 1px 6px rgba(0,0,0,.15); }
    table { border-collapse: collapse; width: 100%; }
    .cab td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
    .cab .empresa { text-align: center; font-weight: bold; font-size: 13px; letter-spacing: .3px; }
    .cab .depto { background: #6b7280; color: #fff; text-align: center; font-weight: bold; letter-spacing: 1px; font-size: 13px; }
    .cab .doc { text-align: center; font-size: 10px; }
    .cab .titulo { text-align: center; font-weight: bold; font-size: 13px; font-style: italic; }
    .cab .etq { text-align: center; font-size: 10px; }
    .cab img { max-height: 42px; display: block; margin: 0 auto; }

    .campos { margin: 14px 0 10px; font-size: 12px; }
    .campos td { padding: 4px 0; }
    .linea { border-bottom: 1px solid #000; display: inline-block; min-width: 180px; padding: 0 6px; }

    .items { font-size: 11px; }
    .items th { background: #6b7280; color: #fff; border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
    .items td { border: 1px solid #000; padding: 5px 6px; height: 20px; }
    .items .c { text-align: center; }
    .items .d { text-align: left; }

    .folio { font-family: 'Courier New', monospace; font-weight: bold; font-size: 12px; border: 1px solid #000; padding: 2px 7px; display: inline-block; letter-spacing: .5px; }
    .firma { margin-top: 26px; font-size: 12px; text-align: center; }
    .firma .linea { min-width: 260px; }

    /* Formato simple */
    .simple-cab { display: flex; align-items: center; gap: 14px; margin-bottom: 6px; }
    .simple-cab img { max-height: 52px; }
    .simple-cab .t1 { font-size: 20px; font-weight: bold; letter-spacing: .5px; }
    .simple-cab .t2 { font-size: 15px; font-style: italic; color: #52525b; }

    @media print {
        @page { size: letter portrait; margin: 10mm; }
        .barra { display: none !important; }
        body { background: #fff; }
        .hoja { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        tr, table { break-inside: avoid; }
    }
</style>
</head>
<body>

<div class="barra">
    <a href="<?= url('requisicion_ver.php?id=' . $id) ?>">← Volver</a>
    <button onclick="window.print()">Imprimir</button>
    <button class="primario" onclick="descargarPDF()">Descargar PDF</button>
    <a href="<?= url('requisicion_imprimir.php?id=' . $id . '&formato=controlado&empresa=' . urlencode($emp_clave)) ?>"
       style="<?= $formato === 'controlado' ? 'background:#f4f4f5;font-weight:700' : '' ?>">Formato 0069-FRM</a>
    <a href="<?= url('requisicion_imprimir.php?id=' . $id . '&formato=simple&empresa=' . urlencode($emp_clave)) ?>"
       style="<?= $formato === 'simple' ? 'background:#f4f4f5;font-weight:700' : '' ?>">Formato simple</a>
    <span style="width:1px;height:22px;background:#e4e4e7"></span>
    <?php foreach ($empresas as $k => $emp): ?>
    <a href="<?= url('requisicion_imprimir.php?id=' . $id . '&formato=' . $formato . '&empresa=' . urlencode($k)) ?>"
       style="<?= $emp_clave === $k ? 'background:#f4f4f5;font-weight:700' : '' ?>"><?= e($emp['corto']) ?></a>
    <?php endforeach; ?>
    <span class="sep">Folio <?= e($req['folio']) ?></span>
</div>

<div class="hoja" id="hoja" data-pdf="<?= e($archivo_pdf) ?>">

<?php if ($formato === 'controlado'): ?>
    <!-- ============ FORMATO CONTROLADO 0069-FRM ============ -->
    <table class="cab">
        <tr>
            <td rowspan="3" style="width:16%">
                <img src="<?= url($empresa['logo']) ?>" alt="" onerror="this.style.display='none'">
            </td>
            <td class="empresa" colspan="2"><?= e($empresa['nombre']) ?></td>
            <td class="depto" colspan="2">MANTENIMIENTO</td>
        </tr>
        <tr>
            <td class="etq" style="width:12%">Elaboró</td>
            <td class="titulo" rowspan="2">Requisición p/compras mantenimiento</td>
            <td class="doc" colspan="2">0069-FRM-Requisición compras mtto</td>
        </tr>
        <tr>
            <td class="etq" style="font-family:'Courier New',monospace;font-weight:bold">&lt;LFRC/&gt;</td>
            <td class="doc" style="width:14%">Rev. B</td>
            <td class="doc" style="width:14%">ECO. 013</td>
        </tr>
    </table>

    <table class="campos">
        <tr>
            <td style="width:34%"><strong>Sucursal:</strong> <span class="linea" style="min-width:130px"><?= e($req['sucursal_nombre']) ?></span></td>
            <td style="width:21%"><strong>Fecha:</strong> <span class="linea" style="min-width:95px"><?= e(fmt_fecha($req['fecha'], false)) ?></span></td>
            <td style="width:27%"><strong>Solicitó:</strong> <span class="linea" style="min-width:120px"><?= e($req['solicito_nombre']) ?></span></td>
            <td style="width:18%;text-align:right"><strong>Folio:</strong> <span class="folio"><?= e($req['folio']) ?></span></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:11%">Cantidad</th>
                <th style="width:12%">Unidad</th>
                <th>Descripción</th>
                <th style="width:14%">Área</th>
                <th style="width:11%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td class="c"><?= rtrim(rtrim(number_format((float) $it['cantidad'], 2), '0'), '.') ?></td>
                <td class="c"><?= e($unidades[$it['unidad']] ?? (string) $it['unidad']) ?></td>
                <td class="d"><?= e($it['descripcion']) ?></td>
                <td class="c"><?= e((string) $it['area_nombre']) ?></td>
                <td class="c"><?= e(ucfirst((string) $it['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $filas_vacias; $i++): ?>
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="firma">
        <strong>Autorizó:</strong>
        <span class="linea"><?= e((string) $req['autorizo_nombre']) ?></span>
    </div>

<?php else: ?>
    <!-- ============ FORMATO SIMPLE ============ -->
    <div class="simple-cab">
        <img src="<?= url($empresa['logo']) ?>" alt="" onerror="this.style.display='none'">
        <div>
            <div class="t1"><?= e(mb_strtoupper($empresa['corto'], 'UTF-8')) ?></div>
            <div class="t2">Requisición</div>
        </div>
    </div>

    <table class="campos">
        <tr>
            <td style="font-size:13px;font-weight:bold;text-transform:uppercase"><?= e($req['sucursal_nombre']) ?></td>
            <td style="text-align:right"><strong>Folio:</strong> <span class="folio"><?= e($req['folio']) ?></span></td>
        </tr>
        <tr>
            <td style="width:55%"><strong>SOLICITÓ:</strong> <span class="linea"><?= e($req['solicito_nombre']) ?></span></td>
            <td><strong>FECHA:</strong> <span class="linea" style="min-width:120px"><?= e(fmt_fecha($req['fecha'], false)) ?></span></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                <th style="width:14%">CANTIDAD</th>
                <th style="width:14%">UNIDAD</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td class="d"><?= e($it['descripcion']) ?></td>
                <td class="c"><?= rtrim(rtrim(number_format((float) $it['cantidad'], 2), '0'), '.') ?></td>
                <td class="c"><?= e($unidades[$it['unidad']] ?? (string) $it['unidad']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $filas_vacias; $i++): ?>
            <tr><td>&nbsp;</td><td></td><td></td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <table style="margin-top:40px;font-size:12px;border:0">
        <tr>
            <td style="text-align:left;vertical-align:bottom;border:0;font-size:10px;color:#52525b">
                Elaboró: <span style="font-family:'Courier New',monospace;font-weight:bold;color:#18181b">&lt;LFRC/&gt;</span>
            </td>
            <td style="text-align:right;border:0">
                <strong>AUTORIZÓ:</strong>
                <span class="linea"><?= e((string) $req['autorizo_nombre']) ?></span>
            </td>
        </tr>
    </table>
<?php endif; ?>

</div>

<script>
function descargarPDF() {
    var el = document.getElementById('hoja');
    if (typeof html2pdf === 'undefined' || !el) { window.print(); return; }
    var opt = {
        margin:      [8, 8, 8, 8],
        filename:    el.getAttribute('data-pdf') || 'requisicion.pdf',
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF:       { unit: 'mm', format: 'letter', orientation: 'portrait' },
        pagebreak:   { mode: ['css', 'legacy'], avoid: ['tr'] }
    };
    html2pdf().set(opt).from(el).save().catch(function () { window.print(); });
}
</script>
</body>
</html>
