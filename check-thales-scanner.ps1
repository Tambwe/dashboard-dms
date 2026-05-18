#Requires -Version 5.1
<#
.SYNOPSIS
    Script de diagnostic pour Scanner Thales Multifinger
    
.DESCRIPTION
    Vérifie l'installation, la configuration et le fonctionnement du scanner Thales
    et de son service WebSocket.
    
.EXAMPLE
    .\check-thales-scanner.ps1
    
.NOTES
    Version: 1.0
    Auteur: Dashboard DMS
    Date: Janvier 2025
#>

# Configuration
$ThalesWSPort = 8181
$ThalesWSUrl = "ws://localhost:$ThalesWSPort"
$TestDuration = 5000 # millisecondes

# Couleurs pour la sortie
function Write-Success { param($Message) Write-Host "[✓] $Message" -ForegroundColor Green }
function Write-Error { param($Message) Write-Host "[✗] $Message" -ForegroundColor Red }
function Write-Warning { param($Message) Write-Host "[⚠] $Message" -ForegroundColor Yellow }
function Write-Info { param($Message) Write-Host "[i] $Message" -ForegroundColor Cyan }
function Write-Section { param($Title) Write-Host "`n=== $Title ===" -ForegroundColor Magenta }

# Bannière
Clear-Host
Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   DIAGNOSTIC SCANNER THALES MULTIFINGER              ║" -ForegroundColor Cyan
Write-Host "║   Version 1.0 - Dashboard DMS                        ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Variables de résultat
$AllChecks = @{
    UsbDevice = $false
    Drivers = $false
    Service = $false
    WebSocket = $false
    Port = $false
}

# ============================================
# 1. VÉRIFICATION MATÉRIEL USB
# ============================================
Write-Section "1. VÉRIFICATION MATÉRIEL USB"

$ThalesDevices = Get-PnpDevice | Where-Object {
    $_.FriendlyName -like "*Thales*" -or 
    $_.FriendlyName -like "*Biometric*" -or
    $_.FriendlyName -like "*Fingerprint*"
}

if ($ThalesDevices) {
    Write-Success "Scanner(s) Thales détecté(s):"
    foreach ($device in $ThalesDevices) {
        $status = if ($device.Status -eq "OK") { "✓" } else { "✗" }
        $color = if ($device.Status -eq "OK") { "Green" } else { "Red" }
        Write-Host "  $status $($device.FriendlyName) [$($device.Status)]" -ForegroundColor $color
        
        if ($device.Status -eq "OK") {
            $AllChecks.UsbDevice = $true
        }
    }
    
    if (-not $AllChecks.UsbDevice) {
        Write-Error "Aucun scanner Thales avec status OK trouvé"
        Write-Warning "Actions suggérées:"
        Write-Host "  - Vérifiez le branchement USB"
        Write-Host "  - Essayez un autre port USB"
        Write-Host "  - Redémarrez l'ordinateur"
    }
} else {
    Write-Error "Aucun scanner Thales détecté"
    Write-Warning "Actions suggérées:"
    Write-Host "  - Vérifiez que le scanner est branché"
    Write-Host "  - Installez les pilotes Thales"
    Write-Host "  - Ouvrez 'Gestionnaire de périphériques' pour plus d'infos"
}

# ============================================
# 2. VÉRIFICATION PILOTES
# ============================================
Write-Section "2. VÉRIFICATION PILOTES"

$ThalesDrivers = Get-WindowsDriver -Online | Where-Object {
    $_.ProviderName -like "*Thales*" -or
    $_.ClassName -like "*Biometric*"
}

if ($ThalesDrivers) {
    Write-Success "Pilote(s) Thales installé(s):"
    foreach ($driver in $ThalesDrivers) {
        Write-Host "  • $($driver.ClassName) - Version $($driver.Version)" -ForegroundColor Green
    }
    $AllChecks.Drivers = $true
} else {
    Write-Warning "Aucun pilote Thales spécifique trouvé"
    Write-Info "Vérification alternative via Gestionnaire de périphériques..."
    
    # Vérifier via WMI comme alternative
    $WmiDrivers = Get-WmiObject Win32_PnPSignedDriver | Where-Object {
        $_.DeviceName -like "*Thales*" -or $_.DeviceName -like "*Biometric*"
    }
    
    if ($WmiDrivers) {
        Write-Success "Pilote détecté via WMI:"
        foreach ($drv in $WmiDrivers) {
            Write-Host "  • $($drv.DeviceName) - $($drv.DriverVersion)" -ForegroundColor Green
        }
        $AllChecks.Drivers = $true
    } else {
        Write-Error "Pilotes non trouvés"
        Write-Warning "Installez les pilotes depuis le CD/site Thales"
    }
}

# ============================================
# 3. VÉRIFICATION SERVICE WINDOWS
# ============================================
Write-Section "3. VÉRIFICATION SERVICE WINDOWS"

$ThalesServices = Get-Service | Where-Object {
    $_.DisplayName -like "*Thales*" -or
    $_.DisplayName -like "*BSAPI*" -or
    $_.DisplayName -like "*Biometric*"
}

