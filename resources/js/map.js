import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix pour les icônes Leaflet avec Webpack/Vite
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: iconRetinaUrl,
    iconUrl: iconUrl,
    shadowUrl: shadowUrl
});

class DashboardMap {
    constructor(containerId) {
        this.containerId = containerId;
        this.map = null;
        this.markersLayer = null;
        this.baseTileLayer = null;
        this.fallbackTileLayer = null;
        this.tileErrorCount = 0;
        this.fallbackActivated = false;
        this.currentFilters = {};
        this.activeRequest = null;
        this.lastRequestId = 0;
        this.onSiteClick = null;
        this.lastPayload = null;
        
        // Coordonnées du Nord-Kivu, RDC (centre approximatif)
        this.defaultCenter = [-0.8611, 29.2333]; // Goma, Nord-Kivu
        this.defaultZoom = 9;
    }

    getSiteTypeLabel(site) {
        const label = site?.categorie_site?.name;
        return (typeof label === 'string' && label.trim() !== '') ? label.trim() : 'Non specifie';
    }

    getTypeColor(typeLabel) {
        const palette = [
            '#2563EB',
            '#DC2626',
            '#059669',
            '#D97706',
            '#7C3AED',
            '#0891B2',
            '#BE123C',
            '#4F46E5',
        ];

        if (!typeLabel || typeLabel === 'Non specifie') {
            return '#6B7280';
        }

        let hash = 0;
        for (let index = 0; index < typeLabel.length; index += 1) {
            hash = ((hash << 5) - hash) + typeLabel.charCodeAt(index);
            hash |= 0;
        }

        return palette[Math.abs(hash) % palette.length];
    }

    buildTypeLegend(sites) {
        const counters = new Map();

        (sites || []).forEach((site) => {
            if (!Number.isFinite(site?.latitude) || !Number.isFinite(site?.longitude) || site.latitude === 0 || site.longitude === 0) {
                return;
            }

            const label = this.getSiteTypeLabel(site);
            counters.set(label, (counters.get(label) || 0) + 1);
        });

        return Array.from(counters.entries())
            .map(([label, count]) => ({
                label,
                count,
                color: this.getTypeColor(label)
            }))
            .sort((left, right) => left.label.localeCompare(right.label, 'fr'));
    }

    /**
     * Définit le callback exécuté lors du clic sur un site
     */
    setOnSiteClick(callback) {
        this.onSiteClick = typeof callback === 'function' ? callback : null;
    }

