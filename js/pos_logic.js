let carrito = [];
let extrasSeleccionados = {};
let metodoPagoSeleccionado = 'Efectivo';
const IVA_RATE = 0.16;
const DENOMINACIONES_MXN = [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.5];

const normalizar = (texto) =>
    (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

function filtrarCategoria(categoria, boton) {
    const botones = document.querySelectorAll('.filter-btn');
    botones.forEach((btn) => btn.classList.remove('active'));
    if (boton) boton.classList.add('active');

    const catBuscada = normalizar(categoria);
    const productos = document.querySelectorAll('.producto-card');

    productos.forEach((card) => {
        const catCard = normalizar(card.getAttribute('data-categoria'));
        card.style.display = catBuscada === 'todos' || catCard === catBuscada ? 'block' : 'none';
    });
}

function buscarProducto() {
    const input = document.getElementById('search-bar');
    if (!input) return;

    const termino = normalizar(input.value);
    const productos = document.querySelectorAll('.producto-card');

    productos.forEach((card) => {
        const nombre = normalizar(card.querySelector('h3')?.innerText || '');
        card.style.display = nombre.includes(termino) ? 'block' : 'none';
    });
}

function seleccionarTamano(btn, idUnico, precio, tamano) {
    const contenedor = btn.parentElement;
    contenedor.querySelectorAll('.size-btn').forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');

    const display = document.getElementById(`precio-display-${idUnico}`);
    if (!display) return;

    display.innerHTML = `<strong>$${Number(precio).toFixed(2)}</strong>`;
    display.setAttribute('data-precio', String(precio));
    display.setAttribute('data-tam', tamano);
}

function toggleExtraLocal(idUnico) {
    const btn = document.getElementById(`extra-${idUnico}`);
    if (!btn) return;

    extrasSeleccionados[idUnico] = !extrasSeleccionados[idUnico];
    btn.classList.toggle('active-extra', extrasSeleccionados[idUnico]);
}

function procesarAgregar(nombre, idUnico, categoria) {
    const display = document.getElementById(`precio-display-${idUnico}`);
    if (!display) return;

    let precio = parseFloat(display.getAttribute('data-precio') || '0');
    const tamano = display.getAttribute('data-tam') || 'Chico';
    let nombreFinal = nombre;

    if (extrasSeleccionados[idUnico]) {
        precio += 5;
        nombreFinal += ' + EXTRA';
    }

    carrito.push({ nombre: nombreFinal, precio, tamano, categoria });

    delete extrasSeleccionados[idUnico];
    const extraBtn = document.getElementById(`extra-${idUnico}`);
    if (extraBtn) extraBtn.classList.remove('active-extra');

    actualizarTicket();
}

function actualizarTicket() {
    const contenedor = document.getElementById('ticket-items');
    const totalMonto = document.getElementById('total-monto');
    if (!contenedor || !totalMonto) return;

    contenedor.innerHTML = '';
    let total = 0;

    carrito.forEach((item, index) => {
        total += item.precio;
        contenedor.innerHTML += `
            <div class="ticket-item" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>${item.nombre} <br><small>${item.tamano}</small></span>
                <span>$${item.precio.toFixed(2)} <button onclick="eliminarDelCarrito(${index})">X</button></span>
            </div>`;
    });

    totalMonto.innerText = `$${total.toFixed(2)}`;
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarTicket();
}

function abrirModalCobro() {
    if (carrito.length === 0) {
        alert('El carrito está vacío.');
        return;
    }

    document.getElementById('modal-cobro').style.display = 'flex';
    const recibidoInput = document.getElementById('monto-recibido');
    if (recibidoInput) recibidoInput.value = '';
    seleccionarPago('Efectivo');
}

function cerrarModal() {
    document.getElementById('modal-cobro').style.display = 'none';
}

function seleccionarPago(metodo) {
    metodoPagoSeleccionado = metodo;

    const detalles = document.getElementById('detalles-pago');
    const camposTarjeta = document.getElementById('campos-tarjeta');
    const camposEfectivo = document.getElementById('campos-efectivo');

    if (detalles) detalles.style.display = 'block';
    if (camposTarjeta) camposTarjeta.style.display = metodo === 'Tarjeta' ? 'block' : 'none';
    if (camposEfectivo) camposEfectivo.style.display = metodo === 'Efectivo' ? 'block' : 'none';

    const botones = document.querySelectorAll('.payment-btn');
    botones.forEach((btn) => {
        const activo = (btn.dataset.metodo || '').toLowerCase() === metodo.toLowerCase();
        btn.classList.toggle('active', activo);
    });

    if (metodo === 'Efectivo') {
        actualizarCambioEfectivo();
    }
}

function calcularDesgloseCambio(cambio) {
    let restante = Math.round(cambio * 100);
    const desglose = [];

    DENOMINACIONES_MXN.forEach((denom) => {
        const denomCentavos = Math.round(denom * 100);
        const cantidad = Math.floor(restante / denomCentavos);
        if (cantidad > 0) {
            desglose.push({ denominacion: denom, cantidad });
            restante -= cantidad * denomCentavos;
        }
    });

    return desglose;
}

function actualizarCambioEfectivo() {
    const total = carrito.reduce((sum, item) => sum + item.precio, 0);
    const inputRecibido = document.getElementById('monto-recibido');
    const totalPagar = document.getElementById('total-pagar-efectivo');
    const cambioDisplay = document.getElementById('cambio-efectivo');
    const aviso = document.getElementById('aviso-efectivo');
    const desgloseContainer = document.getElementById('desglose-cambio');

    if (!inputRecibido || !totalPagar || !cambioDisplay || !aviso || !desgloseContainer) return;

    const recibido = parseFloat(inputRecibido.value || '0');
    const cambio = recibido - total;

    totalPagar.innerText = `$${total.toFixed(2)}`;
    cambioDisplay.innerText = `$${Math.max(cambio, 0).toFixed(2)}`;

    if (recibido <= 0) {
        aviso.textContent = 'Ingresa el monto recibido.';
        aviso.className = 'aviso-efectivo aviso-pendiente';
        desgloseContainer.innerHTML = '';
        return;
    }

    if (cambio < 0) {
        aviso.textContent = `Faltan $${Math.abs(cambio).toFixed(2)} para completar el pago.`;
        aviso.className = 'aviso-efectivo aviso-error';
        desgloseContainer.innerHTML = '';
        return;
    }

    aviso.textContent = 'Pago completo.';
    aviso.className = 'aviso-efectivo aviso-ok';

    const desglose = calcularDesgloseCambio(cambio);
    if (desglose.length === 0) {
        desgloseContainer.innerHTML = '<span class="denom-item">Sin cambio</span>';
        return;
    }

    desgloseContainer.innerHTML = desglose
        .map((d) => `<span class="denom-item">${d.cantidad} x $${d.denominacion.toFixed(2)}</span>`)
        .join('');
}

async function procesarVentaFinal() {
    const total = carrito.reduce((sum, item) => sum + item.precio, 0);
    const requiereFactura = document.getElementById('requiere-factura')?.checked ? 1 : 0;
    const itemsVendidos = carrito.map((item) => ({ ...item }));

    let recibidoEfectivo = null;
    let cambioEfectivo = null;

    if (metodoPagoSeleccionado === 'Efectivo') {
        const recibidoInput = document.getElementById('monto-recibido');
        recibidoEfectivo = parseFloat(recibidoInput?.value || '0');
        cambioEfectivo = recibidoEfectivo - total;

        if (isNaN(recibidoEfectivo) || recibidoEfectivo <= 0) {
            alert('Ingresa el monto recibido en efectivo.');
            return;
        }
        if (cambioEfectivo < 0) {
            alert(`Pago incompleto. Faltan $${Math.abs(cambioEfectivo).toFixed(2)}.`);
            return;
        }
    }

    const datosVenta = {
        total,
        items: carrito,
        factura: requiereFactura,
        metodo: metodoPagoSeleccionado,
        recibido_efectivo: recibidoEfectivo,
        cambio_efectivo: cambioEfectivo
    };

    try {
        const res = await fetch('api/guardar_venta.php', {
            method: 'POST',
            body: JSON.stringify(datosVenta),
            headers: { 'Content-Type': 'application/json' }
        });

        const r = await res.json();
        if (r.success) {
            alert(`Venta guardada. Folio: ${r.folio}`);

            try {
                const resTicket = await fetch('api/generar_ticket_boceto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        folio: r.folio,
                        total: r.total ?? total,
                        metodo: metodoPagoSeleccionado,
                        factura: requiereFactura,
                        iva_rate: IVA_RATE,
                        items: itemsVendidos
                    })
                });
                const ticket = await resTicket.json();
                if (ticket.success && ticket.url) {
                    window.open(ticket.url, '_blank');
                }
            } catch (ticketError) {
                console.error('No se pudo generar ticket boceto:', ticketError);
            }

            carrito = [];
            actualizarTicket();
            cerrarModal();
        } else {
            alert(`Error: ${r.error || 'No se pudo guardar la venta.'}`);
        }
    } catch (e) {
        alert('Error de conexión con api/guardar_venta.php');
    }
}

