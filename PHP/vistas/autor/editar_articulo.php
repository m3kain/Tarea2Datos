<?php
session_start();

require_once(__DIR__ . '/../../conexion.php');

$idArticulo = $_GET['id_articulo'] ?? null;

if (!$idArticulo) {
    echo "ID no proporcionado.";
    exit;
}

$stmt = $conn->prepare("SELECT titulo, resumen FROM articulo WHERE id_articulo = ?");
$stmt->execute([$idArticulo]);
$articulo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$articulo) {
    echo "Artículo no encontrado.";
    exit;
}

$stmtAutores = $conn->prepare("SELECT nombre, email, autor_contacto FROM escribiendo e JOIN usuarios u ON e.id_usuario = u.id_usuario WHERE id_articulo = ?");
$stmtAutores->execute([$idArticulo]);
$autores = $stmtAutores->fetchAll(PDO::FETCH_ASSOC);

$stmtTopicos = $conn->prepare("SELECT id_area FROM topicos WHERE id_articulo = ?");
$stmtTopicos->execute([$idArticulo]);
$topicosSeleccionados = array_column($stmtTopicos->fetchAll(PDO::FETCH_ASSOC), 'id_area');

$stmtAreas = $conn->query("SELECT * FROM area");
$topicos = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Artículo</title>
    <link rel="stylesheet" href="../../public/css/enviar_articulo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .topics {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.5rem 1rem;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .topics label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <h2>Editar Artículo</h2>
    <p><a class="back-link" href="../perfil.php">← Volver al perfil</a></p>

    <form method="POST" action="../../controladores/actualizar_articulo.php">
        <input type="hidden" name="id_articulo" value="<?= htmlspecialchars($idArticulo) ?>">

        <div class="form-group">
            <label for="titulo">Título:</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($articulo['titulo']) ?>" required>
        </div>

        <div class="form-group">
            <label for="resumen">Resumen:</label>
            <textarea name="resumen" rows="4" required><?= htmlspecialchars($articulo['resumen']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Autores:</label>
            <div id="autores">
                <?php foreach ($autores as $i => $autor): ?>
                    <div class="autor-entry fade-in">
                        <input type="text" name="autores[<?= $i ?>][nombre]" value="<?= htmlspecialchars($autor['nombre']) ?>" required>
                        <input type="email" name="autores[<?= $i ?>][email]" value="<?= htmlspecialchars($autor['email']) ?>" required>
                        <label class="contacto-check">
                            <input type="checkbox" name="autores[<?= $i ?>][contacto]" value="1" <?= $autor['autor_contacto'] ? 'checked' : '' ?> onchange="seleccionarContactoUnico(this)"> Contacto
                        </label>
                        <button type="button" class="remove-btn" onclick="eliminarAutor(this)">🗑</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="add-btn" onclick="agregarAutor()">Agregar autor</button>
        </div>

        <div class="form-group">
            <label>Tópicos:</label>
            <div class="topics fade-in">
                <?php foreach ($topicos as $row): ?>
                    <label><input type="checkbox" name="topicos[]" value="<?= $row['id_area'] ?>" <?= in_array($row['id_area'], $topicosSeleccionados) ? 'checked' : '' ?>> <?= htmlspecialchars($row['titulo_area']) ?></label>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="submit" class="submit-btn" value="Guardar Cambios">
    </form>
</div>

<script>
let idx = <?= count($autores) ?>;

function agregarAutor() {
    const container = document.getElementById('autores');
    const div = document.createElement('div');
    div.classList.add('autor-entry', 'fade-in');
    div.innerHTML = `
        <input type="text" name="autores[${idx}][nombre]" placeholder="Nombre" required>
        <input type="email" name="autores[${idx}][email]" placeholder="Email" required>
        <label class="contacto-check">
            <input type="checkbox" name="autores[${idx}][contacto]" value="1" onchange="seleccionarContactoUnico(this)"> Contacto
        </label>
        <button type="button" class="remove-btn" onclick="eliminarAutor(this)">🗑</button>
    `;
    container.appendChild(div);
    idx++;
}

function eliminarAutor(btn) {
    const div = btn.parentElement;
    div.remove();
}

function seleccionarContactoUnico(current) {
    document.querySelectorAll('#autores input[type="checkbox"]').forEach(cb => {
        if (cb !== current) cb.checked = false;
    });
}
</script>
</body>
</html>
