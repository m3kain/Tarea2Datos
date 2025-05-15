<?php
session_start();
require_once(__DIR__ . '/../../controladores/FormularioController.php');

if (!in_array($_SESSION['rol'], [1, 3, 4])) {
    header('Location: ../dashboard.php');
    exit();
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = FormularioController::procesarEvaluacion($_POST)
        ? "Evaluación guardada y artículo verificado para aceptación."
        : "Error al guardar la evaluación.";
}

$asignados = FormularioController::obtenerAsignados($_SESSION['id_usuario']);
$tituloPagina = "Evaluación de artículos";
include_once(__DIR__ . '/../header.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gescon</title>
    <link rel="stylesheet" href="../../public/css/evaluar_articulos.css">
</head>
<body>
<div class="evaluacion-container fade-in">
    <h2>Evaluar Artículos</h2>
    <?php if (!empty($mensaje)): ?>
        <p class="message"> <?= htmlspecialchars($mensaje) ?> </p>
    <?php endif; ?>

    <div class="evaluacion-grid">
        <?php foreach ($asignados as $f): ?>
            <?php if (is_null($f['aceptacion'])): ?>
                <form method="POST" class="form-articulo">
                    <fieldset>
                        <legend><?= htmlspecialchars($f['titulo']) ?></legend>
                        <p><?= htmlspecialchars($f['resumen']) ?></p>

                        <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?>">
                        <input type="hidden" name="id_articulo" value="<?= $f['id_articulo'] ?>">

                        <label for="calidad_<?= $f['id_articulo'] ?>">Calidad Técnica (1-10):</label>
                        <input id="calidad_<?= $f['id_articulo'] ?>" type="number" name="calidad_tecnica" min="1" max="10" value="<?= $f['calidad_tecnica'] ?>">

                        <label for="originalidad_<?= $f['id_articulo'] ?>">Originalidad:</label>
                        <select id="originalidad_<?= $f['id_articulo'] ?>" name="originalidad">
                            <option value="1" <?= $f['originalidad'] ? 'selected' : '' ?>>Sí</option>
                            <option value="0" <?= !$f['originalidad'] ? 'selected' : '' ?>>No</option>
                        </select>

                        <label for="valoracion_<?= $f['id_articulo'] ?>">Valoración Global (1-10):</label>
                        <input id="valoracion_<?= $f['id_articulo'] ?>" type="number" name="valoracion_global" min="1" max="10" value="<?= $f['valoracion_global'] ?>">

                        <label for="argumentos_<?= $f['id_articulo'] ?>">Argumentos:</label>
                        <textarea id="argumentos_<?= $f['id_articulo'] ?>" name="argumentosvg" rows="3"><?= htmlspecialchars($f['argumentosvg'] ?? '') ?></textarea>

                        <label for="comentarios_<?= $f['id_articulo'] ?>">Comentarios a autores:</label>
                        <textarea id="comentarios_<?= $f['id_articulo'] ?>" name="comentarios_autores" rows="3"><?= htmlspecialchars($f['comentarios_autores'] ?? '') ?></textarea>

                        <button type="submit">Evaluar y Verificar Aceptación</button>
                    </fieldset>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="historial-panel">
        <h3>Historial de Evaluaciones</h3>
        <button onclick="document.getElementById('historial').classList.toggle('visible')">Mostrar/ocultar</button>
        <div id="historial" class="historial hidden">
            <?php foreach ($asignados as $f): ?>
                <?php if (!is_null($f['aceptacion'])): ?>
                    <div class="historial-item">
                        <strong><?= htmlspecialchars($f['titulo']) ?></strong> -
                        <?= $f['aceptacion'] == 1 ? '✅ Aceptado' : '❌ Rechazado' ?>
                        <p><?= htmlspecialchars($f['resumen']) ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.querySelector('.historial-panel button');
        toggleBtn?.addEventListener('click', () => {
            document.getElementById('historial').classList.toggle('hidden');
        });
    });
</script>
</body>
</html>
