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
                    $mensaje = urlencode("No se puede asignar el artículo a un revisor que es su autor.");
                    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view&error=$mensaje");
                    exit;
                }
            }
            

            $stmt = $conn->prepare("INSERT INTO formulario (id_usuario, id_articulo, manual) VALUES (?, ?, 1)");
            $stmt->execute([$id_usuario, $id_articulo]);
        }

    } elseif ($accion === 'quitar') {
        // Verificar si el artículo ya fue evaluado completamente
        $stmt = $conn->prepare("SELECT COUNT(*) FROM formulario
        WHERE id_articulo = ? AND calidad_tecnica IS NOT NULL AND valoracion_global IS NOT NULL");
        $stmt->execute([$id_articulo]);
        $evaluadas = $stmt->fetchColumn();

        if ((int) $evaluadas >= 3) {
        header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view&error=ya_evaluado");
        exit;
        }    

        $stmt = $conn->prepare("DELETE FROM formulario WHERE id_usuario = ? AND id_articulo = ?");
        $stmt->execute([$id_usuario, $id_articulo]);
    }

    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view");

} catch (PDOException $e) {
    error_log($e->getMessage());
    header("Location: ../vistas/jefe/gestion_asignaciones.php?view=$view&error=bd");
    exit;
}

echo json_encode(["status" => "ok"]);
exit;