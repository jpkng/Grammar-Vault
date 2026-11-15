@echo off
setlocal EnableExtensions
cd /d "%~dp0"
title Grammar Vault Teacher
set "PHP=php"
where php >nul 2>&1
if errorlevel 1 (
  if exist "C:\php\php.exe" (
    set "PHP=C:\php\php.exe"
  ) else (
    goto :nophp
  )
)
call "%~dp0start_game.bat" teacher
exit /b 0
:nophp
echo.
echo  PHP was not found on this computer.
echo  Double-click install_php.bat in this folder to install it automatically.
echo  (Or install PHP by hand and add it to PATH, or unpack it to C:\php)
echo  See the setup instructions, then run this file again.
echo.
pause
exit /b 1
