<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Province;
use App\Models\Territoire;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminBoundariesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Importing administrative boundaries...');

        $sources = $this->resolveBoundarySources();

        if (! empty($sources['xlsx'])) {
            $this->importProvincesFromXlsx($sources['xlsx']);
            $this->importTerritoiresFromXlsx($sources['xlsx']);
            $this->importCommunesFromXlsx($sources['xlsx'], $sources['geojson']['communes'] ?? null);

            return;
        }

        $this->importProvincesFromGeoJson($sources['geojson']['provinces']);
        $this->importTerritoiresFromGeoJson($sources['geojson']['territoires']);
        $this->importCommunesFromGeoJson($sources['geojson']['communes']);

        $this->command->info('Administrative boundaries imported successfully!');
    }

    /**
     * Resolve the available boundary source files.
     */
    private function resolveBoundarySources(): array
    {
        $geojsonDir = base_path('public/geojson');
        $xlsxPaths = [
            'H:\\cod_adminboundaries_tabulardata.xlsx',
            'H:\\cod_admin_boundaries.xlsx',
            base_path('storage/app/adminboundaries.xlsx'),
        ];

        foreach ($xlsxPaths as $path) {
            if (is_string($path) && is_file($path)) {
                return [
                    'xlsx' => $path,
                    'geojson' => [
                        'provinces' => $geojsonDir.'/cod_admin1.geojson',
                        'territoires' => $geojsonDir.'/cod_admin2.geojson',
                        'communes' => $geojsonDir.'/cod_admin3.geojson',
                    ],
                ];
            }
        }

        return [
            'xlsx' => null,
            'geojson' => [
                'provinces' => $geojsonDir.'/cod_admin1.geojson',
                'territoires' => $geojsonDir.'/cod_admin2.geojson',
                'communes' => $geojsonDir.'/cod_admin3.geojson',
            ],
        ];
    }

    /**
     * Import provinces from XLSX workbook.
     */
    private function importProvincesFromXlsx(string $filePath): void
    {
        $this->command->info('Importing provinces from XLSX...');

        $count = 0;

        foreach ($this->readSheetRows($filePath, 'ADM1') as $row) {
            $pcode = $this->normalizeString($row['ADM1_PCODE'] ?? null);
            $name = $this->normalizeString($row['ADM1_FR'] ?? null) ?: 'Unknown';

            if ($pcode === '') {
                continue;
            }

            Province::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $name,
                    'area_sqkm' => $this->castDecimal($row['AREA_SQKM'] ?? null),
                    'center_lat' => null,
                    'center_lon' => null,
                    'geometry' => [],
                    'properties' => $row,
                ]
            );

            $count++;
        }

        $this->command->info($count.' provinces imported.');
    }

    /**
     * Import territoires from XLSX workbook.
     */
    private function importTerritoiresFromXlsx(string $filePath): void
    {
        $this->command->info('Importing territoires from XLSX...');

        $count = 0;

        foreach ($this->readSheetRows($filePath, 'ADM2') as $row) {
            $pcode = $this->normalizeString($row['ADM2_PCODE'] ?? null);
            $provincePcode = $this->normalizeString($row['ADM1_PCODE'] ?? null);
            $name = $this->normalizeString($row['ADM2_FR'] ?? null) ?: 'Unknown';

            if ($pcode === '' || $provincePcode === '') {
                continue;
            }

            $province = Province::where('pcode', $provincePcode)->first();
            if (! $province) {
                continue;
            }

            Territoire::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $name,
                    'province_id' => $province->id,
                    'area_sqkm' => $this->castDecimal($row['AREA_SQKM'] ?? null),
                    'center_lat' => null,
                    'center_lon' => null,
                    'geometry' => [],
                    'properties' => $row,
                ]
            );

            $count++;
        }

        $this->command->info($count.' territoires imported.');
    }

    /**
     * Import communes from XLSX workbook when an ADM3 sheet is available.
     */
    private function importCommunesFromXlsx(string $filePath, ?string $geojsonPath = null): void
    {
        $this->command->info('Importing communes...');

        $count = 0;
        $sheetRows = $this->readSheetRows($filePath, 'ADM3');

        if (empty($sheetRows) && $geojsonPath !== null && is_file($geojsonPath)) {
            $this->importCommunesFromGeoJson($geojsonPath);

            return;
        }

        foreach ($sheetRows as $row) {
            $pcode = $this->normalizeString($row['ADM3_PCODE'] ?? null);
            $territoirePcode = $this->normalizeString($row['ADM2_PCODE'] ?? null);
            $provincePcode = $this->normalizeString($row['ADM1_PCODE'] ?? null);
            $name = $this->normalizeString($row['ADM3_FR'] ?? null) ?: 'Unknown';

            if ($pcode === '' || $territoirePcode === '' || $provincePcode === '') {
                continue;
            }

            $territoire = Territoire::where('pcode', $territoirePcode)->first();
            $province = Province::where('pcode', $provincePcode)->first();

            if (! $territoire || ! $province) {
                continue;
            }

            Commune::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $name,
                    'territoire_id' => $territoire->id,
                    'province_id' => $province->id,
                    'area_sqkm' => $this->castDecimal($row['AREA_SQKM'] ?? null),
                    'center_lat' => null,
                    'center_lon' => null,
                    'geometry' => [],
                    'properties' => $row,
                ]
            );

            $count++;
        }

        $this->command->info($count.' communes imported.');
    }

    /**
     * Import provinces from GeoJSON.
     */
    private function importProvincesFromGeoJson(string $filePath): void
    {
        $this->command->info('Importing provinces from GeoJSON...');

        $geojson = json_decode(file_get_contents($filePath), true);
        $count = 0;

        foreach ($geojson['features'] ?? [] as $feature) {
            $props = $feature['properties'] ?? [];
            $pcode = $this->extractPcode($props, ['adm1_pcode', 'ADM1_PCODE']);

            if ($pcode === '') {
                continue;
            }

            Province::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $this->extractName($props, ['adm1_name', 'ADM1_NAME']) ?? 'Unknown',
                    'area_sqkm' => $this->castDecimal($props['area_sqkm'] ?? $props['AREA_SQKM'] ?? null),
                    'center_lat' => $this->castDecimal($props['center_lat'] ?? $props['CENTER_LAT'] ?? null),
                    'center_lon' => $this->castDecimal($props['center_lon'] ?? $props['CENTER_LON'] ?? null),
                    'geometry' => $feature['geometry'] ?? [],
                    'properties' => $props,
                ]
            );

            $count++;
        }

        $this->command->info($count.' provinces imported.');
    }

    /**
     * Import territoires from GeoJSON.
     */
    private function importTerritoiresFromGeoJson(string $filePath): void
    {
        $this->command->info('Importing territoires from GeoJSON...');

        $geojson = json_decode(file_get_contents($filePath), true);
        $count = 0;

        foreach ($geojson['features'] ?? [] as $feature) {
            $props = $feature['properties'] ?? [];
            $pcode = $this->extractPcode($props, ['adm2_pcode', 'ADM2_PCODE']);
            $provincePcode = $this->extractPcode($props, ['adm1_pcode', 'ADM1_PCODE']);

            if ($pcode === '' || $provincePcode === '') {
                continue;
            }

            $province = Province::where('pcode', $provincePcode)->first();
            if (! $province) {
                continue;
            }

            Territoire::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $this->extractName($props, ['adm2_name', 'ADM2_NAME']) ?? 'Unknown',
                    'province_id' => $province->id,
                    'area_sqkm' => $this->castDecimal($props['area_sqkm'] ?? $props['AREA_SQKM'] ?? null),
                    'center_lat' => $this->castDecimal($props['center_lat'] ?? $props['CENTER_LAT'] ?? null),
                    'center_lon' => $this->castDecimal($props['center_lon'] ?? $props['CENTER_LON'] ?? null),
                    'geometry' => $feature['geometry'] ?? [],
                    'properties' => $props,
                ]
            );

            $count++;
        }

        $this->command->info($count.' territoires imported.');
    }

    /**
     * Import communes from GeoJSON.
     */
    private function importCommunesFromGeoJson(string $filePath): void
    {
        $this->command->info('Importing communes from GeoJSON...');

        $geojson = json_decode(file_get_contents($filePath), true);
        $count = 0;

        foreach ($geojson['features'] ?? [] as $feature) {
            $props = $feature['properties'] ?? [];
            $pcode = $this->extractPcode($props, ['adm3_pcode', 'ADM3_PCODE']);
            $territoirePcode = $this->extractPcode($props, ['adm2_pcode', 'ADM2_PCODE']);
            $provincePcode = $this->extractPcode($props, ['adm1_pcode', 'ADM1_PCODE']);

            if ($pcode === '' || $territoirePcode === '' || $provincePcode === '') {
                continue;
            }

            $territoire = Territoire::where('pcode', $territoirePcode)->first();
            $province = Province::where('pcode', $provincePcode)->first();

            if (! $territoire || ! $province) {
                continue;
            }

            Commune::updateOrCreate(
                ['pcode' => $pcode],
                [
                    'name' => $this->extractName($props, ['adm3_name', 'ADM3_NAME']) ?? 'Unknown',
                    'territoire_id' => $territoire->id,
                    'province_id' => $province->id,
                    'area_sqkm' => $this->castDecimal($props['area_sqkm'] ?? $props['AREA_SQKM'] ?? null),
                    'center_lat' => $this->castDecimal($props['center_lat'] ?? $props['CENTER_LAT'] ?? null),
                    'center_lon' => $this->castDecimal($props['center_lon'] ?? $props['CENTER_LON'] ?? null),
                    'geometry' => $feature['geometry'] ?? [],
                    'properties' => $props,
                ]
            );

            $count++;
        }

        $this->command->info($count.' communes imported.');
    }

    /**
     * Read rows from a named spreadsheet sheet and normalize headers.
     */
    private function readSheetRows(string $xlsxPath, string $sheetName): array
    {
        $spreadsheet = IOFactory::load($xlsxPath);
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (! $sheet) {
            return [];
        }

        $rows = $sheet->toArray();
        if (count($rows) < 2) {
            return [];
        }

        $headerRow = array_map(fn ($value) => trim((string) ($value ?? '')), $rows[0]);
        $normalized = [];

        foreach (array_slice($rows, 1) as $row) {
            $record = [];
            foreach ($headerRow as $index => $header) {
                $record[strtoupper(trim($header))] = $row[$index] ?? null;
            }
            $normalized[] = $record;
        }

        return $normalized;
    }

    /**
     * Return a normalized string value.
     */
    private function normalizeString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Return a normalized name from property arrays.
     */
    private function extractName(array $props, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($props[$key]) && $this->normalizeString($props[$key]) !== '') {
                return $this->normalizeString($props[$key]);
            }
        }

        return null;
    }

    /**
     * Return a normalized pcode from property arrays.
     */
    private function extractPcode(array $props, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($props[$key])) {
                $value = $this->normalizeString($props[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Cast a decimal-like value while tolerating blanks.
     */
    private function castDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
