<?php
require_once(__DIR__ . '/../conexion.php');
session_start();

$idArticulo = $_POST['id_articulo'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$resumen = $_POST['resumen'] ?? '';
$autores = $_POST['autores'] ?? [];
$topicos = $_POST['topicos'] ?? [];

if (!$idArticulo || !$titulo || !$resumen || empty($autores)) {
    header("Location: ../perfil.php?noti=" . urlencode("❌ Faltan datos para actualizar el artículo"));
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE articulo SET titulo = ?, resumen = ? WHERE id_articulo = ?");
    $stmt->execute([$titulo, $resumen, $idArticulo]);

    $stmt = $conn->prepare("DELETE FROM escribiendo WHERE id_articulo = ?");
    $stmt->execute([$idArticulo]);

    foreach ($autores as $autor) {
        $nombre = trim($autor['nombre']);
        $email = trim($autor['email']);
        $esContacto = isset($autor['contacto']) ? 1 : 0;

        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $idUsuario = $stmt->fetchColumn();

        if (!$idUsuario) {
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email) VALUES (?, ?)");
            $stmt->execute([$nombre, $email]);
            $idUsuario = $conn->lastInsertId();
        }

        $stmt = $conn->prepare("INSERT INTO escribiendo (id_usuario, id_articulo, autor_contacto) VALUES (?, ?, ?)");
        $stmt->execute([$idUsuario, $idArticulo, $esContacto]);
    }

    $stmt = $conn->prepare("DELETE FROM topicos WHERE id_articulo = ?");
    $stmt->execute([$idArticulo]);

    foreach ($topicos as $idArea) {
        $stmt = $conn->prepare("INSERT INTO topicos (id_articulo, id_area) VALUES (?, ?)");
        $stmt->execute([$idArticulo, $idArea]);
    }

    $conn->commit();
    header("Location: ../vistas/perfil.php?noti=" . urlencode("✅ Artículo actualizado correctamente"));
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    header("Location: ../vistas/perfil.php?noti=" . urlencode("❌ Error al actualizar el artículo"));
    exit;
}
