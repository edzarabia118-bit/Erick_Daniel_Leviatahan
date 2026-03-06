<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

$res = $conn->query("SELECT nombre, costo FROM inventario");
$costos = [];
while ($row = $res->fetch_assoc()) {
    $costos[mb_strtolower(trim($row['nombre']), 'UTF-8')] = (float)$row['costo'];
}

$productos = [
    'Nescafe capuchino café de olla',
    'Nescafe capuchino moka',
    'Nescafe capuchino original',
    'Nescafe capuchino vainilla'
];

$tamanos = [
    'Chico' => ['vaso' => 'vasos 8oz', 'agua' => 235, 'nescafe' => 4, 'azucar' => 15],
    'Mediano' => ['vaso' => 'vaso 12oz', 'agua' => 355, 'nescafe' => 7, 'azucar' => 25],
    'Grande' => ['vaso' => 'vaso 16oz', 'agua' => 473, 'nescafe' => 9, 'azucar' => 30]
];

$resultado = [];
foreach ($productos as $p) {
    foreach ($tamanos as $label => $info) {
        $keyP = mb_strtolower($p, 'UTF-8');

        $costoBase = $costos[$keyP] ?? 0;
        $costoVaso = $costos[$info['vaso']] ?? 0;

        $costoAguaUnitario = ($costos['agua'] ?? 0) / 1000;
        $totalAgua = $costoAguaUnitario * $info['agua'];

        $costoAzucarUnitario = ($costos['azucar'] ?? 0) / 1000;
        $totalAzucar = $costoAzucarUnitario * $info['azucar'];

        $costoNescafeUnitario = ($costos['nescafe'] ?? 0) / 1000;
        $totalNescafe = $costoNescafeUnitario * $info['nescafe'];

        $costoFinal = $costoBase + $costoVaso + $totalAgua + $totalAzucar + $totalNescafe;

        $resultado[] = [
            'categoria' => 'Nescafé',
            'nombre' => $p,
            'tamano' => $label,
            'costo' => round($costoFinal, 2)
        ];
    }
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);