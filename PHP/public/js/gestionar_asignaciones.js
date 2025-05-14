document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".actions form").forEach(form => {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: "POST",
                body: formData
            });

            if (response.ok) {
                // Opcional: Actualizar solo este revisor dinámicamente
                location.reload();  // O mejor: fetch para actualizar sin recargar
            } else {
                alert("Ocurrió un error al procesar la acción.");
            }
        });
    });
});


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.accion-select').forEach(select => {
        select.addEventListener('change', () => {
            const action = select.value;
            const userId = select.dataset.id;
            const articuloSelect = document.querySelector(`#articulos-${userId}`);

            // Ocultar todas las opciones
            articuloSelect.querySelectorAll('option[data-mode]').forEach(opt => {
                opt.style.display = 'none';
            });

            // Mostrar solo las del modo seleccionado
            articuloSelect.querySelectorAll(`option[data-mode="${action}"]`).forEach(opt => {
                opt.style.display = 'block';
            });

            articuloSelect.selectedIndex = 0;
        });
    });
});

function cambiarVista(vista) {
    const url = new URL(window.location.href);
    url.searchParams.set("view", vista);
    window.location.href = url.toString();
}
