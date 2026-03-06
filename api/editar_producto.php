<?php
require_once("../conexion/conexion.php"); 

if (!isset($_GET['id'])) {
    header("Location: ../inventario.php");
    exit();
}

$id = intval($_GET['id']);
$stmt_load = $conn->prepare("SELECT * FROM inventario WHERE id = ?");
$stmt_load->bind_param("i", $id);
$stmt_load->execute();
$producto = $stmt_load->get_result()->fetch_assoc();

if (!$producto) {
    header("Location: ../inventario.php?error=no_existe");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_post    = intval($_POST['id']);
    $nombre     = $_POST['nombre'];
    $stock      = floatval($_POST['cantidad']);
    $unidad     = $_POST['unidad'];
    $costo      = floatval($_POST['costo']);

    $stmt_update = $conn->prepare("UPDATE inventario SET nombre=?, stock=?, unidad=?, costo=? WHERE id=?");
    $stmt_update->bind_param("sdsdi", $nombre, $stock, $unidad, $costo, $id_post);

    if ($stmt_update->execute()) {
        header("Location: ../inventario.php?actualizado=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="../css/inventario.css">
    <script defer src="../js/inventario.js"></script>
</head>
<body>
    <div class="container">
        <h1>✏️ Editar Producto</h1>

        <div class="form-container fade show">
            <form method="POST" id="formEditar">
                <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">

                <div class="grid-edit">
                    <div class="campo">
                        <label>Producto</label>
                        <input name="nombre" type="text" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="campo">
                        <label>stock</label>
                        <input type="number" step="0.01" name="cantidad" value="<?php echo $producto['stock']; ?>" required>
                    </div>

                    <div class="campo">
                        <label>Unidad</label>
                        <select name="unidad" required>
                            <?php
                            $unidades = ["Kilo", "Litro", "Gramos", "Mililitros", "Piezas"];
                            foreach($unidades as $u) {
                                $selected = ($producto['unidad'] == $u) ? "selected" : "";
                                echo "<option value='$u' $selected>$u</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Costo</label>
                        <input type="number" step="0.01" name="costo" value="<?php echo $producto['costo']; ?>" required>
                    </div>
                </div>

                <div class="acciones-form">
                    <button type="submit" class="btn-guardar">💾 Guardar Cambios</button>
                    <a href="../inventario.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
