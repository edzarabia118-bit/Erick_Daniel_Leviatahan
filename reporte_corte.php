<?php
require_once __DIR__ . '/conexion/conexion.php';

$corteId = isset($_GET['corte_id']) ? (int)$_GET['corte_id'] : 0;

if ($corteId > 0) {
    $stmt = $conn->prepare("SELECT * FROM `ventas`.`historial_cortes` WHERE id = ?");
    $stmt->bind_param('i', $corteId);
    $stmt->execute();
    $resCorte = $stmt->get_result();
    $corte = $resCorte->fetch_assoc();
} else {
    $resCorte = $conn->query("SELECT * FROM `ventas`.`historial_cortes` ORDER BY id DESC LIMIT 1");
    $corte = $resCorte ? $resCorte->fetch_assoc() : null;
    if ($corte) {
        $corteId = (int)$corte['id'];
    }
}

$detalles = [];
$resumenCategorias = [];
if ($corteId > 0) {
    $stmtDetalle = $conn->prepare("SELECT folio, producto, categoria, tamano, cantidad, precio, subtotal, metodo_pago, factura, fecha_hora FROM `ventas`.`historial_cortes_detalle` WHERE corte_id = ? ORDER BY folio ASC, id ASC");
    $stmtDetalle->bind_param('i', $corteId);
    $stmtDetalle->execute();
    $resDetalle = $stmtDetalle->get_result();
    while ($row = $resDetalle->fetch_assoc()) {
        $detalles[] = $row;
    }

    $stmtResumen = $conn->prepare("SELECT categoria, SUM(cantidad) AS piezas, SUM(subtotal) AS total_categoria FROM `ventas`.`historial_cortes_detalle` WHERE corte_id = ? GROUP BY categoria ORDER BY total_categoria DESC");
    $stmtResumen->bind_param('i', $corteId);
    $stmtResumen->execute();
    $resResumen = $stmtResumen->get_result();
    while ($row = $resResumen->fetch_assoc()) {
        $resumenCategorias[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Corte | Adeenca POS</title>
    <link rel="stylesheet" href="css/corte_style.css">
</head>
<body>
    <div class="reporte-wrapper">
        <header class="reporte-header no-print">
            <h1>Reporte de Corte de Caja</h1>
            <div class="acciones">
                <a href="api/salida.php" class="btn btn-secundario">Cerrar sesion</a>
                <a href="registro_de_inventario.php?corte_id=<?php echo (int)$corteId; ?>" class="btn btn-secundario">Registro de inventario</a>
                <button class="btn btn-primario" onclick="window.print()">Imprimir Reporte</button>
            </div>
        </header>

        <?php if (!$corte): ?>
            <section class="estado-vacio">
                <h2>No existe informacion de corte.</h2>
                <p>Realiza un corte desde el POS para generar este reporte.</p>
            </section>
        <?php else: ?>
            <section class="resumen-grid">
                <article class="resumen-card">
                    <h3>Corte #<?php echo (int)$corte['id']; ?></h3>
                    <p><?php echo htmlspecialchars((string)$corte['fecha_dia']); ?> <?php echo htmlspecialchars((string)$corte['hora_corte']); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Total Ventas</h3>
                    <p>$<?php echo number_format((float)$corte['total_ventas'], 2); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Operaciones</h3>
                    <p><?php echo (int)$corte['numero_operaciones']; ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Total Productos</h3>
                    <p><?php echo (int)($corte['total_productos'] ?? 0); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Efectivo</h3>
                    <p>$<?php echo number_format((float)($corte['efectivo_total'] ?? 0), 2); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Tarjeta</h3>
                    <p>$<?php echo number_format((float)($corte['tarjeta_total'] ?? 0), 2); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Tarjeta Regalo</h3>
                    <p>$<?php echo number_format((float)($corte['tarjeta_regalo_total'] ?? 0), 2); ?></p>
                </article>
                <article class="resumen-card">
                    <h3>Metodo Predominante</h3>
                    <p><?php echo htmlspecialchars((string)($corte['metodo_predominante'] ?? 'N/A')); ?></p>
                </article>
            </section>

            <section class="tabla-section">
                <h2>Resumen por Categoria</h2>
                <div class="tabla-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Piezas Vendidas</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resumenCategorias)): ?>
                                <tr>
                                    <td colspan="3" class="sin-datos">No hay resumen por categoria para este corte.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resumenCategorias as $cat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)$cat['categoria']); ?></td>
                                        <td><?php echo (int)$cat['piezas']; ?></td>
                                        <td>$<?php echo number_format((float)$cat['total_categoria'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="tabla-section">
                <h2>Detalle de Ventas del Corte</h2>
                <div class="tabla-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Producto</th>
                                <th>Categoria</th>
                                <th>Tamano</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                                <th>Metodo</th>
                                <th>Factura</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detalles)): ?>
                                <tr>
                                    <td colspan="10" class="sin-datos">No hay detalles para este corte.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detalles as $fila): ?>
                                    <tr>
                                        <td><?php echo (int)$fila['folio']; ?></td>
                                        <td><?php echo htmlspecialchars((string)$fila['producto']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$fila['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$fila['tamano']); ?></td>
                                        <td><?php echo (int)$fila['cantidad']; ?></td>
                                        <td>$<?php echo number_format((float)$fila['precio'], 2); ?></td>
                                        <td>$<?php echo number_format((float)$fila['subtotal'], 2); ?></td>
                                        <td><?php echo htmlspecialchars((string)$fila['metodo_pago']); ?></td>
                                        <td><?php echo ((int)$fila['factura'] === 1) ? 'Si' : 'No'; ?></td>
                                        <td><?php echo htmlspecialchars((string)$fila['fecha_hora']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
