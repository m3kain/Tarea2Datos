<?php
require_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_POST['accion'], $_POST['id_usuario'], $_POST['id_articulo'])) {
    header("Location: ../vistas/jefe/gestion_asignaciones.php?error=faltan_datos");
    exit;
}

$accion = $_POST['accion'];
$id_usuario = (int) $_POST['id_usuario'];
$id_articulo = (int) $_POST['id_articulo'];
$view = $_POST['view'] ?? 'articulos';

try {
    if ($accion === 'asignar') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_usuario = ? AND id_articulo = ?");
        $stmt->execute([$id_usuario, $id_articulo]);
        $exists = $stmt->fetchColumn();

        if ($exists == 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario WHERE id_articulo = ?");
            $stmt->execute([$id_articulo]);
            $totalAsignados = $stmt->fetchColumn();

            if ($totalAsignados >= 3) {
                header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view&error=max_revisores");
                exit;
            }

            if ($_POST['accion'] === 'asignar') {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM escribiendo WHERE id_usuario = ? AND id_articulo = ?");
                $stmt->execute([$id_usuario, $id_articulo]);
                $esAutor = $stmt->fetchColumn();
            
                if ($esAutor) {
                    die("No se puede asignar el artículo a un revisor que es su autor.");
                }
            }
            

            $stmt = $conn->prepare("INSERT INTO formulario (id_usuario, id_articulo, manual) VALUES (?, ?, 1)");
            $stmt->execute([$id_usuario, $id_articulo]);
        }

    } elseif ($accion === 'quitar') {
        $stmt = $conn->prepare("DELETE FROM formulario WHERE id_usuario = ? AND id_articulo = ?");
        $stmt->execute([$id_usuario, $id_articulo]);
    }

    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view");

} catch (PDOException $e) {
    error_log($e->getMessage());
    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view&error=bd");
    exit;
}