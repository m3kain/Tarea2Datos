<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../controladores/ArticuloController.php');
require_once(__DIR__ . '/../../modelos/Usuario.php');

$nombre_usuario = $_SESSION['nombre'];
$email_usuario = $_SESSION['email'] ?? Usuario::obtenerPorID($_SESSION['id_usuario'])['email'];
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

$mensaje = '';
$topicos_area = ArticuloController::mostrarFormulario();

$titulo = $_POST['titulo'] ?? '';
$resumen = $_POST['resumen'] ?? '';
$autores = $_POST['autores'] ?? [['nombre' => $nombre_usuario, 'email' => $email_usuario, 'contacto' => '1']];
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
<link rel="stylesheet" href="../../public/css/enviar_articulo.css">
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gescon</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../public/css/enviar_articulo.css">

  <style>
    .message.success { color: green; text-align: center; margin-bottom: 1rem; }
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
    <h2>Enviar nuevo artículo</h2>
    <p><a class="back-link" href="../dashboard.php">← Volver </a></p>

    <?php if (!empty($mensaje)): ?>
      <div class="message <?= strpos($mensaje, 'correctamente') !== false ? 'success' : '' ?>"> <?= htmlspecialchars($mensaje) ?> </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validarContacto()">
      <div class="form-group">
        <label for="titulo">Título:</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($titulo) ?>" required>
      </div>

      <div class="form-group">
        <label for="resumen">Resumen:</label>
        <textarea name="resumen" rows="4" required><?= htmlspecialchars($resumen) ?></textarea>
      </div>

      <div class="form-group">
        <label>Autores:</label>
        <div id="autores">
          <?php foreach ($autores as $i => $autor): ?>
            <div class="autor-entry fade-in">
              <input type="text" name="autores[<?= $i ?>][nombre]" value="<?= htmlspecialchars($autor['nombre']) ?>" placeholder="Nombre" required>
              <input type="email" name="autores[<?= $i ?>][email]" value="<?= htmlspecialchars($autor['email']) ?>" placeholder="Email" required>
              <label class="contacto-check">
                <input type="checkbox" name="autores[<?= $i ?>][contacto]" value="1" <?= isset($autor['contacto']) && $autor['contacto'] == '1' ? 'checked' : '' ?> onchange="seleccionarContactoUnico(this)">
                Contacto
              </label>
              <?php if ($autor['nombre'] !== $nombre_usuario || $autor['email'] !== $email_usuario): ?>
                <button type="button" class="remove-btn" onclick="eliminarAutor(this)">🗑</button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          
        </div>
        <button type="button" class="add-btn" onclick="agregarAutor()">Agregar autor</button>
      </div>

      <div class="form-group">
        <label>Tópicos:</label>
        <div class="topics fade-in">
          <?php foreach ($topicos_area as $row): ?>
            <label><input type="checkbox" name="topicos[]" value="<?= $row['id_area'] ?>" <?= in_array($row['id_area'], $topicos_seleccionados) ? 'checked' : '' ?>> <?= htmlspecialchars($row['titulo_area']) ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <input type="submit" class="submit-btn" value="Enviar artículo">
    </form>
  </div>

<script>
let idx = <?= count($autores) ?>;

const datosUsuario = {
    nombre: <?= json_encode($nombre_usuario) ?>,
    email: <?= json_encode($email_usuario) ?>
};

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

function seleccionarContactoUnico(current) {
  document.querySelectorAll('#autores input[type="checkbox"]').forEach(cb => {
    if (cb !== current) cb.checked = false;
  });
}

function eliminarAutor(btn) {
  const div = btn.parentElement;
  const wasContact = div.querySelector('input[type="checkbox"]').checked;
  div.classList.remove('fade-in');
  div.style.opacity = 0;
  setTimeout(() => div.remove(), 300);

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

// Inicializa para los ya renderizados
document.addEventListener('DOMContentLoaded', () => {
    // Activar checkboxes únicos
    document.querySelectorAll('#autores input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function () {
            seleccionarContactoUnico(this);
        });
    });
});


</script>


</body>
</html>
