@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Ménages</h1>
            <p class="text-gray-600 mt-1">Enregistrement et suivi des ménages déplacés</p>
        </div>
        <a href="{{ route('households.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau Ménage
        </a>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">Total Ménages</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\Household::count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">Niveau 1</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\Household::niveau1()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">Niveau 2</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\Household::niveau2()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">Total Personnes</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\Household::sum('nombre_total_personnes') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('households.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                <select name="site_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                <select name="niveau" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les niveaux</option>
                    <option value="1" {{ request('niveau') == '1' ? 'selected' : '' }}>Niveau 1</option>
                    <option value="2" {{ request('niveau') == '2' ? 'selected' : '' }}>Niveau 2</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="statut" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les statuts</option>
                    <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actif</option>
                    <option value="déplacé" {{ request('statut') == 'déplacé' ? 'selected' : '' }}>Déplacé</option>
                    <option value="retourné" {{ request('statut') == 'retourné' ? 'selected' : '' }}>Retourné</option>
                    <option value="réinstallé" {{ request('statut') == 'réinstallé' ? 'selected' : '' }}>Réinstallé</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Nom ou numéro..." 
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Filtrer
                </button>
                <a href="{{ route('households.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des ménages -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chef de Ménage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personnes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($households as $household)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $household->numero_menage }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $household->chef_nom_complet }}</div>
                        <div class="text-sm text-gray-500">{{ $household->chef_telephone ?? 'Pas de téléphone' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $household->site->nom }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $household->getNiveauBadgeClass() }}">
                            Niveau {{ $household->niveau_enregistrement }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            {{ $household->nombre_total_personnes }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $household->getStatusBadgeClass() }}">
                            {{ ucfirst($household->statut) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $household->date_enregistrement->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('households.show', $household) }}" class="text-blue-600 hover:text-blue-900 mr-3">Voir</a>
                        <a href="{{ route('households.edit', $household) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifier</a>
                        @if($household->niveau_enregistrement === '1')
                        <a href="{{ route('households.upgrade-to-level2', $household) }}" class="text-green-600 hover:text-green-900">Niveau 2</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <p class="text-gray-500 text-lg">Aucun ménage trouvé</p>
                            <a href="{{ route('households.create') }}" class="mt-4 text-blue-600 hover:text-blue-700">Enregistrer le premier ménage</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($households->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $households->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
