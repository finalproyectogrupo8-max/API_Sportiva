# club-inventory Specification

## Purpose

Controlar materiales y ubicaciones de cada club, manteniendo el stock a partir de un libro inmutable de movimientos.

## Requirements

### Requirement: Administrar materiales y ubicaciones

La API SHALL permitir administrar los catálogos de materiales y ubicaciones de almacenamiento del club mediante sus rutas de recursos. Los listados SHALL poder buscar y paginar; materiales también pueden declarar un stock mínimo.

#### Scenario: Buscar materiales

- **WHEN** un usuario autorizado busca materiales por nombre
- **THEN** recibe solo coincidencias del club actual en una lista paginada

#### Scenario: Desactivar una ubicación o material en uso

- **WHEN** un usuario autorizado da de baja un elemento del catálogo
- **THEN** queda fuera del listado ordinario sin afectar otros clubes

### Requirement: Consulta de saldos

La API SHALL exponer saldos paginados por material y ubicación, con filtros por `material_id`, `location_id` y `below_minimum`. El acceso SHALL requerir `inventory.view`.

#### Scenario: Filtrar saldos bajo el mínimo

- **WHEN** un usuario autorizado consulta `GET /api/inventory/balances?below_minimum=1`
- **THEN** recibe solo los saldos inferiores al mínimo configurado para su material

### Requirement: Registrar movimientos de inventario

Un usuario con `inventory.adjust` SHALL poder registrar ingresos, egresos y ajustes en `POST /api/inventory/movements`. Cada movimiento SHALL actualizar el saldo en la misma transacción; el libro MUST NOT ofrecer actualización ni borrado.

#### Scenario: Ingreso y egreso

- **WHEN** se registra un ingreso y luego un egreso válido del mismo material y ubicación
- **THEN** el saldo refleja ambas operaciones y cada una queda como movimiento independiente

#### Scenario: Ajuste con signo

- **WHEN** se registra un ajuste con cantidad positiva o negativa distinta de cero
- **THEN** el saldo aumenta o disminuye según el signo, sin quedar negativo

#### Scenario: Stock insuficiente

- **WHEN** un egreso o ajuste negativo excedería el stock disponible
- **THEN** responde 409 con `code: insufficient_stock` y no crea el movimiento

### Requirement: Transferir entre ubicaciones

Un usuario con `inventory.transfer` SHALL poder mover stock entre dos ubicaciones diferentes. La transferencia SHALL crear movimientos `transfer_out` y `transfer_in` vinculados por un identificador común, actualizar ambos saldos atómicamente y fallar sin cambios si no alcanza el stock.

#### Scenario: Transferencia válida

- **WHEN** se transfiere una cantidad disponible a otra ubicación
- **THEN** ambos saldos se actualizan y quedan registrados los dos movimientos

#### Scenario: Transferencia inválida

- **WHEN** el origen y el destino son iguales o el origen no tiene stock suficiente
- **THEN** la operación se rechaza sin aplicar parcialmente la transferencia

### Requirement: Aislamiento por club

Los saldos, ubicaciones y movimientos SHALL pertenecer a la base del club del token y MUST NOT ser visibles desde otro club.

#### Scenario: Consulta desde otro club

- **WHEN** un usuario consulta datos de inventario con un token de otro club
- **THEN** no obtiene los datos del club ajeno
