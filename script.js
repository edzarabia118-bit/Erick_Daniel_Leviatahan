const btnContacto = document.getElementById("btnContacto");
const modal = document.getElementById("modalContacto");
const cerrar = document.getElementById("cerrarModal");
const form = document.getElementById("formContacto");

btnContacto.addEventListener("click", () => {
    modal.style.display = "flex";
});

cerrar.addEventListener("click", () => {
    modal.style.display = "none";
});

window.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
});

form.addEventListener("submit", function(e) {
    e.preventDefault();

    emailjs.sendForm(
        "service_d0egm8k",
        "template_th7wupr",
        this
    ).then(() => {
        alert("Mensaje enviado correctamente 🚀");
        form.reset();
        modal.style.display = "none";
    }, (error) => {
        alert("Error al enviar el mensaje 😢");
        console.log(error);
    });
});