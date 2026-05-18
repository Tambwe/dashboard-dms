@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Profils de Services</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitoring des services disponibles dans les sites</p>
            </div>
            <a href="{{ route('service-profiles.create') }}" 
               class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle collecte
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

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Nom du site..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                    <select name="statut" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Tous</option>
                        <option value="brouillon" {{ request('statut') == 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                        <option value="soumis" {{ request('statut') == 'soumis' ? 'selected' : '' }}>Soumis</option>
                        <option value="valide" {{ request('statut') == 'valide' ? 'selected' : '' }}>Validé</option>
                        <option value="rejete" {{ request('statut') == 'rejete' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Filtrer
                    </button>
                    <a href="{{ route('service-profiles.index') }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des profils -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            @if($profiles->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date collecte</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Services</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collecteur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($profiles as $profile)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $profile->site->nom }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $profile->site->commune->nom ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $profile->date_collecte->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($profile->sante_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Santé</span>
                                    @endif
                                    @if($profile->education_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Éducation</span>
                                    @endif
                                    @if($profile->wash_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">WASH</span>
                                    @endif
                                    @if($profile->environnement_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Environnement</span>
                                    @endif
                                    @if($profile->abri_ame_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Abri/AME</span>
                                    @endif
                                    @if($profile->gestion_disponible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">Gestion</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $profile->collecteur->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $profile->getStatusBadgeClass() }}">
                                    {{ $profile->getStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('service-profiles.show', $profile) }}" 
                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">Voir</a>
                                @if($profile->statut === 'brouillon' && $profile->collecteur_id === auth()->id())
                                <a href="{{ route('service-profiles.edit', $profile) }}" 
                                   class="ml-3 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Modifier</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                {{ $profiles->links() }}
            </div>
            @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun profil de services</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par créer une nouvelle collecte de données.</p>
                <div class="mt-6">
                    <a href="{{ route('service-profiles.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouvelle collecte
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
