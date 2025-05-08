<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');

$idArticulo = $_POST['id_articulo'] ?? null;
$idUsuario = $_SESSION['id_usuario'] ?? null;

if (!$idArticulo || !$idUsuario) {
    die("Parámetros faltantes.");
}

// Verificar si el usuario es autor del artículo
$stmt = $conn->prepare("SELECT COUNT(*) FROM escribiendo WHERE id_articulo = ? AND id_usuario = ?");
$stmt->execute([$idArticulo, $idUsuario]);

if ($stmt->fetchColumn() == 0) {
    die("No tienes permisos para eliminar este artículo.");
}

// Eliminar evaluaciones (formulario)
$conn->prepare("DELETE FROM formulario WHERE id_articulo = ?")->execute([$idArticulo]);

// Eliminar tópicos
$conn->prepare("DELETE FROM topicos WHERE id_articulo = ?")->execute([$idArticulo]);

// Eliminar autores
$conn->prepare("DELETE FROM escribiendo WHERE id_articulo = ?")->execute([$idArticulo]);

// Finalmente eliminar artículo
$conn->prepare("DELETE FROM articulo WHERE id_articulo = ?")->execute([$idArticulo]);

header("Location: ../vistas/perfil.php?borrado=1");
exit;
