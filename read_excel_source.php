<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Site;

// --- PARTIE 1: Afficher les sites DB ---
echo "=== SITES EN BASE ===" . PHP_EOL;
$sites = Site::select('id','code_site','nom')->get();
echo "Total: " . $sites->count() . PHP_EOL;
$sites->each(function($s) {
    echo $s->id . ' | ' . $s->code_site . ' | ' . $s->nom . PHP_EOL;
});
echo PHP_EOL;

$file = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3 - Copy.xlsx';

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);

$sheets = $spreadsheet->getSheetNames();
echo "=== FEUILLES ===" . PHP_EOL;
foreach ($sheets as $i => $name) {
    echo "[$i] $name" . PHP_EOL;
}
echo PHP_EOL;

// Lire la première feuille
$sheet = $spreadsheet->getSheet(0);
$highestRow = min($sheet->getHighestRow(), 10);
$highestCol = $sheet->getHighestColumn();

echo "=== FEUILLE 0: " . $sheets[0] . " ===" . PHP_EOL;
echo "Lignes: " . $sheet->getHighestRow() . ", Colonnes: $highestCol" . PHP_EOL;
echo PHP_EOL;

echo "=== 5 PREMIERES LIGNES (toutes colonnes jusqu'a AC) ===" . PHP_EOL;
for ($row = 1; $row <= 5; $row++) {
    echo "--- Ligne $row ---" . PHP_EOL;
    $colIndex = 1;
    foreach (range('A', 'Z') as $col) {
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            echo "  $col: $val" . PHP_EOL;
        }
        $colIndex++;
    }
    // AA, AB, AC
    foreach (['AA','AB','AC'] as $col) {
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            echo "  $col: $val" . PHP_EOL;
        }
    }
}
