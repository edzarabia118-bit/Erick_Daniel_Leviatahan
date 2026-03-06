<?php
require_once("../conexion/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $stock = floatval($_POST['cantidad']);

    // Actualizar la columna real de inventario: 'stock'
    // Esto evita que el nombre o el costo se borren
    $stmt = $conn->prepare("UPDATE inventario SET stock = ? WHERE id = ?");
    $stmt->bind_param("di", $stock, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
