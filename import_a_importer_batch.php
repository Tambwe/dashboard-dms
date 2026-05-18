<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$FOLDER = 'H:\\A IMPORTER';
$TYPE = 'recensement';
$DRY_RUN = in_array('--dry-run', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--folder=')) {
        $FOLDER = trim(substr($arg, 9), "\"'");
    }
}

echo ($DRY_RUN ? "=== MODE SIMULATION (dry-run) ===" : "=== MODE IMPORT REEL ===") . PHP_EOL;
echo "Dossier source: $FOLDER" . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL . PHP_EOL;

function normalizeStr(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[-_\s]+/', ' ', $value);
    $value = preg_replace('/[^a-z0-9 ]/', '', $value);

    return trim($value);
}

function findDataSheet(Spreadsheet $spreadsheet): Worksheet
{
    $bestSheet = null;
    $bestScore = -1;

    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        $title = normalizeStr($sheet->getTitle());
        if (str_contains($title, 'master list data')) {
            return $sheet;
        }

        $headers = [
            normalizeStr((string) $sheet->getCell('H1')->getValue()),
            normalizeStr((string) $sheet->getCell('O1')->getValue()),
            normalizeStr((string) $sheet->getCell('P1')->getValue()),
            normalizeStr((string) $sheet->getCell('H2')->getValue()),
            normalizeStr((string) $sheet->getCell('O2')->getValue()),
            normalizeStr((string) $sheet->getCell('P2')->getValue()),
        ];

        $score = 0;
        foreach ($headers as $header) {
            if (str_contains($header, 'nom du site')) {
                $score += 3;
            }
            if (str_contains($header, 'menages')) {
                $score += 2;
            }
            if (str_contains($header, 'individus')) {
                $score += 2;
            }
        }

        if ($sheet->getHighestRow() > 150) {
            $score += 1;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestSheet = $sheet;
        }
    }

    if ($bestSheet instanceof Worksheet && $bestScore > 0) {
        return $bestSheet;
    }

    if ($spreadsheet->getSheetCount() > 1) {
        return $spreadsheet->getSheet(1);
    }

    return $spreadsheet->getSheet(0);
}

function detectColumnMapping(array $sheetData): array
{
    $mapping = [
        'headerRow' => 1,
        'province' => 'B',
        'siteName' => 'H',
        'codeSite' => 'I',
        'typeSite' => 'J',
        'mecanisme' => 'K',
        'observation' => 'L',
        'menages' => 'O',
        'individus' => 'P',
        'f0_5' => 'Q',
        'f6_17' => 'R',
        'f18_59' => 'S',
        'f60' => 'T',
        'h0_5' => 'U',
        'h6_17' => 'V',
        'h18_59' => 'W',
        'h60' => 'X',
        'gestionnaire' => 'Y',
        'coordinateur' => 'Z',
    ];

    for ($row = 1; $row <= 4; $row++) {
        $cells = $sheetData[$row] ?? [];
        if (empty($cells)) {
            continue;
        }

        $found = ['siteName' => false, 'menages' => false, 'individus' => false];

        foreach ($cells as $col => $value) {
            $header = normalizeStr((string) $value);
            if ($header === '') {
                continue;
            }

            if (str_contains($header, 'nom du site')) {
                $mapping['siteName'] = $col;
                $found['siteName'] = true;
            }
            if (str_contains($header, 'code site')) {
                $mapping['codeSite'] = $col;
            }
            if ($header === 'province' || str_starts_with($header, 'province')) {
                $mapping['province'] = $col;
            }
            if (str_contains($header, 'type de site')) {
                $mapping['typeSite'] = $col;
            }
            if (str_contains($header, 'mecanisme') || str_contains($header, 'type de gestion')) {
                $mapping['mecanisme'] = $col;
            }
            if (str_contains($header, 'observation mecanisme')) {
                $mapping['observation'] = $col;
            }
            if (str_contains($header, 'menages')) {
                $mapping['menages'] = $col;
                $found['menages'] = true;
            }
            if (str_contains($header, 'individus')) {
                $mapping['individus'] = $col;
                $found['individus'] = true;
            }
            if (str_contains($header, '0 5 f')) {
                $mapping['f0_5'] = $col;
            }
            if (str_contains($header, '6 17 f')) {
                $mapping['f6_17'] = $col;
            }
            if (str_contains($header, '18 59 f')) {
                $mapping['f18_59'] = $col;
            }
            if (str_contains($header, '60 f') || str_contains($header, '60 f ')) {
                $mapping['f60'] = $col;
            }
            if (str_contains($header, '0 5 h')) {
                $mapping['h0_5'] = $col;
            }
            if (str_contains($header, '6 17 h')) {
                $mapping['h6_17'] = $col;
            }
            if (str_contains($header, '18 59 h')) {
                $mapping['h18_59'] = $col;
            }
            if (str_contains($header, '60 h') || str_contains($header, '60 h ')) {
                $mapping['h60'] = $col;
            }
            if (str_contains($header, 'gestionnaire')) {
                $mapping['gestionnaire'] = $col;
            }
            if (str_contains($header, 'coordinateur')) {
                $mapping['coordinateur'] = $col;
            }
        }

        if ($found['siteName'] && $found['menages'] && $found['individus']) {
            $mapping['headerRow'] = $row;
            return $mapping;
        }
    }

    return $mapping;
}

