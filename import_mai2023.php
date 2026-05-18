<?php
/**
 * Script d'import: Master List DRC Mai 2023 → SiteMouvementPopulation
 * 
 * Correspondance: NOM DU SITE (col H) → Site::nom dans la DB
 * Type mouvement: recensement
 * Date: 2023-05-31
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use Illuminate\Support\Facades\DB;

// ============================================================
// CONFIG
// ============================================================
$FILE    = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3 - Copy.xlsx';
$DATE    = '2023-05-31';
$PERIODE = '2023-05';
$TYPE    = 'recensement';
$SOURCE  = 'Master List DRC Mai 2023';
$DRY_RUN = isset($argv[1]) && $argv[1] === '--dry-run'; // passer --dry-run pour simulation

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
$sheet       = $spreadsheet->getSheet(0);
$maxRow      = $sheet->getHighestRow();
// Preload ALL cell values into a plain PHP array to avoid
// "FetchedValue has been disposed" errors on large files
echo "Preloading cell data..." . PHP_EOL;
$sheetData = $sheet->toArray(null, true, false, true); // [row][col] indexed by letter
$spreadsheet->disconnectWorksheets();
unset($spreadsheet, $sheet);
echo "Lignes trouvees: " . ($maxRow - 1) . " (hors en-tete)" . PHP_EOL . PHP_EOL;

// ============================================================
// CHARGEMENT DES SITES DB (normalises pour comparaison)
// ============================================================
$allSites = Site::select('id', 'code_site', 'nom')->get();

// Index par nom normalisé
function normalizeStr(string $s): string {
    $s = mb_strtolower(trim($s));
    // Supprimer accents
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    // Remplacer tirets/underscores/espaces multiples
    $s = preg_replace('/[-_\s]+/', ' ', $s);
    // Supprimer caractères spéciaux sauf lettres, chiffres, espaces
    $s = preg_replace('/[^a-z0-9 ]/', '', $s);
    return trim($s);
}

$sitesByNorm = [];
foreach ($allSites as $site) {
    $norm = normalizeStr($site->nom);
    $sitesByNorm[$norm][] = $site;
}

// ============================================================
// LECTURE LIGNE PAR LIGNE ET MATCHING
// ============================================================
$stats = [
    'total'      => 0,
    'matched'    => 0,
    'no_match'   => 0,
    'duplicate'  => 0,
    'imported'   => 0,
    'error'      => 0,
];

$noMatchList  = [];
$dupList      = [];
$importedList = [];

echo str_repeat('-', 70) . PHP_EOL;

for ($row = 2; $row <= $maxRow; $row++) {
    $stats['total']++;
    $r = $sheetData[$row] ?? [];

    $num      = $r['A'] ?? null;
    $province = $r['B'] ?? null;
    $siteName = trim((string) ($r['H'] ?? ''));
    $codeExcel = trim((string) ($r['I'] ?? ''));

    $menages   = $r['O'] ?? null;
    $individus = $r['P'] ?? null;
    $hommes    = $r['Q'] ?? null;
    $femmes    = $r['R'] ?? null;

    $h6_17  = $r['V'] ?? null;
    $h18_59 = $r['W'] ?? null;
    $h60    = $r['X'] ?? null;

    // Nettoyer les valeurs numériques
    $toInt = function($v) {
        if ($v === null || $v === '' || strtoupper((string)$v) === 'N/A') return null;
        return (int) $v;
    };

    $menages   = $toInt($menages);
    $individus = $toInt($individus);
    $hommes    = $toInt($hommes);
    $femmes    = $toInt($femmes);
    $h6_17     = $toInt($h6_17);
    $h18_59    = $toInt($h18_59);
    $h60       = $toInt($h60);

    if (empty($siteName)) continue;

    // --- Matching du site ---
    $normName = normalizeStr($siteName);
    $matched  = $sitesByNorm[$normName] ?? null;

    // Si pas trouvé, tentative de matching partiel (le nom DB contient le nom Excel ou vice-versa)
    if (!$matched) {
        foreach ($sitesByNorm as $dbNorm => $dbSites) {
            if (str_contains($dbNorm, $normName) || str_contains($normName, $dbNorm)) {
                // Vérifier que la correspondance est assez proche (>70% de la longueur)
                $lenRatio = strlen($normName) > 0 ? min(strlen($dbNorm), strlen($normName)) / max(strlen($dbNorm), strlen($normName)) : 0;
                if ($lenRatio >= 0.65) {
                    $matched = $dbSites;
                    break;
                }
            }
        }
    }

    if (!$matched) {
        $stats['no_match']++;
        $noMatchList[] = ['row' => $row, 'nom' => $siteName, 'province' => $province];
        printf("  NO MATCH  | Ligne %-4d | %-45s | %s\n", $row, $siteName, $province);
        continue;
    }

    // Prendre le premier site correspondant (en cas de doublons DB)
    $site = $matched[0];
    $stats['matched']++;

    // --- Vérification doublon ---
    $exists = SiteMouvementPopulation::where('site_id', $site->id)
        ->where('date_mouvement', $DATE)
        ->where('type_mouvement', $TYPE)
        ->exists();

    if ($exists) {
        $stats['duplicate']++;
        $dupList[] = ['row' => $row, 'nom' => $siteName, 'site_id' => $site->id];
        printf("  DOUBLON   | Ligne %-4d | %-45s | site_id=%d\n", $row, $siteName, $site->id);
        continue;
    }

    // --- Import ---
    if (!$DRY_RUN) {
        try {
            SiteMouvementPopulation::create([
                'site_id'           => $site->id,
                'date_mouvement'    => $DATE,
                'type_mouvement'    => $TYPE,
                'raison_mouvement_id' => null,
                'periode'           => $PERIODE,
                'menages'           => $menages,
                'individus'         => $individus,
                // Hommes par tranche d'âge (disponible = 6-17, 18-59, 60+)
                'h_0_5'             => 0,
                'h_6_17'            => $h6_17 ?? 0,
                'h_18_59'           => $h18_59 ?? 0,
                'h_60_plus'         => $h60 ?? 0,
                // Femmes: pas de détail par âge dans la source
                'f_0_5'             => 0,
                'f_6_17'            => 0,
                'f_18_59'           => 0,
                'f_60_plus'         => 0,
                'source'            => $SOURCE,
                'description'       => "Province: $province | Hommes: $hommes | Femmes: $femmes",
                'statut'            => 'valide',
                'created_by'        => 1,
            ]);
            $stats['imported']++;
            $importedList[] = $siteName;
            printf("  IMPORTE   | Ligne %-4d | %-45s | site_id=%d | %d individus\n", $row, $siteName, $site->id, $individus ?? 0);
        } catch (\Exception $e) {
            $stats['error']++;
            printf("  ERREUR    | Ligne %-4d | %-45s | %s\n", $row, $siteName, $e->getMessage());
        }
    } else {
        $stats['imported']++;
        printf("  [SIM] OK  | Ligne %-4d | %-45s | site_id=%d | %d individus\n", $row, $siteName, $site->id, $individus ?? 0);
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
printf("Doublons skippés         : %d\n", $stats['duplicate']);
printf("Importés%s              : %d\n", $DRY_RUN ? ' (simulation)' : '             ', $stats['imported']);
printf("Erreurs                  : %d\n", $stats['error']);

if (!empty($noMatchList)) {
    echo PHP_EOL . "=== SITES SANS CORRESPONDANCE ===" . PHP_EOL;
    foreach ($noMatchList as $item) {
        printf("  Ligne %d: %-45s | Province: %s\n", $item['row'], $item['nom'], $item['province']);
    }
}

if (!empty($dupList)) {
    echo PHP_EOL . "=== DOUBLONS DÉTECTÉS ===" . PHP_EOL;
    foreach ($dupList as $item) {
        printf("  Ligne %d: %-45s | site_id=%d\n", $item['row'], $item['nom'], $item['site_id']);
    }
}
