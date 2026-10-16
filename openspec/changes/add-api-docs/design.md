# Design

## Context

- La API solo tiene `GET /api/health`, y todas las rutas `/api/*` responden errores en JSON (spec `api-error-responses`).
- No hay `resources/views/`: se quitó en el change anterior (D18), porque la API no tiene frontend.
- La auth del producto (Sanctum, con el token atado al tenant) todavía no existe. Por el BFF del ADR 0001 del front, el browser nunca tiene un token de la API.
- Scramble v0.13.48 (verificado en su código fuente) soporta Laravel 13 (`illuminate/contracts ^13.0`) y ofrece:
  - `Scramble::ignoreDefaultRoutes()`, `Scramble::registerUiRoute($path)` y `Scramble::registerJsonSpecificationRoute($path)`;
  - renderers configurables: `renderer` elige uno de `renderers.*`, cada uno con su `view`, y la vista recibe `config` y `result`;
  - `config('scramble.middleware')`, que por defecto es `['web', RestrictedDocsAccess::class]`. Ese middleware deja pasar en `local` y en el resto responde 403 según el gate `viewApiDocs`;
  - `php artisan scramble:export --path=<archivo>`.
- Scramble no trae Swagger UI: sus renderers oficiales son Stoplight Elements y Scalar.

## Goals / Non-Goals

**Goals:**
- Swagger UI en `/api/docs` y el contrato en `/api/docs/openapi.json`, sin escribir anotaciones.
- Una política de acceso por entorno simple, testeable y que falle cerrada.
- Que el contrato sea un archivo revisable en cada PR, del que el front pueda generar sus tipos.

**Non-Goals:**
- Documentar la auth: el security scheme Bearer entra con el change de Sanctum.
- Exponer la documentación en producción.
- Personalizar el aspecto de Swagger UI más allá de lo mínimo.

## Decisions

### D1. `dedoc/scramble` ^0.13 en `require`
- Va en `require` y no en `require-dev`, porque staging sirve la documentación y un deploy puede instalar sin las dependencias de desarrollo.
- Es un paquete 0.x, así que puede romper entre minors. La versión exacta queda fija en `composer.lock`, y cada actualización pasa por un PR con la CI.
- **Alternativas:**
  - `l5-swagger`: Swagger UI nativo, pero con anotaciones a mano que se desincronizan. Contradice el §6 de la integración;
  - `waad/scramble-swagger`: declara Scramble ^0.11/^0.12 y no está verificado con 0.13 ni con Laravel 13.

### D2. Swagger UI como renderer propio de Scramble
- En `config/scramble.php`, `renderer => 'swagger'` y `renderers.swagger.view => 'api-docs.swagger'`.
- La vista `resources/views/api-docs/swagger.blade.php` carga Swagger UI y le pasa la URL de `/api/docs/openapi.json`.
- Se usa el mecanismo de renderers de Scramble, así que no hay un controller propio para la UI, y el día que se quiera volver a Elements o Scalar es un cambio de configuración.
- Opciones de Swagger UI:
  - `tryItOutEnabled` activado, que solo se usa en local y staging;
  - `persistAuthorization` desactivado;
  - `deepLinking` activado;
  - `validatorUrl: null`. Por defecto Swagger UI muestra un badge que manda la URL del contrato a `validator.swagger.io`; desde staging, eso filtraría la URL a un tercero.
- **Alternativa:** una ruta y un controller propios que rendericen la vista. Hacen lo mismo, con más código y fuera del flujo de Scramble.

### D3. Rutas en `routes/api.php`, con exclusión del contrato
- En `AppServiceProvider::register()` se llama a `Scramble::ignoreDefaultRoutes()`, así no existe `/docs/api`.
- En `routes/api.php` se registran `Scramble::registerUiRoute('docs')` y `Scramble::registerJsonSpecificationRoute('docs/openapi.json')`, **solo en `local`, `staging` y `testing`**. Heredan el prefijo `/api` y el grupo `api`, que no maneja sesiones.
  - En producción las rutas no existen, así que `/api/docs` es indistinguible de una ruta inexistente: el mismo 404, el mismo cuerpo, sin headers del throttle, y con `OPTIONS`/`POST` responde igual que cualquier ruta que no existe.
  - Hallazgo de la revisión de seguridad: con las rutas registradas y el middleware respondiendo 404, se podía detectar que la documentación existía (`message` vacío, `X-RateLimit-*`, `OPTIONS` 200 con `Allow`, `POST` 405). El middleware se queda como segunda capa.
  - `testing` se incluye para que los tests puedan ejercitar las rutas reales, y el middleware igual responde 404 en `testing`. Un test arranca la app como `production` y verifica que las rutas no estén registradas y que la respuesta sea idéntica a la de una ruta inexistente.
