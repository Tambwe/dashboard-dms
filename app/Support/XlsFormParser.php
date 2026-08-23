<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsFormParser
{
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        $survey = $this->readRows($spreadsheet->getSheetByName('survey'));
        $choices = $this->readRows($spreadsheet->getSheetByName('choices'));
        $settingsRows = $this->readRows($spreadsheet->getSheetByName('settings'));

        return [
            'survey' => array_values(array_filter(array_map(fn (array $row) => $this->normalizeSurveyRow($row), $survey))),
            'choices' => array_values(array_filter(array_map(fn (array $row) => $this->normalizeChoiceRow($row), $choices))),
            'settings' => $settingsRows[0] ?? [],
        ];
    }

    private function readRows($sheet): array
    {
        if (! $sheet) {
            return [];
        }

        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestDataRow();

        $rawRows = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false);
        if (empty($rawRows)) {
            return [];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $rawRows[0]);
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $row = [];
            $hasValue = false;

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = $rawRow[$index] ?? null;
                if (is_string($value)) {
                    $value = trim($value);
                }

                if ($value !== null && $value !== '') {
                    $hasValue = true;
                }

                $row[$header] = $value;
            }

            if ($hasValue) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normalizeSurveyRow(array $row): ?array
    {
        $type = trim((string) ($row['type'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $labelFr = $this->pickLabel($row, ['label::Français (fr)', 'label', 'label::English (en)']);
        $hintFr = $this->pickLabel($row, ['hint::Français (fr)', 'hint', 'hint::English (en)']);

        if ($type === '' && $name === '' && $labelFr === '') {
            return null;
        }

        $listName = null;
        if (preg_match('/^select_one\s+(.+)$/i', $type, $matches)) {
            $listName = trim($matches[1]);
        } elseif (preg_match('/^select_multiple\s+(.+)$/i', $type, $matches)) {
            $listName = trim($matches[1]);
        } elseif (preg_match('/^select_one_from_file\s+(.+)$/i', $type, $matches)) {
            $listName = trim(str_replace('.csv', '_csv', strtolower($matches[1])));
        }

        return [
            'type' => $type,
            'name' => $name,
            'label' => $labelFr,
            'label_fr' => $labelFr,
            'label_en' => $this->pickLabel($row, ['label::English (en)']),
            'hint' => $hintFr,
            'hint_fr' => $hintFr,
            'hint_en' => $this->pickLabel($row, ['hint::English (en)']),
            'required' => (string) ($row['required'] ?? ''),
            'relevant' => (string) ($row['relevant'] ?? ''),
            'appearance' => (string) ($row['appearance'] ?? ''),
            'constraint' => (string) ($row['constraint'] ?? ''),
            'constraint_message' => (string) ($row['constraint_message'] ?? ''),
            'choice_filter' => (string) ($row['choice_filter'] ?? ''),
            'calculation' => (string) ($row['calculation'] ?? ''),
            'file' => (string) ($row['file'] ?? ''),
            'list_name' => $listName,
        ];
    }

    private function normalizeChoiceRow(array $row): ?array
    {
        $listName = trim((string) ($row['list_name'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $labelFr = $this->pickLabel($row, ['label::Français (fr)', 'label', 'label::English (en)']);

        if ($listName === '' || $name === '') {
            return null;
        }

        return [
            'list_name' => $listName,
            'name' => $name,
            'label' => $labelFr,
            'label_fr' => $labelFr,
            'label_en' => $this->pickLabel($row, ['label::English (en)']),
            'province' => (string) ($row['province'] ?? ''),
            'territoire' => (string) ($row['territoire'] ?? ''),
            'zs' => (string) ($row['zs'] ?? ''),
        ];
    }

    private function pickLabel(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
