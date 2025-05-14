<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php"); // redirige si ya está logueado
    exit;
}
?>
<link rel="stylesheet" href="../public/css/login.css">


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
</head>
<body>
  <div class="login-container">
    <h2>Login de Usuario</h2>

    <?php if (isset($_GET['error'])): ?>
      <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form method="POST" action="../controladores/LoginController.php">
      <div class="form-group">
        <label for="email">Correo:</label>
        <input type="email" name="email" required>
      </div>

      <div class="form-group">
        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>
      </div>

      <input type="submit" class="submit-btn" value="Iniciar sesión">
    </form>

    <a class="register-link" href="register.php">Registrarse</a>
  </div>
</body>
</html>
