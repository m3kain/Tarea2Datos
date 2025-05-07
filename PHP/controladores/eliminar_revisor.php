<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once(__DIR__ . '/../conexion.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID inválido.");
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_usuario = ?");
$stmt->execute([$id]);
$asignaciones = $stmt->fetchColumn();

if ($asignaciones > 0) {
    die("Este revisor no puede eliminarse porque tiene artículos asignados.");
}

$conn->prepare("DELETE FROM especializacion WHERE id_usuario = ?")->execute([$id]);
$conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);

header('Location: ../vistas/jefe/gestionar_revisores.php');
exit;
?>
