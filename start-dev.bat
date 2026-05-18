@echo off
setlocal

set "PROJECT_DIR=C:\Users\Benoit\dashboard-dms"

echo Démarrage de Laravel (http://127.0.0.1:8000) ...
start "Laravel" cmd /k "cd /d "%PROJECT_DIR%" && php artisan serve"

echo Démarrage de Vite (http://localhost:5173) ...
start "Vite" cmd /k "cd /d "%PROJECT_DIR%" && npm run dev"

echo Les deux serveurs sont lancés dans des fenêtres séparées.
