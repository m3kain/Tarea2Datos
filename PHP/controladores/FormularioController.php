<?php

require_once(__DIR__ . '/../modelos/Formulario.php');

class FormularioController {
    public static function obtenerAsignados($idUsuario) {
        return Formulario::articulosAsignadosA($idUsuario);
    }

    public static function procesarEvaluacion($post) {
        return Formulario::actualizarEvaluacion($post);
    }
    
    public static function aceptarSiEvaluado($idArticulo) {
        Formulario::aceptarSiCumple($idArticulo);
    }
}    

?>