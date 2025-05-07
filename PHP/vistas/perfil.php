<?php
session_start();
require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/Usuario.php');

// Verifica que el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = Usuario::obtenerPorID($_SESSION['id_usuario']);
?>

<h2>Perfil de Usuario</h2>
<p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
<p><strong>ID:</strong> <?= htmlspecialchars($usuario['id_usuario']) ?></p>
<p><strong>Rol:</strong>
    <?php
        switch ($usuario['subclase']) {
            case 1: echo "Jefe de Comité"; break;
            case 2: echo "Autor"; break;
            case 3: echo "Revisor"; break;
            case 4: echo "Autor y Revisor"; break;
            default: echo "Desconocido";
        }
    ?>
</p>


<p><a href="dashboard.php">Volver al Dashboard</a></p>
<p><a href="logout.php">Cerrar sesión</a></p>
