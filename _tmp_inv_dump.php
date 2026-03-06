<?php
require_once __DIR__ . '/conexion/conexion.php';
$res = $conn->query("SELECT id,nombre,stock,unidad FROM inventario ORDER BY id ASC LIMIT 200");
while($r = $res->fetch_assoc()) {
    echo $r['id'] . ' | ' . $r['nombre'] . ' | ' . $r['stock'] . ' | ' . $r['unidad'] . PHP_EOL;
}
