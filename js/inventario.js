/**
 * CONSOLIDADO DE INVENTARIO Y ACCESO POS
 */

// 1. Manejo del formulario de nuevo producto
document.addEventListener("DOMContentLoaded", () => {
    const formInventario = document.getElementById("formInventario");
    if (formInventario) {
        formInventario.addEventListener("submit", function(e) {
            const boton = this.querySelector('button[type="submit"]');
            const cantidadInput = this.querySelector('input[name="cantidad"]');
            const cantidad = parseFloat(cantidadInput.value);

            if (isNaN(cantidad) || cantidad <= 0) {
                alert("⚠️ Por favor, ingresa una cantidad válida mayor a 0.");
                e.preventDefault();
                return;
            }

            boton.disabled = true;
            boton.innerHTML = "⌛ Guardando...";
        });
    }

    // 2. Animación de entrada y Auto-ocultar notificaciones
    const fadeElements = document.querySelectorAll(".fade");
    fadeElements.forEach(el => el.classList.add("show"));

    const alerta = document.getElementById('notificacion');
    if (alerta) {
        setTimeout(() => {
            alerta.style.transition = "opacity 0.6s ease";
            alerta.style.opacity = "0";
            setTimeout(() => {
                alerta.remove();
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 600);
        }, 3000);
    }
});

/**
 * ACTUALIZAR STOCK RÁPIDO
 */
function actualizar(id) {
    let nuevaCantidad = prompt("Introduce la nueva cantidad de stock:");
    if (nuevaCantidad !== null && nuevaCantidad.trim() !== "" && !isNaN(nuevaCantidad)) {
        fetch("api/actualizar_producto.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${encodeURIComponent(id)}&cantidad=${encodeURIComponent(nuevaCantidad)}`
        })
        .then(() => {
            window.location.href = "inventario.php?actualizado=1";
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Error de conexión al actualizar el stock");
        });
    }
}

/**
 * ACCESO SEGURO AL POS
 */
function accederPOS() {
    const passwordCorrecta = "12345"; // Tu contraseña establecida
    const input = prompt("🔐 Acceso Restringido\nIngrese la contraseña para entrar al Punto de Venta:");
    
    if (input === null) return; 
    
    if (input === passwordCorrecta) {
        window.location.href = "pos.php";
    } else {
        alert("❌ Contraseña incorrecta. Acceso denegado.");
    }
}