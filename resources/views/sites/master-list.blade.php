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
        <a href="{{ route('sites.master-list.export', request()->except('page')) }}"
           class="primary-button">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Exporter Excel
        </a>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <form method="GET" action="{{ route('sites.master-list') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rechercher</label>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}"
                       placeholder="Nom du site, code, province, territoire..."
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Province</label>
                <select name="province_id" id="province_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Toutes les provinces</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}" {{ (string) $selectedProvinceId === (string) $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Territoire</label>
                <select name="territoire_id" id="territoire_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les territoires</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zone de santé</label>
                <select name="commune_id" id="commune_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Toutes les zones de santé</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mois / année</label>
                <input type="month"
                       name="periode"
                       value="{{ $selectedPeriod->format('Y-m') }}"
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
            
            <div class="flex items-end gap-2 md:col-span-4">
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
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Variations calculées à partir de la période sélectionnée : <span class="font-semibold">{{ $selectedPeriod->format('m/Y') }}</span>
        </p>
    </div>

    <!-- Statistiques globales -->
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
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $typeSummaryTotals['site_count'] }}</p>
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
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($typeSummaryTotals['total_menages']) }}</p>
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
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($typeSummaryTotals['total_individus']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques par type -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Par type de site</h3>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $typeSummaryTotals['site_count'] }} site(s) filtré(s)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($sitesByType as $typeStat)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $typeStat['type'] }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $typeStat['site_count'] }} sites</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300">
                            {{ $typeStat['site_count'] }}
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700">
                            <p class="text-gray-500 dark:text-gray-400">Ménages</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($typeStat['total_menages']) }}</p>
                        </div>
                        <div class="rounded-lg bg-white dark:bg-gray-800 p-3 border border-gray-100 dark:border-gray-700">
                            <p class="text-gray-500 dark:text-gray-400">Individus</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($typeStat['total_individus']) }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500 dark:text-gray-400">Aucun site correspondant aux filtres.</div>
            @endforelse
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Période de comparaison</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variation Ménages</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variation Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tranches Femmes</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tranches Hommes</th>
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
                                <div class="font-medium flex items-center gap-2">
                                    <a href="{{ route('sites.master-list.history', array_merge(['site' => $site->id], request()->query())) }}"
                                       class="text-primary-700 dark:text-primary-300 hover:underline">
                                        {{ $site->nom }}
                                    </a>
                                    @if($site->date_fermeture)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            Site fermé ({{ \Carbon\Carbon::parse($site->date_fermeture)->format('d/m/Y') }})
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $site->typeSite->name ?? '' }} - {{ $site->categorieSite->name ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div><span class="font-medium">Province:</span> {{ $site->province->name ?? $site->province ?? '-' }}</div>
                                <div class="text-xs"><span class="font-medium">Territoire:</span> {{ $site->territoire->name ?? $site->territoire ?? '-' }}</div>
                                <div class="text-xs"><span class="font-medium">Zone de santé:</span> {{ $site->commune->name ?? $site->zone_sante ?? '-' }}</div>
                                <div class="text-xs"><span class="font-medium">Aire de santé:</span> {{ $site->aire_sante ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($site->current_menages ?? $site->menages) }}</div>
                                @if($site->variation['has_data'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        était {{ number_format($site->variation['menages_previous']) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                @if($site->variation['has_data'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300">
                                        {{ $site->variation['comparison_period_label'] }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
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
                                <div class="font-semibold text-gray-900 dark:text-white">{{ number_format($site->current_individus ?? $site->individus) }}</div>
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
                            <td class="px-4 py-4 text-sm text-right text-gray-700 dark:text-gray-300">
                                <div class="text-xs">0-5: {{ number_format($site->f_0_5 ?? 0) }}</div>
                                <div class="text-xs">6-17: {{ number_format($site->f_6_17 ?? 0) }}</div>
                                <div class="text-xs">18-59: {{ number_format($site->f_18_59 ?? 0) }}</div>
                                <div class="text-xs">60+: {{ number_format($site->f_60_plus ?? 0) }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-right text-gray-700 dark:text-gray-300">
                                <div class="text-xs">0-5: {{ number_format($site->h_0_5 ?? 0) }}</div>
                                <div class="text-xs">6-17: {{ number_format($site->h_6_17 ?? 0) }}</div>
                                <div class="text-xs">18-59: {{ number_format($site->h_18_59 ?? 0) }}</div>
                                <div class="text-xs">60+: {{ number_format($site->h_60_plus ?? 0) }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $site->gestionnaire->nom ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center">
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

@push('scripts')
<script>
    (() => {
        const provinceSelect = document.getElementById('province_id');
        const territoireSelect = document.getElementById('territoire_id');
        const communeSelect = document.getElementById('commune_id');
        const selectedTerritoireId = @json($selectedTerritoireId ?? null);
        const selectedCommuneId = @json($selectedCommuneId ?? null);

        const resetTerritoires = (label = 'Tous les territoires') => {
            territoireSelect.innerHTML = `<option value="">${label}</option>`;
            communeSelect.innerHTML = '<option value="">Toutes les zones de santé</option>';
        };

        const resetCommunes = (label = 'Toutes les zones de santé') => {
            communeSelect.innerHTML = `<option value="">${label}</option>`;
        };

        const loadTerritoires = async (provinceId, preserveSelection = null) => {
            if (!provinceId) {
                resetTerritoires();
                return;
            }

            const response = await fetch(`/api/geographic/territoires?province_id=${provinceId}`);
            const data = response.ok ? await response.json() : [];

            territoireSelect.innerHTML = '<option value="">Tous les territoires</option>';
            data.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                if (preserveSelection && String(preserveSelection) === String(item.id)) {
                    option.selected = true;
                }
                territoireSelect.appendChild(option);
            });
        };

        const loadCommunes = async (territoireId, preserveSelection = null) => {
            if (!territoireId) {
                resetCommunes();
                return;
            }

            const response = await fetch(`/api/geographic/communes?territoire_id=${territoireId}`);
            const data = response.ok ? await response.json() : [];

            communeSelect.innerHTML = '<option value="">Toutes les zones de santé</option>';
            data.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                if (preserveSelection && String(preserveSelection) === String(item.id)) {
                    option.selected = true;
                }
                communeSelect.appendChild(option);
            });
        };

        provinceSelect?.addEventListener('change', async function () {
            await loadTerritoires(this.value);
            resetCommunes();
        });

        territoireSelect?.addEventListener('change', async function () {
            await loadCommunes(this.value);
        });

        if (provinceSelect?.value) {
            loadTerritoires(provinceSelect.value, selectedTerritoireId).then(() => {
                if (territoireSelect.value) {
                    loadCommunes(territoireSelect.value, selectedCommuneId);
                }
            });
        } else if (selectedTerritoireId) {
            // Si une URL est partagée avec territoire/zone sans province, on n'invente pas la province ici.
            loadCommunes(selectedTerritoireId, selectedCommuneId);
        }
    })();
</script>
@endpush
