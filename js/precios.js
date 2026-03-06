// js/precios.js
// Usamos rutas relativas al archivo HTML que llama al script
const apis = ['api/tes.php', 'api/cafe_leche.php', 'api/nescafe.php'];
const tbody = document.getElementById('tabla-cuerpo');

async function cargarCostos() {
    try {
        // Limpiar tabla antes de cargar
        tbody.innerHTML = '<tr><td colspan="7">Cargando datos...</td></tr>';

        const resPrecios = await fetch('api/precios_productos.php');
        const preciosVenta = await resPrecios.json();

        tbody.innerHTML = ''; // Limpiar mensaje de carga

        for (const url of apis) {
            const res = await fetch(url);
            const data = await res.json();
            
            data.forEach(item => {
                const categoriaKey = item.categoria; 
                const precioVenta = (preciosVenta[categoriaKey] && preciosVenta[categoriaKey][item.tamano]) 
                                    ? preciosVenta[categoriaKey][item.tamano] : 0;
                
                const gananciaPesos = precioVenta - item.costo;
                const margenPorcentaje = precioVenta > 0 ? (gananciaPesos / precioVenta) * 100 : 0;
                const colorMargen = margenPorcentaje < 40 ? '#d32f2f' : '#2e7d32';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.categoria}</td>
                    <td><strong>${item.nombre}</strong></td>
                    <td>${item.tamano}</td>
                    <td class="costo-celda">$${item.costo.toFixed(2)}</td>
                    <td>
                        <input type="number" value="${precioVenta.toFixed(2)}" 
                               class="input-precio no-print" 
                               data-costo="${item.costo}"
                               oninput="recalcularFila(this)">
                        <span class="solo-print">$${precioVenta.toFixed(2)}</span>
                    </td>
                    <td class="ganancia-pesos">$${gananciaPesos.toFixed(2)}</td>
                    <td class="ganancia-porcentaje" style="color: ${colorMargen};">
                        ${Math.round(margenPorcentaje)}%
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" style="color:red;">Error al cargar datos. Revisa las rutas de las APIs.</td></tr>';
        console.error("Error:", error);
    }
}

function recalcularFila(input) {
    const fila = input.closest('tr');
    const costo = parseFloat(input.dataset.costo);
    const venta = parseFloat(input.value) || 0;
    const ganancia = venta - costo;
    const porcentaje = venta > 0 ? (ganancia / venta) * 100 : 0;
    
    fila.querySelector('.ganancia-pesos').textContent = `$${ganancia.toFixed(2)}`;
    const celdaPct = fila.querySelector('.ganancia-porcentaje');
    celdaPct.textContent = `${Math.round(porcentaje)}%`;
    celdaPct.style.color = porcentaje < 40 ? '#d32f2f' : '#2e7d32';
    // Actualizar el texto de impresión también
    fila.querySelector('.solo-print').textContent = `$${venta.toFixed(2)}`;
}

cargarCostos();