<?php
require_once(__DIR__ . '/../conexion.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id_usuario = $_POST['id_usuario'] ?? null;
$especialidades_nuevas = $_POST['especialidades'] ?? [];

if (!$id_usuario || !is_array($especialidades_nuevas)) {
    die("Datos incompletos.");
}

// Obtener las especialidades actuales del usuario
$stmt = $conn->prepare("SELECT id_area FROM especializacion WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$especialidades_actuales = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Calcular diferencias
$agregar = array_diff($especialidades_nuevas, $especialidades_actuales);
$eliminar = array_diff($especialidades_actuales, $especialidades_nuevas);

// Insertar nuevas
if (!empty($agregar)) {
    $stmtInsert = $conn->prepare("INSERT INTO especializacion (id_usuario, id_area) VALUES (?, ?)");
    foreach ($agregar as $id_area) {
        $stmtInsert->execute([$id_usuario, $id_area]);
    }
}

// Eliminar deseleccionadas
if (!empty($eliminar)) {
    $stmtDelete = $conn->prepare("DELETE FROM especializacion WHERE id_usuario = ? AND id_area = ?");
    foreach ($eliminar as $id_area) {
        $stmtDelete->execute([$id_usuario, $id_area]);
    }
}

header("Location: ../vistas/jefe/gestionar_revisores.php");
exit;
