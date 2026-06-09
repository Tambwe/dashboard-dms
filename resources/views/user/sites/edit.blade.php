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
            <a href="{{ route('user.sites.index') }}" 
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

        <!-- Alerte de permissions -->
        @if(!$canEditMedia)
        <div class="mb-3 rounded bg-yellow-50 dark:bg-yellow-900/20 p-3 border border-yellow-200 dark:border-yellow-800">
            <p class="text-sm text-yellow-800 dark:text-yellow-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Vous n'avez que les droits de consultation sur ce site. Pour le modifier, contactez votre administrateur.
            </p>
        </div>
        @elseif(!$canEdit)
        <div class="mb-3 rounded bg-blue-50 dark:bg-blue-900/20 p-3 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 10-2 0 1 1 0 002 0zm-2 3a1 1 0 000 2v3a1 1 0 102 0v-3a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Profil SIG : vous pouvez ajouter/modifier les couches GeoJSON et les photos, mais pas les coordonnees GPS.
            </p>
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

        @php
            $geojsonAvailable = !empty($site->geojson_data);
            $defaultGeojsonData = old('geojson_data');
            $defaultCustomLayerName = old('geojson_layer_name', '');
            $defaultCustomLayerKey = old('geojson_active_layer_key', '');

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

            if ($defaultGeojsonData === null && $geojsonAvailable) {
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
                        } elseif ($defaultGeojsonData === null) {
                            $defaultGeojsonData = json_encode($layerGeojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            $defaultCustomLayerName = (string) ($layerItem['name'] ?? '');
                            $defaultCustomLayerKey = $layerKey;
                        }
                    }
                } else {
                    $defaultGeojsonData = json_encode($site->geojson_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
        @endphp

        @if($canEdit)
        <!-- Coordonnées GPS -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                Coordonnées GPS
            </h3>

            <form method="POST" action="{{ route('user.sites.update', $site) }}">
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

        @endif

        @if($canEditMedia)
        <!-- Données GeoJSON -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Couches GeoJSON
                </h3>

                <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                    {{ count($geojsonLayersMeta ?? []) > 0 ? count($geojsonLayersMeta) . ' couche(s) GeoJSON' : 'Aucune couche GeoJSON' }}
                </span>
            </div>

            @if(count($geojsonLayersMeta ?? []) > 0)
            <div class="grid grid-cols-1 gap-3 mb-4">
                @foreach(($geojsonLayersMeta ?? []) as $layerMeta)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3 transition-colors hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="flex flex-col text-left">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $layerMeta['name'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Actions directes sur cette couche</span>
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    class="geojson-layer-action rounded-lg border border-primary-200 px-3 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 dark:border-primary-700 dark:text-primary-300 dark:hover:bg-primary-900/40"
                                    data-action="view"
                                    data-layer-label="{{ $layerMeta['name'] }}"
                                    data-layer-index="{{ $layerMeta['index'] }}"
                                    data-layer-key="{{ $layerMeta['key'] ?? '' }}"
                                    data-editor-target="{{ ($layerMeta['name'] ?? '') === 'Ecole' ? 'geojsonLayerEcoleInput' : (($layerMeta['name'] ?? '') === 'Robinet' ? 'geojsonLayerRobinetInput' : (($layerMeta['name'] ?? '') === 'Lavage main' ? 'geojsonLayerLavageMainInput' : (($layerMeta['name'] ?? '') === 'Bloc sites' ? 'geojsonLayerBlocSitesInput' : (($layerMeta['name'] ?? '') === 'Centre de sante' ? 'geojsonLayerCentreSanteInput' : 'geojsonDataInput')))) }}">
                                Voir
                            </button>
                            <button type="button"
                                    class="geojson-layer-action rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/40"
                                    data-action="edit"
                                    data-layer-label="{{ $layerMeta['name'] }}"
                                    data-layer-index="{{ $layerMeta['index'] }}"
                                    data-layer-key="{{ $layerMeta['key'] ?? '' }}"
                                    data-editor-target="{{ ($layerMeta['name'] ?? '') === 'Ecole' ? 'geojsonLayerEcoleInput' : (($layerMeta['name'] ?? '') === 'Robinet' ? 'geojsonLayerRobinetInput' : (($layerMeta['name'] ?? '') === 'Lavage main' ? 'geojsonLayerLavageMainInput' : (($layerMeta['name'] ?? '') === 'Bloc sites' ? 'geojsonLayerBlocSitesInput' : (($layerMeta['name'] ?? '') === 'Centre de sante' ? 'geojsonLayerCentreSanteInput' : 'geojsonDataInput')))) }}">
                                Modifier
                            </button>
                            <button type="button"
                                    class="geojson-layer-action rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/40"
                                    data-action="delete"
                                    data-layer-label="{{ $layerMeta['name'] }}"
                                    data-layer-index="{{ $layerMeta['index'] }}"
                                    data-layer-key="{{ $layerMeta['key'] ?? '' }}"
                                    data-editor-target="{{ ($layerMeta['name'] ?? '') === 'Ecole' ? 'geojsonLayerEcoleInput' : (($layerMeta['name'] ?? '') === 'Robinet' ? 'geojsonLayerRobinetInput' : (($layerMeta['name'] ?? '') === 'Lavage main' ? 'geojsonLayerLavageMainInput' : (($layerMeta['name'] ?? '') === 'Bloc sites' ? 'geojsonLayerBlocSitesInput' : (($layerMeta['name'] ?? '') === 'Centre de sante' ? 'geojsonLayerCentreSanteInput' : 'geojsonDataInput')))) }}">
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
                <button type="button"
                        id="geojsonCreateLayer"
                        class="inline-flex items-center justify-between rounded-lg border border-dashed border-primary-300 dark:border-primary-700 px-4 py-3 text-left hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                    <span class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-white">Ajouter une nouvelle couche</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Creer une couche GeoJSON personnalisee supplementaire</span>
                    </span>
                    <span class="text-primary-600 text-sm font-semibold">Ajouter</span>
                </button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Les données GeoJSON ne sont chargées qu’à la demande depuis le serveur. Les couches volumineuses sont découpées en blocs à l’affichage.
            </p>
            @else
            <div class="grid grid-cols-1 gap-3 mb-4">
                <button type="button"
                        id="geojsonCreateLayer"
                        class="inline-flex items-center justify-between rounded-lg border border-dashed border-gray-300 dark:border-gray-600 px-4 py-3 text-left hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                    <span class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-white">Ajouter des couches GeoJSON</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Ouvrir l’éditeur pour coller/importer vos couches</span>
                    </span>
                    <span class="text-primary-600 text-sm font-semibold">Ouvrir</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Modal GeoJSON -->
        <div id="geojsonModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4 py-6">
            <div class="w-full max-w-6xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-4">
                    <div>
                        <h4 id="geojsonModalTitle" class="text-lg font-semibold text-gray-900 dark:text-white">Couche GeoJSON</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Carte et contenu chargés à la demande</p>
                    </div>
                    <button type="button" id="geojsonModalClose" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                    <div class="border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-700 p-4">
                        <div id="geojsonModalMap" class="h-80 lg:h-[540px] rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700"></div>
                    </div>

                    <div class="p-4 space-y-3">
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Vue carte</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400" id="geojsonModalMeta">Chargement en cours...</p>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Vous pouvez enregistrer plusieurs couches GeoJSON thématiques pour ce site.
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="geojsonOpenEditor" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                                Modifier la couche
                            </button>
                            <button type="button" id="geojsonCloseEditor" class="hidden rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                                Fermer l'éditeur
                            </button>
                        </div>

                        <div id="geojsonEditorPanel" class="hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 space-y-3">
                            <form id="geojsonEditorForm" method="POST" action="{{ route('user.sites.update', $site) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="geojson_active_layer" id="geojsonActiveLayerField" value="geojson_layer_ecole">
                                <input type="hidden" name="geojson_active_layer_key" id="geojsonActiveLayerKeyField" value="{{ $defaultCustomLayerKey }}">

                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <label for="geojsonEditorLayerPicker" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Edition pas-a-pas: afficher une couche a la fois
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <select id="geojsonEditorLayerPicker" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500">
                                                <option value="geojsonLayerEcoleInput">Ecole</option>
                                                <option value="geojsonLayerRobinetInput">Robinet</option>
                                                <option value="geojsonLayerLavageMainInput">Lavage main</option>
                                                <option value="geojsonLayerBlocSitesInput">Bloc sites</option>
                                                <option value="geojsonLayerCentreSanteInput">Centre de sante</option>
                                                <option value="geojsonDataInput">Autre couche</option>
                                            </select>
                                            <button type="button" id="geojsonEditorNextLayer" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                                                Couche suivante
                                            </button>
                                        </div>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Astuce: chargez et enregistrez chaque couche separement pour eviter de surcharger la page.
                                    </p>
                                </div>

                                <div class="geojson-editor-panel rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonLayerEcoleInput">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonLayerEcoleInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Ecole
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonLayerEcoleInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFileEcole"
                                                   data-target="geojsonLayerEcoleInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_layer_ecole"
                                              id="geojsonLayerEcoleInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultLayerFields['geojson_layer_ecole'] }}</textarea>
                                </div>

                                <div class="geojson-editor-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonLayerRobinetInput">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonLayerRobinetInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Robinet
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonLayerRobinetInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFileRobinet"
                                                   data-target="geojsonLayerRobinetInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_layer_robinet"
                                              id="geojsonLayerRobinetInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultLayerFields['geojson_layer_robinet'] }}</textarea>
                                </div>

                                <div class="geojson-editor-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonLayerLavageMainInput">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonLayerLavageMainInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Lavage main
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonLayerLavageMainInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFileLavageMain"
                                                   data-target="geojsonLayerLavageMainInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_layer_lavage_main"
                                              id="geojsonLayerLavageMainInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultLayerFields['geojson_layer_lavage_main'] }}</textarea>
                                </div>

                                <div class="geojson-editor-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonLayerBlocSitesInput">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonLayerBlocSitesInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Bloc sites
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonLayerBlocSitesInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFileBlocSites"
                                                   data-target="geojsonLayerBlocSitesInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_layer_bloc_sites"
                                              id="geojsonLayerBlocSitesInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultLayerFields['geojson_layer_bloc_sites'] }}</textarea>
                                </div>

                                <div class="geojson-editor-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonLayerCentreSanteInput">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonLayerCentreSanteInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Centre de sante
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonLayerCentreSanteInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFileCentreSante"
                                                   data-target="geojsonLayerCentreSanteInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_layer_centre_sante"
                                              id="geojsonLayerCentreSanteInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultLayerFields['geojson_layer_centre_sante'] }}</textarea>
                                </div>

                                <div class="geojson-editor-panel hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/50 p-3 space-y-2" data-layer-target="geojsonDataInput">
                                    <div>
                                        <label for="geojsonLayerNameInput" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Nom de la couche
                                        </label>
                                        <input type="text"
                                               name="geojson_layer_name"
                                               id="geojsonLayerNameInput"
                                               value="{{ $defaultCustomLayerName }}"
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                                               placeholder="Ex: VNM, Extension, Nouvelle zone">
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="geojsonDataInput" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Autre couche (optionnel)
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="geojson-clear-layer rounded-lg border border-red-200 dark:border-red-700 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                    data-clear-target="geojsonDataInput">
                                                Supprimer cette couche
                                            </button>
                                            <input type="file"
                                                   id="geojsonDataFile"
                                                   data-target="geojsonDataInput"
                                                   accept=".geojson,.json,application/geo+json,application/json"
                                                   class="block w-48 text-xs text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none">
                                        </div>
                                    </div>
                                    <textarea name="geojson_data"
                                              id="geojsonDataInput"
                                              rows="6"
                                              class="geojson-data-input w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                                              placeholder='{"type":"Feature","geometry":{...}}'>{{ $defaultGeojsonData }}</textarea>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                        Enregistrer le GeoJSON
                                    </button>
                                    <span id="geojsonEditorStatus" class="text-xs text-gray-500 dark:text-gray-400"></span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-5 py-4">
                    <button type="button" id="geojsonModalCloseFooter" class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Fermer
                    </button>
                </div>
            </div>
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
                          action="{{ route('user.sites.delete-photo', $site) }}"
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
                  action="{{ route('user.sites.update', $site) }}" 
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
        @else
        <!-- Vue lecture seule pour utilisateurs sans permission d'édition -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Localisation</h3>
            
            @if($site->latitude && $site->longitude)
            <div class="text-sm text-gray-700 dark:text-gray-300">
                <p><strong>Coordonnées GPS:</strong> {{ $site->latitude }}, {{ $site->longitude }}</p>
            </div>
            @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Les coordonnées GPS ne sont pas encore disponibles.</p>
            @endif
        </div>

        @if($site->photos && count($site->photos) > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Photos du Site</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($site->photos as $photo)
                <img src="{{ asset('storage/' . $photo) }}" 
                     alt="Photo du site"
                     class="w-full h-48 object-cover rounded-lg">
                @endforeach
            </div>
        </div>
        @endif
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('geojsonModal');
    const modalTitle = document.getElementById('geojsonModalTitle');
    const modalMeta = document.getElementById('geojsonModalMeta');
    const modalClose = document.getElementById('geojsonModalClose');
    const modalCloseFooter = document.getElementById('geojsonModalCloseFooter');
    const modalMapElement = document.getElementById('geojsonModalMap');
    const layerButtons = Array.from(document.querySelectorAll('.geojson-layer-action[data-action]'));
    const openEditorButton = document.getElementById('geojsonOpenEditor');
    const closeEditorButton = document.getElementById('geojsonCloseEditor');
    const editorPanel = document.getElementById('geojsonEditorPanel');
    const editorLayerPicker = document.getElementById('geojsonEditorLayerPicker');
    const editorNextLayerButton = document.getElementById('geojsonEditorNextLayer');
    const geojsonEditorForm = document.getElementById('geojsonEditorForm');
    const geojsonActiveLayerField = document.getElementById('geojsonActiveLayerField');
    const geojsonActiveLayerKeyField = document.getElementById('geojsonActiveLayerKeyField');
    const geojsonLayerNameInput = document.getElementById('geojsonLayerNameInput');
    const geojsonCreateLayer = document.getElementById('geojsonCreateLayer');
    const editorLayerPanels = Array.from(document.querySelectorAll('.geojson-editor-panel'));
    const geojsonInputs = Array.from(document.querySelectorAll('.geojson-data-input'));
    const geojsonDataInput = document.getElementById('geojsonDataInput');
    const editorStatus = document.getElementById('geojsonEditorStatus');

    if (!modal || !modalTitle || !modalMeta || !modalMapElement) {
        return;
    }

    let modalMap = null;
    let modalLayerGroup = null;
    let currentLayerLabel = 'Couche GeoJSON';
    let currentLayerIndex = 0;
    let currentEditorTarget = 'geojsonDataInput';
    let currentCustomLayerKey = geojsonActiveLayerKeyField ? (geojsonActiveLayerKeyField.value || '') : '';
    const editorTargetToField = {
        geojsonLayerEcoleInput: 'geojson_layer_ecole',
        geojsonLayerRobinetInput: 'geojson_layer_robinet',
        geojsonLayerLavageMainInput: 'geojson_layer_lavage_main',
        geojsonLayerBlocSitesInput: 'geojson_layer_bloc_sites',
        geojsonLayerCentreSanteInput: 'geojson_layer_centre_sante',
        geojsonDataInput: 'geojson_data'
    };

    // Reduce editor rendering overhead for very large GeoJSON payloads.
    geojsonInputs.forEach(function(input) {
        input.setAttribute('spellcheck', 'false');
        input.setAttribute('autocapitalize', 'off');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('autocorrect', 'off');
        input.setAttribute('wrap', 'off');
    });

    function showEditorLayer(targetId) {
        editorLayerPanels.forEach(function(panel) {
            const panelTarget = panel.dataset.layerTarget || '';
            if (panelTarget === targetId) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });

        if (editorLayerPicker) {
            editorLayerPicker.value = targetId;
        }

        if (geojsonActiveLayerField) {
            geojsonActiveLayerField.value = editorTargetToField[targetId] || 'geojson_data';
        }

        if (geojsonActiveLayerKeyField) {
            geojsonActiveLayerKeyField.value = targetId === 'geojsonDataInput' ? currentCustomLayerKey : '';
        }
    }

    function startNewCustomLayer() {
        currentLayerLabel = 'Nouvelle couche';
        currentLayerIndex = 0;
        currentEditorTarget = 'geojsonDataInput';
        currentCustomLayerKey = '';

        if (geojsonLayerNameInput) {
            geojsonLayerNameInput.value = '';
        }

        if (geojsonDataInput) {
            geojsonDataInput.value = '';
        }

        openModal();
        showEditor();
        modalTitle.textContent = 'Nouvelle couche';
        modalMeta.textContent = 'Saisissez un nom et collez/importez le GeoJSON.';
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            if (modalMap) {
                modalMap.invalidateSize();
            }
        }, 80);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        hideEditor();
    }

    function hideEditor() {
        if (editorPanel) {
            editorPanel.classList.add('hidden');
        }
        if (closeEditorButton) {
            closeEditorButton.classList.add('hidden');
        }
        if (openEditorButton) {
            openEditorButton.classList.remove('hidden');
        }
        if (editorStatus) {
            editorStatus.textContent = '';
        }
    }

    function showEditor() {
        if (editorPanel) {
            editorPanel.classList.remove('hidden');
        }
        if (closeEditorButton) {
            closeEditorButton.classList.remove('hidden');
        }
        if (openEditorButton) {
            openEditorButton.classList.add('hidden');
        }

        showEditorLayer(currentEditorTarget || 'geojsonDataInput');
    }

    function syncCurrentLayer(button) {
        currentLayerLabel = button.dataset.layerLabel || 'Couche GeoJSON';
        currentLayerIndex = parseInt(button.dataset.layerIndex || '0', 10);
        currentEditorTarget = button.dataset.editorTarget || 'geojsonDataInput';
        currentCustomLayerKey = button.dataset.layerKey || '';

        if (currentEditorTarget === 'geojsonDataInput' && geojsonLayerNameInput) {
            geojsonLayerNameInput.value = currentLayerLabel || '';
        }

        showEditorLayer(currentEditorTarget);
    }

    function submitGeojsonDeletion(targetId) {
        const editor = targetId ? document.getElementById(targetId) : null;
        if (!editor) {
            return;
        }

        editor.value = '';
        currentEditorTarget = targetId;
        showEditorLayer(targetId);

        const fileInput = document.querySelector(`input[type="file"][data-target="${targetId}"]`);
        if (fileInput) {
            fileInput.value = '';
        }

        if (editorStatus) {
            editorStatus.textContent = 'Suppression de la couche en cours...';
        }

        if (geojsonEditorForm) {
            if (typeof geojsonEditorForm.requestSubmit === 'function') {
                geojsonEditorForm.requestSubmit();
            } else {
                geojsonEditorForm.submit();
            }
        }
    }

    function ensureModalMap() {
        if (modalMap) {
            return modalMap;
        }

        modalMap = L.map('geojsonModalMap', {
            preferCanvas: true,
            zoomControl: true
        }).setView([-4.0383, 21.7587], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(modalMap);

        return modalMap;
    }

    function renderLayerOnModal(payload) {
        const map = ensureModalMap();
        const chunks = Array.isArray(payload?.geojson_chunks) && payload.geojson_chunks.length > 0
            ? payload.geojson_chunks
            : [payload?.geojson_data].filter(Boolean);

        if (modalLayerGroup) {
            modalLayerGroup.remove();
            modalLayerGroup = null;
        }

        if (!chunks.length) {
            modalMeta.textContent = 'Aucune donnée disponible pour cette couche.';
            map.setView([-4.0383, 21.7587], 5);
            return;
        }

        modalLayerGroup = L.featureGroup();

        chunks.forEach(function(chunk) {
            if (!chunk || typeof chunk !== 'object') {
                return;
            }

            const layer = L.geoJSON(chunk, {
                style: function() {
                    return {
                        color: '#2563eb',
                        weight: 3,
                        opacity: 0.9,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.25
                    };
                },
                pointToLayer: function(feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 7,
                        fillColor: '#2563eb',
                        color: '#1d4ed8',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.7
                    });
                }
            });

            modalLayerGroup.addLayer(layer);
        });

        modalLayerGroup.addTo(map);

        const bounds = modalLayerGroup.getBounds();
        if (bounds && bounds.isValid()) {
            map.fitBounds(bounds.pad(0.12));
        }

        if (chunks.length > 1) {
            modalMeta.textContent = `${modalMeta.textContent} • affiché en ${chunks.length} blocs`;
        }

        modalMap.invalidateSize();
    }

    async function loadLayer() {
        modalTitle.textContent = currentLayerLabel;
        modalMeta.textContent = 'Chargement du contenu...';

        openModal();
        hideEditor();

        try {
            const response = await fetch(`{{ route('user.sites.geojson', $site) }}?preview=1&layer=${encodeURIComponent(currentLayerIndex)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            modalMeta.textContent = `${currentLayerLabel}`;

            renderLayerOnModal(payload);
        } catch (error) {
            modalMeta.textContent = 'Erreur de chargement.';
        }
    }

    async function loadLayerForEdit() {
        showEditor();
        if (editorStatus) {
            editorStatus.textContent = 'Chargement de la couche complète...';
        }

        try {
            const response = await fetch(`{{ route('user.sites.geojson', $site) }}?raw=1&layer=${encodeURIComponent(currentLayerIndex)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            const rawData = payload.geojson_data || null;
            const editorTarget = document.getElementById(currentEditorTarget) || geojsonDataInput;

            if (editorTarget) {
                let serialized = '';
                if (rawData) {
                    const compact = JSON.stringify(rawData);
                    serialized = compact && compact.length > 450000
                        ? compact
                        : JSON.stringify(rawData, null, 2);
                }

                editorTarget.value = serialized || '';
                const fileInput = document.querySelector(`input[type="file"][data-target="${editorTarget.id}"]`);
                if (fileInput) {
                    fileInput.value = '';
                }
                editorTarget.focus();
            }

            if (editorStatus) {
                editorStatus.textContent = 'Edition du GeoJSON (mode optimise pour gros fichiers).';
            }
        } catch (error) {
            if (editorStatus) {
                editorStatus.textContent = `Impossible de charger le GeoJSON complet: ${error.message}`;
            }
        }
    }

    layerButtons.forEach(function(layerButton) {
        layerButton.addEventListener('click', async function() {
            const action = this.dataset.action || 'view';
            syncCurrentLayer(this);

            if (action === 'edit') {
                openModal();
                showEditor();
                loadLayerForEdit();
                return;
            }

            if (action === 'delete') {
                const confirmed = await window.swalConfirm(`Supprimer completement la couche "${currentLayerLabel}" ?`, {
                    title: 'Suppression de couche',
                    confirmButtonText: 'Supprimer',
                    icon: 'warning'
                });
                if (confirmed) {
                    submitGeojsonDeletion(currentEditorTarget);
                }
                return;
            }

            loadLayer();
        });
    });

    geojsonCreateLayer?.addEventListener('click', function() {
        startNewCustomLayer();
    });

    editorLayerPicker?.addEventListener('change', function() {
        currentEditorTarget = this.value || 'geojsonDataInput';
        showEditorLayer(currentEditorTarget);
    });

    editorNextLayerButton?.addEventListener('click', function() {
        if (!editorLayerPicker) {
            return;
        }

        const options = Array.from(editorLayerPicker.options);
        const currentIndex = Math.max(0, editorLayerPicker.selectedIndex);
        const nextIndex = (currentIndex + 1) % options.length;
        editorLayerPicker.selectedIndex = nextIndex;
        currentEditorTarget = options[nextIndex].value;
        showEditorLayer(currentEditorTarget);
    });

    openEditorButton?.addEventListener('click', function() {
        loadLayerForEdit();
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
                    if (editorStatus) {
                        editorStatus.textContent = 'Le fichier GeoJSON est vide.';
                    }
                    return;
                }

                editor.value = content;

                if (editorStatus) {
                    editorStatus.textContent = `Fichier chargé: ${file.name}`;
                }
            };

            reader.onerror = function() {
                if (editorStatus) {
                    editorStatus.textContent = 'Impossible de lire le fichier GeoJSON.';
                }
            };

            reader.readAsText(file);
        });
    });

    geojsonEditorForm?.addEventListener('submit', function() {
        const activeEditor = document.getElementById(currentEditorTarget);

        this.querySelectorAll('.geojson-data-input').forEach(function(input) {
            input.disabled = !activeEditor || input.id !== activeEditor.id;
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

            submitGeojsonDeletion(targetId);
        });
    });

    closeEditorButton?.addEventListener('click', function() {
        hideEditor();
    });

    [modalClose, modalCloseFooter].forEach(button => {
        button?.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    showEditorLayer(currentEditorTarget);

});
</script>
@endsection
