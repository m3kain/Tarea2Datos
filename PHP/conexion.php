<?php
$host = 'localhost';
$db = 'sistema_congreso';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $conn = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
