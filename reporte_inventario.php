<?php
require_once("conexion/conexion.php");

$sql = "SELECT id, nombre, stock, unidad, costo FROM inventario ORDER BY nombre ASC";
$resultado = $conn->query($sql);

$total_articulos = 0;
$valor_total_inventario = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inventario Valorizado</title>
    <link rel="stylesheet" href="css/reporte.css">
    <script defer src="js/reporte.js"></script>
</head>
<body>

<div class="report-container fade-in">
    <header class="report-header">
        <div class="header-content">
            <h1>📋 Reporte de Inventario</h1>
            <p>Resumen de existencias y valorización total</p>
            <span class="date-badge">Fecha: <?php echo date('d/m/Y H:i'); ?></span>
        </div>
        <div class="header-actions no-print">
            <a href="inventario.php" class="btn-back">⬅ Volver</a>
            <button id="btnImprimir" class="btn-print">🖨 Imprimir PDF</button>
        </div>
    </header>

    <div class="table-responsive">
        <table class="report-table" id="tablaDatos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th class="text-right">Stock</th>
                    <th>Unidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Total ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $resultado->fetch_assoc()): 
                    $subtotal = $row['stock'] * $row['costo'];
                    $total_articulos += $row['stock'];
                    $valor_total_inventario += $subtotal;
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td class="text-right"><?php echo number_format($row['stock'], 2); ?></td>
                    <td class="text-muted"><?php echo $row['unidad']; ?></td>
                    <td class="text-right">$<?php echo number_format($row['costo'], 2); ?></td>
                    <td class="text-right total-cell">$<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="report-summary">
        <div class="summary-card">
            <span>Total Unidades</span>
            <strong><?php echo number_format($total_articulos, 2); ?></strong>
        </div>
        <div class="summary-card highlight">
            <span>Inversión Total</span>
            <strong>$<?php echo number_format($valor_total_inventario, 2); ?></strong>
        </div>
    </div>

    <footer class="report-footer">
        <p>Software de Gestión de Cafetería - Reporte Oficial</p>
    </footer>
</div>

</body>
</html>