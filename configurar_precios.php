<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Precios | Adeenca</title>
    <link rel="stylesheet" href="css/precios.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>⚙️ Ajuste de Precios de Venta</h1>
            <a href="analisis_costos.php" class="btn-volver">📊 Ver Análisis Total</a>
        </header>

        <div id="mensaje-status" style="display:none; padding:10px; margin:10px 0; border-radius:5px; text-align:center;"></div>

        <table class="tabla-analisis">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Tamaño</th>
                    <th>Costo (Ref)</th>
                    <th>Precio de Venta</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="tabla-config">
                </tbody>
        </table>
    </div>

    <script src="js/configurar.js"></script>

    
</body>
</html>