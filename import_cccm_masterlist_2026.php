<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$jobs = [
    [
        'file' => 'H:\\20260608_CCCM Master List_DRC_31_Mai_2026.xlsx',
        'date' => '2026-05-31',
        'periode' => '2026-05',
        'source' => 'Master List DRC Mai 2026',
    ],
    [
        'file' => 'H:\\20260805_CCCM Master List_DRC_31_Juillet_2026.xlsx',
        'date' => '2026-07-31',
        'periode' => '2026-07',
        'source' => 'Master List DRC Juillet 2026',
    ],
];

$DRY_RUN = isset($argv[1]) && $argv[1] === '--dry-run';

function normalizeString(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[-_\s]+/', ' ', $value);
    $value = preg_replace('/[^a-z0-9 ]/', '', $value);
    return trim($value);
}

function parseInt($value): int
{
    if ($value === null) {
        return 0;
    }

    $text = trim((string) $value);
    if ($text === '' || strtoupper($text) === 'N/A' || strtoupper($text) === 'NO DATA') {
        return 0;
    }

    $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '.', $text));
    if ($clean === '' || $clean === '-') {
        return 0;
    }

    return (int) round((float) $clean);
}

function readHeaderMap(array $header): array
{
    $map = [];
    foreach ($header as $column => $label) {
        $clean = normalizeString((string) $label);
        if ($clean === '') {
            continue;
        }
        $map[$clean] = $column;
    }
    return $map;
}

function getCell(array $row, array $map, string $key, ?string $fallback = null)
{
    $keyNorm = normalizeString($key);
    $column = $map[$keyNorm] ?? null;
    if ($column !== null && array_key_exists($column, $row)) {
        return $row[$column];
    }

    if ($fallback !== null) {
        $fallbackNorm = normalizeString($fallback);
        $column = $map[$fallbackNorm] ?? null;
        if ($column !== null && array_key_exists($column, $row)) {
            return $row[$column];
        }
    }

    return null;
}

$allSites = Site::select('id', 'code_site', 'nom')->get();
$sitesByNorm = [];
$sitesByCode = [];
foreach ($allSites as $site) {
    $siteNameNorm = normalizeString($site->nom ?? '');
    if ($siteNameNorm !== '') {
        $sitesByNorm[$siteNameNorm][] = $site;
    }

    $codeNorm = normalizeString((string) ($site->code_site ?? ''));
    if ($codeNorm !== '') {
        $sitesByCode[$codeNorm] = $site;
    }
}

