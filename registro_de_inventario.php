<?php
require_once __DIR__ . '/conexion/conexion.php';

const DB_VENTAS = 'ventas';
const TABLA_CORTE_INV = 'historial_cortes_inventario';
const TABLA_REGISTRO_INV = 'registro_inventario_corte';

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
    "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_REGISTRO_INV . "` (
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
if ($corteId <= 0) {
    $resUlt = $conn->query("SELECT id FROM `ventas`.`historial_cortes` ORDER BY id DESC LIMIT 1");
    $ult = $resUlt ? $resUlt->fetch_assoc() : null;
    if ($ult) {
        $corteId = (int)$ult['id'];
    }
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $corteId = (int)($_POST['corte_id'] ?? 0);
    if ($corteId > 0 && !empty($_POST['contado']) && is_array($_POST['contado'])) {
        $conn->begin_transaction();
        try {
            $stmtData = $conn->prepare("SELECT inventario_id, stock_despues, costo_unitario FROM `ventas`.`" . TABLA_CORTE_INV . "` WHERE corte_id = ? AND inventario_id = ? LIMIT 1");
            $stmtUpReg = $conn->prepare(
                "INSERT INTO `ventas`.`" . TABLA_REGISTRO_INV . "`
                (corte_id, inventario_id, stock_ideal, stock_contado, diferencia, costo_unitario, costo_diferencia, capturado_en)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    stock_ideal = VALUES(stock_ideal),
                    stock_contado = VALUES(stock_contado),
                    diferencia = VALUES(diferencia),
                    costo_unitario = VALUES(costo_unitario),
                    costo_diferencia = VALUES(costo_diferencia),
                    capturado_en = NOW()"
            );
            $stmtUpInv = $conn->prepare("UPDATE inventario SET stock = ? WHERE id = ?");

            foreach ($_POST['contado'] as $invIdRaw => $valorRaw) {
                $invId = (int)$invIdRaw;
                $stockContado = (float)$valorRaw;

                $stmtData->bind_param('ii', $corteId, $invId);
                $stmtData->execute();
                $row = $stmtData->get_result()->fetch_assoc();
                if (!$row) {
                    continue;
                }

                $stockIdeal = (float)$row['stock_despues'];
                $costoUnit = (float)$row['costo_unitario'];
                $diferencia = $stockContado - $stockIdeal;
                $costoDiff = round($diferencia * $costoUnit, 4);

                $stmtUpReg->bind_param(
                    'iiddddd',
                    $corteId,
                    $invId,
                    $stockIdeal,
                    $stockContado,
                    $diferencia,
                    $costoUnit,
                    $costoDiff
                );
                $stmtUpReg->execute();

                $stmtUpInv->bind_param('di', $stockContado, $invId);
                $stmtUpInv->execute();
            }

            $conn->commit();
            $mensaje = 'Conteo guardado. Inventario actualizado.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Error al guardar conteo: ' . $e->getMessage();
        }
    } else {
        $error = 'No hay datos para guardar.';
    }
}

$rows = [];
if ($corteId > 0) {
    $stmt = $conn->prepare(
        "SELECT h.inventario_id, h.nombre_insumo, h.unidad, h.stock_antes, h.consumo, h.stock_despues, h.costo_unitario,
                r.stock_contado, r.diferencia
         FROM `ventas`.`" . TABLA_CORTE_INV . "` h
         LEFT JOIN `ventas`.`" . TABLA_REGISTRO_INV . "` r
           ON r.corte_id = h.corte_id AND r.inventario_id = h.inventario_id
         WHERE h.corte_id = ?
         ORDER BY h.nombre_insumo ASC"
    );
    $stmt->bind_param('i', $corteId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Inventario | Adeenca POS</title>
    <link rel="stylesheet" href="css/reporte_consolidado.css">
</head>
<body>
    <div class="wrap">
        <header class="head no-print">
            <h1>Registro de Inventario (Corte #<?php echo (int)$corteId; ?>)</h1>
            <div class="actions">
                <a href="api/salida.php" class="btn btn-blue">Cerrar sesion</a>
                <a href="reporte_corte.php?corte_id=<?php echo (int)$corteId; ?>" class="btn btn-blue">Volver a Corte</a>
                <a href="reporte_consolidado.php?corte_id=<?php echo (int)$corteId; ?>" class="btn btn-green">Ver Consolidado</a>
            </div>
        </header>

        <?php if ($mensaje !== ''): ?>
            <section class="card"><h2><?php echo htmlspecialchars($mensaje); ?></h2></section>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <section class="card"><h2 style="color:#b42318;"><?php echo htmlspecialchars($error); ?></h2></section>
        <?php endif; ?>

        <section class="card">
            <h2>Conteo real (actualiza inventario)</h2>
            <form method="POST">
                <input type="hidden" name="corte_id" value="<?php echo (int)$corteId; ?>">
                <table>
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Unidad</th>
                            <th>Stock Antes</th>
                            <th>Consumo Ideal</th>
                            <th>Stock Ideal</th>
                            <th>Stock Contado (Real)</th>
                            <th>Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7">No hay datos de inventario para este corte.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                    $ideal = (float)$r['stock_despues'];
                                    $contado = isset($r['stock_contado']) ? (float)$r['stock_contado'] : $ideal;
                                    $dif = isset($r['diferencia']) ? (float)$r['diferencia'] : ($contado - $ideal);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$r['nombre_insumo']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$r['unidad']); ?></td>
                                    <td><?php echo number_format((float)$r['stock_antes'], 2); ?></td>
                                    <td><?php echo number_format((float)$r['consumo'], 2); ?></td>
                                    <td><?php echo number_format($ideal, 2); ?></td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" name="contado[<?php echo (int)$r['inventario_id']; ?>]" value="<?php echo htmlspecialchars((string)$contado); ?>" style="width:120px;">
                                    </td>
                                    <td class="<?php echo $dif < 0 ? 'rojo' : ''; ?>">
                                        <?php echo number_format($dif, 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($rows)): ?>
                    <div class="actions" style="margin-top:12px;">
                        <button type="submit" class="btn btn-green">Guardar Conteo y Actualizar Inventario</button>
                    </div>
                <?php endif; ?>
            </form>
        </section>
    </div>
</body>
</html>
