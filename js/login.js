function verPassword(){
    let pass = document.getElementById("password");

    pass.type =
        pass.type === "password"
        ? "text"
        : "password";
}

/* animación carga */
document.getElementById("loginForm")
.addEventListener("submit", function(){

    const btn = document.getElementById("btnLogin");

    btn.innerHTML = "Entrando...";
    btn.disabled = true;
});