- Con `scramble.api_path = ['include' => 'api', 'exclude' => ['api/docs']]`, la documentación no aparece en `paths`.
- `scramble.middleware = [ApiDocsAccess::class, 'throttle:30,1']`: no usa el grupo `web` (sin sesiones, cookies ni CSRF, que la API no usa) ni `RestrictedDocsAccess`.

### D4. Middleware `ApiDocsAccess`: política por entorno, que falla cerrada
- **`local`:** deja pasar.
- **`staging`:**
  - si falta `API_DOCS_USERNAME` o `API_DOCS_PASSWORD` → 404;
  - si no viene `Authorization: Basic` o no coincide → 401 con `WWW-Authenticate: Basic realm="API Sportiva docs"` y un cuerpo JSON `{ "message": "Unauthenticated." }`;
  - si coincide → deja pasar. La comparación usa `hash_equals` sobre los **SHA-256** del usuario y la contraseña: `hash_equals` corta antes si los largos difieren, y comparar hashes de largo fijo evita filtrar el largo de las credenciales. Además se comparan los dos valores siempre, sin cortocircuito.
- **Cualquier otro entorno** (`production`, `testing`, valores desconocidos) → 404, el mismo que da una ruta inexistente. No revela que la documentación existe.
- Las credenciales se leen de `config('api-docs.*')`, en un `config/api-docs.php` nuevo, y nunca con `env()` directo, porque eso se rompe con `config:cache`.
- Se agregan `X-Robots-Tag: noindex`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: no-referrer` a toda respuesta que deja pasar.
- En el HTML se agrega una **CSP estricta**:
  - `default-src 'none'`;
  - scripts solo desde `https://cdn.jsdelivr.net/npm/swagger-ui-dist@<versión>/` y el script inline con un nonce por request;
  - `connect-src 'self'`, `frame-ancestors 'none'` y `base-uri 'none'`;
  - `style-src` permite `'unsafe-inline'`, porque Swagger UI aplica estilos desde JS.

  SRI bloquea un bundle alterado, y la CSP limita lo que podría hacer un bundle vulnerable.
- `throttle:30,1` limita los intentos de fuerza bruta contra el Basic de staging. Laravel ordena los middlewares por prioridad y el throttle corre antes que `ApiDocsAccess`, así que también cuenta los 401. Hay un test que verifica el 429 en el intento 31.
- **Por qué HTTP Basic y no la auth de la app:** el browser no tiene token (BFF), y todavía no existe un rol de administrador de plataforma. Basic es simple, estándar y no agrega superficie de sesión. En staging va sobre HTTPS, que Laravel Cloud provee.
- **Alternativa:** el gate `viewApiDocs` de Scramble. Requiere un usuario autenticado por sesión, que la API no tiene.

### D5. Swagger UI por CDN, con versión fija y SRI
- Se cargan `swagger-ui.css` y `swagger-ui-bundle.js` desde jsDelivr, como `swagger-ui-dist@<versión exacta>`, con `integrity="sha384-..."` y `crossorigin="anonymous"`.
- **La versión:** la última publicada hace al menos 14 días al aplicar el change. Hoy la más nueva (5.33.3) salió el 2026-10-15, así que se usa una anterior; el margen evita adoptar una release recién publicada. Los hashes SRI se calculan sobre los archivos descargados.
- La versión y los hashes van en `config('scramble.renderers.swagger')`, en un solo lugar, y el procedimiento de actualización queda en `docs/decisions.md`.
- **Alternativa:** copiar `swagger-ui-dist` dentro de `public/`. Funciona sin CDN, pero suma más o menos 1,5 MB al repo y se actualiza a mano. Se descarta, porque la documentación solo se usa en local y staging, con internet.

