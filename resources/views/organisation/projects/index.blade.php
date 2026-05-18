@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Projets de mon organisation</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Créer et suivre vos projets</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('project-activities-import.index') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Import Excel activités
                </a>
            <a href="{{ route('organisation.projects.create') }}"
               class="inline-flex items-center px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Nouveau projet
            </a>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-3 rounded bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded p-4 mb-4">
            <form method="GET" action="{{ route('organisation.projects.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Rechercher un projet..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Rechercher
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Projet</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bailleurs</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Période</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Financement</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bénéficiaires</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->name }}</div>
                                @if($project->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $project->code ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $statusLabels = [
                                        'planifie' => ['Planifié',  'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300'],
                                        'en_cours' => ['En cours',  'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'],
                                        'suspendu' => ['Suspendu',  'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300'],
                                        'termine'  => ['Terminé',   'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'],
                                        'annule'   => ['Annulé',    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'],
                                    ];
                                    [$sLabel, $sClass] = $statusLabels[$project->status] ?? [ucfirst($project->status), 'bg-gray-100 text-gray-800'];
                                @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sClass }}">{{ $sLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if(!empty($project->donors_json))
                                    {{ implode(', ', $project->donors_json) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $project->start_date ? $project->start_date->format('d/m/Y') : '-' }}
                                -
                                {{ $project->end_date ? $project->end_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $project->funding_amount !== null ? number_format((float) $project->funding_amount, 2, ',', ' ') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @php
                                    $femaleTotal = (int) ($project->beneficiaries_female_0_17 ?? 0) + (int) ($project->beneficiaries_female_18_59 ?? 0) + (int) ($project->beneficiaries_female_60_plus ?? 0);
                                    $maleTotal = (int) ($project->beneficiaries_male_0_17 ?? 0) + (int) ($project->beneficiaries_male_18_59 ?? 0) + (int) ($project->beneficiaries_male_60_plus ?? 0);
                                @endphp
                                F: {{ $femaleTotal }} | H: {{ $maleTotal }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3"
                                    data-action="open-activities-modal"
                                    data-project-id="{{ $project->id }}"
                                    data-project-name="{{ $project->name }}">
                                    Ajouter activites
                                </button>
                                <a href="{{ route('organisation.projects.edit', $project) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3">Modifier</a>
                                <form method="POST" action="{{ route('organisation.projects.destroy', $project) }}" class="inline" onsubmit="return confirm('Supprimer ce projet ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <p class="text-sm">Aucun projet trouvé pour votre organisation.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($projects->hasPages())
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                {{ $projects->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<div id="activities-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" data-close-activities-modal></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[90vh] overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ajouter des activites</h3>
                    <p id="activities-modal-subtitle" class="text-sm text-gray-500 dark:text-gray-400"></p>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" data-close-activities-modal>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="activities-modal-form" method="POST" action="" class="flex flex-col max-h-[calc(90vh-72px)]">
                @csrf
                <div class="overflow-y-auto p-6 space-y-4" id="activities-modal-list"></div>

                <div class="px-6 pb-4">
                    <button type="button" id="activities-modal-add" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Ajouter une activite
                    </button>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300" data-close-activities-modal>
                        Annuler
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium">
                        Enregistrer les activites
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script id="projects-provinces-json" type="application/json">@json(($provinces ?? collect())->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->values())</script>
<script id="projects-program-hierarchy-json" type="application/json">@json($programHierarchy ?? [])</script>
<script id="projects-activities-update-url-template" type="application/json">@json(url('organisation/projects/__ID__/activities'))</script>
<script id="projects-activities-data-url-template" type="application/json">@json(url('organisation/projects/__ID__/activities-data'))</script>
<script>
(function () {
    const modal = document.getElementById('activities-modal');
    const modalList = document.getElementById('activities-modal-list');
    const modalSubtitle = document.getElementById('activities-modal-subtitle');
    const modalForm = document.getElementById('activities-modal-form');
    const addBtn = document.getElementById('activities-modal-add');
    const provinces = JSON.parse(document.getElementById('projects-provinces-json').textContent || '[]');
    const programHierarchy = JSON.parse(document.getElementById('projects-program-hierarchy-json').textContent || '[]');
    const updateUrlTemplate = JSON.parse(document.getElementById('projects-activities-update-url-template').textContent || '""');
    const dataUrlTemplate = JSON.parse(document.getElementById('projects-activities-data-url-template').textContent || '""');

    let rowIndex = 0;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        modalList.innerHTML = '';
        rowIndex = 0;
    }

    function provinceOptions(selected) {
        const opts = ['<option value="">-- Province --</option>'];
        provinces.forEach((p) => {
            opts.push(`<option value="${p.id}" ${String(selected) === String(p.id) ? 'selected' : ''}>${p.name}</option>`);
        });
        return opts.join('');
    }

    const beneficiaryStatuses = [
        { value: 'pdi', label: 'PDI' },
        { value: 'retourne', label: 'Retourne(e)' },
        { value: 'refugie', label: 'Refugie(e)' },
        { value: 'communaute_hote', label: 'Communaute hote' },
        { value: 'autre', label: 'Autre' }
    ];

    const beneficiaryKeys = ['girls_0_17', 'girls_18_59', 'girls_60_plus', 'boys_0_17', 'boys_18_59', 'boys_60_plus'];

    function statutCheckboxes(idx, selectedValues) {

        const selected = Array.isArray(selectedValues)
            ? selectedValues.map((v) => String(v))
            : (selectedValues ? String(selectedValues).split(',').map((v) => v.trim()).filter(Boolean) : []);

        return beneficiaryStatuses.map((s) => {
            const checked = selected.includes(String(s.value)) ? 'checked' : '';
            return `<label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300"><input type="checkbox" name="activities[${idx}][statut_beneficiaire][]" value="${s.value}" ${checked}>${s.label}</label>`;
        }).join('');
    }

    function getStatusLabel(status) {
        return beneficiaryStatuses.find((item) => item.value === status)?.label || status;
    }

    function collectCurrentBeneficiaries(container) {
        const result = {};
        if (!container) return result;

        container.querySelectorAll('[data-status]').forEach((card) => {
            const status = card.getAttribute('data-status');
            if (!status) return;
            result[status] = {};
            beneficiaryKeys.forEach((key) => {
                const input = card.querySelector(`input[name$="[${key}]"]`);
                result[status][key] = input ? Number(input.value || 0) : 0;
            });
        });

        return result;
    }

    function renderBeneficiariesByStatus(wrapper, seed = {}) {
        const container = wrapper.querySelector('.beneficiaries-by-status');
        if (!container) return;

        const idx = wrapper.dataset.index;
        const checkedStatuses = Array.from(wrapper.querySelectorAll('input[name="activities[' + idx + '][statut_beneficiaire][]"]:checked'))
            .map((input) => input.value);

        const merged = { ...seed, ...collectCurrentBeneficiaries(container) };
        container.innerHTML = '';

        if (!checkedStatuses.length) {
            container.innerHTML = '<p class="text-xs text-gray-500 dark:text-gray-400">Choisissez au moins un statut beneficiaire pour saisir la desagregation sexe/age.</p>';
            return;
        }

        checkedStatuses.forEach((status) => {
            const data = merged[status] || {};
            const card = document.createElement('div');
            card.className = 'rounded-lg border border-gray-200 dark:border-gray-600 p-3 space-y-2';
            card.setAttribute('data-status', status);
            card.innerHTML = `
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">${getStatusLabel(status)}</p>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-2">
                    <div><label class="block text-xs text-gray-500 mb-1">Filles 0-17</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][girls_0_17]" value="${Number(data.girls_0_17 || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Femmes 18-59</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][girls_18_59]" value="${Number(data.girls_18_59 || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Femmes 60+</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][girls_60_plus]" value="${Number(data.girls_60_plus || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Garcons 0-17</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][boys_0_17]" value="${Number(data.boys_0_17 || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Hommes 18-59</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][boys_18_59]" value="${Number(data.boys_18_59 || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Hommes 60+</label><input type="number" min="0" name="activities[${idx}][beneficiaries_by_status][${status}][boys_60_plus]" value="${Number(data.boys_60_plus || 0)}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></div>
                </div>`;
            container.appendChild(card);
        });
    }

    async function fetchJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            throw new Error('Erreur de chargement');
        }
        return response.json();
    }

    async function loadTerritoires(row, provinceId, selectedTerritoireId) {
        const territoire = row.querySelector('.activity-territoire');
        territoire.innerHTML = '<option value="">-- Territoire --</option>';
        if (!provinceId) return;

        const data = await fetchJson(`/api/geographic/territoires?province_id=${provinceId}`);
        (data.data ?? data).forEach((t) => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            if (String(t.id) === String(selectedTerritoireId || '')) {
                opt.selected = true;
            }
            territoire.appendChild(opt);
        });
    }

    async function loadCommunes(row, territoireId, selectedCommuneId) {
        const commune = row.querySelector('.activity-commune');
        commune.innerHTML = '<option value="">-- Zone de sante --</option>';
        if (!territoireId) return;

        const data = await fetchJson(`/api/geographic/communes?territoire_id=${territoireId}`);
        (data.data ?? data).forEach((c) => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (String(c.id) === String(selectedCommuneId || '')) {
                opt.selected = true;
            }
            commune.appendChild(opt);
        });
    }

    async function loadSites(row, communeId, selectedSiteId) {
        const site = row.querySelector('.activity-site');
        site.innerHTML = '<option value="">-- Site --</option>';
        if (!communeId) return;

        const data = await fetchJson(`/api/geographic/sites?commune_id=${communeId}`);
        (data.data ?? data).forEach((s) => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.code_site ? `${s.nom} (${s.code_site})` : s.nom;
            if (String(s.id) === String(selectedSiteId || '')) {
                opt.selected = true;
            }
            site.appendChild(opt);
        });
    }

    function updateActivitySummary(wrapper) {
        const name = wrapper.querySelector('input[name*="[activity_name]"]')?.value?.trim() || 'Sans titre';
        const cost = wrapper.querySelector('input[name*="[activity_cost]"]')?.value || '';
        const date = wrapper.querySelector('input[name*="[reporting_date]"]')?.value || '';

        const summary = [];
        summary.push(name);
        if (cost !== '') {
            summary.push(`USD ${cost}`);
        }
        if (date) {
            summary.push(date);
        }

        wrapper.querySelector('[data-activity-summary]').textContent = summary.join(' | ');
    }

    function closeAllActivityAccordions(exceptWrapper = null) {
        modalList.querySelectorAll('.activity-row').forEach((row) => {
            if (exceptWrapper && row === exceptWrapper) return;
            setActivityAccordionState(row, false);
        });
    }

    function programIndicatorOptions(selected) {
        const opts = ['<option value="">-- Indicateur --</option>'];
        programHierarchy.forEach((indicator) => {
            const label = `${indicator.code ? indicator.code + ' - ' : ''}${indicator.label}`;
            opts.push(`<option value="${indicator.id}" ${String(selected || '') === String(indicator.id) ? 'selected' : ''}>${label}</option>`);
        });
        return opts.join('');
    }

    function programActivityOptions(indicatorId, selected) {
        const opts = ['<option value="">-- Activite --</option>'];
        const indicator = programHierarchy.find((item) => String(item.id) === String(indicatorId || ''));
        if (!indicator) return opts.join('');
        indicator.activities.forEach((activity) => {
            const label = `${activity.code ? activity.code + ' - ' : ''}${activity.label}`;
            opts.push(`<option value="${activity.id}" ${String(selected || '') === String(activity.id) ? 'selected' : ''}>${label}</option>`);
        });
        return opts.join('');
    }

    function programSubActivityOptions(indicatorId, activityId, selected) {
        const opts = ['<option value="">-- Sous-activite --</option>'];
        const indicator = programHierarchy.find((item) => String(item.id) === String(indicatorId || ''));
        if (!indicator) return opts.join('');

        const activity = indicator.activities.find((item) => String(item.id) === String(activityId || ''));
        if (!activity) return opts.join('');

        activity.sub_activities.forEach((sub) => {
            const label = `${sub.code ? sub.code + ' - ' : ''}${sub.label}`;
            opts.push(`<option value="${sub.id}" ${String(selected || '') === String(sub.id) ? 'selected' : ''}>${label}</option>`);
        });

        return opts.join('');
    }

    function syncActivityNameFromCascade(wrapper) {
        const hiddenName = wrapper.querySelector('input[name*="[activity_name]"]');
        const activitySelect = wrapper.querySelector('.program-activity');
        const subActivitySelect = wrapper.querySelector('.program-sub-activity');
        if (!hiddenName) return;

        if (subActivitySelect && subActivitySelect.value) {
            hiddenName.value = subActivitySelect.selectedOptions[0]?.textContent?.trim() || '';
        } else if (activitySelect && activitySelect.value) {
            hiddenName.value = activitySelect.selectedOptions[0]?.textContent?.trim() || '';
        } else {
            hiddenName.value = '';
        }
    }

    function setActivityAccordionState(wrapper, isOpen) {
        const body = wrapper.querySelector('[data-activity-body]');
        const chevron = wrapper.querySelector('[data-activity-chevron]');
        if (!body || !chevron) return;

        body.classList.toggle('hidden', !isOpen);
        chevron.classList.toggle('rotate-180', isOpen);
    }

    async function addActivityRow(data = {}, expanded = true) {
        const idx = rowIndex++;
        const selectedIndicatorId = data.program_indicator_id ?? '';
        const selectedActivityId = data.program_activity_id ?? '';
        const selectedSubActivityId = Array.isArray(data.program_sub_activity_ids) && data.program_sub_activity_ids.length
            ? data.program_sub_activity_ids[0]
            : (data.program_sub_activity_id ?? '');
        const wrapper = document.createElement('div');
        wrapper.className = 'activity-row rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden';
        wrapper.dataset.index = idx;
        wrapper.innerHTML = `
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 px-4 py-3">
                <button type="button" class="flex-1 text-left" data-activity-toggle>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activite #${idx + 1}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-activity-summary>Sans titre</p>
                </button>
                <div class="flex items-center gap-3 ml-3">
                    <button type="button" class="text-red-500 hover:text-red-700 text-sm" data-remove-activity>Retirer</button>
                    <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white" data-activity-toggle>
                        <svg class="w-5 h-5 transition-transform" data-activity-chevron fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-4 space-y-3" data-activity-body>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Indicateur</label>
                    <select name="activities[${idx}][program_indicator_id]" class="program-indicator w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        ${programIndicatorOptions(selectedIndicatorId)}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Activite du cadre</label>
                    <select name="activities[${idx}][program_activity_id]" class="program-activity w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        ${programActivityOptions(selectedIndicatorId, selectedActivityId)}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sous-activite</label>
                    <select name="activities[${idx}][program_sub_activity_ids][]" class="program-sub-activity w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        ${programSubActivityOptions(selectedIndicatorId, selectedActivityId, selectedSubActivityId)}
                    </select>
                </div>
                <input type="hidden" name="activities[${idx}][activity_name]" value="${data.activity_name ?? ''}">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Montant activite (USD)</label>
                    <input type="number" min="0" step="0.01" name="activities[${idx}][activity_cost]" value="${data.activity_cost ?? ''}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Statut beneficiaire</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2 border border-gray-200 dark:border-gray-600 rounded-lg">
                        ${statutCheckboxes(idx, data.statut_beneficiaire)}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                    <select name="activities[${idx}][province_id]" class="activity-province w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        ${provinceOptions(data.province_id)}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Territoire</label>
                    <select name="activities[${idx}][territoire_id]" class="activity-territoire w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">-- Territoire --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zone de sante</label>
                    <select name="activities[${idx}][commune_id]" class="activity-commune w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">-- Zone de sante --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Site</label>
                    <select name="activities[${idx}][site_id]" class="activity-site w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">-- Site --</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Beneficiaires desagreges (par statut choisi)</label>
                <div class="beneficiaries-by-status space-y-2"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nombre personnes en situation handicap</label>
                    <input type="number" min="0" name="activities[${idx}][persons_with_disabilities]" value="${data.persons_with_disabilities ?? 0}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date rapport</label>
                    <input type="date" name="activities[${idx}][reporting_date]" value="${data.reporting_date ?? ''}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Commentaire</label>
                <textarea name="activities[${idx}][comment]" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">${data.comment ?? ''}</textarea>
            </div>
            </div>
        `;

        modalList.appendChild(wrapper);

        const indicatorSelect = wrapper.querySelector('.program-indicator');
        const activitySelect = wrapper.querySelector('.program-activity');
        const subActivitySelect = wrapper.querySelector('.program-sub-activity');
        const statusCheckboxes = wrapper.querySelectorAll(`input[name="activities[${idx}][statut_beneficiaire][]"]`);

        setActivityAccordionState(wrapper, expanded);
        if (expanded) {
            closeAllActivityAccordions(wrapper);
        }
        renderBeneficiariesByStatus(wrapper, data.beneficiaries_by_status || {});
        syncActivityNameFromCascade(wrapper);
        updateActivitySummary(wrapper);

        wrapper.querySelectorAll('input[name*="[activity_cost]"], input[name*="[reporting_date]"]').forEach((el) => {
            el.addEventListener('input', () => updateActivitySummary(wrapper));
            el.addEventListener('change', () => updateActivitySummary(wrapper));
        });

        indicatorSelect.addEventListener('change', () => {
            activitySelect.innerHTML = programActivityOptions(indicatorSelect.value, '');
            subActivitySelect.innerHTML = programSubActivityOptions(indicatorSelect.value, '', '');
            syncActivityNameFromCascade(wrapper);
            updateActivitySummary(wrapper);
        });

        activitySelect.addEventListener('change', () => {
            subActivitySelect.innerHTML = programSubActivityOptions(indicatorSelect.value, activitySelect.value, '');
            syncActivityNameFromCascade(wrapper);
            updateActivitySummary(wrapper);
        });

        subActivitySelect.addEventListener('change', () => {
            syncActivityNameFromCascade(wrapper);
            updateActivitySummary(wrapper);
        });

        statusCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                renderBeneficiariesByStatus(wrapper, data.beneficiaries_by_status || {});
            });
        });

        wrapper.querySelectorAll('[data-activity-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', () => {
                const body = wrapper.querySelector('[data-activity-body]');
                const isOpen = body && !body.classList.contains('hidden');
                if (isOpen) {
                    setActivityAccordionState(wrapper, false);
                } else {
                    closeAllActivityAccordions(wrapper);
                    setActivityAccordionState(wrapper, true);
                }
            });
        });

        wrapper.querySelector('[data-remove-activity]').addEventListener('click', () => {
            if (modalList.querySelectorAll('.activity-row').length > 1) {
                wrapper.remove();
            }
        });

        const provinceSelect = wrapper.querySelector('.activity-province');
        const territoireSelect = wrapper.querySelector('.activity-territoire');
        const communeSelect = wrapper.querySelector('.activity-commune');

        provinceSelect.addEventListener('change', async function () {
            await loadTerritoires(wrapper, this.value, null);
            wrapper.querySelector('.activity-commune').innerHTML = '<option value="">-- Zone de sante --</option>';
            wrapper.querySelector('.activity-site').innerHTML = '<option value="">-- Site --</option>';
        });

        territoireSelect.addEventListener('change', async function () {
            await loadCommunes(wrapper, this.value, null);
            wrapper.querySelector('.activity-site').innerHTML = '<option value="">-- Site --</option>';
        });

        communeSelect.addEventListener('change', async function () {
            await loadSites(wrapper, this.value, null);
        });

        if (data.province_id) {
            await loadTerritoires(wrapper, data.province_id, data.territoire_id);
        }
        if (data.territoire_id) {
            await loadCommunes(wrapper, data.territoire_id, data.commune_id);
        }
        if (data.commune_id) {
            await loadSites(wrapper, data.commune_id, data.site_id);
        }
    }

    document.querySelectorAll('[data-close-activities-modal]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    addBtn.addEventListener('click', function () {
        closeAllActivityAccordions();
        addActivityRow({}, false);
    });

    document.querySelectorAll('[data-action="open-activities-modal"]').forEach((btn) => {
        btn.addEventListener('click', async function () {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;

            modalSubtitle.textContent = `Projet: ${projectName}`;
            modalForm.action = updateUrlTemplate.replace('__ID__', projectId);
            modalList.innerHTML = '';
            rowIndex = 0;

            openModal();

            try {
                const payload = await fetchJson(dataUrlTemplate.replace('__ID__', projectId));
                const activities = payload.activities || [];
                if (!activities.length) {
                    await addActivityRow({}, true);
                } else {
                    let i = 0;
                    for (const act of activities) {
                        await addActivityRow(act, i === 0);
                        i += 1;
                    }
                }
            } catch (e) {
                await addActivityRow({}, true);
            }
        });
    });
})();
</script>
@endpush
@endsection
