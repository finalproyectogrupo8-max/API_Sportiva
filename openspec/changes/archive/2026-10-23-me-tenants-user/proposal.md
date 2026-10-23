# Devolver el usuario en GET /api/me/tenants

## Why

El front necesita saber quién es el usuario (nombre e `is_platform_admin`) antes de que elija club: para saludarlo, para decidir si lo manda a `/admin` y para validar la sesión del admin, que usa el token de plataforma. `/api/me` solo acepta tokens de club, así que con el token pre-tenant o el de plataforma no había forma de obtener el usuario. Lo pide FRO-2 (`requireSession()` del front).

## What Changes

- `GET /api/me/tenants` devuelve `user` (`UserResource`) además de `tenants`. Cambio aditivo: los clientes actuales no se rompen.
- El endpoint ya aceptaba cualquier token del usuario (pre-tenant, de club o de plataforma); eso queda explícito en la spec.

## Impact

- `app/Http/Resources/Auth/MeTenantsResource.php`, `docs/api/openapi.json`.
- Tests en `tests/Feature/Auth/AuthFlowTest.php`.
- Front: `lib/session.ts` (FRO-2) usa este endpoint para `requireSession()`.