function extractReferenceDate(string $filename, string $sheetName = ''): ?Carbon
{
    $source = normalizeStr($filename . ' ' . $sheetName);
    $filenameSource = normalizeStr($filename);

    $monthMap = [
        'janvier' => 1, 'jan' => 1,
        'fevrier' => 2, 'fev' => 2, 'february' => 2, 'feb' => 2,
        'mars' => 3, 'mar' => 3,
        'avril' => 4, 'avr' => 4, 'april' => 4, 'apr' => 4,
        'mai' => 5, 'may' => 5,
        'juin' => 6, 'jun' => 6, 'june' => 6,
        'juillet' => 7, 'juil' => 7, 'jul' => 7, 'july' => 7,
        'aout' => 8, 'august' => 8, 'aug' => 8,
        'septembre' => 9, 'sep' => 9, 'september' => 9,
        'octobre' => 10, 'oct' => 10, 'october' => 10,
        'novembre' => 11, 'nov' => 11, 'november' => 11,
        'decembre' => 12, 'dec' => 12, 'december' => 12,
    ];

    $year = null;
    if (preg_match_all('/(20\d{2})/', $source, $yearMatches) && !empty($yearMatches[1])) {
        $year = (int) end($yearMatches[1]);
    }

    $month = null;
    $monthCandidates = [$filenameSource, $source];

    foreach ($monthMap as $token => $value) {
        $pattern = '/(^|[^a-z])' . preg_quote($token, '/') . '([^a-z]|$)/';
        foreach ($monthCandidates as $candidate) {
            if (preg_match($pattern, $candidate)) {
                $month = $value;
                break 2;
            }
        }
    }

    if ($year && $month) {
        return Carbon::create($year, $month, 1, 0, 0, 0)->endOfMonth();
    }

    if (!preg_match('/(\d{8})/', $filename, $matches)) {
        return null;
    }

    $raw = $matches[1];
    $day = (int) substr($raw, 0, 2);
    $month = (int) substr($raw, 2, 2);
    $year = (int) substr($raw, 4, 4);

    // Fallback si format YYYYMMDD (ex: 20231231)
    if ($year < 1900 || $year > 2100) {
        $year = (int) substr($raw, 0, 4);
        $month = (int) substr($raw, 4, 2);
        $day = (int) substr($raw, 6, 2);
    }

    try {
        return Carbon::create($year, $month, $day, 0, 0, 0);
    } catch (Throwable $e) {
        return null;
    }
}

