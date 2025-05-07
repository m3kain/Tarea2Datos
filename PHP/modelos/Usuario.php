<?php
require_once(__DIR__ . '/../conexion.php');

class Usuario {
    public static function obtenerPorID($id_usuario) {
        global $conn;
        $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorEmail($email) {
        global $conn;
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function crear($nombre, $email, $password, $subclase) {
        global $conn;
        $sql = "INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$nombre, $email, $password, $subclase]);
    }
    
    
}
?>
