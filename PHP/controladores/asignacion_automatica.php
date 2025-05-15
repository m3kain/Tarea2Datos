<?php
require_once(__DIR__ . '/../conexion.php');

// Obtener revisores con especialidades y cuántos artículos tienen asignados
$revisores = $conn->query("SELECT u.id_usuario, u.subclase, 
                                  GROUP_CONCAT(e.id_area) AS especialidades,
                                  (SELECT COUNT(*) FROM formulario f WHERE f.id_usuario = u.id_usuario) AS carga
                           FROM usuarios u
                           JOIN especializacion e ON u.id_usuario = e.id_usuario
                           WHERE u.subclase IN (3, 4)
                           GROUP BY u.id_usuario")->fetchAll(PDO::FETCH_ASSOC);

// Reorganizar revisores por menor carga
usort($revisores, function($a, $b) {
    return $a['carga'] <=> $b['carga'];
});

// Obtener artículos con sus tópicos
$articulos = $conn->query("SELECT a.id_articulo, GROUP_CONCAT(t.id_area) AS topicos
                            FROM articulo a
                            JOIN topicos t ON a.id_articulo = t.id_articulo
                            GROUP BY a.id_articulo")->fetchAll(PDO::FETCH_ASSOC);

foreach ($articulos as $articulo) {
    $id_articulo = $articulo['id_articulo'];
    $topicos_articulo = explode(',', $articulo['topicos']);

    // Revisores ya asignados
    $stmt = $conn->prepare("SELECT id_usuario FROM formulario WHERE id_articulo = ?");
    $stmt->execute([$id_articulo]);
    $ya_asignados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_usuario');

    // Autores del artículo
    $stmt = $conn->prepare("SELECT id_usuario FROM escribiendo WHERE id_articulo = ?");
    $stmt->execute([$id_articulo]);
    $autores = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_usuario');

    $total_actuales = count($ya_asignados);
    if ($total_actuales >= 3) continue;

    foreach ($revisores as &$rev) {
        $id_rev = $rev['id_usuario'];

        if (in_array($id_rev, $ya_asignados) || in_array($id_rev, $autores)) continue;

        $especialidades = explode(',', $rev['especialidades']);
        $coinciden = array_intersect($topicos_articulo, $especialidades);

        if (!empty($coinciden)) {
            $conn->prepare("INSERT INTO formulario (id_usuario, id_articulo, manual) VALUES (?, ?, 0)")
                 ->execute([$id_rev, $id_articulo]);
            $rev['carga']++;
            $total_actuales++;
        }

        if ($total_actuales >= 3) break;
    }
    unset($rev);

    // Reordenar revisores según nueva carga
    usort($revisores, function($a, $b) {
        return $a['carga'] <=> $b['carga'];
    });
}

header("Location: ../vistas/jefe/gestion_asignaciones.php?view=articulos");
exit;