function pickBestMatchByCategory(array $candidates, string $typeSiteExcel, string $mecanismeExcel, string $observationExcel): array
{
    if (count($candidates) <= 1) {
        return $candidates;
    }

    $signals = array_filter([
        normalizeStr($typeSiteExcel),
        normalizeStr($mecanismeExcel),
        normalizeStr($observationExcel),
    ]);

    if (empty($signals)) {
        return $candidates;
    }

    $filtered = array_values(array_filter($candidates, function ($site) use ($signals) {
        $siteCategory = normalizeStr((string) ($site->categorieSite->name ?? ''));
        $siteType = normalizeStr((string) ($site->typeSite->name ?? ''));

        foreach ($signals as $signal) {
            if ($signal === '') {
                continue;
            }

            if ($siteCategory !== '' && (str_contains($siteCategory, $signal) || str_contains($signal, $siteCategory))) {
                return true;
            }

            if ($siteType !== '' && (str_contains($siteType, $signal) || str_contains($signal, $siteType))) {
                return true;
            }
        }

        return false;
    }));

    return !empty($filtered) ? $filtered : $candidates;
}

$allSites = Site::with(['categorieSite:id,name', 'typeSite:id,name'])
    ->select('id', 'code_site', 'nom', 'categorie_site_id', 'type_site_id')
    ->get();

$sitesByNorm = [];
$sitesByCode = [];
foreach ($allSites as $site) {
    $sitesByNorm[normalizeStr($site->nom)][] = $site;

    if (!empty($site->code_site)) {
        $sitesByCode[normalizeStr((string) $site->code_site)] = $site;
    }
}

$files = glob($FOLDER . DIRECTORY_SEPARATOR . '*.xlsx') ?: [];
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($files)) {
    echo "Aucun fichier .xlsx trouve dans $FOLDER" . PHP_EOL;
    exit(0);
}

$global = [
    'files' => 0,
    'total' => 0,
    'matched' => 0,
    'no_match' => 0,
    'duplicate' => 0,
    'imported' => 0,
    'error' => 0,
];