    /**
     * Initialise la carte Leaflet
     */
    init() {
        // Créer la carte centrée sur le Nord-Kivu
        this.map = L.map(this.containerId, {
            center: this.defaultCenter,
            zoom: this.defaultZoom,
            zoomControl: true,
            scrollWheelZoom: true
        });

        // Ajouter la couche de tuiles principale
        this.baseTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
            minZoom: 6
        }).addTo(this.map);

        // Fallback automatique si le provider principal échoue.
        this.baseTileLayer.on('tileerror', () => {
            this.tileErrorCount += 1;

            if (!this.fallbackActivated && this.tileErrorCount >= 6) {
                this.activateFallbackTiles();
            }
        });

        // Créer un groupe de marqueurs
        this.markersLayer = L.layerGroup().addTo(this.map);

        // Charger les sites initialement et retourner la promesse
        return this.loadSites();
    }

    activateFallbackTiles() {
        if (!this.map || this.fallbackActivated) {
            return;
        }

        this.fallbackActivated = true;

        if (this.baseTileLayer) {
            this.map.removeLayer(this.baseTileLayer);
        }

        this.fallbackTileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 19,
            minZoom: 6,
        }).addTo(this.map);

        console.warn('Leaflet: bascule automatique vers les tuiles fallback (CARTO).');
    }

    /**
     * Charge les sites depuis l'API
     */
    async loadSites(filters = {}) {
        this.currentFilters = filters;
        const requestId = ++this.lastRequestId;

        if (this.activeRequest) {
            this.activeRequest.abort();
        }

        this.activeRequest = new AbortController();
        
        try {
            const fetchSitesPayload = async (requestFilters = {}) => {
                const params = new URLSearchParams();

                if (requestFilters.province_id) params.append('province_id', requestFilters.province_id);
                if (requestFilters.territoire_id) params.append('territoire_id', requestFilters.territoire_id);
                if (requestFilters.commune_id) params.append('commune_id', requestFilters.commune_id);
                if (requestFilters.site_id) params.append('site_id', requestFilters.site_id);
                if (requestFilters.coordinateur_id) params.append('coordinateur_id', requestFilters.coordinateur_id);
                if (requestFilters.gestionnaire_id) params.append('gestionnaire_id', requestFilters.gestionnaire_id);
                if (requestFilters.categorie_site_id) params.append('categorie_site_id', requestFilters.categorie_site_id);
                if (requestFilters.periode) params.append('periode', requestFilters.periode);

                const response = await fetch(`/api/geographic/sites-coordinates?${params.toString()}`, {
                    signal: this.activeRequest.signal,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                return response.json();
            };

            let payload = await fetchSitesPayload(filters);
            let rawSites = Array.isArray(payload)
                ? payload
                : (Array.isArray(payload?.sites) ? payload.sites : []);

            // Evite une carte vide: si aucune donnée pour la période demandée, retenter sans période.
            if (filters.periode && rawSites.length === 0) {
                const fallbackFilters = { ...filters };
                delete fallbackFilters.periode;

                const fallbackPayload = await fetchSitesPayload(fallbackFilters);
                const fallbackSites = Array.isArray(fallbackPayload)
                    ? fallbackPayload
                    : (Array.isArray(fallbackPayload?.sites) ? fallbackPayload.sites : []);

                if (fallbackSites.length > 0) {
                    payload = Array.isArray(fallbackPayload)
                        ? fallbackPayload
                        : {
                            ...fallbackPayload,
                            requested_periode: filters.periode,
                            used_map_period_fallback: true,
                        };
                    rawSites = fallbackSites;
                }
            }

            const parseCoordinate = (value) => {
                if (value === null || value === undefined || value === '') {
                    return null;
                }

                const normalized = String(value).trim().replace(',', '.');
                const parsed = Number(normalized);

                return Number.isFinite(parsed) ? parsed : null;
            };

            const sites = rawSites.map(site => ({
                    ...site,
                    latitude: parseCoordinate(site.latitude),
                    longitude: parseCoordinate(site.longitude),
                    individus: site.individus === null || site.individus === undefined ? null : Number(site.individus),
                    menages: site.menages === null || site.menages === undefined ? null : Number(site.menages)
                }));

            if (requestId !== this.lastRequestId) {
                return [];
            }

            this.lastPayload = Array.isArray(payload)
                ? { sites }
                : { ...payload, sites };

            // Mettre à jour les marqueurs sur la carte
            this.updateMarkers(sites);

            return sites;
        } catch (error) {
            if (error.name === 'AbortError') {
                return [];
            }

            console.error('Erreur lors du chargement des sites:', error);
            return [];
        } finally {
            if (requestId === this.lastRequestId) {
                this.activeRequest = null;
            }
        }
    }

    /**
     * Met à jour les marqueurs sur la carte
     */
    updateMarkers(sites) {
        // Effacer tous les marqueurs existants
        this.markersLayer.clearLayers();

        const displayableSites = (sites || []).filter((site) => {
            return Number.isFinite(site.latitude)
                && Number.isFinite(site.longitude)
                && site.latitude !== 0
                && site.longitude !== 0;
        });

        if (!displayableSites.length) {
            console.log('Aucun site avec coordonnées GPS trouvé');
            // Recentrer sur le Nord-Kivu si aucun site
            this.map.setView(this.defaultCenter, this.defaultZoom);
            return;
        }

        // Créer des marqueurs pour chaque site
        const bounds = [];
        
        displayableSites.forEach(site => {
            const marker = this.createMarker(site);
            marker.addTo(this.markersLayer);
            bounds.push([site.latitude, site.longitude]);
        });

        // Ajuster la vue de la carte pour inclure tous les marqueurs
        if (bounds.length > 0) {
            this.fitBoundsWithIntelligentZoom(bounds, displayableSites.length);
        }
    }

    /**
     * Ajuste le zoom de manière intelligente selon le nombre de sites et leur dispersion
     */
    fitBoundsWithIntelligentZoom(bounds, siteCount) {
        const latLngBounds = L.latLngBounds(bounds);
        
        // Calculer la taille de la zone
        const ne = latLngBounds.getNorthEast();
        const sw = latLngBounds.getSouthWest();
        const latDiff = Math.abs(ne.lat - sw.lat);
        const lngDiff = Math.abs(ne.lng - sw.lng);
        const area = latDiff * lngDiff;
        
        // Déterminer le zoom maximal selon le nombre de sites et la zone
        let maxZoom = 13;
        
        if (siteCount === 1) {
            // Un seul site : zoom rapproché
            maxZoom = 15;
        } else if (siteCount <= 5) {
            // Quelques sites : zoom moyen-rapproché
            maxZoom = 14;
        } else if (area < 0.5) {
            // Zone compacte : zoom moyen
            maxZoom = 13;
        } else if (area < 2) {
            // Zone moyenne : zoom moyen-éloigné
            maxZoom = 11;
        } else {
            // Grande zone : zoom éloigné
            maxZoom = 10;
        }
        
        this.map.fitBounds(latLngBounds, {
            padding: [50, 50],
            maxZoom: maxZoom,
            animate: true,
            duration: 0.8
        });
    }

    /**
     * Crée un marqueur pour un site
     */
    createMarker(site) {
        // Créer une icône personnalisée selon la catégorie
        const icon = this.getIconForSite(site);

        // Créer le marqueur
        const marker = L.marker([site.latitude, site.longitude], {
            icon: icon,
            title: site.nom
        });

        // Créer le popup avec les informations du site
        const popupContent = this.createPopupContent(site);
        marker.bindPopup(popupContent);

        marker.on('click', () => {
            if (this.onSiteClick) {
                this.onSiteClick(site);
            }

            document.dispatchEvent(new CustomEvent('dashboard-map:site-click', {
                detail: { site }
            }));
        });

        return marker;
    }

    /**
     * Crée le contenu HTML du popup pour un site
     */
    createPopupContent(site) {
        const individus = site.individus === null || site.individus === undefined
            ? 'N/A'
            : site.individus.toLocaleString('fr-FR');
        const menages = site.menages === null || site.menages === undefined
            ? 'N/A'
            : site.menages.toLocaleString('fr-FR');
        const categorie = site.categorie_site ? site.categorie_site.name : 'Non spécifié';
        const gestionnaire = site.gestionnaire ? site.gestionnaire.name : 'Non spécifié';
        const coordinateur = site.coordinateur ? site.coordinateur.name : 'Non spécifié';
        const organisation = site.organisation ? site.organisation.name : 'Non spécifié';

        return `
            <div class="site-popup" style="min-width: 250px;">
                <h3 style="margin: 0 0 10px 0; font-weight: bold; color: #1f2937; font-size: 16px;">
                    ${site.nom}
                </h3>
                <div style="font-size: 13px; color: #4b5563;">
                    <p style="margin: 5px 0;">
                        <strong>Code:</strong> ${site.code_site || 'N/A'}
                    </p>
                    <p style="margin: 5px 0;">
                        <strong>Province:</strong> ${site.province || 'N/A'}
                    </p>
                    <p style="margin: 5px 0;">
                        <strong>Territoire:</strong> ${site.territoire || 'N/A'}
                    </p>
                    <p style="margin: 5px 0;">
                        <strong>Zone de santé:</strong> ${site.zone_sante || 'N/A'}
                    </p>
                    <hr style="margin: 10px 0; border: none; border-top: 1px solid #e5e7eb;">
                    <p style="margin: 5px 0;">
                        <strong>👥 Individus:</strong> <span style="color: #2563eb; font-weight: 600;">${individus}</span>
                    </p>
                    <p style="margin: 5px 0;">
                        <strong>🏠 Ménages:</strong> <span style="color: #059669; font-weight: 600;">${menages}</span>
                    </p>
                    <hr style="margin: 10px 0; border: none; border-top: 1px solid #e5e7eb;">
                    <p style="margin: 5px 0; font-size: 12px;">
                        <strong>Mécanisme CCCM:</strong> ${categorie}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px;">
                        <strong>Organisation:</strong> ${organisation}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px;">
                        <strong>Gestionnaire:</strong> ${gestionnaire}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px;">
                        <strong>Coordinateur:</strong> ${coordinateur}
                    </p>
                </div>
            </div>
        `;
    }

    /**
     * Retourne l'icône appropriée pour un site
     */
    getIconForSite(site) {
        const typeLabel = this.getSiteTypeLabel(site);
        const color = this.getTypeColor(typeLabel);

        return L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="
                    background-color: ${color};
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 10px;
                    font-weight: bold;
                ">
                    📍
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -12]
        });
    }

    /**
     * Recentre la carte sur le Nord-Kivu
     */
    resetView() {
        this.map.setView(this.defaultCenter, this.defaultZoom);
    }

    getLastPayload() {
        return this.lastPayload;
    }

    getTypeLegend(sites = null) {
        if (Array.isArray(sites)) {
            return this.buildTypeLegend(sites);
        }

        const payloadSites = this.lastPayload?.sites;
        return this.buildTypeLegend(Array.isArray(payloadSites) ? payloadSites : []);
    }

    /**
     * Détruit la carte
     */
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}

// Export pour utilisation globale
window.DashboardMap = DashboardMap;

export default DashboardMap;
