# Spec Delta

## Purpose

Define cómo los administradores de plataforma asocian usuarios con clubes y gestionan el rol y el estado de cada membresía.

## ADDED Requirements

### Requirement: Asociar un usuario a un club
`POST /api/admin/tenants/{tenant}/memberships` SHALL crear una membresía para un usuario existente con el rol inicial válido `admin`; cada usuario MUST NOT tener más de una membresía por club.

#### Scenario: Asociación válida
- **WHEN** un administrador envía el `user_id` de un usuario existente para un club activo
- **THEN** responde 201 con la membresía activa y su rol

#### Scenario: Membresía duplicada
- **WHEN** el usuario ya tiene una membresía en ese club
- **THEN** responde 422 y no crea otra membresía

#### Scenario: Rol no permitido
- **WHEN** el request envía un rol distinto de `admin`
- **THEN** responde 422

#### Scenario: Club dado de baja
- **WHEN** se intenta crear una membresía para un club dado de baja
- **THEN** responde 404

### Requirement: Modificar una membresía
`PATCH /api/admin/tenants/{tenant}/memberships/{membership}` SHALL permitir cambiar el rol o `is_active` de la membresía del club indicado; no SHALL permitir cambiar el usuario ni el club asociados.

#### Scenario: Cambiar el rol
- **WHEN** un administrador cambia el rol de una membresía por un rol válido
- **THEN** responde 200 con la membresía actualizada

#### Scenario: Membresía de otro club
- **WHEN** la membresía pertenece a un club distinto del indicado en la URL
- **THEN** responde 404

#### Scenario: Intento de cambiar usuario o club
- **WHEN** el request incluye `user_id` o `tenant_id`
- **THEN** responde 422 y la asociación permanece sin cambios

### Requirement: Revocación al desactivar una membresía
Desactivar una membresía SHALL revocar los tokens del usuario asociados al club de esa membresía, sin revocar tokens del mismo usuario asociados a otros clubes.

#### Scenario: Token del club desactivado
- **WHEN** se desactiva una membresía cuyo usuario tiene un token para ese club
- **THEN** el token deja de autenticar requests y responde 401

#### Scenario: Token de otro club
- **WHEN** se desactiva una membresía y el usuario tiene un token para otro club
- **THEN** ese token del otro club continúa siendo válido
