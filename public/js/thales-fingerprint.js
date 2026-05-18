/**
 * Module d'intégration Thales Multifinger Scanner
 * 
 * Ce module permet la capture d'empreintes digitales via le scanner Thales
 * en utilisant une connexion WebSocket avec l'application locale Thales.
 * 
 * @version 1.0
 * @author Dashboard DMS
 */

class ThalesFingerprintCapture {
    constructor(config = {}) {
        this.wsUrl = config.wsUrl || 'ws://localhost:8181';
        this.timeout = config.timeout || 10000;
        this.quality = config.quality || 60;
        this.fingerCount = config.fingerCount || 1;
        this.ws = null;
        this.isConnected = false;
    }

    /**
     * Établit la connexion avec le service Thales WebSocket
     * @returns {Promise<boolean>} True si connexion réussie
     */
    connect() {
        return new Promise((resolve, reject) => {
            try {
                // Fermer la connexion existante si présente
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    resolve(true);
                    return;
                }

                this.ws = new WebSocket(this.wsUrl);
                
                this.ws.onopen = () => {
                    console.log('✓ Connexion Thales établie');
                    this.isConnected = true;
                    resolve(true);
                };
                
                this.ws.onerror = (error) => {
                    console.error('✗ Erreur connexion Thales:', error);
                    this.isConnected = false;
                    reject(new Error('Impossible de se connecter au scanner Thales'));
                };
                
                this.ws.onclose = () => {
                    console.log('Connexion Thales fermée');
                    this.isConnected = false;
                };
                
                // Timeout de 3 secondes pour la connexion
                setTimeout(() => {
                    if (this.ws.readyState !== WebSocket.OPEN) {
                        reject(new Error('Timeout: Le service Thales ne répond pas'));
                    }
                }, 3000);
            } catch(error) {
                this.isConnected = false;
                reject(error);
            }
        });
    }

    /**
     * Capture une empreinte digitale
     * @returns {Promise<Object>} Objet contenant template, image et qualité
     */
    capture() {
        return new Promise((resolve, reject) => {
            if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
                reject(new Error('Connexion Thales non établie. Appelez connect() d\'abord.'));
                return;
            }
            
            // Commande de capture au format Thales
            const captureCommand = {
                action: "capture",
                timeout: this.timeout,
                quality: this.quality,
                fingerCount: this.fingerCount,
                format: "ANSI_378" // Format standard
            };
            
            // Gestionnaire de réponse unique pour cette capture
            const messageHandler = (event) => {
                try {
                    const response = JSON.parse(event.data);
                    
                    // Succès
                    if (response.status === 'success' && response.data) {
                        this.ws.removeEventListener('message', messageHandler);
                        resolve({
                            template: response.data.template,
                            image: response.data.image,
                            quality: response.data.quality,
                            format: response.data.format || 'ANSI_378'
                        });
                    } 
                    // Erreur
                    else if (response.status === 'error') {
                        this.ws.removeEventListener('message', messageHandler);
                        reject(new Error(response.message || 'Erreur de capture'));
                    }
                } catch(e) {
                    this.ws.removeEventListener('message', messageHandler);
                    reject(new Error('Erreur parsing réponse: ' + e.message));
                }
            };
            
            // Attacher le gestionnaire
            this.ws.addEventListener('message', messageHandler);
            
            // Envoyer la commande
            try {
                this.ws.send(JSON.stringify(captureCommand));
            } catch(error) {
                this.ws.removeEventListener('message', messageHandler);
                reject(new Error('Erreur envoi commande: ' + error.message));
            }
            
            // Timeout pour la capture (15 secondes)
            setTimeout(() => {
                this.ws.removeEventListener('message', messageHandler);
                reject(new Error('Timeout: Aucune empreinte capturée'));
            }, 15000);
        });
    }

    /**
     * Vérifie la qualité d'une empreinte capturée
     * @param {Object} captureResult - Résultat de capture()
     * @returns {Object} Analyse de qualité
     */
    analyzeQuality(captureResult) {
        const quality = captureResult.quality || 0;
        
        let rating, message, color;
        
        if (quality >= 80) {
            rating = 'Excellente';
            message = 'Empreinte de très haute qualité';
            color = 'green';
        } else if (quality >= 60) {
            rating = 'Bonne';
            message = 'Qualité suffisante pour identification';
            color = 'blue';
        } else if (quality >= 40) {
            rating = 'Moyenne';
            message = 'Recommandé de recapturer';
            color = 'yellow';
        } else {
            rating = 'Faible';
            message = 'Veuillez recapturer l\'empreinte';
            color = 'red';
        }
        
        return {
            quality,
            rating,
            message,
            color,
            acceptable: quality >= 40
        };
    }

    /**
     * Formatte les données de capture pour stockage en base de données
     * @param {Object} captureResult - Résultat de capture()
     * @returns {string} JSON stringifié
     */
    formatForStorage(captureResult) {
        return JSON.stringify({
            template: captureResult.template,
            image: captureResult.image,
            quality: captureResult.quality,
            format: captureResult.format || 'ANSI_378',
            capturedAt: new Date().toISOString(),
            device: 'Thales Multifinger Scanner'
        });
    }

    /**
     * Ferme la connexion WebSocket
     */
    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
            this.isConnected = false;
        }
    }

    /**
     * Vérifie si le service Thales est disponible
     * @returns {Promise<boolean>}
     */
    static async checkAvailability(url = 'ws://localhost:8181') {
        try {
            const ws = new WebSocket(url);
            
            return new Promise((resolve) => {
                ws.onopen = () => {
                    ws.close();
                    resolve(true);
                };
                
                ws.onerror = () => {
                    resolve(false);
                };
                
                setTimeout(() => resolve(false), 2000);
            });
        } catch(error) {
            return false;
        }
    }
}

// Export pour utilisation globale
if (typeof window !== 'undefined') {
    window.ThalesFingerprintCapture = ThalesFingerprintCapture;
}

// Export pour modules ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThalesFingerprintCapture;
}
