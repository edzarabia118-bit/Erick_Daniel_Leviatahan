<?php
session_start();

require_once __DIR__ . '/../conexion/conexion_usuarios.php';
require_once __DIR__ . '/../conexion/conexion_control.php';

if (!isset($_POST['usuario'], $_POST['password'])) {
    header("Location: ../login.php?error=1");
    exit();
}

$usuario = trim((string)$_POST['usuario']);
$password = (string)$_POST['password'];

if ($usuario === '' || $password === '') {
    header("Location: ../login.php?error=1");
    exit();
}

$stmt = $connUsuarios->prepare("SELECT usuario, password FROM usuarios WHERE usuario = ? LIMIT 1");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    header("Location: ../login.php?error=usuario");
    exit();
}

$datos = $resultado->fetch_assoc();
if ($password !== (string)$datos['password']) {
    header("Location: ../login.php?error=password");
    exit();
}

$connControl->query(
    "CREATE TABLE IF NOT EXISTS accesos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(100) NOT NULL,
        hora_entrada DATETIME NOT NULL,
        hora_salida DATETIME NULL,
        tiempo_trabajado TIME NULL,
        INDEX idx_usuario_hora (usuario, hora_entrada)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$horaEntrada = date("Y-m-d H:i:s");
$stmtAcceso = $connControl->prepare(
    "INSERT INTO accesos (usuario, hora_entrada, hora_salida, tiempo_trabajado)
     VALUES (?, ?, NULL, NULL)"
);
$stmtAcceso->bind_param("ss", $usuario, $horaEntrada);
$stmtAcceso->execute();

$_SESSION['usuario'] = $usuario;
$_SESSION['acceso_id'] = (int)$connControl->insert_id;
$_SESSION['hora_entrada'] = $horaEntrada;

header("Location: ../inventario.php");
exit();
