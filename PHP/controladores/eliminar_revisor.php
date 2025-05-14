<?php
require_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID inválido.");
}

// Verifica si tiene formularios pendientes
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

// Obtener artículos evaluados
$stmt = $conn->prepare("SELECT id_articulo FROM formulario WHERE id_usuario = ?");
$stmt->execute([$id]);
$articulosEvaluados = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Eliminar sus formularios
$conn->prepare("DELETE FROM formulario WHERE id_usuario = ?")->execute([$id]);

// Reestablecer estado si el artículo queda sin 3 evaluaciones
foreach ($articulosEvaluados as $artId) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ?");
    $stmt->execute([$artId]);
    $count = $stmt->fetchColumn();

    if ($count < 3) {
        $conn->prepare("UPDATE articulo SET aceptacion = NULL WHERE id_articulo = ?")->execute([$artId]);
    }
}

// Eliminar especializaciones
$conn->prepare("DELETE FROM especializacion WHERE id_usuario = ?")->execute([$id]);

// Eliminar usuario o degradar
if ($subclase == 3) {
    $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);
} elseif ($subclase == 4) {
    $conn->prepare("UPDATE usuarios SET subclase = 2 WHERE id_usuario = ?")->execute([$id]);
}

$noti = urlencode("Revisor eliminado correctamente.");
header("Location: ../vistas/jefe/gestionar_revisores.php?noti=$noti");
exit;
