<?php
require_once("../conexion/conexion.php");

if(isset($_GET['id'])) {
    $id_producto = $_GET['id'];
    
    // Unimos la receta con el inventario para saber el nombre y unidad del insumo
    $sql = "SELECT r.id, i.nombre, r.cantidad_necesaria, i.unidad 
            FROM recetas_base r
            JOIN inventario i ON r.id_insumo_inventario = i.id
            WHERE r.id_producto_menu = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        echo "<table class='tabla-detalle'>
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['nombre']}</td>
                    <td>" . number_format($row['cantidad_necesaria'], 3) . "</td>
                    <td>{$row['unidad']}</td>
                    <td><button onclick='eliminarInsumo({$row['id']})' class='btn-eliminar'>❌</button></td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p class='info-text'>Este producto aún no tiene ingredientes asignados.</p>";
    }
}
?>