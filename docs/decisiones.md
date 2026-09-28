Fecha: Abril 2026

Tarea: Creación de la base de datos

Archivos:
- database/database.sql

Decisión:
Se ha diseñado una base de datos relacional con tablas para usuarios, tatuajes, estilos, citas y disponibilidad.

Estado: completado



Fecha: Abril 2026

Tarea: Inserción de datos de prueba

Archivos:
- phpMyAdmin (manual)

Decisión:
Se han insertado datos de prueba en todas las tablas para comprobar el funcionamiento del sistema.

Estado: completado



Fecha: Abril 2026

Tarea: Inserción de datos de prueba

Archivos:
- phpMyAdmin (manual)

Decisión:
Se han insertado datos de prueba en todas las tablas para comprobar el funcionamiento del sistema.

Estado: completado



Fecha: Abril 2026

Tarea: Comprobación de claves primarias y foráneas

Archivos:
- base de datos

Decisión:
Se ha verificado que las claves foráneas impiden la inserción de datos inválidos, asegurando la integridad referencial.

Estado: completado




Fecha: Abril 2026

Tarea: Conexión PHP con MySQL

Archivos:
- app/config/database.php

Decisión:
Se ha creado un usuario específico (tfg_user) para la conexión a la base de datos en lugar de usar root, mejorando la seguridad y evitando problemas de permisos.

Estado: completado




Fecha: Abril 2026

Tarea: Creación de estructura base de la web

Archivos:
- public/index.php
- app/includes/header.php
- app/includes/footer.php

Decisión:
Se ha implementado un sistema de includes para reutilizar header y footer en todas las páginas, mejorando la organización del código.

Estado: completado




Fecha: Abril 2026

Tarea: Creación del portfolio dinámico

Archivos:
- public/portfolio.php
- app/config/database.php
- app/includes/header.php
- app/includes/footer.php

Decisión:
Se ha creado una página de portfolio conectada a la base de datos. La consulta obtiene los tatuajes registrados y muestra su título, descripción, imagen y estilo asociado mediante una relación entre las tablas tatuajes y estilos.

Estado: completado




Fecha: Abril 2026

Tarea: Sistema de autenticación de usuarios

Archivos:
- public/registro.php
- public/login.php
- public/logout.php
- app/includes/auth.php
- app/config/database.php

Decisión:
Se ha implementado un sistema de registro e inicio de sesión utilizando sesiones de PHP. Las contraseñas se almacenan de forma segura mediante password_hash y se verifican con password_verify. También se ha creado un archivo auth.php para proteger páginas privadas y controlar el acceso según el rol del usuario.

Estado: completado



Fecha: Abril 2026

## Implementacion del sistema de roles y gestion de citas


**Archivos modificados:**
- public/registro.php
- public/login.php
- public/logout.php
- public/solicitar-cita.php
- app/controllers/registro_controller.php
- app/controllers/login_controller.php
- app/controllers/solicitar_cita_controller.php
- app/includes/auth.php
- app/config/database.php
- database/database.sql

**Decisión técnica:**
Se ha implementado un sistema de autenticación basado en sesiones de PHP, diferenciando dos roles principales: cliente y administrador.  
El rol cliente permite registrarse, iniciar sesión y solicitar citas a través de la plataforma.  
El rol administrador queda preparado para acceder al panel de gestión y administrar las solicitudes recibidas.

Las contraseñas se almacenan de forma segura mediante `password_hash()` y se validan durante el inicio de sesión con `password_verify()`. Además, se utiliza el archivo `auth.php` para proteger páginas privadas y controlar el acceso según el rol del usuario.

**Resultado:**
Se ha validado correctamente el flujo completo desde la creación de cuenta hasta la solicitud de una cita por parte de un cliente.

**Estado:** Completado

