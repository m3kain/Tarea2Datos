<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    
        // Validaciones antes de insertar
        if (
            !isset($data['calidad_tecnica'], $data['originalidad'], $data['valoracion_global']) ||
            $data['calidad_tecnica'] < 1 || $data['calidad_tecnica'] > 10 ||
            $data['valoracion_global'] < 1 || $data['valoracion_global'] > 10
        ) {
            return false;
        }
    
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
    

    
}
