@php
    $isEdit    = isset($project);
    $oldStatus = old('status', $isEdit ? $project->status : 'planifie');
    $oldDonors = old('donors', $isEdit ? ($project->donors_json ?? []) : []);
    $oldZones  = old('execution_zones', $isEdit
        ? $project->executionZones->map(fn($z) => [
            'province_id'      => $z->province_id,
            'territoire_id'    => $z->territoire_id,
            'commune_id'       => $z->commune_id,
            '_territoire_name' => optional($z->territoire)->name,
            '_commune_name'    => optional($z->commune)->name,
          ])->toArray()
        : []);

        $oldActivities = old('activities', $isEdit
                ? $project->activities->map(fn($a) => [
                        'activity_name' => $a->activity_name,
                'program_indicator_id' => $a->program_indicator_id,
                'program_activity_id' => $a->program_activity_id,
                'program_sub_activity_ids' => $a->program_sub_activity_id ? [$a->program_sub_activity_id] : [],
                        'activity_cost' => $a->activity_cost,
                        'site_id' => $a->site_id,
                        '_site_name' => optional($a->site)->nom,
                        'province_id' => $a->province_id,
                        'territoire_id' => $a->territoire_id,
                        'commune_id' => $a->commune_id,
                        'statut_beneficiaire' => collect(preg_split('/\s*,\s*/', (string) ($a->statut_beneficiaire ?? ''), -1, PREG_SPLIT_NO_EMPTY))->values()->all(),
                        'girls_0_17' => $a->girls_0_17,
                        'girls_18_59' => $a->girls_18_59,
                        'girls_60_plus' => $a->girls_60_plus,
                        'boys_0_17' => $a->boys_0_17,
                        'boys_18_59' => $a->boys_18_59,
                        'boys_60_plus' => $a->boys_60_plus,
                        'persons_with_disabilities' => $a->persons_with_disabilities,
                        'comment' => $a->comment,
                        'reporting_date' => optional($a->reporting_date)->format('Y-m-d'),
                    ])->toArray()
                : []);
    $oldClusterId = old('cluster_id', $isEdit ? $project->cluster_id : null);
@endphp

