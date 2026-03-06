<?php
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['folio']) || empty($data['items']) || !is_array($data['items'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Datos insuficientes para generar ticket.'
    ]);
    exit;
}

$folio = (int)$data['folio'];
$items = $data['items'];
$metodo = trim((string)($data['metodo'] ?? 'Efectivo'));
$factura = !empty($data['factura']) ? 'SI' : 'NO';
$total = (float)($data['total'] ?? 0);
$ivaRate = isset($data['iva_rate']) ? (float)$data['iva_rate'] : 0.16;
$ivaRate = max(0, $ivaRate);
$fecha = date('Y-m-d H:i:s');

if ($total <= 0) {
    foreach ($items as $item) {
        $total += (float)($item['precio'] ?? 0);
    }
}

$subtotalSinIva = $ivaRate > 0 ? round($total / (1 + $ivaRate), 2) : round($total, 2);
$ivaMonto = round($total - $subtotalSinIva, 2);

$ticketDirFs = __DIR__ . '/../img/ventas';
if (!is_dir($ticketDirFs)) {
    mkdir($ticketDirFs, 0777, true);
}

$filename = sprintf('ticket_folio_%04d_%s.svg', $folio, date('Ymd_His'));
$fullPath = $ticketDirFs . '/' . $filename;
$publicPath = 'img/ventas/' . $filename;

$svgWidth = 420;
$lineHeight = 22;
$startY = 140;
$maxRows = 24;
$rows = array_slice($items, 0, $maxRows);
$svgHeight = 220 + (count($rows) * $lineHeight) + 120;

$xmlEsc = static function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
};

$lines = [];
$y = $startY;
foreach ($rows as $idx => $item) {
    $nombre = $xmlEsc((string)($item['nombre'] ?? 'Producto'));
    $tam = $xmlEsc((string)($item['tamano'] ?? ''));
    $precio = number_format((float)($item['precio'] ?? 0), 2);

    $lines[] = '<text x="20" y="' . $y . '" class="item">' . ($idx + 1) . '. ' . $nombre . '</text>';
    $lines[] = '<text x="20" y="' . ($y + 16) . '" class="sub">Tam: ' . $tam . '</text>';
    $lines[] = '<text x="340" y="' . ($y + 16) . '" class="sub">$' . $precio . '</text>';
    $lines[] = '<line x1="20" y1="' . ($y + 22) . '" x2="400" y2="' . ($y + 22) . '" stroke="#e5e7eb"/>';
    $y += $lineHeight + 20;
}

$totalY = $svgHeight - 90;
$svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
'<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '">' .
'<rect width="100%" height="100%" fill="#ffffff"/>' .
'<text x="20" y="36" class="title">ADEENCA POS</text>' .
'<text x="20" y="60" class="meta">Ticket de venta (boceto)</text>' .
'<line x1="20" y1="74" x2="400" y2="74" stroke="#cbd5e1"/>' .
'<text x="20" y="96" class="meta">Folio: ' . $folio . '</text>' .
'<text x="20" y="116" class="meta">Fecha: ' . $xmlEsc($fecha) . '</text>' .
'<text x="260" y="96" class="meta">Metodo: ' . $xmlEsc($metodo) . '</text>' .
'<text x="260" y="116" class="meta">Factura: ' . $factura . '</text>' .
implode('', $lines) .
'<line x1="20" y1="' . ($totalY - 20) . '" x2="400" y2="' . ($totalY - 20) . '" stroke="#111827"/>' .
'<text x="20" y="' . ($totalY + 4) . '" class="meta">Subtotal</text>' .
'<text x="320" y="' . ($totalY + 4) . '" class="meta">$' . number_format($subtotalSinIva, 2) . '</text>' .
'<text x="20" y="' . ($totalY + 26) . '" class="meta">IVA (' . number_format($ivaRate * 100, 0) . '%)</text>' .
'<text x="320" y="' . ($totalY + 26) . '" class="meta">$' . number_format($ivaMonto, 2) . '</text>' .
'<text x="20" y="' . ($totalY + 56) . '" class="total">TOTAL</text>' .
'<text x="320" y="' . ($totalY + 56) . '" class="total">$' . number_format($total, 2) . '</text>' .
'<text x="20" y="' . ($totalY + 84) . '" class="footer">Guardado en /img/ventas</text>' .
'<style>
    .title { font: 700 24px Arial, sans-serif; fill: #0f172a; }
    .meta { font: 400 13px Arial, sans-serif; fill: #334155; }
    .item { font: 700 13px Arial, sans-serif; fill: #111827; }
    .sub { font: 400 12px Arial, sans-serif; fill: #475569; }
    .total { font: 700 20px Arial, sans-serif; fill: #0369a1; }
    .footer { font: 400 12px Arial, sans-serif; fill: #64748b; }
</style>' .
'</svg>';

$ok = file_put_contents($fullPath, $svg);
if ($ok === false) {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo guardar el ticket en disco.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'file' => $filename,
    'path' => $publicPath,
    'url' => $publicPath
], JSON_UNESCAPED_UNICODE);
