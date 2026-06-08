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
    foto_perfil    varchar(255)                           default null,
    creado_en      timestamp default current_timestamp() not null,
    unique (email),
    unique (username)
);

INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (23, 'Aslaug', 'La Reina', 'Volsung', 82, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (24, 'Harald', 'Cabellera Hermosa', 'Noruega', 94, '2026-04-28 21:52:34');
INSERT INTO aldea_vikinga.guerreros (id, nombre, apodo, clan, fuerza, creado_en) VALUES (26, 'Astrid', 'La Valiente (casi)', 'Hedeby', 87, '2026-04-28 21:52:34');