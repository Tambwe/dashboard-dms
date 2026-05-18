# Thales WebSocket Bridge - Application Locale

Cette application Node.js sert de pont entre le navigateur web et le scanner Thales Multifinger via WebSocket.

## 📋 Prérequis

- **Node.js** version 14 ou supérieure
- **Scanner Thales** avec pilotes installés
- **Module Node** pour Thales (fourni par Thales ou développé en C++)

## 📦 Installation

```bash
# Installer les dépendances
npm install ws node-gyp

# Si vous avez le module natif Thales:
npm install ./path/to/thales-native-module

# Ou créer un addon Node.js pour interfacer avec la DLL/SO Thales
```

## 🚀 Utilisation

```bash
# Lancer le serveur WebSocket
node thales-websocket-server.js

# Le serveur écoute sur ws://localhost:8181
```

## 📝 Code du serveur (thales-websocket-server.js)

```javascript
const WebSocket = require('ws');

// Charger le module natif Thales (à adapter selon votre installation)
// Option 1: Module natif C++ (si vous avez le SDK)
// const thalesSDK = require('./build/Release/thales_native.node');

// Option 2: Appel via DLL/SO avec FFI
// const ffi = require('ffi-napi');
// const thalesDLL = ffi.Library('ThalesBSAPI.dll', {...});

// Option 3: Simulation pour tests (à remplacer par vrai SDK)
class ThalesSimulator {
    async captureFingerprint(options) {
        // SIMULATION - Remplacer par vrai appel SDK
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        return {
            status: 'success',
            data: {
                template: Buffer.from('SIMULATED_TEMPLATE_DATA').toString('base64'),
                image: generateSimulatedFingerprintImage(),
                quality: Math.floor(Math.random() * 30) + 70, // 70-100
                format: 'ANSI_378'
            }
        };
    }
}

function generateSimulatedFingerprintImage() {
    // Génère une image PNG simulée en base64
    const pngHeader = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    return pngHeader;
}

// Créer le serveur WebSocket
const wss = new WebSocket.Server({ 
    port: 8181,
    host: 'localhost' // Sécurité: uniquement localhost
});

const thales = new ThalesSimulator(); // Remplacer par vrai SDK

console.log('🚀 Serveur WebSocket Thales démarré sur ws://localhost:8181');
console.log('⚠️  MODE SIMULATION - Remplacer ThalesSimulator par vrai SDK');

wss.on('connection', (ws) => {
    console.log('✓ Nouvelle connexion client');

    ws.on('message', async (message) => {
        try {
            const command = JSON.parse(message);
            console.log('📥 Commande reçue:', command.action);

            if (command.action === 'capture') {
                // Capturer l'empreinte
                const result = await thales.captureFingerprint({
                    timeout: command.timeout || 10000,
                    quality: command.quality || 60,
                    fingerCount: command.fingerCount || 1
                });

                // Envoyer le résultat
                ws.send(JSON.stringify(result));
                console.log('✓ Empreinte capturée et envoyée');
            }
            else if (command.action === 'status') {
                ws.send(JSON.stringify({
                    status: 'success',
                    data: {
                        connected: true,
                        deviceName: 'Thales Multifinger Scanner',
                        version: '1.0.0'
                    }
                }));
            }
            else {
                ws.send(JSON.stringify({
                    status: 'error',
                    message: 'Action inconnue: ' + command.action
                }));
            }
        } catch(error) {
            console.error('✗ Erreur:', error);
            ws.send(JSON.stringify({
                status: 'error',
                message: error.message
            }));
        }
    });

    ws.on('close', () => {
        console.log('✗ Connexion client fermée');
    });

    ws.on('error', (error) => {
        console.error('✗ Erreur WebSocket:', error);
    });
});

// Gestion de l'arrêt propre
process.on('SIGINT', () => {
    console.log('\n🛑 Arrêt du serveur...');
    wss.close(() => {
        console.log('✓ Serveur arrêté');
        process.exit(0);
    });
});
```

## 🔧 Intégration avec vrai SDK Thales

### Étape 1: Créer un addon Node.js natif

Si vous avez le SDK Thales en C/C++, créez un addon:

**binding.gyp:**
```python
{
  "targets": [
    {
      "target_name": "thales_native",
      "sources": [ "src/thales_native.cpp" ],
      "include_dirs": [
        "<!(node -e \"require('nan')\")",
        "C:/Program Files/Thales/SDK/include"
      ],
      "libraries": [
        "C:/Program Files/Thales/SDK/lib/ThalesBSAPI.lib"
      ]
    }
  ]
}
```

