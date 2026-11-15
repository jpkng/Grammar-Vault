@echo off
setlocal EnableExtensions
title Grammar Vault - Install PHP
set "GV_SELF=%~f0"
powershell -NoProfile -ExecutionPolicy Bypass -Command "$s=[IO.File]::ReadAllText($env:GV_SELF); $i=$s.IndexOf('#PSBEGIN'+'#'); & ([scriptblock]::Create($s.Substring($i)))"
set "RC=%ERRORLEVEL%"
echo.
if "%RC%"=="0" (
  echo  All done. Close this window, then open a NEW terminal or
  echo  double-click start_game.bat.
) else (
  echo  The install did not finish. Read the message above, then run
  echo  this file again.
)
echo.
pause
exit /b %RC%
#PSBEGIN#
$ErrorActionPreference = 'Stop'
$ProgressPreference    = 'SilentlyContinue'   # makes downloads much faster
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}
$InstallDir = 'C:\php'
function Say($msg, $color = 'White') { Write-Host $msg -ForegroundColor $color }
# Turns on the settings Grammar Vault needs in a php.ini file.
function Set-PhpIni($dir) {
    $ini = Join-Path $dir 'php.ini'
    if (-not (Test-Path $ini)) {
        $dev = Join-Path $dir 'php.ini-development'
        if (-not (Test-Path $dev)) { throw "php.ini-development not found in $dir" }
        Copy-Item $dev $ini
    }
    $txt = [IO.File]::ReadAllText($ini)
    $txt = [regex]::Replace($txt, '(?m)^[ \t]*;[ \t]*(extension_dir[ \t]*=[ \t]*"ext")', '$1')
    foreach ($e in 'pdo_sqlite', 'sqlite3', 'mbstring') {
        $txt = [regex]::Replace($txt, "(?m)^[ \t]*;[ \t]*(extension=$e)[ \t]*(\r?)$", '$1$2')
    }
    [IO.File]::WriteAllText($ini, $txt, (New-Object Text.UTF8Encoding($false)))
}
function Test-Sqlite($php) {
    $prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'   # PHP warnings go to stderr
    & $php -r "exit((class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers())) ? 0 : 1);" 2>$null
    $ok = ($LASTEXITCODE -eq 0)
    $ErrorActionPreference = $prev
    return $ok
}
try {
    Say ''
    Say '=== Grammar Vault: PHP installer ===' Cyan
    if (-not [Environment]::Is64BitOperatingSystem) { throw 'This installer needs 64-bit Windows.' }
    # ---- 1. Is PHP already installed? ----
    $found = Get-Command php.exe -ErrorAction SilentlyContinue
    if (-not $found -and (Test-Path "$InstallDir\php.exe")) { $found = Get-Item "$InstallDir\php.exe" }
    if ($found) {
        $phpExe = if ($found.PSObject.Properties['Source']) { $found.Source } else { $found.FullName }
        Say "PHP is already installed: $phpExe" Green
        & $phpExe -v | Select-Object -First 1
        if (-not (Test-Sqlite $phpExe)) {
            Say 'The SQLite driver is off - turning it on...' Yellow
            Set-PhpIni (Split-Path $phpExe)
        }
        if (-not (Test-Sqlite $phpExe)) { throw 'PHP is installed but the SQLite driver still would not load.' }
        # make sure it is on PATH even if it was found only at C:\php
        $dirOfPhp = Split-Path $phpExe
        $userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
        if ($null -eq $userPath) { $userPath = '' }
        $have = @(($userPath, [Environment]::GetEnvironmentVariable('Path', 'Machine')) -join ';' -split ';' |
                  ForEach-Object { $_.Trim().TrimEnd('\') }) -contains $dirOfPhp.TrimEnd('\')
        if (-not $have) {
            [Environment]::SetEnvironmentVariable('Path', (($userPath.TrimEnd(';') + ';' + $dirOfPhp).TrimStart(';')), 'User')
            Say "Added $dirOfPhp to your PATH." Green
        }
        Say 'Nothing more to install. PHP and SQLite are ready.' Green
        exit 0
    }
    # ---- 2. Find the newest PHP 8 build ----
    Say 'Looking up the latest PHP 8 release...'
    $rel = Invoke-RestMethod -Uri 'https://windows.php.net/downloads/releases/releases.json' -UseBasicParsing
    $best = $null
    foreach ($p in $rel.PSObject.Properties) {
        if ($p.Name -notmatch '^8\.\d+$') { continue }
        $build = $p.Value.'nts-vs17-x64'
        if (-not $build -or -not $build.zip) { continue }
        $ver = [version]$p.Value.version
        if (-not $best -or $ver -gt $best.Version) { $best = @{ Version = $ver; Zip = $build.zip } }
    }
    if (-not $best) { throw 'Could not find a PHP 8 build on windows.php.net.' }
    $url  = 'https://windows.php.net/downloads/releases/' + $best.Zip.path
    $zip  = Join-Path $env:TEMP $best.Zip.path
    Say ("Found PHP {0}" -f $best.Version) Green
    # ---- 3. Download + verify ----
    Say 'Downloading (about 35 MB)...'
    Invoke-WebRequest -Uri $url -OutFile $zip -UseBasicParsing
    if ($best.Zip.sha256) {
        $got = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLower()
        if ($got -ne $best.Zip.sha256.ToLower()) { Remove-Item $zip -Force; throw 'The download is corrupted (checksum mismatch). Please run this file again.' }
        Say 'Checksum OK.' Green
    }
    # ---- 4. Unzip to C:\php ----
    Say "Installing to $InstallDir ..."
    New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null
    Expand-Archive -Path $zip -DestinationPath $InstallDir -Force
    if (-not (Test-Path "$InstallDir\php.exe")) {
        # in case a future zip wraps everything in one folder
        $inner = Get-ChildItem $InstallDir -Directory | Where-Object { Test-Path (Join-Path $_.FullName 'php.exe') } | Select-Object -First 1
        if ($inner) { Get-ChildItem $inner.FullName | Move-Item -Destination $InstallDir -Force }
    }
    if (-not (Test-Path "$InstallDir\php.exe")) { throw 'php.exe was not found after unzipping.' }
    Remove-Item $zip -Force -ErrorAction SilentlyContinue
    # ---- 5. php.ini + SQLite ----
    Say 'Setting up php.ini (SQLite driver)...'
    Set-PhpIni $InstallDir
    # ---- 6. Visual C++ runtime ----
    $vc = Get-ItemProperty 'HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64' -ErrorAction SilentlyContinue
    if (-not $vc -or $vc.Installed -ne 1) {
        Say 'Installing the Microsoft Visual C++ runtime (click Yes if Windows asks)...'
        $vcExe = Join-Path $env:TEMP 'vc_redist.x64.exe'
        Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $vcExe -UseBasicParsing
        $proc = Start-Process -FilePath $vcExe -ArgumentList '/install', '/quiet', '/norestart' -Wait -PassThru
        if ($proc.ExitCode -notin 0, 1638, 3010) { throw "The Visual C++ runtime install failed (code $($proc.ExitCode))." }
        Remove-Item $vcExe -Force -ErrorAction SilentlyContinue
    }
    # ---- 7. Add C:\php to PATH (your user account; no admin needed) ----
    $userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
    if ($null -eq $userPath) { $userPath = '' }
    $parts = @($userPath -split ';' | ForEach-Object { $_.Trim().TrimEnd('\') } | Where-Object { $_ })
    if ($parts -notcontains $InstallDir) {
        $new = ($userPath.TrimEnd(';') + ';' + $InstallDir).TrimStart(';')
        [Environment]::SetEnvironmentVariable('Path', $new, 'User')
        Say "Added $InstallDir to your PATH." Green
    } else {
        Say "$InstallDir was already on your PATH." Green
    }
    $env:Path = $env:Path + ';' + $InstallDir
    # ---- 8. Check it works ----
    Say ''
    & "$InstallDir\php.exe" -v | Select-Object -First 1
    if (Test-Sqlite "$InstallDir\php.exe") {
        Say 'SQLite driver: ON' Green
    } else {
        throw 'PHP installed, but the SQLite driver did not load.'
    }
    Say ''
    Say 'PHP is installed!' Green
    exit 0
}
catch {
    Say ''
    Say ('ERROR: ' + $_.Exception.Message) Red
    Say 'If this keeps happening, check your internet connection, or install PHP by hand (see README.txt step 1).' Yellow
    exit 1
}
