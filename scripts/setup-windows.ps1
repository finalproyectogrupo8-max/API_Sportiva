<#
.SYNOPSIS
    Bootstrap del entorno de desarrollo de la API Sportiva en Windows (WSL 2 + Docker Desktop).

.DESCRIPTION
    Deja una maquina Windows lista para trabajar, desde cero o a medio configurar:
      1. WSL 2: lo activa si falta (unico paso con permisos de admin: un aviso UAC). Si Windows
         necesita reiniciar, ofrece hacerlo y el script se vuelve a ejecutar solo al iniciar sesion.
      2. Distro (Ubuntu): la instala sin la ventana de primer uso y crea tu usuario de Linux.
      3. Docker Desktop: lo instala por usuario (sin admin, licencia aceptada), lo arranca y
         espera al motor. Si la distro no ve "docker", repara la integracion WSL sola.
      4. Git en WSL: acceso al repo (Git Credential Manager de Windows) e identidad (email
         noreply de GitHub).
      5. Clona el repo DENTRO de WSL y corre scripts/setup.sh.
      6. VS Code: instala la extension WSL y abre el proyecto.

    Todo es idempotente: se puede volver a correr. Toda instalacion o cambio de configuracion
    pide confirmacion, salvo con -Yes. Con -WhatIf solo informa que haria, sin cambiar nada.
    Cada corrida deja un log en %TEMP% (sin contrasenas).

    Correrlo como usuario normal, NO como administrador: si todo corre elevado, WSL y Docker
    pueden quedar configurados para otro contexto. El script pide elevacion solo cuando hace falta.

    El archivo es ASCII a proposito y SIN BOM: Windows PowerShell 5.1 lee los .ps1 sin BOM como
    ANSI (con ASCII da igual), y con "irm <url> | iex" un BOM queda pegado delante de "<#" y rompe
    el parseo de todo el script. La CI lo verifica (paso "Script de setup ejecutable con irm").

.EXAMPLE
    irm https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1 | iex

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1 -WhatIf
#>
[CmdletBinding(SupportsShouldProcess)]
param(
    [ValidatePattern('^[A-Za-z0-9._-]+$')]
    [string]$Distro = 'Ubuntu',
    # URL o ruta del repo. Sin comillas, espacios ni metacaracteres de shell (se pasa a bash).
    [ValidatePattern('^[A-Za-z0-9@:/._~+-]+$')]
    [string]$RepoUrl = 'https://github.com/finalproyectogrupo8-max/API_Sportiva.git',
    # Ruta dentro de WSL, relativa a $HOME. Sin espacios (se pasa a bash).
    [ValidatePattern('^[A-Za-z0-9._/-]+$')]
    [string]$TargetDir = 'repos/api-sportiva',
    # Usuario de GitHub para la identidad de git en WSL (si falta). Sin el, se pregunta.
    [ValidatePattern('^$|^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$')]
    [string]$GitHubUser = '',
    [switch]$Yes,
    [switch]$NoOpen
)

$ErrorActionPreference = 'Stop'
# Hace que wsl.exe escriba UTF-8 en lugar de UTF-16 (si no, los nombres de las distros llegan con nulos).
$env:WSL_UTF8 = '1'

$ScriptUrl = 'https://raw.githubusercontent.com/finalproyectogrupo8-max/API_Sportiva/main/scripts/setup-windows.ps1'
$DockerInstallerUrl = 'https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe'
$DockerInstallArgs = 'install --user --quiet --accept-license --backend=wsl-2'
$VsCodeWslExtension = 'ms-vscode-remote.remote-wsl'
$DryRun = [bool]$WhatIfPreference
$script:Plan = New-Object System.Collections.Generic.List[string]

$LogPath = Join-Path $env:TEMP ('api-sportiva-setup-{0:yyyyMMdd-HHmmss}.log' -f (Get-Date))
try { Start-Transcript -Path $LogPath -WhatIf:$false | Out-Null } catch { $LogPath = $null }

