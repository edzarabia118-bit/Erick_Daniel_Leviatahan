<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

// Cargar costos
$res = $conn->query("SELECT nombre, costo FROM inventario");
$costos = [];
while ($row = $res->fetch_assoc()) {
    $costos[mb_strtolower(trim($row['nombre']), 'UTF-8')] = (float)$row['costo'];
}

$productos = [
    'Te de 7 azahares',
    'Te de canela a la manzana',
    'Te de hierbabuena',
    'Te de jamaica',
    'Te de jengibre-limon',
    'Te de limon',
    'Te de manzanilla',
    'Te verde de mandarina',
    'Te verde de mango'
];

$tamanos = [
    'Chico' => ['vol' => 0.235, 'bolsas' => 1, 'vaso' => 'vasos 8oz'],
    'Mediano' => ['vol' => 0.355, 'bolsas' => 2, 'vaso' => 'vaso 12oz'],
    'Grande' => ['vol' => 0.473, 'bolsas' => 3, 'vaso' => 'vaso 16oz']
];

$resultado = [];
foreach ($productos as $p) {
    foreach ($tamanos as $label => $info) {
        $keyP = mb_strtolower($p, 'UTF-8');
        $costoBolsa = $costos[$keyP] ?? 0;
        $costoVaso = $costos[$info['vaso']] ?? 0;
        $costoAgua = ($costos['agua'] ?? 0) * $info['vol'];

        $total = ($costoBolsa * $info['bolsas']) + $costoVaso + $costoAgua;

        $resultado[] = [
            'categoria' => 'Té',
            'nombre' => $p,
            'tamano' => $label,
            'costo' => round($total, 2)
        ];
    }
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);