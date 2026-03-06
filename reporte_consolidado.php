<?php
require_once __DIR__ . '/conexion/conexion.php';

const DB_VENTAS = 'ventas';
const TABLA_CORTE = 'historial_cortes';
const TABLA_DETALLE = 'historial_cortes_detalle';
const TABLA_CORTE_INV = 'historial_cortes_inventario';
const TABLA_REGISTRO = 'registro_inventario_corte';

$conn->query(
    "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_CORTE_INV . "` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `corte_id` INT NOT NULL,
        `inventario_id` INT NOT NULL,
        `nombre_insumo` VARCHAR(120) NOT NULL,
        `unidad` VARCHAR(30) NOT NULL,
        `costo_unitario` DECIMAL(10,4) NOT NULL DEFAULT 0,
        `stock_antes` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `consumo` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `stock_despues` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `costo_consumido` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `stock_bajo` TINYINT(1) NOT NULL DEFAULT 0,
        INDEX (`corte_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$conn->query(
    "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_REGISTRO . "` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `corte_id` INT NOT NULL,
        `inventario_id` INT NOT NULL,
        `stock_ideal` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `stock_contado` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `diferencia` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `costo_unitario` DECIMAL(10,4) NOT NULL DEFAULT 0,
        `costo_diferencia` DECIMAL(14,4) NOT NULL DEFAULT 0,
        `capturado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `ux_corte_inventario` (`corte_id`, `inventario_id`),
        INDEX (`corte_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$corteId = isset($_GET['corte_id']) ? (int)$_GET['corte_id'] : 0;
if ($corteId > 0) {
    $stmt = $conn->prepare("SELECT * FROM `" . DB_VENTAS . "`.`" . TABLA_CORTE . "` WHERE id = ?");
    $stmt->bind_param('i', $corteId);
    $stmt->execute();
    $corte = $stmt->get_result()->fetch_assoc();
} else {
    $res = $conn->query("SELECT * FROM `" . DB_VENTAS . "`.`" . TABLA_CORTE . "` ORDER BY id DESC LIMIT 1");
    $corte = $res ? $res->fetch_assoc() : null;
    if ($corte) {
        $corteId = (int)$corte['id'];
    }
}

if (!$corte) {
    $sinDatos = true;
} else {
    $sinDatos = false;

    $stmtTotal = $conn->prepare("SELECT COALESCE(SUM(subtotal),0) total, COUNT(DISTINCT folio) tickets FROM `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` WHERE corte_id = ?");
    $stmtTotal->bind_param('i', $corteId);
    $stmtTotal->execute();
    $tot = $stmtTotal->get_result()->fetch_assoc();
    $totalVendido = (float)$tot['total'];
    $tickets = (int)$tot['tickets'];
    $ticketProm = $tickets > 0 ? ($totalVendido / $tickets) : 0;

    $metodos = ['efectivo' => 0.0, 'tarjeta' => 0.0, 'tarjeta de regalo' => 0.0];
    $stmtMet = $conn->prepare("SELECT LOWER(metodo_pago) metodo, COALESCE(SUM(subtotal),0) total FROM `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` WHERE corte_id = ? GROUP BY LOWER(metodo_pago)");
    $stmtMet->bind_param('i', $corteId);
    $stmtMet->execute();
    $resMet = $stmtMet->get_result();
    while ($row = $resMet->fetch_assoc()) {
        $metodos[$row['metodo']] = (float)$row['total'];
    }

    $categorias = [];
    $stmtCat = $conn->prepare("SELECT categoria, COALESCE(SUM(subtotal),0) total FROM `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` WHERE corte_id = ? GROUP BY categoria ORDER BY total DESC");
    $stmtCat->bind_param('i', $corteId);
    $stmtCat->execute();
    $resCat = $stmtCat->get_result();
    while ($row = $resCat->fetch_assoc()) {
        $categorias[] = $row;
    }

    $topProductos = [];
    $stmtTop = $conn->prepare("SELECT producto, tamano, COALESCE(SUM(cantidad),0) ventas FROM `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` WHERE corte_id = ? GROUP BY producto, tamano ORDER BY ventas DESC, producto ASC LIMIT 10");
    $stmtTop->bind_param('i', $corteId);
    $stmtTop->execute();
    $resTop = $stmtTop->get_result();
    while ($row = $resTop->fetch_assoc()) {
        $topProductos[] = $row;
    }

    $stmtIdeal = $conn->prepare("SELECT COALESCE(SUM(costo_consumido),0) AS costo_ideal FROM `" . DB_VENTAS . "`.`" . TABLA_CORTE_INV . "` WHERE corte_id = ?");
    $stmtIdeal->bind_param('i', $corteId);
    $stmtIdeal->execute();
    $ri = $stmtIdeal->get_result()->fetch_assoc();
    $costoIdeal = (float)$ri['costo_ideal'];

    $stmtReal = $conn->prepare(
        "SELECT
            COALESCE(SUM((h.stock_antes - COALESCE(r.stock_contado, h.stock_despues)) * h.costo_unitario), 0) AS costo_real,
            COALESCE(SUM(CASE WHEN (COALESCE(r.stock_contado, h.stock_despues) - h.stock_despues) < 0 THEN ABS(COALESCE(r.stock_contado, h.stock_despues) - h.stock_despues) * h.costo_unitario ELSE 0 END), 0) AS costo_faltante,
            COALESCE(SUM(CASE WHEN (COALESCE(r.stock_contado, h.stock_despues) - h.stock_despues) > 0 THEN (COALESCE(r.stock_contado, h.stock_despues) - h.stock_despues) * h.costo_unitario ELSE 0 END), 0) AS costo_sobrante
         FROM `" . DB_VENTAS . "`.`" . TABLA_CORTE_INV . "` h
         LEFT JOIN `" . DB_VENTAS . "`.`" . TABLA_REGISTRO . "` r
           ON r.corte_id = h.corte_id AND r.inventario_id = h.inventario_id
         WHERE h.corte_id = ?"
    );
    $stmtReal->bind_param('i', $corteId);
    $stmtReal->execute();
    $rr = $stmtReal->get_result()->fetch_assoc();
    $costoReal = (float)$rr['costo_real'];
    $costoFaltante = (float)$rr['costo_faltante'];
    $costoSobrante = (float)$rr['costo_sobrante'];

    $gananciaIdeal = $totalVendido - $costoIdeal;
    $gananciaReal = $totalVendido - $costoReal;
    $margenReal = $totalVendido > 0 ? (($gananciaReal / $totalVendido) * 100) : 0;

    $maxCosto = max($costoIdeal, $costoReal, 1);
    $idealWidth = ($costoIdeal / $maxCosto) * 100;
    $realWidth = ($costoReal / $maxCosto) * 100;

    $mEfectivo = (float)$metodos['efectivo'];
    $mTarjeta = (float)$metodos['tarjeta'];
    $mRegalo = (float)$metodos['tarjeta de regalo'];
    $metodosTotal = max($mEfectivo + $mTarjeta + $mRegalo, 1);
    $a1 = ($mEfectivo / $metodosTotal) * 360;
    $a2 = (($mEfectivo + $mTarjeta) / $metodosTotal) * 360;
    $pieBg = sprintf(
        'conic-gradient(#00a8ff 0deg %.2fdeg, #00b894 %.2fdeg %.2fdeg, #f59e0b %.2fdeg 360deg)',
        $a1,
        $a1,
        $a2,
        $a2
    );

    $variaciones = [];
    $stmtVar = $conn->prepare(
        "SELECT
            h.nombre_insumo, h.unidad, h.stock_antes, h.stock_despues AS stock_ideal,
            COALESCE(r.stock_contado, h.stock_despues) AS stock_contado,
            (COALESCE(r.stock_contado, h.stock_despues) - h.stock_despues) AS diferencia,
            h.costo_unitario
         FROM `" . DB_VENTAS . "`.`" . TABLA_CORTE_INV . "` h
         LEFT JOIN `" . DB_VENTAS . "`.`" . TABLA_REGISTRO . "` r
           ON r.corte_id = h.corte_id AND r.inventario_id = h.inventario_id
         WHERE h.corte_id = ?
         ORDER BY diferencia ASC, h.nombre_insumo ASC"
    );
    $stmtVar->bind_param('i', $corteId);
    $stmtVar->execute();
    $resVar = $stmtVar->get_result();
    while ($row = $resVar->fetch_assoc()) {
        $row['costo_variacion'] = ((float)$row['diferencia']) * ((float)$row['costo_unitario']);
        if ((float)$row['diferencia'] != 0.0) {
            $variaciones[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado | Adeenca POS</title>
    <link rel="stylesheet" href="css/reporte_consolidado.css">
</head>
<body>
    <div class="wrap">
        <header class="head no-print">
            <h1>Reporte Consolidado de Ventas</h1>
            <div class="actions">
                <a href="api/salida.php" class="btn btn-blue">Cerrar sesion</a>
                <button class="btn btn-green" onclick="window.print()">Imprimir reporte</button>
            </div>
        </header>

        <?php if ($sinDatos): ?>
            <section class="card">
                <h2>Sin datos</h2>
                <p>No existe un corte disponible para consolidar.</p>
            </section>
        <?php else: ?>
            <section class="grid">
                <article class="card"><h3>Total vendido</h3><p>$<?php echo number_format($totalVendido, 2); ?></p></article>
                <article class="card"><h3>Numero de ventas</h3><p><?php echo $tickets; ?> tickets</p></article>
                <article class="card"><h3>Ticket promedio</h3><p>$<?php echo number_format($ticketProm, 2); ?></p></article>
                <article class="card"><h3>Corte</h3><p>#<?php echo (int)$corte['id']; ?> | <?php echo htmlspecialchars((string)$corte['hora_corte']); ?></p></article>
            </section>

            <section class="card">
                <h2>Ventas por Metodo de Pago</h2>
                <div class="grid mini">
                    <article class="tile"><h4>Efectivo</h4><p>$<?php echo number_format((float)$metodos['efectivo'], 2); ?></p></article>
                    <article class="tile"><h4>Tarjeta</h4><p>$<?php echo number_format((float)$metodos['tarjeta'], 2); ?></p></article>
                    <article class="tile"><h4>Tarjeta regalo</h4><p>$<?php echo number_format((float)$metodos['tarjeta de regalo'], 2); ?></p></article>
                </div>
            </section>

            <section class="card">
                <h2>Ventas por Categoria</h2>
                <table>
                    <thead><tr><th>Categoria</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$c['categoria']); ?></td>
                                <td>$<?php echo number_format((float)$c['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section class="card">
                <h2>Productos Mas Vendidos</h2>
                <table>
                    <thead><tr><th>Producto</th><th>Tamano</th><th>Ventas</th></tr></thead>
                    <tbody>
                        <?php foreach ($topProductos as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$p['producto']); ?></td>
                                <td><?php echo htmlspecialchars((string)$p['tamano']); ?></td>
                                <td><?php echo (int)$p['ventas']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section class="card">
                <h2>Graficos de Analisis</h2>
                <div class="charts-grid">
                    <div class="chart-box">
                        <h3>Costo Ideal vs Costo Real</h3>
                        <div class="bars">
                            <div class="bar-row">
                                <span class="bar-label">Costo ideal</span>
                                <div class="bar-track"><div class="bar ideal" style="width: <?php echo number_format($idealWidth, 2, '.', ''); ?>%;"></div></div>
                                <span class="bar-value">$<?php echo number_format($costoIdeal, 2); ?></span>
                            </div>
                            <div class="bar-row">
                                <span class="bar-label">Costo real</span>
                                <div class="bar-track"><div class="bar real" style="width: <?php echo number_format($realWidth, 2, '.', ''); ?>%;"></div></div>
                                <span class="bar-value">$<?php echo number_format($costoReal, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="chart-box">
                        <h3>Ventas por Metodo (Pastel)</h3>
                        <div class="pie-wrap">
                            <div class="pie-chart" style="background: <?php echo $pieBg; ?>"></div>
                            <div class="pie-legend">
                                <div><span class="dot d1"></span>Efectivo: $<?php echo number_format($mEfectivo, 2); ?></div>
                                <div><span class="dot d2"></span>Tarjeta: $<?php echo number_format($mTarjeta, 2); ?></div>
                                <div><span class="dot d3"></span>Tarjeta regalo: $<?php echo number_format($mRegalo, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid mini" style="margin-top:12px;">
                    <article class="tile"><h4>Ganancia ideal</h4><p>$<?php echo number_format($gananciaIdeal, 2); ?></p></article>
                    <article class="tile"><h4>Ganancia real</h4><p>$<?php echo number_format($gananciaReal, 2); ?></p></article>
                    <article class="tile"><h4>Margen real</h4><p><?php echo number_format($margenReal, 2); ?>%</p></article>
                    <article class="tile"><h4>Costo por faltante</h4><p>$<?php echo number_format($costoFaltante, 2); ?></p></article>
                    <article class="tile"><h4>Valor por sobrante</h4><p>$<?php echo number_format($costoSobrante, 2); ?></p></article>
                </div>
            </section>

            <section class="card">
                <h2>Faltante / Sobrante por Insumo</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Unidad</th>
                            <th>Stock Antes</th>
                            <th>Stock Ideal</th>
                            <th>Stock Contado</th>
                            <th>Diferencia</th>
                            <th>Costo Variacion</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($variaciones)): ?>
                            <tr><td colspan="8">Sin variaciones. El inventario real coincide con el ideal.</td></tr>
                        <?php else: ?>
                            <?php foreach ($variaciones as $v): ?>
                                <?php
                                    $dif = (float)$v['diferencia'];
                                    $estado = $dif < 0 ? 'Falta' : 'Sobra';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$v['nombre_insumo']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$v['unidad']); ?></td>
                                    <td><?php echo number_format((float)$v['stock_antes'], 2); ?></td>
                                    <td><?php echo number_format((float)$v['stock_ideal'], 2); ?></td>
                                    <td><?php echo number_format((float)$v['stock_contado'], 2); ?></td>
                                    <td class="<?php echo $dif < 0 ? 'rojo' : ''; ?>"><?php echo number_format($dif, 2); ?></td>
                                    <td class="<?php echo $dif < 0 ? 'rojo' : ''; ?>">
                                        $<?php echo number_format((float)$v['costo_variacion'], 2); ?>
                                    </td>
                                    <td class="<?php echo $dif < 0 ? 'rojo' : ''; ?>"><?php echo $estado; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
