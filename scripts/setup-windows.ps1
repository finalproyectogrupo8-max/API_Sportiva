<#
.SYNOPSIS
    Bootstrap del entorno de desarrollo de la API Sportiva en Windows (WSL 2 + Docker Desktop).

.DESCRIPTION
    Pasos (todos idempotentes, se puede volver a correr):
      1. Verifica WSL 2 y la distro (Ubuntu por defecto). Si faltan, ofrece instalarlas.
      2. Verifica Docker Desktop. Si falta, ofrece instalarlo con winget; si no responde, lo abre.
      3. Verifica que "docker" funcione DENTRO de la distro (integracion WSL de Docker Desktop).
      4. Verifica git y las credenciales de GitHub dentro de WSL. Si hace falta, ofrece usar el
         Git Credential Manager de Windows.
      5. Clona el repo dentro de WSL (rapido, a diferencia de un bind mount desde C:\).
      6. Corre scripts/setup.sh dentro de WSL y, si esta VS Code, abre el proyecto con Remote WSL.

    Toda instalacion o cambio de configuracion global pide confirmacion, salvo con -Yes.
    El script nunca borra datos ni cambia la execution policy global.

    El archivo es ASCII a proposito y SIN BOM: Windows PowerShell 5.1 lee los .ps1 sin BOM como
    ANSI (con ASCII da igual), y con "irm <url> | iex" un BOM queda pegado delante de "<#" y rompe
    el parseo de todo el script. La CI lo verifica (paso "Script de setup ejecutable con irm").

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\setup-windows.ps1 -Distro Ubuntu-24.04 -NoOpen
#>
[CmdletBinding()]
param(
    [string]$Distro = 'Ubuntu',
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

function Write-Step([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }
function Write-Ok([string]$Message) { Write-Host "    OK: $Message" -ForegroundColor Green }
function Stop-WithHelp([string]$Message) {
    Write-Host "`n$Message" -ForegroundColor Yellow
    Write-Host "Cuando este resuelto, volve a correr este script." -ForegroundColor Yellow
    # Desde un archivo (-File), exit 1 termina el script con codigo de error.
    # Desde "irm <url> | iex" no hay archivo ($PSCommandPath vacio): exit cerraria la ventana de
    # PowerShell del usuario antes de que lea las instrucciones, asi que se corta con throw.
    if ($PSCommandPath) { exit 1 }
    throw 'Setup detenido (ver el mensaje de arriba).'
}

function Confirm-Action([string]$Question) {
    if ($Yes) { return $true }
    $answer = Read-Host "$Question [s/N]"
    return ($answer -match '^(s|si|y|yes)$')
}

function Test-IsAdmin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    return (New-Object Security.Principal.WindowsPrincipal $identity).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Ejecuta un comando bash dentro de la distro y devuelve SOLO el exit code.
# La salida se manda a la consola con Out-Host: sin eso, PowerShell la incluiria en el valor
# de retorno de la funcion y el chequeo del exit code daria siempre "fallo".
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
    $ErrorActionPreference = 'Continue'
    & $DockerExe info *> $null
    return ($LASTEXITCODE -eq 0)
}

# Para pasar un valor a bash entre comillas simples: ' -> '\''
function ConvertTo-BashLiteral([string]$Value) {
    return "'" + ($Value -replace "'", "'\''") + "'"
}

# C:\Program Files\x -> /mnt/c/Program Files/x
function ConvertTo-WslPath([string]$WindowsPath) {
    return '/mnt/' + $WindowsPath.Substring(0, 1).ToLower() + ($WindowsPath.Substring(2) -replace '\\', '/')
}

# ---------------------------------------------------------------------------
# 1. WSL 2 y la distro
# ---------------------------------------------------------------------------
Write-Step "Verificando WSL 2 y la distro '$Distro'"

$wslAvailable = $null -ne (Get-Command wsl.exe -ErrorAction SilentlyContinue)
$distros = @()
if ($wslAvailable) {
    $ErrorActionPreference = 'Continue'
    $listed = & wsl.exe -l -q 2>$null
    $ErrorActionPreference = 'Stop'
    if ($LASTEXITCODE -eq 0) { $distros = @($listed | ForEach-Object { ($_ -replace "`0", '').Trim() } | Where-Object { $_ }) }
}

if (-not ($distros -contains $Distro)) {
    Write-Host "    No se encontro la distro '$Distro' en WSL."
    if (-not (Confirm-Action "Instalar WSL 2 con '$Distro' ahora (wsl --install -d $Distro)? Puede pedir reiniciar")) {
        Stop-WithHelp "Instala WSL manualmente: abri PowerShell como administrador y corre 'wsl --install -d $Distro'."
    }
    if (-not (Test-IsAdmin)) {
        Stop-WithHelp "Instalar WSL requiere permisos de administrador. Abri PowerShell como administrador y volve a correr el script."
    }
    & wsl.exe --install -d $Distro
    Stop-WithHelp ("WSL se esta instalando. Si Windows lo pide, reinicia. Despues abri '$Distro' desde el menu Inicio " +
        "y crea tu usuario de Linux (lo pide la primera vez).")
}

$ErrorActionPreference = 'Continue'
$versionLines = & wsl.exe -l -v 2>$null
$ErrorActionPreference = 'Stop'
$versionLine = ($versionLines | ForEach-Object { ($_ -replace "`0", '') }) |
    Where-Object { $_ -match "^\s*\*?\s*$([regex]::Escape($Distro))\s" } | Select-Object -First 1
if ($versionLine -and ($versionLine.Trim() -match '\s1$')) {
    Write-Host "    '$Distro' usa WSL 1 y Docker Desktop necesita WSL 2."
    if (-not (Confirm-Action "Convertir '$Distro' a WSL 2 (wsl --set-version $Distro 2)? Puede tardar varios minutos")) {
        Stop-WithHelp "Converti la distro manualmente con: wsl --set-version $Distro 2"
    }
    & wsl.exe --set-version $Distro 2
    if ($LASTEXITCODE -ne 0) { Stop-WithHelp "No se pudo convertir '$Distro' a WSL 2." }
}

$wslUser = Get-FromWsl 'whoami'
if (-not $wslUser -or $wslUser -eq 'root') {
    Stop-WithHelp "La distro '$Distro' no tiene un usuario normal configurado. Abrila una vez desde el menu Inicio y crea tu usuario."
}
Write-Ok "WSL 2 con '$Distro' (usuario $wslUser)"

# ---------------------------------------------------------------------------
# 2. Docker Desktop
# ---------------------------------------------------------------------------
Write-Step "Verificando Docker Desktop"

$dockerExe = Find-Docker
if (-not $dockerExe) {
    Write-Host "    Docker Desktop no esta instalado."
    if (-not (Get-Command winget.exe -ErrorAction SilentlyContinue)) {
        Stop-WithHelp "No se encontro winget. Instala Docker Desktop desde https://www.docker.com/products/docker-desktop/"
    }
    if (-not (Confirm-Action "Instalar Docker Desktop con winget (winget install -e --id Docker.DockerDesktop)?")) {
        Stop-WithHelp "Instala Docker Desktop manualmente desde https://www.docker.com/products/docker-desktop/"
    }
    & winget.exe install -e --id Docker.DockerDesktop --accept-package-agreements --accept-source-agreements
    Stop-WithHelp "Docker Desktop quedo instalado. Abrilo una vez, acepta los terminos y espera a que diga 'Engine running'."
}

if (-not (Test-DockerDaemon $dockerExe)) {
    $desktopExe = Find-DockerDesktopExe
    if ($desktopExe) {
        Write-Host "    El daemon de Docker no responde: abriendo Docker Desktop..."
        Start-Process -FilePath $desktopExe
    }
    $deadline = (Get-Date).AddMinutes(3)
    while (-not (Test-DockerDaemon $dockerExe)) {
        if ((Get-Date) -gt $deadline) { Stop-WithHelp "Docker Desktop no arranco en 3 minutos. Abrilo a mano y revisa que diga 'Engine running'." }
        Start-Sleep -Seconds 5
    }
}
Write-Ok "Docker Desktop responde ($dockerExe)"

# ---------------------------------------------------------------------------
# 3. docker dentro de WSL (integracion de Docker Desktop)
# ---------------------------------------------------------------------------
Write-Step "Verificando que docker funcione dentro de '$Distro'"

if ($null -eq (Get-FromWsl 'docker version --format {{.Server.Version}}')) {
    Stop-WithHelp ("docker no esta disponible dentro de '$Distro'. En Docker Desktop: Settings > Resources > " +
        "WSL integration > activa '$Distro' > 'Apply & restart'. Despues corre 'wsl --terminate $Distro'.")
}
Write-Ok "docker funciona dentro de '$Distro'"

# ---------------------------------------------------------------------------
# 4. git y credenciales dentro de WSL
# ---------------------------------------------------------------------------
Write-Step "Verificando git y el acceso al repo desde WSL"

if ($null -eq (Get-FromWsl 'git --version')) {
    if (-not (Confirm-Action "git no esta instalado en '$Distro'. Instalarlo con apt (pide tu contrasena de Linux)?")) {
        Stop-WithHelp "Instala git en WSL con: sudo apt-get update && sudo apt-get install -y git"
    }
    if ((Invoke-InWsl 'sudo apt-get update && sudo apt-get install -y git') -ne 0) { Stop-WithHelp "No se pudo instalar git en WSL." }
}

$isRemote = $RepoUrl -match '^(https?|ssh|git)://|^git@'
if ($isRemote -and ((Invoke-InWsl "GIT_TERMINAL_PROMPT=0 git ls-remote '$RepoUrl' > /dev/null 2>&1") -ne 0)) {
    Write-Host "    Git en WSL no tiene acceso al repo (es privado)."
    $gcmWindows = Join-Path $env:ProgramFiles 'Git\mingw64\bin\git-credential-manager.exe'
    if (-not (Test-Path $gcmWindows)) {
        Stop-WithHelp ("No se encontro Git for Windows con Git Credential Manager. Configura credenciales en WSL " +
            "(por ejemplo, 'gh auth login' dentro de WSL) o instala Git for Windows.")
    }
    $gcmWsl = ConvertTo-WslPath $gcmWindows
    if (-not (Confirm-Action "Configurar el Git Credential Manager de Windows en el git de WSL (git config --global credential.helper)?")) {
        Stop-WithHelp "Configura credenciales de GitHub dentro de WSL y volve a correr el script."
    }
    if ((Invoke-InWsl "git config --global credential.helper '$($gcmWsl -replace ' ', '\ ')'") -ne 0) { Stop-WithHelp "No se pudo configurar el credential helper." }
    Write-Host "    Puede abrirse una ventana para iniciar sesion en GitHub."
    if ((Invoke-InWsl "git ls-remote '$RepoUrl' > /dev/null") -ne 0) { Stop-WithHelp "Sigue sin haber acceso al repo. Revisa que tu usuario de GitHub tenga permisos." }
}
Write-Ok "git tiene acceso a $RepoUrl"

# El git de WSL es otro git, distinto del de Windows, y arranca sin identidad: sin esto el primer
# commit falla, o queda con un email que GitHub no asocia a la cuenta (commit "sin usuario").
# Se usa el email noreply de GitHub (<id>+<usuario>@users.noreply.github.com): siempre se asocia
# a la cuenta y no expone el email personal.
Write-Step "Verificando la identidad de git en WSL"

$gitName = Get-FromWsl 'git config --global user.name'
$gitEmail = Get-FromWsl 'git config --global user.email'
if ($gitName -and $gitEmail) {
    Write-Ok "git en WSL firma como: $gitName <$gitEmail>"
} else {
    Write-Host "    git en WSL no tiene nombre y/o email configurados."
    if (-not $GitHubUser) {
        if ($Yes) { Stop-WithHelp "Falta la identidad de git en WSL. Volve a correr con -GitHubUser <tu-usuario-de-GitHub>." }
        $GitHubUser = (Read-Host "    Tu usuario de GitHub (por ejemplo, nobodyh-ctrl)").Trim()
    }
    if ($GitHubUser -notmatch '^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$') { Stop-WithHelp "'$GitHubUser' no es un usuario de GitHub valido." }

    try {
        $account = Invoke-RestMethod -Uri "https://api.github.com/users/$GitHubUser" -Headers @{ 'User-Agent' = 'api-sportiva-setup' }
    } catch {
        Stop-WithHelp "No se pudo consultar el usuario '$GitHubUser' en GitHub (existe? hay internet?). Detalle: $($_.Exception.Message)"
    }

    $newName = if ($gitName) { $gitName } elseif ($account.name) { $account.name } else { $account.login }
    $newEmail = if ($gitEmail) { $gitEmail } else { "$($account.id)+$($account.login)@users.noreply.github.com" }

    if (-not (Confirm-Action "Configurar git en WSL (git config --global) como: $newName <$newEmail>?")) {
        Stop-WithHelp ("Configura la identidad manualmente en WSL: git config --global user.name ""Tu Nombre"" " +
            "y git config --global user.email ""<id>+<usuario>@users.noreply.github.com"".")
    }
    $configured = Invoke-InWsl ("git config --global user.name " + (ConvertTo-BashLiteral $newName) +
        " && git config --global user.email " + (ConvertTo-BashLiteral $newEmail))
    if ($configured -ne 0) { Stop-WithHelp "No se pudo configurar la identidad de git en WSL." }
    Write-Ok "git en WSL firma como: $newName <$newEmail>"
}

# ---------------------------------------------------------------------------
# 5. Clonar dentro de WSL
# ---------------------------------------------------------------------------
Write-Step "Clonando el repo en ~/$TargetDir (dentro de WSL)"

if ((Invoke-InWsl "test -d ~/$TargetDir/.git") -eq 0) {
    Write-Ok "Ya existe un clon en ~/${TargetDir}: se usa tal como esta (no se hace pull)"
} elseif ((Invoke-InWsl "test -e ~/$TargetDir") -eq 0) {
    Stop-WithHelp "~/$TargetDir ya existe y no es un repo git. Movelo o usa otro -TargetDir."
} else {
    # Solo se crea la carpeta padre: git clone crea la de destino. Si el clone falla, no queda
    # una carpeta vacia que haga fallar la siguiente corrida ("ya existe y no es un repo git").
    if ((Invoke-InWsl "mkdir -p `$(dirname ~/$TargetDir) && git clone '$RepoUrl' ~/$TargetDir") -ne 0) {
        Stop-WithHelp "Fallo el git clone."
    }
    Write-Ok "Clonado en ~/$TargetDir"
}

# ---------------------------------------------------------------------------
# 6. Setup del proyecto y VS Code
# ---------------------------------------------------------------------------
Write-Step "Corriendo scripts/setup.sh dentro de WSL"

if ((Invoke-InWsl "cd ~/$TargetDir && bash scripts/setup.sh") -ne 0) {
    Stop-WithHelp "scripts/setup.sh fallo. Revisa el error de arriba y la seccion de troubleshooting de docs/desarrollo-local.md."
}

$wslHome = Get-FromWsl 'echo $HOME'
$projectPath = "$wslHome/$TargetDir"
if (-not $NoOpen -and (Get-Command code -ErrorAction SilentlyContinue)) {
    Write-Step "Abriendo VS Code (Remote WSL)"
    & code --remote "wsl+$Distro" $projectPath
}

$uncPath = '\\wsl$\' + $Distro + ($projectPath -replace '/', '\')
Write-Host "`nListo. El proyecto esta en $uncPath" -ForegroundColor Green
Write-Host "Desde una terminal de WSL:  cd ~/$TargetDir && docker compose ps" -ForegroundColor Green
