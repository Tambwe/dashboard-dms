<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteGeography;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeographicCollectedLayersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_site')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('geojson_data')->nullable();
            $table->string('geometry_type')->nullable();
            $table->timestamps();
        });
        Schema::create('site_geographies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('geometry_type')->nullable();
            $table->string('point_category')->nullable();
            $table->string('point_category_other')->nullable();
            $table->string('polygon_category')->nullable();
            $table->string('polygon_block_name')->nullable();
            $table->json('geojson_data')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
        });
        Schema::create('site_mouvements_population', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->date('date_mouvement');
            $table->string('type_mouvement');
            $table->string('statut');
            foreach ([
                'menages',
                'individus',
                'f_0_5',
                'f_6_17',
                'f_18_59',
                'f_60_plus',
                'h_0_5',
                'h_6_17',
                'h_18_59',
                'h_60_plus',
            ] as $field) {
                $table->integer($field)->default(0);
            }
            $table->timestamps();
        });
    }

    public function test_map_api_returns_all_collected_geometries_with_layer_names(): void
    {
        $site = Site::create([
            'nom' => 'Site cartographié',
            'code_site' => 'CARTO-1',
            'latitude' => -4.3243,
            'longitude' => 15.2768,
        ]);

        SiteGeography::create([
            'site_id' => $site->id,
            'geometry_type' => 'point',
            'point_category' => 'autre',
            'point_category_other' => 'Bureau communautaire',
            'geojson_data' => $this->pointGeojson(),
            'collected_at' => '2026-08-22 10:00:00',
        ]);
        SiteGeography::create([
            'site_id' => $site->id,
            'geometry_type' => 'polygon',
            'polygon_category' => 'bloc',
            'polygon_block_name' => 'Bloc A',
            'geojson_data' => $this->polygonGeojson(),
            'collected_at' => '2026-08-23 10:00:00',
        ]);

        $response = $this->getJson("/api/geographic/sites-coordinates?site_id={$site->id}");

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('sites.0.collected_layers.0.name', 'Bureau communautaire')
            ->assertJsonPath('sites.0.collected_layers.0.point_icon', '📍')
            ->assertJsonPath('sites.0.collected_layers.1.name', 'Bloc - Bloc A')
            ->assertJsonPath('sites.0.geojson_data.layers.0.geojson.type', 'FeatureCollection')
            ->assertJsonPath('sites.0.collected_layers.1.geojson.features.0.geometry.coordinates.0.4.0', 15.2767)
            ->assertJsonPath('sites.0.collected_layers.1.geojson.features.0.geometry.coordinates.0.4.1', -4.3242)
            ->assertJsonCount(2, 'sites.0.collected_layers');
    }

    private function pointGeojson(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [15.2768, -4.3243],
                ],
            ]],
        ];
    }

    private function polygonGeojson(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [15.2767, -4.3242],
                        [15.2769, -4.3242],
                        [15.2769, -4.3244],
                        [15.2767, -4.3244],
                    ]],
                ],
            ]],
        ];
    }
}
