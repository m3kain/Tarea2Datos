<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $especialidades = $_POST['areas'] ?? [];

    if (!$nombre || !$email || !$password) {
        die("Faltan campos obligatorios.");
    }

    $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        die("Este email ya está registrado.");
    }

    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, ?, 3)");
    $stmt->execute([$nombre, $email, $password]);

    $id_usuario = $conn->lastInsertId();

    $insert_esp = $conn->prepare("INSERT INTO especializacion (id_usuario, id_area) VALUES (?, ?)");
    foreach ($especialidades as $id_area) {
        $insert_esp->execute([$id_usuario, $id_area]);
    }

    header('Location: ../vistas/jefe/gestionar_revisores.php');
    exit;
    
}
?>
