<?php
require_once("../conexion/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_p = $_POST['id_producto'];
    $id_i = $_POST['id_insumo'];
    $cant = $_POST['cantidad'];

    $sql = "INSERT INTO recetas_base (id_producto_menu, id_insumo_inventario, cantidad_necesaria) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", $id_p, $id_i, $cant);

    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>