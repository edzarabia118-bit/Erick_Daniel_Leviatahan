const productos = [
    {nombre:"Playera 1", precio:450, imagen:"img/playera1.jpg"},
    {nombre:"Playera 2", precio:450, imagen:"img/playera2.jpg"},
    {nombre:"Playera 3", precio:450, imagen:"img/playera3.jpg"},

    {nombre:"Taza 1", precio:150, imagen:"img/taza1.jpg"},
    {nombre:"Taza 2", precio:150, imagen:"img/taza2.jpg"},
    {nombre:"Taza 3", precio:150, imagen:"img/taza3.jpg"},

    {nombre:"Vaso 1", precio:150, imagen:"img/vaso1.jpg"},
    {nombre:"Vaso 2", precio:150, imagen:"img/vaso2.jpg"},
    {nombre:"Vaso 3", precio:150, imagen:"img/vaso3.jpg"},

    {nombre:"Cuaderno 1", precio:250, imagen:"img/cuaderno1.jpg"},
    {nombre:"Cuaderno 2", precio:250, imagen:"img/cuaderno2.jpg"},
    {nombre:"Cuaderno 3", precio:250, imagen:"img/cuaderno3.jpg"},
];

let carrito = [];

const contenedor = document.getElementById("contenedorProductos");

productos.forEach(producto=>{
    contenedor.innerHTML += `
        <div class="producto">
            <img src="${producto.imagen}">
            <h3>${producto.nombre}</h3>
            <p>$${producto.precio}</p>

            <div class="cantidad">
                <button onclick="cambiarCantidad(this,-1)">−</button>
                <input type="number" value="0" min="0">
                <button onclick="cambiarCantidad(this,1)">+</button>
            </div>

            <button class="agregar"
                onclick="agregarCarrito('${producto.nombre}',${producto.precio},this)">
                Agregar al carrito
            </button>
        </div>
    `;
});

function cambiarCantidad(btn, valor){
    let input = btn.parentElement.querySelector("input");
    let nuevaCantidad = parseInt(input.value) + valor;
    if(nuevaCantidad >= 0){
        input.value = nuevaCantidad;
    }
}

function agregarCarrito(nombre, precio, btn){
    let input = btn.parentElement.querySelector("input");
    let cantidad = parseInt(input.value);

    if(cantidad > 0){
        carrito.push({
            nombre: nombre,
            precio: precio,
            cantidad: cantidad
        });

        alert("Producto agregado 🛒");

        // 🔥 Reiniciar contador a 0
        input.value = 0;
    }
}

const btnCarrito = document.getElementById("btnCarrito");
const modal = document.getElementById("modalCarrito");
const cerrar = document.getElementById("cerrarCarrito");

btnCarrito.onclick = ()=>{
    modal.style.display="flex";
    mostrarCarrito();
};

cerrar.onclick = ()=> modal.style.display="none";

function mostrarCarrito(){
    let lista = document.getElementById("listaCarrito");
    let total = 0;
    lista.innerHTML="";

    carrito.forEach(item=>{
        let subtotal = item.precio * item.cantidad;
        total += subtotal;

        lista.innerHTML += `
            <p>${item.nombre} x${item.cantidad} - $${subtotal}</p>
        `;
    });

    document.getElementById("totalCarrito").innerText="Total: $"+total;
}

function finalizarCompra(){
    if(carrito.length === 0){
        alert("Tu carrito está vacío 😅");
        return;
    }

    alert("Gracias por tu compra 🔥\nPronto nos pondremos en contacto contigo.");
    carrito=[];
    modal.style.display="none";
}