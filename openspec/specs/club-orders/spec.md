# club-orders Specification

## Purpose

Gestionar pedidos de indumentaria y materiales, sus aprobaciones, entregas e historial dentro de cada club.

## Requirements

### Requirement: Administrar el catálogo de indumentaria

La API SHALL permitir listar, crear, consultar, actualizar y dar de baja prendas mediante `/api/apparel-items`. Los listados SHALL paginar y admitir búsqueda y filtro por `is_active`; la baja SHALL ser lógica.

#### Scenario: Crear y buscar una prenda

- **WHEN** un usuario autorizado crea una prenda y luego la busca por nombre
- **THEN** la prenda aparece en el listado paginado con su estado

#### Scenario: Baja de una prenda

- **WHEN** un usuario autorizado elimina una prenda del catálogo
- **THEN** responde 204 y la prenda deja de aparecer en las consultas ordinarias

### Requirement: Crear y editar pedidos

La API SHALL permitir crear pedidos de tipo `apparel` o `material` con sus líneas correspondientes. Un pedido nuevo SHALL quedar en `draft`; solo los pedidos en borrador se pueden editar o borrar. Los listados SHALL respetar permisos, alcance y aislamiento del club.

#### Scenario: Pedido de indumentaria

- **WHEN** se crea un pedido `apparel` con líneas asociadas a jugadores, prendas y talles
- **THEN** responde 201 con el pedido en `draft` y sus líneas

#### Scenario: Editar un pedido enviado

- **WHEN** se intenta editar o borrar un pedido que ya no está en `draft`
- **THEN** responde 409 con `code: order_not_draft`

### Requirement: Transiciones del pedido

La API SHALL permitir enviar un borrador, aprobar o rechazar un pedido enviado y registrar entregas. Cada transición SHALL actualizar el historial en la misma operación. Rechazar SHALL requerir un motivo.

#### Scenario: Enviar, aprobar y entregar

- **WHEN** se envía y aprueba un pedido, y se registran entregas parciales o completas
- **THEN** el estado pasa por `submitted`, `approved`, `partially_delivered` y `delivered` según corresponda, con su historial

#### Scenario: Rechazo

- **WHEN** se rechaza un pedido enviado sin motivo
- **THEN** responde 422; con motivo, pasa a `rejected` y conserva el motivo en el historial

#### Scenario: Aprobar un pedido no enviado

- **WHEN** se intenta aprobar un pedido que no está en `submitted`
- **THEN** responde 409 con `code: order_not_submitted`

### Requirement: Entrega de materiales e indumentaria

La entrega de materiales SHALL descontar el inventario y registrar el movimiento asociado al pedido en la misma transacción. Las entregas MUST NOT superar lo pedido ni el stock disponible.

#### Scenario: Entrega de materiales

- **WHEN** se registra una entrega de materiales válida
- **THEN** disminuye el saldo, se registra el movimiento y aumenta la cantidad entregada de la línea

#### Scenario: Entrega superior a lo pedido o al stock

- **WHEN** la cantidad supera el remanente pedido o el stock disponible
- **THEN** la operación se rechaza sin registrar una entrega parcial inválida

### Requirement: Permisos y notificaciones

La autorización SHALL depender del tipo de pedido y del permiso del usuario. Un coordinador puede aprobar pedidos de indumentaria, pero no pedidos de materiales. Al aprobar o rechazar, el sistema SHALL notificar al solicitante.

#### Scenario: Coordinador aprueba indumentaria

- **WHEN** un coordinador aprueba un pedido de indumentaria enviado
- **THEN** se aprueba y se notifica al solicitante

#### Scenario: Coordinador intenta aprobar materiales

- **WHEN** un coordinador intenta aprobar un pedido de materiales
- **THEN** responde 403 y el estado no cambia
