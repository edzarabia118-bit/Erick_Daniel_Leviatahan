// js/configurar.js

async function cargarConfiguracion() {
    try {
        // Obtenemos los precios actuales de la base de datos
        const resPrecios = await fetch('api/precios_productos.php');
        const precios = await resPrecios.json();

        const tbody = document.getElementById('tabla-config');
        tbody.innerHTML = '';

        const categorias = ['Té', 'Café con Leche', 'Nescafé'];
        const tamanos = ['Chico', 'Mediano', 'Grande'];

        categorias.forEach(cat => {
            tamanos.forEach(tam => {
                const row = document.createElement('tr');
                // Buscamos el precio en el JSON que devuelve el API
                const precioActual = (precios[cat] && precios[cat][tam]) ? precios[cat][tam] : 0;
                
                row.innerHTML = `
                    <td>${cat}</td>
                    <td>${tam}</td>
                    <td style="color: #8d6e63; font-style: italic;">Consultar Análisis...</td>
                    <td>
                        <input type="number" step="0.5" value="${precioActual}" 
                               id="p-${cat}-${tam}" class="input-precio">
                    </td>
                    <td>
                        <button onclick="guardarPrecio('${cat}', '${tam}')" class="btn-imprimir">💾 Guardar</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        });
    } catch (error) {
        console.error("Error al cargar configuración:", error);
    }
}

async function guardarPrecio(cat, tam) {
    const nuevoPrecio = document.getElementById(`p-${cat}-${tam}`).value;
    const formData = new FormData();
    formData.append('categoria', cat);
    formData.append('tamano', tam);
    formData.append('precio', nuevoPrecio);

    try {
        const res = await fetch('api/guardar_precios.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        const status = document.getElementById('mensaje-status');
        status.style.display = 'block';

        if(data.status === 'success') {
            status.textContent = `✅ ${cat} (${tam}) actualizado`;
            status.style.backgroundColor = '#d4edda';
            status.style.color = '#155724';
        } else {
            status.textContent = '❌ Error al guardar en BD';
            status.style.backgroundColor = '#f8d7da';
            status.style.color = '#721c24';
        }

        setTimeout(() => { status.style.display = 'none'; }, 3000);
    } catch (error) {
        console.error("Error en la petición:", error);
    }
}

// Iniciar carga al abrir la página
document.addEventListener('DOMContentLoaded', cargarConfiguracion);