{{-- ── INFOS GÉNÉRALES ──────────────────────────────────────── --}}
<div class="space-y-4">
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">Informations générales</h3>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du projet <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $isEdit ? $project->name : '') }}" required
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Cluster --}}
    <div>
        <label for="cluster_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Cluster <span class="text-red-500">*</span>
        </label>
        <select name="cluster_id" id="cluster_id" required
                onchange="dmsClusterChange(this)"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 @error('cluster_id') border-red-500 @enderror">
            <option value="">-- Sélectionnez un cluster --</option>
            @foreach($programHierarchyByCluster as $cluster)
                <option value="{{ $cluster['id'] }}" {{ (string)$oldClusterId === (string)$cluster['id'] ? 'selected' : '' }}>
                    {{ $cluster['code'] }} – {{ $cluster['name'] }}
                </option>
            @endforeach
        </select>
        @error('cluster_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code projet</label>
            <input type="text" name="code" id="code" value="{{ old('code', $isEdit ? $project->code : '') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 @error('code') border-red-500 @enderror">
            @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut <span class="text-red-500">*</span></label>
            <select name="status" id="status" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 @error('status') border-red-500 @enderror">
                @foreach($availableStatuses as $s)
                    <option value="{{ $s->code }}" {{ $oldStatus === $s->code ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début</label>
            <input type="date" name="start_date" id="start_date"
                   value="{{ old('start_date', $isEdit && $project->start_date ? $project->start_date->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin</label>
            <input type="date" name="end_date" id="end_date"
                   value="{{ old('end_date', $isEdit && $project->end_date ? $project->end_date->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            @error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">{{ old('description', $isEdit ? $project->description : '') }}</textarea>
    </div>
</div>

{{-- ── FINANCEMENT & BAILLEURS ──────────────────────────────── --}}
<div class="space-y-4 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">Financement</h3>

    <div>
        <label for="funding_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant du financement (USD)</label>
        <input type="number" name="funding_amount" id="funding_amount" min="0" step="0.01"
               value="{{ old('funding_amount', $isEdit ? $project->funding_amount : '') }}"
               placeholder="0.00"
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 @error('funding_amount') border-red-500 @enderror">
        @error('funding_amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bailleurs de fonds</label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Saisissez un bailleur par ligne. Cliquez sur « + » pour en ajouter plusieurs.</p>
        <div id="donors-container" class="space-y-2">
            @forelse($oldDonors as $donor)
            <div class="donor-row flex gap-2">
                <input type="text" name="donors[]" value="{{ $donor }}"
                       placeholder="Ex : ECHO, UNICEF, USAID…"
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                <button type="button" onclick="dmsDonorRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @empty
            <div class="donor-row flex gap-2">
                <input type="text" name="donors[]" value="" placeholder="Ex : ECHO, UNICEF, USAID…"
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                <button type="button" onclick="dmsDonorRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endforelse
        </div>
        <button type="button" onclick="dmsDonorAdd()"
                class="mt-2 inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Ajouter un bailleur
        </button>
    </div>
</div>

{{-- ── BÉNÉFICIAIRES ─────────────────────────────────────────── --}}
<div class="space-y-3 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">Bénéficiaires (désagrégés par sexe et âge)</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700">
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tranche d'âge</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-pink-600 dark:text-pink-400 uppercase">Filles / Femmes</th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Garçons / Hommes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">0 – 17 ans</td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-pink-500 mb-1">Filles</span>
                        <input type="number" name="beneficiaries_female_0_17" min="0"
                               value="{{ old('beneficiaries_female_0_17', $isEdit ? $project->beneficiaries_female_0_17 : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-blue-500 mb-1">Garçons</span>
                        <input type="number" name="beneficiaries_male_0_17" min="0"
                               value="{{ old('beneficiaries_male_0_17', $isEdit ? $project->beneficiaries_male_0_17 : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                </tr>
                <tr class="bg-gray-50/50 dark:bg-gray-700/30">
                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">18 – 59 ans</td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-pink-500 mb-1">Femmes</span>
                        <input type="number" name="beneficiaries_female_18_59" min="0"
                               value="{{ old('beneficiaries_female_18_59', $isEdit ? $project->beneficiaries_female_18_59 : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-blue-500 mb-1">Hommes</span>
                        <input type="number" name="beneficiaries_male_18_59" min="0"
                               value="{{ old('beneficiaries_male_18_59', $isEdit ? $project->beneficiaries_male_18_59 : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                </tr>
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-700 dark:text-gray-300">60 ans et plus</td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-pink-500 mb-1">Femmes</span>
                        <input type="number" name="beneficiaries_female_60_plus" min="0"
                               value="{{ old('beneficiaries_female_60_plus', $isEdit ? $project->beneficiaries_female_60_plus : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="block text-xs text-blue-500 mb-1">Hommes</span>
                        <input type="number" name="beneficiaries_male_60_plus" min="0"
                               value="{{ old('beneficiaries_male_60_plus', $isEdit ? $project->beneficiaries_male_60_plus : 0) }}"
                               class="w-24 text-center rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── ZONES D'EXÉCUTION ─────────────────────────────────────── --}}
<div class="space-y-3 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">Zones d'exécution</h3>
    @error('execution_zones')<p class="mb-1 text-xs text-red-600">{{ $message }}</p>@enderror

    <div id="zones-list" class="space-y-3">
        @forelse($oldZones as $i => $zone)
        <div class="zone-row grid grid-cols-3 gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <select name="execution_zones[{{ $i }}][province_id]"
                        class="zone-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                        data-idx="{{ $i }}" onchange="dmsZoneProvince(this)">
                    <option value="">-- Province --</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p->id }}" {{ (int)($zone['province_id']??0)===$p->id?'selected':'' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
                <select name="execution_zones[{{ $i }}][territoire_id]"
                        id="t-{{ $i }}"
                        class="zone-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                        data-idx="{{ $i }}" onchange="dmsZoneTerritoire(this)">
                    <option value="">-- Territoire --</option>
                    @if(!empty($zone['territoire_id']))
                        <option value="{{ $zone['territoire_id'] }}" selected>{{ $zone['_territoire_name'] ?? $zone['territoire_id'] }}</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Commune</label>
                <div class="flex gap-1">
                    <select name="execution_zones[{{ $i }}][commune_id]"
                            id="c-{{ $i }}"
                            class="zone-commune flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="">-- Commune --</option>
                        @if(!empty($zone['commune_id']))
                            <option value="{{ $zone['commune_id'] }}" selected>{{ $zone['_commune_name'] ?? $zone['commune_id'] }}</option>
                        @endif
                    </select>
                    <button type="button" onclick="dmsZoneRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="zone-row grid grid-cols-3 gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <select name="execution_zones[0][province_id]"
                        class="zone-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                        data-idx="0" onchange="dmsZoneProvince(this)">
                    <option value="">-- Province --</option>
                    @foreach($provinces as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
                <select name="execution_zones[0][territoire_id]" id="t-0"
                        class="zone-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm"
                        data-idx="0" onchange="dmsZoneTerritoire(this)">
                    <option value="">-- Territoire --</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Commune</label>
                <div class="flex gap-1">
                    <select name="execution_zones[0][commune_id]" id="c-0"
                            class="zone-commune flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                        <option value="">-- Commune --</option>
                    </select>
                    <button type="button" onclick="dmsZoneRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <button type="button" onclick="dmsZoneAdd()"
            class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Ajouter une zone
    </button>
    <input type="hidden" id="zone-count-seed" value="{{ count($oldZones) > 0 ? count($oldZones) : 1 }}">
</div>

{{-- ── ACTIVITES DU PROJET ─────────────────────────────────── --}}
@if($isEdit)
<div class="space-y-3 pt-4">
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 pb-1">Activites menees</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400">Ajoutez les activites menees, leur cout, le lieu d'execution, la desagregation beneficiaries, handicap, commentaire et date de rapportage.</p>
    @error('activities')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    @error('activities.*.activity_name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

    <div id="activities-list" class="space-y-4">
        @php
            $activitiesSeed = count($oldActivities) ? $oldActivities : [[
                'activity_name' => '',
                'program_indicator_id' => '',
                'program_activity_id' => '',
                'program_sub_activity_ids' => [],
                'activity_cost' => '',
                'site_id' => '',
                'province_id' => '',
                'territoire_id' => '',
                'commune_id' => '',
                'statut_beneficiaire' => [],
                'girls_0_17' => 0,
                'girls_18_59' => 0,
                'girls_60_plus' => 0,
                'boys_0_17' => 0,
                'boys_18_59' => 0,
                'boys_60_plus' => 0,
                'persons_with_disabilities' => 0,
                'comment' => '',
                'reporting_date' => '',
            ]];
        @endphp

        @foreach($activitiesSeed as $i => $activity)
        <div class="activity-row rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3" data-idx="{{ $i }}">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activite #{{ $i + 1 }}</p>
                <button type="button" onclick="dmsActivityRemove(this)" class="px-2 py-1 text-red-500 hover:text-red-700 text-sm">Retirer</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Indicateur</label>
                    <select name="activities[{{ $i }}][program_indicator_id]" id="program-ind-{{ $i }}" class="program-indicator w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" data-selected="{{ $activity['program_indicator_id'] ?? '' }}" onchange="dmsProgramIndicator(this)">
                        <option value="">-- Indicateur --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Activite du cadre</label>
                    <select name="activities[{{ $i }}][program_activity_id]" id="program-act-{{ $i }}" class="program-activity w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" data-selected="{{ $activity['program_activity_id'] ?? '' }}" onchange="dmsProgramActivity(this)">
                        <option value="">-- Activite --</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sous-activites (selection multiple)</label>
                    <select name="activities[{{ $i }}][program_sub_activity_ids][]" id="program-sub-{{ $i }}" class="program-subactivities w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" data-selected="{{ implode(',', $activity['program_sub_activity_ids'] ?? []) }}" multiple size="4">
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maintenez Ctrl (ou Cmd) pour choisir plusieurs sous-activites.</p>
                </div>
                <input type="hidden" name="activities[{{ $i }}][activity_name]" value="{{ $activity['activity_name'] ?? '' }}">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cout de l'activite (USD)</label>
                    <input type="number" min="0" step="0.01" name="activities[{{ $i }}][activity_cost]" value="{{ $activity['activity_cost'] ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Statut beneficiaire</label>
                    @php $statusValues = (array) ($activity['statut_beneficiaire'] ?? []); @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2 border border-gray-200 dark:border-gray-600 rounded-lg">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[{{ $i }}][statut_beneficiaire][]" value="pdi" {{ in_array('pdi', $statusValues, true) ? 'checked' : '' }}>PDI</label>
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[{{ $i }}][statut_beneficiaire][]" value="retourne" {{ in_array('retourne', $statusValues, true) ? 'checked' : '' }}>Retourne(e)</label>
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[{{ $i }}][statut_beneficiaire][]" value="refugie" {{ in_array('refugie', $statusValues, true) ? 'checked' : '' }}>Refugie(e)</label>
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[{{ $i }}][statut_beneficiaire][]" value="communaute_hote" {{ in_array('communaute_hote', $statusValues, true) ? 'checked' : '' }}>Communaute hote</label>
                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[{{ $i }}][statut_beneficiaire][]" value="autre" {{ in_array('autre', $statusValues, true) ? 'checked' : '' }}>Autre</label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Site</label>
                    <select name="activities[{{ $i }}][site_id]" id="activity-s-{{ $i }}" class="activity-site w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-selected="{{ $activity['site_id'] ?? '' }}">
                        <option value="">-- Site --</option>
                        @if(!empty($activity['site_id']))
                            <option value="{{ $activity['site_id'] }}" selected>{{ $activity['_site_name'] ?? 'Selection actuelle' }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                    <select name="activities[{{ $i }}][province_id]" class="activity-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" onchange="dmsActivityProvince(this)">
                        <option value="">-- Province --</option>
                        @foreach($provinces as $p)
                            <option value="{{ $p->id }}" {{ (string)($activity['province_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
                    <select name="activities[{{ $i }}][territoire_id]" id="activity-t-{{ $i }}" class="activity-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" onchange="dmsActivityTerritoire(this)">
                        <option value="{{ $activity['territoire_id'] ?? '' }}">{{ $activity['territoire_id'] ? 'Selection actuelle' : '-- Territoire --' }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zone de sante</label>
                    <select name="activities[{{ $i }}][commune_id]" id="activity-c-{{ $i }}" class="activity-commune w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="{{ $i }}" onchange="dmsActivityCommune(this)">
                        <option value="{{ $activity['commune_id'] ?? '' }}">{{ $activity['commune_id'] ? 'Selection actuelle' : '-- Zone de sante --' }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                <div><label class="block text-xs text-gray-500 mb-1">Filles 0-17</label><input type="number" min="0" name="activities[{{ $i }}][girls_0_17]" value="{{ $activity['girls_0_17'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Femmes 18-59</label><input type="number" min="0" name="activities[{{ $i }}][girls_18_59]" value="{{ $activity['girls_18_59'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Femmes 60+</label><input type="number" min="0" name="activities[{{ $i }}][girls_60_plus]" value="{{ $activity['girls_60_plus'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Garcons 0-17</label><input type="number" min="0" name="activities[{{ $i }}][boys_0_17]" value="{{ $activity['boys_0_17'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Hommes 18-59</label><input type="number" min="0" name="activities[{{ $i }}][boys_18_59]" value="{{ $activity['boys_18_59'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Hommes 60+</label><input type="number" min="0" name="activities[{{ $i }}][boys_60_plus]" value="{{ $activity['boys_60_plus'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Personnes en situation de handicap</label>
                    <input type="number" min="0" name="activities[{{ $i }}][persons_with_disabilities]" value="{{ $activity['persons_with_disabilities'] ?? 0 }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date rapportage</label>
                    <input type="date" name="activities[{{ $i }}][reporting_date]" value="{{ $activity['reporting_date'] ?? '' }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Commentaire</label>
                <textarea name="activities[{{ $i }}][comment]" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ $activity['comment'] ?? '' }}</textarea>
            </div>
        </div>
        @endforeach
    </div>

    <button type="button" onclick="dmsActivityAdd()" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Ajouter une activite
    </button>
    <input type="hidden" id="activity-count-seed" value="{{ count($oldActivities) > 0 ? count($oldActivities) : 1 }}">
</div>
@endif

{{-- ── BOUTONS ──────────────────────────────────────────────── --}}
<div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
    <a href="{{ route('organisation.projects.index') }}"
       class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm transition-colors">
        Annuler
    </a>
    <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg shadow-sm text-sm font-medium transition-colors">
        {{ $submitLabel }}
    </button>
</div>

{{-- ── JAVASCRIPT ───────────────────────────────────────────── --}}
<script type="application/json" id="program-hierarchy-by-cluster-json">@json($programHierarchyByCluster)</script>
<script>
(function () {
    const _clusterJson = document.getElementById('program-hierarchy-by-cluster-json');
    let _programHierarchyByCluster = [];
    try {
        _programHierarchyByCluster = _clusterJson ? JSON.parse(_clusterJson.textContent || '[]') : [];
    } catch (e) {
        _programHierarchyByCluster = [];
    }

    // Active indicators for the currently selected cluster
    let _programHierarchy = [];

    function dmsSetClusterIndicators(clusterId) {
        const cluster = _programHierarchyByCluster.find(c => String(c.id) === String(clusterId || ''));
        _programHierarchy = cluster ? cluster.indicators : [];
        document.querySelectorAll('.activity-row').forEach(row => {
            const idx = row.dataset.idx;
            const indSel = document.getElementById('program-ind-' + idx);
            const actSel = document.getElementById('program-act-' + idx);
            const subSel = document.getElementById('program-sub-' + idx);
            const prevInd = indSel ? indSel.value : '';
            const prevAct = actSel ? actSel.value : '';
            const prevSubs = subSel ? Array.from(subSel.selectedOptions).map(o => o.value) : [];
            dmsPopulateIndicators(idx, prevInd);
            dmsPopulateActivities(idx, prevInd, prevAct);
            dmsPopulateSubActivities(idx, prevAct, prevSubs);
        });
    }

    window.dmsClusterChange = function (sel) {
        dmsSetClusterIndicators(sel.value);
    };

    function dmsPopulateIndicators(idx, selectedIndicatorId) {
        const indicatorSelect = document.getElementById('program-ind-' + idx);
        if (!indicatorSelect) return;

        indicatorSelect.innerHTML = '<option value="">-- Indicateur --</option>';
        _programHierarchy.forEach((indicator) => {
            const option = document.createElement('option');
            option.value = indicator.id;
            option.textContent = (indicator.code ? indicator.code + ' - ' : '') + indicator.label;
            if (String(indicator.id) === String(selectedIndicatorId || '')) {
                option.selected = true;
            }
            indicatorSelect.appendChild(option);
        });
    }

    function dmsPopulateActivities(idx, indicatorId, selectedActivityId) {
        const activitySelect = document.getElementById('program-act-' + idx);
        if (!activitySelect) return;

        activitySelect.innerHTML = '<option value="">-- Activite --</option>';
        const indicator = _programHierarchy.find((item) => String(item.id) === String(indicatorId || ''));
        if (!indicator) return;

        indicator.activities.forEach((activity) => {
            const option = document.createElement('option');
            option.value = activity.id;
            option.textContent = (activity.code ? activity.code + ' - ' : '') + activity.label;
            if (String(activity.id) === String(selectedActivityId || '')) {
                option.selected = true;
            }
            activitySelect.appendChild(option);
        });
    }

    function dmsPopulateSubActivities(idx, activityId, selectedSubIds) {
        const subSelect = document.getElementById('program-sub-' + idx);
        if (!subSelect) return;

        subSelect.innerHTML = '';

        let selectedValues = selectedSubIds || [];
        if (!Array.isArray(selectedValues)) {
            selectedValues = [selectedValues];
        }
        selectedValues = selectedValues.map((value) => String(value));

        const indicatorSelect = document.getElementById('program-ind-' + idx);
        const indicator = _programHierarchy.find((item) => String(item.id) === String(indicatorSelect ? indicatorSelect.value : ''));
        const activity = indicator
            ? indicator.activities.find((item) => String(item.id) === String(activityId || ''))
            : null;

        if (!activity) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = '-- Sous-activites --';
            subSelect.appendChild(option);
            return;
        }

        activity.sub_activities.forEach((sub) => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = (sub.code ? sub.code + ' - ' : '') + sub.label;
            if (selectedValues.includes(String(sub.id))) {
                option.selected = true;
            }
            subSelect.appendChild(option);
        });

        dmsEnableMultiSelectToggle(subSelect);
    }

    function dmsEnableMultiSelectToggle(select) {
        if (!select || select.dataset.multiToggleBound === '1') return;
        select.dataset.multiToggleBound = '1';

        select.addEventListener('mousedown', function (event) {
            if (event.target.tagName !== 'OPTION') return;
            event.preventDefault();

            const option = event.target;
            option.selected = !option.selected;
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    window.dmsProgramIndicator = function (sel) {
        const idx = sel.dataset.idx;
        dmsPopulateActivities(idx, sel.value, '');
        dmsPopulateSubActivities(idx, '', []);
    };

    window.dmsProgramActivity = function (sel) {
        const idx = sel.dataset.idx;
        dmsPopulateSubActivities(idx, sel.value, []);
    };

    function dmsHydrateProgramCascade(row) {
        const idx = row.dataset.idx;
        const indicatorInput = row.querySelector('select[name="activities[' + idx + '][program_indicator_id]"]');
        const activityInput = row.querySelector('select[name="activities[' + idx + '][program_activity_id]"]');
        const subInput = row.querySelector('select[name="activities[' + idx + '][program_sub_activity_ids][]"]');
        if (!indicatorInput || !activityInput || !subInput) return;

        const selectedIndicator = indicatorInput.getAttribute('data-selected') || indicatorInput.value || '';
        const selectedActivity = activityInput.getAttribute('data-selected') || activityInput.value || '';
        const selectedSubs = Array.from(subInput.options)
            .filter((opt) => opt.selected)
            .map((opt) => opt.value)
            .concat((subInput.getAttribute('data-selected') || '').split(',').filter(Boolean));

        dmsPopulateIndicators(idx, selectedIndicator);
        dmsPopulateActivities(idx, selectedIndicator, selectedActivity);
        dmsPopulateSubActivities(idx, selectedActivity, selectedSubs);
    }
    /* ---- Bailleurs ---- */
    window.dmsDonorAdd = function () {
        const c = document.getElementById('donors-container');
        const d = document.createElement('div');
        d.className = 'donor-row flex gap-2';
        d.innerHTML = '<input type="text" name="donors[]" placeholder="Ex : ECHO, UNICEF…" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">'
            + '<button type="button" onclick="dmsDonorRemove(this)" class="px-2 text-red-500 hover:text-red-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
        c.appendChild(d);
    };
    window.dmsDonorRemove = function (btn) {
        const c = document.getElementById('donors-container');
        const row = btn.closest('.donor-row');
        if (c.querySelectorAll('.donor-row').length > 1) row.remove();
        else row.querySelector('input').value = '';
    };

    /* ---- Zones géographiques ---- */
    let _zoneIdx = Number((document.getElementById('zone-count-seed') || { value: 1 }).value || 1);
    const _provincesHtml = `@foreach($provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;

    window.dmsZoneAdd = function () {
        const list = document.getElementById('zones-list');
        const i = _zoneIdx++;
        const div = document.createElement('div');
        div.className = 'zone-row grid grid-cols-3 gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600';
        div.innerHTML = `
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
            <select name="execution_zones[${i}][province_id]" class="zone-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm" data-idx="${i}" onchange="dmsZoneProvince(this)">
                <option value="">-- Province --</option>${_provincesHtml}</select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
            <select name="execution_zones[${i}][territoire_id]" id="t-${i}" class="zone-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm" data-idx="${i}" onchange="dmsZoneTerritoire(this)">
                <option value="">-- Territoire --</option></select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">Commune</label>
            <div class="flex gap-1">
                <select name="execution_zones[${i}][commune_id]" id="c-${i}" class="zone-commune flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 text-sm">
                    <option value="">-- Commune --</option></select>
                <button type="button" onclick="dmsZoneRemove(this)" class="px-2 text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div></div>`;
        list.appendChild(div);
    };

    window.dmsZoneRemove = function (btn) {
        const list = document.getElementById('zones-list');
        const row = btn.closest('.zone-row');
        if (list.querySelectorAll('.zone-row').length > 1) row.remove();
    };

    window.dmsZoneProvince = function (sel) {
        const idx = sel.dataset.idx;
        const tSel = document.getElementById('t-' + idx);
        const cSel = document.getElementById('c-' + idx);
        tSel.innerHTML = '<option value="">-- Territoire --</option>';
        if (cSel) cSel.innerHTML = '<option value="">-- Commune --</option>';
        if (!sel.value) return;
        fetch('/api/geographic/territoires?province_id=' + sel.value)
            .then(r => r.json()).then(data => {
                (data.data ?? data).forEach(t => {
                    const o = document.createElement('option');
                    o.value = t.id; o.textContent = t.name;
                    tSel.appendChild(o);
                });
            }).catch(() => {});
    };

    window.dmsZoneTerritoire = function (sel) {
        const idx = sel.dataset.idx;
        const cSel = document.getElementById('c-' + idx);
        cSel.innerHTML = '<option value="">-- Commune --</option>';
        if (!sel.value) return;
        fetch('/api/geographic/communes?territoire_id=' + sel.value)
            .then(r => r.json()).then(data => {
                (data.data ?? data).forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id; o.textContent = c.name;
                    cSel.appendChild(o);
                });
            }).catch(() => {});
    };

    /* ---- Activites ---- */
    let _activityIdx = Number((document.getElementById('activity-count-seed') || { value: 1 }).value || 1);
    const _activityProvinces = `@foreach($provinces as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;
    window.dmsActivityAdd = function () {
        const list = document.getElementById('activities-list');
        const i = _activityIdx++;
        const div = document.createElement('div');
        div.className = 'activity-row rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3';
        div.dataset.idx = i;
        div.innerHTML = `
            <div class="flex items-center justify-between"><p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activite #${i + 1}</p><button type="button" onclick="dmsActivityRemove(this)" class="px-2 py-1 text-red-500 hover:text-red-700 text-sm">Retirer</button></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Indicateur</label><select name="activities[${i}][program_indicator_id]" id="program-ind-${i}" class="program-indicator w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="${i}" onchange="dmsProgramIndicator(this)"><option value="">-- Indicateur --</option></select></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Activite du cadre</label><select name="activities[${i}][program_activity_id]" id="program-act-${i}" class="program-activity w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="${i}" onchange="dmsProgramActivity(this)"><option value="">-- Activite --</option></select></div>
                            <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">Sous-activites (selection multiple)</label><select name="activities[${i}][program_sub_activity_ids][]" id="program-sub-${i}" class="program-subactivities w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" multiple size="4"></select><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maintenez Ctrl (ou Cmd) pour choisir plusieurs sous-activites.</p></div>
                            <input type="hidden" name="activities[${i}][activity_name]" value="">
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Cout de l'activite (USD)</label><input type="number" min="0" step="0.01" name="activities[${i}][activity_cost]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Statut beneficiaire</label><div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2 border border-gray-200 dark:border-gray-600 rounded-lg"><label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${i}][statut_beneficiaire][]" value="pdi">PDI</label><label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${i}][statut_beneficiaire][]" value="retourne">Retourne(e)</label><label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${i}][statut_beneficiaire][]" value="refugie">Refugie(e)</label><label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${i}][statut_beneficiaire][]" value="communaute_hote">Communaute hote</label><label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${i}][statut_beneficiaire][]" value="autre">Autre</label></div></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Site</label><select name="activities[${i}][site_id]" id="activity-s-${i}" class="activity-site w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-selected=""><option value="">-- Site --</option></select></div>
              <div><label class="block text-xs font-medium text-gray-500 mb-1">Province</label><select name="activities[${i}][province_id]" class="activity-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="${i}" onchange="dmsActivityProvince(this)"><option value="">-- Province --</option>${_activityProvinces}</select></div>
              <div><label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label><select name="activities[${i}][territoire_id]" id="activity-t-${i}" class="activity-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="${i}" onchange="dmsActivityTerritoire(this)"><option value="">-- Territoire --</option></select></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">Zone de sante</label><select name="activities[${i}][commune_id]" id="activity-c-${i}" class="activity-commune w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" data-idx="${i}" onchange="dmsActivityCommune(this)"><option value="">-- Zone de sante --</option></select></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
              <div><label class="block text-xs text-gray-500 mb-1">Filles 0-17</label><input type="number" min="0" name="activities[${i}][girls_0_17]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Femmes 18-59</label><input type="number" min="0" name="activities[${i}][girls_18_59]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Femmes 60+</label><input type="number" min="0" name="activities[${i}][girls_60_plus]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Garcons 0-17</label><input type="number" min="0" name="activities[${i}][boys_0_17]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Hommes 18-59</label><input type="number" min="0" name="activities[${i}][boys_18_59]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Hommes 60+</label><input type="number" min="0" name="activities[${i}][boys_60_plus]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div><label class="block text-xs text-gray-500 mb-1">Personnes en situation de handicap</label><input type="number" min="0" name="activities[${i}][persons_with_disabilities]" value="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
              <div><label class="block text-xs text-gray-500 mb-1">Date rapportage</label><input type="date" name="activities[${i}][reporting_date]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
            </div>
            <div><label class="block text-xs text-gray-500 mb-1">Commentaire</label><textarea name="activities[${i}][comment]" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea></div>`;
        list.appendChild(div);
        dmsPopulateIndicators(i, '');
        dmsPopulateActivities(i, '', '');
        dmsPopulateSubActivities(i, '', []);
    };

    window.dmsActivityRemove = function (btn) {
        const list = document.getElementById('activities-list');
        const row = btn.closest('.activity-row');
        if (list.querySelectorAll('.activity-row').length > 1) {
            row.remove();
        }
    };

    window.dmsActivityProvince = function (sel) {
        const idx = sel.dataset.idx;
        const tSel = document.getElementById('activity-t-' + idx);
        const cSel = document.getElementById('activity-c-' + idx);
        const sSel = document.getElementById('activity-s-' + idx);
        tSel.innerHTML = '<option value="">-- Territoire --</option>';
        cSel.innerHTML = '<option value="">-- Zone de sante --</option>';
        if (sSel) sSel.innerHTML = '<option value="">-- Site --</option>';
        if (!sel.value) return;

        fetch('/api/geographic/territoires?province_id=' + sel.value)
            .then(r => r.json())
            .then(data => {
                (data.data ?? data).forEach(t => {
                    const o = document.createElement('option');
                    o.value = t.id;
                    o.textContent = t.name;
                    tSel.appendChild(o);
                });
            }).catch(() => {});
    };

    window.dmsActivityTerritoire = function (sel) {
        const idx = sel.dataset.idx;
        const cSel = document.getElementById('activity-c-' + idx);
        const sSel = document.getElementById('activity-s-' + idx);
        cSel.innerHTML = '<option value="">-- Zone de sante --</option>';
        if (sSel) sSel.innerHTML = '<option value="">-- Site --</option>';
        if (!sel.value) return;

        fetch('/api/geographic/communes?territoire_id=' + sel.value)
            .then(r => r.json())
            .then(data => {
                (data.data ?? data).forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = c.name;
                    cSel.appendChild(o);
                });
            }).catch(() => {});
    };

    window.dmsActivityCommune = function (sel) {
        const idx = sel.dataset.idx;
        const sSel = document.getElementById('activity-s-' + idx);
        const selectedSiteId = sSel ? sSel.dataset.selected : '';
        if (!sSel) return;

        sSel.innerHTML = '<option value="">-- Site --</option>';
        if (!sel.value) return;

        fetch('/api/geographic/sites?commune_id=' + sel.value)
            .then(r => r.json())
            .then(data => {
                (data.data ?? data).forEach(site => {
                    const o = document.createElement('option');
                    o.value = site.id;
                    o.textContent = site.code_site ? `${site.nom} (${site.code_site})` : site.nom;
                    if (String(site.id) === String(selectedSiteId)) {
                        o.selected = true;
                    }
                    sSel.appendChild(o);
                });
                sSel.dataset.selected = '';
            }).catch(() => {});
    };

    document.querySelectorAll('.activity-commune').forEach((select) => {
        if (select.value) {
            window.dmsActivityCommune(select);
        }
    });

    // Initialize cluster indicators before hydrating activity rows
    const clusterSel = document.getElementById('cluster_id');
    if (clusterSel && clusterSel.value) {
        dmsSetClusterIndicators(clusterSel.value);
    }

    document.querySelectorAll('.activity-row').forEach((row) => {
        dmsHydrateProgramCascade(row);
    });
})();
</script>
