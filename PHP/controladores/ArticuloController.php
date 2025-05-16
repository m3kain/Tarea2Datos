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

        // Validar nombres de autores no repetidos
        $nombres = [];
        foreach ($autores as $autor) {
            $nombre = strtolower(trim($autor['nombre']));
            if (in_array($nombre, $nombres)) {
                return "No se permiten nombres de autores duplicados.";
            }
            $nombres[] = $nombre;
        }

    
        foreach ($autores as $autor) {
            $email = trim($autor['email']);
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM articulo a
                JOIN escribiendo e ON e.id_articulo = a.id_articulo
                JOIN usuarios u ON u.id_usuario = e.id_usuario
                WHERE a.titulo = ? AND u.email = ?
            ");
            $stmt->execute([$titulo, $email]);
            if ($stmt->fetchColumn() > 0) {
                return "El autor {$autor['nombre']} ya tiene un artículo con ese título.";
            }
        }
    
        // Insertar artículo
        $stmtArticulo = $conn->prepare("
            INSERT INTO articulo (titulo, resumen, fecha_envio, fecha_limite_modificacion, aceptacion)
            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL)
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
    
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmtCheck->execute([$email]);
            $usuario = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
            if (!$usuario) {
                $password = '1234';
                $stmtNew = $conn->prepare("INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, ?, 2)");
                $stmtNew->execute([$nombre, $email, $password]);
                $idAutor = $conn->lastInsertId();
            } else {
                $idAutor = $usuario['id_usuario'];
            
                $stmtRol = $conn->prepare("SELECT subclase FROM usuarios WHERE id_usuario = ?");
                $stmtRol->execute([$idAutor]);
                $subclase = $stmtRol->fetchColumn();
            
                if ((int)$subclase === 3) {
                    $stmtUpdate = $conn->prepare("UPDATE usuarios SET subclase = 4 WHERE id_usuario = ?");
                    $stmtUpdate->execute([$idAutor]);
                }
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
    
        // Asignar revisor especializado automáticamente
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