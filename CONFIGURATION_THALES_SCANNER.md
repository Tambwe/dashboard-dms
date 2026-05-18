# Configuration du Scanner Thales Multifinger

## 📋 Vue d'ensemble

Ce guide explique comment configurer le scanner d'empreintes digitales **Thales Multifinger Scanner** pour fonctionner avec le système d'enregistrement des ménages.

## 🔧 Prérequis

1. **Scanner Thales Multifinger** branché via USB
2. **Pilotes Thales** installés (fournis avec le scanner)
3. **Application locale Thales** pour exposer l'API WebSocket
4. **Navigateur moderne** (Chrome, Firefox, Edge) avec support WebSocket

---

## 📦 Installation

### Étape 1: Installation des pilotes

1. Branchez le scanner Thales Multifinger à un port USB
2. Insérez le CD/DVD fourni avec le scanner ou téléchargez les pilotes depuis [support.thalesgroup.com](https://support.thalesgroup.com)
3. Lancez l'installateur et suivez les instructions
4. Redémarrez l'ordinateur si demandé

### Étape 2: Installation de l'application Thales WebSocket

Le scanner Thales nécessite une application locale qui agit comme pont entre le navigateur et le matériel.

**Options possibles:**

#### Option A: Thales WebFingerprint SDK (recommandé)
```
1. Téléchargez le SDK depuis le portail Thales
2. Installez "ThalesWebService.exe"
3. Configurez le port (par défaut: 8181)
4. Lancez le service
```

#### Option B: Thales BSAPI (BioSecure API)
```
1. Installez Thales BSAPI Runtime
2. Configurez le service WebSocket
3. Démarrez le service Windows "ThalesBSAPI"
```

#### Option C: Application personnalisée

Si vous avez développé votre propre application locale:
```javascript
// L'application doit exposer une API WebSocket sur ws://localhost:8181
// Format des messages: JSON
{
    "action": "capture",
    "timeout": 10000,
    "quality": 60,
    "fingerCount": 1
}

// Réponse attendue:
{
    "status": "success",
    "data": {
        "template": "base64_encoded_fingerprint_template",
        "image": "base64_encoded_fingerprint_image",
        "quality": 85
    }
}
```

---

## ⚙️ Configuration du port WebSocket

Par défaut, le système utilise le port **8181**. Si votre application Thales utilise un port différent, modifiez cette ligne dans les fichiers:

**Fichier:** `resources/views/households/create.blade.php` (Ligne ~503)  
**Fichier:** `resources/views/households/members/create.blade.php` (Ligne ~407)

```javascript
const THALES_WS_URL = 'ws://localhost:8181'; // ← Modifiez ce port si nécessaire
```

Remplacez `8181` par votre port configuré (ex: `8080`, `9090`, etc.)

---

## 🚀 Vérification de l'installation

### Test 1: Vérifier que le service est actif

Ouvrez PowerShell et exécutez:
```powershell
Test-NetConnection -ComputerName localhost -Port 8181
```

**Résultat attendu:**
```
TcpTestSucceeded : True
```

### Test 2: Test WebSocket dans le navigateur

Ouvrez la console du navigateur (F12) et testez:
```javascript
const ws = new WebSocket('ws://localhost:8181');
ws.onopen = () => console.log('✓ Connexion réussie!');
ws.onerror = (e) => console.error('✗ Erreur:', e);
```

**Résultat attendu:** Message `✓ Connexion réussie!`

### Test 3: Test de capture

1. Accédez à `/households/create`
2. Cliquez sur **"Capturer Empreinte"**
3. Observez les messages:
   - "Connexion au scanner Thales..."
   - "Initialisation du scanner..."
   - "Veuillez placer votre doigt"
   - "✓ Empreinte capturée avec succès"

---

## 🛠️ Dépannage

### Problème 1: "Timeout connexion Thales"

**Cause:** Le service WebSocket n'est pas lancé ou utilise un autre port

**Solution:**
1. Vérifiez que l'application Thales est lancée:
   ```powershell
   Get-Service | Where-Object {$_.Name -like "*Thales*"}
   ```
2. Vérifiez le port utilisé dans la configuration Thales
3. Modifiez `THALES_WS_URL` si nécessaire

### Problème 2: "Connexion Thales non établie"

**Cause:** WebSocket fermé ou non initialisé

**Solution:**
1. Rechargez la page (F5)
2. Vérifiez les logs de l'application Thales
3. Redémarrez le service Thales

### Problème 3: "Erreur de capture"

**Cause:** Scanner non détecté ou doigt mal positionné

**Solution:**
1. Vérifiez que le scanner est branché:
   ```powershell
   Get-PnpDevice | Where-Object {$_.FriendlyName -like "*Thales*"}
   ```
2. Nettoyez la surface du scanner
3. Placez bien le doigt au centre
4. Attendez le voyant vert avant de retirer le doigt

### Problème 4: "CORS error" ou "Mixed content"

**Cause:** HTTPS essaye de se connecter à ws:// (non sécurisé)

**Solution:**
1. Utilisez HTTP pour localhost: `http://127.0.0.1:8000`
2. OU configurez le service Thales en WSS (WebSocket sécurisé): `wss://localhost:8181`
3. OU ajoutez une exception dans le navigateur

---

## 📝 Format des données capturées

L'empreinte est stockée en format JSON dans la base de données:

```json
{
    "template": "base64_encoded_fingerprint_template_ANSI_378_or_ISO_19794",
    "image": "base64_encoded_png_image",
    "quality": 85,
    "capturedAt": "2025-01-15T10:30:00.000Z",
    "device": "Thales Multifinger Scanner"
}
```

**Champs:**
- `template`: Template biométrique (ANSI 378 ou ISO 19794-2) en base64
- `image`: Image PNG de l'empreinte en base64 (optionnel, pour affichage)
- `quality`: Score de qualité (0-100)
- `capturedAt`: Date/heure de capture ISO 8601
- `device`: Nom du dispositif utilisé

---

## 🔐 Sécurité

### Bonnes pratiques:

1. **Ne jamais exposer le WebSocket sur Internet**
   - Utilisez uniquement `localhost` ou `127.0.0.1`
   - Bloquez le port 8181 dans le pare-feu pour les connexions externes

2. **Chiffrement des données**
   - Les templates biométriques sont uniques et irréversibles
   - Stockez-les chiffrés dans la base de données (AES-256)

3. **Authentification**
   - Seuls les utilisateurs authentifiés peuvent accéder aux formulaires
   - Validez les permissions côté serveur avant d'enregistrer

4. **Logs d'audit**
   - Enregistrez tous les accès aux données biométriques
   - Conservez l'historique des captures (qui, quand, où)

---

## 📚 Ressources supplémentaires

### Documentation Thales
- [Thales Biometric Solutions](https://www.thalesgroup.com/en/markets/digital-identity-and-security/government/biometrics)
- [BSAPI Developer Guide](https://support.thalesgroup.com) (compte requis)

### Standards biométriques
- **ANSI/INCITS 378-2004**: Format de template d'empreinte digitale
- **ISO/IEC 19794-2**: Standard international pour données biométriques
- **MINEX III**: Test de performance pour matching d'empreintes

### Support technique
- **Email:** support@thalesgroup.com
- **Téléphone:** +33 1 57 77 80 00 (France)
- **Portail:** https://support.thalesgroup.com

---

## ✅ Checklist de configuration

Avant de déployer en production:

- [ ] Scanner Thales branché et reconnu par Windows
- [ ] Pilotes Thales installés (vérifier dans Gestionnaire de périphériques)
- [ ] Application WebSocket lancée et écoute sur le port configuré
- [ ] Test de connexion WebSocket réussi
- [ ] Test de capture d'empreinte réussi
- [ ] Messages d'erreur appropriés si scanner non disponible
- [ ] Données stockées au format JSON correct
- [ ] Permissions utilisateur vérifiées
- [ ] Chiffrement des données biométriques activé
- [ ] Logs d'audit configurés
- [ ] Documentation fournie aux opérateurs
- [ ] Formation des utilisateurs effectuée

---

## 📞 Support

Pour toute question sur cette intégration:

**Problèmes techniques:**
1. Consultez ce guide en premier
2. Vérifiez les logs du navigateur (F12 → Console)
3. Vérifiez les logs de l'application Thales
4. Contactez l'administrateur système

**Problèmes matériels:**
1. Vérifiez les branchements USB
2. Testez sur un autre port USB
3. Contactez le support Thales

---

*Dernière mise à jour: Janvier 2025*
*Version: 1.0*
