<?php

namespace App\Services;

use Illuminate\Support\Str;

class HumanitarianStandardComparisonService
{
    private const SPHERE_WATER_URL = 'https://handbook.hspstandards.org/en/sphere/#ch006_004_001';
    private const SPHERE_SANITATION_URL = 'https://handbook.hspstandards.org/en/sphere/#ch006_005_002';
    private const SPHERE_HEALTH_URL = 'https://handbook.hspstandards.org/en/sphere/#ch009_003_001_001';
    private const INEE_STANDARD_10_URL = 'https://inee.org/minimum-standards/standard-10';

    public function annotate(array $groups, int $population): array
    {
        foreach ($groups as &$group) {
            foreach ($group['questions'] as &$question) {
                $question['standard'] = $this->comparison(
                    $question['label'],
                    $question['raw_value'],
                    $population,
                    $group['available_questions']
                );
            }
            unset($question);

            $group['standards_note'] = $this->standardsNote((string) $group['title']);
        }
        unset($group);

        return $groups;
    }

    private function comparison(string $label, mixed $rawValue, int $population, array $groupQuestions): ?array
    {
        $normalized = Str::lower(Str::ascii($label));
        $value = $this->numeric($rawValue);

        if ($value !== null && Str::contains($normalized, ['litre par personne', 'litres par personne'])) {
            return $this->minimum($value, 15, 'L/personne/jour', 'Pratique minimale établie', 'Sphere – Approvisionnement en eau 2.1', self::SPHERE_WATER_URL);
        }

        if ($value !== null && Str::contains($normalized, 'distance') && Str::contains($normalized, ['point d eau', 'point d’eau'])) {
            return $this->maximum($value, 500, 'm', 'Maximum', 'Sphere – Approvisionnement en eau 2.1', self::SPHERE_WATER_URL);
        }

        if ($value !== null && Str::contains($normalized, ['temps d attente', 'temps attente']) && Str::contains($normalized, 'eau')) {
            return $this->maximum($value, 30, 'minutes', 'Maximum', 'Sphere – Approvisionnement en eau 2.1', self::SPHERE_WATER_URL);
        }

        if ($value !== null && $value > 0 && $population > 0 && Str::contains($normalized, 'latrine')) {
            $ratio = $population / $value;

            return $this->maximum($ratio, 20, 'personnes/latrine', 'Cible partagée', 'Sphere – Gestion des excréments 3.2', self::SPHERE_SANITATION_URL);
        }

        if ($value !== null && $value > 0 && $population > 0 && Str::contains($normalized, ['structure de sante', 'structures de sante', 'centre de sante'])) {
            $ratio = $population / $value;

            return $this->maximum($ratio, 10000, 'personnes/structure', 'Guide de planification, pas un seuil universel', 'Sphere – Systèmes de santé 1.1', self::SPHERE_HEALTH_URL);
        }

        if ($value !== null && Str::contains($normalized, ['acces aux soins primaires', 'acces aux services de sante']) && Str::contains($normalized, ['pourcent', '%', 'une heure'])) {
            return $this->minimum($value, 80, '% à moins d’une heure de marche', 'Minimum', 'Sphere – Systèmes de santé 1.1', self::SPHERE_HEALTH_URL);
        }

        if ($value !== null && Str::contains($normalized, ['litre par eleve', 'litres par eleve'])) {
            return $this->minimum($value, 3, 'L/élève/jour', 'Recommandation scolaire', 'INEE 2024 – Standard 10', self::INEE_STANDARD_10_URL);
        }

        if ($value !== null && $value > 0 && Str::contains($normalized, ['toilette fille', 'latrine fille'])) {
            $girls = $this->findNumeric($groupQuestions, ['nombre de filles', 'filles inscrites', 'eleves filles']);
            if ($girls !== null) {
                return $this->maximum($girls / $value, 30, 'filles/toilette', 'Maximum', 'INEE 2024 – Standard 10', self::INEE_STANDARD_10_URL);
            }
        }

        if ($value !== null && $value > 0 && Str::contains($normalized, ['toilette garcon', 'latrine garcon'])) {
            $boys = $this->findNumeric($groupQuestions, ['nombre de garcons', 'garcons inscrits', 'eleves garcons']);
            if ($boys !== null) {
                return $this->maximum($boys / $value, 60, 'garçons/toilette', 'Maximum', 'INEE 2024 – Standard 10', self::INEE_STANDARD_10_URL);
            }
        }

        return null;
    }

    private function minimum(float $value, float $threshold, string $unit, string $context, string $source, string $url): array
    {
        return $this->result($value, $threshold, $value >= $threshold, "≥ {$threshold}", $unit, $context, $source, $url);
    }

    private function maximum(float $value, float $threshold, string $unit, string $context, string $source, string $url): array
    {
        return $this->result($value, $threshold, $value <= $threshold, "≤ {$threshold}", $unit, $context, $source, $url);
    }

    private function result(float $value, float $threshold, bool $meets, string $target, string $unit, string $context, string $source, string $url): array
    {
        return [
            'meets' => $meets,
            'measured' => round($value, 1),
            'threshold' => $threshold,
            'target' => "{$target} {$unit}",
            'unit' => $unit,
            'context' => $context,
            'source' => $source,
            'url' => $url,
        ];
    }

    private function findNumeric(array $questions, array $needles): ?float
    {
        foreach ($questions as $question) {
            $label = Str::lower(Str::ascii((string) $question['label']));
            if (Str::contains($label, $needles)) {
                return $this->numeric($question['raw_value']);
            }
        }

        return null;
    }

    private function numeric(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/-?\d+(?:[.,]\d+)?/', $value, $matches)) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return null;
    }

    private function standardsNote(string $title): ?string
    {
        $normalized = Str::lower(Str::ascii($title));

        if (Str::contains($normalized, 'education')) {
            return 'INEE ne fixe pas de ratio universel élèves/enseignant ou élèves/classe. Les comparaisons sont limitées à l’eau et aux toilettes scolaires lorsque ces données sont collectées.';
        }

        if (Str::contains($normalized, 'sante')) {
            return 'Le ratio de structure sanitaire est un guide de planification Sphere, à interpréter selon le contexte et non comme une norme universelle.';
        }

        return null;
    }
}
