# Guía para devs: cómo armar un ABM (CRUD)

Esta guía es la receta para escribir un ABM (alta, baja, modificación y consulta) en la API: dónde va cada archivo, cómo se nombran las rutas, qué hace el controller, cómo se valida, cómo se responde, cómo se lista, cómo se autoriza, cómo se borra y qué tests son obligatorios.

> **🟡 PROPUESTO.** Estas convenciones se adelantaron para que el equipo arranque con las mismas reglas. Se confirman y ajustan con el **primer ABM real**: el de usuarios y clubes del panel de admin, en el change `platform-admin-and-provisioning`. Si algo de esta guía choca con ese código, gana el código, y esta guía se corrige.
>
> **Dependencias.** Los ABM **de club** usan la autenticación (`platform-auth`) y la conexión `tenant` (`tenant-connection`, ver [guia-bases-de-datos.md](guia-bases-de-datos.md)), que ya existen. Los permisos por rol (`hasClubPermission()`, caso 8 de los tests) están en `club-access-control`: ver [roles y permisos](roles-y-permisos.md).
>
> Versión: 17 de octubre de 2026 · Laravel 13.37.

## Índice

1. [Las reglas en una página](#1-las-reglas-en-una-página)
2. [Dónde va cada archivo](#2-dónde-va-cada-archivo)
3. [Ejemplo completo: el ABM de jugadores (players)](#3-ejemplo-completo-el-abm-de-jugadores-players)
4. [Rutas](#4-rutas)
5. [Controller](#5-controller)
6. [Validación (Form Requests)](#6-validación-form-requests)
7. [Respuestas (API Resources)](#7-respuestas-api-resources)
8. [Listados: paginación, filtros, búsqueda y orden](#8-listados-paginación-filtros-búsqueda-y-orden)
9. [Autorización (policies y permisos)](#9-autorización-policies-y-permisos)
10. [Bajas: soft delete o borrado real](#10-bajas-soft-delete-o-borrado-real)
11. [Tests mínimos de cada ABM](#11-tests-mínimos-de-cada-abm)
12. [Paso a paso con comandos (checklist)](#12-paso-a-paso-con-comandos-checklist)
13. [ABM de la base central (panel de admin)](#13-abm-de-la-base-central-panel-de-admin)
14. [Errores comunes](#14-errores-comunes)

---

## 1. Las reglas en una página

| Tema | Regla |
| --- | --- |
| Idioma | El código va **en inglés**: tablas, modelos, rutas y campos (`players`, `Player`, `/api/players`, `birth_date`). La documentación y la UI van en español |
| URLs | Recursos en plural y en *kebab-case*: `/api/players`, `/api/training-sessions`. Nunca un verbo en la URL |
| JSON | Claves en `snake_case`, fechas ISO 8601 con zona horaria y enums en inglés |
| Rutas | `Route::apiResource()`, con 5 acciones: `index`, `store`, `show`, `update` y `destroy` |
| Controller | Fino: autoriza, valida (con el Form Request), llama al modelo o a una Action y devuelve un Resource. Sin lógica de negocio larga |
| Validación | Un `Store<Model>Request` y un `Update<Model>Request` por recurso. Nunca `$request->validate()` en el controller |
| Respuesta | Siempre un **API Resource**, nunca el modelo ni un array suelto. Los listados se paginan |
| Status | `index` y `show` responden 200 · `store` 201 · `update` 200 · `destroy` 204 · validación 422 · no encontrado 404 · sin permiso 403 · sin token 401 |
| Autorización | Una Policy por modelo, contra las *permission keys* del rol (`players.create`). Se llama con `Gate::authorize()` |
| Bajas | Las entidades de negocio usan *soft delete*. Ver la sección 10 |
| Club | Los modelos de club declaran `protected $connection = 'tenant';`. El club **nunca** sale del request: sale del token |
| Tests | Los 10 casos de la sección 11, incluido el **aislamiento entre clubes** |
| Contrato | Al terminar, corré `php artisan scramble:export` y commiteá `docs/api/openapi.json` |

## 2. Dónde va cada archivo

Se usan las carpetas estándar de Laravel, con **un subnombre por área**: `Club` para lo operativo de un club y `Admin` para el panel de administración de plataforma.

```
app/
├── Models/
│   ├── Club/Player.php                       ← modelo de club ($connection = 'tenant')
│   └── Tenant.php, User.php                  ← modelos de la base central
├── Http/
│   ├── Controllers/
│   │   ├── Club/PlayerController.php         ← ABM de club
│   │   └── Admin/TenantController.php        ← ABM del panel de admin
│   ├── Requests/
│   │   └── Club/StorePlayerRequest.php, UpdatePlayerRequest.php
│   └── Resources/
│       └── Club/PlayerResource.php
├── Policies/
│   └── Club/PlayerPolicy.php
└── Actions/                                   ← opcional: lógica de negocio reutilizable
    └── Club/TransferPlayer.php

database/
├── migrations/tenant/2026_10_25_000001_create_players_table.php   ← migración de club
└── factories/Club/PlayerFactory.php

tests/Feature/Club/PlayerApiTest.php
```

**Cuándo crear una Action** (`app/Actions/...`): cuando una operación hace más que guardar un modelo (por ejemplo, "transferir un jugador": cambia de categoría, registra un historial y notifica), o cuando la misma lógica se usa en más de un lugar, como un endpoint y un comando. Un ABM simple no la necesita.

## 3. Ejemplo completo: el ABM de jugadores (players)

> 🟡 **Ilustrativo.** Los nombres de los campos son de ejemplo, no el modelo definitivo de jugadores. La auth y la conexión `tenant` ya existen; los permisos por rol todavía no (`club-access-control`).

**Migración de club** (`database/migrations/tenant/...create_players_table.php`):

```php
Schema::create('players', function (Blueprint $table) {
    $table->id();
    $table->string('first_name');
    $table->string('last_name');
    $table->date('birth_date');
    $table->string('position')->nullable();            // goalkeeper, defender, ...
    $table->string('status')->default('active');        // enum en inglés: active | injured | inactive
    $table->timestamps();
    $table->softDeletes();                              // baja lógica (sección 10)
});
```

**Modelo** (`app/Models/Club/Player.php`):

```php
class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';                   // SIEMPRE en los modelos de club

    protected $fillable = ['first_name', 'last_name', 'birth_date', 'position', 'status'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }
}
```

**Rutas** (`routes/api.php`):

```php
Route::middleware(['auth:sanctum', 'tenant.token'])->group(function () {
    Route::apiResource('players', PlayerController::class);
});
```

**Controller** (`app/Http/Controllers/Club/PlayerController.php`):

```php
class PlayerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Player::class);

        $players = Player::query()
            ->when($request->string('search')->toString(), fn ($q, $s) =>
                $q->where(fn ($q) => $q->where('first_name', 'like', "%{$s}%")
                                       ->orWhere('last_name', 'like', "%{$s}%")))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->paginate($this->perPage($request));

        return PlayerResource::collection($players);    // → { data, links, meta }
    }

    public function store(StorePlayerRequest $request): JsonResponse
    {
        Gate::authorize('create', Player::class);

        $player = Player::create($request->validated());

        return PlayerResource::make($player)->response()->setStatusCode(201);
    }

    public function show(Player $player): PlayerResource
    {
        Gate::authorize('view', $player);

        return PlayerResource::make($player);
    }

    public function update(UpdatePlayerRequest $request, Player $player): PlayerResource
    {
        Gate::authorize('update', $player);

        $player->update($request->validated());

        return PlayerResource::make($player);
    }

    public function destroy(Player $player): Response
    {
        Gate::authorize('delete', $player);

        $player->delete();                               // soft delete

        return response()->noContent();                  // 204
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 20), 1), 100);   // por defecto 20, máximo 100
    }
}
```

**Form Requests** (`app/Http/Requests/Club/`):

```php
class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;                                     // la autorización la hace la Policy
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'position'   => ['nullable', Rule::enum(PlayerPosition::class)],
            'status'     => ['sometimes', Rule::enum(PlayerStatus::class)],
        ];
    }
}

class UpdatePlayerRequest extends StorePlayerRequest
{
    public function rules(): array
    {
        // PATCH parcial: cada campo es opcional, pero si viene, se valida igual que al crear
        return collect(parent::rules())
            ->map(fn (array $rules) => array_merge(['sometimes'], array_diff($rules, ['required'])))
            ->all();
    }
}
```

**Resource** (`app/Http/Resources/Club/PlayerResource.php`):

```php
class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'birth_date' => $this->birth_date->toDateString(),        // 2010-05-31
            'position'   => $this->position,
            'status'     => $this->status,
            'created_at' => $this->created_at->toIso8601String(),     // con zona horaria
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

## 4. Rutas

- **`Route::apiResource('players', PlayerController::class)`** genera las 5 rutas:

  | Método y URL | Acción | Status OK |
  | --- | --- | --- |
  | `GET /api/players` | `index` | 200 |
  | `POST /api/players` | `store` | 201 |
  | `GET /api/players/{player}` | `show` | 200 |
  | `PUT/PATCH /api/players/{player}` | `update` | 200 |
  | `DELETE /api/players/{player}` | `destroy` | 204 |

- Si un recurso no tiene alguna acción, se limita: `->only(['index', 'show'])` o `->except(['destroy'])`.
- Una **acción que no es CRUD** (por ejemplo, transferir) va como subrecurso o como verbo sobre el recurso: `POST /api/players/{player}/transfer`. Nunca `POST /api/transferPlayer`.
- **Recursos anidados**, solo cuando el hijo no tiene sentido sin el padre: `/api/matches/{match}/lineups`. Con `->shallow()`, las acciones sobre un elemento quedan cortas: `/api/lineups/{lineup}`.
- **Grupos de middleware:**
  - de club: `['auth:sanctum', 'tenant.token']`. `tenant.token` valida el club del token y apunta la conexión `tenant` a su base;
  - de panel de admin: `['auth:sanctum', 'platform.admin']`, con prefijo `admin`.

## 5. Controller

- **Una clase por recurso**, con las 5 acciones de `apiResource`. Se genera con `--api --model`.
- **Orden fijo en cada acción:**
  1. autorizar (`Gate::authorize`);
  2. validar (el Form Request ya lo hizo antes de entrar);
  3. operar (el modelo o una Action);
  4. responder (un Resource).
- **El `Controller` base de Laravel 13 está vacío** (no trae `authorize()`): se usa `Gate::authorize(...)`, que lanza 403 si no se cumple.
- **El *route model binding*** (`Player $player` en la firma) busca el registro solo, y si no existe responde **404 en JSON**. Para los modelos de club, busca en la base del club, siempre que el middleware de tenant haya corrido antes (ver la sección 14).
- **Nada de** `$request->all()`, `$request->validate()`, ni devolver el modelo directo (`return $player`).

## 6. Validación (Form Requests)

- `Store<Model>Request` para el alta y `Update<Model>Request` para la modificación.
- `authorize()` devuelve `true`: la autorización vive en la Policy, en un solo lugar.
- **El update es parcial (PATCH):** cada campo lleva `sometimes`, así solo se valida si viene. El ejemplo de la sección 3 lo hereda del Store para no duplicar reglas.
- **Enums con `Rule::enum(...)`**, con valores en inglés.
- **Unicidad en tablas de club:** la regla `unique` usa la conexión por defecto (la central). En tablas de club hay que indicarle la conexión: `Rule::unique('tenant.players', 'document_number')`. En un update, se ignora el propio registro con `->ignore($this->route('player'))`.
- Si la validación falla, Laravel responde **422** con `{ "message": "...", "errors": { "campo": ["..."] } }`. Es el formato que espera el front: no lo cambies.

## 7. Respuestas (API Resources)

- **Siempre un Resource**: define **qué campos** salen. Un campo nuevo en la tabla no sale en la API hasta que se agrega al Resource, y así no se filtran datos por accidente.
- **Fechas:**
  - si tienen hora, en ISO 8601 con zona: `toIso8601String()` → `2026-10-17T15:30:00-03:00`;
  - si son solo fecha: `toDateString()` → `2026-10-17`.
- **Relaciones,** solo si se cargaron: `'category' => CategoryResource::make($this->whenLoaded('category'))`. Eso evita consultas N+1.
- **Listados:** `PlayerResource::collection($paginator)` devuelve `{ data, links, meta }`, que es el formato del contrato con el front.
- Scramble genera el contrato OpenAPI a partir del Resource y del Form Request. Si respetás estas reglas, el contrato sale completo y bien tipado.

## 8. Listados: paginación, filtros, búsqueda y orden

| Parámetro | Para qué | Ejemplo |
| --- | --- | --- |
| `page` | Número de página (lo maneja Laravel) | `?page=2` |
| `per_page` | Cantidad por página: por defecto **20**, máximo **100** | `?per_page=50` |
| `search` | Búsqueda de texto en los campos que defina cada ABM | `?search=gomez` |
| `<campo>` | Filtro exacto por un campo permitido | `?status=active&position=defender` |
| `sort` | Orden: el campo, con `-` adelante para descendente | `?sort=-birth_date` |

Las reglas:

- **Solo campos permitidos.** Cada ABM declara una lista blanca de los campos que se pueden filtrar y ordenar. Nunca se pasa un nombre de columna que viene de la URL directo a la consulta.
- **Orden por defecto estable,** por ejemplo por apellido o por `id`, para que la paginación no repita ni saltee registros.
- **Si un ABM necesita filtros complejos,** se evalúa el paquete `spatie/laravel-query-builder`. No se agrega antes de que haga falta.

## 9. Autorización (policies y permisos)

- **Una Policy por modelo** (`make:policy --model`), con `viewAny`, `view`, `create`, `update` y `delete`.
- **En los ABM de club,** la Policy pregunta si el rol del usuario **en el club actual** tiene la *permission key*:

  ```php
  class PlayerPolicy
  {
      public function viewAny(User $user): bool { return $user->hasClubPermission('players.view'); }
      public function create(User $user): bool  { return $user->hasClubPermission('players.create'); }
      public function update(User $user, Player $player): bool { return $user->hasClubPermission('players.update'); }
      public function delete(User $user, Player $player): bool { return $user->hasClubPermission('players.delete'); }
  }
  ```

  `hasClubPermission()` y `clubPermissionScope()` están en `App\Models\User` (ver [roles y permisos §4](roles-y-permisos.md#4-alcance-todo-el-club-o-sus-categorías)). Con alcance `own` ("sus categorías"), el controlador filtra el listado y un registro ajeno responde 404: ver `Coach::scopeVisibleTo()`.
- **En los tests,** `clubToken($tenant, roles: ['coach'])` crea el usuario con su `club_user` y esos roles. Sin `roles`, el usuario no tiene permisos.
- **Las *permission keys*** se escriben `<recurso>.<acción>`, en inglés y en plural: `players.view`, `players.create`, `players.update`, `players.delete`. Para acciones especiales: `players.transfer`.
- **Cada ABM nuevo agrega sus permisos** a `config/club-permissions.php` (catálogo y roles estándar). Si no, ningún rol los tiene, y nadie puede usar el ABM. `club:sync-permissions` (corre con `tenants:migrate`) los lleva a cada club.
- **Sin permiso,** la respuesta es **403** con `code: forbidden`. El front usa ese `code` para decidir qué mostrar.

## 10. Bajas: soft delete o borrado real

| Tipo de dato | Baja | Por qué |
| --- | --- | --- |
| Entidades de negocio con historia (jugadores, partidos, pedidos, socios) | **Soft delete** (`SoftDeletes` + `$table->softDeletes()`) | Otros registros las referencian (estadísticas, historial) y se pueden restaurar |
| Datos auxiliares o de unión (una asignación, un tag) | Borrado real | No tienen historia propia |
| Datos personales con pedido de eliminación | Borrado real o anonimización | Lo exige la protección de datos. Se define caso por caso |

Lo que hay que tener en cuenta con *soft delete*:

- Los registros borrados **no aparecen** en las consultas normales, ni en el *route model binding*: un `GET` a un jugador borrado responde 404.
- Las reglas `unique` tienen que **ignorar los borrados**, si se permite volver a usar el valor: `->whereNull('deleted_at')`.
- **Restaurar** es una acción aparte: `POST /api/players/{player}/restore`, con `->withTrashed()` en la ruta y el permiso `players.restore`.

## 11. Tests mínimos de cada ABM

Un archivo por recurso, `tests/Feature/Club/<Model>ApiTest.php`. Estos 10 casos son **obligatorios**:

| # | Caso | Espera |
| --- | --- | --- |
| 1 | Listar | 200, con `data`, `links` y `meta`, paginado |
| 2 | Ver uno | 200, con los campos del Resource |
| 3 | Crear con datos válidos | 201, y el registro existe en la base del club |
| 4 | Crear con datos inválidos | 422, con `errors` del campo que falló |
| 5 | Modificar (parcial) | 200, y solo cambió lo que se envió |
| 6 | Borrar | 204, y el registro queda borrado (o con `deleted_at`, si es soft delete) |
| 7 | Sin token | 401 |
| 8 | Sin permiso | 403, con `code: forbidden` |
| 9 | Inexistente | 404 en JSON |
| 10 | **Aislamiento entre clubes** | Con el token del club A, un registro del club B **no se ve ni se puede modificar** (404) |

El caso 10 es el más importante: garantiza que un club nunca accede a los datos de otro. Usá **factories** para armar los datos de prueba (`--factory` al crear el modelo) y nada de datos personales reales.

Los tests de club usan el trait `Tests\Concerns\InteractsWithTenants`, junto con `RefreshDatabase`. Da dos clubes de prueba (`club_test_a` y `club_test_b`) con sus bases migradas y las tablas de club vacías al empezar cada test:

| Helper | Para qué |
| --- | --- |
| `tenantA()`, `tenantB()` | Los dos clubes (`Tenant`) |
| `clubToken($tenant, $user = null)` | Un token de club como el de `select-tenant`, con un usuario miembro (lo crea si no le pasás uno) |
| `withClubToken($token)` | Prepara el próximo request con ese token. Usalo en cada request: Sanctum cachea el usuario entre requests del mismo test |
| `inTenant($tenant, fn () => ...)` | Cargar datos o hacer asserts dentro de la base de un club |

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;

class PlayerApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_a_player_of_another_club_is_not_visible(): void   // caso 10
    {
        $playerB = $this->inTenant($this->tenantB(), fn () => Player::factory()->create());

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->getJson("/api/players/{$playerB->id}")
            ->assertNotFound();
    }
}
```

Los datos de club se crean **dentro de `inTenant`**: fuera, la conexión `tenant` no apunta a ninguna base y la consulta falla. El ejemplo real está en `tests/Feature/Tenancy/TenantContextTest.php`. Si los tests fallan con `Access denied` sobre `club_test_a`, ver [desarrollo-local.md](desarrollo-local.md#los-tests-de-club-fallan-por-permisos-de-mysql).

## 12. Paso a paso con comandos (checklist)

Todos los comandos van con `docker compose exec backend` adelante (acá se omite para que se lean mejor).

1. **Rama:** `git checkout -b feat/players-abm`.
2. **Migración de club:**
   ```bash
   php artisan make:migration create_players_table --path=database/migrations/tenant
   ```
3. **Modelo, factory, controller, Requests, Resource y Policy**, cada uno en su carpeta `Club/`:
   ```bash
   php artisan make:model Club/Player --factory
   php artisan make:controller Club/PlayerController --api --model=Club/Player
   php artisan make:request Club/StorePlayerRequest
   php artisan make:request Club/UpdatePlayerRequest
   php artisan make:resource Club/PlayerResource
   php artisan make:policy Club/PlayerPolicy --model=Club/Player
   ```
   Después, agregale al modelo `$connection = 'tenant'`, `SoftDeletes`, `$fillable` y `casts()`.

   > Existe un atajo, `make:model Club/Player --factory --policy --requests --controller --api`, pero deja el controller, los Requests y la Policy **fuera** de `Club/` (verificado: crea `app/Http/Controllers/PlayerController.php`), y hay que moverlos a mano. Además, en `make:model` la opción `--resource` **no** crea el API Resource: indica que el controller es "de recurso".
4. **Ruta:** `Route::apiResource('players', PlayerController::class)` dentro del grupo de club.
5. **Permisos:** agregá `players.view`, `players.create`, `players.update` y `players.delete` al seeder de roles del club.
6. **Migrar:** `php artisan tenants:migrate` (🟡 cuando exista) en las bases de club locales.
7. **Tests:**
   ```bash
   php artisan make:test Club/PlayerApiTest
   php artisan test --filter=PlayerApiTest
   ```
8. **Probalo en Swagger:** `http://127.0.0.1:8000/api/docs` (recargá la página: no hace falta reiniciar nada).
9. **Contrato y estilo:**
   ```bash
   php artisan scramble:export          # y commiteá docs/api/openapi.json
   vendor/bin/pint                      # formatea
   php artisan test                     # toda la suite
   ```
10. **PR:** la CI tiene que pasar (tests, Pint y el contrato al día) y un compañero tiene que aprobarlo.

## 13. ABM de la base central (panel de admin)

Siguen las mismas reglas, con estas diferencias:

| | ABM de club | ABM del panel de admin |
| --- | --- | --- |
| Base | La del club (`$connection = 'tenant'`) | La central (conexión por defecto) |
| Carpetas | `Club/` | `Admin/` (controllers, Requests y Resources). Los modelos centrales van en `app/Models/` |
| Rutas | Grupo `['auth:sanctum', 'tenant.token']` | Grupo `['auth:sanctum', 'platform.admin']`, con prefijo `/api/admin` |
| Autorización | Permisos del rol en el club | Ser admin de plataforma (`is_platform_admin`) |
| Migraciones | `database/migrations/tenant/` | `database/migrations/` |
| Auditoría | Según el caso | **Siempre**: cada alta, baja o cambio queda registrado |
| Test de aislamiento (caso 10) | Entre clubes | Un usuario que **no** es admin de plataforma recibe 403 |

## 14. Errores comunes

| Error | Qué pasa | Cómo evitarlo |
| --- | --- | --- |
| El modelo de club no declara `$connection = 'tenant'` | Consulta la base central: tabla inexistente o, peor, datos equivocados | Revisá el modelo en cada PR. El test de aislamiento lo detecta |
| El middleware de tenant corre **después** del *route model binding* | `Player $player` se busca en una base sin apuntar y da error (`No database selected`) | Ya está resuelto: `tenant.token` está registrado antes de `SubstituteBindings` (`prependToPriorityList` en `bootstrap/app.php`). No lo saques. Los casos 2 y 10 del test lo cubren |
| Se acepta `tenant_id` o el slug del club en el request | Un usuario podría pedir datos de otro club | El club sale **siempre** del token, nunca del body ni de la URL |
| `unique` sin conexión en una tabla de club | Valida contra la base central | `Rule::unique('tenant.players', ...)` |
| Se devuelve el modelo (`return $player`) | Salen todos los campos (también los internos) y el contrato queda mal tipado | Siempre un Resource |
| Columnas de filtro u orden tomadas de la URL sin lista blanca | Errores de SQL o fuga de datos | Lista blanca por ABM (sección 8) |
| Un ABM nuevo sin permisos en el seeder | Nadie lo puede usar: 403 para todos | El paso 5 del checklist |
| Te olvidás de `scramble:export` | La CI falla en "Contrato OpenAPI al día" | El paso 9 del checklist |
