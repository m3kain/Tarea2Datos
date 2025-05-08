<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/Usuario.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = Usuario::obtenerPorID($_SESSION['id_usuario']);
?>

<?php if (isset($_GET['noti']) && $_GET['noti']): ?>
<script>
    alert(decodeURIComponent(`<?= $_GET['noti'] ?>`));
</script>
<?php endif; ?>

    

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
    <label>Nombre: <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required></label><br>
    <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required></label><br>
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

    if ($articulos) {
        echo "<ul>";
        foreach ($articulos as $art) {
            $estado = is_null($art['aceptacion']) ? "Pendiente" : ($art['aceptacion'] ? "✅ Aceptado" : "❌ Rechazado");

            $evalStmt = $conn->prepare("SELECT COUNT(*) FROM formulario
                                        WHERE id_articulo = ? AND calidad_tecnica IS NOT NULL AND valoracion_global IS NOT NULL");
            $evalStmt->execute([$art['id_articulo']]);
            $evaluadas = $evalStmt->fetchColumn();

            $puedeEditar = false;
            $fechaValida = strtotime($art['fecha_limite_modificacion']) >= strtotime(date('Y-m-d'));

            $evalCheck = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ? AND (calidad_tecnica IS NOT NULL OR valoracion_global IS NOT NULL)");
            $evalCheck->execute([$art['id_articulo']]);
            $yaEvaluado = $evalCheck->fetchColumn() > 0;

            if ($fechaValida && !$yaEvaluado) {
                $puedeEditar = true;
            }

            echo "<li><strong>{$art['titulo']}</strong> - $estado<br>";

            if (isset($_GET['eliminado']) && $_GET['eliminado'] == 1) {
                echo "<p style='color:green;'>✅ Artículo eliminado correctamente.</p>";
            }

            echo "Evaluaciones completadas: {$evaluadas}/3<br>";

            if ($puedeEditar) {
                echo "<a href='editar_articulo.php?id_articulo={$art['id_articulo']}' style='color:blue;'>✏️ Editar</a><br>";
            }

            echo "<form method='POST' action='../controladores/eliminar_articulo.php' style='margin-top:5px;' onsubmit=\"return confirm('¿Estás seguro de eliminar este artículo? Esta acción es irreversible.');\">
                    <input type='hidden' name='id_articulo' value='{$art['id_articulo']}'>
                    <button type='submit'>🗑️ Eliminar</button>
                  </form>";

            if ($evaluadas > 0) {
                echo "<button onclick=\"toggleRevisiones('rev{$art['id_articulo']}')\">Ver Revisiones</button>";
                echo "<div id='rev{$art['id_articulo']}' style='display:none;margin-top:5px;'>";

                $revStmt = $conn->prepare("SELECT u.nombre, f.calidad_tecnica, f.originalidad, f.valoracion_global, f.argumentosvg, f.comentarios_autores
                                            FROM formulario f
                                            JOIN usuarios u ON f.id_usuario = u.id_usuario
                                            WHERE f.id_articulo = ? AND f.calidad_tecnica IS NOT NULL AND f.valoracion_global IS NOT NULL");
                $revStmt->execute([$art['id_articulo']]);
                $revisiones = $revStmt->fetchAll(PDO::FETCH_ASSOC);

                echo "<table border='1' cellpadding='4'><tr>
                        <th>Revisor</th><th>Calidad Técnica</th><th>Originalidad</th><th>Valoración Global</th><th>Argumentos</th><th>Comentarios</th>
                      </tr>";
                foreach ($revisiones as $r) {
                    $original = $r['originalidad'] ? 'Sí' : 'No';
                    echo "<tr>
                            <td>" . htmlspecialchars($r['nombre']) . "</td>
                            <td>{$r['calidad_tecnica']}</td>
                            <td>{$original}</td>
                            <td>{$r['valoracion_global']}</td>
                            <td>" . htmlspecialchars($r['argumentosvg']) . "</td>
                            <td>" . htmlspecialchars($r['comentarios_autores']) . "</td>
                          </tr>";
                }
                echo "</table></div>";
            }

            echo "</li><br>";
        }
        echo "</ul>";
    } else {
        echo "<p>No has enviado artículos aún.</p>";
    }
}
?>

<p><a href="dashboard.php">Volver al Dashboard</a></p>
<p><a href="logout.php">Cerrar sesión</a></p>

<script>
function toggleRevisiones(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
