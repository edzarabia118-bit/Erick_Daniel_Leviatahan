<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

const DB_VENTAS = 'ventas';
const TABLA_VENTAS_ACTUALES = 'ventas_actuales';

function ensureVentasActualesSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` (
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

    $columnas = [
        'folio' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` ADD COLUMN `folio` INT NOT NULL DEFAULT 0 AFTER `id`",
        'categoria' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` ADD COLUMN `categoria` VARCHAR(80) NOT NULL DEFAULT 'General' AFTER `producto`",
        'metodo_pago' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` ADD COLUMN `metodo_pago` VARCHAR(30) NOT NULL DEFAULT 'Efectivo' AFTER `subtotal`",
        'factura' => "ALTER TABLE `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` ADD COLUMN `factura` TINYINT(1) NOT NULL DEFAULT 0 AFTER `metodo_pago`"
    ];

    foreach ($columnas as $columna => $sqlAlter) {
        $check = $conn->query("SHOW COLUMNS FROM `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "` LIKE '$columna'");
        if ($check && $check->num_rows === 0) {
            $conn->query($sqlAlter);
        }
    }
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['items']) || !is_array($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'No hay datos de venta.']);
    exit;
}

$metodo = trim((string)($data['metodo'] ?? 'Efectivo'));
$metodosPermitidos = ['Efectivo', 'Tarjeta', 'Tarjeta de regalo'];
if (!in_array($metodo, $metodosPermitidos, true)) {
    $metodo = 'Efectivo';
}

$factura = !empty($data['factura']) ? 1 : 0;

$conn->begin_transaction();

try {
    ensureVentasActualesSchema($conn);

    $resFolio = $conn->query("SELECT COALESCE(MAX(folio), 0) AS ultimo FROM `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "`");
    $row = $resFolio->fetch_assoc();
    $nuevoFolio = ((int)$row['ultimo']) + 1;

    $stmt = $conn->prepare(
        "INSERT INTO `" . DB_VENTAS . "`.`" . TABLA_VENTAS_ACTUALES . "`
        (folio, producto, categoria, tamano, precio, cantidad, subtotal, metodo_pago, factura, fecha_hora)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    $totalVenta = 0.0;
    foreach ($data['items'] as $item) {
        $producto = trim((string)($item['nombre'] ?? 'Producto'));
        $categoria = trim((string)($item['categoria'] ?? 'General'));
        $tamano = trim((string)($item['tamano'] ?? 'Chico'));
        $precio = (float)($item['precio'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 1);
        if ($cantidad < 1) {
            $cantidad = 1;
        }
        $subtotal = round($precio * $cantidad, 2);
        $totalVenta += $subtotal;

        $stmt->bind_param(
            'isssdidsi',
            $nuevoFolio,
            $producto,
            $categoria,
            $tamano,
            $precio,
            $cantidad,
            $subtotal,
            $metodo,
            $factura
        );
        $stmt->execute();
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'folio' => $nuevoFolio,
        'total' => round($totalVenta, 2),
        'metodo' => $metodo
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
