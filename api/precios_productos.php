<?php
// api/precios_productos.php
require_once("../conexion/conexion.php");
header('Content-Type: application/json');

// 1. Consultamos la nueva tabla de la base de datos
$res = $conn->query("SELECT categoria, tamano, precio FROM precios_venta");
$precios = [];

// 2. Si la tabla tiene datos, los organizamos en el formato que espera el JS
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()){
        $precios[$row['categoria']][$row['tamano']] = (float)$row['precio'];
    }
} else {
    // 3. Opcional: Si la tabla está vacía, enviamos valores por defecto para que no falle la página
    $precios = [
        'Té' => ['Chico' => 17, 'Mediano' => 20, 'Grande' => 25],
        'Café con Leche' => ['Chico' => 27, 'Mediano' => 35, 'Grande' => 40],
        'Nescafé' => ['Chico' => 27, 'Mediano' => 35, 'Grande' => 40]
    ];
}

echo json_encode($precios);