<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileAppDownloadController extends Controller
{
    private const APK_PATH = 'mobile/dashboard-dms.apk';

    public function __invoke(): StreamedResponse
    {
        abort_unless(
            Storage::disk('local')->exists(self::APK_PATH),
            404,
            "L'application mobile n'est pas encore disponible."
        );

        return Storage::disk('local')->download(
            self::APK_PATH,
            'DMS-CCCM.apk',
            ['Content-Type' => 'application/vnd.android.package-archive']
        );
    }
}
