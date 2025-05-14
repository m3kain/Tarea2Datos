<?php
session_start();
require_once(__DIR__ . '/../conexion.php');
require_once(__DIR__ . '/../modelos/Usuario.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $usuario = Usuario::obtenerPorEmail($email);

    if ($usuario && $usuario['password'] === $password) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['subclase'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['subclase'] = $usuario['subclase']; 
        header("Location: ../vistas/dashboard.php");
        exit;
    } else {
        header("Location: ../vistas/login.php?error=Credenciales inválidas");
        exit;
    }
}
?>
