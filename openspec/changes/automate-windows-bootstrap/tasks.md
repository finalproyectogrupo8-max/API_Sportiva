# Tasks

## 1. Base: modo -WhatIf, log y advertencia de admin

- [x] 1.1 Agregar el parámetro `-WhatIf` (una función `Invoke-Change` que en simulación solo imprime), `Start-Transcript` en `%TEMP%` con la ruta en el mensaje final y en `Stop-WithHelp`, y la advertencia cuando el script corre como admin (D1, D7). Verificación: `-WhatIf` en la máquina del dueño (ya configurada) termina sin cambios y dice "nada para instalar". El log existe y no contiene contraseñas.

## 2. WSL y distro

- [ ] 2.1 Activar WSL con elevación mínima: un único proceso hijo con `-Verb RunAs` para `wsl --install --no-distribution` (D1). Verificación: `-WhatIf` en una máquina sin WSL informa la elevación. El camino real se prueba en la matriz manual (5.2).
- [ ] 2.2 Reanudación tras reinicio con `RunOnce` en HKCU, en los modos archivo e `irm`, más el reinicio con confirmación (D2). Verificación: la entrada se crea con el comando correcto en los dos modos (inspección del registro con `-WhatIf` desactivado en una prueba controlada) y se borra después de usarse.
- [x] 2.3 Instalar la distro con `--no-launch` y crear el usuario por root, con la contraseña por stdin y `/etc/wsl.conf` (D3). Verificación: en una distro de prueba descartable (`wsl --install -d Ubuntu-24.04 --no-launch`, que después se borra con `wsl --unregister`, con aprobación), `whoami` da el usuario creado y `sudo -n true` tiene los permisos esperados. La contraseña no aparece en el log ni en la línea de comandos.

## 3. Docker Desktop

- [ ] 3.1 Instalar por usuario con `--accept-license` mediante winget `--override`, con el plan B del instalador oficial (D4). Verificar que winget respete el override. Verificación: `winget show Docker.DockerDesktop` y `-WhatIf`. La instalación real queda para la matriz manual (5.2).
- [ ] 3.2 Arrancar Docker y esperar al motor: `docker desktop start` o el exe, con un timeout de 5 minutos (D4). Verificación: con Docker Desktop cerrado en la máquina del dueño, el script lo abre y sigue solo.
- [x] 3.3 Reparación escalonada de la integración WSL (D5). Verificación: simular la falla (`wsl --terminate` con Docker recién reiniciado o con la distro no predeterminada) y confirmar que el script lo resuelve sin pasos manuales.

## 4. VS Code y Git

- [ ] 4.1 Detectar e instalar Git for Windows y VS Code con winget (con confirmación), y la extensión `ms-vscode-remote.remote-wsl` (D6). Verificación: `code --list-extensions` la incluye después de la corrida.
- [ ] 4.2 Agregar `.vscode/extensions.json` con las extensiones recomendadas para trabajar dentro de WSL, con los IDs verificados en el Marketplace. Verificación: al abrir el proyecto en WSL, VS Code ofrece instalarlas.

## 5. Documentación y verificación

- [x] 5.1 Actualizar `docs/desarrollo-local.md` (Opción 1: qué hace solo, qué hay que confirmar, `-WhatIf` y el log; troubleshooting con el log) y `README.md`. Verificación: cada comando nuevo se ejecutó tal como está escrito.
- [ ] 5.2 Matriz de prueba manual, documentada en el reporte: máquina sin nada; WSL sin distro; Docker instalado pero cerrado; integración rota; todo instalado (idempotencia); modo `irm` y modo archivo. Verificación: cada caso se marca PASS, FAIL o NOT RUN, con la evidencia correspondiente.
- [ ] 5.3 Correr `openspec validate automate-windows-bootstrap --strict`, más el chequeo de la CI (parseo, ASCII y sin BOM) en local. Verificación: sin errores.
- [x] 5.4 Revisión independiente de seguridad: elevación, `RunOnce`, manejo de la contraseña y ejecución remota con `irm`. Verificación: los hallazgos quedan resueltos o registrados.

## Workflow follow-up

- Mergear antes el PR de `fix/setup-irm-bom`.
- Commit y PR de `feat/windows-bootstrap`, con la aprobación de un compañero (ruleset de `main`).
- Archivar con `/opsx:archive` después del merge.
