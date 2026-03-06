<?php
require_once("conexion/conexion.php");

// 1. GESTIÓN DE MENSAJES (Consolidado)
$mensaje = "";
$clase = "ok"; 

if (isset($_GET['actualizado'])) {
    $mensaje = "✅ Producto actualizado correctamente";
} elseif (isset($_GET['eliminado'])) {
    $mensaje = "✅ Producto eliminado correctamente";
} elseif (isset($_GET['ok'])) {
    $mensaje = "✨ Producto guardado con éxito";
} elseif (isset($_GET['error'])) {
    $clase = "error";
    $mensaje = match($_GET['error']) {
        'duplicado' => "❌ El producto ya existe en el inventario",
        'unidad_invalida' => "❌ La unidad seleccionada no es válida",
        default => "❌ Hubo un problema al procesar la solicitud"
    };
}

// 2. LÓGICA DE BÚSQUEDA (Segura)
$buscar = $_GET['buscar'] ?? '';
$termino = "%$buscar%";
$stmt = $conn->prepare("SELECT * FROM inventario WHERE nombre LIKE ? ORDER BY nombre ASC");
$stmt->bind_param("s", $termino);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Cafetería</title>
    <link rel="stylesheet" href="css/inventario.css">
    <script defer src="js/inventario.js"></script>
</head>
<body>

    <?php if($mensaje): ?>
        <div class="<?php echo $clase; ?>" id="notificacion">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <h1>📦 Inventario Cafetería</h1>

        <form method="GET" class="buscador">
            <input type="text" name="buscar" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($buscar) ?>">
            <button type="submit">Buscar</button>
        </form>

        <form action="api/agregar_producto.php" method="POST" id="formInventario" class="fade">
            <input name="nombre" required placeholder="Producto">
            
            <input type="number" step="0.01" name="cantidad" required placeholder="Cantidad">

            <select name="unidad" required>
                <option value="" disabled selected>Unidad</option>
                <option>Kilo</option>
                <option>Litro</option>
                <option>Gramos</option>
                <option>Mililitros</option>
                <option>Piezas</option>
            </select>

            <input type="number" step="0.01" name="costo" required placeholder="Costo">

            <button type="submit">➕ Guardar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Stock</th>
                    <th>Unidad</th>
                    <th>Costo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $resultado->fetch_assoc()): 
                    $id = $row['id'];
                    $cantidad = $row['stock'];
                    $unidad = $row['unidad'];
                    $bajo = "";

                    // Lógica de Stock Bajo
                    if(
                        ($unidad == "Mililitros" && $cantidad <= 300) ||
                        ($unidad == "Litro" && $cantidad <= 1) ||
                        ($unidad == "Gramos" && $cantidad <= 500) ||
                        ($unidad == "Kilo" && $cantidad <= 2) ||
                        ($unidad == "Piezas" && $cantidad <= 20)
                    ) { $bajo = "bajo"; }
                ?>
                    <tr class="<?php echo $bajo; ?>">
                        <td><?php echo $id; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo number_format($row['stock'], 2); ?></td>
                        <td><?php echo $row['unidad']; ?></td>
                        <td>$<?php echo number_format($row['costo'], 2); ?></td>
                        <td>
                            <button type='button' onclick="window.location.href='api/editar_producto.php?id=<?php echo $id; ?>'">✏️</button>
                            <button type="button" title="Ajustar Stock" onclick="actualizar(<?php echo $id; ?>)">🔄</button>
                            <a href="api/eliminar_producto.php?id=<?php echo $id; ?>" 
                               class="btn-eliminar"
                               onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                                🗑
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

<div class="seccion-acciones no-print">
    <a href="reporte_inventario.php" class="btn-secundario">
        <span class="icon">📋</span> Reporte Valorizado
    </a>
    
    <a href="analisis_costos.php" class="btn-analisis">
        <span class="icon">📊</span> Análisis de Costos
    </a>

    <button type="button" id="btnAccesoPOS" class="btn-pos-azul">🛒 Ir a Punto de Venta</button>
</div>

<a href="" class="">
        <span class="icon">📊</span> reporte consolidado
    </a>


        <div class="footer-actions">
            <a href="api/salida.php" class="btn-salir">Cerrar sesión</a>
        </div>
    </div>
<script>
// Usamos un EventListener para que el botón funcione sí o sí
document.addEventListener("DOMContentLoaded", () => {
    const botonPOS = document.getElementById('btnAccesoPOS');
    
    if (botonPOS) {
        botonPOS.addEventListener('click', () => {
            console.log("Intentando acceder al POS..."); // Esto saldrá en la consola (F12)
            
            const passwordCorrecta = "12345"; 
            const input = prompt("🔐 Acceso Restringido\nIngrese la contraseña para entrar al Punto de Venta:");
            
            if (input === null) return; // Si cancela no hace nada
            
            if (input === passwordCorrecta) {
                window.location.href = "pos.php";
            } else {
                alert("❌ Contraseña incorrecta. Intente de nuevo.");
            }
        });
    } else {
        console.error("No se encontró el botón con ID: btnAccesoPOS");
    }
});
</script>
</body>
</html>