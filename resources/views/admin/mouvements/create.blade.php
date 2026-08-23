@extends('layouts.app')

@section('title', 'Ajouter un mouvement de population')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau mouvement de population</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Enregistrer les entrées et sorties de personnes déplacées dans les sites
                <span class="ml-2 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-xs font-semibold rounded-full">
                    ⚠️ Nécessite validation du super admin
                </span>
            </p>
        </div>
        <a href="{{ route('admin.mouvements.index') }}" class="filter-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour à la liste
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Erreurs de validation</h3>
                    <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.mouvements.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Informations de base -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations du mouvement</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sélection géographique en cascade -->
                <div>
                    <label for="site_province" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Province <span class="text-red-500">*</span>
                    </label>
                    <select id="site_province" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sélectionnez une province</option>
                    </select>
                </div>

                <div>
                    <label for="site_territoire" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Territoire <span class="text-red-500">*</span>
                    </label>
                    <select id="site_territoire" required disabled
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60">
                        <option value="">Sélectionnez d'abord une province</option>
                    </select>
                </div>

                <div>
                    <label for="site_zone_sante" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Zone de santé <span class="text-red-500">*</span>
                    </label>
                    <select id="site_zone_sante" required disabled
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60">
                        <option value="">Sélectionnez d'abord un territoire</option>
                    </select>
                </div>

                <!-- Site -->
                <div>
                    <label for="site_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Site <span class="text-red-500">*</span>
                    </label>
                    <select id="site_id" name="site_id" required disabled
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60">
                        <option value="">Sélectionnez d'abord une zone de santé</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}"
                                    data-province="{{ $site->province }}"
                                    data-territoire="{{ $site->territoire }}"
                                    data-zone-sante="{{ $site->zone_sante }}"
                                    {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->nom }} ({{ $site->code_site }}){{ $site->date_fermeture ? ' [Site fermé]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('site_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type de mouvement -->
                <div>
                    <label for="type_mouvement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Type de mouvement <span class="text-red-500">*</span>
                    </label>
                    <select id="type_mouvement" name="type_mouvement" required 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Sélectionnez un type</option>
                        <option value="arrivee" {{ old('type_mouvement') == 'arrivee' ? 'selected' : '' }}>
                            ➕ Arrivée / Nouvelle entrée
                        </option>
                        <option value="depart" {{ old('type_mouvement') == 'depart' ? 'selected' : '' }}>
                            ➖ Départ / Sortie
                        </option>
                        <option value="recensement" {{ old('type_mouvement') == 'recensement' ? 'selected' : '' }}>
                            📊 Recensement / Mise à jour complète
                        </option>
                        <option value="ajustement" {{ old('type_mouvement') == 'ajustement' ? 'selected' : '' }}>
                            🔄 Ajustement
                        </option>
                    </select>
                    @error('type_mouvement')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date du mouvement -->
                <div>
                    <label for="date_mouvement" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date du mouvement <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="date_mouvement" name="date_mouvement" required 
                           value="{{ old('date_mouvement', date('Y-m-d')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    @error('date_mouvement')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Raison du mouvement -->
                <div class="md:col-span-2">
                    <label for="raison_mouvement_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison du mouvement
                    </label>
                    <select id="raison_mouvement_id" name="raison_mouvement_id" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Aucune raison spécifiée</option>
                        <optgroup label="Raisons d'entrée" id="raisons-entree" style="display: none;">
                            @foreach($raisonsEntree as $raison)
                                <option value="{{ $raison->id }}" {{ old('raison_mouvement_id') == $raison->id ? 'selected' : '' }}>
                                    {{ $raison->name }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Raisons de sortie" id="raisons-sortie" style="display: none;">
                            @foreach($raisonsSortie as $raison)
                                <option value="{{ $raison->id }}" {{ old('raison_mouvement_id') == $raison->id ? 'selected' : '' }}>
                                    {{ $raison->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <!-- Période -->
                <div>
                    <label for="periode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Période
                    </label>
                    <input type="text" id="periode" name="periode" value="{{ old('periode') }}"
                           placeholder="Ex: Janvier 2026"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Source -->
                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Source des données
                    </label>
                    <input type="text" id="source" name="source" value="{{ old('source') }}"
                           placeholder="Ex: DTM, Site Management"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Données démographiques -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Données démographiques</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="info-type-mouvement">Les valeurs seront ajoutées à la population actuelle</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ménages -->
                <div class="md:col-span-2 bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg">
                    <label for="menages" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre de ménages <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="menages" name="menages" required min="0"
                           value="{{ old('menages', 0) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Total individus (calculé automatiquement) -->
                <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <label for="individus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Total individus (calculé automatiquement) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="individus" name="individus" required min="0" readonly
                           value="{{ old('individus', 0) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white bg-gray-100 dark:bg-gray-600">
                </div>
            </div>

            <!-- Répartition par sexe et âge -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Femmes -->
                <div class="border border-pink-200 dark:border-pink-800 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-pink-600 dark:text-pink-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Femmes
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">0-5 ans</label>
                            <input type="number" name="f_0_5" min="0" value="{{ old('f_0_5', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">6-17 ans</label>
                            <input type="number" name="f_6_17" min="0" value="{{ old('f_6_17', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">18-59 ans</label>
                            <input type="number" name="f_18_59" min="0" value="{{ old('f_18_59', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">60+ ans</label>
                            <input type="number" name="f_60_plus" min="0" value="{{ old('f_60_plus', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Hommes -->
                <div class="border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-blue-600 dark:text-blue-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Hommes
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">0-5 ans</label>
                            <input type="number" name="h_0_5" min="0" value="{{ old('h_0_5', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">6-17 ans</label>
                            <input type="number" name="h_6_17" min="0" value="{{ old('h_6_17', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">18-59 ans</label>
                            <input type="number" name="h_18_59" min="0" value="{{ old('h_18_59', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">60+ ans</label>
                            <input type="number" name="h_60_plus" min="0" value="{{ old('h_60_plus', 0) }}"
                                   class="age-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes et description -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations complémentaires</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="raison" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison détaillée
                    </label>
                    <input type="text" id="raison" name="raison" value="{{ old('raison') }}"
                           placeholder="Précisions sur la raison du mouvement"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description / Notes
                    </label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Ajoutez des notes ou des observations sur ce mouvement de population..."
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.mouvements.index') }}" class="filter-button">
                Annuler
            </a>
            <button type="submit" class="primary-button">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Enregistrer le mouvement
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeMouvementSelect = document.getElementById('type_mouvement');
    const raisonMouvementSelect = document.getElementById('raison_mouvement_id');
    const raisonsEntree = document.getElementById('raisons-entree');
    const raisonsSortie = document.getElementById('raisons-sortie');
    const infoTypeMouvement = document.getElementById('info-type-mouvement');
    const ageInputs = document.querySelectorAll('.age-input');
    const individusInput = document.getElementById('individus');
    const provinceSelect = document.getElementById('site_province');
    const territoireSelect = document.getElementById('site_territoire');
    const zoneSanteSelect = document.getElementById('site_zone_sante');
    const siteSelect = document.getElementById('site_id');
    const missingValue = '__non_renseigne__';
    const selectedSiteId = @json((string) old('site_id', ''));
    const sites = Array.from(siteSelect.querySelectorAll('option[value]'))
        .filter(option => option.value)
        .map(option => ({
            id: option.value,
            label: option.textContent.trim(),
            province: option.dataset.province?.trim() || missingValue,
            territoire: option.dataset.territoire?.trim() || missingValue,
            zoneSante: option.dataset.zoneSante?.trim() || missingValue,
        }));

    function dimensionLabel(value) {
        return value === missingValue ? 'Non renseigné' : value;
    }

    function uniqueValues(values) {
        return [...new Set(values)].sort((left, right) =>
            dimensionLabel(left).localeCompare(dimensionLabel(right), 'fr', { sensitivity: 'base' })
        );
    }

    function fillSelect(select, values, placeholder, selectedValue = '') {
        select.replaceChildren(new Option(placeholder, ''));
        values.forEach(value => {
            select.appendChild(new Option(dimensionLabel(value), value, false, value === selectedValue));
        });
        select.disabled = values.length === 0;
    }

    function resetSites() {
        siteSelect.replaceChildren(new Option("Sélectionnez d'abord une zone de santé", ''));
        siteSelect.disabled = true;
    }

    function loadSites(selectedValue = '') {
        const filteredSites = sites
            .filter(site =>
                site.province === provinceSelect.value
                && site.territoire === territoireSelect.value
                && site.zoneSante === zoneSanteSelect.value
            )
            .sort((left, right) => left.label.localeCompare(right.label, 'fr', { sensitivity: 'base' }));

        siteSelect.replaceChildren(new Option('Sélectionnez un site', ''));
        filteredSites.forEach(site => {
            siteSelect.appendChild(new Option(site.label, site.id, false, site.id === selectedValue));
        });
        siteSelect.disabled = filteredSites.length === 0;
    }

    function loadZones(selectedValue = '') {
        const zones = uniqueValues(
            sites
                .filter(site => site.province === provinceSelect.value && site.territoire === territoireSelect.value)
                .map(site => site.zoneSante)
        );
        fillSelect(zoneSanteSelect, zones, 'Sélectionnez une zone de santé', selectedValue);
        resetSites();
    }

    function loadTerritoires(selectedValue = '') {
        const territoires = uniqueValues(
            sites
                .filter(site => site.province === provinceSelect.value)
                .map(site => site.territoire)
        );
        fillSelect(territoireSelect, territoires, 'Sélectionnez un territoire', selectedValue);
        fillSelect(zoneSanteSelect, [], "Sélectionnez d'abord un territoire");
        resetSites();
    }

    fillSelect(provinceSelect, uniqueValues(sites.map(site => site.province)), 'Sélectionnez une province');

    provinceSelect.addEventListener('change', function() {
        loadTerritoires();
    });

    territoireSelect.addEventListener('change', function() {
        loadZones();
    });

    zoneSanteSelect.addEventListener('change', function() {
        loadSites();
    });

    if (selectedSiteId) {
        const selectedSite = sites.find(site => site.id === selectedSiteId);
        if (selectedSite) {
            provinceSelect.value = selectedSite.province;
            loadTerritoires(selectedSite.territoire);
            loadZones(selectedSite.zoneSante);
            loadSites(selectedSite.id);
        }
    }

    // Gérer l'affichage des raisons selon le type de mouvement
    typeMouvementSelect.addEventListener('change', function() {
        const type = this.value;
        
        // Cacher toutes les raisons
        raisonsEntree.style.display = 'none';
        raisonsSortie.style.display = 'none';
        raisonMouvementSelect.value = '';

        // Afficher les raisons appropriées
        if (type === 'arrivee') {
            raisonsEntree.style.display = 'block';
            infoTypeMouvement.textContent = 'Les valeurs seront ajoutées à la population actuelle';
        } else if (type === 'depart') {
            raisonsSortie.style.display = 'block';
            infoTypeMouvement.textContent = 'Les valeurs seront soustraites de la population actuelle (utilisez des nombres positifs)';
        } else if (type === 'recensement') {
            infoTypeMouvement.textContent = 'Les valeurs remplaceront complètement la population actuelle';
        } else if (type === 'ajustement') {
            infoTypeMouvement.textContent = 'Les valeurs seront ajoutées ou soustraites (utilisez des nombres négatifs pour diminuer)';
        }
    });

    // Calculer automatiquement le total d'individus
    function calculateTotal() {
        let total = 0;
        ageInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        individusInput.value = total;
    }

    // Écouter les changements sur tous les champs d'âge
    ageInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Calculer au chargement
    calculateTotal();
});
</script>
@endsection
