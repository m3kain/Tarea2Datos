<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$idArticulo = $_GET['id_articulo'] ?? null;
if (!$idArticulo) {
    die("ID de artículo inválido.");
}

$stmt = $conn->prepare("SELECT titulo, resumen FROM articulo WHERE id_articulo = ?");
$stmt->execute([$idArticulo]);
$articulo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$articulo) {
    die("Artículo no encontrado.");
}

$stmtTopicos = $conn->prepare("SELECT id_area FROM topicos WHERE id_articulo = ?");
$stmtTopicos->execute([$idArticulo]);
$topicosSeleccionados = $stmtTopicos->fetchAll(PDO::FETCH_COLUMN);

$stmtAreas = $conn->query("SELECT * FROM area");
$areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

$stmtAutores = $conn->prepare("
    SELECT u.nombre, u.email, e.autor_contacto as contacto
    FROM escribiendo e
    JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE e.id_articulo = ?
");
$stmtAutores->execute([$idArticulo]);
$autores = $stmtAutores->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Editar Artículo</h2>
<p><a href="perfil.php">&larr; Volver al perfil</a></p>

<form method="POST" action="../controladores/editar_articulo_controller.php" onsubmit="return validarContacto()">
    <input type="hidden" name="id_articulo" value="<?= htmlspecialchars($idArticulo) ?>">

    <label>Título:</label><br>
    <input type="text" name="titulo" value="<?= htmlspecialchars($articulo['titulo']) ?>" required><br><br>

    <label>Resumen:</label><br>
    <textarea name="resumen" rows="5" cols="60" required><?= htmlspecialchars($articulo['resumen']) ?></textarea><br><br>

    <label>Autores:</label>
    <div id="autores">
        <?php foreach ($autores as $i => $autor): ?>
            <div>
                Nombre: <input type="text" name="autores[<?= $i ?>][nombre]" value="<?= htmlspecialchars($autor['nombre']) ?>" required>
                Email: <input type="email" name="autores[<?= $i ?>][email]" value="<?= htmlspecialchars($autor['email']) ?>" required>
                Contacto: <input type="checkbox" name="autores[<?= $i ?>][contacto]" value="1" <?= isset($autor['contacto']) && $autor['contacto'] ? 'checked' : '' ?> onchange="seleccionarContactoUnico(this)">
                <button type="button" onclick="eliminarAutor(this)">🗑</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="agregarAutor()">Agregar autor</button><br><br>

    <label>Tópicos:</label><br>
    <?php foreach ($areas as $area): ?>
        <label>
            <input type="checkbox" name="topicos[]" value="<?= $area['id_area'] ?>" <?= in_array($area['id_area'], $topicosSeleccionados) ? 'checked' : '' ?>>
            <?= htmlspecialchars($area['titulo_area']) ?>
        </label><br>
    <?php endforeach; ?><br>

    <button type="submit">Guardar Cambios</button>
</form>

<script>
let idx = <?= count($autores) ?>;

function agregarAutor() {
    const container = document.getElementById('autores');
    const div = document.createElement('div');
    div.innerHTML = `
        Nombre: <input type="text" name="autores[${idx}][nombre]" required>
        Email: <input type="email" name="autores[${idx}][email]" required>
        Contacto: <input type="checkbox" name="autores[${idx}][contacto]" value="1" onchange="seleccionarContactoUnico(this)">
        <button type="button" onclick="eliminarAutor(this)">🗑</button>
    `;
    container.appendChild(div);
    idx++;
}

function seleccionarContactoUnico(current) {
    document.querySelectorAll('#autores input[type="checkbox"]').forEach(cb => {
        if (cb !== current) cb.checked = false;
    });
}

function eliminarAutor(btn) {
    const div = btn.parentElement;
    const wasContact = div.querySelector('input[type="checkbox"]').checked;
    div.remove();

    if (wasContact) {
        const checks = document.querySelectorAll('#autores input[type="checkbox"]');
        if (![...checks].some(cb => cb.checked) && checks.length > 0) {
            checks[0].checked = true;
        }
    }
}

function validarContacto() {
    const checks = document.querySelectorAll('#autores input[type="checkbox"]');
    if (![...checks].some(cb => cb.checked)) {
        alert('Debe haber al menos un autor marcado como contacto.');
        return false;
    }
    return true;
}
</script>