<?php
require_once(__DIR__ . '/../conexion.php');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../vistas/login.php");
    exit;
}

$id = $_SESSION['id_usuario'];
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$actual = $_POST['actual'] ?? '';
$nueva = $_POST['nueva'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if ($nombre && $email) {
    $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?")
         ->execute([$nombre, $email, $id]);
}

// Si algún campo de contraseña fue enviado, procesamos validación
if ($actual || $nueva || $confirmar) {
    if (!$actual || !$nueva || !$confirmar) {
        header("Location: ../vistas/perfil.php?error=incompleto");
        exit;
    }

    $stmt = $conn->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($actual, $hash)) {
        header("Location: ../vistas/perfil.php?error=incorrecta");
        exit;
    }

    if ($nueva !== $confirmar) {
        header("Location: ../vistas/perfil.php?error=nomatch");
        exit;
    }

    $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
    $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?")
         ->execute([$nuevoHash, $id]);
}

header("Location: ../vistas/perfil.php?msg=actualizado");
exit;
