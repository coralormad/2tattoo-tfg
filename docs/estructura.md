# Estructura del proyecto

## Carpetas principales

- /public
Contiene los archivos accesibles desde el navegador (index, portfolio, login, etc).

- /admin
Contiene el panel de administración para gestionar citas y tatuajes.

- /app
Contiene la lógica interna de la aplicación:
  - /config → conexión a base de datos
  - /includes → header y footer reutilizables
  - /controllers → lógica (futuro)
  - /models → acceso a datos (futuro)

- /database
Contiene el script SQL de la base de datos.

- /docs
Contiene la documentación del proyecto (no afecta al funcionamiento de la web).

## Decisión de arquitectura
Se ha separado el frontend (public) de la lógica interna (app) para mantener un código organizado y escalable.