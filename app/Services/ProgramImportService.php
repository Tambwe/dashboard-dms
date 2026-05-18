<?php

namespace App\Services;

use App\Models\ProgramIndicator;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityPlan;
use App\Models\ProgramSectorObjective;
use App\Models\ProgramStrategicObjective;
use App\Models\ProgramSubActivity;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramImportService
{
    /**
     * Importe les 3 niveaux (indicateurs → activités → sous-activités)
     * depuis le fichier Excel dont le chemin est passé en paramètre.
     *
     * @return array{indicators: array, activities: array, sub_activities: array}
     */
    public function import(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Le fichier est introuvable : {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);

        // Nouveau format demandé : une feuille unique avec hiérarchie A->L.
        $planningSheet = $this->resolvePlanningSheet($spreadsheet);
        if ($planningSheet) {
            return $this->importPlanningHierarchy($planningSheet);
        }

        // L'ordre est important : indicateurs d'abord (FK), activités ensuite, sous-activités en dernier
        $resultIndicators   = $this->importIndicators($spreadsheet);
        $resultActivities   = $this->importActivities($spreadsheet);
        $resultSubActivities = $this->importSubActivities($spreadsheet);

        return [
            'indicators'     => $resultIndicators,
            'activities'     => $resultActivities,
            'sub_activities' => $resultSubActivities,
        ];
    }

    private function resolvePlanningSheet(Spreadsheet $spreadsheet): ?Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $headerA = $this->normalizeHeader((string) $sheet->getCell('A1')->getFormattedValue());
            $headerB = $this->normalizeHeader((string) $sheet->getCell('B1')->getFormattedValue());
            $headerK = $this->normalizeHeader((string) $sheet->getCell('K1')->getFormattedValue());
            $headerL = $this->normalizeHeader((string) $sheet->getCell('L1')->getFormattedValue());

            $isNewLayout = $headerA === 'annee_programmation'
                && $headerB === 'sous_activite'
                && $headerK === 'objectifs_sectoriels'
                && $headerL === 'code_objectifs_sectoriels';

            if ($isNewLayout) {
                return $sheet;
            }
        }

        return null;
    }

    private function importPlanningHierarchy(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestDataRow();

        $errors = [];
        $sectorCodes = [];
        $strategicCodes = [];
        $indicatorCodes = [];
        $activityCodes = [];
        $subActivityCodes = [];
        $planRefs = [];

        DB::beginTransaction();
        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $yearRaw = $this->str($sheet->getCell("A{$row}")->getValue());
                $subLabel = $this->str($sheet->getCell("B{$row}")->getValue());
                $indFr = $this->str($sheet->getCell("C{$row}")->getValue());
                $indEn = $this->str($sheet->getCell("D{$row}")->getValue());
                $actEn = $this->str($sheet->getCell("E{$row}")->getValue());
                $actFr = $this->str($sheet->getCell("F{$row}")->getValue());
                $indHnrpCode = $this->str($sheet->getCell("G{$row}")->getValue());
                $indCode = $this->str($sheet->getCell("H{$row}")->getValue());
                $stratLabel = $this->str($sheet->getCell("I{$row}")->getValue());
                $stratCodeRaw = $this->str($sheet->getCell("J{$row}")->getValue());
                $sectorLabel = $this->str($sheet->getCell("K{$row}")->getValue());
                $sectorCodeRaw = $this->str($sheet->getCell("L{$row}")->getValue());

                if ($this->allNull([
                    $yearRaw,
                    $subLabel,
                    $indFr,
                    $indEn,
                    $actEn,
                    $actFr,
                    $indHnrpCode,
                    $indCode,
                    $stratLabel,
                    $stratCodeRaw,
                    $sectorLabel,
                    $sectorCodeRaw,
                ])) {
                    continue;
                }

                $year = is_numeric($yearRaw) ? (int) $yearRaw : null;
                if (!$year) {
                    $errors[] = "Ligne {$row} : Annee_Programmation (colonne A) invalide.";
                    continue;
                }

                $sectorCode = $this->buildCode($sectorCodeRaw, $sectorLabel, 'SEC');
                $strategicCode = $this->buildCode($stratCodeRaw, $stratLabel, 'STR');
                $indicatorCode = $this->buildCode($indHnrpCode, $indCode ?: $indFr ?: $indEn, 'IND');
                $indicatorRef = $this->buildCode($indCode, $indHnrpCode ?: $indFr ?: $indEn, 'INDREF');
                $activityCode = $this->buildCode(null, ($indicatorRef . '-' . ($actFr ?: $actEn ?: ('ACT-' . $row))), 'ACT');
                $subCode = $this->buildCode(null, ($subLabel ?: ('SUB-' . $row)) . '|' . $activityCode, 'SUB');

                if ($stratLabel === null && $stratCodeRaw === null) {
                    $errors[] = "Ligne {$row} : Objectif stratégique vide (colonnes I/J).";
                    continue;
                }
                if ($sectorLabel === null && $sectorCodeRaw === null) {
                    $errors[] = "Ligne {$row} : Objectif sectoriel vide (colonnes K/L).";
                    continue;
                }
                if ($indFr === null && $indEn === null && $indHnrpCode === null && $indCode === null) {
                    $errors[] = "Ligne {$row} : Indicateur incomplet (colonnes C/D/G/H).";
                    continue;
                }

                $sectorObjective = ProgramSectorObjective::updateOrCreate(
                    ['code' => $sectorCode],
                    ['label' => $sectorLabel ?? $sectorCode]
                );
                $sectorCodes[$sectorCode] = true;

                $strategicObjective = ProgramStrategicObjective::updateOrCreate(
                    ['code' => $strategicCode],
                    [
                        'label' => $stratLabel ?? $strategicCode,
                        'program_sector_objective_id' => $sectorObjective->id,
                    ]
                );
                $strategicCodes[$strategicCode] = true;

                $indicatorLabel = $indFr ?? $indEn ?? $indicatorCode;
                $indicator = ProgramIndicator::updateOrCreate(
                    ['reference' => $indicatorRef],
                    [
                        'code' => $indicatorCode,
                        'label' => $indicatorLabel,
                        'program_strategic_objective_id' => $strategicObjective->id,
                        'is_active' => true,
                    ]
                );
                $indicatorCodes[$indicatorCode] = true;

                $activityLabel = $actFr ?? $actEn ?? $activityCode;
                $activity = ProgramActivity::updateOrCreate(
                    ['code' => $activityCode],
                    [
                        'reference' => $activityCode,
                        'label' => $activityLabel,
                        'program_indicator_id' => $indicator->id,
                    ]
                );
                $activityCodes[$activityCode] = true;

                $subActivity = ProgramSubActivity::updateOrCreate(
                    ['code' => $subCode],
                    [
                        'reference' => $subCode,
                        'label' => $subLabel ?? $subCode,
                        'program_activity_id' => $activity->id,
                    ]
                );
                $subActivityCodes[$subCode] = true;

                $planReference = 'PLAN-' . substr(md5(implode('|', [
                    $year,
                    $sectorCode,
                    $strategicCode,
                    $indicatorCode,
                    $activityCode,
                    $subCode,
                ])), 0, 20);

                ProgramActivityPlan::updateOrCreate(
                    ['reference' => $planReference],
                    [
                        'program_year' => $year,
                        'program_indicator_id' => $indicator->id,
                        'program_activity_id' => $activity->id,
                        'program_sub_activity_id' => $subActivity->id,
                        'comment' => 'Import depuis H:\\activites.xlsx (ligne ' . $row . ')',
                    ]
                );
                $planRefs[$planReference] = true;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'sector_objectives' => ['successes' => count($sectorCodes), 'errors' => []],
            'strategic_objectives' => ['successes' => count($strategicCodes), 'errors' => []],
            'indicators' => ['successes' => count($indicatorCodes), 'errors' => []],
            'activities' => ['successes' => count($activityCodes), 'errors' => []],
            'sub_activities' => ['successes' => count($subActivityCodes), 'errors' => []],
            'plans' => ['successes' => count($planRefs), 'errors' => []],
            'errors' => $errors,
        ];
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->replace('__', '_')
            ->trim('_')
            ->toString();
    }

    private function buildCode(?string $preferred, ?string $fallback, string $prefix): string
    {
        $candidate = $preferred ?? $fallback ?? $prefix;
        $slug = Str::upper(Str::of($candidate)->ascii()->replace('|', '-')->slug('-')->toString());

        if ($slug === '') {
            $slug = $prefix . '-' . Str::upper(Str::random(8));
        }

        return Str::limit($slug, 100, '');
    }

    private function allNull(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Feuille : Indicateurs
    //  Colonnes : A=Indicateur_ID  B=Code  C=Libellé  D=Unité
    //             E=Fréquence  F=Responsable  G=Source_Vérification  H=Actif
    // ─────────────────────────────────────────────────────────────────
    private function importIndicators(Spreadsheet $spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Indicateurs');
        if (!$sheet) {
            return ['successes' => 0, 'errors' => ['La feuille "Indicateurs" est introuvable dans le fichier Excel.']];
        }

        $highestRow = $sheet->getHighestDataRow();
        $successes  = 0;
        $errors     = [];

        DB::beginTransaction();
        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $reference = trim((string) $sheet->getCell("A{$row}")->getValue());
                $code      = trim((string) $sheet->getCell("B{$row}")->getValue());
                $label     = trim((string) $sheet->getCell("C{$row}")->getValue());

                // Ligne vide → on saute
                if ($reference === '' && $code === '' && $label === '') {
                    continue;
                }

                if ($reference === '') {
                    $errors[] = "Ligne {$row} (Indicateur) : Indicateur_ID vide.";
                    continue;
                }
                if ($code === '') {
                    $errors[] = "Ligne {$row} (Indicateur) : Code_Indicateur vide.";
                    continue;
                }
                if ($label === '') {
                    $errors[] = "Ligne {$row} (Indicateur) : Libellé vide.";
                    continue;
                }

                $unit      = $this->str($sheet->getCell("D{$row}")->getValue());
                $frequency = $this->str($sheet->getCell("E{$row}")->getValue());
                $owner     = $this->str($sheet->getCell("F{$row}")->getValue());
                $verif     = $this->str($sheet->getCell("G{$row}")->getValue());
                $actifRaw  = strtolower(trim((string) $sheet->getCell("H{$row}")->getValue()));
                $isActive  = in_array($actifRaw, ['oui', 'yes', '1', 'true', 'vrai'], true);

                ProgramIndicator::updateOrCreate(
                    ['code' => $code],
                    [
                        'reference'           => $reference,
                        'label'               => $label,
                        'unit'                => $unit,
                        'frequency'           => $frequency,
                        'owner'               => $owner,
                        'verification_source' => $verif,
                        'is_active'           => $isActive,
                    ]
                );
                $successes++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['successes' => $successes, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────
    //  Feuille : Activites
    //  Colonnes : A=Activite_ID  B=Code  C=Libellé  D=Indicateur_ID
    //             E=Axe_Programme  F=Chef_Projet  G=Statut
    //             H=Date_Début  I=Date_Fin
    // ─────────────────────────────────────────────────────────────────
    private function importActivities(Spreadsheet $spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Activites');
        if (!$sheet) {
            return ['successes' => 0, 'errors' => ['La feuille "Activites" est introuvable dans le fichier Excel.']];
        }

        // Carte référence → id pour résoudre les FK
        $indicatorMap = ProgramIndicator::pluck('id', 'reference')->toArray();

        $highestRow = $sheet->getHighestDataRow();
        $successes  = 0;
        $errors     = [];

        DB::beginTransaction();
        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $reference    = trim((string) $sheet->getCell("A{$row}")->getValue());
                $code         = trim((string) $sheet->getCell("B{$row}")->getValue());
                $label        = trim((string) $sheet->getCell("C{$row}")->getValue());
                $indicatorRef = trim((string) $sheet->getCell("D{$row}")->getValue());

                if ($reference === '' && $code === '' && $label === '') {
                    continue;
                }

                if ($reference === '') {
                    $errors[] = "Ligne {$row} (Activité) : Activite_ID vide.";
                    continue;
                }
                if ($code === '') {
                    $errors[] = "Ligne {$row} (Activité) : Code_Activite vide.";
                    continue;
                }
                if ($label === '') {
                    $errors[] = "Ligne {$row} (Activité) : Libellé vide.";
                    continue;
                }
                if ($indicatorRef === '') {
                    $errors[] = "Ligne {$row} (Activité) : Indicateur_ID vide.";
                    continue;
                }
                if (!array_key_exists($indicatorRef, $indicatorMap)) {
                    $errors[] = "Ligne {$row} (Activité) : Indicateur «{$indicatorRef}» introuvable — vérifiez la feuille Indicateurs.";
                    continue;
                }

                $axis        = $this->str($sheet->getCell("E{$row}")->getValue());
                $projectLead = $this->str($sheet->getCell("F{$row}")->getValue());
                $status      = $this->str($sheet->getCell("G{$row}")->getValue());
                $startDate   = $this->parseDate($sheet->getCell("H{$row}")->getValue());
                $endDate     = $this->parseDate($sheet->getCell("I{$row}")->getValue());

                ProgramActivity::updateOrCreate(
                    ['code' => $code],
                    [
                        'reference'            => $reference,
                        'label'                => $label,
                        'program_indicator_id' => $indicatorMap[$indicatorRef],
                        'program_axis'         => $axis,
                        'project_lead'         => $projectLead,
                        'status'               => $status,
                        'planned_start_date'   => $startDate,
                        'planned_end_date'     => $endDate,
                    ]
                );
                $successes++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['successes' => $successes, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────
    //  Feuille : Sous_activites
    //  Colonnes : A=Sous_Activite_ID  B=Code  C=Libellé  D=Activite_ID
    //             E=Site  F=Province  G=Territoire  H=Zone_Santé
    //             I=Date_Début  J=Date_Fin  K=Statut
    // ─────────────────────────────────────────────────────────────────
    private function importSubActivities(Spreadsheet $spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Sous_activites');
        if (!$sheet) {
            return ['successes' => 0, 'errors' => ['La feuille "Sous_activites" est introuvable dans le fichier Excel.']];
        }

        // Carte référence → id pour résoudre les FK
        $activityMap = ProgramActivity::pluck('id', 'reference')->toArray();

        $highestRow = $sheet->getHighestDataRow();
        $successes  = 0;
        $errors     = [];

        DB::beginTransaction();
        try {
            for ($row = 2; $row <= $highestRow; $row++) {
                $reference   = trim((string) $sheet->getCell("A{$row}")->getValue());
                $code        = trim((string) $sheet->getCell("B{$row}")->getValue());
                $label       = trim((string) $sheet->getCell("C{$row}")->getValue());
                $activityRef = trim((string) $sheet->getCell("D{$row}")->getValue());

                if ($reference === '' && $code === '' && $label === '') {
                    continue;
                }

                if ($reference === '') {
                    $errors[] = "Ligne {$row} (Sous-activité) : Sous_Activite_ID vide.";
                    continue;
                }
                if ($code === '') {
                    $errors[] = "Ligne {$row} (Sous-activité) : Code_Sous_Activite vide.";
                    continue;
                }
                if ($label === '') {
                    $errors[] = "Ligne {$row} (Sous-activité) : Libellé vide.";
                    continue;
                }
                if ($activityRef === '') {
                    $errors[] = "Ligne {$row} (Sous-activité) : Activite_ID vide.";
                    continue;
                }
                if (!array_key_exists($activityRef, $activityMap)) {
                    $errors[] = "Ligne {$row} (Sous-activité) : Activité «{$activityRef}» introuvable — vérifiez la feuille Activites.";
                    continue;
                }

                $siteName   = $this->str($sheet->getCell("E{$row}")->getValue());
                $province   = $this->str($sheet->getCell("F{$row}")->getValue());
                $territoire = $this->str($sheet->getCell("G{$row}")->getValue());
                $healthZone = $this->str($sheet->getCell("H{$row}")->getValue());
                $startDate  = $this->parseDate($sheet->getCell("I{$row}")->getValue());
                $endDate    = $this->parseDate($sheet->getCell("J{$row}")->getValue());
                $status     = $this->str($sheet->getCell("K{$row}")->getValue());

                ProgramSubActivity::updateOrCreate(
                    ['code' => $code],
                    [
                        'reference'           => $reference,
                        'label'               => $label,
                        'program_activity_id' => $activityMap[$activityRef],
                        'site_name'           => $siteName,
                        'province'            => $province,
                        'territoire'          => $territoire,
                        'health_zone'         => $healthZone,
                        'planned_start_date'  => $startDate,
                        'planned_end_date'    => $endDate,
                        'status'              => $status,
                    ]
                );
                $successes++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['successes' => $successes, 'errors' => $errors];
    }

    // ─────────────────────────────────────────────────────────────────
    //  Utilitaires
    // ─────────────────────────────────────────────────────────────────

    /** Retourne null pour une chaîne vide, la chaîne nettoyée sinon. */
    private function str(mixed $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    /**
     * Convertit une valeur de cellule Excel en date 'Y-m-d'.
     * Gère les dates sérielles numériques et les formats texte courants.
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // PhpSpreadsheet peut retourner un float (date sérielle Excel)
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $str);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }
}
