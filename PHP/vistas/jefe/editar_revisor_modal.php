<?php
require_once('../../conexion.php');
$id = $_GET['id'] ?? null;

$stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$revisor = $stmt->fetch();

$stmt = $conn->prepare("SELECT * FROM area");
$stmt->execute();
$areas = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT id_area FROM especializacion WHERE id_usuario = ?");
$stmt->execute([$id]);
$marcados = array_column($stmt->fetchAll(), 'id_area');
?>

<form id="form-especialidades">
    <input type="hidden" name="id_usuario" value="<?= $id ?>">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Editar Especialidades de <?= htmlspecialchars($revisor['nombre']) ?></h3>
        <button type="button" id="cerrar-panel-btn" style="background: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer;">Cerrar</button>
    </div>
    <table>
        <?php for ($i = 0; $i < count($areas); $i += 3): ?>
        <tr>
            <?php for ($j = 0; $j < 3 && $i+$j < count($areas); $j++): ?>
            <td>
                <label>
                    <input type="checkbox" name="especialidades[]"
                        value="<?= $areas[$i+$j]['id_area'] ?>"
                        <?= in_array($areas[$i+$j]['id_area'], $marcados) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($areas[$i+$j]['titulo_area']) ?>
                </label>
            </td>
            <?php endfor; ?>
        </tr>
        <?php endfor; ?>
    </table>
    <br>
    <button type="submit">Actualizar Especialidades</button>
</form>

