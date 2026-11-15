@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Grammar Vault Shared Server
set "PORT=8000"
set "PHP=php"
where php >nul 2>&1
if errorlevel 1 (
  if exist "C:\php\php.exe" (
    set "PHP=C:\php\php.exe"
  ) else (
    goto :nophp
  )
)
if not exist "index2.html" goto :nofiles
if not exist "router.php" goto :nofiles
if not exist "results_api.php" goto :nofiles
if not exist "teacher.php" goto :nofiles
if not exist "teacher_gate.php" goto :nofiles
"%PHP%" -r "exit((class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers())) ? 0 : 1);"
if errorlevel 1 goto :nosqlite
set "IP="
for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "$s=New-Object Net.Sockets.UdpClient;$s.Connect('8.8.8.8',80);$s.Client.LocalEndPoint.Address.ToString()" 2^>nul`) do set "IP=%%I"
if not defined IP (
  for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /C:"IPv4"') do if not defined IP set "IP=%%A"
)
if defined IP set "IP=%IP: =%"
if not defined IP goto :noip
net session >nul 2>&1
if not errorlevel 1 (
  netsh advfirewall firewall show rule name="Grammar Vault (port %PORT%)" >nul 2>&1
  if errorlevel 1 (
    netsh advfirewall firewall add rule name="Grammar Vault (port %PORT%)" dir=in action=allow protocol=TCP localport=%PORT% profile=private >nul
    echo Firewall: opened port %PORT% for private networks.
  )
) else (
  echo Note: if Windows asks whether to allow PHP on the network, click Allow
  echo       and tick PRIVATE networks. ^(Or right-click this file and choose
  echo       Run as administrator once to set it up automatically.^)
  echo.
)
netstat -ano | findstr /R /C:"0.0.0.0:%PORT% .*LISTENING" >nul
if not errorlevel 1 (
  echo A shared server is already running on port %PORT%. Using it.
  goto :announce
)
netstat -ano | findstr /R /C:":%PORT% .*LISTENING" >nul
if not errorlevel 1 goto :portbusy
echo Starting the shared Grammar Vault server...
start "Grammar Vault SHARED Server (close this window to stop)" cmd /k "%PHP% -S 0.0.0.0:%PORT% router.php"
timeout /t 2 /nobreak >nul
:announce
echo http://%IP%:%PORT%/index2.html| clip
cls
echo ============================================================
echo   GRAMMAR VAULT IS SHARED ON YOUR NETWORK
echo ============================================================
echo.
echo   Tell players to open this address in their browser:
echo.
echo       http://%IP%:%PORT%/index2.html
echo.
echo   ^(It has been copied to your clipboard - just paste it.^)
echo.
echo   Teacher dashboard ^(open it on THIS computer^):
echo       http://localhost:%PORT%/teacher.php
echo.
echo   - Players must be on the same Wi-Fi / network as this computer.
echo   - Keep the "Grammar Vault SHARED Server" window open.
echo     Close it to stop sharing.
echo   - All results are saved on this computer in data\grammar_vault.sqlite
echo   - Voice answers ^(microphone^) only work on this computer; on other
echo     devices the game still works, but they type their answers.
echo.
echo   Opening the teacher dashboard now...
start "" "http://localhost:%PORT%/teacher.php"
echo.
pause
exit /b 0
:portbusy
echo.
echo  Port %PORT% is already in use by a server that is NOT shared
echo  ^(for example one started with start_game.bat^).
echo  Close that server window, then run this file again.
echo.
pause
exit /b 1
:noip
echo.
echo  Could not find this computer's network address.
echo  Connect to Wi-Fi or a network, then run this file again.
echo.
pause
exit /b 1
:nofiles
echo.
echo  Some game files are missing from this folder:
echo  %CD%
echo  Keep index2.html, router.php, results_api.php, teacher.php,
echo  teacher_gate.php and the other game files together with this file.
echo.
pause
exit /b 1
:nosqlite
echo.
echo  PHP is missing the SQLite driver, which the results database needs.
echo  1. Run:  php --ini     and find "Loaded Configuration File".
echo     If it says ^(none^), copy php.ini-development to php.ini in your PHP folder.
echo  2. Open php.ini in Notepad and remove the semicolon from the start of:
echo        extension_dir = "ext"
echo        extension=pdo_sqlite
echo        extension=sqlite3
echo  3. Save, then run this file again.
echo.
pause
exit /b 1
:nophp
echo.
echo  PHP was not found on this computer.
echo  Install PHP and add it to PATH, or unpack it to C:\php
echo  ^(Only THIS computer needs PHP - players do not.^)
echo.
pause
exit /b 1
