<?php
/**
 * Import rapide : 2 sites de Goma manquants
 *   - BULENGO/LAC VERT
 *   - LUSHAGALA/LAC VERT
 *
 * Usage :
 *   php import_goma_manquants.php             → import réel
 *   php import_goma_manquants.php --dry-run   → simulation
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Site;
use App\Models\SiteMouvementPopulation;

$DRY_RUN = isset($argv[1]) && $argv[1] === '--dry-run';
$DATE    = '2023-05-31';
$PERIODE = '2023-05';
$TYPE    = 'recensement';
$SOURCE  = 'Master List DRC Mai 2023';

echo ($DRY_RUN ? "=== SIMULATION ===" : "=== IMPORT REEL ===") . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;

// ============================================================
// DONNÉES DES 2 SITES
// ============================================================
$sites = [
    [
        'nom'             => 'BULENGO/LAC VERT',
        'code_site'       => null,
        'province'        => 'NORD-KIVU',
        'code_province'   => 'CD61',
        'territoire'      => 'GOMA',
        'code_territoire' => 'CD6101',
        'zone_sante'      => 'GOMA',
        'code_zone_sante' => 'CD5405ZS11',
        'longitude'       => 29.1202,
        'latitude'        => -1.6227,
        'type_fichier'    => 'Master List Mai 2023',
        'source'          => $SOURCE,
        // Mouvement
        'menages'         => 20512,
        'individus'       => 83799,
        'hommes'          => 23305,
        'femmes'          => 29354,
        'h_6_17'          => null,
        'h_18_59'         => null,
        'h_60_plus'       => null,
    ],
    [
        'nom'             => 'LUSHAGALA/LAC VERT',
        'code_site'       => null,
        'province'        => 'NORD-KIVU',
        'code_province'   => 'CD61',
        'territoire'      => 'GOMA',
        'code_territoire' => 'CD6101',
        'zone_sante'      => 'GOMA',
        'code_zone_sante' => 'CD5405ZS11',
        'longitude'       => 29.1284,
        'latitude'        => -1.6026,
        'type_fichier'    => 'Master List Mai 2023',
        'source'          => $SOURCE,
        // Mouvement
        'menages'         => 4650,
        'individus'       => 18284,
        'hommes'          => 4898,
        'femmes'          => 6521,
        'h_6_17'          => null,
        'h_18_59'         => null,
        'h_60_plus'       => null,
    ],
];

// ============================================================
// TRAITEMENT
// ============================================================
foreach ($sites as $data) {
    $nom = $data['nom'];
    echo "--- $nom ---" . PHP_EOL;

    // 1. Vérifier si le site existe déjà en DB
    $site = Site::where('nom', $nom)->first();

    if ($site) {
        echo "  Site existant : id={$site->id}" . PHP_EOL;
    } else {
        if (!$DRY_RUN) {
            $site = Site::create([
                'nom'             => $nom,
                'code_site'       => $data['code_site'],
                'province'        => $data['province'],
                'code_province'   => $data['code_province'],
                'territoire'      => $data['territoire'],
                'code_territoire' => $data['code_territoire'],
                'zone_sante'      => $data['zone_sante'],
                'code_zone_sante' => $data['code_zone_sante'],
                'longitude'       => $data['longitude'],
                'latitude'        => $data['latitude'],
                'menages'         => $data['menages'],
                'individus'       => $data['individus'],
                'source'          => $data['source'],
                'type_fichier'    => $data['type_fichier'],
            ]);
            echo "  Site CREE : id={$site->id}" . PHP_EOL;
        } else {
            echo "  [SIM] Site à créer : $nom | GPS: {$data['longitude']},{$data['latitude']}" . PHP_EOL;
        }
    }

    // 2. Vérifier si le mouvement existe déjà
    if ($site) {
        $exists = SiteMouvementPopulation::where('site_id', $site->id)
            ->where('date_mouvement', $DATE)
            ->where('type_mouvement', $TYPE)
            ->exists();

        if ($exists) {
            echo "  Mouvement déjà présent pour $DATE/$TYPE → skip" . PHP_EOL;
            continue;
        }
    }

    if (!$DRY_RUN && $site) {
        SiteMouvementPopulation::create([
            'site_id'             => $site->id,
            'date_mouvement'      => $DATE,
            'type_mouvement'      => $TYPE,
            'raison_mouvement_id' => null,
            'periode'             => $PERIODE,
            'menages'             => $data['menages'],
            'individus'           => $data['individus'],
            'h_0_5'               => 0,
            'h_6_17'              => $data['h_6_17']  ?? 0,
            'h_18_59'             => $data['h_18_59'] ?? 0,
            'h_60_plus'           => $data['h_60_plus'] ?? 0,
            'f_0_5'               => 0,
            'f_6_17'              => 0,
            'f_18_59'             => 0,
            'f_60_plus'           => 0,
            'source'              => $SOURCE,
            'description'         => "Territoire: GOMA | Hommes: {$data['hommes']} | Femmes: {$data['femmes']}",
            'statut'              => 'valide',
            'created_by'          => 1,
        ]);
        echo "  Mouvement IMPORTE : {$data['menages']} ménages / {$data['individus']} individus" . PHP_EOL;
    } else {
        echo "  [SIM] Mouvement à créer : {$data['menages']} ménages / {$data['individus']} individus" . PHP_EOL;
    }

    echo PHP_EOL;
}

echo str_repeat('=', 60) . PHP_EOL;
echo "Terminé." . PHP_EOL;