foreach ($files as $filePath) {
    $filename = basename($filePath);
    $source = pathinfo($filename, PATHINFO_FILENAME);

    echo PHP_EOL . str_repeat('-', 80) . PHP_EOL;
    echo "Fichier: $filename" . PHP_EOL;

    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filePath);
    $sheet = findDataSheet($spreadsheet);
    $sheetName = $sheet->getTitle();
    $maxRow = $sheet->getHighestRow();
    $sheetData = $sheet->toArray(null, true, false, true);
    $map = detectColumnMapping($sheetData);
    $startRow = $map['headerRow'] + 1;

    $refDate = extractReferenceDate($filename, $sheetName);
    if (!$refDate) {
        echo "[SKIP] Date introuvable dans le nom/feuille: $filename" . PHP_EOL;
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        continue;
    }

    $movementDate = $refDate->copy()->subMonthNoOverflow()->endOfMonth();
    $date = $movementDate->format('Y-m-d');
    $periode = $movementDate->format('Y-m');

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $sheet);

    echo "Date mouvement: $date | Periode: $periode" . PHP_EOL;
    echo "Feuille utilisee: $sheetName | Lignes: " . ($maxRow - $map['headerRow']) . PHP_EOL;

    $stats = [
        'total' => 0,
        'matched' => 0,
        'no_match' => 0,
        'duplicate' => 0,
        'imported' => 0,
        'error' => 0,
    ];

    $seenSiteIds = [];

    for ($row = $startRow; $row <= $maxRow; $row++) {
        $stats['total']++;
        $record = $sheetData[$row] ?? [];

        $province = trim((string) ($record[$map['province']] ?? ''));
        $siteName = trim((string) ($record[$map['siteName']] ?? ''));
        $codeExcel = trim((string) ($record[$map['codeSite']] ?? ''));
        $typeSiteExcel = trim((string) ($record[$map['typeSite']] ?? ''));
        $mecanismeExcel = trim((string) ($record[$map['mecanisme']] ?? ''));
        $observationExcel = trim((string) ($record[$map['observation']] ?? ''));

        if ($siteName === '' || normalizeStr($siteName) === 'nom du site') {
            continue;
        }

        $toInt = function ($value): int {
            if ($value === null || $value === '' || strtoupper((string) $value) === 'N/A') {
                return 0;
            }

            return (int) $value;
        };

        $menages = $toInt($record[$map['menages']] ?? null);
        $individus = $toInt($record[$map['individus']] ?? null);
        $f0_5 = $toInt($record[$map['f0_5']] ?? null);
        $f6_17 = $toInt($record[$map['f6_17']] ?? null);
        $f18_59 = $toInt($record[$map['f18_59']] ?? null);
        $f60 = $toInt($record[$map['f60']] ?? null);
        $h0_5 = $toInt($record[$map['h0_5']] ?? null);
        $h6_17 = $toInt($record[$map['h6_17']] ?? null);
        $h18_59 = $toInt($record[$map['h18_59']] ?? null);
        $h60 = $toInt($record[$map['h60']] ?? null);
        $gestionnaire = trim((string) ($record[$map['gestionnaire']] ?? ''));
        $coordinateur = trim((string) ($record[$map['coordinateur']] ?? ''));

        $normName = normalizeStr($siteName);
        $normCode = normalizeStr($codeExcel);

        $matched = $sitesByNorm[$normName] ?? null;
        if (is_array($matched)) {
            $matched = pickBestMatchByCategory($matched, $typeSiteExcel, $mecanismeExcel, $observationExcel);
        }

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

                $dbSites = pickBestMatchByCategory($dbSites, $typeSiteExcel, $mecanismeExcel, $observationExcel);

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
            continue;
        }

        $site = $matched[0];
        $stats['matched']++;

        if (isset($seenSiteIds[$site->id])) {
            $stats['duplicate']++;
            continue;
        }

        $exists = SiteMouvementPopulation::where('site_id', $site->id)
            ->where('date_mouvement', $date)
            ->where('type_mouvement', $TYPE)
            ->exists();

        if ($exists) {
            $stats['duplicate']++;
            continue;
        }

        $seenSiteIds[$site->id] = true;

        if ($DRY_RUN) {
            $stats['imported']++;
            continue;
        }

        try {
            SiteMouvementPopulation::create([
                'site_id' => $site->id,
                'date_mouvement' => $date,
                'type_mouvement' => $TYPE,
                'raison_mouvement_id' => null,
                'periode' => $periode,
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
                'source' => $source,
                'description' => "Province: $province | Code Excel: $codeExcel | Type Site: $typeSiteExcel | Mecanisme: $mecanismeExcel | Obs: $observationExcel | Gestionnaire: $gestionnaire | Coordinateur: $coordinateur",
                'statut' => 'valide',
                'created_by' => 1,
            ]);

            $stats['imported']++;
        } catch (Throwable $e) {
            $stats['error']++;
        }
    }

    printf("Total lignes Excel       : %d\n", $stats['total']);
    printf("Sites correspondants     : %d\n", $stats['matched']);
    printf("Sans correspondance      : %d\n", $stats['no_match']);
    printf("Doublons skippes         : %d\n", $stats['duplicate']);
    printf("Importes%s              : %d\n", $DRY_RUN ? ' (simulation)' : '             ', $stats['imported']);
    printf("Erreurs                  : %d\n", $stats['error']);

    $global['files']++;
    $global['total'] += $stats['total'];
    $global['matched'] += $stats['matched'];
    $global['no_match'] += $stats['no_match'];
    $global['duplicate'] += $stats['duplicate'];
    $global['imported'] += $stats['imported'];
    $global['error'] += $stats['error'];
}

echo PHP_EOL . str_repeat('=', 80) . PHP_EOL;
echo "=== RAPPORT GLOBAL ===" . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;
printf("Fichiers traites         : %d\n", $global['files']);
printf("Total lignes Excel       : %d\n", $global['total']);
printf("Sites correspondants     : %d\n", $global['matched']);
printf("Sans correspondance      : %d\n", $global['no_match']);
printf("Doublons skippes         : %d\n", $global['duplicate']);
printf("Importes%s              : %d\n", $DRY_RUN ? ' (simulation)' : '             ', $global['imported']);
printf("Erreurs                  : %d\n", $global['error']);
