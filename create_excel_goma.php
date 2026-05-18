<?php
/**
 * Crée un fichier Excel avec les 2 sites de Goma manquants
 * Output: H:\goma_sites_mai2023.xlsx
 */
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$OUTPUT = __DIR__ . '\goma_sites_mai2023.xlsx';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('DRC_Master List Data_Mai2023');

// === EN-TÊTE ===
$headers = [
    'A' => '№',
    'B' => 'PROVINCE',
    'C' => 'CODE PROVINCE',
    'D' => 'TERRITOIRE',
    'E' => 'CODE TERRITOIRE',
    'F' => 'ZONE DE SANTE',
    'G' => 'CODE ZONE DE SANTE',
    'H' => 'NOM DU SITE',
    'I' => 'CODE SITE',
    'J' => 'TYPE DE SITE',
    'K' => 'MECANISME',
    'L' => 'OBSERVATION MECANISME',
    'M' => 'LONGITUDE',
    'N' => 'LATITUDE',
    'O' => 'MENAGES',
    'P' => 'INDIVIDUS',
    'Q' => 'Hommes',
    'R' => 'Femmes',
    'S' => 'Enfants 0-17 ans',
    'T' => 'Filles 0-17 ans',
    'U' => 'Garcons 0-17 ans',
    'V' => '6 - 17 H',
    'W' => '18 - 59 H',
    'X' => '60 + H',
    'Y' => 'GESTIONNAIRE',
    'Z' => 'COORDINATEUR',
    'AA' => 'DATE DE LA DERNIERE MISE A JOUR',
];

foreach ($headers as $col => $label) {
    $sheet->setCellValue("{$col}1", $label);
}

// Style en-tête : gras + fond bleu
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F497D']],
];
$sheet->getStyle('A1:AA1')->applyFromArray($headerStyle);

// === DONNÉES ===
$rows = [
    2 => [
        'A' => 77, 'B' => 'NORD-KIVU', 'C' => 'CD61', 'D' => 'GOMA', 'E' => 'CD6101',
        'F' => 'GOMA', 'G' => 'CD5405ZS11', 'H' => 'BULENGO/LAC VERT', 'I' => 'N/A',
        'J' => 'Site planifié', 'K' => 'CCCM', 'L' => 'N/A',
        'M' => 29.1202, 'N' => -1.6227,
        'O' => 20512, 'P' => 83799, 'Q' => 23305, 'R' => 29354,
        'S' => 31140, 'T' => 14990, 'U' => 16150,
        'V' => 'N/A', 'W' => 'N/A', 'X' => 'N/A',
        'Y' => 'OIM', 'Z' => 'AIDES', 'AA' => '30/06/2023',
    ],
    3 => [
        'A' => 78, 'B' => 'NORD-KIVU', 'C' => 'CD61', 'D' => 'GOMA', 'E' => 'CD6101',
        'F' => 'GOMA', 'G' => 'CD5405ZS11', 'H' => 'LUSHAGALA/LAC VERT', 'I' => 'N/A',
        'J' => 'Site planifié', 'K' => 'CCCM', 'L' => 'N/A',
        'M' => 29.1284, 'N' => -1.6026,
        'O' => 4650, 'P' => 18284, 'Q' => 4898, 'R' => 6521,
        'S' => 6865, 'T' => 3314, 'U' => 3551,
        'V' => 'N/A', 'W' => 'N/A', 'X' => 'N/A',
        'Y' => 'OIM', 'Z' => 'AIDES', 'AA' => '30/06/2023',
    ],
];

foreach ($rows as $rowNum => $cells) {
    foreach ($cells as $col => $val) {
        $sheet->setCellValue("{$col}{$rowNum}", $val);
    }
}

// Auto-largeur colonnes
foreach (array_keys($headers) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Écriture du fichier
$writer = new Xlsx($spreadsheet);
$writer->save($OUTPUT);

echo "Fichier créé : $OUTPUT" . PHP_EOL;
echo "  Ligne 2 : BULENGO/LAC VERT   | Territoire: GOMA | 20512 ménages | 83799 individus" . PHP_EOL;
echo "  Ligne 3 : LUSHAGALA/LAC VERT | Territoire: GOMA |  4650 ménages | 18284 individus" . PHP_EOL;
