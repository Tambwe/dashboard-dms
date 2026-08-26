<?php

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Models\Site;
use App\Models\SiteMouvementPopulation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('mobile_push_notification_deliveries');
        Schema::dropIfExists('mobile_push_notifications');
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('site_user_access');
        Schema::dropIfExists('site_mouvements_population');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('device_uuid')->unique();
            $table->string('expo_push_token')->nullable()->unique();
            $table->string('device_name')->nullable();
            $table->string('platform', 20);
            $table->string('app_version', 50)->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamp('last_login_at');
            $table->timestamp('last_notification_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code_site')->nullable();
            $table->date('date_mise_a_jour')->nullable();
            $table->timestamps();
        });

        Schema::create('site_user_access', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_collect')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamps();
        });

        Schema::create('site_mouvements_population', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->date('date_mouvement');
            $table->string('type_mouvement');
            $table->integer('menages')->default(0);
            $table->integer('individus')->default(0);
            $table->integer('f_0_5')->default(0);
            $table->integer('f_6_17')->default(0);
            $table->integer('f_18_59')->default(0);
            $table->integer('f_60_plus')->default(0);
            $table->integer('h_0_5')->default(0);
            $table->integer('h_6_17')->default(0);
            $table->integer('h_18_59')->default(0);
            $table->integer('h_60_plus')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->string('statut')->default('en_attente');
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tokenable_type', 'tokenable_id']);
        });

        Schema::create('mobile_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->string('title', 100);
            $table->text('body');
            $table->json('data')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_push_notification_id');
            $table->unsignedBigInteger('mobile_device_id');
            $table->string('token_snapshot');
            $table->string('status', 20)->default('pending');
            $table->string('ticket_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_authenticated_mobile_user_can_register_a_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['mobile-device:register']);

        $response = $this->postJson('/api/mobile/devices', [
            'device_uuid' => 'a5b86154-6a30-4b5c-a9a6-26ff8c614aae',
            'expo_push_token' => 'ExponentPushToken[test-token-123]',
            'device_name' => 'Pixel Test',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'notifications_enabled' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notifications_enabled', true);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $user->id,
            'device_uuid' => 'a5b86154-6a30-4b5c-a9a6-26ff8c614aae',
            'notifications_enabled' => true,
        ]);
    }

    public function test_native_login_returns_a_device_registration_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-login@example.org',
            'password' => Hash::make('mobile-password'),
        ]);

        $response = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'mobile-password',
            'device_uuid' => 'f9ae8f84-7e89-4f41-90b6-c8ddc36fb0e5',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['api_token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mobile-device:f9ae8f84-7e89-4f41-90b6-c8ddc36fb0e5',
        ]);
    }

    public function test_super_admin_can_send_to_selected_device(): void
    {
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => [[
                    'status' => 'ok',
                    'id' => 'expo-ticket-1',
                ]],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $device = MobileDevice::create([
            'user_id' => $admin->id,
            'device_uuid' => '0eaaa1fb-3b53-48aa-b944-5fbe7d631f5c',
            'expo_push_token' => 'ExponentPushToken[test-token-456]',
            'device_name' => 'Samsung Test',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'notifications_enabled' => true,
            'last_login_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.mobile-notifications.send'), [
            'title' => 'Campagne disponible',
            'body' => 'Une nouvelle campagne est prête.',
            'device_ids' => [$device->id],
        ]);

        $response
            ->assertRedirect(route('admin.mobile-notifications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mobile_push_notifications', [
            'created_by' => $admin->id,
            'sent_count' => 1,
            'failed_count' => 0,
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('mobile_push_notification_deliveries', [
            'mobile_device_id' => $device->id,
            'status' => 'sent',
            'ticket_id' => 'expo-ticket-1',
        ]);
        Http::assertSentCount(1);
    }

    public function test_validation_notifies_only_eligible_collectors_assigned_to_the_site(): void
    {
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'validation-ticket']],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $collector = User::factory()->create();
        $unassignedUser = User::factory()->create();
        $disabledCollector = User::factory()->create();
        $site = Site::create(['nom' => 'Bandale', 'code_site' => 'BAN-001']);
        $site->assignedUsers()->attach([
            $collector->id => ['can_collect' => true],
            $disabledCollector->id => ['can_collect' => true],
        ]);

        $recipientDevice = $this->createDevice($collector, 'ExponentPushToken[assigned]');
        $this->createDevice($unassignedUser, 'ExponentPushToken[unassigned]');
        $this->createDevice($disabledCollector, 'ExponentPushToken[disabled]', false);
        $movement = $this->createMovement($site, $admin);

        $this->actingAs($admin)
            ->postJson(route('admin.mouvements.validate', $movement->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $notification = MobilePushNotification::firstOrFail();
        $this->assertSame('Mouvement validé', $notification->title);
        $this->assertSame('valide', $notification->data['status']);
        $this->assertSame($movement->id, $notification->data['movement_id']);
        $this->assertDatabaseHas('mobile_push_notification_deliveries', [
            'mobile_device_id' => $recipientDevice->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseCount('mobile_push_notification_deliveries', 1);
        Http::assertSentCount(1);
    }

    public function test_rejection_notification_contains_the_reason(): void
    {
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'rejection-ticket']],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $collector = User::factory()->create();
        $site = Site::create(['nom' => 'Bandale']);
        $site->assignedUsers()->attach($collector->id, ['can_collect' => true]);
        $this->createDevice($collector, 'ExponentPushToken[rejected]');
        $movement = $this->createMovement($site, $admin);

        $this->actingAs($admin)
            ->postJson(route('admin.mouvements.reject', $movement->id), [
                'rejection_reason' => 'Les chiffres doivent être vérifiés.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $notification = MobilePushNotification::firstOrFail();
        $this->assertSame('Mouvement non validé', $notification->title);
        $this->assertSame('rejete', $notification->data['status']);
        $this->assertStringContainsString(
            'Les chiffres doivent être vérifiés.',
            $notification->body
        );
    }

    private function createDevice(
        User $user,
        string $token,
        bool $notificationsEnabled = true
    ): MobileDevice {
        return MobileDevice::create([
            'user_id' => $user->id,
            'device_uuid' => fake()->uuid(),
            'expo_push_token' => $token,
            'platform' => 'android',
            'notifications_enabled' => $notificationsEnabled,
            'last_login_at' => now(),
        ]);
    }

    private function createMovement(Site $site, User $creator): SiteMouvementPopulation
    {
        return SiteMouvementPopulation::create([
            'site_id' => $site->id,
            'date_mouvement' => '2026-01-15',
            'type_mouvement' => 'recensement',
            'menages' => 25,
            'individus' => 100,
            'created_by' => $creator->id,
            'statut' => 'en_attente',
        ]);
    }
}
