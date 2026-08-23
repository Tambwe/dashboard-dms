@extends('layouts.app')

@section('title', 'Historique des variations - Master List')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Historique des variations du site</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Détails complets des recensements validés et cumul des variations jusqu'à {{ $selectedPeriod->format('m/Y') }}
            </p>
        </div>
        <a href="{{ route('sites.master-list', $backQuery) }}" class="filter-button">
            Retour à la Master List
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-500 dark:text-gray-400">Site</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $site->nom }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Code: {{ $site->code_site }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Localisation</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $site->province->name ?? $site->province ?? '-' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $site->territoire->name ?? $site->territoire ?? '-' }} / {{ $site->commune->name ?? $site->zone_sante ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Cumul variation ménages</p>
                <p class="font-semibold {{ $cumulativeMenagesVariation >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $cumulativeMenagesVariation >= 0 ? '+' : '' }}{{ number_format($cumulativeMenagesVariation) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 dark:text-gray-400">Cumul variation individus</p>
                <p class="font-semibold {{ $cumulativeIndividusVariation >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $cumulativeIndividusVariation >= 0 ? '+' : '' }}{{ number_format($cumulativeIndividusVariation) }}
                </p>
            </div>
        </div>

        @if($latestRecensement)
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Dernier recensement pris en compte: {{ optional($latestRecensement->date_mouvement)->format('d/m/Y') }}
                @if(!empty($latestRecensement->periode))
                    (Période déclarée: {{ $latestRecensement->periode }})
                @endif
            </div>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Historique détaillé des recensements validés</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Période</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ménages</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Var. Ménages</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Var. Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cumul Var. Individus</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">F 0-5</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">F 6-17</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">F 18-59</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">F 60+</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">H 0-5</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">H 6-17</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">H 18-59</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">H 60+</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Détails</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recensements as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ optional($item->date_mouvement)->format('d/m/Y') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $item->periode ?: '-' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ number_format($item->menages ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right {{ $item->variation_menages >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">{{ $item->variation_menages >= 0 ? '+' : '' }}{{ number_format($item->variation_menages) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ number_format($item->individus ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right {{ $item->variation_individus >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">{{ $item->variation_individus >= 0 ? '+' : '' }}{{ number_format($item->variation_individus) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $item->cumul_variation_individus >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">{{ $item->cumul_variation_individus >= 0 ? '+' : '' }}{{ number_format($item->cumul_variation_individus) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->f_0_5 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->f_6_17 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->f_18_59 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->f_60_plus ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->h_0_5 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->h_6_17 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->h_18_59 ?? 0) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">{{ number_format($item->h_60_plus ?? 0) }}</td>
                            <td class="px-4 py-4 text-xs text-gray-500 dark:text-gray-400 min-w-[280px]">
                                <div><span class="font-medium">Statut:</span> {{ $item->statut ?? '-' }}</div>
                                <div><span class="font-medium">Raison:</span> {{ $item->raisonMouvement->libelle ?? $item->raison ?? '-' }}</div>
                                <div><span class="font-medium">Source:</span> {{ $item->source ?? '-' }} @if($item->round) · <span class="font-medium">Round:</span> {{ $item->round }} @endif</div>
                                <div><span class="font-medium">Saisi par:</span> {{ $item->createdBy->name ?? '-' }}</div>
                                <div><span class="font-medium">Validé par:</span> {{ $item->validatedBy->name ?? '-' }} @if($item->validated_at) le {{ $item->validated_at->format('d/m/Y H:i') }} @endif</div>
                                @if($item->description)
                                    <div class="mt-1"><span class="font-medium">Description:</span> {{ $item->description }}</div>
                                @endif
                                @if($item->rejection_reason)
                                    <div class="mt-1 text-red-700 dark:text-red-300"><span class="font-medium">Motif de rejet:</span> {{ $item->rejection_reason }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun recensement validé trouvé pour ce site sur la période sélectionnée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
