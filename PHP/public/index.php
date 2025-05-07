<?php
require_once(__DIR__ . '/../controladores/UsuarioController.php');

// Ejemplo: mostrar perfil del usuario con ID 1
$controller = new UsuarioController();
$controller->mostrarPerfil(1);
?>
