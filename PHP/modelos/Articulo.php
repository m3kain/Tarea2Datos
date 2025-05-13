<?php
require_once(__DIR__ . '/../conexion.php');

class Articulo {
    public static function filtrar($filtros) {
        global $conn;

        $query = "SELECT a.*, GROUP_CONCAT(DISTINCT ar.titulo_area) AS topicos
                FROM articulo a
                LEFT JOIN escribiendo e ON a.id_articulo = e.id_articulo
                LEFT JOIN usuarios u_autor ON e.id_usuario = u_autor.id_usuario
                LEFT JOIN formulario r ON a.id_articulo = r.id_articulo
                LEFT JOIN usuarios u_revisor ON r.id_usuario = u_revisor.id_usuario
                LEFT JOIN topicos t ON a.id_articulo = t.id_articulo
                LEFT JOIN area ar ON t.id_area = ar.id_area
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['titulo'])) {
            $query .= " AND a.titulo LIKE ?";
            $params[] = "%{$filtros['titulo']}%";
        }

        if (!empty($filtros['resumen'])) {
            $query .= " AND a.resumen LIKE ?";
            $params[] = "%{$filtros['resumen']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND a.fecha_envio >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND a.fecha_envio <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        if ($filtros['aceptacion'] !== '') {
            $query .= " AND a.aceptacion = ?";
            $params[] = $filtros['aceptacion'];
        }

        if (!empty($filtros['autor'])) {
            $query .= " AND u_autor.nombre LIKE ?";
            $params[] = "%{$filtros['autor']}%";
        }

        if (!empty($filtros['revisor'])) {
            $query .= " AND u_revisor.nombre LIKE ?";
            $params[] = "%{$filtros['revisor']}%";
        }
        if (!empty($filtros['topico'])) {
            $query .= " AND ar.titulo_area LIKE ?";
            $params[] = "%{$filtros['topico']}%";
        }

        $query .= " GROUP BY a.id_articulo ORDER BY a.fecha_envio DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerAreas() {
        global $conn;
        $stmt = $conn->query("SELECT id_area, titulo_area FROM area");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crearArticuloConAutores($titulo, $resumen, $topicos, $autores) {
        require(__DIR__ . '/../conexion.php');
        $conn->beginTransaction();

        $fechaEnvio = date('Y-m-d');
        $fechaLimite = date('Y-m-d', strtotime('+60 days'));

        $stmt = $conn->prepare("INSERT INTO articulo (titulo, resumen, fecha_envio, fecha_limite_modificacion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titulo, $resumen, $fechaEnvio, $fechaLimite]);

        $idArticulo = $conn->lastInsertId();

        $stmtTopico = $conn->prepare("INSERT INTO topicos (id_area, id_articulo) VALUES (?, ?)");
        foreach ($topicos as $idArea) {
            $stmtTopico->execute([$idArea, $idArticulo]);
        }

        $stmtAutor = $conn->prepare("INSERT INTO escribiendo (id_usuario, id_articulo, autor_contacto) VALUES (?, ?, ?)");
        foreach ($autores as $autor) {
            $nombre = trim($autor['nombre']);
            $email = trim($autor['email']);
            $contacto = isset($autor['contacto']) ? 1 : 0;

            $stmtBuscar = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmtBuscar->execute([$email]);
            $usuario = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $stmtInsert = $conn->prepare("INSERT INTO usuarios (nombre, email, password, subclase) VALUES (?, ?, 'default', 2)");
                $stmtInsert->execute([$nombre, $email]);
                $idUsuario = $conn->lastInsertId();
            } else {
                $idUsuario = $usuario['id_usuario'];
            }

            $stmtAutor->execute([$idUsuario, $idArticulo, $contacto]);
        }

        $conn->commit();
        return $idArticulo;
    }

    public static function mejoresCalificados() {
        global $conn;
        $sql = "SELECT a.id_articulo, a.titulo, a.resumen, 
                       GROUP_CONCAT(DISTINCT ar.titulo_area) AS topicos,
                       GROUP_CONCAT(DISTINCT u.nombre) AS autores,
                       AVG(f.calidad_tecnica) AS promedio_tecnica,
                       AVG(f.valoracion_global) AS promedio_valoracion,
                       MAX(f.originalidad) AS originalidad
                FROM articulo a
                JOIN formulario f ON a.id_articulo = f.id_articulo
                LEFT JOIN topicos t ON a.id_articulo = t.id_articulo
                LEFT JOIN area ar ON t.id_area = ar.id_area
                LEFT JOIN escribiendo e ON a.id_articulo = e.id_articulo
                LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                GROUP BY a.id_articulo
                HAVING originalidad = 1
                ORDER BY promedio_tecnica DESC, promedio_valoracion DESC
                LIMIT 15";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
