@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Gestion des Sites par Organisation</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Attribuer et gérer l'accès des organisations aux sites</p>
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

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('admin.sites.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="{{ request('search') }}"
                           placeholder="Nom du site..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Organisation
                    </label>
                    <select name="organisation_id" 
                            id="organisation_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Toutes les organisations</option>
                        @foreach($organisations as $org)
                            <option value="{{ $org->id }}" {{ request('organisation_id') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Statut
                    </label>
                    <select name="status" 
                            id="status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tous les sites</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Attribués</option>
                        <option value="unassigned" {{ request('status') == 'unassigned' ? 'selected' : '' }}>Non attribués</option>
                    </select>
                </div>

                <div>
                    <label for="closure_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        État du site
                    </label>
                    <select name="closure_status"
                            id="closure_status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tous</option>
                        <option value="open" {{ request('closure_status') == 'open' ? 'selected' : '' }}>Ouverts</option>
                        <option value="closed" {{ request('closure_status') == 'closed' ? 'selected' : '' }}>Fermés</option>
                    </select>
                </div>

                <div>
                    <label for="gps_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        GPS
                    </label>
                    <select name="gps_status"
                            id="gps_status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tous</option>
                        <option value="present" {{ request('gps_status') == 'present' ? 'selected' : '' }}>Sites avec GPS</option>
                        <option value="missing" {{ request('gps_status') == 'missing' ? 'selected' : '' }}>Sites sans GPS</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Attribution en masse -->
        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="POST" action="{{ route('admin.sites.bulk-assign') }}" id="bulkAssignForm">
                @csrf
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label for="bulk_organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Attribuer les sites sélectionnés à :
                        </label>
                        <select name="organisation_id" 
                                id="bulk_organisation_id"
                                required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Choisir une organisation</option>
                            @foreach($organisations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" 
                            id="bulkAssignBtn"
                            disabled
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-lg transition-colors">
                        Attribuer (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </form>
        </div>

        <!-- Tableau des sites -->
        <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-2">
                                <input type="checkbox" 
                                       id="selectAll"
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Site
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Localisation
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Type
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Organisation
                            </th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                État
                            </th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($sites as $site)
                        @php
                            $hasGps = is_numeric($site->latitude) && is_numeric($site->longitude)
                                && (float) $site->latitude !== 0.0
                                && (float) $site->longitude !== 0.0;
                            $gpsBadgeClass = $hasGps
                                ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300'
                                : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300';
                            $gpsBadgeLabel = $hasGps ? 'GPS' : 'GPS manquant / 0';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" 
                                       name="site_ids[]" 
                                       value="{{ $site->id }}"
                                       class="site-checkbox rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $site->nom }}
                                </div>
                                @if($site->code_site)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $site->code_site }}
                                </div>
                                @endif
                                <div class="mt-2">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $gpsBadgeClass }}">
                                        {{ $gpsBadgeLabel }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                <div>{{ $site->territoire }}, {{ $site->province }}</div>
                                @if($site->commune)
                                <div class="text-xs">{{ $site->commune->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $site->typeSite->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($site->organisation)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                    {{ $site->organisation->name }}
                                </span>
                                @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    Non attribué
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($site->date_fermeture)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                    Fermé le {{ \Illuminate\Support\Carbon::parse($site->date_fermeture)->format('d/m/Y') }}
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
                                @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300">
                                    Ouvert
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center justify-end space-x-2">
                                        @if($site->organisation)
                                        <form method="POST" action="{{ route('admin.sites.remove-from-organisation', $site) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('Retirer ce site de l\'organisation ?')"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    title="Retirer de l'organisation">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        @else
                                        <button type="button"
                                                data-site-id="{{ $site->id }}"
                                                data-site-name="{{ $site->nom }}"
                                                onclick="openAssignModalFromButton(this)"
                                                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                                title="Attribuer à une organisation">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>

                                    @if(auth()->user()->isSuperAdmin())
                                        @if($site->date_fermeture)
                                        <form method="POST" action="{{ route('admin.sites.reopen', $site) }}" class="inline">
                                            @csrf
                                            <button type="button"
                                                    data-site-id="{{ $site->id }}"
                                                    data-site-name="{{ $site->nom }}"
                                                    data-date-fermeture="{{ $site->date_fermeture ? \Illuminate\Support\Carbon::parse($site->date_fermeture)->format('d/m/Y') : '' }}"
                                                    data-raison-fermeture="{{ $site->raison_fermeture }}"
                                                    data-commentaire-fermeture="{{ $site->commentaire_fermeture }}"
                                                    data-document-url="{{ $site->document_fermeture ? asset('storage/' . $site->document_fermeture) : '' }}"
                                                    data-document-name="{{ $site->document_fermeture ? basename($site->document_fermeture) : '' }}"
                                                    onclick="openReopenModalFromButton(this)"
                                                    class="px-2 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium">
                                                Réouvrir
                                            </button>
                                        </form>
                                        @else
                                        <button type="button"
                                                data-site-id="{{ $site->id }}"
                                                data-site-name="{{ $site->nom }}"
                                            data-document-url="{{ $site->document_fermeture ? asset('storage/' . $site->document_fermeture) : '' }}"
                                            data-document-name="{{ $site->document_fermeture ? basename($site->document_fermeture) : '' }}"
                                                onclick="openClosureModalFromButton(this)"
                                                class="px-2 py-1 rounded bg-red-600 hover:bg-red-700 text-white text-xs font-medium">
                                                Fermer
                                        </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Aucun site trouvé
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($sites->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $sites->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal d'attribution -->
<div id="assignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
                Attribuer le site
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="siteName"></p>
            
            <form method="POST" id="assignForm">
                @csrf
                <div class="mb-4">
                    <label for="modal_organisation_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Organisation
                    </label>
                    <select name="organisation_id" 
                            id="modal_organisation_id"
                            required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Choisir une organisation</option>
                        @foreach($organisations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeAssignModal()"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg">
                        Attribuer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de fermeture -->
<div id="closureModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    data-reopen="{{ $errors->any() && old('closure_site_id') ? '1' : '0' }}"
    data-old-site-id="{{ old('closure_site_id') }}"
    data-old-site-name="{{ old('closure_site_name', 'Reprendre la fermeture du site') }}">
    <div class="relative top-16 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-2">
                Déclarer un site fermé
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="closureSiteName"></p>

            <form method="POST" id="closureForm" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="closure_site_id" id="closure_site_id" value="{{ old('closure_site_id') }}">
                <input type="hidden" name="closure_site_name" id="closure_site_name" value="{{ old('closure_site_name') }}">

                <div>
                    <label for="closure_date_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Date de fermeture
                    </label>
                    <input type="date"
                           id="closure_date_fermeture"
                           name="date_fermeture"
                           value="{{ old('date_fermeture', now()->format('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="closure_raison_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison de fermeture
                    </label>
                    <input type="text"
                           id="closure_raison_fermeture"
                           name="raison_fermeture"
                           value="{{ old('raison_fermeture') }}"
                           required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div>
                    <label for="closure_commentaire_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Commentaire
                    </label>
                    <textarea id="closure_commentaire_fermeture"
                              name="commentaire_fermeture"
                              rows="4"
                              required
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('commentaire_fermeture') }}</textarea>
                </div>

                <div>
                    <label for="closure_document_fermeture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Document justificatif
                    </label>
                    <input type="file"
                           id="closure_document_fermeture"
                           name="document_fermeture"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm dark:file:bg-gray-600">
                    <div id="closureDocumentPreview" class="hidden mt-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <a id="closureDocumentLink" href="#" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline"></a>
                        <img id="closureDocumentImage" src="" alt="Prévisualisation du document" class="hidden mt-3 max-h-56 rounded-lg border border-gray-200 dark:border-gray-700">
                        <iframe id="closureDocumentFrame" src="" class="hidden mt-3 h-64 w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="closeClosureModal()"
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

<div id="reopenModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-16 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-2">
                Confirmer la réouverture du site
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" id="reopenSiteName"></p>

            <div class="space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Date de fermeture :</span> <span id="reopenDateFermeture">-</span></div>
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Raison :</span> <span id="reopenRaisonFermeture">-</span></div>
                <div class="text-sm text-gray-700 dark:text-gray-200"><span class="font-semibold">Commentaire :</span> <span id="reopenCommentaireFermeture">-</span></div>
                <div id="reopenDocumentPreview" class="hidden">
                    <a id="reopenDocumentLink" href="#" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline"></a>
                    <img id="reopenDocumentImage" src="" alt="Prévisualisation du document" class="hidden mt-3 max-h-56 rounded-lg border border-gray-200 dark:border-gray-700">
                    <iframe id="reopenDocumentFrame" src="" class="hidden mt-3 h-64 w-full rounded-lg border border-gray-200 dark:border-gray-700"></iframe>
                </div>
            </div>

            <form method="POST" id="reopenForm" class="flex justify-end gap-3">
                @csrf
                <button type="button" onclick="closeReopenModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium rounded-lg">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
                    Confirmer la réouverture
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion de la sélection multiple
const selectAllCheckbox = document.getElementById('selectAll');
const siteCheckboxes = document.querySelectorAll('.site-checkbox');
const bulkAssignBtn = document.getElementById('bulkAssignBtn');
const selectedCountSpan = document.getElementById('selectedCount');
const bulkAssignForm = document.getElementById('bulkAssignForm');

selectAllCheckbox.addEventListener('change', function() {
    siteCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkAssignButton();
});

siteCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkAssignButton);
});

function updateBulkAssignButton() {
    const selectedCheckboxes = document.querySelectorAll('.site-checkbox:checked');
    const count = selectedCheckboxes.length;
    selectedCountSpan.textContent = count;
    bulkAssignBtn.disabled = count === 0;
}

bulkAssignForm.addEventListener('submit', function(e) {
    const selectedCheckboxes = document.querySelectorAll('.site-checkbox:checked');
    selectedCheckboxes.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'site_ids[]';
        input.value = checkbox.value;
        this.appendChild(input);
    });
});

