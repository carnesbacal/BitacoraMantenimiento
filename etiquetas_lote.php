<?php
/**
 * ============================================================================
 * etiquetas_lote.php - Impresión de MÚLTIPLES etiquetas QR juntas
 * ============================================================================
 * ?tipo=equipo|herramienta|refaccion
 *   - Sin ids[]  -> pantalla de selección (marca los artículos que quieras).
 *   - Con ids[]  -> hoja con una etiqueta por artículo (cada una con su QR).
 * Ideal para equipos/herramientas: aprovechas la hoja con etiquetas distintas.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/qr_helpers.php';
require_once __DIR__ . '/config/requisiciones_helpers.php';

requerir_login();
$u = usuario_actual();

$tipo = (string) input('tipo', '');
$cfg  = qr_tipo_cfg($tipo);
if (!$cfg) die('Tipo de artículo no válido.');
if (!qr_disponible($tipo)) die('Falta correr migracion_qr_tokens.sql en la base de datos.');

$col_codigo = $tipo === 'equipo' ? 'codigo_inventario' : 'codigo';
$tiene_sucursal = in_array($tipo, ['equipo', 'herramienta'], true);
$ver_todas = tiene_permiso('ver_todas_sucursales');

// Filtro de sucursal para usuarios que no ven todas (equipo/herramienta tienen sucursal_id)
$suc_where = '';
$suc_params = [];
if ($tiene_sucursal && !$ver_todas) {
    $suc_where = ' AND sucursal_id = :suc';
    $suc_params['suc'] = (int) $u['sucursal_id'];
}

$emp_clave = trim((string) input('empresa', '')) ?: 'bacal';
$empresa   = requisicion_empresa($emp_clave);
$empresas  = requisicion_empresas();

$ids = array_values(array_filter(array_unique(array_map('intval', (array) ($_GET['ids'] ?? [])))));
$ids = array_slice($ids, 0, 200);   // tope de seguridad
$modo_print = !empty($ids);

$items = [];
if ($modo_print) {
    $ph = [];
    $p  = $suc_params;
    foreach ($ids as $k => $vid) { $ph[] = ":i{$k}"; $p["i{$k}"] = $vid; }
    $in = implode(',', $ph);
    $items = db_all(
        "SELECT id, {$col_codigo} AS codigo, nombre, marca, modelo
           FROM {$cfg['tabla']}
          WHERE id IN ({$in}) {$suc_where}
          ORDER BY {$col_codigo}",
        $p
    );
} else {
    $catalogo = db_all(
        "SELECT id, {$col_codigo} AS codigo, nombre
           FROM {$cfg['tabla']}
          WHERE activo = 1 {$suc_where}
          ORDER BY {$col_codigo}",
        $suc_params
    );
}

$archivo_pdf = 'etiquetas_' . $tipo . '_' . date('Ymd_His') . '.pdf';
$volver = $tipo === 'equipo' ? url('admin/equipos.php') : ($tipo === 'herramienta' ? url('herramientas.php') : url('refacciones.php'));
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiquetas · <?= e($cfg['label']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="<?= url('assets/js/qrcode.min.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
tailwind.config = { theme: { extend: { colors: { bacal: { 50:'#fef2f2',100:'#fee2e2',200:'#fecaca',300:'#fca5a5',600:'#dc2626',700:'#c8102e',800:'#a80d26' } } } } };
</script>
<style>
    body { font-family: Arial, Helvetica, sans-serif; }
    .area { padding: 18px; display: flex; flex-wrap: wrap; gap: 5mm; justify-content: center; align-content: flex-start; }
    .etiqueta { width: 90mm; height: 55mm; background: #fff; border: 1px solid #000; border-radius: 3mm; padding: 4mm; display: flex; flex-direction: column; overflow: hidden; color:#18181b; }
    .et-cab { display: flex; align-items: center; min-height: 9mm; border-bottom: 0.4mm solid #e4e4e7; padding-bottom: 1.5mm; }
    .et-cab img { max-height: 8.5mm; max-width: 55mm; object-fit: contain; }
    .et-cab .rs-fb { font-size: 11pt; font-weight: bold; letter-spacing: .3pt; text-transform: uppercase; }
    .et-body { flex: 1; display: flex; gap: 3mm; padding-top: 2mm; min-height: 0; }
    .et-txt { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .et-tipo { font-size: 7pt; font-weight: bold; letter-spacing: .6pt; text-transform: uppercase; color: #c8102e; }
    .et-nombre { font-size: 12.5pt; font-weight: bold; line-height: 1.12; margin-top: 1mm; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .et-sub { font-size: 8.5pt; color: #52525b; margin-top: 1mm; line-height: 1.15; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .et-codigo { margin-top: auto; font-family: 'Courier New', monospace; font-size: 15pt; font-weight: bold; letter-spacing: .5pt; }
    .et-qr { width: 26mm; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; }
    .et-qr .box { width: 25mm; height: 25mm; }
    .et-qr .box svg { width: 100% !important; height: 100% !important; display: block; }
    .et-qr .cap { font-size: 6pt; color: #52525b; text-align: center; margin-top: 0.6mm; line-height: 1.1; }
    .et-pie { border-top: 0.4mm solid #e4e4e7; margin-top: 1.5mm; padding-top: 1mm; font-size: 6.5pt; color: #71717a; text-transform: uppercase; letter-spacing: .3pt; }
    @media print { @page { margin: 8mm; } .noprint { display: none !important; } .area { padding: 0; gap: 4mm; } .etiqueta { page-break-inside: avoid; } * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
</style>
</head>
<body class="bg-zinc-100">

<?php if ($modo_print): ?>
<!-- ======================= MODO IMPRESIÓN ======================= -->
<div class="noprint bg-white border-b border-zinc-300 px-4 py-2.5 flex items-center gap-2 flex-wrap sticky top-0 z-10">
    <a href="<?= url('etiquetas_lote.php?tipo=' . rawurlencode($tipo) . '&empresa=' . rawurlencode($emp_clave)) ?>"
       class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-700 hover:bg-zinc-50">← Elegir otros</a>
    <button onclick="window.print()" class="px-3 py-2 rounded-lg border border-zinc-300 text-sm text-zinc-700 hover:bg-zinc-50">Imprimir</button>
    <button onclick="descargarPDF()" class="px-3 py-2 rounded-lg bg-[#E94E1B] text-white text-sm font-bold">Descargar PDF</button>
    <div class="flex items-center gap-1 border border-zinc-200 rounded-lg p-1 ml-1">
        <span class="text-[11px] text-zinc-500 px-1.5">Razón social</span>
        <?php foreach ($empresas as $k => $emp): ?>
        <a href="<?= url('etiquetas_lote.php?tipo=' . rawurlencode($tipo) . '&empresa=' . rawurlencode($k) . '&' . http_build_query(['ids' => $ids])) ?>"
           class="px-2 py-1 rounded text-sm <?= $emp_clave === $k ? 'bg-[#E94E1B] text-white font-bold' : 'text-zinc-700' ?>"><?= e($emp['corto']) ?></a>
        <?php endforeach; ?>
    </div>
    <span class="ml-auto text-xs text-zinc-500"><?= count($items) ?> etiqueta(s)</span>
</div>

<?php if (empty($items)): ?>
<div class="p-10 text-center text-zinc-500">No se encontraron artículos para las etiquetas seleccionadas.</div>
<?php else: ?>
<div class="area" id="area">
    <?php foreach ($items as $it):
        $token = qr_token_de($tipo, (int) $it['id']);
        $url_pub = $token ? qr_url_publica($tipo, $token) : '';
        $sub = trim(((string) $it['marca']) . ' ' . ((string) $it['modelo']));
    ?>
    <div class="etiqueta">
        <div class="et-cab">
            <img src="<?= url($empresa['logo']) ?>" alt=""
                 onerror="this.style.display='none';var t=this.parentNode.querySelector('.rs-fb');if(t)t.style.display='block';">
            <span class="rs-fb" style="display:none"><?= e($empresa['corto']) ?></span>
        </div>
        <div class="et-body">
            <div class="et-txt">
                <div class="et-tipo"><?= e($cfg['label']) ?></div>
                <div class="et-nombre"><?= e($it['nombre']) ?></div>
                <?php if ($sub !== ''): ?><div class="et-sub"><?= e($sub) ?></div><?php endif; ?>
                <div class="et-codigo"><?= e($it['codigo']) ?></div>
            </div>
            <div class="et-qr">
                <div class="box qr-box" data-url="<?= e($url_pub) ?>"></div>
                <div class="cap">Escanea para<br>ver información</div>
            </div>
        </div>
        <div class="et-pie">Propiedad de <?= e($empresa['corto']) ?> · Uso interno</div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function () {
    var boxes = document.querySelectorAll('.qr-box');
    for (var i = 0; i < boxes.length; i++) {
        var u = boxes[i].getAttribute('data-url');
        if (!u) continue;
        try { var q = qrcode(0, 'M'); q.addData(u); q.make(); boxes[i].innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }); }
        catch (e) {}
    }
})();
function descargarPDF() {
    var el = document.getElementById('area');
    if (typeof html2pdf === 'undefined' || !el) { window.print(); return; }
    html2pdf().set({
        margin: 6, filename: <?= json_encode($archivo_pdf) ?>,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 3, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
    }).from(el).save().catch(function () { window.print(); });
}
</script>

<?php else: ?>
<!-- ======================= MODO SELECCIÓN ======================= -->
<div class="max-w-3xl mx-auto p-4 sm:p-6">
    <div class="flex items-center gap-3 mb-4">
        <a href="<?= e($volver) ?>" class="p-2 rounded-lg hover:bg-zinc-200 text-zinc-500">←</a>
        <div>
            <h1 class="font-display text-xl font-extrabold text-zinc-900">Imprimir etiquetas · <?= e($cfg['label']) ?>s</h1>
            <p class="text-xs text-zinc-500">Marca los artículos que quieras y se imprimen juntos, uno por etiqueta.</p>
        </div>
    </div>

    <form method="GET" id="frm">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
            <div class="p-3 border-b border-zinc-100 flex flex-wrap items-center gap-2">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" id="buscar" placeholder="Filtrar por código o nombre…"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 text-sm focus:outline-none focus:border-bacal-700">
                </div>
                <div class="flex items-center gap-1 border border-zinc-200 rounded-lg p-1">
                    <span class="text-[11px] text-zinc-500 px-1.5">Razón social</span>
                    <select name="empresa" class="text-sm px-2 py-1 rounded bg-white">
                        <?php foreach ($empresas as $k => $emp): ?>
                        <option value="<?= e($k) ?>" <?= $emp_clave === $k ? 'selected' : '' ?>><?= e($emp['corto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" onclick="marcarTodos(true)" class="px-2.5 py-1.5 rounded-lg border border-zinc-300 text-xs text-zinc-700 hover:bg-zinc-50">Todos</button>
                <button type="button" onclick="marcarTodos(false)" class="px-2.5 py-1.5 rounded-lg border border-zinc-300 text-xs text-zinc-700 hover:bg-zinc-50">Ninguno</button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto divide-y divide-zinc-100" id="lista">
                <?php if (empty($catalogo)): ?>
                <div class="px-4 py-10 text-center text-sm text-zinc-500">No hay artículos activos.</div>
                <?php else: foreach ($catalogo as $c): ?>
                <label class="fila flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 cursor-pointer"
                       data-txt="<?= e(mb_strtolower($c['codigo'] . ' ' . $c['nombre'])) ?>">
                    <input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" onchange="actualizar()"
                           class="chk w-4 h-4 rounded border-zinc-300 text-bacal-700 focus:ring-bacal-500">
                    <span class="font-mono text-xs font-bold text-zinc-500 w-32 shrink-0 truncate"><?= e($c['codigo']) ?></span>
                    <span class="text-sm text-zinc-800 truncate"><?= e($c['nombre']) ?></span>
                </label>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="sticky bottom-0 mt-3">
            <button type="submit" id="btnVer" disabled
                    class="w-full px-4 py-3 rounded-xl bg-bacal-700 hover:bg-bacal-800 disabled:bg-zinc-300 text-white text-sm font-semibold shadow">
                Ver etiquetas (<span id="cuenta">0</span>)
            </button>
        </div>
    </form>
</div>

<script>
function actualizar() {
    var n = document.querySelectorAll('.chk:checked').length;
    document.getElementById('cuenta').textContent = n;
    document.getElementById('btnVer').disabled = (n === 0);
}
function marcarTodos(v) {
    document.querySelectorAll('.fila').forEach(function (f) {
        if (f.style.display === 'none') return;           // respeta el filtro
        var c = f.querySelector('.chk'); if (c) c.checked = v;
    });
    actualizar();
}
document.getElementById('buscar').addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.fila').forEach(function (f) {
        f.style.display = (!q || f.getAttribute('data-txt').indexOf(q) !== -1) ? '' : 'none';
    });
});
actualizar();
</script>
<?php endif; ?>

</body>
</html>
