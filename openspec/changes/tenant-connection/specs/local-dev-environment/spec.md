# Spec Delta

## ADDED Requirements

### Requirement: Bases de club en el MySQL local

El usuario de la aplicación SHALL poder crear, usar y borrar bases cuyo nombre empieza con `club_` en el MySQL local y en el de la CI, sin usar root. MUST NOT recibir permisos sobre otras bases fuera de la central y la de testing.

#### Scenario: Volumen nuevo

- **WHEN** se levanta el entorno con un volumen de MySQL vacío
- **THEN** el usuario de la aplicación puede ejecutar `CREATE DATABASE club_test_a` y `DROP DATABASE club_test_a`

#### Scenario: Base fuera del patrón

- **WHEN** el usuario de la aplicación intenta crear una base llamada `otra_base`
- **THEN** MySQL lo rechaza por falta de permisos

#### Scenario: Volumen creado antes de este cambio

- **WHEN** un dev tiene un volumen creado antes del cambio y corre los tests de club
- **THEN** el error indica que falta el permiso y la documentación explica cómo recrear el volumen, avisando que borra los datos locales