if ($ThalesServices) {
    Write-Success "Service(s) Thales trouvé(s):"
    foreach ($service in $ThalesServices) {
        $statusIcon = if ($service.Status -eq "Running") { "✓" } else { "✗" }
        $color = if ($service.Status -eq "Running") { "Green" } else { "Yellow" }
        Write-Host "  $statusIcon $($service.DisplayName) [$($service.Status)]" -ForegroundColor $color
        
        if ($service.Status -eq "Running") {
            $AllChecks.Service = $true
        }
    }
    
    if (-not $AllChecks.Service) {
        Write-Warning "Service trouvé mais non actif"
        Write-Info "Tentative de démarrage du service..."
        
        try {
            $service = $ThalesServices[0]
            Start-Service $service.Name -ErrorAction Stop
            Write-Success "Service démarré: $($service.DisplayName)"
            $AllChecks.Service = $true
        } catch {
            Write-Error "Échec démarrage service: $_"
            Write-Warning "Démarrez le service manuellement ou contactez le support"
        }
    }
} else {
    Write-Warning "Aucun service Thales Windows trouvé"
    Write-Info "Le service peut être une application utilisateur ou Node.js"
    Write-Host "  - Vérifiez si 'ThalesWebService.exe' est en cours d'exécution"
    Write-Host "  - Ou si un script Node.js fait office de bridge"
}

# ============================================
# 4. VÉRIFICATION PORT WEBSOCKET
# ============================================
Write-Section "4. VÉRIFICATION PORT WEBSOCKET"

Write-Info "Test connexion au port $ThalesWSPort..."

$PortTest = Test-NetConnection -ComputerName localhost -Port $ThalesWSPort -WarningAction SilentlyContinue

if ($PortTest.TcpTestSucceeded) {
    Write-Success "Port $ThalesWSPort : OUVERT et en écoute"
    $AllChecks.Port = $true
    
    # Identifier le processus
    $ProcessOnPort = Get-NetTCPConnection -LocalPort $ThalesWSPort -ErrorAction SilentlyContinue | 
        Select-Object -First 1
    
    if ($ProcessOnPort) {
        $Process = Get-Process -Id $ProcessOnPort.OwningProcess -ErrorAction SilentlyContinue
        if ($Process) {
            Write-Info "Processus: $($Process.ProcessName) (PID: $($Process.Id))"
            Write-Info "Chemin: $($Process.Path)"
        }
    }
} else {
    Write-Error "Port $ThalesWSPort : FERMÉ (aucun service en écoute)"
    Write-Warning "Actions suggérées:"
    Write-Host "  - Lancez le service WebSocket Thales"
    Write-Host "  - Vérifiez la configuration du port"
    Write-Host "  - Consultez CONFIGURATION_THALES_SCANNER.md"
}

# ============================================
# 5. TEST WEBSOCKET (si port ouvert)
# ============================================
if ($AllChecks.Port) {
    Write-Section "5. TEST WEBSOCKET"
    
    Write-Info "Test connexion WebSocket à $ThalesWSUrl..."
    
    # PowerShell n'a pas de client WebSocket natif simple
    # On utilise une requête HTTP pour vérifier que c'est bien un serveur WebSocket
    try {
        $Response = Invoke-WebRequest -Uri "http://localhost:$ThalesWSPort" -Method GET -TimeoutSec 2 -ErrorAction Stop
        
        if ($Response.StatusCode -eq 101 -or $Response.Headers.'Upgrade' -eq 'websocket') {
            Write-Success "Serveur WebSocket confirmé"
            $AllChecks.WebSocket = $true
        } else {
            Write-Warning "Le port répond mais ne semble pas être un serveur WebSocket"
        }
    } catch {
        if ($_.Exception.Message -like "*400*" -or $_.Exception.Message -like "*426*") {
            # Erreur 400/426 = serveur WebSocket refuse connexion HTTP simple (normal)
            Write-Success "Serveur WebSocket détecté (refuse HTTP simple = comportement attendu)"
            $AllChecks.WebSocket = $true
        } else {
            Write-Warning "Impossible de tester le protocole WebSocket directement"
            Write-Info "Utilisez la page test-thales-scanner.html pour un test complet"
        }
    }
}

# ============================================
# 6. RÉSUMÉ
# ============================================
Write-Section "RÉSUMÉ DU DIAGNOSTIC"

$TotalChecks = $AllChecks.Count
$PassedChecks = ($AllChecks.Values | Where-Object { $_ -eq $true }).Count
$SuccessRate = [math]::Round(($PassedChecks / $TotalChecks) * 100)

Write-Host ""
Write-Host "Résultats: $PassedChecks/$TotalChecks vérifications réussies ($SuccessRate%)" -ForegroundColor $(
    if ($SuccessRate -ge 80) { "Green" } 
    elseif ($SuccessRate -ge 50) { "Yellow" } 
    else { "Red" }
)
Write-Host ""

