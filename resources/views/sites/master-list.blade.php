@extends('layouts.app')

@section('title', 'Master List - Sites')

@section('content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Master List des Sites</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Liste complète des sites avec variations mensuelles
            </p>
        </div>
        <a href="{{ route('sites.master-list.export') }}{{ request()->search ? '?search=' . request()->search : '' }}" 
           class="primary-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exporter Excel
        </a>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <form method="GET" action="{{ route('sites.master-list') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rechercher</label>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Nom du site, code, province, territoire..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div class="w-full md:w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Par page</label>
                <select name="per_page" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                </select>
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="primary-button">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Rechercher
                </button>
                @if($search)
                    <a href="{{ route('sites.master-list') }}" class="filter-button">
                        Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-900/30 rounded-lg p-3">
                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sites</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sites->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Ménages</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($sites->sum('menages')) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 dark:bg-green-900/30 rounded-lg p-3">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Individus</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($sites->sum('individus')) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nom du Site</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Localisation</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ménages</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variation Ménages</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variation Individus</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gestionnaire</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sites as $site)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $site->code_site }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                <div class="font-medium">{{ $site->nom }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $site->typeSite->name ?? '' }} - {{ $site->categorieSite->name ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>{{ $site->province->name ?? '' }}</div>
                                <div class="text-xs">{{ $site->territoire->name ?? '' }} / {{ $site->commune->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($site->menages) }}</div>
                                @if($site->variation['has_data'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        était {{ number_format($site->variation['menages_previous']) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                @if($site->variation['has_data'])
                                    @if($site->variation['menages_variation'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ↗ +{{ number_format($site->variation['menages_variation']) }}
                                            <span class="ml-1">(+{{ $site->variation['menages_percentage'] }}%)</span>
                                        </span>
                                    @elseif($site->variation['menages_variation'] < 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            ↘ {{ number_format($site->variation['menages_variation']) }}
                                            <span class="ml-1">({{ $site->variation['menages_percentage'] }}%)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            → Stable
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($site->individus) }}</div>
                                @if($site->variation['has_data'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        était {{ number_format($site->variation['individus_previous']) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                @if($site->variation['has_data'])
                                    @if($site->variation['individus_variation'] > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ↗ +{{ number_format($site->variation['individus_variation']) }}
                                            <span class="ml-1">(+{{ $site->variation['individus_percentage'] }}%)</span>
                                        </span>
                                    @elseif($site->variation['individus_variation'] < 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            ↘ {{ number_format($site->variation['individus_variation']) }}
                                            <span class="ml-1">({{ $site->variation['individus_percentage'] }}%)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            → Stable
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $site->gestionnaire->nom ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun site trouvé</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($search)
                                        Aucun résultat pour "{{ $search }}"
                                    @else
                                        Aucun site enregistré dans le système
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sites->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Affichage de <span class="font-medium">{{ $sites->firstItem() }}</span>
                        à <span class="font-medium">{{ $sites->lastItem() }}</span>
                        sur <span class="font-medium">{{ $sites->total() }}</span> sites
                    </div>
                    <div>
                        {{ $sites->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
