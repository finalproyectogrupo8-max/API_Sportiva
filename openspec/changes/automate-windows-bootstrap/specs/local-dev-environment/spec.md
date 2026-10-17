# Spec Delta

## ADDED Requirements

### Requirement: El bootstrap eleva permisos solo para activar WSL
El bootstrap de Windows SHALL correr como el usuario y SHALL pedir permisos de administrador únicamente para activar WSL. Docker Desktop, la distro, el usuario de Linux, git y VS Code SHALL quedar instalados y configurados para el usuario que corrió el bootstrap.

#### Scenario: Máquina sin WSL, script corrido como usuario normal
- **WHEN** un dev corre el bootstrap sin permisos de admin en una máquina sin WSL
- **THEN** el bootstrap pide elevación (UAC) solo para activar WSL y continúa como el usuario

#### Scenario: Script corrido entero como administrador
- **WHEN** un dev corre el bootstrap desde una consola de administrador
- **THEN** el bootstrap avisa que conviene correrlo como usuario normal, porque la distro y Docker quedarían configurados para el contexto de administrador

### Requirement: El bootstrap se reanuda después de un reinicio
Si activar WSL requiere reiniciar Windows, el bootstrap SHALL ofrecer reiniciar y SHALL dejarse programado para volver a ejecutarse al iniciar sesión, con confirmación del usuario.

#### Scenario: Activar WSL pide reinicio
- **WHEN** la activación de WSL indica que hace falta reiniciar y el dev acepta
- **THEN** Windows se reinicia y, al iniciar sesión, el bootstrap continúa desde donde quedó

### Requirement: La distro queda lista sin pasos manuales
El bootstrap SHALL instalar la distro sin la ventana de primer uso, SHALL crear un usuario de Linux con el nombre y la contraseña que ingresa el dev, y SHALL dejarlo como usuario por defecto. La contraseña no SHALL aparecer en la línea de comandos de ningún proceso ni en el log.

#### Scenario: Primera instalación de Ubuntu
- **WHEN** la distro no existe y el dev ingresa un usuario y una contraseña
- **THEN** la distro queda instalada, `whoami` dentro de ella devuelve ese usuario y el usuario puede usar `sudo`

### Requirement: El motor de Docker queda corriendo
El bootstrap SHALL instalar Docker Desktop por usuario y con la licencia aceptada en la instalación. SHALL arrancarlo si no está corriendo y SHALL esperar a que el motor responda antes de seguir.

#### Scenario: Docker Desktop instalado pero cerrado
- **WHEN** Docker Desktop está instalado y el motor no responde
- **THEN** el bootstrap lo arranca y espera a que `docker info` responda, sin que el dev tenga que abrirlo

### Requirement: La integración de Docker con WSL se repara sola
Si `docker` no funciona dentro de la distro, el bootstrap SHALL intentar repararlo antes de pedir pasos manuales: dejar la distro como predeterminada, reiniciarla y reiniciar Docker Desktop.

#### Scenario: La distro no ve el comando docker
- **WHEN** Docker Desktop corre pero `docker` no está disponible dentro de la distro
- **THEN** el bootstrap aplica las reparaciones automáticas, vuelve a verificar y solo si sigue fallando muestra los pasos manuales

### Requirement: VS Code queda listo para trabajar en WSL
El bootstrap SHALL asegurar que VS Code tenga la extensión WSL instalada antes de abrir el proyecto. El repo SHALL recomendar las extensiones de trabajo mediante `.vscode/extensions.json`.

#### Scenario: VS Code sin la extensión WSL
- **WHEN** VS Code está instalado sin la extensión WSL
- **THEN** el bootstrap la instala y abre el proyecto en modo Remote WSL

### Requirement: Modo de simulación y log de cada corrida
El bootstrap SHALL ofrecer un modo `-WhatIf` que informe qué instalaría o cambiaría sin modificar nada. Cada corrida SHALL dejar un log en `%TEMP%`, sin contraseñas ni tokens.

#### Scenario: Simulación en una máquina ya configurada
- **WHEN** un dev corre el bootstrap con `-WhatIf` en una máquina que ya tiene todo instalado
- **THEN** el bootstrap informa que no hay nada para instalar y no modifica nada

#### Scenario: Diagnóstico de una falla
- **WHEN** una corrida falla
- **THEN** el mensaje final indica la ruta del log de esa corrida
