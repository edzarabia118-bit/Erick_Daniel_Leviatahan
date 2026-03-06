<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_control.php';

$usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
$accesoId = isset($_SESSION['acceso_id']) ? (int)$_SESSION['acceso_id'] : 0;
$horaSalida = date("Y-m-d H:i:s");

if ($usuario !== '') {
    if ($accesoId > 0) {
        $sql = "UPDATE accesos
                SET hora_salida = ?,
                    tiempo_trabajado = TIMEDIFF(?, hora_entrada)
                WHERE id = ? AND usuario = ?";
        $stmt = $connControl->prepare($sql);
        $stmt->bind_param("ssis", $horaSalida, $horaSalida, $accesoId, $usuario);
        $stmt->execute();
    } else {
        $sql = "UPDATE accesos
                SET hora_salida = ?,
                    tiempo_trabajado = TIMEDIFF(?, hora_entrada)
                WHERE usuario = ?
                  AND (hora_salida IS NULL OR hora_salida = '0000-00-00 00:00:00')
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $connControl->prepare($sql);
        $stmt->bind_param("sss", $horaSalida, $horaSalida, $usuario);
        $stmt->execute();
    }
}

session_unset();
session_destroy();

header("Location: ../login.php");
exit();
