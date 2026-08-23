<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site;
use App\Models\TypeSite;
use App\Models\Gestionnaire;
use App\Models\Coordinateur;
use App\Models\CategorieSite;
use App\Models\Commune;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SitesSeeder extends Seeder
{
    private array $siteTableColumns = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "Importation des sites PDI...\n";
        $this->siteTableColumns = Schema::getColumnListing('sites');

        // Vider les tables dans le bon ordre (enfants avant parents)
        echo "Nettoyage des tables existantes...\n";
        Site::query()->delete();
        TypeSite::query()->delete();
        Gestionnaire::query()->delete();
        Coordinateur::query()->delete();
        CategorieSite::query()->delete();

        $xlsxPath = $this->getMasterListXlsxPath();
        if (file_exists($xlsxPath)) {
            echo "Source détectée: fichier XLSX ($xlsxPath)\n";
            $workbook = IOFactory::load($xlsxPath);
            $this->importFromWorkbook($workbook);
        } else {
            // Étape 1 : Importer les catégories de sites
            echo "Étape 1/5 : Importation des catégories de sites...\n";
            $this->importCategoriesSites();

            // Étape 2 : Importer les types de sites
            echo "Étape 2/5 : Importation des types de sites...\n";
            $this->importTypesSites();

            // Étape 3 : Importer les gestionnaires
            echo "Étape 3/5 : Importation des gestionnaires...\n";
            $this->importGestionnaires();

            // Étape 4 : Importer les coordinateurs
            echo "Étape 4/5 : Importation des coordinateurs...\n";
            $this->importCoordinateurs();

            // Étape 5 : Importer les sites depuis les 3 fichiers
            echo "Étape 5/5 : Importation des sites...\n";
            $this->importSitesFromFile('H:\\PDIs EN COMMUNAUTÉS HÔTES.csv', 'PDIs EN COMMUNAUTÉS HÔTES');
            $this->importSitesFromFile('H:\\SITE SOUS GESTION.csv', 'SITES SOUS GESTION');
            $this->importSitesFromFile('H:\\SITESHORSGESTION.csv', 'SITES HORS GESTION');
        }

        echo "\nImportation terminée avec succès !\n";
        echo "Total des sites importés : " . Site::count() . "\n";
    }

    private function getMasterListXlsxPath(): string
    {
        return env('SITES_MASTERLIST_XLSX', 'H:\\20260805_CCCM Master List_DRC_31_Juillet_2026.xlsx');
    }

    private function importFromWorkbook(Spreadsheet $workbook): void
    {
        echo "Étape 1/5 : Importation des catégories de sites...\n";
        $this->importCategoriesSitesFromWorkbook($workbook);

        echo "Étape 2/5 : Importation des types de sites...\n";
        $this->importTypesSitesFromWorkbook($workbook);

        echo "Étape 3/5 : Importation des gestionnaires...\n";
        $this->importGestionnairesFromWorkbook($workbook);

        echo "Étape 4/5 : Importation des coordinateurs...\n";
        $this->importCoordinateursFromWorkbook($workbook);

        echo "Étape 5/5 : Importation des sites...\n";
        $pdiSheet = $this->findSheetByKeywords($workbook, ['PDIS EN COMMUNAUT', 'HOTES'], ['DEC23']);
        $gestionSheet = $this->findSheetByKeywords($workbook, ['SITES SOUS GESTION']);
        $horsGestionSheet = $this->findSheetByKeywords($workbook, ['SITES HORS GESTION']);

        if (! $pdiSheet) {
            $pdiSheet = $workbook->getSheetByName('PDIs EN COMMUNAUTÉS HÔTES');
        }
        if (! $gestionSheet) {
            $gestionSheet = $workbook->getSheetByName('SITES SOUS GESTION');
        }
        if (! $horsGestionSheet) {
            $horsGestionSheet = $workbook->getSheetByName('SITES HORS GESTION');
        }

        if ($pdiSheet) {
            $this->importSitesFromWorksheet($pdiSheet, 'PDIs EN COMMUNAUTÉS HÔTES');
        }
        if ($gestionSheet) {
            $this->importSitesFromWorksheet($gestionSheet, 'SITES SOUS GESTION');
        }
        if ($horsGestionSheet) {
            $this->importSitesFromWorksheet($horsGestionSheet, 'SITES HORS GESTION');
        }
    }

    private function importCategoriesSitesFromWorkbook(Spreadsheet $workbook): void
    {
        $categoriesUniques = [];
        $sheets = [
            $this->findSheetByKeywords($workbook, ['PDIS EN COMMUNAUT', 'HOTES'], ['DEC23']),
            $this->findSheetByKeywords($workbook, ['SITES SOUS GESTION']),
            $this->findSheetByKeywords($workbook, ['SITES HORS GESTION']),
        ];

        foreach ($sheets as $sheet) {
            if (! $sheet) {
                continue;
            }
            [$header, $rows] = $this->readWorksheetRows($sheet);
            $columnMap = $this->buildColumnMap($header);
            foreach ($rows as $row) {
                $categorie = trim((string) ($this->getValue($row, $columnMap, 'TYPE') ?? ''));
                if ($categorie !== '' && $categorie !== 'No Data' && $categorie !== 'TYPE') {
                    $categoriesUniques[$categorie] = true;
                }
            }
        }

        foreach (array_keys($categoriesUniques) as $categorie) {
            CategorieSite::firstOrCreate(
                ['name' => $categorie],
                ['code' => strtoupper(str_replace(' ', '_', substr($categorie, 0, 20)))]
            );
        }

        echo "  → " . count($categoriesUniques) . " catégories de sites créées/trouvées.\n";
    }

    private function importTypesSitesFromWorkbook(Spreadsheet $workbook): void
    {
        $typesUniques = ['Village' => true];
        $sheets = [
            $this->findSheetByKeywords($workbook, ['SITES SOUS GESTION']),
            $this->findSheetByKeywords($workbook, ['SITES HORS GESTION']),
        ];

        foreach ($sheets as $sheet) {
            if (! $sheet) {
                continue;
            }
            [$header, $rows] = $this->readWorksheetRows($sheet);
            $columnMap = $this->buildColumnMap($header);
            foreach ($rows as $row) {
                $type = trim((string) (
                    $this->getValue($row, $columnMap, 'TYPE DE SITE*')
                    ?? $this->getValue($row, $columnMap, 'TYPE DE SITE')
                    ?? $this->getValue($row, $columnMap, 'TYPE DE SITE ')
                    ?? ''
                ));
                if ($type !== '' && $type !== 'No Data') {
                    $typesUniques[$type] = true;
                }
            }
        }

        foreach (array_keys($typesUniques) as $type) {
            TypeSite::firstOrCreate(
                ['name' => $type],
                ['code' => strtoupper(str_replace(' ', '_', $type))]
            );
        }

        echo "  → " . count($typesUniques) . " types de sites créés/trouvés.\n";
    }

    private function importGestionnairesFromWorkbook(Spreadsheet $workbook): void
    {
        $gestionnairesUniques = [];
        $sheet = $this->findSheetByKeywords($workbook, ['SITES SOUS GESTION']);
        if ($sheet) {
            [$header, $rows] = $this->readWorksheetRows($sheet);
            $columnMap = $this->buildColumnMap($header);
            foreach ($rows as $row) {
                $gestionnaire = trim((string) ($this->getValue($row, $columnMap, 'GESTIONNAIRE*') ?? ''));
                if ($gestionnaire !== '' && $gestionnaire !== 'No Data') {
                    $gestionnairesUniques[$gestionnaire] = true;
                }
            }
        }

        foreach (array_keys($gestionnairesUniques) as $gestionnaire) {
            Gestionnaire::firstOrCreate(
                ['name' => $gestionnaire],
                ['code' => strtoupper(substr($gestionnaire, 0, 10))]
            );
        }

        echo "  → " . count($gestionnairesUniques) . " gestionnaires créés/trouvés.\n";
    }

    private function importCoordinateursFromWorkbook(Spreadsheet $workbook): void
    {
        $coordinateursUniques = [];
        $sheet = $this->findSheetByKeywords($workbook, ['SITES SOUS GESTION']);
        if ($sheet) {
            [$header, $rows] = $this->readWorksheetRows($sheet);
            $columnMap = $this->buildColumnMap($header);
            foreach ($rows as $row) {
                $coordinateur = trim((string) ($this->getValue($row, $columnMap, 'COORDINATEUR*') ?? ''));
                if ($coordinateur !== '' && $coordinateur !== 'No Data') {
                    $coordinateursUniques[$coordinateur] = true;
                }
            }
        }

        foreach (array_keys($coordinateursUniques) as $coordinateur) {
            Coordinateur::firstOrCreate(
                ['name' => $coordinateur],
                ['code' => strtoupper(substr($coordinateur, 0, 10))]
            );
        }

        echo "  → " . count($coordinateursUniques) . " coordinateurs créés/trouvés.\n";
    }

    private function importSitesFromWorksheet(Worksheet $sheet, string $typeFichier): void
    {
        [$header, $rows] = $this->readWorksheetRows($sheet);
        $columnMap = $this->buildColumnMap($header);
        $count = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $siteData = $this->extractSiteData($row, $columnMap, $typeFichier);
                if (! empty($siteData['nom'])) {
                    $this->createSiteWithPopulation($siteData);
                    $count++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        echo "  → $typeFichier : $count sites importés ($errors erreurs)\n";
    }

    private function readWorksheetRows(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestDataRow();
        $header = $sheet->rangeToArray("A1:{$highestColumn}1", null, false, false)[0];
        $rows = [];

        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
            $row = $sheet->rangeToArray("A{$rowIndex}:{$highestColumn}{$rowIndex}", null, false, false)[0];
            $rows[] = $row;
        }

        return [$header, $rows];
    }

    private function buildColumnMap(array $header): array
    {
        $map = [];
        foreach ($header as $index => $column) {
            $map[(string) $column] = $index;
        }
        return $map;
    }

    private function normalizeText(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        return $ascii !== false ? $ascii : $normalized;
    }

    private function findSheetByKeywords(Spreadsheet $workbook, array $keywords, array $excludeKeywords = []): ?Worksheet
    {
        foreach ($workbook->getWorksheetIterator() as $sheet) {
            $title = $this->normalizeText($sheet->getTitle());
            $containsAll = true;
            foreach ($keywords as $keyword) {
                if (! str_contains($title, $this->normalizeText($keyword))) {
                    $containsAll = false;
                    break;
                }
            }
            if (! $containsAll) {
                continue;
            }

            $isExcluded = false;
            foreach ($excludeKeywords as $keyword) {
                if (str_contains($title, $this->normalizeText($keyword))) {
                    $isExcluded = true;
                    break;
                }
            }

            if (! $isExcluded) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * Importe les types de sites uniques depuis les 3 fichiers
     */
    private function importTypesSites()
    {
        $typesUniques = ['Village' => true]; // Type par défaut pour les communautés hôtes

        // Collecter les types depuis les 3 fichiers
        $files = [
            ['path' => 'H:\\SITE SOUS GESTION.csv', 'columns' => ['TYPE DE SITE*', 'TYPE DE SITE']],
            ['path' => 'H:\\SITESHORSGESTION.csv', 'columns' => ['TYPE DE SITE ', 'TYPE DE SITE']],
        ];

        foreach ($files as $file) {
            if (file_exists($file['path'])) {
                $handle = fopen($file['path'], 'r');
                $header = $this->readCsvLine($handle);
                
                // Chercher la première colonne qui existe
                $typeIndex = null;
                foreach ($file['columns'] as $columnName) {
                    $index = array_search($columnName, $header);
                    if ($index !== false) {
                        $typeIndex = $index;
                        break;
                    }
                }

                if ($typeIndex !== null) {
                    while (($row = $this->readCsvLine($handle)) !== false) {
                        $type = trim($row[$typeIndex] ?? '');
                        if (!empty($type) && $type !== 'No Data') {
                            $typesUniques[$type] = true;
                        }
                    }
                }
                fclose($handle);
            }
        }

        // Créer les types de sites
        foreach (array_keys($typesUniques) as $type) {
            TypeSite::firstOrCreate(
                ['name' => $type],
                ['code' => strtoupper(str_replace(' ', '_', $type))]
            );
        }

        echo "  → " . count($typesUniques) . " types de sites créés/trouvés.\n";
    }

    /**
     * Importe les catégories de sites uniques depuis la colonne TYPE des 3 fichiers
     */
    private function importCategoriesSites()
    {
        $categoriesUniques = [];

        // Collecter les catégories depuis les 3 fichiers
        $files = [
            ['path' => 'H:\\PDIs EN COMMUNAUTÉS HÔTES.csv', 'column' => 'TYPE'],
            ['path' => 'H:\\SITE SOUS GESTION.csv', 'column' => 'TYPE'],
            ['path' => 'H:\\SITESHORSGESTION.csv', 'column' => 'TYPE'],
        ];

        foreach ($files as $file) {
            if (file_exists($file['path'])) {
                $handle = fopen($file['path'], 'r');
                $header = $this->readCsvLine($handle);
                $categorieIndex = array_search($file['column'], $header);

                if ($categorieIndex !== false) {
                    while (($row = $this->readCsvLine($handle)) !== false) {
                        $categorie = trim($row[$categorieIndex] ?? '');
                        if (!empty($categorie) && $categorie !== 'No Data' && $categorie !== 'TYPE') {
                            $categoriesUniques[$categorie] = true;
                        }
                    }
                }
                fclose($handle);
            }
        }

        // Créer les catégories de sites
        foreach (array_keys($categoriesUniques) as $categorie) {
            CategorieSite::firstOrCreate(
                ['name' => $categorie],
                ['code' => strtoupper(str_replace(' ', '_', substr($categorie, 0, 20)))]
            );
        }

        echo "  → " . count($categoriesUniques) . " catégories de sites créées/trouvées.\n";
    }

    /**
     * Lit une ligne CSV et convertit l'encodage si nécessaire
     */
    private function readCsvLine($handle)
    {
        $line = fgetcsv($handle, 0, ',');
        
        if ($line === false) {
            return false;
        }
        
        // Convertir l'encodage de Windows-1252 vers UTF-8
        return array_map(function($field) {
            return mb_convert_encoding($field, 'UTF-8', 'Windows-1252');
        }, $line);
    }

    /**
     * Importe les gestionnaires uniques depuis le fichier SITE SOUS GESTION
     */
    private function importGestionnaires()
    {
        $gestionnairesUniques = [];
        $file = 'H:\\SITE SOUS GESTION.csv';

        if (file_exists($file)) {
            $handle = fopen($file, 'r');
            $header = $this->readCsvLine($handle);
            $gestionnaireIndex = array_search('GESTIONNAIRE*', $header);

            if ($gestionnaireIndex !== false) {
                while (($row = $this->readCsvLine($handle)) !== false) {
                    $gestionnaire = trim($row[$gestionnaireIndex] ?? '');
                    if (!empty($gestionnaire) && $gestionnaire !== 'No Data') {
                        $gestionnairesUniques[$gestionnaire] = true;
                    }
                }
            }
            fclose($handle);
        }

        // Créer les gestionnaires
        foreach (array_keys($gestionnairesUniques) as $gestionnaire) {
            Gestionnaire::firstOrCreate(
                ['name' => $gestionnaire],
                ['code' => strtoupper(substr($gestionnaire, 0, 10))]
            );
        }

        echo "  → " . count($gestionnairesUniques) . " gestionnaires créés/trouvés.\n";
    }

    /**
     * Importe les coordinateurs uniques depuis le fichier SITE SOUS GESTION
     */
    private function importCoordinateurs()
    {
        $coordinateursUniques = [];
        $file = 'H:\\SITE SOUS GESTION.csv';

        if (file_exists($file)) {
            $handle = fopen($file, 'r');
            $header = $this->readCsvLine($handle);
            $coordinateurIndex = array_search('COORDINATEUR*', $header);

            if ($coordinateurIndex !== false) {
                while (($row = $this->readCsvLine($handle)) !== false) {
                    $coordinateur = trim($row[$coordinateurIndex] ?? '');
                    if (!empty($coordinateur) && $coordinateur !== 'No Data') {
                        $coordinateursUniques[$coordinateur] = true;
                    }
                }
            }
            fclose($handle);
        }

        // Créer les coordinateurs
        foreach (array_keys($coordinateursUniques) as $coordinateur) {
            Coordinateur::firstOrCreate(
                ['name' => $coordinateur],
                ['code' => strtoupper(substr($coordinateur, 0, 10))]
            );
        }

        echo "  → " . count($coordinateursUniques) . " coordinateurs créés/trouvés.\n";
    }

    /**
     * Importe les sites depuis un fichier CSV
     */
    private function importSitesFromFile($filePath, $typeFichier)
    {
        if (!file_exists($filePath)) {
            echo "  ⚠ Fichier non trouvé : $filePath\n";
            return;
        }

        $handle = fopen($filePath, 'r');
        $header = $this->readCsvLine($handle);
        
        // Mapper les colonnes selon le fichier
        $columnMap = $this->getColumnMap($header, $typeFichier);
        
        $count = 0;
        $errors = 0;

        while (($row = $this->readCsvLine($handle)) !== false) {
            try {
                $siteData = $this->extractSiteData($row, $columnMap, $typeFichier);
                
                if (!empty($siteData['nom'])) {
                    $this->createSiteWithPopulation($siteData);
                    $count++;
                }
            } catch (\Exception $e) {
                $errors++;
                // echo "  ⚠ Erreur ligne : " . $e->getMessage() . "\n";
            }
        }

        fclose($handle);
        echo "  → $typeFichier : $count sites importés ($errors erreurs)\n";
    }

    /**
     * Retourne le mapping des colonnes selon le type de fichier
     */
    private function getColumnMap($header, $typeFichier)
    {
        $map = [];
        
        foreach ($header as $index => $column) {
            $map[$column] = $index;
        }
        
        return $map;
    }

    /**
     * Extrait les données d'un site depuis une ligne CSV
     */
    private function extractSiteData($row, $columnMap, $typeFichier)
    {
        // Déterminer le nom du site selon le fichier
        $nom = '';
        if ($typeFichier === 'PDIs EN COMMUNAUTÉS HÔTES') {
            $nom = $this->getValue($row, $columnMap, 'VILLAGE')
                ?? $this->getValue($row, $columnMap, 'NOM DU SITE')
                ?? $this->getValue($row, $columnMap, 'NOM DU SITE*');
        } else {
            $nom = $this->getValue($row, $columnMap, 'NOM DU SITE') 
                   ?? $this->getValue($row, $columnMap, 'NOM DU SITE*');
        }

        // Code zone de santé pour lier avec la table communes
        $codeZoneSante = $this->getValue($row, $columnMap, 'CODE ZONE DE SANTE')
                      ?? $this->getValue($row, $columnMap, 'CODE ZONE DE SANTE');
        
        // Trouver la commune correspondante
        $commune = null;
        if ($codeZoneSante) {
            $commune = Commune::where('pcode', trim($codeZoneSante))->first();
        }

        // Type de site
        $typeSiteNom = $this->getValue($row, $columnMap, 'TYPE DE SITE')
                    ?? $this->getValue($row, $columnMap, 'TYPE DE SITE*')
                    ?? $this->getValue($row, $columnMap, 'TYPE DE SITE ')
                    ?? 'Village'; // Par défaut pour les communautés hôtes
        
        $typeSite = TypeSite::where('name', trim($typeSiteNom))->first();

        // Gestionnaire et Coordinateur (seulement pour SITES SOUS GESTION)
        $gestionnaire = null;
        $coordinateur = null;
        
        if ($typeFichier === 'SITES SOUS GESTION') {
            $gestionnaireNom = $this->getValue($row, $columnMap, 'GESTIONNAIRE*');
            $coordinateurNom = $this->getValue($row, $columnMap, 'COORDINATEUR*');
            
            if ($gestionnaireNom && $gestionnaireNom !== 'No Data') {
                $gestionnaire = Gestionnaire::where('name', trim($gestionnaireNom))->first();
            }
            
            if ($coordinateurNom && $coordinateurNom !== 'No Data') {
                $coordinateur = Coordinateur::where('name', trim($coordinateurNom))->first();
            }
        }

        // Catégorie de site (depuis la colonne TYPE)
        $categorieSite = null;
        $categorieNom = $this->getValue($row, $columnMap, 'TYPE');
        
        if ($categorieNom && $categorieNom !== 'No Data' && $categorieNom !== 'TYPE') {
            $categorieSite = CategorieSite::where('name', trim($categorieNom))->first();
        }

        // Date de mise à jour
        $dateMiseAJour = null;
        $dateStr = $this->getValue($row, $columnMap, 'DATE DE LA DERNIERE MISE A JOUR*')
                ?? $this->getValue($row, $columnMap, 'DATE DE MISE A JOUR/ ENQUÊTE');
        
        if ($dateStr) {
            try {
                $dateMiseAJour = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dateStr))->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignorer les erreurs de date
            }
        }

        return [
            'nom' => trim($nom),
            'code_site' => $this->getValue($row, $columnMap, 'CODE SITE') 
                        ?? $this->getValue($row, $columnMap, 'CODE SITE*'),
            'type_site_id' => $typeSite?->id,
            'commune_id' => $commune?->id,
            'gestionnaire_id' => $gestionnaire?->id,
            'coordinateur_id' => $coordinateur?->id,
            'categorie_site_id' => $categorieSite?->id,
            'province' => $this->getValue($row, $columnMap, 'PROVINCE') 
                       ?? $this->getValue($row, $columnMap, 'PROVINCE*'),
            'code_province' => trim($this->getValue($row, $columnMap, 'CODE PROVINCE  ') ?? ''),
            'territoire' => $this->getValue($row, $columnMap, 'TERRITOIRE')
                         ?? $this->getValue($row, $columnMap, 'TERRITOIRE*'),
            'code_territoire' => $this->getValue($row, $columnMap, 'CODE TERRITOIRE'),
            'zone_sante' => $this->getValue($row, $columnMap, 'ZONE DE SANTE')
                         ?? $this->getValue($row, $columnMap, 'ZONE DE SANTE*'),
            'code_zone_sante' => $codeZoneSante,
            'aire_sante' => $this->getValue($row, $columnMap, 'AIRE DE SANTE'),
            'code_aire_sante' => $this->getValue($row, $columnMap, 'CODE AIRE DE SANTE'),
            'longitude' => $this->parseDecimal($this->getValue($row, $columnMap, 'LONGITUDE')
                        ?? $this->getValue($row, $columnMap, 'LONGITUDE*')
                        ?? $this->getValue($row, $columnMap, 'LONGITUDE ')),
            'latitude' => $this->parseDecimal($this->getValue($row, $columnMap, 'LATITUDE')
                       ?? $this->getValue($row, $columnMap, 'LATITUDE*')),
            'menages' => $this->parseInt($this->getValue($row, $columnMap, 'MENAGES')
                      ?? $this->getValue($row, $columnMap, 'MENAGES*')),
            'individus' => $this->parseInt($this->getValue($row, $columnMap, 'INDIVIDUS')
                        ?? $this->getValue($row, $columnMap, 'INDIVIDUS*')),
            'f_0_5' => $this->parseInt($this->getValue($row, $columnMap, '0 - 5 F')),
            'f_6_17' => $this->parseInt($this->getValue($row, $columnMap, '6 - 17 F')),
            'f_18_59' => $this->parseInt($this->getValue($row, $columnMap, '18 - 59 F ')
                      ?? $this->parseInt($this->getValue($row, $columnMap, '18 - 59 F'))),
            'f_60_plus' => $this->parseInt($this->getValue($row, $columnMap, '60 + F ')
                        ?? $this->parseInt($this->getValue($row, $columnMap, '60 + F'))),
            'h_0_5' => $this->parseInt($this->getValue($row, $columnMap, '0 - 5 H')),
            'h_6_17' => $this->parseInt($this->getValue($row, $columnMap, '6 - 17 H')),
            'h_18_59' => $this->parseInt($this->getValue($row, $columnMap, '18 - 59 H')),
            'h_60_plus' => $this->parseInt($this->getValue($row, $columnMap, '60 + H')),
            'source' => $this->getValue($row, $columnMap, 'SOURCE'),
            'round' => $this->getValue($row, $columnMap, 'ROUND'),
            'type_gestion' => $this->getValue($row, $columnMap, 'TYPE DE GESTION'),
            'date_mise_a_jour' => $dateMiseAJour,
            'type_fichier' => $typeFichier,
        ];
    }

    /**
     * Récupère une valeur depuis une ligne CSV
     */
    private function getValue($row, $columnMap, $columnName)
    {
        $index = $columnMap[$columnName] ?? null;
        if ($index !== null && isset($row[$index])) {
            $value = trim($row[$index]);
            return $value !== '' && $value !== 'No Data' ? $value : null;
        }
        return null;
    }

    private function createSiteWithPopulation(array $siteData): Site
    {
        $populationFields = [
            'menages', 'individus',
            'f_0_5', 'f_6_17', 'f_18_59', 'f_60_plus',
            'h_0_5', 'h_6_17', 'h_18_59', 'h_60_plus',
        ];
        $site = Site::create($this->filterSiteDataForCurrentSchema(
            collect($siteData)->except($populationFields)->all()
        ));

        if (! Schema::hasTable('site_mouvements_population')) {
            return $site;
        }

        $population = collect($populationFields)
            ->mapWithKeys(fn (string $field) => [$field => abs((int) ($siteData[$field] ?? 0))])
            ->all();

        if (collect($population)->doesntContain(fn (int $value) => $value !== 0)) {
            return $site;
        }

        DB::table('site_mouvements_population')->insert([
            'site_id' => $site->id,
            'date_mouvement' => $siteData['date_mise_a_jour'] ?? now()->toDateString(),
            'type_mouvement' => 'recensement',
            'periode' => date('Y-m', strtotime($siteData['date_mise_a_jour'] ?? now()->toDateString())),
            ...$population,
            'source' => $siteData['source'] ?? 'sites_seeder',
            'round' => $siteData['round'] ?? null,
            'statut' => 'valide',
            'validated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $site;
    }

    /**
     * Parse un nombre entier depuis une chaîne
     */
    private function parseInt($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Retirer les espaces et les virgules
        $cleaned = str_replace([' ', ','], '', $value);
        return is_numeric($cleaned) ? (int)$cleaned : null;
    }

    /**
     * Parse un nombre décimal depuis une chaîne
     */
    private function parseDecimal($value)
    {
        if ($value === null || $value === '' || $value === '0.000' || $value === '0.000 ') {
            return null;
        }
        
        // Retirer les espaces
        $cleaned = trim(str_replace(' ', '', $value));
        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    private function filterSiteDataForCurrentSchema(array $siteData): array
    {
        if (empty($this->siteTableColumns)) {
            $this->siteTableColumns = Schema::getColumnListing('sites');
        }

        return array_filter(
            $siteData,
            fn ($key) => in_array($key, $this->siteTableColumns, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
