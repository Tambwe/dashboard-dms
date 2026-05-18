/**
 * Serveur WebSocket pour Scanner Thales Multifinger
 * 
 * Ce serveur simule l'API Thales pour permettre les tests
 * Remplacez la classe ThalesSimulator par l'appel au vrai SDK Thales
 * 
 * Usage: node thales-websocket-server.js
 */

import { WebSocketServer, WebSocket } from 'ws';

// ============================================
// SIMULATEUR THALES (à remplacer par vrai SDK)
// ============================================
class ThalesSimulator {
    async captureFingerprint(options) {
        console.log('  📸 Simulation capture empreinte...');
        console.log('    - Qualité min:', options.quality);
        console.log('    - Timeout:', options.timeout + 'ms');
        
        // Simuler un délai de capture (1-3 secondes)
        const delay = 1000 + Math.floor(Math.random() * 2000);
        await new Promise(resolve => setTimeout(resolve, delay));
        
        // Générer des données simulées
        const quality = 60 + Math.floor(Math.random() * 40); // 60-100
        
        return {
            status: 'success',
            data: {
                template: this.generateFakeTemplate(),
                image: this.generateFakeFingerprintImage(),
                quality: quality,
                format: 'ANSI_378'
            }
        };
    }
    
    generateFakeTemplate() {
        // Génère un template FMR_ISO (ISO/IEC 19794-2:2005) minimal mais parseable
        // Structure : header 30 octets + 1 vue doigt + N minuties (6 octets chacune)
        const NUM_MINUTIAE = 20 + Math.floor(Math.random() * 20); // 20-39 minuties
        const totalLen = 30 + 4 + NUM_MINUTIAE * 6 + 2; // header + view header + minuties + ext length
        const buf = Buffer.alloc(totalLen, 0);

        // Header (30 octets)
        buf.write('FMR\x00', 0, 'binary');          // [0-3]  magic
        buf.write(' 20\x00', 4, 'binary');           // [4-7]  version
        buf.writeUInt32BE(totalLen, 8);              // [8-11] total length
        buf.writeUInt16BE(0x0000, 12);               // [12-13] CBEFF product type
        buf.writeUInt16BE(0x0000, 14);               // [14-15] CBEFF product owner
        buf.writeUInt16BE(0x0000, 16);               // [16-17] equipment compliance
        buf.writeUInt16BE(0x0000, 18);               // [18-19] equipment ID
        buf.writeUInt16BE(500, 20);                  // [20-21] image width px
        buf.writeUInt16BE(500, 22);                  // [22-23] image height px
        buf.writeUInt16BE(197, 24);                  // [24-25] res X px/cm (~500 dpi)
        buf.writeUInt16BE(197, 26);                  // [26-27] res Y px/cm
        buf[28] = 1;                                 // [28]    nb views = 1
        buf[29] = 0;                                 // [29]    reserved

        // View header (4 octets)
        let off = 30;
        buf[off]     = 1;             // finger position (index)
        buf[off + 1] = 0;             // view number | impression type
        buf[off + 2] = 80;            // quality
        buf[off + 3] = NUM_MINUTIAE;  // nb minutiae
        off += 4;

        // Minuties — légèrement aléatoires mais reproductibles par doigt simulé
        for (let i = 0; i < NUM_MINUTIAE; i++) {
            const type = (Math.random() < 0.5) ? 1 : 2; // 1=termination, 2=bifurcation
            const x    = 50 + Math.floor(Math.random() * 400);  // 50-449 px
            const y    = 50 + Math.floor(Math.random() * 400);
            const a    = Math.floor(Math.random() * 256);        // 0-255 (0-360°)
            // [type(2b)|X(14b)] uint16 big-endian
            buf.writeUInt16BE(((type & 0x03) << 14) | (x & 0x3FFF), off);
            // [0(2b)|Y(14b)]
            buf.writeUInt16BE(y & 0x3FFF, off + 2);
            buf[off + 4] = a;    // angle
            buf[off + 5] = 80;   // quality per minutia
            off += 6;
        }

        // Extended data length = 0
        buf.writeUInt16BE(0, off);

        return buf.toString('base64');
    }
    
    generateFakeFingerprintImage() {
        // Template d'image PNG minimaliste valide (pixel noir 200x200)
        // En production, le SDK Thales fournit une vraie image
        const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        return pngBase64;
    }
}

