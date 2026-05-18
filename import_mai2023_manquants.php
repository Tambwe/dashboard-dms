<?php
/**
 * Script d'import COMPLEMENTAIRE : Master List DRC Mai 2023
 * → Importe uniquement les sites NON encore enregistrés dans site_mouvements_population
 *   pour la période 2023-05 (date 2023-05-31, type recensement).
 *
 * Stratégie :
 *   1. Matching par code_site (exact), sinon par nom normalisé (exact + partiel ≥65%)
 *   2. Si site absent de la table `sites` → le créer à partir des données Excel
 *   3. Créer le SiteMouvementPopulation seulement s'il n'existe pas déjà
 *
 * Usage :
 *   php import_mai2023_manquants.php             → import réel
 *   php import_mai2023_manquants.php --dry-run   → simulation sans écriture
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;

// ============================================================
// CONFIG
// ============================================================
$FILE    = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3.xlsx';
$DATE    = '2023-05-31';
$PERIODE = '2023-05';
$TYPE    = 'recensement';
$SOURCE  = 'Master List DRC Mai 2023';
$DRY_RUN = isset($argv[1]) && $argv[1] === '--dry-run';

echo ($DRY_RUN ? "=== MODE SIMULATION (dry-run) ===" : "=== MODE IMPORT REEL ===") . PHP_EOL;
echo "Fichier : $FILE" . PHP_EOL;
echo "Date    : $DATE  |  Type : $TYPE" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL . PHP_EOL;

// ============================================================
// CHARGEMENT EXCEL
// ============================================================
if (!file_exists($FILE)) {
    echo "[ERREUR] Fichier introuvable : $FILE" . PHP_EOL;
    exit(1);
}

echo "Chargement du fichier Excel..." . PHP_EOL;
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($FILE);

// Essaie la 2e feuille (index 1) si disponible, sinon feuille 0
$sheetCount = $spreadsheet->getSheetCount();
$sheet = $sheetCount > 1 ? $spreadsheet->getSheet(1) : $spreadsheet->getSheet(0);
echo "Feuille utilisée : " . $sheet->getTitle() . PHP_EOL;

$maxRow  = $sheet->getHighestRow();
echo "Preloading cell data..." . PHP_EOL;
$sheetData = $sheet->toArray(null, true, false, true); // [row][colLetter]
$spreadsheet->disconnectWorksheets();
unset($spreadsheet, $sheet);
echo "Lignes trouvées : " . ($maxRow - 1) . " (hors en-tête)" . PHP_EOL . PHP_EOL;

// ============================================================
// DETECTION DYNAMIQUE DES COLONNES DEPUIS L'EN-TETE (ligne 1)
// ============================================================
$headerRow = $sheetData[1] ?? [];
$colMap    = []; // [label normalisé] => lettre de colonne

foreach ($headerRow as $col => $val) {
    $label = mb_strtolower(trim((string) $val));
    if ($label !== '') {
        $colMap[$label] = $col;
    }
}

/**
 * Résout une colonne depuis $colMap avec plusieurs alias possibles.
 */
function resolveCol(array $colMap, array $aliases): ?string
{
    foreach ($aliases as $alias) {
        $key = mb_strtolower(trim($alias));
        if (isset($colMap[$key])) {
            return $colMap[$key];
        }
    }
    return null;
}

// Colonnes détectées dynamiquement
$COL = [
    'province'        => resolveCol($colMap, ['province', 'province*']) ?? 'B',
    'code_province'   => resolveCol($colMap, ['code province', 'code province*', 'code province  ']) ?? 'C',
    'territoire'      => resolveCol($colMap, ['territoire', 'territoire*', 'territory']) ?? 'D',
    'code_territoire' => resolveCol($colMap, ['code territoire', 'code territoire*']) ?? 'E',
    'zone_sante'      => resolveCol($colMap, ['zone de sante', 'zone de santé', 'zone de sante*']) ?? 'F',
    'code_zone_sante' => resolveCol($colMap, ['code zone de sante', 'code zone de santé', 'code zone de sante']) ?? 'G',
    'aire_sante'      => resolveCol($colMap, ['aire de sante', 'aire de santé']),
    'nom_site'   => resolveCol($colMap, ['nom du site', 'nom du site*', 'site name']) ?? 'H',
    'code_site'  => resolveCol($colMap, ['code site', 'code du site', 'code site*']) ?? 'I',
    'menages'    => resolveCol($colMap, ['menages', 'ménages', 'menages*']) ?? 'O',
    'individus'  => resolveCol($colMap, ['individus', 'individus*', 'total individus']) ?? 'P',
    'hommes'     => resolveCol($colMap, ['hommes', 'total hommes', 'h total']) ?? 'Q',
    'femmes'     => resolveCol($colMap, ['femmes', 'total femmes', 'f total']) ?? 'R',
    'h_6_17'     => resolveCol($colMap, ['6 - 17 h', '6-17 h', '6 - 17h']) ?? 'V',
    'h_18_59'    => resolveCol($colMap, ['18 - 59 h', '18-59 h', '18 - 59h']) ?? 'W',
    'h_60'       => resolveCol($colMap, ['60 + h', '60+ h', '60 +h']) ?? 'X',
    'longitude'  => resolveCol($colMap, ['longitude', 'longitude*']),
    'latitude'   => resolveCol($colMap, ['latitude', 'latitude*']),
];