# ---------------------------------------------------------------------------
# Utilidades
# ---------------------------------------------------------------------------
function Write-Step([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }
function Write-Ok([string]$Message) { Write-Host "    OK: $Message" -ForegroundColor Green }
function Write-Note([string]$Message) { Write-Host "    $Message" }

function Stop-Log {
    try { Stop-Transcript | Out-Null } catch { }
}

function Stop-WithHelp([string]$Message) {
    Write-Host "`n$Message" -ForegroundColor Yellow
    Write-Host "Cuando este resuelto, volve a correr este script." -ForegroundColor Yellow
    if ($LogPath) { Write-Host "Log de esta corrida: $LogPath" -ForegroundColor Yellow }
    Stop-Log
    # Desde un archivo (-File), exit 1 termina el script con codigo de error.
    # Desde "irm <url> | iex" no hay archivo ($PSCommandPath vacio): exit cerraria la ventana de
    # PowerShell del usuario antes de que lea las instrucciones, asi que se corta con throw.
    if ($PSCommandPath) { exit 1 }
    throw 'Setup detenido (ver el mensaje de arriba).'
}

# -AlwaysAsk: se pregunta aunque se haya pasado -Yes (reiniciar Windows, seguir como admin).
function Confirm-Action([string]$Question, [switch]$AlwaysAsk) {
    if ($Yes -and -not $AlwaysAsk) { return $true }
    $answer = Read-Host "$Question [s/N]"
    return ($answer -match '^(s|si|y|yes)$')
}

# Un cambio en el sistema: en -WhatIf se anota en el plan; si no, se pide confirmacion y se ejecuta.
# Devuelve 'whatif', 'done' o 'declined'.
function Request-Change([string]$Description, [scriptblock]$Action) {
    if ($DryRun) {
        $script:Plan.Add($Description)
        Write-Host "    [WhatIf] $Description" -ForegroundColor Magenta
        return 'whatif'
    }
    if (-not (Confirm-Action "$Description?")) { return 'declined' }
    # Se anuncia siempre (tambien con -Yes): queda en pantalla y en el log que se cambio.
    Write-Host "    -> $Description" -ForegroundColor Cyan
    & $Action
    return 'done'
}

function Test-IsAdmin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    return (New-Object Security.Principal.WindowsPrincipal $identity).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Test-Command([string]$Name) {
    return $null -ne (Get-Command $Name -ErrorAction SilentlyContinue)
}

# Corre un ejecutable nativo descartando stderr sin que ErrorActionPreference=Stop lo convierta en error.
function Invoke-Native([scriptblock]$Command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { $output = & $Command 2>$null } finally { $ErrorActionPreference = $previous }
    return [pscustomobject]@{ Output = (($output | Out-String).Trim()); ExitCode = $LASTEXITCODE }
}

# Ejecuta un comando bash dentro de la distro y devuelve SOLO el exit code.
# La salida va a la consola con Out-Host: sin eso, PowerShell la incluiria en el valor de retorno
# de la funcion y el chequeo del exit code daria siempre "fallo".
function Invoke-InWsl([string]$Command) {
    & wsl.exe -d $Distro -- bash -lc $Command | Out-Host
    return $LASTEXITCODE
}

# Igual que Invoke-InWsl pero captura la salida (para leer valores). Devuelve $null si falla.
function Get-FromWsl([string]$Command) {
    # El stderr se descarta dentro de bash: en Windows PowerShell 5.1, redirigir el stderr de un
    # ejecutable nativo con ErrorActionPreference=Stop lo convierte en un error terminante.
    $output = & wsl.exe -d $Distro -- bash -lc "{ $Command ; } 2>/dev/null"
    if ($LASTEXITCODE -ne 0) { return $null }
    return (($output | Out-String).Trim())
}

# Para pasar un valor a bash entre comillas simples: ' -> '\''
function ConvertTo-BashLiteral([string]$Value) {
    return "'" + ($Value -replace "'", "'\''") + "'"
}

# C:\Program Files\x -> /mnt/c/Program Files/x
function ConvertTo-WslPath([string]$WindowsPath) {
    return '/mnt/' + $WindowsPath.Substring(0, 1).ToLower() + ($WindowsPath.Substring(2) -replace '\\', '/')
}

# Los parametros con los que se corrio el script, para relanzarlo igual (tras un reinicio).
# Se relanza siempre con -File, donde las comillas simples NO se interpretan: van comillas dobles.
# Ningun parametro valido lleva comillas dobles (TargetDir y GitHubUser estan validados; Distro y
# RepoUrl no las usan), y si alguno las tuviera se rechaza en lugar de armar un comando roto.
function Get-ArgumentString([System.Collections.IDictionary]$BoundParameters) {
    $parts = foreach ($entry in $BoundParameters.GetEnumerator()) {
        if ($entry.Key -eq 'WhatIf') { continue }
        if ($entry.Value -is [System.Management.Automation.SwitchParameter]) {
            if ($entry.Value.IsPresent) { "-$($entry.Key)" }
        } else {
            $value = [string]$entry.Value
            if ($value.Contains('"')) { throw "El parametro -$($entry.Key) no puede contener comillas dobles." }
            # Una barra invertida final escaparia la comilla de cierre en la linea de comandos de Windows.
            if ($value.EndsWith([string][char]92)) { throw "El parametro -$($entry.Key) no puede terminar en barra invertida." }
            "-$($entry.Key) `"$value`""
        }
    }
    return ($parts -join ' ')
}
$ScriptArguments = Get-ArgumentString $PSBoundParameters

# ---------------------------------------------------------------------------
# WSL
# ---------------------------------------------------------------------------
# wsl.exe de System32. En PowerShell de 32 bits sobre Windows de 64 bits, System32 se redirige a SysWOW64
# (donde no hay wsl.exe): el real se ve en Sysnative.
function Get-SystemWslPath {
    if (-not [Environment]::Is64BitProcess -and [Environment]::Is64BitOperatingSystem) {
        return (Join-Path $env:SystemRoot 'Sysnative\wsl.exe')
    }
    return (Join-Path $env:SystemRoot 'System32\wsl.exe')
}

# Program Files de 64 bits aunque el proceso sea de 32 (donde ProgramFiles apunta a "Program Files (x86)").
function Get-ProgramFiles64 {
    if ($env:ProgramW6432) { return $env:ProgramW6432 }
    return $env:ProgramFiles
}

# Corre un comando en la distro con la consola heredada (sin redirigir la salida), para que lo que
# necesita una terminal (por ejemplo, sudo pidiendo la contrasena) funcione. Devuelve el exit code.
function Invoke-InWslInteractive([string]$Command) {
    $process = Start-Process -FilePath 'wsl.exe' -ArgumentList "-d $Distro -- bash -lc `"$Command`"" -NoNewWindow -Wait -PassThru
    return $process.ExitCode
}

# WSL de la Store (wsl --version) o WSL del sistema activo (wsl --status). Sin WSL, los dos fallan.
function Test-WslReady {
    if (-not (Test-Command 'wsl.exe')) { return $false }
    if ((Invoke-Native { wsl.exe --version }).ExitCode -eq 0) { return $true }
    return ((Invoke-Native { wsl.exe --status }).ExitCode -eq 0)
}

function Get-WslDistros {
    $result = Invoke-Native { wsl.exe -l -q }
    if ($result.ExitCode -ne 0) { return @() }
    return @($result.Output -split "`r?`n" | ForEach-Object { ($_ -replace "`0", '').Trim() } | Where-Object { $_ })
}

function Get-WslDefaultDistro {
    $result = Invoke-Native { wsl.exe -l -v }
    $line = ($result.Output -split "`r?`n" | ForEach-Object { $_ -replace "`0", '' }) | Where-Object { $_ -match '^\s*\*' } | Select-Object -First 1
    if ($line -and $line -match '^\s*\*\s*(\S+)') { return $Matches[1] }
    return $null
}

# Programa el script para que se vuelva a ejecutar al iniciar sesion (RunOnce de HKCU: no requiere admin
# y Windows lo borra despues de usarlo). Desde un archivo relanza el archivo; desde irm, vuelve a bajarlo.
# Desde un archivo, RunOnce relanza ese archivo. Desde "irm | iex" no hay archivo: se guarda una copia
# local del script (se baja de nuevo de main en ese momento, segundos despues de haberlo ejecutado) y
# RunOnce relanza esa copia. Asi el comando es corto (RunOnce tiene limite de largo) y despues del
# reinicio no se baja codigo remoto. Al terminar bien, Clear-ResumeAfterReboot borra la entrada y la copia.
function Get-ResumeCommand([string]$ScriptPath) {
    return "powershell.exe -NoExit -ExecutionPolicy Bypass -File `"$ScriptPath`" $ScriptArguments".TrimEnd()
}

function Get-ResumeScriptPath {
    if ($PSCommandPath) { return $PSCommandPath }
    $folder = Join-Path $env:LOCALAPPDATA 'ApiSportiva'
    New-Item -ItemType Directory -Path $folder -Force -WhatIf:$false | Out-Null
    $copy = Join-Path $folder 'setup-windows.ps1'
    Invoke-WebRequest -UseBasicParsing -Uri $ScriptUrl -OutFile $copy -WhatIf:$false
    return $copy
}

function Register-ResumeAfterReboot {
    $command = Get-ResumeCommand (Get-ResumeScriptPath)
    New-ItemProperty -Path 'HKCU:\Software\Microsoft\Windows\CurrentVersion\RunOnce' -Name 'ApiSportivaSetup' `
        -Value $command -PropertyType String -Force -WhatIf:$false | Out-Null
}

# Si el usuario rechazo el reinicio o el setup termino por otra via, la entrada quedaria esperando el
# proximo inicio de sesion y correria una copia vieja: se limpia al final de una corrida exitosa.
function Clear-ResumeAfterReboot {
    Remove-ItemProperty -Path 'HKCU:\Software\Microsoft\Windows\CurrentVersion\RunOnce' -Name 'ApiSportivaSetup' `
        -ErrorAction SilentlyContinue -WhatIf:$false
    $folder = Join-Path $env:LOCALAPPDATA 'ApiSportiva'
    if (Test-Path $folder) { Remove-Item $folder -Recurse -Force -WhatIf:$false }
}

function Request-Reboot {
    $result = Request-Change 'Programar este script para que siga solo despues de reiniciar' { Register-ResumeAfterReboot }
    if ($result -eq 'whatif') { return }
    if ($result -eq 'done') { Write-Ok 'El setup va a seguir solo cuando vuelvas a iniciar sesion.' }
    Write-Host '    Windows necesita reiniciar para terminar de activar WSL. Guarda tu trabajo antes de seguir.' -ForegroundColor Yellow
    if (Confirm-Action 'Reiniciar ahora' -AlwaysAsk) {
        Stop-Log
        # Sin -Force: si una aplicacion tiene trabajo sin guardar, Windows lo avisa en lugar de cerrarla.
        Restart-Computer -WhatIf:$false
        exit 0
    }
    Stop-WithHelp 'Reinicia Windows para terminar de activar WSL.'
}

# Manda texto por stdin a un comando que corre como root en la distro y devuelve su exit code.
# Windows PowerShell 5.1 antepone un BOM y termina las lineas en CRLF al escribir en el stdin de un
# ejecutable nativo (aunque $OutputEncoding sea UTF-8 sin BOM): se limpian del lado de Linux antes de
# pasar el texto al comando. $Command no tiene que llevar comillas dobles (se pasa como argumento).
function Send-ToRootStdin([string]$Text, [string]$Command) {
    # Out-Host: la salida no puede quedar en el valor de retorno (solo el exit code).
    $Text | & wsl.exe -d $Distro -u root --exec sh -c "sed '1s/^\xEF\xBB\xBF//' | tr -d '\r' | $Command" | Out-Host
    return $LASTEXITCODE
}

function New-LinuxUser {
    $suggested = ($env:USERNAME.ToLower() -replace '[^a-z0-9_-]', '')
    if ($suggested -notmatch '^[a-z_]') { $suggested = "dev$suggested" }
    if ($suggested.Length -gt 32) { $suggested = $suggested.Substring(0, 32) }

    Write-Note 'Ahora se crea tu usuario de Linux (en lugar de la ventana de primer uso de Ubuntu).'
    $linuxUser = (Read-Host "    Usuario de Linux [$suggested]").Trim()
    if (-not $linuxUser) { $linuxUser = $suggested }
    # -cmatch: distingue mayusculas (useradd solo acepta minusculas).
    if ($linuxUser -cnotmatch '^[a-z_][a-z0-9_-]{0,31}$') {
        Stop-WithHelp "'$linuxUser' no es un usuario de Linux valido (minusculas, numeros, - y _; empieza con letra)."
    }

    $secure = Read-Host '    Contrasena (para sudo dentro de Linux)' -AsSecureString
    $secureAgain = Read-Host '    Repeti la contrasena' -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    $bstrAgain = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureAgain)
    try {
        $password = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
        $passwordAgain = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstrAgain)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstrAgain)
    }
    if (-not $password) { Stop-WithHelp 'La contrasena no puede estar vacia.' }
    if ($password -ne $passwordAgain) { Stop-WithHelp 'Las contrasenas no coinciden.' }

    # Todo como root y sin shell intermedio (--exec): los argumentos llegan tal cual. Con --exec el PATH
    # no incluye /usr/sbin, por eso las herramientas de usuarios van con ruta absoluta.
    # Si el usuario ya existe (una corrida anterior que se corto), se reutiliza en lugar de fallar.
    $createdNow = $false
    if ((Invoke-Native { wsl.exe -d $Distro -u root --exec id -u $linuxUser }).ExitCode -eq 0) {
        Write-Note "El usuario '$linuxUser' ya existe en ${Distro}: se completa su configuracion."
        & wsl.exe -d $Distro -u root --exec /usr/sbin/usermod -aG sudo $linuxUser | Out-Host
    } else {
        & wsl.exe -d $Distro -u root --exec /usr/sbin/useradd -m -s /bin/bash -G sudo $linuxUser | Out-Host
        if ($LASTEXITCODE -ne 0) { Stop-WithHelp "No se pudo crear el usuario '$linuxUser' en $Distro." }
        $createdNow = $true
    }

    # La contrasena viaja por stdin (nunca como argumento: no queda en la lista de procesos ni en el log).
    try {
        $chpasswdExit = Send-ToRootStdin "${linuxUser}:$password" '/usr/sbin/chpasswd'
    } finally {
        $password = $null
        $passwordAgain = $null
    }
    if ($chpasswdExit -ne 0) {
        # Sin contrasena el usuario no puede usar sudo: se deshace para que la proxima corrida empiece limpia.
        if ($createdNow) { Invoke-Native { wsl.exe -d $Distro -u root --exec /usr/sbin/userdel -r $linuxUser } | Out-Null }
        Stop-WithHelp 'No se pudo asignar la contrasena del usuario de Linux.'
    }

    # Usuario por defecto en /etc/wsl.conf, sin duplicar la seccion [user] si ya existe. El script va por
    # stdin a "sh -s" ($linuxUser ya esta validado: solo minusculas, numeros, - y _).
    $wslConfScript = "set -e`nf=/etc/wsl.conf`ntouch `"`$f`"`n" +
        "if grep -q '^default=' `"`$f`"; then sed -i `"s/^default=.*/default=`$1/`" `"`$f`"; " +
        "else printf '\n[user]\ndefault=%s\n' `"`$1`" >> `"`$f`"; fi`n"
    if ((Send-ToRootStdin $wslConfScript "sh -s -- $linuxUser") -ne 0) {
        Stop-WithHelp 'No se pudo configurar el usuario por defecto en /etc/wsl.conf.'
    }

    # Reiniciar la distro para que tome el usuario por defecto.
    Invoke-Native { wsl.exe --terminate $Distro } | Out-Null
}

# ---------------------------------------------------------------------------
# Docker Desktop
# ---------------------------------------------------------------------------
function Find-Docker {
    $cmd = Get-Command docker.exe -ErrorAction SilentlyContinue
    if ($null -ne $cmd) { return $cmd.Source }
    $candidates = @(
        (Join-Path $env:LOCALAPPDATA 'Programs\DockerDesktop\resources\bin\docker.exe'),
        (Join-Path $env:ProgramFiles 'Docker\Docker\resources\bin\docker.exe')
    )
    foreach ($path in $candidates) { if (Test-Path $path) { return $path } }
    return $null
}

function Find-DockerDesktopExe {
    $candidates = @(
        (Join-Path $env:LOCALAPPDATA 'Programs\DockerDesktop\Docker Desktop.exe'),
        (Join-Path $env:ProgramFiles 'Docker\Docker\Docker Desktop.exe')
    )
    foreach ($path in $candidates) { if (Test-Path $path) { return $path } }
    return $null
}

function Test-DockerDaemon([string]$DockerExe) {
    return ((Invoke-Native { & $DockerExe info }).ExitCode -eq 0)
}

function Wait-DockerDaemon([string]$DockerExe, [int]$Minutes) {
    $deadline = (Get-Date).AddMinutes($Minutes)
    while (-not (Test-DockerDaemon $DockerExe)) {
        if ((Get-Date) -gt $deadline) { return $false }
        Start-Sleep -Seconds 5
    }
    return $true
}

function Start-DockerDesktop([string]$DockerExe) {
    # "docker desktop start" existe desde Docker Desktop 4.37; si no, se abre el ejecutable.
    if ((Invoke-Native { & $DockerExe desktop start }).ExitCode -ne 0) {
        $desktopExe = Find-DockerDesktopExe
        if (-not $desktopExe) { Stop-WithHelp 'No se encontro Docker Desktop para arrancarlo.' }
        Start-Process -FilePath $desktopExe
    }
}

function Restart-DockerDesktop([string]$DockerExe) {
    if ((Invoke-Native { & $DockerExe desktop restart }).ExitCode -ne 0) {
        Get-Process -Name 'Docker Desktop' -ErrorAction SilentlyContinue | Stop-Process -Force -WhatIf:$false
        Start-Sleep -Seconds 5
        Start-DockerDesktop $DockerExe
    }
}

function Install-DockerDesktop {
    if (Test-Command 'winget.exe') {
        # El paquete de winget es el instalador oficial (tipo exe, verificado por SHA256): --override le
        # pasa los argumentos documentados por Docker (por usuario, sin admin, licencia aceptada).
        & winget.exe install -e --id Docker.DockerDesktop --accept-package-agreements --accept-source-agreements --override $DockerInstallArgs | Out-Host
        if ($LASTEXITCODE -eq 0) { return }
        Write-Note "winget termino con codigo $LASTEXITCODE; se intenta con el instalador oficial."
    }
    # Plan B: instalador oficial, verificando que este firmado por Docker antes de ejecutarlo.
    $installer = Join-Path $env:TEMP ("DockerDesktopInstaller-{0}.exe" -f [guid]::NewGuid())
    Invoke-WebRequest -UseBasicParsing -Uri $DockerInstallerUrl -OutFile $installer -WhatIf:$false
    $signature = Get-AuthenticodeSignature -FilePath $installer
    # Organizacion EXACTA del certificado (no un substring: "Docker Incubator" no tiene que pasar).
    $signerIsDocker = $signature.SignerCertificate -and
        ($signature.SignerCertificate.Subject -match '(^|,\s*)O="?Docker Inc"?\s*(,|$)')
    if ($signature.Status -ne 'Valid' -or -not $signerIsDocker) {
        Remove-Item $installer -Force -WhatIf:$false
        Stop-WithHelp "El instalador descargado no tiene una firma valida de Docker Inc (estado: $($signature.Status)). No se ejecuto."
    }
    $process = Start-Process -FilePath $installer -ArgumentList $DockerInstallArgs -Wait -PassThru
    Remove-Item $installer -Force -WhatIf:$false
    if ($process.ExitCode -ne 0) { Stop-WithHelp "El instalador de Docker Desktop termino con codigo $($process.ExitCode)." }
}

# ---------------------------------------------------------------------------
# 0. Contexto
# ---------------------------------------------------------------------------
if ($DryRun) { Write-Host "`nModo -WhatIf: solo se informa que se haria. No se cambia nada." -ForegroundColor Magenta }
if ($LogPath) { Write-Host "Log de esta corrida: $LogPath" }

if (Test-IsAdmin) {
    Write-Host "`n!! Estas corriendo el script como ADMINISTRADOR." -ForegroundColor Yellow
    Write-Host '!! Conviene correrlo como usuario normal: el script pide permisos solo cuando hacen falta.' -ForegroundColor Yellow
    Write-Host '!! Como admin, WSL y Docker pueden quedar configurados para otro contexto que no es el tuyo.' -ForegroundColor Yellow
    if (-not $DryRun -and -not (Confirm-Action 'Seguir igual como administrador' -AlwaysAsk)) {
        Stop-WithHelp 'Abri PowerShell normal (sin "Ejecutar como administrador") y volve a correr el script.'
    }
}

# ---------------------------------------------------------------------------
# 1. WSL 2
# ---------------------------------------------------------------------------
Write-Step 'Verificando WSL 2'

$wslReady = Test-WslReady
$wslInstalledThisRun = $false
if ($wslReady) {
    Write-Ok 'WSL esta activo'
} else {
    $result = Request-Change 'Activar WSL 2 (pide permisos de administrador una vez; puede pedir reiniciar)' {
        # Ruta completa del wsl.exe del sistema: con -Verb RunAs (ShellExecute) un nombre suelto podria
        # resolver a un wsl.exe plantado en la carpeta actual, y el UAC aprobaria ese ejecutable.
        $process = Start-Process -FilePath (Get-SystemWslPath) -ArgumentList '--install', '--no-distribution' -Verb RunAs -Wait -PassThru
        Write-Note "wsl --install termino con codigo $($process.ExitCode)."
        $script:wslInstalledThisRun = $true
    }
    if ($result -eq 'declined') { Stop-WithHelp 'Activa WSL manualmente: en PowerShell como administrador, "wsl --install --no-distribution".' }
    if ($result -eq 'done') {
        $wslReady = Test-WslReady
        if (-not $wslReady) { Request-Reboot }
        Write-Ok 'WSL activado'
    }
}

# ---------------------------------------------------------------------------
# 2. Distro y usuario de Linux
# ---------------------------------------------------------------------------
Write-Step "Verificando la distro '$Distro'"

$distroReady = $false
if (-not $wslReady) {
    Write-Note '(se verifica despues de activar WSL)'
} elseif ((Get-WslDistros) -notcontains $Distro) {
    $result = Request-Change "Instalar $Distro en WSL (sin la ventana de primer uso) y crear tu usuario de Linux" {
        & wsl.exe --install -d $Distro --no-launch | Out-Host
        if ($LASTEXITCODE -ne 0) {
            # Recien activado WSL, la plataforma de virtualizacion puede necesitar un reinicio.
            if ($wslInstalledThisRun) { Request-Reboot }
            Stop-WithHelp "No se pudo instalar $Distro (codigo $LASTEXITCODE)."
        }
        # La distro recien instalada arranca como root hasta que exista un usuario.
        New-LinuxUser
    }
    if ($result -eq 'declined') { Stop-WithHelp "Instala la distro manualmente: wsl --install -d $Distro" }
    $distroReady = ($result -eq 'done')
    if ($result -eq 'whatif' -and (Get-WslDefaultDistro) -ne $Distro) {
        $null = Request-Change "Dejar '$Distro' como distro predeterminada de WSL (Docker se integra con la predeterminada)" { }
    }
} else {
    $distroReady = $true
}

if ($distroReady) {
    $versionLine = ((Invoke-Native { wsl.exe -l -v }).Output -split "`r?`n" | ForEach-Object { $_ -replace "`0", '' }) |
        Where-Object { $_ -match "^\s*\*?\s*$([regex]::Escape($Distro))\s" } | Select-Object -First 1
    if ($versionLine -and ($versionLine.Trim() -match '\s1$')) {
        $result = Request-Change "Convertir '$Distro' a WSL 2 (Docker Desktop lo necesita; puede tardar)" {
            & wsl.exe --set-version $Distro 2 | Out-Host
            if ($LASTEXITCODE -ne 0) { Stop-WithHelp "No se pudo convertir '$Distro' a WSL 2." }
        }
        if ($result -eq 'declined') { Stop-WithHelp "Converti la distro manualmente con: wsl --set-version $Distro 2" }
    }

    $wslUser = Get-FromWsl 'whoami'
    if ($wslUser -eq 'root') {
        # Distro instalada antes sin usuario (por ejemplo, una corrida anterior interrumpida).
        $result = Request-Change "Crear tu usuario de Linux en $Distro" { New-LinuxUser }
        if ($result -eq 'declined') { Stop-WithHelp "Abri '$Distro' desde el menu Inicio y crea tu usuario." }
        $wslUser = Get-FromWsl 'whoami'
    }
    if (-not $DryRun -and (-not $wslUser -or $wslUser -eq 'root')) {
        Stop-WithHelp "La distro '$Distro' no tiene un usuario normal configurado. Abrila una vez desde el menu Inicio y crea tu usuario."
    }
    if ($wslUser) { Write-Ok "$Distro con el usuario $wslUser" }

    # Docker Desktop activa la integracion WSL en la distro PREDETERMINADA.
    $defaultDistro = Get-WslDefaultDistro
    if ($defaultDistro -ne $Distro) {
        $result = Request-Change "Dejar '$Distro' como distro predeterminada de WSL (hoy: '$defaultDistro'; Docker se integra con la predeterminada)" {
            & wsl.exe --set-default $Distro | Out-Host
        }
        if ($result -eq 'declined') { Write-Note "Seguimos sin cambiarla; si docker no anda dentro de '$Distro', activa la integracion a mano." }
    }
}

# ---------------------------------------------------------------------------
# 3. Docker Desktop
# ---------------------------------------------------------------------------
Write-Step 'Verificando Docker Desktop'

$dockerExe = Find-Docker
$dockerReady = $false
if (-not $dockerExe) {
    $result = Request-Change 'Instalar Docker Desktop (por usuario, sin admin, con la licencia aceptada)' { Install-DockerDesktop }
    if ($result -eq 'declined') { Stop-WithHelp 'Instala Docker Desktop desde https://www.docker.com/products/docker-desktop/' }
    if ($result -eq 'done') {
        $dockerExe = Find-Docker
        if (-not $dockerExe) { Stop-WithHelp 'Docker Desktop se instalo pero no se encuentra docker.exe. Cerra y abri PowerShell y volve a correr el script.' }
    }
}

if ($dockerExe) {
    if (Test-DockerDaemon $dockerExe) {
        $dockerReady = $true
    } else {
        $result = Request-Change 'Arrancar Docker Desktop y esperar a que el motor responda' {
            Start-DockerDesktop $dockerExe
            Write-Note 'Esperando al motor de Docker (la primera vez puede tardar unos minutos)...'
            if (-not (Wait-DockerDaemon $dockerExe 5)) {
                Stop-WithHelp "El motor de Docker no respondio en 5 minutos. Abri Docker Desktop y revisa que diga 'Engine running'."
            }
        }
        if ($result -eq 'declined') { Stop-WithHelp "Abri Docker Desktop y espera a que diga 'Engine running'." }
        $dockerReady = ($result -eq 'done')
    }
    if ($dockerReady) { Write-Ok "el motor de Docker responde ($dockerExe)" }
}

# ---------------------------------------------------------------------------
# 4. docker dentro de WSL (integracion de Docker Desktop), con reparacion escalonada
# ---------------------------------------------------------------------------
Write-Step "Verificando que docker funcione dentro de '$Distro'"

function Test-DockerInWsl { return ($null -ne (Get-FromWsl 'docker version --format {{.Server.Version}}')) }

# Docker Desktop tarda unos segundos en reconectar la integracion despues de reiniciar la distro o
# el propio Docker: se consulta cada 3 segundos en lugar de mirar una sola vez.
function Wait-DockerInWsl([int]$Seconds) {
    $deadline = (Get-Date).AddSeconds($Seconds)
    do {
        if (Test-DockerInWsl) { return $true }
        Start-Sleep -Seconds 3
    } while ((Get-Date) -lt $deadline)
    return $false
}

if (-not ($distroReady -and $dockerReady)) {
    Write-Note '(se verifica despues de instalar lo que falta)'
} elseif (Test-DockerInWsl) {
    Write-Ok "docker funciona dentro de '$Distro'"
} else {
    Write-Note "docker no responde dentro de '$Distro': se intenta reparar."
    $fixed = $false

    $result = Request-Change "Reiniciar la distro '$Distro' (wsl --terminate; cierra las terminales de Linux abiertas)" {
        Invoke-Native { wsl.exe --terminate $Distro } | Out-Null
    }
    if ($result -eq 'done') {
        Write-Note 'Esperando a que Docker reconecte la distro (hasta 30 s)...'
        $fixed = Wait-DockerInWsl 30
    }

    if (-not $fixed -and $result -ne 'whatif') {
        $result = Request-Change 'Reiniciar Docker Desktop (detiene los contenedores en ejecucion; los datos se conservan)' {
            Restart-DockerDesktop $dockerExe
            # "docker desktop restart" puede volver antes de que el motor se reinicie: si se consulta
            # enseguida, todavia responde el motor viejo. Se da un margen antes de esperar al nuevo.
            Start-Sleep -Seconds 15
            if (-not (Wait-DockerDaemon $dockerExe 5)) { Stop-WithHelp 'El motor de Docker no volvio a responder despues de reiniciarlo.' }
            Invoke-Native { wsl.exe --terminate $Distro } | Out-Null
        }
        if ($result -eq 'done') {
            Write-Note 'Esperando a que Docker reconecte la distro (hasta 90 s)...'
            $fixed = Wait-DockerInWsl 90
        }
    }

    # Ultimo escalon: la carpeta compartida entre distros (/mnt/wsl, donde Docker deja el socket de la
    # integracion) puede quedar desincronizada (por ejemplo, despues de registrar o borrar otra distro).
    # Ni reiniciar la distro ni reiniciar Docker la recuperan: hay que reiniciar toda la VM de WSL.
    if (-not $fixed -and $result -ne 'whatif' -and $result -ne 'declined') {
        $result = Request-Change 'Reiniciar WSL completo (wsl --shutdown: detiene TODAS las distros) y Docker Desktop' {
            Invoke-Native { wsl.exe --shutdown } | Out-Null
            Start-Sleep -Seconds 5
            Restart-DockerDesktop $dockerExe
            Start-Sleep -Seconds 15
            if (-not (Wait-DockerDaemon $dockerExe 5)) { Stop-WithHelp 'El motor de Docker no volvio a responder despues de reiniciar WSL.' }
        }
        if ($result -eq 'done') {
            Write-Note 'Esperando a que Docker reconecte la distro (hasta 90 s)...'
            $fixed = Wait-DockerInWsl 90
        }
    }

    if ($fixed) {
        Write-Ok "docker funciona dentro de '$Distro'"
    } elseif (-not $DryRun) {
        Stop-WithHelp ("docker sigue sin estar disponible dentro de '$Distro'. En Docker Desktop: Settings > Resources > " +
            "WSL integration > activa '$Distro' > 'Apply & restart'. Despues corre 'wsl --terminate $Distro'.")
    }
}

# ---------------------------------------------------------------------------
# 5. git y credenciales dentro de WSL
# ---------------------------------------------------------------------------
Write-Step 'Verificando git y el acceso al repo desde WSL'

$gitReady = $false
if (-not $distroReady) {
    Write-Note '(se verifica despues de instalar la distro)'
} else {
    if ($null -eq (Get-FromWsl 'git --version')) {
        $result = Request-Change "Instalar git en '$Distro' con apt (pide tu contrasena de Linux)" {
            if ((Invoke-InWslInteractive 'sudo apt-get update && sudo apt-get install -y git') -ne 0) { Stop-WithHelp 'No se pudo instalar git en WSL.' }
        }
        if ($result -eq 'declined') { Stop-WithHelp 'Instala git en WSL con: sudo apt-get update && sudo apt-get install -y git' }
    }

    $isRemote = $RepoUrl -match '^(https?|ssh|git)://|^git@'
    $hasAccess = (-not $isRemote) -or ((Invoke-InWsl ("GIT_TERMINAL_PROMPT=0 git ls-remote " + (ConvertTo-BashLiteral $RepoUrl) + " > /dev/null 2>&1")) -eq 0)
    if (-not $hasAccess) {
        Write-Note 'Git en WSL no tiene acceso al repo (es privado o requiere login).'
        $gcmWindows = Join-Path (Get-ProgramFiles64) 'Git\mingw64\bin\git-credential-manager.exe'
        if (-not (Test-Path $gcmWindows)) {
            $result = Request-Change 'Instalar Git for Windows con winget (para usar tu login de GitHub desde WSL)' {
                & winget.exe install -e --id Git.Git --accept-package-agreements --accept-source-agreements | Out-Host
            }
            if (-not $DryRun -and -not (Test-Path $gcmWindows)) {
                Stop-WithHelp ("No se encontro Git for Windows con Git Credential Manager. Configura credenciales en WSL " +
                    "(por ejemplo, 'gh auth login' dentro de WSL) o instala Git for Windows.")
            }
        }
        $gcmWsl = ConvertTo-WslPath $gcmWindows
        $result = Request-Change 'Configurar el Git Credential Manager de Windows en el git de WSL (git config --global credential.helper)' {
            if ((Invoke-InWsl "git config --global credential.helper '$($gcmWsl -replace ' ', '\ ')'") -ne 0) { Stop-WithHelp 'No se pudo configurar el credential helper.' }
        }
        if ($result -eq 'declined') { Stop-WithHelp 'Configura credenciales de GitHub dentro de WSL y volve a correr el script.' }
        if ($result -eq 'done') {
            Write-Note 'Puede abrirse una ventana para iniciar sesion en GitHub.'
            if ((Invoke-InWsl ("git ls-remote " + (ConvertTo-BashLiteral $RepoUrl) + " > /dev/null")) -ne 0) { Stop-WithHelp 'Sigue sin haber acceso al repo. Revisa que tu usuario de GitHub tenga permisos.' }
            $hasAccess = $true
        }
    }
    if ($hasAccess) { Write-Ok "git tiene acceso a $RepoUrl" }

    # El git de WSL es otro git, distinto del de Windows, y arranca sin identidad: sin esto el primer
    # commit falla, o queda con un email que GitHub no asocia a la cuenta (commit "sin usuario").
    # Se usa el email noreply de GitHub (<id>+<usuario>@users.noreply.github.com).
    Write-Step 'Verificando la identidad de git en WSL'
    $gitName = Get-FromWsl 'git config --global user.name'
    $gitEmail = Get-FromWsl 'git config --global user.email'
    if ($gitName -and $gitEmail) {
        Write-Ok "git en WSL firma como: $gitName <$gitEmail>"
    } elseif ($DryRun) {
        $null = Request-Change 'Configurar nombre y email de git en WSL (email noreply de tu cuenta de GitHub)' { }
    } else {
        Write-Note 'git en WSL no tiene nombre y/o email configurados.'
        if (-not $GitHubUser) {
            if ($Yes) { Stop-WithHelp 'Falta la identidad de git en WSL. Volve a correr con -GitHubUser <tu-usuario-de-GitHub>.' }
            $GitHubUser = (Read-Host '    Tu usuario de GitHub (por ejemplo, nobodyh-ctrl)').Trim()
        }
        if ($GitHubUser -notmatch '^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$') { Stop-WithHelp "'$GitHubUser' no es un usuario de GitHub valido." }
        try {
            $account = Invoke-RestMethod -Uri "https://api.github.com/users/$GitHubUser" -Headers @{ 'User-Agent' = 'api-sportiva-setup' }
        } catch {
            Stop-WithHelp "No se pudo consultar el usuario '$GitHubUser' en GitHub (existe? hay internet?). Detalle: $($_.Exception.Message)"
        }
        $newName = if ($gitName) { $gitName } elseif ($account.name) { $account.name } else { $account.login }
        $newEmail = if ($gitEmail) { $gitEmail } else { "$($account.id)+$($account.login)@users.noreply.github.com" }
        $result = Request-Change "Configurar git en WSL (git config --global) como: $newName <$newEmail>" {
            $configured = Invoke-InWsl ("git config --global user.name " + (ConvertTo-BashLiteral $newName) +
                " && git config --global user.email " + (ConvertTo-BashLiteral $newEmail))
            if ($configured -ne 0) { Stop-WithHelp 'No se pudo configurar la identidad de git en WSL.' }
        }
        if ($result -eq 'declined') {
            Stop-WithHelp ("Configura la identidad manualmente en WSL: git config --global user.name ""Tu Nombre"" " +
                "y git config --global user.email ""<id>+<usuario>@users.noreply.github.com"".")
        }
        Write-Ok "git en WSL firma como: $newName <$newEmail>"
    }
    $gitReady = $true
}

# ---------------------------------------------------------------------------
# 6. Clonar dentro de WSL y correr el setup del proyecto
# ---------------------------------------------------------------------------
Write-Step "Clonando el repo en ~/$TargetDir (dentro de WSL)"

$cloneReady = $false
$cloneExisted = $false
if (-not $gitReady) {
    Write-Note '(se hace despues de preparar la distro y git)'
} elseif ((Invoke-InWsl "test -d ~/$TargetDir/.git") -eq 0) {
    Write-Ok "Ya existe un clon en ~/${TargetDir}: se usa tal como esta (no se hace pull)"
    $cloneReady = $true
    $cloneExisted = $true
} elseif ((Invoke-InWsl "test -e ~/$TargetDir") -eq 0) {
    Stop-WithHelp "~/$TargetDir ya existe y no es un repo git. Movelo o usa otro -TargetDir."
} else {
    $result = Request-Change "Clonar $RepoUrl en ~/$TargetDir" {
        # Solo se crea la carpeta padre: git clone crea la de destino. Si el clone falla, no queda
        # una carpeta vacia que haga fallar la siguiente corrida ("ya existe y no es un repo git").
        if ((Invoke-InWsl ("mkdir -p `$(dirname ~/$TargetDir) && git clone " + (ConvertTo-BashLiteral $RepoUrl) + " ~/$TargetDir")) -ne 0) {
            Stop-WithHelp 'Fallo el git clone.'
        }
    }
    if ($result -eq 'declined') { Stop-WithHelp "Clona el repo manualmente en WSL: git clone $RepoUrl ~/$TargetDir" }
    $cloneReady = ($result -eq 'done')
    if ($cloneReady) { Write-Ok "Clonado en ~/$TargetDir" }
}

Write-Step 'Corriendo scripts/setup.sh dentro de WSL'
if (-not ($cloneReady -and $dockerReady)) {
    Write-Note '(se corre despues de clonar el repo y tener Docker andando)'
} elseif ($DryRun -and $cloneExisted) {
    Write-Note '(el entorno ya existe: setup.sh es idempotente y se puede volver a correr cuando quieras)'
} else {
    $result = Request-Change 'Correr scripts/setup.sh (build de la imagen, MySQL, dependencias y migraciones)' {
        if ((Invoke-InWsl "cd ~/$TargetDir && bash scripts/setup.sh") -ne 0) {
            Stop-WithHelp 'scripts/setup.sh fallo. Revisa el error de arriba y la seccion de troubleshooting de docs/desarrollo-local.md.'
        }
    }
    if ($result -eq 'declined') { Write-Note "Correlo cuando quieras dentro de WSL: cd ~/$TargetDir && bash scripts/setup.sh" }
}

# ---------------------------------------------------------------------------
# 7. VS Code con la extension WSL
# ---------------------------------------------------------------------------
Write-Step 'Verificando VS Code'

$codeCmd = if (Test-Command 'code') { 'code' } else {
    $candidate = Join-Path $env:LOCALAPPDATA 'Programs\Microsoft VS Code\bin\code.cmd'
    if (Test-Path $candidate) { $candidate } else { $null }
}
if (-not $codeCmd) {
    $result = Request-Change 'Instalar VS Code con winget (por usuario)' {
        & winget.exe install -e --id Microsoft.VisualStudioCode --scope user --accept-package-agreements --accept-source-agreements | Out-Host
    }
    $candidate = Join-Path $env:LOCALAPPDATA 'Programs\Microsoft VS Code\bin\code.cmd'
    if (Test-Path $candidate) { $codeCmd = $candidate }
    elseif ($result -eq 'done') { Write-Note 'VS Code se instalo; si no lo encuentro, cerra y abri PowerShell.' }
}

if ($codeCmd) {
    $extensions = (Invoke-Native { & $codeCmd --list-extensions }).Output -split "`r?`n"
    if ($extensions -contains $VsCodeWslExtension) {
        Write-Ok "VS Code tiene la extension WSL ($VsCodeWslExtension)"
    } else {
        $result = Request-Change "Instalar la extension WSL de VS Code ($VsCodeWslExtension)" {
            & $codeCmd --install-extension $VsCodeWslExtension | Out-Host
        }
        if ($result -eq 'done') { Write-Ok 'extension WSL instalada' }
    }
}

# ---------------------------------------------------------------------------
# Resumen
# ---------------------------------------------------------------------------
if ($DryRun) {
    Write-Host ''
    if ($script:Plan.Count -eq 0) {
        Write-Host 'Modo -WhatIf: nada para instalar ni configurar. La maquina ya esta lista.' -ForegroundColor Green
    } else {
        Write-Host "Modo -WhatIf: el script haria $($script:Plan.Count) cambio(s):" -ForegroundColor Magenta
        $script:Plan | ForEach-Object { Write-Host "  - $_" -ForegroundColor Magenta }
    }
    if ($LogPath) { Write-Host "Log: $LogPath" }
    Stop-Log
    return
}

# Corrida exitosa: si habia una reanudacion programada (o una copia local del script), ya no hace falta.
Clear-ResumeAfterReboot

$wslHome = Get-FromWsl 'echo $HOME'
$projectPath = "$wslHome/$TargetDir"
if ($codeCmd -and -not $NoOpen -and $cloneReady) {
    Write-Step 'Abriendo VS Code (Remote WSL)'
    Write-Note 'Al abrir el proyecto, VS Code ofrece instalar las extensiones recomendadas del repo: acepta.'
    & $codeCmd --remote "wsl+$Distro" $projectPath
}

$uncPath = '\\wsl$\' + $Distro + ($projectPath -replace '/', '\')
Write-Host "`nListo. El proyecto esta en $uncPath" -ForegroundColor Green
Write-Host "Desde una terminal de WSL:  cd ~/$TargetDir && docker compose ps" -ForegroundColor Green
if ($LogPath) { Write-Host "Log de esta corrida: $LogPath" }
Stop-Log
