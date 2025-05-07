<?php

require_once(__DIR__ . '/../modelos/Articulo.php');

class ArticuloController {
    public static function mostrarFormulario() {
        return Articulo::obtenerAreas();
    }
    public static function procesarEnvio($post) {
        require_once(__DIR__ . '/../conexion.php');
        global $conn;
    
        $titulo = trim($post['titulo'] ?? '');
        $resumen = trim($post['resumen'] ?? '');
        $topicos = $post['topicos'] ?? [];
        $autores = $post['autores'] ?? [];
    
        if (!is_array($topicos) || !is_array($autores) || count($topicos) === 0 || count($autores) === 0) {
            return "Faltan autores o tópicos.";
        }
    
        // Insertar artículo
        $stmtArticulo = $conn->prepare("
            INSERT INTO articulo (titulo, resumen, fecha_envio, fecha_limite_modificacion, aceptacion)
            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), NULL)
        ");
        $stmtArticulo->execute([$titulo, $resumen]);
    
        $idArticulo = $conn->lastInsertId();
    
        // Insertar tópicos
        $stmtTopico = $conn->prepare("INSERT INTO topicos (id_area, id_articulo) VALUES (?, ?)");
        foreach ($topicos as $idArea) {
            $stmtTopico->execute([$idArea, $idArticulo]);
        }
    
        // Insertar autores (crear si no existe)
        foreach ($autores as $autor) {
            $email = trim($autor['email']);
            $nombre = trim($autor['nombre']);
            $contacto = isset($autor['contacto']) ? 1 : 0;
    
            // Verificar existencia
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
            if (!$usuario) {
                $stmtNew = $conn->prepare("INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, '1234', 2)");
                $stmtNew->execute([$nombre, $email]);
                $idAutor = $conn->lastInsertId();
            } else {
                $idAutor = $usuario['id_usuario'];
            }
    
            $stmtEscrib = $conn->prepare("INSERT INTO escribiendo (id_usuario, id_articulo, autor_contacto) VALUES (?, ?, ?)");
            $stmtEscrib->execute([$idAutor, $idArticulo, $contacto]);
        }
    
        // Obtener IDs de autores
        $idsAutores = [];
        foreach ($autores as $autor) {
            $stmtId = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmtId->execute([trim($autor['email'])]);
            $result = $stmtId->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $idsAutores[] = $result['id_usuario'];
            }
        }
    
        // Buscar 1 revisor especializado que no sea autor
        $placeholdersAreas = implode(',', array_fill(0, count($topicos), '?'));
        $placeholdersAutores = implode(',', array_fill(0, count($idsAutores), '?'));
    
        $query = "
            SELECT DISTINCT u.id_usuario
            FROM usuarios u
            JOIN especializacion e ON u.id_usuario = e.id_usuario
            WHERE u.subclase IN (3,4)
            AND e.id_area IN ($placeholdersAreas)
            " . (count($idsAutores) ? "AND u.id_usuario NOT IN ($placeholdersAutores)" : "") . "
            ORDER BY RAND()
            LIMIT 1
        ";
    
        $stmtRevisor = $conn->prepare($query);
        $stmtRevisor->execute(array_merge($topicos, $idsAutores));
        $revisor = $stmtRevisor->fetch(PDO::FETCH_ASSOC);
    
        if ($revisor) {
            $stmtForm = $conn->prepare("
                INSERT INTO formulario (id_usuario, id_articulo, calidad_tecnica, originalidad, valoracion_global, argumentosvg, comentarios_autores)
                VALUES (?, ?, NULL, NULL, NULL, NULL, NULL)
            ");
            $stmtForm->execute([$revisor['id_usuario'], $idArticulo]);
        }
    
        return "Artículo enviado correctamente. CORREO ENVIADO";
    }
    
    
    
}

?>