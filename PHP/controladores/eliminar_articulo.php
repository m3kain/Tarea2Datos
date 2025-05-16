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
        echo json_encode(["status" => "error", "message" => "⚠️ No es autor de contacto."]);
        exit;
    }

    $permiso = true;
}

if (!$permiso) {
    echo json_encode(["status" => "error", "message" => "No tienes permisos para eliminar este artículo."]);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("SELECT id_usuario FROM escribiendo WHERE id_articulo = ?");
    $stmt->execute([$idArticulo]);
    $usuariosRelacionados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $conn->prepare("DELETE FROM formulario WHERE id_articulo = ?")->execute([$idArticulo]);
    $conn->prepare("DELETE FROM topicos WHERE id_articulo = ?")->execute([$idArticulo]);
    $conn->prepare("DELETE FROM escribiendo WHERE id_articulo = ?")->execute([$idArticulo]);

    // Verificamos si el artículo existía realmente
    $stmt = $conn->prepare("DELETE FROM articulo WHERE id_articulo = ?");
    $stmt->execute([$idArticulo]);


    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => "❌ El artículo no existe o ya fue eliminado."]);
        exit;
    }

    if (!empty($usuariosRelacionados)) {
        $in = str_repeat('?,', count($usuariosRelacionados) - 1) . '?';

        $stmt = $conn->prepare("SELECT u.id_usuario FROM usuarios u WHERE u.subclase = 4 AND u.id_usuario IN ($in) AND NOT EXISTS (SELECT 1 FROM escribiendo e WHERE e.id_usuario = u.id_usuario)");
        $stmt->execute($usuariosRelacionados);
        $aDegradar = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($aDegradar)) {
            $in2 = str_repeat('?,', count($aDegradar) - 1) . '?';
            $stmt = $conn->prepare("UPDATE usuarios SET subclase = 3 WHERE id_usuario IN ($in2)");
            $stmt->execute($aDegradar);
        }
    }

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "✅ Artículo eliminado correctamente."]);
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("ERROR al eliminar artículo: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "❌ No se pudo eliminar el artículo."]);
}

exit;
