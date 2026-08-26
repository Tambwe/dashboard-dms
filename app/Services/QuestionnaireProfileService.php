<?php

namespace App\Services;

use App\Models\MobileQuestionnaire;
use App\Models\MobileQuestionnaireSubmission;
use Illuminate\Support\Str;

class QuestionnaireProfileService
{
    public function groups(
        MobileQuestionnaire $questionnaire,
        ?MobileQuestionnaireSubmission $submission = null,
        ?array $visibleQuestionKeys = null
    ): array {
        $answers = is_array($submission?->answers) ? $submission->answers : [];
        $choices = is_array($questionnaire->choices) ? $questionnaire->choices : [];
        $groups = [];
        $stack = [];

        foreach ($questionnaire->survey ?? [] as $row) {
            $type = Str::lower(trim((string) ($row['type'] ?? '')));

            if ($this->isGroupStart($type)) {
                $stack[] = [
                    'key' => trim((string) ($row['name'] ?? '')),
                    'label' => $this->label($row, (string) ($row['name'] ?? 'Groupe')),
                ];

                if (count($stack) === 1) {
                    $key = $stack[0]['key'] ?: 'default';
                    $groups[$key] = [
                        'key' => $key,
                        'title' => $stack[0]['label'],
                        'icon' => $this->icon($stack[0]['label']),
                        'collected' => false,
                        'collector' => $submission?->user?->name,
                        'questions' => [],
                        'available_questions' => [],
                    ];
                }
                continue;
            }

            if ($this->isGroupEnd($type)) {
                array_pop($stack);
                continue;
            }

            if ($stack === [] || $this->isMetadataType($type)) {
                continue;
            }

            $field = trim((string) ($row['name'] ?? ''));
            if ($field === '') {
                continue;
            }

            $groupKey = $stack[0]['key'] ?: 'default';
            if (! isset($groups[$groupKey])) {
                continue;
            }

            $questionDefinition = [
                'key' => $field,
                'label' => $this->label($row, $field),
                'type' => $type,
                'subgroup' => count($stack) > 1 ? $stack[count($stack) - 1]['label'] : null,
                'default_visible' => false,
                'relevance_score' => $this->relevanceScore($row, $stack),
                'raw_value' => null,
            ];
            $groups[$groupKey]['available_questions'][] = $questionDefinition;

            if (! array_key_exists($field, $answers) || ! $this->hasValue($answers[$field])) {
                continue;
            }

            $question = array_merge($questionDefinition, [
                'value' => $this->formatValue($answers[$field], $row, $choices),
                'raw_value' => $answers[$field],
            ]);
            $lastAvailableQuestion = array_key_last($groups[$groupKey]['available_questions']);
            $groups[$groupKey]['available_questions'][$lastAvailableQuestion] = $question;
            $groups[$groupKey]['collected'] = true;

            if ($visibleQuestionKeys !== null && in_array($field, $visibleQuestionKeys, true)) {
                $groups[$groupKey]['questions'][] = $question;
            }
        }

        foreach ($groups as &$group) {
            $recommendedKeys = collect($group['available_questions'])
                ->filter(fn (array $question): bool => $question['raw_value'] !== null && $question['relevance_score'] > 0)
                ->sortByDesc('relevance_score')
                ->take(4)
                ->pluck('key')
                ->all();

            foreach ($group['available_questions'] as &$question) {
                $question['default_visible'] = in_array($question['key'], $recommendedKeys, true);
                unset($question['relevance_score']);
            }
            unset($question);

            if ($visibleQuestionKeys === null) {
                $group['questions'] = collect($group['available_questions'])
                    ->filter(fn (array $question): bool => $question['default_visible'])
                    ->map(function (array $question) use ($answers, $choices, $questionnaire): array {
                        $surveyQuestion = collect($questionnaire->survey ?? [])
                            ->firstWhere('name', $question['key']) ?? [];
                        $question['value'] = $this->formatValue(
                            $answers[$question['key']],
                            $surveyQuestion,
                            $choices
                        );

                        return $question;
                    })
                    ->values()
                    ->all();
            }
        }
        unset($group);

        return array_values($groups);
    }

