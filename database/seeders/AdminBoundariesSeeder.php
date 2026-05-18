<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Territoire;
use App\Models\Commune;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminBoundariesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Importing administrative boundaries from GeoJSON files...');

        // Path to GeoJSON files
        $basePath = 'H:\\cod_admin_boundaries.geojson\\';

        // Import Provinces (admin1)
        $this->importProvinces($basePath . 'cod_admin1.geojson');

        // Import Territoires (admin2)
        $this->importTerritoires($basePath . 'cod_admin2.geojson');

        // Import Communes (admin3)
        $this->importCommunes($basePath . 'cod_admin3.geojson');

        $this->command->info('Administrative boundaries imported successfully!');
    }

    /**
     * Import provinces from GeoJSON
     */
    private function importProvinces(string $filePath): void
    {
        $this->command->info('Importing provinces...');

        $geojson = json_decode(file_get_contents($filePath), true);

        foreach ($geojson['features'] as $feature) {
            $props = $feature['properties'];

            Province::create([
                'name' => $props['adm1_name'] ?? $props['ADM1_NAME'] ?? 'Unknown',
                'pcode' => $props['adm1_pcode'] ?? $props['ADM1_PCODE'] ?? '',
                'area_sqkm' => $props['area_sqkm'] ?? null,
                'center_lat' => $props['center_lat'] ?? null,
                'center_lon' => $props['center_lon'] ?? null,
                'geometry' => $feature['geometry'],
                'properties' => $props,
            ]);
        }

        $this->command->info(count($geojson['features']) . ' provinces imported.');
    }

    /**
     * Import territoires from GeoJSON
     */
    private function importTerritoires(string $filePath): void
    {
        $this->command->info('Importing territoires...');

        $geojson = json_decode(file_get_contents($filePath), true);

        foreach ($geojson['features'] as $feature) {
            $props = $feature['properties'];

            // Find parent province by pcode
            $province = Province::where('pcode', $props['adm1_pcode'] ?? $props['ADM1_PCODE'] ?? '')->first();

            if ($province) {
                Territoire::create([
                    'name' => $props['adm2_name'] ?? $props['ADM2_NAME'] ?? 'Unknown',
                    'pcode' => $props['adm2_pcode'] ?? $props['ADM2_PCODE'] ?? '',
                    'province_id' => $province->id,
                    'area_sqkm' => $props['area_sqkm'] ?? null,
                    'center_lat' => $props['center_lat'] ?? null,
                    'center_lon' => $props['center_lon'] ?? null,
                    'geometry' => $feature['geometry'],
                    'properties' => $props,
                ]);
            }
        }

        $this->command->info(count($geojson['features']) . ' territoires imported.');
    }

    /**
     * Import communes from GeoJSON
     */
    private function importCommunes(string $filePath): void
    {
        $this->command->info('Importing communes...');

        $geojson = json_decode(file_get_contents($filePath), true);
        $count = 0;

        foreach ($geojson['features'] as $feature) {
            $props = $feature['properties'];

            // Find parent territoire by pcode
            $territoire = Territoire::where('pcode', $props['adm2_pcode'] ?? $props['ADM2_PCODE'] ?? '')->first();
            $province = Province::where('pcode', $props['adm1_pcode'] ?? $props['ADM1_PCODE'] ?? '')->first();

            if ($territoire && $province) {
                Commune::create([
                    'name' => $props['adm3_name'] ?? $props['ADM3_NAME'] ?? 'Unknown',
                    'pcode' => $props['adm3_pcode'] ?? $props['ADM3_PCODE'] ?? '',
                    'territoire_id' => $territoire->id,
                    'province_id' => $province->id,
                    'area_sqkm' => $props['area_sqkm'] ?? null,
                    'center_lat' => $props['center_lat'] ?? null,
                    'center_lon' => $props['center_lon'] ?? null,
                    'geometry' => $feature['geometry'],
                    'properties' => $props,
                ]);
                $count++;
            }
        }

        $this->command->info($count . ' communes imported.');
    }
}
