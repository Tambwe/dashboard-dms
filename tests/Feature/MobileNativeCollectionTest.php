<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileNativeCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('mobile_questionnaire_submissions');
        Schema::dropIfExists('mobile_questionnaires');
        Schema::dropIfExists('mobile_collection_submissions');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('users');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_site')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_collection_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('sector');
            $table->string('sector')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_native_sector_payload_can_be_saved(): void
    {
        $user = User::factory()->create();
        $site = Site::create([
            'nom' => 'Site de test',
            'code_site' => 'SITE-TEST-1',
        ]);

        $response = $this->json('POST', '/api/mobile/save', [
            'user_id' => $user->id,
            'type' => 'sector',
            'site_id' => $site->id,
            'sector' => 'wash',
            'payload' => [
                'date_collecte' => '2026-08-20',
                'wash_disponible' => '1',
                'wash_points_eau' => '3',
                'wash_observations' => 'Nettoyage de la station',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stored', true);

        $this->assertDatabaseHas('mobile_collection_submissions', [
            'site_id' => $site->id,
            'type' => 'sector',
            'sector' => 'wash',
        ]);
    }

    public function test_native_geography_payload_can_be_saved(): void
    {
        $user = User::factory()->create();
        $site = Site::create([
            'nom' => 'Site géo',
            'code_site' => 'SITE-GEO-1',
        ]);

        $response = $this->json('POST', '/api/mobile/save', [
            'user_id' => $user->id,
            'type' => 'geography',
            'site_id' => $site->id,
            'payload' => [
                'latitude' => -0.8611,
                'longitude' => 29.2333,
                'accuracy_meters' => 12,
                'geometry_type' => 'polygon',
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => [[
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [[[29.22, -0.86], [29.24, -0.86], [29.24, -0.87], [29.22, -0.87], [29.22, -0.86]]],
                        ],
                    ]],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stored', true);

        $this->assertDatabaseHas('mobile_collection_submissions', [
            'site_id' => $site->id,
            'type' => 'geography',
        ]);
    }
}
