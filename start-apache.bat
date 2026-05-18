@echo off
setlocal

set "APACHE_EXE=C:\xampp\apache\bin\httpd.exe"
set "APP_URL=http://127.0.0.1:8080/dashboard"
set "APACHE_DIR=C:\xampp\apache\bin"

title Apache Dashboard DMS

if not exist "%APACHE_EXE%" (
    echo [ERREUR] Apache introuvable: %APACHE_EXE%
    echo.
    pause
    exit /b 1
)

netstat -ano | find ":8080" | find "LISTENING" >nul
if errorlevel 1 (
    echo Demarrage d'Apache...
    pushd "%APACHE_DIR%"
    start "Apache Dashboard DMS" "%APACHE_EXE%"
    popd
) else (
    echo Apache est deja en cours d'execution.
)

echo.
echo Apache est disponible sur:
echo   %APP_URL%
echo.
pause