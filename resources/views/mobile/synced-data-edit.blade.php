@extends('layouts.app')

@section('content')
<div class="max-w-[1400px] mx-auto p-4 md:p-8">
    <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary-600">Mobile</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Éditer formulaire synchronisé</h1>
            <p class="mt-1 text-sm text-gray-600">Modifiez les informations générales et les réponses du formulaire dans une vue unifiée.</p>
        </div>
        <a href="{{ route('mobile.synced-data') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Retour</a>
    </div>

    <form method="POST" action="{{ route('mobile.synced-data.update', ['source' => $normalizedSource, 'id' => $record->id]) }}" class="rounded-2xl border border-gray-200 bg-white p-5 md:p-7 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 md:p-5">
            <h2 class="mb-4 text-base font-semibold text-gray-900">Informations générales</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Site</label>
                <select name="site_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((int)old('site_id', $record->site_id) === (int)$site->id)>
                            {{ $site->nom }} ({{ $site->code_site ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
                @error('site_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Date collecte</label>
                <input type="date" name="date_collecte" value="{{ old('date_collecte', $normalizedSource === 'mobile' ? ($record->payload['date_collecte'] ?? null) : optional($record->date_collecte)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('date_collecte')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Province</label>
                <select name="province_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">--</option>
                    @foreach($provinceMap as $id => $name)
                        <option value="{{ $id }}" @selected((string)old('province_id', $normalizedSource === 'mobile' ? ($record->payload['province_id'] ?? null) : $record->province_id) === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('province_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Territoire</label>
                <select name="territoire_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">--</option>
                    @foreach($territoireMap as $id => $name)
                        <option value="{{ $id }}" @selected((string)old('territoire_id', $normalizedSource === 'mobile' ? ($record->payload['territoire_id'] ?? null) : $record->territoire_id) === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('territoire_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Commune</label>
                <select name="commune_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">--</option>
                    @foreach($communeMap as $id => $name)
                        <option value="{{ $id }}" @selected((string)old('commune_id', $normalizedSource === 'mobile' ? ($record->payload['commune_id'] ?? null) : $record->commune_id) === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('commune_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-100 p-4 md:p-5">
            @php($oldContent = old('content', []))
            @php($oldContentMulti = old('content_multi', []))
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-900">Contenu du formulaire</h2>
                <p class="text-sm text-gray-600">Les réponses sont affichées par groupes pour faciliter la lecture et l’édition.</p>
            </div>
            @if(empty($contentRows))
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    Aucune donnée de contenu disponible.
                </div>
            @elseif(!empty($groupedContentSections))
                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Choisir une partie du formulaire</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-edit-section-target="all" class="rounded-full border border-primary-300 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Tout afficher</button>
                        @foreach($groupedContentSections as $sectionIndex => $section)
                            <button type="button" data-edit-section-target="section-{{ $sectionIndex }}" class="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                                {{ $section['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($groupedContentSections as $sectionIndex => $section)
                        <div class="rounded-xl border border-gray-200 overflow-hidden" data-edit-section-id="section-{{ $sectionIndex }}">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 md:px-5">
                                <p class="text-sm font-semibold text-gray-900">{{ $section['label'] }}</p>
                                @if(count($section['subgroups']) > 1)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button type="button" data-edit-subgroup-target="all" data-edit-subgroup-parent="section-{{ $sectionIndex }}" class="rounded-full border border-primary-300 bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700">Tous</button>
                                        @foreach($section['subgroups'] as $subIndex => $subgroup)
                                            <button type="button" data-edit-subgroup-target="sub-{{ $subIndex }}" data-edit-subgroup-parent="section-{{ $sectionIndex }}" class="rounded-full border border-gray-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700 hover:bg-gray-100">
                                                {{ $subgroup['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-5 p-4 md:p-5">
                                @foreach($section['subgroups'] as $subIndex => $subgroup)
                                    <div data-edit-subgroup-id="sub-{{ $subIndex }}" data-edit-subgroup-parent="section-{{ $sectionIndex }}">
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $subgroup['label'] }}</p>
                                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                            @foreach($subgroup['questions'] as $row)
                                                @php($currentValue = array_key_exists($row['path'], $oldContent) ? $oldContent[$row['path']] : $row['raw_value'])
                                                @php($currentValues = array_key_exists($row['path'], $oldContentMulti) && is_array($oldContentMulti[$row['path']]) ? $oldContentMulti[$row['path']] : ($row['selected_values'] ?? []))
                                                <div class="rounded-lg border border-gray-100 p-3 bg-white">
                                                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ $row['field'] }}</label>
                                                    <input type="hidden" name="content_types[{{ $row['path'] }}]" value="{{ $row['input_type'] }}">
                                                    @if($row['input_type'] === 'select_one')
                                                        <select
                                                            name="content[{{ $row['path'] }}]"
                                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                                        >
                                                            <option value="">-- Sélectionner --</option>
                                                            @foreach(($row['options'] ?? []) as $option)
                                                                <option value="{{ $option['value'] }}" @selected((string)$currentValue === (string)$option['value'])>{{ $option['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($row['input_type'] === 'select_multiple')
                                                        <div class="space-y-1 rounded-lg border border-gray-200 p-2 max-h-48 overflow-auto">
                                                            @foreach(($row['options'] ?? []) as $option)
                                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                                    <input
                                                                        type="checkbox"
                                                                        name="content_multi[{{ $row['path'] }}][]"
                                                                        value="{{ $option['value'] }}"
                                                                        @checked(in_array((string)$option['value'], array_map('strval', $currentValues), true))
                                                                    >
                                                                    <span>{{ $option['label'] }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @elseif($row['input_type'] === 'boolean')
                                                        <select
                                                            name="content[{{ $row['path'] }}]"
                                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                                        >
                                                            <option value="1" @selected((string)$currentValue === '1')>Oui</option>
                                                            <option value="0" @selected((string)$currentValue === '0')>Non</option>
                                                        </select>
                                                    @elseif($row['input_type'] === 'number')
                                                        <input
                                                            type="number"
                                                            step="any"
                                                            name="content[{{ $row['path'] }}]"
                                                            value="{{ $currentValue }}"
                                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                                        >
                                                    @else
                                                        <input
                                                            type="text"
                                                            name="content[{{ $row['path'] }}]"
                                                            value="{{ $currentValue }}"
                                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                                        >
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
                <div class="max-h-[65vh] overflow-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Champ</th>
                                <th class="px-3 py-2 text-left">Valeur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($contentRows as $row)
                                @php($currentValue = array_key_exists($row['path'], $oldContent) ? $oldContent[$row['path']] : $row['raw_value'])
                                <tr>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['field'] }}</td>
                                    <td class="px-3 py-2 text-gray-900">
                                        <input type="hidden" name="content_types[{{ $row['path'] }}]" value="{{ $row['input_type'] }}">
                                        @if($row['input_type'] === 'boolean')
                                            <select
                                                name="content[{{ $row['path'] }}]"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                            >
                                                <option value="1" @selected((string)$currentValue === '1')>Oui</option>
                                                <option value="0" @selected((string)$currentValue === '0')>Non</option>
                                            </select>
                                        @elseif($row['input_type'] === 'number')
                                            <input
                                                type="number"
                                                step="any"
                                                name="content[{{ $row['path'] }}]"
                                                value="{{ $currentValue }}"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                            >
                                        @else
                                            <input
                                                type="text"
                                                name="content[{{ $row['path'] }}]"
                                                value="{{ $currentValue }}"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                            >
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="sticky bottom-0 z-10 -mx-5 -mb-5 border-t border-gray-200 bg-white/95 backdrop-blur px-5 py-4 md:-mx-7 md:-mb-7 md:px-7">
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('mobile.synced-data.show', ['source' => $normalizedSource, 'id' => $record->id]) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Annuler</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Enregistrer modifications</button>
            </div>
        </div>
    </form>
</div>

@if(!empty($groupedContentSections))
<script>
    (() => {
        const sectionButtons = Array.from(document.querySelectorAll('[data-edit-section-target]'));
        const sectionCards = Array.from(document.querySelectorAll('[data-edit-section-id]'));
        const subgroupButtons = Array.from(document.querySelectorAll('[data-edit-subgroup-target]'));
        const subgroupCards = Array.from(document.querySelectorAll('[data-edit-subgroup-id]'));

        sectionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-edit-section-target');
                sectionCards.forEach((card) => {
                    const id = card.getAttribute('data-edit-section-id');
                    card.style.display = (target === 'all' || id === target) ? '' : 'none';
                });
            });
        });

        subgroupButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const parent = button.getAttribute('data-edit-subgroup-parent');
                const target = button.getAttribute('data-edit-subgroup-target');
                subgroupCards.forEach((card) => {
                    const cardParent = card.getAttribute('data-edit-subgroup-parent');
                    if (cardParent !== parent) {
                        return;
                    }
                    const id = card.getAttribute('data-edit-subgroup-id');
                    card.style.display = (target === 'all' || id === target) ? '' : 'none';
                });
            });
        });
    })();
</script>
@endif
@endsection
