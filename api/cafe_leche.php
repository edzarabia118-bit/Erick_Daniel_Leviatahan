<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

$res = $conn->query("SELECT nombre, costo FROM inventario");
$costos = [];
while ($row = $res->fetch_assoc()) {
    $costos[mb_strtolower(trim($row['nombre']), 'UTF-8')] = (float)$row['costo'];
}

$leches = ['Leche de Almendras', 'Leche deslactosada Alpura'];
$tamanos = [
    'Chico' => ['leche' => 0.235, 'gr_cafe' => 4, 'vaso' => 'vasos 8oz'],
    'Mediano' => ['leche' => 0.355, 'gr_cafe' => 7, 'vaso' => 'vaso 12oz'],
    'Grande' => ['leche' => 0.473, 'gr_cafe' => 9, 'vaso' => 'vaso 16oz']
];

$resultado = [];
foreach ($leches as $l) {
    foreach ($tamanos as $label => $info) {
        $keyL = mb_strtolower($l, 'UTF-8');
        $costoLecheLitro = $costos[$keyL] ?? 0;
        $costoNescafeGR = ($costos['nescafe'] ?? 0) / 1000;

        $total = ($costoLecheLitro * $info['leche']) + ($costoNescafeGR * $info['gr_cafe']) + ($costos[$info['vaso']] ?? 0);

        $resultado[] = [
            'categoria' => 'Café con Leche',
            'nombre' => $l,
            'tamano' => $label,
            'costo' => round($total, 2)
        ];
    }
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);