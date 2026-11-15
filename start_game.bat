@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Grammar Vault Launcher
set "PORT=8000"
set "PAGE=index2.html"
if /i "%~1"=="teacher" set "PAGE=teacher.php"
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
"%PHP%" -r "exit((class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers())) ? 0 : 1);"
if errorlevel 1 goto :nosqlite
netstat -ano | findstr /R /C:":%PORT% .*LISTENING" >nul
if not errorlevel 1 (
  echo A server is already running on port %PORT%. Using it.
  goto :openpage
)
echo Starting the Grammar Vault server...
start "Grammar Vault Server (close this window to stop)" cmd /k "%PHP% -S localhost:%PORT% router.php"
timeout /t 2 /nobreak >nul
:openpage
echo Opening http://localhost:%PORT%/%PAGE%
start "" "http://localhost:%PORT%/%PAGE%"
echo.
echo  Game:    http://localhost:%PORT%/index2.html
echo  Teacher: http://localhost:%PORT%/teacher.php
echo.
echo  Keep the "Grammar Vault Server" window open while playing.
echo  Close that window to stop the server.
timeout /t 8 >nul
exit /b 0
:nofiles
echo.
echo  Some game files are missing from this folder:
echo  %CD%
echo  Keep index2.html, router.php, results_api.php and the other
echo  game files together with this .bat file.
echo.
pause
exit /b 1
:nosqlite
echo.
echo  PHP is missing the SQLite driver, which the results database needs.
echo  1. Run:  php --ini     and find "Loaded Configuration File".
echo     If it says (none), copy php.ini-development to php.ini in your PHP folder.
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
echo  Double-click install_php.bat in this folder to install it automatically.
echo  (Or install PHP by hand and add it to PATH, or unpack it to C:\php)
echo  See the setup instructions, then run this file again.
echo.
pause
exit /b 1
