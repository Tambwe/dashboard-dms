<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use PhpOffice\PhpSpreadsheet\IOFactory;

// 1. Sites territoire GOMA en DB
$goma = Site::where('territoire', 'like', '%GOMA%')
    ->orWhere('territoire', 'like', '%goma%')
    ->select('id','nom','territoire','province','code_site')
    ->get();
echo "Sites territoire GOMA en DB: " . $goma->count() . PHP_EOL;
foreach ($goma as $s) {
    echo "  id={$s->id} | {$s->nom} | territoire: {$s->territoire}" . PHP_EOL;
}

// 2. Recherche spécifique
echo PHP_EOL . "--- Recherche BULENGO / LUSHAGALA / LAC VERT ---" . PHP_EOL;
foreach (['BULENGO', 'LUSHAGALA', 'LAC VERT'] as $kw) {
    $res = Site::where('nom', 'like', "%{$kw}%")->select('id','nom')->get();
    echo "{$kw}: " . $res->count() . " résultat(s)";
    foreach ($res as $s) echo " | id={$s->id} {$s->nom}";
    echo PHP_EOL;
}

// 3. Mouvements 2023-05-31 déjà enregistrés
$mv = SiteMouvementPopulation::where('date_mouvement', '2023-05-31')
    ->where('type_mouvement', 'recensement')
    ->count();
echo PHP_EOL . "Mouvements 2023-05-31 / recensement en DB: {$mv}" . PHP_EOL;

// 4. Scanner tout le fichier Excel et comparer avec la DB
echo PHP_EOL . "--- Scan complet du fichier Excel ---" . PHP_EOL;
$FILE = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3.xlsx';
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($FILE);
$sheet = $spreadsheet->getSheetCount() > 1 ? $spreadsheet->getSheet(1) : $spreadsheet->getSheet(0);
$maxRow = $sheet->getHighestRow();
$data = $sheet->toArray(null, true, false, true);
$spreadsheet->disconnectWorksheets();

// Index DB par nom normalisé
function normalizeStr(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[-_\s]+/', ' ', $s);
    $s = preg_replace('/[^a-z0-9 ]/', '', $s);
    return trim($s);
}

$allSites = Site::select('id','nom','code_site')->get();
$sitesByNorm = [];
foreach ($allSites as $s) {
    $sitesByNorm[normalizeStr($s->nom)][] = $s;
}

$alreadyImported = SiteMouvementPopulation::where('date_mouvement','2023-05-31')
    ->where('type_mouvement','recensement')
    ->pluck('site_id')
    ->flip()
    ->all();

$missing = [];
$noMatch = [];

for ($row = 2; $row <= $maxRow; $row++) {
    $r = $data[$row] ?? [];
    $siteName  = trim((string)($r['H'] ?? ''));
    $territoire = trim((string)($r['D'] ?? ''));
    $province  = trim((string)($r['B'] ?? ''));
    $codeExcel = trim((string)($r['I'] ?? ''));
    if (empty($siteName)) continue;

    // Matching
    $normName = normalizeStr($siteName);
    $found = $sitesByNorm[$normName] ?? null;
    if (!$found) {
        foreach ($sitesByNorm as $dbNorm => $dbSites) {
            if (str_contains($dbNorm, $normName) || str_contains($normName, $dbNorm)) {
                $maxLen = max(strlen($dbNorm), strlen($normName));
                $minLen = min(strlen($dbNorm), strlen($normName));
                if ($maxLen > 0 && ($minLen / $maxLen) >= 0.65) {
                    $found = $dbSites;
                    break;
                }
            }
        }
    }

    if (!$found) {
        $noMatch[] = ['row' => $row, 'nom' => $siteName, 'territoire' => $territoire, 'province' => $province];
    } else {
        $siteId = $found[0]->id;
        if (!isset($alreadyImported[$siteId])) {
            $missing[] = ['row' => $row, 'nom' => $siteName, 'territoire' => $territoire, 'province' => $province, 'site_id' => $siteId, 'reason' => 'mouvement_manquant'];
        }
    }
}

$totalExcel = $maxRow - 1;
echo "Total lignes Excel   : {$totalExcel}" . PHP_EOL;
echo "Sites non trouvés DB : " . count($noMatch) . PHP_EOL;
echo "Sites DB sans mouvt  : " . count($missing) . PHP_EOL;

if (!empty($noMatch)) {
    echo PHP_EOL . "=== SITES ABSENTS DE LA DB ===" . PHP_EOL;
    foreach ($noMatch as $item) {
        printf("  L%-4d | %-40s | %-15s | %s\n", $item['row'], $item['nom'], $item['territoire'], $item['province']);
    }
}

if (!empty($missing)) {
    echo PHP_EOL . "=== SITES EN DB MAIS SANS MOUVEMENT 2023-05-31 ===" . PHP_EOL;
    foreach ($missing as $item) {
        printf("  L%-4d | %-40s | %-15s | site_id=%d\n", $item['row'], $item['nom'], $item['territoire'], $item['site_id']);
    }
}
