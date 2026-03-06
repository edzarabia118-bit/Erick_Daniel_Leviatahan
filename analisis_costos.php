<!DOCTYPE html>



<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Costos | Adeenca</title>
    <link rel="stylesheet" href="css/precios.css">
    <style>
        /* Estilos para que no abra otra pestaña y se imprima bien */
        .solo-print { display: none; }

        @media print {
            .no-print, .btn-acciones, header, input { display: none !important; }
            .solo-print { display: inline; font-weight: bold; }
            body { background: white; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; font-size: 10pt; }
            .container { width: 100%; max-width: none; margin: 0; }
        }

        .btn-acciones { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn-imprimir { background: #5d4037; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <header class="no-print">
    <h1>📊 Matriz de Costos y Rentabilidad</h1>
    <div class="btn-acciones">
        <a href="inventario.php" class="btn-volver">🏠 Inventario</a>
        <a href="configurar_precios.php" class="btn-config">⚙️ Configurar Precios</a>
        <button onclick="window.print()" class="btn-imprimir">🖨️ Imprimir Reporte</button>
    </div>
</header>

        <table class="tabla-analisis">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Producto</th>
                    <th>Tamaño</th>
                    <th>Costo</th>
                    <th>Venta</th>
                    <th>Ganancia</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody id="tabla-cuerpo">
                </tbody>
        </table>
    </div>

    <script src="js/precios.js"></script>
</body>
</html>