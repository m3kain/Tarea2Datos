<?php
require_once(__DIR__ . '/../../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de revisor no válido.");
}

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$revisor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$revisor) {
    die("Revisor no encontrado.");
}

$areas = $conn->query("SELECT * FROM area")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id_area FROM especializacion WHERE id_usuario = ?");
$stmt->execute([$id]);
$especializacionesActuales = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_area');
?>

<h2>Editar Especialidades de <?= htmlspecialchars($revisor['nombre']) ?></h2>
<form method="POST" action="../../controladores/actualizar_especialidades.php">
    <input type="hidden" name="id_usuario" value="<?= $id ?>">

    <table>
        <?php for ($i = 0; $i < count($areas); $i += 3): ?>
            <tr>
                <?php for ($j = 0; $j < 3 && ($i + $j) < count($areas); $j++): ?>
                    <?php $area = $areas[$i + $j]; ?>
                    <td>
                        <label>
                            <input type="checkbox" name="especialidades[]" value="<?= $area['id_area'] ?>" <?= in_array($area['id_area'], $especializacionesActuales) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($area['titulo_area']) ?>
                        </label>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    <br>
    <button type="submit">Actualizar Especialidades</button>
</form>
<p><a href="gestionar_revisores.php">Volver</a></p>
