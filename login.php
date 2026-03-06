<?php if(isset($_GET['registro'])): ?>

<div class="mensaje exito">
    ✅ Usuario creado correctamente<br>
    Tu usuario es:
    <b><?php echo $_GET['usuario']; ?></b>
</div>

<?php endif; ?>


<?php if(isset($_GET['error'])): ?>

<div class="mensaje error">

<?php
if($_GET['error']=="usuario"){
    echo "❌ El usuario no existe";
}
elseif($_GET['error']=="password"){
    echo "❌ Contraseña incorrecta";
}
else{
    echo "❌ Error al iniciar sesión";
}
?>

</div>

<?php endif; ?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Adeenca - Login</title>

<link rel="stylesheet" href="css/login.css">
<script defer src="js/login.js"></script>

</head>

<body>

<div class="overlay"></div>

<div class="login-container">

    <div class="brand">
        ☕ Adeenca
        <p>Inventario & Ventas</p>
    </div>

    <form action="api/login.php" method="POST" id="loginForm">

        <div class="input-group">
            <input type="text" name="usuario" required>
            <label>Usuario</label>
        </div>

        <div class="input-group">
            <input type="password" name="password" id="password" required>
            <label>Contraseña</label>
            <span class="toggle" onclick="verPassword()">👁</span>
        </div>

        <button type="submit" id="btnLogin">
            Iniciar Sesión
        </button>

<!-- <div class="registro">
¿No tienes cuenta?
<a href="registro.php">Registrarme</a>
</div> -->



    </form>

</div>

</body>
</html>