async function realizarCorteDeCaja() {
    const confirmar = confirm('Se realizara el corte y se vaciaran las ventas actuales. Deseas continuar?');
    if (!confirmar) return;

    try {
        const res = await fetch('api/realizar_corte.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const r = await res.json();

        if (r.status !== 'success') {
            alert(r.message || 'No se pudo realizar el corte.');
            return;
        }

        alert(`Corte realizado. Total: $${Number(r.total_ventas).toFixed(2)}`);
        const urlReporte = `reporte_corte.php?corte_id=${encodeURIComponent(r.corte_id)}`;
        window.location.href = urlReporte;
    } catch (error) {
        alert('Error de conexion con api/realizar_corte.php');
    }
}

async function cargarProductosPOS() {
    const grid = document.getElementById('grid-productos');
    if (!grid) return;

    grid.innerHTML = '<div style="padding:20px; text-align:center;">Cargando menu...</div>';

    try {
        const resPrecios = await fetch('api/precios_productos.php');
        if (!resPrecios.ok) throw new Error('No se pudo leer api/precios_productos.php');
        const preciosVenta = await resPrecios.json();

        const apis = [
            { url: 'api/tes.php', cat: 'Té' },
            { url: 'api/cafe_leche.php', cat: 'Café con Leche' },
            { url: 'api/nescafe.php', cat: 'Nescafé' }
        ];

        grid.innerHTML = '';
        let indexImg = 1;

        for (const api of apis) {
            try {
                const res = await fetch(api.url);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const rawText = await res.text();
                let rawData;
                try {
                    rawData = JSON.parse(rawText);
                } catch (parseErr) {
                    console.error(`Respuesta invalida en ${api.url}:`, rawText);
                    throw parseErr;
                }

                const productosAgrupados = {};
                rawData.forEach((item) => {
                    if (!item?.nombre) return;
                    if (!productosAgrupados[item.nombre]) {
                        productosAgrupados[item.nombre] = {
                            nombre: item.nombre,
                            categoria: item.categoria || api.cat
                        };
                    }
                });

                Object.values(productosAgrupados).forEach((item) => {
                    const idUnico = item.nombre.replace(/\s+/g, '-').replace(/[()]/g, '');

                    const categoriaPrecio = Object.keys(preciosVenta).find(
                        (k) => normalizar(k) === normalizar(item.categoria)
                    );
                    const catData = categoriaPrecio ? preciosVenta[categoriaPrecio] : {};

                    const pChico = parseFloat(catData?.Chico || 0);
                    const pMediano = parseFloat(catData?.Mediano || 0);
                    const pGrande = parseFloat(catData?.Grande || 0);

                    if (pChico <= 0 && pMediano <= 0 && pGrande <= 0) return;

                    const precioInicial = pChico || pMediano || pGrande;
                    const tamInicial = pChico ? 'Chico' : pMediano ? 'Mediano' : 'Grande';

                    const card = document.createElement('div');
                    card.className = 'producto-card';
                    card.setAttribute('data-categoria', item.categoria);

                    card.innerHTML = `
                        <img src="img/img${String(indexImg).padStart(2, '0')}.jpg" class="prod-img" onerror="this.src='img/default.jpg'">
                        <div class="card-info">
                            <h3>${item.nombre}</h3>
                            <div class="size-selector">
                                ${pChico > 0 ? `<button class="size-btn ${tamInicial === 'Chico' ? 'active' : ''}" onclick="seleccionarTamano(this, '${idUnico}', ${pChico}, 'Chico')">CH</button>` : ''}
                                ${pMediano > 0 ? `<button class="size-btn ${tamInicial === 'Mediano' ? 'active' : ''}" onclick="seleccionarTamano(this, '${idUnico}', ${pMediano}, 'Mediano')">MD</button>` : ''}
                                ${pGrande > 0 ? `<button class="size-btn ${tamInicial === 'Grande' ? 'active' : ''}" onclick="seleccionarTamano(this, '${idUnico}', ${pGrande}, 'Grande')">GD</button>` : ''}
                            </div>
                            <button class="extra-btn" id="extra-${idUnico}" onclick="toggleExtraLocal('${idUnico}')">
                                ${normalizar(item.categoria) === 'te' ? '+ Bolsa Extra' : '+ Extra'} ($5)
                            </button>
                            <p id="precio-display-${idUnico}" data-precio="${precioInicial}" data-tam="${tamInicial}">
                                <strong>$${precioInicial.toFixed(2)}</strong>
                            </p>
                            <button class="btn-agregar-prod" onclick="procesarAgregar('${item.nombre}', '${idUnico}', '${item.categoria}')">Agregar</button>
                        </div>
                    `;

                    grid.appendChild(card);
                    indexImg++;
                });
            } catch (err) {
                console.error(`Error cargando ${api.url}:`, err);
            }
        }
    } catch (error) {
        console.error('Error critico en carga de productos:', error);
        grid.innerHTML = '<div style="color:red; padding:12px;">No se pudieron cargar los productos.</div>';
    }
}

function initPOS() {
    cargarProductosPOS();

    const btnCorte = document.getElementById('realizar-corte');
    if (btnCorte) {
        btnCorte.addEventListener('click', realizarCorteDeCaja);
    }
}

document.addEventListener('DOMContentLoaded', initPOS);

Object.assign(window, {
    filtrarCategoria,
    buscarProducto,
    seleccionarTamano,
    toggleExtraLocal,
    procesarAgregar,
    eliminarDelCarrito,
    abrirModalCobro,
    cerrarModal,
    seleccionarPago,
    procesarVentaFinal,
    actualizarCambioEfectivo
});





