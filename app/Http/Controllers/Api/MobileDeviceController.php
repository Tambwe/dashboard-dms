<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->tokenCan('mobile-device:register')) {
            abort(403, 'Ce jeton ne peut pas enregistrer un appareil.');
        }

        $data = $request->validate([
            'device_uuid' => ['required', 'uuid'],
            'expo_push_token' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(ExponentPushToken|ExpoPushToken)\[[A-Za-z0-9_-]+\]$/',
            ],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'notifications_enabled' => ['required', 'boolean'],
        ]);

        $pushToken = $data['expo_push_token'] ?? null;
        $notificationsEnabled = (bool) $data['notifications_enabled'] && $pushToken !== null;

        $device = DB::transaction(function () use ($request, $data, $pushToken, $notificationsEnabled) {
            if ($pushToken) {
                MobileDevice::query()
                    ->where('expo_push_token', $pushToken)
                    ->where('device_uuid', '!=', $data['device_uuid'])
                    ->update([
                        'expo_push_token' => null,
                        'notifications_enabled' => false,
                    ]);
            }

            return MobileDevice::query()->updateOrCreate(
                ['device_uuid' => $data['device_uuid']],
                [
                    'user_id' => $request->user()->id,
                    'expo_push_token' => $pushToken,
                    'device_name' => $data['device_name'] ?? null,
                    'platform' => $data['platform'],
                    'app_version' => $data['app_version'] ?? null,
                    'notifications_enabled' => $notificationsEnabled,
                    'last_login_at' => now(),
                    'last_error' => $notificationsEnabled ? null : 'Permission de notification absente ou jeton indisponible.',
                ]
            );
        });

        return response()->json([
            'success' => true,
            'device_id' => $device->id,
            'notifications_enabled' => $device->notifications_enabled,
            'message' => $device->notifications_enabled
                ? 'Appareil enregistré pour les notifications.'
                : 'Appareil enregistré sans notification active.',
        ]);
    }
}