### D6. El contrato se versiona en `docs/api/openapi.json`, y la CI controla que esté al día
- `scramble.export_path = 'docs/api/openapi.json'`. Se regenera con `docker compose exec backend php artisan scramble:export`.
- En la CI, después de los tests:
  1. `php artisan scramble:export`;
  2. `git diff --exit-code docs/api/openapi.json`. Si falla, imprime el comando para regenerarlo.
- **Por qué versionarlo:** cada PR muestra cómo cambia el contrato, y el front puede generar sus tipos desde un archivo con historia. No se publica como artefacto (spec `ci-verification`).
- **Que el export sea determinista y no dependa de la máquina:**
  - `servers` se fija como URL relativa (`/api`), con un document transformer en `AppServiceProvider`. Por defecto Scramble usa `url('api')`, que depende de `APP_URL`.
  - El título se fija en `ui.title` (`API Sportiva`). Por defecto sale de `APP_NAME`.

  Así, un dev con otro `APP_URL` o `APP_PORT` exporta exactamente el mismo archivo. Además, "Try it out" siempre llama al mismo origen que la página, en cualquier puerto. La versión sale de `API_VERSION` (default `0.0.1`) y queda para cuando haya releases.
  - **Verificado:** dos exports seguidos y un export con otro `APP_URL` y otro `APP_NAME` dan archivos idénticos.
- **Caché de Scramble:** el export solo usa un contrato cacheado si alguien corrió `scramble:cache`. Esto se documenta: no usar ese comando en local.
- **`dev_tools` desactivado:** con `APP_DEBUG` registraría la ruta `_scramble/dev-tools/devtools.js` fuera de `/api`, y el renderer de Swagger no la usa.

### D7. Tests
- `ApiDocsAccessTest` cubre la matriz de la spec: local; staging sin header, con credenciales incorrectas, con las correctas y sin credenciales configuradas; producción. El entorno se cambia con `$this->app['env']` y las credenciales con `config()`.
- `ApiDocsContractTest`:
  - el JSON es OpenAPI 3.1;
  - incluye `/health`;
  - no incluye ninguna ruta `/docs*`;
  - la UI referencia la versión fija, tiene `integrity` y apunta a `/api/docs/openapi.json`.
- El guard de `TestCase` (base `sportiva_testing`) sigue vigente.

## Risks / Trade-offs

- **Scramble es 0.x** y puede tener cambios incompatibles → `composer.lock` fija la versión, y cualquier actualización es un PR con la CI y el diff del contrato a la vista.
- **"Try it out" y el origen.** Si `servers` apuntara a otro host o puerto, los requests serían cross-origin y fallarían, porque la API no tiene CORS → `servers` es relativo (`/api`, ver D6) y además `APP_URL` pasa a `http://127.0.0.1:8000`. Verificado con un clic real en Chrome headless: 200 y sin errores en la consola.
- **Fricción por el chequeo del contrato.** Un dev cambia un endpoint y la CI falla → el mensaje dice el comando exacto, y el comando queda en `AGENTS.md`.
- **CDN caído o hash SRI que no coincide.** La UI no carga → solo afecta a local y staging. El contrato JSON sigue disponible.
- **"Try it out" en staging** ejecuta requests reales contra los datos de staging → staging no tiene datos de producción, y el acceso requiere las credenciales Basic.
- **`APP_ENV=local` en un entorno desplegado** abriría la documentación sin credenciales → se documenta que ningún entorno desplegado usa `local`, y se revisa al configurar Laravel Cloud.
- **La documentación es un mapa de la API.** Por eso está desactivada en producción (404) y en staging va con credenciales. Igual, la autorización de cada endpoint es la defensa real.

## Migration Plan

- No hay migraciones de base.
- Para staging, cuando exista: cargar `API_DOCS_USERNAME` y `API_DOCS_PASSWORD` en el entorno de Laravel Cloud. Sin esas variables, la documentación devuelve 404, porque falla cerrada.
- **Rollback:** revertir el PR. No hay datos involucrados.

## Open Questions

- Cómo lee la vista las claves propias del renderer: `$config->renderer()` o `config('scramble.renderers.swagger')`. Se resuelve al implementar y no cambia el diseño.
