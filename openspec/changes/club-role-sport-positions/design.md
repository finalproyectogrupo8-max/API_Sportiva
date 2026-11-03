# Design

## Context

Ver [proposal.md](./proposal.md) para el motivo y el alcance. Los roles y permisos estándar se sincronizan desde `config/club-permissions.php` a cada base de club; las asignaciones de rol también están en esa base. El alcance `sport` ya limita datos asociados a categorías, pero las temporadas no tienen asociación a deporte. Los jugadores actualmente guardan `preferred_position` como texto libre. La base central ya admite más de una membresía por usuario, una por cada club.

## Goals / Non-Goals

**Goals:**

- Aplicar permisos mínimos en API y menú para `sport_coordinator`.
- Retirar las asignaciones estándar `coordinator` sin elevar ni reasignar accesos.
- Modelar posiciones reutilizables por deporte y asociar varias a cada jugador.
- Conservar los valores históricos de `preferred_position` y las membresías multiclub existentes.

**Non-Goals:**

- Cambiar la autenticación central, la conexión por base de club o el modelo de membresías.
- Convertir automáticamente texto libre histórico en posiciones estructuradas.
- Implementar permisos granulares por jugador más allá de las policies y alcances actuales.
- Cambiar el frontend dentro de este repositorio; el código de `Front-Sportiva` no está disponible en este workspace.

## Decisions

### Alcance del coordinador de deporte

`sport_coordinator` tendrá únicamente `seasons.view`, `categories.view` y `competitions.view`. Categorías y competencias usan alcance `sport`; temporadas se consultan a nivel del club porque el modelo no las vincula directamente con un deporte. No se asignarán permisos de escritura ni de otros módulos. El menú del frontend se deriva de estos permisos y debe mostrar solo Temporadas, Categorías y Competencias.

Se elige reducir la matriz de permisos en vez de agregar excepciones especiales a las policies: mantiene la autorización en el catálogo, `/api/me` y el middleware de permisos existentes, y niega acceso también si se invocan endpoints directamente.

### Retiro de `coordinator`

La sincronización de permisos eliminará el rol estándar obsoleto y las filas que lo asignan en cada base de club. No migrará a sus usuarios a `admin` ni a `sport_coordinator`; sus otros roles y membresías quedan intactos. Esto sigue la política de mínimo privilegio elegida y requiere que los administradores revisen y reasignen manualmente los accesos necesarios.

La alternativa de convertir automáticamente a los usuarios a otro rol se descarta porque elevaría o alteraría privilegios sin conocer sus responsabilidades actuales.

Las alertas generales conservarán al Administrador como destinatario, y las alertas de categoría seguirán llegando a profes asignados y coordinadores de deporte del deporte correspondiente. El código de destinatarios dejará de consultar `coordinator`.

### Catálogo de posiciones y relación con jugadores

Cada base de club tendrá una tabla `positions` con clave foránea a `sports`, nombre único dentro del deporte y estado activo/inactivo. El catálogo es administrable mediante permisos `positions.*`; el rol estándar `admin` los recibe por tener el catálogo completo, y ningún otro rol estándar los recibe. Roles custom podrán recibir los permisos que un actor autorizado pueda otorgar.

Una tabla pivote `player_positions` asociará jugadores con posiciones. La posición ya identifica su deporte, por lo que un jugador puede tener varias posiciones dentro del mismo deporte y posiciones de más de un deporte sin duplicar `sport_id` en la pivote. La API valida que las posiciones pertenezcan al club actual y estén activas al crear o reemplazar asociaciones. La selección de opciones se filtra por deporte.

Se elige catálogo por deporte y pivote relacional en vez de una tabla física por cada deporte o un campo de texto: los deportes son configurables por club y las posiciones deben ser consultables, validables y reutilizables.

Desactivar una posición no borra su fila ni sus asociaciones; deja de estar disponible para nuevas asignaciones, pero continúa apareciendo en los jugadores que ya la tienen. Esto evita pérdida de información y mantiene legibles los historiales.

### Compatibilidad con `preferred_position`

La migración no inferirá posiciones estructuradas desde `preferred_position`: el texto libre puede ser ambiguo, pertenecer a un catálogo no creado o carecer de deporte atribuible. El campo se conserva y se sigue exponiendo en el recurso durante esta transición; `position_ids` administra la relación nueva de forma independiente.

Se descarta eliminar el campo o convertir automáticamente sus valores para evitar pérdida de datos y asociaciones incorrectas. Una migración de esos valores requeriría un mapeo validado por club y queda fuera de este cambio.

### Membresías en varios clubes

No se modifica el esquema central: `tenant_memberships` ya identifica de forma única cada par usuario-club. Se preservarán las membresías independientes y la revocación de tokens de un club no afectará los tokens de otros; se añadirá o ajustará cobertura de regresión si falta.

### Frontend y contrato

Los cambios del menú y del formulario de jugador deben implementarse en el repositorio `Front-Sportiva`, no disponible en este workspace. El frontend debe consumir `permissions` y `permission_scopes` para el menú, y obtener las posiciones del deporte seleccionado. La API mantiene la autoridad: ocultar una solapa no reemplaza la autorización de cada endpoint.

## Risks / Trade-offs

- [Los usuarios que solo tenían `coordinator` perderán permisos al sincronizar] → Comunicar el cambio antes del deploy; el Administrador del club debe revisar y reasignar los accesos. No hay conversión automática.
- [Hay muchas referencias a `coordinator` en pruebas, alertas y documentación] → Buscar y actualizar todas las referencias funcionales; conservar datos históricos de auditoría o texto sin tratarlo como un rol asignable.
- [El texto `preferred_position` puede diferir del nuevo catálogo] → Mantenerlo sin conversión ni borrado; comunicar que las nuevas asociaciones son estructuradas.
- [Front-Sportiva no está presente para validar el menú y selector] → Incluir tareas explícitas de coordinación y pruebas en ese repositorio antes de considerar completo el cambio de producto.
- [Desactivar posiciones puede ocultarlas de formularios aunque existan asociaciones históricas] → Las respuestas de jugador incluirán posiciones inactivas ya asociadas; solo las consultas de opciones excluirán inactivas.

## Migration Plan

1. Publicar en el frontend el manejo de permisos y de posiciones antes o junto con el cambio de API.
2. Desplegar las migraciones tenant para crear el catálogo y la pivote; el dato `preferred_position` permanece intacto.
3. Sincronizar permisos y roles en cada base de club; retirar el rol `coordinator` y sus asignaciones sin reasignación.
4. Verificar los usuarios afectados y pedir al Administrador del club que reasigne roles cuando corresponda.
5. Si hay rollback, revertir el esquema de posiciones solo cuando no se hayan creado asociaciones nuevas. Restaurar los permisos de usuarios retirados requiere revisión y reasignación explícita; no debe hacerse una elevación automática.

## Open Questions

Ninguna que cambie los requisitos o el enfoque elegido.