foreach ($jobs as $job) {
    $file = $job['file'];
    $date = $job['date'];
    $periode = $job['periode'];
    $source = $job['source'];

    echo str_repeat('=', 80) . PHP_EOL;
    echo "Traitement : {$source} ({$date})" . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;

    if (!file_exists($file)) {
        echo "Fichier introuvable : {$file}" . PHP_EOL;
        continue;
    }

    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file);
    $sheetNames = $spreadsheet->getSheetNames();
    $totalRows = 0;
    $matched = 0;
    $noMatch = 0;
    $duplicate = 0;
    $imported = 0;
    $seenInFile = [];

    foreach ($sheetNames as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (!$sheet) {
            continue;
        }

        $rows = $sheet->toArray(null, false, false, true);
        $headerRowIndex = null;
        $headerMap = [];

        foreach ($rows as $index => $row) {
            foreach ($row as $cellValue) {
                if (is_string($cellValue) && preg_match('/nom du site/i', $cellValue)) {
                    $headerRowIndex = $index;
                    break 2;
                }
            }
        }

        if ($headerRowIndex === null) {
            continue;
        }

        $headerMap = readHeaderMap($rows[$headerRowIndex]);
        if (!isset($headerMap['nomdusite']) && !isset($headerMap['nom du site'])) {
            continue;
        }

        for ($rowIndex = $headerRowIndex + 1; $rowIndex <= $sheet->getHighestRow(); $rowIndex++) {
            $record = $rows[$rowIndex] ?? [];
            if (!is_array($record) || $record === []) {
                continue;
            }

            $totalRows++;

            $province = trim((string) (getCell($record, $headerMap, 'province', 'province*') ?? ''));
            $siteName = trim((string) (getCell($record, $headerMap, 'nomdusite', 'nom du site') ?? ''));
            $codeExcel = trim((string) (getCell($record, $headerMap, 'codesite', 'code site') ?? ''));
            $siteName = $siteName === '' ? trim((string) (getCell($record, $headerMap, 'nom site') ?? '')) : $siteName;
            $codeExcel = $codeExcel === '' ? trim((string) (getCell($record, $headerMap, 'code site*') ?? '')) : $codeExcel;

            if ($siteName === '') {
                continue;
            }

            $normName = normalizeString($siteName);
            $matchedSite = $sitesByNorm[$normName] ?? null;

            if (!$matchedSite && $codeExcel !== '') {
                $codeNorm = normalizeString($codeExcel);
                $matchedSite = $sitesByCode[$codeNorm] ?? null;
                if ($matchedSite) {
                    $matchedSite = [$matchedSite];
                }
            }

            if (!$matchedSite) {
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

                    $matchedSite = $dbSites;
                    break;
                }
            }

            if (!$matchedSite) {
                $noMatch++;
                echo "NO MATCH | {$siteName} | {$province} | code={$codeExcel}" . PHP_EOL;
                continue;
            }

            $site = $matchedSite[0];
            $matched++;

            if (isset($seenInFile[$site->id])) {
                $duplicate++;
                echo "DUPLICATE FILE | {$siteName} | site_id={$site->id}" . PHP_EOL;
                continue;
            }

            $exists = SiteMouvementPopulation::where('site_id', $site->id)
                ->where('date_mouvement', $date)
                ->where('type_mouvement', 'recensement')
                ->exists();

            if ($exists) {
                $duplicate++;
                echo "DUPLICATE DB | {$siteName} | site_id={$site->id}" . PHP_EOL;
                continue;
            }

            $seenInFile[$site->id] = true;

            $f0_5 = parseInt(getCell($record, $headerMap, '0 5f', '0 - 5 f'));
            $f6_17 = parseInt(getCell($record, $headerMap, '6 17f', '6 - 17 f'));
            $f18_59 = parseInt(getCell($record, $headerMap, '18 59f', '18 - 59 f'));
            $f60 = parseInt(getCell($record, $headerMap, '60 f', '60 + f'));
            $h0_5 = parseInt(getCell($record, $headerMap, '0 5h', '0 - 5 h'));
            $h6_17 = parseInt(getCell($record, $headerMap, '6 17h', '6 - 17 h'));
            $h18_59 = parseInt(getCell($record, $headerMap, '18 59h', '18 - 59 h'));
            $h60 = parseInt(getCell($record, $headerMap, '60 h', '60 + h'));

            $menages = parseInt(getCell($record, $headerMap, 'menages', 'menages*'));
            $individus = parseInt(getCell($record, $headerMap, 'individus', 'individus*'));

            if (!$DRY_RUN) {
                SiteMouvementPopulation::create([
                    'site_id' => $site->id,
                    'date_mouvement' => $date,
                    'type_mouvement' => 'recensement',
                    'raison_mouvement_id' => null,
                    'periode' => $periode,
                    'menages' => $menages,
                    'individus' => $individus,
                    'f_0_5' => $f0_5,
                    'f_6_17' => $f6_17,
                    'f_18_59' => $f18_59,
                    'f_60_plus' => $f60,
                    'h_0_5' => $h0_5,
                    'h_6_17' => $h6_17,
                    'h_18_59' => $h18_59,
                    'h_60_plus' => $h60,
                    'source' => $source,
                    'description' => "Province: {$province} | Code Excel: {$codeExcel}",
                    'statut' => 'valide',
                    'created_by' => 1,
                ]);
            }

            $imported++;
            echo "IMPORT | {$siteName} | site_id={$site->id} | nb={$individus}" . PHP_EOL;
        }
    }

    echo PHP_EOL . 'STATUT ' . $source . PHP_EOL;
    echo 'rows=' . $totalRows . PHP_EOL;
    echo 'matched=' . $matched . PHP_EOL;
    echo 'no_match=' . $noMatch . PHP_EOL;
    echo 'duplicate=' . $duplicate . PHP_EOL;
    echo 'imported=' . $imported . PHP_EOL;
    echo 'mode=' . ($DRY_RUN ? 'dry-run' : 'real') . PHP_EOL . PHP_EOL;
}

if ($DRY_RUN) {
    echo 'Dry-run termine. Aucune donnee n\'a ete enregistree.' . PHP_EOL;
} else {
    echo 'Import termine.' . PHP_EOL;
}
