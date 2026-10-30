-- database/seed.sql
-- Datos FICTICIOS para la demo de 2Tattoo.
-- ⚠️ BORRA todos los datos actuales y los reemplaza. Ejecutar solo sobre la BD de demo.
-- Requiere haber importado antes database/database.sql.
-- Las fechas son relativas al día de ejecución (CURDATE), así la demo nunca caduca.

USE tfg_tattoo;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE citas;
TRUNCATE TABLE disponibilidad;
TRUNCATE TABLE tatuajes;
TRUNCATE TABLE estilos;
TRUNCATE TABLE usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- Usuarios (contraseñas guardadas con password_hash / bcrypt)
-- Cliente demo: cliente@demo.test / Demo2026!
-- Admin demo:   admin@demo.test   / (contraseña privada, no publicada)
INSERT INTO usuarios (id_usuario, nombre, email, password, rol) VALUES
(1, 'Admin Demo',   'admin@demo.test',   '$2y$10$2rPpt7lbxLIRA1MyU78XZOZKbGUb6mEUTyvh5VlCaKlBj5b0YwgTG', 'admin'),
(2, 'Cliente Demo', 'cliente@demo.test', '$2y$10$rX8lFyuJicCUJhmuoqPFS.ZM/g5s.0BvQDURZAbuk6pE.0o/.6WbK', 'cliente'),
(3, 'Lucía Martín', 'lucia@demo.test',   '$2y$10$rX8lFyuJicCUJhmuoqPFS.ZM/g5s.0BvQDURZAbuk6pE.0o/.6WbK', 'cliente'),
(4, 'Javier Ruiz',  'javier@demo.test',  '$2y$10$rX8lFyuJicCUJhmuoqPFS.ZM/g5s.0BvQDURZAbuk6pE.0o/.6WbK', 'cliente');

INSERT INTO estilos (id_estilo, nombre_estilo) VALUES
(1, 'Blackwork'),
(2, 'Fine line'),
(3, 'Tradicional'),
(4, 'Realismo'),
(5, 'Lettering');

INSERT INTO tatuajes (titulo, descripcion, imagen, id_estilo) VALUES
('Composición floral', 'Pieza de antebrazo con línea fina y sombreado suave.', 'tattoo_75e5d1491882ce5cd41083db3a58645f.jpg', 2),
('Pieza en negro sólido', 'Diseño geométrico con relleno de negro sólido.', 'tattoo_e718a34a59e3b4db1a4e035b5ad15ace.jpg', 1),
('Golondrina clásica', 'Tradicional americano con colores planos y contorno grueso.', NULL, 3),
('Retrato en grises', 'Realismo en escala de grises para brazo.', NULL, 4),
('Frase en caligrafía', 'Lettering a mano alzada para costillas.', NULL, 5);

-- Franjas: próximas dos semanas
INSERT INTO disponibilidad (id_disponibilidad, fecha, hora_inicio, hora_fin, disponible) VALUES
(1, CURDATE() + INTERVAL 2 DAY,  '10:00', '12:00', 0),
(2, CURDATE() + INTERVAL 2 DAY,  '16:00', '18:00', 0),
(3, CURDATE() + INTERVAL 3 DAY,  '10:00', '13:00', 1),
(4, CURDATE() + INTERVAL 5 DAY,  '11:00', '13:00', 1),
(5, CURDATE() + INTERVAL 5 DAY,  '17:00', '19:00', 1),
(6, CURDATE() + INTERVAL 7 DAY,  '10:00', '12:00', 1),
(7, CURDATE() + INTERVAL 9 DAY,  '16:00', '19:00', 1),
(8, CURDATE() + INTERVAL 12 DAY, '10:00', '12:00', 1);

-- Citas: una de cada estado (las activas tienen su franja bloqueada)
INSERT INTO citas (fecha, hora, descripcion, estado, id_usuario, id_disponibilidad) VALUES
(CURDATE() + INTERVAL 2 DAY, '10:00', 'Rosa fine line en el antebrazo, unos 8 cm.', 'pendiente', 2, 1),
(CURDATE() + INTERVAL 2 DAY, '16:00', 'Golondrina tradicional en el hombro.', 'aceptada', 3, 2),
(CURDATE() + INTERVAL 3 DAY, '10:00', 'Frase en lettering en las costillas.', 'rechazada', 4, NULL);