    public function questionKeys(MobileQuestionnaire $questionnaire): array
    {
        return collect($questionnaire->survey ?? [])
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->values()
            ->all();
    }

    private function isGroupStart(string $type): bool
    {
        return in_array($type, ['begin_group', 'begin group', 'group'], true);
    }

    private function isGroupEnd(string $type): bool
    {
        return in_array($type, ['end_group', 'end group'], true);
    }

    private function isMetadataType(string $type): bool
    {
        return in_array($type, [
            'start',
            'end',
            'today',
            'deviceid',
            'phonenumber',
            'calculate',
            'note',
        ], true);
    }

    private function label(array $row, string $fallback): string
    {
        foreach (['label_fr', 'label', 'label_en'] as $field) {
            $label = trim((string) ($row[$field] ?? ''));
            if ($label !== '') {
                return $label;
            }
        }

        return Str::headline($fallback);
    }

    private function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    private function relevanceScore(array $question, array $stack): int
    {
        $label = Str::lower(Str::ascii($this->label($question, (string) ($question['name'] ?? ''))));
        $name = Str::lower(Str::ascii((string) ($question['name'] ?? '')));
        $searchable = "{$name} {$label}";

        if (Str::contains($searchable, [
            'nom complet',
            'numero de telephone',
            'telephone',
            'adresse electronique',
            'email',
            'e-mail',
            'accepte-t-il que ces coordonnees',
            'si autre',
            'autre, precise',
            'autre veuillez',
            'observations',
            'commentaire',
        ])) {
            return -100;
        }

        $rootGroup = Str::lower(Str::ascii((string) ($stack[0]['label'] ?? '')));
        $score = Str::contains($rootGroup, [
            'besoin',
            'wash',
            'sante',
            'securite alimentaire',
            'protection',
            'education',
            'acces aux services',
        ]) ? 2 : 0;

        if (Str::contains($searchable, ['prioritaire', 'risque', 'urgent', 'fonctionnel', 'disponible', 'acces'])) {
            $score += 8;
        }

        if (Str::contains($searchable, ['litre', 'latrine', 'distance', 'temps d attente', 'capacite'])) {
            $score += 7;
        }

        if (Str::contains($searchable, ['nombre', 'combien', 'taux', 'pourcentage'])) {
            $score += 5;
        }

        if (Str::startsWith(Str::lower((string) ($question['type'] ?? '')), ['select_one', 'select one'])) {
            $score += 2;
        }

        return $score;
    }

    private function formatValue(mixed $value, array $question, array $choices): string
    {
        $values = is_array($value)
            ? $value
            : (Str::startsWith(Str::lower((string) ($question['type'] ?? '')), 'select_multiple')
                ? preg_split('/\s+/', trim((string) $value)) ?: []
                : [$value]);
        $listName = trim((string) ($question['list_name'] ?? ''));
        if ($listName === '') {
            $typeParts = preg_split('/\s+/', trim((string) ($question['type'] ?? '')));
            $listName = (string) ($typeParts[1] ?? '');
        }

        $labels = array_map(function (mixed $selected) use ($choices, $listName): string {
            foreach ($choices as $choice) {
                if (
                    (string) ($choice['name'] ?? '') === (string) $selected
                    && ($listName === '' || (string) ($choice['list_name'] ?? '') === $listName)
                ) {
                    return $this->label($choice, (string) $selected);
                }
            }

            if (is_bool($selected)) {
                return $selected ? 'Oui' : 'Non';
            }

            return (string) $selected;
        }, $values);

        return implode(', ', array_filter($labels, fn (string $label): bool => $label !== ''));
    }

    private function icon(string $label): string
    {
        $normalized = Str::lower(Str::ascii($label));

        return match (true) {
            Str::contains($normalized, 'wash') => '💧',
            Str::contains($normalized, 'sante') => '🏥',
            Str::contains($normalized, 'education') => '🎓',
            Str::contains($normalized, ['abri', 'ame']) => '🏕️',
            Str::contains($normalized, 'protection') => '🛡️',
            Str::contains($normalized, 'alimentaire') => '🌾',
            Str::contains($normalized, 'population') => '👥',
            Str::contains($normalized, 'acteur') => '🗺️',
            default => '📋',
        };
    }
}
