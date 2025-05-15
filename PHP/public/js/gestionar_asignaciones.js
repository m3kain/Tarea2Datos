document.addEventListener("DOMContentLoaded", () => {
    // Resaltar filas con <=1 evaluaciones completadas
    document.querySelectorAll("tr[id^='row-']").forEach(row => {
        const revisores = parseInt(row.dataset.revisores || "0");
        if (revisores <= 2) {
            row.style.backgroundColor = "#fff3cd";
            row.style.borderLeft = "5px solid #ffc107";
        }
        
    });

    // Submits para formularios de .actions
    document.querySelectorAll(".actions form").forEach(form => {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: "POST",
                body: formData
            });

            if (response.ok) {
                location.reload();
            } else {
                alert("Ocurrió un error al procesar la acción.");
            }
        });
    });

    // Filtro por tipo de acción
    document.querySelectorAll('.accion-select').forEach(select => {
        select.addEventListener('change', () => {
            const action = select.value;
            const userId = select.dataset.id;
            const articuloSelect = document.querySelector(`#articulos-${userId}`);

            articuloSelect.querySelectorAll('option[data-mode]').forEach(opt => {
                opt.style.display = 'none';
            });

            articuloSelect.querySelectorAll(`option[data-mode="${action}"]`).forEach(opt => {
                opt.style.display = 'block';
            });

            articuloSelect.selectedIndex = 0;
        });
    });

});


function showCustomConfirm(message, callback) {
    const modal = document.getElementById("custom-confirm");
    const msg = document.getElementById("custom-confirm-msg");
    msg.textContent = message;

    modal.style.display = "flex";

    const confirmYes = document.getElementById("confirm-yes");
    const confirmNo = document.getElementById("confirm-no");

    const closeModal = () => {
        modal.style.display = "none";
        confirmYes.onclick = null;
        confirmNo.onclick = null;
    };

    confirmYes.onclick = () => {
        closeModal();
        callback(true);
    };

    confirmNo.onclick = () => {
        closeModal();
        callback(false);
    };
}



function cambiarVista(vista) {
    const url = new URL(window.location.href);
    url.searchParams.set("view", vista);
    window.location.href = url.toString();
}


