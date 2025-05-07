<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php"); // redirige si ya está logueado
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
</head>
<body>
    <h2>Login de Usuario</h2>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>

    <form method="POST" action="../controladores/LoginController.php">
        <label for="email">Correo:</label>
        <input type="email" name="email" required><br><br>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Iniciar sesión">
    </form>

    <a href="register.php">Registrarse</a>

</body>
</html>
