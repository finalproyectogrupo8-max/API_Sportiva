# Design

## Context

- `scripts/setup-windows.ps1` hoy se **detiene** en cada falta y le pide al dev que la resuelva a mano:
  - sin WSL: "instalalo como admin";
  - después de instalar Docker: "abrilo y aceptá los términos";
  - sin integración: "activala en Settings".
- Solo instala WSL si se lo corre como admin.
- En la primera prueba real en una máquina limpia, el dev lo corrió como admin y aun así tuvo que hacer cuatro pasos a mano (ver la proposal). La hipótesis es que correr **todo** como admin configura WSL y Docker en un contexto distinto al del usuario. Además, ese día `irm | iex` estaba roto por el BOM.
- Lo verificado en la documentación oficial (2026-10-16):
  - el instalador de Docker Desktop acepta `install --user --quiet --accept-license --backend=wsl-2`, y **en modo `--user` no requiere admin**;
  - activar WSL 2 por primera vez requiere admin, una vez por máquina;
  - la CLI `docker desktop start|restart|status` existe desde Docker Desktop 4.37;
  - la integración de Docker con WSL se activa por defecto en la distro **predeterminada** (`wsl --set-default`).
- Restricción: el script tiene que seguir siendo ASCII, sin BOM, y ejecutable con `irm | iex`. La CI lo verifica.

## Goals / Non-Goals

**Goals:**
- Que en una máquina limpia, el único trabajo manual sea **confirmar**, el UAC una vez, el reinicio si Windows lo pide, y elegir el usuario y la contraseña de Linux.
- Que cada falla deje un log útil.

**Non-Goals:**
- Tocar la configuración interna de Docker Desktop (`settings-store.json`): no está documentada y cambia entre versiones.
- Probar los caminos de instalación en la CI: los runners de Windows no tienen virtualización anidada.
- Soportar Windows sin winget.

## Decisions

### D1. Elevación mínima: un proceso hijo elevado solo para `wsl --install --no-distribution`
- Si falta WSL, el script lanza `Start-Process wsl.exe -ArgumentList '--install','--no-distribution' -Verb RunAs -Wait`, que es un único UAC. Todo lo demás sigue en el proceso del usuario.
- Si el script detecta que corre como admin, lo advierte y pide confirmación para seguir. No lo bloquea, porque en algunas máquinas el usuario **es** admin sin consola elevada separada.
- **Alternativa:** relanzar todo el script elevado. Es lo que probablemente falló en la prueba real, porque instala la distro y Docker en otro contexto.

### D2. Reanudación tras reinicio con `RunOnce` del usuario
- La señal de que hace falta reiniciar es el exit code de `wsl --install`, o que `wsl --status` siga sin funcionar después de instalar.
- Con confirmación, el script escribe `HKCU:\Software\Microsoft\Windows\CurrentVersion\RunOnce\ApiSportivaSetup`, que apunta a:
  - **si se corrió desde un archivo:** `powershell -ExecutionPolicy Bypass -File <ruta> <mismos parámetros>`;
  - **si se corrió con `irm`:** `powershell -NoExit -Command "<irm url | iex>"`, porque no hay archivo para relanzar.
- Después ofrece reiniciar, con `Restart-Computer` y confirmación.
- Es `HKCU` y no `HKLM`, así que no requiere admin. Windows borra `RunOnce` solo, después de ejecutarlo una vez.
- **Alternativa:** una tarea programada. Es más pesada y requiere más permisos.

### D3. Distro sin la ventana de primer uso y usuario creado por root
1. `wsl --install -d Ubuntu --no-launch` instala la distro sin abrir la ventana que pide usuario y contraseña.
2. El usuario se pide con `Read-Host`, y la sugerencia es el nombre de Windows normalizado a `^[a-z_][a-z0-9_-]{0,31}$`. La contraseña se pide dos veces con `Read-Host -AsSecureString`.
3. Como root, sin pedir contraseña:
   - `useradd -m -s /bin/bash -G sudo <user>`;
   - `chpasswd`, con `<user>:<pass>` por **stdin**, nunca como argumento, así no queda visible en la lista de procesos ni en el log;
   - `/etc/wsl.conf` con `[user] default=<user>`.
