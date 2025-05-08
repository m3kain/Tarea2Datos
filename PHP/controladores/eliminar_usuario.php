<?php
require_once(__DIR__ . '/../conexion.php');
session_start();

$id = $_POST['id_usuario'] ?? null;
if (!$id || $_SESSION['id_usuario'] != $id) {
    header("Location: ../vistas/perfil.php?error=permisos");
    exit;
}

$stmt = $conn->prepare("SELECT subclase FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$rol = $stmt->fetchColumn();

if ($rol == 1) {
    $stmt = $conn->query("SELECT COUNT(*) FROM usuarios WHERE subclase = 1");
    $jefes = $stmt->fetchColumn();
    if ((int)$jefes <= 1) {
        header("Location: ../vistas/perfil.php?error=unico_jefe");
        exit;
    }
}

if (in_array($rol, [2, 4])) {
    $stmt = $conn->prepare("SELECT id_articulo FROM escribiendo WHERE id_usuario = ? AND autor_contacto = 1");
    $stmt->execute([$id]);
    $articulosContacto = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($articulosContacto as $idArt) {
        $stmt = $conn->prepare("SELECT id_usuario FROM escribiendo WHERE id_articulo = ? AND id_usuario != ?");
        $stmt->execute([$idArt, $id]);
        $nuevo = $stmt->fetchColumn();

        if ($nuevo) {
            $stmt = $conn->prepare("UPDATE escribiendo SET autor_contacto = 1 WHERE id_articulo = ? AND id_usuario = ?");
            $stmt->execute([$idArt, $nuevo]);
        }

        $stmt = $conn->prepare("UPDATE escribiendo SET autor_contacto = 0 WHERE id_articulo = ? AND id_usuario = ?");
        $stmt->execute([$idArt, $id]);
    }
}

if (in_array($rol, [3, 4])) {
    $stmt = $conn->prepare("DELETE FROM formulario WHERE id_usuario = ? AND calidad_tecnica IS NULL AND valoracion_global IS NULL");
    $stmt->execute([$id]);
}

$conn->prepare("DELETE FROM especializacion WHERE id_usuario = ?")->execute([$id]);
$conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?")->execute([$id]);

session_destroy();
header("Location: ../vistas/login.php?msg=cuenta_eliminada");
exit;
