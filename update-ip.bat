@echo off
:: Lance le script de mise a jour de l'IP en tant qu'administrateur
:: Double-cliquez sur ce fichier pour mettre a jour l'IP du serveur DMS CCCM

echo Mise a jour de l'IP du serveur DMS CCCM...
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0update-server-ip.ps1"
