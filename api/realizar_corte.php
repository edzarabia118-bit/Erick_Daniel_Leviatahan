<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

const DB_VENTAS = 'ventas';
const TABLA_ACTUALES = 'ventas_actuales';
const TABLA_CORTES = 'historial_cortes';
const TABLA_DETALLE = 'historial_cortes_detalle';
const TABLA_INVENTARIO_CORTE = 'historial_cortes_inventario';

function normalizarTexto(string $texto): string
{
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $buscar = ['á', 'é', 'í', 'ó', 'ú', 'ñ'];
    $reemplazo = ['a', 'e', 'i', 'o', 'u', 'n'];
    return str_replace($buscar, $reemplazo, $texto);
}

function productoBase(string $producto): string
{
    $base = preg_replace('/\s*\+\s*extra\s*$/iu', '', $producto);
    return trim((string)$base);
}

function vasoPorTamano(string $tamano): string
{
    $t = normalizarTexto($tamano);
    if ($t === 'mediano') {
        return 'Vaso 12oz';
    }
    if ($t === 'grande') {
        return 'Vaso 16oz';
    }
    return 'Vasos 8oz';
}

function recetaConsumo(array $venta): array
{
    $categoria = normalizarTexto((string)($venta['categoria'] ?? ''));
    $tamano = normalizarTexto((string)($venta['tamano'] ?? 'chico'));
    $cantidad = max(1, (int)($venta['cantidad'] ?? 1));
    $producto = (string)($venta['producto'] ?? '');
    $base = productoBase($producto);
    $tieneExtra = normalizarTexto($producto) !== normalizarTexto($base);

    $aguaML = ['chico' => 235, 'mediano' => 355, 'grande' => 473];
    $nescafeGR = ['chico' => 4, 'mediano' => 7, 'grande' => 9];
    $azucarGR = ['chico' => 15, 'mediano' => 25, 'grande' => 30];
    $bolsasTe = ['chico' => 1, 'mediano' => 2, 'grande' => 3];

    $t = isset($aguaML[$tamano]) ? $tamano : 'chico';
    $consumo = [];

    if ($categoria === 'te') {
        $bolsas = $bolsasTe[$t] + ($tieneExtra ? 1 : 0);
        $consumo[$base] = ($consumo[$base] ?? 0) + ($bolsas * $cantidad);
        $consumo['Agua'] = ($consumo['Agua'] ?? 0) + ($aguaML[$t] * $cantidad);
        $vaso = vasoPorTamano($t);
        $consumo[$vaso] = ($consumo[$vaso] ?? 0) + (1 * $cantidad);
    } elseif ($categoria === 'cafe con leche') {
        $consumo[$base] = ($consumo[$base] ?? 0) + ($aguaML[$t] * $cantidad);
        $extraNescafe = $tieneExtra ? 2 : 0;
        $consumo['Nescafe Clasico'] = ($consumo['Nescafe Clasico'] ?? 0) + (($nescafeGR[$t] + $extraNescafe) * $cantidad);
        $vaso = vasoPorTamano($t);
        $consumo[$vaso] = ($consumo[$vaso] ?? 0) + (1 * $cantidad);
    } elseif ($categoria === 'nescafe') {
        $consumo[$base] = ($consumo[$base] ?? 0) + (1 * $cantidad);
        $consumo['Agua'] = ($consumo['Agua'] ?? 0) + ($aguaML[$t] * $cantidad);
        $consumo['Azucar'] = ($consumo['Azucar'] ?? 0) + ($azucarGR[$t] * $cantidad);
        $extraNescafe = $tieneExtra ? 2 : 0;
        $consumo['Nescafe Clasico'] = ($consumo['Nescafe Clasico'] ?? 0) + (($nescafeGR[$t] + $extraNescafe) * $cantidad);
        $vaso = vasoPorTamano($t);
        $consumo[$vaso] = ($consumo[$vaso] ?? 0) + (1 * $cantidad);
    }

    return $consumo;
}

function esStockBajo(float $stock, string $unidad): bool
{
    $u = normalizarTexto($unidad);
    if ($u === 'mililitros') {
        return $stock <= 300;
    }
    if ($u === 'litro') {
        return $stock <= 1;
    }
    if ($u === 'gramos') {
        return $stock <= 500;
    }
    if ($u === 'kilo') {
        return $stock <= 2;
    }
    if ($u === 'piezas') {
        return $stock <= 20;
    }
    return false;
}

