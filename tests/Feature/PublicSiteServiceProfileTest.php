<?php

namespace Tests\Feature;

use App\Models\ServiceProfile;
use App\Models\Site;
use App\Models\User;
use App\Models\MobileQuestionnaire;
use App\Models\MobileQuestionnaireSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicSiteServiceProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('territoires', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('province_id')->nullable();
        });
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('territoire_id')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
        });
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('type_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
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
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->unsignedBigInteger('type_site_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
        Schema::create('service_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('date_collecte');
            $table->foreignId('collecteur_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('sante_disponible')->default(false);
            $table->text('sante_observations')->nullable();
            $table->boolean('wash_disponible')->default(false);
            $table->integer('wash_points_eau')->nullable();
            $table->text('wash_observations')->nullable();
            $table->string('statut')->default('brouillon');
            $table->text('notes_generales')->nullable();
            $table->json('groupes_collectes')->nullable();
            $table->timestamps();
        });
        Schema::create('mobile_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('title');
            $table->integer('version');
            $table->boolean('is_active')->default(true);
            $table->json('survey');
            $table->json('choices')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        Schema::create('mobile_questionnaire_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id');
            $table->foreignId('user_id');
            $table->foreignId('site_id');
            $table->date('date_collecte');
            $table->json('answers');
            $table->string('status');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
        Schema::create('profile_question_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('questionnaire_id');
            $table->json('visible_question_keys');
            $table->timestamps();
            $table->unique(['user_id', 'questionnaire_id']);
        });
    }

    public function test_profile_displays_every_group_and_defaults_to_latest_collection_period(): void
    {
        [$site, $collector] = $this->createSiteAndCollector();

        ServiceProfile::create([
            'site_id' => $site->id,
            'collecteur_id' => $collector->id,
            'date_collecte' => '2026-07-15',
            'sante_disponible' => true,
            'sante_observations' => 'Observation santé juillet',
            'statut' => 'soumis',
            'groupes_collectes' => ['sante'],
        ]);
        ServiceProfile::create([
            'site_id' => $site->id,
            'collecteur_id' => $collector->id,
            'date_collecte' => '2026-08-20',
            'wash_disponible' => true,
            'wash_points_eau' => 4,
            'wash_observations' => 'Observation WASH août',
            'statut' => 'valide',
            'groupes_collectes' => ['wash'],
        ]);

        $response = $this->get("/profil-site/{$site->id}");

        $response->assertOk()
            ->assertSee('Observation WASH août')
            ->assertDontSee('Observation santé juillet')
            ->assertSee('Santé')
            ->assertSee('Éducation')
            ->assertSee('Eau, hygiène et assainissement (WASH)')
            ->assertSee('Environnement')
            ->assertSee('Abri et AME')
            ->assertSee('Gestion et coordination')
            ->assertSee('Données pas encore collectées');
    }

    public function test_profile_can_display_an_older_collection_period(): void
    {
        [$site, $collector] = $this->createSiteAndCollector();

        foreach ([
            ['2026-07-15', 'Observation santé juillet'],
            ['2026-08-20', 'Observation santé août'],
        ] as [$date, $observation]) {
            ServiceProfile::create([
                'site_id' => $site->id,
                'collecteur_id' => $collector->id,
                'date_collecte' => $date,
                'sante_disponible' => true,
                'sante_observations' => $observation,
                'statut' => 'soumis',
                'groupes_collectes' => ['sante'],
            ]);
        }

        $this->get("/profil-site/{$site->id}?periode=2026-07-15")
            ->assertOk()
            ->assertSee('Observation santé juillet')
            ->assertDontSee('Observation santé août');
    }

    public function test_profile_uses_questionnaire_main_groups_and_synchronized_answers(): void
    {
        [$site, $collector] = $this->createSiteAndCollector();
        $questionnaire = MobileQuestionnaire::create([
            'code' => 'service-cartography',
            'title' => 'Cartographie des services',
            'version' => 1,
            'is_active' => true,
            'survey' => [
                ['type' => 'begin_group', 'name' => 'gestion', 'label_fr' => '1. Gestion du site'],
                ['type' => 'text', 'name' => 'gestionnaire', 'label_fr' => 'Nom complet du gestionnaire'],
                ['type' => 'end_group', 'name' => 'gestion'],
                ['type' => 'begin_group', 'name' => 'wash', 'label_fr' => '2. WASH'],
                ['type' => 'integer', 'name' => 'points_eau', 'label_fr' => 'Nombre de points d’eau'],
                ['type' => 'decimal', 'name' => 'litres_personne', 'label_fr' => 'Litres par personne et par jour'],
                ['type' => 'end_group', 'name' => 'wash'],
            ],
            'choices' => [],
            'settings' => [],
        ]);
        MobileQuestionnaireSubmission::create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $collector->id,
            'site_id' => $site->id,
            'date_collecte' => '2026-08-23',
            'answers' => [
                'gestionnaire' => 'Gestionnaire Mushwago',
                'points_eau' => 4,
                'litres_personne' => 12,
            ],
            'status' => 'submitted',
            'synced_at' => now(),
        ]);

        $this->get("/profil-site/{$site->id}")
            ->assertOk()
            ->assertSee('1. Gestion du site')
            ->assertDontSee('Gestionnaire Mushwago')
            ->assertSee('2. WASH')
            ->assertSee('Nombre de points d’eau')
            ->assertSee('Écart au repère')
            ->assertSee('Sphere – Approvisionnement en eau 2.1')
            ->assertSee('suivent le questionnaire');
    }

    public function test_authenticated_user_can_personalize_visible_questions(): void
    {
        [$site, $collector] = $this->createSiteAndCollector();
        $questionnaire = MobileQuestionnaire::create([
            'code' => 'service-cartography',
            'title' => 'Cartographie des services',
            'version' => 1,
            'is_active' => true,
            'survey' => [
                ['type' => 'begin_group', 'name' => 'gestion', 'label_fr' => 'Gestion du site'],
                ['type' => 'text', 'name' => 'gestionnaire', 'label_fr' => 'Nom complet du gestionnaire'],
                ['type' => 'integer', 'name' => 'comites', 'label_fr' => 'Nombre de comités fonctionnels'],
                ['type' => 'end_group', 'name' => 'gestion'],
            ],
            'choices' => [],
            'settings' => [],
        ]);
        MobileQuestionnaireSubmission::create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $collector->id,
            'site_id' => $site->id,
            'date_collecte' => '2026-08-24',
            'answers' => [
                'gestionnaire' => 'Gestionnaire personnalisé',
                'comites' => 3,
            ],
            'status' => 'submitted',
            'synced_at' => now(),
        ]);

        $this->actingAs($collector)
            ->post(route('public.site.questions.update', $site), [
                'question_keys' => ['gestionnaire'],
                'periode' => '2026-08-24',
            ])
            ->assertRedirect(route('public.site.show', [
                'site' => $site,
                'periode' => '2026-08-24',
            ]));

        $this->actingAs($collector)
            ->get(route('public.site.show', $site))
            ->assertOk()
            ->assertSee('Gestionnaire personnalisé')
            ->assertSee('data-profile-question="gestionnaire"', false)
            ->assertDontSee('data-profile-question="comites"', false);

        $this->assertDatabaseHas('profile_question_preferences', [
            'user_id' => $collector->id,
            'questionnaire_id' => $questionnaire->id,
        ]);
    }

    public function test_guest_sees_a_concise_profile_and_login_prompt_for_personalization(): void
    {
        [$site, $collector] = $this->createSiteAndCollector();
        $survey = [
            ['type' => 'begin_group', 'name' => 'wash', 'label_fr' => 'WASH'],
        ];
        $answers = [];

        foreach (range(1, 6) as $index) {
            $survey[] = [
                'type' => 'integer',
                'name' => "indicateur_{$index}",
                'label_fr' => "Nombre de services disponibles {$index}",
            ];
            $answers["indicateur_{$index}"] = $index;
        }

        $survey[] = ['type' => 'end_group', 'name' => 'wash'];
        $questionnaire = MobileQuestionnaire::create([
            'code' => 'service-cartography',
            'title' => 'Cartographie des services',
            'version' => 1,
            'is_active' => true,
            'survey' => $survey,
            'choices' => [],
            'settings' => [],
        ]);
        MobileQuestionnaireSubmission::create([
            'questionnaire_id' => $questionnaire->id,
            'user_id' => $collector->id,
            'site_id' => $site->id,
            'date_collecte' => '2026-08-24',
            'answers' => $answers,
            'status' => 'submitted',
            'synced_at' => now(),
        ]);

        $response = $this->get(route('public.site.show', $site));

        $response->assertOk()
            ->assertSee('Vue synthétique et repères internationaux')
            ->assertSee('Se connecter pour personnaliser');
        $this->assertSame(4, substr_count($response->getContent(), 'data-profile-question='));
    }

    private function createSiteAndCollector(): array
    {
        $collector = User::factory()->create();
        $site = Site::create([
            'nom' => 'Site test services',
            'code_site' => 'SERVICES-1',
        ]);

        return [$site, $collector];
    }
}
