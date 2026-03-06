function verPassword(){
    let pass = document.getElementById("password");

    pass.type =
        pass.type === "password"
        ? "text"
        : "password";
}

document
.getElementById("registroForm")
.addEventListener("submit",function(){

    let btn=document.getElementById("btnRegistro");

    btn.innerHTML="Creando usuario...";
    btn.disabled=true;
});