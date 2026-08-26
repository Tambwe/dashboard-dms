<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Services\ExpoPushNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $devicesQuery = MobileDevice::query()
            ->with(['user:id,name,email,organisation_id', 'user.organisation:id,name']);
        $this->scopeForAdmin($devicesQuery, $request);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $devicesQuery->where(function (Builder $query) use ($search) {
                $query
                    ->where('device_name', 'like', "%{$search}%")
                    ->orWhere('platform', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $request->input('status') === 'active'
                ? $devicesQuery->eligible()
                : $devicesQuery->where(function (Builder $query) {
                    $query
                        ->where('notifications_enabled', false)
                        ->orWhereNull('expo_push_token');
                });
        }

        $devices = $devicesQuery
            ->orderByDesc('last_login_at')
            ->paginate(50)
            ->withQueryString();

        $historyQuery = MobilePushNotification::query()
            ->with('creator:id,name,organisation_id')
            ->orderByDesc('id');
        if (!$request->user()->isSuperAdmin()) {
            $historyQuery->where('created_by', $request->user()->id);
        }

        $history = $historyQuery->limit(20)->get();

        return view('admin.mobile-notifications.index', compact('devices', 'history'));
    }

    public function send(Request $request, ExpoPushNotificationService $pushService): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1000'],
            'device_ids' => ['required', 'array', 'min:1'],
            'device_ids.*' => ['integer', 'distinct'],
        ]);

        $requestedIds = collect($data['device_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $devicesQuery = MobileDevice::query()
            ->whereIn('id', $requestedIds)
            ->eligible();
        $this->scopeForAdmin($devicesQuery, $request);
        $devices = $devicesQuery->get();

        if ($devices->count() !== $requestedIds->count()) {
            return back()
                ->withInput()
                ->withErrors([
                    'device_ids' => 'Un ou plusieurs appareils sélectionnés sont inactifs ou hors de votre organisation.',
                ]);
        }

        $notification = MobilePushNotification::create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'recipient_count' => $devices->count(),
            'status' => 'sending',
        ]);

        $result = $pushService->send($notification, $devices);

        if ($result['failed'] > 0) {
            return redirect()
                ->route('admin.mobile-notifications.index')
                ->with(
                    'warning',
                    "{$result['sent']} notification(s) envoyée(s), {$result['failed']} échec(s). Consultez l’historique."
                );
        }

        return redirect()
            ->route('admin.mobile-notifications.index')
            ->with('success', "{$result['sent']} notification(s) envoyée(s) avec succès.");
    }

    private function scopeForAdmin(Builder $query, Request $request): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }

        $query->whereHas('user', function (Builder $userQuery) use ($request) {
            $userQuery->where('organisation_id', $request->user()->organisation_id);
        });
    }
}
