@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $site->nom }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $site->territoire }}, {{ $site->province }}</p>
            </div>
            <a href="{{ route('organisation.sites.index') }}" 
               class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-3 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-200">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Tableau de bord OSSAT --}}
        @include('ossat.partials.site-dashboard', ['site' => $site, 'ossatReport' => $ossatReport ?? null, 'populationMouvement' => $populationMouvement ?? null])

        <!-- Informations générales (lecture seule) -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informations générales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Code Site</label>
                    <p class="text-gray-900 dark:text-white">{{ $site->code_site ?? '-' }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Type de Site</label>
                    <p class="text-gray-900 dark:text-white">{{ $site->typeSite->name ?? '-' }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Catégorie</label>
                    <p class="text-gray-900 dark:text-white">{{ $site->categorieSite->name ?? '-' }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Zone de Santé</label>
                    <p class="text-gray-900 dark:text-white">{{ $site->zone_sante }}</p>
                </div>

                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Population</label>
                    <p class="text-gray-900 dark:text-white">
                        {{ number_format($site->individus ?? 0) }} personnes
                        @if($site->menages)
                            ({{ number_format($site->menages) }} ménages)
                        @endif
                    </p>
                </div>

                <div>
                    <label class="font-medium text-gray-700 dark:text-gray-300">Date de mise à jour</label>
                    <p class="text-gray-900 dark:text-white">
                        {{ $site->date_mise_a_jour ? \Illuminate\Support\Carbon::parse($site->date_mise_a_jour)->format('d/m/Y') : '-' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Coordonnées GPS -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                Coordonnées GPS
            </h3>

            <form method="POST" action="{{ route('organisation.sites.update', $site) }}">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Latitude
                        </label>
                        <input type="number" 
                               name="latitude" 
                               id="latitude" 
                               step="0.00000001"
                               value="{{ old('latitude', $site->latitude) }}"
                               placeholder="Ex: -4.3250623"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entre -90 et 90</p>
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Longitude
                        </label>
                        <input type="number" 
                               name="longitude" 
                               id="longitude" 
                               step="0.00000001"
                               value="{{ old('longitude', $site->longitude) }}"
                               placeholder="Ex: 15.3350623"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entre -180 et 180</p>
                    </div>
                </div>

                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Enregistrer les coordonnées
                </button>
            </form>

            @if($site->latitude && $site->longitude)
            <div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded border border-emerald-200 dark:border-emerald-800">
                <p class="text-sm text-emerald-800 dark:text-emerald-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Coordonnées GPS enregistrées : {{ $site->latitude }}, {{ $site->longitude }}
                </p>
            </div>
            @endif
        </div>

        <!-- Données GeoJSON -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
                Données GeoJSON
            </h3>

            <form id="geojsonEditForm" method="POST" action="{{ route('organisation.sites.update', $site) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="geojson_active_layer" id="geojsonActiveLayerField" value="geojson_layer_ecole">

                @php
                    $defaultGeojsonData = old('geojson_data');
                    $defaultLayerFields = [
                        'geojson_layer_ecole' => old('geojson_layer_ecole'),
                        'geojson_layer_robinet' => old('geojson_layer_robinet'),
                        'geojson_layer_lavage_main' => old('geojson_layer_lavage_main'),
                        'geojson_layer_bloc_sites' => old('geojson_layer_bloc_sites'),
                        'geojson_layer_centre_sante' => old('geojson_layer_centre_sante'),
                    ];

                    $layerFieldByKey = [
                        'ecole' => 'geojson_layer_ecole',
                        'robinet' => 'geojson_layer_robinet',
                        'lavage_main' => 'geojson_layer_lavage_main',
                        'bloc_sites' => 'geojson_layer_bloc_sites',
                        'centre_sante' => 'geojson_layer_centre_sante',
                    ];

                    if ($defaultGeojsonData === null && $site->geojson_data) {
                        if (isset($site->geojson_data['layers']) && is_array($site->geojson_data['layers'])) {
                            foreach ($site->geojson_data['layers'] as $layerItem) {
                                if (!is_array($layerItem)) {
                                    continue;
                                }

                                $layerKey = (string) ($layerItem['key'] ?? '');
                                $layerGeojson = $layerItem['geojson'] ?? null;
                                if (!is_array($layerGeojson)) {
                                    continue;
                                }

                                $baseLayerKey = strstr($layerKey, '_', true) ?: $layerKey;
                                $targetField = $layerFieldByKey[$baseLayerKey] ?? null;
                                if ($targetField) {
                                    $defaultLayerFields[$targetField] = json_encode($layerGeojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                } else {
                                    $defaultGeojsonData = json_encode($layerGeojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                            }
                        } else {
                            $defaultGeojsonData = json_encode($site->geojson_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                    }
                @endphp
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Couche GeoJSON
                    </label>

                    <div class="geojson-layer-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojson_data">
                        <div class="flex items-center justify-between gap-2">
                            <label for="geojson_data" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                GeoJSON
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                        data-clear-target="geojson_data">
                                    Supprimer cette couche
                                </button>
                                <input type="file"
                                       id="geojsonDataFile"
                                       data-target="geojson_data"
                                       accept=".geojson,.json,application/geo+json,application/json"
                                       class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                            </div>
                        </div>
                        <textarea name="geojson_data"
                                  id="geojson_data"
                                  rows="6"
                                  placeholder='{"type":"Feature","geometry":{...}}'
                                  class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultGeojsonData }}</textarea>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 mt-3 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <label for="geojsonLayerPicker" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Edition pas-a-pas: afficher une couche a la fois
                            </label>
                            <div class="flex items-center gap-2">
                                <select id="geojsonLayerPicker" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500">
                                    <option value="geojson_layer_ecole">Ecole</option>
                                    <option value="geojson_layer_robinet">Robinet</option>
                                    <option value="geojson_layer_lavage_main">Lavage main</option>
                                    <option value="geojson_layer_bloc_sites">Bloc sites</option>
                                    <option value="geojson_layer_centre_sante">Centre de sante</option>
                                    <option value="geojson_data">Autre couche</option>
                                </select>
                                <button type="button" id="geojsonNextLayer" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    Couche suivante
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Chargez et enregistrez chaque couche une par une pour eviter la surcharge du navigateur.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <div class="geojson-layer-panel" data-layer-target="geojson_layer_ecole">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="geojson_layer_ecole" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ecole</label>
                                <button type="button" class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30" data-clear-target="geojson_layer_ecole">Supprimer cette couche</button>
                            </div>
                            <textarea name="geojson_layer_ecole" id="geojson_layer_ecole" rows="5" placeholder='{"type":"Feature","geometry":{...}}' class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultLayerFields['geojson_layer_ecole'] }}</textarea>
                        </div>
                        <div class="geojson-layer-panel hidden" data-layer-target="geojson_layer_robinet">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="geojson_layer_robinet" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Robinet</label>
                                <button type="button" class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30" data-clear-target="geojson_layer_robinet">Supprimer cette couche</button>
                            </div>
                            <textarea name="geojson_layer_robinet" id="geojson_layer_robinet" rows="5" placeholder='{"type":"Feature","geometry":{...}}' class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultLayerFields['geojson_layer_robinet'] }}</textarea>
                        </div>
                        <div class="geojson-layer-panel hidden" data-layer-target="geojson_layer_lavage_main">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="geojson_layer_lavage_main" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lavage main</label>
                                <button type="button" class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30" data-clear-target="geojson_layer_lavage_main">Supprimer cette couche</button>
                            </div>
                            <textarea name="geojson_layer_lavage_main" id="geojson_layer_lavage_main" rows="5" placeholder='{"type":"Feature","geometry":{...}}' class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultLayerFields['geojson_layer_lavage_main'] }}</textarea>
                        </div>
                        <div class="geojson-layer-panel hidden" data-layer-target="geojson_layer_bloc_sites">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="geojson_layer_bloc_sites" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bloc sites</label>
                                <button type="button" class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30" data-clear-target="geojson_layer_bloc_sites">Supprimer cette couche</button>
                            </div>
                            <textarea name="geojson_layer_bloc_sites" id="geojson_layer_bloc_sites" rows="5" placeholder='{"type":"Feature","geometry":{...}}' class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultLayerFields['geojson_layer_bloc_sites'] }}</textarea>
                        </div>
                        <div class="geojson-layer-panel hidden md:col-span-2" data-layer-target="geojson_layer_centre_sante">
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="geojson_layer_centre_sante" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Centre de sante</label>
                                <button type="button" class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30" data-clear-target="geojson_layer_centre_sante">Supprimer cette couche</button>
                            </div>
                            <textarea name="geojson_layer_centre_sante" id="geojson_layer_centre_sante" rows="5" placeholder='{"type":"Feature","geometry":{...}}' class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ $defaultLayerFields['geojson_layer_centre_sante'] }}</textarea>
                        </div>
                    </div>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Vous pouvez enregistrer plusieurs couches GeoJSON (Ecole, Robinet, Lavage main, Bloc sites, Centre de sante) et les superposer dans la cartographie.
                        <br>
                        Format GeoJSON standard.
                        <a href="https://geojson.org/" target="_blank" class="text-primary-600 hover:text-primary-700">Voir la documentation</a>
                    </p>
                </div>

                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Enregistrer la couche GeoJSON
                </button>
            </form>
        </div>

        <!-- Photos du site -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Photos du Site
            </h3>

            <!-- Galerie de photos existantes -->
            @if($site->photos && count($site->photos) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                @foreach($site->photos as $photo)
                <div class="relative group">
                    <img src="{{ asset('storage/' . $photo) }}" 
                         alt="Photo du site"
                         class="w-full h-48 object-cover rounded-lg">
                    
                    <form method="POST" 
                          action="{{ route('organisation.sites.delete-photo', $site) }}"
                          class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="photo_path" value="{{ $photo }}">
                        <button type="submit" 
                                onclick="return confirm('Supprimer cette photo ?')"
                                class="p-2 bg-red-500 hover:bg-red-600 text-white rounded-full shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Formulaire d'ajout de photos -->
            <form method="POST" 
                  action="{{ route('organisation.sites.update', $site) }}" 
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="photos" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ajouter des photos
                    </label>
                    <input type="file" 
                           name="photos[]" 
                           id="photos" 
                           multiple
                           accept="image/*"
                           class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG, GIF (max 5MB par image). Vous pouvez sélectionner plusieurs fichiers.
                    </p>
                </div>

                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Ajouter les photos
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const geojsonLayerPicker = document.getElementById('geojsonLayerPicker');
const geojsonNextLayer = document.getElementById('geojsonNextLayer');
const geojsonLayerPanels = Array.from(document.querySelectorAll('.geojson-layer-panel'));
const geojsonInputs = Array.from(document.querySelectorAll('.geojson-data-input'));
const geojsonActiveLayerField = document.getElementById('geojsonActiveLayerField');
const geojsonEditForm = document.getElementById('geojsonEditForm');

// Reduce rendering overhead for very large GeoJSON text payloads.
geojsonInputs.forEach(input => {
    input.setAttribute('spellcheck', 'false');
    input.setAttribute('autocapitalize', 'off');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('autocorrect', 'off');
    input.setAttribute('wrap', 'off');
});

function showGeojsonLayerPanel(targetId) {
    geojsonLayerPanels.forEach(panel => {
        const panelTarget = panel.dataset.layerTarget || '';
        if (panelTarget === targetId) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    });

    if (geojsonLayerPicker) {
        geojsonLayerPicker.value = targetId;
    }

    if (geojsonActiveLayerField) {
        geojsonActiveLayerField.value = targetId;
    }

    if (geojsonActiveLayerField) {
        geojsonActiveLayerField.value = targetId;
    }
}

geojsonLayerPicker?.addEventListener('change', function() {
    showGeojsonLayerPanel(this.value || 'geojson_layer_ecole');
});

geojsonNextLayer?.addEventListener('click', function() {
    if (!geojsonLayerPicker) {
        return;
    }

    const options = Array.from(geojsonLayerPicker.options);
    const currentIndex = Math.max(0, geojsonLayerPicker.selectedIndex);
    const nextIndex = (currentIndex + 1) % options.length;
    geojsonLayerPicker.selectedIndex = nextIndex;
    showGeojsonLayerPanel(options[nextIndex].value);
});

showGeojsonLayerPanel('geojson_layer_ecole');

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const geojsonInputs = this.querySelectorAll('.geojson-data-input');
        const activeField = geojsonActiveLayerField ? geojsonActiveLayerField.value : '';

        if (geojsonInputs.length) {
            geojsonInputs.forEach(input => {
                input.disabled = !activeField || input.id !== activeField;
            });
        }

        if (!geojsonInputs.length) {
            return;
        }

        for (let i = 0; i < geojsonInputs.length; i++) {
            const input = geojsonInputs[i];

            if (input.disabled) {
                continue;
            }

            if (input.value.trim() === '') {
                continue;
            }

            try {
                JSON.parse(input.value);
            } catch (error) {
                e.preventDefault();
                showGeojsonLayerPanel(input.id);
                alert('Le format GeoJSON n\'est pas valide.');
                input.focus();
                break;
            }
        }

    });
});