# Tableau détaillé
$ResultsTable = @(
    [PSCustomObject]@{
        Check = "Matériel USB"
        Status = if ($AllChecks.UsbDevice) { "✓ OK" } else { "✗ ÉCHEC" }
        Color = if ($AllChecks.UsbDevice) { "Green" } else { "Red" }
    }
    [PSCustomObject]@{
        Check = "Pilotes installés"
        Status = if ($AllChecks.Drivers) { "✓ OK" } else { "✗ ÉCHEC" }
        Color = if ($AllChecks.Drivers) { "Green" } else { "Red" }
    }
    [PSCustomObject]@{
        Check = "Service Windows"
        Status = if ($AllChecks.Service) { "✓ OK" } else { "⚠ NON TROUVÉ" }
        Color = if ($AllChecks.Service) { "Green" } else { "Yellow" }
    }
    [PSCustomObject]@{
        Check = "Port WebSocket"
        Status = if ($AllChecks.Port) { "✓ OK" } else { "✗ FERMÉ" }
        Color = if ($AllChecks.Port) { "Green" } else { "Red" }
    }
    [PSCustomObject]@{
        Check = "Serveur WebSocket"
        Status = if ($AllChecks.WebSocket) { "✓ OK" } else { "⚠ NON TESTÉ" }
        Color = if ($AllChecks.WebSocket) { "Green" } else { "Yellow" }
    }
)

foreach ($result in $ResultsTable) {
    Write-Host "  $($result.Status) $($result.Check)" -ForegroundColor $result.Color
}

# ============================================
# 7. RECOMMANDATIONS
# ============================================
Write-Section "RECOMMANDATIONS"

if ($SuccessRate -eq 100) {
    Write-Success "Tous les tests réussis! Le scanner Thales est prêt à l'emploi."
    Write-Host ""
    Write-Info "Étapes suivantes:"
    Write-Host "  1. Ouvrez: http://127.0.0.1:8000/test-thales-scanner.html"
    Write-Host "  2. Cliquez 'Tester Connexion'"
    Write-Host "  3. Cliquez 'Capturer Empreinte'"
    Write-Host "  4. Utilisez dans l'application: /households/create"
} 
elseif ($SuccessRate -ge 60) {
    Write-Warning "Configuration partielle détectée"
    Write-Host ""
    
    if (-not $AllChecks.UsbDevice) {
        Write-Host "➜ PRIORITÉ: Vérifiez le branchement USB du scanner" -ForegroundColor Red
    }
    if (-not $AllChecks.Drivers) {
        Write-Host "➜ Installez les pilotes Thales depuis le CD/site web" -ForegroundColor Yellow
    }
    if (-not $AllChecks.Port) {
        Write-Host "➜ PRIORITÉ: Lancez le service WebSocket Thales" -ForegroundColor Red
        Write-Host "  Consultez: CONFIGURATION_THALES_SCANNER.md" -ForegroundColor Cyan
    }
}
else {
    Write-Error "Configuration incomplète ou problème matériel"
    Write-Host ""
    Write-Host "➜ ACTIONS REQUISES:" -ForegroundColor Red
    Write-Host ""
    
    if (-not $AllChecks.UsbDevice) {
        Write-Host "1. Branchez le scanner Thales via USB" -ForegroundColor Yellow
        Write-Host "2. Vérifiez dans Gestionnaire de périphériques" -ForegroundColor Yellow
    }
    if (-not $AllChecks.Drivers) {
        Write-Host "3. Installez les pilotes Thales" -ForegroundColor Yellow
        Write-Host "   Téléchargez depuis: https://support.thalesgroup.com" -ForegroundColor Cyan
    }
    if (-not $AllChecks.Port) {
        Write-Host "4. Installez et lancez le service WebSocket" -ForegroundColor Yellow
        Write-Host "   Consultez: THALES_WEBSOCKET_BRIDGE.md" -ForegroundColor Cyan
    }
}

# ============================================
# 8. DOCUMENTATION
# ============================================
Write-Host ""
Write-Section "DOCUMENTATION"
Write-Host ""
Write-Info "Guides disponibles:"
Write-Host "  • CONFIGURATION_THALES_SCANNER.md - Installation complète"
Write-Host "  • THALES_WEBSOCKET_BRIDGE.md      - Créer service WebSocket"
Write-Host "  • INTEGRATION_THALES_README.md    - Guide rapide"
Write-Host "  • test-thales-scanner.html         - Page de test diagnostic"

Write-Host ""
Write-Info "Support:"
Write-Host "  • Support Thales: support@thalesgroup.com"
Write-Host "  • Téléphone: +33 1 57 77 80 00"
Write-Host "  • Portail: https://support.thalesgroup.com"

# ============================================
# Fin
# ============================================
Write-Host ""
Write-Host "╔═══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   FIN DU DIAGNOSTIC                                  ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Retourner le code d'erreur basé sur le taux de réussite
if ($SuccessRate -ge 80) {
    exit 0
} else {
    exit 1
}
