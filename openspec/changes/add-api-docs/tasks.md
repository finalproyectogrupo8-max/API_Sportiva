# Tasks

## 1. Dependencias y configuración

- [x] 1.1 Instalar `dedoc/scramble` ^0.13 en `require` (D1) dentro del contenedor, y publicar `config/scramble.php`. Verificación: `composer.json` lo lista en `require`, `composer validate` pasa y `php artisan route:list` no muestra `/docs/api`, una vez aplicado `ignoreDefaultRoutes`.
- [x] 1.2 Elegir la versión de `swagger-ui-dist` (la última publicada hace al menos 14 días) y calcular los hashes SRI `sha384` de `swagger-ui.css` y `swagger-ui-bundle.js` a partir de los archivos descargados de jsDelivr. Registrar la versión, los hashes y el procedimiento de actualización en `docs/decisions.md`. Verificación: recalcular el hash del archivo servido por el CDN da el mismo valor.
- [x] 1.3 Crear `config/api-docs.php` (`username` y `password`, desde `API_DOCS_USERNAME` y `API_DOCS_PASSWORD`), agregar las dos variables vacías a `.env.example` y cambiar `APP_URL` a `http://127.0.0.1:8000` (D4, riesgo de "Try it out"). Verificación: `php artisan config:show api-docs` muestra las claves, y `.env.example` no tiene valores reales.

## 2. Acceso por entorno

- [x] 2.1 Implementar el middleware `ApiDocsAccess` (D4): local abierto; staging con Basic y `hash_equals`, 401 con `WWW-Authenticate` y 404 si no hay credenciales configuradas; 404 en el resto; headers `X-Robots-Tag` y `Cache-Control`. Agregar `tests/Feature/ApiDocsAccessTest.php` con todos los escenarios de acceso de la spec `api-docs`. Verificación: `php artisan test --filter=ApiDocsAccessTest` en verde.

## 3. Rutas, contrato y Swagger UI

- [x] 3.1 Registrar las rutas (D3): `ignoreDefaultRoutes` en `AppServiceProvider`, `registerUiRoute('docs')` y `registerJsonSpecificationRoute('docs/openapi.json')` en `routes/api.php`, `api_path` con la exclusión de `api/docs` y `scramble.middleware` con `ApiDocsAccess` y `throttle:30,1`. Verificación: `route:list` muestra `api/docs` y `api/docs/openapi.json`, y no muestra `docs/api`.
- [x] 3.2 Crear el renderer `swagger` y la vista `resources/views/api-docs/swagger.blade.php` (D2, D5), con la versión fija, SRI y "Try it out". Resolver la pregunta abierta sobre cómo la vista lee la configuración del renderer. Verificación: `http://127.0.0.1:8000/api/docs` muestra Swagger UI desde el host, y "Try it out" sobre `GET /health` devuelve 200 sin errores de CORS en la consola del browser.
- [x] 3.3 Agregar `tests/Feature/ApiDocsContractTest.php`: OpenAPI 3.1, incluye `/health`, no incluye rutas `/docs*`, y la UI referencia la versión fija, `integrity` y la URL del JSON. Verificación: `php artisan test` completo en verde y Pint pasa.

## 4. Contrato versionado y CI

- [x] 4.1 Configurar `export_path = docs/api/openapi.json`, generar el archivo y confirmar que el export es determinista. Verificación: dos exports seguidos dan el mismo archivo (`git diff --exit-code` sin cambios).
- [x] 4.2 Agregar a `.github/workflows/ci.yml` el paso que regenera el contrato y lo compara con `git diff --exit-code`. Si falla, el paso tiene que imprimir el comando para regenerarlo (spec `ci-verification`). Verificación: actionlint pasa. Prueba negativa local: cambiar una ruta sin regenerar el contrato hace fallar el diff. Se reporta como "no verificado en GitHub" hasta que corra en el PR.

## 5. Documentación

- [x] 5.1 Actualizar `docs/desarrollo-local.md` (cómo abrir `/api/docs`, cómo regenerar el contrato y la política de acceso por entorno), `AGENTS.md` (el comando de export y la regla de regenerar el contrato al cambiar endpoints), `README.md` (el link a la documentación) y `docs/arquitectura-multi-tenant.md` si corresponde. Verificación: cada comando nuevo de la documentación se ejecutó tal como está escrito.

## 6. Verificación integral y revisión

- [x] 6.1 Correr `openspec validate add-api-docs --strict`. Verificación: sin errores.
- [x] 6.2 Revisión independiente de seguridad del middleware de acceso, la vista (SRI, XSS por datos del contrato) y la configuración. Verificación: los hallazgos quedan resueltos o registrados como riesgos.
- [x] 6.3 Reporte final con cada escenario de la spec `api-docs` marcado como PASS, FAIL, BLOCKED o NOT RUN.

## Workflow follow-up

- Commit en `feat/api-docs` con el usuario de GitHub del dueño, push y PR, cada uno con aprobación explícita.
- Ver la CI en verde en el PR, incluido el chequeo del contrato.
- Archivar con `/opsx:archive` después del merge.
- Repo Front-Sportiva (change aparte): generar los tipos con `openapi-typescript` desde `docs/api/openapi.json`.
- Pendientes que dejó la revisión de seguridad, para changes futuros:
  - **Deploy:** correr `scramble:cache` en staging para no generar el contrato en cada request, y revisar que ningún entorno de Laravel Cloud use `APP_ENV=local`.
  - **Auth con Sanctum:** el browser puede reenviar las credenciales Basic cacheadas a otras URLs `/api/*`. Tenerlo en cuenta al agregar Bearer.
  - **Hardening de la CI:** fijar las actions por SHA en lugar de por tag.
  - **Al sumar endpoints:** revisar que las descripciones y los ejemplos del contrato no incluyan datos personales reales.
