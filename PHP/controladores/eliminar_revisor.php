<?php
require_once(__DIR__ . '/../conexion.php');

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_POST['id_revisor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos.']);
    exit;
}

$id = (int) $_POST['id_revisor'];

try {
    // Verificar si el revisor tiene asignaciones
    $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_usuario = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Este revisor tiene artículos asignados.']);
        exit;
    }

    // Eliminar revisor
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'ok', 'message' => 'Revisor eliminado correctamente.']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
}
