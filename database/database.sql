CREATE DATABASE IF NOT EXISTS tfg_tattoo
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE tfg_tattoo;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE estilos (
    id_estilo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estilo VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE tatuajes (
    id_tatuaje INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    id_estilo INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_estilo) REFERENCES estilos(id_estilo)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE disponibilidad (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    disponible BOOLEAN DEFAULT TRUE
);

CREATE TABLE citas (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'cancelada') 
        NOT NULL DEFAULT 'pendiente',
    id_usuario INT NOT NULL,
    id_disponibilidad INT,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_disponibilidad) REFERENCES disponibilidad(id_disponibilidad)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);