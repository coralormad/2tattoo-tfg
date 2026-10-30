# 2Tattoo

Aplicación web para estudios de tatuaje: portfolio público, solicitud de citas sobre franjas de disponibilidad y panel de administración. Proyecto final del ciclo DAW, recuperado y preparado para producción.

## Funcionalidades

**Cliente**
- Registro e inicio de sesión.
- Portfolio filtrable por estilo.
- Solicitud de cita sobre franjas disponibles.
- Consulta del estado de sus citas (pendiente, aceptada, rechazada, cancelada).

**Administración**
- Gestión de citas: aceptar o rechazar. Al rechazar, la franja se libera.
- CRUD de tatuajes con subida de imágenes.
- Gestión de franjas horarias con detección de solapes.

## Stack

- PHP 8 sin framework, PDO
- MySQL / MariaDB
- HTML, CSS y JavaScript vanilla
- Apache (`.htaccess`)

## Decisiones técnicas

- **Sin doble reserva**: la reserva se hace en una transacción con `SELECT … FOR UPDATE` sobre la franja.
- **Consultas preparadas** en todo el acceso a datos.
- **Contraseñas** con `password_hash` / `password_verify` y regeneración del id de sesión al iniciar sesión.
- **Subida de imágenes** validada por tipo MIME real (`finfo`), con nombre aleatorio y sin ejecución de scripts en la carpeta.
- **Credenciales fuera del código**: variables de entorno o `app/config/config.local.php` (no versionado).
- **Carpetas internas** (`app/`, `database/`, `docs/`) bloqueadas desde el navegador con `.htaccess`.

## Instalación local

Requisitos: PHP 8.1 o superior y MySQL/MariaDB (por ejemplo, XAMPP).

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/coralormad/2tattoo.git
   cd 2tattoo
   ```
2. Crear la base de datos y cargar los datos de demo:
   ```bash
   mysql -u root -p < database/database.sql
   mysql -u root -p < database/seed.sql
   ```
3. Configurar la conexión:
   ```bash
   cp app/config/config.example.php app/config/config.local.php
   ```
   y rellenar host, puerto, usuario y contraseña.
   Alternativa: variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
4. Arrancar:
   ```bash
   php -S localhost:8000
   ```
   Abrir http://localhost:8000.

Nota: el servidor integrado de PHP ignora `.htaccess`. En Apache, las protecciones se aplican solas.

## Cuenta de demo

- Cliente: `cliente@demo.test` / `Demo2026!`

El seed (`database/seed.sql`) **borra y reemplaza** todos los datos. Las fechas se generan relativas al día de ejecución.

## Estructura

```
admin/      Panel de administración
app/        Configuración, controladores e includes (no accesible por web)
database/   Esquema y datos de demo (no accesible por web)
docs/       Documentación del desarrollo (no accesible por web)
public/     Páginas públicas y assets
index.php   Redirige a public/index.php
```

## Próximos pasos

- Protección CSRF y endurecimiento de cookies de sesión.
- Cancelación de citas por el cliente y CRUD de estilos.
- Tests automatizados de la lógica de reservas.
