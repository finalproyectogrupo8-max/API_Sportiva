## Context

Una sola API con dos tipos de base: central (`sportiva_platform`) y una por club (ver `docs/guia-bases-de-datos.md`). El front es un BFF: el browser nunca llama a esta API, por lo que se usan tokens y no cookies de sesión.

## Goals / Non-Goals

**Goals**
- Poder autenticarse y usar `/api/admin/*` de punta a punta.
- Que el club salga del token y nunca del cliente.
- Cerrar los hallazgos de la revisión de los PR #8 y #9.

**Non-Goals**
- Crear la base del club (`ProvisionTenant`), el middleware de tenant y los módulos del club.

## Decisions

1. **Dos tokens.** Login emite un token con ability `pre-tenant` (vence en 15 min). `select-tenant` lo revoca y emite uno con ability `tenant` y `tenant_id`.
2. **Token de plataforma.** Un usuario con `is_platform_admin` usa el token `pre-tenant`/de plataforma para `/api/admin/*`, sin elegir club. Detalle de abilities a confirmar en la revisión.
3. **`tenant_id` en `personal_access_tokens`** con un modelo `PersonalAccessToken` propio registrado con `Sanctum::usePersonalAccessTokenModel()`. Se completa con `forceFill` porque `createToken` no lo acepta.
4. **`database_name` derivado:** `club_<slug>`, asignado por el servidor, sin campo en el Request. El `slug` valida `^[a-z0-9_]+$`, máximo 50 caracteres (el límite de nombre de base en MySQL es 64 y el prefijo ocupa 5). El prefijo `club_` impide chocar con `sportiva_platform`, `sportiva_testing`, `mysql` o `sys`.
5. **`slug` inmutable** tras la creación (cambiarlo desalinearía `database_name`).
6. **`is_platform_admin` fuera de `#[Fillable]`.** Se asigna explícitamente en `platform:create-admin`.
7. **Sanctum:** `expiration` definido, `token_prefix` `sportiva_` (secret scanning de GitHub), `guard` sin `web` y `stateful` vacío.
8. **Convenciones (AGENTS.md):** `TenantResource`, Form Request en `index`, paginación 20 por defecto y 100 máximo, `Gate::authorize` con una `TenantPolicy`.
9. **Login:** rate limiting y mensaje genérico para credenciales inválidas (sin revelar si el email existe).

## Risks / Trade-offs

- Cambio breaking en los Requests del ABM. Hoy no hay consumidores (nadie puede autenticarse), por lo que el costo es bajo.
- `forceFill` sobre el token depende del modelo personalizado de Sanctum.

## Open Questions

- **¿`suspended` es un estado válido?** La guía define `provisioning | active | failed`. Si lo es, hay que actualizar la guía y la migración. Si no, `destroy` necesita otro mecanismo (la guía pide soft delete en entidades de negocio).
- **Duración del token de club** (propuesta: 8 horas) y del token `pre-tenant` (15 minutos).
- **Qué abilities usa el token de un platform admin** para `/api/admin/*`.