@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-600">Mobile</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Collecte hors ligne et synchronisation</h1>
        </div>
        <div class="flex items-center gap-3">
            <span id="networkStatus" class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                En ligne
            </span>
            <button id="syncBtn" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                Synchroniser maintenant
            </button>
        </div>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Type de collecte">
            <button type="button" class="tab-btn active rounded-full border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700" data-tab="sector">Collecte multisectorielle</button>
            <button type="button" class="tab-btn rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700" data-tab="geography">Géographie du site</button>
        </div>
    </div>

    <div id="sectorTab" class="tab-panel">
        <form id="sectorForm" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Site</label>
                    <select name="site_id" id="siteSelect" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" required>
                        <option value="">Sélectionner un site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->nom }} ({{ $site->code_site ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Date de collecte</label>
                    <input type="date" name="date_collecte" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" required>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-gray-600">Thématique</p>
                <div class="flex flex-wrap gap-2" id="sectorButtons">
                    <button type="button" class="sector-btn active rounded-full border border-primary-200 bg-primary-600 px-3 py-2 text-sm font-medium text-white" data-sector="wash">WASH</button>
                    <button type="button" class="sector-btn rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700" data-sector="sante">Santé</button>
                    <button type="button" class="sector-btn rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700" data-sector="protection">Protection</button>
                    <button type="button" class="sector-btn rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700" data-sector="education">Éducation</button>
                    <button type="button" class="sector-btn rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700" data-sector="environnement">Environnement</button>
                    <button type="button" class="sector-btn rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700" data-sector="abri">Abri / AME</button>
                </div>
            </div>

            <div id="sectorFields" class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Disponible</label>
                    <select name="wash_disponible" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Points d'eau</label>
                    <input type="number" name="wash_points_eau" value="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Latrines</label>
                    <input type="number" name="wash_latrines" value="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Douches</label>
                    <input type="number" name="wash_douches" value="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
                <div class="md:col-span-2 rounded-2xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Observations / commentaires</label>
                    <textarea name="wash_observations" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"></textarea>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Enregistrer dans la file hors ligne</button>
                <button type="button" id="saveAndSyncBtn" class="rounded-lg border border-primary-200 bg-primary-50 px-5 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-100">Enregistrer et synchroniser</button>
            </div>
        </form>
    </div>

    <div id="geoTab" class="tab-panel hidden">
        <form id="geoForm" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Site</label>
                    <select name="site_id_geo" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" required>
                        <option value="">Sélectionner un site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Type de géométrie</label>
                    <select name="geometry_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                        <option value="point">Point</option>
                        <option value="polygon">Polygone</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="0.000001" name="latitude" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="0.000001" name="longitude" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100">
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4">
                <label class="mb-2 block text-sm font-medium text-gray-700">GeoJSON / Polygone</label>
                <textarea name="geojson" rows="8" placeholder='{"type":"Polygon","coordinates":[[[...]]]}' class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"></textarea>
            </div>

            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Enregistrer la géométrie hors ligne</button>
        </form>
    </div>
</div>

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/mobile-sw.js').catch(() => {});
    });
}

