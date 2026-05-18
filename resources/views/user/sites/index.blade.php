@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Mes Sites</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sites que vous pouvez gérer et collecter des données</p>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        <!-- Barre de recherche -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('user.sites.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Rechercher un site..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Rechercher
                </button>
            </form>
        </div>

        <!-- Carte interactive -->
        @if($sites->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Carte des sites</h3>
            <div id="map" class="w-full h-96 rounded-lg"></div>
        </div>
        @endif

        <!-- Liste des sites en cartes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($sites as $site)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                 onclick="window.location.href='{{ route('user.sites.edit', $site) }}'">
                <!-- Photo du site -->
                <div class="h-48 bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
                    @if($site->photos && count($site->photos) > 0)
                        <img src="{{ asset('storage/' . $site->photos[0]) }}" 
                             alt="{{ $site->nom }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Badges -->
                    <div class="absolute top-2 right-2 flex flex-col gap-1">
                        @if($site->latitude && $site->longitude)
                        <span class="px-2 py-1 bg-emerald-500 text-white text-xs rounded-full flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            GPS
                        </span>
                        @endif
                        
                        @if($site->geojson_data)
                        <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            GeoJSON
                        </span>
                        @endif
                        
                        @php
                            $userAccess = auth()->user()->assignedSites()->where('sites.id', $site->id)->first();
                        @endphp
                        
                        @if($userAccess)
                        <span class="px-2 py-1 bg-purple-500 text-white text-xs rounded-full">
                            Assigné
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Informations du site -->
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex-1">
                            {{ $site->nom }}
                        </h3>
                        @if($site->code_site)
                        <span class="px-2 py-1 text-xs font-medium rounded bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300 ml-2">
                            {{ $site->code_site }}
                        </span>
                        @endif
                    </div>

                    <div class="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ $site->territoire }}, {{ $site->province }}
                        </div>

                        @if($site->typeSite)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $site->typeSite->name }}
                        </div>
                        @endif

                        @if($site->menages || $site->individus)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                            @if($site->individus)
                                {{ number_format($site->individus) }} personnes
                            @endif
                            @if($site->menages)
                                ({{ number_format($site->menages) }} ménages)
                            @endif
                        </div>
                        @endif

                        @if($site->organisation)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            {{ $site->organisation->name }}
                        </div>
                        @endif
                    </div>

                    <!-- Permissions -->
                    @if($userAccess)
                    <div class="mb-3 flex items-center gap-2 text-xs">
                        @if($userAccess->pivot->can_edit)
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded">
                            ✓ Modification
                        </span>
                        @endif
                        @if($userAccess->pivot->can_collect)
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded">
                            ✓ Collecte
                        </span>
                        @endif
                    </div>
                    @endif

                    <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $site->photos ? count($site->photos) : 0 }}
                            </span>
                        </div>

                        <a href="{{ route('user.sites.edit', $site) }}" 
                           onclick="event.stopPropagation()"
                           class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Profil
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun site</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Aucun site ne vous a été attribué pour le moment
                    </p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($sites->hasPages())
        <div class="mt-6">
            {{ $sites->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<style>
    /* Style pour les labels permanents des polygones GeoJSON */
    .geojson-label {
        background-color: rgba(59, 130, 246, 0.9) !important;
        border: 2px solid #1E40AF !important;
        border-radius: 6px !important;
        padding: 4px 10px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        white-space: nowrap !important;
        text-align: center !important;
    }
    
    /* Style pour le mode sombre */
    .dark .geojson-label {
        background-color: rgba(37, 99, 235, 0.95) !important;
        border-color: #1E3A8A !important;
    }
    
    /* Supprimer la flèche du tooltip par défaut */
    .geojson-label::before {
        display: none !important;
    }
</style>

<script>
    // Initialiser la carte uniquement si des sites existent
    @if($sites->count() > 0)
    document.addEventListener('DOMContentLoaded', function() {
        // Créer la carte centrée sur la RDC
        const map = L.map('map').setView([-4.0383, 21.7587], 5);

        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Créer un groupe de marqueurs pour ajuster le zoom
        const markers = [];

        // Données des sites avec GPS ou GeoJSON
        const sites = [
            @foreach($sites as $site)
                @if($site->latitude && $site->longitude || $site->geojson_data)
                {
                    id: {{ $site->id }},
                    nom: "{{ addslashes($site->nom) }}",
                    code: "{{ $site->code_site ?? '' }}",
                    lat: {{ $site->latitude ?? 'null' }},
                    lng: {{ $site->longitude ?? 'null' }},
                    territoire: "{{ addslashes($site->territoire) }}",
                    province: "{{ addslashes($site->province) }}",
                    type: "{{ $site->typeSite ? addslashes($site->typeSite->name) : '' }}",
                    menages: {{ $site->menages ?? 0 }},
                    individus: {{ $site->individus ?? 0 }},
                    organisation: "{{ $site->organisation ? addslashes($site->organisation->name) : '' }}",
                    url: "{{ route('user.sites.edit', $site) }}",
                    geojson: @json($site->geojson_data)
                },
                @endif
            @endforeach
        ];

        // Ajouter les marqueurs (seulement pour les sites avec GPS)
        sites.forEach(site => {
            if (site.lat !== null && site.lng !== null) {
                const marker = L.marker([site.lat, site.lng]).addTo(map);
            
                // Contenu du popup
                let popupContent = `
                    <div class="p-2">
                        <h4 class="font-bold text-lg mb-2 text-primary-600">${site.nom}</h4>
                        ${site.code ? `<p class="text-sm text-gray-600 mb-2"><strong>Code:</strong> ${site.code}</p>` : ''}
                        <p class="text-sm text-gray-600"><strong>Localisation:</strong> ${site.territoire}, ${site.province}</p>
                        ${site.type ? `<p class="text-sm text-gray-600"><strong>Type:</strong> ${site.type}</p>` : ''}
                        ${site.individus > 0 ? `<p class="text-sm text-gray-600"><strong>Population:</strong> ${site.individus.toLocaleString()} personnes</p>` : ''}
                        ${site.menages > 0 ? `<p class="text-sm text-gray-600"><strong>Ménages:</strong> ${site.menages.toLocaleString()}</p>` : ''}
                        ${site.organisation ? `<p class="text-sm text-gray-600"><strong>Organisation:</strong> ${site.organisation}</p>` : ''}
                        ${site.geojson ? `<p class="text-sm text-emerald-600 mt-2"><svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Zone GeoJSON affichée</p>` : ''}
                        <div class="mt-3">
                            <a href="${site.url}" class="inline-block px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded transition-colors">
                                Profil du site
                            </a>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent, { maxWidth: 300 });
                markers.push(marker);
            }
            
            // Ajouter les données GeoJSON si elles existent (pour tous les sites)
            if (site.geojson && site.geojson !== null && typeof site.geojson === 'object') {
                try {
                    // Compteur pour suivre l'index des features
                    let featureIndex = 0;
                    
                    // Créer une couche GeoJSON avec style personnalisé
                    const geojsonLayer = L.geoJSON(site.geojson, {
                        style: function(feature) {
                            return {
                                color: '#3B82F6',        // Bleu primaire
                                weight: 3,
                                opacity: 0.8,
                                fillColor: '#60A5FA',    // Bleu clair
                                fillOpacity: 0.3
                            };
                        },
                        onEachFeature: function(feature, layer) {
                            const currentIndex = featureIndex++;
                            
                            // Ajouter un popup aux features GeoJSON
                            let featurePopup = `
                                <div class="p-2">
                                    <h4 class="font-bold text-base mb-2 text-primary-600">${site.nom}</h4>
                                    <p class="text-sm text-gray-600">Zone GeoJSON du site</p>
                                    ${feature.properties && feature.properties.name ? `<p class="text-sm text-gray-600"><strong>Nom:</strong> ${feature.properties.name}</p>` : ''}
                                    ${feature.properties && feature.properties.description ? `<p class="text-sm text-gray-600 mt-1">${feature.properties.description}</p>` : ''}
                                    <div class="mt-3">
                                        <a href="${site.url}" class="inline-block px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded transition-colors">
                                            Profil du site
                                        </a>
                                    </div>
                                </div>
                            `;
                            layer.bindPopup(featurePopup, { maxWidth: 300 });
                            
                            // Ajouter un label permanent SAUF pour le premier feature (index 0)
                            if (currentIndex > 0) {
                                // On cherche : properties.NOM (majuscule), properties.nom, properties.name, puis site.nom
                                let labelText = site.nom; // Valeur par défaut
                                if (feature.properties) {
                                    if (feature.properties.NOM) {
                                        labelText = feature.properties.NOM;
                                    } else if (feature.properties.nom) {
                                        labelText = feature.properties.nom;
                                    } else if (feature.properties.name) {
                                        labelText = feature.properties.name;
                                    }
                                }
                                
                                layer.bindTooltip(labelText, {
                                    permanent: true,
                                    direction: 'center',
                                    className: 'geojson-label',
                                    opacity: 0.9
                                });
                            }
                        },
                        pointToLayer: function(feature, latlng) {
                            // Personnaliser les points GeoJSON (si le GeoJSON contient des points)
                            return L.circleMarker(latlng, {
                                radius: 8,
                                fillColor: '#3B82F6',
                                color: '#1E40AF',
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.6
                            });
                        }
                    }).addTo(map);
                    
                    // Ajouter les limites de la couche GeoJSON au groupe pour le zoom
                    const bounds = geojsonLayer.getBounds();
                    if (bounds.isValid()) {
                        markers.push(geojsonLayer);
                    }
                    
                    console.log(`✓ GeoJSON chargé pour le site: ${site.nom}`);
                } catch (error) {
                    console.error(`Erreur lors du chargement du GeoJSON pour ${site.nom}:`, error);
                }
            }
        });

        // Ajuster le zoom pour afficher tous les marqueurs
        if (markers.length > 0) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    });
    @endif
</script>
@endpush

@endsection
