<?php
date_default_timezone_set('America/Mexico_City'); // Ajusta a tu zona horaria

$connControl = new mysqli("localhost", "root", "", "control_db");

if ($connControl->connect_error) {
    die("Error crítico de conexión: " . $connControl->connect_error);
}

// Establecer caracteres para evitar errores con nombres
$connControl->set_charset("utf8mb4");
?>