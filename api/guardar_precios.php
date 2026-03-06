<?php
require_once("../conexion/conexion.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria = $_POST['categoria'];
    $tamano = $_POST['tamano'];
    $precio = (float)$_POST['precio'];

    // Esta consulta es "mágica": si no existe la fila la crea, si existe la actualiza.
    $sql = "INSERT INTO precios_venta (categoria, tamano, precio) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE precio = VALUES(precio)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $categoria, $tamano, $precio);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Precio actualizado"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>