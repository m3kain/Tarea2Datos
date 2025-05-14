<!DOCTYPE html>
<link rel="stylesheet" href="../public/css/register.css">
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Usuario</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
  <div class="register-container">
    <h2>Registro de Autor</h2>

    <?php if (isset($_GET['error'])): ?>
      <div class="error"> <?= htmlspecialchars($_GET['error']) ?> </div>
    <?php endif; ?>

    <form action="../controladores/RegisterController.php" method="POST">
      <div class="form-group">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
      </div>

      <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required>
      </div>

      <div class="form-group">
        <label>Contraseña:</label>
        <input type="password" name="password" required>
      </div>

      <div class="form-group">
        <label>Confirmar Contraseña:</label>
        <input type="password" name="confirmar" required>
      </div>

      <button type="submit" class="submit-btn">Registrarse</button>
    </form>
  </div>
</body>
</html>
