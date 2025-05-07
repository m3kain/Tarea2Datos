<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/Usuario.php');



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validación simple
    if (empty($nombre) || empty($email) || empty($password)) {
        header("Location: ../vistas/register.php?error=Faltan campos");
        exit;
    }

    // Verificar si ya existe ese correo
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: ../vistas/register.php?error=El correo ya está registrado");
        exit;
    }

    // Subclase 2 → autor por defecto
    $creado = Usuario::crear($nombre, $email, $password, 2);

    if ($creado) {
        // Guardar sesión automáticamente después del registro
        $_SESSION['nombre'] = $nombre;
        $_SESSION['subclase'] = 2;
        $_SESSION['rol'] = 2;

        // Obtener el ID recién creado
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['id_usuario'] = $usuario['id_usuario'];

        header("Location: ../vistas/dashboard.php");
        exit;
    } else {
        header("Location: ../vistas/register.php?error=Error al registrar usuario");
        exit;
    }
}
?>
