@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Mes Sites</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sites que vous pouvez gérer et collecter des données</p>
            <div class="mt-3 inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <a href="{{ route('user.sites.index') }}" class="rounded-md bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white">
                    Mes sites
                </a>
                <a href="{{ route('user.sites.collected.index') }}" class="rounded-md px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    Sites collectés
                </a>
            </div>
        </div>

        <!-- Messages flash -->
        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-3 rounded bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
            <ul class="text-sm text-red-800 dark:text-red-200 list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Barre de recherche -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('user.sites.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Rechercher un site..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="md:w-64">
                    <label for="gps_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GPS</label>
                    <select name="gps_status" id="gps_status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="" {{ request('gps_status') === null || request('gps_status') === '' ? 'selected' : '' }}>Tous les sites</option>
                        <option value="present" {{ request('gps_status') === 'present' ? 'selected' : '' }}>Sites avec GPS</option>
                        <option value="missing" {{ request('gps_status') === 'missing' ? 'selected' : '' }}>Sites sans GPS</option>
                    </select>
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
            @php
                $hasGps = is_numeric($site->latitude) && is_numeric($site->longitude)
                    && (float) $site->latitude !== 0.0
                    && (float) $site->longitude !== 0.0;
                $gpsBadgeClass = $hasGps
                    ? 'bg-emerald-500 text-white'
                    : 'bg-red-500 text-white';
                $gpsBadgeLabel = $hasGps
                    ? 'GPS'
                    : 'GPS manquant / 0';
            @endphp
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
                        <span class="px-2 py-1 {{ $gpsBadgeClass }} text-xs rounded-full flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $gpsBadgeLabel }}
                        </span>
                        
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

                    @if($site->date_fermeture)
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                            Site fermé ({{ \Carbon\Carbon::parse($site->date_fermeture)->format('d/m/Y') }})
                        </span>
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-300 space-y-1">
                            <div><span class="font-semibold">Raison :</span> {{ $site->raison_fermeture ?? '-' }}</div>
                            <div><span class="font-semibold">Commentaire :</span> {{ $site->commentaire_fermeture ?? '-' }}</div>
                            @if($site->document_fermeture)
                            <div>
                                <a href="{{ asset('storage/' . $site->document_fermeture) }}" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline">
                                    Voir le document joint
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

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

                        <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                            @if(auth()->user()->isSuperAdmin())
                                @if($site->date_fermeture)
                                    <form action="{{ route('user.sites.reopen', $site) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="button"
                                                data-site-id="{{ $site->id }}"
                                                data-site-name="{{ $site->nom }}"
                                                data-date-fermeture="{{ $site->date_fermeture ? \Carbon\Carbon::parse($site->date_fermeture)->format('d/m/Y') : '' }}"
                                                data-raison-fermeture="{{ $site->raison_fermeture }}"
                                                data-commentaire-fermeture="{{ $site->commentaire_fermeture }}"
                                                data-document-url="{{ $site->document_fermeture ? asset('storage/' . $site->document_fermeture) : '' }}"
                                                data-document-name="{{ $site->document_fermeture ? basename($site->document_fermeture) : '' }}"
                                                onclick="openUserReopenModalFromButton(this)"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                                            Réouvrir
                                        </button>
                                    </form>
                                @else
                                    <button type="button"
                                            data-site-id="{{ $site->id }}"
                                            data-site-name="{{ $site->nom }}"
                                            data-document-url="{{ $site->document_fermeture ? asset('storage/' . $site->document_fermeture) : '' }}"
                                            data-document-name="{{ $site->document_fermeture ? basename($site->document_fermeture) : '' }}"
                                            onclick="openUserClosureModalFromButton(this)"
                                            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                            Fermer
                                    </button>
                                @endif
                            @endif

                            <a href="{{ route('user.sites.edit', $site) }}"
                               class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Profil
                            </a>
                        </div>
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

