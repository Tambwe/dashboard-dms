# ============================================================
#  update-server-ip.ps1
#  Met a jour l'IP du serveur DMS CCCM automatiquement
#  Usage : clic-droit > Executer avec PowerShell  (ou double-clic sur update-ip.bat)
# ============================================================

$ErrorActionPreference = "Stop"
$PROJECT_ROOT  = "C:\Users\Benoit\dashboard-dms"
$ENV_FILE      = "$PROJECT_ROOT\.env"
$VHOSTS_FILE   = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
$HTTPD_FILE    = "C:\xampp\apache\conf\httpd.conf"
$PORT          = 8080
$ARTISAN       = "C:\xampp\php\php.exe"

# ── 1. Detecter l'IP Wi-Fi / Ethernet active ─────────────────────────
Write-Host "`n[1/5] Detection de l'adresse IP reseau..." -ForegroundColor Cyan

$ip = (Get-NetIPAddress -AddressFamily IPv4 `
    | Where-Object { $_.IPAddress -notlike "127.*" -and $_.IPAddress -notlike "169.*" -and $_.PrefixOrigin -ne "WellKnown" } `
    | Sort-Object -Property InterfaceIndex `
    | Select-Object -First 1).IPAddress

if (-not $ip) {
    # Fallback via ipconfig si Get-NetIPAddress echoue
    $ipconfigOutput = ipconfig | Select-String "IPv4"
    $match = $ipconfigOutput | Where-Object { $_ -notmatch "127\.|169\." } | Select-Object -First 1
    if ($match) {
        $ip = ($match -split ":")[1].Trim()
    }
}

if (-not $ip) {
    Write-Host "  ERREUR : Impossible de detecter l'IP reseau. Verifiez votre connexion." -ForegroundColor Red
    Read-Host "Appuyez sur Entree pour quitter"
    exit 1
}

Write-Host "  IP detectee : $ip" -ForegroundColor Green

# ── 2. Lire l'IP actuelle dans .env ──────────────────────────────────
Write-Host "`n[2/5] Lecture de la configuration actuelle..." -ForegroundColor Cyan

$envContent = Get-Content $ENV_FILE -Raw
$currentMatch = [regex]::Match($envContent, 'APP_URL=http://([^:]+):(\d+)')
$currentIp   = if ($currentMatch.Success) { $currentMatch.Groups[1].Value } else { "" }
$currentPort = if ($currentMatch.Success) { $currentMatch.Groups[2].Value } else { $PORT }

Write-Host "  APP_URL actuel : http://$currentIp`:$currentPort"

if ($currentIp -eq $ip) {
    Write-Host "  L'IP n'a pas change ($ip). Rien a faire." -ForegroundColor Yellow
    Read-Host "`nAppuyez sur Entree pour quitter"
    exit 0
}

# ── 3. Mettre a jour .env ─────────────────────────────────────────────
Write-Host "`n[3/5] Mise a jour de .env..." -ForegroundColor Cyan

$envContent = $envContent -replace 'APP_URL=http://[^:]+:\d+', "APP_URL=http://$ip`:$PORT"
[System.IO.File]::WriteAllText($ENV_FILE, $envContent, [System.Text.Encoding]::UTF8)
Write-Host "  APP_URL=http://$ip`:$PORT" -ForegroundColor Green

# ── 4. Mettre a jour httpd-vhosts.conf et httpd.conf ─────────────────
Write-Host "`n[4/5] Mise a jour de la configuration Apache..." -ForegroundColor Cyan

# httpd-vhosts.conf : ServerName
$vhosts = Get-Content $VHOSTS_FILE -Raw
$vhosts = $vhosts -replace "ServerName $currentIp", "ServerName $ip"
[System.IO.File]::WriteAllText($VHOSTS_FILE, $vhosts, [System.Text.Encoding]::UTF8)
Write-Host "  httpd-vhosts.conf : ServerName mis a jour -> $ip" -ForegroundColor Green

# httpd.conf : Listen IP:PORT (la ligne avec l'ancienne IP)
$httpd = Get-Content $HTTPD_FILE -Raw
if ($currentIp -and $httpd -match "Listen $currentIp`:$PORT") {
    $httpd = $httpd -replace "Listen $currentIp`:$PORT", "Listen $ip`:$PORT"
    [System.IO.File]::WriteAllText($HTTPD_FILE, $httpd, [System.Text.Encoding]::UTF8)
    Write-Host "  httpd.conf       : Listen mis a jour -> $ip`:$PORT" -ForegroundColor Green
} else {
    Write-Host "  httpd.conf       : directive Listen sans IP fixe, pas de changement." -ForegroundColor DarkGray
}

# ── 5. Vider le cache Laravel + redemarrer Apache ─────────────────────
Write-Host "`n[5/5] Redemarrage des services..." -ForegroundColor Cyan

# Vider le cache Laravel
if (Test-Path $ARTISAN) {
    Push-Location $PROJECT_ROOT
    & $ARTISAN artisan config:clear 2>$null
    & $ARTISAN artisan cache:clear  2>$null
    Pop-Location
    Write-Host "  Cache Laravel vide." -ForegroundColor Green
}

# Redemarrer Apache
$apacheProc = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apacheProc) {
    Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 1
}
Start-Process "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
Start-Sleep -Seconds 2
$check = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($check) {
    Write-Host "  Apache demarre (PID: $($check.Id -join ', '))." -ForegroundColor Green
} else {
    Write-Host "  ATTENTION : Apache ne semble pas demarre. Verifiez XAMPP." -ForegroundColor Red
}

# ── Resume ─────────────────────────────────────────────────────────────
Write-Host "`n============================================" -ForegroundColor White
Write-Host "  Mise a jour terminee avec succes!" -ForegroundColor Green
Write-Host "  URL locale  : http://127.0.0.1:$PORT" -ForegroundColor White
Write-Host "  URL reseau  : http://$ip`:$PORT" -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor White

Read-Host "Appuyez sur Entree pour quitter"
