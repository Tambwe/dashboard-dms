<div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 mb-6">
    <form method="GET" action="{{ route('public.site.show', $site) }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
        <div class="flex-1">
            <label for="periode" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                Période de collecte
            </label>
            <select
                id="periode"
                name="periode"
                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                onchange="this.form.submit()"
                {{ $periods->isEmpty() ? 'disabled' : '' }}
            >
                @forelse($periods as $period)
                    <option value="{{ $period->format('Y-m-d') }}" @selected($selectedPeriod === $period->format('Y-m-d'))>
                        {{ $period->translatedFormat('d F Y') }}
                    </option>
                @empty
                    <option>Aucune période collectée</option>
                @endforelse
            </select>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            @if($selectedPeriod)
                Données affichées au {{ \Illuminate\Support\Carbon::parse($selectedPeriod)->translatedFormat('d F Y') }}
            @else
                Aucune collecte de profil des services
            @endif
        </div>
    </form>
</div>

<div class="mb-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Profil des services</h3>
        @auth
            @if($questionnaireBasedProfile)
                <button type="button" data-question-settings-toggle class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                    Personnaliser les questions
                </button>
            @endif
        @endauth
        @guest
            @if($questionnaireBasedProfile)
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg border border-primary-300 bg-primary-50 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-100 dark:border-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    Se connecter pour personnaliser
                </a>
            @endif
        @endguest
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        @if($questionnaireBasedProfile)
            Les groupes et réponses ci-dessous suivent le questionnaire « Cartographie des services ».
        @else
            Les informations ci-dessous proviennent exclusivement des collectes « Profil des services ».
        @endif
    </p>
</div>

@if($questionnaireBasedProfile)
    @php
        $standardQuestions = collect($serviceGroups)
            ->flatMap(fn ($group) => $group['questions'])
            ->filter(fn ($question) => !empty($question['standard']));
        $standardsMet = $standardQuestions->filter(fn ($question) => $question['standard']['meets'])->count();
        $standardsGaps = $standardQuestions->count() - $standardsMet;
    @endphp
    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="font-bold text-blue-950 dark:text-blue-100">Vue synthétique et repères internationaux</h4>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                    Jusqu’à quatre indicateurs prioritaires sont affichés par thème. Les comparaisons apparaissent uniquement lorsque les données nécessaires sont disponibles.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-white px-3 py-1.5 text-green-700 shadow-sm dark:bg-gray-800 dark:text-green-300">{{ $standardsMet }} conforme(s)</span>
                <span class="rounded-full bg-white px-3 py-1.5 text-amber-700 shadow-sm dark:bg-gray-800 dark:text-amber-300">{{ $standardsGaps }} écart(s)</span>
            </div>
        </div>
        <p class="mt-3 text-xs text-blue-700 dark:text-blue-300">
            Références : Sphere Handbook et INEE 2024. Aucun seuil n’est affiché lorsqu’il n’existe pas de norme universelle vérifiée.
        </p>
    </div>
@endif

