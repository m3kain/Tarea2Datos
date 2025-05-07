<?php
session_start();
require_once(__DIR__ . '/../../controladores/FormularioController.php');

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 1 && $_SESSION['rol'] !== 4 ) {
    header('Location: ../dashboard.php');
    exit();
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (FormularioController::procesarEvaluacion($_POST)) {
        FormularioController::aceptarSiEvaluado($_POST['id_articulo']);
        $mensaje = "Evaluación guardada y artículo verificado para aceptación.";
    } else {
        $mensaje = "Error al guardar la evaluación.";
    }
}

$asignados = FormularioController::obtenerAsignados($_SESSION['id_usuario']);
?>

<h2>Evaluar Artículos</h2>
<p><a href="../dashboard.php">← Volver al Dashboard</a></p>
<p style="color:green"><?= htmlspecialchars($mensaje) ?></p>

<?php foreach ($asignados as $f): ?>
    <form method="POST">
        <fieldset style="margin-bottom: 30px;">
            <legend><strong><?= htmlspecialchars($f['titulo']) ?></strong></legend>
            <p><?= htmlspecialchars($f['resumen']) ?></p>

            <?php if ($f['aceptacion'] === '1' || $f['aceptacion'] === 1): ?>
                 <p style="color:green;"><strong>Artículo aceptado.</strong></p>
            <?php elseif ($f['aceptacion'] === '0' || $f['aceptacion'] === 0): ?>
                <p style="color:crimson;"><strong>Artículo rechazado.</strong></p>
            <?php else: ?>

                <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?>">
                <input type="hidden" name="id_articulo" value="<?= $f['id_articulo'] ?>">

                Calidad Técnica (1-10): <input type="number" name="calidad_tecnica" min="1" max="10" value="<?= $f['calidad_tecnica'] ?>"><br>
                Originalidad:
                <select name="originalidad">
                    <option value="1" <?= $f['originalidad'] ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= !$f['originalidad'] ? 'selected' : '' ?>>No</option>
                </select><br>
                Valoración Global (1-10): <input type="number" name="valoracion_global" min="1" max="10" value="<?= $f['valoracion_global'] ?>"><br>
                Argumentos: <textarea name="argumentosvg"><?= htmlspecialchars($f['argumentosvg']) ?></textarea><br>
                Comentarios a autores: <textarea name="comentarios_autores"><?= htmlspecialchars($f['comentarios_autores']) ?></textarea><br><br>

                <button type="submit">Evaluar y Verificar Aceptación</button>
            <?php endif; ?>
        </fieldset>
    </form>
<?php endforeach; ?>
