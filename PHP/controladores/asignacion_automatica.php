<?php
require_once(__DIR__ . '/../conexion.php');

// Obtener todos los artículos con sus tópicos
$articulos = $conn->query("SELECT a.id_articulo, GROUP_CONCAT(t.id_area) AS topicos
                           FROM articulo a
                           JOIN topicos t ON a.id_articulo = t.id_articulo
                           GROUP BY a.id_articulo")->fetchAll(PDO::FETCH_ASSOC);

// Obtener revisores con sus especializaciones y subclase
$revisores = $conn->query("SELECT u.id_usuario, u.subclase, GROUP_CONCAT(e.id_area) AS especialidades
                            FROM usuarios u
                            JOIN especializacion e ON u.id_usuario = e.id_usuario
                            WHERE u.subclase IN (3, 4)
                            GROUP BY u.id_usuario")->fetchAll(PDO::FETCH_ASSOC);

foreach ($articulos as $articulo) {
    $id_articulo = $articulo['id_articulo'];
    $topicos_articulo = explode(',', $articulo['topicos']);

    // Revisores ya asignados manualmente o automáticamente
    $stmtExistentes = $conn->prepare("SELECT id_usuario FROM formulario WHERE id_articulo = ?");
    $stmtExistentes->execute([$id_articulo]);
    $ya_asignados = array_column($stmtExistentes->fetchAll(PDO::FETCH_ASSOC), 'id_usuario');

    // Obtener autores del artículo
    $stmtAutores = $conn->prepare("SELECT id_usuario FROM escribiendo WHERE id_articulo = ?");
    $stmtAutores->execute([$id_articulo]);
    $autores = array_column($stmtAutores->fetchAll(PDO::FETCH_ASSOC), 'id_usuario');

    // Contar cuántos revisores ya tiene asignado
    $total_actuales = count($ya_asignados);
    if ($total_actuales >= 3) continue;

    foreach ($revisores as $rev) {
        $id_rev = $rev['id_usuario'];

        if (in_array($id_rev, $ya_asignados) || in_array($id_rev, $autores)) continue;

        $especialidades = explode(',', $rev['especialidades']);
        $coinciden = array_intersect($topicos_articulo, $especialidades);
        
        if (!empty($coinciden)) {
            $conn->prepare("INSERT INTO formulario (id_usuario, id_articulo, manual) VALUES (?, ?, 0)")
                 ->execute([$id_rev, $id_articulo]);
            $total_actuales++;
        }

        if ($total_actuales >= 3) break;
    }
}

header("Location: ../vistas/jefe/gestion_asignaciones.php?view=articulos");
exit;
