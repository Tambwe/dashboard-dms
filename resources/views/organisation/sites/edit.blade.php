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
                        {{ $site->date_mise_a_jour ? $site->date_mise_a_jour->format('d/m/Y') : '-' }}
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

            <form method="POST" action="{{ route('organisation.sites.update', $site) }}">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="geojson_data" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Données GeoJSON (format JSON valide)
                    </label>
                    <textarea name="geojson_data" 
                              id="geojson_data" 
                              rows="8"
                              placeholder='{"type": "Point", "coordinates": [15.3350623, -4.3250623]}'
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono text-sm">{{ old('geojson_data', $site->geojson_data ? json_encode($site->geojson_data, JSON_PRETTY_PRINT) : '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Format GeoJSON standard. 
                        <a href="https://geojson.org/" target="_blank" class="text-primary-600 hover:text-primary-700">Voir la documentation</a>
                    </p>
                </div>

                <button type="submit" 
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Enregistrer les données GeoJSON
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
// Validation du GeoJSON avant soumission
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const geojsonInput = this.querySelector('#geojson_data');
        
        if (geojsonInput && geojsonInput.value.trim() !== '') {
            try {
                JSON.parse(geojsonInput.value);
            } catch (error) {
                e.preventDefault();
                alert('Le format GeoJSON n\'est pas valide. Veuillez vérifier votre saisie.');
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
