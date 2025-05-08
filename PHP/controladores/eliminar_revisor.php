<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID inválido.");
}

// Verifica si tiene formularios asignados
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM formulario f
    JOIN articulo a ON f.id_articulo = a.id_articulo
    WHERE f.id_usuario = ? AND a.aceptacion IS NULL
");
$stmt->execute([$id]);
$pendientes = $stmt->fetchColumn();

if ($pendientes > 0) {
    die("No se puede eliminar: tiene evaluaciones pendientes.");
}



// Verifica subclase
$stmt = $conn->prepare("SELECT subclase FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$subclase = $stmt->fetchColumn();

// Eliminar especializaciones
$conn->prepare("DELETE FROM especializacion WHERE id_usuario = ?")->execute([$id]);

if ($subclase == 3) {
    // Eliminar completamente
    $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);
} elseif ($subclase == 4) {
    // Cambiar a autor
    $conn->prepare("UPDATE usuarios SET subclase = 2 WHERE id_usuario = ?")->execute([$id]);
}

$noti = urlencode("Revisor eliminado y notificado correctamente.");
header("Location: ../vistas/jefe/gestionar_revisores.php?noti=$noti");
exit;