function descontarInventarioPorVentas(mysqli $conn, int $corteId): array
{
    $resVentas = $conn->query(
        "SELECT producto, categoria, tamano, cantidad
         FROM `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "`"
    );

    $consumoAcumulado = [];
    while ($venta = $resVentas->fetch_assoc()) {
        $consumo = recetaConsumo($venta);
        foreach ($consumo as $insumo => $valor) {
            if ($valor <= 0) {
                continue;
            }
            $consumoAcumulado[$insumo] = ($consumoAcumulado[$insumo] ?? 0) + $valor;
        }
    }

    if (empty($consumoAcumulado)) {
        return ['aplicado' => 0, 'faltantes' => [], 'costo_total' => 0.0];
    }

    $resInv = $conn->query("SELECT id, nombre, stock, unidad, costo FROM inventario FOR UPDATE");
    $inventarioNorm = [];
    while ($row = $resInv->fetch_assoc()) {
        $inventarioNorm[normalizarTexto($row['nombre'])] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'stock' => (float)$row['stock'],
            'unidad' => (string)$row['unidad'],
            'costo' => (float)$row['costo']
        ];
    }

    $stmtUpdate = $conn->prepare("UPDATE inventario SET stock = ? WHERE id = ?");
    $stmtInsertHist = $conn->prepare(
        "INSERT INTO `" . DB_VENTAS . "`.`" . TABLA_INVENTARIO_CORTE . "`
        (corte_id, inventario_id, nombre_insumo, unidad, costo_unitario, stock_antes, consumo, stock_despues, costo_consumido, stock_bajo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $aplicados = 0;
    $faltantes = [];
    $costoTotal = 0.0;

    foreach ($consumoAcumulado as $insumo => $valor) {
        $key = normalizarTexto($insumo);
        if (!isset($inventarioNorm[$key])) {
            $faltantes[] = $insumo;
            continue;
        }

        $inv = $inventarioNorm[$key];
        $id = (int)$inv['id'];
        $monto = (float)$valor;
        $stockAntes = (float)$inv['stock'];
        $stockDespues = max(0, $stockAntes - $monto);
        $costoUnit = (float)$inv['costo'];
        $consumoReal = min($stockAntes, $monto);
        $costoConsumido = round($consumoReal * $costoUnit, 2);
        $costoTotal += $costoConsumido;
        $stockBajo = esStockBajo($stockDespues, (string)$inv['unidad']) ? 1 : 0;

        $stmtUpdate->bind_param('di', $stockDespues, $id);
        $stmtUpdate->execute();

        $nombreInsumo = (string)$inv['nombre'];
        $unidad = (string)$inv['unidad'];
        $stmtInsertHist->bind_param(
            'iissdddddi',
            $corteId,
            $id,
            $nombreInsumo,
            $unidad,
            $costoUnit,
            $stockAntes,
            $monto,
            $stockDespues,
            $costoConsumido,
            $stockBajo
        );
        $stmtInsertHist->execute();

        $inventarioNorm[$key]['stock'] = $stockDespues;
        $aplicados++;
    }

    return ['aplicado' => $aplicados, 'faltantes' => $faltantes, 'costo_total' => round($costoTotal, 2)];
}

function ensureSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `folio` INT NOT NULL,
            `producto` VARCHAR(120) NOT NULL,
            `categoria` VARCHAR(80) NOT NULL DEFAULT 'General',
            `tamano` VARCHAR(30) NOT NULL,
            `precio` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `cantidad` INT NOT NULL DEFAULT 1,
            `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `metodo_pago` VARCHAR(30) NOT NULL DEFAULT 'Efectivo',
            `factura` TINYINT(1) NOT NULL DEFAULT 0,
            `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fecha_dia` DATE NOT NULL,
            `total_ventas` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `numero_operaciones` INT NOT NULL DEFAULT 0,
            `metodo_predominante` VARCHAR(30) NOT NULL DEFAULT 'Efectivo',
            `efectivo_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `tarjeta_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `tarjeta_regalo_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `total_productos` INT NOT NULL DEFAULT 0,
            `hora_corte` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `corte_id` INT NOT NULL,
            `folio` INT NOT NULL DEFAULT 0,
            `producto` VARCHAR(120) NOT NULL,
            `categoria` VARCHAR(80) NOT NULL DEFAULT 'General',
            `tamano` VARCHAR(30) NOT NULL,
            `precio` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `cantidad` INT NOT NULL DEFAULT 1,
            `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
            `metodo_pago` VARCHAR(30) NOT NULL DEFAULT 'Efectivo',
            `factura` TINYINT(1) NOT NULL DEFAULT 0,
            `fecha_hora` DATETIME NOT NULL,
            INDEX (`corte_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_INVENTARIO_CORTE . "` (
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

    $altersActuales = [
        'folio' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` ADD COLUMN `folio` INT NOT NULL DEFAULT 0 AFTER `id`",
        'categoria' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` ADD COLUMN `categoria` VARCHAR(80) NOT NULL DEFAULT 'General' AFTER `producto`",
        'metodo_pago' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` ADD COLUMN `metodo_pago` VARCHAR(30) NOT NULL DEFAULT 'Efectivo' AFTER `subtotal`",
        'factura' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` ADD COLUMN `factura` TINYINT(1) NOT NULL DEFAULT 0 AFTER `metodo_pago`"
    ];

    foreach ($altersActuales as $columna => $sqlAlter) {
        $check = $conn->query("SHOW COLUMNS FROM `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "` LIKE '$columna'");
        if ($check && $check->num_rows === 0) {
            $conn->query($sqlAlter);
        }
    }

    $altersCorte = [
        'efectivo_total' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` ADD COLUMN `efectivo_total` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `metodo_predominante`",
        'tarjeta_total' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` ADD COLUMN `tarjeta_total` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `efectivo_total`",
        'tarjeta_regalo_total' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` ADD COLUMN `tarjeta_regalo_total` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `tarjeta_total`",
        'total_productos' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` ADD COLUMN `total_productos` INT NOT NULL DEFAULT 0 AFTER `tarjeta_regalo_total`"
    ];

    foreach ($altersCorte as $columna => $sqlAlter) {
        $check = $conn->query("SHOW COLUMNS FROM `" . DB_VENTAS . "`.`" . TABLA_CORTES . "` LIKE '$columna'");
        if ($check && $check->num_rows === 0) {
            $conn->query($sqlAlter);
        }
    }

    $checkCategoriaDetalle = $conn->query("SHOW COLUMNS FROM `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` LIKE 'categoria'");
    if ($checkCategoriaDetalle && $checkCategoriaDetalle->num_rows === 0) {
        $conn->query("ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "` ADD COLUMN `categoria` VARCHAR(80) NOT NULL DEFAULT 'General' AFTER `producto`");
    }
}

$conn->begin_transaction();

try {
    ensureSchema($conn);

    $res = $conn->query(
        "SELECT
            COALESCE(SUM(subtotal), 0) AS total_ventas,
            COUNT(DISTINCT folio) AS operaciones,
            COALESCE(SUM(cantidad), 0) AS total_productos,
            COALESCE(SUM(CASE WHEN LOWER(metodo_pago) = 'efectivo' THEN subtotal ELSE 0 END), 0) AS total_efectivo,
            COALESCE(SUM(CASE WHEN LOWER(metodo_pago) = 'tarjeta' THEN subtotal ELSE 0 END), 0) AS total_tarjeta,
            COALESCE(SUM(CASE WHEN LOWER(metodo_pago) = 'tarjeta de regalo' THEN subtotal ELSE 0 END), 0) AS total_regalo
        FROM `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "`"
    );
    $datos = $res->fetch_assoc();

    $totalVentas = (float)$datos['total_ventas'];
    $operaciones = (int)$datos['operaciones'];
    $totalProductos = (int)$datos['total_productos'];
    $totalEfectivo = (float)$datos['total_efectivo'];
    $totalTarjeta = (float)$datos['total_tarjeta'];
    $totalRegalo = (float)$datos['total_regalo'];

    if ($totalVentas <= 0 || $operaciones <= 0) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'No hay ventas registradas para realizar el corte.'
        ]);
        exit;
    }

    $metodoPredominante = 'Efectivo';
    $maxMetodo = $totalEfectivo;
    if ($totalTarjeta > $maxMetodo) {
        $maxMetodo = $totalTarjeta;
        $metodoPredominante = 'Tarjeta';
    }
    if ($totalRegalo > $maxMetodo) {
        $metodoPredominante = 'Tarjeta de regalo';
    }

    $fechaDia = date('Y-m-d');
    $horaCorte = date('Y-m-d H:i:s');

    $stmtCorte = $conn->prepare(
        "INSERT INTO `" . DB_VENTAS . "`.`" . TABLA_CORTES . "`
        (fecha_dia, total_ventas, numero_operaciones, metodo_predominante, efectivo_total, tarjeta_total, tarjeta_regalo_total, total_productos, hora_corte)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmtCorte->bind_param(
        'sdisdddis',
        $fechaDia,
        $totalVentas,
        $operaciones,
        $metodoPredominante,
        $totalEfectivo,
        $totalTarjeta,
        $totalRegalo,
        $totalProductos,
        $horaCorte
    );
    $stmtCorte->execute();
    $corteId = (int)$conn->insert_id;

    $stmtDetalle = $conn->prepare(
        "INSERT INTO `" . DB_VENTAS . "`.`" . TABLA_DETALLE . "`
        (corte_id, folio, producto, categoria, tamano, precio, cantidad, subtotal, metodo_pago, factura, fecha_hora)
        SELECT ?, folio, producto, categoria, tamano, precio, cantidad, subtotal, metodo_pago, factura, fecha_hora
        FROM `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "`"
    );
    $stmtDetalle->bind_param('i', $corteId);
    $stmtDetalle->execute();

    $descuentoInventario = descontarInventarioPorVentas($conn, $corteId);

    $conn->query("DELETE FROM `" . DB_VENTAS . "`.`" . TABLA_ACTUALES . "`");

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Corte de caja realizado con exito.',
        'corte_id' => $corteId,
        'total_ventas' => round($totalVentas, 2),
        'operaciones' => $operaciones,
        'total_productos' => $totalProductos,
        'efectivo_total' => round($totalEfectivo, 2),
        'tarjeta_total' => round($totalTarjeta, 2),
        'tarjeta_regalo_total' => round($totalRegalo, 2),
        'metodo_predominante' => $metodoPredominante,
        'insumos_descontados' => $descuentoInventario['aplicado'],
        'insumos_faltantes' => $descuentoInventario['faltantes'],
        'costo_insumos_total' => $descuentoInventario['costo_total'],
        'ganancia_estimada' => round($totalVentas - $descuentoInventario['costo_total'], 2)
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al realizar el corte: ' . $e->getMessage()
    ]);
}
