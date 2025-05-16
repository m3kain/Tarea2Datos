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
    // Verifica si tiene artículos pendientes por revisar
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM formulario 
        WHERE id_usuario = ? AND (calidad_tecnica IS NULL OR valoracion_global IS NULL)
    ");
    $stmt->execute([$id]);
    $pendientes = $stmt->fetchColumn();

    if ((int)$pendientes > 0) {
        echo json_encode(['status' => 'error', 'message' => 'No puedes eliminar un revisor con artículos pendientes.']);
        exit;
    }

    // Eliminar especializaciones asociadas (opcional pero recomendable)
    $conn->prepare("DELETE FROM especializacion WHERE id_usuario = ?")->execute([$id]);

    // Eliminar usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'ok', 'message' => 'Revisor eliminado correctamente.']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
}
