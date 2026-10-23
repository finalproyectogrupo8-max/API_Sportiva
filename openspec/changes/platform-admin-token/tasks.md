# Tasks: Token Dedicado de Plataforma para el Panel de Administración (TIZ-07)

## 1. Configuración de Entorno y Sanctum

- [ ] 1.1 Agregar `SANCTUM_PLATFORM_TTL=480` en `.env.example`.
- [ ] 1.2 Agregar clave `'platform_ttl' => (int) env('SANCTUM_PLATFORM_TTL', 480)` en `config/sanctum.php`.

## 2. Emisión y Serialización de Tokens en Login

- [ ] 2.1 Actualizar `LoginResource` para recibir opcionalmente la instancia `platformToken` y exponer condicionalmente `platform_token` y `platform_expires_at` usando `$this->when(...)`.
- [ ] 2.2 Modificar `AuthController@login` para crear el token de plataforma con ability `['platform:admin']` y TTL correspondiente solo si `$user->is_platform_admin === true`.

## 3. Middleware y Rutas Administrativas

- [ ] 3.1 Actualizar el grupo `/api/admin/*` en `routes/api.php` con middleware `['auth:sanctum', 'abilities:platform:admin', 'platform.admin']`.
- [ ] 3.2 Verificar que todas las rutas de administración (`users`, `tenants`, `memberships`, `retry`) queden protegidas.

## 4. Tests y Documentación

- [ ] 4.1 Crear suite `tests/Feature/Auth/PlatformAdminTokenTest.php` cubriendo emisión condicional, rechazo en endpoints de club y de selección, expiración temporal, revocación y formato de error 403 forbidden.
- [ ] 4.2 Actualizar `docs/desarrollo-local.md` documentando la taxonomía de los 3 tokens, TTLs y variables de entorno.
- [ ] 4.3 Regenerar especificación OpenAPI con `php artisan scramble:export`.
- [ ] 4.4 Ejecutar suite completa de tests y validación de linter con Pint.

