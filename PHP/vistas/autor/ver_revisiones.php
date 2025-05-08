<?php
session_start();
require_once(__DIR__ . '/../../conexion.php');

if (!isset($_GET['id_articulo'])) {
    die("ID de artículo no especificado.");
}

$id_articulo = (int) $_GET['id_articulo'];

$stmt = $conn->prepare("SELECT u.nombre, f.calidad_tecnica, f.originalidad, f.valoracion_global, f.argumentosvg, f.comentarios_autores
                        FROM formulario f
                        JOIN usuarios u ON f.id_usuario = u.id_usuario
                        WHERE f.id_articulo = ? AND f.calidad_tecnica IS NOT NULL");
$stmt->execute([$id_articulo]);
$revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Revisiones del Artículo</h3>
<p><a href="../perfil.php">← Volver al perfil</a></p>

<?php if ($revisiones): ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>Revisor</th><th>Calidad Técnica</th><th>Originalidad</th><th>Valoración Global</th><th>Argumentos</th><th>Comentarios</th>
        </tr>
        <?php foreach ($revisiones as $rev): ?>
            <tr>
                <td><?= htmlspecialchars($rev['nombre']) ?></td>
                <td><?= $rev['calidad_tecnica'] ?></td>
                <td><?= $rev['originalidad'] ? 'Sí' : 'No' ?></td>
                <td><?= $rev['valoracion_global'] ?></td>
                <td><?= nl2br(htmlspecialchars($rev['argumentosvg'])) ?></td>
                <td><?= nl2br(htmlspecialchars($rev['comentarios_autores'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No hay revisiones disponibles para este artículo.</p>
<?php endif; ?>
