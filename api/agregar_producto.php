<?php
require_once("../conexion/conexion.php");

// 1. CAPTURA DE DATOS
$nombre   = $_POST['nombre'] ?? '';
$stock    = $_POST['cantidad'] ?? 0;
$unidad   = $_POST['unidad'] ?? '';
$costo    = $_POST['costo'] ?? 0;

// 2. VALIDACIÓN DE UNIDADES (Antes de hacer nada)
$unidadesValidas = ["Kilo", "Litro", "Gramos", "Mililitros", "Piezas"];

if (!in_array($unidad, $unidadesValidas)) {
    header("Location: ../inventario.php?error=unidad_invalida");
    exit();
}

// 3. COMPROBAR SI YA EXISTE (Usando Consultas Preparadas para seguridad)
$stmt_check = $conn->prepare("SELECT id FROM inventario WHERE nombre = ?");
$stmt_check->bind_param("s", $nombre);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows > 0) {
    header("Location: ../inventario.php?error=duplicado");
    exit();
}
$stmt_check->close();

// 4. INSERTAR (UNA SOLA VEZ)
$stmt_insert = $conn->prepare("INSERT INTO inventario (nombre, stock, unidad, costo) VALUES (?, ?, ?, ?)");
// "sdsd" significa: string, double (decimal), string, double (decimal)
$stmt_insert->bind_param("sdsd", $nombre, $stock, $unidad, $costo);

if ($stmt_insert->execute()) {
    // Éxito: Redirigimos con mensaje
    header("Location: ../inventario.php?actualizado=1");
} else {
    // Error
    header("Location: ../inventario.php?error=sql");
}

$stmt_insert->close();
$conn->close();
exit(); // Finalizamos el script para asegurar que no se ejecute nada más
?>
