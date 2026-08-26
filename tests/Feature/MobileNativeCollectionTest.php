<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\MobileQuestionnaire;
use App\Models\User;
use App\Services\MobileSiteAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileNativeCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('mobile_questionnaire_submissions');
        Schema::dropIfExists('mobile_questionnaires');
        Schema::dropIfExists('site_mouvements_population');
        Schema::dropIfExists('mobile_collection_submissions');
        Schema::dropIfExists('site_user_access');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_site')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->unsignedBigInteger('type_site_id')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->unsignedBigInteger('gestionnaire_id')->nullable();
            $table->unsignedBigInteger('coordinateur_id')->nullable();
            $table->unsignedBigInteger('categorie_site_id')->nullable();
            $table->string('province')->nullable();
            $table->string('code_province')->nullable();
            $table->string('territoire')->nullable();
            $table->string('code_territoire')->nullable();
            $table->string('zone_sante')->nullable();
            $table->string('code_zone_sante')->nullable();
            $table->string('aire_sante')->nullable();
            $table->string('code_aire_sante')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('source')->nullable();
            $table->string('round')->nullable();
            $table->string('type_gestion')->nullable();
            $table->date('date_mise_a_jour')->nullable();
            $table->string('type_fichier')->nullable();
            $table->string('geometry_type')->nullable();
            $table->json('geojson_data')->nullable();
            $table->decimal('collection_accuracy_m', 10, 2)->nullable();
            $table->timestamp('geometry_collected_at')->nullable();
            $table->date('date_fermeture')->nullable();
            $table->string('raison_fermeture')->nullable();
            $table->text('commentaire_fermeture')->nullable();
            $table->string('document_fermeture')->nullable();
            $table->timestamps();
        });

        Schema::create('site_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_collect')->default(true);
            $table->timestamp('granted_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'site_id']);
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

        Schema::create('mobile_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->json('survey');
            $table->json('choices')->nullable();
            $table->json('settings')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_questionnaire_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('mobile_questionnaires')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('territoire_id')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_collecte')->nullable();
            $table->json('answers');
            $table->string('status')->default('submitted');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('site_geographies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('mobile_collection_submission_id')->nullable()->constrained('mobile_collection_submissions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('geometry_type')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->string('point_category')->nullable();
            $table->string('point_category_other')->nullable();
            $table->string('polygon_category')->nullable();
            $table->string('polygon_block_name')->nullable();
            $table->json('geojson_data')->nullable();
            $table->json('polygon_segment_distances_m')->nullable();
            $table->decimal('polygon_segment_min_m', 10, 2)->nullable();
            $table->decimal('polygon_segment_max_m', 10, 2)->nullable();
            $table->decimal('polygon_segment_avg_m', 10, 2)->nullable();
            $table->decimal('polygon_perimeter_m', 10, 2)->nullable();
            $table->unsignedInteger('polygon_point_count')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->string('source')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('site_mouvements_population', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->date('date_mouvement');
            $table->string('type_mouvement');
            $table->string('periode')->nullable();
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
                $table->unsignedInteger($field)->default(0);
            }
            $table->text('raison')->nullable();
            $table->string('source')->nullable();
            $table->string('round')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });
    }

    public function test_native_sector_payload_can_be_saved(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        Sanctum::actingAs($user);
        $site = Site::create([
            'nom' => 'Site de test',
            'code_site' => 'SITE-TEST-1',
            'organisation_id' => 10,
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
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        Sanctum::actingAs($user);
        $site = Site::create([
            'nom' => 'Site géo',
            'code_site' => 'SITE-GEO-1',
            'organisation_id' => 10,
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

    public function test_explicitly_assigned_external_site_can_be_used(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        $site = Site::create([
            'nom' => 'Site attribué',
            'code_site' => 'SITE-ASSIGNED',
            'organisation_id' => 20,
        ]);
        $user->assignedSites()->attach($site->id, [
            'can_edit' => false,
            'can_collect' => true,
            'granted_by' => $user->id,
            'granted_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/save', [
            'user_id' => 999999,
            'type' => 'sector',
            'site_id' => $site->id,
            'payload' => ['date_collecte' => '2026-08-20'],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('mobile_collection_submissions', [
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);
    }

    public function test_mobile_site_list_is_limited_to_organisation_and_explicit_access(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        $organisationSite = Site::create([
            'nom' => 'Site organisation',
            'organisation_id' => 10,
        ]);
        $assignedSite = Site::create([
            'nom' => 'Site attribué externe',
            'organisation_id' => 20,
        ]);
        $hiddenSite = Site::create([
            'nom' => 'Site externe masqué',
            'organisation_id' => 20,
        ]);
        $user->assignedSites()->attach($assignedSite->id, [
            'can_collect' => true,
            'granted_by' => $user->id,
            'granted_at' => now(),
        ]);

        $siteIds = app(MobileSiteAccessService::class)
            ->accessibleSitesQuery($user)
            ->pluck('sites.id')
            ->all();

        $this->assertContains($organisationSite->id, $siteIds);
        $this->assertContains($assignedSite->id, $siteIds);
        $this->assertNotContains($hiddenSite->id, $siteIds);
    }

    public function test_external_site_without_access_is_rejected_even_with_spoofed_user_id(): void
    {
        $collector = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        $otherUser = User::factory()->create(['organisation_id' => 20, 'role' => 'user']);
        $site = Site::create([
            'nom' => 'Site externe',
            'code_site' => 'SITE-EXTERNAL',
            'organisation_id' => 20,
        ]);
        $otherUser->assignedSites()->attach($site->id, [
            'can_collect' => true,
            'granted_by' => $otherUser->id,
            'granted_at' => now(),
        ]);
        Sanctum::actingAs($collector);

        $this->postJson('/api/mobile/save', [
            'user_id' => $otherUser->id,
            'type' => 'sector',
            'site_id' => $site->id,
            'payload' => ['date_collecte' => '2026-08-20'],
        ])->assertForbidden();

        $this->assertDatabaseCount('mobile_collection_submissions', 0);
    }

    public function test_new_mobile_site_is_linked_to_collectors_organisation_and_access(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        Sanctum::actingAs($user);
        MobileQuestionnaire::create([
            'code' => 'service-cartography',
            'title' => 'Cartographie des services',
            'version' => 1,
            'is_active' => true,
            'survey' => [],
            'choices' => [],
            'settings' => [],
        ]);

        $response = $this->postJson('/api/mobile/questionnaire/submit', [
            'user_id' => 999999,
            'questionnaire_code' => 'service-cartography',
            'is_new_site' => true,
            'new_site' => ['nom' => 'Nouveau site mobile'],
            'answers' => [],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $site = Site::query()->where('nom', 'Nouveau site mobile')->firstOrFail();

        $this->assertSame(10, (int) $site->organisation_id);
        $this->assertDatabaseHas('site_user_access', [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'can_edit' => true,
            'can_collect' => true,
        ]);
        $this->assertDatabaseHas('mobile_questionnaire_submissions', [
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);
    }

    public function test_new_geography_site_uses_top_level_geometry_coordinates(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/sync', [
            'user_id' => $user->id,
            'records' => [[
                'id' => 'geo-new-site',
                'type' => 'geography',
                'site_id' => 0,
                'payload' => [
                    'is_new_site' => true,
                    'new_site' => [
                        'nom' => 'Nouveau site géographique',
                        'code_site' => 'GEO-NEW-1',
                        'menages' => 25,
                        'individus' => 100,
                        'f_0_5' => 10,
                        'f_6_17' => 15,
                        'f_18_59' => 20,
                        'f_60_plus' => 5,
                        'h_0_5' => 10,
                        'h_6_17' => 15,
                        'h_18_59' => 20,
                        'h_60_plus' => 5,
                    ],
                    'latitude' => -0.8611,
                    'longitude' => 29.2333,
                    'accuracy_meters' => 12,
                    'geometry_type' => 'polygon',
                    'geojson' => [
                        'type' => 'FeatureCollection',
                        'features' => [
                            [
                                'type' => 'Feature',
                                'properties' => ['geometry_type' => 'polygon'],
                                'geometry' => [
                                    'type' => 'Polygon',
                                    'coordinates' => [[[29.22, -0.86], [29.24, -0.86], [29.24, -0.87], [29.22, -0.87], [29.22, -0.86]]],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $site = Site::query()->where('nom', 'Nouveau site géographique')->firstOrFail();

        $this->assertSame('polygon', $site->geometry_type);
        $this->assertSame('-0.86110000', (string) $site->latitude);
        $this->assertSame('29.23330000', (string) $site->longitude);
        $this->assertDatabaseHas('site_user_access', [
            'user_id' => $user->id,
            'site_id' => $site->id,
            'can_collect' => true,
            'can_edit' => true,
        ]);
        $this->assertDatabaseHas('site_mouvements_population', [
            'site_id' => $site->id,
            'type_mouvement' => 'recensement',
            'menages' => 25,
            'individus' => 100,
            'statut' => 'en_attente',
            'source' => 'mobile_geography',
        ]);
    }

    public function test_geography_sync_rejects_accuracy_above_fifteen_meters(): void
    {
        $user = User::factory()->create(['organisation_id' => 10, 'role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/sync', [
            'user_id' => $user->id,
            'records' => [[
                'id' => 'geo-low-accuracy',
                'type' => 'geography',
                'site_id' => 0,
                'payload' => [
                    'is_new_site' => true,
                    'new_site' => [
                        'nom' => 'Site GPS imprécis',
                        'menages' => 1,
                        'individus' => 1,
                        'f_0_5' => 1,
                        'f_6_17' => 0,
                        'f_18_59' => 0,
                        'f_60_plus' => 0,
                        'h_0_5' => 0,
                        'h_6_17' => 0,
                        'h_18_59' => 0,
                        'h_60_plus' => 0,
                    ],
                    'accuracy_meters' => 16,
                    'geometry_type' => 'polygon',
                    'geojson' => [
                        'type' => 'FeatureCollection',
                        'features' => [[
                            'type' => 'Feature',
                            'geometry' => [
                                'type' => 'Polygon',
                                'coordinates' => [[[29.22, -0.86], [29.24, -0.86], [29.24, -0.87]]],
                            ],
                        ]],
                    ],
                ],
            ]],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('failed', 1);
        $this->assertDatabaseMissing('sites', ['nom' => 'Site GPS imprécis']);
    }
}
