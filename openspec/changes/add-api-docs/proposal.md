# Proposal

## Why

El frontend necesita un contrato tipado de la API (doc de integración del front, §6), y el equipo necesita explorar los endpoints sin leer el código. Hoy no hay ninguna de las dos cosas. Conviene sumarlo ahora, antes de auth y de los módulos de negocio, para que cada endpoint nuevo quede documentado desde el primer día y sin trabajo extra.

## What Changes

- Se agrega `dedoc/scramble`, que genera el documento OpenAPI 3.1 a partir de las rutas, los Form Requests y los API Resources, sin anotaciones a mano.
- **`GET /api/docs`**: una página con **Swagger UI** (`swagger-ui-dist` con versión fija). Es la única UI de la API.
- **`GET /api/docs/openapi.json`**: el contrato en JSON.
- Visibilidad por entorno:
  - **local:** abierta, con "Try it out";
  - **staging:** protegida con HTTP Basic, con credenciales en variables de entorno. Si no hay credenciales configuradas, devuelve 404;
  - **producción y cualquier otro entorno:** 404.
- Las rutas de la documentación no aparecen dentro del propio contrato.
- El contrato se versiona en `docs/api/openapi.json`. La CI lo regenera y falla si el archivo commiteado quedó desactualizado.
- Documentación: cómo abrir la documentación, cómo regenerar el contrato y cómo lo consume el front.

## Capabilities

### New Capabilities

- `api-docs`: documentación navegable (Swagger UI) y contrato OpenAPI de la API, con su política de acceso por entorno.

### Modified Capabilities

- `ci-verification`: se agrega un requisito, que la CI verifique que el contrato OpenAPI versionado está al día con el código.

## Impact

- **Dependencias:** `dedoc/scramble` ^0.13 (en `require`, no en `require-dev`, porque staging también la necesita) y `swagger-ui-dist`, cargado por CDN con versión fija e integridad SRI. No hay npm.
- **Código:**
  - `config/scramble.php`;
  - un renderer y una vista propios (`resources/views/api-docs/swagger.blade.php`; esto recrea `resources/views/`);
  - el registro de las rutas;
  - un middleware de acceso;
  - tests.
- **CI:** un paso nuevo, el export y la comparación del contrato. No publica nada.
- **Configuración:** `API_DOCS_USERNAME` y `API_DOCS_PASSWORD`, solo para staging, que se cargan en Laravel Cloud. En `.env.example` van vacías.
- **Frontend:** puede generar sus tipos con `openapi-typescript` desde `docs/api/openapi.json`. La integración del lado del front es un change de ese repo.
- **Fuera de alcance:**
  - la documentación de auth (todavía no existe): el security scheme Bearer se agrega con el change de Sanctum;
  - publicar la documentación en producción;
  - los cambios en el repo del front.