const queueKey = 'dms-mobile-queue-v1';
const csrfToken = '{{ csrf_token() }}';
    const tabs = document.querySelectorAll('.tab-btn');
    const panels = {
        sector: document.getElementById('sectorTab'),
        geography: document.getElementById('geoTab')
    };

    function updateNetworkStatus() {
        const status = document.getElementById('networkStatus');
        const online = navigator.onLine;
        status.textContent = online ? 'En ligne' : 'Hors ligne';
        status.className = online
            ? 'inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-semibold text-green-700'
            : 'inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700';
    }

    function getQueue() {
        try {
            return JSON.parse(localStorage.getItem(queueKey) || '[]');
        } catch (error) {
            return [];
        }
    }

    function saveQueue(items) {
        localStorage.setItem(queueKey, JSON.stringify(items));
    }

    function queueRecord(record) {
        const queue = getQueue();
        queue.push(record);
        saveQueue(queue);
        return queue.length;
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = type === 'error'
            ? 'fixed right-4 top-4 z-50 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 shadow-lg'
            : 'fixed right-4 top-4 z-50 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 shadow-lg';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach((button) => {
                const active = button === tab;
                button.classList.toggle('bg-primary-50', active);
                button.classList.toggle('border-primary-200', active);
                button.classList.toggle('text-primary-700', active);
                button.classList.toggle('bg-gray-50', !active);
                button.classList.toggle('border-gray-200', !active);
                button.classList.toggle('text-gray-700', !active);
            });
            Object.entries(panels).forEach(([name, panel]) => {
                panel.classList.toggle('hidden', name !== target);
            });
        });
    });

    const sectorButtons = document.querySelectorAll('.sector-btn');
    let activeSector = 'wash';

    sectorButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeSector = button.dataset.sector;
            sectorButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle('bg-primary-600', isActive);
                item.classList.toggle('text-white', isActive);
                item.classList.toggle('border-primary-200', isActive);
                item.classList.toggle('bg-white', !isActive);
                item.classList.toggle('text-gray-700', !isActive);
                item.classList.toggle('border-gray-200', !isActive);
            });

            const fieldMap = {
                wash: ['wash_disponible','wash_points_eau','wash_latrines','wash_douches','wash_observations'],
                sante: ['sante_disponible','sante_structures_fonctionnelles','sante_personnel_medical','sante_consultations_mois','sante_observations'],
                protection: ['gestion_disponible','gestion_membres_comite','gestion_mecanisme_plainte','gestion_reunions_mois','gestion_observations'],
                education: ['education_disponible','education_ecoles_fonctionnelles','education_enseignants','education_eleves_inscrits','education_observations'],
                environnement: ['environnement_disponible','environnement_gestion_dechets','environnement_drainage','environnement_espaces_verts','environnement_observations'],
                abri: ['abri_ame_disponible','abri_logements_fonctionnels','abri_menages_ame','abri_types','abri_observations'],
            };

            const fields = fieldMap[activeSector] || fieldMap.wash;
            document.querySelectorAll('#sectorFields input, #sectorFields textarea, #sectorFields select').forEach((input) => {
                const wrapper = input.closest('.rounded-2xl');
                if (!wrapper) return;
                wrapper.classList.remove('hidden');
                if (!fields.includes(input.name)) {
                    wrapper.classList.add('hidden');
                }
            });
        });
    });

    function buildSectorPayload(form) {
        const formData = new FormData(form);
        const payload = {};
        for (const [name, value] of formData.entries()) {
            if (name === 'site_id' || name === 'date_collecte') continue;
            payload[name] = value;
        }
        payload.site_id = form.querySelector('[name="site_id"]').value;
        payload.date_collecte = form.querySelector('[name="date_collecte"]').value;
        payload.sector = activeSector;
        return payload;
    }

    function persistSector(form, shouldSync = false) {
        const siteId = form.querySelector('[name="site_id"]').value;
        const dateCollecte = form.querySelector('[name="date_collecte"]').value;
        if (!siteId || !dateCollecte) {
            showToast('Sélectionnez le site et la date pour continuer.', 'error');
            return;
        }

        const payload = buildSectorPayload(form);
        const record = {
            id: 'local_' + Date.now(),
            type: 'sector',
            sector: activeSector,
            site_id: Number(siteId),
            payload,
            created_at: new Date().toISOString()
        };

        queueRecord(record);
        showToast('Données enregistrées hors ligne.');

        if (shouldSync && navigator.onLine) {
            syncQueue();
        }
    }

    function persistGeography(form) {
        const siteId = form.querySelector('[name="site_id_geo"]').value;
        if (!siteId) {
            showToast('Sélectionnez un site pour enregistrer la géométrie.', 'error');
            return;
        }

        const geoJsonValue = form.querySelector('[name="geojson"]').value.trim();
        let parsedGeojson = null;
        if (geoJsonValue) {
            try {
                parsedGeojson = JSON.parse(geoJsonValue);
            } catch (error) {
                showToast('Le GeoJSON est invalide, utilisez un format JSON correct.', 'error');
                return;
            }
        }

        const payload = {
            site_id: Number(siteId),
            latitude: form.querySelector('[name="latitude"]').value,
            longitude: form.querySelector('[name="longitude"]').value,
            geojson: parsedGeojson,
            geometry_type: form.querySelector('[name="geometry_type"]').value,
            date_collecte: new Date().toISOString().slice(0,10)
        };

        const record = {
            id: 'geo_' + Date.now(),
            type: 'geography',
            sector: 'geography',
            site_id: Number(siteId),
            payload,
            created_at: new Date().toISOString()
        };

        queueRecord(record);
        showToast('Géométrie enregistrée hors ligne.');

        if (navigator.onLine) {
            syncQueue();
        }
    }

    async function syncQueue() {
        const queue = getQueue();
        if (!queue.length) {
            showToast('Aucune donnée en attente de synchronisation.');
            return;
        }
        if (!navigator.onLine) {
            showToast('Connexion indisponible. La synchronisation est reportée.', 'error');
            return;
        }

        try {
            const response = await fetch('/mobile/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ records: queue })
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Erreur de synchronisation.');
            }

            saveQueue([]);
            showToast(result.message || 'Synchronisation terminée.');
        } catch (error) {
            showToast(error.message || 'Synchronisation impossible.', 'error');
        }
    }

    document.getElementById('sectorForm').addEventListener('submit', function(event) {
        event.preventDefault();
        persistSector(this, false);
    });

    document.getElementById('saveAndSyncBtn').addEventListener('click', function() {
        persistSector(document.getElementById('sectorForm'), true);
    });

    document.getElementById('geoForm').addEventListener('submit', function(event) {
        event.preventDefault();
        persistGeography(this);
    });

    document.getElementById('syncBtn').addEventListener('click', syncQueue);
    window.addEventListener('online', updateNetworkStatus);
    window.addEventListener('offline', updateNetworkStatus);
    updateNetworkStatus();
</script>
@endsection
