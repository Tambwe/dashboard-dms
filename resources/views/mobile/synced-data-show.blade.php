@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto p-4 md:p-8">
    <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-600">Mobile</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Visualiser formulaire synchronisé</h1>
            <p class="mt-1 text-sm text-gray-600">Consultez les informations et les réponses du formulaire dans une vue structurée.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($record->validation_status !== 'validated')
                <form method="POST" action="{{ route('mobile.synced-data.validate', ['source' => $normalizedSource, 'id' => $record->id]) }}" onsubmit="return confirm('Valider ce formulaire synchronisé ?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Valider</button>
                </form>
            @endif
            <a href="{{ route('mobile.synced-data.edit', ['source' => $normalizedSource, 'id' => $record->id]) }}" class="inline-flex items-center justify-center rounded-lg border border-primary-300 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">Éditer</a>
            <a href="{{ route('mobile.synced-data') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Retour</a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-7 shadow-sm space-y-6">
        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 md:p-5">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Informations générales</h2>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 text-sm">
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Source:</span> {{ $normalizedSource === 'mobile' ? 'Collecte mobile' : 'Questionnaire' }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">ID:</span> {{ $record->id }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Type:</span> {{ $normalizedSource === 'mobile' ? ($record->type ?? '-') : ($record->questionnaire->title ?? $record->questionnaire->code ?? '-') }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Date collecte:</span> {{ $normalizedSource === 'mobile' ? ($record->payload['date_collecte'] ?? '-') : optional($record->date_collecte)->format('Y-m-d') }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Province:</span> {{ $provinceMap[(int)(($normalizedSource === 'mobile' ? ($record->payload['province_id'] ?? 0) : ($record->province_id ?? 0)))] ?? '-' }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Territoire:</span> {{ $territoireMap[(int)(($normalizedSource === 'mobile' ? ($record->payload['territoire_id'] ?? 0) : ($record->territoire_id ?? 0)))] ?? '-' }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Commune:</span> {{ $communeMap[(int)(($normalizedSource === 'mobile' ? ($record->payload['commune_id'] ?? 0) : ($record->commune_id ?? 0)))] ?? '-' }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Site:</span> {{ $record->site ? ($record->site->nom.' ('.($record->site->code_site ?? 'N/A').')') : 'Site '.($record->site_id ?? '-') }}</div>
                <div class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                    <span class="font-semibold text-gray-700">Validation:</span>
                    <span class="{{ $record->validation_status === 'validated' ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $record->validation_status === 'validated' ? 'Validé' : 'En attente' }}
                    </span>
                </div>
                @if($record->validation_status === 'validated')
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Validé par:</span> {{ $record->validatedBy->name ?? '-' }}</div>
                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Date validation:</span> {{ optional($record->validated_at)->format('Y-m-d H:i') ?? '-' }}</div>
                @endif
            </div>
        </div>

        @if($normalizedSource === 'mobile' && ($record->type ?? null) === 'geography')
            @php
                $geographyEntry = $record->geographyEntry;
                $distances = is_array($geographyEntry?->polygon_segment_distances_m) ? $geographyEntry->polygon_segment_distances_m : [];
                $pointCount = $geographyEntry?->polygon_point_count;
                $segmentMin = $geographyEntry?->polygon_segment_min_m;
                $segmentMax = $geographyEntry?->polygon_segment_max_m;
                $segmentAvg = $geographyEntry?->polygon_segment_avg_m;
                $perimeter = $geographyEntry?->polygon_perimeter_m;
            @endphp
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 md:p-5">
                <h2 class="mb-4 text-base font-semibold text-emerald-900">Métriques de précision du polygone</h2>
                @if($geographyEntry && ($perimeter !== null || !empty($distances)))
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 text-sm">
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Points:</span> {{ $pointCount ?? '-' }}</div>
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Segments:</span> {{ count($distances) }}</div>
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Périmètre:</span> {{ $perimeter !== null ? number_format((float) $perimeter, 2, ',', ' ') . ' m' : '-' }}</div>
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Distance moyenne:</span> {{ $segmentAvg !== null ? number_format((float) $segmentAvg, 2, ',', ' ') . ' m' : '-' }}</div>
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Distance min:</span> {{ $segmentMin !== null ? number_format((float) $segmentMin, 2, ',', ' ') . ' m' : '-' }}</div>
                        <div class="rounded-lg border border-emerald-200 bg-white px-3 py-2"><span class="font-semibold text-gray-700">Distance max:</span> {{ $segmentMax !== null ? number_format((float) $segmentMax, 2, ',', ' ') . ' m' : '-' }}</div>
                    </div>
                    @if(!empty($distances))
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-white p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">Distances entre segments (m)</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($distances as $index => $distance)
                                    <span class="inline-flex items-center rounded-full border border-emerald-300 bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                        S{{ $index + 1 }}: {{ number_format((float) $distance, 2, ',', ' ') }} m
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Les métriques de précision ne sont pas encore disponibles pour cette collecte.
                    </div>
                @endif
            </div>
        @endif

        <div class="pt-1 border-t border-gray-100">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-900">Contenu du formulaire</h2>
                <p class="text-sm text-gray-600">Affichage structuré pour une lecture rapide des réponses.</p>
            </div>
            @if(empty($contentRows))
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    Aucune donnée de contenu disponible.
                </div>
            @elseif(!empty($groupedContentSections))
                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Choisir une partie du formulaire</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-show-section-target="all" class="rounded-full border border-primary-300 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Tout afficher</button>
                        @foreach($groupedContentSections as $sectionIndex => $section)
                            <button type="button" data-show-section-target="section-{{ $sectionIndex }}" class="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                                {{ $section['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($groupedContentSections as $sectionIndex => $section)
                        <div class="rounded-xl border border-gray-200 overflow-hidden" data-show-section-id="section-{{ $sectionIndex }}">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 md:px-5">
                                <p class="text-sm font-semibold text-gray-900">{{ $section['label'] }}</p>
                                @if(count($section['subgroups']) > 1)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button type="button" data-show-subgroup-target="all" data-show-subgroup-parent="section-{{ $sectionIndex }}" class="rounded-full border border-primary-300 bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700">Tous</button>
                                        @foreach($section['subgroups'] as $subIndex => $subgroup)
                                            <button type="button" data-show-subgroup-target="sub-{{ $subIndex }}" data-show-subgroup-parent="section-{{ $sectionIndex }}" class="rounded-full border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700 hover:bg-gray-100">
                                                {{ $subgroup['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-5 p-4 md:p-5">
                                @foreach($section['subgroups'] as $subIndex => $subgroup)
                                    <div data-show-subgroup-id="sub-{{ $subIndex }}" data-show-subgroup-parent="section-{{ $sectionIndex }}">
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $subgroup['label'] }}</p>
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            @foreach($subgroup['questions'] as $row)
                                                <div class="rounded-lg border border-gray-100 p-3 bg-white">
                                                    <p class="mb-1 text-sm font-medium text-gray-700">{{ $row['field'] }}</p>
                                                    @if($row['input_type'] === 'select_multiple')
                                                        <div class="space-y-1 rounded-lg border border-gray-200 p-2 max-h-48 overflow-auto">
                                                            @foreach(($row['options'] ?? []) as $option)
                                                                @php($selected = in_array((string)$option['value'], array_map('strval', $row['selected_values'] ?? []), true))
                                                                <label class="flex items-center gap-2 text-sm {{ $selected ? 'text-gray-900' : 'text-gray-500' }}">
                                                                    <input type="checkbox" disabled @checked($selected)>
                                                                    <span>{{ $option['label'] }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @elseif($row['input_type'] === 'select_one')
                                                        @php($selectedLabel = collect($row['options'] ?? [])->firstWhere('value', (string)($row['raw_value'] ?? ''))['label'] ?? ($row['raw_value'] ?: '-'))
                                                        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900">{{ $selectedLabel }}</p>
                                                    @else
                                                        <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900">{{ $row['value'] }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Champ</th>
                                <th class="px-3 py-2 text-left">Valeur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($contentRows as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['field'] }}</td>
                                    <td class="px-3 py-2 text-gray-900">{{ $row['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@if(!empty($groupedContentSections))
<script>
    (() => {
        const sectionButtons = Array.from(document.querySelectorAll('[data-show-section-target]'));
        const sectionCards = Array.from(document.querySelectorAll('[data-show-section-id]'));
        const subgroupButtons = Array.from(document.querySelectorAll('[data-show-subgroup-target]'));
        const subgroupCards = Array.from(document.querySelectorAll('[data-show-subgroup-id]'));

        sectionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-show-section-target');
                sectionCards.forEach((card) => {
                    const id = card.getAttribute('data-show-section-id');
                    card.style.display = (target === 'all' || id === target) ? '' : 'none';
                });
            });
        });

        subgroupButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const parent = button.getAttribute('data-show-subgroup-parent');
                const target = button.getAttribute('data-show-subgroup-target');
                subgroupCards.forEach((card) => {
                    const cardParent = card.getAttribute('data-show-subgroup-parent');
                    if (cardParent !== parent) {
                        return;
                    }
                    const id = card.getAttribute('data-show-subgroup-id');
                    card.style.display = (target === 'all' || id === target) ? '' : 'none';
                });
            });
        });
    })();
</script>
@endif
@endsection
