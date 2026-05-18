@echo off
setlocal

set "SDK_DIR=C:\Users\Benoit\Downloads\MULTISCAN-SDK_5-2-1_2025-06-03\4.SDK\RUN_WIN_X86-64"
set "JAR_NAME=webapi-1.2.8.jar"
set "PORT=8090"

if not exist "%SDK_DIR%\%JAR_NAME%" (
  echo [ERROR] JAR introuvable: %SDK_DIR%\%JAR_NAME%
  exit /b 1
)

where java >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Java non trouve dans le PATH
  exit /b 1
)

cd /d "%SDK_DIR%"
set "PATH=%SDK_DIR%;%PATH%"

echo Lancement de %JAR_NAME% sur le port %PORT%...
java -jar "%JAR_NAME%" --server.port=%PORT%
