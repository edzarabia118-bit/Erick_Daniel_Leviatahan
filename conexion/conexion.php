<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$bd = "cafeteria";

$conn = new mysqli($servidor, $usuario, $password, $bd);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// opcional
$conn->set_charset("utf8");

?>