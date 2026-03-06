<?php
require_once("conexion/conexion.php");

// Consultar costos actuales del inventario para cálculos en tiempo real
$res = $conn->query("SELECT nombre, costo FROM inventario");
$costos = [];
while($row = $res->fetch_assoc()){
    $costos[$row['nombre']] = $row['costo'];
}

// Ejemplo de función para calcular costo de un Té Grande
function calcularCostoTe($nombreTe, $costos) {
    $costoBolsa = $costos[$nombreTe] ?? 0;
    $costoVaso = $costos['Vaso 16oz'] ?? 0;
    $costoAgua = ($costos['Agua'] ?? 0) * 0.473;
   
    
    return ($costoBolsa * 3) + $costoVaso + $costoAgua;
}
?>

<div class="reporte-container">
    <h2>💰 Lista de Precios y Rentabilidad</h2>
    <table class="tabla-precios">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Tamaño</th>
                <th>Costo de Producción</th>
                <th>Precio de Venta (Editable)</th>
                <th>Ganancia ($)</th>
                <th>Margen (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Te de jamaica</td>
                <td>Grande (16oz)</td>
                <td>$<?php echo number_format($costo = calcularCostoTe('Te de jamaica', $costos), 2); ?></td>
                <td><input type="number" value="25.00" class="input-precio" data-id="1001"></td>
                <td class="ganancia">$<?php echo number_format(25.00 - $costo, 2); ?></td>
                <td class="margen"><?php echo round(((25 - $costo)/25)*100); ?>%</td>
            </tr>
            </tbody>
    </table>
</div>