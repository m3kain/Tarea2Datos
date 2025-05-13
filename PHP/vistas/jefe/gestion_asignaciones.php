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
            v.aceptacion
        FROM articulo a
        JOIN vista_estado_articulos v ON v.id_articulo = a.id_articulo
        JOIN escribiendo e ON a.id_articulo = e.id_articulo
        JOIN usuarios u ON u.id_usuario = e.id_usuario
        LEFT JOIN formulario f ON f.id_articulo = a.id_articulo
        LEFT JOIN usuarios ur ON ur.id_usuario = f.id_usuario
        JOIN topicos t ON t.id_articulo = a.id_articulo
        JOIN area ar ON ar.id_area = t.id_area
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

<h2>Gestión de Asignaciones</h2>
<p><a href="../dashboard.php">← Volver al Dashboard</a></p>

<p>
    <a href="?view=articulos">Vista por Artículos</a> | 
    <a href="?view=revisores">Vista por Revisores</a>
</p>

<form method="POST" action="../../controladores/asignacion_automatica.php">
    <button type="submit">Asignación Automática</button>
</form>

<?php if ($view === 'articulos'): ?>
    <h3>Asignar / Quitar Revisor a Artículo</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>#</th><th>Título</th><th>Estado</th><th>Autores</th><th>Tópicos</th><th>Revisores</th><th>Asignar</th><th>Quitar</th>
        </tr>
        <?php foreach ($articulos as $art): ?>
            <?php
                $idsAsignados = array_filter(explode(',', $art['id_revisores'] ?? ''));
                $revisoresAsignados = array_filter($revisores, fn($r) => in_array($r['id_usuario'], $idsAsignados));
            ?>
            <tr>
                <td><?= $art['id_articulo'] ?></td>
                <td><?= htmlspecialchars($art['titulo']) ?></td>
                <td>
                    <?= ($art['aceptacion'] === null) ? "🕓 Pendiente" :
                        (($art['aceptacion'] == 1) ? "✅ Aceptado" : "❌ Rechazado") ?>
                    <br>
                    <?= $art['evaluaciones_completadas'] ?>/3 evaluadas
                </td>
                <td><?= htmlspecialchars($art['autores']) ?></td>
                <td>
                    <?php foreach (explode(', ', $art['topicos']) as $topic): ?>
                        <div style="border: 1px solid #ccc; padding: 4px; margin: 2px;"> <?= htmlspecialchars($topic) ?> </div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php foreach (explode(', ', $art['revisores'] ?? '') as $rev): ?>
                        <div style="border: 1px solid #ccc; padding: 4px; margin: 2px;"> <?= htmlspecialchars($rev) ?> </div>
                    <?php endforeach; ?>
                </td>
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
                            <button type="submit">Aceptar</button>
                        </form>
                    <?php else: ?>
                        <div style="color: red;">Máximo 3 revisores</div>
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
                        <button type="submit">Aceptar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <h3>Asignar / Quitar Artículo a Revisor</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>Nombre</th><th>Tópicos</th><th>Artículos Asignados</th><th>Asignar</th><th>Quitar</th>
        </tr>
        <?php foreach ($revisoresVista as $rev): ?>
            <?php
                $stmtTop = $conn->prepare("SELECT a.titulo_area FROM especializacion e JOIN area a ON a.id_area = e.id_area WHERE e.id_usuario = ?");
                $stmtTop->execute([$rev['id_usuario']]);
                $topicos = $stmtTop->fetchAll(PDO::FETCH_COLUMN);

                $stmtArt = $conn->prepare("SELECT ar.id_articulo, ar.titulo FROM formulario f JOIN articulo ar ON f.id_articulo = ar.id_articulo WHERE f.id_usuario = ?");
                $stmtArt->execute([$rev['id_usuario']]);
                $artAsignados = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

                $idsAsignados = array_column($artAsignados, 'id_articulo');
                $artNoAsignados = array_filter($articulosLista, fn($a) => !in_array($a['id_articulo'], $idsAsignados));
            ?>
            <tr>
                <td><?= htmlspecialchars($rev['nombre']) ?></td>
                <td>
                    <?php foreach ($topicos as $t): ?>
                        <div style="border: 1px solid #ccc; padding: 4px; margin: 2px;"> <?= htmlspecialchars($t) ?> </div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php foreach ($artAsignados as $art): ?>
                        <div style="border: 1px solid #ccc; padding: 4px; margin: 2px;"> <?= htmlspecialchars($art['titulo']) ?> </div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php
                        $artValidos = array_filter($artNoAsignados, function ($a) use ($conn) {
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ?");
                            $stmt->execute([$a['id_articulo']]);
                            return $stmt->fetchColumn() < 3;
                        });
                    ?>
                    <?php if (!empty($artValidos)): ?>
                        <form method="POST" action="../../controladores/asignar_quitar.php">
                            <input type="hidden" name="id_usuario" value="<?= $rev['id_usuario'] ?>">
                            <input type="hidden" name="accion" value="asignar">
                            <select name="id_articulo" required>
                                <option value="" disabled selected>---</option>
                                <?php foreach ($artValidos as $art): ?>
                                    <option value="<?= $art['id_articulo'] ?>"> <?= htmlspecialchars($art['titulo']) ?> </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Aceptar</button>
                        </form>
                    <?php else: ?>
                        <div style="color: red;">Todos los artículos tienen 3 revisores</div>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="../../controladores/asignar_quitar.php">
                        <input type="hidden" name="id_usuario" value="<?= $rev['id_usuario'] ?>">
                        <input type="hidden" name="accion" value="quitar">
                        <select name="id_articulo" required>
                            <option value="" disabled selected>---</option>
                            <?php foreach ($artAsignados as $art): ?>
                                <option value="<?= $art['id_articulo'] ?>"> <?= htmlspecialchars($art['titulo']) ?> </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Aceptar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
