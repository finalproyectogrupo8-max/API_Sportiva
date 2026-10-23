# Cómo funciona el login

Explicación funcional del ingreso a Sportiva, para quien no necesita ver código. El detalle técnico está en `docs/integracion-api-laravel.md` del repo del front y en las specs de `openspec/specs/platform-auth/` y `openspec/specs/platform-admin-token/`.

## La idea general

- **Hay un solo login para todos.** Los usuarios viven en una base central, no en la de cada club. Una persona tiene un solo usuario aunque trabaje en varios clubes.
- **Cada club tiene su propia base de datos.** Por eso, después de saber quién sos, el sistema tiene que saber **en qué club** vas a trabajar.
- **Al entrar no recibís "acceso a todo", sino un pase** (un *token*) para un lugar concreto. Cada pase abre una sola cosa y vence solo.
- **El navegador nunca ve el pase.** Lo guarda el servidor de la web en una cookie protegida: aunque alguien inyecte código en la página, no lo puede robar.

## Los pases

| Pase | Quién lo recibe | Dura | Sirve para | No sirve para |
|---|---|---|---|---|
| **Sala de espera** (pre-tenant) | Todos, al loguearse | 15 min | Ver tus clubes y elegir uno | Ver o tocar datos de un club |
| **De club** | Quien elige un club | 8 h (una jornada) | Trabajar en ese club | Otro club, o administrar la plataforma |
| **De administración** (plataforma) | Solo admins de plataforma, al loguearse | 8 h | El panel: alta de usuarios y clubes, membresías | Operar dentro de un club |

## Usuario de un club

1. **Ingresa email y contraseña.** Si están mal, ve "Credenciales inválidas" (nunca se le dice cuál de los dos falló, para no dar pistas). Si se equivoca 5 veces en un minuto, se bloquea un rato.
2. **Recibe el pase de sala de espera.**
3. **Elige el club:**
   - Si tiene uno solo, entra directo.
   - Si tiene varios, ve "¿A qué club entrás hoy?".
   - Si no tiene ninguno, ve "No tenés clubes asignados".
4. **Al elegir, el sistema vuelve a verificar** que siga siendo miembro y que el club esté activo. Si todo está bien, destruye el pase de sala de espera y le da el pase del club.
5. **A partir de ahí, todo lo que hace va contra la base de ese club.** El club lo define el pase, no la dirección de la página: cambiar la URL a mano no lo lleva a otro club.

**Cambiar de club** es repetir el paso 4: el sistema anula el pase actual y da uno nuevo, sin volver a pedir la contraseña.

## Admin de plataforma

1. **Usa el mismo login.**
2. **Además del pase de sala de espera, recibe el pase de administración**, el único que abre el panel.
3. **El sistema lo lleva directo al panel.** Un usuario común que escriba esa dirección a mano vuelve a su pantalla de clubes, y aunque lo intente por otra vía, el servidor lo rechaza.
4. **El pase de administración no sirve para operar dentro de un club**, ni el de un club para administrar: cada pase hace una sola cosa.

El panel dura las 8 horas del pase de administración. Pasados los 15 minutos, el pase de sala de espera vence solo; si el admin quiere entrar a un club, tiene que volver a loguearse.

## Por qué existe el pase de sala de espera

Entre el login y la elección del club, el servidor tiene que demostrar quién sos para mostrarte tus clubes. Las alternativas eran peores:

| Opción | Problema |
|---|---|
| Guardar email y contraseña hasta que elijas | El servidor tendría que retener la contraseña |
| Dar de entrada un pase completo sin club | Si se roba, es una sesión entera |
| **Un pase corto que solo sirve para elegir club** (elegida) | — |

Si alguien lo roba, solo puede ver la lista de clubes de esa persona, por unos minutos. Y como la membresía se verifica de nuevo al elegir, si a alguien le quitan el acceso entre el login y la elección, no entra.

## Salir y vencimientos

- **"Salir" anula los pases en el servidor**, no solo en el navegador: aunque alguien los hubiera copiado, dejan de funcionar.
- **Cuando un pase vence**, o el admin desactiva al usuario, la próxima acción lo devuelve al login.
- **Si a alguien le quitan el acceso a un club** (o el club se suspende), lo pierde aunque tenga la sesión abierta: el acceso se verifica en cada pedido.

## Estado (23/10/2026)

| Parte | Estado |
|---|---|
| Login, pases, salir y vencimientos | Funciona (API y front) |
| Redirección al panel o a los clubes según el usuario | Funciona |
| Elección de club con los clubes reales | Pendiente (FRO-3) |
| Entrar al club con sus datos reales | Pendiente (FRO-4) |
| Panel de administración | Pendiente (FRO-5 a FRO-7) |
