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

## Crear una cuenta: invitación, no registro público

Por decisión de TIZ-9, **no hay registro público**. Una persona no puede crear una cuenta desde una pantalla abierta; la cuenta la crea un administrador de plataforma o un administrador de club la invita al equipo.

1. **El admin crea o invita.** En el panel de plataforma o desde el equipo del club indica el email; si la persona todavía no tiene cuenta, también indica su nombre. En el club se asignan además uno o más roles. Si no se define una contraseña inicial, se envía una invitación; si el admin define una, la cuenta nueva queda activa directamente.
2. **La cuenta nueva queda pendiente.** No puede iniciar sesión hasta aceptar la invitación. Sportiva envía un enlace por email; el admin puede reenviarlo mientras siga pendiente.
3. **La persona elige su contraseña.** Abre el enlace y envía el email invitado, el token y la contraseña nueva con su confirmación a `POST /api/auth/accept-invite`.
4. **La cuenta se activa.** Con una invitación vigente, el sistema confirma la aceptación y permite iniciar sesión. Si el enlace venció, no coincide con el email o ya se usó, responde con un error de validación.

Si la persona ya tiene cuenta, el admin del club la agrega usando el mismo email: se le asigna acceso y roles en ese club, sin crear otra cuenta ni mandarle una invitación nueva.

## Recuperar la contraseña

1. **La persona pide un enlace** desde la opción de recuperación. `POST /api/auth/forgot-password` responde siempre con el mismo mensaje (HTTP 202), exista o no la cuenta; solo se envía email a una cuenta activa. Hay un límite de tres solicitudes por minuto para la combinación de email e IP.
2. **El enlace dura 60 minutos.** Llega al frontend configurado y contiene el token y el email. La persona elige una contraseña que cumpla las mismas reglas que en el resto del sistema y la confirma en `POST /api/auth/reset-password`.
3. **Se cierran las sesiones API anteriores.** Si el token es válido, el cambio responde HTTP 204 y revoca todos los pases que tenía esa cuenta. Un enlace inválido, vencido o ya usado no cambia la contraseña.

Un administrador de plataforma también puede pedir el envío del enlace desde la ficha de un usuario activo. La respuesta no revela si se envió: las cuentas inactivas reciben la misma respuesta, pero no reciben un correo.

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

## Estado de la API (03/11/2026)

| Parte | Estado |
|---|---|
| Login, selección de club, pases, salir y vencimientos | Implementado en la API |
| Invitaciones y aceptación de cuenta | Implementado en la API; no hay registro público |
| Recuperación de contraseña | Implementada en la API |
| Integración de las pantallas del frontend con estos endpoints | Verificar en el repo Front-Sportiva |
