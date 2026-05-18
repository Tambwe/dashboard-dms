@echo off
REM Script de lancement du serveur WebSocket Thales
REM Dashboard DMS - Version 1.0

title Serveur WebSocket Thales
color 0A

echo.
echo ========================================================
echo    SERVEUR WEBSOCKET THALES - LANCEMENT
echo    Dashboard DMS
echo ========================================================
echo.

REM Vérifier que Node.js est installé
where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Node.js n'est pas installe!
    echo.
    echo Telechargez Node.js depuis: https://nodejs.org
    echo Installez la version LTS recommandee
    echo.
    pause
    exit /b 1
)

echo [OK] Node.js detecte
node --version
echo.

REM Vérifier si le module ws est installé
if not exist "node_modules\ws" (
    echo [INFO] Installation des dependances...
    echo.
    
    REM Créer package.json si nécessaire
    if not exist "package.json" (
        if exist "package-websocket.json" (
            copy package-websocket.json package.json >nul
            echo [OK] package.json cree
        )
    )
    
    call npm install ws
    
    if %ERRORLEVEL% NEQ 0 (
        echo.
        echo [ERREUR] Echec installation du module ws
        echo.
        pause
        exit /b 1
    )
    
    echo.
    echo [OK] Module ws installe avec succes
    echo.
) else (
    echo [OK] Module ws deja installe
    echo.
)

REM Vérifier que le port 8181 n'est pas déjà utilisé
netstat -ano | findstr ":8181" >nul
if %ERRORLEVEL% EQU 0 (
    echo [ATTENTION] Le port 8181 est deja utilise!
    echo.
    echo Un autre serveur est peut-etre deja en cours d'execution.
    echo Fermez-le avant de continuer ou changez le port dans le code.
    echo.
    pause
)

echo ========================================================
echo    DEMARRAGE DU SERVEUR
echo ========================================================
echo.
echo Le serveur WebSocket va demarrer sur:
echo   ws://localhost:8181
echo.
echo Pour tester:
echo   1. Ouvrez: http://127.0.0.1:8000/test-thales-scanner.html
echo   2. Cliquez "Tester Connexion"
echo   3. Cliquez "Capturer Empreinte"
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.
echo ========================================================
echo.

REM Lancer le serveur
node thales-websocket-server.js

REM Si le serveur s'arrête
echo.
echo [INFO] Serveur arrete
pause
