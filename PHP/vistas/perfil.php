<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/Usuario.php');
$tituloPagina = "Perfil";
include_once(__DIR__ . '/header.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = Usuario::obtenerPorID($_SESSION['id_usuario']);


?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gescon</title>
    <link rel="stylesheet" href="../public/css/perfil.css">
    <style>
        .btn-editar, .btn-eliminar {
            display: inline-block;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
        }
        .btn-editar {
            background-color: #007bff;
            color: white;
        }
        .btn-eliminar {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
<div class="perfil-container">

    <h2>Perfil de Usuario</h2>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
    <p><strong>ID:</strong> <?= htmlspecialchars($usuario['id_usuario']) ?></p>
    <p><strong>Rol:</strong>
        <?php
            switch ($usuario['subclase']) {
                case 1: echo "Jefe de Comité"; break;
                case 2: echo "Autor"; break;
                case 3: echo "Revisor"; break;
                case 4: echo "Autor y Revisor"; break;
                default: echo "Desconocido";
            }
        ?>
    </p>

    <hr>
    <h3>Gestión de Cuenta</h3>
    <form method="POST" action="../controladores/actualizar_usuario.php">
        <label>Nombre:
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
        </label>

        <label>Email:
            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </label>

        <h4>Cambiar Contraseña</h4>
        <label>Contraseña actual:
            <input type="password" name="actual">
        </label>

        <label>Nueva contraseña:
            <input type="password" name="nueva">
        </label>

        <label>Confirmar nueva contraseña:
            <input type="password" name="confirmar">
        </label>

        <button type="submit">Actualizar Datos</button>
    </form>

    <form method="POST" action="../controladores/eliminar_usuario.php" onsubmit="return confirm('¿Estás seguro de que deseas eliminar tu cuenta? Esta acción es irreversible.')">
        <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?>">
        <button type="submit" style="color:red;">Eliminar Cuenta</button>
    </form>

<?php
if (in_array($usuario['subclase'], [2, 4])) {
    echo "<h4>Mis Artículos</h4>";
    $stmt = $conn->prepare("SELECT a.id_articulo, a.titulo, a.aceptacion, a.fecha_limite_modificacion
                            FROM articulo a
                            JOIN escribiendo e ON a.id_articulo = e.id_articulo
                            WHERE e.id_usuario = ?
                            ORDER BY FIELD(a.aceptacion, NULL, 0, 1)");

    $stmt->execute([$_SESSION['id_usuario']]);
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($articulos): ?>
        <div class="articulos-lista fade-in">
            <?php foreach ($articulos as $art): ?>
                <div class="articulo-item">
                    <div>
                        <strong><?= htmlspecialchars($art['titulo']) ?></strong>
                        <span class="articulo-estado">
                            <?= is_null($art['aceptacion']) ? ' - Pendiente' : ($art['aceptacion'] ? ' - ✅ Aceptado' : ' - ❌ Rechazado') ?>
                        </span>
                    </div>

                    <?php
                    $evalStmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ? AND calidad_tecnica IS NOT NULL AND valoracion_global IS NOT NULL");
                    $evalStmt->execute([$art['id_articulo']]);
                    $evaluadas = $evalStmt->fetchColumn();

                    $puedeEditar = false;
                    $fechaValida = strtotime($art['fecha_limite_modificacion']) >= strtotime(date('Y-m-d'));
                    $evalCheck = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ? AND (calidad_tecnica IS NOT NULL OR valoracion_global IS NOT NULL)");
                    $evalCheck->execute([$art['id_articulo']]);
                    $yaEvaluado = $evalCheck->fetchColumn() > 0;
                    if ($fechaValida && !$yaEvaluado) $puedeEditar = true;
                    ?>

                    <div>Evaluaciones completadas: <?= $evaluadas ?>/3</div>

                    <?php if (isset($_GET['eliminado']) && $_GET['eliminado'] == 1): ?>
                        <div class="message success">✅ Artículo eliminado correctamente.</div>
                    <?php endif; ?>

                    <div class="articulo-botones">
                        <?php if ($puedeEditar): ?>
                            <a href="autor/editar_articulo.php?id_articulo=<?= $art['id_articulo'] ?>" class="btn-editar">✏️ Editar</a>
                        <?php endif; ?>

                        <form class="delete-form" data-id="<?= $art['id_articulo'] ?>" onsubmit="return false;">
                            <input type="hidden" name="id_articulo" value="<?= $art['id_articulo'] ?>">
                            <button type="submit" class="btn-eliminar">🗑 Eliminar</button>
                        </form>
                    </div>
                    <?php if ($evaluadas > 0): ?>
                        <button onclick="toggleRevisiones('rev<?= $art['id_articulo'] ?>')">Ver Revisiones</button>
                        <div id="rev<?= $art['id_articulo'] ?>" style="display:none; margin-top:5px;">
                            <table border="1" cellpadding="4" class="tabla-revisiones">
                                <tr>
                                    <th>Revisor</th><th>Calidad Técnica</th><th>Originalidad</th><th>Valoración Global</th><th>Argumentos</th><th>Comentarios</th>
                                </tr>
                                <?php
                                $revStmt = $conn->prepare("SELECT u.nombre, f.calidad_tecnica, f.originalidad, f.valoracion_global, f.argumentosvg, f.comentarios_autores
                                                        FROM formulario f JOIN usuarios u ON f.id_usuario = u.id_usuario
                                                        WHERE f.id_articulo = ? AND f.calidad_tecnica IS NOT NULL AND f.valoracion_global IS NOT NULL");
                                $revStmt->execute([$art['id_articulo']]);
                                foreach ($revStmt->fetchAll(PDO::FETCH_ASSOC) as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                                        <td><?= $r['calidad_tecnica'] ?></td>
                                        <td><?= $r['originalidad'] ? 'Sí' : 'No' ?></td>
                                        <td><?= $r['valoracion_global'] ?></td>
                                        <td><?= htmlspecialchars($r['argumentosvg']) ?></td>
                                        <td><?= htmlspecialchars($r['comentarios_autores']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No has enviado artículos aún.</p>
    <?php endif;

    
}
?>

<style>
.toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    min-width: 200px;
    max-width: 500px;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    color: white;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    animation: fade-in-out 4s ease forwards;
}

.toast.success {
    background: #28a745;
}

.toast.error {
    background: #dc3545;
}

.toast.info {
    background: #007bff;
}

@keyframes fade-in-out {
    0% { opacity: 0; }
    10% { opacity: 1; pointer-events: auto; }
    90% { opacity: 1; pointer-events: auto; }
    100% { opacity: 0; pointer-events: none; }
}
</style>



<div id="toast" class="toast" style="display:none;"></div>


<div id="confirm-dialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#0006; justify-content:center; align-items:center; z-index:9999;">
  <div style="background:#fff; padding:20px 30px; border-radius:8px; box-shadow:0 4px 20px #0004; text-align:center;">
    <p id="confirm-text" style="margin-bottom:20px;">¿Estás seguro de eliminar este artículo?</p>
    <button id="confirm-yes" style="margin-right:10px;" class="btn-eliminar">Eliminar</button>
    <button id="confirm-no" class="btn-cancelar">Cancelar</button>
  </div>
</div>


<div style="margin-top: 20px;">
    <a href="logout.php" class="btn-logout">Cerrar sesión</a>
</div>

</div>
</body>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const msg = params.get("noti");
    if (msg) showToast(decodeURIComponent(msg));
});

function showToast(text) {
    const toast = document.getElementById("toast");
    toast.innerText = text;
    toast.style.display = "block";
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
        toast.style.display = "none";
    }, 4000);
}


document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.getElementById("confirm-dialog");
  const confirmText = document.getElementById("confirm-text");
  const btnYes = document.getElementById("confirm-yes");
  const btnNo = document.getElementById("confirm-no");

  let currentForm = null;

  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      currentForm = form;
      confirmText.textContent = "¿Estás seguro de eliminar este artículo? Esta acción es irreversible.";
      dialog.style.display = "flex";
    });
  });

  btnYes.addEventListener('click', () => {
    dialog.style.display = "none";
    if (!currentForm) return;

    const formData = new FormData(currentForm);

    fetch('../controladores/eliminar_articulo.php', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const contentType = res.headers.get("content-type") || "";
        if (!res.ok) throw new Error("HTTP error: " + res.status);

        if (contentType.includes("application/json")) {
            const data = await res.json();
            console.log("Respuesta del servidor:", data);

            mostrarNotificacion(data.message, data.status === "success" ? "success" : "error");

            if (data.status === "success") {
                const card = currentForm.closest(".articulo-item");
                card.style.transition = "opacity 0.5s";
                card.style.opacity = "0";
                setTimeout(() => card.remove(), 500);
            }
        } else {
            const raw = await res.text();
            console.warn("❌ Respuesta no-JSON:", raw);
            mostrarNotificacion("⚠️ Respuesta inesperada del servidor", "error");
        }
    })
    .catch(err => {
        console.error("❌ Error de conexión o parseo:", err);
        mostrarNotificacion("❌ Error de red o servidor", "error");
    });
});




  btnNo.addEventListener('click', () => {
    dialog.style.display = "none";
    currentForm = null;
  });



  btnNo.addEventListener('click', () => {
    dialog.style.display = "none";
    currentForm = null;
  });
});





function eliminarArticulo(form) {
    event.preventDefault();

    if (!confirm("¿Estás seguro de eliminar este artículo? Esta acción es irreversible.")) return;

    const formData = new FormData(form);
    fetch('../controladores/eliminar_articulo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log("Respuesta del servidor:", data);
        mostrarNotificacion(data.message, data.status === "success" ? "success" : "error");
        if (data.status === "success") {
            form.closest(".articulo-item")?.remove();
        }
    })
    .catch(err => {
        mostrarNotificacion("❌ Error de red o servidor.");
    });

    return false;
}

// Utilidad visual para mostrar notificaciones flotantes
function mostrarNotificacion(mensaje, tipo = "info") {
    const toast = document.getElementById("toast");
    toast.innerText = mensaje;
    toast.className = "toast " + tipo;
    toast.style.display = "block";
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
        toast.style.display = "none";
    }, 4000);
}


function toggleRevisiones(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

</script>



</html>


