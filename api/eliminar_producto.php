<?php
require_once("../conexion/conexion.php");

/* 1. VALIDAR ID */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../inventario.php?error=id_no_encontrado");
    exit();
}

// Convertimos a entero para mayor seguridad
$id = intval($_GET['id']);

/* 2. ELIMINAR (Usando Prepared Statements) */
$stmt = $conn->prepare("DELETE FROM inventario WHERE id = ?");
$stmt->bind_param("i", $id); // "i" indica que el valor es un integer (entero)

if ($stmt->execute()) {
    // Si se eliminó algo (afectó filas)
    if ($stmt->affected_rows > 0) {
        header("Location: ../inventario.php?eliminado=1");
    } else {
        header("Location: ../inventario.php?error=no_existe");
    }
} else {
    header("Location: ../inventario.php?error=db");
}

$stmt->close();
$conn->close();
exit();
?>