<?php
$connUsuarios = new mysqli(
    "localhost",
    "root",
    "",
    "usuarios_db"
);

if ($connUsuarios->connect_error) {
    die("Error conexión usuarios: " . $connUsuarios->connect_error);
}

// Opcional pero recomendado para evitar problemas con tildes
$connUsuarios->set_charset("utf8");
?>