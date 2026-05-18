<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$FILE = 'H:\005 - 30052023_Master List DRC_Mai 2023_v3.xlsx';

$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($FILE);

echo "Nombre de feuilles: " . $spreadsheet->getSheetCount() . PHP_EOL;
for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
    $s = $spreadsheet->getSheet($i);
    echo "  Feuille $i: " . $s->getTitle() . " | MaxRow: " . $s->getHighestRow() . PHP_EOL;
}

// Choisir la bonne feuille
$sheet = $spreadsheet->getSheetCount() > 1 ? $spreadsheet->getSheet(1) : $spreadsheet->getSheet(0);
echo PHP_EOL . "Feuille utilisée: " . $sheet->getTitle() . PHP_EOL;
$data = $sheet->toArray(null, true, false, true);

// En-tête
echo PHP_EOL . "=== EN-TETE (ligne 1) ===" . PHP_EOL;
foreach ($data[1] ?? [] as $col => $val) {
    if (trim((string)$val) !== '') {
        echo "  $col => $val" . PHP_EOL;
    }
}

// Lignes 74 à 82
echo PHP_EOL . "=== LIGNES 74 à 82 ===" . PHP_EOL;
for ($r = 74; $r <= 82; $r++) {
    $row = $data[$r] ?? [];
    echo "L$r:";
    foreach ($row as $col => $val) {
        if (trim((string)$val) !== '') {
            echo " [$col]=$val";
        }
    }
    echo PHP_EOL;
}
