@echo off
setlocal

:: Solicita permisos de administrador si el archivo no se inicio elevado.
net session >nul 2>&1
if not "%errorlevel%"=="0" (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

:: Cada proceso se abre en su propia ventana de PowerShell.
start "Laravel Server" powershell.exe -NoExit -NoProfile -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%~dp0'; php artisan serve"
start "Vite" powershell.exe -NoExit -NoProfile -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%~dp0'; npm run dev"
start "Laravel Queue" powershell.exe -NoExit -NoProfile -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%~dp0'; php artisan queue:work database --queue=ai,default --tries=1 --timeout=120"
start "Laravel Scheduler" powershell.exe -NoExit -NoProfile -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%~dp0'; php artisan schedule:work"

endlocal
