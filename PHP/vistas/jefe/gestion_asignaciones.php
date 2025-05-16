<?php
require_once(__DIR__ . '/../../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$view = $_GET['view'] ?? 'articulos';
$stmtAreas = $conn->query("SELECT * FROM area");
$areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

if ($view === 'articulos') {
    $stmt = $conn->query("
        SELECT a.id_articulo, a.titulo, 
            GROUP_CONCAT(DISTINCT ar.titulo_area SEPARATOR ', ') AS topicos,
            GROUP_CONCAT(DISTINCT u.nombre SEPARATOR ', ') AS autores,
            GROUP_CONCAT(DISTINCT ur.nombre SEPARATOR ', ') AS revisores,
            GROUP_CONCAT(DISTINCT ur.id_usuario SEPARATOR ',') AS id_revisores,
            v.evaluaciones_completadas,
            v.total_asignados,
            v.aceptacion,
            u_contacto.nombre AS autor_contacto
        FROM articulo a
        JOIN vista_estado_articulos v ON v.id_articulo = a.id_articulo
        JOIN escribiendo e ON a.id_articulo = e.id_articulo
        JOIN usuarios u ON u.id_usuario = e.id_usuario
        LEFT JOIN formulario f ON f.id_articulo = a.id_articulo
        LEFT JOIN usuarios ur ON ur.id_usuario = f.id_usuario
        JOIN topicos t ON t.id_articulo = a.id_articulo
        JOIN area ar ON ar.id_area = t.id_area
        LEFT JOIN (
            SELECT e.id_articulo, u.nombre
            FROM escribiendo e
            JOIN usuarios u ON e.id_usuario = u.id_usuario
            WHERE e.autor_contacto = 1
        ) AS u_contacto ON u_contacto.id_articulo = a.id_articulo
        GROUP BY a.id_articulo
    ");

    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtRevisores = $conn->query("SELECT id_usuario, nombre FROM usuarios WHERE subclase IN (3, 4)");
    $revisores = $stmtRevisores->fetchAll(PDO::FETCH_ASSOC);
}if ($view !== 'articulos') {
    // Solo si estamos en la vista de revisores
    $stmt = $conn->query("SELECT u.id_usuario, u.nombre
                          FROM usuarios u
                          WHERE u.subclase IN (3, 4)");
    $revisoresVista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtArticulos = $conn->query("SELECT id_articulo, titulo FROM articulo");
    $articulosLista = $stmtArticulos->fetchAll(PDO::FETCH_ASSOC);

    // Cargar pendientes/revisados para ordenar
    foreach ($revisoresVista as &$rev) {
        $stmt = $conn->prepare("SELECT ar.id_articulo, ar.titulo FROM formulario f JOIN articulo ar ON f.id_articulo = ar.id_articulo WHERE f.id_usuario = ?");
        $stmt->execute([$rev['id_usuario']]);
        $asignados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rev['pendientes'] = [];

        foreach ($asignados as $art) {
            $estadoStmt = $conn->prepare("SELECT aceptacion FROM articulo WHERE id_articulo = ?");
            $estadoStmt->execute([$art['id_articulo']]);
            $estado = $estadoStmt->fetchColumn();

            if (is_null($estado)) {
                $rev['pendientes'][] = $art;
            }
        }
    }

    // ✅ Ordena de menor a mayor pendientes
    usort($revisoresVista, function ($a, $b) {
        return count($a['pendientes']) <=> count($b['pendientes']);
    });
}


?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="../../public/css/gestionar_asignaciones.css">


<?php
$tituloPagina = "Gestión de Asignaciones";
include_once(__DIR__ . '/../header.php');
?>
<script src="../../public/js/gestionar_asignaciones.js"></script>

<head>
    <meta charset="UTF-8">
    <title>Gescon</title>
    <style>
    .toast-popup {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        font-size: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: 9999;
    }
    .toast-popup.show {
        opacity: 1;
    }
    .toast-popup.hide {
        opacity: 0;
    }
    </style>
</head>

<h2 class="asignacion-title">📋 Asignaciones</h2>

<div class="asignacion-vistas">
    <a href="?view=articulos" class="vista-btn <?= ($view === 'articulos') ? 'active' : '' ?>">📝 Vista por Artículos</a>
    <a href="?view=revisores" class="vista-btn <?= ($view === 'revisores') ? 'active' : '' ?>">👁️ Vista por Revisores</a>
</div>

<h3 class="asignacion-subtitulo">
    <?= $view === 'articulos'
        ? 'Asignar / Quitar Revisor a Artículo'
        : 'Asignar / Quitar Artículo a Revisor' ?>
</h3>

<form method="POST" action="../../controladores/asignacion_automatica.php" style="text-align:center; margin-bottom:20px;">
    <button type="submit" class="auto-btn">🔁 Asignación Automática</button>
</form>

<?php if (isset($_GET['error'])): ?>
    <div id="toast-error" class="toast-popup error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>






<?php if ($view === 'articulos'): ?>
    <div style="text-align:center; margin: 20px auto;">
        <input type="number" id="delete-id" class="form-control d-inline w-auto me-2" placeholder="ID del artículo">
        <button onclick="eliminarPorID()" class="btn btn-danger">Eliminar</button>
        <div id="delete-alert" style="margin-top:10px; display:none; padding:10px; border-radius:4px;"></div>
    </div>
    <table border="1" cellpadding="6">
        <tr>
            <th>#</th><th>Título</th><th>Estado</th><th>Autores</th><th>Tópicos</th><th>Revisores</th><th>Asignar</th><th>Quitar</th>
        </tr>
        <?php foreach ($articulos as $art): ?>
            <?php
                $idsAsignados = array_filter(explode(',', $art['id_revisores'] ?? ''));
                $revisoresAsignados = array_filter($revisores, fn($r) => in_array($r['id_usuario'], $idsAsignados));
            ?>
            <tr id="row-<?= $art['id_articulo'] ?>" 
                data-title="<?= htmlspecialchars($art['titulo']) ?>" 
                data-contacto="<?= htmlspecialchars($art['autor_contacto']) ?>"
                data-revisores="<?= count($revisoresAsignados) ?>">

                <td><?= $art['id_articulo'] ?></td>
                <td><?= htmlspecialchars($art['titulo']) ?></td>
                <td>
                    <?= ($art['aceptacion'] === null) ? "🕓 Pendiente" :
                        (($art['aceptacion'] == 1) ? "✅ Aceptado" : "❌ Rechazado") ?>
                    <br>
                    <?= $art['evaluaciones_completadas'] ?>/3 evaluadas
                </td>
                <td><?= htmlspecialchars($art['autores']) ?></td>
                <td><?= htmlspecialchars($art['topicos']) ?></td>
                <td><?= htmlspecialchars($art['revisores'] ?? '') ?></td>
                <td>
                    <?php if (count($revisoresAsignados) < 3): ?>
                        <form method="POST" action="../../controladores/asignar_quitar.php">
                            <input type="hidden" name="id_articulo" value="<?= $art['id_articulo'] ?>">
                            <input type="hidden" name="accion" value="asignar">
                            <select name="id_usuario" required>
                                <option value="" disabled selected>---</option>
                                <?php foreach ($revisores as $rev): ?>
                                    <option value="<?= $rev['id_usuario'] ?>"> <?= htmlspecialchars($rev['nombre']) ?> </option>
                                <?php endforeach; ?>
                            </select>
                            <button>Aceptar</button>
                        </form>
                    <?php else: ?>
                        <span style="color:red">Máximo 3 revisores</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="../../controladores/asignar_quitar.php">
                        <input type="hidden" name="id_articulo" value="<?= $art['id_articulo'] ?>">
                        <input type="hidden" name="accion" value="quitar">
                        <select name="id_usuario" required>
                            <option value="" disabled selected>---</option>
                            <?php foreach ($revisoresAsignados as $rev): ?>
                                <?php
                                    $stmtEval = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ? AND id_usuario = ? AND calidad_tecnica IS NOT NULL");
                                    $stmtEval->execute([$art['id_articulo'], $rev['id_usuario']]);
                                    $yaEvaluo = $stmtEval->fetchColumn() > 0;
                                ?>
                                <option
                                    value="<?= $rev['id_usuario'] ?>" 
                                    data-evaluado="<?= $yaEvaluo ? '1' : '0' ?>"> 
                                    <?= htmlspecialchars($rev['nombre']) ?> 
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button>Aceptar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
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



    <script>
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("tr[id^='row-']").forEach(row => {
            const revisoresAsignados = parseInt(row.dataset.revisores || "0");
            if (revisoresAsignados <= 2) {
                row.style.backgroundColor = "#fff3cd";
                row.style.borderLeft = "5px solid #ffc107";
            }
        });

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
document.addEventListener("DOMContentLoaded", () => {
    // Confirmar al quitar si ya evaluó
    document.querySelectorAll("form[action*='asignar_quitar.php']").forEach(form => {
        form.addEventListener("submit", function (e) {
            const accion = form.querySelector("input[name='accion']").value;
            if (accion === "quitar") {
                const select = form.querySelector("select[name='id_usuario']");
                const selectedOption = select?.selectedOptions?.[0];

                if (!selectedOption) return;

                const yaEvaluo = selectedOption.dataset.evaluado === "1";

                if (yaEvaluo) {
                    e.preventDefault();
                    showCustomConfirm("⚠️ Este revisor ya evaluó el artículo. ¿Estás seguro de que deseas quitarlo?", function(confirmado) {
                        if (confirmado) form.submit();
                    });
                }
            }
        });
    });
});
</script>



<script>
function eliminarPorID() {
    const id = document.getElementById('delete-id').value;

    const mensaje = `⚠️ ¿Eliminar artículo #${id}? Esta acción es irreversible.`;

    showCustomConfirm(mensaje, (confirmado) => {
        if (!confirmado) return;

        const formData = new FormData();
        formData.append('id_articulo', id);

        fetch('../../controladores/eliminar_articulo.php', {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const text = await res.text();
            try {
                const data = JSON.parse(text);
                showToast(data.message, data.status === 'success' ? 'success' : 'error');

                if (data.status === 'success') {
                    const row = document.getElementById('row-' + id);
                    if (row) {
                        row.style.transition = "opacity 0.4s ease";
                        row.style.opacity = "0";
                        setTimeout(() => row.remove(), 400);
                    }
                }
            } catch (e) {
                console.error("❌ No JSON:", text);
                showToast("⚠️ Error inesperado del servidor", "error");
            }
        })
        .catch(err => {
            console.error("❌ Red error:", err);
            showToast("❌ Error de red o conexión", "error");
        });
    });
}
</script>


<script>
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


<?php else: ?>
    
    <table class="tabla-asignaciones">
    <tr>
        <th>Nombre</th>
        <th>Especialización</th>
        <th>Artículos Asignados</th>
        <th>Acción</th>
    </tr>
    <?php foreach ($revisoresVista as $rev): ?>
        <?php
            $stmtTop = $conn->prepare("SELECT a.titulo_area FROM especializacion e JOIN area a ON a.id_area = e.id_area WHERE e.id_usuario = ?");
            $stmtTop->execute([$rev['id_usuario']]);
            $topicos = $stmtTop->fetchAll(PDO::FETCH_COLUMN);

            $stmtArt = $conn->prepare("SELECT ar.id_articulo, ar.titulo FROM formulario f JOIN articulo ar ON f.id_articulo = ar.id_articulo WHERE f.id_usuario = ?");
            $stmtArt->execute([$rev['id_usuario']]);
            $artAsignados = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

            $revisados = [];
            $pendientes = [];
            

            foreach ($artAsignados as $art) {
                $estadoStmt = $conn->prepare("SELECT aceptacion FROM articulo WHERE id_articulo = ?");
                $estadoStmt->execute([$art['id_articulo']]);
                $estado = $estadoStmt->fetchColumn();

                if (is_null($estado)) {
                    $pendientes[] = $art;
                } else {
                    $revisados[] = $art;
                }
            }

            $idsAsignados = array_column($artAsignados, 'id_articulo');
            $artNoAsignados = array_filter($articulosLista, fn($a) => !in_array($a['id_articulo'], $idsAsignados));

            $artValidos = array_filter($artNoAsignados, function ($a) use ($conn) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ?");
                $stmt->execute([$a['id_articulo']]);
                return $stmt->fetchColumn() < 3;
            });

            $opciones = array_merge($artValidos, $artAsignados);
            $enviados = [];
            $enviados = [];
            foreach ($pendientes as $art) {
                $stmtEnvio = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM formulario 
                    WHERE id_usuario = ? 
                      AND id_articulo = ? 
                      AND valoracion_global IS NOT NULL
                      AND calidad_tecnica IS NOT NULL
                ");
                $stmtEnvio->execute([$rev['id_usuario'], $art['id_articulo']]);
                if ($stmtEnvio->fetchColumn() > 0) {
                    $enviados[] = $art['id_articulo'];
                }
            }            
        ?>
        <tr>
            <td><?= htmlspecialchars($rev['nombre']) ?></td>
            <td><?= implode(', ', $topicos) ?></td>
            <td class="assigned-articles">
                <strong><?= count($revisados) ?> Revisado(s)</strong><br>
                <strong><?= count($pendientes) ?> Pendiente(s):</strong>
                <?php foreach ($pendientes as $art): ?>
                    <?php $esEnviado = in_array($art['id_articulo'], $enviados); ?>
                    <div class="article-title-box <?= $esEnviado ? 'formulario-enviado' : '' ?>">
                        <?= htmlspecialchars($art['titulo']) ?>
                    </div>
                <?php endforeach; ?>
            </td>
            <td>
                <form method="POST" action="../../controladores/asignar_quitar.php" class="actions">
                    <input type="hidden" name="id_usuario" value="<?= $rev['id_usuario'] ?>">

                    <select name="accion" required class="accion-select" data-id="<?= $rev['id_usuario'] ?>">
                        <option value="" disabled selected>Acción</option>
                        <option value="asignar">Asignar</option>
                        <option value="quitar">Quitar</option>
                    </select>

                    <select name="id_articulo" required class="articulo-select" id="articulos-<?= $rev['id_usuario'] ?>">
                        <option value="" disabled selected>---</option>
                        <?php foreach (array_merge($artValidos, $artAsignados) as $art): ?>
                            <option 
                                value="<?= $art['id_articulo'] ?>" 
                                data-mode="<?= in_array($art, $artAsignados) ? 'quitar' : 'asignar' ?>">
                                <?= htmlspecialchars($art['titulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Aceptar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-popup ${type}`;
    toast.textContent = message;

    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100); // fade in

    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 800); // remove from DOM
    }, 4000);
}
</script>



<?php endif; ?>