document.querySelectorAll('input[type="file"][data-target]').forEach(fileInput => {
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
        const targetId = this.dataset.target;
        const editor = targetId ? document.getElementById(targetId) : null;

        if (!file || !editor) {
            return;
        }

        const reader = new FileReader();

        reader.onload = function() {
            const content = typeof reader.result === 'string' ? reader.result : '';

            if (!content.trim()) {
                alert('Le fichier GeoJSON est vide.');
                return;
            }

            editor.value = content;
        };

        reader.onerror = function() {
            alert('Impossible de lire le fichier GeoJSON.');
        };

        reader.readAsText(file);
    });
});

document.querySelectorAll('.geojson-clear-layer[data-clear-target]').forEach(button => {
    button.addEventListener('click', async function() {
        const targetId = this.dataset.clearTarget;
        const editor = targetId ? document.getElementById(targetId) : null;
        if (!editor) {
            return;
        }

        const confirmed = await window.swalConfirm('Supprimer les donnees de cette couche ?', {
            title: 'Suppression de donnees',
            confirmButtonText: 'Supprimer',
            icon: 'warning'
        });
        if (!confirmed) {
            return;
        }

        editor.value = '';
        showGeojsonLayerPanel(targetId);
        const fileInput = document.querySelector(`input[type="file"][data-target="${targetId}"]`);
        if (fileInput) {
            fileInput.value = '';
        }

        if (geojsonEditForm) {
            if (typeof geojsonEditForm.requestSubmit === 'function') {
                geojsonEditForm.requestSubmit();
            } else {
                geojsonEditForm.submit();
            }
        }
    });
});

// Prévisualisation des images avant upload
document.getElementById('photos')?.addEventListener('change', function(e) {
    const files = e.target.files;
    if (files.length > 0) {
        console.log(`${files.length} fichier(s) sélectionné(s)`);
    }
});
</script>
@endsection
