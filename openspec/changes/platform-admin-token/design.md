# Design: Token Dedicado de Plataforma para el Panel de Administración (TIZ-07)

## Contexto y Decisiones de Diseño

### 1. Emisión en `POST /api/auth/login` vs Endpoint Dedicado (`POST /api/auth/platform`)

Se descartó la creación de un endpoint específico `POST /api/auth/platform` por las siguientes razones:
- **Simplicidad en el cliente (BFF Next.js):** El frontend realiza una única petición de autenticación en la pantalla de inicio de sesión. Al recibir tanto el token de usuario como el token de plataforma en el mismo payload (cuando corresponda), el BFF puede almacenar y delegar las credenciales apropiadas de forma directa sin roundtrips HTTP adicionales ni re-autenticación redundante.
- **Cohesión de Identidad:** La condición de `is_platform_admin` es un atributo persistente del usuario central verificado en la autenticación con contraseña. No existen factores secundarios de autenticación distintos en esta etapa que justifiquen un endpoint separado.

### 2. Ciclo de Vida y Tipos de Tokens en Sportiva

La plataforma distingue tres tipos de Bearer tokens gestionados mediante Laravel Sanctum:

| Tipo de Token | Nombre Sanctum | Abilities | `tenant_id` | TTL por defecto | Usos autorizados |
|---|---|---|---|---|---|
| **Pre-Tenant** | `pre-tenant` | `['tenant:select']` | `null` | 15 min (`SANCTUM_PRE_TENANT_TTL`) | Selector de club (`POST /api/auth/select-tenant`), consulta de clubes (`GET /api/me/tenants`), logout. |
| **Club (Tenant)** | `tenant:<slug>` | `['tenant:select', 'tenant:operate']` | `int` (ID de club) | 480 min (`SANCTUM_TENANT_TTL`) | Operaciones dentro del club (`/api/players`, `/api/sports`, etc.), `GET /api/me`, logout, cambio de club. |
| **Plataforma** | `platform` | `['platform:admin']` | `null` | 480 min (`SANCTUM_PLATFORM_TTL`) | Gestión global de plataforma (`/api/admin/*`), logout. |

### 3. Expiración Global vs TTLs Específicos

En `config/sanctum.php`, el valor `'expiration' => 480` actúa como límite superior absoluto evaluado por Sanctum. Como `SANCTUM_PLATFORM_TTL` se establece por defecto en 480 minutos, `expiration >= platform_ttl`, evitando que el token sea invalidado antes del tiempo fijado en su atributo `expires_at`.

### 4. Seguridad en Capas para `/api/admin/*`

El acceso a las rutas administrativas se protege con una secuencia de middleware estricta:
1. `auth:sanctum`: Valida firma, vigencia y autenticidad del Bearer token.
2. `abilities:platform:admin`: Valida que el token emitido posea explícitamente la ability `platform:admin`. Si no la posee (ej. tokens pre-tenant o de club), lanza `MissingAbilityException`, la cual `bootstrap/app.php` traduce a `ApiError::forbidden('forbidden')` (HTTP 403 con `{ message, code: 'forbidden' }`).
3. `platform.admin` (`EnsurePlatformAdmin`): Valida que el modelo `User` autenticado tenga `is_platform_admin === true`. Previene el uso de tokens con abilities forzadas si el usuario ya no es administrador.

### 5. Formato de Respuesta en `LoginResource`

Para evitar fugas de información o estructuras innecesarias en clientes no administrativos, los atributos `platform_token` y `platform_expires_at` se definen mediante `$this->when($this->platformToken !== null, ...)`. Cuando el usuario no es admin, estos campos quedan omitidos del JSON.

