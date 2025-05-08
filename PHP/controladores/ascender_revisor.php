<?php
require_once(__DIR__ . '/../conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario'])) {
    $id = intval($_POST['id_usuario']);

    // Verifica si el autor tiene artículos
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM escribiendo 
        WHERE id_usuario = ?
    ");
    $stmt->execute([$id]);
    $tieneArticulos = $stmt->fetchColumn() > 0;

    // Asignar subclase
    $nuevaSubclase = $tieneArticulos ? 4 : 3;
    $stmt = $conn->prepare("UPDATE usuarios SET subclase = ? WHERE id_usuario = ?");
    $stmt->execute([$nuevaSubclase, $id]);

    if ($tieneArticulos) {
        // Obtener áreas relacionadas a los artículos escritos
        $stmt = $conn->prepare("
            SELECT DISTINCT t.id_area
            FROM escribiendo e
            JOIN topicos t ON e.id_articulo = t.id_articulo
            WHERE e.id_usuario = ?
        ");
        $stmt->execute([$id]);
        $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Insertar en especializacion si no existe
        foreach ($areas as $areaId) {
            $stmt = $conn->prepare("
                INSERT IGNORE INTO especializacion (id_usuario, id_area)
                VALUES (?, ?)
            ");
            $stmt->execute([$id, $areaId]);
        }
    }

    $noti = urlencode("Autor ascendido a revisor correctamente.");
    header("Location: ../vistas/jefe/gestionar_revisores.php?noti=$noti");
    exit;    
}

header('Location: ../dashboard.php');
exit();
