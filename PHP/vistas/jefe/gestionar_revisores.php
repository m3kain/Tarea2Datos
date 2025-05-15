<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../conexion.php');

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 1) {
    header("Location: ../dashboard.php");
    exit();
}

// Obtener revisores
$stmt = $conn->query("SELECT * FROM usuarios WHERE subclase IN (3,4)");
$revisores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener especializaciones por revisor
$especializaciones = [];
$stmt = $conn->query("SELECT e.id_usuario, a.titulo_area FROM especializacion e JOIN area a ON e.id_area = a.id_area");
foreach ($stmt as $esp) {
    $especializaciones[$esp['id_usuario']][] = $esp['titulo_area'];
}
$autores = $conn->query("SELECT id_usuario, nombre, email FROM usuarios WHERE subclase = 2")->fetchAll(PDO::FETCH_ASSOC);
$areas = $conn->query("SELECT * FROM area")->fetchAll(PDO::FETCH_ASSOC);

$view = $_GET['view'] ?? 'revisores';
?>
<link rel="stylesheet" href="../../public/css/gestionar_revisores.css">
<?php
$tituloPagina = 'Gestión de Revisores';
include_once(__DIR__ . '/../header.php');
?>

<head>
    <meta charset="UTF-8">
    <title>Gescon</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <style>
        .toast-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #dc3545;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(-20px);
            z-index: 9999;
            transition: opacity 0.4s ease, transform 0.4s ease;
            font-weight: bold;
            max-width: 300px;
        }

        .toast-popup.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-popup.hide {
            opacity: 0;
            transform: translateY(-20px);
        }
    </style>
</head>

<?php if (isset($_GET['error'])): ?>
<div id="toast-error" class="toast-popup show">
    <?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const toast = document.getElementById("toast-error");
    if (toast) {
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.add("hide");
            setTimeout(() => toast.remove(), 800);
        }, 4000);

        // Quitar ?error=... de la URL sin recargar
        const url = new URL(window.location);
        url.searchParams.delete("error");
        window.history.replaceState({}, document.title, url);
    }
});
</script>


<div class="agregar-revisor-panel">
    <h3>Agregar nuevo revisor</h3>
    <form method="POST" action="../../controladores/crear_revisor.php">
        <div class="input-row">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="password" placeholder="Contraseña" required>
        </div>

        <label>Especialidades:</label>
        <div class="checkbox-group">
            <?php foreach ($areas as $area): ?>
                <label><input type="checkbox" name="areas[]" value="<?= $area['id_area'] ?>"> <?= $area['titulo_area'] ?></label>
            <?php endforeach; ?>
        </div>

        <button type="submit">Agregar Revisor</button>
    </form>
</div>


<p>
    <a href="?view=revisores">Vista Revisores</a>| 
    <a href="?view=autores">Vista Autores</a>
</p>



<div id="modal-editar" class="modal hidden">
    <div class="modal-content">
        <button id="cerrar-panel-btn" style="float: right; background: transparent; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        <div id="modal-body">Cargando...</div> 
    </div>
</div>

<?php if ($view === 'revisores'): ?>
    <h3>Revisores Actuales</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Especializaciones</th>
            <th style="width: 150px;">Acciones</th>


        </tr>
        <?php foreach ($revisores as $rev): ?>
            <tr>
                <td><?= htmlspecialchars($rev['nombre']) ?></td>
                <td><?= htmlspecialchars($rev['email']) ?></td>
                <td><?= isset($especializaciones[$rev['id_usuario']]) ? implode(', ', $especializaciones[$rev['id_usuario']]) : 'Sin especialización' ?></td>
                <td>
                    <div class="accion-botones">
                        <form method="POST" action="../../controladores/eliminar_revisor.php" class="accion-form eliminar-revisor">
                            <button type="button" class="btn-editar boton-accion" title="Editar" data-id="<?= $rev['id_usuario'] ?>">Editar</button>
                            <input type="hidden" name="id_revisor" value="<?= $rev['id_usuario'] ?>">
                            <button type="submit" class="boton-accion btn-rojo btn-eliminar" title="Eliminar" data-id="<?= $rev['id_usuario'] ?>">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    