// ============================================
// SERVEUR WEBSOCKET
// ============================================
const PORT = 8181;
const HOST = 'localhost'; // Sécurité: localhost uniquement

const wss = new WebSocketServer({ 
    port: PORT,
    host: HOST
});

const thales = new ThalesSimulator();

console.log('╔═══════════════════════════════════════════════════════╗');
console.log('║   SERVEUR WEBSOCKET THALES - MODE SIMULATION         ║');
console.log('║   Dashboard DMS - Version 1.0                        ║');
console.log('╚═══════════════════════════════════════════════════════╝');
console.log('');
console.log('✓ Serveur démarré sur ws://localhost:' + PORT);
console.log('⚠  MODE SIMULATION - Remplacer ThalesSimulator par SDK réel');
console.log('');
console.log('En attente de connexions...');
console.log('');

// Statistiques
let connectionsCount = 0;
let capturesCount = 0;

wss.on('connection', (ws, req) => {
    connectionsCount++;
    const clientIp = req.socket.remoteAddress;
    
    console.log(`[${new Date().toLocaleTimeString()}] ✓ Nouvelle connexion (#${connectionsCount})`);
    console.log(`  Client: ${clientIp}`);
    console.log('');

    ws.on('message', async (message) => {
        try {
            const command = JSON.parse(message);
            console.log(`[${new Date().toLocaleTimeString()}] 📥 Commande reçue: ${command.action}`);

            // ACTION: CAPTURE
            if (command.action === 'capture') {
                capturesCount++;
                console.log(`  🔍 Capture #${capturesCount}`);
                
                try {
                    // Capturer l'empreinte (simulé ou via SDK réel)
                    const result = await thales.captureFingerprint({
                        timeout: command.timeout || 10000,
                        quality: command.quality || 60,
                        fingerCount: command.fingerCount || 1
                    });

                    // Envoyer le résultat
                    ws.send(JSON.stringify(result));
                    console.log(`  ✓ Empreinte capturée (Qualité: ${result.data.quality}%)`);
                    console.log('');
                    
                } catch (error) {
                    console.error('  ✗ Erreur capture:', error.message);
                    ws.send(JSON.stringify({
                        status: 'error',
                        message: 'Échec de capture: ' + error.message
                    }));
                }
            }
            
            // ACTION: STATUS
            else if (command.action === 'status') {
                console.log('  ℹ Demande de statut');
                
                ws.send(JSON.stringify({
                    status: 'success',
                    data: {
                        connected: true,
                        deviceName: 'Thales Multifinger Scanner (Simulé)',
                        version: '1.0.0',
                        mode: 'simulation',
                        totalCaptures: capturesCount,
                        uptime: process.uptime()
                    }
                }));
                console.log('  ✓ Statut envoyé');
                console.log('');
            }
            
            // ACTION INCONNUE
            else {
                console.log('  ⚠ Action inconnue:', command.action);
                ws.send(JSON.stringify({
                    status: 'error',
                    message: 'Action inconnue: ' + command.action
                }));
                console.log('');
            }
            
        } catch(error) {
            console.error('  ✗ Erreur parsing message:', error.message);
            ws.send(JSON.stringify({
                status: 'error',
                message: 'Erreur serveur: ' + error.message
            }));
            console.log('');
        }
    });

    ws.on('close', () => {
        console.log(`[${new Date().toLocaleTimeString()}] ✗ Connexion fermée`);
        console.log('');
    });

    ws.on('error', (error) => {
        console.error(`[${new Date().toLocaleTimeString()}] ✗ Erreur WebSocket:`, error.message);
        console.log('');
    });
});

// Gestion de l'arrêt propre
process.on('SIGINT', () => {
    console.log('');
    console.log('🛑 Arrêt du serveur...');
    console.log('');
    console.log('Statistiques de session:');
    console.log(`  - Connexions totales: ${connectionsCount}`);
    console.log(`  - Captures totales: ${capturesCount}`);
    console.log('');
    
    wss.close(() => {
        console.log('✓ Serveur arrêté proprement');
        process.exit(0);
    });
});

// Gestion des erreurs non capturées
process.on('uncaughtException', (error) => {
    console.error('✗ Erreur non gérée:', error);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('✗ Promise rejetée:', reason);
});

// Message de rappel toutes les 30 secondes
setInterval(() => {
    console.log(`[${new Date().toLocaleTimeString()}] ℹ Serveur actif - ${connectionsCount} connexions - ${capturesCount} captures`);
}, 30000);
