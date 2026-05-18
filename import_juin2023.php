<?php
/**
 * Script d'import: Master List DRC Juillet 2023 -> SiteMouvementPopulation Juin 2023
 *
 * Correspondance principale: NOM DU SITE (col H) -> Site::nom dans la DB
 * Désambiguïsation secondaire: CODE SITE Excel (col I) -> Site::code_site
 * Type mouvement: recensement
 * Date: 2023-06-30
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use PhpOffice\PhpSpreadsheet\IOFactory;

// ============================================================
// CONFIG
// ============================================================
$FILE = 'H:\007 - 31072023_Master List DRC_Juillet 2023_v3.xlsx';
$DATE = '2023-06-30';
$PERIODE = '2023-06';
$TYPE = 'recensement';
$SOURCE = 'Master List DRC Juillet 2023';
$DRY_RUN = isset($argv[1]) && $argv[1] === '--dry-run';

echo ($DRY_RUN ? "=== MODE SIMULATION (dry-run) ===" : "=== MODE IMPORT REEL ===") . PHP_EOL;
echo "Date: $DATE | Type: $TYPE | Fichier: $FILE" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;

// ============================================================
// CHARGEMENT EXCEL
// ============================================================
echo "Chargement du fichier Excel..." . PHP_EOL;
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($FILE);
$sheet = $spreadsheet->getSheetByName('DRC_Master List Data_Jul2023') ?? $spreadsheet->getSheet(1);
$maxRow = $sheet->getHighestRow();

echo "Preloading cell data..." . PHP_EOL;
$sheetData = $sheet->toArray(null, true, false, true);
$spreadsheet->disconnectWorksheets();
unset($spreadsheet, $sheet);

echo "Lignes trouvees: " . ($maxRow - 1) . " (hors en-tete)" . PHP_EOL . PHP_EOL;

// ============================================================
// CHARGEMENT DES SITES DB
// ============================================================
$allSites = Site::select('id', 'code_site', 'nom')->get();

function normalizeStr(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[-_\s]+/', ' ', $value);
    $value = preg_replace('/[^a-z0-9 ]/', '', $value);

    return trim($value);
}

$sitesByNorm = [];
$sitesByCode = [];
foreach ($allSites as $site) {
    $sitesByNorm[normalizeStr($site->nom)][] = $site;

    if (!empty($site->code_site)) {
        $sitesByCode[normalizeStr((string) $site->code_site)] = $site;
    }
}

// ============================================================
// LECTURE LIGNE PAR LIGNE ET MATCHING
// ============================================================
$stats = [
    'total' => 0,
    'matched' => 0,
    'no_match' => 0,
    'duplicate' => 0,
    'imported' => 0,
    'error' => 0,
];

$seenSiteIds = [];
$noMatchList = [];
$dupList = [];

echo str_repeat('-', 70) . PHP_EOL;

for ($row = 2; $row <= $maxRow; $row++) {
    $stats['total']++;
    $record = $sheetData[$row] ?? [];

    $province = trim((string) ($record['B'] ?? ''));
    $siteName = trim((string) ($record['H'] ?? ''));
    $codeExcel = trim((string) ($record['I'] ?? ''));

    $menages = $record['O'] ?? null;
    $individus = $record['P'] ?? null;
    $hommes = $record['Q'] ?? null;
    $femmes = $record['R'] ?? null;
    $f0_5 = $record['Q'] ?? null;
    $f6_17 = $record['R'] ?? null;
    $f18_59 = $record['S'] ?? null;
    $f60 = $record['T'] ?? null;
    $h0_5 = $record['U'] ?? null;
    $h6_17 = $record['V'] ?? null;
    $h18_59 = $record['W'] ?? null;
    $h60 = $record['X'] ?? null;
    $gestionnaire = trim((string) ($record['Y'] ?? ''));
    $coordinateur = trim((string) ($record['Z'] ?? ''));

    $toInt = function ($value) {
        if ($value === null || $value === '' || strtoupper((string) $value) === 'N/A') {
            return null;
        }

        return (int) $value;
    };

    $menages = $toInt($menages) ?? 0;
    $individus = $toInt($individus) ?? 0;
    $hommes = $toInt($hommes) ?? 0;
    $femmes = $toInt($femmes) ?? 0;
    $f0_5 = $toInt($f0_5) ?? 0;
    $f6_17 = $toInt($f6_17) ?? 0;
    $f18_59 = $toInt($f18_59) ?? 0;
    $f60 = $toInt($f60) ?? 0;
    $h0_5 = $toInt($h0_5) ?? 0;
    $h6_17 = $toInt($h6_17) ?? 0;
    $h18_59 = $toInt($h18_59) ?? 0;
    $h60 = $toInt($h60) ?? 0;

    if ($siteName === '') {
        continue;
    }

    $normName = normalizeStr($siteName);
    $normCode = normalizeStr($codeExcel);
    $matched = $sitesByNorm[$normName] ?? null;

    if (is_array($matched) && count($matched) > 1 && $normCode !== '' && isset($sitesByCode[$normCode])) {
        $matched = [$sitesByCode[$normCode]];
    }

    if (!$matched && $normCode !== '' && isset($sitesByCode[$normCode])) {
        $matched = [$sitesByCode[$normCode]];
    }

    if (!$matched) {
        foreach ($sitesByNorm as $dbNorm => $dbSites) {
            if (!str_contains($dbNorm, $normName) && !str_contains($normName, $dbNorm)) {
                continue;
            }

            $lenRatio = strlen($normName) > 0
                ? min(strlen($dbNorm), strlen($normName)) / max(strlen($dbNorm), strlen($normName))
                : 0;

            if ($lenRatio < 0.65) {
                continue;
            }

            if (count($dbSites) > 1 && $normCode !== '' && isset($sitesByCode[$normCode])) {
                $matched = [$sitesByCode[$normCode]];
            } else {
                $matched = $dbSites;
            }

            break;
        }
    }

    if (!$matched) {
        $stats['no_match']++;
        $noMatchList[] = ['row' => $row, 'nom' => $siteName, 'province' => $province, 'code_site' => $codeExcel];
        printf("  NO MATCH  | Ligne %-4d | %-45s | %s\n", $row, $siteName, $province);
        continue;
    }

    $site = $matched[0];
    $stats['matched']++;

    if (isset($seenSiteIds[$site->id])) {
        $stats['duplicate']++;
        $dupList[] = ['row' => $row, 'nom' => $siteName, 'site_id' => $site->id, 'reason' => 'doublon dans le fichier'];
        printf("  DOUBLON   | Ligne %-4d | %-45s | site_id=%d | doublon dans le fichier\n", $row, $siteName, $site->id);
        continue;
    }

    $exists = SiteMouvementPopulation::where('site_id', $site->id)
        ->where('date_mouvement', $DATE)
        ->where('type_mouvement', $TYPE)
        ->exists();

    if ($exists) {
        $stats['duplicate']++;
        $dupList[] = ['row' => $row, 'nom' => $siteName, 'site_id' => $site->id, 'reason' => 'deja present en base'];
        printf("  DOUBLON   | Ligne %-4d | %-45s | site_id=%d | deja present en base\n", $row, $siteName, $site->id);
        continue;
    }

    $seenSiteIds[$site->id] = true;

    if ($DRY_RUN) {
        $stats['imported']++;
        printf("  [SIM] OK  | Ligne %-4d | %-45s | site_id=%d | %d individus\n", $row, $siteName, $site->id, $individus);
        continue;
    }

    try {
        SiteMouvementPopulation::create([
            'site_id' => $site->id,
            'date_mouvement' => $DATE,
            'type_mouvement' => $TYPE,
            'raison_mouvement_id' => null,
            'periode' => $PERIODE,
            'menages' => $menages,
            'individus' => $individus,
            'h_0_5' => $h0_5,
            'h_6_17' => $h6_17,
            'h_18_59' => $h18_59,
            'h_60_plus' => $h60,
            'f_0_5' => $f0_5,
            'f_6_17' => $f6_17,
            'f_18_59' => $f18_59,
            'f_60_plus' => $f60,
            'source' => $SOURCE,
            'description' => "Province: $province | Code Excel: $codeExcel | Gestionnaire: $gestionnaire | Coordinateur: $coordinateur | Hommes: $hommes | Femmes: $femmes",
            'statut' => 'valide',
            'created_by' => 1,
        ]);

        $stats['imported']++;
        printf("  IMPORTE   | Ligne %-4d | %-45s | site_id=%d | %d individus\n", $row, $siteName, $site->id, $individus);
    } catch (\Throwable $exception) {
        $stats['error']++;
        printf("  ERREUR    | Ligne %-4d | %-45s | %s\n", $row, $siteName, $exception->getMessage());
    }
}

// ============================================================
// RAPPORT FINAL
// ============================================================
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo "=== RAPPORT FINAL ===" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;
printf("Total lignes Excel       : %d\n", $stats['total']);
printf("Sites correspondants     : %d\n", $stats['matched']);
printf("Sans correspondance      : %d\n", $stats['no_match']);
printf("Doublons skippes         : %d\n", $stats['duplicate']);
printf("Importes%s              : %d\n", $DRY_RUN ? ' (simulation)' : '             ', $stats['imported']);
printf("Erreurs                  : %d\n", $stats['error']);

if (!empty($noMatchList)) {
    echo PHP_EOL . "=== SITES SANS CORRESPONDANCE ===" . PHP_EOL;
    foreach ($noMatchList as $item) {
        printf(
            "  Ligne %d: %-45s | Province: %s | Code: %s\n",
            $item['row'],
            $item['nom'],
            $item['province'],
            $item['code_site']
        );
    }
}

if (!empty($dupList)) {
    echo PHP_EOL . "=== DOUBLONS DETECTES ===" . PHP_EOL;
    foreach ($dupList as $item) {
        printf(
            "  Ligne %d: %-45s | site_id=%d | %s\n",
            $item['row'],
            $item['nom'],
            $item['site_id'],
            $item['reason']
        );
    }
}