echo "Colonnes détectées :" . PHP_EOL;
foreach ($COL as $field => $col) {
    echo sprintf("  %-12s → %s\n", $field, $col ?? '(non trouvée)');
}
echo PHP_EOL;

// ============================================================
// INDEX DES SITES EN DB
// ============================================================
$allSites    = Site::select('id', 'code_site', 'nom')->get();
$sitesByCode = [];
$sitesByNorm = [];

function normalizeStr(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[-_\s]+/', ' ', $s);
    $s = preg_replace('/[^a-z0-9 ]/', '', $s);
    return trim($s);
}

foreach ($allSites as $site) {
    $sitesByNorm[normalizeStr($site->nom)][] = $site;
    if (!empty($site->code_site)) {
        $normCode = normalizeStr((string) $site->code_site);
        $sitesByCode[$normCode] = $site;
    }
}

// ============================================================
// INDEX DES MOUVEMENTS DEJA IMPORTES pour éviter les doublons
// ============================================================
$alreadyImported = SiteMouvementPopulation::where('date_mouvement', $DATE)
    ->where('type_mouvement', $TYPE)
    ->pluck('site_id')
    ->flip()
    ->all(); // [site_id => true]

echo "Sites déjà importés pour $DATE/$TYPE : " . count($alreadyImported) . PHP_EOL . PHP_EOL;

// ============================================================
// HELPERS
// ============================================================
$toInt = function ($v): ?int {
    if ($v === null || $v === '' || strtoupper((string) $v) === 'N/A') {
        return null;
    }
    $num = preg_replace('/[^0-9\-]/', '', (string) $v);
    return $num !== '' ? (int) $num : null;
};

$toFloat = function ($v): ?float {
    if ($v === null || $v === '') return null;
    $num = str_replace(',', '.', (string) $v);
    $num = preg_replace('/[^0-9.\-]/', '', $num);
    return $num !== '' ? (float) $num : null;
};

function getCell(array $row, ?string $col): mixed
{
    if ($col === null) return null;
    return $row[$col] ?? null;
}

// ============================================================
// STATS
// ============================================================
$stats = [
    'total'         => 0,
    'skip_empty'    => 0,
    'already_done'  => 0,
    'matched_code'  => 0,
    'matched_name'  => 0,
    'site_created'  => 0,
    'mouvement_ok'  => 0,
    'no_match'      => 0,
    'error'         => 0,
];

$noMatchList   = [];
$createdSites  = [];
$importedList  = [];

echo str_repeat('-', 70) . PHP_EOL;

// ============================================================
// RENOMMAGE DES DOUBLONS EXCEL (même nom, sites distincts)
// Clé : numéro de ligne Excel → nom de remplacement
// ============================================================
$RENAME_ROWS = [
    113 => 'EKIOKELA 2',
    117 => 'SOFIBEF (Centre collectif)',
    121 => 'NABINDI 2',
    136 => 'KIMBI 2',
];

