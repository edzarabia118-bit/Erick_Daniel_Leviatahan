document.addEventListener("DOMContentLoaded", () => {
    const btnImprimir = document.getElementById("btnImprimir");

    if (btnImprimir) {
        btnImprimir.addEventListener("click", () => {
            // Podemos agregar un mensaje antes de imprimir
            console.log("Generando documento para impresión...");
            window.print();
        });
    }

    // Ejemplo: Resaltar filas con stock muy alto en el reporte
    const filas = document.querySelectorAll(".report-table tbody tr");
    filas.forEach(fila => {
        const stock = parseFloat(fila.cells[2].innerText);
        if (stock > 1000) {
            fila.style.backgroundColor = "rgba(46, 125, 50, 0.05)";
        }
    });
});