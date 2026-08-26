<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileAppDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_download_mobile_application(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mobile/dashboard-dms.apk', 'apk-content');

        $response = $this->get(route('mobile.apk.download'));

        $response
            ->assertOk()
            ->assertDownload('DMS-CCCM.apk')
            ->assertHeader('content-type', 'application/vnd.android.package-archive');
    }

    public function test_authenticated_user_can_download_mobile_application(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mobile/dashboard-dms.apk', 'apk-content');

        $response = $this
            ->actingAs(User::factory()->create())
            ->get(route('mobile.apk.download'));

        $response
            ->assertOk()
            ->assertDownload('DMS-CCCM.apk')
            ->assertHeader('content-type', 'application/vnd.android.package-archive');
    }
}
