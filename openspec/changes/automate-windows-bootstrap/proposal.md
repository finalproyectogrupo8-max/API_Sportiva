# Proposal

## Why

La primera instalación en una máquina limpia (2026-10-16) necesitó varios pasos a mano, aunque el script se corrió como administrador:

- instalar WSL;
- abrir Docker Desktop para que arranque el motor;
- correr `wsl --terminate Ubuntu` para que Ubuntu viera el comando `docker`;
- instalar la extensión WSL de VS Code.

Además, ese mismo día `irm | iex` falló por un BOM en el script (ya corregido en `fix/setup-irm-bom`). El objetivo de "setup en un comando" no se cumple en el caso que más importa: un dev nuevo con una máquina sin nada instalado.

## What Changes

- **WSL:**
  - el script eleva permisos **solo** para activar WSL (`wsl --install --no-distribution`), que es el único paso que requiere admin y se hace una vez por máquina;
  - todo lo demás corre como el usuario, así WSL, Ubuntu y Docker quedan instalados para la persona correcta;
  - si se corre el script entero como administrador, avisa y recomienda correrlo como usuario normal.
- **Reinicio:** si Windows lo necesita, el script ofrece reiniciar y **se vuelve a ejecutar solo** al iniciar sesión (una entrada `RunOnce` del usuario).
- **Ubuntu:** se instala sin abrir la ventana de primer uso. El script crea el usuario de Linux pidiendo nombre y contraseña, con el nombre de Windows como sugerencia, y lo deja como usuario por defecto.
- **Docker Desktop:**
  - se instala por usuario, sin admin (`--user --quiet --accept-license --backend=wsl-2`), así no hay que aceptar los términos al primer arranque;
  - el script lo arranca y espera a que el motor responda.
- **Integración de Docker con WSL:** antes de pedir pasos manuales, el script la repara solo:
  1. deja Ubuntu como distro por defecto;
  2. `wsl --terminate`;
  3. reinicia Docker Desktop;
  4. solo si sigue fallando, da instrucciones.
- **Git for Windows:** si falta, el script ofrece instalarlo con winget. Hace falta para el login con GitHub (Git Credential Manager).
- **VS Code:** instala la extensión WSL (`ms-vscode-remote.remote-wsl`), y si VS Code no está instalado, ofrece instalarlo con winget. El repo agrega `.vscode/extensions.json` con extensiones recomendadas para trabajar dentro de WSL.
- **Diagnóstico:**
  - el modo `-WhatIf` muestra qué haría el script sin cambiar nada;
  - cada corrida deja un log en `%TEMP%` para poder diagnosticar fallas como la de "como admin falló igual".
- **Lo que no cambia:** toda instalación o cambio de configuración global sigue pidiendo confirmación, salvo con `-Yes`.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `local-dev-environment`: se agregan requisitos sobre el bootstrap de Windows en una máquina limpia (elevación mínima, reanudación tras reinicio, arranque de Docker, reparación de la integración WSL, VS Code, `-WhatIf` y log). El requisito "Setup en un solo comando" se mantiene sin cambios.

## Impact

- **Código:** `scripts/setup-windows.ps1`, que tiene bastante lógica nueva, y `.vscode/extensions.json`, que es nuevo.
- **Documentación:** `docs/desarrollo-local.md` (opción 1, requisitos previos y troubleshooting) y `README.md`.
- **CI:** el chequeo de parseo y codificación (`fix/setup-irm-bom`) cubre el script. Los caminos de instalación **no se pueden probar en la CI**: los runners de Windows de GitHub no tienen la virtualización anidada que necesitan WSL 2 y Docker.
- **Prerequisito:** el PR de `fix/setup-irm-bom` se mergea primero.
- **Fuera de alcance:**
  - editar a mano la configuración interna de Docker Desktop (`settings-store.json`), porque no está documentada;
  - macOS y Linux, donde `setup.sh` ya alcanza;
  - Windows sin winget (Windows 10 muy viejo): el script da instrucciones manuales.
