# Runbook de deploy: Vercel + Laravel Cloud

Cómo poner Sportiva en un entorno (staging o producción) y cómo actualizarlo, sin depender de una persona en particular. Está escrito para seguirse en orden.

> Versión: 2 de noviembre de 2026 (TIZ-26). Se basa en el ADR 0002 del front ([Front-Sportiva `docs/adr/0002-hosting-vercel-laravel-cloud.md`](https://github.com/finalproyectogrupo8-max/Front-Sportiva/blob/main/docs/adr/0002-hosting-vercel-laravel-cloud.md)), en la [guía de jobs](guia-jobs.md) y en [mail-produccion.md](mail-produccion.md). Si algo del panel de Laravel Cloud o de Vercel no coincide con lo que dice este documento, gana el panel: corregí el documento en el mismo PR.

## Índice

1. [Cómo está armado](#1-cómo-está-armado)
2. [Antes del primer deploy](#2-antes-del-primer-deploy)
3. [Variables de entorno de la API](#3-variables-de-entorno-de-la-api)
4. [Variables de entorno del front](#4-variables-de-entorno-del-front)
5. [Primer deploy de un entorno](#5-primer-deploy-de-un-entorno)
6. [Deploy de una actualización](#6-deploy-de-una-actualización)
7. [Cola y scheduler](#7-cola-y-scheduler)
8. [Verificar que quedó bien](#8-verificar-que-quedó-bien)
9. [Rollback](#9-rollback)
10. [Backups](#10-backups)
11. [Pendientes antes de producción](#11-pendientes-antes-de-producción)

---

## 1. Cómo está armado

```
 browser ──> Vercel (Next.js, BFF) ──API_URL──> Laravel Cloud (API Laravel)
                                                   │
                                                   ├─ MySQL: sportiva_platform (base central, conectada al entorno)
                                                   │         club_<slug> (una base por club, mismo cluster)
                                                   ├─ Cola: worker o managed queue (provisioning y mails)
                                                   └─ Scheduler: tareas diarias (requisitos, alertas, tokens)
```

- **Ninguna de las dos plataformas usa Docker:** las dos compilan desde Git. Docker es solo para desarrollo local y CI.
- **La base central** es la única que se conecta al entorno desde el panel de Laravel Cloud (`DB_DATABASE`).
- **Las bases de los clubes** (`club_<slug>`) viven en el mismo cluster y la API las abre con la conexión `tenant`, con el mismo host, usuario y contraseña. **Nunca elijas una base de club en el selector de base del entorno:** reemplazaría a la central.
- **El front y la API van en la misma región** (ADR 0002), porque cada request hace el salto browser → Vercel → Laravel Cloud.

## 2. Antes del primer deploy

| Qué | Dónde | Quién |
| --- | --- | --- |
| Elegir la región: São Paulo si las dos plataformas la tienen, si no US East | Panel de Vercel y de Laravel Cloud | Equipo (ver `docs/decisions.md`) |
| Cluster Laravel MySQL 8.4 creado, con la base `sportiva_platform` | Laravel Cloud → Resources | Dueño del deploy |
| El usuario de la base puede **crear** bases `club_%` (el alta de clubes hace `CREATE DATABASE`) | Laravel Cloud → la base → usuarios | Dueño del deploy |
| Dominio remitente verificado en Resend (SPF, DKIM, DMARC) | Resend + DNS | Ver [mail-produccion.md](mail-produccion.md) |
| Acceso al repo de la API y del front desde las dos plataformas | GitHub → integraciones | Dueño del repo |

## 3. Variables de entorno de la API

Se cargan en Laravel Cloud → el entorno → **Environment variables**. Las que dicen *secreto* van como variables protegidas. Ningún valor real va al repo: `.env.example` solo lista los nombres.

### Aplicación

| Variable | Staging | Producción | De dónde sale |
| --- | --- | --- | --- |
| `APP_NAME` | `Sportiva` | `Sportiva` | Fijo |
| `APP_ENV` | `staging` | `production` | Fijo. Define el acceso a `/api/docs`: Basic en `staging`, 404 en `production` |
| `APP_KEY` | *secreto* | *secreto* | Laravel Cloud la genera al crear el entorno. **No la cambies después**: invalida lo que esté encriptado |
| `APP_DEBUG` | `false` | `false` | Fijo. Nunca `true` fuera de local |
| `APP_URL` | URL del entorno | URL del entorno | La que muestra Laravel Cloud (o el dominio propio) |
| `FRONTEND_URL` | URL de la preview o de staging en Vercel | URL de producción del front | Vercel. Arma los links de los mails: convocatorias y recuperación de contraseña |
| `CLUB_TIMEZONE` | `America/Argentina/Buenos_Aires` | igual | Fijo (es el valor por defecto) |
| `LOG_LEVEL` | `debug` | `warning` | Fijo |

No definas `FRONT_URL`: es un alias viejo y, si falta, se usa `FRONTEND_URL`.

### Base de datos

| Variable | Valor | De dónde sale |
| --- | --- | --- |
| `DB_CONNECTION` | `mysql` | Fijo |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Los del cluster; `DB_DATABASE=sportiva_platform` | Laravel Cloud los inyecta al conectar la base al entorno. Revisalos en el panel |

La conexión `tenant` usa **los mismos** `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD`, y no lee `DB_URL`. Si Laravel Cloud solo inyecta `DB_URL`, cargá además las variables sueltas: si no, las bases de club no conectan.

### Sanctum (tokens)

| Variable | Valor | Para qué |
| --- | --- | --- |
| `SANCTUM_TOKEN_PREFIX` | `sportiva_` | Prefijo de los tokens (lo detectan los scanners de secretos) |
| `SANCTUM_PRE_TENANT_TTL` | `15` | Minutos del token antes de elegir club |
| `SANCTUM_TENANT_TTL` | `480` | Minutos del token de club |
| `SANCTUM_PLATFORM_TTL` | `480` | Minutos del token del panel de plataforma |

### Documentación de la API

| Variable | Staging | Producción |
| --- | --- | --- |
| `API_DOCS_USERNAME` | *secreto* | No se define |
| `API_DOCS_PASSWORD` | *secreto* | No se define |

### Cola, cache y sesión

| Variable | Valor | Nota |
| --- | --- | --- |
| `QUEUE_CONNECTION` | `database` con worker propio, o `cloud` si Laravel Cloud la define con managed queue | Ver la [sección 7](#7-cola-y-scheduler) |
| `CACHE_STORE` | `database` | Igual que en local |
| `SESSION_DRIVER` | `database` | Igual que en local |

### Mail

Ver [mail-produccion.md](mail-produccion.md): `MAIL_MAILER=resend`, `RESEND_API_KEY` (*secreto*), `MAIL_FROM_ADDRESS` (del dominio verificado) y `MAIL_FROM_NAME=Sportiva`.

### Documentos de los clubes

| Variable | Valor | Nota |
| --- | --- | --- |
| `DOCUMENTS_DISK` | `s3` | Aptos médicos, fotos y escudos de los clubes. Privado: la API los sirve, el bucket nunca se expone. Sin esto, se borran en cada deploy |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL` | Los del bucket | Laravel Cloud los inyecta si se conecta un bucket (Object Storage) al entorno. El bucket tiene que ser **privado** |

## 4. Variables de entorno del front

Se cargan en Vercel → el proyecto → Settings → **Environment Variables**, por entorno.

| Variable | Preview | Production | Nota |
| --- | --- | --- | --- |
| `API_URL` | URL de la API de **staging** | URL de la API de producción | Solo la usa el servidor de Next.js (BFF). El browser nunca llama a la API |

Las demás variables del front están en el README de [Front-Sportiva](https://github.com/finalproyectogrupo8-max/Front-Sportiva). Si el front suma una variable que apunta a la API, agregala a esta tabla.

## 5. Primer deploy de un entorno

1. **Crear el entorno** en Laravel Cloud, conectado al repo de la API y a la rama que corresponda: `main` para producción, o la que decida el equipo para staging.
2. **Conectar la base** `sportiva_platform` del cluster al entorno. Revisar que `DB_DATABASE` sea `sportiva_platform` y no una base de club.
3. **Cargar las variables** de la [sección 3](#3-variables-de-entorno-de-la-api).
4. **Comandos de build** (Laravel Cloud → el entorno → Settings → Build): lo que trae por defecto (`composer install --no-dev ...`) alcanza. La API no tiene `npm`.
5. **Comandos de deploy**, en este orden:

   ```bash
   php artisan migrate --force
   php artisan tenants:migrate --force
   ```

   - `migrate` actualiza la base central.
   - `tenants:migrate` recorre todas las bases de los clubes **activos**, aplica `database/migrations/tenant` y resincroniza roles y permisos (`club:sync-permissions`).
   - Si alguno falla, el deploy falla y la versión anterior sigue activa.
6. **Activar la cola y el scheduler** ([sección 7](#7-cola-y-scheduler)).
7. **Deploy.**
8. **Crear el primer administrador de plataforma** desde la consola del entorno (Laravel Cloud → Commands):

   ```bash
   php artisan platform:create-admin admin@tudominio.com --name="Nombre Apellido"
   ```

   El comando pide la contraseña dos veces, de forma interactiva (mínimo 12 caracteres). Si la consola de Laravel Cloud no acepta entrada interactiva, el comando falla al pedirla: anotalo en los [pendientes](#11-pendientes-antes-de-producción) y creá el admin desde una sesión SSH, si el plan la tiene.

9. **Front:** cargar `API_URL` en Vercel ([sección 4](#4-variables-de-entorno-del-front)) y hacer deploy del front.
10. **Verificar** con la [sección 8](#8-verificar-que-quedó-bien).

## 6. Deploy de una actualización

Laravel Cloud despliega solo con cada push a la rama del entorno. El orden cuando hay migraciones es:

1. **Base central:** `php artisan migrate --force` (comando de deploy).
2. **Bases de los clubes:** `php artisan tenants:migrate --force` (comando de deploy, más el seeder de roles).
3. **Front:** recién después, deploy en Vercel (merge a `main` del front).

**Por qué en este orden:** el front nuevo puede pedir campos que todavía no existen en la base. Por la misma razón, **las migraciones tienen que ser compatibles hacia atrás**: el código anterior tiene que seguir funcionando con la base ya migrada, porque durante unos segundos conviven los dos.

| Hacé | No hagas |
| --- | --- |
| Agregar columnas nullable o con default | Renombrar o borrar una columna que el código actual usa, en el mismo deploy |
| Borrar una columna en un deploy **posterior**, cuando ningún código la lee | Migraciones que tarden minutos en bases grandes sin avisar |
| Regenerar `docs/api/openapi.json` en el PR (lo exige la CI) | Deployar con la CI en rojo |

**Si cambiaste un job:** la managed queue toma el código nuevo sola. Con un worker propio, el deploy lo reinicia. Si los jobs viejos que siguen en la cola no son compatibles con el código nuevo, esperá a que se vacíe antes de mergear.

## 7. Cola y scheduler

**Sin cola no funciona:** el alta de clubes (`ProvisionTenant`) y todos los mails van por la cola. Si no hay worker, el club queda en `provisioning` y los mails no salen.

| Opción | Cómo se activa | Requisito | Recomendación |
| --- | --- | --- | --- |
| **Managed queue** | Laravel Cloud → **Add compute → Managed queue** → deploy. Define `QUEUE_CONNECTION=cloud` | `composer require aws/aws-sdk-php` (**hoy no está instalado**) | Recomendada: sigue procesando aunque la app esté dormida (*Scale to Zero*) |
| **Worker propio** | Un Worker cluster con `php artisan queue:work --tries=3 --timeout=80` y `QUEUE_CONNECTION=database` | Ninguno | Funciona hoy, pero no procesa mientras la app duerme |

Límites del plan Starter: **1 cola**, hasta **3 workers** y **90 s como máximo por job**. Detalle en la [guía de jobs](guia-jobs.md#producción-laravel-cloud).

**Scheduler:** en el App cluster, activar el toggle **Scheduler** y hacer deploy. Corre lo que está en `routes/console.php`:

| Tarea | Cuándo |
| --- | --- |
| `sanctum:prune-expired --hours=24` | Todos los días |
| `club:expire-requirements` | Todos los días a las 03:00 |
| `alerts:run` | Todos los días a las 06:00 |

Los horarios del scheduler están en UTC (`timezone` de `config/app.php`), no en la hora del club: las 03:00 UTC son las 00:00 en Buenos Aires. Un cambio en `routes/console.php` recién vale después del siguiente deploy.

## 8. Verificar que quedó bien

| Qué | Cómo | Resultado esperado |
| --- | --- | --- |
| La API responde y ve la base central | `curl https://<api>/api/health` | `200` con `{"status":"ok","checks":{"database":"ok"}}`. Si la base no responde: `503` con `"status":"error"` |
| La documentación no está expuesta en producción | `curl -i https://<api>/api/docs` | `404` en producción; `401` sin credenciales en staging |
| Las bases de club están al día | Laravel Cloud → Commands: `php artisan tenants:migrate --force` | "Migrando..." con ✔ en cada club y sin migraciones pendientes |
| La cola procesa | `php artisan mail:test tu-direccion@tudominio.com` | El mail llega. Si no llega, ver la [sección 7](#7-cola-y-scheduler) |
| El mail sale sin depender de la cola | `php artisan mail:test tu-direccion@tudominio.com --sync` | El mail llega |
| Los documentos van al bucket | Subir un apto médico a un jugador, hacer un deploy y volver a descargarlo | Se descarga igual después del deploy. Si la subida da error 500, revisar las variables `AWS_*` |
| El alta de clubes funciona | Crear un club de prueba desde el panel de admin | Pasa de `provisioning` a `active` en menos de un minuto |
| El front habla con la API | Login en el front del entorno | Entra y lista los clubes |

`/api/health` solo revisa la base central. Que responda `ok` no garantiza que la cola ni el mail funcionen: para eso están las otras filas.

## 9. Rollback

### Código

En Laravel Cloud → el entorno → **Deployments**, volver a desplegar el último deploy que funcionaba. En Vercel, **Promote** del deploy anterior. Las migraciones **no** se revierten solas: por eso tienen que ser compatibles hacia atrás ([sección 6](#6-deploy-de-una-actualización)).

### Si falla `migrate` (base central)

El deploy falla y la versión anterior sigue activa. MySQL no puede revertir los cambios de esquema (DDL) a mitad de camino, así que una migración que falló puede haber quedado aplicada a medias:

1. Leer el error en el log del deploy.
2. Ver en qué quedó: `php artisan migrate:status`.
3. Corregir la migración en un PR nuevo. Si dejó una tabla o columna a medias, la migración corregida tiene que contemplarlo (por ejemplo, `Schema::hasColumn`).
4. Deploy de nuevo.

### Si falla `tenants:migrate` a mitad de camino

`tenants:migrate` sigue con los demás clubes aunque uno falle, y al final sale con error. Entonces:

- **El deploy falla**, y la versión anterior del código sigue activa.
- **Los clubes que migraron bien quedan migrados.** No pasa nada: las migraciones son compatibles hacia atrás.
- **Los que fallaron** quedan con la migración pendiente o a medias.

Pasos:

1. En el log del deploy, buscar las líneas `✖ Error al migrar base [club_...]` para saber qué clubes fallaron y por qué.
2. Corregir la causa: un dato del club que choca con la migración, o la migración misma (en un PR).
3. Reintentar solo ese club desde la consola:

   ```bash
   php artisan tenants:migrate --tenant=<slug> --force
   ```

   Es idempotente: aplica solo lo pendiente y resincroniza los permisos.
4. Cuando todos los clubes estén al día, deploy de nuevo.

**Nunca uses `--fresh` en staging ni en producción:** borra los datos del club.

## 10. Backups

- **Base central y bases de club:** están en el mismo cluster. Revisar en Laravel Cloud → el cluster → **Backups** que los backups automáticos estén activos y qué retención tienen en el plan contratado. Anotar el resultado acá.
- **Antes de una migración riesgosa** (borrar o transformar datos), hacer un backup manual del cluster desde el panel, o un `mysqldump` de las bases afectadas con el public endpoint (ADR 0002), y desactivar el endpoint al terminar.
- **Documentos (bucket):** revisar si el proveedor del bucket tiene versionado o backups. Hasta que se confirme, un documento borrado no se recupera.
- **Probar la restauración** al menos una vez en staging antes de cargar datos reales de clubes.

## 11. Pendientes antes de producción

Lo que hoy falta en el código o en la configuración para que este runbook funcione completo. Cada uno debería tener su ticket.

| Pendiente | Por qué | Qué hacer |
| --- | --- | --- |
| `aws/aws-sdk-php` no está en `composer.json` | La managed queue lo requiere | Agregarlo cuando se elija la managed queue, o usar el worker propio |
| El usuario de la base tiene permisos amplios | Puede crear y borrar cualquier base del cluster: un SQL injection tendría un impacto alto (ADR 0002) | Separar un usuario de operación y otro de provisión (pendiente en `docs/decisions.md`) |
| El deploy no espera a la CI | Laravel Cloud despliega con cada push, aunque los tests fallen | Desactivar el deploy automático y dispararlo con un *deploy hook* desde la CI cuando pase (ADR 0002) |
| La región no está elegida | Latencia entre Vercel y Laravel Cloud | Decidir y anotarlo en `docs/decisions.md` |
| Los backups no están verificados | No sabemos la retención real del plan | Completar la [sección 10](#10-backups) |
