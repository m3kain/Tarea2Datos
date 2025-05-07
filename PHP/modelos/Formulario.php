<?php
require_once(__DIR__ . '/../conexion.php');

class Formulario {
    public static function articulosAsignadosA($idRevisor) {
        global $conn;
        $sql = "SELECT f.*, a.titulo, a.resumen, a.fecha_limite_modificacion, a.aceptacion
                FROM formulario f
                JOIN articulo a ON a.id_articulo = f.id_articulo
                WHERE f.id_usuario = ?
                ORDER BY FIELD(a.aceptacion, NULL, 0, 1), a.id_articulo";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idRevisor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public static function actualizarEvaluacion($data) {
        global $conn;
        $sql = "UPDATE formulario SET calidad_tecnica = ?, originalidad = ?, valoracion_global = ?, argumentosvg = ?, comentarios_autores = ?
                WHERE id_usuario = ? AND id_articulo = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            $data['calidad_tecnica'],
            $data['originalidad'],
            $data['valoracion_global'],
            $data['argumentosvg'],
            $data['comentarios_autores'],
            $data['id_usuario'],
            $data['id_articulo']
        ]);
    }

    public static function contarYPromediarEvaluaciones($idArticulo) {
        global $conn;
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cantidad,
                   AVG(valoracion_global) as promedio,
                   MIN(calidad_tecnica) as tecnica_minima
            FROM formulario
            WHERE id_articulo = ?");
        $stmt->execute([$idArticulo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function aceptarSiCumple($idArticulo) {
        global $conn;
        $data = self::contarYPromediarEvaluaciones($idArticulo);
        if ($data['cantidad'] >= 2 && $data['promedio'] >= 5 && $data['tecnica_minima'] > 3) {
            $stmt = $conn->prepare("UPDATE articulo SET aceptacion = 1 WHERE id_articulo = ?");
            $stmt->execute([$idArticulo]);
        }
    }
    
}

?>

