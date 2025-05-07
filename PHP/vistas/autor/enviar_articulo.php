<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once(__DIR__ . '/../../controladores/ArticuloController.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

$mensaje = '';
$topicos_area = ArticuloController::mostrarFormulario();

$titulo = $_POST['titulo'] ?? '';
$resumen = $_POST['resumen'] ?? '';
$autores = $_POST['autores'] ?? [['nombre' => '', 'email' => '', 'contacto' => '']];
$topicos_seleccionados = $_POST['topicos'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = ArticuloController::procesarEnvio($_POST);
    if (strpos($mensaje, 'correctamente') !== false) {
        $titulo = '';
        $resumen = '';
        $autores = [['nombre' => '', 'email' => '', 'contacto' => '']];
        $topicos_seleccionados = [];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar Artículo</title>
</head>
<body>
    <h2>Enviar nuevo artículo</h2>
    <p><a href="../dashboard.php">← Volver al Dashboard</a></p>
    <p style="color:red;"> <?= htmlspecialchars($mensaje) ?> </p>

    <form method="POST" action="">
        <label>Título:</label><br>
        <input type="text" name="titulo" value="<?= htmlspecialchars($titulo) ?>" required><br><br>

        <label>Resumen:</label><br>
        <textarea name="resumen" rows="4" cols="50" required><?= htmlspecialchars($resumen) ?></textarea><br><br>

        <label>Autores:</label>
        <div id="autores">
            <?php foreach ($autores as $i => $autor): ?>
                <div>
                    Nombre: <input type="text" name="autores[<?= $i ?>][nombre]" value="<?= htmlspecialchars($autor['nombre']) ?>" required>
                    Email: <input type="email" name="autores[<?= $i ?>][email]" value="<?= htmlspecialchars($autor['email']) ?>" required>
                    Contacto: <input type="checkbox" name="autores[<?= $i ?>][contacto]" value="1" <?= isset($autor['contacto']) && $autor['contacto'] == '1' ? 'checked' : '' ?>>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" onclick="agregarAutor()">Agregar autor</button><br><br>

        <label>Tópicos:</label><br>
        <?php foreach ($topicos_area as $row): ?>
            <label><input type="checkbox" name="topicos[]" value="<?= $row['id_area'] ?>" <?= in_array($row['id_area'], $topicos_seleccionados) ? 'checked' : '' ?>> <?= htmlspecialchars($row['titulo_area']) ?></label><br>
        <?php endforeach; ?><br>

        <input type="submit" value="Enviar artículo">
    </form>

    <script>
        let idx = <?= count($autores) ?>;
        function agregarAutor() {
            const container = document.getElementById('autores');
            const div = document.createElement('div');
            div.innerHTML = `Nombre: <input type="text" name="autores[${idx}][nombre]" required>
                             Email: <input type="email" name="autores[${idx}][email]" required>
                             Contacto: <input type="checkbox" name="autores[${idx}][contacto]" value="1">`;
            container.appendChild(div);
            idx++;
        }
    </script>
</body>
</html>
