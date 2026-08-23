@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Configurer le questionnaire</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $mobileQuestionnaire->code }} - version {{ $mobileQuestionnaire->version }}</p>
            </div>
            <a
                href="{{ route('admin.mobile-questionnaires.export', $mobileQuestionnaire) }}"
                class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Exporter en XLSX
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900">Ajouter une nouvelle question</h2>
        <p class="text-sm text-gray-600 mt-1">Ajoutez une question en précisant le type, le groupe et le libellé.</p>

        <form method="POST" action="{{ route('admin.mobile-questionnaires.questions.store', $mobileQuestionnaire) }}" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Groupe</label>
                <select name="group_key" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($groups as $group)
                        <option value="{{ $group['key'] }}" @selected(old('group_key', 'default') === $group['key'])>{{ $group['label'] }}</option>
                    @endforeach
                    <option value="__new_group__" @selected(old('group_key') === '__new_group__')>Nouveau groupe</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du nouveau groupe (si nécessaire)</label>
                <input type="text" name="group_label" value="{{ old('group_label') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Ex: Accès aux services">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de question</label>
                <select name="question_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    @foreach($questionTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('question_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom technique (optionnel)</label>
                <input type="text" name="question_name" value="{{ old('question_name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Ex: nb_menages">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nouvelle question (libellé)</label>
                <input type="text" name="question_label" value="{{ old('question_label') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required placeholder="Ex: Combien de ménages sont présents ?">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de liste (pour choix unique/multiple)</label>
                <input type="text" name="list_name" value="{{ old('list_name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Ex: yesno">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Options (séparées par virgules ou lignes)</label>
                <textarea name="choice_options" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Oui, Non">{{ old('choice_options') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="required" value="1" class="rounded border-gray-300" @checked(old('required'))>
                    Question obligatoire
                </label>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Ajouter la question</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-gray-900">Questions par groupe</h2>
        <p class="text-sm text-gray-600 mt-1">Modifiez directement les questions organisées par section.</p>

        @php
            $initialGroupTab = old('active_group_tab', session('active_group_tab', $groupedQuestions[0]['key'] ?? 'default'));
        @endphp

        <form method="POST" action="{{ route('admin.mobile-questionnaires.grouped-update', $mobileQuestionnaire) }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="active_group_tab" id="active_group_tab" value="{{ $initialGroupTab }}">

            <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
                @foreach($groupedQuestions as $group)
                    <button
                        type="button"
                        data-group-tab-button="{{ $group['key'] }}"
                        class="group-tab-button rounded-lg border px-3 py-2 text-sm font-semibold"
                    >
                        {{ $group['label'] }} <span class="text-xs">({{ count($group['questions']) }})</span>
                    </button>
                @endforeach
            </div>

            @foreach($groupedQuestions as $group)
                <div data-group-tab-panel="{{ $group['key'] }}" class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ count($group['questions']) }} question(s)</span>
                            @if(($group['key'] ?? 'default') !== 'default')
                                <button
                                    type="button"
                                    data-delete-action="delete_group:{{ $group['key'] }}"
                                    data-delete-confirm="Supprimer ce groupe et toutes ses questions ?"
                                    class="rounded-md border border-red-300 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100"
                                >
                                    Supprimer groupe
                                </button>
                            @endif
                        </div>
                    </div>

                    @if(!empty($group['children']))
                        <div class="mt-3">
                            <p class="text-xs font-semibold text-gray-600">Sous-groupes:</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach($group['children'] as $child)
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $child['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(count($group['questions']) > 0)
                        @php($hasParentQuestions = collect($group['questions'])->contains(fn($q) => (($q['child_key'] ?? '') === null || ($q['child_key'] ?? '') === '')))
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" data-subgroup-button="{{ $group['key'] }}" data-subgroup-key="__all__" class="subgroup-filter-button rounded-md border border-primary-300 bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700">
                                Tous
                            </button>
                            @if($hasParentQuestions)
                                <button type="button" data-subgroup-button="{{ $group['key'] }}" data-subgroup-key="__parent__" class="subgroup-filter-button rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700">
                                    Parent
                                </button>
                            @endif
                            @foreach($group['children'] as $childKey => $child)
                                <button type="button" data-subgroup-button="{{ $group['key'] }}" data-subgroup-key="{{ $childKey }}" class="subgroup-filter-button rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700">
                                    {{ $child['label'] }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-4 space-y-4">
                            @foreach($group['questions'] as $question)
                                @php($questionSubgroupKey = ($question['child_key'] ?? '') !== '' ? $question['child_key'] : '__parent__')
                                <div data-question-subgroup="{{ $questionSubgroupKey }}" class="grid gap-3 md:grid-cols-5 rounded-lg border border-gray-100 p-3">
                                    <div class="md:col-span-5">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">
                                            {{ ($question['child_label'] ?? '') !== '' ? 'Sous-groupe: '.$question['child_label'] : 'Parent' }}
                                        </span>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Libellé</label>
                                        <input type="text" name="rows[{{ $question['index'] }}][label]" value="{{ old('rows.'.$question['index'].'.label', $question['label']) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom technique</label>
                                        <input type="text" name="rows[{{ $question['index'] }}][name]" value="{{ old('rows.'.$question['index'].'.name', $question['name']) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                        <select name="rows[{{ $question['index'] }}][type]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                            @foreach($questionTypes as $value => $label)
                                                <option value="{{ $value }}" @selected(old('rows.'.$question['index'].'.type', $question['type']) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Liste (si choix)</label>
                                        <input type="text" name="rows[{{ $question['index'] }}][list_name]" value="{{ old('rows.'.$question['index'].'.list_name', $question['list_name']) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                    </div>
                                    <div class="md:col-span-3 flex items-center">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                            <input type="hidden" name="rows[{{ $question['index'] }}][required]" value="0">
                                            <input type="checkbox" name="rows[{{ $question['index'] }}][required]" value="1" class="rounded border-gray-300" @checked(old('rows.'.$question['index'].'.required', $question['required']))>
                                            Obligatoire
                                        </label>
                                    </div>
                                    <div class="md:col-span-2 md:justify-self-end flex items-center">
                                        <button
                                            type="button"
                                            data-delete-action="delete_question:{{ $question['index'] }}"
                                            data-delete-confirm="Supprimer cette question ?"
                                            class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                        >
                                            Supprimer la question
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-xs text-gray-500">Aucune question modifiable dans ce groupe.</p>
                    @endif
                </div>
            @endforeach

            <div>
                <button type="submit" name="action_type" value="save" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer les modifications par groupe</button>
            </div>
        </form>

        <form id="grouped-delete-form" method="POST" action="{{ route('admin.mobile-questionnaires.grouped-update', $mobileQuestionnaire) }}" class="hidden">
            @csrf
            <input type="hidden" name="active_group_tab" id="delete_active_group_tab" value="{{ $initialGroupTab }}">
            <input type="hidden" name="action_type" id="delete_action_type" value="">
        </form>
    </div>

    <form method="POST" action="{{ route('admin.mobile-questionnaires.update', $mobileQuestionnaire) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-gray-200 bg-white p-5 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                <input type="text" name="title" value="{{ old('title', $mobileQuestionnaire->title) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Actif</label>
                <select name="is_active" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="1" @selected(old('is_active', $mobileQuestionnaire->is_active ? '1' : '0') === '1')>Oui</option>
                    <option value="0" @selected(old('is_active', $mobileQuestionnaire->is_active ? '1' : '0') === '0')>Non</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $mobileQuestionnaire->description) }}</textarea>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Survey (JSON)</label>
                <textarea name="survey_json" rows="12" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono" required>{{ old('survey_json', json_encode($mobileQuestionnaire->survey, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Choices (JSON)</label>
                <textarea name="choices_json" rows="12" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono">{{ old('choices_json', json_encode($mobileQuestionnaire->choices, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Settings (JSON)</label>
                <textarea name="settings_json" rows="6" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-mono">{{ old('settings_json', json_encode($mobileQuestionnaire->settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <a href="{{ route('admin.mobile-questionnaires.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Retour</a>
            <button
                id="delete-questionnaire-button"
                type="button"
                class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
            >
                Supprimer ce questionnaire
            </button>
        </div>
    </form>

    <form id="delete-questionnaire-form" method="POST" action="{{ route('admin.mobile-questionnaires.destroy', $mobileQuestionnaire) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const tabButtons = Array.from(document.querySelectorAll('[data-group-tab-button]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-group-tab-panel]'));
    const activeTabInput = document.getElementById('active_group_tab');
    const groupedDeleteForm = document.getElementById('grouped-delete-form');
    const groupedDeleteActionInput = document.getElementById('delete_action_type');
    const groupedDeleteActiveTabInput = document.getElementById('delete_active_group_tab');
    const deleteButtons = Array.from(document.querySelectorAll('[data-delete-action]'));
    const subgroupButtons = Array.from(document.querySelectorAll('[data-subgroup-button]'));
    const questionnaireDeleteButton = document.getElementById('delete-questionnaire-button');
    const questionnaireDeleteForm = document.getElementById('delete-questionnaire-form');

    if (questionnaireDeleteButton && questionnaireDeleteForm) {
        questionnaireDeleteButton.addEventListener('click', () => {
            if (!window.confirm('Supprimer ce questionnaire ? Cette action est définitive.')) {
                return;
            }
            questionnaireDeleteForm.submit();
        });
    }

    if (tabButtons.length === 0 || tabPanels.length === 0 || !activeTabInput) {
        return;
    }

    const setActiveTab = (tabKey) => {
        activeTabInput.value = tabKey;
        tabButtons.forEach((button) => {
            const isActive = button.getAttribute('data-group-tab-button') === tabKey;
            button.classList.toggle('bg-primary-600', isActive);
            button.classList.toggle('border-primary-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('border-gray-300', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
        });

        tabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-group-tab-panel') !== tabKey);
        });
    };

    const setActiveSubgroup = (panel, subgroupKey) => {
        const buttons = Array.from(panel.querySelectorAll('[data-subgroup-button]'));
        const questions = Array.from(panel.querySelectorAll('[data-question-subgroup]'));

        buttons.forEach((button) => {
            const isActive = button.getAttribute('data-subgroup-key') === subgroupKey;
            button.classList.toggle('bg-primary-50', isActive);
            button.classList.toggle('border-primary-300', isActive);
            button.classList.toggle('text-primary-700', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('border-gray-300', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
        });

        questions.forEach((question) => {
            const questionGroup = question.getAttribute('data-question-subgroup') || '__parent__';
            const show = subgroupKey === '__all__' || questionGroup === subgroupKey;
            question.classList.toggle('hidden', !show);
        });
    };

    const initialTab = activeTabInput.value || tabButtons[0].getAttribute('data-group-tab-button');
    setActiveTab(initialTab);

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tabKey = button.getAttribute('data-group-tab-button');
            if (tabKey) {
                setActiveTab(tabKey);
            }
        });
    });

    subgroupButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const panelKey = button.getAttribute('data-subgroup-button');
            const subgroupKey = button.getAttribute('data-subgroup-key');
            if (!panelKey || !subgroupKey) {
                return;
            }

            const panel = tabPanels.find((item) => item.getAttribute('data-group-tab-panel') === panelKey);
            if (!panel) {
                return;
            }

            setActiveSubgroup(panel, subgroupKey);
        });
    });

    tabPanels.forEach((panel) => {
        const defaultButton = panel.querySelector('[data-subgroup-key="__all__"]');
        if (defaultButton) {
            setActiveSubgroup(panel, '__all__');
        }
    });

    deleteButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!groupedDeleteForm || !groupedDeleteActionInput || !groupedDeleteActiveTabInput) {
                return;
            }

            const actionType = button.getAttribute('data-delete-action');
            if (!actionType) {
                return;
            }

            const confirmationMessage = button.getAttribute('data-delete-confirm');
            if (confirmationMessage && !window.confirm(confirmationMessage)) {
                return;
            }

            groupedDeleteActionInput.value = actionType;
            groupedDeleteActiveTabInput.value = activeTabInput.value || '';
            groupedDeleteForm.submit();
        });
    });
})();
</script>
@endpush