4. `wsl --terminate` para que tome el usuario por defecto.
- **Alternativa:** dejar que abra la ventana de primer uso. Es el comportamiento estándar, pero el dev tiene que escribir `exit` para volver, y el script pierde el control del flujo.

### D4. Docker Desktop por usuario, con licencia aceptada, arranque y espera
- `winget install -e --id Docker.DockerDesktop --override "install --user --quiet --accept-license --backend=wsl-2"`.
  - `--override` reemplaza los argumentos por defecto del instalador. Hay que verificar al implementar que winget lo respete para este paquete. Si no, el plan B es descargar `Docker Desktop Installer.exe` de la URL oficial y ejecutarlo con esos argumentos.
- Para arrancarlo: `docker desktop start` si la CLI existe; si no, `Start-Process "Docker Desktop.exe"`. Después espera hasta que `docker info` responda, con hasta 5 minutos de timeout, porque el primer arranque crea la VM.
- **Alternativa:** la instalación `--all-users`, que es la default. Requiere admin y vuelve a abrir la puerta al problema del contexto.

### D5. Reparación de la integración WSL, en escalones
1. Si la distro no es la predeterminada → `wsl --set-default <distro>`, con confirmación, porque es una preferencia del usuario.
2. `wsl --terminate <distro>` y se vuelve a verificar. Es el paso que el dev tuvo que hacer a mano.
3. `docker desktop restart` (o cerrar y abrir Docker Desktop), espera al motor y se vuelve a verificar.
4. Solo si los tres fallan: instrucciones para activar el toggle en Settings, y la ruta del log.

### D6. VS Code y extensiones
- Si `code` no está disponible → se ofrece `winget install -e --id Microsoft.VisualStudioCode`.
- Si `code --list-extensions` no incluye `ms-vscode-remote.remote-wsl` → `code --install-extension ms-vscode-remote.remote-wsl`.
- `.vscode/extensions.json` en el repo recomienda las extensiones de trabajo, que VS Code ofrece instalar **dentro de WSL** al abrir el proyecto. Los IDs exactos se verifican al implementar.

### D7. `-WhatIf` y log
- `-WhatIf` corre todas las detecciones y, en lugar de instalar o configurar, imprime `[WhatIf] instalaría/configuraría ...`. Sirve para probar el script en una máquina ya configurada sin riesgo.
- `Start-Transcript` en `%TEMP%\api-sportiva-setup-<fecha>.log`. Las contraseñas nunca se escriben con `Write-Host`, y viajan solo por stdin.

## Risks / Trade-offs

- **Los caminos de instalación no tienen CI.** Si se rompen, se descubre en la máquina de un dev → `-WhatIf` para probar las detecciones, más una matriz de prueba manual en `tasks.md`. Cada camino que no se pueda probar se reporta como NOT RUN.
- **`--override` de winget.** Si el paquete no lo respeta, Docker queda instalado para todos los usuarios o con la ventana de términos → se verifica al implementar, con el plan B de D4.
- **Crear el usuario de Linux por script es menos estándar que la ventana de primer uso** → se usan comandos estándar (`useradd`, `chpasswd`) y se verifica con `whoami` y `sudo -n true`.
- **`RunOnce` y la ejecución de un script remoto en modo `irm`.** Al reiniciar se vuelve a bajar `main` → se documenta, y se puede fijar un tag en la URL.
- **La hipótesis sobre "como admin falló igual" no está confirmada** → el log de la próxima corrida lo va a confirmar o descartar.

## Migration Plan

No aplica: solo cambia el bootstrap. Las máquinas ya configuradas no se tocan (todas las detecciones son idempotentes).

## Open Questions

- Los IDs exactos de las extensiones recomendadas y el comportamiento de `--override` en winget. Se verifican al implementar y no cambian el diseño.

