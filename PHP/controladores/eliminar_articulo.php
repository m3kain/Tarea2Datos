<?php
session_start();
require_once(__DIR__ . '/../conexion.php');

header('Content-Type: application/json');

$idArticulo = $_POST['id_articulo'] ?? null;
$idUsuario = $_SESSION['id_usuario'] ?? null;
$subclase = $_SESSION['rol'] ?? null;

if (!$idArticulo || !$idUsuario) {
    echo json_encode(["status" => "error", "message" => "❌ Parámetros faltantes."]);
    exit;
}

// Validación de permiso
if ((int)$subclase === 1) {
    $permiso = true;
} else {
    $stmt = $conn->prepare("SELECT autor_contacto FROM escribiendo WHERE id_articulo = ? AND id_usuario = ?");
    $stmt->execute([$idArticulo, $idUsuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["status" => "error", "message" => "❌ El usuario no está registrado como autor."]);
        exit;
    }

    if ((int)$row['autor_contacto'] !== 1) {
        echo json_encode(["status" => "error", "message" => "🚫 No es autor de contacto."]);
        exit;
    }

    $permiso = true;
}

if (!$permiso) {
    echo json_encode(["status" => "error", "message" => "🚫 No tienes permisos para eliminar este artículo."]);
    exit;
}

// Eliminar
$conn->prepare("DELETE FROM formulario WHERE id_articulo = ?")->execute([$idArticulo]);
$conn->prepare("DELETE FROM topicos WHERE id_articulo = ?")->execute([$idArticulo]);
$conn->prepare("DELETE FROM escribiendo WHERE id_articulo = ?")->execute([$idArticulo]);
$conn->prepare("DELETE FROM articulo WHERE id_articulo = ?")->execute([$idArticulo]);

echo json_encode(["status" => "success", "message" => "✅ Artículo eliminado correctamente."]);
exit;
