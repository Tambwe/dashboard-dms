<?php
/**
 * Affiche les lignes avec noms identiques dans le fichier Excel Mai 2023
 * pour permettre une vérification avant import.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$FILE = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3.xlsx';

$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($FILE);
$sheet = $spreadsheet->getSheetCount() > 1 ? $spreadsheet->getSheet(1) : $spreadsheet->getSheet(0);
$maxRow = $sheet->getHighestRow();
$data = $sheet->toArray(null, true, false, true);
$spreadsheet->disconnectWorksheets();

// Regrouper toutes les lignes par nom de site
$byName = [];
for ($row = 2; $row <= $maxRow; $row++) {
    $r = $data[$row] ?? [];
    $nom = trim((string)($r['H'] ?? ''));
    if (empty($nom)) continue;
    $byName[$nom][] = [
        'row'            => $row,
        'province'       => trim((string)($r['B'] ?? '')),
        'territoire'     => trim((string)($r['D'] ?? '')),
        'code_territoire'=> trim((string)($r['E'] ?? '')),
        'zone_sante'     => trim((string)($r['F'] ?? '')),
        'code_zone_sante'=> trim((string)($r['G'] ?? '')),
        'type_site'      => trim((string)($r['J'] ?? '')),
        'mecanisme'      => trim((string)($r['K'] ?? '')),
        'obs_meca'       => trim((string)($r['L'] ?? '')),
        'longitude'      => trim((string)($r['M'] ?? '')),
        'latitude'       => trim((string)($r['N'] ?? '')),
        'menages'        => trim((string)($r['O'] ?? '')),
        'individus'      => trim((string)($r['P'] ?? '')),
        'gestionnaire'   => trim((string)($r['Y'] ?? '')),
    ];
}

// Filtrer uniquement les noms en double
$doublons = array_filter($byName, fn($rows) => count($rows) > 1);

echo "=== DOUBLONS DE NOM DANS LE FICHIER EXCEL (" . count($doublons) . " groupes) ===" . PHP_EOL;
echo str_repeat('=', 100) . PHP_EOL;

foreach ($doublons as $nom => $rows) {
    echo PHP_EOL . "NOM: \"$nom\" (" . count($rows) . " occurrences)" . PHP_EOL;
    echo str_repeat('-', 100) . PHP_EOL;
    foreach ($rows as $r) {
        printf(
            "  L%-4d | %-15s / %-15s | Zone: %-20s | Type: %-18s | Meca: %-8s | Obs: %-20s | GPS: %s,%s | Ménages: %s | Individus: %s | Gest: %s\n",
            $r['row'],
            $r['territoire'],
            $r['zone_sante'],
            $r['code_zone_sante'],
            $r['type_site'],
            $r['mecanisme'],
            $r['obs_meca'],
            $r['longitude'],
            $r['latitude'],
            $r['menages'],
            $r['individus'],
            $r['gestionnaire']
        );
    }
}

echo PHP_EOL . "=== TOUS LES 87 SITES À IMPORTER (par territoire) ===" . PHP_EOL;
echo str_repeat('=', 100) . PHP_EOL;

// Reconstruire la liste complète manquante (même logique que le script principal)
function normalizeStr(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[-_\s]+/', ' ', $s);
    $s = preg_replace('/[^a-z0-9 ]/', '', $s);
    return trim($s);
}

use App\Models\Site;
use App\Models\SiteMouvementPopulation;

$allSites = Site::select('id','nom','code_site')->get();
$sitesByNorm = [];
foreach ($allSites as $s) {
    $sitesByNorm[normalizeStr($s->nom)][] = $s;
}
$alreadyImported = SiteMouvementPopulation::where('date_mouvement','2023-05-31')
    ->where('type_mouvement','recensement')
    ->pluck('site_id')->flip()->all();

$missing = [];
for ($row = 2; $row <= $maxRow; $row++) {
    $r = $data[$row] ?? [];
    $nom = trim((string)($r['H'] ?? ''));
    if (empty($nom)) continue;

    $normName = normalizeStr($nom);
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

    $siteId = $found ? $found[0]->id : null;
    $alreadyDone = $siteId && isset($alreadyImported[$siteId]);

    if (!$alreadyDone) {
        $missing[] = [
            'row'        => $row,
            'nom'        => $nom,
            'territoire' => trim((string)($r['D'] ?? '')),
            'zone_sante' => trim((string)($r['F'] ?? '')),
            'type_site'  => trim((string)($r['J'] ?? '')),
            'mecanisme'  => trim((string)($r['K'] ?? '')),
            'longitude'  => trim((string)($r['M'] ?? '')),
            'latitude'   => trim((string)($r['N'] ?? '')),
            'menages'    => trim((string)($r['O'] ?? '')),
            'individus'  => trim((string)($r['P'] ?? '')),
            'gestionnaire'=> trim((string)($r['Y'] ?? '')),
            'in_db'      => $siteId ? "oui (id=$siteId)" : 'NON',
            'doublon_excel' => count($byName[$nom]) > 1 ? 'DOUBLON EXCEL' : '',
        ];
    }
}

// Trier par territoire puis nom
usort($missing, fn($a,$b) => strcmp($a['territoire'].$a['nom'], $b['territoire'].$b['nom']));

$currentTerr = '';
foreach ($missing as $item) {
    if ($item['territoire'] !== $currentTerr) {
        $currentTerr = $item['territoire'];
        echo PHP_EOL . "  >> TERRITOIRE: $currentTerr" . PHP_EOL;
    }
    printf(
        "    L%-4d %-40s | Zone: %-20s | Type: %-18s | Men:%s Ind:%s | GPS:%s,%s %s\n",
        $item['row'], $item['nom'], $item['zone_sante'],
        $item['type_site'], $item['menages'], $item['individus'],
        $item['longitude'], $item['latitude'],
        $item['doublon_excel'] ? ' *** ' . $item['doublon_excel'] : ''
    );
}

echo PHP_EOL . "Total à traiter: " . count($missing) . " lignes (" . count($doublons) . " groupes de doublons à vérifier)" . PHP_EOL;