// ============================================================
// BOUCLE PRINCIPALE
// ============================================================
for ($row = 2; $row <= $maxRow; $row++) {
    $r = $sheetData[$row] ?? [];

    $siteName  = trim((string) getCell($r, $COL['nom_site']));
    // Appliquer le renommage si la ligne est dans la table des doublons
    if (isset($RENAME_ROWS[$row])) {
        echo "  RENOMME   | Ligne $row : \"$siteName\" → \"{$RENAME_ROWS[$row]}\"" . PHP_EOL;
        $siteName = $RENAME_ROWS[$row];
    }
    $codeExcel = trim((string) getCell($r, $COL['code_site']));
    $province  = trim((string) getCell($r, $COL['province']));
    $territoire      = trim((string) getCell($r, $COL['territoire']));
    $codeTerritoire  = trim((string) getCell($r, $COL['code_territoire']));
    $zoneSante       = trim((string) getCell($r, $COL['zone_sante']));
    $codeZoneSante   = trim((string) getCell($r, $COL['code_zone_sante']));
    $codeProvince    = trim((string) getCell($r, $COL['code_province']));

    if (empty($siteName)) {
        $stats['skip_empty']++;
        continue;
    }
    $stats['total']++;

    $menages   = $toInt(getCell($r, $COL['menages']));
    $individus = $toInt(getCell($r, $COL['individus']));
    $hommes    = $toInt(getCell($r, $COL['hommes']));
    $femmes    = $toInt(getCell($r, $COL['femmes']));
    $h6_17     = $toInt(getCell($r, $COL['h_6_17']));
    $h18_59    = $toInt(getCell($r, $COL['h_18_59']));
    $h60       = $toInt(getCell($r, $COL['h_60']));
    $longitude = $toFloat(getCell($r, $COL['longitude']));
    $latitude  = $toFloat(getCell($r, $COL['latitude']));
    $aireSante = $COL['aire_sante'] ? trim((string) getCell($r, $COL['aire_sante'])) : '';

    // ---- Matching du site -------------------------------------------
    $site       = null;
    $matchType  = null;

    // 1) Par code_site
    if (!empty($codeExcel)) {
        $normCode = normalizeStr($codeExcel);
        if (isset($sitesByCode[$normCode])) {
            $site      = $sitesByCode[$normCode];
            $matchType = 'code';
            $stats['matched_code']++;
        }
    }

    // 2) Par nom normalisé exact
    if (!$site) {
        $normName = normalizeStr($siteName);
        if (isset($sitesByNorm[$normName])) {
            $site      = $sitesByNorm[$normName][0];
            $matchType = 'nom_exact';
            $stats['matched_name']++;
        }
    }

    // 3) Matching partiel (≥65% de longueur commune)
    if (!$site) {
        $normName = normalizeStr($siteName);
        foreach ($sitesByNorm as $dbNorm => $dbSites) {
            if (str_contains($dbNorm, $normName) || str_contains($normName, $dbNorm)) {
                $maxLen = max(strlen($dbNorm), strlen($normName));
                $minLen = min(strlen($dbNorm), strlen($normName));
                if ($maxLen > 0 && ($minLen / $maxLen) >= 0.65) {
                    $site      = $dbSites[0];
                    $matchType = 'nom_partiel';
                    $stats['matched_name']++;
                    break;
                }
            }
        }
    }

    // ---- Site pas trouvé : le créer --------------------------------
    if (!$site) {
        if (!$DRY_RUN) {
            try {
                $site = Site::create([
                    'nom'             => $siteName,
                    'code_site'       => ($codeExcel && strtoupper($codeExcel) !== 'N/A') ? $codeExcel : null,
                    'province'        => $province ?: null,
                    'code_province'   => $codeProvince ?: null,
                    'territoire'      => $territoire ?: null,
                    'code_territoire' => ($codeTerritoire && strtoupper($codeTerritoire) !== 'N/A') ? $codeTerritoire : null,
                    'zone_sante'      => $zoneSante ?: null,
                    'code_zone_sante' => ($codeZoneSante && strtoupper($codeZoneSante) !== 'N/A') ? $codeZoneSante : null,
                    'aire_sante'      => $aireSante ?: null,
                    'longitude'       => $longitude,
                    'latitude'        => $latitude,
                    'menages'         => $menages,
                    'individus'       => $individus,
                    'source'          => $SOURCE,
                    'type_fichier'    => 'Master List Mai 2023',
                ]);

                // Mise à jour des index locaux pour éviter les doublons en cas
                // d'occurrence multiple du même site dans le fichier
                $sitesByNorm[normalizeStr($siteName)][] = $site;
                if (!empty($codeExcel)) {
                    $sitesByCode[normalizeStr($codeExcel)] = $site;
                }

                $matchType = 'cree';
                $stats['site_created']++;
                $createdSites[] = [
                    'row'      => $row,
                    'nom'      => $siteName,
                    'code'     => $codeExcel,
                    'province' => $province,
                    'territoire' => $territoire,
                ];
                printf("  SITE CREE | Ligne %-4d | %-45s | id=%d | %s / %s\n",
                    $row, $siteName, $site->id, $province, $territoire);
            } catch (\Exception $e) {
                $stats['error']++;
                printf("  ERREUR    | Ligne %-4d | %-45s | Création site: %s\n",
                    $row, $siteName, $e->getMessage());
                continue;
            }
        } else {
            $stats['no_match']++;
            $noMatchList[] = ['row' => $row, 'nom' => $siteName, 'province' => $province, 'territoire' => $territoire];
            printf("  [SIM] NEW | Ligne %-4d | %-45s | %s / %s\n",
                $row, $siteName, $province, $territoire);
            continue;
        }
    }

    // ---- Vérification doublon (déjà importé) -----------------------
    if (isset($alreadyImported[$site->id])) {
        $stats['already_done']++;
        // printf("  SKIP      | Ligne %-4d | %-45s | site_id=%d (déjà importé)\n", $row, $siteName, $site->id);
        continue;
    }

    // ---- Import du mouvement de population -------------------------
    if (!$DRY_RUN) {
        try {
            SiteMouvementPopulation::create([
                'site_id'             => $site->id,
                'date_mouvement'      => $DATE,
                'type_mouvement'      => $TYPE,
                'raison_mouvement_id' => null,
                'periode'             => $PERIODE,
                'menages'             => $menages,
                'individus'           => $individus,
                'h_0_5'               => 0,
                'h_6_17'              => $h6_17  ?? 0,
                'h_18_59'             => $h18_59 ?? 0,
                'h_60_plus'           => $h60    ?? 0,
                'f_0_5'               => 0,
                'f_6_17'              => 0,
                'f_18_59'             => 0,
                'f_60_plus'           => 0,
                'source'              => $SOURCE,
                'description'         => "Province: $province | Territoire: $territoire | Zone: $zoneSante | Hommes: $hommes | Femmes: $femmes | Match: $matchType",
                'statut'              => 'valide',
                'created_by'          => 1,
            ]);

            // Marquer comme importé pour éviter doublons dans la même exécution
            $alreadyImported[$site->id] = true;

            $stats['mouvement_ok']++;
            $importedList[] = $siteName;
            printf("  IMPORTE   | Ligne %-4d | %-45s | site_id=%d | %d ind. | [%s] %s / %s\n",
                $row, $siteName, $site->id, $individus ?? 0, $matchType, $province, $territoire);
        } catch (\Exception $e) {
            $stats['error']++;
            printf("  ERREUR    | Ligne %-4d | %-45s | %s\n", $row, $siteName, $e->getMessage());
        }
    } else {
        $stats['mouvement_ok']++;
        printf("  [SIM] OK  | Ligne %-4d | %-45s | site_id=%d | %d ind. | [%s] %s / %s\n",
            $row, $siteName, $site->id, $individus ?? 0, $matchType, $province, $territoire);
    }
}

