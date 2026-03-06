<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<title>Registro | Adeenca</title>

<link rel="stylesheet" href="css/registro.css">
<script defer src="js/registro.js"></script>

</head>

<body>

<div class="overlay"></div>

<div class="registro-container">

<div class="brand">
☕ Adeenca
<p>Crear nueva cuenta</p>
</div>

<form action="api/registro.php" method="POST" id="registroForm">

<div class="input-group">
<input name="nombres" required>
<label>Nombres</label>
</div>

<div class="input-group">
<input name="apellidos" required>
<label>Apellidos</label>
</div>

<div class="input-group">
<input name="telefono">
<label>Teléfono</label>
</div>

<div class="input-group">
<input name="correo" type="email">
<label>Correo</label>
</div>

<div class="input-group">
<input type="date" name="cumpleanos" required>
<label class="fecha">Cumpleaños</label>
</div>

<div class="input-group">
<input type="password" name="password" id="password" required>
<label>Contraseña</label>
<span class="toggle" onclick="verPassword()">👁</span>
</div>

<button type="submit" id="btnRegistro">
Crear Usuario
</button>

</form>

<div class="volver">
<a href="login.php">← Volver al login</a>
</div>

</div>

</body>
</html>