<div id="userClosureModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    data-reopen="{{ $errors->any() && old('closure_site_id') ? '1' : '0' }}"
    data-old-site-id="{{ old('closure_site_id') }}"
    data-old-site-name="{{ old('closure_site_name', 'Reprendre la fermeture du site') }}">
    <div class="relative top-16 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-2">
                Déclarer un site fermé
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="userClosureSiteName"></p>

            <form method="POST" id="userClosureForm" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="closure_site_id" id="user_closure_site_id" value="{{ old('closure_site_id') }}">
                <input type="hidden" name="closure_site_name" id="user_closure_site_name" value="{{ old('closure_site_name') }}">

                <div>
                    <label for="user_closure_date_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date de fermeture
                    </label>
                    <input type="date"
                           id="user_closure_date_fermeture"
                           name="date_fermeture"
                           value="{{ old('date_fermeture', date('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="user_closure_raison_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison de fermeture
                    </label>
                    <input type="text"
                           id="user_closure_raison_fermeture"
                           name="raison_fermeture"
                           value="{{ old('raison_fermeture') }}"
                           required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="user_closure_commentaire_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Commentaire
                    </label>
                    <textarea id="user_closure_commentaire_fermeture"
                              name="commentaire_fermeture"
                              rows="4"
                              required
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('commentaire_fermeture') }}</textarea>
                </div>

                <div>
                    <label for="user_closure_document_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Document justificatif
                    </label>
                    <input type="file"
                           id="user_closure_document_fermeture"
                           name="document_fermeture"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm dark:file:bg-gray-600">
                    <div id="userClosureDocumentPreview" class="hidden mt-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <a id="userClosureDocumentLink" href="#" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline"></a>
                        <img id="userClosureDocumentImage" src="" alt="Prévisualisation du document" class="hidden mt-3 max-h-56 rounded-lg border border-gray-200 dark:border-gray-700">
                        <iframe id="userClosureDocumentFrame" src="" class="hidden mt-3 h-64 w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="closeUserClosureModal()"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium rounded-lg">
                        Annuler
                    </button>
                    <button type="submit"
                            onclick="return confirm('Déclarer ce site fermé à partir de la date indiquée ?')"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">
                        Confirmer la fermeture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="userReopenModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-16 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-2">
                Confirmer la réouverture du site
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="userReopenSiteName"></p>

            <div class="space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Date de fermeture :</span> <span id="userReopenDateFermeture">-</span></div>
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Raison :</span> <span id="userReopenRaisonFermeture">-</span></div>
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Commentaire :</span> <span id="userReopenCommentaireFermeture">-</span></div>
                <div id="userReopenDocumentPreview" class="hidden">
                    <a id="userReopenDocumentLink" href="#" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline"></a>
                    <img id="userReopenDocumentImage" src="" alt="Prévisualisation du document" class="hidden mt-3 max-h-56 rounded-lg border border-gray-200 dark:border-gray-700">
                    <iframe id="userReopenDocumentFrame" src="" class="hidden mt-3 h-64 w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                </div>
            </div>

            <form method="POST" id="userReopenForm" class="flex justify-end gap-3">
                @csrf
                <button type="button" onclick="closeUserReopenModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium rounded-lg">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg">
                    Confirmer la réouverture
                </button>
            </form>
        </div>
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

    .geojson-legend {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(229, 231, 235, 0.95);
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: 12px 14px;
        max-width: 320px;
    }

    .dark .geojson-legend {
        background: rgba(17, 24, 39, 0.96);
        border-color: rgba(55, 65, 81, 0.9);
        color: #e5e7eb;
    }

    .geojson-legend h4 {
        margin: 0 0 8px 0;
        font-size: 0.8rem;
        font-weight: 700;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .dark .geojson-legend h4 {
        color: #f9fafb;
    }

    .geojson-legend-list {
        display: grid;
        gap: 8px;
        max-height: 220px;
        overflow: auto;
        padding-right: 2px;
    }

    .geojson-legend-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: 0.76rem;
        line-height: 1.25rem;
        color: #334155;
    }

    .dark .geojson-legend-item {
        color: #cbd5e1;
    }

    .geojson-legend-swatch {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        border: 2px solid rgba(255,255,255,0.95);
        box-shadow: 0 0 0 1px rgba(15,23,42,0.18);
        flex: 0 0 auto;
        margin-top: 2px;
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

        const overlayMaps = {};
        const deferredGeojsonLayers = [];
        const layerPalette = ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed'];

        // Créer un groupe de marqueurs pour ajuster le zoom
        const markers = [];

        async function fetchLayerGeojson(site, layerIndex) {
            const cacheKey = `layer-${layerIndex}-low`;
            site._geojsonCache = site._geojsonCache || {};
            site._geojsonPromiseCache = site._geojsonPromiseCache || {};

            if (Object.prototype.hasOwnProperty.call(site._geojsonCache, cacheKey)) {
                return site._geojsonCache[cacheKey];
            }

            if (!site._geojsonPromiseCache[cacheKey]) {
                site._geojsonPromiseCache[cacheKey] = fetch(`${site.geojsonUrl}?layer=${encodeURIComponent(layerIndex)}&preview=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(payload => payload.geojson_data || null);
            }

            const data = await site._geojsonPromiseCache[cacheKey];
            site._geojsonCache[cacheKey] = data;
            return data;
        }

        function getLayerColor(layerIndex) {
            return layerPalette[layerIndex % layerPalette.length];
        }

        function getLayerShade(layerIndex) {
            return ['#1d4ed8', '#15803d', '#b45309', '#b91c1c', '#6d28d9'][layerIndex % layerPalette.length];
        }

        function buildGeojsonLayer(site, layerName, layerGeometry, layerIndex) {
            const color = getLayerColor(layerIndex);
            const shade = getLayerShade(layerIndex);

            return L.geoJSON(layerGeometry, {
                style: function() {
                    return {
                        color: color,
                        weight: 3,
                        opacity: 0.8,
                        fillColor: color,
                        fillOpacity: 0.3
                    };
                },
                onEachFeature: function(feature, layer) {
                    let featurePopup = `
                        <div class="p-2">
                            <h4 class="font-bold text-base mb-2 text-primary-600">${site.nom}</h4>
                            <div class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-semibold mb-2" style="background:${color}15;color:${shade};border:1px solid ${color}55;">
                                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:${color};"></span>
                                <span>${layerName}</span>
                            </div>
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

                    // Pas de tooltip permanent sur les grosses couches: cela surcharge le DOM.
                },
                pointToLayer: function(feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 8,
                        fillColor: color,
                        color: shade,
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.6
                    });
                }
            });
        }

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
                    geojsonUrl: "{{ route('user.sites.geojson', $site) }}",
                    hasGeojson: {{ $site->geojson_data ? 'true' : 'false' }},
                    geojsonLayers: @json($geojsonLayersMetaBySite[$site->id] ?? [])
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
                        ${site.hasGeojson ? `<p class="text-sm text-emerald-600 mt-2"><svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>GeoJSON disponible</p>` : ''}
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

            // Declarer les couches GeoJSON disponibles sans les construire immediatement
            if (site.hasGeojson && Array.isArray(site.geojsonLayers) && site.geojsonLayers.length > 0) {
                site.geojsonLayers.forEach(layerMeta => {
                    const layerGroup = L.layerGroup();
                    const styleIndex = deferredGeojsonLayers.length;
                    const layerLabel = layerMeta && layerMeta.name ? layerMeta.name : 'GeoJSON';
                    const layerIndex = Number.isInteger(layerMeta && layerMeta.index) ? layerMeta.index : styleIndex;
                    const labelBase = `${site.nom} - ${layerLabel}`;
                    let controlLabel = labelBase;
                    let duplicateIndex = 2;
                    while (Object.prototype.hasOwnProperty.call(overlayMaps, controlLabel)) {
                        controlLabel = `${labelBase} (${duplicateIndex})`;
                        duplicateIndex++;
                    }

                    overlayMaps[controlLabel] = layerGroup;
                    deferredGeojsonLayers.push({
                        site,
                        layerIndex,
                        styleIndex,
                        layerName: layerLabel,
                        layerColor: getLayerColor(styleIndex),
                        layerGroup,
                        loaded: false,
                        pendingPromises: {},
                        hasError: false
                    });
                });
            }
        });

        if (Object.keys(overlayMaps).length > 0) {
            L.control.layers(null, overlayMaps, { collapsed: false, position: 'topright' }).addTo(map);
        }

        const legendControl = L.control({ position: 'bottomright' });

        legendControl.onAdd = function() {
            const container = L.DomUtil.create('div', 'geojson-legend');
            const itemsHtml = deferredGeojsonLayers.slice(0, 15).map(layer => `
                <div class="geojson-legend-item">
                    <span class="geojson-legend-swatch" style="background:${layer.layerColor};"></span>
                    <span><strong>${layer.site.nom}</strong><br>${layer.layerName}</span>
                </div>
            `).join('');

            container.innerHTML = `
                <h4>Légende GeoJSON</h4>
                <div class="geojson-legend-list">
                    ${itemsHtml || '<div class="text-xs text-gray-500 dark:text-gray-400">Aucune couche GeoJSON</div>'}
                </div>
            `;

            return container;
        };

        legendControl.addTo(map);

        async function ensureLayerRendered(deferredLayer) {
            if (deferredLayer.loaded || deferredLayer.hasError) {
                return;
            }

            const pendingKey = 'low';
            deferredLayer.pendingPromises = deferredLayer.pendingPromises || {};
            if (deferredLayer.pendingPromises[pendingKey]) {
                return deferredLayer.pendingPromises[pendingKey];
            }

            deferredLayer.pendingPromises[pendingKey] = (async () => {
                try {
                    const layerGeometry = await fetchLayerGeojson(deferredLayer.site, deferredLayer.layerIndex);

                    if (!layerGeometry) {
                        deferredLayer.hasError = true;
                        return;
                    }

                    const geojsonLayer = buildGeojsonLayer(deferredLayer.site, deferredLayer.layerName, layerGeometry, deferredLayer.styleIndex);

                    deferredLayer.layerGroup.clearLayers();
                    deferredLayer.layerGroup.addLayer(geojsonLayer);
                    deferredLayer.loaded = true;
                } catch (error) {
                    deferredLayer.hasError = true;
                    console.error(`Erreur lors du chargement du GeoJSON pour ${deferredLayer.site.nom}:`, error);
                } finally {
                    delete deferredLayer.pendingPromises[pendingKey];
                }
            })();

            return deferredLayer.pendingPromises[pendingKey];
        }

        map.on('overlayadd', async function(event) {
            const deferredLayer = deferredGeojsonLayers.find(item => item.layerGroup === event.layer);
            if (!deferredLayer || deferredLayer.hasError) {
                return;
            }

            await ensureLayerRendered(deferredLayer);
        });

        map.on('overlayremove', function(event) {
            const deferredLayer = deferredGeojsonLayers.find(item => item.layerGroup === event.layer);
            if (!deferredLayer) {
                return;
            }
        });

        // Ajuster le zoom pour afficher tous les marqueurs
        if (markers.length > 0) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }

        function openUserClosureModal(siteId, siteName, documentUrl = '', documentName = '') {
            document.getElementById('userClosureSiteName').textContent = siteName;
            document.getElementById('userClosureForm').action = `/my/sites/${siteId}/close`;
            document.getElementById('user_closure_site_id').value = siteId;
            document.getElementById('user_closure_site_name').value = siteName;
            updateUserDocumentPreview('userClosure', documentUrl, documentName);
            document.getElementById('userClosureModal').classList.remove('hidden');
        }

        window.openUserClosureModalFromButton = function(button) {
            openUserClosureModal(
                button.dataset.siteId,
                button.dataset.siteName || '',
                button.dataset.documentUrl || '',
                button.dataset.documentName || ''
            );
        };

        window.closeUserClosureModal = function() {
            document.getElementById('userClosureModal').classList.add('hidden');
        };

        function updateUserDocumentPreview(prefix, url, name) {
            const container = document.getElementById(prefix + 'DocumentPreview');
            const link = document.getElementById(prefix + 'DocumentLink');
            const image = document.getElementById(prefix + 'DocumentImage');
            const frame = document.getElementById(prefix + 'DocumentFrame');

            link.textContent = '';
            link.href = '#';
            image.classList.add('hidden');
            image.src = '';
            frame.classList.add('hidden');
            frame.src = '';

            if (!url) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');
            link.textContent = name || 'Voir le document joint';
            link.href = url;

            const normalizedUrl = url.toLowerCase();
            if (normalizedUrl.match(/\.(png|jpg|jpeg|gif|webp)$/)) {
                image.src = url;
                image.classList.remove('hidden');
            } else if (normalizedUrl.endsWith('.pdf')) {
                frame.src = url;
                frame.classList.remove('hidden');
            }
        }

        function openUserReopenModal(siteId, siteName, dateFermeture, raisonFermeture, commentaireFermeture, documentUrl, documentName) {
            document.getElementById('userReopenSiteName').textContent = siteName;
            document.getElementById('userReopenDateFermeture').textContent = dateFermeture || '-';
            document.getElementById('userReopenRaisonFermeture').textContent = raisonFermeture || '-';
            document.getElementById('userReopenCommentaireFermeture').textContent = commentaireFermeture || '-';
            document.getElementById('userReopenForm').action = `/my/sites/${siteId}/reopen`;
            updateUserDocumentPreview('userReopen', documentUrl || '', documentName || '');
            document.getElementById('userReopenModal').classList.remove('hidden');
        }

        window.openUserReopenModalFromButton = function(button) {
            openUserReopenModal(
                button.dataset.siteId,
                button.dataset.siteName || '',
                button.dataset.dateFermeture || '',
                button.dataset.raisonFermeture || '',
                button.dataset.commentaireFermeture || '',
                button.dataset.documentUrl || '',
                button.dataset.documentName || ''
            );
        };

        window.closeUserReopenModal = function() {
            document.getElementById('userReopenModal').classList.add('hidden');
        };

        document.getElementById('userClosureModal').addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeUserClosureModal();
            }
        });

        document.getElementById('userReopenModal').addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeUserReopenModal();
            }
        });

        const userClosureModal = document.getElementById('userClosureModal');
        if (userClosureModal.dataset.reopen === '1' && userClosureModal.dataset.oldSiteId) {
            openUserClosureModal(userClosureModal.dataset.oldSiteId, userClosureModal.dataset.oldSiteName || 'Reprendre la fermeture du site');
        }
    });
    @endif
</script>
@endpush

@endsection
