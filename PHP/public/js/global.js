// Transiciones entre páginas
document.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("fade-in");

    document.querySelectorAll("a[href]").forEach(link => {
        const href = link.getAttribute("href");
        if (href && !href.startsWith("#") && !link.hasAttribute("target")) {
            link.addEventListener("click", e => {
                e.preventDefault();
                document.body.classList.remove("fade-in");
                document.body.classList.add("fade-out");
                setTimeout(() => window.location.href = href, 300);
            });
        }
    });
});

// Mostrar toast
function mostrarNotificacion(mensaje, tipo = "info") {
    let toast = document.getElementById("toast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "toast";
        document.body.appendChild(toast);
    }

    toast.textContent = mensaje;
    toast.className = "toast show " + tipo;

    setTimeout(() => toast.classList.remove("show"), 4000);
}
