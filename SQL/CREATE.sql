-- Tabla de usuarios (autores, revisores, jefe comité, admin)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(100),
    subclase INT CHECK (subclase IN (1, 2, 3, 4))
);

-- Tabla de áreas temáticas
CREATE TABLE area (
    id_area INT AUTO_INCREMENT PRIMARY KEY,
    titulo_area TEXT NOT NULL
);

-- Tabla de artículos
CREATE TABLE articulo (
    id_articulo INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    resumen VARCHAR(150),
    fecha_envio DATE NOT NULL,
    fecha_limite_modificacion DATE NOT NULL,
    aceptacion BOOLEAN DEFAULT NULL -- NULL = en proceso, 0 = rechazado, 1 = aceptado
);

-- Tabla de tópicos del artículo
CREATE TABLE topicos (
    id_area INT,
    id_articulo INT,
    PRIMARY KEY (id_area, id_articulo),
    FOREIGN KEY (id_area) REFERENCES area(id_area) ON DELETE CASCADE,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE CASCADE
);

-- Especialización: relación entre revisor y áreas temáticas
CREATE TABLE especializacion (
    id_usuario INT,
    id_area INT,
    PRIMARY KEY (id_usuario, id_area),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_area) REFERENCES area(id_area) ON DELETE CASCADE
);

-- Relación autores-artículos
CREATE TABLE escribiendo (
    id_usuario INT,
    id_articulo INT,
    autor_contacto BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id_usuario, id_articulo),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE CASCADE
);

-- Evaluación de artículos por revisores
CREATE TABLE formulario (
    id_usuario INT,
    id_articulo INT,
    calidad_tecnica INT CHECK (calidad_tecnica BETWEEN 1 AND 10),
    originalidad BOOLEAN,
    valoracion_global INT CHECK (valoracion_global BETWEEN 1 AND 10),
    argumentosvg TEXT,
    comentarios_autores TEXT,
    manual TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id_usuario, id_articulo),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE CASCADE
);



DELIMITER $$

CREATE FUNCTION contar_evaluaciones_completas(id INT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;

    SELECT COUNT(*) INTO total
    FROM formulario
    WHERE id_articulo = id
      AND calidad_tecnica IS NOT NULL
      AND valoracion_global IS NOT NULL;

    RETURN total;
END$$

DELIMITER ;


DELIMITER $$

CREATE TRIGGER trigger_actualizar_aceptacion
AFTER UPDATE ON formulario
FOR EACH ROW
BEGIN
    DECLARE total INT;
    DECLARE aprobados INT;
    DECLARE rechazados INT;

    -- Contar evaluaciones completas
    SELECT COUNT(*) INTO total
    FROM formulario
    WHERE id_articulo = NEW.id_articulo
      AND calidad_tecnica IS NOT NULL
      AND valoracion_global IS NOT NULL;

    -- Promedios y conteo de originalidad
    SELECT COUNT(*) INTO aprobados
    FROM formulario
    WHERE id_articulo = NEW.id_articulo
      AND calidad_tecnica >= 5
      AND valoracion_global >= 5
      AND originalidad = 1;

    SELECT COUNT(*) INTO rechazados
    FROM formulario
    WHERE id_articulo = NEW.id_articulo
      AND (calidad_tecnica < 5 OR valoracion_global < 5);

    IF total = 3 THEN
        IF aprobados >= 1 AND rechazados = 0 THEN
            UPDATE articulo SET aceptacion = 1 WHERE id_articulo = NEW.id_articulo;
        ELSE
            UPDATE articulo SET aceptacion = 0 WHERE id_articulo = NEW.id_articulo;
        END IF;
    ELSE
        UPDATE articulo SET aceptacion = NULL WHERE id_articulo = NEW.id_articulo;
    END IF;
END$$

DELIMITER ;

CREATE OR REPLACE VIEW vista_estado_articulos AS
SELECT 
    a.id_articulo,
    a.titulo,
    a.aceptacion,
    COUNT(f.id_usuario) AS total_asignados,
    SUM(CASE WHEN f.calidad_tecnica IS NOT NULL AND f.valoracion_global IS NOT NULL THEN 1 ELSE 0 END) AS evaluaciones_completadas
FROM articulo a
LEFT JOIN formulario f ON a.id_articulo = f.id_articulo
GROUP BY a.id_articulo, a.titulo, a.aceptacion;