// ============================================================
// RAPPORT FINAL
// ============================================================
echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "=== RAPPORT FINAL ===" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
printf("Lignes traitées          : %d\n", $stats['total']);
printf("Lignes vides skippées    : %d\n", $stats['skip_empty']);
printf("Déjà importés (skippés)  : %d\n", $stats['already_done']);
printf("Matchés par code_site    : %d\n", $stats['matched_code']);
printf("Matchés par nom          : %d\n", $stats['matched_name']);
printf("Sites créés en DB        : %d\n", $stats['site_created']);
printf("Mouvements importés%s   : %d\n", $DRY_RUN ? ' (sim)' : '      ', $stats['mouvement_ok']);
printf("Sans correspondance      : %d\n", $stats['no_match']);
printf("Erreurs                  : %d\n", $stats['error']);

if (!empty($createdSites)) {
    echo PHP_EOL . "=== NOUVEAUX SITES CRÉÉS EN DB ===" . PHP_EOL;
    foreach ($createdSites as $s) {
        printf("  Ligne %d: %-40s | Code: %-12s | %s / %s\n",
            $s['row'], $s['nom'], $s['code'], $s['province'], $s['territoire']);
    }
}

if (!empty($noMatchList)) {
    echo PHP_EOL . "=== SITES SANS CORRESPONDANCE (simulation) ===" . PHP_EOL;
    foreach ($noMatchList as $item) {
        printf("  Ligne %d: %-40s | %s / %s\n",
            $item['row'], $item['nom'], $item['province'], $item['territoire']);
    }
}

echo PHP_EOL . "Terminé." . PHP_EOL;
