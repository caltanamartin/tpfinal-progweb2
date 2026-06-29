create schema aldea_vikinga;
use aldea_vikinga;
create table aldea_vikinga.guerreros
(
    id        int auto_increment
        primary key,
    nombre    varchar(50)                           not null,
    apodo     varchar(50)                           null,
    clan      varchar(50)                           null,
    fuerza    int       default 0                   null,
    creado_en timestamp default current_timestamp() not null
);

create table aldea_vikinga.usuarios
(
    id             int auto_increment
        primary key,
    email          varchar(100)                          not null,
    nombre         varchar(100)                          not null,
    username       varchar(50)                           not null,
    password       varchar(255)                          not null,
    anio_nacimiento date                                  default null,
    sexo           enum('Masculino','Femenino','Prefiero no cargarlo') default 'Prefiero no cargarlo',
    pais           varchar(100)                           default '',
    ciudad         varchar(100)                           default '',
    foto_perfil       varchar(255)                           default null,
    verificado        tinyint(1)                             default 0,
    token_verificacion varchar(64)                            default null,
    creado_en         timestamp default current_timestamp() not null,
    unique (email),
    unique (username)
);

INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (23, 'Aslaug', 'La Reina', 'Volsung', 82, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (24, 'Harald', 'Cabellera Hermosa', 'Noruega', 94, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (26, 'Astrid', 'La Valiente (casi)', 'Hedeby', 87, '2026-04-28 21:52:34');

CREATE TABLE aldea_vikinga.categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL
);

CREATE TABLE aldea_vikinga.preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    pregunta TEXT NOT NULL,
    opcion_a VARCHAR(255) NOT NULL,
    opcion_b VARCHAR(255) NOT NULL,
    opcion_c VARCHAR(255) NOT NULL,
    opcion_d VARCHAR(255) NOT NULL,
    respuesta_correcta ENUM('A','B','C','D') NOT NULL,
    activa              TINYINT(1) DEFAULT 1,
    reportado           TINYINT(1) DEFAULT 0,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE aldea_vikinga.partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    puntaje INT DEFAULT 0,
    estado ENUM('jugando','terminada') DEFAULT 'jugando',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    terminada_en TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE aldea_vikinga.partidas_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partida_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    respuesta CHAR(1) NULL,
    es_correcta TINYINT(1) NULL,
    orden INT DEFAULT 0,
    respondida_en TIMESTAMP NULL,
    FOREIGN KEY (partida_id) REFERENCES partidas(id),
    FOREIGN KEY (pregunta_id) REFERENCES preguntas(id)
);

INSERT INTO aldea_vikinga.categorias (nombre, color) VALUES
('Historia', '#f74114'),
('Deportes', '#007ca6'),
('Cultura', '#00b582'),
('Ciencia', '#ffb042'),
('Geografía', '#8b5cf6');

INSERT INTO aldea_vikinga.preguntas (categoria_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta) VALUES
(1, '¿En qué año llegó Cristóbal Colón a América?', '1492', '1500', '1480', '1510', 'A'),
(1, '¿Quién fue el primer presidente de Argentina?', 'Domingo Faustino Sarmiento', 'Bernardino Rivadavia', 'Manuel Belgrano', 'Justo José de Urquiza', 'B'),
(1, '¿Qué imperio construyó Machu Picchu?', 'Azteca', 'Maya', 'Inca', 'Español', 'C'),
(1, '¿En qué año cayó el Muro de Berlín?', '1987', '1989', '1991', '1985', 'B'),
(1, '¿Quién fue el primer presidente de Estados Unidos?', 'Thomas Jefferson', 'John Adams', 'George Washington', 'Benjamin Franklin', 'C'),
(2, '¿En qué deporte se utiliza un volante?', 'Tenis', 'Bádminton', 'Squash', 'Pádel', 'B'),
(2, '¿Cada cuántos años se juega la Copa Mundial de la FIFA?', '2', '3', '4', '5', 'C'),
(2, '¿Quién ganó el Mundial de Fútbol de 1986?', 'Brasil', 'Alemania', 'Argentina', 'Italia', 'C'),
(2, '¿Cuántos puntos vale un triple en básquetbol?', '2', '3', '4', '1', 'B'),
(3, '¿Quién pintó "La última cena"?', 'Miguel Ángel', 'Rafael', 'Leonardo da Vinci', 'Donatello', 'C'),
(3, '¿Quién escribió "Cien años de soledad"?', 'Gabriel García Márquez', 'Julio Cortázar', 'Mario Vargas Llosa', 'Pablo Neruda', 'A'),
(3, '¿En qué año se fundó la primera universidad de Argentina?', '1613', '1821', '1913', '1536', 'A'),
(4, '¿Cuál es el planeta más grande del sistema solar?', 'Saturno', 'Júpiter', 'Neptuno', 'Urano', 'B'),
(4, '¿Cuál es la fórmula química del agua?', 'CO2', 'NaCl', 'H2O', 'O2', 'C'),
(4, '¿Cuántos huesos tiene el cuerpo humano adulto?', '186', '206', '226', '196', 'B'),
(4, '¿Qué órgano es responsable de bombear sangre?', 'Pulmón', 'Cerebro', 'Corazón', 'Hígado', 'C'),
(5, '¿Cuál es el río más largo del mundo?', 'Amazonas', 'Nilo', 'Misisipi', 'Yangtsé', 'B'),
(5, '¿Cuál es la capital de Australia?', 'Sídney', 'Melbourne', 'Canberra', 'Brisbane', 'C'),
(5, '¿En qué continente está el desierto del Sahara?', 'Asia', 'África', 'América', 'Oceanía', 'B');