<?php
require_once('../conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_articulo'])) {
    $id = (int)$_POST['id_articulo'];

    $stmt = $conn->prepare("UPDATE formulario SET manual = 0 WHERE id_articulo = ?");
    $stmt->execute([$id]);

    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=articulos");
    exit();
}
