<?php


require_once __DIR__ . '/../conexion/conexion_usuarios.php';

/* recibir datos */
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$cumpleanos = $_POST['cumpleanos'];
$password = $_POST['password'];

/* ===== GENERAR USUARIO ===== */

$nombre = strtolower(explode(" ", $nombres)[0]);
$apellido = strtolower(explode(" ", $apellidos)[0]);
$anio = date("Y", strtotime($cumpleanos));

$usuarioBase = $nombre.$apellido.$anio;
$usuario = $usuarioBase;

$contador = 1;

/* verificar duplicados */
while(true){

    $check = $connUsuarios->query(
        "SELECT id FROM usuarios WHERE usuario='$usuario'"
    );

    if($check->num_rows == 0){
        break;
    }

    $usuario = $usuarioBase.$contador;
    $contador++;
}

/* ===== INSERTAR ===== */

$sql = "INSERT INTO usuarios 
(nombres,apellidos,telefono,correo,cumpleanos,usuario,password)
VALUES
('$nombres','$apellidos','$telefono','$correo',
'$cumpleanos','$usuario','$password')";

if($connUsuarios->query($sql)){

    header("Location: ../login.php?registro=ok&usuario=".$usuario);
    exit();

}else{

    header("Location: ../registro.php?error=1");
    exit();
}

?>