# Spec Delta

## Purpose

Verifica automáticamente, en cada PR y en cada push a `main`, que la imagen de desarrollo se construye y que los tests pasan contra MySQL. No publica artefactos ni usa permisos de escritura.

## ADDED Requirements

### Requirement: La CI corre en los PRs y en main
El pipeline SHALL ejecutarse en cada pull request y en cada push a `main`. SHALL fallar si el build de la imagen o algún test falla.

#### Scenario: Un PR rompe un test
- **WHEN** se abre un PR con un cambio que hace fallar un test
- **THEN** el check de la CI queda en rojo

#### Scenario: Push a main
- **WHEN** se hace un merge a `main`
- **THEN** el pipeline corre el build y los tests sobre ese commit

### Requirement: Los tests corren en la misma imagen que usa desarrollo
El pipeline SHALL construir la imagen de desarrollo desde el Dockerfile del repo y SHALL correr los tests dentro de ella, contra la misma versión de MySQL que el entorno local.

#### Scenario: Cambio en el Dockerfile
- **WHEN** un PR rompe el Dockerfile
- **THEN** la CI falla en el paso de build, antes de correr los tests

### Requirement: La CI no publica ni tiene permisos de escritura
El pipeline no SHALL publicar imágenes ni otros artefactos en registries. SHALL correr con permisos de solo lectura sobre el repo y sin secrets propios.

#### Scenario: PR desde un fork
- **WHEN** un PR que viene de un fork dispara la CI
- **THEN** el pipeline corre sin acceso a secrets y sin permisos de escritura
- **AND** no se publica ninguna imagen
