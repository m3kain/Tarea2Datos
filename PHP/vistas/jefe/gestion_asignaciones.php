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
} else {
    $stmt = $conn->query("SELECT u.id_usuario, u.nombre
                            FROM usuarios u
                            WHERE u.subclase IN (3, 4)");
    $revisoresVista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtArticulos = $conn->query("SELECT id_articulo, titulo FROM articulo");
    $articulosLista = $stmtArticulos->fetchAll(PDO::FETCH_ASSOC);
}
?>
<link rel="stylesheet" href="../../public/css/gestionar_asignaciones.css">
<?php
$tituloPagina = "Gestión de Asignaciones";
include_once(__DIR__ . '/../header.php');
?>

<h2 class="asignacion-title">Asignaciones</h2>

<div class="asignacion-vistas">
    <a href="?view=articulos" class="vista-btn <?= ($view === 'articulos') ? 'active' : '' ?>">📝 Vista por Artículos</a>
    <a href="?view=revisores" class="vista-btn <?= ($view === 'revisores') ? 'active' : '' ?>">👁️ Vista por Revisores</a>
</div>

<h3 class="asignacion-subtitulo">
    <?= $view === 'articulos'
        ? 'Asignar / Quitar Revisor a Artículo'
        : 'Asignar / Quitar Artículo a Revisor' ?>
</h3>

<form method="POST" action="../../controladores/asignar_automatico.php" style="text-align:center; margin-bottom:20px;">
    <button type="submit" class="auto-btn">🔁 Asignación Automática</button>
</form>

<?php if ($view === 'articulos'): ?>
    <div style="text-align:center; margin: 20px auto;">
        <input type="number" id="delete-id" placeholder="ID del artículo" style="padding: 6px; width: 200px;">
        <button onclick="eliminarPorID()" class="delete-btn">Eliminar Artículo</button>
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
            <tr id="row-<?= $art['id_articulo'] ?>" data-title="<?= htmlspecialchars($art['titulo']) ?>" data-contacto="<?= htmlspecialchars($art['autor_contacto']) ?>">
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
                <td><?= htmlspecialchars($art['revisores']) ?></td>
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
                                <option value="<?= $rev['id_usuario'] ?>"> <?= htmlspecialchars($rev['nombre']) ?> </option>
                            <?php endforeach; ?>
                        </select>
                        <button>Aceptar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <form id="delete-form" method="POST" action="../../controladores/eliminar_articulo.php" style="display:none;">
        <input type="hidden" name="id_articulo" id="hidden-delete-id">
    </form>

    <script>
    function eliminarPorID() {
        const id = document.getElementById('delete-id').value;
        const row = document.getElementById('row-' + id);
        const alertBox = document.getElementById('delete-alert');

        if (!row) {
            alertBox.style.display = 'block';
            alertBox.style.backgroundColor = '#f8d7da';
            alertBox.style.color = '#721c24';
            alertBox.innerText = '❌ Artículo no encontrado';
            return;
        }

        const titulo = row.dataset.title;
        const contacto = row.dataset.contacto;

        if (confirm(`¿Eliminar artículo #${id} - "${titulo}" de ${contacto}?`)) {
            const formData = new FormData();
            formData.append('id_articulo', id);

            fetch('../../controladores/eliminar_articulo.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alertBox.style.display = 'block';
                alertBox.innerText = data.message;
                if (data.status === 'success') {
                    alertBox.style.backgroundColor = '#d4edda';
                    alertBox.style.color = '#155724';
                    row.remove();
                } else {
                    alertBox.style.backgroundColor = '#f8d7da';
                    alertBox.style.color = '#721c24';
                }
            })
            .catch(() => {
                alertBox.style.display = 'block';
                alertBox.style.backgroundColor = '#f8d7da';
                alertBox.style.color = '#721c24';
                alertBox.innerText = '⚠️ Error de conexión con el servidor.';
            });
        }
    }
    </script>
<?php else: ?>
    <table border="1" cellpadding="6">
    <tr>
        <th>Nombre</th><th>Especialización</th><th>Artículos Asignados</th><th>Acción</th>
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
        ?>
        <tr>
            <td><?= htmlspecialchars($rev['nombre']) ?></td>
            <td><?= implode(', ', $topicos) ?></td>
            <td class="assigned-articles">
                <strong><?= count($revisados) ?> Revisado(s)</strong><br>
                <strong><?= count($pendientes) ?> Pendiente(s):</strong>
                <?php foreach ($pendientes as $art): ?>
                    <div class="article-title-box"><?= htmlspecialchars($art['titulo']) ?></div>
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
<script src="../../public/js/gestionar_asignaciones.js"></script>
<?php endif; ?>
