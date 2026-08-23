@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Gérer les sites de {{ $user->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $user->email }} 
                    @if($user->organisation)
                        • {{ $user->organisation->name }}
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.user-site-access.index') }}" 
               class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white flex items-center">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Retour
            </a>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-3 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sites assignés -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center justify-between">
                    <span>Sites assignés ({{ $user->assignedSites->count() }})</span>
                </h3>

                @if($user->assignedSites->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p>Aucun site assigné</p>
                </div>
                @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($user->assignedSites as $site)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $site->nom }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $site->territoire }}, {{ $site->province }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.user-site-access.revoke', [$user, $site]) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        onclick="return confirm('Retirer l\'accès à ce site ?')"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        
                        <div class="flex items-center space-x-4 text-xs">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       {{ $site->pivot->can_edit ? 'checked' : '' }}
                                       onchange="updatePermission({{ $user->id }}, {{ $site->id }}, 'can_edit', this.checked)"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-1">
                                <span class="text-gray-700 dark:text-gray-300">Modification</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       {{ $site->pivot->can_collect ? 'checked' : '' }}
                                       onchange="updatePermission({{ $user->id }}, {{ $site->id }}, 'can_collect', this.checked)"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-1">
                                <span class="text-gray-700 dark:text-gray-300">Collecte</span>
                            </label>
                        </div>
                        
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                            Accordé le {{ $site->pivot->granted_at ? \Carbon\Carbon::parse($site->pivot->granted_at)->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Attribuer de nouveaux sites -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center justify-between">
                    <span>Attribuer des sites</span>
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        <span id="filteredCount">{{ $availableSites->count() - $user->assignedSites->count() }}</span> disponible(s)
                    </span>
                </h3>

                <!-- Barre de recherche et filtres -->
                @if($availableSites->count() > $user->assignedSites->count())
                <div class="mb-4 space-y-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg">
                    <div>
                        <input type="text" 
                               id="siteSearch"
                               placeholder="Rechercher un site par nom..."
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <select id="provinceFilter" 
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Toutes provinces</option>
                            @foreach($availableSites->pluck('province')->unique()->sort()->values() as $province)
                            <option value="{{ $province }}">{{ $province }}</option>
                            @endforeach
                        </select>
                        <select id="territoireFilter" 
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Tous territoires</option>
                            @foreach($availableSites->pluck('territoire')->unique()->sort()->values() as $territoire)
                            <option value="{{ $territoire }}">{{ $territoire }}</option>
                            @endforeach
                        </select>
                        <select id="communeFilter" 
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <option value="">Toutes communes</option>
                            @foreach($availableSites->pluck('commune.nom')->filter()->unique()->sort()->values() as $commune)
                            <option value="{{ $commune }}">{{ $commune }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" 
                            onclick="resetFilters()"
                            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                        Réinitialiser les filtres
                    </button>
                </div>
                @endif

                <!-- Formulaire d'attribution unique -->
                <form method="POST" action="{{ route('admin.user-site-access.grant', $user) }}" class="mb-6">
                    @csrf
                    
                    @if($availableSites->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="font-medium">Aucun site disponible</p>
                        <p class="text-sm">Il n'y a aucun site dans la base de données.</p>
                    </div>
                    @elseif($availableSites->count() === $user->assignedSites->count())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="font-medium">Tous les sites sont déjà assignés</p>
                        <p class="text-sm">Cet utilisateur a accès à tous les sites disponibles.</p>
                    </div>
                    @else
                    <div class="mb-4">
                        <label for="site_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sélectionner un site
                        </label>
                        <select name="site_id" 
                                id="site_id"
                                required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Choisir un site...</option>
                            @foreach($availableSites as $site)
                                @if(!$user->assignedSites->contains($site->id))
                                <option value="{{ $site->id }}"
                                        data-nom="{{ strtolower($site->nom) }}"
                                        data-province="{{ $site->province }}"
                                        data-territoire="{{ $site->territoire }}"
                                        data-commune="{{ $site->commune ? $site->commune->nom : '' }}">
                                    {{ $site->nom }}{{ $site->date_fermeture ? ' [Site fermé]' : '' }} - {{ $site->territoire }}, {{ $site->province }}
                                    @if($site->organisation)
                                        ({{ $site->organisation->name }})
                                    @endif
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4 space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="can_edit" 
                                   value="1"
                                   checked
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Autoriser la modification</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="can_collect" 
                                   value="1"
                                   checked
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Autoriser la collecte de données</span>
                        </label>
                    </div>

                    <button type="submit" 
                            class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Attribuer le site
                    </button>
                    @endif
                </form>

                <!-- Attribution en masse -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Attribution en masse</h4>
                    
                    <form method="POST" action="{{ route('admin.user-site-access.bulk-grant', $user) }}" id="bulkForm">
                        @csrf
                        
                        <div class="mb-3 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-2">
                            @php
                                $unassignedSites = $availableSites->filter(fn($s) => !$user->assignedSites->contains($s->id));
                            @endphp
                            
                            @if($unassignedSites->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                                Tous les sites disponibles sont déjà assignés
                            </p>
                            @else
                            @foreach($unassignedSites as $site)
                            <label class="flex items-center py-1 hover:bg-gray-50 dark:hover:bg-gray-700 px-2 rounded bulk-site-item"
                                   data-nom="{{ strtolower($site->nom) }}"
                                   data-province="{{ $site->province }}"
                                   data-territoire="{{ $site->territoire }}"
                                   data-commune="{{ $site->commune ? $site->commune->nom : '' }}">
                                <input type="checkbox" 
                                       name="site_ids[]" 
                                       value="{{ $site->id }}"
                                       class="bulk-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $site->nom }} 
                                    <span class="text-xs text-gray-500">
                                        - {{ $site->territoire }}
                                        @if($site->organisation)
                                            • {{ $site->organisation->name }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                            @endforeach
                            @endif
                        </div>

                        <div class="mb-3 space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="can_edit" 
                                       value="1"
                                       checked
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Autoriser la modification</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="can_collect" 
                                       value="1"
                                       checked
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Autoriser la collecte</span>
                            </label>
                        </div>

                        <button type="submit" 
                                id="bulkButton"
                                disabled
                                class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors">
                            Attribuer (<span id="selectedCount">0</span>) sites
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion des permissions en temps réel
function updatePermission(userId, siteId, permission, value) {
    fetch(`/admin/user-site-access/${userId}/sites/${siteId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            [permission]: value
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Permission mise à jour');
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de la permission');
    });
}

// Gestion de la sélection en masse
const bulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
const bulkButton = document.getElementById('bulkButton');
const selectedCount = document.getElementById('selectedCount');

function updateBulkButton() {
    const checked = document.querySelectorAll('.bulk-checkbox:checked:not([style*="display: none"])');
    selectedCount.textContent = checked.length;
    bulkButton.disabled = checked.length === 0;
}

bulkCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButton);
});

updateBulkButton();

// Système de filtrage des sites
function filterSites() {
    const searchText = document.getElementById('siteSearch')?.value.toLowerCase() || '';
    const provinceFilter = document.getElementById('provinceFilter')?.value || '';
    const territoireFilter = document.getElementById('territoireFilter')?.value || '';
    const communeFilter = document.getElementById('communeFilter')?.value || '';

    // Filtrer le select d'attribution unique
    const siteSelect = document.getElementById('site_id');
    if (siteSelect) {
        let visibleCount = 0;
        Array.from(siteSelect.options).forEach(option => {
            if (option.value === '') {
                option.style.display = '';
                return;
            }

            const nom = option.dataset.nom || '';
            const province = option.dataset.province || '';
            const territoire = option.dataset.territoire || '';
            const commune = option.dataset.commune || '';

            const matchSearch = searchText === '' || nom.includes(searchText);
            const matchProvince = provinceFilter === '' || province === provinceFilter;
            const matchTerritoire = territoireFilter === '' || territoire === territoireFilter;
            const matchCommune = communeFilter === '' || commune === communeFilter;

            if (matchSearch && matchProvince && matchTerritoire && matchCommune) {
                option.style.display = '';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });
    }

    // Filtrer les checkboxes d'attribution en masse
    const bulkSiteItems = document.querySelectorAll('.bulk-site-item');
    let visibleBulkCount = 0;
    bulkSiteItems.forEach(item => {
        const nom = item.dataset.nom || '';
        const province = item.dataset.province || '';
        const territoire = item.dataset.territoire || '';
        const commune = item.dataset.commune || '';

        const matchSearch = searchText === '' || nom.includes(searchText);
        const matchProvince = provinceFilter === '' || province === provinceFilter;
        const matchTerritoire = territoireFilter === '' || territoire === territoireFilter;
        const matchCommune = communeFilter === '' || commune === communeFilter;

        if (matchSearch && matchProvince && matchTerritoire && matchCommune) {
            item.style.display = '';
            visibleBulkCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Mettre à jour le compteur
    const filteredCount = document.getElementById('filteredCount');
    if (filteredCount) {
        filteredCount.textContent = visibleBulkCount;
    }

    // Mettre à jour le bouton de sélection en masse
    updateBulkButton();
}

// Fonction pour réinitialiser les filtres
function resetFilters() {
    const siteSearch = document.getElementById('siteSearch');
    const provinceFilter = document.getElementById('provinceFilter');
    const territoireFilter = document.getElementById('territoireFilter');
    const communeFilter = document.getElementById('communeFilter');

    if (siteSearch) siteSearch.value = '';
    if (provinceFilter) provinceFilter.value = '';
    if (territoireFilter) territoireFilter.value = '';
    if (communeFilter) communeFilter.value = '';

    filterSites();
}

// Attacher les événements de filtrage
document.addEventListener('DOMContentLoaded', function() {
    const siteSearch = document.getElementById('siteSearch');
    const provinceFilter = document.getElementById('provinceFilter');
    const territoireFilter = document.getElementById('territoireFilter');
    const communeFilter = document.getElementById('communeFilter');

    if (siteSearch) {
        siteSearch.addEventListener('input', filterSites);
    }
    if (provinceFilter) {
        provinceFilter.addEventListener('change', filterSites);
    }
    if (territoireFilter) {
        territoireFilter.addEventListener('change', filterSites);
    }
    if (communeFilter) {
        communeFilter.addEventListener('change', filterSites);
    }
});
</script>
@endsection
