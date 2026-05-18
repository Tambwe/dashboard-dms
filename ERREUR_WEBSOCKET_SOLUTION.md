# 🔴 ERREUR: Connexion WebSocket Impossible

## Symptôme
```
tentative de connexion à ws://localhost:8181...
Erreur de connexion: [object Event]
```

## ✅ Cause
Le **service WebSocket Thales n'est pas en cours d'exécution**. Le code web fonctionne correctement, mais il n'y a pas de serveur à l'autre bout.

---

## 🚀 SOLUTION IMMÉDIATE (2 minutes)

### Option 1: Script automatique (RECOMMANDÉ)

Double-cliquez sur ce fichier:
```
start-thales-server.bat
```

C'est tout! Le serveur va:
- Vérifier Node.js
- Installer les dépendances automatiquement
- Démarrer le serveur sur le port 8181

### Option 2: Ligne de commande

```powershell
# Terminal 1: Installer la dépendance WebSocket
npm install ws

# Terminal 2: Lancer le serveur
node thales-websocket-server.js
```

Vous devriez voir:
```
╔═══════════════════════════════════════════════════════╗
║   SERVEUR WEBSOCKET THALES - MODE SIMULATION         ║
╚═══════════════════════════════════════════════════════╝

✓ Serveur démarré sur ws://localhost:8181
⚠  MODE SIMULATION
En attente de connexions...
```

**LAISSEZ CE TERMINAL OUVERT** pendant vos tests.

---

## 🧪 Tester maintenant

Avec le serveur lancé, testez:

### Test 1: Page de diagnostic
```
http://127.0.0.1:8000/test-thales-scanner.html
```

1. Cliquez **"Tester Connexion"**
   - Vous devriez voir: "✓ Connecté au scanner"
   - Indicateur vert qui pulse

2. Cliquez **"Capturer Empreinte"**
   - Vous devriez voir: "✓ Empreinte capturée avec succès!"
   - Qualité affichée (60-100%)
   - Image d'empreinte (simulée pour l'instant)

### Test 2: Formulaire réel
```
http://127.0.0.1:8000/households/create
```

1. Allez à la section **"3. Biométrie du Chef de Ménage"**
2. Cliquez **"Capturer Empreinte"**
3. Vérifiez le succès avec l'image

---

## 🔍 Vérification rapide

### Le serveur est-il lancé?

```powershell
Test-NetConnection -ComputerName localhost -Port 8181
```

**Attendu:** `TcpTestSucceeded : True`

Si `False` → Le serveur n'est pas lancé, utilisez `start-thales-server.bat`

### Le processus Node.js tourne-t-il?

```powershell
Get-Process node
```

Vous devriez voir un processus avec "thales-websocket-server.js"

---

## ⚙️ Mode Simulation vs Mode Réel

### Ce que vous avez maintenant (SIMULATION)

✅ Le serveur WebSocket fonctionne  
✅ La connexion web ↔ serveur est établie  
✅ Les données sont capturées et stockées  
⚠️ Les empreintes sont **simulées** (données aléatoires)  
⚠️ Le **vrai scanner Thales n'est pas utilisé**

**C'est parfait pour:**
- Tester l'interface utilisateur
- Développer le reste de l'application
- Vérifier que tout fonctionne côté web

### Pour utiliser le vrai scanner Thales

Suivez ces étapes:

1. **Installez le SDK Thales**
   - Contactez support@thalesgroup.com
   - Demandez "Software Development Kit pour Multifinger Scanner"

2. **Remplacez la classe ThalesSimulator**
   - Dans `thales-websocket-server.js`
   - Ligne 10-46: Remplacer par appels au SDK réel
   - Voir documentation dans THALES_WEBSOCKET_BRIDGE.md

3. **Testez avec le matériel**
   - Branchez le scanner
   - Relancez le serveur
   - Les empreintes seront maintenant réelles!

---

## 📊 Que se passe-t-il maintenant?

Quand vous cliquez "Capturer Empreinte":

```
[Navigateur] 
    ↓ WebSocket
[Serveur Node.js sur localhost:8181]
    ↓ Classe ThalesSimulator (pour l'instant)
[Données simulées générées]
    ↑ Réponse JSON
[Navigateur - Affichage succès]
    ↓
[Stockage en base de données]
```

**En mode réel** (après intégration SDK):

```
[Navigateur] 
    ↓ WebSocket
[Serveur Node.js]
    ↓ SDK Thales natif
[Scanner Thales Multifinger - VRAI MATÉRIEL]
    ↑ Empreinte réelle
[Navigateur - Affichage]
    ↓
[Base de données]
```

---

## 🛠️ Dépannage

### "npm n'est pas reconnu"

Node.js n'est pas installé.

**Solution:**
1. Téléchargez: https://nodejs.org
2. Installez la version LTS (20.x ou supérieure)
3. Redémarrez PowerShell
4. Réessayez

### "Le port 8181 est déjà utilisé"

Un autre processus utilise ce port.

**Solution:**
```powershell
# Trouver le processus
Get-NetTCPConnection -LocalPort 8181 | Select-Object OwningProcess

# Noter le PID, puis:
Stop-Process -Id <PID>

# Ou changez le port dans thales-websocket-server.js ligne 58
```

### Le serveur se ferme immédiatement

Vérifiez les erreurs dans le terminal.

**Solution courante:**
```powershell
# Réinstaller le module ws
npm install ws --force
```

### "Still cannot connect"

1. **Vérifiez que le serveur tourne:**
   ```powershell
   Get-Process node
   ```

2. **Vérifiez le port:**
   ```powershell
   Test-NetConnection -ComputerName localhost -Port 8181
   ```

3. **Vérifiez le navigateur console (F12):**
   - Recherchez des erreurs WebSocket
   - Vérifiez l'URL: doit être `ws://localhost:8181`

---

## ✅ Checklist de démarrage

- [ ] Node.js installé (`node --version`)
- [ ] Module ws installé (`npm install ws`)
- [ ] Serveur lancé (`node thales-websocket-server.js` OU `start-thales-server.bat`)
- [ ] Terminal ouvert et affiche "En attente de connexions..."
- [ ] Port 8181 en écoute (`Test-NetConnection`)
- [ ] Page de test ouverte (test-thales-scanner.html)
- [ ] Connexion testée et réussie (indicateur vert)
- [ ] Capture testée et réussie (image affichée)

---

## 🎯 Prochaines étapes

Une fois que tout fonctionne en simulation:

1. **Utilisez normalement l'application**
   - Enregistrez des ménages avec empreintes simulées
   - Testez tout le workflow

2. **Quand vous serez prêt pour le vrai scanner:**
   - Contactez Thales pour le SDK
   - Modifiez `thales-websocket-server.js`
   - Remplacez ThalesSimulator par vraies fonctions SDK

3. **Pour production:**
   - Installez le serveur comme service Windows
   - Configurez le démarrage automatique
   - Voir: THALES_WEBSOCKET_BRIDGE.md section "Service Windows"

---

**Commencez maintenant:**

```powershell
# Double-cliquez sur:
start-thales-server.bat

# Puis ouvrez dans le navigateur:
http://127.0.0.1:8000/test-thales-scanner.html
```

🎉 Vous devriez voir "✓ Connecté au scanner" !
