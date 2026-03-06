document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formReceta');
    const selectProducto = document.getElementById('id_producto');
    const detalleDiv = document.getElementById('detalleReceta');

    // Función para cargar los ingredientes de un producto seleccionado
    const cargarReceta = () => {
        const id = selectProducto.value;
        if (!id) return;

        fetch(`api/obtener_receta.php?id=${id}`)
            .then(res => res.text())
            .then(data => {
                detalleDiv.innerHTML = data;
            });
    };

    selectProducto.addEventListener('change', cargarReceta);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        fetch('api/guardar_receta.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                alert('Insumo vinculado con éxito');
                cargarReceta(); // Refrescar la lista
            }
        });
    });
});