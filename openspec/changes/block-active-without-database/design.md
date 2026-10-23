# Design: Bloqueo de Activación de Clubes sin Base Provisionada (TIZ-06)

## Arquitectura y Flujo de Validación

El endpoint `PATCH /api/admin/tenants/{tenant}` está delegado en `TenantController@update`:

```
Cliente Admin ──> PATCH /api/admin/tenants/{id} { status: "active" }
                       │
                       ▼
             UpdateTenantRequest
             (rules + withValidator/after)
                       │
                       ├─ ¿Club está en "provisioning" o "failed"?
                       │    ├── SÍ ──> 422 Unprocessable Content { errors: { status: [...] } }
                       │    └── NO ──> Pasa al Controller
                       │
                       ▼
             TenantController@update
             (transacción DB + AuditLogger)
                       │
                       ▼
             200 OK con TenantResource
```

### Implementación en `UpdateTenantRequest`

Para mantener la regla arquitectónica de validación exclusiva en Form Requests y evitar lógica duplicada en los controladores:
- Se utiliza el hook `withValidator` de Laravel invocando `$validator->after(...)`.
- Se obtiene el modelo `Tenant` enlazado en la ruta mediante `$this->route('tenant')`.
- Si se envía `status === 'active'` y el club posee actualmente `status === 'provisioning'` o `status === 'failed'`, se agrega un error en la clave `status`:
  `"No se puede activar un club en estado '{$tenant->status}' cuya base de datos no ha sido provisionada."`
- La inferencia estática de OpenAPI realizada por Scramble no se ve afectada porque `rules()` mantiene su definición de reglas declarativas (`Rule::in(['active', 'suspended'])`).

### Matriz de Transiciones de Estado para `PATCH /api/admin/tenants/{tenant}`

| Estado Actual | `status` enviado | Resultado HTTP | Auditoría generada |
|---|---|---|---|
| `provisioning` | `active` | 422 Unprocessable | No |
| `failed` | `active` | 422 Unprocessable | No |
| `suspended` | `active` | 200 OK | Sí (`tenant.updated`) |
| `active` | `suspended` | 200 OK | Sí (`tenant.updated`) |
| `active` | `active` | 200 OK | No (sin cambios reales) |
| `provisioning` | *(no enviado, solo `name`)* | 200 OK | Sí (`tenant.updated` con nuevo nombre) |
| `provisioning` | `suspended` | 200 OK | Sí (`tenant.updated`) |
| `failed` | `suspended` | 200 OK | Sí (`tenant.updated`) |
| Cualquier estado | `provisioning` o `failed` | 422 Unprocessable (por `Rule::in`) | No |

### Exclusión de Vías Autorizadas a `active`

El job de segundo plano `App\Jobs\ProvisionTenant` y el endpoint de reintento `POST /api/admin/tenants/{tenant}/retry` no consumen `UpdateTenantRequest`, preservando la capacidad legítima de provisionar y activar bases de datos.

