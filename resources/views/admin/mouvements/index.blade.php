@extends('layouts.app')

@section('title', 'Mouvements de population')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Mouvements de population</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if(auth()->user()->role === 'super_admin')
                    Tous les flux de population dans tous les sites
                @else
                    Historique des flux de population pour votre organisation
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Bouton import Excel --}}
            <button onclick="document.getElementById('modal-import').classList.remove('hidden')"
                    class="filter-button flex items-center">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Importer Excel
            </button>
            <a href="{{ route('admin.mouvements.create') }}" class="primary-button">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau mouvement
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">{{ session('warning') }}</p>
            @if(session('import_errors'))
                <ul class="mt-2 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- Filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <form method="GET" action="{{ route('admin.mouvements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4" id="mouvements-filters-form">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site</label>
                <select name="site_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->nom }} ({{ $site->code_site }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                <select name="type_mouvement" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les types</option>
                    <option value="arrivee">Arrivée</option>
                    <option value="depart">Départ</option>
                    <option value="recensement">Recensement</option>
                    <option value="ajustement">Ajustement</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select name="statut" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="valide">Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Période (mois/année)</label>
                <input type="month"
                       name="periode"
                       id="periode-filter"
                       value="{{ request('periode') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="md:col-span-4 flex justify-end space-x-3">
                <a href="{{ route('admin.mouvements.index') }}" class="filter-button">Réinitialiser</a>
                <button type="submit" class="primary-button">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const periodeInput = document.getElementById('periode-filter');
            const form = document.getElementById('mouvements-filters-form');

            if (periodeInput && form) {
                periodeInput.addEventListener('change', function () {
                    form.submit();
                });
            }
        });
    </script>

    <!-- Tableau des mouvements -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Historique des mouvements
                    <span class="ml-2 px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-sm font-semibold rounded-full">
                        {{ $mouvements->total() }}
                    </span>
                </h3>
                <button class="text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exporter
                </button>
            </div>
        </div>

        @if($mouvements->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Raison</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ménages</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Individus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Créé par</th>
                            @if(auth()->user()->role === 'super_admin')
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($mouvements as $mouvement)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $mouvement->date_mouvement->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="font-medium">{{ $mouvement->site->nom }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $mouvement->site->code_site }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($mouvement->type_mouvement === 'arrivee')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ➕ Arrivée
                                        </span>
                                    @elseif($mouvement->type_mouvement === 'depart')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            ➖ Départ
                                        </span>
                                    @elseif($mouvement->type_mouvement === 'recensement')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            📊 Recensement
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            🔄 Ajustement
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($mouvement->statut === 'en_attente')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            ⏳ En attente
                                        </span>
                                    @elseif($mouvement->statut === 'valide')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ✓ Validé
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            ✗ Rejeté
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $mouvement->raisonMouvement->name ?? ($mouvement->raison ?? '-') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                    @if($mouvement->type_mouvement === 'depart')
                                        <span class="text-red-600 dark:text-red-400">-{{ number_format($mouvement->menages) }}</span>
                                    @else
                                        <span class="text-green-600 dark:text-green-400">+{{ number_format($mouvement->menages) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">
                                    @if($mouvement->type_mouvement === 'depart')
                                        <span class="text-red-600 dark:text-red-400">-{{ number_format($mouvement->individus) }}</span>
                                    @else
                                        <span class="text-green-600 dark:text-green-400">+{{ number_format($mouvement->individus) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <div>{{ $mouvement->createdBy->name ?? 'Système' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-500">{{ $mouvement->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                @if(auth()->user()->role === 'super_admin')
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        @if($mouvement->statut === 'en_attente')
                                            <div class="flex justify-end space-x-2">
                                                <form method="POST" action="{{ route('admin.mouvements.validate', $mouvement->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-md transition-colors"
                                                            onclick="return confirm('Valider ce mouvement ? Les statistiques du site seront mises à jour.')">
                                                        ✓ Valider
                                                    </button>
                                                </form>
                                                <button onclick="showRejectModal({{ $mouvement->id }})"
                                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-md transition-colors">
                                                    ✗ Rejeter
                                                </button>
                                            </div>
                                        @elseif($mouvement->statut === 'valide')
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <div>Validé par {{ $mouvement->validatedBy->name ?? 'Admin' }}</div>
                                                <div>{{ $mouvement->validated_at?->format('d/m/Y H:i') }}</div>
                                            </div>
                                        @else
                                            <div class="text-xs text-red-600 dark:text-red-400">
                                                <div>Rejeté</div>
                                                <div class="text-gray-500 dark:text-gray-400">{{ $mouvement->rejection_reason }}</div>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $mouvements->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun mouvement enregistré</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Commencez à enregistrer les flux de population dans vos sites
                </p>
                <a href="{{ route('admin.mouvements.create') }}" class="primary-button">
                    Ajouter un mouvement
                </a>
            </div>
        @endif
    </div>

    <!-- Modal Import Excel -->
    <div id="modal-import" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-6 border w-full max-w-lg shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Importer des mouvements depuis Excel</h3>
                <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-800 dark:text-blue-300">
                <p class="font-medium mb-1">Instructions :</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Téléchargez le modèle Excel ci-dessous</li>
                    <li>Remplissez les données à partir de la ligne 5 (la feuille <em>IMPORTATION</em>)</li>
                    <li>Consultez les feuilles <em>SITES_RÉFÉRENCE</em> et <em>RAISONS_RÉFÉRENCE</em> pour les codes valides</li>
                    <li>Uploadez le fichier rempli via le formulaire ci-dessous</li>
                </ol>
            </div>

            <a href="{{ route('admin.mouvements.import.template') }}"
               class="flex items-center justify-center w-full mb-5 px-4 py-2 border-2 border-dashed border-green-400 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Télécharger le modèle Excel (.xlsx)
            </a>

            <form action="{{ route('admin.mouvements.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fichier Excel rempli <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="fichier_excel" accept=".xlsx,.xls" required
                           class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-300">
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-import').classList.add('hidden')"
                            class="filter-button">Annuler</button>
                    <button type="submit" class="primary-button">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de rejet -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    Rejeter le mouvement
                </h3>
                <form id="rejectForm" method="POST" action="">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Raison du rejet<span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="rejection_reason" 
                            rows="4" 
                            required
                            maxlength="500"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Expliquez pourquoi ce mouvement est rejeté..."></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maximum 500 caractères</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeRejectModal()"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-sm font-medium rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
                            Annuler
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showRejectModal(mouvementId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `/admin/mouvements/${mouvementId}/reject`;
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    document.getElementById('rejectForm').reset();
}

// Fermer le modal en cliquant en dehors
document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});

// Fermer avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRejectModal();
    }
});
</script>
@endsection