## Ajustes durante la implementación

Salieron de las pruebas en vivo y de la revisión de seguridad independiente:

- **D2, reanudación:**
  - en modo `irm`, `RunOnce` no ejecuta `irm | iex`: el script guarda una **copia local** en `%LOCALAPPDATA%\ApiSportiva\`, que se baja de `main` en ese momento, y `RunOnce` la ejecuta con `-File`. Así el comando es corto (156 caracteres medidos; `RunOnce` tiene límite de largo) y después del reinicio no se baja código remoto;
  - la entrada y la copia se **borran al final de una corrida exitosa**, para que un reinicio rechazado no deje una copia vieja esperando el próximo inicio de sesión;
  - los parámetros se relanzan con comillas dobles, porque con `-File` las comillas simples no se interpretan. Se rechazan los valores con comillas dobles o con una barra invertida al final.
- **D3, usuario de Linux:**
  - con `wsl --exec`, el `PATH` no incluye `/usr/sbin`: `useradd`, `usermod`, `userdel` y `chpasswd` van con ruta absoluta. Bug encontrado en la prueba en vivo;
  - Windows PowerShell 5.1 antepone un **BOM** y usa CRLF al escribir en el stdin de un ejecutable nativo, aunque `$OutputEncoding` sea UTF-8 sin BOM. `chpasswd` recibía `<BOM>devtest`. `Send-ToRootStdin` los limpia del lado de Linux con `sed` y `tr`. Bug encontrado en la prueba en vivo;
  - si `chpasswd` falla, el usuario recién creado se borra (`userdel -r`) y no se toca `wsl.conf`. Si el usuario ya existe, se reutiliza. `wsl.conf` se actualiza sin duplicar `[user]`;
  - el nombre de usuario se valida con `-cmatch`, que distingue mayúsculas.
- **D1, elevación:** `Start-Process` usa la ruta completa del `wsl.exe` del sistema (`System32`, o `Sysnative` en PowerShell de 32 bits), para que un `wsl.exe` en la carpeta actual no pueda tomar su lugar en el UAC.
- **`-Yes`** no acepta solo ni el reinicio ni seguir como administrador. El reinicio se hace sin `-Force`, para no cerrar apps con trabajo sin guardar.
- **Parámetros:** `-RepoUrl` y `-Distro` se validan con una lista blanca, y `-RepoUrl` se pasa a bash con `ConvertTo-BashLiteral` (antes se podía inyectar un comando).
- **D4, plan B de Docker:** la verificación compara el campo `O=` exacto del certificado, y el instalador se descarga con un nombre aleatorio.
- **`sudo apt-get`** corre con la consola heredada (`Invoke-InWslInteractive`), porque necesita una terminal para pedir la contraseña.
- **CI:** el chequeo del script también falla con caracteres de control. Un `\b` mal escapado en Python dejó un backspace dentro de una ruta: el script parseaba bien, pero la ruta quedaba rota.
- **D5, reparación de la integración, verificada en un caso real** (2026-10-16). Después de registrar y borrar una distro de prueba, Docker perdió la integración con `Ubuntu`. El log de Docker decía *"waiting 1m0s for the backend socket"*: la carpeta compartida `/mnt/wsl` quedó desincronizada.
  - La primera versión fallaba: `docker desktop restart` devuelve el control antes de que el motor se reinicie, y el script miraba una sola vez a los 5 s.
  - Cambios:
    - cada reparación **espera consultando cada 3 s** (30 s después de reiniciar la distro, 90 s después de reiniciar Docker);
    - se dan 15 s de margen antes de esperar al motor nuevo;
    - se agregó un **tercer escalón**, `wsl --shutdown` más el reinicio de Docker, para cuando `/mnt/wsl` quedó desincronizada;
    - cada cambio se anuncia (`-> ...`) también con `-Yes`, así queda en el log.
  - Con eso, el script real reparó la integración y siguió solo hasta el final (exit 0).