@auth
    @if($questionnaireBasedProfile)
        <form
            id="question-preferences-form"
            method="POST"
            action="{{ route('public.site.questions.update', $site) }}"
            class="hidden mb-6 rounded-2xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-800 dark:bg-primary-900/20"
        >
            @csrf
            <input type="hidden" name="periode" value="{{ $selectedPeriod }}">
            <div class="mb-4">
                <h4 class="font-bold text-gray-900 dark:text-white">Questions affichées dans mon profil</h4>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Cette sélection est privée et suivra votre compte sur tous vos appareils. Elle ne modifie pas le questionnaire de collecte.
                </p>
            </div>
            <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="flex-1">
                    <span class="sr-only">Rechercher une question</span>
                    <input
                        type="search"
                        data-question-search
                        placeholder="Rechercher une question..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-select-questions="recommended" class="rounded-lg border border-primary-300 bg-white px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-50 dark:border-primary-700 dark:bg-gray-800 dark:text-primary-300">
                        Sélection recommandée
                    </button>
                    <button type="button" data-select-questions="all" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Tout sélectionner
                    </button>
                    <button type="button" data-select-questions="none" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Tout masquer
                    </button>
                </div>
            </div>
            <div class="space-y-5">
                @foreach($serviceGroups as $group)
                    @if(count($group['available_questions'] ?? []) > 0)
                        <fieldset data-question-group>
                            <legend class="mb-2 text-sm font-bold text-gray-800 dark:text-gray-100">{{ $group['icon'] }} {{ $group['title'] }}</legend>
                            <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">
                                @foreach($group['available_questions'] as $question)
                                    @php
                                        $isSelected = $questionPreference
                                            ? in_array($question['key'], $questionPreference->visible_question_keys ?? [], true)
                                            : $question['default_visible'];
                                    @endphp
                                    <label data-question-option class="flex cursor-pointer items-start gap-2 rounded-lg bg-white p-3 text-sm shadow-sm dark:bg-gray-800">
                                        <input
                                            type="checkbox"
                                            name="question_keys[]"
                                            value="{{ $question['key'] }}"
                                            data-question-key="{{ $question['key'] }}"
                                            data-default-visible="{{ $question['default_visible'] ? 'true' : 'false' }}"
                                            class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                            @checked($isSelected)
                                        >
                                        <span class="text-gray-700 dark:text-gray-200">{{ $question['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                @endforeach
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                    Enregistrer mon affichage
                </button>
                <button type="button" data-question-settings-toggle class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    Annuler
                </button>
            </div>
        </form>
    @endif
@endauth

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach($serviceGroups as $key => $group)
        @if($questionnaireBasedProfile)
            <section id="service-{{ $group['key'] }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl" aria-hidden="true">{{ $group['icon'] }}</span>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $group['title'] }}</h4>
                            @if($group['collected'] && $group['collector'])
                                <p class="text-xs text-gray-400 mt-0.5">Collecté par {{ $group['collector'] }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $group['collected'] ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                        {{ $group['collected'] ? 'Collecté' : 'Données pas encore collectées' }}
                    </span>
                </div>

                @if(!$group['collected'])
                    <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-7 text-center">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Données pas encore collectées</p>
                        <p class="text-xs text-gray-400 mt-1">Aucune réponse pour ce groupe durant la période sélectionnée.</p>
                    </div>
                @else
                    @if(count($group['questions']) === 0)
                        <div class="rounded-xl border-2 border-dashed border-gray-200 p-5 text-center dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune question sélectionnée dans ce groupe.</p>
                        </div>
                    @else
                        <dl class="grid grid-cols-1 gap-4">
                            @foreach($group['questions'] as $question)
                                <div data-profile-question="{{ $question['key'] }}" class="group/question relative rounded-lg border border-transparent p-2 hover:border-gray-200 dark:hover:border-gray-700">
                                    @auth
                                        <button
                                            type="button"
                                            data-hide-question="{{ $question['key'] }}"
                                            class="absolute right-2 top-2 hidden rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 group-hover/question:block dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="Masquer cette question de mon profil"
                                        >
                                            Masquer
                                        </button>
                                    @endauth
                                @if($question['subgroup'])
                                    <p class="mb-1 text-xs font-semibold text-primary-600 dark:text-primary-400">{{ $question['subgroup'] }}</p>
                                @endif
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $question['label'] }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $question['value'] }}</dd>
                                    @if($question['standard'])
                                        <div class="mt-2 rounded-lg border px-3 py-2 text-xs {{ $question['standard']['meets'] ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200' }}">
                                            <p class="font-bold">{{ $question['standard']['meets'] ? 'Conforme au repère' : 'Écart au repère' }}</p>
                                            <p class="mt-1">Valeur calculée : {{ $question['standard']['measured'] }} {{ $question['standard']['unit'] }} · Repère : {{ $question['standard']['target'] }}</p>
                                            <p class="mt-1">{{ $question['standard']['context'] }} · <a href="{{ $question['standard']['url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline">{{ $question['standard']['source'] }}</a></p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </dl>
                    @endif
                    @if($group['standards_note'])
                        <p class="mt-4 rounded-lg bg-blue-50 p-3 text-xs text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                            {{ $group['standards_note'] }}
                        </p>
                    @endif
                @endif
            </section>
            @continue
        @endif

        @php
            $profile = $group['profile'];
            $availableField = $group['available_field'];
        @endphp
        <section id="service-{{ $key }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-start justify-between gap-3 mb-5">
                <div class="flex items-center gap-3">
                    <span class="text-3xl" aria-hidden="true">{{ $group['icon'] }}</span>
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $group['title'] }}</h4>
                        @if($profile)
                            <p class="text-xs text-gray-400 mt-0.5">
                                Collecté par {{ $profile->collecteur?->name ?? '—' }}
                            </p>
                        @endif
                    </div>
                </div>

                @if(!$profile)
                    <span class="inline-flex px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold">
                        Données pas encore collectées
                    </span>
                @elseif($profile->{$availableField})
                    <span class="inline-flex px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-semibold">
                        Disponible
                    </span>
                @else
                    <span class="inline-flex px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-semibold">
                        Non disponible
                    </span>
                @endif
            </div>

            @if(!$profile)
                <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-7 text-center">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Données pas encore collectées</p>
                    <p class="text-xs text-gray-400 mt-1">Aucune information pour ce groupe durant la période sélectionnée.</p>
                </div>
            @else
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($group['fields'] as [$label, $field, $type])
                        @php $value = $profile->{$field}; @endphp
                        <div class="{{ $type === 'text' || $type === 'list' ? 'sm:col-span-2' : '' }}">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($type === 'boolean')
                                    {{ $value === null ? '—' : ($value ? 'Oui' : 'Non') }}
                                @elseif($type === 'list')
                                    @if(is_array($value) && count($value) > 0)
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($value as $item)
                                                <span class="px-2.5 py-1 rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 text-xs">
                                                    {{ $item }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                @elseif($type === 'number')
                                    {{ $value === null ? '—' : number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 2, ',', ' ') }}
                                @else
                                    {{ filled($value) ? $value : '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>
    @endforeach
</div>