**src/thales_native.cpp:**
```cpp
#include <nan.h>
#include <ThalesBSAPI.h> // Header SDK Thales

void CaptureFingerprint(const Nan::FunctionCallbackInfo<v8::Value>& info) {
    // Appeler l'API Thales pour capturer
    BSAPI_CAPTURE_REQUEST request;
    request.timeout = 10000;
    request.quality = 60;
    
    BSAPI_CAPTURE_RESULT result;
    int status = BSAPI_Capture(&request, &result);
    
    if (status == BSAPI_SUCCESS) {
        v8::Local<v8::Object> obj = Nan::New<v8::Object>();
        Nan::Set(obj, Nan::New("template").ToLocalChecked(), 
                 Nan::New<v8::String>(result.template).ToLocalChecked());
        Nan::Set(obj, Nan::New("quality").ToLocalChecked(), 
                 Nan::New<v8::Number>(result.quality));
        
        info.GetReturnValue().Set(obj);
    } else {
        Nan::ThrowError("Capture failed");
    }
}

void Init(v8::Local<v8::Object> exports) {
    Nan::SetMethod(exports, "captureFingerprint", CaptureFingerprint);
}

NODE_MODULE(thales_native, Init)
```

Compilez:
```bash
node-gyp configure
node-gyp build
```

### Étape 2: Utiliser le module natif

Dans `thales-websocket-server.js`:
```javascript
const thalesNative = require('./build/Release/thales_native.node');

class ThalesSDK {
    async captureFingerprint(options) {
        return new Promise((resolve, reject) => {
            try {
                const result = thalesNative.captureFingerprint(options);
                resolve({
                    status: 'success',
                    data: result
                });
            } catch(error) {
                reject(error);
            }
        });
    }
}

const thales = new ThalesSDK();
```

## 🐳 Installation comme Service Windows

Pour que l'application démarre automatiquement:

**Option 1: NSSM (Non-Sucking Service Manager)**
```powershell
# Télécharger NSSM: https://nssm.cc/download
nssm install ThalesWebSocketBridge "C:\Program Files\nodejs\node.exe"
nssm set ThalesWebSocketBridge AppParameters "C:\path\to\thales-websocket-server.js"
nssm set ThalesWebSocketBridge AppDirectory "C:\path\to"
nssm set ThalesWebSocketBridge DisplayName "Thales WebSocket Bridge"
nssm set ThalesWebSocketBridge Description "Service pont WebSocket pour scanner Thales"
nssm set ThalesWebSocketBridge Start SERVICE_AUTO_START
nssm start ThalesWebSocketBridge
```

**Option 2: node-windows**
```javascript
const Service = require('node-windows').Service;

const svc = new Service({
    name: 'Thales WebSocket Bridge',
    description: 'Service pont WebSocket pour scanner Thales',
    script: 'C:\\path\\to\\thales-websocket-server.js'
});

svc.on('install', () => {
    svc.start();
});

svc.install();
```

## 🔐 Sécurité

**Important:** Ce serveur écoute uniquement sur localhost (127.0.0.1)

Pour renforcer la sécurité:

1. **Authentification par token:**
```javascript
const validTokens = new Set(['your-secret-token-here']);

ws.on('message', async (message) => {
    const command = JSON.parse(message);
    
    if (!validTokens.has(command.token)) {
        ws.send(JSON.stringify({
            status: 'error',
            message: 'Token invalide'
        }));
        return;
    }
    
    // ... reste du code
});
```

2. **Rate limiting:**
```javascript
const rateLimit = new Map();

ws.on('message', async (message) => {
    const clientId = ws._socket.remoteAddress;
    const now = Date.now();
    
    if (rateLimit.has(clientId)) {
        const lastRequest = rateLimit.get(clientId);
        if (now - lastRequest < 1000) { // 1 requête par seconde max
            ws.send(JSON.stringify({
                status: 'error',
                message: 'Trop de requêtes'
            }));
            return;
        }
    }
    
    rateLimit.set(clientId, now);
    // ... reste du code
});
```

## 📚 Documentation API Thales

Pour intégrer le vrai SDK Thales, consultez:

- **BSAPI (BioSecure API):** Documentation officielle Thales
- **Functions principales:**
  - `BSAPI_Init()` - Initialiser le SDK
  - `BSAPI_Capture()` - Capturer une empreinte
  - `BSAPI_Match()` - Comparer deux empreintes
  - `BSAPI_Terminate()` - Libérer les ressources

Contactez Thales pour obtenir:
- SDK complet (DLL/SO + headers)
- Documentation API
- Exemples de code
- Support technique

## ✅ Tests

Testez le serveur:

```bash
# Terminal 1: Lancer le serveur
node thales-websocket-server.js

# Terminal 2: Tester avec wscat
npm install -g wscat
wscat -c ws://localhost:8181

# Envoyer une commande de capture:
{"action":"capture","timeout":10000,"quality":60,"fingerCount":1}

# Vérifier le statut:
{"action":"status"}
```

## 📞 Support

Pour questions sur cette application:
- Consultez la documentation Thales SDK
- Vérifiez les logs du serveur Node.js
- Testez avec wscat pour isoler les problèmes

---

*Dernière mise à jour: Janvier 2025*
