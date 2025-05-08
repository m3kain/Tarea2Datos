<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../conexion.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_articulo = $_POST['id_articulo'] ?? null;
    $titulo = trim($_POST['titulo'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $topicos = $_POST['topicos'] ?? [];
    $autores = $_POST['autores'] ?? [];

    // Validar nombres duplicados
    $nombres = [];
    foreach ($autores as $autor) {
        $nombre = strtolower(trim($autor['nombre']));
        if (in_array($nombre, $nombres)) {
            echo "<script>alert('No se permiten autores con nombres repetidos.'); history.back();</script>";
            exit;
        }
        $nombres[] = $nombre;
    }

    // Validar que el título no se repita para los mismos autores (excepto el actual artículo)
    $stmtDuplicado = $conn->prepare("
    SELECT COUNT(*) 
    FROM articulo a
    JOIN escribiendo e ON a.id_articulo = e.id_articulo
    WHERE a.titulo = ?
    AND a.id_articulo != ?
    AND e.id_usuario IN (" . implode(',', array_fill(0, count($autores), '?')) . ")
    ");

    $params = array_merge([$titulo, $id_articulo], array_map(fn($a) => trim($a['email']), $autores));

    // Obtener los ID de los autores por sus emails
    $ids = [];
    foreach ($autores as $a) {
    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->execute([trim($a['email'])]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) $ids[] = $u['id_usuario'];
    }

    if ($ids) {
    $stmtDuplicado->execute(array_merge([$titulo, $id_articulo], $ids));
    if ($stmtDuplicado->fetchColumn() > 0) {
        echo "<script>alert('Ya existe un artículo con este título para uno de los autores.'); history.back();</script>";
        exit;
        }
    }

    if (!$id_articulo || empty($titulo) || empty($resumen) || !is_array($topicos) || count($topicos) === 0 || !is_array($autores) || count($autores) === 0) {
        die('Datos incompletos.');
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM escribiendo WHERE id_usuario = ? AND id_articulo = ?");
    $stmt->execute([$_SESSION['id_usuario'], $id_articulo]);
    if ($stmt->fetchColumn() == 0) {
        die('No autorizado.');
    }

    $stmt = $conn->prepare("UPDATE articulo SET titulo = ?, resumen = ? WHERE id_articulo = ?");
    $stmt->execute([$titulo, $resumen, $id_articulo]);

    $conn->prepare("DELETE FROM topicos WHERE id_articulo = ?")->execute([$id_articulo]);
    $stmtTopico = $conn->prepare("INSERT INTO topicos (id_area, id_articulo) VALUES (?, ?)");
    foreach ($topicos as $id_area) {
        $stmtTopico->execute([$id_area, $id_articulo]);
    }

    $conn->prepare("DELETE FROM escribiendo WHERE id_articulo = ?")->execute([$id_articulo]);
    $stmtAutor = $conn->prepare("INSERT INTO escribiendo (id_usuario, id_articulo, autor_contacto) VALUES (?, ?, ?)");

    $contactoCount = 0;
    $credenciales = [];
    foreach ($autores as $autor) {
        $email = trim($autor['email']);
        $nombre = trim($autor['nombre']);
        $contacto = isset($autor['contacto']) ? 1 : 0;
    
        if ($contacto) $contactoCount++;
    
        $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
        if (!$usuario) {
            $stmtNew = $conn->prepare("INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, '1234', 2)");
            $stmtNew->execute([$nombre, $email]);
            $idAutor = $conn->lastInsertId();
        
            $credenciales[] = "Correo enviado al nuevo autor creado.\nUsuario: $email\nContraseña: 1234";
        }else {
            $idAutor = $usuario['id_usuario'];
        }
    
        $stmtAutor->execute([$idAutor, $id_articulo, $contacto]);
    }
    
    if ($contactoCount !== 1) {
        die('Debe haber exactamente un autor de contacto.');
    }

    $mensajeCredenciales = urlencode(implode("\n\n", $credenciales));
    header("Location: ../vistas/perfil.php?actualizado=1&noti={$mensajeCredenciales}");
    exit;
} else {
    die('Método inválido.');
}
    