<?php
require_once(__DIR__ . '/../modelos/Usuario.php');

class UsuarioController {
    public function mostrarPerfil($id_usuario) {
        $usuario = Usuario::obtenerPorID($id_usuario);
        include(__DIR__ . '/../vistas/perfil.php');
    }
}
?>