<?php elseif ($view === 'autores'): ?>
    <h3>Autores Existentes</h3>
    <form method="POST" action="../../controladores/ascender_revisor.php">
        <table border="1" cellpadding="6">
            <tr><th>Nombre</th><th>Email</th><th>Acción</th></tr>
            <?php foreach ($autores as $autor): ?>
                <tr>
                    <td><?= htmlspecialchars($autor['nombre']) ?></td>
                    <td><?= htmlspecialchars($autor['email']) ?></td>
                    <td>
                        <button type="submit" name="id_usuario" value="<?= $autor['id_usuario'] ?>">Autor a Revisor</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
<?php elseif ($view === 'autores'): ?>
    <h3>Autores Existentes</h3>
    <form method="POST" action="../../controladores/ascender_revisor.php">
        <table border="1" cellpadding="6">
            <tr><th>Nombre</th><th>Email</th><th>Acción</th></tr>
            <?php foreach ($autores as $autor): ?>
                <tr>
                    <td><?= htmlspecialchars($autor['nombre']) ?></td>
                    <td><?= htmlspecialchars($autor['email']) ?></td>
                    <td>
                        <button type="submit" name="id_usuario" value="<?= $autor['id_usuario'] ?>">Autor a Revisor</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
<?php endif; ?>


<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form.accion-form").forEach(form => {
        form.addEventListener("submit", function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const row = form.closest("tr");

            showCustomConfirm("⚠️ ¿Seguro que deseas eliminar este revisor?", confirmado => {
                if (!confirmado) return;

                fetch(this.action, {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        showToast("✅ Revisor eliminado exitosamente", "success");
                        row.remove();
                    } else {
                        showToast("❌ " + (data.message || "Error al eliminar"), "error");
                    }
                })
                .catch(() => {
                    showToast("⚠️ Error de conexión con el servidor", "error");
                });
            });
        });
    });
});

function showToast(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = "toast-popup show";
    toast.textContent = message;

    // Color por tipo
    if (type === "success") {
        toast.style.backgroundColor = "#28a745";
    } else if (type === "error") {
        toast.style.backgroundColor = "#dc3545";
    } else {
        toast.style.backgroundColor = "#17a2b8";
    }

    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add("hide"), 3500);
    setTimeout(() => toast.remove(), 4500);
}
</script>



<script>
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
</script>

<div id="custom-confirm" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#00000099; z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:10px; max-width:400px; text-align:center; box-shadow:0 0 15px #000;">
        <p id="custom-confirm-msg" style="margin-bottom:20px;">¿Estás seguro?</p>
        <button id="confirm-yes" style="margin-right:10px; padding:6px 12px;">Aceptar</button>
        <button id="confirm-no" style="padding:6px 12px;">Cancelar</button>
    </div>
</div>


<script>
function cerrarModal() {
    const modal = document.getElementById('modal-editar');
    modal.classList.add('hidden');
    document.getElementById('modal-body').innerHTML = '';
}

document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const modal = document.getElementById('modal-editar');
        const body = document.getElementById('modal-body');

        modal.classList.remove('hidden');
        body.innerHTML = 'Cargando...';

        fetch('./editar_revisor_modal.php?id=' + id)
            .then(res => res.text())
            .then(html => {
                body.innerHTML = html;
            });
    });
});

document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'cerrar-panel-btn') {
        cerrarModal();
    }
});


document.addEventListener('submit', function(e) {
    if (e.target.matches('#form-especialidades')) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);

        fetch('../../controladores/actualizar_especialidades.php', {
            method: 'POST',
            body: data
        }).then(res => res.text())
          .then(() => {
              cerrarModal();
              location.reload();
          });
    }
});


document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form.accion-form").forEach(form => {
        form.addEventListener("submit", function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const row = form.closest("tr");

            showCustomConfirm("⚠️ ¿Seguro que deseas eliminar este revisor?", confirmado => {
                if (!confirmado) return;

                fetch(this.action, {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success" || data.status === "ok") {
                        showToast("✅ Revisor eliminado exitosamente", "success");
                        row.remove();
                    } else {
                        showToast("❌ " + (data.message || "Error al eliminar"), "error");
                    }
                })
                .catch(() => {
                    showToast("⚠️ Error de conexión con el servidor", "error");
                });
            });
        });
    });
});




</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
    const toast = document.getElementById("toast-error");
    if (toast) {
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.add("hide");
            setTimeout(() => toast.remove(), 800);
        }, 4000);
    }
});
</script>





