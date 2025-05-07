<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
</head>
<body>
    <h2>Registro de Autor</h2>
    <form action="../controladores/RegisterController.php" method="POST">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br>

        <label>Confirmar Contraseña:</label><br>
        <input type="password" name="confirmar" required><br>

        <button type="submit">Registrarse</button>
    </form>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_GET['error']) ?></p>
    <?php endif; ?>
</body>
</html>
