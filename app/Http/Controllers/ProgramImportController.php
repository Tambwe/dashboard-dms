<?php

namespace App\Http\Controllers;

use App\Models\ProgramActivity;
use App\Models\ProgramIndicator;
use App\Models\ProgramSubActivity;
use App\Services\ProgramImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramImportController extends Controller
{
    private const FILE_PATH = 'H:\\activites.xlsx';

    public function __construct()
    {
        $this->middleware(['auth', 'check.role:super_admin']);
    }

    // ─────────────────────────────────────────────────────────────────
    //  Import
    // ─────────────────────────────────────────────────────────────────

    public function showImport()
    {
        $fileExists = file_exists(self::FILE_PATH);

        return view('admin.programme.import', [
            'fileExists' => $fileExists,
            'filePath'   => self::FILE_PATH,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        try {
            $service = new ProgramImportService();
            $result  = $service->import(self::FILE_PATH);

            $ind  = $result['indicators'];
            $act  = $result['activities'];
            $sub  = $result['sub_activities'];
            $strat = $result['strategic_objectives'] ?? ['successes' => 0, 'errors' => []];
            $sect  = $result['sector_objectives'] ?? ['successes' => 0, 'errors' => []];
            $plans = $result['plans'] ?? ['successes' => 0, 'errors' => []];

            $allErrors = array_merge(
                $ind['errors'] ?? [],
                $act['errors'] ?? [],
                $sub['errors'] ?? [],
                $strat['errors'] ?? [],
                $sect['errors'] ?? [],
                $plans['errors'] ?? [],
                $result['errors'] ?? []
            );

            $message = sprintf(
                'Import terminé : %d objectif(s) sectoriel(s), %d objectif(s) stratégique(s), %d indicateur(s), %d activité(s), %d sous-activité(s), %d plan(s).',
                $sect['successes'],
                $strat['successes'],
                $ind['successes'],
                $act['successes'],
                $sub['successes'],
                $plans['successes']
            );

            if (!empty($allErrors)) {
                return redirect()->route('admin.programme.import.show')
                    ->with('warning', $message)
                    ->with('import_errors', $allErrors);
            }

            return redirect()->route('admin.programme.import.show')
                ->with('success', $message);

        } catch (\RuntimeException $e) {
            return redirect()->route('admin.programme.import.show')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('admin.programme.import.show')
                ->with('error', 'Erreur inattendue lors de l\'import : ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  Téléchargement du modèle Excel vierge
    // ─────────────────────────────────────────────────────────────────

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildSheet($spreadsheet, 'Indicateurs', [
            'Indicateur_ID', 'Code_Indicateur', 'Libelle_Indicateur', 'Unite_Mesure',
            'Frequence', 'Responsable', 'Source_Verification', 'Actif',
        ], [
            ['IND-001', 'CODE-IND-001', 'Libellé indicateur exemple', 'Site', 'Mensuelle', 'MEAL', 'Rapport terrain', 'Oui'],
        ]);

        $this->buildSheet($spreadsheet, 'Activites', [
            'Activite_ID', 'Code_Activite', 'Libelle_Activite', 'Indicateur_ID',
            'Axe_Programme', 'Chef_Projet', 'Statut', 'Date_Debut_Prevue', 'Date_Fin_Prevue',
        ], [
            ['ACT-001', 'CODE-ACT-001', 'Libellé activité exemple', 'IND-001', 'Axe 1', 'Chef de projet', 'Planifie', '2026-01-01', '2026-12-31'],
        ]);

        $this->buildSheet($spreadsheet, 'Sous_activites', [
            'Sous_Activite_ID', 'Code_Sous_Activite', 'Libelle_Sous_Activite', 'Activite_ID',
            'Site', 'Province', 'Territoire', 'Zone_Sante', 'Date_Debut_Prevue', 'Date_Fin_Prevue', 'Statut',
        ], [
            ['SA-001', 'CODE-SA-001', 'Libellé sous-activité exemple', 'ACT-001', 'Site A', 'Nord-Kivu', 'Goma', 'Kyeshero', '2026-01-01', '2026-06-30', 'Planifie'],
        ]);

        $filename = 'activites_modele_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function buildSheet(Spreadsheet $spreadsheet, string $name, array $headers, array $rows): void
    {
        $ws = new Worksheet($spreadsheet, $name);
        $spreadsheet->addSheet($ws);

        // Headers ligne 1
        foreach ($headers as $col => $header) {
            $ws->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastCol}1";

        $ws->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1D5DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Données exemple
        foreach ($rows as $rowIdx => $rowData) {
            foreach ($rowData as $col => $value) {
                $ws->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $value);
            }
        }

        // Auto-size
        foreach (range(1, count($headers)) as $colIdx) {
            $ws->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        // Freeze row 1
        $ws->freezePane('A2');
    }

    // ─────────────────────────────────────────────────────────────────
    //  Listes CRUD (lecture seule)
    // ─────────────────────────────────────────────────────────────────

    public function indicatorsIndex()
    {
        $indicators = ProgramIndicator::withCount('activities')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.indicateurs.index', compact('indicators'));
    }

    public function activitiesIndex()
    {
        $activities = ProgramActivity::with('indicator')
            ->withCount('subActivities')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.activites.index', compact('activities'));
    }

    public function subActivitiesIndex()
    {
        $subActivities = ProgramSubActivity::with('activity.indicator')
            ->orderBy('code')
            ->paginate(25);

        return view('admin.programme.sous-activites.index', compact('subActivities'));
    }
}
