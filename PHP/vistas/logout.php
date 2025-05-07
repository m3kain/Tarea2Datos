<?php
session_start();          // Iniciar sesión (necesario para manipularla)
session_unset();          // Limpia todas las variables de sesión
session_destroy();        // Destruye la sesión actual

header("Location: login.php"); // Redirige al login
exit;
?>
