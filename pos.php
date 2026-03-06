<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adeenca POS | Punto de Venta</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/pos_style.css">
</head>
<body>
    <div class="pos-wrapper">
        <section class="products-panel">
            <header class="pos-header">
    <div class="brand">
        <h1>Adeenca <span>POS</span></h1>
    </div>
    
    <div class="header-controls" style="display: flex; gap: 15px; align-items: center;">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="🔍 Buscar producto..." onkeyup="buscarProducto()">
        </div>
        <a href="inventario.php" class="btn-nav-inventario">
            📦 Inventario
        </a>
    </div>
</header>
            
            <nav class="category-filters">
                <div class="category-container">
                    <button class="filter-btn active" onclick="filtrarCategoria('Todos', this)">🌟 Todos</button>
                    <button class="filter-btn" onclick="filtrarCategoria('Te', this)">🍃 Tés</button>
                    <button class="filter-btn" onclick="filtrarCategoria('Cafe con Leche', this)">☕ Cafés</button>
                    <button class="filter-btn" onclick="filtrarCategoria('Nescafe', this)">⚡ Nescafé</button>
                </div>
            </nav>

            <div id="grid-productos" class="grid-productos">
                </div>
        </section>

        <aside class="ticket-panel">
            <div class="ticket-header">
                <h2>📋 Pedido Actual</h2>
            </div>
            
            <div id="ticket-items" class="ticket-items">
                </div>
            
            <div class="total-section">
                <div class="total-row">
                    <span>TOTAL</span>
                    <span id="total-monto" class="total-price">$0.00</span>
                </div>
                
                <div class="action-buttons">
                    <button id="btn-finalizar" class="btn-main btn-cobrar" onclick="abrirModalCobro()">
    💰 COBRAR
</button>
                    <button id="realizar-corte" class="btn-main btn-corte">
                        📊 CORTE DE CAJA
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <div id="modal-cobro" class="modal" style="display:none;">
        <div class="modal-content">
            <h2>Finalizar Venta</h2>
            <div class="payment-grid">
                <button class="payment-btn" data-metodo="Efectivo" onclick="seleccionarPago('Efectivo')">💵 Efectivo</button>
                <button class="payment-btn" data-metodo="Tarjeta" onclick="seleccionarPago('Tarjeta')">💳 Tarjeta</button>
                <button class="payment-btn" data-metodo="Tarjeta de regalo" onclick="seleccionarPago('Tarjeta de regalo')">🎁 Tarjeta de regalo</button>
            </div>

            <div id="detalles-pago" style="display:none; margin-top:15px;">
                <div id="campos-efectivo" style="display:none;">
                    <div class="efectivo-grid">
                        <div class="campo-efectivo">
                            <label for="monto-recibido">Monto recibido</label>
                            <input type="number" id="monto-recibido" min="0" step="0.50" placeholder="0.00" oninput="actualizarCambioEfectivo()">
                        </div>
                        <div class="campo-efectivo">
                            <label>Total a pagar</label>
                            <p id="total-pagar-efectivo">$0.00</p>
                        </div>
                        <div class="campo-efectivo">
                            <label>Cambio</label>
                            <p id="cambio-efectivo">$0.00</p>
                        </div>
                    </div>
                    <div id="aviso-efectivo" class="aviso-efectivo"></div>
                    <div class="denominaciones-wrap">
                        <p class="denom-title">Desglose de denominaciones para cambio</p>
                        <div id="desglose-cambio" class="denominaciones-list"></div>
                    </div>
                </div>

                <div id="campos-tarjeta" style="display:none;">
                    <input type="text" id="tarjeta-4" placeholder="Últimos 4 dígitos" maxlength="4">
                    <div style="display:flex; gap:5px; margin-top:5px;">
                        <select id="tarjeta-tipo"><option>Visa</option><option>Mastercard</option></select>
                        <select id="tarjeta-credito"><option>Debito</option><option>Credito</option></select>
                    </div>
                </div>
                <label style="margin-top:10px; display:block;">
                    <input type="checkbox" id="requiere-factura"> ¿Factura?
                </label>
            </div>

            <div class="modal-footer" style="margin-top:20px; display:flex; gap:10px;">
                <button class="btn-cancelar" onclick="cerrarModal()">Volver</button>
                <button onclick="procesarVentaFinal()" class="btn-main btn-cobrar">
    CONFIRMAR PAGO
</button>
            </div>
        </div>
    </div>

    <script src="js/pos_logic.js"></script>
</body>
</html>