// Gestion du modal
function openAssignModal(siteId, siteName) {
    document.getElementById('siteName').textContent = siteName;
    document.getElementById('assignForm').action = `/admin/sites/${siteId}/assign-to-organisation`;
    document.getElementById('assignModal').classList.remove('hidden');
}

function openAssignModalFromButton(button) {
    openAssignModal(button.dataset.siteId, button.dataset.siteName || '');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

function openClosureModal(siteId, siteName, documentUrl = '', documentName = '') {
    document.getElementById('closureSiteName').textContent = siteName;
    document.getElementById('closureForm').action = `/admin/sites/${siteId}/close`;
    document.getElementById('closure_site_id').value = siteId;
    document.getElementById('closure_site_name').value = siteName;
    updateDocumentPreview('closure', documentUrl, documentName);
    document.getElementById('closureModal').classList.remove('hidden');
}

function openClosureModalFromButton(button) {
    openClosureModal(
        button.dataset.siteId,
        button.dataset.siteName || '',
        button.dataset.documentUrl || '',
        button.dataset.documentName || ''
    );
}

function closeClosureModal() {
    document.getElementById('closureModal').classList.add('hidden');
}

function updateDocumentPreview(prefix, url, name) {
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

function openReopenModal(siteId, siteName, dateFermeture, raisonFermeture, commentaireFermeture, documentUrl, documentName) {
    document.getElementById('reopenSiteName').textContent = siteName;
    document.getElementById('reopenDateFermeture').textContent = dateFermeture || '-';
    document.getElementById('reopenRaisonFermeture').textContent = raisonFermeture || '-';
    document.getElementById('reopenCommentaireFermeture').textContent = commentaireFermeture || '-';
    document.getElementById('reopenForm').action = `/admin/sites/${siteId}/reopen`;
    updateDocumentPreview('reopen', documentUrl || '', documentName || '');
    document.getElementById('reopenModal').classList.remove('hidden');
}

function openReopenModalFromButton(button) {
    openReopenModal(
        button.dataset.siteId,
        button.dataset.siteName || '',
        button.dataset.dateFermeture || '',
        button.dataset.raisonFermeture || '',
        button.dataset.commentaireFermeture || '',
        button.dataset.documentUrl || '',
        button.dataset.documentName || ''
    );
}

function closeReopenModal() {
    document.getElementById('reopenModal').classList.add('hidden');
}

// Fermer le modal en cliquant en dehors
document.getElementById('assignModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignModal();
    }
});

document.getElementById('closureModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeClosureModal();
    }
});

document.getElementById('reopenModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReopenModal();
    }
});

const closureModal = document.getElementById('closureModal');
if (closureModal.dataset.reopen === '1' && closureModal.dataset.oldSiteId) {
    openClosureModal(closureModal.dataset.oldSiteId, closureModal.dataset.oldSiteName || 'Reprendre la fermeture du site');
}
</script>
@endsection
