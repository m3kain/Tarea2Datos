<?php
require_once(__DIR__ . '/../conexion.php');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../vistas/login.php");
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($nombre && $email) {
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?");
    $stmt->execute([$nombre, $email, $_SESSION['id_usuario']]);
    header("Location: ../vistas/perfil.php?msg=actualizado");
} else {
    header("Location: ../vistas/perfil.php?error=datos_invalidos");
}
exit;
