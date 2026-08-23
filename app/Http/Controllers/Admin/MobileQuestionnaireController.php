<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileQuestionnaire;
use App\Support\XlsFormParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileQuestionnaireController extends Controller
{
    public function index(): View
    {
        $questionnaires = MobileQuestionnaire::query()
            ->orderBy('code')
            ->orderByDesc('version')
            ->paginate(20);

        return view('admin.mobile-questionnaires.index', compact('questionnaires'));
    }

    public function edit(MobileQuestionnaire $mobileQuestionnaire): View
    {
        $groups = $this->extractGroups($mobileQuestionnaire->survey ?? []);
        $groupedQuestions = $this->extractGroupedQuestions($mobileQuestionnaire->survey ?? []);
        $questionTypes = $this->questionTypeOptions();

        return view('admin.mobile-questionnaires.edit', compact('mobileQuestionnaire', 'groups', 'groupedQuestions', 'questionTypes'));
    }

    public function addQuestion(Request $request, MobileQuestionnaire $mobileQuestionnaire): RedirectResponse
    {
        $validated = $request->validate([
            'group_key' => ['nullable', 'string', 'max:120'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'question_label' => ['required', 'string', 'max:255'],
            'question_name' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_]+$/'],
            'question_type' => ['required', 'in:text,integer,decimal,select_one,select_multiple,note'],
            'list_name' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'choice_options' => ['nullable', 'string'],
            'required' => ['nullable', 'boolean'],
        ]);

        $groupKey = trim((string) ($validated['group_key'] ?? ''));
        $groupLabel = trim((string) ($validated['group_label'] ?? ''));
        if ($groupKey === '__new_group__') {
            $groupKey = Str::snake(Str::lower($groupLabel));
        }
        if ($groupKey === '' || $groupKey === 'default') {
            $groupKey = '';
        }

        $type = (string) $validated['question_type'];
        $listName = trim((string) ($validated['list_name'] ?? ''));
        if (in_array($type, ['select_one', 'select_multiple'], true) && $listName === '') {
            return back()->withInput()->withErrors([
                'list_name' => 'Le nom de liste est requis pour une question de sélection.',
            ]);
        }

        $survey = $mobileQuestionnaire->survey ?? [];
        $choices = $mobileQuestionnaire->choices ?? [];

        $existingNames = collect($survey)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $questionName = trim((string) ($validated['question_name'] ?? ''));
        if ($questionName === '') {
            $questionName = Str::snake(Str::lower(Str::limit($validated['question_label'], 80, '')));
            $questionName = trim($questionName, '_');
        }
        if ($questionName === '') {
            $questionName = 'question';
        }
        $questionName = $this->makeUniqueName($questionName, $existingNames);

        $questionRow = [
            'type' => in_array($type, ['select_one', 'select_multiple'], true) ? "{$type} {$listName}" : $type,
            'name' => $questionName,
            'label' => (string) $validated['question_label'],
            'label_fr' => (string) $validated['question_label'],
            'label_en' => '',
            'hint' => '',
            'hint_fr' => '',
            'hint_en' => '',
            'required' => (bool) ($validated['required'] ?? false) ? 'yes' : '',
            'relevant' => '',
            'appearance' => '',
            'constraint' => '',
            'constraint_message' => '',
            'choice_filter' => '',
            'calculation' => '',
            'file' => '',
            'list_name' => in_array($type, ['select_one', 'select_multiple'], true) ? $listName : null,
        ];

        $survey = $this->insertQuestionIntoSurvey($survey, $questionRow, $groupKey, $groupLabel);

        if (in_array($type, ['select_one', 'select_multiple'], true)) {
            $choices = $this->mergeChoicesFromText($choices, $listName, (string) ($validated['choice_options'] ?? ''));
        }

        $mobileQuestionnaire->update([
            'survey' => array_values($survey),
            'choices' => array_values($choices),
        ]);

        return redirect()
            ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
            ->with('success', 'Question ajoutée au questionnaire.');
    }

    public function updateGrouped(Request $request, MobileQuestionnaire $mobileQuestionnaire): RedirectResponse
    {
        $activeGroupTab = trim((string) $request->input('active_group_tab', ''));

        $actionType = trim((string) $request->input('action_type', 'save'));
        if (Str::startsWith($actionType, 'delete_question:')) {
            $rawIndex = trim((string) Str::after($actionType, 'delete_question:'));
            $index = ctype_digit($rawIndex) ? (int) $rawIndex : $rawIndex;
            $survey = $mobileQuestionnaire->survey ?? [];

            if (!array_key_exists($index, $survey)) {
                return redirect()
                    ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                    ->with('error', 'Question introuvable pour suppression.')
                    ->with('active_group_tab', $activeGroupTab);
            }

            $type = Str::lower(trim((string) ($survey[$index]['type'] ?? '')));
            if ($this->isGroupStartType($type) || $this->isGroupEndType($type)) {
                return redirect()
                    ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                    ->with('error', 'Suppression impossible : cette ligne est une structure de groupe.')
                    ->with('active_group_tab', $activeGroupTab);
            }

            unset($survey[$index]);
            $mobileQuestionnaire->update([
                'survey' => array_values($survey),
            ]);

            return redirect()
                ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                ->with('success', 'Question supprimée avec succès.')
                ->with('active_group_tab', $activeGroupTab);
        }

        if (Str::startsWith($actionType, 'delete_group:')) {
            $groupKey = trim((string) Str::after($actionType, 'delete_group:'));
            if ($groupKey === '' || $groupKey === 'default') {
                return redirect()
                    ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                    ->with('error', 'Ce groupe ne peut pas être supprimé.')
                    ->with('active_group_tab', $activeGroupTab);
            }

            $survey = $mobileQuestionnaire->survey ?? [];
            $updatedSurvey = $this->removeGroupFromSurvey($survey, $groupKey);
            if ($updatedSurvey === $survey) {
                return redirect()
                    ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                    ->with('error', 'Groupe introuvable pour suppression.')
                    ->with('active_group_tab', $activeGroupTab);
            }

            $mobileQuestionnaire->update([
                'survey' => array_values($updatedSurvey),
            ]);

            return redirect()
                ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
                ->with('success', 'Groupe supprimé avec succès.')
                ->with('active_group_tab', $activeGroupTab);
        }

        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.label' => ['required', 'string', 'max:255'],
            'rows.*.name' => ['nullable', 'string', 'max:120'],
            'rows.*.type' => ['required', 'in:text,integer,decimal,select_one,select_multiple,note'],
            'rows.*.list_name' => ['nullable', 'string', 'max:120'],
            'rows.*.required' => ['nullable', 'boolean'],
        ]);

        $survey = $mobileQuestionnaire->survey ?? [];
        $rows = $validated['rows'] ?? [];
        $questionTypes = ['text', 'integer', 'decimal', 'select_one', 'select_multiple', 'note'];

        $editableIndexes = collect(array_keys($rows))
            ->map(fn ($index) => (int) $index)
            ->filter(fn ($index) => array_key_exists($index, $survey))
            ->sort()
            ->values()
            ->all();

        $usedNames = [];
        foreach ($survey as $index => $row) {
            if (in_array((int) $index, $editableIndexes, true)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $usedNames[] = $name;
            }
        }

        foreach ($editableIndexes as $index) {
            $rowInput = $rows[(string) $index] ?? $rows[$index] ?? null;
            if (!is_array($rowInput)) {
                continue;
            }

            $type = (string) ($rowInput['type'] ?? '');
            if (!in_array($type, $questionTypes, true)) {
                continue;
            }

            $listName = trim((string) ($rowInput['list_name'] ?? ''));
            if (in_array($type, ['select_one', 'select_multiple'], true) && $listName === '') {
                return back()->withInput()->withErrors([
                    "rows.{$index}.list_name" => 'Le nom de liste est requis pour ce type de question.',
                ]);
            }

            $existingName = trim((string) ($survey[$index]['name'] ?? ''));
            $nextName = trim((string) ($rowInput['name'] ?? ''));
            $nextName = preg_replace('/[^a-zA-Z0-9_]/', '_', $nextName ?? '') ?? '';
            $nextName = trim((string) $nextName, '_');
            if ($nextName === '') {
                $nextName = $existingName !== '' ? $existingName : "question_{$index}";
            }
            $nextName = $this->makeUniqueName($nextName, $usedNames);
            $usedNames[] = $nextName;

            $label = trim((string) ($rowInput['label'] ?? ''));
            $isRequired = (bool) ($rowInput['required'] ?? false);

            $survey[$index]['name'] = $nextName;
            $survey[$index]['label'] = $label;
            $survey[$index]['label_fr'] = $label;
            $survey[$index]['type'] = in_array($type, ['select_one', 'select_multiple'], true) ? "{$type} {$listName}" : $type;
            $survey[$index]['list_name'] = in_array($type, ['select_one', 'select_multiple'], true) ? $listName : null;
            $survey[$index]['required'] = $isRequired ? 'yes' : '';
        }

        $mobileQuestionnaire->update([
            'survey' => array_values($survey),
        ]);

        return redirect()
            ->route('admin.mobile-questionnaires.edit', $mobileQuestionnaire)
            ->with('success', 'Questions mises à jour par groupe.')
            ->with('active_group_tab', $activeGroupTab);
    }

    private function removeGroupFromSurvey(array $survey, string $groupKey): array
    {
        $startKey = null;
        $endKey = null;
        $groupDepth = 0;
        $stack = [];

        foreach ($survey as $key => $row) {
            $type = Str::lower(trim((string) ($row['type'] ?? '')));

            if ($this->isGroupStartType($type)) {
                $currentGroupKey = trim((string) ($row['name'] ?? ''));
                $stack[] = $currentGroupKey;

                if ($startKey === null && $currentGroupKey === $groupKey) {
                    $startKey = $key;
                    $groupDepth = count($stack);
                }

                continue;
            }

            if ($this->isGroupEndType($type)) {
                if ($startKey !== null && count($stack) === $groupDepth) {
                    $endKey = $key;
                    break;
                }
                array_pop($stack);
            }
        }

        if ($startKey === null) {
            return $survey;
        }

        if ($endKey === null) {
            $endKey = $startKey;
        }

        $keys = array_keys($survey);
        $startPosition = array_search($startKey, $keys, true);
        $endPosition = array_search($endKey, $keys, true);

        if ($startPosition === false || $endPosition === false) {
            return $survey;
        }

        $surveyValues = array_values($survey);

        // Supprimer uniquement les lignes de structure du groupe ciblé
        // (begin_* et end_*) et conserver les questions/sous-groupes internes.
        unset($surveyValues[$endPosition], $surveyValues[$startPosition]);

        return array_values($surveyValues);
    }

    public function update(Request $request, MobileQuestionnaire $mobileQuestionnaire): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'survey_json' => ['required', 'json'],
            'choices_json' => ['nullable', 'json'],
            'settings_json' => ['nullable', 'json'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);
        if ($isActive) {
            MobileQuestionnaire::query()
                ->where('code', $mobileQuestionnaire->code)
                ->where('id', '!=', $mobileQuestionnaire->id)
                ->update(['is_active' => false]);
        }

        $mobileQuestionnaire->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'survey' => json_decode($validated['survey_json'], true) ?? [],
            'choices' => json_decode($validated['choices_json'] ?? '[]', true) ?? [],
            'settings' => json_decode($validated['settings_json'] ?? '[]', true) ?? [],
            'is_active' => $isActive,
            'published_at' => $isActive ? now() : $mobileQuestionnaire->published_at,
        ]);

        return redirect()
            ->route('admin.mobile-questionnaires.index')
            ->with('success', 'Questionnaire mobile mis à jour.');
    }

    public function destroy(MobileQuestionnaire $mobileQuestionnaire): RedirectResponse
    {
        $questionnaireCode = $mobileQuestionnaire->code;
        $wasActive = (bool) $mobileQuestionnaire->is_active;

        $mobileQuestionnaire->delete();

        if ($wasActive) {
            $replacement = MobileQuestionnaire::query()
                ->where('code', $questionnaireCode)
                ->orderByDesc('version')
                ->first();

            if ($replacement) {
                $replacement->update([
                    'is_active' => true,
                    'published_at' => now(),
                ]);
            }
        }

        return redirect()
            ->route('admin.mobile-questionnaires.index')
            ->with('success', 'Questionnaire supprimé avec succès.');
    }

    public function importFromXlsx(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'xlsx_file' => ['required', 'file', 'mimes:xlsx'],
            'code' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $xlsxFile = $request->file('xlsx_file');
        if (! $xlsxFile || ! $xlsxFile->isValid()) {
            return back()->withInput()->with('error', 'Le fichier XLSX est invalide.');
        }

        $path = $xlsxFile->getRealPath();
        if ($path === false || ! is_file($path)) {
            return back()->withInput()->with('error', 'Le fichier XLSX téléversé est introuvable.');
        }

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            $code = 'service-cartography';
        }

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = 'Cartographie des services (XLSForm)';
        }

        $parser = new XlsFormParser();
        $parsed = $parser->parse($path);
        if (empty($parsed['survey'] ?? [])) {
            return back()->withInput()->with('error', 'Le fichier XLSX ne contient pas de feuille "survey" exploitable.');
        }

        $nextVersion = ((int) MobileQuestionnaire::query()->where('code', $code)->max('version')) + 1;
        MobileQuestionnaire::query()->where('code', $code)->update(['is_active' => false]);

        MobileQuestionnaire::query()->create([
            'code' => $code,
            'title' => $title,
            'description' => 'Version importée depuis XLSForm via interface web.',
            'version' => max(1, $nextVersion),
            'is_active' => true,
            'survey' => $parsed['survey'],
            'choices' => $parsed['choices'],
            'settings' => $parsed['settings'],
            'source_file' => (string) $xlsxFile->getClientOriginalName(),
            'published_at' => now(),
        ]);

        return redirect()
            ->route('admin.mobile-questionnaires.index')
            ->with('success', 'Questionnaire importé et activé.');
    }

    public function exportXlsx(MobileQuestionnaire $mobileQuestionnaire): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $surveyRows = collect($mobileQuestionnaire->survey ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'type' => (string) ($row['type'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'label::Français (fr)' => (string) ($row['label_fr'] ?? $row['label'] ?? ''),
                    'label::English (en)' => (string) ($row['label_en'] ?? ''),
                    'hint::Français (fr)' => (string) ($row['hint_fr'] ?? $row['hint'] ?? ''),
                    'hint::English (en)' => (string) ($row['hint_en'] ?? ''),
                    'required' => (string) ($row['required'] ?? ''),
                    'relevant' => (string) ($row['relevant'] ?? ''),
                    'appearance' => (string) ($row['appearance'] ?? ''),
                    'constraint' => (string) ($row['constraint'] ?? ''),
                    'constraint_message' => (string) ($row['constraint_message'] ?? ''),
                    'choice_filter' => (string) ($row['choice_filter'] ?? ''),
                    'calculation' => (string) ($row['calculation'] ?? ''),
                    'file' => (string) ($row['file'] ?? ''),
                ];
            })
            ->values()
            ->all();
        $this->buildXlsSheet($spreadsheet, 'survey', [
            'type',
            'name',
            'label::Français (fr)',
            'label::English (en)',
            'hint::Français (fr)',
            'hint::English (en)',
            'required',
            'relevant',
            'appearance',
            'constraint',
            'constraint_message',
            'choice_filter',
            'calculation',
            'file',
        ], $surveyRows);

        $choicesRows = collect($mobileQuestionnaire->choices ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) {
                return [
                    'list_name' => (string) ($row['list_name'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'label::Français (fr)' => (string) ($row['label_fr'] ?? $row['label'] ?? ''),
                    'label::English (en)' => (string) ($row['label_en'] ?? ''),
                    'province' => (string) ($row['province'] ?? ''),
                    'territoire' => (string) ($row['territoire'] ?? ''),
                    'zs' => (string) ($row['zs'] ?? ''),
                ];
            })
            ->values()
            ->all();
        $this->buildXlsSheet($spreadsheet, 'choices', [
            'list_name',
            'name',
            'label::Français (fr)',
            'label::English (en)',
            'province',
            'territoire',
            'zs',
        ], $choicesRows);

        $settingsData = $mobileQuestionnaire->settings ?? [];
        $settingsRow = [];
        if (is_array($settingsData)) {
            if (array_is_list($settingsData)) {
                $first = $settingsData[0] ?? [];
                if (is_array($first)) {
                    $settingsRow = $first;
                }
            } else {
                $settingsRow = $settingsData;
            }
        }
        $settingsHeaders = array_keys($settingsRow);
        if ($settingsHeaders === []) {
            $settingsHeaders = ['form_title', 'form_id', 'version'];
            $settingsRow = [
                'form_title' => (string) $mobileQuestionnaire->title,
                'form_id' => (string) $mobileQuestionnaire->code,
                'version' => (string) $mobileQuestionnaire->version,
            ];
        }
        $this->buildXlsSheet($spreadsheet, 'settings', $settingsHeaders, [$settingsRow]);

        $filename = sprintf(
            '%s-v%s-%s.xlsx',
            Str::slug((string) ($mobileQuestionnaire->code ?: 'questionnaire')),
            (string) $mobileQuestionnaire->version,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function extractGroups(array $survey): array
    {
        $groups = [
            ['key' => 'default', 'label' => 'Général'],
        ];
        $stack = [['key' => 'default', 'label' => 'Général']];

        foreach ($survey as $row) {
            $type = Str::lower(trim((string) ($row['type'] ?? '')));
            if ($this->isGroupStartType($type)) {
                $key = trim((string) ($row['name'] ?? ''));
                if ($key === '') {
                    $key = 'default';
                }
                $label = trim((string) ($row['label'] ?? $row['label_fr'] ?? $key));

                $stack[] = ['key' => $key, 'label' => $label];
                $depth = count($stack) - 1;

                $shouldDisplay = $depth === 1;
                if ($shouldDisplay && $key !== 'default') {
                    if (!collect($groups)->contains(fn ($group) => $group['key'] === $key)) {
                        $groups[] = [
                            'key' => $key,
                            'label' => $label,
                        ];
                    }
                }

                continue;
            }

            if (!$this->isGroupEndType($type)) {
                continue;
            }

            if (count($stack) > 1) {
                array_pop($stack);
            }
        }

        return $groups;
    }

    private function extractGroupedQuestions(array $survey): array
    {
        $grouped = [
            'default' => [
                'key' => 'default',
                'label' => 'Général',
                'children' => [],
                'questions' => [],
            ],
        ];

        $stack = [['key' => 'default', 'label' => 'Général']];
        foreach ($survey as $index => $row) {
            $type = Str::lower(trim((string) ($row['type'] ?? '')));

            if ($this->isGroupStartType($type)) {
                $groupKey = trim((string) ($row['name'] ?? ''));
                if ($groupKey === '') {
                    $groupKey = 'default';
                }
                $groupLabel = trim((string) ($row['label'] ?? $row['label_fr'] ?? $groupKey));

                $stack[] = ['key' => $groupKey, 'label' => $groupLabel];
                $depth = count($stack) - 1;

                $shouldDisplay = $depth === 1;
                if ($shouldDisplay && !array_key_exists($groupKey, $grouped)) {
                    $grouped[$groupKey] = [
                        'key' => $groupKey,
                        'label' => $groupLabel,
                        'children' => [],
                        'questions' => [],
                    ];
                }

                if ($depth >= 2) {
                    $parentGroup = $stack[1]['key'] ?? 'default';
                    if (!array_key_exists($parentGroup, $grouped)) {
                        $grouped[$parentGroup] = [
                            'key' => $parentGroup,
                            'label' => (string) ($stack[1]['label'] ?? $parentGroup),
                            'children' => [],
                            'questions' => [],
                        ];
                    }

                    $childSegments = array_slice($stack, 2);
                    $childPathKey = implode('::', array_map(
                        fn (array $segment) => (string) ($segment['key'] ?? ''),
                        $childSegments
                    ));
                    $childPathLabel = implode(' > ', array_map(
                        fn (array $segment) => (string) ($segment['label'] ?? $segment['key'] ?? ''),
                        $childSegments
                    ));

                    if ($childPathKey !== '' && !array_key_exists($childPathKey, $grouped[$parentGroup]['children'])) {
                        $grouped[$parentGroup]['children'][$childPathKey] = [
                            'key' => $childPathKey,
                            'label' => $childPathLabel !== '' ? $childPathLabel : $groupLabel,
                        ];
                    }
                }
                continue;
            }

            if ($this->isGroupEndType($type)) {
                if (count($stack) > 1) {
                    array_pop($stack);
                }
                continue;
            }

            if (in_array($type, ['start', 'end', 'today', 'deviceid', 'phonenumber', 'calculate'], true)) {
                continue;
            }

            $currentGroup = 'default';
            for ($i = count($stack) - 1; $i >= 1; $i--) {
                $candidate = $stack[$i];
                $candidateKey = (string) ($candidate['key'] ?? '');
                if ($candidateKey === '') {
                    continue;
                }
                if (array_key_exists($candidateKey, $grouped)) {
                    $currentGroup = $candidateKey;
                    break;
                }
            }

            if (!array_key_exists($currentGroup, $grouped)) {
                $grouped[$currentGroup] = [
                    'key' => $currentGroup,
                    'label' => $currentGroup,
                    'questions' => [],
                ];
            }

            $normalizedType = $this->normalizeQuestionType((string) ($row['type'] ?? ''));
            $childKey = null;
            $childLabel = null;
            if (count($stack) > 2) {
                $childSegments = array_slice($stack, 2);
                $childKey = implode('::', array_map(
                    fn (array $segment) => (string) ($segment['key'] ?? ''),
                    $childSegments
                ));
                $childLabel = implode(' > ', array_map(
                    fn (array $segment) => (string) ($segment['label'] ?? $segment['key'] ?? ''),
                    $childSegments
                ));
            }

            $grouped[$currentGroup]['questions'][] = [
                'index' => $index,
                'name' => (string) ($row['name'] ?? ''),
                'label' => (string) ($row['label'] ?? $row['label_fr'] ?? $row['name'] ?? ''),
                'type' => $normalizedType,
                'list_name' => (string) (($row['list_name'] ?? '') !== '' ? $row['list_name'] : $this->extractListNameFromType((string) ($row['type'] ?? ''))),
                'required' => (string) ($row['required'] ?? '') !== '',
                'child_key' => $childKey,
                'child_label' => $childLabel,
            ];
        }

        return array_values($grouped);
    }

    private function insertQuestionIntoSurvey(array $survey, array $questionRow, string $groupKey, string $groupLabel): array
    {
        if ($groupKey === '') {
            $survey[] = $questionRow;

            return $survey;
        }

        $stack = [];
        foreach ($survey as $index => $row) {
            $type = Str::lower(trim((string) ($row['type'] ?? '')));

            if ($this->isGroupStartType($type)) {
                $stack[] = trim((string) ($row['name'] ?? ''));
                continue;
            }

            if ($this->isGroupEndType($type)) {
                $closingGroup = array_pop($stack);
                if ($closingGroup === $groupKey) {
                    array_splice($survey, $index, 0, [$questionRow]);

                    return $survey;
                }
            }
        }

        $survey[] = [
            'type' => 'begin_group',
            'name' => $groupKey,
            'label' => $groupLabel !== '' ? $groupLabel : $groupKey,
            'label_fr' => $groupLabel !== '' ? $groupLabel : $groupKey,
            'label_en' => '',
            'hint' => '',
            'hint_fr' => '',
            'hint_en' => '',
            'required' => '',
            'relevant' => '',
            'appearance' => '',
            'constraint' => '',
            'constraint_message' => '',
            'choice_filter' => '',
            'calculation' => '',
            'file' => '',
            'list_name' => null,
        ];
        $survey[] = $questionRow;
        $survey[] = [
            'type' => 'end_group',
            'name' => '',
            'label' => '',
            'label_fr' => '',
            'label_en' => '',
            'hint' => '',
            'hint_fr' => '',
            'hint_en' => '',
            'required' => '',
            'relevant' => '',
            'appearance' => '',
            'constraint' => '',
            'constraint_message' => '',
            'choice_filter' => '',
            'calculation' => '',
            'file' => '',
            'list_name' => null,
        ];

        return $survey;
    }

    private function mergeChoicesFromText(array $choices, string $listName, string $rawOptions): array
    {
        $listName = trim($listName);
        if ($listName === '' || trim($rawOptions) === '') {
            return $choices;
        }

        $existingByList = collect($choices)
            ->where('list_name', $listName)
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();

        $labels = preg_split('/[\r\n,;]+/', $rawOptions) ?: [];
        foreach ($labels as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }

            $baseName = Str::snake(Str::lower($label));
            $baseName = trim($baseName, '_');
            if ($baseName === '') {
                $baseName = 'option';
            }

            $name = $this->makeUniqueName($baseName, $existingByList);
            $existingByList[] = $name;

            $choices[] = [
                'list_name' => $listName,
                'name' => $name,
                'label' => $label,
                'label_fr' => $label,
                'label_en' => '',
                'province' => '',
                'territoire' => '',
                'zs' => '',
            ];
        }

        return $choices;
    }

    private function makeUniqueName(string $baseName, array $existingNames): string
    {
        $candidate = $baseName;
        $suffix = 2;
        while (in_array($candidate, $existingNames, true)) {
            $candidate = "{$baseName}_{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeQuestionType(string $type): string
    {
        $normalized = Str::lower(trim($type));
        if (Str::startsWith($normalized, 'select_one')) {
            return 'select_one';
        }
        if (Str::startsWith($normalized, 'select_multiple')) {
            return 'select_multiple';
        }
        if (in_array($normalized, ['text', 'integer', 'decimal', 'note'], true)) {
            return $normalized;
        }

        return 'text';
    }

    private function extractListNameFromType(string $type): string
    {
        $normalized = trim($type);
        if (preg_match('/^select_one\s+(.+)$/i', $normalized, $matches)) {
            return trim((string) $matches[1]);
        }
        if (preg_match('/^select_multiple\s+(.+)$/i', $normalized, $matches)) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private function questionTypeOptions(): array
    {
        return [
            'text' => 'Texte',
            'integer' => 'Nombre entier',
            'decimal' => 'Nombre décimal',
            'select_one' => 'Choix unique',
            'select_multiple' => 'Choix multiple',
            'note' => 'Note / information',
        ];
    }

    private function buildXlsSheet(Spreadsheet $spreadsheet, string $name, array $headers, array $rows): void
    {
        $cleanName = preg_replace('/[\[\]\*\:\/\\\\\?]/', '_', trim($name)) ?: 'sheet';
        $sheet = new Worksheet($spreadsheet, Str::limit($cleanName, 31, ''));
        $spreadsheet->addSheet($sheet);

        foreach ($headers as $columnIndex => $header) {
            $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $columnIndex => $header) {
                $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $cell = $column . ($rowIndex + 2);
                $sheet->setCellValue($cell, (string) ($row[$header] ?? ''));
            }
        }

        foreach (range(1, max(1, count($headers))) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
    }

    private function isGroupStartType(string $type): bool
    {
        return preg_match('/^begin[ _](group|repeat)$/', $type) === 1;
    }

    private function isGroupEndType(string $type): bool
    {
        return preg_match('/^end[ _](group|repeat)$/', $type) === 1;
    }

}
