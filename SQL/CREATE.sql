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
    calidad_tecnica INT CHECK (calidad_tecnica BETWEEN 1 AND 7),
    originalidad BOOLEAN,
    valoracion_global INT CHECK (valoracion_global BETWEEN 1 AND 7),
    argumentosvg TEXT,
    comentarios_autores TEXT,
    PRIMARY KEY (id_usuario, id_articulo),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_articulo) REFERENCES articulo(id_articulo) ON DELETE CASCADE
);
